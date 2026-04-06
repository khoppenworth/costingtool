<?php ob_start(); ?>
<h1>Upgrade Utility</h1>
<p class="small">Manual upload wizard for versioned upgrade packages.</p>
<?php if (!empty($error)): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<?php if (!empty($success)): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

<form method="post" enctype="multipart/form-data">
    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
    <label>Upgrade ZIP package</label>
    <input type="file" name="upgrade_zip" accept=".zip" required>
    <button class="btn" type="submit">Run upgrade</button>
</form>

<h2>Recent upgrade logs</h2>
<table>
    <thead><tr><th>Package</th><th>From</th><th>To</th><th>Status</th><th>When</th></tr></thead>
    <tbody>
    <?php foreach ($logs as $log): ?>
        <tr>
            <td><?= e($log['package_id']) ?></td>
            <td><?= e($log['from_version']) ?></td>
            <td><?= e($log['to_version']) ?></td>
            <td><?= e($log['result_status']) ?></td>
            <td><?= e($log['created_at']) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php $content = ob_get_clean(); require base_path('resources/views/layout.php'); ?>
