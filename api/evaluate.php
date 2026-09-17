<?php
// api/evaluate.php — compares a user's unstructured answer to the model answer via LLM.
// Env vars come from /workspace/api/.env: LLM_API_KEY, LLM_BASE_URL, LLM_MODEL.

header('Content-Type: application/json');

// Load .env (only used when not provided as real env)
$envFile = __DIR__ . '/.env';
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) continue;
        [$k, $v] = explode('=', $line, 2);
        if (getenv($k) === false) putenv(trim($k) . '=' . trim(trim($v), "\"'"));
    }
}

$apiKey  = getenv('LLM_API_KEY');
$baseUrl = rtrim(getenv('LLM_BASE_URL') ?: 'https://llm.drytis.ai/v1', '/');
$model   = getenv('LLM_MODEL') ?: 'gpt-4o-mini';

if (!$apiKey) {
    http_response_code(500);
    echo json_encode(['error' => 'LLM API key not configured']);
    exit;
}

$raw = file_get_contents('php://input');
$req = json_decode($raw, true);
$prompt      = trim($req['prompt'] ?? '');
$modelAnswer = trim($req['model_answer'] ?? '');
$userAnswer  = trim($req['user_answer'] ?? '');

if ($prompt === '' || $modelAnswer === '' || $userAnswer === '') {
    http_response_code(422);
    echo json_encode(['error' => 'prompt, model_answer and user_answer are required']);
    exit;
}

$system = <<<SYS
You are a professional business-communication examiner for a Capgemini-style communication assessment.
Compare the candidate's answer to the reference model answer for the given prompt.
The candidate's answer may be a written email/proposal or a spoken-response transcript (transcribed via speech-to-text, so it may contain verbal fillers, minor transcription errors or run-on sentences — interpret generously but grade language quality as spoken/written work).
Score out of 10. Respond ONLY with valid JSON in this exact shape:
{"score": <integer 0-10>, "strengths": "<2-3 sentences>", "improvements": "<2-4 sentences, concrete and actionable>", "revised_answer": "<an improved model version of the candidate's answer, keeping their intent>"}
SYS;

$userMsg = "PROMPT GIVEN TO CANDIDATE:\n{$prompt}\n\nREFERENCE MODEL ANSWER:\n{$modelAnswer}\n\nCANDIDATE'S ANSWER:\n{$userAnswer}";

function buildPayload($model, $system, $userMsg) {
    return json_encode([
        'model' => $model,
        'messages' => [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $userMsg],
        ],
        'temperature' => 0.3,
    ]);
}

$payload = buildPayload($model, $system, $userMsg);

$response = false;
$httpCode = 0;
$curlErr  = '';
$attempts = 3; // retry transient upstream failures (502/503/429/timeouts)
for ($i = 1; $i <= $attempts; $i++) {
    $ch = curl_init($baseUrl . '/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ],
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_TIMEOUT => 45,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    $transient = ($response === false) || in_array($httpCode, [429, 500, 502, 503, 504], true);
    if (!$transient) break;
    if ($i < $attempts) {
        sleep($i * 2);
        $payload = buildPayload($model, $system, $userMsg); // rebuild in case of encoding issues
    }
}

if ($response === false) {
    http_response_code(502);
    echo json_encode(['error' => 'LLM request failed: ' . $curlErr]);
    exit;
}
if ($httpCode !== 200) {
    http_response_code(502);
    echo json_encode(['error' => 'LLM returned HTTP ' . $httpCode]);
    exit;
}

$body = json_decode($response, true);
$content = $body['choices'][0]['message']['content'] ?? null;
if (!$content) {
    http_response_code(502);
    echo json_encode(['error' => 'Unexpected LLM response shape']);
    exit;
}

$eval = json_decode($content, true);
if (!is_array($eval)) {
    // Some models wrap JSON in prose or code fences — try to recover.
    if (preg_match('/\{[\s\S]*\}/', $content, $mm)) {
        $eval = json_decode($mm[0], true);
    }
}
if (!is_array($eval)) {
    http_response_code(502);
    echo json_encode(['error' => 'LLM returned non-JSON evaluation']);
    exit;
}

echo json_encode([
    'score'          => (int)($eval['score'] ?? 0),
    'strengths'      => (string)($eval['strengths'] ?? ''),
    'improvements'   => (string)($eval['improvements'] ?? ''),
    'revised_answer' => (string)($eval['revised_answer'] ?? ''),
]);
