# CIDB Chatbot Database Contract

This document is database-only. It uses `home.html` as the source of truth for the current chatbot flow and freezes the final PostgreSQL schema for the backend.

## 1. Frontend Flow Source Of Truth

The frontend state machine is:

- `ask_lang`
- `ask_state`
- `ask_name`
- `ask_ic`
- `ask_ic_copy`
- `done`

The frontend currently collects:

- language
- Malaysian state
- full name
- IC / passport number
- IC front upload
- IC back upload
- signature drawn in the canvas

Frontend validation rules that the database contract must support:

- language must be English or Bahasa Malaysia
- state must be one of the approved Malaysian states
- name must be non-empty
- IC may be MyKad or passport
- MyKad is normalized to `######-##-####`
- passport is uppercased
- IC front and back are required
- signature is required
- uploads must be limited to safe file types and file sizes

## 2. Schema Review And Simplification Decisions

I reviewed the previous draft and removed or merged anything that did not clearly earn a place in the production schema.

Merged into other tables:

- no separate `signatures` table; the signature is stored as an `uploaded_documents` row with document type `SIGNATURE` and capture mode `signature_pad`
- no separate `cancellation_requests` table; `service_requests` is the normalized request parent and can support future application types through `reference_request_types`

Removed as unnecessary for the core database contract:

- `chatbot_messages`
- `chatbot_api_logs`

Why they were removed:

- the frontend conversation text is not needed as a separate permanent business entity
- API telemetry is better handled by application observability unless it becomes part of the business workflow
- the important traceability requirements are already covered by session state, status history, audit logs, verification logs, and CIMS result logs

Kept because they are structurally useful and not over-engineered:

- `chatbot_workflows`
- `reference_languages`
- `reference_malaysian_states`
- `reference_request_types`
- `reference_document_types`
- `chatbot_sessions`
- `chatbot_applicants`
- `service_requests`
- `uploaded_documents`
- `document_verifications`
- `cims_verification_results`
- `chatbot_status_history`
- `chatbot_audit_logs`
- `chatbot_configuration`

## 3. Final Approved Tables

### 3.1 `chatbot_workflows`

Purpose:

- defines the chatbot workflow version being used
- supports future multiple chatbot workflows without changing the request schema

Why it exists:

- the current project has one flow today, but the backend should not hardcode the flow into session rows

Relationships:

- one workflow has many sessions
- one workflow has many service requests

Primary key:

- `id`

Foreign keys:

- none

Columns:

- `id` uuid, not null, default `gen_random_uuid()`
- `workflow_code` varchar(80), not null
- `workflow_name` varchar(150), not null
- `description` text, nullable
- `version` varchar(30), not null, default `'1.0'`
- `is_active` boolean, not null, default `true`
- `created_at` timestamptz, not null, default `now()`
- `updated_at` timestamptz, not null, default `now()`

Constraints:

- primary key on `id`
- unique on `workflow_code`
- check `version <> ''`

Indexes:

- unique index on `workflow_code`
- index on `is_active`

Performance considerations:

- tiny lookup table, read-heavy, no special tuning needed beyond the unique index

Security considerations:

- no sensitive data stored here

---

### 3.2 `reference_languages`

Purpose:

- stores supported conversation languages

Why it exists:

- the frontend allows exactly English and Bahasa Malaysia today, but more languages may be added later

Relationships:

- one language can be used by many sessions
- one language can be used by many applicants
- one language can be used by many service requests
- one language can be used by many messages if messages are added later

Primary key:

- `code`

Foreign keys:

- none

Columns:

- `code` varchar(10), not null
- `language_name` varchar(80), not null
- `locale_tag` varchar(20), nullable
- `is_active` boolean, not null, default `true`
- `created_at` timestamptz, not null, default `now()`
- `updated_at` timestamptz, not null, default `now()`

Constraints:

- primary key on `code`
- `code` should be short and stable, for example `en`, `ms`

Indexes:

- primary key index
- index on `is_active`

Performance considerations:

- tiny static lookup table

Security considerations:

- no sensitive data stored here

---

### 3.3 `reference_malaysian_states`

Purpose:

- stores the approved Malaysian states list used by the chatbot

Why it exists:

- the frontend state selection is a business control and must be enforced server-side with a lookup table

Relationships:

- one state can be linked to many applicants

Primary key:

- `state_code`

Foreign keys:

- none

Columns:

- `state_code` varchar(10), not null
- `state_name` varchar(50), not null
- `display_order` smallint, not null, default `0`
- `is_active` boolean, not null, default `true`
- `created_at` timestamptz, not null, default `now()`
- `updated_at` timestamptz, not null, default `now()`

Constraints:

- primary key on `state_code`
- unique on `state_name`

Indexes:

- unique index on `state_name`
- index on `is_active`
- index on `display_order`

Performance considerations:

- supports fast dropdown population and exact state validation

Security considerations:

- no sensitive data stored here

---

### 3.4 `reference_request_types`

Purpose:

- stores supported business request types

Why it exists:

- the current frontend is for email ID cancellation, but future application types should not require a schema rewrite

Relationships:

- one request type can be used by many service requests

Primary key:

- `request_type_code`

Foreign keys:

- none

Columns:

- `request_type_code` varchar(30), not null
- `label_en` varchar(120), not null
- `label_ms` varchar(120), not null
- `description` text, nullable
- `is_active` boolean, not null, default `true`
- `created_at` timestamptz, not null, default `now()`
- `updated_at` timestamptz, not null, default `now()`

Constraints:

- primary key on `request_type_code`

Indexes:

- primary key index
- index on `is_active`

Performance considerations:

- tiny static lookup table

Security considerations:

- no sensitive data stored here

---

### 3.5 `reference_document_types`

Purpose:

- stores the document types allowed by the chatbot

Why it exists:

- the current flow requires IC front, IC back, and signature
- future document additions should be configuration-driven rather than hardcoded

Relationships:

- one document type can be used by many uploaded documents
- one document type can drive many validation records

Primary key:

- `document_type_code`

Foreign keys:

- none

Columns:

- `document_type_code` varchar(30), not null
- `label_en` varchar(100), not null
- `label_ms` varchar(100), not null
- `capture_mode` varchar(20), not null
- `is_required_for_submission` boolean, not null, default `true`
- `allow_multiple` boolean, not null, default `false`
- `sort_order` smallint, not null, default `0`
- `allowed_mime_types` jsonb, not null, default `'[]'::jsonb`
- `max_file_size_mb` integer, not null, default `10`
- `requires_ocr` boolean, not null, default `false`
- `is_active` boolean, not null, default `true`
- `created_at` timestamptz, not null, default `now()`
- `updated_at` timestamptz, not null, default `now()`

Constraints:

- primary key on `document_type_code`
- `capture_mode` must be one of:
  - `upload`
  - `signature_pad`
- `max_file_size_mb > 0`
- `allowed_mime_types` must be a JSON array

Indexes:

- primary key index
- index on `capture_mode`
- index on `is_active`
- index on `sort_order`

Performance considerations:

- keeps file-type and size policy in the database so validation can be data-driven

Security considerations:

- file policy lives here, but the backend must still enforce MIME, extension, magic-byte, and size checks

---

### 3.6 `chatbot_sessions`

Purpose:

- stores the live conversation session and step state

