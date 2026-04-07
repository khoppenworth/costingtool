<?php ob_start(); ?>
<h1>Sample Information</h1>
<?php if (!empty($errors)): ?><div class="alert alert-error"><?= e(json_encode($errors)) ?></div><?php endif; ?>
<form method="post">
    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
    <div class="grid-2">
        <div><label>Sites surveyed</label><input type="number" name="sites_surveyed" value="<?= e((string) ($record['sites_surveyed'] ?? '')) ?>"></div>
        <div><label>Sites total</label><input type="number" name="sites_total" value="<?= e((string) ($record['sites_total'] ?? '')) ?>"></div>
        <div><label>Central units</label><input type="number" name="central_units" value="<?= e((string) ($record['central_units'] ?? '')) ?>"></div>
        <div><label>Hubs</label><input type="number" name="hubs" value="<?= e((string) ($record['hubs'] ?? '')) ?>"></div>
    </div>
    <label>Notes</label>
    <textarea name="notes"><?= e($record['notes'] ?? '') ?></textarea>
    <label>Change reason (optional)</label>
    <input name="change_reason" placeholder="Describe why this revision changed">
    <button class="btn" type="submit">Save</button>
    <a class="btn btn-secondary" href="/assessments/<?= e((string) $assessmentId) ?>">Back</a>
</form>
<?php $content = ob_get_clean(); require base_path('resources/views/layout.php'); ?>
