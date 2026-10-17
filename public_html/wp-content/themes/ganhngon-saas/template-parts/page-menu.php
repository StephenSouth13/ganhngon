<?php
if (!defined('ABSPATH')) exit;
$catalog = gns_menu_catalog();
$total = 0;
foreach ($catalog as $group) $total += count($group['dishes']);
$groups = count($catalog);
?>
<section class="page-hero page-hero-img menu-hero" aria-labelledby="menu-title">
  <img src="<?php echo esc_url(gns_photo('2026/09/ganhngon-bo-nuong-tang.webp')); ?>" alt="Thăn bò Úc nướng tảng" width="1600" height="914" loading="eager" decoding="async" class="page-hero-bg">
  <div class="hero-shade" aria-hidden="true"></div>
  <div class="wrap page-hero-inner">
    <p class="eyebrow eyebrow-light">Thực đơn Gánh Ngon</p>
    <h1 id="menu-title" class="display">Lửa than hồng,<br><em>nồi lẩu sầu riêng</em>.</h1>
    <p class="hero-lead">Hải sản đảo và bò Úc nướng tại quầy bếp mở, lẩu gà sầu riêng cho bàn đông, cơm niêu buổi trưa, món Âu cho các bé và quầy bar đến 2 giờ sáng. Giá báo tại nhà hàng.</p>
    <div class="hero-actions">
      <a class="btn btn-ember" href="#thuc-don">Xem thực đơn <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-arrow"></use></svg></a>
      <a class="btn btn-ghost-light" href="<?php echo esc_url(gns_url('dat-ban')); ?>"><?php echo esc_html(gns_t('btn_book', 'Đặt bàn')); ?></a>
    </div>
    <ul class="menu-hero-facts">
      <li><svg class="icon" aria-hidden="true" focusable="false"><use href="#i-flame"></use></svg> Nướng than hồng mỗi tối</li>
      <li><svg class="icon" aria-hidden="true" focusable="false"><use href="#i-clock"></use></svg> <?php echo esc_html(gns_hours()); ?></li>
      <li><svg class="icon" aria-hidden="true" focusable="false"><use href="#i-users"></use></svg> Phòng VIP</li>
    </ul>
  </div>
</section>

<section class="band band-ivory menu-explorer" id="thuc-don" aria-labelledby="explorer-title" data-menu-explorer>
  <div class="wrap">
    <div class="section-head section-head-center">
      <p class="eyebrow" data-menu-stats><?php echo esc_html($total . ' món · ' . $groups . ' nhóm'); ?></p>
      <h2 id="explorer-title" class="title">Thưởng thức trọn vị than hồng &amp; sầu riêng</h2>
      <p class="section-lead">Tìm theo tên món hoặc chọn một nhóm. Giá món, combo và phòng VIP nhà mình báo trực tiếp khi đặt bàn.</p>
    </div>

    <form class="menu-search" role="search" aria-label="Tìm món" data-menu-search>
      <label class="ms-field ms-q"><svg class="icon" aria-hidden="true" focusable="false"><use href="#i-search"></use></svg><span class="sr-only">Tên món</span><input type="search" name="q" placeholder="Tìm món: tôm, lẩu, cà phê…" autocomplete="off"></label>
      <label class="ms-field"><span class="sr-only">Nhóm món</span>
        <select name="cat">
          <option value="">Nhóm món</option>
          <?php foreach ($catalog as $group): ?>
            <option value="<?php echo esc_attr($group['id']); ?>"><?php echo esc_html($group['name']); ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="ms-field"><span class="sr-only">Đồ ăn hay đồ uống</span>
        <select name="kind">
          <option value="">Ăn &amp; uống</option>
          <option value="food">Đồ ăn</option>
          <option value="drink">Đồ uống</option>
        </select>
      </label>
      <button type="submit" class="btn btn-ember ms-submit">Tìm món</button>
    </form>

    <div class="cat-grid" data-cat-grid>
      <?php foreach ($catalog as $group): ?>
        <button type="button" class="cat-card" data-cat="<?php echo esc_attr($group['id']); ?>" data-kind="<?php echo esc_attr($group['kind']); ?>">
          <span class="cat-media">
            <?php if ($group['photo']): ?><img src="<?php echo esc_url(gns_photo($group['photo'])); ?>" alt="" width="1600" height="914" loading="lazy" decoding="async"><?php endif; ?>
            <span class="cat-count"><?php echo esc_html(count($group['dishes']) . ' món'); ?></span>
          </span>
          <span class="cat-body">
            <strong class="cat-name"><?php echo esc_html($group['name']); ?></strong>
            <span class="cat-kind"><?php echo $group['kind'] === 'drink' ? 'Đồ uống' : 'Đồ ăn'; ?></span>
            <?php if ($group['desc'] !== ''): ?><span class="cat-desc"><?php echo esc_html($group['desc']); ?></span><?php endif; ?>
          </span>
        </button>
      <?php endforeach; ?>
    </div>

    <div class="menu-results" id="danh-sach-mon" aria-live="polite">
      <div class="results-head">
        <h3 class="results-title" data-results-title>Tất cả món</h3>
        <p class="results-count" data-results-count><?php echo esc_html($total . ' món'); ?></p>
        <p class="results-note">Hải sản theo thời giá — giá báo khi đặt bàn.</p>
        <button type="button" class="results-clear" data-results-clear hidden>Xem tất cả</button>
      </div>
      <div class="dish-grid" data-dish-grid>
        <?php foreach ($catalog as $group): ?>
          <?php foreach ($group['dishes'] as $dish): ?>
            <article class="dish-card<?php echo $dish['photo'] ? ' has-img' : ' is-compact'; ?>" data-cat="<?php echo esc_attr($group['id']); ?>" data-kind="<?php echo esc_attr($group['kind']); ?>">
              <?php if ($dish['photo']): ?>
                <img class="dish-photo" src="<?php echo esc_url(gns_photo($dish['photo'])); ?>" alt="" loading="lazy" decoding="async">
              <?php endif; ?>
              <p class="dish-group"><?php echo esc_html($group['name']); ?></p>
              <h4 class="dish-name"><?php echo esc_html($dish['name']); ?></h4>
              <?php if ($dish['desc'] !== ''): ?><p class="dish-desc"><?php echo esc_html($dish['desc']); ?></p><?php endif; ?>
            </article>
          <?php endforeach; ?>
        <?php endforeach; ?>
      </div>
      <p class="results-empty" data-results-empty hidden>Chưa tìm thấy món phù hợp — thử từ khác, hoặc gọi <?php echo esc_html(gns_phone_label()); ?> để bếp tư vấn.</p>
    </div>
  </div>
