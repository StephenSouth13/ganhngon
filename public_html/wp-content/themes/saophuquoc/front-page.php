<?php
/**
 * SAO PHU QUOC - Front Page (Homepage)
 */
get_header(); ?>

<section class="hero">
<div class="hero-content">
<span class="hero-label">✦ Phú Quốc, Việt Nam ✦</span>
<h1>SAO BEACH<br>PHÚ QUỐC</h1>
<p class="hero-subtitle">Biển đẹp. Món ngon. Du thuyền sang trọng.</p>
<p class="hero-desc">Hệ sinh thái du lịch trọn vẹn tại đảo ngọc Phú Quốc — nơi hội tụ biển xanh Bãi Sao, ẩm thực Việt tại Gánh Ngon 113 Trần Hưng Đạo, và du thuyền cao cấp Paghera Yacht.</p>
<div class="hero-ctas">
<a href="<?=home_url('/dat-cho/?brand=bai_sao')?>" class="btn btn-gold btn-lg">🏖️ Đặt Bãi Sao</a>
<a href="<?=home_url('/dat-cho/?brand=ganh_ngon')?>" class="btn btn-gold btn-lg">🍜 Đặt Gánh Ngon</a>
<a href="<?=home_url('/dat-cho/?brand=paghera_yacht')?>" class="btn btn-outline-white btn-lg">🛥️ Đặt Yacht</a>
<a href="<?=home_url('/ve-combo')?>" class="btn btn-outline-white btn-lg">🎫 Xem Combo</a>
</div></div></section>

<section class="section section-light"><div class="container">
<h2 class="section-title">Một hệ sinh thái, ba trải nghiệm</h2>
<p class="section-desc">Tắm biển tại Bãi Sao, thưởng thức đặc sản Việt Nam tại Gánh Ngon và nâng cấp kỳ nghỉ bằng chuyến du ngoạn sang trọng cùng Paghera Yacht.</p>
<div class="card-grid">
<div class="card"><div class="card-img" style="background-image:url(https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=600)"><span class="card-badge badge-beach">Bãi Sao</span></div><div class="card-body"><h3>🏖️ Sao Beach Phú Quốc</h3><p>Biển trắng mịn, nước xanh trong — điểm đến trung tâm cho một ngày biển trọn vẹn. Vé từ 150.000đ.</p><a href="<?=home_url('/dat-cho/?brand=bai_sao')?>" class="btn btn-ocean btn-sm">Đặt vé Bãi Sao →</a><a href="<?=home_url('/bai-sao')?>" class="btn btn-ocean btn-sm" style="margin-left:8px">Tìm hiểu thêm</a></div></div>
<div class="card"><div class="card-img" style="background-image:url(https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=600)"><span class="card-badge badge-food">Gánh Ngon</span></div><div class="card-body"><h3>🍜 Gánh Ngon Vietnamese Kitchen</h3><p>Nhà hàng đặc sản Việt Nam tại 113 Trần Hưng Đạo. Set menu từ 400.000đ/người.</p><a href="<?=home_url('/dat-cho/?brand=ganh_ngon')?>" class="btn btn-ocean btn-sm">Đặt bàn ngay →</a><a href="<?=home_url('/ganh-ngon')?>" class="btn btn-ocean btn-sm" style="margin-left:8px">Xem thực đơn</a></div></div>
<div class="card"><div class="card-img" style="background-image:url(https://images.unsplash.com/photo-1569263979104-865ab7cd8d13?w=600)"><span class="card-badge badge-yacht">Paghera Yacht</span></div><div class="card-body"><h3>🛥️ Paghera Yacht Phu Quoc</h3><p>Du ngoạn, lặn đảo và tận hưởng Phú Quốc trên du thuyền sang trọng.</p><a href="<?=home_url('/dat-cho/?brand=paghera_yacht')?>" class="btn btn-ocean btn-sm">Nhận tư vấn →</a><a href="<?=home_url('/paghera-yacht')?>" class="btn btn-ocean btn-sm" style="margin-left:8px">Khám phá</a></div></div>
</div></div></section>

