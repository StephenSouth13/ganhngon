<?php
/**
 * Local dish and booking advisor. Answers from restaurant facts only.
 * Does not invent prices, claim a table is free, or call an outside model.
 */
if (!function_exists('gn_advisor_fold')) {
    function gn_advisor_fold($text) {
        $text = mb_strtolower((string) $text, 'UTF-8');
        $map = [
            'à'=>'a','á'=>'a','ạ'=>'a','ả'=>'a','ã'=>'a','â'=>'a','ầ'=>'a','ấ'=>'a','ậ'=>'a','ẩ'=>'a','ẫ'=>'a','ă'=>'a','ằ'=>'a','ắ'=>'a','ặ'=>'a','ẳ'=>'a','ẵ'=>'a',
            'è'=>'e','é'=>'e','ẹ'=>'e','ẻ'=>'e','ẽ'=>'e','ê'=>'e','ề'=>'e','ế'=>'e','ệ'=>'e','ể'=>'e','ễ'=>'e',
            'ì'=>'i','í'=>'i','ị'=>'i','ỉ'=>'i','ĩ'=>'i',
            'ò'=>'o','ó'=>'o','ọ'=>'o','ỏ'=>'o','õ'=>'o','ô'=>'o','ồ'=>'o','ố'=>'o','ộ'=>'o','ổ'=>'o','ỗ'=>'o','ơ'=>'o','ờ'=>'o','ớ'=>'o','ợ'=>'o','ở'=>'o','ỡ'=>'o',
            'ù'=>'u','ú'=>'u','ụ'=>'u','ủ'=>'u','ũ'=>'u','ư'=>'u','ừ'=>'u','ứ'=>'u','ự'=>'u','ử'=>'u','ữ'=>'u',
            'ỳ'=>'y','ý'=>'y','ỵ'=>'y','ỷ'=>'y','ỹ'=>'y','đ'=>'d',
        ];
        return strtr($text, $map);
    }
}

if (!function_exists('gn_advisor_intent')) {
    function gn_advisor_intent($message) {
        $t = gn_advisor_fold($message);
        $has = function ($words) use ($t) {
            foreach ($words as $w) {
                if ($w !== '' && mb_strpos($t, $w, 0, 'UTF-8') !== false) return true;
            }
            return false;
        };
        if ($has(['gia', 'price', 'cost', 'how much', 'bao nhieu', '多少', '价格', '价钱', '가격', '얼마', 'сколько', 'цена', 'سعر', 'بكم', 'كم السعر'])) return 'price';
        if ($has(['sau rieng', 'durian', 'hotpot', 'lau ga', '榴莲', '두리안', 'дуриан', 'دوريان'])) return 'dish';
        if ($has(['gio mo', 'opening', 'open', 'hours', 'may gio', '营业', '几点', '영업', '시간', 'часы', 'открыт', 'ساعات', 'مفتوح'])) return 'hours';
        if ($has(['map', 'dia chi', 'address', 'chi duong', 'where', 'o dau', '地址', '怎么走', '지도', '어디', 'адрес', 'как добраться', 'عنوان', 'خرائط'])) return 'map';
        if ($has(['zalo', 'whatsapp', 'phone', 'hotline', 'goi', 'call', 'so dien thoai', '电话', '微信', '전화', 'телефон', 'هاتف', 'واتساب'])) return 'contact';
        if ($has(['vip', 'phong rieng', 'private room', '包厢', '프라이빗', 'vip-комнат', 'غرفة vip'])) return 'vip';
        if ($has(['doan', 'group', '20 khach', 'tiec', '团体', '단체', 'групп', 'مجموعة'])) return 'group';
        if ($has(['dat ban', 'book', 'reserv', 'hold', 'walk', 'hom nay', 'today', 'same day', 'ban thuong', '预订', '订位', '예약', 'бронь', 'заброни', 'حجز', 'طاولة'])) return 'book';
        if ($has(['menu', 'thuc don', 'mon an', '菜单', '메뉴', 'меню', 'قائمة'])) return 'menu';
        if ($has(['hello', 'hi', 'chao', 'xin chao', '你好', '안녕', 'здрав', 'مرحبا', 'السلام'])) return 'hello';
        return 'fallback';
    }
}

