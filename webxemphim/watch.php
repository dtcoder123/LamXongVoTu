<?php
/**
 * watch.php — Trang xem phim / chi tiết phim
 */

require_once __DIR__ . '/includes/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 1;

$stmt = $pdo->prepare('SELECT * FROM movies WHERE id = :id AND status = 1 LIMIT 1');
$stmt->execute([':id' => $id]);
$movie = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$movie) {
    $movie = $pdo->query('SELECT * FROM movies WHERE status = 1 ORDER BY id ASC LIMIT 1')->fetch(PDO::FETCH_ASSOC);
    $id = (int)($movie['id'] ?? 1);
}

$movie['cast'] = normalizeMovieCast($movie['cast'] ?? []);
$movie['video_url'] = trim((string)($movie['video_url'] ?? $movie['trailer_url'] ?? ''));
if ($movie['video_url'] === '') {
    $movie['video_url'] = 'https://www.w3schools.com/html/mov_bbb.mp4';
}

$googleDriveId = '';
if (preg_match('/drive\.google\.com\/file\/d\/([A-Za-z0-9_-]+)/i', $movie['video_url'], $matches)) {
    $googleDriveId = $matches[1];
    $movie['video_url'] = 'https://drive.google.com/file/d/' . $googleDriveId . '/preview';
} elseif (preg_match('/drive\.google\.com\/uc\?[^\s]*id=([A-Za-z0-9_-]+)/i', $movie['video_url'], $matches)) {
    $googleDriveId = $matches[1];
    $movie['video_url'] = 'https://drive.google.com/file/d/' . $googleDriveId . '/preview';
} elseif (!preg_match('/^(https?:\/\/|\/)/i', $movie['video_url'])) {
    $movie['video_url'] = 'uploads/movies/' . ltrim($movie['video_url'], '/');
}
$movie['trailer_url'] = $movie['video_url'];

$isYouTubeVideo = preg_match('/(?:youtube\.com|youtu\.be)/i', $movie['video_url']) === 1;
$isGoogleDriveVideo = $googleDriveId !== '' || preg_match('/drive\.google\.com\/(file\/d\/|uc\?)/i', $movie['video_url']) === 1;
$youtubeEmbedUrl = '';
if ($isYouTubeVideo) {
    preg_match('/(?:v=|be\/)([A-Za-z0-9_-]{11})/', $movie['video_url'], $youtubeMatches);
    $youtubeId = $youtubeMatches[1] ?? '';
    if ($youtubeId !== '') {
        $youtubeEmbedUrl = 'https://www.youtube.com/embed/' . $youtubeId . '?autoplay=0&controls=1&rel=0&enablejsapi=1';
    }
}
$pageTitle = $movie['title'];

$suggestedStmt = $pdo->prepare('SELECT * FROM movies WHERE id != :id AND status = 1 ORDER BY id DESC LIMIT 4');
$suggestedStmt->execute([':id' => $id]);
$suggested = $suggestedStmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($suggested as &$item) {
    $item['cast'] = normalizeMovieCast($item['cast'] ?? []);
}
unset($item);

