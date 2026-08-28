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
use Cidb\Backend\Utils\Logger;

final class SubmissionController extends AbstractController
{
    public function __construct(
        private readonly SubmissionService $submissionService,
        private readonly SessionService $sessionService,
        private readonly ApplicantService $applicantService,
        private readonly RequestService $requestService,
        private readonly DocumentService $documentService,
        private readonly VerificationService $verificationService,
        private readonly Logger $logger
    ) {
    }

    public function submit(array $request): array
    {
        $payload = $this->payload($request);
        $result = $this->submissionService->submit($payload);

        return $this->success($result, (string) ($result['message'] ?? 'Submission completed.'), 201);
    }

    public function retry(array $request): array
    {
        $identifier = $this->routeParam($request, 'id');
        $result = $this->submissionService->retryCancellation($identifier);

        return $this->success($result, (string) ($result['message'] ?? 'Cancellation retry completed.'), 200);
    }

    public function show(array $request): array
    {
        $traceId = bin2hex(random_bytes(8));
        $requestedAt = microtime(true);
        $identifier = $this->routeParam($request, 'id');

        $this->logger->debug('RPA trace GET /submission start.', [
            'trace_id' => $traceId,
            'identifier' => $identifier,
            'request_timestamp' => date(DATE_ATOM),
            'request_microtime' => $requestedAt,
        ]);

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

        $draft = is_array($session['draft_payload'] ?? null) ? $session['draft_payload'] : json_decode((string) ($session['draft_payload'] ?? '{}'), true);
        $draft = is_array($draft) ? $draft : [];
        if ($verification === null && (($submission['request_type_code'] ?? '') === 'COMPANY_EMAIL_ID_CANCELLATION') && is_array($draft['company_rpa_result'] ?? null)) {
            $draftVerification = $draft['company_rpa_result'];
            $draftStatus = strtolower(trim((string) ($draftVerification['result_status'] ?? '')));
            $draftDisplayMessage = trim((string) ($draftVerification['display_message'] ?? ''));
            if ($draftStatus === 'pending' || $draftDisplayMessage !== '') {
                $verification = $draftVerification;
            }
        }

        $retryState = $this->resolveRetryState($submission, $verification);
        $nextAction = 'done';
        if (is_array($verification) && strtolower(trim((string) ($verification['result_status'] ?? ''))) === 'pending') {
            $nextAction = 'poll';
        }

        $this->logger->debug('RPA trace GET /submission resolved.', [
            'trace_id' => $traceId,
            'identifier' => $identifier,
            'request_id' => $submission['id'] ?? null,
            'response_timestamp' => date(DATE_ATOM),
            'response_microtime' => microtime(true),
            'verification_id' => $verification['id'] ?? null,
            'display_message_length' => strlen((string) ($verification['display_message'] ?? '')),
            'display_message_is_empty' => trim((string) ($verification['display_message'] ?? '')) === '',
        ]);

        return $this->success([
            'submission' => $submission,
            'session' => $session,
            'applicant' => $applicant,
            'documents' => $documents,
            'verification' => $verification,
            'next_action' => $nextAction,
            'retry_available' => $retryState['retry_available'],
            'retry_in_progress' => $retryState['retry_in_progress'],
            'retry_reason' => $retryState['retry_reason'],
        ], 'Submission retrieved.');
    }

    /**
     * @param array<string, mixed> $submission
     * @param array<string, mixed>|null $verification
     * @return array{retry_available: bool, retry_in_progress: bool, retry_reason: ?string}
     */
    private function resolveRetryState(array $submission, ?array $verification): array
    {
        $requestStatus = (string) ($submission['status'] ?? '');
        $hasDisplayMessage = is_array($verification) && trim((string) ($verification['display_message'] ?? '')) !== '';
        $retryAvailable = is_array($verification) && $hasDisplayMessage && (bool) ($verification['retry_available'] ?? false);
        $hasFinalState = in_array($requestStatus, ['failed', 'approved', 'manual_review', 'rejected', 'completed'], true);

        if ($requestStatus === 'under_review') {
            return [
                'retry_available' => false,
                'retry_in_progress' => true,
                'retry_reason' => 'Cancellation retry is already running.',
            ];
        }

        if ($retryAvailable) {
            return [
                'retry_available' => true,
                'retry_in_progress' => false,
                'retry_reason' => 'Cancellation retry is available.',
            ];
        }

        return [
            'retry_available' => false,
            'retry_in_progress' => false,
            'retry_reason' => $hasFinalState ? 'Cancellation has already reached a terminal state.' : null,
        ];
    }
}
