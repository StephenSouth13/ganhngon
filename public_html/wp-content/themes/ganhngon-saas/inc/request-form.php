<?php
/**
 * One request form for /dat-ban/, /lien-he/ and /khach-doan-tiec-rieng/.
 * The handler writes a Sao booking and a gn_lead. Old endpoints stay registered.
 */
if (!defined('ABSPATH')) exit;

function gns_party_types() {
    return [
        'sinh-nhat' => 'Sinh nhật',
        'thoi-noi' => 'Thôi nôi',
        'mung-tho' => 'Mừng thọ',
        'ky-niem' => 'Kỷ niệm',
        'khac' => 'Khác',
    ];
}

function gns_request_group_from_query() {
    if (!isset($_GET['nhom'])) return '';
    $raw = sanitize_title(wp_unslash($_GET['nhom']));
    $aliases = [
        'le' => 'khach-le',
        'khach-le' => 'khach-le',
        'gia-dinh' => 'gia-dinh',
        'doan' => 'doan-truc-tiep',
        'doan-truc-tiep' => 'doan-truc-tiep',
        'doanh-nghiep' => 'doanh-nghiep',
        'tiec' => 'dat-tiec',
        'dat-tiec' => 'dat-tiec',
        'khach-dat-tiec' => 'dat-tiec',
        'khach-doan' => 'doan-truc-tiep',
        'khach-doanh-nghiep' => 'doanh-nghiep',
        'khach-gia-dinh' => 'gia-dinh',
        'doi-tac' => 'doi-tac-dua-khach',
        'doi-tac-dua-khach' => 'doi-tac-dua-khach',
    ];
    $key = isset($aliases[$raw]) ? $aliases[$raw] : '';
    $groups = gns_guest_groups();
    return isset($groups[$key]) ? $key : '';
}

function gns_request_default_group($context) {
    $from_query = gns_request_group_from_query();
    if ($from_query !== '') return $from_query;
    if (function_exists('gns_legacy_request_spec')) {
        $spec = gns_legacy_request_spec();
        if (is_array($spec)) return $spec[1];
    }
    if ($context === 'dat-ban') return 'khach-le';
    if ($context === 'tiec') return 'dat-tiec';
    return '';
}

function gns_request_source($context) {
    if ($context === 'tiec') return 'khach-doan-tiec-rieng';
    if ($context === 'dat-ban' || $context === 'lien-he') return $context;
    return 'lien-he';
}

