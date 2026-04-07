<?php ob_start(); ?>
<h1>User Administration</h1>
<?php if (!empty($error)): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

<h2>Create User</h2>
<form method="post" action="/admin/users">
    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
    <label>Username</label><input name="username" required>
    <label>Email</label><input name="email" type="email" required>
    <label>Display name</label><input name="display_name" required>
    <label>Password</label><input name="password" type="password" required>
    <label>Locale</label><select name="locale"><option value="en">English</option><option value="am">Amharic</option></select>
    <label>Roles</label>
    <select name="role_ids[]" multiple size="5"><?php foreach ($roles as $role): ?><option value="<?= e((string) $role['id']) ?>"><?= e($role['name']) ?></option><?php endforeach; ?></select>
    <label>Organization Scope</label>
    <select name="organization_ids[]" multiple size="5"><?php foreach ($organizations as $organization): ?><option value="<?= e((string) $organization['id']) ?>"><?= e($organization['name']) ?></option><?php endforeach; ?></select>
    <button class="btn" type="submit">Create user</button>
</form>

<h2>Existing Users</h2>
<table>
    <tr><th>ID</th><th>Username</th><th>Email</th><th>Roles</th><th>Status</th><th>Actions</th></tr>
    <?php foreach ($users as $user): ?>
        <tr>
            <td><?= e((string) $user['id']) ?></td>
            <td><?= e($user['username']) ?></td>
            <td><?= e($user['email']) ?></td>
            <td><?= e((string) ($user['role_names'] ?? '')) ?></td>
            <td><?= (int) $user['is_active'] === 1 ? 'Active' : 'Inactive' ?></td>
            <td>
                <form method="post" action="/admin/users/<?= e((string) $user['id']) ?>" style="margin-bottom:8px;">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <label>Status</label>
                    <select name="is_active"><option value="1" <?= (int) $user['is_active'] === 1 ? 'selected' : '' ?>>Active</option><option value="0" <?= (int) $user['is_active'] === 0 ? 'selected' : '' ?>>Inactive</option></select>
                    <label>Roles</label>
                    <select name="role_ids[]" multiple size="4"><?php foreach ($roles as $role): ?><option value="<?= e((string) $role['id']) ?>" <?= in_array((int) $role['id'], $userRoleMap[(int) $user['id']] ?? [], true) ? 'selected' : '' ?>><?= e($role['name']) ?></option><?php endforeach; ?></select>
                    <label>Organization Scope</label>
                    <select name="organization_ids[]" multiple size="4"><?php foreach ($organizations as $organization): ?><option value="<?= e((string) $organization['id']) ?>" <?= in_array((int) $organization['id'], $userOrganizationMap[(int) $user['id']] ?? [], true) ? 'selected' : '' ?>><?= e($organization['name']) ?></option><?php endforeach; ?></select>
                    <button class="btn btn-secondary" type="submit">Save</button>
                </form>
                <form method="post" action="/admin/users/<?= e((string) $user['id']) ?>/reset-password">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <label>Reset password</label>
                    <input name="new_password" type="password" minlength="10" required>
                    <button class="btn" type="submit">Reset</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
<?php $content = ob_get_clean(); require base_path('resources/views/layout.php'); ?>
