<?php ob_start(); ?>
<h1>Admin Dashboard</h1>
<div class="grid-4">
    <div class="card"><strong>Users</strong><br><?= e((string) $stats['users']) ?></div>
    <div class="card"><strong>Assessments</strong><br><?= e((string) $stats['assessments']) ?></div>
    <div class="card"><strong>Upgrades</strong><br><?= e((string) $stats['upgrades']) ?></div>
    <div class="card"><strong>Current version</strong><br><?= e(($stats['version'] ?? 'See upgrade log')) ?></div>
</div>
<p><a class="btn" href="/admin/upgrades">Open upgrade utility</a></p>
<?php $content = ob_get_clean(); require base_path('resources/views/layout.php'); ?>
