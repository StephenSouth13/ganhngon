<?php
/* GN-CONV-GUARD */
if (!defined('ABSPATH')) exit;

/** Current language slug (vi/en/zh/ko/ru/ar) */
function gn_cur_lang() {
    if (function_exists('pll_current_language')) {
        $l = pll_current_language('slug');
        if ($l) return $l;
    }
    // fallback: detect from URL
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    if (preg_match('#^/(en|zh|ko|ru|ar)(/|$)#', $uri, $m)) return $m[1];
    return 'vi';
}

/** Language-aware home URL */
function gn_home($path = '/') {
    if (function_exists('pll_home_url')) {
        $base = rtrim(pll_home_url(), '/');
        return $base . '/' . ltrim($path, '/');
    }
    return home_url($path);
}

/** Translation dictionary — UI chrome + homepage strings */
function gn_t($key) {
    static $D = null;
    if ($D === null) $D = gn_i18n_dict();
    $lang = gn_cur_lang();
    if (isset($D[$key][$lang])) return $D[$key][$lang];
    if (isset($D[$key]['en'])) return $D[$key]['en'];
    if (isset($D[$key]['vi'])) return $D[$key]['vi'];
    return $key;
}

function gn_i18n_dict() {
    return [
        // ---- NAV ----
        'nav_home'      => ['vi'=>'Trang chủ','en'=>'Home','zh'=>'首页','ko'=>'홈','ru'=>'Главная','ar'=>'الرئيسية'],
        'nav_about'     => ['vi'=>'Về Gánh Ngon','en'=>'About','zh'=>'关于我们','ko'=>'소개','ru'=>'О нас','ar'=>'من نحن'],
        'nav_menu'      => ['vi'=>'Thực đơn','en'=>'Menu','zh'=>'菜单','ko'=>'메뉴','ru'=>'Меню','ar'=>'القائمة'],
        'nav_book'      => ['vi'=>'Đặt bàn','en'=>'Reservations','zh'=>'预订','ko'=>'예약','ru'=>'Бронь','ar'=>'الحجز'],
        'nav_groups'    => ['vi'=>'Đoàn & Tiệc','en'=>'Groups & Events','zh'=>'团体与宴会','ko'=>'단체 & 행사','ru'=>'Группы и события','ar'=>'المجموعات والفعاليات'],
        'nav_contact'   => ['vi'=>'Liên hệ','en'=>'Contact','zh'=>'联系','ko'=>'연락처','ru'=>'Контакты','ar'=>'اتصل بنا'],
        'btn_book'      => ['vi'=>'Đặt bàn','en'=>'Book a Table','zh'=>'预订','ko'=>'예약하기','ru'=>'Забронировать','ar'=>'احجز'],

        'tagline' => ['vi'=>'Đặc sản Việt Nam. Không gian sang trọng. Trải nghiệm đáng nhớ.','en'=>'Vietnamese Flavors. Elegant Dining. Memorable Moments.','zh'=>'越南美食。高雅空间。难忘体验。','ko'=>'베트남의 맛. 우아한 다이닝. 잊지 못할 순간.','ru'=>'Вкусы Вьетнама. Изысканный ужин. Незабываемые моменты.','ar'=>'نكهات فيتنامية. تناول طعام أنيق. لحظات لا تُنسى.'],

        // ---- HERO (signature dish is the headline in every language the site serves) ----
        'hero_eyebrow'  => ['vi'=>'Món đặc trưng · Gánh Ngon Phú Quốc','en'=>'Signature dish · Gánh Ngon Phu Quoc','zh'=>'招牌菜 · 富国岛堪根','ko'=>'대표 요리 · 푸꾸옥 간 응온','ru'=>'Фирменное блюдо · Gánh Ngon Фукуок','ar'=>'طبق التوقيع · Gánh Ngon فو كووك'],
        'hero_grab'     => ['vi'=>'5 min from Night Market, 10 min from airport – Grab: Gánh Ngon 113 Tran Hung Dao','en'=>'5 min from Night Market, 10 min from airport – Grab: Gánh Ngon 113 Tran Hung Dao','zh'=>'5 min from Night Market, 10 min from airport – Grab: Gánh Ngon 113 Tran Hung Dao','ko'=>'5 min from Night Market, 10 min from airport – Grab: Gánh Ngon 113 Tran Hung Dao','ru'=>'5 min from Night Market, 10 min from airport – Grab: Gánh Ngon 113 Tran Hung Dao','ar'=>'5 min from Night Market, 10 min from airport – Grab: Gánh Ngon 113 Tran Hung Dao'],
        'hero_h1'       => ['vi'=>'Lẩu gà sầu riêng','en'=>'Durian chicken hotpot','zh'=>'榴莲鸡火锅','ko'=>'두리안 닭 샤브샤브','ru'=>'Куриный хотпот с дурианом','ar'=>'حلة الدجاج بالدوريان'],
        'hero_lead'     => ['vi'=>'Sầu riêng Phú Quốc hoà trong nước hầm gà — món khách nhớ trước cả thực đơn 220 món Việt ba miền. Bàn thường nhận trong ngày. Giá báo tại nhà hàng.','en'=>'Phu Quoc durian in chicken broth — the dish guests remember before the rest of a 220-dish Vietnamese menu. Regular tables are taken the same day. Prices are quoted in the restaurant.','zh'=>'富国岛榴莲融入鸡汤。客人先记住这道菜,再看220多道越南菜。普通桌可当天入座。价格到店报价。','ko'=>'푸꾸옥 두리안을 닭 육수에 넣은 대표 요리. 220여 가지 베트남 메뉴보다 먼저 보이는 맛. 일반 테이블은 당일 예약. 가격은 매장에서 안내.','ru'=>'Дуриан Фукуока в курином бульоне — блюдо, которое запоминают раньше остального меню из 220 позиций. Обычный стол можно занять сегодня. Цены называют в ресторане.','ar'=>'دوريان فو كووك في مرق الدجاج — الطبق الذي يُذكر قبل قائمة تضم أكثر من 220 طبقًا. الطاولة العادية في اليوم نفسه. الأسعار في المطعم.'],
        'hero_cta1'     => ['vi'=>'Đặt bàn thường hôm nay','en'=>'Book a table today','zh'=>'今天订普通桌','ko'=>'오늘 일반 테이블 예약','ru'=>'Забронировать стол сегодня','ar'=>'احجز طاولة اليوم'],
        'hero_cta2'     => ['vi'=>'Xem món này','en'=>'See this dish','zh'=>'看这道菜','ko'=>'이 요리 보기','ru'=>'Смотреть блюдо','ar'=>'شاهد الطبق'],
        'hero_cta3'     => ['vi'=>'Đoàn & phòng VIP','en'=>'Groups & VIP rooms','zh'=>'团体与VIP包厢','ko'=>'단체 & VIP룸','ru'=>'Группы и VIP','ar'=>'المجموعات وVIP'],
        'hero_note'     => ['vi'=>'Bàn thường: đến và ngồi trong ngày. Nồi lẩu nên báo trước khoảng 1 tiếng. Phòng VIP khoảng 24 giờ. Đoàn từ 20 khách: 48–72 giờ.','en'=>'Regular tables: arrive and sit the same day. The hotpot needs about an hour’s notice. VIP rooms: about 24 hours. Groups of 20+: 48–72 hours.','zh'=>'普通桌当天可坐。火锅建议提前约1小时。VIP包厢约24小时。20人以上团体：48–72小时。','ko'=>'일반 테이블은 당일 착석. 샤브샤브는 약 1시간 전 연락. VIP룸은 약 24시간. 20명 이상은 48–72시간.','ru'=>'Обычный стол — в тот же день. Хотпот лучше заказать примерно за час. VIP — около 24 часов. Группы от 20 человек — за 48–72 часа.','ar'=>'الطاولة العادية في اليوم نفسه. الحلة تحتاج إشعارًا نحو ساعة. غرف VIP نحو 24 ساعة. المجموعات من 20 ضيفًا: 48–72 ساعة.'],
        'hours_clock'   => ['vi'=>'Mở cửa hằng ngày 6:30–2:00','en'=>'Open daily 6:30–2:00','zh'=>'每天营业 6:30–2:00','ko'=>'매일 영업 6:30–2:00','ru'=>'Ежедневно 6:30–2:00','ar'=>'يوميًا 6:30–2:00'],
        'arrival_addr'  => ['vi'=>'113 Trần Hưng Đạo, Dương Đông','en'=>'113 Tran Hung Dao, Duong Dong','zh'=>'陈兴道街113号, 阳东','ko'=>'쩐흥다오 113, 즈엉동','ru'=>'113 Чан Хынг Дао, Зыонг Донг','ar'=>'113 تران هونغ داو، دونغ دونغ'],
        'arrival_walk'  => ['vi'=>'Đến hôm nay','en'=>'Here today','zh'=>'今天就到','ko'=>'오늘 도착','ru'=>'Вы сегодня здесь','ar'=>'وصلت اليوم'],
        'loc_intro'     => ['vi'=>'Trung tâm Dương Đông. Từ sân bay hoặc resort, mở bản đồ là tới. Bàn thường không cần đặt từ hôm trước.','en'=>'Central Duong Dong. From the airport or a resort, open the map and come. A regular table does not need to be booked the day before.','zh'=>'阳东中心。从机场或度假村打开地图即可到达。普通桌不必提前一天订。','ko'=>'즈엉동 중심. 공항이나 리조트에서 지도를 열면 됩니다. 일반 테이블은 전날 예약이 필요 없습니다.','ru'=>'Центр Зыонг Донга. От аэропорта или курорта откройте карту и приезжайте. Обычный стол не нужно бронировать накануне.','ar'=>'وسط دونغ دونغ. من المطار أو المنتجع افتح الخريطة وتعال. الطاولة العادية لا تحتاج حجزًا من اليوم السابق.'],
        'sig_kicker'    => ['vi'=>'Món đặc trưng','en'=>'Signature dish','zh'=>'招牌菜','ko'=>'대표 요리','ru'=>'Фирменное блюдо','ar'=>'طبق التوقيع'],
        'sig_body'      => ['vi'=>'Sầu riêng chín cây được đánh nhuyễn rồi hoà vào nước hầm gà, không thả nguyên múi. Bên cạnh nồi này vẫn là 220+ món Việt ba miền — phở, bánh xèo, cơm tấm, hải sản đảo.','en'=>'Ripe durian is blended into chicken broth, not dropped in as whole segments. Beside this pot: 220+ Vietnamese dishes from all three regions — pho, banh xeo, broken rice, island seafood.','zh'=>'熟榴莲打匀后融入鸡汤,不是整瓣丢进锅里。除此之外还有220多道三地越南菜:河粉、煎饼、碎米饭、海岛海鲜。','ko'=>'잘 익은 두리안을 갈아 닭 육수에 넣습니다. 통 과육을 넣지 않습니다. 이 냄비 옆에는 쌀국수, 반쎄오, 껌떰, 섬 해산물 등 220여 가지가 있습니다.','ru'=>'Спелый дуриан измельчают и смешивают с куриным бульоном, а не кладут дольками. Рядом — 220+ блюд из трёх регионов: фо, бань сео, ком там, морепродукты острова.','ar'=>'يُهرس الدوريان الناضج ويُمزج بمرق الدجاج، ولا يُلقى فصوصًا كاملة. إلى جانبه أكثر من 220 طبقًا من المناطق الثلاث.'],
        'sig_price'     => ['vi'=>'Giá báo tại nhà hàng. Website không niêm yết giá.','en'=>'Quoted at the restaurant. Prices are not listed on the website.','zh'=>'价格到店报价。网站不标价。','ko'=>'가격은 매장에서 안내합니다. 웹사이트에 가격이 없습니다.','ru'=>'Цену называют в ресторане. На сайте цены не указаны.','ar'=>'السعر في المطعم. الموقع لا يعرض الأسعار.'],
        'sig_prep'      => ['vi'=>'Báo trước khoảng 1 tiếng để nồi nóng lúc ngồi xuống.','en'=>'About one hour’s notice so the pot is hot when you sit.','zh'=>'提前约1小时,坐下时锅已热。','ko'=>'앉을 때 뜨겁도록 약 1시간 전 연락.','ru'=>'Предупредите примерно за час, чтобы горшок был горячим.','ar'=>'أخبرونا قبل نحو ساعة ليكون القدر ساخنًا.'],
        'menu_h2'       => ['vi'=>'Thêm 220+ món Việt<br>ba miền','en'=>'Plus 220+ Vietnamese dishes<br>from three regions','zh'=>'另有220多道<br>三地越南菜','ko'=>'그리고 3개 지역<br>베트남 요리 220여 가지','ru'=>'И ещё 220+ блюд<br>из трёх регионов','ar'=>'وأكثر من 220 طبقًا<br>من ثلاث مناطق'],
        'qb_same'       => ['vi'=>'Giữ bàn thường hôm nay','en'=>'Hold a regular table today','zh'=>'今天留普通桌','ko'=>'오늘 일반 테이블 잡기','ru'=>'Обычный стол на сегодня','ar'=>'طاولة عادية اليوم'],
        'qb_name'       => ['vi'=>'Tên','en'=>'Name','zh'=>'姓名','ko'=>'이름','ru'=>'Имя','ar'=>'الاسم'],
        'qb_dish'       => ['vi'=>'Muốn gọi lẩu gà sầu riêng','en'=>'We want durian chicken hotpot','zh'=>'想点榴莲鸡火锅','ko'=>'두리안 닭 샤브샤브를 원합니다','ru'=>'Хотим хотпот с дурианом','ar'=>'نريد حلة الدجاج بالدوريان'],
        'qb_vip_link'   => ['vi'=>'Phòng VIP hoặc đoàn đông','en'=>'VIP room or a large group','zh'=>'VIP包厢或大团','ko'=>'VIP룸 또는 큰 단체','ru'=>'VIP или большая группа','ar'=>'غرفة VIP أو مجموعة كبيرة'],
        'adv_title'     => ['vi'=>'Tư vấn món và bàn thường','en'=>'Dish and table advisor','zh'=>'菜品与普通桌顾问','ko'=>'요리·테이블 안내','ru'=>'Совет по блюдам и столу','ar'=>'إرشاد الطبق والطاولة'],
        'adv_link'      => ['vi'=>'Hỏi món & đường đi','en'=>'Ask about the dish','zh'=>'询问这道菜','ko'=>'요리 문의','ru'=>'Спросить о блюде','ar'=>'اسأل عن الطبق'],
        'book_sent'     => ['vi'=>'Đã nhận. Hotline/Zalo/WhatsApp 0979 232 233 – xác nhận nhanh trong giờ mở cửa 6:30–2:00. Nhà hàng không chốt “còn bàn” trước khi nhân viên gọi lại.','en'=>'Received. Hotline/Zalo/WhatsApp 0979 232 233 – xác nhận nhanh trong giờ mở cửa 6:30–2:00. A free table is not confirmed before staff call back.','zh'=>'已收到。Hotline/Zalo/WhatsApp 0979 232 233 – xác nhận nhanh trong giờ mở cửa 6:30–2:00。回电前不声称还有空位。','ko'=>'접수했습니다. Hotline/Zalo/WhatsApp 0979 232 233 – xác nhận nhanh trong giờ mở cửa 6:30–2:00. 직원이 다시 전화하기 전에는 빈 테이블이라고 확정하지 않습니다.','ru'=>'Заявка принята. Hotline/Zalo/WhatsApp 0979 232 233 – xác nhận nhanh trong giờ mở cửa 6:30–2:00. Свободный стол до звонка не подтверждают.','ar'=>'تم الاستلام. Hotline/Zalo/WhatsApp 0979 232 233 – xác nhận nhanh trong giờ mở cửa 6:30–2:00. لا يُؤكد وجود طاولة قبل أن يتصل الموظف.'],

        // ---- QUICK BOOKING ----
        'qb_date'   => ['vi'=>'Ngày đến','en'=>'Date','zh'=>'日期','ko'=>'날짜','ru'=>'Дата','ar'=>'التاريخ'],
        'qb_time'   => ['vi'=>'Giờ','en'=>'Time','zh'=>'时间','ko'=>'시간','ru'=>'Время','ar'=>'الوقت'],
        'qb_guests' => ['vi'=>'Số khách','en'=>'Guests','zh'=>'人数','ko'=>'인원','ru'=>'Гостей','ar'=>'الضيوف'],
        'qb_type'   => ['vi'=>'Loại khách','en'=>'Guest type','zh'=>'客人类型','ko'=>'고객 유형','ru'=>'Тип гостя','ar'=>'نوع الضيف'],
        'qb_need'   => ['vi'=>'Nhu cầu','en'=>'Need','zh'=>'需求','ko'=>'요청','ru'=>'Запрос','ar'=>'الطلب'],
        'qb_phone'  => ['vi'=>'SĐT / Zalo','en'=>'Phone / WhatsApp','zh'=>'电话','ko'=>'전화','ru'=>'Телефон','ar'=>'الهاتف'],
        'qb_submit' => ['vi'=>'Giữ bàn ngay','en'=>'Hold My Table','zh'=>'立即留位','ko'=>'예약 확정','ru'=>'Забронировать','ar'=>'احجز طاولتي'],
        'people'    => ['vi'=>'người','en'=>'guests','zh'=>'人','ko'=>'명','ru'=>'чел.','ar'=>'ضيف'],

        // ---- ABOUT ----
        'about_eyebrow' => ['vi'=>'Vì sao chọn Gánh Ngon','en'=>'Why choose Gánh Ngon','zh'=>'为何选择堪根','ko'=>'간 응온을 선택하는 이유','ru'=>'Почему Gánh Ngon','ar'=>'لماذا تختار Gánh Ngon'],
        'about_h2'      => ['vi'=>'Một điểm đến ẩm thực Việt Nam<br>đáng nhớ tại Phú Quốc','en'=>'A memorable Vietnamese culinary<br>destination in Phu Quoc','zh'=>'富国岛令人难忘的<br>越南美食目的地','ko'=>'푸꾸옥의 잊지 못할<br>베트남 미식 명소','ru'=>'Незабываемое вьетнамское<br>кулинарное место на Фукуоке','ar'=>'وجهة طعام فيتنامية<br>لا تُنسى في فو كووك'],
        'about_p'       => ['vi'=>'Gánh Ngon không chỉ là nơi dùng bữa. Đây là không gian để thưởng thức món Việt tinh tuyển, gặp gỡ gia đình, tiếp đón đối tác và tạo nên những khoảnh khắc đáng nhớ.','en'=>'Gánh Ngon is more than a place to dine. It is a space to enjoy curated Vietnamese cuisine, gather with family, host partners and create memorable moments.','zh'=>'堪根不仅是用餐的地方,更是品尝精选越南美食、与家人相聚、接待伙伴、创造难忘时刻的空间。','ko'=>'간 응온은 단순한 식당이 아닙니다. 엄선된 베트남 요리를 즐기고, 가족과 모이고, 파트너를 맞이하며 잊지 못할 순간을 만드는 공간입니다.','ru'=>'Gánh Ngon — это больше, чем ресторан. Это место, где можно насладиться отборной вьетнамской кухней, собраться с семьёй, принять партнёров и создать незабываемые моменты.','ar'=>'Gánh Ngon أكثر من مجرد مكان لتناول الطعام. إنه مساحة للاستمتاع بالمأكولات الفيتنامية المختارة والاجتماع مع العائلة واستقبال الشركاء وخلق لحظات لا تُنسى.'],
        'about_reasons_title' => ['vi'=>'6 lý do nổi bật','en'=>'6 reasons to choose us','zh'=>'选择我们的6个理由','ko'=>'선택하는 6가지 이유','ru'=>'6 причин выбрать нас','ar'=>'6 أسباب لاختيارنا'],
        'about_r1' => ['vi'=>'220+ món đặc sản Việt Nam ba miền','en'=>'220+ Vietnamese specialties from 3 regions','zh'=>'220+道三地越南特色菜','ko'=>'3개 지역 베트남 특선 220여 종','ru'=>'220+ вьетнамских блюд из 3 регионов','ar'=>'أكثر من 220 طبقًا فيتناميًا من 3 مناطق'],
        'about_r2' => ['vi'=>'Không gian sang trọng, rộng rãi','en'=>'Elegant, spacious atmosphere','zh'=>'高雅宽敞的空间','ko'=>'우아하고 넓은 공간','ru'=>'Элегантная просторная атмосфера','ar'=>'أجواء أنيقة وفسيحة'],
        'about_r3' => ['vi'=>'10 phòng VIP riêng tư (4–40 khách)','en'=>'10 private VIP rooms (4–40 guests)','zh'=>'10间私人VIP包厢(4-40人)','ko'=>'10개 프라이빗 VIP룸 (4-40명)','ru'=>'10 приватных VIP-комнат (4-40 гостей)','ar'=>'10 غرف VIP خاصة (4-40 ضيفًا)'],
        'about_r4' => ['vi'=>'Phù hợp gia đình, đoàn, doanh nghiệp','en'=>'For families, groups, businesses','zh'=>'适合家庭、团体、企业','ko'=>'가족, 단체, 기업에 적합','ru'=>'Для семей, групп, бизнеса','ar'=>'للعائلات والمجموعات والشركات'],
        'about_r5' => ['vi'=>'Bàn thường nhận trong ngày','en'=>'Regular tables the same day','zh'=>'普通桌当天可订','ko'=>'일반 테이블 당일','ru'=>'Обычный стол в тот же день','ar'=>'طاولة عادية في اليوم نفسه'],
        'about_r6' => ['vi'=>'Menu 4 ngôn ngữ cho khách quốc tế','en'=>'4-language menu for international guests','zh'=>'4种语言菜单','ko'=>'4개 언어 메뉴','ru'=>'Меню на 4 языках','ar'=>'قائمة بأربع لغات'],
        'about_learn'  => ['vi'=>'Tìm hiểu thêm','en'=>'Learn more','zh'=>'了解更多','ko'=>'더 알아보기','ru'=>'Подробнее','ar'=>'اعرف المزيد'],
        'stat_dishes'  => ['vi'=>'Món đặc sản VN','en'=>'VN specialties','zh'=>'越南特色菜','ko'=>'베트남 특선','ru'=>'блюд','ar'=>'أطباق'],
        'stat_vip'     => ['vi'=>'Phòng VIP','en'=>'VIP rooms','zh'=>'VIP包厢','ko'=>'VIP룸','ru'=>'VIP-комнат','ar'=>'غرف VIP'],
        'stat_lang'    => ['vi'=>'Việt·Anh·Trung·Nga','en'=>'VI·EN·ZH·RU','zh'=>'越·英·中·俄','ko'=>'베·영·중·러','ru'=>'VI·EN·ZH·RU','ar'=>'VI·EN·ZH·RU'],

        // ---- MENU SECTION ----
        'menu_eyebrow' => ['vi'=>'Thực đơn','en'=>'Menu','zh'=>'菜单','ko'=>'메뉴','ru'=>'Меню','ar'=>'القائمة'],
        'menu_intro'   => ['vi'=>'220+ món đặc sản Việt Nam ba miền — 13 nhóm. Mỗi món có tên 4 ngôn ngữ. Hỏi tư vấn để nhận set menu phù hợp.','en'=>'220+ Vietnamese specialties from 3 regions — 13 categories. Each dish in 4 languages. Ask us for the right set menu.','zh'=>'220+道三地越南特色菜 — 13大类。每道菜4种语言。咨询我们获取合适套餐。','ko'=>'3개 지역 베트남 특선 220여 종 — 13개 카테고리. 각 요리 4개 언어. 맞춤 세트 메뉴 문의하세요.','ru'=>'220+ вьетнамских блюд из 3 регионов — 13 категорий. Каждое на 4 языках. Спросите про сет-меню.','ar'=>'أكثر من 220 طبقًا فيتناميًا من 3 مناطق — 13 فئة. كل طبق بأربع لغات. اسأل عن قائمة السيت المناسبة.'],
        'menu_noprice' => ['vi'=>'Gánh Ngon không hiển thị giá công khai. Vui lòng để lại ngày, giờ, số khách và nhu cầu để được tư vấn set menu phù hợp.','en'=>'Gánh Ngon does not display prices publicly. Please share your date, time, party size and needs for a tailored menu recommendation.','zh'=>'堪根不公开价格。请提供日期、时间、人数和需求,我们将为您推荐合适菜单。','ko'=>'간 응온은 가격을 공개하지 않습니다. 날짜, 시간, 인원, 요청사항을 남겨주시면 맞춤 메뉴를 추천해 드립니다.','ru'=>'Gánh Ngon не показывает цены публично. Сообщите дату, время, число гостей и пожелания для подбора меню.','ar'=>'لا يعرض Gánh Ngon الأسعار علنًا. يرجى ترك التاريخ والوقت وعدد الضيوف والاحتياجات للحصول على توصية مخصصة.'],
        'menu_viewall' => ['vi'=>'Xem toàn bộ thực đơn','en'=>'View full menu','zh'=>'查看完整菜单','ko'=>'전체 메뉴 보기','ru'=>'Полное меню','ar'=>'عرض القائمة الكاملة'],
        'menu_more'    => ['vi'=>'Xem nhóm','en'=>'See group','zh'=>'查看','ko'=>'더 보기','ru'=>'Смотреть','ar'=>'عرض'],
        'mc1' => ['vi'=>'Bánh & Cuốn','en'=>'Cakes & rolls','zh'=>'饼与卷','ko'=>'반과 롤','ru'=>'Блинчики и роллы','ar'=>'فطائر ولفائف'],
        'mc2' => ['vi'=>'Nộm, Gỏi & Salad','en'=>'Salads','zh'=>'沙拉','ko'=>'샐러드','ru'=>'Салаты','ar'=>'سلطات'],
        'mc3' => ['vi'=>'Bún, Phở, Miến, Mì','en'=>'Noodle soups','zh'=>'粉面','ko'=>'국수','ru'=>'Супы с лапшой','ar'=>'حساء المعكرونة'],
        'mc4' => ['vi'=>'Gà, Vịt, Bồ câu','en'=>'Chicken, duck, pigeon','zh'=>'鸡鸭鸽','ko'=>'닭·오리·비둘기','ru'=>'Курица, утка, голубь','ar'=>'دجاج وبط وحمام'],
        'mc5' => ['vi'=>'Bò & Heo','en'=>'Beef & pork','zh'=>'牛与猪','ko'=>'소·돼지','ru'=>'Говядина и свинина','ar'=>'لحم بقر وخنزير'],
        'mc6' => ['vi'=>'Hải sản','en'=>'Seafood','zh'=>'海鲜','ko'=>'해산물','ru'=>'Морепродукты','ar'=>'مأكولات بحرية'],
        'mc7' => ['vi'=>'Cơm, Canh, Rau','en'=>'Rice, soup, greens','zh'=>'饭汤青菜','ko'=>'밥·국·채소','ru'=>'Рис, суп, зелень','ar'=>'أرز وحساء وخضار'],
        'mc8' => ['vi'=>'Đồ chay','en'=>'Vegetarian','zh'=>'素食','ko'=>'채식','ru'=>'Вегетарианское','ar'=>'نباتي'],
        'mc9' => ['vi'=>'Chè & tráng miệng','en'=>'Desserts','zh'=>'甜品','ko'=>'디저트','ru'=>'Десерты','ar'=>'حلويات'],
        'mc10' => ['vi'=>'Nướng & chiên','en'=>'Grill & fry','zh'=>'烤与炸','ko'=>'구이·튀김','ru'=>'Гриль и жареное','ar'=>'مشوي ومقلي'],
        'mc11' => ['vi'=>'Rau vùng miền','en'=>'Regional greens','zh'=>'地方时蔬','ko'=>'지역 채소','ru'=>'Овощи регионов','ar'=>'خضار المناطق'],
        'mc12' => ['vi'=>'Rượu & đồ uống','en'=>'Drinks','zh'=>'酒水','ko'=>'음료','ru'=>'Напитки','ar'=>'مشروبات'],

        // ---- CUSTOMER TYPES ----
        'ct_eyebrow' => ['vi'=>'Phù hợp với mọi nhóm khách','en'=>'For every type of guest','zh'=>'适合各类客人','ko'=>'모든 고객 유형','ru'=>'Для всех типов гостей','ar'=>'لكل أنواع الضيوف'],
        'ct_h2'      => ['vi'=>'Một nhà hàng — Nhiều trải nghiệm','en'=>'One restaurant — Many experiences','zh'=>'一家餐厅 — 多种体验','ko'=>'하나의 레스토랑 — 다양한 경험','ru'=>'Один ресторан — много впечатлений','ar'=>'مطعم واحد — تجارب متعددة'],
        'ct_family'  => ['vi'=>'Gia đình','en'=>'Family','zh'=>'家庭','ko'=>'가족','ru'=>'Семья','ar'=>'العائلة'],
        'ct_group'   => ['vi'=>'Khách đoàn','en'=>'Tour groups','zh'=>'旅游团','ko'=>'단체','ru'=>'Тургруппы','ar'=>'المجموعات'],
        'ct_biz'     => ['vi'=>'Doanh nghiệp','en'=>'Business','zh'=>'企业','ko'=>'기업','ru'=>'Бизнес','ar'=>'الشركات'],
        'ct_intl'    => ['vi'=>'Khách quốc tế','en'=>'International','zh'=>'国际客人','ko'=>'외국인','ru'=>'Иностранцы','ar'=>'الدوليون'],
        'ct_party'   => ['vi'=>'Tiệc riêng','en'=>'Private events','zh'=>'私人宴会','ko'=>'프라이빗 행사','ru'=>'Частные мероприятия','ar'=>'الفعاليات الخاصة'],
        'ct_vip'     => ['vi'=>'Khách VIP','en'=>'VIP guests','zh'=>'VIP客人','ko'=>'VIP 고객','ru'=>'VIP-гости','ar'=>'ضيوف VIP'],

        // ---- GALLERY ----
        'gal_eyebrow' => ['vi'=>'Không gian Gánh Ngon','en'=>'The Gánh Ngon space','zh'=>'堪根空间','ko'=>'간 응온 공간','ru'=>'Пространство Gánh Ngon','ar'=>'مساحة Gánh Ngon'],
        'gal_h2'      => ['vi'=>'Sang trọng · Ấm cúng · Tinh tế','en'=>'Elegant · Warm · Refined','zh'=>'高雅 · 温馨 · 精致','ko'=>'우아함 · 따뜻함 · 세련됨','ru'=>'Элегантно · Уютно · Изысканно','ar'=>'أنيق · دافئ · راقٍ'],
        'gal_viewall' => ['vi'=>'Xem toàn bộ gallery','en'=>'View full gallery','zh'=>'查看全部图库','ko'=>'전체 갤러리 보기','ru'=>'Вся галерея','ar'=>'عرض المعرض الكامل'],

        // ---- TESTIMONIALS ----
        'tm_eyebrow' => ['vi'=>'Khách nói về Gánh Ngon','en'=>'What guests say','zh'=>'客人评价','ko'=>'고객 후기','ru'=>'Отзывы гостей','ar'=>'آراء الضيوف'],
        'tm_h2'      => ['vi'=>'Niềm tin từ thực khách','en'=>'Trusted by our guests','zh'=>'食客的信任','ko'=>'고객의 신뢰','ru'=>'Доверие наших гостей','ar'=>'ثقة ضيوفنا'],

        // ---- RESERVATION ----
        'rv_eyebrow' => ['vi'=>'Đặt bàn nhanh','en'=>'Quick reservation','zh'=>'快速预订','ko'=>'빠른 예약','ru'=>'Быстрая бронь','ar'=>'حجز سريع'],
        'rv_h2'      => ['vi'=>'Giữ bàn cho bữa ăn của bạn','en'=>'Reserve your table','zh'=>'为您预留餐桌','ko'=>'테이블을 예약하세요','ru'=>'Забронируйте столик','ar'=>'احجز طاولتك'],
        'rv_intro'   => ['vi'=>'Hotline/Zalo/WhatsApp 0979 232 233 – xác nhận nhanh trong giờ mở cửa 6:30–2:00','en'=>'Hotline/Zalo/WhatsApp 0979 232 233 – xác nhận nhanh trong giờ mở cửa 6:30–2:00','zh'=>'Hotline/Zalo/WhatsApp 0979 232 233 – xác nhận nhanh trong giờ mở cửa 6:30–2:00','ko'=>'Hotline/Zalo/WhatsApp 0979 232 233 – xác nhận nhanh trong giờ mở cửa 6:30–2:00','ru'=>'Hotline/Zalo/WhatsApp 0979 232 233 – xác nhận nhanh trong giờ mở cửa 6:30–2:00','ar'=>'Hotline/Zalo/WhatsApp 0979 232 233 – xác nhận nhanh trong giờ mở cửa 6:30–2:00'],
        'rv_name'    => ['vi'=>'Họ tên *','en'=>'Full name *','zh'=>'姓名 *','ko'=>'성함 *','ru'=>'Имя *','ar'=>'الاسم *'],
        'rv_phone'   => ['vi'=>'Số điện thoại / Zalo *','en'=>'Phone / WhatsApp *','zh'=>'电话 / Zalo *','ko'=>'전화 / Zalo *','ru'=>'Телефон / Zalo *','ar'=>'الهاتف / Zalo *'],
        'rv_note'    => ['vi'=>'Ghi chú: dị ứng, món chay, trẻ em, yêu cầu trang trí...','en'=>'Notes: allergies, vegetarian, children, decoration requests...','zh'=>'备注:过敏、素食、儿童、装饰要求...','ko'=>'메모: 알레르기, 채식, 어린이, 장식 요청...','ru'=>'Примечания: аллергии, вегетарианство, дети, декор...','ar'=>'ملاحظات: الحساسية، نباتي، أطفال، طلبات الديكور...'],

        // ---- FAQ ----
        'faq_eyebrow' => ['vi'=>'Câu hỏi thường gặp','en'=>'FAQ','zh'=>'常见问题','ko'=>'자주 묻는 질문','ru'=>'Частые вопросы','ar'=>'الأسئلة الشائعة'],
        'faq_h2'      => ['vi'=>'FAQ — Đặt bàn tại Gánh Ngon','en'=>'FAQ — Booking at Gánh Ngon','zh'=>'常见问题 — 堪根预订','ko'=>'FAQ — 간 응온 예약','ru'=>'FAQ — Бронь в Gánh Ngon','ar'=>'الأسئلة الشائعة — الحجز في Gánh Ngon'],
        'faq_intro'   => ['vi'=>'Câu hỏi thường gặp. Còn câu khác? Gọi hotline hoặc Zalo.','en'=>'Common questions. For anything else, call the hotline or Zalo.','zh'=>'常见问题。其他问题请致电或用 Zalo。','ko'=>'자주 묻는 질문. 다른 문의는 전화나 Zalo.','ru'=>'Частые вопросы. Остальное — по телефону или в Zalo.','ar'=>'أسئلة شائعة. لأي شيء آخر اتصلوا أو استخدموا Zalo.'],

        // ---- LOCATION ----
        'loc_eyebrow' => ['vi'=>'Chỉ đường đến Gánh Ngon','en'=>'Directions to Gánh Ngon','zh'=>'前往堪根','ko'=>'간 응온 오시는 길','ru'=>'Как добраться','ar'=>'الاتجاهات إلى Gánh Ngon'],
        'loc_addr_h'  => ['vi'=>'Địa chỉ','en'=>'Address','zh'=>'地址','ko'=>'주소','ru'=>'Адрес','ar'=>'العنوان'],
        'loc_contact_h'=> ['vi'=>'Liên hệ nhanh','en'=>'Quick contact','zh'=>'快速联系','ko'=>'빠른 연락','ru'=>'Быстрая связь','ar'=>'اتصال سريع'],
        'loc_maps'    => ['vi'=>'Mở Google Maps','en'=>'Open Google Maps','zh'=>'打开谷歌地图','ko'=>'구글 지도 열기','ru'=>'Открыть Google Maps','ar'=>'افتح خرائط جوجل'],
        'loc_call'    => ['vi'=>'Gọi hỏi đường','en'=>'Call for directions','zh'=>'致电问路','ko'=>'길 문의 전화','ru'=>'Позвонить','ar'=>'اتصل للاتجاهات'],
        'hours'       => ['vi'=>'Mở cửa hằng ngày 6:30–2:00. Bàn thường nhận trong ngày.','en'=>'Open daily 6:30–2:00. Regular tables the same day.','zh'=>'每天营业 6:30–2:00。普通桌可当天入座。','ko'=>'매일 6:30–2:00. 일반 테이블은 당일.','ru'=>'Ежедневно 6:30–2:00. Обычный стол в тот же день.','ar'=>'يوميًا 6:30–2:00. الطاولة العادية في اليوم نفسه.'],

        // ---- FOOTER ----
        'ft_cta_h'   => ['vi'=>'Hotline/Zalo/WhatsApp 0979 232 233 – xác nhận nhanh trong giờ mở cửa 6:30–2:00','en'=>'Hotline/Zalo/WhatsApp 0979 232 233 – xác nhận nhanh trong giờ mở cửa 6:30–2:00','zh'=>'Hotline/Zalo/WhatsApp 0979 232 233 – xác nhận nhanh trong giờ mở cửa 6:30–2:00','ko'=>'Hotline/Zalo/WhatsApp 0979 232 233 – xác nhận nhanh trong giờ mở cửa 6:30–2:00','ru'=>'Hotline/Zalo/WhatsApp 0979 232 233 – xác nhận nhanh trong giờ mở cửa 6:30–2:00','ar'=>'Hotline/Zalo/WhatsApp 0979 232 233 – xác nhận nhanh trong giờ mở cửa 6:30–2:00'],
        'ft_explore' => ['vi'=>'Khám phá','en'=>'Explore','zh'=>'探索','ko'=>'둘러보기','ru'=>'Обзор','ar'=>'استكشف'],
        'ft_connect' => ['vi'=>'Kết nối với Gánh Ngon','en'=>'Connect with Gánh Ngon','zh'=>'联系堪根','ko'=>'간 응온과 연결','ru'=>'Связаться с Gánh Ngon','ar'=>'تواصل مع Gánh Ngon'],
        'ft_contact' => ['vi'=>'Liên hệ trực tiếp','en'=>'Direct contact','zh'=>'直接联系','ko'=>'직접 연락','ru'=>'Прямая связь','ar'=>'اتصال مباشر'],
        'ft_addr'    => ['vi'=>'Địa chỉ & Giờ mở cửa','en'=>'Address & Hours','zh'=>'地址与营业时间','ko'=>'주소 & 영업시간','ru'=>'Адрес и часы','ar'=>'العنوان وساعات العمل'],
        'ft_policy'  => ['vi'=>'Chính sách','en'=>'Policies','zh'=>'政策','ko'=>'정책','ru'=>'Политика','ar'=>'السياسات'],
        'ft_partner_h'      => ['vi'=>'Đối tác','en'=>'Partners','zh'=>'合作','ko'=>'파트너','ru'=>'Партнёры','ar'=>'الشركاء'],
        'ft_partner_join'   => ['vi'=>'Hợp tác kinh doanh','en'=>'Business partnership','zh'=>'商务合作','ko'=>'비즈니스 제휴','ru'=>'Деловое сотрудничество','ar'=>'شراكة تجارية'],
        'ft_partner_portal' => ['vi'=>'Cổng đối tác','en'=>'Partner portal','zh'=>'合作伙伴入口','ko'=>'파트너 포털','ru'=>'Портал партнёра','ar'=>'بوابة الشريك'],
        'ft_partner_verify' => ['vi'=>'Xác minh đối tác','en'=>'Verify a partner','zh'=>'核验合作伙伴','ko'=>'파트너 확인','ru'=>'Проверка партнёра','ar'=>'التحقق من الشريك'],
        'ft_guide'   => ['vi'=>'Cẩm nang Phú Quốc','en'=>'Phu Quoc Guide','zh'=>'富国岛指南','ko'=>'푸꾸옥 가이드','ru'=>'Гид по Фукуоку','ar'=>'دليل فو كووك'],
        'ft_travel'  => ['vi'=>'Du lịch Phú Quốc Hub','en'=>'Phu Quoc Travel Hub','zh'=>'富国岛旅游中心','ko'=>'푸꾸옥 여행 허브','ru'=>'Хаб путешествий','ar'=>'مركز سفر فو كووك'],

        // ---- STICKY MOBILE BAR ----
        'sticky_call'   => ['vi'=>'Gọi','en'=>'Call','zh'=>'电话','ko'=>'전화','ru'=>'Звонок','ar'=>'اتصل'],
        'sticky_book'   => ['vi'=>'Đặt bàn','en'=>'Book','zh'=>'预订','ko'=>'예약','ru'=>'Бронь','ar'=>'احجز'],
        'sticky_dir'    => ['vi'=>'Chỉ đường','en'=>'Directions','zh'=>'路线','ko'=>'길찾기','ru'=>'Маршрут','ar'=>'الاتجاهات'],

        // ---- FOOTER DLPQ HUB ----
        'dlpq_eyebrow'  => ['vi'=>'Cẩm nang khám phá đảo ngọc','en'=>'Explore the Pearl Island','zh'=>'探索珍珠岛','ko'=>'진주섬 탐험','ru'=>'Откройте Жемчужный остров','ar'=>'استكشف جزيرة اللؤلؤ'],
        'dlpq_intro'    => ['vi'=>'Bài viết SEO đa ngôn ngữ về ẩm thực, điểm tham quan, lưu trú, di chuyển và kinh nghiệm du lịch Phú Quốc.','en'=>'Multi-language guides on food, attractions, stays, transport and travel tips for Phu Quoc.','zh'=>'关于富国岛美食、景点、住宿、交通和旅游贴士的多语言指南。','ko'=>'푸꾸옥의 음식, 명소, 숙박, 교통, 여행 팁 다국어 가이드.','ru'=>'Многоязычные гиды о еде, достопримечательностях, жилье и транспорте Фукуока.','ar'=>'أدلة متعددة اللغات عن الطعام والمعالم والإقامة والنقل في فو كووك.'],
        'dlpq_food'     => ['vi'=>'Ăn uống Phú Quốc','en'=>'Food & Dining','zh'=>'美食','ko'=>'음식','ru'=>'Еда','ar'=>'الطعام'],
        'dlpq_see'      => ['vi'=>'Điểm tham quan','en'=>'Attractions','zh'=>'景点','ko'=>'명소','ru'=>'Места','ar'=>'المعالم'],
        'dlpq_stay'     => ['vi'=>'Lưu trú','en'=>'Stays','zh'=>'住宿','ko'=>'숙박','ru'=>'Жильё','ar'=>'الإقامة'],
        'dlpq_move'     => ['vi'=>'Di chuyển','en'=>'Transport','zh'=>'交通','ko'=>'교통','ru'=>'Транспорт','ar'=>'النقل'],
        'dlpq_tips'     => ['vi'=>'Kinh nghiệm','en'=>'Tips','zh'=>'攻略','ko'=>'팁','ru'=>'Советы','ar'=>'نصائح'],
        'dlpq_all'      => ['vi'=>'Tất cả bài','en'=>'All articles','zh'=>'全部文章','ko'=>'전체 글','ru'=>'Все статьи','ar'=>'كل المقالات'],
        'dlpq_viewhub'  => ['vi'=>'Xem hub đầy đủ →','en'=>'View full hub →','zh'=>'查看完整 →','ko'=>'전체 보기 →','ru'=>'Весь хаб →','ar'=>'عرض الكل ←'],
    ];
}

