<?php
declare(strict_types=1);

namespace App\Support;

final class ConfigRepository
{
    private array $items = [];
    private static ?self $instance = null;

    private function __construct()
    {
    }

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function load(array $configuration): void
    {
        $this->items = array_replace_recursive($this->items, $configuration);
    }

    public function get(string $key, $default = null)
    {
        $segments = explode('.', $key);
        $value = $this->items;
        foreach ($segments as $segment) {
            if (is_array($value) && array_key_exists($segment, $value)) {
                $value = $value[$segment];
            } else {
                return $default;
            }
        }
        return $value;
    }

    public function set(string $key, $value): void
    {
        $segments = explode('.', $key);
        $reference = &$this->items;
        foreach ($segments as $segment) {
            if (!is_array($reference)) {
                $reference = [];
            }
            if (!array_key_exists($segment, $reference)) {
                $reference[$segment] = [];
            }
            $reference = &$reference[$segment];
        }
        $reference = $value;
    }

    public function all(): array
    {
        return $this->items;
    }
}
