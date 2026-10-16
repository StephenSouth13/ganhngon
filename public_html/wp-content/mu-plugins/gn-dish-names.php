<?php
/**
 * Plugin Name: GN Dish Names
 * Description: Ten 33 mon theo 6 ngon ngu, dung cho alt/caption anh mon theo ngon ngu trang dang xem.
 *   gn_dish_name('pho-bo-ha-noi')          -> ten mon theo ngon ngu hien tai (Polylang), mac dinh vi
 *   gn_dish_alt('pho-bo-ha-noi')           -> "Pho bo Ha Noi — Ganh Ngon Phu Quoc" theo ngon ngu
 * Version: 1.0
 */
if (!defined('ABSPATH')) exit;

function gn_dish_table() {
    static $t = null;
    if ($t !== null) return $t;
    $t = array(
        'lau-ga-sau-rieng'       => array('vi' => 'Lẩu gà sầu riêng Phú Quốc',         'en' => 'Phu Quoc durian chicken hotpot',       'zh' => '富国岛榴莲鸡火锅',         'ko' => '푸꾸옥 두리안 닭 샤브샤브',        'ru' => 'Куриный хотпот с дурианом',              'ar' => 'حلة الدجاج بالدوريان من فو كووك'),
        'com-nieu-dap'           => array('vi' => 'Cơm niêu đập',                       'en' => 'Clay-pot rice',                        'zh' => '砂锅饭',                   'ko' => '뚝배기 누룽지밥',                  'ru' => 'Рис в глиняном горшочке',                 'ar' => 'أرز القدر الفخاري'),
        'banh-mi'                => array('vi' => 'Bánh mì Việt Nam',                   'en' => 'Vietnamese banh mi',                   'zh' => '越南法棍三明治',           'ko' => '베트남 반미',                       'ru' => 'Вьетнамский бань ми',                     'ar' => 'بان مي الفيتنامي'),
        'pho-bo-ha-noi'          => array('vi' => 'Phở bò Hà Nội',                      'en' => 'Hanoi beef pho',                       'zh' => '河内牛肉河粉',             'ko' => '하노이 소고기 쌀국수',              'ru' => 'Ханойский фо с говядиной',                'ar' => 'فو اللحم على طريقة هانوي'),
        'bun-bo-hue'             => array('vi' => 'Bún bò Huế',                         'en' => 'Hue spicy beef noodle soup',           'zh' => '顺化牛肉米线',             'ko' => '후에 매운 소고기 쌀국수',            'ru' => 'Бун бо Хюэ',                              'ar' => 'بون بو هوي'),
        'bun-cha-ha-noi'         => array('vi' => 'Bún chả Hà Nội',                     'en' => 'Hanoi bun cha',                        'zh' => '河内烤肉米线',             'ko' => '하노이 분짜',                       'ru' => 'Бун ча по-ханойски',                      'ar' => 'بون تشا هانوي'),
        'cha-ca-la-vong'         => array('vi' => 'Chả cá Lã Vọng',                     'en' => 'Cha ca La Vong turmeric fish',         'zh' => '河内姜黄煎鱼',             'ko' => '짜까 라봉',                         'ru' => 'Ча ка Ла Вонг',                           'ar' => 'تشا كا لا فونغ'),
        'banh-cuon-thanh-tri'    => array('vi' => 'Bánh cuốn Thanh Trì chả quế',        'en' => 'Thanh Tri steamed rice rolls',         'zh' => '青池蒸粉卷',               'ko' => '탄찌 반꾸온',                       'ru' => 'Бань куон Тхань Чи',                      'ar' => 'بان كون تان تري'),
        'cha-gio'                => array('vi' => 'Chả giò',                            'en' => 'Fried spring rolls',                   'zh' => '越南炸春卷',               'ko' => '짜조 (튀김 스프링롤)',              'ru' => 'Жареные спринг-роллы',                    'ar' => 'لفائف الربيع المقلية'),
        'nem-cua-be'             => array('vi' => 'Nem cua bể Hải Phòng',               'en' => 'Hai Phong crab spring rolls',          'zh' => '海防蟹肉春卷',             'ko' => '하이퐁 게살 스프링롤',              'ru' => 'Крабовые роллы из Хайфона',               'ar' => 'لفائف السلطعون من هاي فونغ'),
        'banh-hoi-nem-nuong'     => array('vi' => 'Bánh hỏi nem nướng Nha Trang',       'en' => 'Banh hoi with grilled pork rolls',     'zh' => '芽庄细米线配烤肉卷',       'ko' => '냐짱 반호이 넴느엉',                'ru' => 'Бань хой с нем нуонг',                    'ar' => 'بان هوي مع لحم مشوي'),
        'banh-xeo-nam-bo'        => array('vi' => 'Bánh xèo Nam Bộ',                    'en' => 'Southern banh xeo crepe',              'zh' => '南部越南煎饼',             'ko' => '남부식 반쎄오',                     'ru' => 'Южный бань сео',                          'ar' => 'بان سيو الجنوبي'),
        'banh-khot-vung-tau'     => array('vi' => 'Bánh khọt Vũng Tàu',                 'en' => 'Vung Tau mini shrimp cakes',           'zh' => '头顿迷你虾饼',             'ko' => '붕따우 반콧',                       'ru' => 'Бань кхот из Вунгтау',                    'ar' => 'بان خوت فونغ تاو'),
        'banh-beo-chen-hue'      => array('vi' => 'Bánh bèo chén Huế',                  'en' => 'Hue steamed rice cakes',               'zh' => '顺化小碗蒸米糕',           'ko' => '후에 반베오',                       'ru' => 'Бань бео из Хюэ',                         'ar' => 'بان بيو هوي'),
        'banh-can'               => array('vi' => 'Bánh căn trứng cút',                 'en' => 'Banh can with quail eggs',             'zh' => '鹌鹑蛋米饼',               'ko' => '메추리알 반깐',                     'ru' => 'Бань кан с перепелиными яйцами',         'ar' => 'بان كان ببيض السمان'),
        'banh-bo-thot-not'       => array('vi' => 'Bánh bò thốt nốt',                   'en' => 'Palm-sugar honeycomb cake',            'zh' => '糖棕蜂巢糕',               'ko' => '팜슈가 벌집떡',                     'ru' => 'Пирожное бань бо с пальмовым сахаром',   'ar' => 'كعكة بان بو بسكر النخيل'),
        'nem-lui-hue'            => array('vi' => 'Nem lụi Huế',                        'en' => 'Hue lemongrass pork skewers',          'zh' => '顺化香茅烤肉串',           'ko' => '후에 넴루이',                       'ru' => 'Нем луй из Хюэ',                          'ar' => 'نيم لوي هوي'),
        'mi-quang-ga'            => array('vi' => 'Mì Quảng gà',                        'en' => 'Mi Quang chicken noodles',             'zh' => '广南鸡肉面',               'ko' => '닭고기 미꽝',                       'ru' => 'Ми Куанг с курицей',                      'ar' => 'مي كوانغ بالدجاج'),
        'hu-tieu-nam-vang'       => array('vi' => 'Hủ tiếu Nam Vang',                   'en' => 'Phnom Penh-style noodle soup',         'zh' => '金边粿条',                 'ko' => '남방 후띠우',                       'ru' => 'Ху тиеу Нам Ванг',                        'ar' => 'هو تيو نام فانغ'),
        'com-tam-suon-bi-cha'    => array('vi' => 'Cơm tấm sườn bì chả',                'en' => 'Broken rice with grilled pork chop',   'zh' => '烤排骨碎米饭',             'ko' => '껌떰 (돼지갈비 덮밥)',              'ru' => 'Ком там со свиной отбивной',              'ar' => 'كوم تام مع ضلع الخنزير المشوي'),
        'thit-kho-tau'           => array('vi' => 'Thịt kho tàu nước dừa',              'en' => 'Caramelized pork in coconut water',    'zh' => '椰汁红烧肉',               'ko' => '코코넛 돼지고기 조림',              'ru' => 'Свинина, тушёная в кокосовой воде',       'ar' => 'لحم خنزير مطهو بماء جوز الهند'),
        'canh-chua-ca-loc'       => array('vi' => 'Canh chua cá lóc Nam Bộ',            'en' => 'Southern sour fish soup',              'zh' => '南部酸鱼汤',               'ko' => '남부식 가물치 새콤한 국',            'ru' => 'Кисло-сладкий суп с рыбой',               'ar' => 'حساء السمك الحامض الجنوبي'),
        'bo-nuong-tang'          => array('vi' => 'Bò nướng tảng Nha Trang',            'en' => 'Nha Trang grilled beef slab',          'zh' => '芽庄整块烤牛肉',           'ko' => '냐짱 통소고기 구이',                'ru' => 'Говядина на гриле по-нячангски',          'ar' => 'شريحة لحم بقري مشوية نها ترانغ'),
        'vit-co-van-dinh-hap'    => array('vi' => 'Vịt cỏ Vân Đình hấp',                'en' => 'Van Dinh steamed duck',                'zh' => '云亭蒸鸭',                 'ko' => '번딘 오리찜',                       'ru' => 'Утка на пару из Ван Диня',                'ar' => 'بط فان دينه المطهو على البخار'),
        'tom-sot-hoang-kim'      => array('vi' => 'Tôm sốt hoàng kim trứng muối',       'en' => 'Salted-egg golden prawns',             'zh' => '咸蛋黄金虾',               'ko' => '소금 달걀 황금 새우',               'ru' => 'Креветки в соусе из солёного желтка',     'ar' => 'روبيان بصلصة صفار البيض المملح'),
        'oc-huong-hap-sa'        => array('vi' => 'Ốc hương hấp sả',                    'en' => 'Lemongrass steamed sea snails',        'zh' => '香茅蒸海螺',               'ko' => '레몬그라스 바다고둥 찜',            'ru' => 'Морские улитки с лемонграссом',           'ar' => 'حلزون البحر المطهو بعشب الليمون'),
        'muc-trung-hap-hanh-gung'=> array('vi' => 'Mực trứng hấp hành gừng',            'en' => 'Steamed egg squid with ginger',        'zh' => '姜葱蒸墨鱼',               'ko' => '생강 파 오징어찜',                  'ru' => 'Кальмар на пару с имбирём',               'ar' => 'حبار مطهو بالزنجبيل والبصل الأخضر'),
        'dau-hu-sot-nam'         => array('vi' => 'Đậu hũ sốt nấm',                     'en' => 'Tofu in mushroom sauce',               'zh' => '蘑菇豆腐',                 'ko' => '버섯 소스 두부',                     'ru' => 'Тофу в грибном соусе',                    'ar' => 'توفو بصلصة الفطر'),
        'che-trang-mieng'        => array('vi' => 'Chè và bánh ngọt tráng miệng',       'en' => 'Che and Vietnamese desserts',          'zh' => '越南甜品拼盘',             'ko' => '베트남 디저트 쩨',                  'ru' => 'Вьетнамские десерты че',                  'ar' => 'حلويات فيتنامية تشي'),
        'trai-cay-nhiet-doi'     => array('vi' => 'Đĩa trái cây nhiệt đới',             'en' => 'Tropical fruit platter',               'zh' => '热带水果拼盘',             'ko' => '열대 과일 플래터',                  'ru' => 'Тарелка тропических фруктов',             'ar' => 'طبق الفواكه الاستوائية'),
        'nom-hoa-chuoi'          => array('vi' => 'Nộm hoa chuối',                      'en' => 'Banana blossom salad',                 'zh' => '香蕉花沙拉',               'ko' => '바나나꽃 샐러드',                   'ru' => 'Салат из цветов банана',                  'ar' => 'سلطة زهرة الموز'),
        'rau-luoc-kho-quet'      => array('vi' => 'Rau đặc sản luộc chấm kho quẹt',     'en' => 'Steamed greens with kho quet',         'zh' => '白灼时蔬配焦糖鱼露酱',     'ko' => '데친 채소와 코쿠엣 소스',           'ru' => 'Отварные овощи с соусом кхо куэт',        'ar' => 'خضار مسلوقة مع صلصة خو كويت'),
        'ruou-sim-phu-quoc'      => array('vi' => 'Rượu sim Phú Quốc',                  'en' => 'Phu Quoc sim wine',                    'zh' => '富国岛桃金娘酒',           'ko' => '푸꾸옥 심 와인',                    'ru' => 'Вино сим из Фукуока',                     'ar' => 'نبيذ سيم فو كووك'),
    );
    return $t;
}

function gn_dish_lang() {
    $l = function_exists('pll_current_language') ? pll_current_language() : 'vi';
    return in_array($l, array('vi', 'en', 'zh', 'ko', 'ru', 'ar'), true) ? $l : 'vi';
}

function gn_dish_name($slug, $lang = null) {
    $t = gn_dish_table();
    if (!isset($t[$slug])) return $slug;
    $l = $lang ? $lang : gn_dish_lang();
    return isset($t[$slug][$l]) ? $t[$slug][$l] : $t[$slug]['vi'];
}

function gn_dish_alt($slug, $lang = null) {
    return gn_dish_name($slug, $lang) . ' — Gánh Ngon Phú Quốc';
}
