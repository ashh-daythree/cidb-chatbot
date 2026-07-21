# Phase 10 Final Review

This document freezes the backend implementation after Phases 1 to 10. The frontend flow remains the source of truth, and the backend is now ready for production-style deployment with mock verification.

## Backend Architecture

### Folder Structure

- `backend/bootstrap/`
  - Application container and bootstrap wiring.
- `backend/config/`
  - Environment-backed configuration objects and connection setup.
- `backend/controllers/`
  - Thin HTTP controllers that parse requests and call services.
- `backend/migrations/`
  - Migration interface, executor, and manager.
- `backend/models/`
  - One model per approved database table.
- `backend/public/`
  - Front controller entry point.
- `backend/repositories/`
  - Database access layer only.
- `backend/routes/`
  - Route definitions and router dispatching.
- `backend/services/`
  - All business logic, workflow orchestration, and transaction boundaries.
- `backend/storage/`
  - Storage abstraction for uploaded files.
- `backend/uploads/`
  - Reserved upload area if needed by future implementations.
- `backend/utils/`
  - JSON responses, logging, error handling, and exceptions.
- `backend/validators/`
  - Reusable request and domain validators.

### Design Principles Used

- Separation of concerns
- Thin controllers
- Service-layer business logic
- Repository-only database access
- PSR-4 autoloading
- Transactional consistency around multi-table workflow updates
- Secure file handling with randomized names

## Request Lifecycle

1. The browser hits the PHP front controller.
2. Environment and services are bootstrapped.
3. The router matches the incoming method and path.
4. The controller validates request structure.
5. The controller calls the relevant service.
6. The service validates business rules and coordinates repositories.
7. Database writes occur through repositories and PDO prepared statements.
8. The response is normalized as JSON.
9. Global error handling converts exceptions into structured JSON errors.

## Complete Chatbot Flow

1. `POST /session/start`
2. `POST /session/language`
3. `POST /session/state`
4. `POST /session/name`
5. `POST /session/identity`
6. `POST /documents/upload` for `IC_FRONT`
7. `POST /documents/upload` for `IC_BACK`
8. `POST /signature/upload`
9. `POST /submission`
10. Mock CIMS verification
11. Status updates and final response

## API Documentation

### Standard Response Shape

Success:

```json
{
  "success": true,
  "message": "OK",
  "data": {}
}
```

Error:

```json
{
  "success": false,
  "message": "An unexpected error occurred.",
  "error_code": "SERVER_ERROR",
  "errors": {}
}
```

### Endpoints

#### `POST /session/start`

- Request: `{ "workflow_code": "CIDB_EMAIL_ID_CANCELLATION" }`
- Response: session object
- Validation: workflow must exist and be active
- Errors: invalid workflow, invalid JSON

#### `POST /session/language`

- Request: `{ "session_id": "uuid", "language": "English" }`
- Response: updated session
- Validation: session ID required, language must be valid, step transition must be allowed

#### `POST /session/state`

- Request: `{ "session_id": "uuid", "state": "Selangor" }`
- Response: updated session
- Validation: session ID required, Malaysian state required, step transition must be allowed

#### `POST /session/name`

- Request: `{ "session_id": "uuid", "full_name": "Aisyah binti Ahmad" }`
- Response: updated session
- Validation: full name required, step transition must be allowed

#### `POST /session/identity`

- Request: `{ "session_id": "uuid", "identity_type": "MYKAD", "identity_number": "900101101234" }`
- Response: session plus applicant
- Validation: identity must be MyKad or passport, step transition must be allowed

#### `GET /session/{id}`

- Response: session, applicant, submission, and uploaded documents
- Validation: session must exist

#### `POST /documents/upload`

- Request: multipart form data with `session_id`, `document_type_code`, optional `request_id`, and uploaded file
- Response: document metadata row
- Validation: MIME, extension, filename, size, malware placeholder, checksum, duplicate detection

