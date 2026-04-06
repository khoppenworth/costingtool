<?php $user = app(\App\Core\Auth\Auth::class)->user(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= e(config('app.name')) ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f7f7f7; }
        nav { background: #1f4d7a; color: white; padding: 12px 20px; }
        nav a { color: white; margin-right: 16px; text-decoration: none; }
        .container { max-width: 1100px; margin: 20px auto; background: white; padding: 24px; border-radius: 8px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        input, select, textarea { width: 100%; padding: 8px; margin: 4px 0 12px; }
        .actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .btn { display: inline-block; padding: 8px 12px; background: #2f78bc; color: white; text-decoration: none; border: none; border-radius: 4px; cursor: pointer; }
        .btn-secondary { background: #6c757d; }
        .alert { padding: 10px; margin-bottom: 15px; border-radius: 4px; }
        .alert-error { background: #f8d7da; }
        .alert-success { background: #d1e7dd; }
        .small { color: #666; font-size: 0.9rem; }
        .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; }
        .grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
        .card { border: 1px solid #ddd; border-radius: 8px; padding: 16px; background: #fff; }
    </style>
</head>
<body>
<nav>
    <a href="/assessments"><?= e(__('messages.nav.assessments')) ?></a>
    <?php if ($user): ?>
        <a href="/admin"><?= e(__('messages.nav.admin')) ?></a>
        <form action="/logout" method="post" style="display:inline">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <button class="btn btn-secondary" type="submit"><?= e(__('messages.nav.logout')) ?></button>
        </form>
    <?php endif; ?>
</nav>
<div class="container">
    <?= $content ?>
</div>
</body>
</html>
