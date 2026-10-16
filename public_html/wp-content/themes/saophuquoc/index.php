<?php
/**
 * SAO PHU QUOC - Main Template (all pages)
 * Luxury beach design consistent with homepage
 */
get_header();

// Define page hero settings
$slug = get_post_field('post_name', get_the_ID());
$hero_config = [
    'bai-sao' => [
        'title' => 'Sao Beach<br>Phú Quốc',
        'subtitle' => 'Biển trắng mịn, nước xanh trong, trải nghiệm trọn ngày',
        'desc' => 'Điểm đến trung tâm của SAO PHU QUOC — nơi du khách tắm biển, nghỉ ghế, đặt cabana, chụp ảnh, ăn uống, chơi hoạt động biển và bắt đầu hành trình cao cấp hơn.',
        'img' => 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=1200',
        'cta' => ['Đặt vé Bãi Sao' => '/ve-combo', 'Xem cabana' => '/bai-sao']
    ],
    'ganh-ngon' => [
        'title' => 'Gánh Ngon<br>Nhà Hàng Đặc Sản',
        'subtitle' => 'Hương vị Việt Nam tại 113 Trần Hưng Đạo, Phú Quốc',
        'desc' => 'Điểm dừng lý tưởng cho bữa ăn gia đình, nhóm bạn, đoàn khách và khách quốc tế — trước hoặc sau trải nghiệm Bãi Sao và Paghera Yacht.',
        'img' => 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=1200',
        'cta' => ['Đặt bàn ngay' => '/dat-cho', 'Xem thực đơn' => '/ganh-ngon']
    ],
    'paghera-yacht' => [
        'title' => 'Paghera Yacht<br>Du Thuyền Cao Cấp',
        'subtitle' => 'Du ngoạn, lặn đảo và tận hưởng Phú Quốc trên du thuyền sang trọng',
        'desc' => 'Dành cho khách muốn nâng cấp kỳ nghỉ bằng tour lặn đảo, du ngoạn biển, sunset yacht, tiệc riêng, sinh nhật hoặc hành trình VIP.',
        'img' => 'https://images.unsplash.com/photo-1569263979104-865ab7cd8d13?w=1200',
        'cta' => ['Nhận tư vấn' => '/dat-cho', 'Xem tour' => '/paghera-yacht']
    ],
    've-combo' => [
        'title' => 'Vé & Combo<br>SAO PHU QUOC',
        'subtitle' => 'Chọn combo phù hợp với chuyến đi của bạn',
        'desc' => 'Từ một ngày biển tại Bãi Sao, bữa ăn Việt Nam tại Gánh Ngon đến hành trình du thuyền Paghera Yacht — tất cả trong một hệ sinh thái.',
        'img' => 'https://images.unsplash.com/photo-1537996194471-e657df975ab4?w=1200',
        'cta' => ['Xem combo' => '/ve-combo', 'Đặt ngay' => '/dat-cho']
    ],
    'dat-cho' => [
        'title' => 'Đặt Chỗ<br>SAO PHU QUOC',
        'subtitle' => 'Chọn trải nghiệm, đặt trước, đến là tận hưởng',
        'desc' => 'Đặt vé Bãi Sao, đặt bàn Gánh Ngon, nhận tư vấn Paghera Yacht hoặc thiết kế hành trình riêng.',
        'img' => 'https://images.unsplash.com/photo-1488646953014-85cb44e25828?w=1200',
        'cta' => ['Đặt Bãi Sao' => '/dat-cho', 'Đặt Gánh Ngon' => '/dat-cho']
    ],
    'su-kien' => [
        'title' => 'Sự Kiện<br>& Team Building',
        'subtitle' => 'Tổ chức sự kiện doanh nghiệp và tiệc riêng cao cấp',
        'desc' => 'Kết hợp bãi biển Bãi Sao, ẩm thực Gánh Ngon và du thuyền Paghera Yacht cho chương trình doanh nghiệp, gala dinner và sự kiện riêng.',
        'img' => 'https://images.unsplash.com/photo-1511578314322-379afb476865?w=1200',
        'cta' => ['Nhận proposal' => '/lien-he', 'Gửi yêu cầu' => '/lien-he']
    ],
    'di-chuyen' => [
        'title' => 'Di Chuyển<br>Đến SAO PHU QUOC',
        'subtitle' => 'Hướng dẫn di chuyển dễ dàng đến Bãi Sao, Gánh Ngon và Paghera Yacht',
        'desc' => 'Từ sân bay, Dương Đông, khách sạn — chọn tuyến đường và phương tiện phù hợp.',
        'img' => 'https://images.unsplash.com/photo-1469854523086-cc02fe5d8800?w=1200',
        'cta' => ['Đặt shuttle' => '/dat-cho', 'Xem bản đồ' => '/di-chuyen']
    ],
    'cam-nang' => [
        'title' => 'Cẩm Nang<br>SAO PHU QUOC',
        'subtitle' => 'Tất tần tật kinh nghiệm du lịch Bãi Sao, Gánh Ngon và Paghera Yacht',
        'desc' => 'Khám phá cẩm nang du lịch, mẹo hay, lịch trình gợi ý và những điều cần biết khi đến Phú Quốc.',
        'img' => 'https://images.unsplash.com/photo-1501785888041-af3ef285b470?w=1200',
        'cta' => ['Đọc cẩm nang' => '/cam-nang', 'Đặt trải nghiệm' => '/ve-combo']
    ],
    'hinh-anh-review' => [
        'title' => 'Hình Ảnh<br>& Review',
        'subtitle' => 'SAO PHU QUOC qua ống kính du khách',
        'desc' => 'Khám phá những khoảnh khắc đẹp nhất tại Bãi Sao, Gánh Ngon và Paghera Yacht.',
        'img' => 'https://images.unsplash.com/photo-1476514525535-07fb3b4ae5f1?w=1200',
        'cta' => ['Xem gallery' => '/hinh-anh-review', 'Gửi ảnh' => '/lien-he']
    ],
    'lien-he' => [
        'title' => 'Liên Hệ<br>SAO PHU QUOC',
        'subtitle' => 'Chúng tôi sẵn sàng hỗ trợ bạn',
        'desc' => 'Liên hệ đặt chỗ, tư vấn tour, sự kiện hoặc bất kỳ câu hỏi nào về trải nghiệm SAO PHU QUOC.',
        'img' => 'https://images.unsplash.com/photo-1526772662000-3f88f10405ff?w=1200',
        'cta' => ['Gọi ngay' => 'tel:090xxxxxxx', 'Nhắn Zalo' => 'https://zalo.me/090xxxxxxx']
    ],
];

