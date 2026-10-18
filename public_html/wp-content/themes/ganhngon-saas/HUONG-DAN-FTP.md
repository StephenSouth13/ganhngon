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
wp-content/themes/ganhngon-saas/assets/contact.js
wp-content/themes/ganhngon-saas/assets/favicon.svg
wp-content/themes/ganhngon-saas/assets/img/logo-mark.svg
wp-content/themes/ganhngon-saas/inc/menu-data.php
wp-content/themes/ganhngon-saas/inc/routes.php
wp-content/themes/ganhngon-saas/PARITY.md
wp-content/themes/ganhngon-saas/template-parts/icons.php
wp-content/themes/ganhngon-saas/template-parts/page-menu.php
wp-content/themes/ganhngon-saas/template-parts/page-booking.php
wp-content/themes/ganhngon-saas/template-parts/page-news.php
wp-content/themes/ganhngon-saas/template-parts/page-content.php
wp-content/themes/ganhngon-saas/template-parts/contact-dock.php
wp-content/themes/ganhngon-saas/assets/forms.js
wp-content/themes/ganhngon-saas/assets/img/photos/
wp-content/themes/ganhngon-saas/inc/menu-photos.php
wp-content/themes/ganhngon-saas/inc/menu-snapshot.json
wp-content/themes/ganhngon-saas/inc/render.php
wp-content/themes/ganhngon-saas/parts/mains/
```

Ảnh món tháng 10/2026 nằm trong `assets/img/photos/` (ảnh nhà hàng đã đăng). Upload cả thư mục đó cùng theme. Không xoá `wp-content/uploads/`.

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
- Thực đơn `/thuc-don/` liệt kê món từ `inc/menu-snapshot.json`. Món nào có ảnh đúng món đó thì hiện ảnh món. Mâm xiên chỉ dùng cho nhóm xiên và các món xiên, có nhãn nhỏ «Ảnh nhóm». Nhóm chưa có ảnh đúng nhóm hiện ô hoạ tiết thương hiệu (không phải ảnh món). Không lấy ảnh một món khác làm ảnh nhóm. Không dùng ảnh minh hoạ tháng 9 và không dùng ảnh stock làm ảnh món. **Không có giá.**
- Zalo, điện thoại, WhatsApp, WeChat, bản đồ dùng đúng hàm/số của theme cũ.
- Khối pháp nhân (CÔNG TY TNHH GÁNH NGON, MST, địa chỉ, cảnh báo giả mạo) nằm ở chân trang tối. Theme gỡ hook `wp_footer` của `gn-footer-legal.php` và `achau-eco-pq.php` để không in thêm một khối sáng, và gỡ mọi liên kết ra tên miền không thuộc nhà hàng.
- Năm nút chat nổi gộp thành một nút Liên hệ (chat nhà hàng, Zalo, WhatsApp, Telegram, WeChat).

## 4. Hoàn tác ngay

1. **Giao diện → Giao diện**.
2. Bật lại **Gánh Ngon Restaurant** (`ganhngon-restaurant`).

Không cần xoá file. Trang Tin tức nếu mới tạo có thể để nguyên (theme cũ sẽ hiện trang trống) hoặc cho vào Thùng rác. Không ảnh hưởng đặt bàn hay URL khác.

## 5. Kiểm tra sau khi bật

- Trang chủ, `/thuc-don/`, `/dat-ban/` (gửi thử một đặt bàn rồi xoá trong admin nếu là thử).
- `/ve-ganh-ngon/`, `/lien-he/`, `/khach-doan-tiec-rieng/`, `/thuc-don/do-uong/`, `/lau-ga-sau-rieng-phu-quoc/` (`/lau-ga-sau-rieng/` chuyển 301 sang đường dài).
- Đổi ngôn ngữ Polylang trên một trang đã dịch.
- Nguồn trang: title/description của Yoast vẫn có.
- Không thấy số tiền trên thực đơn.
- `/thuc-don/`: nhóm Bò nướng không còn ảnh sầu riêng chiên. «Ảnh nhóm» chỉ trên mâm xiên. Các ô còn lại là hoạ tiết, không phải ảnh món khác. Thẻ món cao bằng nhau kể cả khi thiếu mô tả.

## 6. Thêm ảnh cho một món

Vào **wp-admin → Media → Thêm mới**, tải một file ảnh nhà hàng chụp.

Tên file (không đuôi), hoặc slug của media, hoặc tiêu đề media, phải đúng **slug món**:

- bỏ dấu, chữ thường, khoảng trắng thành gạch ngang
- ví dụ món «Tôm nướng» → file `tom-nuong.jpg`
- ví dụ «Lẩu gà sầu riêng Phú Quốc» → `lau-ga-sau-rieng-phu-quoc.jpg`

Tải xong, mở lại `/thuc-don/`. Ảnh mới thay ô hoạ tiết (hoặc ảnh nhóm) của đúng món đó, không cần sửa theme. Ảnh không đúng slug sẽ không gắn vào món nào.

## 7. File lõi WordPress — cần cấu hình máy chủ

Theme gỡ thẻ `generator` và tham số `?ver=` đúng bằng phiên bản WordPress trên file CSS/JS mà WordPress in qua `wp_head`. Ba đường sau do web server trả thẳng, theme không chặn được:

- `/readme.html`
- `/license.txt`
- `/wp-admin/install.php`

Dán vào `.htaccess` ở thư mục gốc site (Apache 2.4), phía trên khối WordPress:

```apache
# ganhngon-saas — không để file lõi lộ phiên bản WordPress
<Files "readme.html">
  Require all denied
</Files>
<Files "license.txt">
  Require all denied
</Files>
<Files "install.php">
  Require all denied
</Files>
```

Nginx:

```nginx
location = /readme.html { deny all; }
location = /license.txt { deny all; }
location = /wp-admin/install.php { deny all; }
```

Chặn `install.php` nghĩa là không mở được trình cài đặt cho đến khi gỡ rule. Site đã cài xong thì không cần file đó trên web.
