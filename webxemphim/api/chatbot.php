<?php

/**
 * ai-chat.php — Trợ lý AI thông minh tích hợp kho dữ liệu STARK-SYS
 */
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

// 1. KẾT NỐI DATABASE VÀ LẤY TOÀN BỘ KHO PHIM ĐỂ TRỢ LÝ CÓ "BỘ NHỚ"
require_once __DIR__ . '/includes/db.php';

$moviesList = [];
try {
    $stmt = $pdo->query('SELECT id, title, genre, tagline, year, duration, rating FROM movies WHERE status = 1 ORDER BY id DESC');
    $moviesList = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Xử lý nếu lỗi DB
}

// Tổng hợp danh sách phim thành chuỗi ngắn gọn để đưa vào bộ nhớ của AI
$movieDatabaseText = "";
foreach ($moviesList as $m) {
    $movieDatabaseText .= "- [ID: {$m['id']}] {$m['title']} | Thể loại: {$m['genre']} | Năm: {$m['year']} | Đánh giá: {$m['rating']}★ | Mô tả: {$m['tagline']}\n";
}

$fallbackAnswer = 'Tôi đang kết nối với hệ thống STARK-SYS. Bạn có thể hỏi tôi về gợi ý phim hành động, hoạt hình, khoa học viễn tưởng, hoặc cách sử dụng website nhé!';

// 2. XÂY DỰNG PROMPT THÔNG MINH VÀ ĐA DỤNG HƠN
$prompt = "Bạn là FILM-SYS AI, trợ lý ảo cao cấp, thông minh, am hiểu điện ảnh và cực kỳ đáng tin cậy cho website xem phim STARK-SYS.
Nhiệm vụ của bạn:
1. Trả lời bằng tiếng Việt, phong cách công nghệ HUD hiện đại, thân thiện, súc tích, tự nhiên.
2. Nếu người dùng hỏi gợi ý phim, hãy tra cứu trong KHO DỮ LIỆU PHIM bên dưới để chọn đúng các bộ phim đang có sẵn, giới thiệu tên, thể loại và điểm số cho họ. Tuyệt đối không tự bịa ra phim không có trong danh sách.
3. Nếu kho dữ liệu không có thể loại phim đó, hãy lịch sự thông báo và gợi ý các phim hay nhất gần giống nhất đang có sẵn.
4. Bạn cũng có thể hướng dẫn người dùng cách đăng nhập, xem thông tin phim, hoặc tìm kiếm trên web.

KHO DỮ LIỆU PHIM HIỆN CÓ TRÊN HỆ THỐNG:
{$movieDatabaseText}

Câu hỏi của người dùng: {$question}

Trợ lý STARK-SYS trả lời:";

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

// Làm sạch văn bản trả về từ mô hình AI
$answer = preg_replace('/\s+/u', ' ', $answer);
$answer = preg_replace('/^.*?trả lời\s*:/uis', '', $answer);
$answer = preg_replace('/^.*?Trợ lý FILM-SYS trả lời\s*:/uis', '', $answer);
$answer = trim($answer);

if ($answer === '') {
    $answer = $fallbackAnswer;
}

echo json_encode(['answer' => $answer]);
