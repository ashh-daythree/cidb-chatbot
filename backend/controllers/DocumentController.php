<?php

declare(strict_types=1);

namespace Cidb\Backend\Controllers;

use Cidb\Backend\Services\UploadService;

final class DocumentController extends AbstractController
{
    public function __construct(
        private readonly UploadService $uploadService
    ) {
    }

    public function upload(array $request): array
    {
        $payload = $this->payload($request);
        $sessionId = $this->requireString($payload, 'session_id', 'Session ID is required.', 'SESSION_ID_REQUIRED');
        $documentTypeCode = $this->requireString($payload, 'document_type_code', 'Document type is required.', 'DOCUMENT_TYPE_REQUIRED');
        $requestId = trim((string) ($payload['request_id'] ?? '')) ?: null;
        $file = $this->file($request, $payload['file_field'] ?? 'file');

        $document = match ($documentTypeCode) {
            'IC_FRONT' => $this->uploadService->storeIcFront($sessionId, $requestId, $file, [
                'upload_source' => $payload['upload_source'] ?? 'user_upload',
                'metadata' => is_array($payload['metadata'] ?? null) ? $payload['metadata'] : [],
            ]),
            'IC_BACK' => $this->uploadService->storeIcBack($sessionId, $requestId, $file, [
                'upload_source' => $payload['upload_source'] ?? 'user_upload',
                'metadata' => is_array($payload['metadata'] ?? null) ? $payload['metadata'] : [],
            ]),
            default => $this->uploadService->storeUploadedFile($sessionId, $requestId, $documentTypeCode, $file, [
                'upload_source' => $payload['upload_source'] ?? 'user_upload',
                'metadata' => is_array($payload['metadata'] ?? null) ? $payload['metadata'] : [],
            ]),
        };

        return $this->success([
            'document' => $document,
        ], 'Document uploaded.', 201);
    }
}