Why it exists:

- the frontend is a state machine; this table keeps the backend aligned with the exact flow and prevents step skipping

Relationships:

- many sessions belong to one workflow
- one session can have at most one applicant row
- one session can have at most one service request row
- one session can have many uploaded documents
- one session can have many audit logs
- one session can have many status history rows

Primary key:

- `id`

Foreign keys:

- `workflow_id` -> `chatbot_workflows.id`
- `language_code` -> `reference_languages.code`

Columns:

- `id` uuid, not null, default `gen_random_uuid()`
- `workflow_id` uuid, not null
- `language_code` varchar(10), nullable
- `status` varchar(30), not null, default `'awaiting_language'`
- `current_step` varchar(30), not null, default `'ask_lang'`
- `draft_payload` jsonb, not null, default `'{}'::jsonb`
- `started_at` timestamptz, not null, default `now()`
- `last_activity_at` timestamptz, not null, default `now()`
- `completed_at` timestamptz, nullable
- `expired_at` timestamptz, nullable
- `created_at` timestamptz, not null, default `now()`
- `updated_at` timestamptz, not null, default `now()`

Constraints:

- primary key on `id`
- foreign key on `workflow_id`
- foreign key on `language_code`
- `status` must be one of:
  - `awaiting_language`
  - `awaiting_state`
  - `awaiting_name`
  - `awaiting_identity`
  - `awaiting_documents`
  - `submitted`
  - `under_review`
  - `completed`
  - `abandoned`
  - `expired`
  - `failed`
- `current_step` must be one of:
  - `ask_lang`
  - `ask_state`
  - `ask_name`
  - `ask_ic`
  - `ask_ic_copy`
  - `done`
- `draft_payload` must be a JSON object

Indexes:

- index on `workflow_id`
- index on `language_code`
- index on `status`
- index on `current_step`
- index on `last_activity_at`
- index on `started_at`

Performance considerations:

- this is the main live lookup table for the chatbot
- the `last_activity_at` index supports cleanup, timeout, and retry scans

Security considerations:

- keep only temporary state here
- do not store raw document content in `draft_payload`

---

### 3.7 `chatbot_applicants`

Purpose:

- stores the applicant identity record separate from the transient session

Why it exists:

- the identity data is the durable core of the request, while the session is temporary conversation state
- separating applicant data from the session keeps the schema normalized and supports future request types

Relationships:

- one applicant belongs to exactly one session
- many service requests can point to an applicant in the future if business rules expand, but the current flow expects one request per session
- one applicant belongs to one state and one language

Primary key:

- `id`

Foreign keys:

- `session_id` -> `chatbot_sessions.id`
- `state_code` -> `reference_malaysian_states.state_code`
- `language_code` -> `reference_languages.code`

Columns:

- `id` uuid, not null, default `gen_random_uuid()`
- `session_id` uuid, not null
- `full_name_ciphertext` bytea, not null
- `full_name_hash` char(64), not null
- `identity_type` varchar(20), not null
- `identity_number_ciphertext` bytea, not null
- `identity_number_hash` char(64), not null
- `identity_number_last4` varchar(4), nullable
- `state_code` varchar(10), not null
- `language_code` varchar(10), not null
- `verification_status` varchar(20), not null, default `'pending'`
- `is_draft` boolean, not null, default `true`
- `created_at` timestamptz, not null, default `now()`
- `updated_at` timestamptz, not null, default `now()`

Constraints:

- primary key on `id`
- unique on `session_id`
- `identity_type` must be one of:
  - `MYKAD`
  - `PASSPORT`
- `verification_status` must be one of:
  - `pending`
  - `verified`
  - `rejected`
- `full_name_hash` must be 64 hex characters if stored as lowercase SHA-256 hex
- `identity_number_hash` must be 64 hex characters if stored as lowercase SHA-256 hex
- `identity_number_last4` must be 4 alphanumeric characters when present

Indexes:

- unique index on `session_id`
- index on `identity_number_hash`
- index on `state_code`
- index on `language_code`
- index on `verification_status`
- index on `created_at`

Performance considerations:

- search by IC / passport should always use the hash column, not plaintext
- the session-to-applicant unique link keeps retrieval simple and fast

Security considerations:

- encrypt full name and identity number at rest
- never store the full IC / passport number in plaintext
- use the hash for lookup and duplicate detection
- mask the identity number in any UI summary or logs

---

### 3.8 `service_requests`

Purpose:

- stores the final business request created from the chatbot session

Why it exists:

- the request is the durable business record that the backend and future integrations should work against

Relationships:

- many service requests belong to one workflow
- one service request belongs to one session
- one service request belongs to one applicant
- one service request has many uploaded documents
- one service request has many CIMS results
- one service request has many status history rows
- one service request has many audit logs

Primary key:

- `id`

Foreign keys:

- `workflow_id` -> `chatbot_workflows.id`
- `session_id` -> `chatbot_sessions.id`
- `applicant_id` -> `chatbot_applicants.id`
- `request_type_code` -> `reference_request_types.request_type_code`
- `submission_language_code` -> `reference_languages.code`

Columns:

- `id` uuid, not null, default `gen_random_uuid()`
- `request_number` varchar(40), not null
- `workflow_id` uuid, not null
- `session_id` uuid, not null
- `applicant_id` uuid, not null
- `request_type_code` varchar(30), not null
- `status` varchar(30), not null, default `'draft'`
- `submission_language_code` varchar(10), not null
- `submitted_at` timestamptz, nullable
- `latest_cims_status` varchar(20), not null, default `'pending'`
- `final_outcome` varchar(20), nullable
- `final_outcome_at` timestamptz, nullable
- `closed_at` timestamptz, nullable
- `created_at` timestamptz, not null, default `now()`
- `updated_at` timestamptz, not null, default `now()`

Constraints:

- primary key on `id`
- unique on `request_number`
- unique on `session_id`
- `status` must be one of:
  - `draft`
  - `submitted`
  - `under_review`
  - `pending_cims`
  - `approved`
  - `rejected`
  - `manual_review`
  - `cancelled`
  - `failed`
- `latest_cims_status` must be one of:
  - `pending`
  - `deleted`
  - `linked`
  - `norecord`
  - `error`
- `final_outcome` must be one of:
  - `deleted`
  - `linked`
  - `norecord`

Indexes:

- unique index on `request_number`
- unique index on `session_id`
- index on `workflow_id`
- index on `applicant_id`
- index on `request_type_code`
- index on `status`
- index on `latest_cims_status`
- index on `submitted_at`
- index on `created_at`

Performance considerations:

- `request_number`, `status`, `latest_cims_status`, and `submitted_at` are the primary query paths
- keep the request row narrow and push document metadata into `uploaded_documents`

Security considerations:

- the request row should not duplicate full PII unless required by business rules
- use applicant encryption and hashes for identity

---

### 3.9 `uploaded_documents`

Purpose:

- stores metadata for IC front, IC back, and signature files

Why it exists:

- the actual file content should live in secure file storage, while the database stores immutable metadata, verification state, and pointers

Relationships:

- one uploaded document belongs to one session
- one uploaded document may later be attached to one service request
- one uploaded document belongs to one document type
- one uploaded document can have many verification rows

Primary key:

- `id`

Foreign keys:

