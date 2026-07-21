<?php

declare(strict_types=1);

namespace Cidb\Backend\Controllers;

use Cidb\Backend\Services\SignatureService;

final class SignatureController extends AbstractController
{
    public function __construct(
        private readonly SignatureService $signatureService
    ) {
    }

    public function upload(array $request): array
    {
        $payload = $this->payload($request);
        $sessionId = $this->requireString($payload, 'session_id', 'Session ID is required.', 'SESSION_ID_REQUIRED');
        $signature = $payload['signature_data_url'] ?? $payload['signature'] ?? $payload['data_url'] ?? null;
        $requestId = trim((string) ($payload['request_id'] ?? '')) ?: null;

        $document = $this->signatureService->storeSignature($sessionId, $requestId, (string) $signature, [
            'metadata' => is_array($payload['metadata'] ?? null) ? $payload['metadata'] : [],
        ]);

        return $this->success([
            'document' => $document,
        ], 'Signature uploaded.', 201);
    }
}
