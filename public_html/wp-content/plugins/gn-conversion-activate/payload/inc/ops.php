<?php
/* GN-CONV-GUARD */
/**
 * Pure rules for same-day tables and partner roles.
 * No WordPress calls — safe to run from tests.
 */
if (!function_exists('gn_response_promise')) {
    /** One guest-facing confirmation line. No two-minute claim. */
    function gn_response_promise() {
        return 'Hotline/Zalo/WhatsApp 0979 232 233 – xác nhận nhanh trong giờ mở cửa 6:30–2:00';
    }
}

if (!function_exists('gn_booking_time_slots')) {
    /**
     * Bookable times, shared by the homepage quick form and /dat-ban/.
     * Open 6:30–2:00; last slot is 01:00, every 30 minutes, no afternoon gap.
     *
     * @return string[]
     */
    function gn_booking_time_slots() {
        $slots = [];
        $h = 6;
        $m = 30;
        while (true) {
            $slots[] = sprintf('%02d:%02d', $h, $m);
            if ($h === 1 && $m === 0) break;
            $m += 30;
            if ($m === 60) {
                $m = 0;
                $h = ($h + 1) % 24;
            }
        }
        return $slots;
    }
}
if (!function_exists('gn_format_ref_code')) {
    function gn_format_ref_code($n) {
        $n = max(1, (int) $n);
        return 'GN-P' . str_pad((string) $n, 3, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('gn_normalize_partner_role')) {
    /**
     * Sales-agent role. Business type (hotel, guide, driver) stays on `type`.
     * Roles the portal understands: partner, affiliate, koc, kol.
     */
    function gn_normalize_partner_role($type) {
        $t = strtolower(trim((string) $type));
        $map = [
            'kol'            => 'kol',
            'blogger'        => 'kol',
            'koc'            => 'koc',
            'affiliate'      => 'affiliate',
            'ctv'            => 'affiliate',
            'cong-tac-vien'  => 'affiliate',
        ];
        return $map[$t] ?? 'partner';
    }
}

if (!function_exists('gn_partner_role_label')) {
    function gn_partner_role_label($role) {
        $labels = [
            'partner'   => 'Đối tác',
            'affiliate' => 'Affiliate',
            'koc'       => 'KOC',
            'kol'       => 'KOL',
        ];
        return $labels[$role] ?? 'Đối tác';
    }
}

if (!function_exists('gn_classify_booking')) {
    /**
     * Regular tables are accepted the same day. VIP and groups of 20+ are
     * still accepted, but flagged so staff know the preferred lead time.
     *
     * @param array{date:string,today:string,guests?:int,space?:string} $in
     * @return array{same_day:int,lead_class:string,staff_note:string}
     */
    function gn_classify_booking(array $in) {
        $date   = (string) ($in['date'] ?? '');
        $today  = (string) ($in['today'] ?? '');
        $guests = max(1, (int) ($in['guests'] ?? 1));
        $space  = strtolower(trim((string) ($in['space'] ?? '')));

        $vip = in_array($space, ['vip', 'phong-vip', 'phong_vip', 'signature'], true);
        $group_space = in_array($space, ['doan', 'tiec', 'group', 'event'], true);
        $group = $group_space || $guests >= 20;
        $same = ($date !== '' && $date === $today) ? 1 : 0;

        if ($group) {
            $lead = 'group_lead';
            $note = 'Đoàn từ 20 khách hoặc khu tiệc: ưu tiên báo trước 48–72 giờ. Yêu cầu vẫn được nhận, nhà hàng gọi xác nhận.';
        } elseif ($vip) {
            $lead = 'vip_lead';
            $note = 'Phòng VIP: ưu tiên báo trước 24 giờ. Nếu chưa giữ được phòng, chuyển khách sang bàn thường cùng ngày.';
        } elseif ($same) {
            $lead = 'same_day';
            $note = 'Bàn thường cùng ngày.';
        } else {
            $lead = 'advance';
            $note = '';
        }

        return [
            'same_day'   => $same,
            'lead_class' => $lead,
            'staff_note' => $note,
        ];
    }
}

if (!function_exists('gn_booking_fact_column_sql')) {
    /**
     * Real columns on sao_bookings. Notes may repeat a staff hint; these fields are the record.
     *
     * @return array<string,string> column => SQL type clause
     */
    function gn_booking_fact_column_sql() {
        return [
            'same_day'       => 'TINYINT(1) NOT NULL DEFAULT 0',
            'signature_dish' => 'TINYINT(1) NOT NULL DEFAULT 0',
            'lead_class'     => 'VARCHAR(20) NULL DEFAULT NULL',
        ];
    }
}

if (!function_exists('gn_partner_role_column_sql')) {
    /** @return array<string,string> */
    function gn_partner_role_column_sql() {
        return [
            'role' => "VARCHAR(20) NOT NULL DEFAULT 'partner'",
        ];
    }
}

if (!function_exists('gn_booking_facts')) {
    /**
     * Facts written on the booking row. `signature` is an explicit request
     * (form checkbox or the dish cookie), not a guess from the menu page.
     *
     * @param array{date?:string,today?:string,guests?:int,space?:string,signature?:bool} $in
     * @return array{same_day:int,signature_dish:int,lead_class:string,staff_note:string}
     */
    function gn_booking_facts(array $in) {
        $cls = gn_classify_booking($in);
        return [
            'same_day'       => (int) $cls['same_day'],
            'signature_dish' => !empty($in['signature']) ? 1 : 0,
            'lead_class'     => (string) $cls['lead_class'],
            'staff_note'     => (string) $cls['staff_note'],
        ];
    }
}

if (!function_exists('gn_booking_persist_fields')) {
    /**
     * The three columns every insert must include. Kept separate from notes.
     *
     * @param array{same_day?:int,signature_dish?:int,lead_class?:string} $facts
     * @return array{same_day:int,signature_dish:int,lead_class:string}
     */
    function gn_booking_persist_fields(array $facts) {
        return [
            'same_day'       => (int) ($facts['same_day'] ?? 0),
            'signature_dish' => (int) ($facts['signature_dish'] ?? 0),
            'lead_class'     => (string) ($facts['lead_class'] ?? ''),
        ];
    }
}

if (!function_exists('gn_booking_guest_message')) {
    /** Guest-facing confirmation. Does not claim a table is free. */
    function gn_booking_guest_message(array $facts) {
        $lead = (string) ($facts['lead_class'] ?? '');
        $promise = gn_response_promise();
        if ($lead === 'same_day') {
            $message = 'Đã nhận bàn thường hôm nay. ' . $promise . ' Chưa xác nhận còn bàn trước cuộc gọi đó.';
        } elseif ($lead === 'vip_lead') {
            $message = 'Đã nhận yêu cầu phòng VIP. Phòng riêng thường cần khoảng 24 giờ. Nếu cần ngồi hôm nay, nhà hàng đề nghị bàn thường và gọi lại. ' . $promise;
        } elseif ($lead === 'group_lead') {
            $message = 'Đã nhận yêu cầu đoàn. Đoàn từ 20 khách nên báo trước 48–72 giờ. Nhà hàng gọi để chốt. ' . $promise;
        } else {
            $message = 'Đã nhận yêu cầu đặt bàn. ' . $promise;
        }
        if (!empty($facts['signature_dish'])) {
            $message .= ' Lẩu gà sầu riêng: báo bếp khoảng 1 tiếng trước giờ ngồi.';
        }
        return $message;
    }
}

if (!function_exists('gn_partner_share_paths')) {
    /**
     * Two public links a partner hands to a guest. QR images point at these paths.
     *
     * @return array{table:string,signature:string}
     */
    function gn_partner_share_paths($ref) {
        $ref = trim((string) $ref);
        return [
            'table'     => '/p/' . $ref,
            'signature' => '/p/' . $ref . '/lau-ga',
        ];
    }
}

if (!function_exists('gn_partner_list_plan')) {
    /**
     * Admin list contract: 25 rows, search, and a sales-role filter.
     * `$q` is the literal needle (escape LIKE wildcards before calling).
     * `$total` is the count after the same filter, used only to clamp the page.
     *
     * @return array{where:string,args:array<int,string>,role:string,q:string,per:int,page:int,pages:int,offset:int}
     */
    function gn_partner_list_plan($q, $role, $page, $total, $per = 25) {
        $q = trim((string) $q);
        $role = strtolower(trim((string) $role));
        $roles = ['partner', 'affiliate', 'koc', 'kol'];
        $where = ['1=1'];
        $args = [];
        if ($q !== '') {
            $like = '%' . $q . '%';
            $where[] = '(contact LIKE %s OR company LIKE %s OR phone LIKE %s OR ref_code LIKE %s OR email LIKE %s)';
            array_push($args, $like, $like, $like, $like, $like);
        }
        $role_on = in_array($role, $roles, true) ? $role : '';
        if ($role_on !== '') {
            $where[] = 'role = %s';
            $args[] = $role_on;
        }
        $per = (int) $per;
        if ($per !== 25) $per = 25;
        $total = max(0, (int) $total);
        $pages = max(1, (int) ceil($total / $per));
        $page = max(1, (int) $page);
        if ($page > $pages) $page = $pages;
        return [
            'where'  => implode(' AND ', $where),
            'args'   => $args,
            'role'   => $role_on,
            'q'      => $q,
            'per'    => $per,
            'page'   => $page,
            'pages'  => $total === 0 ? 1 : $pages,
            'offset' => ($page - 1) * $per,
        ];
    }
}

if (!function_exists('gn_partner_page')) {
    /**
     * In-memory form of the admin list. Tests use this; the admin screen uses
     * gn_partner_list_plan() against the partners table. No sample people.
     *
     * @param array<int,array<string,mixed>> $rows
     * @return array{rows:array,total:int,where:string,args:array,role:string,q:string,per:int,page:int,pages:int,offset:int}
     */
    function gn_partner_page(array $rows, $q, $role, $page) {
        $q = trim((string) $q);
        $role = strtolower(trim((string) $role));
        $roles = ['partner', 'affiliate', 'koc', 'kol'];
        $use_role = in_array($role, $roles, true);
        $needle = mb_strtolower($q, 'UTF-8');
        $matched = [];
        foreach ($rows as $row) {
            if ($use_role && strtolower((string) ($row['role'] ?? '')) !== $role) continue;
            if ($needle !== '') {
                $hay = mb_strtolower(implode(' ', [
                    (string) ($row['contact'] ?? ''),
                    (string) ($row['company'] ?? ''),
                    (string) ($row['phone'] ?? ''),
                    (string) ($row['ref_code'] ?? ''),
                    (string) ($row['email'] ?? ''),
                ]), 'UTF-8');
                if (mb_strpos($hay, $needle, 0, 'UTF-8') === false) continue;
            }
            $matched[] = $row;
        }
        $plan = gn_partner_list_plan($q, $role, $page, count($matched));
        $plan['total'] = count($matched);
        $plan['rows'] = array_slice($matched, $plan['offset'], $plan['per']);
        return $plan;
    }
}

if (!function_exists('gn_partner_signup_fields')) {
    /**
     * Public registration. Required: name, phone/Zalo, and which way they
     * want to work. Email, area, and a channel link stay optional.
     * No bank account, tax id, password, volume, or long intro.
     *
     * @return array<int,array{name:string,required:bool,label:string,options?:array<string,string>}>
     */
    function gn_partner_signup_fields() {
        return [
            ['name' => 'full_name', 'required' => true, 'label' => 'Họ tên'],
            ['name' => 'phone', 'required' => true, 'label' => 'Số điện thoại / Zalo'],
            [
                'name' => 'partner_type',
                'required' => true,
                'label' => 'Bạn muốn hợp tác thế nào?',
                'options' => [
                    'gioi-thieu' => 'Giới thiệu bằng link/mã',
                    'doan-dia-phuong' => 'Đưa đoàn / hợp tác địa phương',
                    'tu-van' => 'Cần tư vấn thêm',
                ],
            ],
            ['name' => 'email', 'required' => false, 'label' => 'Email'],
            ['name' => 'area', 'required' => false, 'label' => 'Khu vực hoạt động'],
            ['name' => 'social_link', 'required' => false, 'label' => 'Website / Facebook / TikTok'],
        ];
    }
}

if (!function_exists('gn_partner_signup_field')) {
    function gn_partner_signup_field($name) {
        foreach (gn_partner_signup_fields() as $field) {
            if ($field['name'] === $name) return $field;
        }
        return null;
    }
}

if (!function_exists('gn_partner_signup_success_message')) {
    /** Shown only after a row is actually inserted. No approval clock, no kit. */
    function gn_partner_signup_success_message() {
        return 'Gánh Ngon đã nhận đăng ký, sẽ liên hệ qua số đã để lại.';
    }
}

if (!function_exists('gn_partner_duplicate_phone_reply')) {
    /**
     * A repeat of a known phone is not a new application.
     * Must not include that partner's ref code or portal link.
     *
     * @return array{message:string}
     */
    function gn_partner_duplicate_phone_reply() {
        return [
            'message' => 'Số điện thoại này đã được đăng ký. Gánh Ngon sẽ liên hệ lại.',
        ];
    }
}

if (!function_exists('gn_partner_register_actions')) {
    /**
     * Live HTML posts gn_partner_register. The theme used to post
     * sao_partner_login. Both names call the same save handler.
     *
     * @return array<string,string>
     */
    function gn_partner_register_actions() {
        return [
            'sao_partner_login' => 'gn_ajax_partner_register',
            'gn_partner_register' => 'gn_ajax_partner_register',
        ];
    }
}

if (!function_exists('gn_partner_footer_links')) {
    /**
     * Footer partner group, in this order. Not a store finder.
     *
     * @return array<int,array{label:string,path:string}>
     */
    function gn_partner_footer_links() {
        return [
            ['label' => 'ft_partner_join', 'path' => '/doi-tac/#dang-ky'],
            ['label' => 'ft_partner_portal', 'path' => '/cong-doi-tac/'],
            ['label' => 'ft_partner_verify', 'path' => '/xac-minh/'],
        ];
    }
}

if (!function_exists('gn_partner_footer_i18n')) {
    /**
     * @return array<string,array<string,string>>
     */
    function gn_partner_footer_i18n() {
        return [
            'ft_partner_h' => [
                'vi' => 'Đối tác',
                'en' => 'Partners',
                'zh' => '合作',
                'ko' => '파트너',
                'ru' => 'Партнёры',
                'ar' => 'الشركاء',
            ],
            'ft_partner_join' => [
                'vi' => 'Hợp tác kinh doanh',
                'en' => 'Business partnership',
                'zh' => '商务合作',
                'ko' => '비즈니스 제휴',
                'ru' => 'Деловое сотрудничество',
                'ar' => 'شراكة تجارية',
            ],
            'ft_partner_portal' => [
                'vi' => 'Cổng đối tác',
                'en' => 'Partner portal',
                'zh' => '合作伙伴入口',
                'ko' => '파트너 포털',
                'ru' => 'Портал партнёра',
                'ar' => 'بوابة الشريك',
            ],
            'ft_partner_verify' => [
                'vi' => 'Xác minh đối tác',
                'en' => 'Verify a partner',
                'zh' => '核验合作伙伴',
                'ko' => '파트너 확인',
                'ru' => 'Проверка партнёра',
                'ar' => 'التحقق من الشريك',
            ],
        ];
    }
}
