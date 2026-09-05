<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/db.php';

function chatJson(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function chatNormalize(string $value): string
{
    $value = mb_strtolower(trim($value), 'UTF-8');
    $value = strtr($value, [
        'à'=>'a','á'=>'a','ả'=>'a','ã'=>'a','ạ'=>'a','ă'=>'a','ằ'=>'a','ắ'=>'a','ẳ'=>'a','ẵ'=>'a','ặ'=>'a','â'=>'a','ầ'=>'a','ấ'=>'a','ẩ'=>'a','ẫ'=>'a','ậ'=>'a',
        'è'=>'e','é'=>'e','ẻ'=>'e','ẽ'=>'e','ẹ'=>'e','ê'=>'e','ề'=>'e','ế'=>'e','ể'=>'e','ễ'=>'e','ệ'=>'e',
        'ì'=>'i','í'=>'i','ỉ'=>'i','ĩ'=>'i','ị'=>'i','ò'=>'o','ó'=>'o','ỏ'=>'o','õ'=>'o','ọ'=>'o','ô'=>'o','ồ'=>'o','ố'=>'o','ổ'=>'o','ỗ'=>'o','ộ'=>'o','ơ'=>'o','ờ'=>'o','ớ'=>'o','ở'=>'o','ỡ'=>'o','ợ'=>'o',
        'ù'=>'u','ú'=>'u','ủ'=>'u','ũ'=>'u','ụ'=>'u','ư'=>'u','ừ'=>'u','ứ'=>'u','ử'=>'u','ữ'=>'u','ự'=>'u','ỳ'=>'y','ý'=>'y','ỷ'=>'y','ỹ'=>'y','ỵ'=>'y','đ'=>'d',
    ]);
    $value = preg_replace('/[^a-z0-9]+/i', ' ', $value) ?? '';
    return trim(preg_replace('/\s+/', ' ', $value) ?? '');
}

function chatTokens(string $value): array
{
    $ignored = array_flip(['cho','toi','minh','mot','vai','bo','phim','movie','xem','tim','co','va','ve','the','loai','hay','nhat','giup','voi','rating','diem','danh','gia','moi','top','goi','y','duoc','tren','website','web','khong','thuoc','loai','tap','tung','so','cao','thap','nhieu','it','ton','tai','phu','hop','cua','dien','vien','dao','director','actor','tu','den']);
    $tokens = preg_split('/\s+/', chatNormalize($value), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    return array_values(array_filter($tokens, fn($token) => mb_strlen($token) >= 2 && !isset($ignored[$token])));
}

function chatMoviePayload(array $movie): array
{
    return ['id' => (int)$movie['id'], 'title' => (string)$movie['title'], 'genre' => (string)$movie['genre'], 'year' => (int)$movie['year'], 'rating' => (string)$movie['rating'], 'poster' => (string)$movie['poster'], 'watch_url' => 'watch.php?id=' . (int)$movie['id']];
}

function chatEntityMatches(string $needle, array $values): array
{
    $needle = chatNormalize($needle);
    if ($needle === '') return [];
    $matches = [];
    foreach ($values as $value) {
        $normalized = chatNormalize((string)$value);
        if ($normalized !== '' && (str_contains($normalized, $needle) || str_contains($needle, $normalized))) $matches[] = $value;
    }
    return array_values(array_unique($matches));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') chatJson(['error' => 'Chỉ hỗ trợ phương thức POST.'], 405);
$input = json_decode(file_get_contents('php://input') ?: '', true);
$message = trim((string)($input['message'] ?? ''));
if ($message === '' || mb_strlen($message, 'UTF-8') > 2000) chatJson(['error' => 'Tin nhắn phải có từ 1 đến 2000 ký tự.'], 422);

$now = time();
$recent = array_values(array_filter($_SESSION['chat_request_times'] ?? [], fn($time) => $time > $now - 60));
if (count($recent) >= 20) chatJson(['error' => 'Bạn gửi hơi nhanh. Vui lòng thử lại sau một phút.'], 429);
$_SESSION['chat_request_times'] = [...$recent, $now];

$movies = $pdo->query('SELECT id, title, genre, rating, year, duration, poster, tagline, description, director, cast FROM movies WHERE status = 1')->fetchAll(PDO::FETCH_ASSOC);
$genres = $pdo->query("SELECT DISTINCT genre FROM movies WHERE status = 1 AND genre <> '' ORDER BY genre")->fetchAll(PDO::FETCH_COLUMN);
$normalizedMessage = chatNormalize($message);
$tokens = chatTokens($message);

if (!preg_match('/\b(phim|movie|xem|tim|tìm|goi|gợi|rating|diem|điểm|the loai|thể loại|dien vien|diễn viên|dao dien|đạo diễn|top|hay|moi|mới|tap|tập|website|web|kho|kho phim)\b/ui', $message)) {
    chatJson(['answer' => 'Xin lỗi, tôi chỉ có thể hỗ trợ bạn tìm kiếm và gợi ý phim trên website.', 'movies' => []]);
}

$limit = 5;
if (preg_match('/\b(?:top|goi y|gợi ý|cho toi|cho tôi)\s*(\d{1,2})\b/ui', $message, $match)) $limit = min(20, max(1, (int)$match[1]));
$ratingMinimum = null;
if (preg_match('/(?:tren|trên|hon|hơn|tu|từ|>=)\s*(\d+(?:[.,]\d+)?)/ui', $message, $match)) $ratingMinimum = (float)str_replace(',', '.', $match[1]);
$wantsTopRated = (bool)preg_match('/\b(?:top|cao nhat|cao nhất|hay nhat|hay nhất|danh gia cao|đánh giá cao|rating cao|tot nhat|tốt nhất)\b/ui', $message);
$wantsNewest = (bool)preg_match('/\b(?:moi nhat|mới nhất|moi cap nhat|mới cập nhật)\b/ui', $message);

$actorNeedle = '';
if (preg_match('/(?:dien vien|diễn viên)\s+(.+?)(?=\s+(?:co|có|va|và|rating|the loai|thể loại|top)\b|$)/ui', $message, $match)) $actorNeedle = trim($match[1]);
elseif (preg_match('/\bphim\s+co\s+(.+?)(?=\s+(?:rating|the loai|thể loại|top)\b|$)/ui', $message, $match)) $actorNeedle = trim($match[1]);
elseif (preg_match('/\b(?:co|có)\s+(.+?)(?=\s+(?:rating|the loai|thể loại|top|tren|trên)\b|$)/ui', $message, $match)) $actorNeedle = trim($match[1]);
$directorNeedle = '';
if (preg_match('/(?:dao dien|đạo diễn)\s+(.+?)(?=\s+(?:co|có|va|và|rating|the loai|thể loại|top)\b|$)/ui', $message, $match)) $directorNeedle = trim($match[1]);
elseif (preg_match('/\bphim\s+cua\s+(.+?)(?=\s+(?:co|có|rating|the loai|thể loại|top)\b|$)/ui', $message, $match)) $directorNeedle = trim($match[1]);
elseif (preg_match('/\b(?:cua|của)\s+(.+?)(?=\s+(?:co|có|rating|the loai|thể loại|top|tren|trên)\b|$)/ui', $message, $match)) $directorNeedle = trim($match[1]);

$actorValues = [];
$directorValues = [];
foreach ($movies as $movie) {
    $actorValues = [...$actorValues, ...normalizeMovieCast($movie['cast'] ?? [])];
    if (trim((string)$movie['director']) !== '') $directorValues[] = $movie['director'];
}
$actorMatches = $actorNeedle !== '' ? chatEntityMatches($actorNeedle, $actorValues) : [];
if ($actorNeedle !== '' && !$actorMatches) chatJson(['answer' => "Xin lỗi, không tìm thấy diễn viên '{$actorNeedle}' trong hệ thống.", 'movies' => []]);
$directorMatches = $directorNeedle !== '' ? chatEntityMatches($directorNeedle, $directorValues) : [];
if ($directorNeedle !== '' && !$directorMatches) chatJson(['answer' => "Xin lỗi, không tìm thấy đạo diễn '{$directorNeedle}' trong hệ thống.", 'movies' => []]);

$genreNeedle = '';
if (preg_match('/(?:the loai|thể loại|genre)\s+(.+?)(?=\s+(?:co|có|rating|top|tren|trên)\b|$)/ui', $message, $match)) $genreNeedle = trim($match[1]);
elseif (preg_match('/^\s*(?:(?:top\s+\d+|goi y|gợi ý|cho toi|cho tôi)\s+)?phim\s+(.+?)(?=\s+(?:co|có|rating|top|tren|trên)\b|$)/ui', $message, $match) && $actorNeedle === '' && $directorNeedle === '' && !preg_match('/^\s*(?:ve|về)\b/ui', trim($match[1]))) $genreNeedle = trim($match[1]);
$genreMatches = $genreNeedle !== '' ? chatEntityMatches($genreNeedle, $genres) : [];
if ($genreNeedle !== '' && !$genreMatches) chatJson(['answer' => "Xin lỗi, thể loại '{$genreNeedle}' hiện không có trong hệ thống.", 'movies' => []]);

$movieNeedle = '';
if ($actorNeedle === '' && $directorNeedle === '' && $genreNeedle === '' && !preg_match('/\b(?:ve|về)\s+/ui', $message) && preg_match('/(?:tim|tìm|phim)\s+(.+)/ui', $message, $match)) {
    $movieNeedle = trim($match[1]);
    $movieNeedle = preg_replace('/^phim\s+/ui', '', $movieNeedle) ?? $movieNeedle;
    $movieNeedle = preg_replace('/\s+(?:co|có|rating|top|tren|trên)\s+.*$/ui', '', $movieNeedle) ?? $movieNeedle;
}
$movieMatches = $movieNeedle !== '' ? chatEntityMatches($movieNeedle, array_column($movies, 'title')) : [];
if ($movieNeedle !== '' && !$movieMatches) chatJson(['answer' => "Xin lỗi, phim '{$movieNeedle}' hiện không có trong hệ thống.", 'movies' => []]);

$contentNeedle = '';
if ($actorNeedle === '' && $directorNeedle === '' && $genreNeedle === '' && $movieNeedle === '' && preg_match('/\b(?:ve|về)\s+(.+)/ui', $message, $match)) $contentNeedle = trim($match[1]);

$results = [];
foreach ($movies as $movie) {
    if ($ratingMinimum !== null && (float)$movie['rating'] < $ratingMinimum) continue;
    if ($genreMatches && !in_array($movie['genre'], $genreMatches, true)) continue;
    if ($movieMatches && !in_array($movie['title'], $movieMatches, true)) continue;
    if ($directorMatches && !in_array($movie['director'], $directorMatches, true)) continue;
    if ($actorMatches && !array_intersect(normalizeMovieCast($movie['cast'] ?? []), $actorMatches)) continue;
    if ($contentNeedle && !str_contains(chatNormalize(implode(' ', [$movie['title'], $movie['tagline'], $movie['description']])), chatNormalize($contentNeedle))) continue;
    if (!$actorMatches && !$directorMatches && !$genreMatches && !$movieMatches && !$contentNeedle && !$wantsTopRated && !$wantsNewest && $ratingMinimum === null) continue;
    $results[] = $movie;
}

if ($wantsNewest) usort($results, fn($a, $b) => ((int)$b['year'] <=> (int)$a['year']) ?: ((int)$b['id'] <=> (int)$a['id']));
else usort($results, fn($a, $b) => (float)$b['rating'] <=> (float)$a['rating']);
$results = array_slice($results, 0, $limit);
if (!$results) chatJson(['answer' => 'Xin lỗi, hiện tại không tìm thấy phim phù hợp với yêu cầu của bạn.', 'movies' => []]);

$answer = count($results) === 1 ? 'Mình tìm thấy bộ phim phù hợp với yêu cầu của bạn:' : 'Mình tìm thấy ' . count($results) . ' phim phù hợp với yêu cầu của bạn:';
if ($wantsTopRated) $answer .= ' Danh sách được sắp xếp theo rating từ cao xuống thấp.';
chatJson(['answer' => $answer, 'movies' => array_map('chatMoviePayload', $results)]);