$movieCatalog = array_map(function ($item) {
    return [
        'id' => (int)($item['id'] ?? 0),
        'title' => (string)($item['title'] ?? ''),
        'genre' => (string)($item['genre'] ?? ''),
        'tagline' => (string)($item['tagline'] ?? ''),
        'poster' => (string)($item['poster'] ?? ''),
        'keywords' => strtolower(trim((string)($item['title'] ?? '') . ' ' . (string)($item['genre'] ?? '') . ' ' . (string)($item['tagline'] ?? '') . ' ' . implode(' ', normalizeMovieCast($item['cast'] ?? []))))
    ];
}, $pdo->query('SELECT * FROM movies WHERE status = 1 ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC));

$servers = [
    ['name' => 'SERVER ALPHA', 'sub' => 'Ổn định · Full HD', 'status' => 'online'],
    ['name' => 'SERVER BETA — J.A.R.V.I.S', 'sub' => 'Tốc độ cao · 4K HDR', 'status' => 'online'],
    ['name' => 'SERVER GAMMA', 'sub' => 'Dự phòng · HD', 'status' => 'standby'],
];

include 'includes/header.php';
?>

<script>
window.movieCatalog = <?php echo json_encode($movieCatalog, JSON_UNESCAPED_UNICODE); ?>;
</script>

<main>
  <section class="watch-hero container">

    <!-- ============ PLAYER HUD ============ -->
    <div class="player-column">

      <div class="player-hud hud-frame">
        <div class="hud-corner hud-corner--tl"></div>
        <div class="hud-corner hud-corner--tr"></div>
        <div class="hud-corner hud-corner--bl"></div>
        <div class="hud-corner hud-corner--br"></div>

        <div class="player-hud__topbar">
          <span class="hud-tag">STREAM_ID // <?php echo str_pad($id, 4, '0', STR_PAD_LEFT); ?></span>
          <span class="hud-tag hud-tag--live"><span class="live-dot"></span> ĐANG PHÁT</span>
          <span class="hud-tag">BUFFER: 100%</span>
        </div>

        <div class="player-hud__screen" id="playerScreen">
          <?php if ($isYouTubeVideo && $youtubeEmbedUrl !== ''): ?>
            <iframe id="moviePlayer" class="player-hud__video" src="<?php echo htmlspecialchars($youtubeEmbedUrl); ?>" title="<?php echo htmlspecialchars($movie['title']); ?>" frameborder="0" allow="accelerometer; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
          <?php elseif ($isGoogleDriveVideo && $googleDriveId !== ''): ?>
            <iframe id="moviePlayer" class="player-hud__video" src="https://drive.google.com/file/d/<?php echo htmlspecialchars($googleDriveId); ?>/preview" title="<?php echo htmlspecialchars($movie['title']); ?>" frameborder="0" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen></iframe>
          <?php else: ?>
            <video id="moviePlayer" class="player-hud__video" controls playsinline preload="metadata" poster="<?php echo htmlspecialchars($movie['poster']); ?>" src="<?php echo htmlspecialchars($movie['video_url']); ?>"></video>
          <?php endif; ?>
          <div class="player-hud__scan"></div>
          <div class="player-hud__crosshair">
            <span></span><span></span><span></span><span></span>
          </div>
          <?php if (!$isYouTubeVideo): ?>
            <button class="player-hud__playbtn" id="playBtn" aria-label="Phát video">▶</button>
          <?php endif; ?>
        </div>

      </div>

      <!-- ============ SERVER SELECT ============ -->
      <div class="server-select">
        <h3 class="block-heading"><span class="block-heading__index">A</span> CHỌN SERVER PHÁT</h3>
        <div class="server-list">
          <?php foreach ($servers as $i => $s): ?>
            <button class="server-chip <?php echo $i === 1 ? 'is-active' : ''; ?> <?php echo $s['status'] === 'standby' ? 'is-standby' : ''; ?>">
              <span class="server-chip__dot"></span>
              <span class="server-chip__text">
                <strong><?php echo htmlspecialchars($s['name']); ?></strong>
                <small><?php echo htmlspecialchars($s['sub']); ?></small>
              </span>
            </button>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- ============ MOVIE INFO ============ -->
        <div class="movie-info hud-panel" id="movieInfo">
        <div class="hud-corner hud-corner--tl"></div>
        <div class="hud-corner hud-corner--br"></div>

        <div class="movie-info__head">
          <h1 class="movie-info__title"><?php echo htmlspecialchars($movie['title']); ?></h1>
          <div class="hero__energy-bar hero__energy-bar--sm">
            <span class="hero__energy-fill" style="width:<?php echo $movie['rating'] * 10; ?>%"></span>
          </div>
        </div>

        <div class="hero__meta">
          <span class="meta-chip meta-chip--rating">★ <?php echo $movie['rating']; ?></span>
          <span class="meta-chip"><?php echo $movie['year']; ?></span>
          <span class="meta-chip"><?php echo htmlspecialchars($movie['duration']); ?></span>
          <span class="meta-chip"><?php echo htmlspecialchars($movie['genre']); ?></span>
        </div>

        <p class="movie-info__desc"><?php echo htmlspecialchars($movie['description'] ?? $movie['desc'] ?? ''); ?></p>

        <div class="movie-info__crew">
          <p><span class="mono-label">ĐẠO DIỄN:</span> <?php echo htmlspecialchars($movie['director']); ?></p>
          <p><span class="mono-label">DIỄN VIÊN:</span> <?php echo htmlspecialchars(implode(' · ', $movie['cast'])); ?></p>
        </div>

        <div class="hero__actions">
          <button class="btn-hud btn-hud--primary">☆ THÊM VÀO DANH SÁCH</button>
          <button class="btn-hud btn-hud--ghost">⬇ TẢI XUỐNG</button>
        </div>
      </div>

    </div>

    <!-- ============ SUGGESTED SIDEBAR ============ -->
    <aside class="suggested-column">
      <h3 class="block-heading"><span class="block-heading__index">B</span> DỮ LIỆU ĐỀ XUẤT</h3>

      <div class="suggested-list">
        <?php foreach ($suggested as $s): ?>
          <a href="watch.php?id=<?php echo (int)$s['id']; ?>" class="suggested-card" data-id="<?php echo (int)$s['id']; ?>" data-title="<?php echo htmlspecialchars($s['title'], ENT_QUOTES); ?>" data-genre="<?php echo htmlspecialchars($s['genre'], ENT_QUOTES); ?>" data-tagline="<?php echo htmlspecialchars($s['tagline'] ?? '', ENT_QUOTES); ?>" data-poster="<?php echo htmlspecialchars($s['poster'], ENT_QUOTES); ?>" data-rating="<?php echo htmlspecialchars((string)($s['rating'] ?? '0'), ENT_QUOTES); ?>" data-year="<?php echo htmlspecialchars((string)($s['year'] ?? ''), ENT_QUOTES); ?>">
            <div class="suggested-card__thumb">
              <img src="<?php echo $s['poster']; ?>" alt="<?php echo htmlspecialchars($s['title']); ?>" loading="lazy">
              <span class="suggested-card__play">▶</span>
            </div>
            <div class="suggested-card__body">
              <h4><?php echo htmlspecialchars($s['title']); ?></h4>
              <span class="suggested-card__meta"><?php echo htmlspecialchars($s['genre']); ?> · <?php echo $s['year']; ?></span>
              <div class="rating-bar rating-bar--sm">
                <div class="rating-bar__track">
                  <div class="rating-bar__fill" style="width:<?php echo $s['rating'] * 10; ?>%"></div>
                </div>
                <span class="rating-bar__value"><?php echo $s['rating']; ?></span>
              </div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    </aside>

  </section>
</main>

<?php include 'includes/footer.php'; ?>
