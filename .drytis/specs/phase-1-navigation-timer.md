# Phase 1 · Navigation & Timer Rework

## Goal
Fix navigation and rework the timer behavior across the app.

## Changes
- `index.html` (and its JS): every section view gets a "← Back to Home" button (top-left) that returns to the section-selection home without losing state until test is submitted.
- Timer: remove the persistent top-right timer from the **home page**.
- Each section test starts a **30:00 countdown** shown in the top-right while the test is active.
- On expiry: auto-submit whatever the user has completed, lock inputs, show "Time's up — test auto-submitted", and unlock View Solutions.

## Acceptance Criteria
- [ ] Opening any section shows a Back to Home button that returns to the home screen
- [ ] The home page shows no timer
- [ ] Starting a test shows a 30-minute countdown in the top-right
- [ ] When the timer hits zero, the test closes automatically and a report is available in View Solutions

## Edge cases
- User leaves mid-test via Back to Home → confirm dialog ("test in progress, timer keeps running / resets — pick reset for simplicity")
- Timer must not duplicate when restarting a section
