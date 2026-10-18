<?php
/**
 * 1.3.2 public fixes that do not edit the database or the parent theme.
 * Hidden media patterns, legacy booking forms, share images, schema scrub,
 * news 404s past the last page, and theme UI strings.
 */
if (!defined('ABSPATH')) exit;

function gns_ui_lang() {
    if (function_exists('pll_current_language')) {
        $lang = pll_current_language('slug');
        if (is_string($lang) && $lang !== '') return $lang;
    }
    if (function_exists('gn_cur_lang')) {
        $lang = gn_cur_lang();
        if (is_string($lang) && $lang !== '') return $lang;
    }
    return 'vi';
}

function gns_ui_dict() {
    return [
        'nav_home' => ['vi' => 'Trang chủ', 'en' => 'Home', 'zh' => '首页', 'ko' => '홈', 'ru' => 'Главная', 'ar' => 'الرئيسية'],
        'nav_menu' => ['vi' => 'Thực đơn', 'en' => 'Menu', 'zh' => '菜单', 'ko' => '메뉴', 'ru' => 'Меню', 'ar' => 'القائمة'],
        'nav_groups' => ['vi' => 'Tiệc & Sự kiện', 'en' => 'Events', 'zh' => '宴会活动', 'ko' => '연회·행사', 'ru' => 'Банкеты', 'ar' => 'الحفلات'],
        'nav_news' => ['vi' => 'Tin tức', 'en' => 'News', 'zh' => '新闻', 'ko' => '소식', 'ru' => 'Новости', 'ar' => 'الأخبار'],
        'nav_about' => ['vi' => 'Về Gánh Ngon', 'en' => 'About', 'zh' => '关于我们', 'ko' => '소개', 'ru' => 'О нас', 'ar' => 'من نحن'],
        'nav_contact' => ['vi' => 'Liên hệ', 'en' => 'Contact', 'zh' => '联系', 'ko' => '연락처', 'ru' => 'Контакты', 'ar' => 'اتصل بنا'],
        'nav_book' => ['vi' => 'Đặt bàn', 'en' => 'Book a table', 'zh' => '预订', 'ko' => '예약', 'ru' => 'Забронировать', 'ar' => 'احجز'],
        'btn_book' => ['vi' => 'Đặt bàn', 'en' => 'Book a table', 'zh' => '预订', 'ko' => '예약하기', 'ru' => 'Забронировать', 'ar' => 'احجز'],
        'skip' => ['vi' => 'Bỏ qua đến nội dung', 'en' => 'Skip to content', 'zh' => '跳到内容', 'ko' => '본문으로', 'ru' => 'К содержанию', 'ar' => 'تخطَّ إلى المحتوى'],
        'nav_open' => ['vi' => 'Mở menu', 'en' => 'Open menu', 'zh' => '打开菜单', 'ko' => '메뉴 열기', 'ru' => 'Открыть меню', 'ar' => 'افتح القائمة'],
        'nav_close' => ['vi' => 'Đóng menu', 'en' => 'Close menu', 'zh' => '关闭菜单', 'ko' => '메뉴 닫기', 'ru' => 'Закрыть меню', 'ar' => 'أغلق القائمة'],
        'nav_primary' => ['vi' => 'Điều hướng chính', 'en' => 'Primary', 'zh' => '主导航', 'ko' => '주 메뉴', 'ru' => 'Основное меню', 'ar' => 'القائمة الرئيسية'],
        'nav_mobile' => ['vi' => 'Điều hướng trên điện thoại', 'en' => 'Mobile', 'zh' => '手机导航', 'ko' => '모바일 메뉴', 'ru' => 'Мобильное меню', 'ar' => 'قائمة الجوال'],
        'footer_explore' => ['vi' => 'Khám phá', 'en' => 'Explore', 'zh' => '浏览', 'ko' => '둘러보기', 'ru' => 'Разделы', 'ar' => 'استكشف'],
        'footer_contact' => ['vi' => 'Liên hệ', 'en' => 'Contact', 'zh' => '联系', 'ko' => '연락처', 'ru' => 'Контакты', 'ar' => 'اتصل بنا'],
        'footer_visit' => ['vi' => 'Ghé nhà hàng', 'en' => 'Visit', 'zh' => '到店', 'ko' => '방문', 'ru' => 'Как добраться', 'ar' => 'زورونا'],
        'action_call' => ['vi' => 'Gọi', 'en' => 'Call', 'zh' => '致电', 'ko' => '전화', 'ru' => 'Звонок', 'ar' => 'اتصل'],
        'action_map' => ['vi' => 'Chỉ đường', 'en' => 'Map', 'zh' => '地图', 'ko' => '길찾기', 'ru' => 'Маршрут', 'ar' => 'الخريطة'],
        'action_quick' => ['vi' => 'Liên hệ nhanh', 'en' => 'Quick contact', 'zh' => '快速联系', 'ko' => '빠른 연락', 'ru' => 'Быстрая связь', 'ar' => 'تواصل سريع'],
        'news_more' => ['vi' => 'Đọc tiếp', 'en' => 'Read', 'zh' => '阅读', 'ko' => '더 보기', 'ru' => 'Читать', 'ar' => 'اقرأ'],
        'news_all' => ['vi' => 'Tất cả', 'en' => 'All', 'zh' => '全部', 'ko' => '전체', 'ru' => 'Все', 'ar' => 'الكل'],
        'news_prev' => ['vi' => 'Trước', 'en' => 'Previous', 'zh' => '上一页', 'ko' => '이전', 'ru' => 'Назад', 'ar' => 'السابق'],
        'news_next' => ['vi' => 'Sau', 'en' => 'Next', 'zh' => '下一页', 'ko' => '다음', 'ru' => 'Далее', 'ar' => 'التالي'],
        'news_cats' => ['vi' => 'Chuyên mục', 'en' => 'Categories', 'zh' => '分类', 'ko' => '분류', 'ru' => 'Рубрики', 'ar' => 'التصنيفات'],
        'news_pages' => ['vi' => 'Phân trang', 'en' => 'Pages', 'zh' => '分页', 'ko' => '페이지', 'ru' => 'Страницы', 'ar' => 'الصفحات'],
        'form_aria' => ['vi' => 'Gửi yêu cầu đặt bàn', 'en' => 'Send a table request', 'zh' => '发送订位请求', 'ko' => '예약 요청 보내기', 'ru' => 'Отправить запрос на стол', 'ar' => 'أرسل طلب حجز'],
        'form_title' => ['vi' => 'Gửi yêu cầu', 'en' => 'Send a request', 'zh' => '发送请求', 'ko' => '요청 보내기', 'ru' => 'Отправить запрос', 'ar' => 'أرسل الطلب'],
        'form_name' => ['vi' => 'Họ tên', 'en' => 'Name', 'zh' => '姓名', 'ko' => '이름', 'ru' => 'Имя', 'ar' => 'الاسم'],
        'form_phone' => ['vi' => 'SĐT / Zalo', 'en' => 'Phone / Zalo', 'zh' => '电话 / Zalo', 'ko' => '전화 / Zalo', 'ru' => 'Телефон / Zalo', 'ar' => 'الهاتف / Zalo'],
        'form_email' => ['vi' => 'Email', 'en' => 'Email', 'zh' => '邮箱', 'ko' => '이메일', 'ru' => 'Эл. почта', 'ar' => 'البريد'],
        'form_guests' => ['vi' => 'Số lượng khách', 'en' => 'Party size', 'zh' => '人数', 'ko' => '인원', 'ru' => 'Гостей', 'ar' => 'عدد الضيوف'],
        'form_guests_ph' => ['vi' => 'Ví dụ: 4', 'en' => 'e.g. 4', 'zh' => '例如：4', 'ko' => '예: 4', 'ru' => 'например, 4', 'ar' => 'مثال: 4'],
        'form_date' => ['vi' => 'Ngày', 'en' => 'Date', 'zh' => '日期', 'ko' => '날짜', 'ru' => 'Дата', 'ar' => 'التاريخ'],
        'form_time' => ['vi' => 'Giờ', 'en' => 'Time', 'zh' => '时间', 'ko' => '시간', 'ru' => 'Время', 'ar' => 'الوقت'],
        'form_time_ph' => ['vi' => 'Chọn giờ', 'en' => 'Choose a time', 'zh' => '选择时间', 'ko' => '시간 선택', 'ru' => 'Выберите время', 'ar' => 'اختر الوقت'],
        'form_party' => ['vi' => 'Loại tiệc', 'en' => 'Party type', 'zh' => '宴会类型', 'ko' => '행사 종류', 'ru' => 'Тип праздника', 'ar' => 'نوع الحفلة'],
        'form_party_ph' => ['vi' => 'Chọn loại tiệc', 'en' => 'Choose a type', 'zh' => '选择类型', 'ko' => '종류 선택', 'ru' => 'Выберите тип', 'ar' => 'اختر النوع'],
        'form_company' => ['vi' => 'Tên công ty', 'en' => 'Company', 'zh' => '公司名称', 'ko' => '회사명', 'ru' => 'Компания', 'ar' => 'الشركة'],
        'form_vat' => ['vi' => 'Cần hóa đơn VAT', 'en' => 'VAT invoice needed', 'zh' => '需要增值税发票', 'ko' => '세금계산서 필요', 'ru' => 'Нужен счёт с НДС', 'ar' => 'فاتورة ضريبية'],
        'form_unit' => ['vi' => 'Công ty / đơn vị', 'en' => 'Company / organisation', 'zh' => '公司 / 单位', 'ko' => '회사 / 단체', 'ru' => 'Компания / организация', 'ar' => 'الشركة / الجهة'],
        'form_note' => ['vi' => 'Ghi chú / yêu cầu', 'en' => 'Note', 'zh' => '备注', 'ko' => '메모', 'ru' => 'Примечание', 'ar' => 'ملاحظة'],
        'form_submit' => ['vi' => 'Gửi yêu cầu', 'en' => 'Send request', 'zh' => '发送', 'ko' => '보내기', 'ru' => 'Отправить', 'ar' => 'إرسال'],
        'form_done_title' => ['vi' => 'Đã nhận yêu cầu', 'en' => 'Request received', 'zh' => '已收到请求', 'ko' => '요청을 받았습니다', 'ru' => 'Запрос получен', 'ar' => 'تم استلام الطلب'],
        'form_done_message' => ['vi' => 'Đã nhận. Nhà hàng sẽ gọi lại xác nhận trong giờ mở cửa.', 'en' => 'Received. The restaurant will call back during opening hours.', 'zh' => '已收到。餐厅会在营业时间内回电确认。', 'ko' => '접수했습니다. 영업시간에 전화로 확인합니다.', 'ru' => 'Получено. Ресторан перезвонит в часы работы.', 'ar' => 'تم الاستلام. سيتصل المطعم خلال ساعات العمل.'],
        'form_group' => ['vi' => 'Nhóm khách hàng', 'en' => 'Guest type', 'zh' => '客人类型', 'ko' => '손님 유형', 'ru' => 'Тип гостей', 'ar' => 'نوع الضيوف'],
        'form_group_ph' => ['vi' => 'Chọn nhóm khách', 'en' => 'Choose a group', 'zh' => '选择类型', 'ko' => '유형 선택', 'ru' => 'Выберите группу', 'ar' => 'اختر المجموعة'],
        'form_group_hint' => ['vi' => 'Chọn nhóm để nhà hàng chuẩn bị đúng nhu cầu.', 'en' => 'Choose a group so the restaurant can prepare.', 'zh' => '选择类型，方便餐厅准备。', 'ko' => '유형을 고르면 식당이 준비합니다.', 'ru' => 'Выберите группу, чтобы ресторан подготовился.', 'ar' => 'اختر المجموعة ليجهّز المطعم.'],
        'form_area' => ['vi' => 'Khu vực', 'en' => 'Area', 'zh' => '区域', 'ko' => '구역', 'ru' => 'Зона', 'ar' => 'المنطقة'],
        'area_khu-a' => ['vi' => 'Khu A', 'en' => 'Area A', 'zh' => 'A区', 'ko' => 'A구역', 'ru' => 'Зона A', 'ar' => 'المنطقة A'],
        'area_khu-b' => ['vi' => 'Khu B', 'en' => 'Area B', 'zh' => 'B区', 'ko' => 'B구역', 'ru' => 'Зона B', 'ar' => 'المنطقة B'],
        'area_phong-vip' => ['vi' => 'Phòng VIP', 'en' => 'VIP room', 'zh' => 'VIP包厢', 'ko' => 'VIP룸', 'ru' => 'VIP-комната', 'ar' => 'غرفة VIP'],
        'area_khong-yeu-cau' => ['vi' => 'Không yêu cầu', 'en' => 'No preference', 'zh' => '不指定', 'ko' => '상관없음', 'ru' => 'Без предпочтений', 'ar' => 'بدون تفضيل'],
        'group_khach-le' => ['vi' => 'Khách lẻ', 'en' => 'Individual guests', 'zh' => '散客', 'ko' => '개인 손님', 'ru' => 'Индивидуальные гости', 'ar' => 'ضيوف أفراد'],
        'group_gia-dinh' => ['vi' => 'Khách gia đình', 'en' => 'Family', 'zh' => '家庭', 'ko' => '가족', 'ru' => 'Семья', 'ar' => 'عائلة'],
        'group_doan-truc-tiep' => ['vi' => 'Khách đoàn trực tiếp', 'en' => 'Group booking', 'zh' => '团队', 'ko' => '단체', 'ru' => 'Группа', 'ar' => 'مجموعة'],
        'group_doanh-nghiep' => ['vi' => 'Khách doanh nghiệp', 'en' => 'Company', 'zh' => '企业', 'ko' => '기업', 'ru' => 'Компания', 'ar' => 'شركة'],
        'group_dat-tiec' => ['vi' => 'Khách đặt tiệc', 'en' => 'Private event', 'zh' => '宴会预订', 'ko' => '연회 예약', 'ru' => 'Банкет', 'ar' => 'حجز حفلة'],
        'group_doi-tac-dua-khach' => ['vi' => 'Đối tác đưa khách', 'en' => 'Partner referral', 'zh' => '合作方带客', 'ko' => '파트너 소개', 'ru' => 'Партнёр', 'ar' => 'شريك يجلب ضيوفاً'],
        'party_sinh-nhat' => ['vi' => 'Sinh nhật', 'en' => 'Birthday', 'zh' => '生日', 'ko' => '생일', 'ru' => 'День рождения', 'ar' => 'عيد ميلاد'],
        'party_thoi-noi' => ['vi' => 'Thôi nôi', 'en' => 'First birthday', 'zh' => '周岁', 'ko' => '돌잔치', 'ru' => 'Первый день рождения', 'ar' => 'عيد الميلاد الأول'],
        'party_mung-tho' => ['vi' => 'Mừng thọ', 'en' => 'Longevity', 'zh' => '寿宴', 'ko' => '칠순·팔순', 'ru' => 'Юбилей', 'ar' => 'احتفال بالعمر'],
        'party_ky-niem' => ['vi' => 'Kỷ niệm', 'en' => 'Anniversary', 'zh' => '纪念日', 'ko' => '기념일', 'ru' => 'Годовщина', 'ar' => 'ذكرى'],
        'party_khac' => ['vi' => 'Khác', 'en' => 'Other', 'zh' => '其他', 'ko' => '기타', 'ru' => 'Другое', 'ar' => 'أخرى'],
        'drinks' => ['vi' => 'Đồ uống', 'en' => 'Drinks', 'zh' => '饮品', 'ko' => '음료', 'ru' => 'Напитки', 'ar' => 'مشروبات'],
        'food' => ['vi' => 'Đồ ăn', 'en' => 'Food', 'zh' => '菜品', 'ko' => '음식', 'ru' => 'Еда', 'ar' => 'طعام'],
    ];
}

