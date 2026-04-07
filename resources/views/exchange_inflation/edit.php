<?php ob_start(); ?>
<h1>Exchange, Interest, and Inflation</h1>
<?php if (!empty($errors)): ?><div class="alert alert-error"><?= e(json_encode($errors)) ?></div><?php endif; ?>
<form method="post">
    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
    <table>
        <thead><tr><th>Year</th><th>ETB per USD</th><th>Interest %</th><th>Inflation %</th><th>Source notes</th></tr></thead>
        <tbody>
        <?php $displayRows = $rows ?: [['year'=>'','etb_per_usd'=>'','interest_rate'=>'','inflation_rate'=>'','source_notes'=>'']]; ?>
        <?php foreach ($displayRows as $row): ?>
            <tr>
                <td><input name="year[]" value="<?= e((string) ($row['year'] ?? '')) ?>"></td>
                <td><input name="etb_per_usd[]" value="<?= e((string) ($row['etb_per_usd'] ?? '')) ?>"></td>
                <td><input name="interest_rate[]" value="<?= e((string) ($row['interest_rate'] ?? '')) ?>"></td>
                <td><input name="inflation_rate[]" value="<?= e((string) ($row['inflation_rate'] ?? '')) ?>"></td>
                <td><input name="source_notes[]" value="<?= e((string) ($row['source_notes'] ?? '')) ?>"></td>
            </tr>
        <?php endforeach; ?>
        <tr>
            <td><input name="year[]" placeholder="New row"></td>
            <td><input name="etb_per_usd[]"></td>
            <td><input name="interest_rate[]"></td>
            <td><input name="inflation_rate[]"></td>
            <td><input name="source_notes[]"></td>
        </tr>
        </tbody>
    </table>
    <label>Change reason (optional)</label>
    <input name="change_reason" placeholder="Describe why this revision changed">
    <button class="btn" type="submit">Save</button>
    <a class="btn btn-secondary" href="/assessments/<?= e((string) $assessmentId) ?>">Back</a>
</form>
<?php $content = ob_get_clean(); require base_path('resources/views/layout.php'); ?>
