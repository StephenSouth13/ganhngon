<?php if (!defined('ABSPATH')) exit; ?>
<section class="gn-section">
  <div class="gn-container">
    <h2>Không gian riêng tư<br>cho tiếp khách, gia đình &amp; tiệc sang trọng</h2>
    <p class="section-intro">Gánh Ngon sở hữu 10 phòng VIP với sức chứa và phong cách khác nhau — phù hợp cho gia đình, nhóm bạn, doanh nghiệp, khách VIP, đối tác, sinh nhật, họp mặt và các bữa tiệc cần sự riêng tư. <strong>Phí phòng VIP không hiển thị công khai</strong>, vui lòng nhận tư vấn riêng.</p>

    <div class="gn-vip-grid">
      <?php
      $rooms = [
        ['Phòng Sen', '4–8 khách', 'Cho gia đình nhỏ, bữa tối ấm cúng', 'https://ganhngon.com/wp-content/uploads/2026/09/ganhngon-phong-sen-768x439.webp', ['Bàn tròn 8 khách','Cửa sổ view phố','Trang trí gốm Bát Tràng']],
        ['Phòng Trúc', '6–10 khách', 'Cho nhóm bạn, gặp gỡ thân mật', 'https://ganhngon.com/wp-content/uploads/2026/09/ganhngon-phong-truc-768x439.webp', ['Bàn dài 10 chỗ','Cây trúc tự nhiên','Karaoke nhẹ']],
        ['Phòng Đào', '8–12 khách', 'Cho gia đình lớn, tụ họp 3 thế hệ', 'https://ganhngon.com/wp-content/uploads/2026/09/ganhngon-phong-dao-768x439.webp', ['Bàn tròn xoay 12 chỗ','Phòng riêng có cửa','Trang trí hoa đào']],
        ['Phòng Mai', '10–14 khách', 'Cho tiệc sinh nhật, kỷ niệm', 'https://ganhngon.com/wp-content/uploads/2026/09/ganhngon-phong-mai-768x439.webp', ['Bàn dài 14 chỗ','Loa karaoke','Bảng chúc mừng LED']],
        ['Phòng Cúc', '12–16 khách', 'Cho họp mặt công ty', 'https://ganhngon.com/wp-content/uploads/2026/09/ganhngon-phong-cuc-768x439.webp', ['Bàn dài 16 chỗ','Màn chiếu','TV smart 65"']],
        ['Phòng Lan', '14–18 khách', 'Cho team building nhỏ, doanh nghiệp', 'https://ganhngon.com/wp-content/uploads/2026/09/ganhngon-phong-lan-768x439.webp', ['Bàn ghép linh hoạt','Mic không dây','Phòng cách âm']],
        ['Phòng Hồng', '16–20 khách', 'Cho tiệc công ty trung bình', 'https://ganhngon.com/wp-content/uploads/2026/09/ganhngon-phong-hong-768x439.webp', ['Bàn dài 20 chỗ','Backdrop tiệc','Âm thanh chuyên nghiệp']],
        ['Phòng Nguyệt', '18–24 khách', 'Cho gala dinner nhỏ', 'https://ganhngon.com/wp-content/uploads/2026/09/ganhngon-phong-nguyet-768x439.webp', ['Sảnh riêng','Backdrop LED','MC + DJ tùy chọn']],
        ['Phòng Phượng', '20–28 khách', 'Cho tiệc cưới nhỏ, sự kiện công ty', 'https://ganhngon.com/wp-content/uploads/2026/09/ganhngon-phong-phuong-768x439.webp', ['Sảnh sang trọng','Sân khấu nhỏ','Trang trí theo theme']],
        ['Phòng Signature', '24–40 khách', 'Cho VIP, đối tác, sự kiện cao cấp', 'https://ganhngon.com/wp-content/uploads/2026/09/ganhngon-phong-signature-768x439.webp', ['Phòng lớn nhất','Bàn dài kiểu Âu','Concierge riêng']],
      ];
      foreach ($rooms as $r):
      ?>
      <div class="gn-vip-card">
        <img src="<?php echo esc_url($r[3]); ?>" alt="<?php echo esc_attr($r[0]); ?> — Gánh Ngon Phú Quốc" width="768" height="439" loading="lazy" decoding="async">
        <div class="body">
          <h3><?php echo esc_html($r[0]); ?></h3>
          <div class="vip-meta">Sức chứa: <?php echo esc_html($r[1]); ?></div>
          <p class="desc"><?php echo esc_html($r[2]); ?></p>
          <ul>
            <?php foreach ($r[4] as $a) echo '<li>' . esc_html($a) . '</li>'; ?>
          </ul>
          <a class="gn-cta-btn" href="<?php echo esc_url(home_url('/dat-ban/?room=' . urlencode($r[0]))); ?>" style="font-size:.82rem;padding:9px 18px">Đặt phòng này</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <div style="text-align:center;margin-top:40px">
      <a class="gn-cta-btn" href="<?php echo esc_url(home_url('/phong-vip/')); ?>">Xem chi tiết 10 phòng VIP</a>
    </div>
  </div>
</section>