/**
 * English durian-hotpot questions. Facts already published on the site:
 * blended into chicken broth, about one hour’s notice, same-day tables,
 * open 6:30–2:00, 113 Tran Hung Dao, prices quoted in the restaurant.
 * No smell score, price, or review is invented.
 */
function gn_en_durian_faqs() {
    return [
        ['q'=>'What is durian chicken hotpot?','a'=>'Ripe durian is blended into chicken broth at Gánh Ngon in Phu Quoc, Duong Dong. It is the signature dish, not a dessert and not whole segments dropped in the pot. The rest of the menu is 220+ Vietnamese dishes. Prices are quoted at the restaurant and are not listed on the website.'],
        ['q'=>'Does it smell like durian?','a'=>'The pot is chicken broth with ripe durian blended in, so durian is part of the dish and the aroma comes from that fruit in the broth. It is a savory hotpot, not a fruit dessert. Gánh Ngon does not publish a smell rating — call 0979 232 233 if you want to ask before you order.'],
        ['q'=>'How early should I order durian chicken hotpot?','a'=>'About one hour ahead so the pot is hot when you sit. A regular table can still be requested the same day. Open daily 6:30–2:00. Hotline/Zalo/WhatsApp 0979 232 233 – xác nhận nhanh trong giờ mở cửa 6:30–2:00'],
        ['q'=>'Where is Gánh Ngon?','a'=>'113 Tran Hung Dao, Phu Quoc, Duong Dong. 5 min from Night Market, 10 min from airport – Grab: Gánh Ngon 113 Tran Hung Dao.'],
    ];
}

