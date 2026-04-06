<?php ob_start(); ?>
<h1>Create Assessment</h1>
<?php if (!empty($errors)): ?><div class="alert alert-error"><?= e(json_encode($errors)) ?></div><?php endif; ?>
<form method="post" action="/assessments">
    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
    <label>Title</label>
    <input type="text" name="title" required>
    <label>Organization</label>
    <select name="organization_id" required>
        <?php foreach ($organizations as $org): ?>
            <option value="<?= e((string) $org['id']) ?>"><?= e($org['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <label>Fiscal year</label>
    <select name="fiscal_year_id" required>
        <?php foreach ($fiscalYears as $fy): ?>
            <option value="<?= e((string) $fy['id']) ?>"><?= e($fy['label']) ?></option>
        <?php endforeach; ?>
    </select>
    <label>Assessment period</label>
    <input type="text" name="assessment_period" placeholder="Q1 FY2026" required>
    <label>Assumptions and notes</label>
    <textarea name="assumptions_notes"></textarea>
    <button class="btn" type="submit">Create</button>
</form>
<?php $content = ob_get_clean(); require base_path('resources/views/layout.php'); ?>
