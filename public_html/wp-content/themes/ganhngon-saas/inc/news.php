<?php
/**
 * /tin-tuc/ is a page, so the main query is that page. Posts come from a
 * secondary query. /tin-tuc/page/2/ is read from the paged query var (and from
 * the page var, which WordPress uses for page content). The page var is cleared
 * so WordPress does not 404 a page that has no <!--nextpage--> splits.
 */
if (!defined('ABSPATH')) exit;

function gns_news_grid_size() {
    return 9;
}

function gns_news_lang() {
    if (!function_exists('pll_current_language')) return '';
    $lang = pll_current_language('slug');
    return is_string($lang) ? $lang : '';
}

function gns_news_paged() {
    $paged = absint(get_query_var('paged'));
    $page = absint(get_query_var('page'));
    $n = max($paged, $page);
    if ($n > 1) return $n;
    $uri = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '';
    $path = trim((string) parse_url($uri, PHP_URL_PATH), '/');
    if (preg_match('#(?:^|/)tin-tuc/page/([0-9]+)/?$#', $path, $m)) {
        return max(1, (int) $m[1]);
    }
    return 1;
}

function gns_news_fix_request($vars) {
    if (is_admin()) return $vars;
    $uri = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '';
    $path = trim((string) parse_url($uri, PHP_URL_PATH), '/');
    if (!preg_match('#^(?:(en|zh|ko|ru|ar)/)?tin-tuc/page/([0-9]+)/?$#', $path, $m)) return $vars;
    $vars['pagename'] = 'tin-tuc';
    $vars['paged'] = (int) $m[2];
    if ($m[1] !== '') $vars['lang'] = $m[1];
    unset($vars['page'], $vars['name'], $vars['attachment'], $vars['error'], $vars['category_name']);
    return $vars;
}
add_filter('request', 'gns_news_fix_request', 1);

function gns_news_stop_canonical($redirect) {
    $uri = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '';
    $path = trim((string) parse_url($uri, PHP_URL_PATH), '/');
    if (preg_match('#^(?:(?:en|zh|ko|ru|ar)/)?tin-tuc/page/[0-9]+/?$#', $path)) return false;
    return $redirect;
}
add_filter('redirect_canonical', 'gns_news_stop_canonical');

function gns_news_category_slug() {
    if (!isset($_GET['cm'])) return '';
    $slug = sanitize_title(wp_unslash($_GET['cm']));
    if ($slug === '' || $slug === 'uncategorized') return '';
    $term = get_category_by_slug($slug);
    if (!$term || is_wp_error($term)) return '';
    return $slug;
}

function gns_news_archive_url() {
    if (function_exists('is_page') && is_page()) {
        $id = (int) get_queried_object_id();
        if ($id) {
            $url = get_permalink($id);
            if ($url) return $url;
        }
    }
    return function_exists('gns_url') ? gns_url('tin-tuc') : home_url('/tin-tuc/');
}

function gns_news_query($paged, $cat_slug) {
    $grid = gns_news_grid_size();
    $args = [
        'post_type' => 'post',
        'post_status' => 'publish',
        'ignore_sticky_posts' => true,
        'no_found_rows' => false,
    ];
    $lang = gns_news_lang();
    if ($lang !== '') $args['lang'] = $lang;
    if ($cat_slug !== '') $args['category_name'] = $cat_slug;
    if ($paged <= 1) {
        $args['posts_per_page'] = $grid + 1;
    } else {
        $args['posts_per_page'] = $grid;
        $args['offset'] = 1 + $grid * ($paged - 1);
    }
    return new WP_Query($args);
}

function gns_news_page_count($total) {
    $total = (int) $total;
    $first = gns_news_grid_size() + 1;
    if ($total <= 0) return 0;
    if ($total <= $first) return 1;
    return 1 + (int) ceil(($total - $first) / gns_news_grid_size());
}

function gns_post_date_html($post = null) {
    $display = get_the_date('d/m/Y', $post);
    $iso = get_the_date('Y-m-d', $post);
    if ($display === '') return '';
    return '<time datetime="' . esc_attr($iso) . '">' . esc_html($display) . '</time>';
}

/**
 * A URL may be shown only when it is a real upload: an attachment row, or a
 * file that exists under the uploads directory. Hidden AI/stock patterns are out.
 */
function gns_usable_upload_url($url) {
    $url = trim((string) $url);
    if ($url === '' || (function_exists('gns_is_hidden_media_url') && gns_is_hidden_media_url($url))) return '';
    $id = function_exists('attachment_url_to_postid') ? (int) attachment_url_to_postid($url) : 0;
    if ($id > 0) return $url;
    $uploads = wp_get_upload_dir();
    if (empty($uploads['baseurl']) || empty($uploads['basedir']) || !empty($uploads['error'])) return '';
    $path = parse_url($url, PHP_URL_PATH);
    $base_path = parse_url($uploads['baseurl'], PHP_URL_PATH);
    if (!is_string($path) || !is_string($base_path) || $base_path === '' || strpos($path, $base_path) !== 0) return '';
    $rel = rawurldecode(substr($path, strlen($base_path)));
    if ($rel === '' || strpos($rel, '..') !== false) return '';
    return is_file($uploads['basedir'] . $rel) ? $url : '';
}

