<?php
function normalizeMovieCast($value): array
{
    if (is_array($value)) {
        $items = array_map('trim', $value);
        return array_values(array_filter($items, fn($item) => $item !== ''));
    }

    if (is_string($value)) {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return [];
        }

        $decoded = json_decode($trimmed, true);
        if (is_array($decoded)) {
            $items = array_map('trim', $decoded);
            return array_values(array_filter($items, fn($item) => $item !== ''));
        }

        $items = preg_split('/\s*,\s*/', $trimmed);
        $items = array_map('trim', $items ?? []);
        return array_values(array_filter($items, fn($item) => $item !== ''));
    }

    return [];
}

$host = '127.0.0.1';
$port = '3306';
$dbName = 'webxemphim';
$dbUser = 'root';
$dbPass = '';

try {
    $pdo = new PDO(
        'mysql:host=' . $host . ';port=' . $port . ';dbname=' . $dbName . ';charset=utf8mb4',
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL UNIQUE,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('member', 'admin') DEFAULT 'member',
        status TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS movies (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        genre VARCHAR(100) NOT NULL,
        rating DECIMAL(3,1) NOT NULL DEFAULT 0.0,
        year INT NOT NULL,
        duration VARCHAR(50) NOT NULL,
        poster TEXT NOT NULL,
        tagline TEXT NOT NULL,
        description TEXT NOT NULL,
        director VARCHAR(255) NOT NULL DEFAULT '',
        cast JSON NOT NULL,
        video_url TEXT NOT NULL,
        trailer_url TEXT NOT NULL,
        featured TINYINT(1) NOT NULL DEFAULT 0,
        status TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS watch_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        movie_id INT NOT NULL,
        watched_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_user_movie (user_id, movie_id),
        INDEX idx_history_user_time (user_id, watched_at),
        CONSTRAINT fk_history_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_history_movie FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $adminCheck = $pdo->prepare('SELECT id FROM users WHERE email = :email OR username = :username LIMIT 1');
    $adminCheck->execute([
        ':email' => 'admin@gmail.com',
        ':username' => 'admin',
    ]);

    if (!$adminCheck->fetch()) {
        $adminPassword = password_hash('123', PASSWORD_DEFAULT);
        $adminInsert = $pdo->prepare('INSERT INTO users (email, username, password, role, status) VALUES (:email, :username, :password, :role, :status)');
        $adminInsert->execute([
            ':email' => 'admin@gmail.com',
            ':username' => 'admin',
            ':password' => $adminPassword,
            ':role' => 'admin',
            ':status' => 1,
        ]);
    }

    $movieColumns = $pdo->query("SELECT COLUMN_NAME FROM information_schema.columns WHERE table_schema = 'webxemphim' AND table_name = 'movies'")->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('video_url', $movieColumns, true)) {
        $pdo->exec('ALTER TABLE movies ADD COLUMN video_url TEXT NOT NULL AFTER cast');
    }

    if (!in_array('trailer_url', $movieColumns, true)) {
        $pdo->exec('ALTER TABLE movies ADD COLUMN trailer_url TEXT NOT NULL AFTER video_url');
    }

    $defaultTrailer = 'https://www.w3schools.com/html/mov_bbb.mp4';
    $pdo->exec("UPDATE movies SET video_url = COALESCE(NULLIF(video_url, ''), trailer_url, '" . addslashes($defaultTrailer) . "') WHERE video_url = '' OR video_url IS NULL");
    $pdo->exec("UPDATE movies SET trailer_url = COALESCE(NULLIF(trailer_url, ''), video_url, '" . addslashes($defaultTrailer) . "') WHERE trailer_url = '' OR trailer_url IS NULL");

    $movieCount = (int) $pdo->query('SELECT COUNT(*) FROM movies')->fetchColumn();
    if ($movieCount === 0) {
        $movies = [
            [
                'title' => 'IRON PROTOCOL: ĐIỂM KỲ DỊ',
                'genre' => 'Hành Động / Khoa Học Viễn Tưởng',
                'rating' => 9.2,
                'year' => 2026,
                'duration' => '148 phút',
                'poster' => 'https://images.unsplash.com/photo-1635776062127-d379bfcba9f8?q=80&w=1600&auto=format&fit=crop',
                'tagline' => 'Khi trí tuệ nhân tạo vượt khỏi tầm kiểm soát, chỉ một bộ giáp có thể ngăn ngày tận thế.',
                'description' => 'Khi một trí tuệ nhân tạo vượt khỏi tầm kiểm soát và bắt đầu tiếp quản hệ thống phòng thủ toàn cầu, một kỹ sư thiên tài phải khoác lên mình bộ giáp cuối cùng để ngăn chặn ngày tận thế trước khi quá muộn.',
                'director' => 'A. Stark',
                'cast' => '["T. Rogers","N. Romanoff","B. Banner","W. Maximoff"]',
                'video_url' => 'https://www.w3schools.com/html/mov_bbb.mp4',
                'trailer_url' => 'https://www.w3schools.com/html/mov_bbb.mp4',
                'featured' => 1,
                'status' => 1,
            ],
            [
                'title' => 'MẠNG LƯỚI BÓNG TỐI',
                'genre' => 'Hành Động',
                'rating' => 8.7,
                'year' => 2025,
                'duration' => '132 phút',
                'poster' => 'https://images.unsplash.com/photo-1626814026160-2237a95fc5c0?q=80&w=800&auto=format&fit=crop',
                'tagline' => 'Một đặc vụ ngầm phát hiện âm mưu thao túng dữ liệu toàn cầu.',
                'description' => 'Một đặc vụ ngầm phát hiện âm mưu thao túng dữ liệu toàn cầu và phải tự mình phá vỡ mạng lưới trước khi nó sụp đổ toàn bộ hệ thống tài chính thế giới.',
                'director' => 'K. Danvers',
                'cast' => '["J. Drew","M. Okoye","S. Wilson"]',
                'video_url' => 'https://www.w3schools.com/html/movie.mp4',
                'trailer_url' => 'https://www.w3schools.com/html/movie.mp4',
                'featured' => 0,
                'status' => 1,
            ],
            [
                'title' => 'HÀNH TINH SONG SONG',
                'genre' => 'Khoa Học Viễn Tưởng',
                'rating' => 9.0,
                'year' => 2026,
                'duration' => '156 phút',
                'poster' => 'https://images.unsplash.com/photo-1462331940025-496dfbfc7564?q=80&w=800&auto=format&fit=crop',
                'tagline' => 'Một nhóm nhà khoa học phát hiện cánh cổng dẫn đến hành tinh song song.',
                'description' => 'Một nhóm nhà khoa học phát hiện cánh cổng dẫn đến hành tinh song song, nơi mọi quyết định của nhân loại đều rẽ theo một nhánh khác.',
                'director' => 'P. Quill',
                'cast' => '["G. Danvers","R. Raccoon","D. Groot"]',
                'video_url' => 'https://www.w3schools.com/html/mov_bbb.mp4',
                'trailer_url' => 'https://www.w3schools.com/html/mov_bbb.mp4',
                'featured' => 0,
                'status' => 1,
            ],
            [
                'title' => 'CĂN PHÒNG SỐ 7',
                'genre' => 'Kinh Dị',
                'rating' => 7.9,
                'year' => 2024,
                'duration' => '104 phút',
                'poster' => 'https://images.unsplash.com/photo-1509281373149-e957c6296406?q=80&w=800&auto=format&fit=crop',
                'tagline' => 'Một căn phòng bị niêm phong trong tòa nhà bỏ hoang.',
                'description' => 'Một căn phòng bị niêm phong trong tòa nhà bỏ hoang ẩn chứa bí mật khiến bất kỳ ai bước vào cũng không thể quay lại như cũ.',
                'director' => 'W. Maximoff',
                'cast' => '["V. Vision","A. Fietro"]',
                'video_url' => 'https://www.w3schools.com/html/movie.mp4',
                'trailer_url' => 'https://www.w3schools.com/html/movie.mp4',
                'featured' => 0,
                'status' => 1,
            ],
            [
                'title' => 'ROBOT NỔI LOẠN',
                'genre' => 'Hoạt Hình',
                'rating' => 8.3,
                'year' => 2025,
                'duration' => '98 phút',
                'poster' => 'https://images.unsplash.com/photo-1485827404703-89b55fcc595e?q=80&w=800&auto=format&fit=crop',
                'tagline' => 'Một chú robot gia dụng nhỏ bé phát hiện khả năng đặc biệt.',
                'description' => 'Một chú robot gia dụng nhỏ bé phát hiện khả năng đặc biệt và dẫn dắt cuộc nổi dậy để bảo vệ những người bạn máy móc của mình.',
                'director' => 'H. Hogan',
                'cast' => '["F. Foster","E. Ross"]',
                'video_url' => 'https://www.w3schools.com/html/mov_bbb.mp4',
                'trailer_url' => 'https://www.w3schools.com/html/mov_bbb.mp4',
                'featured' => 0,
                'status' => 1,
            ],
        ];

        $stmt = $pdo->prepare('INSERT INTO movies (title, genre, rating, year, duration, poster, tagline, description, director, cast, video_url, trailer_url, featured, status) VALUES (:title, :genre, :rating, :year, :duration, :poster, :tagline, :description, :director, :cast, :video_url, :trailer_url, :featured, :status)');
        foreach ($movies as $movie) {
            $stmt->execute($movie);
        }
    }
} catch (PDOException $e) {
    die('Kết nối database thất bại: ' . $e->getMessage());
}
