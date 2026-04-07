<?php
declare(strict_types=1);

use App\Core\Workflow\WorkflowEngine;

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/bootstrap/helpers.php';

$engine = new WorkflowEngine();
$failures = 0;

$assert = static function (bool $condition, string $message) use (&$failures): void {
    if ($condition) {
        echo "[OK] {$message}\n";
        return;
    }
    echo "[FAIL] {$message}\n";
    $failures++;
};

$assert($engine->canTransition('draft', 'submitted'), 'draft -> submitted is allowed');
$assert(!$engine->canTransition('draft', 'approved'), 'draft -> approved is blocked');
$assert($engine->canTransition('submitted', 'reviewed'), 'submitted -> reviewed is allowed');
$assert($engine->canTransition('approved', 'locked'), 'approved -> locked is allowed');
$assert(!$engine->canTransition('locked', 'submitted'), 'locked -> submitted is blocked');
$assert($engine->canEdit('draft'), 'draft is editable');
$assert($engine->canEdit('returned'), 'returned is editable');
$assert(!$engine->canEdit('submitted'), 'submitted is read-only for data entry edits');
$assert(!$engine->canEdit('locked'), 'locked is read-only');

exit($failures > 0 ? 1 : 0);
