# Phase 7 API Layer

This document describes the REST API contract exposed by the backend. The controllers are intentionally thin: they validate request structure, call services, and return JSON responses.

## Standard Response Format

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
  "message": "Invalid request.",
  "error_code": "REQUEST_INVALID",
  "errors": {}
}
```

## Endpoints

### POST /session/start

Starts a chatbot session.

Request:

```json
{
  "workflow_code": "EMAIL_ID_CANCELLATION"
}
```

Response:

```json
{
  "success": true,
  "message": "Session started.",
  "data": {
    "session": {}
  }
}
```

Validation errors:
- `workflow_code` invalid or workflow unavailable
- request body is not valid JSON

### POST /session/language

Stores the selected language.

Request:

```json
{
  "session_id": "uuid",
  "language": "English"
}
```

Response:

```json
{
  "success": true,
  "message": "Language saved.",
  "data": {
    "session": {}
  }
}
```

Validation errors:
- missing `session_id`
- invalid language value
- session step transition is not allowed

### POST /session/state

Stores the Malaysian state.

Request:

```json
{
  "session_id": "uuid",
  "state": "Selangor"
}
```

Response:

```json
{
  "success": true,
  "message": "State saved.",
  "data": {
    "session": {}
  }
}
```

Validation errors:
- missing `session_id`
- invalid state
- invalid step transition

### POST /session/name

Stores the applicant full name.

Request:

```json
{
  "session_id": "uuid",
  "full_name": "Aisyah binti Ahmad"
}
```

Response:

```json
{
  "success": true,
  "message": "Name saved.",
  "data": {
    "session": {}
  }
}
```

Validation errors:
- missing `session_id`
- invalid or empty name
- invalid step transition

### POST /session/identity

Stores the IC or passport number and finalizes the applicant record.

Request:

```json
{
  "session_id": "uuid",
  "identity_type": "MYKAD",
  "identity_number": "900101101234"
}
```

Response:

```json
{
  "success": true,
  "message": "Identity saved.",
  "data": {
    "session": {},
    "applicant": {}
  }
}
```

Validation errors:
- missing `session_id`
- invalid IC or passport format
- invalid step transition
- applicant data incomplete

### POST /session/mobile

Stores the applicant mobile number.

Request:

```json
{
  "session_id": "uuid",
  "mobile": "+60123456789"
}
```

Response:

```json
{
  "success": true,
  "message": "Mobile saved.",
  "data": {
    "session": {}
  }
}
```

Validation errors:
- missing `session_id`
- missing or invalid mobile number
- invalid step transition

### POST /session/email

Stores the applicant email address.

Request:

```json
{
  "session_id": "uuid",
  "email": "name@example.com"
}
```

Response:

```json
{
  "success": true,
  "message": "Email saved.",
  "data": {
    "session": {}
  }
}
```

Validation errors:
- missing `session_id`
- missing or invalid email address
- invalid step transition

### GET /session/{id}

Returns the current session state and related records.

Response:

```json
{
  "success": true,
  "message": "Session retrieved.",
  "data": {
    "session": {},
    "applicant": null,
    "submission": null,
    "documents": []
  }
}
```

Validation errors:
- session not found

### POST /documents/upload

Uploads a document file such as IC front or IC back.

Request: `multipart/form-data`

Fields:
- `session_id`
- `document_type_code`
- `request_id` optional
- `file` uploaded file

Response:

```json
{
  "success": true,
  "message": "Document uploaded.",
  "data": {
    "document": {}
  }
}
```

Validation errors:
- missing `session_id`
- missing `document_type_code`
- missing uploaded file
- unsupported MIME type or extension
- file too large
- malware placeholder check failed

### POST /signature/upload

Uploads the signature as a PNG data URL.

Request:

```json
{
  "session_id": "uuid",
  "signature_data_url": "data:image/png;base64,..."
}
```

Response:

```json
{
  "success": true,
  "message": "Signature uploaded.",
  "data": {
    "document": {}
  }
}
```

Validation errors:
- missing `session_id`
- missing signature payload
- invalid PNG data URL
- corrupted or unreadable image

### POST /submission

Submits the full application.

Request:

```json
{
  "session_id": "uuid",
  "cims": {
    "mock_outcome": "deleted"
  }
}
```

Response:

```json
{
  "success": true,
  "message": "Submission completed.",
  "data": {
    "session": {},
    "applicant": {},
    "request": {},
    "documents": [],
    "verification": {}
  }
}
```

Validation errors:
- session not ready
- missing session data
- missing mobile number
- missing email address
- missing required documents
- missing signature
- incomplete applicant data

### GET /submission/{id}

Returns submission status and related records.

Identifier:
- `request_number`, or
- `session_id`

Response:

```json
{
  "success": true,
  "message": "Submission retrieved.",
  "data": {
    "submission": {},
    "session": {},
    "applicant": {},
    "documents": []
  }
}
```

Validation errors:
- submission not found

## Central Error Handling

All uncaught exceptions are converted into consistent JSON error responses by the global error handler. The API never exposes raw stack traces in production mode.