/** Translated UI string, or null when this key is not in the child dictionary. */
function gns_ui_text($key) {
    $dict = gns_ui_dict();
    if (!isset($dict[$key])) return null;
    $lang = gns_ui_lang();
    if (isset($dict[$key][$lang])) return $dict[$key][$lang];
    if (isset($dict[$key]['en'])) return $dict[$key]['en'];
    return $dict[$key]['vi'];
}

function gns_tx($key, $fallback) {
    $own = gns_ui_text($key);
    if ($own !== null && $own !== '') return $own;
    return $fallback;
}

/**
 * URLs that must not be shown as photographs.
 * September 2026 ganhngon-*.webp uploads are AI illustrations.
 * photos/com-nieu.webp is a Wikimedia Commons file, not a restaurant photo.
 */
function gns_hidden_media_patterns() {
    $patterns = [
        '#/uploads/2026/09/ganhngon-.+\.webp#i',
        '#/img/photos/com-nieu\.webp#i',
    ];
    $filtered = apply_filters('gns_hidden_media_patterns', $patterns);
    return is_array($filtered) ? $filtered : $patterns;
}

function gns_is_hidden_media_url($url) {
    $url = (string) $url;
    if ($url === '') return false;
    foreach (gns_hidden_media_patterns() as $pattern) {
        if (is_string($pattern) && $pattern !== '' && preg_match($pattern, $url)) return true;
    }
    return false;
}

