<?php

declare(strict_types=1);

namespace Cidb\Backend\Validators;

final class SubmissionReadinessValidator extends AbstractValidator
{
    public function __construct(
        private readonly ?SessionValidator $sessionValidator = null,
        private readonly ?LanguageValidator $languageValidator = null,
        private readonly ?MalaysianStateValidator $stateValidator = null,
        private readonly ?FullNameValidator $fullNameValidator = null,
        private readonly ?IdentityValidator $identityValidator = null
    ) {
    }

    public function validate(mixed $input): ValidationResult
    {
        $payload = is_array($input) ? $input : [];

        if ($payload === []) {
            return $this->invalid('submission', 'submission_required', 'Submission payload is required.');
        }

        $sessionValidator = $this->sessionValidator ?? new SessionValidator();
        $languageValidator = $this->languageValidator ?? new LanguageValidator();
        $stateValidator = $this->stateValidator ?? new MalaysianStateValidator();
        $fullNameValidator = $this->fullNameValidator ?? new FullNameValidator();
        $identityValidator = $this->identityValidator ?? new IdentityValidator();

        $result = ValidationResult::success();

        if (isset($payload['session'])) {
            $sessionResult = $sessionValidator->validate($payload['session']);
            $result->merge($sessionResult);

            $currentStep = is_array($payload['session']) ? ($payload['session']['current_step'] ?? null) : null;
            if (is_string($currentStep) && $currentStep !== 'ask_ic_copy') {
                $result->addError(
                    'session.current_step',
                    'session_not_ready',
                    'Session is not ready for submission.',
                    $currentStep,
                    ['required_step' => 'ask_ic_copy']
                );
            }
        } else {
            $result->addError('session', 'session_required', 'Session is required for submission.');
        }

        if (isset($payload['language'])) {
            $result->merge($languageValidator->validate($payload['language']));
        } else {
            $result->addError('language', 'language_required', 'Language is required for submission.');
        }

        if (isset($payload['state'])) {
            $result->merge($stateValidator->validate($payload['state']));
        } else {
            $result->addError('state', 'state_required', 'State is required for submission.');
        }

        if (isset($payload['full_name'])) {
            $result->merge($fullNameValidator->validate($payload['full_name']));
        } else {
            $result->addError('full_name', 'full_name_required', 'Full name is required for submission.');
        }

        if (isset($payload['identity_number'])) {
            $result->merge($identityValidator->validate($payload['identity_number']));
        } else {
            $result->addError('identity_number', 'identity_required', 'Identity number is required for submission.');
        }

        $documents = is_array($payload['documents'] ?? null) ? $payload['documents'] : [];

        if ($documents === []) {
            $result->addError('documents', 'documents_required', 'Required documents are missing.');
        } else {
            if (!array_key_exists('front', $documents)) {
                $result->addError('documents.front', 'front_document_required', 'IC front document is required.');
            } else {
                $result->merge($this->validateStoredDocument($documents['front'], 'documents.front', 'IC_FRONT'));
            }

            if (!array_key_exists('back', $documents)) {
                $result->addError('documents.back', 'back_document_required', 'IC back document is required.');
            } else {
                $result->merge($this->validateStoredDocument($documents['back'], 'documents.back', 'IC_BACK'));
            }

            if (!array_key_exists('signature', $documents)) {
                $result->addError('documents.signature', 'signature_document_required', 'Signature document is required.');
            } else {
                $result->merge($this->validateStoredDocument($documents['signature'], 'documents.signature', 'SIGNATURE'));
            }
        }

        return $result;
    }

    private function validateStoredDocument(mixed $document, string $field, string $expectedTypeCode): ValidationResult
    {
        if (!is_array($document) || $document === []) {
            return $this->invalid($field, 'document_invalid', 'Document record is invalid.');
        }

        $result = ValidationResult::success();

        $typeCode = (string) ($document['document_type_code'] ?? '');
        if ($typeCode === '') {
            $result->addError($field . '.document_type_code', 'document_type_code_required', 'Document type code is required.');
        } elseif ($typeCode !== $expectedTypeCode) {
            $result->addError(
                $field . '.document_type_code',
                'document_type_code_invalid',
                'Document type code does not match the expected slot.',
                $typeCode,
                ['expected' => $expectedTypeCode]
            );
        }

        $status = (string) ($document['upload_status'] ?? '');
        if ($status === '') {
            $result->addError($field . '.upload_status', 'upload_status_required', 'Upload status is required.');
        } elseif (!in_array($status, ['stored', 'quarantined', 'deleted'], true)) {
            $result->addError($field . '.upload_status', 'upload_status_invalid', 'Upload status is invalid.', $status);
        } elseif ($status !== 'stored') {
            $result->addError($field . '.upload_status', 'upload_not_ready', 'Document is not ready for submission.', $status);
        }

        foreach (['id' => 'document_id', 'storage_path' => 'storage_path', 'sha256_checksum' => 'sha256_checksum'] as $sourceField => $label) {
            if (!array_key_exists($sourceField, $document) || trim((string) $document[$sourceField]) === '') {
                $result->addError($field . '.' . $sourceField, $label . '_required', ucfirst(str_replace('_', ' ', $sourceField)) . ' is required.');
            }
        }

        if (isset($document['file_size_bytes']) && (int) $document['file_size_bytes'] <= 0) {
            $result->addError($field . '.file_size_bytes', 'file_size_invalid', 'File size must be greater than zero.', $document['file_size_bytes']);
        }

        return $result;
    }
}
