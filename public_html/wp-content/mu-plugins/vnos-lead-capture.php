<?php
/**
 * Plugin Name: VNOS Lead Capture -> Jetpack CRM + Google Sheet (route theo cong_ty)
 * Description: Hook CF7/WPForms/FluentForms -> tao Jetpack CRM contact + POST sang Apps Script (route cong_ty -> sheet rieng). Dat o mu-plugins/. Go = xoa file.
 * Version: 1.0
 * Author: VNOS AI Ops
 */
if (!defined('ABSPATH')) exit;

if (!defined('VNOS_APPS_SCRIPT_URL')) define('VNOS_APPS_SCRIPT_URL', '');
if (!defined('VNOS_LEAD_TOKEN'))      define('VNOS_LEAD_TOKEN', '');
if (!defined('VNOS_CONG_TY'))         define('VNOS_CONG_TY', 'ganhngon');
if (!defined('VNOS_LEAD_SECRET'))     define('VNOS_LEAD_SECRET', '');

function vnos_push_lead($d) {
    $name = trim($d['name'] ?? '');
    $email = isset($d['email']) ? sanitize_email($d['email']) : '';
    $phone = preg_replace('/[^0-9+ ]/', '', $d['phone'] ?? '');
    $company = trim($d['company'] ?? '');
    $note = trim($d['note'] ?? '');
    $source = trim($d['source'] ?? 'website');
    if ($name === '' && $email === '' && $phone === '') return false;
    $fname = $name; $lname = '';
    if (strpos($name, ' ') !== false) { $p = explode(' ', $name); $fname = array_shift($p); $lname = implode(' ', $p); }
    $crm_id = 0; global $zbs;
    if (isset($zbs->DAL->contacts) && method_exists($zbs->DAL->contacts, 'addUpdateContact')) {
        $existing = ($email && function_exists('zeroBS_getCustomerIDWithEmail')) ? (int) zeroBS_getCustomerIDWithEmail($email) : 0;
        try {
            $crm_id = $zbs->DAL->contacts->addUpdateContact(['id' => $existing ?: -1, 'data' => [
                'fname' => $fname, 'lname' => $lname, 'email' => $email, 'hometel' => $phone,
                'company' => $company, 'status' => 'Lead',
                'tags' => array_filter(['Website', $source]), 'tag_mode' => 'append']]);
        } catch (\Throwable $e) { error_log('VNOS CRM err: ' . $e->getMessage()); }
    }
    if (VNOS_APPS_SCRIPT_URL) {
        wp_remote_post(VNOS_APPS_SCRIPT_URL, ['timeout' => 8, 'blocking' => false,
            'headers' => ['Content-Type' => 'application/json'],
            'body' => wp_json_encode([
                'token' => VNOS_LEAD_TOKEN, 'cong_ty' => VNOS_CONG_TY,
                'ten' => $name, 'sdt' => ($phone !== '' ? "'" . $phone : ''), 'email' => $email,
                'cong_ty_khach' => $company, 'nhu_cau' => $note, 'ghi_chu' => $note,
                'nguon' => $source, 'domain' => ($_SERVER['HTTP_HOST'] ?? ''), 'crm_id' => $crm_id])]);
    }
    return $crm_id;
}

function vnos_extract_assoc($data) {
    $name = $email = $phone = $company = $note = '';
    foreach ((array) $data as $k => $v) {
        if (is_array($v)) {
            if (isset($v['first_name']) || isset($v['last_name'])) { $name = trim(($v['first_name'] ?? '') . ' ' . ($v['last_name'] ?? '')); continue; }
            $v = implode(' ', array_filter($v, 'is_scalar'));
        }
        $v = trim((string) $v); if ($v === '') continue;
        $lk = strtolower($k);
        if (!$email && filter_var($v, FILTER_VALIDATE_EMAIL)) { $email = $v; continue; }
        if (!$phone && preg_match('/(phone|tel|sdt|mobile|so.?dien|dien.?thoai)/', $lk) && preg_match('/[0-9]{8,}/', $v)) { $phone = $v; continue; }
        if (!$phone && preg_match('/^(0|\+84)[0-9 .]{8,12}$/', $v)) { $phone = $v; continue; }
        if (!$name && preg_match('/(name|ho.?ten|hoten|^ten|full)/', $lk)) { $name = $v; continue; }
        if (!$company && preg_match('/(company|cong.?ty|congty|doanh.?nghiep|business)/', $lk)) { $company = $v; continue; }
        if (!$note && preg_match('/(message|note|mo.?ta|noi.?dung|content|nhu.?cau|subject|y.?kien)/', $lk)) { $note = $v; }
    }
    return compact('name', 'email', 'phone', 'company', 'note');
}

add_action('wpcf7_mail_sent', function ($cf) {
    if (!class_exists('WPCF7_Submission')) return;
    $sub = WPCF7_Submission::get_instance(); if (!$sub) return;
    $d = vnos_extract_assoc($sub->get_posted_data());
    $d['source'] = 'cf7-' . (is_object($cf) ? $cf->id() : '');
    vnos_push_lead($d);
});
add_action('wpforms_process_complete', function ($fields, $entry, $form_data, $entry_id) {
    $vals = []; foreach ((array) $fields as $f) { $vals[($f['name'] ?? $f['type'] ?? 'f')] = $f['value'] ?? ''; }
    $d = vnos_extract_assoc($vals); $d['source'] = 'wpforms-' . ($form_data['id'] ?? '');
    vnos_push_lead($d);
}, 10, 4);
add_action('fluentform/submission_inserted', function ($id, $formData, $form) {
    $d = vnos_extract_assoc($formData); $d['source'] = 'ff-' . (is_object($form) ? ($form->id ?? '') : '');
    vnos_push_lead($d);
}, 20, 3);

add_action('rest_api_init', function () {
    register_rest_route('vnos/v1', '/lead', [
        'methods' => 'POST',
        'permission_callback' => function ($req) { return hash_equals(VNOS_LEAD_SECRET, (string) $req->get_param('secret')); },
        'callback' => function ($req) {
            $id = vnos_push_lead(['name' => $req->get_param('name'), 'phone' => $req->get_param('phone'),
                'email' => $req->get_param('email'), 'company' => $req->get_param('company'),
                'note' => $req->get_param('note'), 'source' => $req->get_param('source') ?: 'chatbot']);
            return ['ok' => true, 'crm_id' => $id];
        }]);
});