function gns_real_og_image_url() {
    return get_stylesheet_directory_uri() . '/assets/img/photos/lau-ga-sau-rieng.webp';
}

function gns_safe_share_image($url) {
    $url = is_string($url) ? $url : '';
    if ($url === '' || gns_is_hidden_media_url($url)) return gns_real_og_image_url();
    return $url;
}

function gns_form_slug() {
    $slug = function_exists('gns_base_slug') ? (string) gns_base_slug() : '';
    return (string) preg_replace('/-(en|zh|ko|ru|ar)$/', '', $slug);
}

/** [context, guest group] for pages whose old booking form is replaced. */
function gns_legacy_request_spec($slug = '') {
    if ($slug === '') $slug = gns_form_slug();
    $map = [
        'tiec-sinh-nhat' => ['tiec', 'dat-tiec'],
        'gala-dinner' => ['tiec', 'dat-tiec'],
        'khach-doan' => ['tiec', 'doan-truc-tiep'],
        'tiec-cong-ty' => ['tiec', 'doanh-nghiep'],
        'hop-mat-gia-dinh' => ['tiec', 'gia-dinh'],
        'doi-tac' => ['lien-he', 'doi-tac-dua-khach'],
    ];
    return isset($map[$slug]) ? $map[$slug] : null;
}

