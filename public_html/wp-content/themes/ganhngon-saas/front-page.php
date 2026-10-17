<?php
if (!defined('ABSPATH')) exit;
get_header();
$menu = gns_url('thuc-don');
$book = gns_url('dat-ban');
$events = gns_url('khach-doan-tiec-rieng');
$news = gns_url('tin-tuc');
$lau = gns_url('lau-ga-sau-rieng');
?>
<section class="hero" aria-labelledby="hero-title">
  <img src="<?php echo esc_url(gns_photo('2026/09/ganhngon-lau-ga-sau-rieng.webp')); ?>" alt="<?php echo esc_attr(gns_t('hero_h1', 'Nồi lẩu gà sầu riêng')); ?>" width="1600" height="914" loading="eager" decoding="async" class="hero-img">
  <div class="hero-shade" aria-hidden="true"></div>
  <div class="wrap hero-inner">
    <p class="eyebrow eyebrow-light"><?php echo esc_html(gns_t('hero_eyebrow', 'Nướng than hồng · Lẩu gà sầu riêng · Phú Quốc')); ?></p>
    <h1 id="hero-title" class="display"><?php echo function_exists('gn_cur_lang') && gn_cur_lang() !== 'vi' ? esc_html(gn_t('hero_h1')) : 'Lửa than hồng,<br><em>vị sầu riêng</em> của đảo.'; ?></h1>
    <p class="hero-lead"><?php echo esc_html(gns_t('hero_lead', 'Nhà hàng Việt giữa trung tâm Dương Đông. Hải sản và bò Úc nướng tại quầy bếp mở, lẩu gà sầu riêng cho bàn đông người, cơm niêu buổi trưa và quầy bar ngoài trời đến 2 giờ sáng.')); ?></p>
    <div class="hero-actions">
      <a class="btn btn-ember" href="<?php echo esc_url($book); ?>"><?php echo esc_html(gns_t('hero_cta1', 'Đặt bàn hôm nay')); ?></a>
      <a class="btn btn-ghost-light" href="<?php echo esc_url($menu); ?>"><?php echo esc_html(gns_t('menu_viewall', 'Xem thực đơn')); ?></a>
    </div>
    <dl class="hero-facts">
      <div><dt><svg class="icon" aria-hidden="true" focusable="false"><use href="#i-pin"></use></svg> Địa chỉ</dt><dd><?php echo esc_html(function_exists('gn_t') ? gn_t('arrival_addr') : '113 Trần Hưng Đạo, Dương Đông'); ?></dd></div>
      <div><dt><svg class="icon" aria-hidden="true" focusable="false"><use href="#i-clock"></use></svg> Mở cửa</dt><dd><?php echo esc_html(gns_hours()); ?></dd></div>
      <div><dt><svg class="icon" aria-hidden="true" focusable="false"><use href="#i-phone"></use></svg> Gọi · Zalo</dt><dd><a href="<?php echo esc_url(gns_phone_href()); ?>"><?php echo esc_html(gns_phone_label()); ?></a></dd></div>
    </dl>
  </div>
</section>

