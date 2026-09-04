<?php

/**
 * watch.php — Trang xem phim / chi tiết phim
 */
session_start();

// Kiểm tra xem người dùng đã đăng nhập chưa, nếu chưa thì đá sang trang login
if (empty($_SESSION['user_logged_in'])) {
  $_SESSION['flash_message'] = 'Vui lòng đăng nhập tài khoản FILM-SYS để xem phim.';
  header('Location: login.php');
  exit;
}

require_once __DIR__ . '/includes/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 1;

// Xử lý gửi bình luận & đánh giá
$commentError = '';
$commentSuccess = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_comment'])) {
  if (empty($_SESSION['user_logged_in'])) {
    $commentError = 'Bạn cần đăng nhập để thực hiện thao tác này.';
  } else {
    $rating = (int)($_POST['rating'] ?? 5);
    $content = trim($_POST['comment'] ?? '');

    if ($rating < 1 || $rating > 5) {
      $rating = 5;
    }

    if ($content === '') {
      $commentError = 'Nội dung bình luận không được để trống.';
    } else {
      $stmtComm = $pdo->prepare('INSERT INTO movie_comments (movie_id, user_id, rating, comment) VALUES (:movie_id, :user_id, :rating, :comment)');
      $stmtComm->execute([
        ':movie_id' => $id,
        ':user_id' => $_SESSION['user_id'],
        ':rating' => $rating,
        ':comment' => $content
      ]);
      $commentSuccess = 'Đã truyền tải dữ liệu bình luận thành công vào hệ thống.';
    }
  }
}

// Xử lý xóa bình luận
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_comment_id'])) {
  if (empty($_SESSION['user_logged_in'])) {
    $commentError = 'Bạn cần đăng nhập để thực hiện thao tác này.';
  } else {
    $delId = (int)$_POST['delete_comment_id'];

    // Kiểm tra xem bình luận có tồn tại và thuộc về user hiện tại hay là admin không
    $checkStmt = $pdo->prepare('SELECT user_id FROM movie_comments WHERE id = :id LIMIT 1');
    $checkStmt->execute([':id' => $delId]);
    $targetComment = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if ($targetComment) {
      $isOwner = ((int)$targetComment['user_id'] === (int)($_SESSION['user_id'] ?? 0));
      $isAdmin = (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin');

      if ($isOwner || $isAdmin) {
        $delStmt = $pdo->prepare('DELETE FROM movie_comments WHERE id = :id');
        $delStmt->execute([':id' => $delId]);

        // Tải lại trang để cập nhật danh sách
        header('Location: watch.php?id=' . $id);
        exit;
      } else {
        $commentError = 'Bạn không có quyền xóa bản ghi dữ liệu này.';
      }
    }
  }
}

