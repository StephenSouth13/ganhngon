<?php
// SAO PHU QUOC - VNPay Payment Redirect
require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';

global $wpdb;
$p = $wpdb->prefix . 'sao_';
$bid = (int)($_GET['booking_id'] ?? 0);
$booking = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$p}bookings WHERE id=%d", $bid));
if(!$booking) die('Booking not found');

$vnp_TmnCode = 'SAOPHUQUOC';
$vnp_HashSecret = 'SAOPHUQUOCVNP@2026';
$vnp_Url = 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html';
$vnp_ReturnUrl = home_url('/vnpay-return/');

$amount = (int)$booking->deposit * 100; // VNPay uses smallest currency unit
$vnp_Params = [
    'vnp_Version' => '2.1.0',
    'vnp_Command' => 'pay',
    'vnp_TmnCode' => $vnp_TmnCode,
    'vnp_Amount' => $amount,
    'vnp_CurrCode' => 'VND',
    'vnp_TxnRef' => $booking->booking_code . '_' . time(),
    'vnp_OrderInfo' => 'SAO PHU QUOC Booking ' . $booking->booking_code,
    'vnp_OrderType' => 'billpayment',
    'vnp_Locale' => 'vn',
    'vnp_ReturnUrl' => $vnp_ReturnUrl,
    'vnp_IpAddr' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
    'vnp_CreateDate' => date('YmdHis'),
];

ksort($vnp_Params);
$query = '';
$hashdata = '';
foreach ($vnp_Params as $key => $value) {
    if ($hashdata != '') { $hashdata .= '&'; $query .= '&'; }
    $hashdata .= $key . '=' . $value;
    $query .= $key . '=' . urlencode($value);
}
$vnp_SecureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);
$paymentUrl = $vnp_Url . '?' . $query . '&vnp_SecureHash=' . $vnp_SecureHash;

header('Location: ' . $paymentUrl);
exit;
