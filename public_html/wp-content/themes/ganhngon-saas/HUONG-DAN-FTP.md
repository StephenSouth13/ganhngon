# Triển khai giao diện SaaS lên WordPress (FTP)

Giao diện mới là **child theme** `ganhngon-saas`. Theme đang chạy `ganhngon-restaurant` **không bị sửa và không bị xoá**. Chỉ cần đổi theme trong wp-admin là về giao diện cũ ngay.

Không deploy từ git. Không đụng database backup. Không đổi domain.

## 1. Thư mục cần upload

FTP root là thư mục gốc WordPress (nơi có `wp-config.php`, `wp-admin`, `wp-content`).

Upload **đúng một thư mục**:

```
wp-content/themes/ganhngon-saas/
```

Cấu trúc bên trong (upload cả cây, giữ nguyên tên file):

```
wp-content/themes/ganhngon-saas/style.css
wp-content/themes/ganhngon-saas/functions.php
wp-content/themes/ganhngon-saas/header.php
wp-content/themes/ganhngon-saas/footer.php
wp-content/themes/ganhngon-saas/front-page.php
wp-content/themes/ganhngon-saas/page.php
wp-content/themes/ganhngon-saas/index.php
wp-content/themes/ganhngon-saas/single.php
wp-content/themes/ganhngon-saas/archive.php
wp-content/themes/ganhngon-saas/search.php
wp-content/themes/ganhngon-saas/404.php
wp-content/themes/ganhngon-saas/HUONG-DAN-FTP.md
wp-content/themes/ganhngon-saas/assets/styles.css
wp-content/themes/ganhngon-saas/assets/wp.css
wp-content/themes/ganhngon-saas/assets/script.js
wp-content/themes/ganhngon-saas/assets/booking.js
wp-content/themes/ganhngon-saas/assets/favicon.svg
wp-content/themes/ganhngon-saas/assets/img/logo-mark.svg
wp-content/themes/ganhngon-saas/inc/menu-data.php
wp-content/themes/ganhngon-saas/template-parts/icons.php
wp-content/themes/ganhngon-saas/template-parts/page-menu.php
wp-content/themes/ganhngon-saas/template-parts/page-booking.php
wp-content/themes/ganhngon-saas/template-parts/page-news.php
wp-content/themes/ganhngon-saas/template-parts/page-content.php
```

Ảnh món và không gian **không** nằm trong theme. Theme đọc ảnh đã có trong `wp-content/uploads/2026/`. Không upload lại uploads, không xoá ảnh cũ.

Không upload đè:

- `wp-content/themes/ganhngon-restaurant/`
- plugin (Sao booking, Polylang, Yoast, …)
- `wp-config.php`, `.htaccess`

## 2. Bật giao diện

1. Đăng nhập `wp-admin`.
2. **Giao diện → Giao diện**.
3. Bật **Gánh Ngon SaaS** (thư mục `ganhngon-saas`).
4. Lúc bật, nếu chưa có trang slug `tin-tuc`, theme tạo một trang **Tin tức** (tiếng Việt, nội dung trống). Trang này chỉ là vỏ; danh sách bài lấy từ bài viết WordPress và các URL đang có (`/lau-ga-sau-rieng/`, `/khach-doan-tiec-rieng/tiec-sinh-nhat/`, `/thuc-don/do-uong/`).

Permalink giữ nguyên (`/thuc-don/`, `/dat-ban/`, `/ve-ganh-ngon/`, `/lien-he/`, `/khach-doan-tiec-rieng/`, ngôn ngữ Polylang `/en/`, `/zh/`, …).

## 3. Việc theme giữ từ WordPress

- `wp_head()` / `wp_footer()`: Yoast SEO, Polylang và plugin khác vẫn in ra như cũ.
- Form `/dat-ban/` vẫn là form của theme cha, gửi `admin-ajax.php?action=sao_create_booking` (bảng đặt bàn, CRM, Zalo/hotline không đổi).
- Trang không có mẫu riêng (giới thiệu, liên hệ, tiệc, đồ uống, chính sách, đối tác, tư vấn) in `the_content()` — nội dung trong database giữ nguyên, chỉ đổi khung header/footer và chữ.
- Thực đơn `/thuc-don/` dùng bố cục mới (ảnh nhóm món, dòng món không ảnh, khối lẩu gà sầu riêng). Danh sách món lấy từ thực đơn đã đăng trên site và theme SaaS v1.0.7. **Không có giá.**
- Zalo, điện thoại, WhatsApp, WeChat, bản đồ dùng đúng hàm/số của theme cũ.

## 4. Hoàn tác ngay

1. **Giao diện → Giao diện**.
2. Bật lại **Gánh Ngon Restaurant** (`ganhngon-restaurant`).

Không cần xoá file. Trang Tin tức nếu mới tạo có thể để nguyên (theme cũ sẽ hiện trang trống) hoặc cho vào Thùng rác. Không ảnh hưởng đặt bàn hay URL khác.

## 5. Kiểm tra sau khi bật

- Trang chủ, `/thuc-don/`, `/dat-ban/` (gửi thử một đặt bàn rồi xoá trong admin nếu là thử).
- `/ve-ganh-ngon/`, `/lien-he/`, `/khach-doan-tiec-rieng/`, `/thuc-don/do-uong/`, `/lau-ga-sau-rieng/`.
- Đổi ngôn ngữ Polylang trên một trang đã dịch.
- Nguồn trang: title/description của Yoast vẫn có.
- Không thấy số tiền trên thực đơn.