- `session_id` -> `chatbot_sessions.id`
- `request_id` -> `service_requests.id`
- `document_type_code` -> `reference_document_types.document_type_code`

Columns:

- `id` uuid, not null, default `gen_random_uuid()`
- `session_id` uuid, not null
- `request_id` uuid, nullable
- `document_type_code` varchar(30), not null
- `upload_source` varchar(20), not null, default `'user_upload'`
- `storage_disk` varchar(50), not null, default `'local'`
- `storage_path` text, not null
- `storage_file_name` text, not null
- `original_file_name_ciphertext` bytea, nullable
- `mime_type` varchar(100), not null
- `file_extension` varchar(10), not null
- `file_size_bytes` bigint, not null
- `sha256_checksum` char(64), not null
- `upload_status` varchar(20), not null, default `'pending'`
- `security_status` varchar(20), not null, default `'not_scanned'`
- `metadata` jsonb, not null, default `'{}'::jsonb`
- `created_at` timestamptz, not null, default `now()`
- `updated_at` timestamptz, not null, default `now()`

Constraints:

- primary key on `id`
- `storage_path` unique
- `storage_file_name` unique
- `upload_source` must be one of:
  - `user_upload`
  - `signature_pad`
  - `system_import`
- `upload_status` must be one of:
  - `pending`
  - `stored`
  - `quarantined`
  - `rejected`
  - `deleted`
- `security_status` must be one of:
  - `not_scanned`
  - `clean`
  - `infected`
  - `error`
- `file_size_bytes > 0`
- `metadata` must be a JSON object

Indexes:

- index on `session_id`
- index on `request_id`
- index on `document_type_code`
- index on `sha256_checksum`
- index on `upload_status`
- index on `security_status`
- index on `created_at`

Performance considerations:

- random file names make storage efficient and avoid filename collisions
- indexing `document_type_code` and `request_id` supports document retrieval by request and document slot

Security considerations:

- do not trust the original filename
- randomize storage filenames
- validate MIME type, extension, magic bytes, and file size again server-side
- store signature images exactly like other uploads, but keep the source as `signature_pad`

---

### 3.10 `document_verifications`

Purpose:

- stores validation and verification history for uploaded documents

Why it exists:

- document checks are not one-off values; they may be automated, manual, or repeated later

Relationships:

- many verifications belong to one uploaded document

Primary key:

- `id`

Foreign keys:

- `uploaded_document_id` -> `uploaded_documents.id`

Columns:

- `id` uuid, not null, default `gen_random_uuid()`
- `uploaded_document_id` uuid, not null
- `verification_type` varchar(30), not null
- `verifier` varchar(30), not null
- `status` varchar(20), not null
- `score` numeric(5,2), nullable
- `reason_code` varchar(50), nullable
- `reason_message` text, nullable
- `details` jsonb, not null, default `'{}'::jsonb`
- `verified_at` timestamptz, not null, default `now()`
- `created_at` timestamptz, not null, default `now()`

Constraints:

- primary key on `id`
- `verification_type` must be one of:
  - `file_integrity`
  - `mime_check`
  - `size_check`
  - `malware_scan`
  - `ocr_quality`
  - `signature_quality`
  - `manual_review`
- `verifier` must be one of:
  - `system`
  - `agent`
  - `ai`
  - `cims`
- `status` must be one of:
  - `pending`
  - `passed`
  - `failed`
  - `warning`
- `score` must be between 0 and 100 when present
- `details` must be a JSON object

Indexes:

- index on `uploaded_document_id`
- index on `verification_type`
- index on `status`
- index on `verified_at`

Performance considerations:

- supports repeat verification and future OCR/AI checks without altering the upload table

Security considerations:

- store only verification metadata and safe excerpts
- do not store raw document images inside the verification table

---

### 3.11 `cims_verification_results`

Purpose:

- stores every CIMS verification attempt and its outcome

Why it exists:

- the frontend ends with a CIMS outcome branch, and future integrations need an auditable result trail

Relationships:

- many CIMS results belong to one service request

Primary key:

- `id`

Foreign keys:

- `request_id` -> `service_requests.id`

Columns:

- `id` uuid, not null, default `gen_random_uuid()`
- `request_id` uuid, not null
- `attempt_no` integer, not null
- `result_status` varchar(20), not null
- `response_code` varchar(50), nullable
- `response_message` text, nullable
- `external_reference_no` varchar(80), nullable
- `latency_ms` integer, nullable
- `response_payload` jsonb, not null, default `'{}'::jsonb`
- `verified_at` timestamptz, nullable
- `created_at` timestamptz, not null, default `now()`

Constraints:

- primary key on `id`
- unique on `(request_id, attempt_no)`
- `attempt_no > 0`
- `result_status` must be one of:
  - `pending`
  - `deleted`
  - `linked`
  - `norecord`
  - `error`
- `latency_ms` must be greater than or equal to 0 when present
- `response_payload` must be a JSON object

Indexes:

- unique index on `(request_id, attempt_no)`
- index on `request_id`
- index on `result_status`
- index on `created_at`

Performance considerations:

- the composite unique key supports retries cleanly
- result status indexing supports fast outcome search

Security considerations:

- keep the response payload redacted to the minimum needed for business traceability

---

### 3.12 `chatbot_status_history`

Purpose:

- stores the history of state transitions for sessions, requests, documents, and CIMS processing

Why it exists:

- status history is essential for troubleshooting, support, and auditability

Relationships:

- a status history row may belong to a session, a service request, or a document

Primary key:

- `id`

Foreign keys:

- `session_id` -> `chatbot_sessions.id`
- `request_id` -> `service_requests.id`
- `document_id` -> `uploaded_documents.id`

Columns:

- `id` uuid, not null, default `gen_random_uuid()`
- `session_id` uuid, nullable
- `request_id` uuid, nullable
- `document_id` uuid, nullable
- `status_scope` varchar(20), not null
- `from_status` varchar(30), nullable
- `to_status` varchar(30), not null
- `changed_by_type` varchar(20), not null
- `changed_by_reference` varchar(80), nullable
- `reason_code` varchar(50), nullable
- `reason_message` text, nullable
- `metadata` jsonb, not null, default `'{}'::jsonb`
- `created_at` timestamptz, not null, default `now()`

Constraints:

- primary key on `id`
- at least one of `session_id`, `request_id`, or `document_id` must be present
- `status_scope` must be one of:
  - `session`
  - `request`
  - `document`
  - `cims`
- `changed_by_type` must be one of:
  - `user`
  - `system`
  - `agent`
  - `integration`
- `metadata` must be a JSON object

Indexes:

- index on `session_id`
- index on `request_id`
- index on `document_id`
- index on `status_scope`
- index on `created_at`

Performance considerations:

- separate history rows keep the main request and session tables lean

Security considerations:

- store only the minimum necessary reason text and metadata

---

### 3.13 `chatbot_audit_logs`

Purpose:

- stores validation failures, upload failures, security events, and server-side business exceptions

Why it exists:

- audit logging is needed for production support and compliance, but the logs must not expose raw sensitive data

Relationships:

- a log row may be linked to a session, a request, or both

Primary key:

- `id`

Foreign keys:

- `session_id` -> `chatbot_sessions.id`
- `request_id` -> `service_requests.id`

Columns:

