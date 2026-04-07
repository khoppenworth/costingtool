<?php ob_start(); ?>
<h1>Revision Comparison</h1>
<p><a class="btn btn-secondary" href="/assessments/<?= e((string) $id) ?>/revisions">Back to revisions</a></p>
<p>Comparing revision <strong><?= e((string) $from) ?></strong> to <strong><?= e((string) $to) ?></strong>.</p>
<table>
    <thead><tr><th>Module</th><th>Field</th><th>From</th><th>To</th></tr></thead>
    <tbody>
    <?php foreach ($comparison as $row): ?>
        <tr>
            <td><?= e($row['module_name']) ?></td>
            <td><?= e($row['field_name']) ?></td>
            <td><?= e((string) $row['from_value']) ?></td>
            <td><?= e((string) $row['to_value']) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php $content = ob_get_clean(); require base_path('resources/views/layout.php'); ?>