function gns_post_image_url($post_id) {
    $post_id = (int) $post_id;
    if ($post_id <= 0) return '';
    $thumb_id = (int) get_post_thumbnail_id($post_id);
    if ($thumb_id > 0) {
        $url = gns_usable_upload_url((string) wp_get_attachment_image_url($thumb_id, 'large'));
        if ($url !== '') return $url;
    }
    $post = get_post($post_id);
    if ($post && is_string($post->post_content) && preg_match_all('/<img\b[^>]*\ssrc=["\']([^"\']+)["\']/i', $post->post_content, $found)) {
        foreach ($found[1] as $src) {
            $url = gns_usable_upload_url($src);
            if ($url !== '') return $url;
        }
    }
    $attachments = get_children([
        'post_parent' => $post_id,
        'post_type' => 'attachment',
        'post_mime_type' => 'image',
        'numberposts' => 8,
        'orderby' => 'menu_order ID',
        'order' => 'ASC',
    ]);
    if (is_array($attachments)) {
        foreach ($attachments as $attachment) {
            $url = gns_usable_upload_url((string) wp_get_attachment_image_url($attachment->ID, 'large'));
            if ($url !== '') return $url;
        }
    }
    return '';
}

function gns_image_or_slot_html($url, $slot_class, $label) {
    $slot = function_exists('gns_slot_markup') ? gns_slot_markup($slot_class, $label, 'i-flame') : '';
    if ($url === '') return $slot;
    $fallback = $slot === '' ? '' : preg_replace('/^<div /', '<div hidden ', $slot, 1);
    $onerror = 'this.onerror=null;var s=this.nextElementSibling;if(s)s.hidden=false;this.remove();';
    return '<img class="' . esc_attr($slot_class) . '" src="' . esc_url($url) . '" alt="" loading="lazy" decoding="async" onerror="' . esc_attr($onerror) . '">' . $fallback;
}

function gns_news_media_html($post_id) {
    return gns_image_or_slot_html(gns_post_image_url($post_id), '', get_the_title($post_id));
}

function gns_news_card_html($featured = false) {
    $id = get_the_ID();
    $url = get_permalink();
    $class = $featured ? 'news-card news-card-featured' : 'news-card';
    $title_tag = $featured ? 'h2' : 'h3';
    $cats = get_the_category($id);
    if (!is_array($cats)) $cats = [];
    ob_start();
    ?>
    <article class="<?php echo esc_attr($class); ?>">
      <a class="news-media" href="<?php echo esc_url($url); ?>" tabindex="-1" aria-hidden="true"><?php echo gns_news_media_html($id); ?></a>
      <div class="news-body">
        <p class="news-meta"><?php echo gns_post_date_html(); ?><?php
        foreach ($cats as $cat) {
            if (!isset($cat->slug) || $cat->slug === 'uncategorized') continue;
            $chip = add_query_arg('cm', $cat->slug, gns_news_archive_url());
            echo '<a class="news-cat" href="' . esc_url($chip) . '">' . esc_html($cat->name) . '</a>';
        }
        ?></p>
        <<?php echo $title_tag; ?> class="news-title"><a href="<?php echo esc_url($url); ?>"><?php the_title(); ?></a></<?php echo $title_tag; ?>>
        <?php $excerpt = wp_strip_all_tags(get_the_excerpt()); if ($excerpt !== ''): ?>
        <p class="news-excerpt"><?php echo esc_html($excerpt); ?></p>
        <?php endif; ?>
        <a class="link news-more" href="<?php echo esc_url($url); ?>"><?php echo esc_html(function_exists('gns_t') ? gns_t('news_more', 'Đọc tiếp') : 'Đọc tiếp'); ?> <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-arrow"></use></svg></a>
      </div>
    </article>
    <?php
    return ob_get_clean();
}

function gns_news_filters_html($current) {
    $args = [
        'hide_empty' => true,
        'orderby' => 'name',
        'order' => 'ASC',
    ];
    $lang = gns_news_lang();
    if ($lang !== '') $args['lang'] = $lang;
    $terms = get_categories($args);
    if (is_wp_error($terms) || !is_array($terms)) $terms = [];
    $terms = array_values(array_filter($terms, function ($term) {
        return isset($term->slug) && $term->slug !== 'uncategorized' && (int) $term->count > 0;
    }));
    if (!$terms && $current === '') return '';
    $base = gns_news_archive_url();
    $html = '<nav class="news-filters" aria-label="' . esc_attr(function_exists('gns_t') ? gns_t('news_cats', 'Chuyên mục') : 'Chuyên mục') . '">';
    $html .= '<a href="' . esc_url($base) . '"' . ($current === '' ? ' aria-current="true"' : '') . '>' . esc_html(function_exists('gns_t') ? gns_t('news_all', 'Tất cả') : 'Tất cả') . '</a>';
    foreach ($terms as $term) {
        $url = add_query_arg('cm', $term->slug, $base);
        $on = $current === $term->slug ? ' aria-current="true"' : '';
        $html .= '<a href="' . esc_url($url) . '"' . $on . '>' . esc_html($term->name) . '</a>';
    }
    $html .= '</nav>';
    return $html;
}

