<?php
declare(strict_types=1);

namespace App\Core\Upgrade;

use App\Core\Database\DB;
use App\Core\Database\Migrator;
use RuntimeException;
use ZipArchive;

class UpgradeManager
{
    public function __construct(private DB $db, private ManifestValidator $validator)
    {
    }

    public function checkForGithubUpdate(): array
    {
        $release = $this->fetchLatestRelease();
        $currentVersion = $this->currentVersion();
        $latestVersion = ltrim((string) ($release['tag_name'] ?? '0.0.0'), 'vV');

        return [
            'current_version' => $currentVersion,
            'latest_version' => $latestVersion,
            'release_tag' => (string) ($release['tag_name'] ?? ''),
            'release_name' => (string) ($release['name'] ?? ''),
            'release_url' => (string) ($release['html_url'] ?? ''),
            'published_at' => (string) ($release['published_at'] ?? ''),
            'has_update' => version_compare($latestVersion, $currentVersion, '>'),
        ];
    }

    public function runFromGithubLatest(): array
    {
        $release = $this->fetchLatestRelease();
        $currentVersion = $this->currentVersion();
        $latestVersion = ltrim((string) ($release['tag_name'] ?? '0.0.0'), 'vV');

        if (!version_compare($latestVersion, $currentVersion, '>')) {
            throw new RuntimeException('No newer release available for upgrade.');
        }

        $asset = $this->pickReleaseAsset($release);
        $downloadPath = storage_path('upgrades/downloads/' . uniqid('release_', true) . '.zip');
        @mkdir(dirname($downloadPath), 0777, true);

        $binary = $this->httpGet((string) ($asset['browser_download_url'] ?? ''));
        file_put_contents($downloadPath, $binary);

        return $this->run($downloadPath, [
            'release_tag' => (string) ($release['tag_name'] ?? ''),
            'release_name' => (string) ($release['name'] ?? ''),
            'release_url' => (string) ($release['html_url'] ?? ''),
            'asset_name' => (string) ($asset['name'] ?? ''),
        ]);
    }

