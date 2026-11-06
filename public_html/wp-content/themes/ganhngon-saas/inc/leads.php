<?php
/**
 * Private leads (gn_lead) plus an optional bot email and webhook.
 * Storage or notification failures never stop the booking or the note email.
 */
if (!defined('ABSPATH')) exit;

function gns_lead_statuses() {
    return [
        'moi' => 'Mới',
        'da-lien-he' => 'Đã liên hệ',
        'chot' => 'Chốt',
        'huy' => 'Huỷ',
    ];
}

function gns_lead_status_label($key) {
    $all = gns_lead_statuses();
    $key = sanitize_key((string) $key);
    return isset($all[$key]) ? $all[$key] : $all['moi'];
}

add_action('init', function () {
    register_post_type('gn_lead', [
        'labels' => [
            'name' => 'Lead khách',
            'singular_name' => 'Lead',
            'menu_name' => 'Lead khách',
            'add_new_item' => 'Thêm lead',
            'edit_item' => 'Sửa lead',
            'search_items' => 'Tìm lead',
            'not_found' => 'Chưa có lead',
        ],
        'public' => false,
        'publicly_queryable' => false,
        'exclude_from_search' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_rest' => false,
        'menu_icon' => 'dashicons-id',
        'menu_position' => 26,
        'capability_type' => 'post',
        'map_meta_cap' => true,
        'hierarchical' => false,
        'supports' => ['title'],
        'rewrite' => false,
        'query_var' => false,
        'has_archive' => false,
    ]);
}, 5);

function gns_lead_meta_keys() {
    return [
        'gns_source',
        'gns_name',
        'gns_phone',
        'gns_email',
        'gns_guest_group',
        'gns_guest_count',
        'gns_area',
        'gns_when',
        'gns_message',
        'gns_status',
    ];
}

/**
 * @param array $data source, name, phone, email, guest_group, guest_count, area, when, message
 * @return int Post ID, or 0 on failure.
 */
function gns_store_lead($data) {
    $data = is_array($data) ? $data : [];
    $name = sanitize_text_field($data['name'] ?? '');
    $phone = sanitize_text_field($data['phone'] ?? '');
    if ($name === '' && $phone === '') return 0;

    $groups = function_exists('gns_guest_groups') ? gns_guest_groups() : [];
    $areas = function_exists('gns_guest_areas') ? gns_guest_areas() : [];
    $group = sanitize_key($data['guest_group'] ?? '');
    if (!isset($groups[$group])) $group = '';
    $area = sanitize_key($data['area'] ?? '');
    if (!isset($areas[$area])) $area = '';
    $count = absint($data['guest_count'] ?? 0);
    if ($count > 5000) $count = 0;
    $status = sanitize_key($data['status'] ?? 'moi');
    if (!isset(gns_lead_statuses()[$status])) $status = 'moi';

    $row = [
        'gns_source' => sanitize_text_field($data['source'] ?? ''),
        'gns_name' => $name,
        'gns_phone' => $phone,
        'gns_email' => sanitize_email($data['email'] ?? ''),
        'gns_guest_group' => $group,
        'gns_guest_count' => (string) $count,
        'gns_area' => $area,
        'gns_when' => sanitize_text_field($data['when'] ?? ''),
        'gns_message' => mb_substr(sanitize_textarea_field($data['message'] ?? ''), 0, 5000),
        'gns_status' => $status,
    ];

    $id = wp_insert_post([
        'post_type' => 'gn_lead',
        'post_status' => 'private',
        'post_title' => $name !== '' ? $name : $phone,
        'post_content' => $row['gns_message'],
    ], true);
    if (is_wp_error($id) || !$id) return 0;

    foreach ($row as $key => $value) {
        update_post_meta($id, $key, $value);
    }
    gns_notify_lead((int) $id, $row);
    return (int) $id;
}

function gns_store_lead_safe($data) {
    try {
        return gns_store_lead($data);
    } catch (Throwable $e) {
        return 0;
    }
}

function gns_lead_group_label($key) {
    $groups = function_exists('gns_guest_groups') ? gns_guest_groups() : [];
    $key = sanitize_key((string) $key);
    return isset($groups[$key]) ? $groups[$key][0] : '';
}

