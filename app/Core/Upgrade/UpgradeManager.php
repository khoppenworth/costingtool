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

    public function run(string $uploadedZipPath): array
    {
        $current = $this->db->one('SELECT version_code FROM application_versions ORDER BY id DESC LIMIT 1');
        $currentVersion = $current['version_code'] ?? '0.0.0';

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

        MaintenanceMode::enable();
        try {
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
                'details_json' => json_encode($manifest, JSON_UNESCAPED_UNICODE),
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            return ['status' => 'success', 'manifest' => $manifest];
        } catch (\Throwable $e) {
            $this->db->insert('upgrade_logs', [
                'package_id' => $manifest['package_id'] ?? 'unknown',
                'from_version' => $manifest['from_version'] ?? $currentVersion,
                'to_version' => $manifest['to_version'] ?? $currentVersion,
                'operator_user_id' => $_SESSION['user_id'] ?? null,
                'result_status' => 'failed',
                'details_json' => json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            throw $e;
        } finally {
            MaintenanceMode::disable();
        }
    }
}
