<?php
session_start();

if (empty($_SESSION['user_logged_in'])) {
    $_SESSION['flash_message'] = 'Vui lòng đăng nhập để xem lịch sử phim.';
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/includes/db.php';

$historyStmt = $pdo->prepare('
    SELECT m.*, h.watched_at
    FROM watch_history h
    INNER JOIN movies m ON m.id = h.movie_id
    WHERE h.user_id = :user_id AND m.status = 1
    ORDER BY h.watched_at DESC
');
$historyStmt->execute([':user_id' => (int)$_SESSION['user_id']]);
$history = $historyStmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Lịch sử xem phim';
include 'includes/header.php';
?>

<main class="container history-page">
  <div class="section-heading">
    <h1><span class="section-heading__index">03</span> LỊCH SỬ XEM PHIM</h1>
    <span class="section-heading__count"><?php echo count($history); ?> ENTRIES FOUND</span>
  </div>

  <?php if (!$history): ?>
    <div class="history-empty hud-panel">
      <h2>Chưa có lịch sử xem</h2>
      <p>Các phim bạn đã mở sẽ được lưu tại đây.</p>
      <a href="index.php#grid" class="btn-hud btn-hud--primary">KHÁM PHÁ KHO PHIM</a>
    </div>
  <?php else: ?>
    <div class="history-grid">
      <?php foreach ($history as $movie): ?>
        <a href="watch.php?id=<?php echo (int)$movie['id']; ?>" class="movie-card">
          <div class="movie-card__frame">
            <img src="<?php echo htmlspecialchars($movie['poster']); ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>" class="movie-card__img" loading="lazy">
            <div class="movie-card__overlay"><span class="movie-card__play">▶</span></div>
          </div>
          <div class="movie-card__body">
            <h2 class="movie-card__title"><?php echo htmlspecialchars($movie['title']); ?></h2>
            <div class="movie-card__meta"><span><?php echo htmlspecialchars($movie['genre']); ?></span></div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</main>

<?php include 'includes/footer.php'; ?>