$hero = $hero_config[$slug] ?? [
    'title' => get_the_title(),
    'subtitle' => '',
    'desc' => '',
    'img' => 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=1200',
    'cta' => []
];
?>

<!-- Page Hero -->
<section class="hero" style="background:linear-gradient(135deg,rgba(27,42,74,.9),rgba(10,126,140,.85)),url('<?=$hero['img']?>') center/cover no-repeat;min-height:50vh">
<div class="hero-content">
<span class="hero-label">✦ SAO PHU QUOC ✦</span>
<h1><?=$hero['title']?></h1>
<?php if($hero['subtitle']):?><p class="hero-subtitle"><?=$hero['subtitle']?></p><?php endif;?>
<?php if($hero['desc']):?><p class="hero-desc"><?=$hero['desc']?></p><?php endif;?>
<?php if($hero['cta']):?>
<div class="hero-ctas">
<?php foreach($hero['cta'] as $text => $url):?>
<a href="<?=$url?>" class="btn btn-gold btn-lg"><?=$text?></a>
<?php endforeach;?>
</div>
<?php endif;?>
</div>
</section>

<!-- Page Content -->
<section class="section section-light"><div class="container">
<div class="entry-content" style="max-width:900px;margin:0 auto">
<?php while(have_posts()): the_post(); the_content(); endwhile; ?>
</div>
</div></section>

<!-- Gallery Grid for hinh-anh-review page -->
<?php if($slug === 'hinh-anh-review'):?>
<section class="section"><div class="container">
<h2 class="section-title">Gallery Bãi Sao</h2>
<div class="gallery-grid">
<?php 
$unsplash = [
    '1507525428034-b723cf961d3e','1414235077428-338989a2e8c0','1537996194471-e657df975ab4',
    '1469854523086-cc02fe5d8800','1501785888041-af3ef285b470','1476514525535-07fb3b4ae5f1',
    '1488646953014-85cb44e25828','1511578314322-379afb476865','1569263979104-865ab7cd8d13',
    '1526772662000-3f88f10405ff','1506197602752-ee48e4e9b13b','1540202404-1b927e27fa8b',
];
foreach($unsplash as $img):?>
<div class="gallery-item"><img src="https://images.unsplash.com/photo-<?=$img?>?w=400" alt="SAO PHU QUOC" loading="lazy"></div>
<?php endforeach;?>
</div></div></section>
<?php endif;?>

<!-- CTA Section -->
<section class="section cta-section">
<h2>Sẵn sàng khám phá SAO PHU QUOC?</h2>
<p>Đặt trải nghiệm ngay hôm nay — Beach. Taste. Yacht.</p>
<div class="cta-buttons">
<a href="/dat-cho" class="btn btn-gold btn-lg">Đặt chỗ ngay</a>
<a href="/ve-combo" class="btn btn-outline-white btn-lg">Xem combo</a>
</div>
</section>

<?php get_footer(); ?>