function gns_request_form_html($context = 'lien-he') {
    $context = sanitize_key((string) $context);
    if (!in_array($context, ['dat-ban', 'lien-he', 'tiec'], true)) $context = 'lien-he';
    $selected = gns_request_default_group($context);
    $source = gns_request_source($context);
    $ajax = esc_url(admin_url('admin-ajax.php'));
    $today = function_exists('gn_today_ymd') ? gn_today_ymd() : wp_date('Y-m-d');
    $times = function_exists('gn_booking_time_options_html') ? gn_booking_time_options_html() : '';
    $parties = gns_party_types();
    $class = 'form gn-request';
    if ($context === 'dat-ban') $class .= ' booking-form';
    elseif ($context === 'lien-he') $class .= ' card contact-form';
    else $class .= ' card';
    $hint = function_exists('gns_saved_text') ? gns_saved_text('gns_contact_hint') : '';

    ob_start(); ?>
<div class="gn-request-wrap">
  <form class="<?php echo esc_attr($class); ?>" id="gn-request-form" data-gn-request method="post" action="<?php echo $ajax; ?>" aria-label="<?php echo esc_attr(gns_t('form_aria', 'Gửi yêu cầu đặt bàn')); ?>">
    <input type="text" name="website" class="sr-only" tabindex="-1" autocomplete="off" aria-hidden="true">
    <input type="hidden" name="action" value="gns_submit_request">
    <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('gns_request')); ?>">
    <input type="hidden" name="form_context" value="<?php echo esc_attr($source); ?>">
    <?php if ($context === 'lien-he'): ?>
      <h2 class="title title-sm"><?php echo esc_html(gns_t('form_title', 'Gửi yêu cầu')); ?></h2>
    <?php endif; ?>
    <div class="form-grid">
      <label><?php echo esc_html(gns_t('form_name', 'Họ tên')); ?> *
        <input type="text" name="customer_name" required autocomplete="name" maxlength="120">
      </label>
      <label><?php echo esc_html(gns_t('form_phone', 'SĐT / Zalo')); ?> *
        <input type="tel" name="customer_phone" required autocomplete="tel" inputmode="tel" dir="ltr" pattern="[0-9 +().-]{8,}" maxlength="30">
      </label>
      <label class="span-2"><?php echo esc_html(gns_t('form_email', 'Email')); ?>
        <input type="email" name="customer_email" autocomplete="email" maxlength="120" dir="ltr">
      </label>
      <div class="span-2"><?php echo gns_guest_group_select(true, $selected); ?></div>
      <label><?php echo esc_html(gns_t('form_guests', 'Số lượng khách')); ?> *
        <input type="number" name="guests" required min="1" max="5000" step="1" inputmode="numeric" placeholder="<?php echo esc_attr(gns_t('form_guests_ph', 'Ví dụ: 4')); ?>">
      </label>
      <label><?php echo esc_html(gns_t('form_date', 'Ngày')); ?> *
        <input type="date" name="booking_date" required min="<?php echo esc_attr($today); ?>">
      </label>
      <label><?php echo esc_html(gns_t('form_time', 'Giờ')); ?> *
        <select name="booking_time" required>
          <option value=""><?php echo esc_html(gns_t('form_time_ph', 'Chọn giờ')); ?></option>
          <?php echo $times; ?>
        </select>
      </label>
      <?php echo gns_guest_area_select(true, function_exists('gns_request_area_from_query') ? gns_request_area_from_query() : ''); ?>
      <div class="span-2 gn-extra" data-gn-extra="dat-tiec"<?php echo $selected === 'dat-tiec' ? '' : ' hidden'; ?>>
        <label><?php echo esc_html(gns_t('form_party', 'Loại tiệc')); ?>
          <select name="party_type">
            <option value=""><?php echo esc_html(gns_t('form_party_ph', 'Chọn loại tiệc')); ?></option>
            <?php foreach ($parties as $key => $label): ?>
              <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html(gns_t('party_' . $key, $label)); ?></option>
            <?php endforeach; ?>
          </select>
        </label>
      </div>
      <div class="span-2 gn-extra" data-gn-extra="doanh-nghiep"<?php echo $selected === 'doanh-nghiep' ? '' : ' hidden'; ?>>
        <label><?php echo esc_html(gns_t('form_company', 'Tên công ty')); ?>
          <input type="text" name="company_biz" autocomplete="organization" maxlength="160">
        </label>
        <label class="gn-check"><input type="checkbox" name="vat" value="1"> <?php echo esc_html(gns_t('form_vat', 'Cần hóa đơn VAT')); ?></label>
      </div>
      <div class="span-2 gn-extra" data-gn-extra="doi-tac-dua-khach"<?php echo $selected === 'doi-tac-dua-khach' ? '' : ' hidden'; ?>>
        <label><?php echo esc_html(gns_t('form_unit', 'Công ty / đơn vị')); ?>
          <input type="text" name="company_partner" autocomplete="organization" maxlength="160">
        </label>
      </div>
      <label class="span-2"><?php echo esc_html(gns_t('form_note', 'Ghi chú / yêu cầu')); ?>
        <textarea name="note" rows="4" maxlength="2000"></textarea>
      </label>
    </div>
    <button class="btn btn-ember btn-block" type="submit"><?php echo esc_html(gns_t('form_submit', 'Gửi yêu cầu')); ?></button>
    <p class="form-note" data-gn-error role="status"></p>
    <?php if ($context === 'lien-he' && $hint !== ''): ?>
      <p class="form-note"><?php echo esc_html($hint); ?></p>
    <?php endif; ?>
  </form>
  <div class="booking-done gn-request-done" data-gn-done hidden role="status" tabindex="-1">
    <span class="booking-done-icon"><svg class="icon" aria-hidden="true" focusable="false"><use href="#i-check"></use></svg></span>
    <h2 class="booking-card-title"><?php echo esc_html(gns_t('form_done_title', 'Đã nhận yêu cầu')); ?></h2>
    <p data-gn-done-message><?php echo esc_html(gns_t('form_done_message', 'Đã nhận. Nhà hàng sẽ gọi lại xác nhận trong giờ mở cửa.')); ?></p>
  </div>
