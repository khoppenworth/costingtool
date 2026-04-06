<?php
declare(strict_types=1);

namespace App\Core\Validation;

class Validator
{
    public array $errors = [];
    public array $warnings = [];

    public function __construct(private array $data, private array $rules)
    {
    }

    public function passes(): bool
    {
        foreach ($this->rules as $field => $fieldRules) {
            $value = $this->data[$field] ?? null;
            foreach ($fieldRules as $rule) {
                [$name, $parameter] = array_pad(explode(':', $rule, 2), 2, null);
                match ($name) {
                    'required' => $this->validateRequired($field, $value),
                    'numeric' => $this->validateNumeric($field, $value),
                    'min' => $this->validateMin($field, $value, (float) $parameter),
                    'max' => $this->validateMax($field, $value, (float) $parameter),
                    'gte' => $this->validateGte($field, $value, $parameter),
                    default => null,
                };
            }
        }
        return $this->errors === [];
    }

    public function warning(string $field, string $message): void
    {
        $this->warnings[$field][] = $message;
    }

    private function validateRequired(string $field, mixed $value): void
    {
        if ($value === null || $value === '') {
            $this->errors[$field][] = __('messages.validation.required');
        }
    }

    private function validateNumeric(string $field, mixed $value): void
    {
        if ($value !== null && $value !== '' && !is_numeric($value)) {
            $this->errors[$field][] = __('messages.validation.numeric');
        }
    }

    private function validateMin(string $field, mixed $value, float $parameter): void
    {
        if ($value !== null && $value !== '' && is_numeric($value) && (float) $value < $parameter) {
            $this->errors[$field][] = __('messages.validation.min', ['min' => $parameter]);
        }
    }

    private function validateMax(string $field, mixed $value, float $parameter): void
    {
        if ($value !== null && $value !== '' && is_numeric($value) && (float) $value > $parameter) {
            $this->errors[$field][] = __('messages.validation.max', ['max' => $parameter]);
        }
    }

    private function validateGte(string $field, mixed $value, string $otherField): void
    {
        $otherValue = $this->data[$otherField] ?? null;
        if (is_numeric($value) && is_numeric($otherValue) && (float) $value < (float) $otherValue) {
            $this->errors[$field][] = __('messages.validation.gte', ['other' => $otherField]);
        }
    }
}
