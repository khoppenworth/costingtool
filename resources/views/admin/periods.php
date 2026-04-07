<?php ob_start(); ?>
<h1>Fiscal Years & Assessment Periods</h1>

<h2>Create Fiscal Year</h2>
<form method="post" action="/admin/fiscal-years">
    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
    <label>Label</label><input name="label" placeholder="FY2027" required>
    <label>Start date</label><input type="date" name="start_date" required>
    <label>End date</label><input type="date" name="end_date" required>
    <button class="btn" type="submit">Create fiscal year</button>
</form>

<table>
    <tr><th>Label</th><th>Start</th><th>End</th><th>Status</th><th>Update</th></tr>
    <?php foreach ($fiscalYears as $fy): ?>
        <tr>
            <td><?= e($fy['label']) ?></td><td><?= e($fy['start_date']) ?></td><td><?= e($fy['end_date']) ?></td><td><?= (int) $fy['is_active'] ? 'Active' : 'Inactive' ?></td>
            <td>
                <form method="post" action="/admin/fiscal-years/<?= e((string) $fy['id']) ?>">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input name="label" value="<?= e($fy['label']) ?>" required>
                    <input type="date" name="start_date" value="<?= e($fy['start_date']) ?>" required>
                    <input type="date" name="end_date" value="<?= e($fy['end_date']) ?>" required>
                    <select name="is_active"><option value="1" <?= (int) $fy['is_active'] === 1 ? 'selected' : '' ?>>Active</option><option value="0" <?= (int) $fy['is_active'] === 0 ? 'selected' : '' ?>>Inactive</option></select>
                    <button class="btn btn-secondary">Save</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

<h2>Create Assessment Period</h2>
<form method="post" action="/admin/assessment-periods">
    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
    <label>Code</label><input name="period_code" placeholder="2026-H1" required>
    <label>Label</label><input name="label" placeholder="FY2026 H1" required>
    <label>Start date</label><input type="date" name="start_date" required>
    <label>End date</label><input type="date" name="end_date" required>
    <button class="btn" type="submit">Create assessment period</button>
</form>

<table>
    <tr><th>Code</th><th>Label</th><th>Start</th><th>End</th><th>Status</th><th>Update</th></tr>
    <?php foreach ($assessmentPeriods as $period): ?>
        <tr>
            <td><?= e($period['period_code']) ?></td><td><?= e($period['label']) ?></td><td><?= e($period['start_date']) ?></td><td><?= e($period['end_date']) ?></td><td><?= (int) $period['is_active'] ? 'Active' : 'Inactive' ?></td>
            <td>
                <form method="post" action="/admin/assessment-periods/<?= e((string) $period['id']) ?>">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input name="period_code" value="<?= e($period['period_code']) ?>" required>
                    <input name="label" value="<?= e($period['label']) ?>" required>
                    <input type="date" name="start_date" value="<?= e($period['start_date']) ?>" required>
                    <input type="date" name="end_date" value="<?= e($period['end_date']) ?>" required>
                    <select name="is_active"><option value="1" <?= (int) $period['is_active'] === 1 ? 'selected' : '' ?>>Active</option><option value="0" <?= (int) $period['is_active'] === 0 ? 'selected' : '' ?>>Inactive</option></select>
                    <button class="btn btn-secondary">Save</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
<?php $content = ob_get_clean(); require base_path('resources/views/layout.php'); ?>
