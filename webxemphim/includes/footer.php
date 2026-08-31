<?php
// includes/footer.php
?>

<aside class="assistant-widget" aria-live="polite">
  <div class="assistant-widget__header">
    <div class="assistant-widget__status">
      <span class="assistant-widget__dot"></span>
      <span>AI Assistant</span>
    </div>
    <button class="assistant-widget__toggle" type="button" aria-label="Mở / đóng trợ lý">—</button>
  </div>

  <div class="assistant-widget__body">
    <div class="assistant-widget__messages">
      <div class="assistant-message assistant-message--bot">
        Xin chào! Tôi là trợ lý JARVIS của FILM.SYS. Tôi có thể gợi ý phim, trả lời câu hỏi và hỗ trợ bạn tìm nội dung trên website.
      </div>
    </div>

    <div class="assistant-widget__quick-actions">
      <button type="button" class="assistant-quick-btn">Gợi ý phim</button>
      <button type="button" class="assistant-quick-btn">Phim hành động</button>
      <button type="button" class="assistant-quick-btn">Đăng nhập</button>
    </div>

    <form class="assistant-widget__composer" autocomplete="off">
      <input type="text" id="assistantInput" placeholder="Nhập câu hỏi của bạn..." aria-label="Nhập câu hỏi cho trợ lý" />
      <button type="submit" aria-label="Gửi câu hỏi">➤</button>
    </form>
  </div>
</aside>

<footer class="site-footer hud-panel">
  <div class="sovereignty-badge">
    <span class="sovereignty-badge__star">★</span>
    <span class="sovereignty-badge__text">Hoàng Sa &amp; Trường Sa là của Việt Nam!</span>
  </div>
  <div class="hud-corner hud-corner--tl"></div>
  <div class="hud-corner hud-corner--tr"></div>

  <div class="container site-footer__inner">
    <div class="footer-col footer-col--brand">
      <div class="brand brand--footer">
        <span class="brand__ring brand__ring--sm"><span class="brand__core"></span></span>
        <span class="brand__text">FILM<span class="brand__text-accent">.SYS</span></span>
      </div>
      <p class="footer-tagline">FILM.SYS / – Trung tâm truyền phát điện ảnh thế hệ mới, vận hành trên nền tảng phân tích dữ liệu thời gian thực và đồng bộ cùng mạng lưới phim toàn cầu. Truy xuất kho lưu trữ đa dạng từ phim chiếu rạp, phim bộ đến các tác phẩm bom tấn quốc tế (Việt Nam, Hàn Quốc, Trung Quốc, Âu Mỹ...) với độ phân giải chuẩn 4K Ultra HD. Trải nghiệm ngay giao diện giải trí tương lai vượt trội!</p>
      <div class="footer-status">
        <span class="status-dot"></span> TẤT CẢ HỆ THỐNG HOẠT ĐỘNG BÌNH THƯỜNG
      </div>
    </div>

    <div class="footer-col">
      <h4 class="footer-heading">// ĐIỀU HƯỚNG</h4>
      <a href="index.php">Trang chủ</a>
      <a href="index.php#featured">Phim đề cử</a>
      <a href="index.php#grid">Kho dữ liệu phim</a>
      <a href="#">Thể loại</a>
    </div>

    <div class="footer-col">
      <h4 class="footer-heading">// HỖ TRỢ</h4>
      <a href="#">Trung tâm trợ giúp</a>
      <a href="#">Báo lỗi liên kết</a>
      <a href="#">Điều khoản dịch vụ</a>
      <a href="#">Quyền riêng tư</a>
    </div>

    <div class="footer-col">
      <h4 class="footer-heading">// TỌA ĐỘ HỆ THỐNG</h4>
      <p class="mono-line">LAT/LNG: 10.7769° N, 106.7009° E</p>
      <p class="mono-line">CORE TEMP: 36.6°C — NOMINAL</p>
      <p class="mono-line">BUILD: STARK-SYS v4.7.0-MK47</p>
    </div>
  </div>

  <div class="site-footer__bottom">
    <span>© <?php echo date('Y'); ?> STARK INDUSTRIES — CINEMA DIVISION. ALL RIGHTS RESERVED.</span>
    <span class="mono-line">POWERED BY ARC REACTOR</span>
  </div>
</footer>

<script src="js/main.js?v=20260830-hero-switcher-verify"></script>
</body>

</html>