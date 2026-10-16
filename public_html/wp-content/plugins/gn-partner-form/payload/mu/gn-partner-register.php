<?php
/**
 * Plugin Name: GN Partner Register
 * Description: Hop nhat duong dang ky doi tac (phan con lai cua P0-1).
 *
 *   TINH HINH TRUOC:
 *   - Theme gan ca hai ten ajax vao mot ham luu, ghi dung vao bang `partners`
 *     voi ma GN-Pxxx. Tang xu ly da dung.
 *   - NHUNG form khong co JavaScript. Submit xong trinh duyet nhay thang sang
 *     admin-ajax.php va in JSON tho ra man hinh. Khach thay trang trang day ma,
 *     tuong hong, bo di. Ban ghi co the da tao ma khong ai biet.
 *   - Form cong khai khong co bay ong mat, khong gioi han so lan gui.
 *   - Bang `affiliates` (ma SAOxxxxxx) van con duong ghi neu plugin SAO bat lai.
 *     Bang do man wp-admin > Ganh Ngon > Doi tac KHONG BAO GIO doc.
 *
 *   PLUGIN NAY LAM 4 VIEC:
 *   1. Them bay ong mat + gioi han 3 lan / 10 phut cho moi IP, chay TRUOC handler.
 *   2. Chen JavaScript vao form: gui bang fetch, hien ket qua ngay tren trang.
 *   3. Form render dung action gn_partner_register. Ten cu sao_partner_login
 *      van duoc theme gan vao cung ham luu, de link cu khong rot.
 *   4. Chan han duong ghi vao bang `affiliates`.
 *
 *   Khong sua functions.php cua theme. Go bo = xoa file nay.
 *
 * Version: 1.0
 */
if (!defined('ABSPATH')) exit;

/* ---------------------------------------------------------------------------
 * 1. Bay ong mat + gioi han so lan gui — chay o uu tien 1, truoc handler cua theme
 * ------------------------------------------------------------------------- */

function gn_pr_ip() {
    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
    foreach (array('HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR') as $k) {
        if (!empty($_SERVER[$k])) {
            $parts = explode(',', $_SERVER[$k]);
            $ip = trim($parts[0]);
            break;
        }
    }
    return $ip;
}

function gn_pr_guard() {
    // Bot dien het moi o. Nguoi that khong nhin thay o nay nen de trong.
    if (!empty($_POST['website_hp'])) {
        wp_send_json_success(array('message' => 'Đã nhận hồ sơ.'));
    }

    $key = 'gn_pr_' . md5('reg:' . gn_pr_ip());
    $n = (int) get_transient($key);
    if ($n >= 3) {
        wp_send_json_error(array(
            'message' => 'Quý khách đã gửi quá nhiều lần. Vui lòng thử lại sau 10 phút, hoặc gọi 0979 232 233 để đăng ký trực tiếp.',
        ), 429);
    }
    set_transient($key, $n + 1, 600);

    // So dien thoai: chan som de khong ghi rac vao bang doi tac.
    $phone = isset($_POST['phone']) ? preg_replace('/[\s\-\.\(\)]/', '', (string) $_POST['phone']) : '';
    if ($phone !== '' && !preg_match('/^(0\d{9,10}|\+84\d{9,10})$/', $phone)) {
        wp_send_json_error(array('message' => 'Số điện thoại không hợp lệ. Dạng đúng: 0979232233.'));
    }
}

add_action('wp_ajax_nopriv_sao_partner_login', 'gn_pr_guard', 1);
add_action('wp_ajax_sao_partner_login',        'gn_pr_guard', 1);

/* ---------------------------------------------------------------------------
 * 2. Ten hanh dong ro rang. Handler van la ham cua theme — khong nhan doi logic.
 * ------------------------------------------------------------------------- */

add_action('init', function () {
    add_action('wp_ajax_nopriv_gn_partner_register', 'gn_pr_guard', 1);
    add_action('wp_ajax_gn_partner_register',        'gn_pr_guard', 1);
}, 20);

/* ---------------------------------------------------------------------------
 * 3. Chan han duong ghi vao bang `affiliates`
 *    Neu plugin SAO duoc bat lai, hai handler cu se song day va ghi vao bang ma
 *    man quan tri khong doc — dung dung loi da lam mat dau doi tac truoc day.
 * ------------------------------------------------------------------------- */

add_action('init', function () {
    remove_all_actions('wp_ajax_sao_partner_register');
    remove_all_actions('wp_ajax_nopriv_sao_partner_register');
    add_action('wp_ajax_nopriv_sao_partner_register', 'gn_pr_duong_cu');
    add_action('wp_ajax_sao_partner_register',        'gn_pr_duong_cu');
}, 99);