<section class="section"><div class="container">
<h2 class="section-title">Combo nổi bật</h2>
<div class="combo-grid">
<div class="combo-card" onclick="location.href='<?=home_url('/dat-cho/?combo=sao_beach_day')?>'" style="cursor:pointer"><h4>⭐ Sao Beach Day</h4><span class="combo-price">Từ 150.000đ</span><p>Một ngày biển cơ bản tại Bãi Sao. Phù hợp khách tự túc, nhóm bạn.</p><span class="btn btn-gold btn-sm">Đặt ngay</span></div>
<div class="combo-card" onclick="location.href='<?=home_url('/dat-cho/?combo=family')?>'" style="cursor:pointer"><h4>👨‍👩‍👧‍👦 Family Beach Day</h4><span class="combo-price">Từ 500.000đ</span><p>Ngày biển an toàn, tiện lợi cho gia đình có trẻ em.</p><span class="btn btn-gold btn-sm">Đặt ngay</span></div>
<div class="combo-card" onclick="location.href='<?=home_url('/dat-cho/?combo=couple')?>'" style="cursor:pointer"><h4>💑 Couple Chill</h4><span class="combo-price">Từ 600.000đ</span><p>Riêng tư cho cặp đôi. Có thể nâng cấp sunset yacht.</p><span class="btn btn-gold btn-sm">Đặt ngay</span></div>
<div class="combo-card" onclick="location.href='<?=home_url('/dat-cho/?combo=beach_taste')?>'" style="cursor:pointer"><h4>🍽️ Beach & Taste</h4><span class="combo-price">Từ 800.000đ</span><p>Bãi Sao + bữa tối tại Gánh Ngon 113 Trần Hưng Đạo.</p><span class="btn btn-gold btn-sm">Đặt ngay</span></div>
<div class="combo-card" onclick="location.href='<?=home_url('/dat-cho/?combo=beach_yacht')?>'" style="cursor:pointer"><h4>🛥️ Beach & Yacht</h4><span class="combo-price">Liên hệ</span><p>Bãi Sao + Paghera Yacht. Dành cho khách cao cấp.</p><span class="btn btn-gold btn-sm">Tư vấn</span></div>
<div class="combo-card" onclick="location.href='<?=home_url('/dat-cho/?combo=signature')?>'" style="cursor:pointer"><h4>👑 Signature Journey</h4><span class="combo-price">Thiết kế riêng</span><p>Trọn vẹn cả 3 trải nghiệm trong một ngày.</p><span class="btn btn-gold btn-sm">Tư vấn</span></div>
<div class="combo-card" onclick="location.href='<?=home_url('/su-kien/')?>'" style="cursor:pointer"><h4>🏢 Corporate Experience</h4><span class="combo-price">Liên hệ</span><p>Team building + Gala + VIP Yacht cho doanh nghiệp.</p><span class="btn btn-gold btn-sm">Báo giá</span></div>
<div class="combo-card" onclick="location.href='<?=home_url('/su-kien/')?>'" style="cursor:pointer"><h4>💎 Private Luxury</h4><span class="combo-price">Thiết kế riêng</span><p>Sinh nhật, cầu hôn, tiệc riêng trên biển.</p><span class="btn btn-gold btn-sm">Gửi yêu cầu</span></div>
</div></div></section>

<section class="section cta-section">
<h2>Sẵn sàng khám phá SAO PHU QUOC?</h2>
<p>Đặt trải nghiệm ngay hôm nay — Bãi Sao, Gánh Ngon, Paghera Yacht.</p>
<div class="cta-buttons">
<a href="<?=home_url('/dat-cho/')?>" class="btn btn-gold btn-lg" style="font-size:1.2rem;padding:16px 48px">🎫 ĐẶT CHỖ NGAY</a>
</div>
</section>

<?php get_footer(); ?>