function gns_page_has_baked_request_form($slug) {
    $slug = preg_replace('/[^a-z0-9\-]/', '', (string) $slug);
    if ($slug === '') return false;
    $file = get_stylesheet_directory() . '/parts/mains/' . $slug . '.html';
    if (!is_readable($file)) return false;
    $raw = file_get_contents($file);
    return is_string($raw) && (strpos($raw, '{{GN_FORM:') !== false || strpos($raw, '{{PARTNER_FORM}}') !== false);
}

function gns_strip_legacy_forms($html) {
    $html = preg_replace('/<form\b[^>]*\bgn-kd-lead-form\b[^>]*>.*?<\/form>/is', '', $html);
    $html = preg_replace('/<form\b[^>]*sao_create_booking[^>]*>.*?<\/form>/is', '', $html);
    $html = preg_replace('/<h[1-3][^>]*>\s*Xin báo giá[^<]*<\/h[1-3]>/iu', '', $html);
    return is_string($html) ? $html : '';
}

function gns_scrub_public_copy($text) {
    if (!is_string($text) || $text === '') return $text;
    $text = str_ireplace(
        ['Hotline/Zalo 24/7', 'Hotline 24/7'],
        'Hotline (6:30–2:00)',
        $text
    );
    $text = str_replace('10 phòng VIP', '9 phòng VIP', $text);
    $text = preg_replace('/\bhơn\s+220\s+món\b/iu', 'các món', $text);
    $text = preg_replace('/\b220\+\s*món\b/u', 'các món', $text);
    $text = preg_replace('/\b220-dish\b/i', '', $text);
    $text = preg_replace('/\b220\+/u', '', $text);
    $text = preg_replace('/Đặt cọc\s*30\s*%[^<.]*/iu', '', $text);
    $text = preg_replace('/cần đặt cọc\s*30\s*%[^<.]*/iu', '', $text);
    $text = preg_replace('/cọc\s*30\s*%[^<.]*/iu', '', $text);
    $text = preg_replace('/[ \t]{2,}/', ' ', $text);
    return $text;
}