function gn_pr_duong_cu() {
    wp_send_json_error(array(
        'message' => 'Đường đăng ký cũ đã ngừng hoạt động. Vui lòng tải lại trang rồi gửi lại, hoặc gọi 0979 232 233.',
    ), 410);
}

/* ---------------------------------------------------------------------------
 * 4. Chen bay ong mat + JavaScript vao form tren /doi-tac/
 *    Uu tien 6 — ngay sau khi theme dung xong noi dung trang (uu tien 5).
 * ------------------------------------------------------------------------- */

add_filter('the_content', function ($content) {
    if (is_admin() || !in_the_loop() || !is_main_query() || !is_page()) return $content;
    if (get_post_field('post_name', get_queried_object_id()) !== 'doi-tac') return $content;
    if (strpos($content, 'gn-pr-form') !== false) return $content;

    $needle = '?action=sao_partner_login">';
    $at = strpos($content, $needle);
    if ($at === false) return $content;

    // Danh dau form + chen o bay ong mat ngay sau the <form ...>
    $cut = $at + strlen($needle);
    $hp  = '<input type="text" name="website_hp" value="" tabindex="-1" autocomplete="off"'
         . ' aria-hidden="true" style="position:absolute;left:-9999px;width:1px;height:1px;opacity:0">';
    $content = substr($content, 0, $at)
             . '?action=gn_partner_register" id="gn-pr-form" novalidate>'
             . $hp
             . substr($content, $cut);

    return $content . gn_pr_script();
}, 6);

function gn_pr_script() {
    ob_start(); ?>
    <style>
    #gn-pr-out{margin:16px 0 0;padding:18px 20px;border-radius:14px;line-height:1.7;display:none}
    #gn-pr-out.is-ok{background:#e4f3ea;border:1px solid #9ed4b5;color:#10502b}
    #gn-pr-out.is-err{background:#fbe6e3;border:1px solid #e4a89f;color:#7d2015}
    #gn-pr-out b.code{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:19px;letter-spacing:.06em}
    #gn-pr-form.is-busy{opacity:.55;pointer-events:none}
    </style>
    <div id="gn-pr-out" role="status" aria-live="polite"></div>
    <script>
    (function () {
      var f = document.getElementById('gn-pr-form');
      if (!f) return;
      var out = document.getElementById('gn-pr-out');
      f.parentNode.insertBefore(out, f.nextSibling);

      f.addEventListener('submit', function (e) {
        e.preventDefault();
        var req = f.querySelectorAll('[required]');
        for (var i = 0; i < req.length; i++) {
          if (!req[i].value.trim()) {
            show('err', 'Vui lòng điền đủ các ô có dấu *.');
            req[i].focus();
            return;
          }
        }
        f.classList.add('is-busy');
        show('ok', 'Đang gửi hồ sơ…');

        fetch(f.action, { method: 'POST', body: new FormData(f), credentials: 'same-origin' })
          .then(function (r) { return r.json(); })
          .then(function (j) {
            f.classList.remove('is-busy');
            if (j && j.success && j.data && j.data.message) {
              show('ok', j.data.message);
              f.reset();
            } else {
              show('err', (j && j.data && j.data.message) ? j.data.message
                : 'Chưa gửi được hồ sơ. Vui lòng gọi 0979 232 233 để đăng ký trực tiếp.');
            }
          })
          .catch(function () {
            f.classList.remove('is-busy');
            show('err', 'Mất kết nối. Vui lòng thử lại, hoặc gọi 0979 232 233.');
          });
      });

      function show(kind, text) {
        out.className = kind === 'ok' ? 'is-ok' : 'is-err';
        out.textContent = text;
        out.style.display = 'block';
        out.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
    })();
    </script>
    <?php
    return ob_get_clean();
}

/* ---------------------------------------------------------------------------
 * 5. Mot cong doi tac duy nhat
 *    /doi-tac/dashboard/ la trang cu cua mo hinh dang nhap bang tai khoan
 *    WordPress. No mo coi (khong trang nao tro toi) va thuc te dang render
 *    noi dung cua trang cha. Cong that la /cong-doi-tac/?t=token.
 *    Chuyen huong han de khoi ton tai hai cong song song.
 * ------------------------------------------------------------------------- */

add_action('template_redirect', function () {
    $uri = isset($_SERVER['REQUEST_URI']) ? strtok($_SERVER['REQUEST_URI'], '?') : '';
    $uri = rtrim($uri, '/') . '/';
    if ($uri === '/doi-tac/dashboard/' || $uri === '/dashboard/') {
        wp_safe_redirect(home_url('/cong-doi-tac/'), 301);
        exit;
    }
}, 8);