- `id` uuid, not null, default `gen_random_uuid()`
- `correlation_id` uuid, not null, default `gen_random_uuid()`
- `session_id` uuid, nullable
- `request_id` uuid, nullable
- `event_type` varchar(50), not null
- `severity` varchar(10), not null
- `actor_type` varchar(20), not null
- `actor_reference` varchar(80), nullable
- `message` text, not null
- `masked_payload` jsonb, not null, default `'{}'::jsonb`
- `ip_hash` bytea, nullable
- `user_agent_hash` char(64), nullable
- `created_at` timestamptz, not null, default `now()`

Constraints:

- primary key on `id`
- `severity` must be one of:
  - `debug`
  - `info`
  - `warning`
  - `error`
  - `security`
- `actor_type` must be one of:
  - `user`
  - `bot`
  - `agent`
  - `system`
  - `integration`
- `masked_payload` must be a JSON object

Indexes:

- index on `correlation_id`
- index on `session_id`
- index on `request_id`
- index on `event_type`
- index on `severity`
- index on `created_at`

Performance considerations:

- write-heavy table, so keep the payload compact and index only the fields used for support and security investigations

Security considerations:

- never store full IC / passport numbers, raw signature images, or raw document contents here
- store only masked or hashed payload fragments

---

### 3.14 `chatbot_configuration`

Purpose:

- stores runtime configuration and policy values that should be data-driven

Why it exists:

- allows upload limits, retention rules, and feature flags to change without schema changes

Relationships:

- none required

Primary key:

- `id`

Foreign keys:

- none

Columns:

- `id` uuid, not null, default `gen_random_uuid()`
- `config_key` varchar(100), not null
- `config_group` varchar(50), not null, default `'general'`
- `config_value` jsonb, not null
- `is_sensitive` boolean, not null, default `false`
- `description` text, nullable
- `created_at` timestamptz, not null, default `now()`
- `updated_at` timestamptz, not null, default `now()`

Constraints:

- primary key on `id`
- unique on `config_key`
- `config_value` must be a JSON object or scalar value suitable for the key

Indexes:

- unique index on `config_key`
- index on `config_group`
- index on `is_sensitive`

Performance considerations:

- small table, used for application policy reads

Security considerations:

- mark secrets and sensitive integration settings with `is_sensitive = true`

## 4. Entity Relationship Diagram

```
chatbot_workflows
    |---1:M---- chatbot_sessions
    |---1:M---- service_requests

reference_languages
    |---1:M---- chatbot_sessions
    |---1:M---- chatbot_applicants
    |---1:M---- service_requests

reference_malaysian_states
    |---1:M---- chatbot_applicants

reference_request_types
    |---1:M---- service_requests

reference_document_types
    |---1:M---- uploaded_documents

chatbot_sessions
    |---1:1---- chatbot_applicants
    |---1:1---- service_requests
    |---1:M---- uploaded_documents
    |---1:M---- chatbot_status_history
    |---1:M---- chatbot_audit_logs

chatbot_applicants
    |---1:1---- chatbot_sessions
    |---1:M---- service_requests (future expansion only; current flow uses one request per session)

service_requests
    |---1:M---- uploaded_documents
    |---1:M---- cims_verification_results
    |---1:M---- chatbot_status_history
    |---1:M---- chatbot_audit_logs

uploaded_documents
    |---1:M---- document_verifications
    |---1:M---- chatbot_status_history

chatbot_configuration
    |--- standalone lookup table
```

## 5. Migration Order

Recommended creation order:

1. `chatbot_workflows`
2. `reference_languages`
3. `reference_malaysian_states`
4. `reference_request_types`
5. `reference_document_types`
6. `chatbot_configuration`
7. `chatbot_sessions`
8. `chatbot_applicants`
9. `service_requests`
10. `uploaded_documents`
11. `document_verifications`
12. `cims_verification_results`
13. `chatbot_status_history`
14. `chatbot_audit_logs`

## 6. Recommended Migration Folder

If migrations are used, keep them separate from application code:

```text
backend/
  database/
    migrations/
      001_create_extensions.sql
      002_create_reference_tables.sql
      003_create_core_entities.sql
      004_create_document_tables.sql
      005_create_history_and_audit_tables.sql
      006_seed_reference_data.sql
    seeds/
      seed_chatbot_configuration.sql
```

Recommended practice:

- keep one migration per logical change set
- seed lookup tables separately from structural DDL
- never hand-edit old migrations after release

## 7. Performance Review

Indexing priorities:

- `chatbot_sessions.status`
- `chatbot_sessions.current_step`
- `chatbot_sessions.last_activity_at`
- `chatbot_applicants.identity_number_hash`
- `service_requests.request_number`
- `service_requests.status`
- `service_requests.latest_cims_status`
- `service_requests.submitted_at`
- `uploaded_documents.session_id`
- `uploaded_documents.request_id`
- `uploaded_documents.document_type_code`
- `uploaded_documents.sha256_checksum`
- `cims_verification_results.request_id`
- `cims_verification_results.result_status`
- `chatbot_audit_logs.correlation_id`

Query patterns supported:

- search by IC / passport hash
- search by request number
- search by status
- search by CIMS result
- search by created date
- retrieval of all documents for a request
- retrieval of active sessions by last activity

## 8. Security Review

Fields to encrypt:

- `chatbot_applicants.full_name_ciphertext`
- `chatbot_applicants.identity_number_ciphertext`
- `uploaded_documents.original_file_name_ciphertext`

Fields to hash:

- `chatbot_applicants.full_name_hash`
- `chatbot_applicants.identity_number_hash`
- `uploaded_documents.sha256_checksum`
- `chatbot_audit_logs.ip_hash`
- `chatbot_audit_logs.user_agent_hash`

Fields to mask:

- IC / passport numbers in the UI and logs
- filenames in user-visible summaries
- signature thumbnails in audit or debug output

Retention guidance:

- keep draft sessions only as long as needed
- expire abandoned sessions on a scheduled cleanup job
- keep audit logs according to compliance policy
- archive or purge uploaded files according to retention policy

Delete policy guidance:

- prefer soft delete for request lifecycle records
- use hard delete only for cleanup and retention expiry
- do not physically delete logs before the retention period ends

## 9. Database Review

Unnecessary tables removed:

- `chatbot_messages`
- `chatbot_api_logs`
- `signatures`
- `cancellation_requests`

Why they were removed:

- the core workflow is fully represented by sessions, applicants, requests, documents, verifications, CIMS results, and logs
- keeping messages and API telemetry inside the business schema would add noise without improving the request contract

Redundant columns removed or avoided:

- no plaintext IC / passport column in the applicant table
- no original filename in plaintext
- no separate signatures table
- no duplicate request-specific document tables

Normalization review:

- applicant data is separated from request data
- document metadata is separated from verification data
- reference data is separated into lookup tables
- workflow state is separated from audit/history

Indexing improvements:

- hash-based identity search
- request number lookup
- session recency lookup
- CIMS result lookup
- document lookup by request and document type

Future scalability improvements:

- multiple workflows supported by `chatbot_workflows`
- multiple request types supported by `reference_request_types`
- multiple document types supported by `reference_document_types`
- OCR and AI document checks supported by `document_verifications`
- future government integrations supported by `cims_verification_results` and `chatbot_configuration`
- additional language support supported by `reference_languages`

