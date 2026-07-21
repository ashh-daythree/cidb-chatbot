<?php

declare(strict_types=1);

namespace Cidb\Backend\Controllers;

use Cidb\Backend\Services\ApplicantService;
use Cidb\Backend\Services\DocumentService;
use Cidb\Backend\Services\RequestService;
use Cidb\Backend\Services\SessionService;
use Cidb\Backend\Services\SubmissionService;
use Cidb\Backend\Services\VerificationService;
use Cidb\Backend\Utils\Exceptions\AppException;

final class SubmissionController extends AbstractController
{
    public function __construct(
        private readonly SubmissionService $submissionService,
        private readonly SessionService $sessionService,
        private readonly ApplicantService $applicantService,
        private readonly RequestService $requestService,
        private readonly DocumentService $documentService,
        private readonly VerificationService $verificationService
    ) {
    }

    public function submit(array $request): array
    {
        $payload = $this->payload($request);
        $result = $this->submissionService->submit($payload);

        return $this->success($result, 'Submission completed.', 201);
    }

    public function show(array $request): array
    {
        $identifier = $this->routeParam($request, 'id');

        $submission = $this->requestService->findByRequestNumber($identifier);
        if ($submission === null) {
            $submission = $this->requestService->findBySessionId($identifier);
        }

        if ($submission === null) {
            throw new AppException('Submission not found.', 404, 'SUBMISSION_NOT_FOUND');
        }

        $sessionId = (string) ($submission['session_id'] ?? $identifier);
        $session = $this->sessionService->getById($sessionId);
        $applicant = $this->applicantService->findBySessionId($sessionId);
        $documents = $submission['id'] ?? null
            ? $this->documentService->findRequestDocuments((string) $submission['id'])
            : $this->documentService->findSessionDocuments($sessionId);
        $verification = $submission['id'] ?? null
            ? $this->verificationService->latestByRequestId((string) $submission['id'])
            : null;

        return $this->success([
            'submission' => $submission,
            'session' => $session,
            'applicant' => $applicant,
            'documents' => $documents,
            'verification' => $verification,
        ], 'Submission retrieved.');
    }
}
