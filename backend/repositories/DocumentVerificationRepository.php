<?php

declare(strict_types=1);

namespace Cidb\Backend\Repositories;

use PDO;

final class DocumentVerificationRepository extends BaseRepository
{
    protected function tableName(): string
    {
        return 'document_verifications';
    }

    public function findByUploadedDocumentId(string $uploadedDocumentId, int $limit = 100, int $offset = 0): array
    {
        return $this->findAll(['uploaded_document_id' => $uploadedDocumentId], $limit, $offset, 'verified_at', 'DESC');
    }

    public function findLatestByUploadedDocumentId(string $uploadedDocumentId): ?array
    {
        return $this->findAll(['uploaded_document_id' => $uploadedDocumentId], 1, 0, 'verified_at', 'DESC')[0] ?? null;
    }

    public function countBySessionIdAndVerificationType(string $sessionId, string $verificationType): int
    {
        $sql = <<<'SQL'
            SELECT COUNT(*) AS aggregate
            FROM document_verifications dv
            INNER JOIN uploaded_documents ud ON ud.id = dv.uploaded_document_id
            WHERE ud.session_id = :session_id
              AND dv.verification_type = :verification_type
        SQL;

        $statement = $this->connection->pdo()->prepare($sql);
        $statement->bindValue(':session_id', $sessionId);
        $statement->bindValue(':verification_type', $verificationType);
        $statement->execute();

        $row = $statement->fetch(PDO::FETCH_ASSOC);
        return isset($row['aggregate']) ? (int) $row['aggregate'] : 0;
    }
}
