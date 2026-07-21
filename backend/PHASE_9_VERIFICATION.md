# Phase 9 Verification Layer

This phase keeps CIMS as a development mock. No external API calls are made.

## Complete Lifecycle

1. Session starts.
2. User selects language.
3. User selects Malaysian state.
4. User enters full name.
5. User enters IC or passport number.
6. User uploads IC front.
7. User uploads IC back.
8. User uploads signature.
9. User submits the application.
10. `SubmissionService` validates readiness and assembles the full request payload.
11. `VerificationService` returns a mock CIMS result.
12. `StatusService` records request and session status changes.
13. The backend returns the final structured response.

## Mock Verification Modes

The mock verifier is controlled through environment variables:

- `CIMS_MOCK_MODE=random`
- `CIMS_MOCK_MODE=fixed`
- `CIMS_MOCK_MODE=env`

Supported mock outcomes:

- `deleted`
- `linked`
- `norecord`
- `error`

Environment variables:

- `CIMS_MOCK_MODE`
- `CIMS_MOCK_OUTCOME`

## How To Test

### Always deleted

Set:

```env
CIMS_MOCK_MODE=fixed
CIMS_MOCK_OUTCOME=deleted
```

### Always linked

```env
CIMS_MOCK_MODE=fixed
CIMS_MOCK_OUTCOME=linked
```

### Always norecord

```env
CIMS_MOCK_MODE=fixed
CIMS_MOCK_OUTCOME=norecord
```

### Always error

```env
CIMS_MOCK_MODE=fixed
CIMS_MOCK_OUTCOME=error
```

### Random

```env
CIMS_MOCK_MODE=random
```

## Submission Flow

`SubmissionService` performs the following inside a transaction:

- readiness validation
- applicant finalization if needed
- request creation if needed
- document coverage validation
- document attachment to request
- request status update to submitted
- mock CIMS verification
- CIMS result persistence
- request final outcome update
- session completion or failure update
- audit logging

## Replacement Points For Real CIMS

When the real integration becomes available, only these locations should change:

- [backend/services/VerificationService.php](C:/Users/aswathy.ramachandran/OneDrive%20-%20Daythree%20Digital%20Berhad/Desktop/CIDB/backend/services/VerificationService.php)
- [backend/config/CimsConfig.php](C:/Users/aswathy.ramachandran/OneDrive%20-%20Daythree%20Digital%20Berhad/Desktop/CIDB/backend/config/CimsConfig.php)
- [backend/bootstrap/Bootstrap.php](C:/Users/aswathy.ramachandran/OneDrive%20-%20Daythree%20Digital%20Berhad/Desktop/CIDB/backend/bootstrap/Bootstrap.php)

The rest of the workflow should remain unchanged.