#### `POST /signature/upload`

- Request: `{ "session_id": "uuid", "signature_data_url": "data:image/png;base64,..." }`
- Response: signature document metadata row
- Validation: valid PNG data URL, decodes successfully, non-empty, size limit

#### `POST /submission`

- Request: `{ "session_id": "uuid", "cims": { "mock_outcome": "deleted" } }`
- Response: final submission payload
- Validation: session readiness, applicant completeness, required uploads, signature, identity, workflow state

#### `GET /submission/{id}`

- Identifier: request number or session ID
- Response: submission, session, applicant, documents
- Validation: record must exist

## Database Usage

### Services to Repositories

- `SessionService`
  - `ChatbotSessionRepository`
  - `ChatbotWorkflowRepository`
  - `ReferenceLanguageRepository`
  - `SessionValidator` support only
- `ApplicantService`
  - `ChatbotApplicantRepository`
  - `ChatbotSessionRepository`
  - `ReferenceLanguageRepository`
  - `ReferenceMalaysianStateRepository`
- `RequestService`
  - `ServiceRequestRepository`
  - `ChatbotSessionRepository`
  - `ChatbotApplicantRepository`
  - `ChatbotWorkflowRepository`
  - `ReferenceRequestTypeRepository`
- `DocumentService`
  - `UploadedDocumentRepository`
  - `DocumentVerificationRepository`
  - `ReferenceDocumentTypeRepository`
- `UploadService`
  - `ReferenceDocumentTypeRepository`
  - `UploadedDocumentRepository`
  - `DocumentStorageInterface`
- `SignatureService`
  - `SignatureValidator`
  - `UploadService`
- `VerificationService`
  - `CimsVerificationResultRepository`
  - `ServiceRequestRepository`
- `StatusService`
  - `ChatbotSessionRepository`
  - `ServiceRequestRepository`
  - `UploadedDocumentRepository`
  - `ChatbotStatusHistoryRepository`
  - `CimsVerificationResultRepository`
- `SubmissionService`
  - `SessionService`
  - `ApplicantService`
  - `RequestService`
  - `DocumentService`
  - `VerificationService`
  - `StatusService`
  - `SubmissionReadinessValidator`

### Table Access Pattern

- Session state: `chatbot_sessions`
- Applicant profile: `chatbot_applicants`
- Request lifecycle: `service_requests`
- Uploaded files: `uploaded_documents`
- File verification metadata: `document_verifications`
- Mock CIMS results: `cims_verification_results`
- Status history: `chatbot_status_history`
- Audit trail: `chatbot_audit_logs`
- Workflow and lookup data: reference tables and `chatbot_workflows`

## Upload Flow

### Storage Behavior

- Uploaded files are stored with random filenames.
- Client filenames are never used as storage names.
- Logical storage keys are generated by session and document type.
- Metadata is persisted in `uploaded_documents`.
- For local development, the runtime upload root should live outside the watched workspace, for example `C:/CIDB-RUNTIME/storage`, so browser live-reload does not trigger on IC upload writes.

### Validation Layers

- MIME type
- Extension
- Maximum file size
- Filename sanitation
- Double-extension detection
- Corruption and malware placeholder checks
- SHA-256 checksum
- Duplicate detection by session, document type, and checksum

### Rollback Behavior

- If storage fails, no database row is written.
- If the database write fails, the stored file is deleted.
- Signature uploads use the same storage pipeline through the signature service.

### Current Storage Provider

- `DocumentStorageInterface`
- `LocalDocumentStorage`

### Future Cloud Support

The storage provider can later be replaced with an S3, Azure Blob, or similar implementation by binding a new class to `DocumentStorageInterface`.

## Mock Verification Flow

### Current Behavior

`VerificationService` is the permanent development mock until a real CIMS API exists.

Supported outcomes:

- `deleted`
- `linked`
- `norecord`
- `error`
- `random`

### Configuration