if (!function_exists('gn_advisor_catalog')) {
    function gn_advisor_catalog() {
        $dish = [
            'vi' => 'Lẩu gà sầu riêng',
            'en' => 'Durian chicken hotpot',
            'zh' => '榴莲鸡火锅',
            'ko' => '두리안 닭 샤브샤브',
            'ru' => 'Куриный хотпот с дурианом',
            'ar' => 'حلة الدجاج بالدوريان',
        ];
        $pack = [];
        foreach ($dish as $lang => $name) {
            $pack[$lang] = ['name' => $name];
        }
        $pack['vi'] += [
            'hello' => 'Gánh Ngon ở 113 Trần Hưng Đạo, Dương Đông, Phú Quốc. Món đặc trưng là lẩu gà sầu riêng. Bàn thường nhận trong ngày, mở cửa 6:30–2:00. Gọi hoặc Zalo 0979 232 233. Giá báo tại nhà hàng.',
            'dish'  => 'Món đặc trưng là lẩu gà sầu riêng: sầu riêng Phú Quốc hoà vào nước hầm gà, không phải món tráng miệng. Nên báo trước khoảng 1 tiếng để nồi nóng khi quý khách ngồi. Bàn thường vẫn nhận trong ngày. Giá báo tại nhà hàng, không niêm yết trên web.',
            'price' => 'Nhà hàng không niêm yết giá trên web. Mỗi bàn một kiểu, báo trực tiếp mới đúng. Gọi hoặc Zalo 0979 232 233, cho biết ngày, giờ, số khách và có muốn lẩu gà sầu riêng hay không.',
            'hours' => 'Mở cửa hằng ngày 6:30 sáng đến 2:00 sáng hôm sau, kể cả lễ. Bàn thường nhận trong ngày.',
            'map'   => '113 Trần Hưng Đạo, Dương Đông, Phú Quốc — trung tâm Dương Đông. Mở Google Maps: https://maps.google.com/?q=113+Tran+Hung+Dao+Duong+Dong+Phu+Quoc. Gọi 0979 232 233 nếu cần chỉ đường.',
            'contact' => 'Điện thoại và Zalo: 0979 232 233. WhatsApp: https://wa.me/84979232233. Email: contact@ganhngon.com.',
            'book'  => 'Bàn thường đặt và đến trong ngày được. Giữ bàn tại /dat-ban/ hoặc gọi 0979 232 233. Lẩu gà sầu riêng nên báo trước khoảng 1 tiếng. Nhà hàng không xác nhận “còn bàn” trước khi nhân viên gọi lại.',
            'vip'   => 'Phòng VIP (10 phòng, 4–40 khách) nên báo trước khoảng 24 giờ. Nếu cần ngồi hôm nay, hãy chọn bàn thường — đường đó không cần chờ phòng riêng.',
            'group' => 'Đoàn từ 20 khách và tiệc riêng nên báo trước 48–72 giờ. Bàn thường cho nhóm nhỏ vẫn nhận trong ngày. Gọi 0979 232 233.',
            'menu'  => 'Thực đơn có 220+ món Việt ba miền, ngoài lẩu gà sầu riêng. Menu tiếng Việt, Anh, Trung, Nga. Giá không hiện trên web.',
            'fallback' => 'Em giúp được việc đến trong hôm nay: bàn thường, đường đến 113 Trần Hưng Đạo, và món lẩu gà sầu riêng. Gọi 0979 232 233 hoặc mở /dat-ban/. Giá báo tại nhà hàng.',
        ];
        $pack['en'] += [
            'hello' => 'Gánh Ngon is at 113 Tran Hung Dao, Duong Dong, Phu Quoc. The signature dish is durian chicken hotpot. A regular table can be booked today. Open daily 6:30–2:00. Call or Zalo 0979 232 233. WhatsApp: https://wa.me/84979232233. Prices are quoted at the restaurant.',
            'dish'  => 'The signature dish is durian chicken hotpot: Phu Quoc durian blended into chicken broth, not a dessert. Tell the kitchen about an hour ahead so the pot is hot when you sit. A regular table can still be requested the same day. The price is quoted at the restaurant and is not listed on the website.',
            'price' => 'Prices are not listed on the website. Each table is different, so the team quotes directly. Call or WhatsApp 0979 232 233 with the date, time, party size, and whether you want durian chicken hotpot.',
            'hours' => 'Open every day from 6:30 to 2:00 the next morning, including holidays. Regular tables are accepted the same day.',
            'map'   => '113 Tran Hung Dao, Duong Dong, Phu Quoc — central Duong Dong. Google Maps: https://maps.google.com/?q=113+Tran+Hung+Dao+Duong+Dong+Phu+Quoc. Call 0979 232 233 if you need directions.',
            'contact' => 'Phone and Zalo: 0979 232 233. WhatsApp: https://wa.me/84979232233. Email: contact@ganhngon.com.',
            'book'  => 'A regular table can be reserved and used today. Use /dat-ban/ or call 0979 232 233. For durian chicken hotpot, about one hour’s notice helps the kitchen. The restaurant does not confirm a free table until staff call you back.',
            'vip'   => 'VIP rooms (10 rooms, 4–40 guests) are best requested about 24 hours ahead. If you need to sit today, book a regular table — that path does not wait on a private room.',
            'group' => 'Groups of 20 or more, and private events, are best requested 48–72 hours ahead. Smaller regular tables are still accepted the same day. Call 0979 232 233.',
            'menu'  => 'The menu has 220+ Vietnamese dishes from all three regions, besides durian chicken hotpot. Menus are in Vietnamese, English, Chinese and Russian. Prices are not shown on the website.',
            'fallback' => 'For an arrival today: a regular table, directions to 113 Tran Hung Dao, and durian chicken hotpot. Call 0979 232 233 or open /dat-ban/. Prices are quoted at the restaurant.',
        ];
        $pack['zh'] += [
            'hello' => '堪根在富国岛阳东陈兴道街113号。招牌菜是榴莲鸡火锅。普通桌可以当天预订。每天6:30–2:00营业。电话或Zalo：0979 232 233。WhatsApp：https://wa.me/84979232233。价格到店报价。',
            'dish'  => '招牌菜是榴莲鸡火锅：富国岛榴莲融入鸡汤，不是甜点。建议提前约1小时告诉厨房，坐下时锅已热。普通桌仍可当天申请。价格到店报价，网站不标价。',
            'price' => '网站不公开价格。每桌需求不同，需由店员直接报价。请致电或WhatsApp 0979 232 233，告知日期、时间、人数，以及是否要榴莲鸡火锅。',
            'hours' => '每天6:30营业至次日凌晨2:00，含节假日。普通桌可当天预订。',
            'map'   => '富国岛阳东陈兴道街113号，位于阳东中心。谷歌地图：https://maps.google.com/?q=113+Tran+Hung+Dao+Duong+Dong+Phu+Quoc。问路请打0979 232 233。',
            'contact' => '电话与Zalo：0979 232 233。WhatsApp：https://wa.me/84979232233。邮箱：contact@ganhngon.com。',
            'book'  => '普通桌可以当天预订并入座。请用 /dat-ban/ 或致电0979 232 233。榴莲鸡火锅建议提前约1小时。店员回电前，不会声称“还有空位”。',
            'vip'   => 'VIP包厢（10间，4–40人）建议提前约24小时。若今天就要坐，请订普通桌，不必等包厢。',
            'group' => '20人以上团体和私人宴会建议提前48–72小时。人数较少的普通桌仍可当天接待。请致电0979 232 233。',
            'menu'  => '除榴莲鸡火锅外，还有220多道三地越南菜。菜单有越南语、英语、中文和俄语。网站不标价。',
            'fallback' => '今天抵达：普通桌、陈兴道街113号路线，以及榴莲鸡火锅。请致电0979 232 233或打开 /dat-ban/。价格到店报价。',
        ];
        $pack['ko'] += [
            'hello' => '간 응온은 푸꾸옥 즈엉동 쩐흥다오 113번지입니다. 대표 요리는 두리안 닭 샤브샤브입니다. 일반 테이블은 당일 예약됩니다. 매일 6:30–2:00. 전화·Zalo 0979 232 233. WhatsApp: https://wa.me/84979232233. 가격은 매장에서 안내합니다.',
            'dish'  => '대표 요리는 두리안 닭 샤브샤브입니다. 푸꾸옥 두리안을 닭 육수에 섞으며, 디저트가 아닙니다. 앉으실 때 냄비가 뜨겁도록 약 1시간 전에 알려 주세요. 일반 테이블은 당일에도 가능합니다. 가격은 웹에 없고 매장에서 안내합니다.',
            'price' => '웹사이트에 가격을 올리지 않습니다. 테이블마다 달라 직원이 직접 안내합니다. 0979 232 233으로 날짜, 시간, 인원, 두리안 닭 샤브샤브 여부를 알려 주세요.',
            'hours' => '휴일 포함 매일 6:30부터 다음 날 2:00까지. 일반 테이블은 당일 예약됩니다.',
            'map'   => '푸꾸옥 즈엉동 쩐흥다오 113번지, 즈엉동 중심. 구글 지도: https://maps.google.com/?q=113+Tran+Hung+Dao+Duong+Dong+Phu+Quoc. 길 문의 0979 232 233.',
            'contact' => '전화·Zalo: 0979 232 233. WhatsApp: https://wa.me/84979232233. 이메일: contact@ganhngon.com.',
            'book'  => '일반 테이블은 당일 예약해 앉을 수 있습니다. /dat-ban/ 또는 0979 232 233. 두리안 닭 샤브샤브는 약 1시간 전 연락이 좋습니다. 직원이 다시 전화하기 전에는 빈 테이블이라고 확정하지 않습니다.',
            'vip'   => 'VIP룸(10실, 4–40명)은 약 24시간 전 요청이 좋습니다. 오늘 앉아야 하면 일반 테이블을 예약하세요.',
            'group' => '20명 이상 단체와 프라이빗 행사는 48–72시간 전이 좋습니다. 소규모 일반 테이블은 당일에도 받습니다. 0979 232 233.',
            'menu'  => '두리안 닭 샤브샤브 외에 3개 지역 베트남 요리 220여 가지가 있습니다. 메뉴는 베트남어, 영어, 중국어, 러시아어. 웹에 가격은 없습니다.',
            'fallback' => '오늘 도착: 일반 테이블, 쩐흥다오 113번지 길, 두리안 닭 샤브샤브. 0979 232 233 또는 /dat-ban/. 가격은 매장에서 안내합니다.',
        ];
        $pack['ru'] += [
            'hello' => 'Gánh Ngon: 113 Чан Хынг Дао, Зыонг Донг, Фукуок. Фирменное блюдо — куриный хотпот с дурианом. Обычный стол можно занять сегодня. Открыто ежедневно 6:30–2:00. Телефон и Zalo: 0979 232 233. WhatsApp: https://wa.me/84979232233. Цены называют в ресторане.',
            'dish'  => 'Фирменное блюдо — куриный хотпот с дурианом: дуриан Фукуока смешивают с куриным бульоном, это не десерт. Предупредите кухню примерно за час, чтобы горшок был горячим к посадке. Заявку на обычный стол принимают в тот же день. Цену называют в ресторане, на сайте её нет.',
            'price' => 'Цены на сайте не публикуют. Каждый стол разный, поэтому стоимость называют напрямую. Позвоните или напишите в WhatsApp 0979 232 233: дата, время, число гостей и нужен ли хотпот с дурианом.',
            'hours' => 'Открыто каждый день с 6:30 до 2:00 следующих суток, включая праздники. Обычный стол принимают в тот же день.',
            'map'   => '113 Чан Хынг Дао, Зыонг Донг, Фукуок — центр Зыонг Донга. Google Maps: https://maps.google.com/?q=113+Tran+Hung+Dao+Duong+Dong+Phu+Quoc. Навигация по телефону 0979 232 233.',
            'contact' => 'Телефон и Zalo: 0979 232 233. WhatsApp: https://wa.me/84979232233. Email: contact@ganhngon.com.',
            'book'  => 'Обычный стол можно забронировать и занять сегодня. Форма /dat-ban/ или звонок 0979 232 233. Для хотпота с дурианом лучше предупредить примерно за час. Свободный стол не подтверждают, пока сотрудник не перезвонит.',
            'vip'   => 'VIP-комнаты (10 залов, 4–40 гостей) лучше просить примерно за 24 часа. Если нужно сесть сегодня — бронируйте обычный стол.',
            'group' => 'Группы от 20 гостей и частные банкеты лучше просить за 48–72 часа. Небольшие обычные столы принимают в тот же день. Телефон 0979 232 233.',
            'menu'  => 'Кроме хотпота с дурианом, в меню 220+ вьетнамских блюд из трёх регионов. Меню на вьетнамском, английском, китайском и русском. Цен на сайте нет.',
            'fallback' => 'Если вы приезжаете сегодня: обычный стол, маршрут до 113 Чан Хынг Дао и хотпот с дурианом. Звоните 0979 232 233 или откройте /dat-ban/. Цены называют в ресторане.',
        ];
        $pack['ar'] += [
            'hello' => 'Gánh Ngon في 113 شارع تران هونغ داو، دونغ دونغ، فو كووك. طبق التوقيع هو حلة الدجاج بالدوريان. الطاولة العادية تُحجز في اليوم نفسه. مفتوح يوميًا 6:30–2:00. الهاتف وZalo: 0979 232 233. واتساب: https://wa.me/84979232233. الأسعار في المطعم.',
            'dish'  => 'طبق التوقيع هو حلة الدجاج بالدوريان: دوريان فو كووك يُمزج بمرق الدجاج، وليس حلوى. أخبروا المطبخ قبل نحو ساعة ليكون القدر ساخنًا عند الجلوس. يمكن طلب الطاولة العادية في اليوم نفسه. السعر في المطعم وغير منشور على الموقع.',
            'price' => 'الأسعار غير منشورة على الموقع. كل طاولة مختلفة، لذلك يخبركم الفريق مباشرة. اتصلوا أو راسلوا واتساب 0979 232 233 مع التاريخ والوقت وعدد الضيوف وهل تريدون حلة الدجاج بالدوريان.',
            'hours' => 'مفتوح كل يوم من 6:30 حتى 2:00 من صباح اليوم التالي، بما في ذلك العطل. الطاولة العادية تُقبل في اليوم نفسه.',
            'map'   => '113 تران هونغ داو، دونغ دونغ، فو كووك — وسط دونغ دونغ. خرائط جوجل: https://maps.google.com/?q=113+Tran+Hung+Dao+Duong+Dong+Phu+Quoc. للاتجاهات اتصلوا 0979 232 233.',
            'contact' => 'الهاتف وZalo: 0979 232 233. واتساب: https://wa.me/84979232233. البريد: contact@ganhngon.com.',
            'book'  => 'يمكن حجز طاولة عادية والجلوس اليوم. النموذج /dat-ban/ أو الاتصال 0979 232 233. حلة الدجاج بالدوريان أفضل مع إشعار نحو ساعة. لا يؤكد المطعم طاولة فارغة قبل أن يتصل الموظف.',
            'vip'   => 'غرف VIP (10 غرف، 4–40 ضيفًا) يُفضَّل طلبها قبل نحو 24 ساعة. إذا أردتم الجلوس اليوم فاحجزوا طاولة عادية.',
            'group' => 'المجموعات من 20 ضيفًا والحفلات الخاصة يُفضَّل طلبها قبل 48–72 ساعة. الطاولات العادية الأصغر تُقبل في اليوم نفسه. اتصلوا 0979 232 233.',
            'menu'  => 'إلى جانب حلة الدجاج بالدوريان هناك أكثر من 220 طبقًا فيتناميًا من ثلاث مناطق. القائمة بالفيتنامية والإنجليزية والصينية والروسية. الأسعار غير ظاهرة على الموقع.',
            'fallback' => 'للوصول اليوم: طاولة عادية، الطريق إلى 113 تران هونغ داو، وحلة الدجاج بالدوريان. اتصلوا 0979 232 233 أو افتحوا /dat-ban/. الأسعار في المطعم.',
        ];
        return $pack;
    }
}