/** Language-aware FAQ (used for rendering + FAQPage schema) */
function gn_faqs_i18n() {
    $lang = gn_cur_lang();
    $all = [
        'en' => [
            ['q'=>'What is Gánh Ngon?','a'=>'Gánh Ngon is an elegant Vietnamese specialty restaurant in Phu Quoc, Duong Dong, serving 220+ dishes from all three regions, with 10 private VIP rooms. Perfect for families, tour groups, businesses and international guests.'],
            ['q'=>'Does Gánh Ngon have VIP rooms?','a'=>'Yes. 10 private VIP rooms from 4 to 40 guests (Sen, Trúc, Đào, Mai, Cúc, Lan, Hồng, Nguyệt, Phượng, Signature), each with a dedicated concierge and soundproofing.'],
            ['q'=>'Do I need to book in advance?','a'=>'A regular table can be booked and used the same day, including if you landed in Phu Quoc today. Open daily 6:30–2:00. Durian chicken hotpot is better with about one hour’s notice. VIP rooms: about 24 hours. Groups of 20+: 48–72 hours. Call or Zalo 0979 232 233.'],
            ['q'=>'Do you accept tour groups?','a'=>'Yes. We serve groups of 10–80 with set menus, fast 60–90 min service, VAT invoice 8%, a dedicated coordinator and parking for 16/29/45-seat buses. Tour guides eat free.'],
            ['q'=>'Is there a menu for international guests?','a'=>'Yes. Our menu is in 4 languages (Vietnamese, English, Chinese, Russian) with easy-to-love dishes. We support WhatsApp, WeChat and Telegram booking.'],
            ['q'=>'Can I book a birthday or private party?','a'=>'Yes — birthdays, family reunions, corporate dinners, small galas and private events. Cake, flowers, backdrop, MC and DJ available on request.'],
            ['q'=>'Can I book a VIP room online?','a'=>'Yes. Use the form at /dat-ban/ and choose "VIP room", or call the hotline / Zalo / WhatsApp for a tailored recommendation.'],
            ['q'=>'Not sure what to order?','a'=>'Start with durian chicken hotpot, the signature dish. The rest of the menu is 220+ Vietnamese specialties. Prices are not listed online. The advisor at /ai-tu-van/ answers hours, directions and same-day tables, or call 0979 232 233.'],
        ],
        'zh' => [
            ['q'=>'堪根是什么餐厅?','a'=>'堪根是富国岛的高雅越南特色餐厅,提供三地220+道菜,设有10间私人VIP包厢。适合家庭、旅游团、企业和国际客人。'],
            ['q'=>'堪根有VIP包厢吗?','a'=>'有。10间私人VIP包厢,容纳4至40人,每间配专属管家和隔音设施。'],
            ['q'=>'需要提前预订吗?','a'=>'普通桌可以当天预订并入座,包括今天刚到富国岛。每天6:30–2:00。榴莲鸡火锅建议提前约1小时。VIP包厢约24小时。20人以上团体48–72小时。电话或Zalo 0979 232 233。'],
            ['q'=>'接待旅游团吗?','a'=>'接待。10-80人团体套餐,60-90分钟快速服务,8%增值税发票,专属协调员,提供16/29/45座大巴停车。导游免费用餐。'],
            ['q'=>'有国际客人菜单吗?','a'=>'有。菜单提供4种语言(越南语、英语、中文、俄语),有易于接受的菜品。支持WhatsApp、微信、Telegram预订。'],
            ['q'=>'可以预订生日或私人宴会吗?','a'=>'可以——生日、家庭聚会、企业晚宴、小型晚会和私人活动。可按需提供蛋糕、鲜花、背景板、主持和DJ。'],
            ['q'=>'可以在线预订VIP包厢吗?','a'=>'可以。使用 /dat-ban/ 表格选择"VIP包厢",或致电热线/Zalo/WhatsApp获取推荐。'],
            ['q'=>'不知道点什么?','a'=>'先点招牌榴莲鸡火锅,其余是220多道越南菜。网站不标价。/ai-tu-van/ 回答营业时间、路线和当天普通桌,或致电0979 232 233。'],
        ],
        'ko' => [
            ['q'=>'간 응온은 어떤 레스토랑인가요?','a'=>'간 응온은 푸꾸옥의 우아한 베트남 특선 레스토랑으로, 3개 지역 220여 종 요리와 10개 프라이빗 VIP룸을 갖추고 있습니다. 가족, 단체, 기업, 외국인 손님에 적합합니다.'],
            ['q'=>'VIP룸이 있나요?','a'=>'네. 4-40명 규모의 프라이빗 VIP룸 10개가 있으며, 각 룸마다 전담 컨시어지와 방음 시설을 갖추고 있습니다.'],
            ['q'=>'사전 예약이 필요한가요?','a'=>'일반 테이블은 오늘 푸꾸옥에 도착해도 당일 예약해 앉을 수 있습니다. 매일 6:30–2:00. 두리안 닭 샤브샤브는 약 1시간 전이 좋습니다. VIP룸은 약 24시간, 20명 이상은 48–72시간. 전화·Zalo 0979 232 233.'],
            ['q'=>'단체 손님을 받나요?','a'=>'네. 10-80명 단체 세트 메뉴, 60-90분 신속 서비스, 부가세 8% 영수증, 전담 코디네이터, 16/29/45인승 버스 주차 제공. 가이드 무료 식사.'],
            ['q'=>'외국인 손님용 메뉴가 있나요?','a'=>'네. 4개 언어(베트남어, 영어, 중국어, 러시아어) 메뉴를 제공합니다. WhatsApp, WeChat, Telegram 예약 지원.'],
            ['q'=>'생일이나 프라이빗 파티를 예약할 수 있나요?','a'=>'네 — 생일, 가족 모임, 기업 만찬, 소규모 갈라, 프라이빗 행사. 요청 시 케이크, 꽃, 배경, MC, DJ 제공.'],
            ['q'=>'VIP룸을 온라인으로 예약할 수 있나요?','a'=>'네. /dat-ban/ 양식에서 "VIP룸"을 선택하거나 핫라인/Zalo/WhatsApp으로 문의하세요.'],
            ['q'=>'무엇을 주문할지 모르겠다면?','a'=>'대표 요리인 두리안 닭 샤브샤브부터 시작하세요. 그 외 220여 가지 베트남 요리가 있습니다. 웹에 가격은 없습니다. /ai-tu-van/ 에서 영업시간, 길, 당일 테이블을 묻거나 0979 232 233으로 전화하세요.'],
        ],
        'ru' => [
            ['q'=>'Что такое Gánh Ngon?','a'=>'Gánh Ngon — изысканный вьетнамский ресторан на Фукуоке, 220+ блюд из трёх регионов и 10 приватных VIP-комнат. Идеально для семей, тургрупп, бизнеса и иностранных гостей.'],
            ['q'=>'Есть ли VIP-комнаты?','a'=>'Да. 10 приватных VIP-комнат от 4 до 40 гостей, каждая с персональным консьержем и звукоизоляцией.'],
            ['q'=>'Нужно ли бронировать заранее?','a'=>'Обычный стол можно забронировать и занять в день прилёта на Фукуок. Открыто ежедневно 6:30–2:00. Хотпот с дурианом лучше заказать примерно за час. VIP — около 24 часов. Группы от 20 человек — за 48–72 часа. Телефон и Zalo: 0979 232 233.'],
            ['q'=>'Принимаете ли группы?','a'=>'Да. Группы 10-80 человек, сет-меню, быстрое обслуживание 60-90 мин, счёт НДС 8%, персональный координатор, парковка для автобусов. Гиды едят бесплатно.'],
            ['q'=>'Есть ли меню для иностранцев?','a'=>'Да. Меню на 4 языках (вьетнамский, английский, китайский, русский). Поддержка бронирования через WhatsApp, WeChat, Telegram.'],
            ['q'=>'Можно ли заказать день рождения или частную вечеринку?','a'=>'Да — дни рождения, семейные встречи, корпоративы, небольшие гала и частные мероприятия. Торт, цветы, фон, MC и DJ по запросу.'],
            ['q'=>'Можно ли забронировать VIP-комнату онлайн?','a'=>'Да. Используйте форму на /dat-ban/ и выберите "VIP-комната", или позвоните на горячую линию / Zalo / WhatsApp.'],
            ['q'=>'Не знаете, что заказать?','a'=>'Начните с фирменного хотпота с дурианом. Дальше — 220+ вьетнамских блюд. Цен на сайте нет. /ai-tu-van/ отвечает про часы, маршрут и стол на сегодня, либо звоните 0979 232 233.'],
        ],
        'ar' => [
            ['q'=>'ما هو مطعم Gánh Ngon؟','a'=>'Gánh Ngon مطعم فيتنامي راقٍ في فو كووك يقدم أكثر من 220 طبقًا من المناطق الثلاث، مع 10 غرف VIP خاصة. مثالي للعائلات والمجموعات والشركات والضيوف الدوليين.'],
            ['q'=>'هل يوجد غرف VIP؟','a'=>'نعم. 10 غرف VIP خاصة تتسع من 4 إلى 40 ضيفًا، لكل منها كونسيرج مخصص وعزل صوتي.'],
            ['q'=>'هل أحتاج للحجز مسبقًا؟','a'=>'الطاولة العادية تُحجز وتُستخدم في يوم الوصول إلى فو كووك. مفتوح يوميًا 6:30–2:00. حلة الدجاج بالدوريان أفضل مع إشعار نحو ساعة. غرف VIP نحو 24 ساعة. المجموعات من 20 ضيفًا: 48–72 ساعة. الهاتف وZalo: 0979 232 233.'],
            ['q'=>'هل تستقبلون المجموعات السياحية؟','a'=>'نعم. مجموعات 10-80 ضيفًا، قوائم سيت، خدمة سريعة 60-90 دقيقة، فاتورة ضريبة 8%، منسق مخصص، مواقف للحافلات. المرشدون يأكلون مجانًا.'],
            ['q'=>'هل يوجد قائمة للضيوف الدوليين؟','a'=>'نعم. قائمتنا بأربع لغات (الفيتنامية، الإنجليزية، الصينية، الروسية). ندعم الحجز عبر WhatsApp وWeChat وTelegram.'],
            ['q'=>'هل يمكنني حجز حفلة عيد ميلاد أو حفلة خاصة؟','a'=>'نعم — أعياد الميلاد والتجمعات العائلية والعشاءات المؤسسية والحفلات الصغيرة والفعاليات الخاصة. الكعك والزهور والخلفية ومقدم الحفل وDJ متاحة عند الطلب.'],
            ['q'=>'هل يمكنني حجز غرفة VIP عبر الإنترنت؟','a'=>'نعم. استخدم النموذج في /dat-ban/ واختر "غرفة VIP"، أو اتصل بالخط الساخن / Zalo / WhatsApp.'],
            ['q'=>'لست متأكدًا مما تطلب؟','a'=>'ابدأوا بحلة الدجاج بالدوريان، طبق التوقيع. ثم أكثر من 220 طبقًا فيتناميًا. الأسعار غير منشورة. /ai-tu-van/ يجيب عن الساعات والطريق والطاولة اليوم، أو اتصلوا 0979 232 233.'],
        ],
    ];
    if ($lang !== 'vi' && isset($all[$lang])) {
        $pack = $all[$lang];
        if ($lang === 'en') $pack = array_merge(gn_en_durian_faqs(), $pack);
        return $pack;
    }
    return gn_get_faqs(); // VI default from functions.php
}

