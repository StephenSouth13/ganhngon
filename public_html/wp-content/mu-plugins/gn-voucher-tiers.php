<?php
/**
 * Plugin Name: GN Voucher Tiers
 * Description: Bang menh gia eVoucher — NGUON SU THAT DUY NHAT cho ca trang mua
 *   (/voucher/) lan khau kiem tra phia may chu (gn_ajax_voucher_buy).
 *
 *   CEO chot 27/09/2026: giu dung 4 muc dang hien thi cho khach, chiet khau 10-16%.
 *   Truoc do file nay tra ve bo 299k/599k/799k/1299k/1999k trong khi trang mua
 *   hardcode 4 muc duoi day => moi lan khach bam mua deu bi "Menh gia khong hop le".
 *   Trang mua nay doc truc tiep tu day, nen sua o day la doi ca hai noi.
 *
 *   ==> SUA MENH GIA / GIA BAN NGAY TRONG MANG BEN DUOI. <==
 *   Dinh dang: menh gia (so tien khach tieu duoc) => gia ban (so tien khach tra).
 *
 * Version: 2.0
 */
if (!defined('ABSPATH')) exit;

add_filter('gn_voucher_tiers', function () {
    return array(
         500000 =>  450000,   // loi  50.000d
        1000000 =>  880000,   // loi 120.000d
        2000000 => 1700000,   // loi 300.000d
        5000000 => 4200000,   // loi 800.000d
    );
}, 10);
