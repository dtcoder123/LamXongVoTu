<?php
session_start();

if (!empty($_SESSION['user_logged_in'])) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/includes/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $username === '' || $password === '') {
        $error = 'Vui lòng điền đầy đủ thông tin.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email không hợp lệ.';
    } elseif (strlen($username) < 3 || strlen($username) > 50) {
        $error = 'Tên đăng nhập phải từ 3 đến 50 ký tự.';
    } elseif (strlen($password) < 6) {
        $error = 'Mật khẩu phải có ít nhất 6 ký tự.';
    } else {
        $check = $pdo->prepare('SELECT id FROM users WHERE email = :email OR username = :username LIMIT 1');
        $check->execute([':email' => $email, ':username' => $username]);

        if ($check->fetch()) {
            $error = 'Email hoặc tên đăng nhập đã tồn tại.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $insert = $pdo->prepare('INSERT INTO users (email, username, password, role, status) VALUES (:email, :username, :password, :role, :status)');
            $insert->execute([
                ':email' => $email,
                ':username' => $username,
                ':password' => $hash,
                ':role' => 'member',
                ':status' => 1,
            ]);

            $success = 'Đăng ký thành công! Bạn có thể đăng nhập ngay.';
            $_SESSION['flash_message'] = $success;
            header('Location: login.php');
            exit;
        }
    }
}

$pageTitle = 'Đăng ký';
include 'includes/header.php';
?>

<main class="container" style="padding: 80px 0;">
  <div class="hud-panel" style="max-width: 480px; margin: 0 auto; padding: 32px;">
    <div class="hud-corner hud-corner--tl"></div>
    <div class="hud-corner hud-corner--br"></div>

    <h1 style="font-size: 28px; margin-bottom: 24px; text-transform: uppercase;">Đăng ký</h1>

    <?php if ($error): ?>
      <div style="margin-bottom: 18px; padding: 12px 14px; background: rgba(255,77,0,0.12); border: 1px solid rgba(255,77,0,0.35); color: #ffd7c7; font-family: 'Rajdhani', sans-serif; letter-spacing: 0.04em;">
        <?php echo htmlspecialchars($error); ?>
      </div>
    <?php endif; ?>

    <form method="post" style="display: grid; gap: 18px;">
      <label style="display: grid; gap: 8px; font-family: 'Rajdhani', sans-serif; letter-spacing: 0.05em; color: #7fa8b8;">
        Email
        <input type="email" name="email" value="" style="background: rgba(0,240,255,0.05); border: 1px solid rgba(0,240,255,0.2); color: #d7f4fb; padding: 12px 14px; font-size: 16px;" required>
      </label>

      <label style="display: grid; gap: 8px; font-family: 'Rajdhani', sans-serif; letter-spacing: 0.05em; color: #7fa8b8;">
        Tên đăng nhập
        <input type="text" name="username" value="" style="background: rgba(0,240,255,0.05); border: 1px solid rgba(0,240,255,0.2); color: #d7f4fb; padding: 12px 14px; font-size: 16px;" required>
      </label>

      <label style="display: grid; gap: 8px; font-family: 'Rajdhani', sans-serif; letter-spacing: 0.05em; color: #7fa8b8;">
        Mật khẩu
        <input type="password" name="password" value="" style="background: rgba(0,240,255,0.05); border: 1px solid rgba(0,240,255,0.2); color: #d7f4fb; padding: 12px 14px; font-size: 16px;" required>
      </label>

      <button type="submit" class="btn-hud btn-hud--primary" style="justify-content: center; width: 100%;">
        <span class="btn-hud__icon">▶</span> TẠO TÀI KHOẢN
      </button>
    </form>
  </div>
</main>

<?php include 'includes/footer.php'; ?>