<section class="band band-ivory" aria-labelledby="kitchens-title">
  <div class="wrap">
    <div class="section-head">
      <p class="eyebrow">Sáu căn bếp, một mái nhà</p>
      <h2 id="kitchens-title" class="title">Mỗi bữa ở Gánh Ngon<br>là một kiểu ăn riêng</h2>
      <p class="section-lead">Từ than hồng đến nồi lẩu, từ cơm niêu trưa đến ly cocktail lúc nửa đêm — chọn kiểu ăn hợp với bàn của anh chị.</p>
    </div>
    <div class="kitchens">
      <a class="kitchen-card kitchen-wide" href="<?php echo esc_url($menu . '?nhom=hai-san-nuong-than-hong#thuc-don'); ?>">
        <img src="<?php echo esc_url(gns_photo('2026/09/ganhngon-bo-nuong-tang.webp')); ?>" alt="Thăn bò Úc nướng tảng" width="1600" height="914" loading="lazy" decoding="async">
        <span class="kitchen-body"><span class="kicker">01 · Than hồng</span><span class="kitchen-name">Hải sản &amp; bò nướng than hồng</span><span class="kitchen-lead">Quầy bếp mở, than hồng cháy suốt buổi tối. Hải sản đảo và bò Úc nướng ngay trước mắt khách.</span></span>
      </a>
      <a class="kitchen-card" href="<?php echo esc_url($menu . '#best-seller'); ?>">
        <img src="<?php echo esc_url(gns_photo('2026/09/ganhngon-lau-ga-sau-rieng.webp')); ?>" alt="Nồi lẩu gà sầu riêng" width="1600" height="914" loading="lazy" decoding="async">
        <span class="kitchen-body"><span class="kicker">02 · Món đặc trưng</span><span class="kitchen-name">Lẩu gà sầu riêng Phú Quốc</span><span class="kitchen-lead">Sầu riêng chín cây đánh nhuyễn vào nước hầm gà ta — béo ngậy, thơm sâu, ngọt hậu.</span></span>
      </a>
      <a class="kitchen-card" href="<?php echo esc_url($menu . '?nhom=com-nieu-trua#thuc-don'); ?>">
        <img src="<?php echo esc_url(gns_photo('2026/09/ganhngon-com-nieu-dap.webp')); ?>" alt="Cơm niêu" width="1600" height="914" loading="lazy" decoding="async">
        <span class="kitchen-body"><span class="kicker">03 · Bữa trưa</span><span class="kitchen-name">Cơm niêu trưa</span><span class="kitchen-lead">Cơm niêu nóng, món mặn theo ngày, canh và rau — bữa trưa kiểu nhà giữa phố Dương Đông.</span></span>
      </a>
      <a class="kitchen-card" href="<?php echo esc_url($menu . '?nhom=banh-xeo#thuc-don'); ?>">
        <img src="<?php echo esc_url(gns_photo('2026/09/ganhngon-banh-xeo-nam-bo.webp')); ?>" alt="Bánh xèo" width="1600" height="914" loading="lazy" decoding="async">
        <span class="kitchen-body"><span class="kicker">04 · Món Việt</span><span class="kitchen-name">Bánh xèo, gỏi cuốn &amp; salad</span><span class="kitchen-lead">Những món Việt quen thuộc cho bàn đông người và khách lần đầu đến Việt Nam.</span></span>
      </a>
      <a class="kitchen-card" href="<?php echo esc_url($menu . '?nhom=mon-au#thuc-don'); ?>">
        <img src="<?php echo esc_url(gns_photo('2026/09/ganhngon-tom-sot-hoang-kim.webp')); ?>" alt="Tôm sốt hoàng kim" width="1600" height="914" loading="lazy" decoding="async">
        <span class="kitchen-body"><span class="kicker">05 · Món Âu</span><span class="kitchen-name">Pizza, burger &amp; mỳ Ý</span><span class="kitchen-lead">Cho trẻ nhỏ và khách quốc tế muốn một bữa quen vị bên cạnh món Việt.</span></span>
      </a>
      <a class="kitchen-card kitchen-full" href="<?php echo esc_url(home_url('/thuc-don/do-uong/')); ?>">
        <img src="<?php echo esc_url(gns_photo('2026/10/ganh-ngon-cocktail-xanh-quay-bar.jpg')); ?>" alt="Cocktail tại quầy bar" width="1600" height="1600" loading="lazy" decoding="async">
        <span class="kitchen-body"><span class="kicker">06 · Quầy bar ngoài trời</span><span class="kitchen-name">Cocktail, trà signature &amp; cà phê</span><span class="kitchen-lead">Quầy bar ngoài trời mở đến 2 giờ sáng: cocktail sau bữa nướng, bia ướp lạnh, trà trái cây buổi chiều.</span></span>
      </a>
    </div>
  </div>
</section>

