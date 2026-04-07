<?php ob_start(); ?>
<h1>Organization & Facility Management</h1>

<h2>Create Organization</h2>
<form method="post" action="/admin/organizations">
    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
    <label>Name</label><input name="name" required>
    <button class="btn" type="submit">Create organization</button>
</form>

<h2>Organizations</h2>
<table>
    <tr><th>ID</th><th>Name</th><th>Status</th><th>Update</th></tr>
    <?php foreach ($organizations as $organization): ?>
        <tr>
            <td><?= e((string) $organization['id']) ?></td>
            <td><?= e($organization['name']) ?></td>
            <td><?= (int) $organization['is_active'] === 1 ? 'Active' : 'Inactive' ?></td>
            <td>
                <form method="post" action="/admin/organizations/<?= e((string) $organization['id']) ?>">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input name="name" value="<?= e($organization['name']) ?>" required>
                    <select name="is_active"><option value="1" <?= (int) $organization['is_active'] === 1 ? 'selected' : '' ?>>Active</option><option value="0" <?= (int) $organization['is_active'] === 0 ? 'selected' : '' ?>>Inactive</option></select>
                    <button class="btn btn-secondary" type="submit">Save</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

<h2>Create Facility / Hub / Central Unit</h2>
<form method="post" action="/admin/facilities">
    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
    <label>Organization</label>
    <select name="organization_id"><?php foreach ($organizations as $organization): ?><option value="<?= e((string) $organization['id']) ?>"><?= e($organization['name']) ?></option><?php endforeach; ?></select>
    <label>Name</label><input name="name" required>
    <label>Type</label>
    <select name="facility_type">
        <option value="facility">Facility</option>
        <option value="hub">Hub</option>
        <option value="central_unit">Central Unit</option>
    </select>
    <button class="btn" type="submit">Create facility</button>
</form>

<h2>Facilities</h2>
<table>
    <tr><th>ID</th><th>Organization</th><th>Name</th><th>Type</th><th>Status</th><th>Update</th></tr>
    <?php foreach ($facilities as $facility): ?>
        <tr>
            <td><?= e((string) $facility['id']) ?></td>
            <td><?= e($facility['organization_name']) ?></td>
            <td><?= e($facility['name']) ?></td>
            <td><?= e($facility['facility_type']) ?></td>
            <td><?= (int) $facility['is_active'] === 1 ? 'Active' : 'Inactive' ?></td>
            <td>
                <form method="post" action="/admin/facilities/<?= e((string) $facility['id']) ?>">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <select name="organization_id"><?php foreach ($organizations as $organization): ?><option value="<?= e((string) $organization['id']) ?>" <?= (int) $organization['id'] === (int) $facility['organization_id'] ? 'selected' : '' ?>><?= e($organization['name']) ?></option><?php endforeach; ?></select>
                    <input name="name" value="<?= e($facility['name']) ?>" required>
                    <select name="facility_type">
                        <option value="facility" <?= $facility['facility_type'] === 'facility' ? 'selected' : '' ?>>Facility</option>
                        <option value="hub" <?= $facility['facility_type'] === 'hub' ? 'selected' : '' ?>>Hub</option>
                        <option value="central_unit" <?= $facility['facility_type'] === 'central_unit' ? 'selected' : '' ?>>Central Unit</option>
                    </select>
                    <select name="is_active"><option value="1" <?= (int) $facility['is_active'] === 1 ? 'selected' : '' ?>>Active</option><option value="0" <?= (int) $facility['is_active'] === 0 ? 'selected' : '' ?>>Inactive</option></select>
                    <button class="btn btn-secondary" type="submit">Save</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
<?php $content = ob_get_clean(); require base_path('resources/views/layout.php'); ?>
