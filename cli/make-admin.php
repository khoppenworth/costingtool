<?php
declare(strict_types=1);

use App\Core\Database\DB;
use App\Core\Auth\Passwords;

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/bootstrap/helpers.php';

[$script, $username, $email, $password] = array_pad($argv, 4, null);
if (!$username || !$email || !$password) {
    exit("Usage: php cli/make-admin.php username email password\n");
}
$db = DB::make(config('database'));
$userId = $db->insert('users', [
    'username' => $username,
    'email' => $email,
    'password_hash' => Passwords::hash($password),
    'display_name' => $username,
    'is_active' => 1,
    'locale' => 'en',
    'created_at' => date('Y-m-d H:i:s'),
    'updated_at' => date('Y-m-d H:i:s'),
]);
$role = $db->one("SELECT id FROM roles WHERE name = 'Super Admin'");
$db->insert('user_role_assignments', ['user_id' => $userId, 'role_id' => $role['id']]);

echo "Admin created.\n";
