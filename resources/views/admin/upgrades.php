<?php ob_start(); ?>
<h1>Upgrade Utility</h1>
<p class="small">Check GitHub releases and run controlled upgrade (DB backup + app backup + deploy + migrations).</p>
<?php if (!empty($error)): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<?php if (!empty($success)): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

<div class="card">
    <h2>Current version</h2>
    <p><strong><?= e($currentVersion ?? '0.0.0') ?></strong></p>
</div>

<form method="post" style="margin-top:12px;">
    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="check">
    <button class="btn" type="submit">Check latest GitHub release</button>
</form>

<?php if (!empty($releaseInfo)): ?>
    <div class="card" style="margin-top:12px;">
        <h2>Latest release</h2>
        <p><strong>Tag:</strong> <?= e($releaseInfo['release_tag'] ?? '') ?></p>
        <p><strong>Version:</strong> <?= e($releaseInfo['latest_version'] ?? '') ?></p>
        <p><strong>Published:</strong> <?= e($releaseInfo['published_at'] ?? '') ?></p>
        <?php if (!empty($releaseInfo['release_url'])): ?>
            <p><a href="<?= e($releaseInfo['release_url']) ?>" target="_blank" rel="noopener noreferrer">View release notes</a></p>
        <?php endif; ?>

        <?php if (!empty($releaseInfo['has_update'])): ?>
            <form method="post">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="run_github">
                <button class="btn" type="submit" onclick="return confirm('Run upgrade now? The app will enter maintenance mode and create DB/app backups before deployment.');">Run upgrade from latest GitHub release</button>
            </form>
        <?php else: ?>
            <p class="small">No newer version detected.</p>
        <?php endif; ?>
    </div>
<?php endif; ?>

<h2 style="margin-top:20px;">Manual package upload (fallback)</h2>
<form method="post" enctype="multipart/form-data">
    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="upload">
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
