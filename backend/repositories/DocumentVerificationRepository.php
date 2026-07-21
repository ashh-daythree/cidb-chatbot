<?php

declare(strict_types=1);

namespace Cidb\Backend\Repositories;

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
}