function gns_lead_area_label($key) {
    $areas = function_exists('gns_guest_areas') ? gns_guest_areas() : [];
    $key = sanitize_key((string) $key);
    return isset($areas[$key]) ? $areas[$key] : '';
}

function gns_lead_pairs($id, $row) {
    $group = gns_lead_group_label($row['gns_guest_group']);
    $count = (int) $row['gns_guest_count'];
    return [
        'Nguồn' => $row['gns_source'] !== '' ? $row['gns_source'] : '-',
        'Họ tên' => $row['gns_name'] !== '' ? $row['gns_name'] : '-',
        'SĐT' => $row['gns_phone'] !== '' ? $row['gns_phone'] : '-',
        'Email' => $row['gns_email'] !== '' ? $row['gns_email'] : '-',
        'Nhóm khách' => $group !== '' ? $group : '-',
        'Số khách' => $count > 0 ? (string) $count : '-',
        'Khu vực' => gns_lead_area_label($row['gns_area']) !== '' ? gns_lead_area_label($row['gns_area']) : '-',
        'Ngày giờ' => $row['gns_when'] !== '' ? $row['gns_when'] : '-',
        'Nội dung' => $row['gns_message'] !== '' ? $row['gns_message'] : '-',
        'Trạng thái' => gns_lead_status_label($row['gns_status']),
        'Lead' => (string) (int) $id,
    ];
}

function gns_notify_lead($id, $row) {
    try {
        $group = gns_lead_group_label($row['gns_guest_group']);
        $count = (int) $row['gns_guest_count'];
        $form = $row['gns_source'] !== '' ? $row['gns_source'] : '-';
        $subject = '[GN-LEAD] ' . ($group !== '' ? $group : '-') . ' | ' . ($count > 0 ? $count : '-') . ' khách | ' . $form;
        $lines = '';
        foreach (gns_lead_pairs($id, $row) as $key => $value) {
            $lines .= $key . ': ' . $value . "\n";
        }

        $to = sanitize_email((string) get_option('gns_lead_email', ''));
        if ($to !== '' && is_email($to)) {
            wp_mail($to, $subject, $lines, ['Content-Type: text/plain; charset=UTF-8']);
        }

        $url = esc_url_raw((string) get_option('gns_lead_webhook', ''));
        if ($url !== '') {
            $payload = [
                'lead_id' => (int) $id,
                'source' => $row['gns_source'],
                'name' => $row['gns_name'],
                'phone' => $row['gns_phone'],
                'email' => $row['gns_email'],
                'guest_group' => $row['gns_guest_group'],
                'guest_group_label' => $group,
                'guest_count' => $count,
                'area' => $row['gns_area'],
                'area_label' => gns_lead_area_label($row['gns_area']),
                'when' => $row['gns_when'],
                'message' => $row['gns_message'],
                'status' => $row['gns_status'],
            ];
            $headers = ['Content-Type' => 'application/json; charset=utf-8'];
            $secret = sanitize_text_field((string) get_option('gns_lead_secret', ''));
            if ($secret !== '') $headers['X-GN-Secret'] = $secret;
            wp_remote_post($url, [
                'timeout' => 5,
                'blocking' => false,
                'headers' => $headers,
                'body' => wp_json_encode($payload),
            ]);
        }
    } catch (Throwable $e) {
        return;
    }
}

