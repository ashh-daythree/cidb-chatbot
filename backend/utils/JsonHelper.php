<?php

declare(strict_types=1);

namespace Cidb\Backend\Utils;

use Cidb\Backend\Utils\Exceptions\AppException;
use Throwable;

final class JsonHelper
{
    public static function encode(mixed $value, bool $forceObject = false): string
    {
        $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE;
        $payload = $forceObject ? (object) $value : $value;

        $encoded = json_encode($payload, $flags);
        if ($encoded !== false) {
            return $encoded;
        }

        $sanitized = self::sanitize($payload);
        $encoded = json_encode($sanitized, $flags);
        if ($encoded !== false) {
            return $encoded;
        }

        throw new AppException(
            'Unable to encode JSON payload.',
            500,
            'JSON_ENCODE_FAILED',
            [
                'json_error' => json_last_error_msg(),
            ]
        );
    }

    /**
     * @return mixed
     */
    private static function sanitize(mixed $value): mixed
    {
        if (is_string($value)) {
            return self::sanitizeString($value);
        }

        if (is_array($value)) {
            $sanitized = [];
            foreach ($value as $key => $item) {
                $sanitized[$key] = self::sanitize($item);
            }

            return $sanitized;
        }

        if (is_object($value)) {
            $sanitized = new \stdClass();
            foreach (get_object_vars($value) as $key => $item) {
                $sanitized->{$key} = self::sanitize($item);
            }

            return $sanitized;
        }

        if (is_resource($value)) {
            return null;
        }

        return $value;
    }

    private static function sanitizeString(string $value): string
    {
        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'UTF-8//IGNORE', $value);
            if (is_string($converted) && $converted !== '') {
                return $converted;
            }
        }

        if (function_exists('mb_convert_encoding')) {
            $converted = @mb_convert_encoding($value, 'UTF-8', 'UTF-8');
            if (is_string($converted) && $converted !== '') {
                return $converted;
            }
        }

        return preg_replace('/[^\x09\x0A\x0D\x20-\x7E\x{A0}-\x{10FFFF}]/u', '', $value) ?? '';
    }
}
