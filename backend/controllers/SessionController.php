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
        $workflowCode = trim((string) ($payload['workflow_code'] ?? 'CIDB_EMAIL_ID_CANCELLATION'));
        if ($workflowCode === '') {
            $workflowCode = 'CIDB_EMAIL_ID_CANCELLATION';
        }

        if ($workflowCode === 'EMAIL_ID_CANCELLATION') {
            $workflowCode = 'CIDB_EMAIL_ID_CANCELLATION';
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

    public function service(array $request): array
    {
        $payload = $this->payload($request);
        $sessionId = $this->requireString($payload, 'session_id', 'Session ID is required.', 'SESSION_ID_REQUIRED');
        $service = $payload['service_type'] ?? $payload['service'] ?? $payload['selection'] ?? $payload['choice'] ?? null;

        $session = $this->sessionService->saveServiceSelection($sessionId, $service);

        return $this->success([
            'session' => $session,
        ], 'Service saved.');
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

    public function mobile(array $request): array
    {
        $payload = $this->payload($request);
        $sessionId = $this->requireString($payload, 'session_id', 'Session ID is required.', 'SESSION_ID_REQUIRED');
        $mobile = $payload['mobile'] ?? $payload['mobile_number'] ?? $payload['phone'] ?? $payload['phone_number'] ?? null;

        $session = $this->sessionService->saveMobile($sessionId, $mobile);

        return $this->success([
            'session' => $session,
        ], 'Mobile saved.');
    }

    public function email(array $request): array
    {
        $payload = $this->payload($request);
        $sessionId = $this->requireString($payload, 'session_id', 'Session ID is required.', 'SESSION_ID_REQUIRED');
        $email = $payload['email'] ?? $payload['email_address'] ?? null;

        $session = $this->sessionService->saveEmail($sessionId, $email);

        return $this->success([
            'session' => $session,
        ], 'Email saved.');
    }

    public function companyPpk(array $request): array
    {
        $payload = $this->payload($request);
        $sessionId = $this->requireString($payload, 'session_id', 'Session ID is required.', 'SESSION_ID_REQUIRED');
        $ppk = $payload['ppk_number'] ?? $payload['ssm_number'] ?? $payload['registration_number'] ?? $payload['company_number'] ?? null;

        $session = $this->sessionService->saveCompanyPpkNumber($sessionId, $ppk);

        return $this->success([
            'session' => $session,
        ], 'Company registration number saved.');
    }

    public function companyName(array $request): array
    {
        $payload = $this->payload($request);
        $sessionId = $this->requireString($payload, 'session_id', 'Session ID is required.', 'SESSION_ID_REQUIRED');
        $companyName = $payload['company_name'] ?? $payload['name'] ?? null;

        $session = $this->sessionService->saveCompanyName($sessionId, $companyName);

        return $this->success([
            'session' => $session,
        ], 'Company name saved.');
    }

    public function companyEmail(array $request): array
    {
        $payload = $this->payload($request);
        $sessionId = $this->requireString($payload, 'session_id', 'Session ID is required.', 'SESSION_ID_REQUIRED');
        $email = $payload['company_email'] ?? $payload['email'] ?? $payload['email_address'] ?? null;

        $session = $this->sessionService->saveCompanyEmail($sessionId, $email);

        return $this->success([
            'session' => $session,
        ], 'Company email saved.');
    }

    public function companyCategory(array $request): array
    {
        $payload = $this->payload($request);
        $sessionId = $this->requireString($payload, 'session_id', 'Session ID is required.', 'SESSION_ID_REQUIRED');
        $category = $payload['category'] ?? $payload['company_category'] ?? null;

        $session = $this->sessionService->saveCompanyCategory($sessionId, $category);

        return $this->success([
            'session' => $session,
        ], 'Company category saved.');
    }

    public function companyDirectorName(array $request): array
    {
        $payload = $this->payload($request);
        $sessionId = $this->requireString($payload, 'session_id', 'Session ID is required.', 'SESSION_ID_REQUIRED');
        $name = $payload['director_full_name'] ?? $payload['full_name'] ?? $payload['name'] ?? null;

        $session = $this->sessionService->saveCompanyDirectorName($sessionId, $name);

        return $this->success([
            'session' => $session,
        ], 'Director name saved.');
    }

    public function companyDirectorIdentity(array $request): array
    {
        $payload = $this->payload($request);
        $sessionId = $this->requireString($payload, 'session_id', 'Session ID is required.', 'SESSION_ID_REQUIRED');
        $identity = $payload['director_identity_number'] ?? $payload['identity_number'] ?? $payload['ic'] ?? $payload['passport'] ?? $payload['identity'] ?? null;

        $result = $this->sessionService->saveCompanyDirectorIdentity($sessionId, [
            'identity_type' => $payload['identity_type'] ?? null,
            'identity_number' => $identity,
        ]);

        return $this->success($result, 'Director identity saved.');
    }

    public function companyReason(array $request): array
    {
        $payload = $this->payload($request);
        $sessionId = $this->requireString($payload, 'session_id', 'Session ID is required.', 'SESSION_ID_REQUIRED');
        $reason = $payload['reason'] ?? $payload['cancellation_reason'] ?? $payload['company_reason'] ?? null;

        $session = $this->sessionService->saveCompanyReason($sessionId, $reason);

        return $this->success([
            'session' => $session,
        ], 'Company cancellation reason saved.');
    }

    public function faqTopic(array $request): array
    {
        $payload = $this->payload($request);
        $sessionId = $this->requireString($payload, 'session_id', 'Session ID is required.', 'SESSION_ID_REQUIRED');
        $topic = $payload['topic_code'] ?? $payload['topic'] ?? null;

        $result = $this->sessionService->saveFaqTopicSelection($sessionId, $topic);

        return $this->success($result, 'FAQ topic saved.');
    }

    public function faqSubtopic(array $request): array
    {
        $payload = $this->payload($request);
        $sessionId = $this->requireString($payload, 'session_id', 'Session ID is required.', 'SESSION_ID_REQUIRED');
        $subtopic = $payload['subtopic_code'] ?? $payload['subtopic'] ?? null;

        $result = $this->sessionService->saveFaqSubtopicSelection($sessionId, $subtopic);

        return $this->success($result, 'FAQ subtopic saved.');
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