function gns_news_pagination_html($paged, $pages, $cat_slug) {
    if ($pages < 2) return '';
    $base = trailingslashit(gns_news_archive_url()) . 'page/%#%/';
    $links = paginate_links([
        'base' => $base,
        'format' => '',
        'current' => max(1, (int) $paged),
        'total' => (int) $pages,
        'type' => 'list',
        'prev_text' => function_exists('gns_t') ? gns_t('news_prev', 'Trước') : 'Trước',
        'next_text' => function_exists('gns_t') ? gns_t('news_next', 'Sau') : 'Sau',
        'mid_size' => 1,
        'end_size' => 1,
        'add_args' => $cat_slug !== '' ? ['cm' => $cat_slug] : false,
    ]);
    if (!is_string($links) || $links === '') return '';
    return '<nav class="news-pages" aria-label="' . esc_attr(function_exists('gns_t') ? gns_t('news_pages', 'Phân trang') : 'Phân trang') . '">' . $links . '</nav>';
}

function gns_news_archive_html() {
    $paged = gns_news_paged();
    $cat = gns_news_category_slug();
    $q = gns_news_query($paged, $cat);
    $pages = gns_news_page_count($q->found_posts);
    ob_start();
    echo gns_news_filters_html($cat);
    echo '<div class="news-grid news-grid-list">';
    if (!$q->have_posts()) {
        echo '<div class="news-empty"><p>Chưa có bài trong mục này.</p></div>';
    } else {
        $index = 0;
        while ($q->have_posts()) {
            $q->the_post();
            $featured = ($paged === 1 && $index === 0);
            echo gns_news_card_html($featured);
            $index++;
        }
    }
    echo '</div>';
    echo gns_news_pagination_html($paged, $pages, $cat);
    wp_reset_postdata();
    return ob_get_clean();
}

function gns_news_home_html() {
    $args = [
        'post_type' => 'post',
        'post_status' => 'publish',
        'posts_per_page' => 3,
        'ignore_sticky_posts' => true,
        'no_found_rows' => true,
    ];
    $lang = gns_news_lang();
    if ($lang !== '') $args['lang'] = $lang;
    $q = new WP_Query($args);
    if (!$q->have_posts()) {
        wp_reset_postdata();
        return '<p class="news-empty">Bài viết mới sẽ sớm có ở đây.</p>';
    }
    $html = '<div class="news-grid news-grid-3">';
    while ($q->have_posts()) {
        $q->the_post();
        $html .= gns_news_card_html(false);
    }
    $html .= '</div>';
    wp_reset_postdata();
    return $html;
}

function gns_related_posts_html($post_id) {
    $post_id = (int) $post_id;
    if ($post_id <= 0) return '';
    $cats = wp_get_post_categories($post_id);
    if (is_wp_error($cats)) $cats = [];
    $args = [
        'post_type' => 'post',
        'post_status' => 'publish',
        'posts_per_page' => 3,
        'post__not_in' => [$post_id],
        'ignore_sticky_posts' => true,
        'no_found_rows' => true,
    ];
    $lang = gns_news_lang();
    if ($lang !== '') $args['lang'] = $lang;
    if ($cats) $args['category__in'] = $cats;
    $q = new WP_Query($args);
    if (!$q->have_posts() && $cats) {
        unset($args['category__in']);
        $q = new WP_Query($args);
    }
    if (!$q->have_posts()) {
        wp_reset_postdata();
        return '';
    }
    ob_start();
    echo '<section class="post-related" aria-labelledby="related-title">';
    echo '<h2 id="related-title" class="title">Bài liên quan</h2>';
    echo '<div class="news-grid news-grid-3">';
    while ($q->have_posts()) {
        $q->the_post();
        echo gns_news_card_html(false);
    }
    echo '</div></section>';
    wp_reset_postdata();
    return ob_get_clean();
}

function gns_post_cta_html() {
    $url = function_exists('gns_url') ? gns_url('dat-ban') : home_url('/dat-ban/');
    ob_start();
    ?>
    <section class="band band-ember" aria-labelledby="post-cta">
      <div class="wrap cta-band">
        <div>
          <h2 id="post-cta" class="title title-light">Giữ bàn cho bữa tiếp theo</h2>
          <p>Một form cho đặt bàn, liên hệ và tiệc. Nhà hàng gọi lại xác nhận trong giờ mở cửa.</p>
        </div>
        <div class="actions"><a class="btn btn-ember" href="<?php echo esc_url($url); ?>">Đặt bàn</a></div>
      </div>
    </section>
    <?php
    return ob_get_clean();
}