function gns_lead_from_sao_post() {
    if (!empty($_POST['gns_lead_done']) || !empty($_POST['website'])) return;
    $name = sanitize_text_field(wp_unslash($_POST['customer_name'] ?? $_POST['name'] ?? ''));
    $phone = sanitize_text_field(wp_unslash($_POST['customer_phone'] ?? $_POST['phone'] ?? ''));
    if ($name === '' || $phone === '') return;

    $date = sanitize_text_field(wp_unslash($_POST['booking_date'] ?? $_POST['date'] ?? ''));
    if ($date !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) return;

    $guest = function_exists('gns_guest_fields_from_post') ? gns_guest_fields_from_post() : ['group' => '', 'count' => 0, 'area' => ''];
    $count = $guest['count'] > 0 ? $guest['count'] : absint(wp_unslash($_POST['guests'] ?? 0));
    $time = sanitize_text_field(wp_unslash($_POST['booking_time'] ?? $_POST['time'] ?? ''));
    $when = trim($date . ($time !== '' ? ' ' . $time : ''));
    $need = sanitize_text_field(wp_unslash($_POST['need'] ?? ''));
    $note = sanitize_textarea_field(wp_unslash($_POST['note'] ?? $_POST['notes'] ?? ''));
    $message = trim($need . ($need !== '' && $note !== '' ? "\n" : '') . $note);
    $source = sanitize_text_field(wp_unslash($_POST['source'] ?? ''));
    if ($source === '') $source = 'dat-ban';

    gns_store_lead_safe([
        'source' => $source,
        'name' => $name,
        'phone' => $phone,
        'email' => sanitize_email(wp_unslash($_POST['customer_email'] ?? $_POST['email'] ?? '')),
        'guest_group' => $guest['group'],
        'guest_count' => $count,
        'area' => $guest['area'],
        'when' => $when,
        'message' => $message,
    ]);
    $_POST['gns_lead_done'] = '1';
}
add_action('wp_ajax_sao_create_booking', 'gns_lead_from_sao_post', 0);
add_action('wp_ajax_nopriv_sao_create_booking', 'gns_lead_from_sao_post', 0);

add_filter('manage_gn_lead_posts_columns', function ($columns) {
    return [
        'cb' => $columns['cb'] ?? '',
        'title' => 'Họ tên',
        'gns_phone' => 'SĐT',
        'gns_group' => 'Nhóm khách',
        'gns_count' => 'Số khách',
        'gns_area' => 'Khu vực',
        'gns_when' => 'Ngày giờ',
        'gns_source' => 'Nguồn',
        'gns_status' => 'Trạng thái',
        'date' => 'Nhận lúc',
    ];
});

add_action('manage_gn_lead_posts_custom_column', function ($column, $post_id) {
    $map = [
        'gns_phone' => 'gns_phone',
        'gns_count' => 'gns_guest_count',
        'gns_when' => 'gns_when',
        'gns_source' => 'gns_source',
    ];
    if (isset($map[$column])) {
        $value = (string) get_post_meta($post_id, $map[$column], true);
        echo esc_html($value !== '' ? $value : '—');
        return;
    }
    if ($column === 'gns_group') {
        $label = gns_lead_group_label(get_post_meta($post_id, 'gns_guest_group', true));
        echo esc_html($label !== '' ? $label : '—');
        return;
    }
    if ($column === 'gns_area') {
        $label = gns_lead_area_label(get_post_meta($post_id, 'gns_area', true));
        echo esc_html($label !== '' ? $label : '—');
        return;
    }
    if ($column === 'gns_status') {
        echo esc_html(gns_lead_status_label(get_post_meta($post_id, 'gns_status', true)));
    }
}, 10, 2);

add_action('restrict_manage_posts', function ($post_type) {
    if ($post_type !== 'gn_lead') return;
    $group = isset($_GET['gns_group']) ? sanitize_key(wp_unslash($_GET['gns_group'])) : '';
    $status = isset($_GET['gns_status']) ? sanitize_key(wp_unslash($_GET['gns_status'])) : '';
    echo '<select name="gns_group"><option value="">Mọi nhóm khách</option>';
    if (function_exists('gns_guest_groups')) {
        foreach (gns_guest_groups() as $key => $g) {
            echo '<option value="' . esc_attr($key) . '"' . selected($group, $key, false) . '>' . esc_html($g[0]) . '</option>';
        }
    }
    echo '</select>';
    echo '<select name="gns_status"><option value="">Mọi trạng thái</option>';
    foreach (gns_lead_statuses() as $key => $label) {
        echo '<option value="' . esc_attr($key) . '"' . selected($status, $key, false) . '>' . esc_html($label) . '</option>';
    }
    echo '</select>';
});

