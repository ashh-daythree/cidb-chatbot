<?php

declare(strict_types=1);

namespace Cidb\Backend\Controllers;

use Cidb\Backend\Services\ApplicantService;
use Cidb\Backend\Services\DocumentService;
use Cidb\Backend\Services\RequestService;
use Cidb\Backend\Services\SessionService;
use Cidb\Backend\Utils\Exceptions\AppException;

final class SessionController extends AbstractController
{
    public function __construct(
        private readonly SessionService $sessionService,
        private readonly ApplicantService $applicantService,
        private readonly RequestService $requestService,
        private readonly DocumentService $documentService
    ) {
    }

    public function start(array $request): array
    {
        $payload = $this->payload($request);
        $workflowCode = trim((string) ($payload['workflow_code'] ?? 'EMAIL_ID_CANCELLATION'));
        if ($workflowCode === '') {
            $workflowCode = 'EMAIL_ID_CANCELLATION';
        }

        $session = $this->sessionService->start($workflowCode);

        return $this->success([
            'session' => $session,
        ], 'Session started.', 201);
    }

    public function language(array $request): array
    {
        $payload = $this->payload($request);
        $sessionId = $this->requireString($payload, 'session_id', 'Session ID is required.', 'SESSION_ID_REQUIRED');
        $language = $payload['language'] ?? $payload['language_code'] ?? $payload['preferred_language'] ?? null;

        $session = $this->sessionService->saveLanguage($sessionId, $language);

        return $this->success([
            'session' => $session,
        ], 'Language saved.');
    }

    public function state(array $request): array
    {
        $payload = $this->payload($request);
        $sessionId = $this->requireString($payload, 'session_id', 'Session ID is required.', 'SESSION_ID_REQUIRED');
        $state = $payload['state'] ?? $payload['state_name'] ?? $payload['state_code'] ?? null;

        $session = $this->sessionService->saveState($sessionId, $state);

        return $this->success([
            'session' => $session,
        ], 'State saved.');
    }

    public function name(array $request): array
    {
        $payload = $this->payload($request);
        $sessionId = $this->requireString($payload, 'session_id', 'Session ID is required.', 'SESSION_ID_REQUIRED');
        $name = $payload['full_name'] ?? $payload['name'] ?? null;

        $session = $this->sessionService->saveName($sessionId, $name);

        return $this->success([
            'session' => $session,
        ], 'Name saved.');
    }

    public function identity(array $request): array
    {
        $payload = $this->payload($request);
        $sessionId = $this->requireString($payload, 'session_id', 'Session ID is required.', 'SESSION_ID_REQUIRED');
        $identity = $payload['identity_number'] ?? $payload['ic'] ?? $payload['passport'] ?? $payload['identity'] ?? null;

        $result = $this->sessionService->saveIdentity($sessionId, [
            'identity_type' => $payload['identity_type'] ?? null,
            'identity_number' => $identity,
        ]);

        return $this->success($result, 'Identity saved.');
    }

    public function show(array $request): array
    {
        $sessionId = $this->routeParam($request, 'id');
        $session = $this->sessionService->getById($sessionId);

        if ($session === null) {
            throw new AppException('Session not found.', 404, 'SESSION_NOT_FOUND');
        }

        $applicant = $this->applicantService->findBySessionId($sessionId);
        $submission = $this->requestService->findBySessionId($sessionId);
        $documents = $this->documentService->findSessionDocuments($sessionId);

        return $this->success([
            'session' => $session,
            'applicant' => $applicant,
            'submission' => $submission,
            'documents' => $documents,
        ], 'Session retrieved.');
    }
}
