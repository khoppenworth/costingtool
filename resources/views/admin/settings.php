<?php ob_start(); ?>
<h1>System Settings</h1>
<?php $settingMap = []; foreach ($settings as $setting) { $settingMap[$setting['setting_key']] = $setting['setting_value']; } ?>
<form method="post" action="/admin/settings">
    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
    <label>Default locale</label>
    <select name="default_locale">
        <option value="en" <?= (($settingMap['default_locale'] ?? 'en') === 'en') ? 'selected' : '' ?>>English</option>
        <option value="am" <?= (($settingMap['default_locale'] ?? 'en') === 'am') ? 'selected' : '' ?>>Amharic</option>
    </select>
    <label>Country name</label><input name="country_name" value="<?= e((string) ($settingMap['country_name'] ?? '')) ?>">
    <label>Currency code</label><input name="currency_code" value="<?= e((string) ($settingMap['currency_code'] ?? 'ETB')) ?>">
    <label>Support email</label><input type="email" name="support_email" value="<?= e((string) ($settingMap['support_email'] ?? '')) ?>">
    <button class="btn" type="submit">Save settings</button>
</form>
<?php $content = ob_get_clean(); require base_path('resources/views/layout.php'); ?>
