# FAQ Flow — Database Diagnostic Report

**Branch:** `feature/faq-flow-3`
**Scope:** Read-only investigation only. No files were changed, no migrations run, no DB writes made.
**Goal:** Identify whether the new FAQ tables/structure are the cause of a SQL/DB error affecting another (previously working) flow.

---

## 1. What FAQ actually added to the database

Two migrations, in order:

### `backend/migrations/20260819_add_faq_flow.php`
Creates a 3-level hierarchy, all new tables:

| Table | Key | Notes |
|---|---|---|
| `reference_faq_topics` | PK `topic_code` (varchar 30) | top level, no FKs in |
| `reference_faq_subtopics` | PK `subtopic_code` (varchar 40) | FK `topic_code → reference_faq_topics` |
| `chatbot_faq_questions` | PK `id` (uuid) | FK `subtopic_code → reference_faq_subtopics` |

Also **alters the shared `chatbot_sessions` table** — drops and recreates two CHECK constraints to add FAQ values:
- `ck_chatbot_sessions_status` → adds `'awaiting_faq_topic'`, `'awaiting_faq_subtopic'`
- `ck_chatbot_sessions_current_step` → adds `'ask_faq_topic'`, `'ask_faq_subtopic'`

This is the **only** place the FAQ migration touches something other flows also depend on.

### `backend/migrations/20260820_seed_real_faq_content.php`
Data-only: deletes and reloads rows in `chatbot_faq_questions` / `reference_faq_subtopics` with real bilingual content. No DDL, no other tables touched.

**No other table in the schema has a foreign key into any FAQ table.** FAQ selections during a chat session are stored only inside `chatbot_sessions.draft_payload` (JSONB), under the keys `faq_topic_code` / `faq_subtopic_code` — the same generic draft-storage mechanism every other flow (individual, company) already uses for its own fields.

---

## 2. Things I checked and can rule out

✅ **`chatbot_sessions` CHECK constraints are not missing anything.**
I diffed the new constraint definitions in `20260819_add_faq_flow.php` line-by-line against the previous version in `20260813_add_company_email_id_cancellation_flow.php`, and against the PHP-side mirror in `backend/validators/SessionValidator.php` (`ALLOWED_STATUS`, `ALLOWED_STEPS`, `$stepTransitions`). Every pre-existing status/step value is preserved in all three places — nothing was silently dropped when the constraint was rebuilt for FAQ. If this were the bug, existing flows would suddenly reject valid status/step values; that is not the case in the code as written.

✅ **FAQ session writes don't clobber other flows' data.**
`SessionService::saveFaqTopicSelection()` / `saveFaqSubtopicSelection()` (`backend/services/SessionService.php`) use the exact same decode → mutate specific key → re-encode pattern as every other step handler in that file. They only touch `faq_topic_code` / `faq_subtopic_code` inside the shared JSONB blob, not the whole thing.

✅ **Migrations can't half-apply and corrupt the schema.**
`MigrationExecutor::run()` wraps each migration in a DB transaction and rolls back entirely on any failure (`backend/migrations/MigrationExecutor.php`). So `20260819_add_faq_flow.php` either fully applied or didn't apply at all — it can't leave `chatbot_sessions` in a half-updated state.

✅ **Migrations do not auto-run on every request.**
`MigrationManager::runPending()` is registered in the DI container (`backend/bootstrap/Bootstrap.php`) but nothing in the codebase actually calls it — no CLI script, no request-path hook. This means a broken/pending FAQ migration cannot fatal-crash unrelated requests automatically; migrations must be applied manually. (Worth confirming with whoever normally runs them, since no committed entrypoint for this exists.)

✅ **The FAQ repositories/controller don't join or touch any non-FAQ table.**
`ChatbotFaqQuestionRepository`, `ReferenceFaqTopicRepository`, `ReferenceFaqSubtopicRepository`, and `FaqController` only ever query the three FAQ tables. No cross-table joins.

---

## 3. Uncommitted, untested code (the real risk area)

Everything below is currently only in your **working tree** (`git status`/`git diff`), not part of any commit — meaning it has apparently not been run end-to-end yet:

- **`backend/repositories/ChatbotFaqQuestionRepository.php`** — three new methods added: `searchQuestionsByKeywords()`, `countSearchQuestionsByKeywords()`, `suggestClosestQuestions()`. These build **raw hand-written SQL** (per-keyword `ILIKE` scoring) against `chatbot_faq_questions`, with the table name hardcoded inline rather than via the repository's own `tableName()` helper. Still isolated to that one table — no other table referenced.
- **`backend/controllers/FaqController.php`** — new `search()` action wired to the above, plus a Levenshtein-based "suggestions" fallback.
- **`backend/routes/api.php`** — new route `GET /faq/search`.
- **`frontend_api.js`** — FAQ search UI integration, **plus one unrelated line change**:
  ```diff
  - const API_BASE_URL = 'http://localhost:8000';
  + const API_BASE_URL = 'http://localhost:8080';
  ```
  This constant is used by **every** flow in the SPA (login, OCR upload, company flow, FAQ), not just FAQ. This is a connectivity issue, not a SQL/DB one, so it's a separate bug from what you're describing — flagged for awareness, not touched.
- **`docs/faq-search-feature.md`** (untracked) documents an **older version** of this design (different method names, response shape without `suggestions`) — confirms the search feature changed after the doc was written and, combined with it being uncommitted, suggests it likely hasn't been tested against a real database yet.

---

## 4. Most likely candidates for the SQL/DB error, ranked

Since you confirmed the error is SQL/DB-specific (constraint/column), and everything structurally provable from the code checks out clean, the two most probable explanations are:

1. **The FAQ migrations haven't been (fully) applied to the database you're hitting, but the code that queries the new tables/columns has already been deployed/run.** This produces exactly a `relation "chatbot_faq_questions" does not exist` / `column ... does not exist` style error. This is the single most common cause of "new tables broke things" reports.
2. **The uncommitted raw-SQL search methods** (`searchQuestionsByKeywords` etc.) have not been tested against a live DB yet (see stale docs above) and may contain a bind-parameter or SQL-syntax bug that only surfaces when `/faq/search` is actually hit.

Structurally, I found **no evidence** that the FAQ migration corrupted or narrowed anything shared (`chatbot_sessions` constraints and validators match exactly), so if another flow is genuinely failing with a DB error, the most likely link is indirect: an unhandled exception thrown somewhere in the FAQ code path surfacing through shared error-handling/bootstrap code, making it look like "another flow" is affected when the trigger is still FAQ-specific.

---

## 5. What I could not verify from here (needs a manual check on your end)

I do not have a working DB client in this environment (no `php` or `psql` on PATH, Docker daemon not running), and `.env` points at a remote staging RDS instance — I did not attempt to connect to it. To close the loop, please run, against that same database:

```sql
-- 1. Confirm both FAQ migrations actually applied
SELECT migration_name, applied_at FROM migration_history ORDER BY applied_at;

-- 2. Compare the live constraint text against what's in 20260819_add_faq_flow.php
\d chatbot_sessions
-- or:
SELECT conname, pg_get_constraintdef(oid)
FROM pg_constraint
WHERE conrelid = 'chatbot_sessions'::regclass;
```

And if possible, capture the **literal error message/stack trace** the next time the other flow fails — that alone would confirm which of the two candidates above is the real cause.

---

## 6. Not changed / no action taken

Per your instruction, nothing was modified — no migrations run, no code edited, no `API_BASE_URL` revert. This file is purely the diagnostic writeup for your review.