</div>
    <?php
    return ob_get_clean();
}

function gns_request_rate_limited() {
    $ip = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '';
    $bucket = wp_date('YmdHi', (int) (floor(time() / 600) * 600));
    $key = 'gns_req_' . md5($ip . $bucket);
    $hits = (int) get_transient($key);
    if ($hits >= 8) return true;
    set_transient($key, $hits + 1, 10 * MINUTE_IN_SECONDS);
    return false;
}

function gns_request_valid_time($time) {
    if ($time === '') return false;
    if (function_exists('gn_booking_time_slots')) {
        return in_array($time, gn_booking_time_slots(), true);
    }
    return (bool) preg_match('/^\d{2}:\d{2}$/', $time);
}

add_action('wp_ajax_nopriv_gns_submit_request', 'gns_ajax_submit_request');
add_action('wp_ajax_gns_submit_request', 'gns_ajax_submit_request');
function gns_ajax_submit_request() {
    check_ajax_referer('gns_request', 'nonce');
    if (!empty($_POST['website'])) {
        wp_send_json_success(['message' => 'Đã nhận. Nhà hàng sẽ gọi lại xác nhận trong giờ mở cửa.']);
    }
    if (gns_request_rate_limited()) {
        wp_send_json_error(['message' => 'Bạn vừa gửi nhiều yêu cầu. Gọi 0979 232 233.']);
    }

    $allowed_sources = ['dat-ban', 'lien-he', 'khach-doan-tiec-rieng'];
    $source = sanitize_text_field(wp_unslash($_POST['form_context'] ?? ''));
    if (!in_array($source, $allowed_sources, true)) $source = 'lien-he';

    $name = sanitize_text_field(wp_unslash($_POST['customer_name'] ?? ''));
    $phone = sanitize_text_field(wp_unslash($_POST['customer_phone'] ?? ''));
    $email_raw = sanitize_text_field(wp_unslash($_POST['customer_email'] ?? ''));
    $email = sanitize_email($email_raw);
    $guest = gns_guest_fields_from_post();
    $count = absint(wp_unslash($_POST['guests'] ?? 0));
    $date = sanitize_text_field(wp_unslash($_POST['booking_date'] ?? ''));
    $time = sanitize_text_field(wp_unslash($_POST['booking_time'] ?? ''));
    $note_in = sanitize_textarea_field(wp_unslash($_POST['note'] ?? ''));

    if ($name === '' || $phone === '') {
        wp_send_json_error(['message' => 'Thiếu họ tên hoặc số điện thoại.']);
    }
    $digits = preg_replace('/\D+/', '', $phone);
    if (strlen($digits) < 8) {
        wp_send_json_error(['message' => 'Số điện thoại chưa đúng.']);
    }
    if ($email_raw !== '' && $email === '') {
        wp_send_json_error(['message' => 'Email chưa đúng.']);
    }
    if ($guest['group'] === '') {
        wp_send_json_error(['message' => 'Chọn nhóm khách hàng.']);
    }
    if ($count < 1 || $count > 5000) {
        wp_send_json_error(['message' => 'Số lượng khách từ 1 đến 5000.']);
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        wp_send_json_error(['message' => 'Ngày không hợp lệ.']);
    }
    try {
        $today = (new DateTime('now', new DateTimeZone('Asia/Ho_Chi_Minh')))->format('Y-m-d');
        $max = (new DateTime('now', new DateTimeZone('Asia/Ho_Chi_Minh')))->modify('+1 year')->format('Y-m-d');
    } catch (Exception $e) {
        $today = gmdate('Y-m-d');
        $max = gmdate('Y-m-d', time() + 365 * DAY_IN_SECONDS);
    }
    if ($date < $today) wp_send_json_error(['message' => 'Không đặt bàn cho ngày đã qua.']);
    if ($date > $max) wp_send_json_error(['message' => 'Chỉ đặt bàn trong vòng 12 tháng tới.']);
    if (!gns_request_valid_time($time)) wp_send_json_error(['message' => 'Chọn giờ trong danh sách.']);

    $parties = gns_party_types();
    $party = sanitize_key(wp_unslash($_POST['party_type'] ?? ''));
    if ($guest['group'] !== 'dat-tiec' || !isset($parties[$party])) $party = '';
    $company = '';
    if ($guest['group'] === 'doanh-nghiep') {
        $company = sanitize_text_field(wp_unslash($_POST['company_biz'] ?? ''));
    } elseif ($guest['group'] === 'doi-tac-dua-khach') {
        $company = sanitize_text_field(wp_unslash($_POST['company_partner'] ?? ''));
    }
    $vat = ($guest['group'] === 'doanh-nghiep' && !empty($_POST['vat']));

    $need_bits = [$guest['group_label']];
    if ($party !== '') $need_bits[] = 'Loại tiệc: ' . $parties[$party];
    if ($company !== '') $need_bits[] = 'Đơn vị: ' . $company;
    $need = implode('. ', $need_bits);

    $note_bits = [];
    if ($guest['area'] !== '' && $guest['area'] !== 'khong-yeu-cau') {
        $note_bits[] = 'Khu vực: ' . $guest['area_label'] . '.';
    }
    if ($vat) $note_bits[] = 'Cần hóa đơn VAT.';
    if ($note_in !== '') $note_bits[] = $note_in;
    $note = trim(implode(' ', $note_bits));

    if ($guest['area'] === 'phong-vip') $space = 'vip';
    elseif (in_array($guest['group'], ['doan-truc-tiep', 'doi-tac-dua-khach'], true)) $space = 'doan';
    else $space = 'individual';

    $when = trim($date . ' ' . $time);
    $lead_message = $need;
    if ($note !== '') $lead_message = trim($lead_message . "\n" . $note);

    $_POST['gns_lead_done'] = '1';
    gns_store_lead_safe([
        'source' => $source,
        'name' => $name,
        'phone' => $phone,
        'email' => $email,
        'guest_group' => $guest['group'],
        'guest_count' => $count,
        'area' => $guest['area'],
        'when' => $when,
        'message' => $lead_message,
    ]);

    $_POST['customer_name'] = $name;
    $_POST['customer_phone'] = $phone;
    $_POST['customer_email'] = $email;
    $_POST['booking_date'] = $date;
    $_POST['booking_time'] = $time;
    $_POST['guests'] = (string) $count;
    $_POST['space_type'] = $space;
    $_POST['need'] = $need;
    $_POST['note'] = $note;
    $_POST['source'] = $source;
    unset(
        $_POST['guest_group'],
        $_POST['area'],
        $_POST['party_type'],
        $_POST['company_biz'],
        $_POST['company_partner'],
        $_POST['vat'],
        $_POST['website'],
        $_POST['form_context'],
        $_POST['action'],
        $_POST['nonce']
    );

    if (!function_exists('gn_ajax_create_booking')) {
        wp_send_json_success(['message' => 'Đã nhận. Nhà hàng sẽ gọi lại xác nhận trong giờ mở cửa.']);
    }
    gn_ajax_create_booking();
}
