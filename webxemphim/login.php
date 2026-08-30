<?php
session_start();

if (!empty($_SESSION['user_logged_in'])) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/includes/db.php';

$error = '';
$flashMessage = $_SESSION['flash_message'] ?? '';
unset($_SESSION['flash_message']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu.';
    } else {
        $stmt = $pdo->prepare('SELECT id, username, password, role, status FROM users WHERE username = :username OR email = :email LIMIT 1');
        $stmt->execute([
            ':username' => $username,
            ':email' => $username,
        ]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password']) && (int)$user['status'] === 1) {
            $_SESSION['user_logged_in'] = true;
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['user_name'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];

            header('Location: index.php');
            exit;
        }

        $error = 'Tên đăng nhập hoặc mật khẩu không đúng.';
    }
}

$pageTitle = 'Đăng nhập';
include 'includes/header.php';
?>

<main class="container" style="padding: 80px 0;">
  <div class="hud-panel" style="max-width: 480px; margin: 0 auto; padding: 32px;">
    <div class="hud-corner hud-corner--tl"></div>
    <div class="hud-corner hud-corner--br"></div>

    <h1 style="font-size: 28px; margin-bottom: 24px; text-transform: uppercase;">Đăng nhập</h1>

    <?php if ($flashMessage): ?>
      <div style="margin-bottom: 18px; padding: 12px 14px; background: rgba(0,240,255,0.12); border: 1px solid rgba(0,240,255,0.35); color: #d7f4fb; font-family: 'Rajdhani', sans-serif; letter-spacing: 0.04em;">
        <?php echo htmlspecialchars($flashMessage); ?>
      </div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div style="margin-bottom: 18px; padding: 12px 14px; background: rgba(255,77,0,0.12); border: 1px solid rgba(255,77,0,0.35); color: #ffd7c7; font-family: 'Rajdhani', sans-serif; letter-spacing: 0.04em;">
        <?php echo htmlspecialchars($error); ?>
      </div>
    <?php endif; ?>

    <form method="post" style="display: grid; gap: 18px;">
      <label style="display: grid; gap: 8px; font-family: 'Rajdhani', sans-serif; letter-spacing: 0.05em; color: #7fa8b8;">
        Tên đăng nhập / Email
        <input type="text" name="username" value="" style="background: rgba(0,240,255,0.05); border: 1px solid rgba(0,240,255,0.2); color: #d7f4fb; padding: 12px 14px; font-size: 16px;" required>
      </label>

      <label style="display: grid; gap: 8px; font-family: 'Rajdhani', sans-serif; letter-spacing: 0.05em; color: #7fa8b8;">
        Mật khẩu
        <input type="password" name="password" value="" style="background: rgba(0,240,255,0.05); border: 1px solid rgba(0,240,255,0.2); color: #d7f4fb; padding: 12px 14px; font-size: 16px;" required>
      </label>

      <button type="submit" class="btn-hud btn-hud--primary" style="justify-content: center; width: 100%;">
        <span class="btn-hud__icon">▶</span> XÁC NHẬN
      </button>
    </form>
  </div>
</main>

<?php include 'includes/footer.php'; ?>
