<?php

declare(strict_types=1);

namespace Cidb\Backend\Utils;

use Cidb\Backend\Config\EnvironmentLoader;
use Cidb\Backend\Utils\Exceptions\ConfigurationException;

final class SensitiveDataCrypto
{
    private const CIPHER = 'aes-256-gcm';

    public static function encrypt(string $plaintext): string
    {
        self::assertProductionConfiguration();

        if (self::isDevelopmentPassthroughEnabled()) {
            return $plaintext;
        }

        if ($plaintext === '') {
            throw new ConfigurationException('Sensitive plaintext cannot be empty.');
        }

        $key = self::keyMaterial();
        $iv = random_bytes(12);
        $tag = '';
        $ciphertext = openssl_encrypt(
            $plaintext,
            self::CIPHER,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($ciphertext === false || $tag === '') {
            throw new ConfigurationException('Unable to encrypt sensitive data.');
        }

        // Store as PostgreSQL bytea text format so PDO does not try to treat
        // the raw binary payload as UTF-8 text during parameter binding.
        return '\\x' . bin2hex($iv . $tag . $ciphertext);
    }

    private static function keyMaterial(): string
    {
        $explicitKey = trim((string) EnvironmentLoader::get('APP_ENCRYPTION_KEY', ''));
        if ($explicitKey !== '') {
            return hash('sha256', $explicitKey, true);
        }

        $fallbackSeed = implode('|', [
            (string) EnvironmentLoader::get('APP_NAME', 'CIDB Chatbot'),
            (string) EnvironmentLoader::get('APP_URL', 'http://localhost'),
            (string) EnvironmentLoader::get('DB_DATABASE', 'cidb_chatbot'),
        ]);

        return hash('sha256', $fallbackSeed, true);
    }

    private static function isDevelopmentPassthroughEnabled(): bool
    {
        return filter_var(EnvironmentLoader::get('APP_DISABLE_HASHING', false), FILTER_VALIDATE_BOOL);
    }

    private static function assertProductionConfiguration(): void
    {
        $environment = strtolower(trim((string) EnvironmentLoader::get('APP_ENV', 'local')));
        if ($environment !== 'production') {
            return;
        }

        if (self::isDevelopmentPassthroughEnabled()) {
            throw new ConfigurationException('APP_DISABLE_HASHING must be false in production.');
        }

        if (trim((string) EnvironmentLoader::get('APP_ENCRYPTION_KEY', '')) === '') {
            throw new ConfigurationException('APP_ENCRYPTION_KEY must be configured in production.');
        }
    }
}
