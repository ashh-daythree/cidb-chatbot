<?php

declare(strict_types=1);

namespace Cidb\Backend\Config;

final class EnvironmentLoader
{
    private function __construct()
    {
    }

    public static function load(string $filePath): array
    {
        if (!is_file($filePath)) {
            return [];
        }

        $values = [];
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($lines === false) {
            return [];
        }

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                continue;
            }

            $parts = explode('=', $trimmed, 2);

            if (count($parts) !== 2) {
                continue;
            }

            [$key, $value] = $parts;
            $key = trim($key);
            $value = self::normalizeValue(trim($value));

            $values[$key] = $value;
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
            putenv(sprintf('%s=%s', $key, $value));
        }

        return $values;
    }

    private static function normalizeValue(string $value): string
    {
        $length = strlen($value);

        if ($length >= 2) {
            $first = $value[0];
            $last = $value[$length - 1];

            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                return substr($value, 1, -1);
            }
        }

        return $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        return $value === false || $value === null || $value === '' ? $default : $value;
    }
}

