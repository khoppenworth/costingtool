<?php
declare(strict_types=1);

namespace App\Core\Upgrade;

use RuntimeException;

class ManifestValidator
{
    public function validate(array $manifest, string $currentVersion): void
    {
        foreach (['package_id', 'from_version', 'to_version', 'migration_sequence'] as $required) {
            if (!array_key_exists($required, $manifest)) {
                throw new RuntimeException("Upgrade manifest missing [$required].");
            }
        }
        if ($manifest['from_version'] !== $currentVersion) {
            throw new RuntimeException('Incompatible version jump for uploaded package.');
        }
    }
}
