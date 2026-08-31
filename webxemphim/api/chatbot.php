<?php
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'answer' => 'Phương thức không hợp lệ. Vui lòng gửi yêu cầu POST.'
    ]);
    exit;
}

$rawBody = file_get_contents('php://input');
$data = json_decode($rawBody, true);
$question = trim((string) ($data['question'] ?? ''));

if ($question === '') {
    http_response_code(400);
    echo json_encode([
        'answer' => 'Bạn chưa nhập câu hỏi.'
    ]);
    exit;
}

$fallbackAnswer = 'Tôi đang tìm câu trả lời phù hợp cho bạn. Hãy thử hỏi theo kiểu: “gợi ý phim hành động”, “phim khoa học viễn tưởng”, hoặc “đăng nhập”.';

$prompt = "Bạn là trợ lý AI cho website xem phim FILM.SYS. Hãy trả lời bằng tiếng Việt, ngắn gọn, tự nhiên, thân thiện và đúng ngữ cảnh. Nếu người dùng hỏi về phim, hãy ưu tiên gợi ý phim phù hợp với nhu cầu. Nếu website chưa có loại phim đó, hãy nói rõ sự thật và đề xuất các phim gần nhất trong kho dữ liệu hiện có.\n\nCâu hỏi: {$question}\n\nTrả lời:";

$endpoint = 'https://api-inference.huggingface.co/models/google/gemma-2-2b-it';
$token = trim((string) getenv('HF_API_TOKEN'));
$headers = ['Content-Type: application/json'];
if ($token !== '') {
    $headers[] = 'Authorization: Bearer ' . $token;
}

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $endpoint,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_POSTFIELDS => json_encode(['inputs' => $prompt]),
]);

$response = curl_exec($ch);
$curlError = curl_error($ch);
$httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$answer = $fallbackAnswer;

if ($response !== false && $curlError === '') {
    $decoded = json_decode($response, true);

    if (is_array($decoded) && isset($decoded[0]['generated_text'])) {
        $answer = trim((string) $decoded[0]['generated_text']);
    } elseif (is_array($decoded) && isset($decoded['generated_text'])) {
        $answer = trim((string) $decoded['generated_text']);
    } elseif (is_array($decoded) && isset($decoded['error'])) {
        $answer = trim((string) $decoded['error']);
    }
}

if ($answer === '' || $answer === 'null' || $httpCode >= 400) {
    $answer = $fallbackAnswer;
}

$answer = preg_replace('/\s+/u', ' ', $answer);
$answer = preg_replace('/^.*?Trả lời\s*:/uis', '', $answer);
$answer = trim($answer);

if ($answer === '') {
    $answer = $fallbackAnswer;
}

echo json_encode(['answer' => $answer]);
