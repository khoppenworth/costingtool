<?php
declare(strict_types=1);

namespace App\Modules\Admin;

use App\Core\Controller;
use App\Core\Response;
use App\Core\Upgrade\UpgradeManager;

class UpgradeController extends Controller
{
    public function index(): Response
    {
        $logs = $this->db()->all('SELECT * FROM upgrade_logs ORDER BY id DESC LIMIT 20');
        $currentVersion = $this->db()->one('SELECT version_code FROM application_versions ORDER BY id DESC LIMIT 1')['version_code'] ?? '0.0.0';

        return $this->render('admin.upgrades', [
            'logs' => $logs,
            'currentVersion' => $currentVersion,
        ]);
    }

    public function run(): Response
    {
        $logs = $this->db()->all('SELECT * FROM upgrade_logs ORDER BY id DESC LIMIT 20');
        $currentVersion = $this->db()->one('SELECT version_code FROM application_versions ORDER BY id DESC LIMIT 1')['version_code'] ?? '0.0.0';
        $action = (string) $this->request->input('action', 'upload');

        try {
            $manager = $this->container->get(UpgradeManager::class);

            if ($action === 'check') {
                $releaseInfo = $manager->checkForGithubUpdate();
                return $this->render('admin.upgrades', [
                    'logs' => $logs,
                    'currentVersion' => $currentVersion,
                    'releaseInfo' => $releaseInfo,
                    'success' => $releaseInfo['has_update']
                        ? 'New version found on GitHub.'
                        : 'You are already on the latest version.',
                ]);
            }

            if ($action === 'run_github') {
                $result = $manager->runFromGithubLatest();
                $logs = $this->db()->all('SELECT * FROM upgrade_logs ORDER BY id DESC LIMIT 20');
                $currentVersion = $this->db()->one('SELECT version_code FROM application_versions ORDER BY id DESC LIMIT 1')['version_code'] ?? $currentVersion;

                return $this->render('admin.upgrades', [
                    'logs' => $logs,
                    'currentVersion' => $currentVersion,
                    'success' => 'Upgrade completed successfully from latest GitHub release.',
                    'result' => $result,
                ]);
            }

            if (!isset($this->request->files['upgrade_zip']) || $this->request->files['upgrade_zip']['error'] !== UPLOAD_ERR_OK) {
                return new Response(view('admin.upgrades', [
                    'error' => 'Please upload a valid package or use GitHub upgrade.',
                    'logs' => $logs,
                    'currentVersion' => $currentVersion,
                ]), 422);
            }

            $tmpPath = $this->request->files['upgrade_zip']['tmp_name'];
            $result = $manager->run($tmpPath, ['source' => 'manual_upload']);
            $logs = $this->db()->all('SELECT * FROM upgrade_logs ORDER BY id DESC LIMIT 20');
            $currentVersion = $this->db()->one('SELECT version_code FROM application_versions ORDER BY id DESC LIMIT 1')['version_code'] ?? $currentVersion;

            return $this->render('admin.upgrades', [
                'logs' => $logs,
                'currentVersion' => $currentVersion,
                'success' => 'Upgrade completed from uploaded package.',
                'result' => $result,
            ]);
        } catch (\Throwable $e) {
            return new Response(view('admin.upgrades', [
                'error' => $e->getMessage(),
                'logs' => $logs,
                'currentVersion' => $currentVersion,
            ]), 422);
        }
    }
}
