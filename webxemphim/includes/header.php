<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isLoggedIn = !empty($_SESSION['user_logged_in']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo isset($pageTitle) ? $pageTitle . ' — FILM-SYS' : 'FILM-SYS // CINEMA NETWORK'; ?></title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500;700;900&family=Chakra+Petch:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Rajdhani:wght@400;500;600;700&display=swap" rel="stylesheet">

<link rel="stylesheet" href="css/style.css?v=20260831-restore-old">
<link rel="stylesheet" href="css/jarvis-hud.css?v=20260831-restore-old">
</head>
<body>
<!-- ============ JARVIS BOOT SEQUENCE ============ -->
<div class="boot-sequence" id="bootSequence" aria-hidden="true">
  <div class="boot-sequence__grid"></div>
  <div class="boot-sequence__ring">
    <span class="boot-sequence__ring-inner"></span>
    <span class="boot-sequence__core"></span>
  </div>
  <div class="boot-sequence__title">FILM<span>.SYS</span></div>
  <div class="boot-sequence__subtitle">J.A.R.V.I.S CORE INITIALIZATION</div>

  <div class="boot-sequence__log" id="bootLog"></div>

  <div class="boot-sequence__bar-wrap">
    <div class="boot-sequence__bar">
      <div class="boot-sequence__bar-fill" id="bootBarFill"></div>
    </div>
    <span class="boot-sequence__pct" id="bootPct">0%</span>
  </div>
</div>

<!-- SCANLINE OVERLAY -->
<div class="jarvis-scanlines" aria-hidden="true"></div>
<div class="jarvis-vignette" aria-hidden="true"></div>

<!-- BOOT STATUS STRIP -->
<div class="boot-strip">
  <span class="boot-strip__item">FILM.SYS // ONLINE</span>
  <span class="boot-strip__item boot-strip__item--sep">::</span>
  <span class="boot-strip__item">UPLINK STABLE</span>
  <span class="boot-strip__item boot-strip__item--sep">::</span>
  <span class="boot-strip__item" id="clockReadout">00:00:00</span>
</div>

<header class="site-header hud-panel" data-hud="true">
  <div class="hud-corner hud-corner--tl"></div>
  <div class="hud-corner hud-corner--tr"></div>
  <div class="hud-corner hud-corner--bl"></div>
  <div class="hud-corner hud-corner--br"></div>

  <div class="site-header__inner container">

    <a href="index.php" class="brand">
      <span class="brand__ring">
        <span class="brand__core"></span>
      </span>
      <span class="brand__text">
        FILM<span class="brand__text-accent">.SYS</span>
        <small>ONLINE CINEMA NETWORK</small>
      </span>
    </a>

    <nav class="main-nav" id="mainNav">
      <a href="index.php" class="main-nav__link is-active">TRANG CHỦ</a>
      <a href="index.php#featured" class="main-nav__link">PHIM ĐỀ CỬ</a>
      <a href="index.php#grid" class="main-nav__link">KHO DỮ LIỆU</a>
      <a href="index.php#" class="main-nav__link">THỂ LOẠI</a>
      <a href="index.php#" class="main-nav__link">LỊCH SỬ</a>
    </nav>

    <div class="header-actions">
      <form class="hud-search" action="#" method="get" autocomplete="off">
        <span class="hud-search__icon">◎</span>
        <input type="text" id="movieSearchInput" name="q" class="hud-search__input" placeholder="QUÉT DỮ LIỆU PHIM…">
        <span class="hud-search__scan"></span>
        <div id="movieSuggestions" class="movie-suggestions" aria-live="polite"></div>
      </form>

      <?php if ($isLoggedIn): ?>
        <div class="auth-actions">
          <?php if (($userRole = $_SESSION['user_role'] ?? 'member') === 'admin'): ?>
            <a href="admin.php" class="btn-hud btn-hud--primary auth-btn">
              ADMIN
            </a>
          <?php endif; ?>
          <span class="auth-user">Xin chào, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></span>
          <a href="logout.php" class="btn-hud btn-hud--ghost auth-btn">
            <span class="btn-hud__icon">⇠</span> ĐĂNG XUẤT
          </a>
        </div>
      <?php else: ?>
        <div class="auth-actions">
          <a href="login.php" class="btn-hud btn-hud--ghost auth-btn">
            ĐĂNG NHẬP
          </a>
          <a href="register.php" class="btn-hud btn-hud--primary auth-btn">
            ĐĂNG KÝ
          </a>
        </div>
      <?php endif; ?>

      <button class="btn-hud btn-hud--ghost" id="navToggle" aria-label="Mở menu">
        <span></span><span></span><span></span>
      </button>
    </div>

  </div>
</header>
