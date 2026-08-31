<?php
session_start();
require_once __DIR__ . '/includes/db.php';

if (!empty($_SESSION['user_logged_in']) && ($_SESSION['user_role'] ?? 'member') === 'admin') {
    // Admin is allowed
} else {
    header('Location: login.php');
    exit;
}

$message = '';
$movieId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

function normalizeMovieVideoSource(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }

    if (preg_match('/drive\.google\.com\/file\/d\/([A-Za-z0-9_-]+)/i', $value, $matches)) {
        return 'https://drive.google.com/file/d/' . $matches[1] . '/preview';
    }

    if (preg_match('/drive\.google\.com\/uc\?[^\s]*id=([A-Za-z0-9_-]+)/i', $value, $matches)) {
        return 'https://drive.google.com/file/d/' . $matches[1] . '/preview';
    }

    if (preg_match('/^(https?:\/\/|\/|uploads\/)/i', $value) === 1) {
        return $value;
    }

    if (preg_match('/^[A-Za-z]:\\\\|^[A-Za-z]:\//', $value) === 1) {
        $sourcePath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $value);
        if (is_file($sourcePath)) {
            $uploadDir = __DIR__ . '/uploads/movies/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $extension = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));
            $fileName = 'movie_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
            $targetPath = $uploadDir . $fileName;

            if (copy($sourcePath, $targetPath)) {
                return 'uploads/movies/' . $fileName;
            }
        }
    }

    return $value;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'add';

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare('DELETE FROM movies WHERE id = :id');
            $stmt->execute([':id' => $id]);
            $message = 'Xóa phim thành công.';
        }
    } else {
        $title = trim($_POST['title'] ?? '');
        $genre = trim($_POST['genre'] ?? '');
        $rating = (float)($_POST['rating'] ?? 0);
        $year = (int)($_POST['year'] ?? date('Y'));
        $duration = trim($_POST['duration'] ?? '');
        $poster = trim($_POST['poster'] ?? '');
        $tagline = trim($_POST['tagline'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $director = trim($_POST['director'] ?? '');
        $videoUrl = normalizeMovieVideoSource((string)($_POST['video_url'] ?? $_POST['trailer_url'] ?? ''));
        $trailerUrl = normalizeMovieVideoSource((string)($_POST['trailer_url'] ?? $videoUrl));

        $castInput = $_POST['cast'] ?? '';
        $castArray = normalizeMovieCast($castInput);
        $cast = json_encode($castArray, JSON_UNESCAPED_UNICODE);

        $featured = !empty($_POST['featured']) ? 1 : 0;
        $status = !empty($_POST['status']) ? 1 : 0;

        if ($title !== '' && $genre !== '' && $duration !== '' && $poster !== '' && $description !== '') {
            if (!empty($_POST['id'])) {
                $stmt = $pdo->prepare('UPDATE movies SET title = :title, genre = :genre, rating = :rating, year = :year, duration = :duration, poster = :poster, tagline = :tagline, description = :description, director = :director, cast = :cast, video_url = :video_url, trailer_url = :trailer_url, featured = :featured, status = :status WHERE id = :id');
                $stmt->execute([
                    ':title' => $title,
                    ':genre' => $genre,
                    ':rating' => $rating,
                    ':year' => $year,
                    ':duration' => $duration,
                    ':poster' => $poster,
                    ':tagline' => $tagline,
                    ':description' => $description,
                    ':director' => $director,
                    ':cast' => $cast,
                    ':video_url' => $videoUrl !== '' ? $videoUrl : 'https://www.w3schools.com/html/mov_bbb.mp4',
                    ':trailer_url' => $trailerUrl !== '' ? $trailerUrl : ($videoUrl !== '' ? $videoUrl : 'https://www.w3schools.com/html/mov_bbb.mp4'),
                    ':featured' => $featured,
                    ':status' => $status,
                    ':id' => (int)$_POST['id'],
                ]);
                $message = 'Cập nhật phim thành công.';
            } else {
                $stmt = $pdo->prepare('INSERT INTO movies (title, genre, rating, year, duration, poster, tagline, description, director, cast, video_url, trailer_url, featured, status) VALUES (:title, :genre, :rating, :year, :duration, :poster, :tagline, :description, :director, :cast, :video_url, :trailer_url, :featured, :status)');
                $stmt->execute([
                    ':title' => $title,
                    ':genre' => $genre,
                    ':rating' => $rating,
                    ':year' => $year,
                    ':duration' => $duration,
                    ':poster' => $poster,
                    ':tagline' => $tagline,
                    ':description' => $description,
                    ':director' => $director,
                    ':cast' => $cast,
                    ':video_url' => $videoUrl !== '' ? $videoUrl : 'https://www.w3schools.com/html/mov_bbb.mp4',
                    ':trailer_url' => $trailerUrl !== '' ? $trailerUrl : ($videoUrl !== '' ? $videoUrl : 'https://www.w3schools.com/html/mov_bbb.mp4'),
                    ':featured' => $featured,
                    ':status' => $status,
                ]);
                $message = 'Thêm phim thành công.';
            }
        } else {
            $message = 'Vui lòng nhập đầy đủ thông tin bắt buộc.';
        }
    }
}

$editMovie = null;
if ($movieId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM movies WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $movieId]);
    $editMovie = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($editMovie) {
        $editMovie['cast'] = normalizeMovieCast($editMovie['cast'] ?? []);
    }
}

