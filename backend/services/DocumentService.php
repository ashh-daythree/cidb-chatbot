<?php

declare(strict_types=1);

namespace Cidb\Backend\Services;

use Cidb\Backend\Config\DatabaseConnection;
use Cidb\Backend\Repositories\DocumentVerificationRepository;
use Cidb\Backend\Repositories\ReferenceDocumentTypeRepository;
use Cidb\Backend\Repositories\UploadedDocumentRepository;
use Cidb\Backend\Utils\Exceptions\AppException;

final class DocumentService extends AbstractService
{
    public function __construct(
        DatabaseConnection $connection,
        private readonly UploadedDocumentRepository $documentRepository,
        private readonly DocumentVerificationRepository $verificationRepository,
        private readonly ReferenceDocumentTypeRepository $documentTypeRepository,
        private readonly AuditService $auditService
    ) {
        parent::__construct($connection);
    }

    public function createDocument(array $data): array
    {
        return $this->transactional(function () use ($data): array {
            return $this->documentRepository->insert($data);
        });
    }

    public function attachDocumentToRequest(string $documentId, string $requestId): array
    {
        return $this->transactional(function () use ($documentId, $requestId): array {
            $document = $this->documentRepository->findById($documentId);
            if ($document === null) {
                throw new AppException('Document not found.', 404, 'DOCUMENT_NOT_FOUND');
            }

            $updated = $this->documentRepository->update($documentId, [
                'request_id' => $requestId,
                'updated_at' => $this->now(),
            ]);

            $this->auditService->record('document_attached_to_request', 'Document linked to request.', [
                'document_id' => $documentId,
                'request_id' => $requestId,
                'document_type_code' => $document['document_type_code'] ?? null,
            ], 'info', $document['session_id'] ?? null, $requestId);

            return $updated ?? $document;
        });
    }

    public function attachSessionDocumentsToRequest(string $sessionId, string $requestId): array
    {
        return $this->transactional(function () use ($sessionId, $requestId): array {
            $documents = $this->documentRepository->findBySessionId($sessionId);
            $attached = [];

            foreach ($documents as $document) {
                if (!empty($document['request_id'])) {
                    continue;
                }

                $attached[] = $this->documentRepository->update((string) $document['id'], [
                    'request_id' => $requestId,
                    'updated_at' => $this->now(),
                ]) ?? $document;
            }

            return $attached;
        });
    }

    public function findRequiredDocumentTypes(): array
    {
        return $this->documentTypeRepository->findAll(['is_active' => true, 'is_required_for_submission' => true], 100, 0, 'sort_order', 'ASC');
    }

    public function findSessionDocuments(string $sessionId): array
    {
        return $this->documentRepository->findBySessionId($sessionId);
    }

    public function findRequestDocuments(string $requestId): array
    {
        return $this->documentRepository->findByRequestId($requestId);
    }

    /**
     * @return array{missing: array<int, string>, present: array<int, string>}
     */
    public function resolveRequiredDocumentCoverage(array $documents, ?string $serviceType = null, ?string $identityType = null): array
    {
        $requiredCodes = array_values(array_unique(array_column($this->findRequiredDocumentTypes(), 'document_type_code')));
        $serviceType = mb_strtolower(trim((string) $serviceType));
        $identityType = mb_strtoupper(trim((string) $identityType));
        $isPassport = $identityType === 'PASSPORT';

        if ($serviceType === 'company') {
            $requiredCodes = array_values(array_diff($requiredCodes, ['SIGNATURE']));
            if (!in_array('SSM_PPK_CERTIFICATE', $requiredCodes, true)) {
                $requiredCodes[] = 'SSM_PPK_CERTIFICATE';
            }
        } else {
            $requiredCodes = array_values(array_diff($requiredCodes, ['SSM_PPK_CERTIFICATE']));
            if (!in_array('SIGNATURE', $requiredCodes, true)) {
                $requiredCodes[] = 'SIGNATURE';
            }
        }

        if ($isPassport) {
            $requiredCodes = array_values(array_filter(
                $requiredCodes,
                static fn (string $code): bool => $code !== 'IC_BACK'
            ));
        }

        $presentCodes = array_values(array_unique(array_map(
            static fn (array $document): string => (string) ($document['document_type_code'] ?? ''),
            $documents
        )));

        $missing = array_values(array_diff($requiredCodes, $presentCodes));

        return [
            'missing' => $missing,
            'present' => $presentCodes,
        ];
    }

    public function latestVerification(string $uploadedDocumentId): ?array
    {
        return $this->verificationRepository->findLatestByUploadedDocumentId($uploadedDocumentId);
    }
}
