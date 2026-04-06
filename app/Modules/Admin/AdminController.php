<?php
declare(strict_types=1);

namespace App\Modules\Admin;

use App\Core\Controller;
use App\Core\Response;

class AdminController extends Controller
{
    public function dashboard(): Response
    {
        $stats = [
            'users' => $this->db()->one('SELECT COUNT(*) AS c FROM users')['c'] ?? 0,
            'assessments' => $this->db()->one('SELECT COUNT(*) AS c FROM assessments')['c'] ?? 0,
            'upgrades' => $this->db()->one('SELECT COUNT(*) AS c FROM upgrade_logs')['c'] ?? 0,
            'version' => $this->db()->one('SELECT version_code AS c FROM application_versions ORDER BY id DESC LIMIT 1')['c'] ?? '0.1.0',
        ];
        return $this->render('admin.dashboard', compact('stats'));
    }
}
