# Phase 4 · Spoken Topics: Mic Input + Speech-to-Text + LLM Evaluation

## Goal
In "Spoken Topics", capture the user's spoken answer via microphone, transcribe it, and evaluate it against the model answer via LLM.

## Changes
- Add mic record/stop button per spoken question using the browser **Web Speech API** (`webkitSpeechRecognition` / `SpeechRecognition`), live transcript shown in a textarea (editable after transcription).
- Graceful fallback: if the browser doesn't support speech recognition, show a message and let the user type the answer.
- Transcript is submitted as the user answer; evaluation goes through the same `api/evaluate.php` LLM flow as other unstructured sections (compare vs model answer; feedback on correctness, fluency, improvements).
- Respect the 30-minute section timer: recording disabled after expiry.

## Acceptance Criteria
- [ ] Each Spoken Topics question has a mic button that transcribes speech to text into an editable field
- [ ] Unsupported browsers fall back to typing with a clear notice
- [ ] The transcribed text is evaluated by the LLM against the model answer with improvement feedback in View Solutions
- [ ] Recording stops and input locks when the timer expires

## Edge cases
- Mic permission denied → clear error + typed fallback
- Silence / no speech recognized → prompt user to retry
