<?php

declare(strict_types=1);

namespace Cidb\Backend\Services;

use Cidb\Backend\Config\DatabaseConnection;
use Cidb\Backend\Config\UploadConfig;
use Cidb\Backend\Repositories\ReferenceDocumentTypeRepository;
use Cidb\Backend\Repositories\UploadedDocumentRepository;
use Cidb\Backend\Storage\DocumentStorageInterface;
use Cidb\Backend\Utils\JsonHelper;
use Cidb\Backend\Utils\SensitiveDataCrypto;
use Cidb\Backend\Validators\FileUploadValidator;
use Cidb\Backend\Utils\Exceptions\AppException;

final class UploadService extends AbstractService
{
    public function __construct(
        DatabaseConnection $connection,
        private readonly UploadConfig $uploadConfig,
        private readonly FileUploadValidator $fileUploadValidator,
        private readonly ReferenceDocumentTypeRepository $documentTypeRepository,
        private readonly UploadedDocumentRepository $documentRepository,
        private readonly DocumentStorageInterface $storageProvider,
        private readonly AuditService $auditService
    ) {
        parent::__construct($connection);
    }

    public function storeUploadedFile(string $sessionId, ?string $requestId, string $documentTypeCode, array $file, array $options = []): array
    {
        return $this->transactional(function () use ($sessionId, $requestId, $documentTypeCode, $file, $options): array {
            $documentType = $this->documentTypeRepository->findActiveByCode($documentTypeCode);
            if ($documentType === null) {
                throw new AppException('Document type is not allowed.', 422, 'DOCUMENT_TYPE_INVALID');
            }

            $maxBytes = $this->resolveMaxBytes($documentType, $options);
            $policy = [
                'field' => $options['field'] ?? $documentTypeCode,
                'allowed_mime_types' => $options['allowed_mime_types'] ?? json_decode((string) ($documentType['allowed_mime_types'] ?? '[]'), true) ?: [],
                'allowed_extensions' => $options['allowed_extensions'] ?? ['jpg', 'jpeg', 'png', 'webp', 'pdf'],
                'max_bytes' => $maxBytes,
            ];

            $validation = $this->fileUploadValidator->validate($file, $policy);
            if (!$validation->isValid()) {
                throw new AppException('Uploaded file failed validation.', 422, 'FILE_UPLOAD_INVALID', $validation->errors());
            }

            $tmpPath = (string) ($file['tmp_name'] ?? '');
            if (!is_file($tmpPath)) {
                throw new AppException('Temporary upload file is missing.', 422, 'FILE_TEMPORARY_MISSING');
            }

            $checksum = hash_file('sha256', $tmpPath);
            if (!is_string($checksum) || $checksum === '') {
                throw new AppException('Unable to calculate file checksum.', 500, 'CHECKSUM_FAILED');
            }

            $expectedChecksum = isset($options['expected_checksum']) ? trim((string) $options['expected_checksum']) : null;
            if ($expectedChecksum !== null && $expectedChecksum !== '' && !hash_equals(strtolower($expectedChecksum), strtolower($checksum))) {
                throw new AppException('File checksum does not match the expected value.', 422, 'CHECKSUM_MISMATCH');
            }

            if ($this->documentRepository->findBySessionDocumentTypeAndChecksum($sessionId, $documentTypeCode, $checksum) !== null) {
                throw new AppException('Duplicate document upload detected.', 409, 'DUPLICATE_DOCUMENT');
            }

            $randomFileName = $this->generateFileName((string) ($validation->data()['file_extension'] ?? pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION)));
            $storageKey = $this->buildStorageKey($sessionId, $documentTypeCode, $randomFileName);

            try {
                $storedKey = $this->storageProvider->storeFile($storageKey, $tmpPath);

                $document = $this->documentRepository->insert([
                    'session_id' => $sessionId,
                    'request_id' => $requestId,
                    'document_type_code' => $documentTypeCode,
                    'upload_source' => $this->resolveUploadSource($documentTypeCode, $options),
                    'storage_disk' => $this->uploadConfig->storageDriver(),
                    'storage_path' => $storedKey,
                    'storage_file_name' => $randomFileName,
                    'original_file_name_ciphertext' => SensitiveDataCrypto::encrypt((string) ($validation->data()['file_name'] ?? basename((string) ($file['name'] ?? '')))),
                    'mime_type' => (string) ($validation->data()['mime_type'] ?? ($file['type'] ?? 'application/octet-stream')),
                    'file_extension' => (string) ($validation->data()['file_extension'] ?? pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION)),
                    'file_size_bytes' => (int) ($validation->data()['size_bytes'] ?? ($file['size'] ?? 0)),
                    'sha256_checksum' => $checksum,
                    'upload_status' => 'stored',
                    'security_status' => 'not_scanned',
                    'metadata' => JsonHelper::encode(array_merge($options['metadata'] ?? [], [
                        'original_file_name' => basename((string) ($file['name'] ?? '')),
                        'expected_checksum' => $expectedChecksum,
                        'checksum_verified' => true,
                    ]), true),
                    'created_at' => $this->now(),
                    'updated_at' => $this->now(),
                ]);
            } catch (\Throwable $throwable) {
                $this->storageProvider->delete($storageKey);
                throw $throwable;
            }

            $this->auditService->record('file_stored', 'File stored successfully.', [
                'session_id' => $sessionId,
                'request_id' => $requestId,
                'document_type_code' => $documentTypeCode,
                'storage_file_name' => $randomFileName,
                'checksum' => $checksum,
            ], 'info', $sessionId, $requestId);

            return $document;
        });
    }

    public function storeIcFront(string $sessionId, ?string $requestId, array $file, array $options = []): array
    {
        return $this->storeUploadedFile($sessionId, $requestId, 'IC_FRONT', $file, $options);
    }

    public function storeIcBack(string $sessionId, ?string $requestId, array $file, array $options = []): array
    {
        return $this->storeUploadedFile($sessionId, $requestId, 'IC_BACK', $file, $options);
    }

    public function storeBinaryDocument(string $sessionId, ?string $requestId, string $documentTypeCode, string $binaryData, array $options = []): array
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'cidb_doc_');
        if ($tempFile === false) {
            throw new AppException('Unable to create a temporary file.', 500, 'TEMP_FILE_CREATE_FAILED');
        }

        if (file_put_contents($tempFile, $binaryData) === false) {
            @unlink($tempFile);
            throw new AppException('Unable to write temporary file.', 500, 'TEMP_FILE_WRITE_FAILED');
        }

        $file = [
            'name' => $options['name'] ?? 'signature.png',
            'type' => $options['mime_type'] ?? 'image/png',
            'tmp_name' => $tempFile,
            'error' => UPLOAD_ERR_OK,
            'size' => strlen($binaryData),
        ];

        try {
            return $this->storeUploadedFile($sessionId, $requestId, $documentTypeCode, $file, $options);
        } finally {
            if (is_file($tempFile)) {
                @unlink($tempFile);
            }
        }
    }

    private function resolveMaxBytes(array $documentType, array $options): int
    {
        $documentTypeLimit = isset($documentType['max_file_size_mb']) ? ((int) $documentType['max_file_size_mb'] * 1024 * 1024) : $this->uploadConfig->maxFileSizeBytes();
        $configuredLimit = match ((string) ($options['field'] ?? '')) {
            'signature' => $this->uploadConfig->signatureMaxFileSizeBytes(),
            default => $this->uploadConfig->maxFileSizeBytes(),
        };
        $overrideLimit = isset($options['max_bytes']) ? max(1, (int) $options['max_bytes']) : null;

        return (int) min($documentTypeLimit, $configuredLimit, $overrideLimit ?? PHP_INT_MAX);
    }

    private function buildStorageKey(string $sessionId, string $documentTypeCode, string $fileName): string
    {
        return 'documents/' . $sessionId . '/' . $documentTypeCode . '/' . $fileName;
    }

    private function generateFileName(string $extension): string
    {
        $extension = ltrim(strtolower($extension), '.');
        $random = bin2hex(random_bytes(16));

        return $extension === '' ? $random : $random . '.' . $extension;
    }

    private function resolveUploadSource(string $documentTypeCode, array $options): string
    {
        $candidate = strtolower(trim((string) ($options['upload_source'] ?? '')));
        $allowed = ['user_upload', 'signature_pad', 'system_import'];

        if (in_array($candidate, $allowed, true)) {
            return $candidate;
        }

        if ($documentTypeCode === 'SIGNATURE') {
            return 'signature_pad';
        }

        return 'user_upload';
    }
}
