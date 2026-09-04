<?php
/**
 * index.php — Trang chủ STARK-SYS Cinema Network
 */

$pageTitle = 'Trang chủ';

require_once __DIR__ . '/includes/db.php';

$genreRows = $pdo->query('SELECT DISTINCT genre FROM movies WHERE status = 1 ORDER BY genre ASC')->fetchAll(PDO::FETCH_COLUMN);
$genres = ['Tất cả'];
foreach ($genreRows as $genre) {
    $genres[] = $genre;
}

$featured = $pdo->query('SELECT * FROM movies WHERE featured = 1 AND status = 1 ORDER BY id DESC LIMIT 1')->fetch(PDO::FETCH_ASSOC);
if (!$featured) {
    $featured = $pdo->query('SELECT * FROM movies WHERE status = 1 ORDER BY id ASC LIMIT 1')->fetch(PDO::FETCH_ASSOC);
}

$movies = $pdo->query('SELECT * FROM movies WHERE status = 1 ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
foreach ($movies as &$movie) {
    $movie['cast'] = normalizeMovieCast($movie['cast'] ?? []);
    $movie['hot'] = !empty($movie['featured']) ? true : false;
}
unset($movie);

$movieShelves = [];
foreach ($movies as $movie) {
  $shelfGenre = trim((string)($movie['genre'] ?? '')) ?: 'Khác';
  $movieShelves[$shelfGenre][] = $movie;
}

$movieCatalog = array_map(function ($movie) {
    return [
        'id' => (int)($movie['id'] ?? 0),
        'title' => (string)($movie['title'] ?? ''),
        'genre' => (string)($movie['genre'] ?? ''),
        'tagline' => (string)($movie['tagline'] ?? ''),
        'poster' => (string)($movie['poster'] ?? ''),
        'keywords' => strtolower(trim((string)($movie['title'] ?? '') . ' ' . (string)($movie['genre'] ?? '') . ' ' . (string)($movie['tagline'] ?? '') . ' ' . implode(' ', normalizeMovieCast($movie['cast'] ?? []))))
    ];
}, $movies);

include 'includes/header.php';
?>

<script>
window.movieCatalog = <?php echo json_encode($movieCatalog, JSON_UNESCAPED_UNICODE); ?>;
</script>

<main>

  <!-- ============ HERO / HOLOGRAM BANNER ============ -->
  <section class="hero" id="featured">
    <div class="hero__bg" style="background-image:url('<?php echo $featured['poster']; ?>')"></div>
    <div class="hero__scrim"></div>
    <div class="hero__grid-overlay" aria-hidden="true"></div>

    <div class="container hero__inner">
      <div class="hero__content">
        <p class="eyebrow"><span class="eyebrow__dot"></span> ĐANG PHÁT SÓNG TRỰC TIẾP TỪ TRUNG TÂM DỮ LIỆU</p>
        <h1 class="hero__title glitch" data-text="<?php echo htmlspecialchars($featured['title']); ?>">
          <?php echo htmlspecialchars($featured['title']); ?>
        </h1>
        <p class="hero__tagline"><?php echo htmlspecialchars($featured['tagline']); ?></p>

        <div class="hero__meta">
          <span class="meta-chip meta-chip--rating">★ <?php echo $featured['rating']; ?></span>
          <span class="meta-chip"><?php echo $featured['year']; ?></span>
          <span class="meta-chip"><?php echo $featured['duration']; ?></span>
          <span class="meta-chip"><?php echo $featured['genre']; ?></span>
        </div>

        <div class="hero__energy-bar">
          <span class="hero__energy-fill" style="width:<?php echo $featured['rating'] * 10; ?>%"></span>
        </div>

        <div class="hero__actions">
          <?php 
          // Kiểm tra trạng thái đăng nhập để đổi đường dẫn
          $watchLink = !empty($_SESSION['user_logged_in']) ? 'watch.php?id=' . $featured['id'] : 'login.php';
          if (empty($_SESSION['user_logged_in'])) {
              $_SESSION['flash_message'] = 'Bạn cần đăng nhập để xem phim.';
          }
          ?>
          <a href="watch.php?id=<?php echo $featured['id']; ?>" class="btn-hud btn-hud--primary">
            <span class="btn-hud__icon">▶</span> XEM NGAY
          </a>
          <a href="watch.php?id=<?php echo $featured['id']; ?>#movieInfo" class="btn-hud btn-hud--ghost btn-hud--wide">
            <span class="btn-hud__icon">ⓘ</span> THÔNG TIN SYSTEM
          </a>
        </div>
      </div>

      <div class="hero__visual">
        <div class="hero__poster-wrap">
          <img src="<?php echo $featured['poster']; ?>" alt="<?php echo htmlspecialchars($featured['title']); ?>" class="hero__poster-img">
          <div class="hologram-flicker"></div>
          <div class="hologram-lines"></div>
        </div>

        <div class="hero__rail" aria-label="Phim đề xuất">
          <?php foreach (array_slice($movies, 0, 5) as $mini): ?>
            <button type="button"
               class="hero__mini-card"
               title="<?php echo htmlspecialchars($mini['title']); ?>"
               data-id="<?php echo (int)$mini['id']; ?>"
               data-title="<?php echo htmlspecialchars($mini['title'], ENT_QUOTES); ?>"
               data-tagline="<?php echo htmlspecialchars($mini['tagline'], ENT_QUOTES); ?>"
               data-genre="<?php echo htmlspecialchars($mini['genre'], ENT_QUOTES); ?>"
               data-poster="<?php echo htmlspecialchars($mini['poster'], ENT_QUOTES); ?>"
               data-year="<?php echo htmlspecialchars($mini['year'], ENT_QUOTES); ?>"
               data-duration="<?php echo htmlspecialchars($mini['duration'], ENT_QUOTES); ?>"
               data-rating="<?php echo htmlspecialchars($mini['rating'], ENT_QUOTES); ?>">
              <img src="<?php echo $mini['poster']; ?>" alt="<?php echo htmlspecialchars($mini['title']); ?>" loading="lazy">
            </button>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </section>

  <!-- ============ CATEGORY / FILTER TABS ============ -->
  <section class="filters container">
    <div class="filters__label">
      <span class="filters__label-dash"></span> BỘ LỌC DỮ LIỆU
    </div>
    <div class="filter-tabs" id="filterTabs">
      <?php foreach ($genres as $i => $g): ?>
        <button class="filter-tab <?php echo $i === 0 ? 'is-active' : ''; ?>" data-genre="<?php echo htmlspecialchars($g); ?>">
          <?php echo htmlspecialchars($g); ?>
        </button>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- ============ MOVIE GRID ============ -->
  <section class="movie-grid-section container" id="grid">
    <div class="section-heading">
      <h2><span class="section-heading__index">02</span> KHO DỮ LIỆU PHIM</h2>
      <span class="section-heading__count"><?php echo count($movies); ?> ENTRIES FOUND</span>
    </div>

    <div class="movie-shelves" id="movieGrid">
      <?php foreach ($movieShelves as $shelfGenre => $shelfMovies): ?>
        <section class="movie-shelf">
          <div class="movie-shelf__intro">
            <h3 class="movie-shelf__title"><?php echo htmlspecialchars($shelfGenre); ?></h3>
            <a href="#grid" class="movie-shelf__link">Xem toàn bộ <span aria-hidden="true">›</span></a>
          </div>

          <div class="movie-shelf__track">
            <?php foreach ($shelfMovies as $m): ?>
              <a href="watch.php?id=<?php echo $m['id']; ?>" class="movie-card" data-id="<?php echo (int)$m['id']; ?>" data-title="<?php echo htmlspecialchars($m['title']); ?>" data-genre="<?php echo htmlspecialchars($m['genre']); ?>" data-tagline="<?php echo htmlspecialchars($m['tagline']); ?>" data-keywords="<?php echo htmlspecialchars(strtolower($m['title'] . ' ' . $m['genre'] . ' ' . $m['tagline'] . ' ' . implode(' ', $m['cast']))); ?>">
                <div class="movie-card__frame">
                  <div class="hud-corner hud-corner--tl"></div>
                  <div class="hud-corner hud-corner--br"></div>

                  <?php if (!empty($m['hot'])): ?>
                    <span class="movie-card__badge">HOT</span>
                  <?php endif; ?>

                  <img src="<?php echo $m['poster']; ?>" alt="<?php echo htmlspecialchars($m['title']); ?>" class="movie-card__img" loading="lazy">

                  <div class="movie-card__overlay">
                    <span class="movie-card__play">▶</span>
                  </div>
                </div>

                <div class="movie-card__body">
                  <h3 class="movie-card__title"><?php echo htmlspecialchars($m['title']); ?></h3>
                  <div class="movie-card__meta">
                    <span><?php echo htmlspecialchars($m['tagline']); ?></span>
                  </div>
                </div>
              </a>
            <?php endforeach; ?>
          </div>
          <button class="movie-shelf__next" type="button" aria-label="Xem thêm phim">›</button>
        </section>
      <?php endforeach; ?>
    </div>

    
  </section>
  <div class="container" style="text-align: center; margin-top: 90px; margin-bottom: 50px; clear: both; width: 100%;">
    <button class="btn-hud btn-hud--ghost btn-hud--wide" id="loadMoreBtn" style="display: inline-block; margin: 0 auto;">
      TẢI THÊM DỮ LIỆU ⌄
    </button>
  </div>

</main>

<?php include 'includes/footer.php'; ?>
