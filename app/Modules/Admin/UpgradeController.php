<?php
declare(strict_types=1);

namespace App\Modules\Admin;

use App\Core\Auth\Csrf;
use App\Core\Controller;
use App\Core\Response;
use App\Core\Upgrade\UpgradeManager;

class UpgradeController extends Controller
{
    public function index(): Response
    {
        $logs = $this->db()->all('SELECT * FROM upgrade_logs ORDER BY id DESC LIMIT 20');
        return $this->render('admin.upgrades', compact('logs'));
    }

    public function run(): Response
    {
        Csrf::validate($this->request->input('_csrf'));
        if (!isset($this->request->files['upgrade_zip']) || $this->request->files['upgrade_zip']['error'] !== UPLOAD_ERR_OK) {
            return new Response(view('admin.upgrades', ['error' => 'Please upload a valid package.', 'logs' => []]), 422);
        }

        $tmpPath = $this->request->files['upgrade_zip']['tmp_name'];
        $result = $this->container->get(UpgradeManager::class)->run($tmpPath);
        $logs = $this->db()->all('SELECT * FROM upgrade_logs ORDER BY id DESC LIMIT 20');
        return new Response(view('admin.upgrades', ['success' => 'Upgrade completed.', 'result' => $result, 'logs' => $logs]));
    }
}