if (!function_exists('gn_advisor_asserts_open_table')) {
    /**
     * True when a reply promises a free table. Denial sentences
     * ("does not confirm a free table") are allowed.
     */
    function gn_advisor_asserts_open_table($text) {
        $t = mb_strtolower((string) $text, 'UTF-8');
        $denial = (bool) preg_match(
            '/không xác nhận|chưa xác nhận|does not confirm|do not confirm|not confirm|不会声称|확정하지|не подтвержда|لا يؤكد/u',
            $t
        );
        $needles = [
            'still available', 'tables are available', 'table is available',
            'table is free', 'tables are free', 'a free table', 'we have a table',
            'còn bàn', 'con ban', 'bàn trống', '还有空位', '有空位',
            'доступен', 'доступна', 'свободен', 'متاح', '빈자리 있', '빈 테이블이',
        ];
        foreach ($needles as $n) {
            if (mb_strpos($t, $n, 0, 'UTF-8') === false) continue;
            if ($denial) continue;
            return true;
        }
        return false;
    }
}

if (!function_exists('gn_advisor_reply')) {
    function gn_advisor_reply($message, $lang = 'vi') {
        $lang = in_array($lang, ['vi', 'en', 'zh', 'ko', 'ru', 'ar'], true) ? $lang : 'vi';
        $intent = gn_advisor_intent($message);
        $cat = gn_advisor_catalog();
        $row = $cat[$lang];
        if (!isset($row[$intent])) $intent = 'fallback';
        return $row[$intent];
    }
}