- `CIMS_MOCK_MODE=fixed`
- `CIMS_MOCK_MODE=random`
- `CIMS_MOCK_MODE=env`
- `CIMS_MOCK_OUTCOME=deleted|linked|norecord|error`

### Replacement Point Later

Only the following should change when the real CIMS API arrives:

- `backend/services/VerificationService.php`
- `backend/config/CimsConfig.php`
- `backend/bootstrap/Bootstrap.php`

The rest of the application should remain unchanged.

## Logging Review

- Audit entries mask sensitive payload content.
- Error handler emits structured JSON errors.
- Logger writes to the configured log directory.
- Security-sensitive personal data is not logged in raw form.

## Configuration Review

### `.env.example`

Contains only non-secret defaults and placeholders for:

- app settings
- database settings
- storage settings
- logging settings
- upload limits
- mock verification settings
- CIMS placeholders

### Key Production Defaults

- `APP_DEBUG=false` in production
- `CIMS_MOCK_MODE=random` during development
- storage and log directories configured from environment
- local runtime uploads and logs should point to an external path outside the repo by default

## Performance Review

### Good

- Prepared statements only
- Repository-level filtering and pagination
- Indexed lookup paths for sessions, requests, documents, and verification results
- Random filename generation prevents collisions

### Areas To Watch

- Upload storage and database insert currently occur inside the same transaction for rollback safety. This is safe, but long-running uploads can hold a database transaction open.
- Large audit payloads should stay compact.
- Future cloud uploads should avoid buffering large files fully into memory.

## Security Review

### Strong Points

- SQL injection protection through prepared statements
- Path traversal protections in storage
- Filename randomization
- MIME and extension validation
- Checksum validation
- JSON validation
- Centralized exception handling
- Sensitive data masking in logs

### Remaining Operational Controls

- Production antivirus or object-storage scanning
- File retention policy
- Log rotation
- Backup strategy

## Final Review Report

### Potential Bugs

- If a future upload provider fails after the file is persisted but before DB write, cleanup must remain enforced by the storage adapter and upload service.
- Very large uploads should be tested against the configured memory and request body limits in the web server.
- Random mock outcomes are intentionally non-deterministic; tests should force fixed modes.

### Edge Cases

- Invalid JSON bodies
- Missing multipart file keys
- Duplicate uploads with same checksum
- Session step re-entry
- Submission attempts with incomplete documents
- Verification result `error`

### Maintainability Notes

- The service and repository split is clean.
- The router is minimal and stable.
- Future real CIMS integration will be isolated to the verifier and config.

### Scalability Notes

- The schema is normalized and indexed for lookup by session, request number, identity hash, and document checksum.
- Cloud object storage can be added without changing the controllers or submission flow.

## Deployment Checklist

- Set production `.env`
- Disable debug mode
- Configure database credentials
- Configure storage and log paths
- Run migrations
- Seed reference data
- Verify permissions for storage and logs
- Confirm PHP 8.2+ and PostgreSQL 15+
- Validate front controller entry point
- Test upload permissions

## Testing Checklist

- Session start
- Language selection
- State selection
- Name validation
- Identity validation
- IC front upload
- IC back upload
- Signature upload
- Duplicate upload rejection
- Submission with fixed mock outcomes
- Submission with random mock outcome
- Error-path submission
- JSON error responses
- Audit log masking

## Future Integration Checklist For Real CIMS

- Replace mock logic in `VerificationService`
- Map CIMS request/response payloads
- Add HTTP client implementation
- Add retry and timeout logic
- Add request signing or authentication
- Add integration error mapping
- Preserve the existing submission and status flow

## Final Summary

Implemented components:

- bootstrap and container
- configuration layer
- PostgreSQL connection
- migrations
- models
- validators
- repositories
- services
- REST APIs
- secure uploads
- mock verification
- logging
- error handling

This backend is now ready for production-style deployment with the current mock verification flow and frozen frontend conversation behavior.