add_action('pre_get_posts', function ($query) {
    if (!is_admin() || !$query->is_main_query()) return;
    if ($query->get('post_type') !== 'gn_lead') return;
    $meta = [];
    if (!empty($_GET['gns_group'])) {
        $meta[] = ['key' => 'gns_guest_group', 'value' => sanitize_key(wp_unslash($_GET['gns_group']))];
    }
    if (!empty($_GET['gns_status'])) {
        $meta[] = ['key' => 'gns_status', 'value' => sanitize_key(wp_unslash($_GET['gns_status']))];
    }
    if ($meta) $query->set('meta_query', $meta);
});

add_action('add_meta_boxes', function () {
    add_meta_box('gns_lead_box', 'Nội dung lead', 'gns_lead_meta_box', 'gn_lead', 'normal', 'high');
});

function gns_lead_meta_box($post) {
    wp_nonce_field('gns_lead_status', 'gns_lead_status_nonce');
    $status = (string) get_post_meta($post->ID, 'gns_status', true);
    if ($status === '') $status = 'moi';
    echo '<p><label for="gns_status">Trạng thái</label><br><select name="gns_status" id="gns_status">';
    foreach (gns_lead_statuses() as $key => $label) {
        echo '<option value="' . esc_attr($key) . '"' . selected($status, $key, false) . '>' . esc_html($label) . '</option>';
    }
    echo '</select></p>';
    $fields = [
        'gns_source' => 'Nguồn',
        'gns_phone' => 'SĐT',
        'gns_email' => 'Email',
        'gns_when' => 'Ngày giờ',
        'gns_message' => 'Nội dung',
    ];
    echo '<table class="widefat striped"><tbody>';
    $group = gns_lead_group_label(get_post_meta($post->ID, 'gns_guest_group', true));
    $area = gns_lead_area_label(get_post_meta($post->ID, 'gns_area', true));
    echo '<tr><th>Nhóm khách</th><td>' . esc_html($group !== '' ? $group : '—') . '</td></tr>';
    echo '<tr><th>Số khách</th><td>' . esc_html((string) get_post_meta($post->ID, 'gns_guest_count', true)) . '</td></tr>';
    echo '<tr><th>Khu vực</th><td>' . esc_html($area !== '' ? $area : '—') . '</td></tr>';
    foreach ($fields as $key => $label) {
        $value = (string) get_post_meta($post->ID, $key, true);
        echo '<tr><th>' . esc_html($label) . '</th><td>' . nl2br(esc_html($value !== '' ? $value : '—')) . '</td></tr>';
    }
    echo '</tbody></table>';
}

add_action('save_post_gn_lead', function ($post_id) {
    if (!isset($_POST['gns_lead_status_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['gns_lead_status_nonce'])), 'gns_lead_status')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    $status = sanitize_key(wp_unslash($_POST['gns_status'] ?? ''));
    if (!isset(gns_lead_statuses()[$status])) return;
    update_post_meta($post_id, 'gns_status', $status);
});

add_action('rest_api_init', function () {
    register_rest_route('gns/v1', '/leads', [
        'methods' => 'GET',
        'permission_callback' => function () {
            return current_user_can('edit_posts');
        },
        'callback' => 'gns_rest_leads',
    ]);
});

function gns_rest_leads() {
    $posts = get_posts([
        'post_type' => 'gn_lead',
        'post_status' => 'private',
        'posts_per_page' => 100,
        'orderby' => 'date',
        'order' => 'DESC',
    ]);
    $out = [];
    foreach ($posts as $post) {
        $row = [];
        foreach (gns_lead_meta_keys() as $key) {
            $row[$key] = (string) get_post_meta($post->ID, $key, true);
        }
        $out[] = [
            'id' => $post->ID,
            'source' => $row['gns_source'],
            'name' => $row['gns_name'],
            'phone' => $row['gns_phone'],
            'email' => $row['gns_email'],
            'guest_group' => $row['gns_guest_group'],
            'guest_group_label' => gns_lead_group_label($row['gns_guest_group']),
            'guest_count' => (int) $row['gns_guest_count'],
            'area' => $row['gns_area'],
            'area_label' => gns_lead_area_label($row['gns_area']),
            'when' => $row['gns_when'],
            'message' => $row['gns_message'],
            'status' => $row['gns_status'] !== '' ? $row['gns_status'] : 'moi',
            'received' => $post->post_date,
        ];
    }
    return rest_ensure_response($out);
}
