<?php
// includes/footer.php
?>

<button class="ai-chat-launcher" id="aiChatLauncher" type="button" aria-label="Mở AI Chatbox">
  <span class="ai-chat-launcher__pulse"></span>
  <span class="ai-chat-launcher__icon">✦</span>
  <span class="ai-chat-launcher__label">AI CHAT</span>
</button>

<aside class="ai-chatbox" id="aiChatbox" aria-label="AI Chatbox" aria-hidden="true">
  <div class="ai-chatbox__header">
    <div>
      <span class="ai-chatbox__eyebrow">FILM.SYS // INTELLIGENCE</span>
      <h2>AI Chatbox</h2>
    </div>
    <button class="ai-chatbox__close" id="aiChatClose" type="button" aria-label="Đóng AI Chatbox">×</button>
  </div>
  <div class="ai-chatbox__messages" id="aiChatMessages" aria-live="polite">
    <div class="ai-chat-message ai-chat-message--assistant">
      <span class="ai-chat-message__avatar">✦</span>
      <p>Mình có thể tìm phim, tra thể loại, diễn viên, đạo diễn và thông tin đang có trong kho phim.</p>
    </div>
  </div>
  <div class="ai-chatbox__quick-actions">
    <button type="button" data-ai-prompt="Gợi ý cho tôi vài phim hay trong kho phim">Gợi ý phim</button>
    <button type="button" data-ai-prompt="Tìm phim theo thể loại hành động">Phim hành động</button>
    <button type="button" data-ai-prompt="Phim nào đang nổi bật trên website?">Phim nổi bật</button>
  </div>
  <form class="ai-chatbox__form" id="aiChatForm">
    <textarea id="aiChatInput" rows="1" maxlength="2000" placeholder="Hỏi về phim trong website..." aria-label="Nhập câu hỏi"></textarea>
    <button type="submit" aria-label="Gửi tin nhắn">➤</button>
  </form>
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

<script src="js/main.js?v=20260905-ai-chat"></script>
</body>

</html>