Security improvements:

- encrypted identity fields
- hashed lookup fields
- randomized file storage names
- audit logs without raw PII
- configuration-driven upload policy

## 10. Default Inserts

### 10.1 `chatbot_workflows`

```sql
INSERT INTO chatbot_workflows (workflow_code, workflow_name, description, version, is_active)
VALUES
('CIDB_EMAIL_ID_CANCELLATION', 'CIDB Email ID Cancellation', 'Current chatbot flow for Email ID cancellation requests', '1.0', true)
ON CONFLICT (workflow_code) DO NOTHING;
```

### 10.2 `reference_languages`

```sql
INSERT INTO reference_languages (code, language_name, locale_tag, is_active)
VALUES
('en', 'English', 'en', true),
('ms', 'Bahasa Malaysia', 'ms', true)
ON CONFLICT (code) DO NOTHING;
```

### 10.3 `reference_malaysian_states`

```sql
INSERT INTO reference_malaysian_states (state_code, state_name, display_order, is_active)
VALUES
('JHR', 'Johor', 1, true),
('KDH', 'Kedah', 2, true),
('KTN', 'Kelantan', 3, true),
('MLK', 'Melaka', 4, true),
('NSN', 'Negeri Sembilan', 5, true),
('PHG', 'Pahang', 6, true),
('PRK', 'Perak', 7, true),
('PLS', 'Perlis', 8, true),
('PNG', 'Pulau Pinang', 9, true),
('SBH', 'Sabah', 10, true),
('SWK', 'Sarawak', 11, true),
('SGR', 'Selangor', 12, true),
('TRG', 'Terengganu', 13, true),
('WPKL', 'W.P. Kuala Lumpur', 14, true),
('WPLB', 'W.P. Labuan', 15, true),
('WPPJ', 'W.P. Putrajaya', 16, true)
ON CONFLICT (state_code) DO NOTHING;
```

### 10.4 `reference_request_types`

```sql
INSERT INTO reference_request_types (request_type_code, label_en, label_ms, description, is_active)
VALUES
('EMAIL_ID_CANCELLATION', 'Email ID Cancellation', 'Pembatalan Email ID', 'Current CIDB chatbot request type', true)
ON CONFLICT (request_type_code) DO NOTHING;
```

### 10.5 `reference_document_types`

```sql
INSERT INTO reference_document_types (
    document_type_code,
    label_en,
    label_ms,
    capture_mode,
    is_required_for_submission,
    allow_multiple,
    sort_order,
    allowed_mime_types,
    max_file_size_mb,
    requires_ocr,
    is_active
)
VALUES
('IC_FRONT', 'IC Front', 'IC Depan', 'upload', true, false, 1, '["image/jpeg","image/png","image/jpg","image/webp","application/pdf"]'::jsonb, 10, false, true),
('IC_BACK', 'IC Back', 'IC Belakang', 'upload', true, false, 2, '["image/jpeg","image/png","image/jpg","image/webp","application/pdf"]'::jsonb, 10, false, true),
('SIGNATURE', 'Signature', 'Tandatangan', 'signature_pad', true, false, 3, '["image/png"]'::jsonb, 5, false, true)
ON CONFLICT (document_type_code) DO NOTHING;
```

### 10.6 `chatbot_configuration`

```sql
INSERT INTO chatbot_configuration (config_key, config_group, config_value, is_sensitive, description)
VALUES
('SESSION_TIMEOUT_MINUTES', 'retention', '{"value":30}'::jsonb, false, 'Maximum idle time before a session is considered expired'),
('ABANDONED_SESSION_RETENTION_DAYS', 'retention', '{"value":90}'::jsonb, false, 'How long abandoned sessions are retained'),
('UPLOAD_MAX_FILE_SIZE_MB', 'security', '{"value":10}'::jsonb, false, 'Default maximum file size for document uploads'),
('UPLOAD_ALLOWED_MIME_TYPES', 'security', '{"value":["image/jpeg","image/png","image/jpg","image/webp","application/pdf"]}'::jsonb, false, 'Default allowed MIME types for IC uploads'),
('SIGNATURE_ALLOWED_MIME_TYPES', 'security', '{"value":["image/png"]}'::jsonb, false, 'Allowed MIME types for signature capture output'),
('CIMS_TIMEOUT_MS', 'integration', '{"value":15000}'::jsonb, false, 'Default timeout for CIMS integration calls'),
('ENABLE_AUDIT_LOGGING', 'general', '{"value":true}'::jsonb, false, 'Enable business and security audit logging'),
('DOCUMENT_RETENTION_DAYS', 'retention', '{"value":180}'::jsonb, false, 'Default retention period for uploaded documents')
ON CONFLICT (config_key) DO NOTHING;
```

## 11. Complete PostgreSQL DDL

Assumption: PostgreSQL 15+.

