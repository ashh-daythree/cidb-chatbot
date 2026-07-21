<?php

declare(strict_types=1);

namespace Cidb\Backend\Repositories;

final class UploadedDocumentRepository extends BaseRepository
{
    protected function tableName(): string
    {
        return 'uploaded_documents';
    }

    public function findBySessionId(string $sessionId, int $limit = 100, int $offset = 0): array
    {
        return $this->findAll(['session_id' => $sessionId], $limit, $offset, 'created_at', 'DESC');
    }

    public function findByRequestId(string $requestId, int $limit = 100, int $offset = 0): array
    {
        return $this->findAll(['request_id' => $requestId], $limit, $offset, 'created_at', 'DESC');
    }

    public function findByDocumentTypeCode(string $documentTypeCode, int $limit = 100, int $offset = 0): array
    {
        return $this->findAll(['document_type_code' => $documentTypeCode], $limit, $offset, 'created_at', 'DESC');
    }

    public function findByChecksum(string $checksum): ?array
    {
        return $this->findOneBy(['sha256_checksum' => $checksum]);
    }

    public function findLatestBySessionAndDocumentType(string $sessionId, string $documentTypeCode): ?array
    {
        return $this->findAll(
            ['session_id' => $sessionId, 'document_type_code' => $documentTypeCode],
            1,
            0,
            'created_at',
            'DESC'
        )[0] ?? null;
    }

    public function findBySessionDocumentTypeAndChecksum(string $sessionId, string $documentTypeCode, string $checksum): ?array
    {
        return $this->findOneBy([
            'session_id' => $sessionId,
            'document_type_code' => $documentTypeCode,
            'sha256_checksum' => $checksum,
        ]);
    }
}