<section class="band band-ember signature" aria-labelledby="signature-title">
  <div class="wrap split">
    <div class="split-media"><img src="<?php echo esc_url(gns_photo('2026/09/ganhngon-lau-ga-sau-rieng.webp')); ?>" alt="Lẩu gà sầu riêng với rau và bún tươi" width="1600" height="914" loading="lazy" decoding="async"></div>
    <div class="split-copy">
      <p class="eyebrow eyebrow-gold"><?php echo esc_html(gns_t('sig_kicker', 'Món mũi nhọn')); ?></p>
      <h2 id="signature-title" class="title title-light"><?php echo esc_html(function_exists('gn_cur_lang') && gn_cur_lang() !== 'vi' ? gn_t('hero_h1') : 'Sầu riêng không nằm ở phần tráng miệng.'); ?></h2>
      <p><?php echo esc_html(gns_t('sig_body', 'Nó nằm trong nồi nước dùng, tan vào nước gà, để lại vị béo ngậy, thơm sâu và ngọt hậu mà không gia vị nào thay được. Người sợ sầu riêng thường ngạc nhiên nhất ở điểm này.')); ?></p>
      <ul class="ticks ticks-light">
        <li><svg class="icon" aria-hidden="true" focusable="false"><use href="#i-check"></use></svg> Gà ta thịt săn, da mỏng, không bở khi nhúng lâu</li>
        <li><svg class="icon" aria-hidden="true" focusable="false"><use href="#i-check"></use></svg> Sầu riêng Phú Quốc chín cây, đánh nhuyễn — không thả nguyên múi</li>
        <li><svg class="icon" aria-hidden="true" focusable="false"><use href="#i-check"></use></svg> <?php echo esc_html(gns_t('sig_prep', 'Báo trước khoảng 1 tiếng để nồi nóng lúc ngồi xuống')); ?></li>
      </ul>
      <div class="actions">
        <a class="btn btn-ember" href="<?php echo esc_url($book); ?>">Đặt bàn &amp; giữ nồi lẩu</a>
        <a class="link-light" href="<?php echo esc_url($lau); ?>">Câu chuyện nồi lẩu <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-arrow"></use></svg></a>
      </div>
    </div>
  </div>
</section>

<section class="band band-ivory" aria-labelledby="spaces-title">
  <div class="wrap">
    <div class="section-head">
      <p class="eyebrow">Không gian</p>
      <h2 id="spaces-title" class="title">Đẹp mà không xa cách</h2>
    </div>
    <div class="spaces">
      <figure class="space space-tall"><img src="<?php echo esc_url(gns_photo('2026/09/ganhngon-khong-gian-quan.webp')); ?>" alt="Sảnh chính Gánh Ngon" width="1600" height="1066" loading="lazy" decoding="async"><figcaption><strong>Sảnh chính</strong><span>Rộng, sáng đèn vàng — cho gia đình, nhóm bạn và đoàn khách.</span></figcaption></figure>
      <figure class="space"><img src="<?php echo esc_url(gns_photo('2026/10/ganh-ngon-phong-vip-ban-tiec-dai.jpg')); ?>" alt="Bàn tiệc trong phòng VIP" width="1600" height="1067" loading="lazy" decoding="async"><figcaption><strong>Phòng VIP</strong><span>Phòng riêng cho tiệc, màn hình chiếu lời chúc và bàn dài.</span></figcaption></figure>
      <figure class="space"><img src="<?php echo esc_url(gns_photo('2026/10/ganh-ngon-khong-gian-quay-bar-ngoai-troi.jpg')); ?>" alt="Quầy bar ngoài trời" width="1600" height="1067" loading="lazy" decoding="async"><figcaption><strong>Quầy bar ngoài trời</strong><span>Cocktail, bia và trà signature, mở đến 2 giờ sáng.</span></figcaption></figure>
    </div>
  </div>
</section>

