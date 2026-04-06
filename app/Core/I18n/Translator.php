<?php
declare(strict_types=1);

namespace App\Core\I18n;

class Translator
{
    private array $loaded = [];

    public function __construct(
        private string $basePath,
        private string $locale,
        private string $fallbackLocale
    ) {
    }

    public function get(string $key, array $replace = []): string
    {
        [$file, $item] = array_pad(explode('.', $key, 2), 2, 'messages');
        $value = $this->line($this->locale, $file, $item) ?? $this->line($this->fallbackLocale, $file, $item) ?? $key;
        foreach ($replace as $search => $replacement) {
            $value = str_replace(':' . $search, (string) $replacement, $value);
        }
        return $value;
    }

    private function line(string $locale, string $file, string $item): mixed
    {
        if (!isset($this->loaded[$locale][$file])) {
            $path = $this->basePath . '/' . $locale . '/' . $file . '.php';
            $this->loaded[$locale][$file] = file_exists($path) ? require $path : [];
        }
        $value = $this->loaded[$locale][$file];
        foreach (explode('.', $item) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return null;
            }
            $value = $value[$segment];
        }
        return $value;
    }
}