// Lấy danh sách bình luận của phim này kèm tên user
$commentsStmt = $pdo->prepare('
    SELECT c.*, u.username 
    FROM movie_comments c 
    JOIN users u ON c.user_id = u.id 
    WHERE c.movie_id = :movie_id 
    ORDER BY c.id DESC
');
$commentsStmt->execute([':movie_id' => $id]);
$commentsList = $commentsStmt->fetchAll(PDO::FETCH_ASSOC);

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


      <!-- ============ KHỐI BÌNH LUẬN & ĐÁNH GIÁ (TÁCH RIÊNG) ============ -->
      <div class="movie-comments-section hud-panel" style="margin-top: 24px; padding: 24px; position: relative;">
        <div class="hud-corner hud-corner--tl"></div>
        <div class="hud-corner hud-corner--br"></div>

        <h3 class="block-heading" style="margin-bottom: 16px;">
          <span class="block-heading__index">C</span> BÌNH LUẬN VÀ ĐÁNH GIÁ (<?php echo count($commentsList); ?>)
        </h3>

        <?php if ($commentError): ?>
          <div style="margin-bottom: 16px; padding: 10px 12px; background: rgba(255,77,0,0.12); border: 1px solid rgba(255,77,0,0.35); color: #ffd7c7; font-family: 'Rajdhani', sans-serif;">
            <?php echo htmlspecialchars($commentError); ?>
          </div>
        <?php endif; ?>

        <?php if ($commentSuccess): ?>
          <div style="margin-bottom: 16px; padding: 10px 12px; background: rgba(0,240,255,0.12); border: 1px solid rgba(0,240,255,0.35); color: #d7f4fb; font-family: 'Rajdhani', sans-serif;">
            <?php echo htmlspecialchars($commentSuccess); ?>
          </div>
        <?php endif; ?>

        <!-- Form gửi bình luận (Chỉ hiển thị khi đã đăng nhập) -->
        <?php if (!empty($_SESSION['user_logged_in'])): ?>
          <form method="post" style="background: rgba(0,240,255,0.03); border: 1px solid rgba(0,240,255,0.15); padding: 16px; margin-bottom: 24px;">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
              <label style="font-family: 'Rajdhani', sans-serif; color: #7fa8b8; font-weight: 600;">MỨC ĐỘ ĐÁNH GIÁ:</label>
              <select name="rating" style="background: rgba(0,240,255,0.08); border: 1px solid rgba(0,240,255,0.3); color: #00f0ff; padding: 6px 10px; font-family: 'Rajdhani', sans-serif; font-weight: bold;">
                <option value="5" style="background: #0b0f19;">★★★★★ (5/5 - Tuyệt vời)</option>
                <option value="4" style="background: #0b0f19;">★★★★☆ (4/5 - Rất tốt)</option>
                <option value="3" style="background: #0b0f19;">★★★☆☆ (3/5 - Ổn định)</option>
                <option value="2" style="background: #0b0f19;">★★☆☆☆ (2/5 - Tạm được)</option>
                <option value="1" style="background: #0b0f19;">★☆☆☆☆ (1/5 - Kém)</option>
              </select>
            </div>
            <div style="margin-bottom: 12px;">
              <textarea name="comment" rows="3" placeholder="Nhập nội dung bình luận của bạn về phim này..." style="width: 100%; background: rgba(0,240,255,0.05); border: 1px solid rgba(0,240,255,0.2); color: #d7f4fb; padding: 10px; font-family: 'Rajdhani', sans-serif; font-size: 15px; resize: vertical;" required></textarea>
            </div>
            <button type="submit" name="submit_comment" class="btn-hud btn-hud--primary" style="font-size: 14px; padding: 8px 16px;">
              <span class="btn-hud__icon">⚡</span> GỬI BÌNH LUẬN
            </button>
          </form>
        <?php else: ?>
          <div style="background: rgba(0,240,255,0.03); border: 1px solid rgba(0,240,255,0.15); padding: 14px; margin-bottom: 24px; text-align: center; font-family: 'Rajdhani', sans-serif; color: #7fa8b8;">
            Vui lòng <a href="login.php" style="color: #00f0ff; text-decoration: underline;">ĐĂNG NHẬP</a> để gửi đánh giá và bình luận.
          </div>
        <?php endif; ?>

        <!-- Danh sách hiển thị bình luận -->
        <div class="comments-list" style="display: grid; gap: 12px;">
          <?php if (empty($commentsList)): ?>
            <p style="font-family: 'Rajdhani', sans-serif; color: #5a7b8c; font-style: italic;">Chưa có bình luận nào cho bộ phim này.</p>
          <?php else: ?>
            <?php foreach ($commentsList as $comm): ?>
              <div style="background: rgba(0,240,255,0.02); border-left: 2px solid #00f0ff; padding: 12px 14px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                  <span style="font-family: 'Rajdhani', sans-serif; font-weight: bold; color: #00f0ff; font-size: 15px;"><?php echo htmlspecialchars($comm['username']); ?></span>

                  <div style="display: flex; align-items: center; gap: 12px;">
                    <span style="font-family: 'Rajdhani', sans-serif; color: #f39c12; font-size: 13px;">
                      <?php echo str_repeat('★', (int)$comm['rating']) . str_repeat('☆', 5 - (int)$comm['rating']); ?>
                      <span style="color: #5a7b8c; margin-left: 6px;"><?php echo $comm['created_at']; ?></span>
                    </span>

                    <!-- Nút xóa chỉ hiện khi đúng chủ nhân hoặc admin -->
                    <?php
                    $isOwner = !empty($_SESSION['user_id']) && ((int)$comm['user_id'] === (int)$_SESSION['user_id']);
                    $isAdmin = !empty($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
                    if ($isOwner || $isAdmin):
                    ?>
                      <form method="post" onsubmit="return confirm('Xác nhận xóa bản ghi đánh giá này khỏi hệ thống?');" style="margin: 0;">
                        <input type="hidden" name="delete_comment_id" value="<?php echo (int)$comm['id']; ?>">
                        <button type="submit" style="background: transparent; border: 1px solid rgba(255,77,0,0.3); color: #ff4d00; font-family: 'Rajdhani', sans-serif; font-size: 11px; padding: 2px 6px; cursor: pointer; letter-spacing: 0.05em;">[ XÓA ]</button>
                      </form>
                    <?php endif; ?>
                  </div>
                </div>

                <!--<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                  <span style="font-family: 'Rajdhani', sans-serif; font-weight: bold; color: #00f0ff; font-size: 15px;"><?php echo htmlspecialchars($comm['username']); ?></span>
                  <span style="font-family: 'Rajdhani', sans-serif; color: #f39c12; font-size: 13px;">
                    <?php echo str_repeat('★', (int)$comm['rating']) . str_repeat('☆', 5 - (int)$comm['rating']); ?>
                    <span style="color: #5a7b8c; margin-left: 6px;"><?php echo $comm['created_at']; ?></span>
                  </span>
                </div> -->

                <p style="font-family: 'Rajdhani', sans-serif; color: #d7f4fb; font-size: 14px; line-height: 1.4; margin: 0;"><?php echo nl2br(htmlspecialchars($comm['comment'])); ?></p>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

    </div> <!-- Kết thúc .player-column -->
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