<section class="band band-night" aria-labelledby="ent-title">
  <div class="wrap split split-rev">
    <div class="split-media media-stack">
      <img src="<?php echo esc_url(gns_photo('2026/10/ganh-ngon-phong-vip-trang-tri-sinh-nhat.jpg')); ?>" alt="Phòng VIP trang trí tiệc" width="1600" height="1067" loading="lazy" decoding="async" class="stack-a">
      <img src="<?php echo esc_url(gns_photo('2026/10/ganh-ngon-tiec-phong-vip-dj-poster.jpg')); ?>" alt="Tiệc phòng VIP có DJ" width="1600" height="1067" loading="lazy" decoding="async" class="stack-b">
    </div>
    <div class="split-copy">
      <p class="eyebrow eyebrow-gold">Âm thanh · Ca nhạc · Giải trí</p>
      <h2 id="ent-title" class="title title-light">Bữa tiệc có nhạc,<br>có người dẫn, có kỷ niệm.</h2>
      <p>Ca nhạc live, DJ, MC song ngữ, âm thanh ánh sáng, màn hình chiếu lời chúc và chụp ảnh — nhà mình lo trọn để anh chị chỉ việc nâng ly.</p>
      <ul class="chips">
        <li><svg class="icon" aria-hidden="true" focusable="false"><use href="#i-music"></use></svg> Ca nhạc &amp; ban nhạc</li>
        <li><svg class="icon" aria-hidden="true" focusable="false"><use href="#i-disc"></use></svg> DJ</li>
        <li><svg class="icon" aria-hidden="true" focusable="false"><use href="#i-mic"></use></svg> MC chuyên nghiệp</li>
        <li><svg class="icon" aria-hidden="true" focusable="false"><use href="#i-speaker"></use></svg> Âm thanh &amp; ánh sáng</li>
        <li><svg class="icon" aria-hidden="true" focusable="false"><use href="#i-screen"></use></svg> Màn hình &amp; backdrop</li>
        <li><svg class="icon" aria-hidden="true" focusable="false"><use href="#i-camera"></use></svg> Chụp ảnh &amp; quay phim</li>
      </ul>
      <div class="actions">
        <a class="btn btn-gold" href="<?php echo esc_url($events); ?>">Tiệc &amp; sự kiện</a>
        <a class="link-light" href="<?php echo esc_url(home_url('/khach-doan-tiec-rieng/tiec-sinh-nhat/')); ?>">Tiệc sinh nhật <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-arrow"></use></svg></a>
      </div>
    </div>
  </div>
</section>

<section class="band band-ivory" aria-labelledby="who-title">
  <div class="wrap">
    <div class="section-head">
      <p class="eyebrow">Ai hay ghé nhà mình</p>
      <h2 id="who-title" class="title">Một nhà hàng, nhiều kiểu bữa</h2>
    </div>
    <div class="who">
      <article><svg class="icon" aria-hidden="true" focusable="false"><use href="#i-users"></use></svg><h3>Gia đình &amp; nhóm bạn</h3><p>Bàn thường nhận trong ngày, kể cả khi vừa đến Phú Quốc. Có ghế cao cho trẻ em.</p></article>
      <article><svg class="icon" aria-hidden="true" focusable="false"><use href="#i-bus"></use></svg><h3>Đoàn du lịch</h3><p>Set định lượng chuẩn, phục vụ 60–90 phút, hoá đơn VAT 8%, đậu xe đoàn trước nhà hàng.</p></article>
      <article><svg class="icon" aria-hidden="true" focusable="false"><use href="#i-receipt"></use></svg><h3>Doanh nghiệp</h3><p>Tiếp khách trong phòng VIP riêng tư, tiệc công ty 20–60 khách, year-end và gala.</p></article>
      <article><svg class="icon" aria-hidden="true" focusable="false"><use href="#i-globe"></use></svg><h3>Khách quốc tế</h3><p>Đặt bàn qua WhatsApp, WeChat, Telegram; gợi ý món nướng dễ ăn cho khách nước ngoài.</p></article>
    </div>
  </div>
