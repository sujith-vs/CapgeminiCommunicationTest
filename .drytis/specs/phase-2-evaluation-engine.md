# Phase 2 · Evaluation Engine (LLM + Deterministic MCQ)

## Goal
Make "View Solutions" a real evaluation: locked until the test is complete; MCQs scored deterministically, unstructured answers scored by an LLM.

## Changes
- **Lock**: View Solutions disabled until test completion (all questions answered OR timer expiry). Show "Complete the test to view solutions".
- **MCQ sections** ("Reading Paragraph", "Grammar & Sentence Correction"): score client-side via answer key — no LLM call.
- **Unstructured sections** (email-writing, spoken topics, etc.): new backend `api/evaluate.php` (PHP) that proxies to the OpenAI-compatible LLM using env vars `LLM_API_KEY`, `LLM_BASE_URL`, `LLM_MODEL` (from `/workspace/api/.env`). Send user answer + model answer + rubric; return JSON `{score, strengths, improvements, revised_answer}`.
- Solutions view per question: MCQ → correct answer + whether the user was right; unstructured → user answer vs model answer, LLM score + feedback.
- Caddy already routes `/` to workspace; add an `api/` directory with `evaluate.php`; ensure the php proxy handles it (plain PHP under Caddy).

## Acceptance Criteria
- [ ] View Solutions is inactive until the test is submitted (or timer expires)
- [ ] Reading Paragraph & Grammar sections are scored deterministically with zero LLM calls
- [ ] Email/spoken answers receive an LLM comparison: score, strengths, improvements, and a revised version
- [ ] The report (per-question result + overall score) is visible in View Solutions after completion

## Edge cases
- Empty/unanswered unstructured questions → scored as skipped, no LLM call
- LLM failure/timeout → graceful fallback message per question, rest of report intact
- API key never appears in frontend code
