<?php ob_start(); ?>
<h1>Login</h1>
<?php if (!empty($error)): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<form method="post" action="/login">
    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
    <label>Username or email</label>
    <input type="text" name="identity" required>
    <label>Password</label>
    <input type="password" name="password" required>
    <button class="btn" type="submit">Login</button>
</form>
<p class="small">Default seeded admin: admin / ChangeMe123!</p>
<?php $content = ob_get_clean(); require base_path('resources/views/layout.php'); ?>
