<?php

declare(strict_types=1);

namespace Cidb\Backend\Services;

use Cidb\Backend\Config\DatabaseConnection;
use Cidb\Backend\Validators\SignatureValidator;
use Cidb\Backend\Utils\Exceptions\AppException;

final class SignatureService extends AbstractService
{
    public function __construct(
        DatabaseConnection $connection,
        private readonly SignatureValidator $signatureValidator,
        private readonly UploadService $uploadService,
        private readonly AuditService $auditService
    ) {
        parent::__construct($connection);
    }

    public function storeSignature(string $sessionId, ?string $requestId, string $signatureDataUrl, array $options = []): array
    {
        $validation = $this->signatureValidator->validate($signatureDataUrl);
        if (!$validation->isValid()) {
            throw new AppException('Signature validation failed.', 422, 'SIGNATURE_INVALID', $validation->errors());
        }

        $bytes = (string) ($validation->data()['signature_bytes'] ?? '');
        if ($bytes === '') {
            throw new AppException('Signature bytes are missing.', 422, 'SIGNATURE_BYTES_MISSING');
        }

        $document = $this->uploadService->storeBinaryDocument(
            $sessionId,
            $requestId,
            'SIGNATURE',
            $bytes,
            array_merge($options, [
                'field' => 'signature',
                'name' => 'signature.png',
                'mime_type' => 'image/png',
                'upload_source' => 'signature_pad',
                'allowed_mime_types' => ['image/png'],
                'allowed_extensions' => ['png'],
                'max_bytes' => 1024 * 1024 * 5,
            ])
        );

        $this->auditService->record('signature_stored', 'Signature stored successfully.', [
            'session_id' => $sessionId,
            'request_id' => $requestId,
            'document_id' => $document['id'] ?? null,
        ], 'info', $sessionId, $requestId);

        return $document;
    }
}
