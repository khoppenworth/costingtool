<?php ob_start(); ?>
<h1>Assessment Revisions</h1>
<p><a class="btn btn-secondary" href="/assessments/<?= e((string) $id) ?>">Back to Assessment</a></p>
<table>
    <thead><tr><th>Revision</th><th>Reason</th><th>Created By</th><th>Created At</th><th>Compare</th></tr></thead>
    <tbody>
    <?php foreach ($revisions as $revision): ?>
        <tr>
            <td><?= e((string) $revision['revision_number']) ?></td>
            <td><?= e((string) ($revision['reason'] ?? '')) ?></td>
            <td><?= e((string) $revision['created_by']) ?></td>
            <td><?= e((string) $revision['created_at']) ?></td>
            <td><a class="btn" href="/assessments/<?= e((string) $id) ?>/revisions/compare?from=<?= e((string) max(1, ((int) $revision['revision_number']) - 1)) ?>&to=<?= e((string) $revision['revision_number']) ?>">Compare with previous</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php $content = ob_get_clean(); require base_path('resources/views/layout.php'); ?>
