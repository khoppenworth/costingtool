<?php ob_start(); ?>
<h1><?= e($assessment['title'] ?? 'Assessment') ?></h1>
<div class="grid-2">
    <div class="card">
        <strong>Status:</strong> <?= e($assessment['status'] ?? '') ?><br>
        <strong>Organization:</strong> <?= e($assessment['organization_name'] ?? '') ?><br>
        <strong>Fiscal year:</strong> <?= e($assessment['fiscal_year_label'] ?? '') ?><br>
        <strong>Calculation version:</strong> <?= e($assessment['calculation_version'] ?? '') ?><br>
    </div>
    <div class="card">
        <strong>Actions</strong>
        <div class="actions" style="margin-top:10px;">
            <a class="btn" href="/assessments/<?= e((string) $assessment['assessment_id']) ?>/sample-information">Sample Information</a>
            <a class="btn" href="/assessments/<?= e((string) $assessment['assessment_id']) ?>/exchange-inflation">Exchange/Inflation</a>
        </div>
        <div class="actions" style="margin-top:10px;">
            <form method="post" action="/assessments/<?= e((string) $assessment['assessment_id']) ?>/submit">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><button class="btn" type="submit">Submit</button>
            </form>
            <form method="post" action="/assessments/<?= e((string) $assessment['assessment_id']) ?>/approve">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><button class="btn" type="submit">Approve</button>
            </form>
            <form method="post" action="/assessments/<?= e((string) $assessment['assessment_id']) ?>/lock">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><button class="btn" type="submit">Lock</button>
            </form>
        </div>
    </div>
</div>

<h2>Module status</h2>
<table>
    <thead><tr><th>Module</th><th>Status</th><th>Updated</th></tr></thead>
    <tbody>
    <?php foreach ($moduleStatuses as $row): ?>
        <tr><td><?= e($row['module_key']) ?></td><td><?= e($row['status']) ?></td><td><?= e($row['updated_at']) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
</table>

<h2>Sample information snapshot</h2>
<?php if ($sample): ?>
    <table><tbody>
        <tr><th>Sites surveyed</th><td><?= e((string) $sample['sites_surveyed']) ?></td></tr>
        <tr><th>Sites total</th><td><?= e((string) $sample['sites_total']) ?></td></tr>
        <tr><th>Central units</th><td><?= e((string) $sample['central_units']) ?></td></tr>
        <tr><th>Hubs</th><td><?= e((string) $sample['hubs']) ?></td></tr>
    </tbody></table>
<?php else: ?><p class="small">No sample information yet.</p><?php endif; ?>

<h2>Exchange, interest, and inflation</h2>
<table>
    <thead><tr><th>Year</th><th>ETB/USD</th><th>USD/ETB</th><th>Interest %</th><th>Inflation %</th></tr></thead>
    <tbody>
    <?php foreach ($exchangeRows as $row): ?>
        <tr>
            <td><?= e((string) $row['year']) ?></td>
            <td><?= e((string) $row['etb_per_usd']) ?></td>
            <td><?= e((string) $row['usd_per_etb']) ?></td>
            <td><?= e((string) $row['interest_rate']) ?></td>
            <td><?= e((string) $row['inflation_rate']) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php $content = ob_get_clean(); require base_path('resources/views/layout.php'); ?>