/** Language-aware testimonials */
function gn_testimonials_i18n() {
    $lang = gn_cur_lang();
    $all = [
        'en' => [
            ['t'=>'Booked a VIP room for our company group of 20. The Northern specialty set menu was generous, service professional, with an MC and a birthday backdrop for our boss. Worth recommending to partners.','a'=>'Trang Nguyen','r'=>'Business · HCMC'],
            ['t'=>'Family dinner for 3 generations. Hanoi pho, bun cha, rainbow jelly dessert — authentic Northern flavors. Spacious, with high chairs for kids and English-speaking staff.','a'=>'Minh Hoang','r'=>'Family · Hanoi'],
            ['t'=>'Best place in Phu Quoc for authentic Vietnamese food. Menu in English and Russian, staff helpful with allergy notes. Booked a private room for 12 — perfect for first-time visitors.','a'=>'Anastasia K.','r'=>'International · Moscow'],
        ],
        'zh' => [
            ['t'=>'为我们20人的公司团队预订了VIP包厢。北方特色套餐丰盛,服务专业,还为老板准备了主持和生日背景板。值得推荐给合作伙伴。','a'=>'Trang Nguyen','r'=>'企业 · 胡志明市'],
            ['t'=>'三代同堂的家庭晚餐。河内河粉、烤肉米线、三色甜品——正宗北方风味。空间宽敞,有儿童高脚椅和会说英语的员工。','a'=>'Minh Hoang','r'=>'家庭 · 河内'],
            ['t'=>'富国岛最正宗的越南菜。菜单有英文和俄文,员工对过敏提示很周到。为12人预订了包厢——非常适合初次来访的客人。','a'=>'Anastasia K.','r'=>'国际 · 莫斯科'],
        ],
        'ko' => [
            ['t'=>'회사 단체 20명을 위해 VIP룸을 예약했습니다. 북부 특선 세트 메뉴가 푸짐했고 서비스가 전문적이었으며, 사장님을 위한 MC와 생일 배경도 준비해 주셨습니다. 파트너에게 추천할 만합니다.','a'=>'Trang Nguyen','r'=>'기업 · 호치민'],
            ['t'=>'3대 가족 저녁 식사. 하노이 쌀국수, 분짜, 무지개 젤리 디저트 — 정통 북부 맛. 넓은 공간, 아동용 의자, 영어 가능 직원.','a'=>'Minh Hoang','r'=>'가족 · 하노이'],
            ['t'=>'푸꾸옥에서 가장 정통 베트남 음식. 영어와 러시아어 메뉴, 알레르기 메모에 친절한 직원. 12명 프라이빗룸 예약 — 첫 방문객에게 완벽합니다.','a'=>'Anastasia K.','r'=>'외국인 · 모스크바'],
        ],
        'ru' => [
            ['t'=>'Забронировали VIP-комнату для группы из 20 коллег. Сет-меню из северных деликатесов было щедрым, обслуживание профессиональное, с ведущим и фоном для дня рождения босса. Рекомендую партнёрам.','a'=>'Trang Nguyen','r'=>'Бизнес · Хошимин'],
            ['t'=>'Семейный ужин на 3 поколения. Ханойский фо, бунча, желейный десерт — аутентичные северные вкусы. Просторно, есть стульчики для детей и англоговорящий персонал.','a'=>'Minh Hoang','r'=>'Семья · Ханой'],
            ['t'=>'Лучшее место на Фукуоке для аутентичной вьетнамской кухни. Меню на английском и русском, персонал внимателен к аллергиям. Забронировали комнату на 12 — идеально для первого визита.','a'=>'Anastasia K.','r'=>'Иностранцы · Москва'],
        ],
        'ar' => [
            ['t'=>'حجزنا غرفة VIP لمجموعة شركتنا المكونة من 20 شخصًا. كانت قائمة التخصصات الشمالية سخية، والخدمة احترافية، مع مقدم حفل وخلفية عيد ميلاد لمديرنا. تستحق التوصية للشركاء.','a'=>'Trang Nguyen','r'=>'شركة · هو تشي منه'],
            ['t'=>'عشاء عائلي لثلاثة أجيال. فو هانوي، بون تشا، حلوى الجيلي — نكهات شمالية أصيلة. مساحة واسعة، كراسي للأطفال وموظفون يتحدثون الإنجليزية.','a'=>'Minh Hoang','r'=>'عائلة · هانوي'],
            ['t'=>'أفضل مكان في فو كووك للمأكولات الفيتنامية الأصيلة. قائمة بالإنجليزية والروسية، موظفون متعاونون مع ملاحظات الحساسية. حجزنا غرفة خاصة لـ12 شخصًا — مثالية للزوار لأول مرة.','a'=>'Anastasia K.','r'=>'دولي · موسكو'],
        ],
    ];
    if ($lang !== 'vi' && isset($all[$lang])) return $all[$lang];
    return [
        ['t'=>'Đặt phòng VIP cho đoàn 20 người công ty. Set menu đặc sản miền Bắc rất đầy đặn, phục vụ chuyên nghiệp, có MC và backdrop sinh nhật cho sếp. Đáng để giới thiệu cho đối tác.','a'=>'Trang Nguyễn','r'=>'Doanh nghiệp · TP.HCM'],
        ['t'=>'Ăn tối gia đình 3 thế hệ. Phở bò Hà Nội, bún chả, chè sương sa hạt lựu — đúng vị Bắc. Không gian rộng, có ghế cho trẻ em, lễ tân nói được tiếng Anh.','a'=>'Minh Hoàng','r'=>'Gia đình · Hà Nội'],
        ['t'=>'Best place in Phu Quoc for authentic Vietnamese food. Menu in English and Russian. Staff helpful with allergy notes. Booked private room for our group of 12.','a'=>'Anastasia K.','r'=>'International · Moscow'],
    ];
}