function gns_isolate_phones($html) {
    $parts = preg_split('/(<[^>]+>)/', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
    if (!is_array($parts)) return $html;
    foreach ($parts as $i => $part) {
        if ($part === '' || $part[0] === '<') continue;
        $parts[$i] = preg_replace(
            '/((?:\+84|0)(?:[\s.\-]*\d){8,12})/u',
            '<bdi dir="ltr">$1</bdi>',
            $part
        );
    }
    return implode('', $parts);
}

function gns_fix_videos($html) {
    $out = preg_replace_callback('/<video\b([^>]*)>/i', function ($m) {
        $attrs = preg_replace('/\sautoplay(?:\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+))?/i', '', $m[1]);
        $attrs = preg_replace('/\spreload\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $attrs);
        $attrs = preg_replace('/\sdata-gns-video\b/', '', $attrs);
        return '<video' . $attrs . ' preload="none" data-gns-video>';
    }, $html);
    return is_string($out) ? $out : $html;
}

function gns_swap_hidden_images($html, $drop) {
    if ($drop) {
        $html = preg_replace_callback('/<figure\b[^>]*>.*?<\/figure>/is', function ($m) {
            $fig = $m[0];
            if (stripos($fig, 'gns-slot') !== false) return '';
            if (!preg_match_all('/<img\b[^>]*\ssrc="([^"]+)"/i', $fig, $imgs)) return $fig;
            foreach ($imgs[1] as $src) {
                if (!gns_is_hidden_media_url($src)) return $fig;
            }
            return '';
        }, $html);
    }
    $html = preg_replace_callback('/<img\b[^>]*>/i', function ($m) use ($drop) {
        if (!preg_match('/\ssrc="([^"]+)"/', $m[0], $src)) return $m[0];
        if (!gns_is_hidden_media_url($src[1])) return $m[0];
        if ($drop) return '';
        $class = '';
        if (preg_match('/\sclass="([^"]*)"/', $m[0], $c)) $class = $c[1];
        return function_exists('gns_slot_markup') ? gns_slot_markup($class, '', 'i-flame') : '';
    }, $html);
    return is_string($html) ? $html : '';
}

function gns_drop_tile_gallery($html) {
    return gns_swap_hidden_images($html, true);
}

function gns_demote_content_h1($html) {
    $html = preg_replace('/<h1(\b[^>]*)>/i', '<h2$1>', $html);
    $html = preg_replace('/<\/h1>/i', '</h2>', $html);
    return is_string($html) ? $html : '';
}

function gns_public_content($content) {
    if (is_admin() || !is_string($content)) return $content;
    $content = gns_strip_legacy_forms($content);
    $content = gns_scrub_public_copy($content);
    $slug = gns_form_slug();
    $drop = ($slug === 'hinh-anh-review');
    $content = gns_swap_hidden_images($content, $drop);
    $content = gns_fix_videos($content);
    $content = gns_isolate_phones($content);
    if (is_singular()) $content = gns_demote_content_h1($content);
    $spec = gns_legacy_request_spec($slug);
    if ($spec && is_singular('page') && strpos($content, 'data-gn-request') === false && !gns_page_has_baked_request_form($slug)) {
        if (function_exists('gns_request_form_html')) {
            $content .= gns_request_form_html($spec[0]);
        }
    }
    return $content;
}
add_filter('the_content', 'gns_public_content', 12);

add_filter('do_shortcode_tag', function ($output, $tag) {
    if (!is_string($output)) return $output;
    if (stripos($output, 'sao_create_booking') !== false || stripos($output, 'gn-kd-lead-form') !== false) return '';
    unset($tag);
    return $output;
}, 10, 2);

add_filter('post_thumbnail_html', function ($html, $post_id, $thumb_id) {
    $url = $thumb_id ? wp_get_attachment_image_url($thumb_id, 'large') : '';
    if (!$url || !gns_is_hidden_media_url($url)) return $html;
    unset($post_id);
    return function_exists('gns_slot_markup') ? gns_slot_markup('post-cover', '', 'i-flame') : '';
}, 10, 3);

function gns_meta_from_excerpt($desc) {
    $desc = gns_scrub_public_copy(is_string($desc) ? $desc : '');
    if (trim(wp_strip_all_tags($desc)) !== '') return trim($desc);
    if (!is_singular()) return $desc;
    $post = get_queried_object();
    if (!$post || empty($post->post_excerpt) && empty($post->post_content)) return $desc;
    $source = $post->post_excerpt !== '' ? $post->post_excerpt : $post->post_content;
    $source = wp_strip_all_tags(strip_shortcodes((string) $source));
    $source = gns_scrub_public_copy($source);
    $words = wp_trim_words($source, 28, '…');
    return is_string($words) ? $words : $desc;
}
add_filter('wpseo_metadesc', 'gns_meta_from_excerpt', 40);
add_filter('wpseo_opengraph_desc', 'gns_meta_from_excerpt', 40);

add_action('wp_head', function () {
    if (defined('WPSEO_VERSION') || defined('RANK_MATH_VERSION')) return;
    if (!is_singular()) return;
    $desc = gns_meta_from_excerpt('');
    if ($desc === '') return;
    echo '<meta name="description" content="' . esc_attr($desc) . '">' . "\n";
}, 3);

add_filter('wpseo_opengraph_image', 'gns_safe_share_image', 20);
add_filter('wpseo_twitter_image', 'gns_safe_share_image', 20);

add_filter('wpseo_frontend_presentation', function ($presentation) {
    if (!is_object($presentation)) return $presentation;
    $real = gns_real_og_image_url();
    $images = [];
    if (isset($presentation->open_graph_images) && is_array($presentation->open_graph_images)) {
        foreach ($presentation->open_graph_images as $image) {
            $url = '';
            if (is_array($image) && isset($image['url'])) $url = (string) $image['url'];
            elseif (is_string($image)) $url = $image;
            if ($url === '' || gns_is_hidden_media_url($url)) continue;
            $images[$url] = is_array($image) ? $image : ['url' => $url];
        }
    }
    if (!$images) $images[$real] = ['url' => $real];
    $presentation->open_graph_images = $images;
    $twitter = isset($presentation->twitter_image) ? (string) $presentation->twitter_image : '';
    if ($twitter === '' || gns_is_hidden_media_url($twitter)) $presentation->twitter_image = $real;
    return $presentation;
}, 20);

function gns_schema_is_list($node) {
    if (!is_array($node)) return false;
    $i = 0;
    foreach ($node as $key => $unused) {
        if ($key !== $i) return false;
        $i++;
        unset($unused);
    }
    return true;
}

function gns_scrub_schema_node($node) {
    if (is_string($node)) {
        if (preg_match('#^https?://(?:www\.)?saophuquoc\.com/?$#i', trim($node))) return null;
        $node = preg_replace('#https?://(?:www\.)?saophuquoc\.com/?#i', '', $node);
        if (gns_is_hidden_media_url($node)) return gns_real_og_image_url();
        return gns_scrub_public_copy(str_replace(
            ['Phú Quốc, Kiên Giang', 'Phu Quoc, Kien Giang'],
            ['Phú Quốc', 'Phu Quoc'],
            $node
        ));
    }
    if (!is_array($node)) return $node;
    $type = isset($node['@type']) ? $node['@type'] : '';
    $types = is_array($type) ? $type : [$type];
    if (in_array('Person', $types, true)) return null;
    if (isset($node['parentOrganization'])) {
        $blob = wp_json_encode($node['parentOrganization']);
        if (is_string($blob) && stripos($blob, 'saophuquoc') !== false) unset($node['parentOrganization']);
    }
    if (isset($node['sameAs']) && is_array($node['sameAs'])) {
        $node['sameAs'] = array_values(array_filter($node['sameAs'], function ($url) {
            return !is_string($url) || stripos($url, 'saophuquoc.com') === false;
        }));
    }
    $list = gns_schema_is_list($node);
    $out = [];
    foreach ($node as $key => $value) {
        if ($key === 'author' || $key === 'author_name' || $key === 'author_url') continue;
        if ($key === 'addressRegion' && is_string($value)) {
            $out[$key] = (strpos($value, 'Phu') !== false && strpos($value, 'Phú') === false) ? 'Phu Quoc' : 'Phú Quốc';
            continue;
        }
        $clean = gns_scrub_schema_node($value);
        if ($clean === null) continue;
        $out[$key] = $clean;
    }
    return $list ? array_values($out) : $out;
}
add_filter('wpseo_schema_graph', function ($graph) {
    return gns_scrub_schema_node($graph);
}, 20);

function gns_scrub_schema_html($html) {
    $html = preg_replace_callback(
        '#<script type="application/ld\+json">\s*(.*?)\s*</script>#s',
        function ($m) {
            $data = json_decode($m[1], true);
            if (!is_array($data)) {
                $raw = preg_replace('#https?://(?:www\.)?saophuquoc\.com/?#i', '', $m[1]);
                return '<script type="application/ld+json">' . $raw . '</script>';
            }
            $data = gns_scrub_schema_node($data);
            if (!is_array($data)) $data = [];
            return '<script type="application/ld+json">' . wp_json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
        },
        $html
    );
    $real = gns_real_og_image_url();
    $html = preg_replace_callback('#https?://[^"\\\\\s]+#', function ($m) use ($real) {
        return gns_is_hidden_media_url($m[0]) ? $real : $m[0];
    }, $html);
    $html = str_replace(
        ['Phú Quốc, Kiên Giang', 'Phu Quoc, Kien Giang'],
        ['Phú Quốc', 'Phu Quoc'],
        $html
    );
    $html = str_replace(
        [
            '220+ Vietnamese dishes from three regions in Phu Quoc, Duong Dong',
            '220+ món đặc sản Việt Nam ba miền tại Phú Quốc',
            'phục vụ 220+ món Việt ba miền',
            'từ 4–40 khách ',
            '(Sen, Trúc, Đào, Mai, Cúc, Lan, Hồng, Nguyệt, Phượng, Signature). ',
            'Hướng dẫn viên đi cùng đoàn ăn miễn phí. ',
            'có chỗ đậu xe 16/29/45 chỗ',
            'VAT 8%',
            'Phần còn lại là 220+ món Việt ba miền. ',
        ],
        [
            'Vietnamese dishes from three regions in Phu Quoc, Duong Dong',
            'Món đặc sản Việt Nam ba miền tại Phú Quốc',
            'phục vụ món Việt ba miền',
            '',
            '',
            '',
            'có chỗ đậu xe',
            'VAT',
            'Phần còn lại là món Việt ba miền. ',
        ],
        $html
    );
    return gns_scrub_public_copy(is_string($html) ? $html : '');
}

add_action('init', function () {
    remove_action('wp_head', 'gn_schema_all', 30);
}, 20);

add_action('wp_head', function () {
    if (!function_exists('gn_schema_all')) return;
    ob_start();
    gn_schema_all();
    $html = ob_get_clean();
    echo gns_scrub_schema_html(is_string($html) ? $html : '');
}, 30);

add_filter('nav_menu_item_title', function ($title) {
    $map = [
        'Trang chủ' => 'nav_home',
        'Thực đơn' => 'nav_menu',
        'Tiệc & Sự kiện' => 'nav_groups',
        'Tin tức' => 'nav_news',
        'Về Gánh Ngon' => 'nav_about',
        'Liên hệ' => 'nav_contact',
        'Đặt bàn' => 'nav_book',
    ];
    if (!isset($map[$title])) return $title;
    $text = gns_ui_text($map[$title]);
    return $text !== null ? $text : $title;
}, 20);

add_action('template_redirect', function () {
    if (is_admin()) return;
    $slug = gns_form_slug();
    $paged = function_exists('gns_news_paged') ? gns_news_paged() : 1;
    if ($slug !== 'tin-tuc' || $paged < 2) return;
    if (!function_exists('gns_news_query') || !function_exists('gns_news_page_count')) return;
    $cat = function_exists('gns_news_category_slug') ? gns_news_category_slug() : '';
    $q = gns_news_query(1, $cat);
    $pages = gns_news_page_count((int) $q->found_posts);
    wp_reset_postdata();
    if ($paged <= $pages) return;
    global $wp_query;
    $wp_query->set_404();
    status_header(404);
    nocache_headers();
    $template = get_404_template();
    if ($template) include $template;
    exit;
}, 1);

add_action('wp_footer', function () {
    echo "<script>document.querySelectorAll('video[data-gns-video]').forEach(function(v){if(window.matchMedia('(min-width:768px)').matches){var p=v.play();if(p&&p.catch)p.catch(function(){});}});</script>\n";
}, 40);

add_filter('rest_endpoints', function ($endpoints) {
    if (is_user_logged_in() || !is_array($endpoints)) return $endpoints;
    unset($endpoints['/wp/v2/users'], $endpoints['/wp/v2/users/(?P<id>[\d]+)']);
    return $endpoints;
});

add_filter('wp_sitemaps_add_provider', function ($provider, $name) {
    if ($name === 'users') return false;
    return $provider;
}, 10, 2);

add_filter('wp_sitemaps_posts_query_args', function ($args) {
    if (!is_array($args)) $args = [];
    $args['post_status'] = 'publish';
    return $args;
});

add_filter('wp_sitemaps_taxonomies_query_args', function ($args) {
    if (!is_array($args)) $args = [];
    $args['hide_empty'] = true;
    return $args;
});

add_filter('wpseo_sitemap_exclude_author', function ($value) {
    if (is_array($value)) return [];
    return true;
});

add_filter('wpseo_sitemap_exclude_empty_terms', '__return_true');

add_filter('wpseo_sitemap_exclude_post', function ($exclude, $post_id) {
    $status = get_post_status($post_id);
    if ($status !== 'publish') return true;
    return $exclude;
}, 10, 2);

add_filter('option_wpseo_titles', function ($value) {
    if (!is_array($value)) return $value;
    $value['disable-author'] = true;
    $value['noindex-author-wpseo'] = true;
    return $value;
});

add_filter('oembed_response_data', function ($data) {
    if (!is_array($data) || is_user_logged_in()) return $data;
    unset($data['author_name'], $data['author_url']);
    return $data;
});