```sql
CREATE EXTENSION IF NOT EXISTS pgcrypto;

CREATE OR REPLACE FUNCTION set_updated_at()
RETURNS trigger
LANGUAGE plpgsql
AS $$
BEGIN
    NEW.updated_at = now();
    RETURN NEW;
END;
$$;

CREATE TABLE chatbot_workflows (
    id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    workflow_code varchar(80) NOT NULL,
    workflow_name varchar(150) NOT NULL,
    description text,
    version varchar(30) NOT NULL DEFAULT '1.0',
    is_active boolean NOT NULL DEFAULT true,
    created_at timestamptz NOT NULL DEFAULT now(),
    updated_at timestamptz NOT NULL DEFAULT now(),
    CONSTRAINT uq_chatbot_workflows_code UNIQUE (workflow_code),
    CONSTRAINT ck_chatbot_workflows_version CHECK (version <> '')
);

CREATE INDEX idx_chatbot_workflows_is_active ON chatbot_workflows (is_active);

CREATE TABLE reference_languages (
    code varchar(10) PRIMARY KEY,
    language_name varchar(80) NOT NULL,
    locale_tag varchar(20),
    is_active boolean NOT NULL DEFAULT true,
    created_at timestamptz NOT NULL DEFAULT now(),
    updated_at timestamptz NOT NULL DEFAULT now()
);

CREATE INDEX idx_reference_languages_is_active ON reference_languages (is_active);

CREATE TABLE reference_malaysian_states (
    state_code varchar(10) PRIMARY KEY,
    state_name varchar(50) NOT NULL,
    display_order smallint NOT NULL DEFAULT 0,
    is_active boolean NOT NULL DEFAULT true,
    created_at timestamptz NOT NULL DEFAULT now(),
    updated_at timestamptz NOT NULL DEFAULT now(),
    CONSTRAINT uq_reference_malaysian_states_name UNIQUE (state_name)
);

CREATE INDEX idx_reference_malaysian_states_is_active ON reference_malaysian_states (is_active);
CREATE INDEX idx_reference_malaysian_states_display_order ON reference_malaysian_states (display_order);

CREATE TABLE reference_request_types (
    request_type_code varchar(30) PRIMARY KEY,
    label_en varchar(120) NOT NULL,
    label_ms varchar(120) NOT NULL,
    description text,
    is_active boolean NOT NULL DEFAULT true,
    created_at timestamptz NOT NULL DEFAULT now(),
    updated_at timestamptz NOT NULL DEFAULT now()
);

CREATE INDEX idx_reference_request_types_is_active ON reference_request_types (is_active);

CREATE TABLE reference_document_types (
    document_type_code varchar(30) PRIMARY KEY,
    label_en varchar(100) NOT NULL,
    label_ms varchar(100) NOT NULL,
    capture_mode varchar(20) NOT NULL,
    is_required_for_submission boolean NOT NULL DEFAULT true,
    allow_multiple boolean NOT NULL DEFAULT false,
    sort_order smallint NOT NULL DEFAULT 0,
    allowed_mime_types jsonb NOT NULL DEFAULT '[]'::jsonb,
    max_file_size_mb integer NOT NULL DEFAULT 10,
    requires_ocr boolean NOT NULL DEFAULT false,
    is_active boolean NOT NULL DEFAULT true,
    created_at timestamptz NOT NULL DEFAULT now(),
    updated_at timestamptz NOT NULL DEFAULT now(),
    CONSTRAINT ck_reference_document_types_capture_mode CHECK (capture_mode IN ('upload', 'signature_pad')),
    CONSTRAINT ck_reference_document_types_max_file_size_mb CHECK (max_file_size_mb > 0),
    CONSTRAINT ck_reference_document_types_allowed_mime_types CHECK (jsonb_typeof(allowed_mime_types) = 'array')
);

CREATE INDEX idx_reference_document_types_capture_mode ON reference_document_types (capture_mode);
CREATE INDEX idx_reference_document_types_is_active ON reference_document_types (is_active);
CREATE INDEX idx_reference_document_types_sort_order ON reference_document_types (sort_order);

CREATE TABLE chatbot_configuration (
    id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    config_key varchar(100) NOT NULL,
    config_group varchar(50) NOT NULL DEFAULT 'general',
    config_value jsonb NOT NULL,
    is_sensitive boolean NOT NULL DEFAULT false,
    description text,
    created_at timestamptz NOT NULL DEFAULT now(),
    updated_at timestamptz NOT NULL DEFAULT now(),
    CONSTRAINT uq_chatbot_configuration_key UNIQUE (config_key)
);

CREATE INDEX idx_chatbot_configuration_group ON chatbot_configuration (config_group);
CREATE INDEX idx_chatbot_configuration_is_sensitive ON chatbot_configuration (is_sensitive);

CREATE TABLE chatbot_sessions (
    id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    workflow_id uuid NOT NULL REFERENCES chatbot_workflows(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    language_code varchar(10) REFERENCES reference_languages(code) ON DELETE RESTRICT ON UPDATE CASCADE,
    status varchar(30) NOT NULL DEFAULT 'awaiting_language',
    current_step varchar(30) NOT NULL DEFAULT 'ask_lang',
    draft_payload jsonb NOT NULL DEFAULT '{}'::jsonb,
    started_at timestamptz NOT NULL DEFAULT now(),
    last_activity_at timestamptz NOT NULL DEFAULT now(),
    completed_at timestamptz,
    expired_at timestamptz,
    created_at timestamptz NOT NULL DEFAULT now(),
    updated_at timestamptz NOT NULL DEFAULT now(),
    CONSTRAINT ck_chatbot_sessions_status CHECK (
        status IN (
            'awaiting_language',
            'awaiting_service',
            'awaiting_state',
            'awaiting_name',
            'awaiting_identity',
            'awaiting_documents',
            'awaiting_company_ppk',
            'awaiting_company_name',
            'awaiting_company_email',
            'awaiting_company_contact',
            'awaiting_company_state',
            'awaiting_company_category',
            'awaiting_company_director_name',
            'awaiting_company_director_ic',
            'awaiting_company_reason',
            'submitted',
            'under_review',
            'completed',
            'abandoned',
            'expired',
            'failed'
        )
    ),
    CONSTRAINT ck_chatbot_sessions_current_step CHECK (
        current_step IN (
            'ask_lang',
            'ask_service',
            'ask_state',
            'ask_name',
            'ask_ic',
            'ask_mobile',
            'ask_email',
            'ask_ic_copy',
            'ask_company_ppk',
            'ask_company_name',
            'ask_company_email',
            'ask_company_contact',
            'ask_company_state',
            'ask_company_category',
            'ask_company_director_name',
            'ask_company_director_ic',
            'ask_company_reason',
            'done'
        )
    ),
    CONSTRAINT ck_chatbot_sessions_draft_payload CHECK (jsonb_typeof(draft_payload) = 'object')
);

CREATE INDEX idx_chatbot_sessions_workflow_id ON chatbot_sessions (workflow_id);
CREATE INDEX idx_chatbot_sessions_language_code ON chatbot_sessions (language_code);
CREATE INDEX idx_chatbot_sessions_status ON chatbot_sessions (status);
CREATE INDEX idx_chatbot_sessions_current_step ON chatbot_sessions (current_step);
CREATE INDEX idx_chatbot_sessions_last_activity_at ON chatbot_sessions (last_activity_at);
CREATE INDEX idx_chatbot_sessions_started_at ON chatbot_sessions (started_at);

CREATE TABLE chatbot_applicants (
    id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    session_id uuid NOT NULL UNIQUE REFERENCES chatbot_sessions(id) ON DELETE CASCADE ON UPDATE CASCADE,
    full_name_ciphertext bytea NOT NULL,
    full_name_hash char(64) NOT NULL,
    identity_type varchar(20) NOT NULL,
    identity_number_ciphertext bytea NOT NULL,
    identity_number_hash char(64) NOT NULL,
    identity_number_last4 varchar(4),
    state_code varchar(10) NOT NULL REFERENCES reference_malaysian_states(state_code) ON DELETE RESTRICT ON UPDATE CASCADE,
    language_code varchar(10) NOT NULL REFERENCES reference_languages(code) ON DELETE RESTRICT ON UPDATE CASCADE,
    verification_status varchar(20) NOT NULL DEFAULT 'pending',
    is_draft boolean NOT NULL DEFAULT true,
    created_at timestamptz NOT NULL DEFAULT now(),
    updated_at timestamptz NOT NULL DEFAULT now(),
    CONSTRAINT ck_chatbot_applicants_identity_type CHECK (identity_type IN ('MYKAD', 'PASSPORT')),
    CONSTRAINT ck_chatbot_applicants_verification_status CHECK (verification_status IN ('pending', 'verified', 'rejected')),
    CONSTRAINT ck_chatbot_applicants_identity_hash_length CHECK (length(full_name_hash) = 64 AND length(identity_number_hash) = 64),
    CONSTRAINT ck_chatbot_applicants_last4 CHECK (identity_number_last4 IS NULL OR identity_number_last4 ~ '^[A-Z0-9]{4}$')
);

CREATE INDEX idx_chatbot_applicants_identity_number_hash ON chatbot_applicants (identity_number_hash);
CREATE INDEX idx_chatbot_applicants_state_code ON chatbot_applicants (state_code);
CREATE INDEX idx_chatbot_applicants_language_code ON chatbot_applicants (language_code);
CREATE INDEX idx_chatbot_applicants_verification_status ON chatbot_applicants (verification_status);
CREATE INDEX idx_chatbot_applicants_created_at ON chatbot_applicants (created_at);

CREATE TABLE service_requests (
    id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    request_number varchar(40) NOT NULL,
    workflow_id uuid NOT NULL REFERENCES chatbot_workflows(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    session_id uuid NOT NULL UNIQUE REFERENCES chatbot_sessions(id) ON DELETE CASCADE ON UPDATE CASCADE,
    applicant_id uuid NOT NULL REFERENCES chatbot_applicants(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    request_type_code varchar(30) NOT NULL REFERENCES reference_request_types(request_type_code) ON DELETE RESTRICT ON UPDATE CASCADE,
    status varchar(30) NOT NULL DEFAULT 'draft',
    submission_language_code varchar(10) NOT NULL REFERENCES reference_languages(code) ON DELETE RESTRICT ON UPDATE CASCADE,
    submitted_at timestamptz,
    latest_cims_status varchar(20) NOT NULL DEFAULT 'pending',
    final_outcome varchar(20),
    final_outcome_at timestamptz,
    closed_at timestamptz,
    created_at timestamptz NOT NULL DEFAULT now(),
    updated_at timestamptz NOT NULL DEFAULT now(),
    CONSTRAINT uq_service_requests_request_number UNIQUE (request_number),
    CONSTRAINT ck_service_requests_status CHECK (
        status IN (
            'draft',
            'submitted',
            'under_review',
            'pending_cims',
            'approved',
            'rejected',
            'manual_review',
            'cancelled',
            'failed'
        )
    ),
    CONSTRAINT ck_service_requests_latest_cims_status CHECK (latest_cims_status IN ('pending', 'deleted', 'linked', 'norecord', 'error', 'approved', 'rejected', 'manual_review')),
    CONSTRAINT ck_service_requests_final_outcome CHECK (final_outcome IS NULL OR final_outcome IN ('deleted', 'linked', 'norecord'))
);

CREATE INDEX idx_service_requests_workflow_id ON service_requests (workflow_id);
CREATE INDEX idx_service_requests_applicant_id ON service_requests (applicant_id);
CREATE INDEX idx_service_requests_request_type_code ON service_requests (request_type_code);
CREATE INDEX idx_service_requests_status ON service_requests (status);
CREATE INDEX idx_service_requests_latest_cims_status ON service_requests (latest_cims_status);
CREATE INDEX idx_service_requests_submitted_at ON service_requests (submitted_at);
CREATE INDEX idx_service_requests_created_at ON service_requests (created_at);

CREATE TABLE uploaded_documents (
    id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    session_id uuid NOT NULL REFERENCES chatbot_sessions(id) ON DELETE CASCADE ON UPDATE CASCADE,
    request_id uuid REFERENCES service_requests(id) ON DELETE CASCADE ON UPDATE CASCADE,
    document_type_code varchar(30) NOT NULL REFERENCES reference_document_types(document_type_code) ON DELETE RESTRICT ON UPDATE CASCADE,
    upload_source varchar(20) NOT NULL DEFAULT 'user_upload',
    storage_disk varchar(50) NOT NULL DEFAULT 'local',
    storage_path text NOT NULL,
    storage_file_name text NOT NULL,
    original_file_name_ciphertext bytea,
    mime_type varchar(100) NOT NULL,
    file_extension varchar(10) NOT NULL,
    file_size_bytes bigint NOT NULL,
    sha256_checksum char(64) NOT NULL,
    upload_status varchar(20) NOT NULL DEFAULT 'pending',
    security_status varchar(20) NOT NULL DEFAULT 'not_scanned',
    metadata jsonb NOT NULL DEFAULT '{}'::jsonb,
    created_at timestamptz NOT NULL DEFAULT now(),
    updated_at timestamptz NOT NULL DEFAULT now(),
    CONSTRAINT uq_uploaded_documents_storage_path UNIQUE (storage_path),
    CONSTRAINT uq_uploaded_documents_storage_file_name UNIQUE (storage_file_name),
    CONSTRAINT ck_uploaded_documents_upload_source CHECK (upload_source IN ('user_upload', 'signature_pad', 'system_import')),
    CONSTRAINT ck_uploaded_documents_upload_status CHECK (upload_status IN ('pending', 'stored', 'quarantined', 'rejected', 'deleted')),
    CONSTRAINT ck_uploaded_documents_security_status CHECK (security_status IN ('not_scanned', 'clean', 'infected', 'error')),
    CONSTRAINT ck_uploaded_documents_file_size_bytes CHECK (file_size_bytes > 0),
    CONSTRAINT ck_uploaded_documents_metadata CHECK (jsonb_typeof(metadata) = 'object')
);

CREATE INDEX idx_uploaded_documents_session_id ON uploaded_documents (session_id);
CREATE INDEX idx_uploaded_documents_request_id ON uploaded_documents (request_id);
CREATE INDEX idx_uploaded_documents_document_type_code ON uploaded_documents (document_type_code);
CREATE INDEX idx_uploaded_documents_sha256_checksum ON uploaded_documents (sha256_checksum);
CREATE INDEX idx_uploaded_documents_upload_status ON uploaded_documents (upload_status);
CREATE INDEX idx_uploaded_documents_security_status ON uploaded_documents (security_status);
CREATE INDEX idx_uploaded_documents_created_at ON uploaded_documents (created_at);

CREATE TABLE document_verifications (
    id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    uploaded_document_id uuid NOT NULL REFERENCES uploaded_documents(id) ON DELETE CASCADE ON UPDATE CASCADE,
    verification_type varchar(30) NOT NULL,
    verifier varchar(30) NOT NULL,
    status varchar(20) NOT NULL,
    score numeric(5,2),
    reason_code varchar(50),
    reason_message text,
    details jsonb NOT NULL DEFAULT '{}'::jsonb,
    verified_at timestamptz NOT NULL DEFAULT now(),
    created_at timestamptz NOT NULL DEFAULT now(),
    CONSTRAINT ck_document_verifications_verification_type CHECK (verification_type IN ('file_integrity', 'mime_check', 'size_check', 'malware_scan', 'ocr_quality', 'signature_quality', 'manual_review')),
    CONSTRAINT ck_document_verifications_verifier CHECK (verifier IN ('system', 'agent', 'ai', 'cims')),
    CONSTRAINT ck_document_verifications_status CHECK (status IN ('pending', 'passed', 'failed', 'warning')),
    CONSTRAINT ck_document_verifications_score CHECK (score IS NULL OR (score >= 0 AND score <= 100)),
    CONSTRAINT ck_document_verifications_details CHECK (jsonb_typeof(details) = 'object')
);

CREATE INDEX idx_document_verifications_uploaded_document_id ON document_verifications (uploaded_document_id);
CREATE INDEX idx_document_verifications_verification_type ON document_verifications (verification_type);
CREATE INDEX idx_document_verifications_status ON document_verifications (status);
CREATE INDEX idx_document_verifications_verified_at ON document_verifications (verified_at);

CREATE TABLE cims_verification_results (
    id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    request_id uuid NOT NULL REFERENCES service_requests(id) ON DELETE CASCADE ON UPDATE CASCADE,
    attempt_no integer NOT NULL,
    result_status varchar(20) NOT NULL,
    response_code varchar(50),
    response_message text,
    external_reference_no varchar(80),
    latency_ms integer,
    response_payload jsonb NOT NULL DEFAULT '{}'::jsonb,
    verified_at timestamptz,
    created_at timestamptz NOT NULL DEFAULT now(),
    CONSTRAINT uq_cims_verification_results_attempt UNIQUE (request_id, attempt_no),
    CONSTRAINT ck_cims_verification_results_attempt_no CHECK (attempt_no > 0),
    CONSTRAINT ck_cims_verification_results_result_status CHECK (result_status IN ('pending', 'deleted', 'linked', 'norecord', 'error', 'approved', 'rejected', 'manual_review')),
    CONSTRAINT ck_cims_verification_results_latency_ms CHECK (latency_ms IS NULL OR latency_ms >= 0),
    CONSTRAINT ck_cims_verification_results_response_payload CHECK (jsonb_typeof(response_payload) = 'object')
);

CREATE INDEX idx_cims_verification_results_request_id ON cims_verification_results (request_id);
CREATE INDEX idx_cims_verification_results_result_status ON cims_verification_results (result_status);
CREATE INDEX idx_cims_verification_results_created_at ON cims_verification_results (created_at);

CREATE TABLE chatbot_status_history (
    id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    session_id uuid REFERENCES chatbot_sessions(id) ON DELETE SET NULL ON UPDATE CASCADE,
    request_id uuid REFERENCES service_requests(id) ON DELETE SET NULL ON UPDATE CASCADE,
    document_id uuid REFERENCES uploaded_documents(id) ON DELETE SET NULL ON UPDATE CASCADE,
    status_scope varchar(20) NOT NULL,
    from_status varchar(30),
    to_status varchar(30) NOT NULL,
    changed_by_type varchar(20) NOT NULL,
    changed_by_reference varchar(80),
    reason_code varchar(50),
    reason_message text,
    metadata jsonb NOT NULL DEFAULT '{}'::jsonb,
    created_at timestamptz NOT NULL DEFAULT now(),
    CONSTRAINT ck_chatbot_status_history_scope CHECK (status_scope IN ('session', 'request', 'document', 'cims')),
    CONSTRAINT ck_chatbot_status_history_changed_by_type CHECK (changed_by_type IN ('user', 'system', 'agent', 'integration')),
    CONSTRAINT ck_chatbot_status_history_entity_present CHECK (session_id IS NOT NULL OR request_id IS NOT NULL OR document_id IS NOT NULL),
    CONSTRAINT ck_chatbot_status_history_metadata CHECK (jsonb_typeof(metadata) = 'object')
);

CREATE INDEX idx_chatbot_status_history_session_id ON chatbot_status_history (session_id);
CREATE INDEX idx_chatbot_status_history_request_id ON chatbot_status_history (request_id);
CREATE INDEX idx_chatbot_status_history_document_id ON chatbot_status_history (document_id);
CREATE INDEX idx_chatbot_status_history_status_scope ON chatbot_status_history (status_scope);
CREATE INDEX idx_chatbot_status_history_created_at ON chatbot_status_history (created_at);

CREATE TABLE chatbot_audit_logs (
    id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    correlation_id uuid NOT NULL DEFAULT gen_random_uuid(),
    session_id uuid REFERENCES chatbot_sessions(id) ON DELETE SET NULL ON UPDATE CASCADE,
    request_id uuid REFERENCES service_requests(id) ON DELETE SET NULL ON UPDATE CASCADE,
    event_type varchar(50) NOT NULL,
    severity varchar(10) NOT NULL,
    actor_type varchar(20) NOT NULL,
    actor_reference varchar(80),
    message text NOT NULL,
    masked_payload jsonb NOT NULL DEFAULT '{}'::jsonb,
    ip_hash bytea,
    user_agent_hash char(64),
    created_at timestamptz NOT NULL DEFAULT now(),
    CONSTRAINT ck_chatbot_audit_logs_severity CHECK (severity IN ('debug', 'info', 'warning', 'error', 'security')),
    CONSTRAINT ck_chatbot_audit_logs_actor_type CHECK (actor_type IN ('user', 'bot', 'agent', 'system', 'integration')),
    CONSTRAINT ck_chatbot_audit_logs_masked_payload CHECK (jsonb_typeof(masked_payload) = 'object')
);

CREATE INDEX idx_chatbot_audit_logs_correlation_id ON chatbot_audit_logs (correlation_id);
CREATE INDEX idx_chatbot_audit_logs_session_id ON chatbot_audit_logs (session_id);
CREATE INDEX idx_chatbot_audit_logs_request_id ON chatbot_audit_logs (request_id);
CREATE INDEX idx_chatbot_audit_logs_event_type ON chatbot_audit_logs (event_type);
CREATE INDEX idx_chatbot_audit_logs_severity ON chatbot_audit_logs (severity);
CREATE INDEX idx_chatbot_audit_logs_created_at ON chatbot_audit_logs (created_at);

CREATE TRIGGER trg_chatbot_workflows_updated_at
BEFORE UPDATE ON chatbot_workflows
FOR EACH ROW EXECUTE FUNCTION set_updated_at();

CREATE TRIGGER trg_reference_languages_updated_at
BEFORE UPDATE ON reference_languages
FOR EACH ROW EXECUTE FUNCTION set_updated_at();

CREATE TRIGGER trg_reference_malaysian_states_updated_at
BEFORE UPDATE ON reference_malaysian_states
FOR EACH ROW EXECUTE FUNCTION set_updated_at();

CREATE TRIGGER trg_reference_request_types_updated_at
BEFORE UPDATE ON reference_request_types
FOR EACH ROW EXECUTE FUNCTION set_updated_at();

CREATE TRIGGER trg_reference_document_types_updated_at
BEFORE UPDATE ON reference_document_types
FOR EACH ROW EXECUTE FUNCTION set_updated_at();

CREATE TRIGGER trg_chatbot_configuration_updated_at
BEFORE UPDATE ON chatbot_configuration
FOR EACH ROW EXECUTE FUNCTION set_updated_at();

CREATE TRIGGER trg_chatbot_sessions_updated_at
BEFORE UPDATE ON chatbot_sessions
FOR EACH ROW EXECUTE FUNCTION set_updated_at();

CREATE TRIGGER trg_chatbot_applicants_updated_at
BEFORE UPDATE ON chatbot_applicants
FOR EACH ROW EXECUTE FUNCTION set_updated_at();

CREATE TRIGGER trg_service_requests_updated_at
BEFORE UPDATE ON service_requests
FOR EACH ROW EXECUTE FUNCTION set_updated_at();

CREATE TRIGGER trg_uploaded_documents_updated_at
BEFORE UPDATE ON uploaded_documents
FOR EACH ROW EXECUTE FUNCTION set_updated_at();
```

## 12. Final Approved Schema

This is the approved database contract:

- `chatbot_workflows`
- `reference_languages`
- `reference_malaysian_states`
- `reference_request_types`
- `reference_document_types`
- `chatbot_configuration`
- `chatbot_sessions`
- `chatbot_applicants`
- `service_requests`
- `uploaded_documents`
- `document_verifications`
- `cims_verification_results`
- `chatbot_status_history`
- `chatbot_audit_logs`

This schema is intentionally compact, normalized, and production-ready for the current frontend flow while staying flexible for future workflows, document types, OCR, AI verification, multiple request types, multiple languages, and additional government integrations.
