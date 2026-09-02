<?php

declare(strict_types=1);

namespace Cidb\Backend\Migrations;

use Cidb\Backend\Config\EnvironmentLoader;
use Cidb\Backend\Utils\SensitiveDataCrypto;
use PDO;
use RuntimeException;

final class VerifyChatbotApplicantsPrivacySchema extends AbstractMigration
{
    public function name(): string
    {
        return '20260902_verify_chatbot_applicants_privacy_schema';
    }

    public function up(PDO $pdo): void
    {
        $statement = $pdo->query(
            "SELECT column_name, is_nullable, data_type\n" .
            "FROM information_schema.columns\n" .
            "WHERE table_schema = current_schema()\n" .
            "  AND table_name = 'chatbot_applicants'"
        );
        $columns = [];

        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $column) {
            $columns[(string) $column['column_name']] = [
                'is_nullable' => (string) $column['is_nullable'],
                'data_type' => (string) $column['data_type'],
            ];
        }

        $privacyColumns = ['full_name_ciphertext', 'full_name_hash', 'identity_number_ciphertext', 'identity_number_hash'];
        $hasPrivacySchema = count(array_filter(
            $privacyColumns,
            static fn (string $column): bool => isset($columns[$column])
        )) === count($privacyColumns);

        if (!$hasPrivacySchema && isset($columns['full_name'], $columns['identity_number'])) {
            $this->convertLegacySchema($pdo, $columns);
            return;
        }

        $missing = array_values(array_filter(
            $privacyColumns,
            static fn (string $column): bool => !isset($columns[$column]) || $columns[$column]['is_nullable'] !== 'NO'
        ));

        if ($missing !== []) {
            throw new RuntimeException(sprintf(
                'chatbot_applicants privacy schema is incomplete. Required NOT NULL columns missing or nullable: %s',
                implode(', ', $missing)
            ));
        }
    }

    public function down(PDO $pdo): void
    {
        // This migration verifies a production invariant and intentionally has no rollback.
    }

    /**
     * Converts the original plaintext applicant columns before enforcing privacy constraints.
     *
     * @param array<string, array{is_nullable: string, data_type: string}> $columns
     */
    private function convertLegacySchema(PDO $pdo, array $columns): void
    {
        if (filter_var(EnvironmentLoader::get('APP_DISABLE_HASHING', false), FILTER_VALIDATE_BOOL)) {
            throw new RuntimeException('Set APP_DISABLE_HASHING=false before converting chatbot_applicants.');
        }

        $pdo->exec(
            'ALTER TABLE chatbot_applicants ' .
            'ADD COLUMN full_name_ciphertext bytea, ' .
            'ADD COLUMN full_name_hash char(64), ' .
            'ADD COLUMN identity_number_ciphertext bytea, ' .
            'ADD COLUMN identity_number_hash char(64)'
        );

        $rows = $pdo->query('SELECT id, full_name, identity_number FROM chatbot_applicants')->fetchAll(PDO::FETCH_ASSOC);
        $update = $pdo->prepare(
            'UPDATE chatbot_applicants
                SET full_name_ciphertext = :full_name_ciphertext,
                    full_name_hash = :full_name_hash,
                    identity_number_ciphertext = :identity_number_ciphertext,
                    identity_number_hash = :identity_number_hash
              WHERE id = :id'
        );

        foreach ($rows as $row) {
            $fullName = trim((string) ($row['full_name'] ?? ''));
            $identityNumber = preg_replace('/[\s-]+/', '', strtoupper(trim((string) ($row['identity_number'] ?? ''))))
                ?? strtoupper(trim((string) ($row['identity_number'] ?? '')));

            if ($fullName === '' || $identityNumber === '') {
                throw new RuntimeException(sprintf('Cannot convert applicant %s with empty sensitive data.', (string) $row['id']));
            }

            $update->execute([
                'full_name_ciphertext' => SensitiveDataCrypto::encrypt($fullName),
                'full_name_hash' => hash('sha256', $fullName),
                'identity_number_ciphertext' => SensitiveDataCrypto::encrypt($identityNumber),
                'identity_number_hash' => hash('sha256', $identityNumber),
                'id' => $row['id'],
            ]);
        }

        $pdo->exec(
            'ALTER TABLE chatbot_applicants ' .
            'ALTER COLUMN full_name_ciphertext SET NOT NULL, ' .
            'ALTER COLUMN full_name_hash SET NOT NULL, ' .
            'ALTER COLUMN identity_number_ciphertext SET NOT NULL, ' .
            'ALTER COLUMN identity_number_hash SET NOT NULL'
        );

        $pdo->exec('ALTER TABLE chatbot_applicants DROP COLUMN full_name, DROP COLUMN identity_number');
    }
}
