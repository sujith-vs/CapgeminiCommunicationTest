# Capgemini Communication Practice Test

A browser-based Capgemini-style communication assessment trainer with **6 practice sections**, a **30-minute timer per section**, **deterministic MCQ scoring**, and **AI-powered evaluation** of written emails and spoken responses.

## Sections

| Section | Questions | Evaluation |
|---|---|---|
| Situational Awareness (workplace emails) | 6 writing tasks | 🤖 AI (LLM comparison vs. model answer) |
| Reading Paragraph | 4 MCQs | ✅ Deterministic answer key |
| Listening Comprehension (text-to-speech audio) | 4 MCQs | ✅ Deterministic answer key |
| Spoken Topics | 2 speaking topics | 🎤 Speech-to-text + 🤖 AI evaluation |
| Business Writing | 2 writing tasks | 🤖 AI (LLM comparison vs. model answer) |
| Grammar & Sentence Correction | 34 MCQs | ✅ Deterministic answer key |

## Features

- **Section-based flow** — pick any of the 6 sections from the home page; every section has a *Back to Home* button.
- **30-minute timer per section** (top-right during a test). When time expires the test is **auto-submitted** with whatever was completed, and the report becomes available.
- **Solutions locked until submission** — *View Solutions* activates only after a section is submitted (or the timer expires).
- **Deterministic MCQ scoring** — Reading Paragraph, Grammar & Sentence Correction, and Listening Comprehension are graded entirely client-side against the answer key. **No LLM calls** for these sections.
- **AI evaluation for unstructured answers** — emails and spoken transcripts are sent to an LLM that compares them against the reference model answer and returns:
  - a score out of 10
  - strengths
  - concrete improvements
  - a revised version of your answer
- **Speech-to-text for Spoken Topics** — uses the browser's built-in Web Speech API (`SpeechRecognition` / `webkitSpeechRecognition`). The transcript field is **read-only** — the answer must be spoken, exactly as in the real test. Supported in Chrome/Edge (mic permission over HTTPS required).
- **Listening questions use text-to-speech** — audio prompts are synthesized in-browser via `speechSynthesis`, no audio files needed.

## Tech Stack

- **Frontend:** single-page vanilla HTML/CSS/JS (`index.html`) — no build step, no framework.
- **Backend:** one PHP endpoint (`api/evaluate.php`) acting as a secure LLM proxy. The API key lives **only** on the server — it is never exposed to the browser.
- **LLM:** OpenAI-compatible chat completions API (model configured via `.env`, see below).

## Getting Started

### Prerequisites
- PHP 8+ with the `curl` extension
- A web server that can serve PHP (Apache, nginx + PHP-FPM, or Caddy)
- Chrome or Edge for the speech-to-text feature

### Setup

1. Clone the repo:
   ```bash
   git clone https://github.com/sujith-vs/CapgeminiCommunicationTest.git
   cd CapgeminiCommunicationTest
   ```

2. Create the environment file at `api/.env`:

   ```env
   LLM_API_KEY=your-api-key-here
   LLM_BASE_URL=https://api.openai.com/v1
   LLM_MODEL=gpt-4o-mini
   ```

3. Serve the project root and open it in a browser. Example with PHP's dev server (development only):

   ```bash
   php -S localhost:8000
   ```

   Then visit `http://localhost:8000`.

> ⚠️ Never commit your real `api/.env`. It is loaded at runtime by `api/evaluate.php`.

## Environment Variables (`.env`)

| Variable | Required | Description |
|---|---|---|
| `LLM_API_KEY` | ✅ | Secret API key for the LLM provider. Server-side only — keep it out of the frontend. |
| `LLM_BASE_URL` | ✅ | OpenAI-compatible base URL. Defaults to `https://api.openai.com/v1` if unset. Point this at any OpenAI-compatible gateway/provider. |
| `LLM_MODEL` | ✅ | Chat model used for evaluation (e.g. `gpt-4o-mini`, `glm-4.6v`, `kimi-k2`…). The current deployment uses **`z-ai/glm-4.6v`** via an OpenAI-compatible gateway. |

### Models used

Evaluation runs through an **OpenAI-compatible** `/chat/completions` endpoint, so any compatible model works by editing `LLM_MODEL`:

- **Current production model:** `z-ai/glm-4.6v` (via `https://llm.drytis.ai/v1`)
- Also compatible out of the box: `gpt-4o-mini`, `gpt-4o`, DeepSeek, Kimi, and any other provider exposing an OpenAI-compatible API.

The evaluation prompt instructs the model to respond strictly in JSON:

```json
{
  "score": 0,
  "strengths": "...",
  "improvements": "...",
  "revised_answer": "..."
}
```

If the model returns malformed output, the backend attempts to recover the JSON blob before failing gracefully in the UI.

## API

### `POST /api/evaluate.php`

Evaluates an unstructured answer against a model answer.

**Request** (`application/json`):
```json
{
  "prompt": "The original question / scenario given to the candidate",
  "model_answer": "Reference model answer",
  "user_answer": "The candidate's written or transcribed-spoken answer"
}
```

**Response `200`:**
```json
{
  "score": 8,
  "strengths": "...",
  "improvements": "...",
  "revised_answer": "..."
}
```

**Resilience:** transient upstream failures (`429/5xx`/timeouts) are retried up to 3 times with back-off before surfacing a per-question error in the UI — the rest of the report stays intact.

## Project Structure

```
├── index.html          # entire SPA (UI, question bank, scoring, speech features)
├── api/
│   ├── evaluate.php    # LLM evaluation proxy (reads api/.env)
│   └── .env            # LLM_API_KEY / LLM_BASE_URL / LLM_MODEL (not committed)
└── README.md
```

## Notes & Limitations

- Speech recognition quality varies by browser/accent; Chrome desktop gives the best results.
- The spoken transcript is intentionally **read-only** — the test measures spoken delivery, not typing.
- MCQ answer keys live in `index.html` (client-side) for simplicity; they are not hidden from a determined user.