</section>

<section class="band band-sand" aria-labelledby="visit-title">
  <div class="wrap visit">
    <div class="visit-copy">
      <p class="eyebrow">Ghé Gánh Ngon</p>
      <h2 id="visit-title" class="title">113 Trần Hưng Đạo,<br>Dương Đông</h2>
      <p><?php echo esc_html(gns_t('loc_intro', 'Trung tâm Dương Đông — từ sân bay hay resort, mở bản đồ là tới.')); ?></p>
      <ul class="facts">
        <li><svg class="icon" aria-hidden="true" focusable="false"><use href="#i-clock"></use></svg><span><strong><?php echo esc_html(gns_hours()); ?></strong>Bàn thường: đến và ngồi trong ngày.</span></li>
        <li><svg class="icon" aria-hidden="true" focusable="false"><use href="#i-flame"></use></svg><span><strong>Nồi lẩu</strong><?php echo esc_html(gns_t('sig_prep', 'Báo trước khoảng 1 tiếng.')); ?></span></li>
        <li><svg class="icon" aria-hidden="true" focusable="false"><use href="#i-users"></use></svg><span><strong>Phòng VIP</strong>Đặt trước khoảng 24 giờ. Đoàn từ 20 khách: 48–72 giờ.</span></li>
      </ul>
      <div class="actions">
        <a class="btn btn-ember" href="<?php echo esc_url($book); ?>"><?php echo esc_html(gns_t('btn_book', 'Đặt bàn')); ?></a>
        <a class="btn btn-outline" href="<?php echo esc_url(gns_maps()); ?>" rel="noopener" target="_blank"><svg class="icon" aria-hidden="true" focusable="false"><use href="#i-pin"></use></svg> Chỉ đường</a>
      </div>
    </div>
    <div class="visit-map">
      <iframe title="Bản đồ đến Gánh Ngon, 113 Trần Hưng Đạo, Dương Đông, Phú Quốc" src="https://www.google.com/maps?q=113+Tr%E1%BA%A7n+H%C6%B0ng+%C4%90%E1%BA%A1o,+D%C6%B0%C6%A1ng+%C4%90%C3%B4ng,+Ph%C3%BA+Qu%E1%BB%91c&amp;output=embed" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
    </div>
  </div>
</section>