    public function run(string $uploadedZipPath, array $context = []): array
    {
        $currentVersion = $this->currentVersion();

        $extractDir = storage_path('upgrades/' . uniqid('upgrade_', true));
        @mkdir($extractDir, 0777, true);

        $zip = new ZipArchive();
        if ($zip->open($uploadedZipPath) !== true) {
            throw new RuntimeException('Could not open upgrade package.');
        }
        $zip->extractTo($extractDir);
        $zip->close();

        $manifestPath = $extractDir . '/manifest.json';
        if (!file_exists($manifestPath)) {
            throw new RuntimeException('Upgrade package missing manifest.json.');
        }

        $manifest = json_decode((string) file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);
        $this->validator->validate($manifest, $currentVersion);

        $backupDir = storage_path('backups/' . date('Ymd_His') . '_' . uniqid('upgrade_', false));
        $dbBackupFile = $backupDir . '/db.sql.gz';
        $appBackupFile = $backupDir . '/app.zip';
        $rollbackStatus = 'not-needed';

        MaintenanceMode::enable();
        try {
            @mkdir($backupDir, 0777, true);
            $this->backupDatabase($dbBackupFile);
            $this->backupApplication($appBackupFile);

            $this->deployApplicationFiles($extractDir);

            $migrator = new Migrator($this->db);
            if (is_dir($extractDir . '/migrations')) {
                $migrator->run($extractDir . '/migrations');
            }

            $this->db->insert('application_versions', [
                'version_code' => $manifest['to_version'],
                'package_id' => $manifest['package_id'],
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            $this->db->insert('upgrade_logs', [
                'package_id' => $manifest['package_id'],
                'from_version' => $manifest['from_version'],
                'to_version' => $manifest['to_version'],
                'operator_user_id' => $_SESSION['user_id'] ?? null,
                'result_status' => 'success',
                'details_json' => json_encode([
                    'manifest' => $manifest,
                    'backup_dir' => $backupDir,
                    'db_backup' => $dbBackupFile,
                    'app_backup' => $appBackupFile,
                    'source' => $context,
                ], JSON_UNESCAPED_UNICODE),
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            return ['status' => 'success', 'manifest' => $manifest, 'backup_dir' => $backupDir];
        } catch (\Throwable $e) {
            $rollbackStatus = 'failed';
            try {
                if (file_exists($dbBackupFile) && file_exists($appBackupFile)) {
                    $this->restoreApplicationFromBackup($appBackupFile);
                    $this->restoreDatabase($dbBackupFile);
                    $rollbackStatus = 'success';
                }
            } catch (\Throwable $rollbackError) {
                $rollbackStatus = 'failed: ' . $rollbackError->getMessage();
            }

            $this->db->insert('upgrade_logs', [
                'package_id' => $manifest['package_id'] ?? 'unknown',
                'from_version' => $manifest['from_version'] ?? $currentVersion,
                'to_version' => $manifest['to_version'] ?? $currentVersion,
                'operator_user_id' => $_SESSION['user_id'] ?? null,
                'result_status' => 'failed',
                'details_json' => json_encode([
                    'error' => $e->getMessage(),
                    'rollback_status' => $rollbackStatus,
                    'backup_dir' => $backupDir,
                    'source' => $context,
                ], JSON_UNESCAPED_UNICODE),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            throw $e;
        } finally {
            MaintenanceMode::disable();
        }
    }

    private function currentVersion(): string
    {
        $current = $this->db->one('SELECT version_code FROM application_versions ORDER BY id DESC LIMIT 1');
        return $current['version_code'] ?? '0.0.0';
    }

    private function fetchLatestRelease(): array
    {
        $owner = trim((string) env('UPGRADE_GITHUB_OWNER', ''));
        $repo = trim((string) env('UPGRADE_GITHUB_REPO', ''));

        if ($owner === '' || $repo === '') {
            throw new RuntimeException('GitHub upgrade source is not configured. Set UPGRADE_GITHUB_OWNER and UPGRADE_GITHUB_REPO in .env.');
        }

        $url = sprintf('https://api.github.com/repos/%s/%s/releases/latest', rawurlencode($owner), rawurlencode($repo));
        return $this->httpGetJson($url);
    }

    private function pickReleaseAsset(array $release): array
    {
        $assets = $release['assets'] ?? [];
        if (!is_array($assets) || $assets === []) {
            throw new RuntimeException('Latest release has no downloadable assets.');
        }

        $preferred = trim((string) env('UPGRADE_RELEASE_ASSET', 'upgrade-package.zip'));
        foreach ($assets as $asset) {
            if (($asset['name'] ?? '') === $preferred) {
                return $asset;
            }
        }

        foreach ($assets as $asset) {
            if (str_ends_with(strtolower((string) ($asset['name'] ?? '')), '.zip')) {
                return $asset;
            }
        }

        throw new RuntimeException('No ZIP upgrade asset found in latest GitHub release.');
    }

    private function httpGetJson(string $url): array
    {
        $raw = $this->httpGet($url, 'application/vnd.github+json');
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Unable to decode GitHub response.');
        }
        return $decoded;
    }

    private function httpGet(string $url, string $accept = 'application/octet-stream'): string
    {
        if ($url === '') {
            throw new RuntimeException('Download URL is empty.');
        }

        $headers = [
            'User-Agent: costingtool-upgrader',
            'Accept: ' . $accept,
        ];

        $token = trim((string) env('UPGRADE_GITHUB_TOKEN', ''));
        if ($token !== '') {
            $headers[] = 'Authorization: Bearer ' . $token;
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => implode("\r\n", $headers),
                'ignore_errors' => true,
                'timeout' => 60,
            ],
        ]);

        $raw = file_get_contents($url, false, $context);
        $statusLine = $http_response_header[0] ?? '';
        if ($raw === false || !preg_match('/\s(\d{3})\s/', $statusLine, $m) || (int) $m[1] >= 400) {
            throw new RuntimeException('Unable to fetch GitHub release information or asset.');
        }

        return $raw;
    }

    private function backupDatabase(string $outputFile): void
    {
        $db = config('database');
        $pipeline = sprintf(
            'MYSQL_PWD=%s mysqldump --single-transaction --quick --routines --triggers -h %s -P %s -u %s %s | gzip > %s',
            escapeshellarg((string) ($db['password'] ?? '')),
            escapeshellarg((string) ($db['host'] ?? '127.0.0.1')),
            escapeshellarg((string) ($db['port'] ?? '3306')),
            escapeshellarg((string) ($db['username'] ?? 'root')),
            escapeshellarg((string) ($db['database'] ?? '')),
            escapeshellarg($outputFile)
        );

        $this->runPipelineWithPipefail($pipeline, $output, $exitCode);
        if ($exitCode !== 0 || !file_exists($outputFile) || filesize($outputFile) === 0) {
            throw new RuntimeException('Database backup failed. Ensure mysqldump and gzip are installed for web user.');
        }
    }

    private function restoreDatabase(string $backupFile): void
    {
        $db = config('database');
        $pipeline = sprintf(
            'gunzip -c %s | MYSQL_PWD=%s mysql -h %s -P %s -u %s %s',
            escapeshellarg($backupFile),
            escapeshellarg((string) ($db['password'] ?? '')),
            escapeshellarg((string) ($db['host'] ?? '127.0.0.1')),
            escapeshellarg((string) ($db['port'] ?? '3306')),
            escapeshellarg((string) ($db['username'] ?? 'root')),
            escapeshellarg((string) ($db['database'] ?? ''))
        );

        $this->runPipelineWithPipefail($pipeline, $output, $exitCode);
        if ($exitCode !== 0) {
            throw new RuntimeException('Database restore failed.');
        }
    }

    private function runPipelineWithPipefail(string $pipeline, array &$output, int &$exitCode): void
    {
        $command = '/bin/bash -o pipefail -c ' . escapeshellarg($pipeline) . ' 2>&1';
        exec($command, $output, $exitCode);
    }

    private function backupApplication(string $zipFile): void
    {
        $zip = new ZipArchive();
        if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Unable to create app backup archive.');
        }

        $basePath = base_path();
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($basePath, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $absolutePath = $file->getRealPath();
            if ($absolutePath === false) {
                continue;
            }

            $relativePath = ltrim(str_replace($basePath, '', $absolutePath), DIRECTORY_SEPARATOR);
            if ($this->shouldSkipBackupPath($relativePath)) {
                continue;
            }

            $zip->addFile($absolutePath, str_replace('\\', '/', $relativePath));
        }

        $zip->close();
    }

    private function deployApplicationFiles(string $extractDir): void
    {
        $sourceRoot = is_dir($extractDir . '/app') ? $extractDir . '/app' : $extractDir;
        $items = scandir($sourceRoot);
        if ($items === false) {
            throw new RuntimeException('Unable to read extracted upgrade package.');
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            if (in_array($item, ['manifest.json', 'migrations', 'checksums.txt'], true)) {
                continue;
            }
            if (in_array($item, ['.env', 'storage'], true)) {
                continue;
            }

            $source = $sourceRoot . '/' . $item;
            $destination = base_path($item);
            $this->copyRecursive($source, $destination);
        }
    }

    private function restoreApplicationFromBackup(string $zipFile): void
    {
        $restoreDir = storage_path('upgrades/' . uniqid('restore_', true));
        @mkdir($restoreDir, 0777, true);

        $zip = new ZipArchive();
        if ($zip->open($zipFile) !== true) {
            throw new RuntimeException('Unable to open app backup archive for rollback.');
        }
        $zip->extractTo($restoreDir);
        $zip->close();

        $items = scandir($restoreDir) ?: [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $source = $restoreDir . '/' . $item;
            $destination = base_path($item);
            $this->copyRecursive($source, $destination);
        }
    }

    private function copyRecursive(string $source, string $destination): void
    {
        if (is_file($source)) {
            @mkdir(dirname($destination), 0777, true);
            if (!copy($source, $destination)) {
                throw new RuntimeException('Failed to copy file during upgrade: ' . $destination);
            }
            return;
        }

        if (!is_dir($source)) {
            return;
        }

        @mkdir($destination, 0777, true);
        $entries = scandir($source);
        if ($entries === false) {
            throw new RuntimeException('Unable to read source directory: ' . $source);
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $from = $source . DIRECTORY_SEPARATOR . $entry;
            $to = $destination . DIRECTORY_SEPARATOR . $entry;
            $this->copyRecursive($from, $to);
        }
    }

    private function shouldSkipBackupPath(string $relativePath): bool
    {
        $normalized = str_replace('\\', '/', $relativePath);

        return str_starts_with($normalized, '.git/')
            || str_starts_with($normalized, 'storage/backups/')
            || str_starts_with($normalized, 'storage/upgrades/')
            || str_starts_with($normalized, 'storage/logs/')
            || str_starts_with($normalized, 'storage/cache/');
    }
}