$movies = $pdo->query('SELECT * FROM movies ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
$pageTitle = 'Admin - Quản lý phim';
include 'includes/header.php';
?>

<main class="container" style="padding: 40px 0 80px;">
  <div class="hud-panel" style="padding: 24px; margin-bottom: 24px;">
    <div class="hud-corner hud-corner--tl"></div>
    <div class="hud-corner hud-corner--br"></div>
    <h1 style="font-size: 30px; margin-bottom: 10px; text-transform: uppercase;">Bảng điều khiển admin</h1>
    <p style="color: #7fa8b8; font-family: 'Rajdhani', sans-serif; letter-spacing: 0.06em;">Quản lý phim · cập nhật dữ liệu · xoá nội dung</p>
  </div>

  <?php if ($message): ?>
    <div class="hud-panel" style="padding: 16px 20px; margin-bottom: 24px; background: rgba(0,240,255,0.08); border-color: rgba(0,240,255,0.25);">
      <?php echo htmlspecialchars($message); ?>
    </div>
  <?php endif; ?>

  <div class="hud-panel" style="padding: 24px; margin-bottom: 30px;">
    <div class="hud-corner hud-corner--tl"></div>
    <div class="hud-corner hud-corner--br"></div>
    <h2 style="margin-bottom: 20px; text-transform: uppercase;">
      <?php echo $editMovie ? 'Sửa phim' : 'Thêm phim mới'; ?>
    </h2>

    <form method="post" style="display: grid; gap: 18px;">
      <?php if ($editMovie): ?>
        <input type="hidden" name="id" value="<?php echo (int)$editMovie['id']; ?>">
      <?php endif; ?>

      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
        <label style="display: grid; gap: 8px; color: #7fa8b8; font-family: 'Rajdhani', sans-serif;">
          Tiêu đề phim
          <input type="text" name="title" value="<?php echo htmlspecialchars($editMovie['title'] ?? ''); ?>" style="padding: 12px; background: rgba(0,240,255,0.05); border: 1px solid rgba(0,240,255,0.2); color: #d7f4fb;" required>
        </label>

        <label style="display: grid; gap: 8px; color: #7fa8b8; font-family: 'Rajdhani', sans-serif;">
          Thể loại
          <input type="text" name="genre" value="<?php echo htmlspecialchars($editMovie['genre'] ?? ''); ?>" style="padding: 12px; background: rgba(0,240,255,0.05); border: 1px solid rgba(0,240,255,0.2); color: #d7f4fb;" required>
        </label>

        <label style="display: grid; gap: 8px; color: #7fa8b8; font-family: 'Rajdhani', sans-serif;">
          Rating
          <input type="number" step="0.1" min="0" max="10" name="rating" value="<?php echo htmlspecialchars((string)($editMovie['rating'] ?? 0)); ?>" style="padding: 12px; background: rgba(0,240,255,0.05); border: 1px solid rgba(0,240,255,0.2); color: #d7f4fb;">
        </label>

        <label style="display: grid; gap: 8px; color: #7fa8b8; font-family: 'Rajdhani', sans-serif;">
          Năm phát hành
          <input type="number" name="year" value="<?php echo htmlspecialchars((string)($editMovie['year'] ?? date('Y'))); ?>" style="padding: 12px; background: rgba(0,240,255,0.05); border: 1px solid rgba(0,240,255,0.2); color: #d7f4fb;" required>
        </label>

        <label style="display: grid; gap: 8px; color: #7fa8b8; font-family: 'Rajdhani', sans-serif;">
          Thời lượng
          <input type="text" name="duration" value="<?php echo htmlspecialchars($editMovie['duration'] ?? ''); ?>" style="padding: 12px; background: rgba(0,240,255,0.05); border: 1px solid rgba(0,240,255,0.2); color: #d7f4fb;" required>
        </label>

        <label style="display: grid; gap: 8px; color: #7fa8b8; font-family: 'Rajdhani', sans-serif;">
          Poster URL
          <input type="text" name="poster" value="<?php echo htmlspecialchars($editMovie['poster'] ?? ''); ?>" style="padding: 12px; background: rgba(0,240,255,0.05); border: 1px solid rgba(0,240,255,0.2); color: #d7f4fb;" required>
        </label>
      </div>

      <label style="display: grid; gap: 8px; color: #7fa8b8; font-family: 'Rajdhani', sans-serif;">
        Tagline
        <input type="text" name="tagline" value="<?php echo htmlspecialchars($editMovie['tagline'] ?? ''); ?>" style="padding: 12px; background: rgba(0,240,255,0.05); border: 1px solid rgba(0,240,255,0.2); color: #d7f4fb;">
      </label>

      <label style="display: grid; gap: 8px; color: #7fa8b8; font-family: 'Rajdhani', sans-serif;">
        Mô tả
        <textarea name="description" rows="5" style="padding: 12px; background: rgba(0,240,255,0.05); border: 1px solid rgba(0,240,255,0.2); color: #d7f4fb;" required><?php echo htmlspecialchars($editMovie['description'] ?? ''); ?></textarea>
      </label>

      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
        <label style="display: grid; gap: 8px; color: #7fa8b8; font-family: 'Rajdhani', sans-serif;">
          Đạo diễn
          <input type="text" name="director" value="<?php echo htmlspecialchars($editMovie['director'] ?? ''); ?>" style="padding: 12px; background: rgba(0,240,255,0.05); border: 1px solid rgba(0,240,255,0.2); color: #d7f4fb;">
        </label>

        <label style="display: grid; gap: 8px; color: #7fa8b8; font-family: 'Rajdhani', sans-serif;">
          URL video chiếu phim (mp4 hoặc YouTube)
          <input type="url" name="video_url" value="<?php echo htmlspecialchars($editMovie['video_url'] ?? ($editMovie['trailer_url'] ?? 'https://www.w3schools.com/html/mov_bbb.mp4')); ?>" style="padding: 12px; background: rgba(0,240,255,0.05); border: 1px solid rgba(0,240,255,0.2); color: #d7f4fb;">
        </label>

        <label style="display: grid; gap: 8px; color: #7fa8b8; font-family: 'Rajdhani', sans-serif;">
          Diễn viên (cách nhau bằng dấu phẩy)
          <input type="text" name="cast" value="<?php echo htmlspecialchars(implode(', ', $editMovie['cast'] ?? [])); ?>" style="padding: 12px; background: rgba(0,240,255,0.05); border: 1px solid rgba(0,240,255,0.2); color: #d7f4fb;">
        </label>
      </div>

      <div style="display: flex; gap: 16px; flex-wrap: wrap; align-items: center;">
        <label style="display: flex; align-items: center; gap: 8px; color: #d7f4fb;">
          <input type="checkbox" name="featured" value="1" <?php echo !empty($editMovie['featured']) ? 'checked' : ''; ?>> Nổi bật
        </label>
        <label style="display: flex; align-items: center; gap: 8px; color: #d7f4fb;">
          <input type="checkbox" name="status" value="1" <?php echo !empty($editMovie['status']) ? 'checked' : ''; ?>> Hiển thị
        </label>
      </div>

      <div style="display: flex; gap: 12px; flex-wrap: wrap;">
        <button type="submit" class="btn-hud btn-hud--primary">
          <span class="btn-hud__icon">✓</span> <?php echo $editMovie ? 'LƯU THAY ĐỔI' : 'THÊM PHIM'; ?>
        </button>
        <?php if ($editMovie): ?>
          <a href="admin.php" class="btn-hud btn-hud--ghost">HỦY</a>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <div class="hud-panel" style="padding: 24px;">
    <div class="hud-corner hud-corner--tl"></div>
    <div class="hud-corner hud-corner--br"></div>
    <h2 style="margin-bottom: 20px; text-transform: uppercase;">Danh sách phim</h2>

    <div style="display: grid; gap: 14px;">
      <?php foreach ($movies as $movie): ?>
        <div style="display: grid; grid-template-columns: 90px 1fr auto; gap: 16px; align-items: center; background: rgba(0,240,255,0.04); border: 1px solid rgba(0,240,255,0.15); padding: 12px;">
          <img src="<?php echo htmlspecialchars($movie['poster']); ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>" style="width: 90px; height: 120px; object-fit: cover; border: 1px solid rgba(0,240,255,0.2);">
          <div>
            <div style="font-size: 18px; font-weight: 700; color: #d7f4fb; margin-bottom: 4px;"><?php echo htmlspecialchars($movie['title']); ?></div>
            <div style="color: #7fa8b8; font-family: 'Rajdhani', sans-serif; letter-spacing: 0.04em;">
              <?php echo htmlspecialchars($movie['genre']); ?> · <?php echo (int)$movie['year']; ?> · <?php echo htmlspecialchars($movie['duration']); ?>
            </div>
          </div>
          <div style="display: flex; gap: 8px; flex-wrap: wrap; justify-content: flex-end;">
            <a href="admin.php?id=<?php echo (int)$movie['id']; ?>" class="btn-hud btn-hud--ghost">Sửa</a>
            <form method="post" action="admin.php" onsubmit="return confirm('Xóa phim này?');" style="display: inline;">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?php echo (int)$movie['id']; ?>">
              <button type="submit" class="btn-hud btn-hud--ghost" style="border-color: rgba(255,77,0,0.3); color: #ffd7c7;">Xóa</button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</main>

<?php include 'includes/footer.php'; ?>