<section class="band band-sand" aria-labelledby="news-teaser-title">
  <div class="wrap">
    <div class="section-head section-head-row">
      <div>
        <p class="eyebrow">Tin tức</p>
        <h2 id="news-teaser-title" class="title">Chuyện bếp, chuyện tiệc</h2>
      </div>
      <a class="link" href="<?php echo esc_url($news); ?>">Xem tất cả <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-arrow"></use></svg></a>
    </div>
    <div class="news-grid news-grid-3">
      <article class="news-card">
        <a class="news-media" href="<?php echo esc_url($lau); ?>" tabindex="-1" aria-hidden="true"><img src="<?php echo esc_url(gns_photo('2026/09/ganhngon-lau-ga-sau-rieng.webp')); ?>" alt="" width="1600" height="914" loading="lazy" decoding="async"></a>
        <div class="news-body">
          <h3 class="news-title"><a href="<?php echo esc_url($lau); ?>">Lẩu gà sầu riêng Phú Quốc — vì sao nên báo trước một tiếng</a></h3>
          <p class="news-excerpt">Sầu riêng không nằm ở phần tráng miệng. Nó nằm trong nồi nước dùng — và cần khoảng một tiếng để nồi nóng khi anh chị ngồi xuống.</p>
          <a class="link news-more" href="<?php echo esc_url($lau); ?>">Đọc tiếp <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-arrow"></use></svg></a>
        </div>
      </article>
      <article class="news-card">
        <a class="news-media" href="<?php echo esc_url(home_url('/khach-doan-tiec-rieng/tiec-sinh-nhat/')); ?>" tabindex="-1" aria-hidden="true"><img src="<?php echo esc_url(gns_photo('2026/10/ganh-ngon-phong-vip-trang-tri-sinh-nhat.jpg')); ?>" alt="" width="1600" height="914" loading="lazy" decoding="async"></a>
        <div class="news-body">
          <h3 class="news-title"><a href="<?php echo esc_url(home_url('/khach-doan-tiec-rieng/tiec-sinh-nhat/')); ?>">Tiệc sinh nhật trong phòng VIP</a></h3>
          <p class="news-excerpt">Trang trí, màn hình chiếu lời chúc và phòng riêng — xem gói tiệc sinh nhật đang có trên website.</p>
          <a class="link news-more" href="<?php echo esc_url(home_url('/khach-doan-tiec-rieng/tiec-sinh-nhat/')); ?>">Đọc tiếp <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-arrow"></use></svg></a>
        </div>
      </article>
      <article class="news-card">
        <a class="news-media" href="<?php echo esc_url($menu . '?nhom=hai-san-nuong-than-hong#thuc-don'); ?>" tabindex="-1" aria-hidden="true"><img src="<?php echo esc_url(gns_photo('2026/09/ganhngon-bo-nuong-tang.webp')); ?>" alt="" width="1600" height="914" loading="lazy" decoding="async"></a>
        <div class="news-body">
          <h3 class="news-title"><a href="<?php echo esc_url($menu . '?nhom=hai-san-nuong-than-hong#thuc-don'); ?>">Than hồng và hải sản đảo</a></h3>
          <p class="news-excerpt">Tôm, mực, hàu, ghẹ Hàm Ninh, bò Úc nướng tảng. Giá báo khi đặt bàn.</p>
          <a class="link news-more" href="<?php echo esc_url($menu); ?>">Xem thực đơn <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-arrow"></use></svg></a>
        </div>
      </article>
    </div>
  </div>
</section>

<section class="band band-ivory" aria-labelledby="faq-title">
  <div class="wrap faq">
    <div class="section-head">
      <p class="eyebrow">Hỏi nhanh</p>
      <h2 id="faq-title" class="title">Câu hỏi thường gặp</h2>
      <p class="section-lead">Còn câu khác? Gọi hoặc Zalo <a href="<?php echo esc_url(gns_phone_href()); ?>"><?php echo esc_html(gns_phone_label()); ?></a>.</p>
    </div>
    <div class="faq-list">
      <details><summary>Có cần đặt bàn trước không?</summary><p>Bàn thường nhận trong ngày. Lẩu gà sầu riêng nên báo trước khoảng 1 tiếng, phòng VIP khoảng 24 giờ, đoàn từ 20 khách 48–72 giờ. Gọi hoặc nhắn Zalo <?php echo esc_html(gns_phone_label()); ?>.</p></details>
      <details><summary>Website có niêm yết giá không?</summary><p><?php echo esc_html(gns_t('sig_price', 'Giá món, combo và phòng VIP được báo trực tiếp cho từng bàn vì mỗi bữa mỗi khác. Website không niêm yết giá.')); ?></p></details>
      <details><summary>Nhà hàng có phòng VIP cho tiệc sinh nhật không?</summary><p>Có phòng VIP riêng, màn hình chiếu lời chúc và trang trí tiệc. Xem chi tiết ở trang tiệc sinh nhật hoặc gọi <?php echo esc_html(gns_phone_label()); ?>.</p></details>
      <details><summary>Có xuất hoá đơn VAT cho công ty không?</summary><p>Có hoá đơn VAT 8% cho công ty du lịch và doanh nghiệp, hỗ trợ thanh toán chuyển khoản.</p></details>
      <details><summary>Khách nước ngoài liên hệ thế nào?</summary><p>WhatsApp <?php echo esc_html(gns_phone_label()); ?>, WeChat ID ganhngon hoặc Telegram. Nhân viên gợi ý món nướng dễ ăn cho khách quốc tế.</p></details>
    </div>
  </div>
</section>
<?php
get_footer();
