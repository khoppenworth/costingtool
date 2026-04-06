<?php ob_start(); ?>
<h1>Assessments</h1>
<p><a class="btn" href="/assessments/create">Create assessment</a></p>
<table>
    <thead>
        <tr><th>ID</th><th>Title</th><th>Organization</th><th>Fiscal year</th><th>Status</th><th></th></tr>
    </thead>
    <tbody>
    <?php foreach ($assessments as $assessment): ?>
        <tr>
            <td><?= e((string) $assessment['id']) ?></td>
            <td><?= e($assessment['title']) ?></td>
            <td><?= e($assessment['organization_name'] ?? '') ?></td>
            <td><?= e($assessment['fiscal_year_label'] ?? '') ?></td>
            <td><?= e($assessment['status']) ?></td>
            <td><a class="btn btn-secondary" href="/assessments/<?= e((string) $assessment['id']) ?>">Open</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php $content = ob_get_clean(); require base_path('resources/views/layout.php'); ?>