</section>

<section class="bestseller" id="best-seller" aria-labelledby="bestseller-title">
  <div class="wrap bestseller-inner">
    <div class="bestseller-media">
      <img src="<?php echo esc_url(gns_photo('2026/09/ganhngon-lau-ga-sau-rieng.webp')); ?>" alt="Nồi lẩu gà sầu riêng" width="1600" height="914" loading="lazy" decoding="async">
      <span class="badge-best"><svg class="icon" aria-hidden="true" focusable="false"><use href="#i-flame"></use></svg> Best seller</span>
    </div>
    <div class="bestseller-copy">
      <p class="eyebrow eyebrow-gold">Món bán chạy nhất · Món mũi nhọn</p>
      <h2 id="bestseller-title" class="title title-light">Lẩu gà sầu riêng Phú Quốc</h2>
      <p>Sầu riêng chín cây đánh nhuyễn vào nước hầm gà ta — béo ngậy, thơm sâu, ngọt hậu. Món khách phương xa hay dè dặt lúc gọi, rồi gọi thêm nước dùng lúc gần tàn bữa.</p>
      <ul class="pot-chips">
        <li><strong>Gà ta</strong><span>thịt săn, da mỏng</span></li>
        <li><strong>Sầu riêng Phú Quốc</strong><span>chín cây, đánh nhuyễn</span></li>
        <li><strong>Nước hầm xương</strong><span>ngọt nền, hầm kỹ</span></li>
        <li><strong>Rau &amp; bún</strong><span>rau đảo theo mùa</span></li>
      </ul>
      <p class="bestseller-note"><svg class="icon" aria-hidden="true" focusable="false"><use href="#i-clock"></use></svg> Báo trước khoảng 1 tiếng để nồi nóng khi anh chị vừa ngồi xuống. Giá báo tại nhà hàng.</p>
      <div class="actions">
        <a class="btn btn-ember" href="<?php echo esc_url(gns_url('dat-ban')); ?>">Đặt bàn &amp; giữ nồi lẩu</a>
        <a class="link-light" href="<?php echo esc_url(gns_url('lau-ga-sau-rieng')); ?>">Câu chuyện nồi lẩu <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-arrow"></use></svg></a>
      </div>
    </div>
  </div>
</section>

<section class="band band-ember" aria-labelledby="menu-cta-title">
  <div class="wrap cta-band">
    <div>
      <h2 id="menu-cta-title" class="title title-light">Chọn xong món, giữ bàn thôi</h2>
      <p>Cho nhà hàng biết số khách và nhu cầu — bên mình gọi lại xác nhận trong giờ mở cửa. Đặt bàn tiệc, phòng VIP hay giữ nồi lẩu đều ở cùng một chỗ.</p>
    </div>
    <div class="actions">
      <a class="btn btn-ember" href="<?php echo esc_url(gns_url('dat-ban')); ?>">Đặt bàn ngay</a>
      <a class="btn btn-ghost-light" href="<?php echo esc_url(gns_phone_href()); ?>"><svg class="icon" aria-hidden="true" focusable="false"><use href="#i-phone"></use></svg> <?php echo esc_html(gns_phone_label()); ?></a>
    </div>
  </div>
</section>
