<?php
/**
 * Translations Module
 *
 * Provides multilingual content management: per-post language tagging,
 * translation linking, hreflang output, og:locale, language-aware nav menus,
 * and a language-prefix URL system.
 *
 * @package mini
 */

if (!defined('ABSPATH')) {
    exit;
}

/* START - Helper functions */

/**
 * Returns the parsed language list from settings.
 * Each entry: ['code' => 'en', 'label' => 'English']
 */
function mini_translations_get_languages() {
    static $cache = null;
    if ($cache !== null) { return $cache; }
    $options = get_option('mini_translations_settings');
    $raw = is_array($options) && !empty($options['languages']) ? $options['languages'] : '';
    $languages = [];
    foreach (preg_split('/\r?\n/', $raw) as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        $parts = explode(':', $line, 2);
        $code  = sanitize_key($parts[0]);
        $label = isset($parts[1]) ? sanitize_text_field($parts[1]) : $code;
        if ($code !== '') {
            $languages[] = ['code' => $code, 'label' => $label];
        }
    }
    $cache = $languages;
    return $cache;
}

/**
 * Returns the language code stored on a post.
 */
function mini_get_post_lang($post_id) {
    return (string) get_post_meta((int) $post_id, '_mini_lang', true);
}

/**
 * Returns the translation map stored on a post.
 * Example: ['en' => 123, 'it' => 456]
 */
function mini_get_translations($post_id) {
    $translations = get_post_meta((int) $post_id, '_mini_translations', true);
    return is_array($translations) ? $translations : [];
}

/**
 * Returns the permalink of a specific translation of a post, or empty string.
 */
function mini_get_translation_url($post_id, $lang) {
    $translations = mini_get_translations($post_id);
    if (empty($translations[$lang])) {
        return '';
    }
    return (string) get_permalink((int) $translations[$lang]);
}

/**
 * Returns all language-code => URL pairs for a post, including itself.
 * Example: ['en' => 'https://...', 'it' => 'https://...']
 */
function mini_get_all_translation_urls($post_id) {
    $post_id = (int) $post_id;
    $urls    = [];

    $own_lang = mini_get_post_lang($post_id);
    if ($own_lang !== '') {
        $own_url = get_permalink($post_id);
        if ($own_url) {
            $urls[$own_lang] = $own_url;
        }
    }

    foreach (mini_get_translations($post_id) as $lang => $tid) {
        $tid = (int) $tid;
        if ($tid > 0 && get_post_status($tid) === 'publish') {
            $url = get_permalink($tid);
            if ($url) {
                $urls[sanitize_key($lang)] = $url;
            }
        }
    }

    return $urls;
}

/* END - Helper functions */

/* START - Translations settings */

function mini_translations_settings_init() {
    register_setting('mini_translations', 'mini_translations_settings', [
        'sanitize_callback' => 'mini_translations_settings_sanitize',
    ]);
    add_settings_section(
        'mini_translations_section',
        __('Translations settings', 'mini'),
        '__return_empty_string',
        'mini-translations'
    );
}
add_action('admin_init', 'mini_translations_settings_init');

function mini_translations_settings_sanitize($input) {
    $input     = is_array($input) ? $input : [];
    $sanitized = [];

    $sanitized['mini_enable_translations']    = !empty($input['mini_enable_translations']) ? '1' : '0';
    $sanitized['mini_nav_show_untranslated']  = !empty($input['mini_nav_show_untranslated']) ? '1' : '0';

    if (isset($input['languages'])) {
        $lines       = preg_split('/\r?\n/', $input['languages']);
        $clean_lines = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $parts = explode(':', $line, 2);
            $code  = sanitize_key($parts[0]);
            $label = isset($parts[1]) ? sanitize_text_field($parts[1]) : $code;
            if ($code !== '') {
                $clean_lines[] = $code . ':' . $label;
            }
        }
        $sanitized['languages'] = implode("\n", $clean_lines);
    }

    return $sanitized;
}

function mini_translations_page_html() {
    if (!current_user_can('manage_options')) {
        return;
    }

    if (isset($_GET['settings-updated'])) {
        add_settings_error('mini_messages', 'mini_message', __('Settings Saved', 'mini'), 'updated');
    }

    settings_errors('mini_messages');

    $enabled            = is_mini_option_enabled('mini_translations_settings', 'mini_enable_translations');
    $show_untranslated  = is_mini_option_enabled('mini_translations_settings', 'mini_nav_show_untranslated');
    $options            = get_option('mini_translations_settings');
    $languages_raw      = is_array($options) && isset($options['languages']) ? $options['languages'] : "en:English\nit:Italian";
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

        <form action="options.php" method="post">
            <?php settings_fields('mini_translations'); ?>
            <?php do_settings_sections('mini-translations'); ?>

            <div class="boxes align-items-start">

                <div class="box-100 p-2 grad-3-to-4 b-rad-5 box-shadow">
                    <h4 class="white-text"><?php esc_html_e('Enable Translations', 'mini'); ?></h4>
                    <label for="mini_enable_translations" class="white-text">
                        <input
                            type="checkbox"
                            id="mini_enable_translations"
                            name="mini_translations_settings[mini_enable_translations]"
                            class="me-1"
                            value="1"
                            <?php checked($enabled, true); ?>
                        >
                        <?php esc_html_e('Enable multilingual content support for pages, posts, and custom content types.', 'mini'); ?>
                    </label>
                    <p class="S false-white-text">
                        <?php esc_html_e("When enabled, you can set a language for each post/page and link translated versions. hreflang tags and og:locale are automatically output in the page head.", 'mini'); ?>
                    </p>
                </div>

                <div class="box-100 p-2 white-bg b-rad-5 box-shadow">
                    <h4><?php esc_html_e('Configured Languages', 'mini'); ?></h4>
                    <p class="grey-text S">
                        <?php esc_html_e('Enter one language per line in the format:', 'mini'); ?>
                        <code>code:Label</code>
                        <?php esc_html_e('(e.g., en:English).', 'mini'); ?>
                    </p>
                    <textarea
                        name="mini_translations_settings[languages]"
                        rows="8"
                        style="width: 100%; font-family: monospace;"
                        placeholder="en:English&#10;it:Italian&#10;fr:French"
                    ><?php echo esc_textarea($languages_raw); ?></textarea>
                    <p class="S grey-text mt-05">
                        <?php esc_html_e('Use ISO 639-1 codes (en, it, fr, de, es, …). These are used as hreflang attribute values.', 'mini'); ?>
                    </p>
                </div>

                <div class="box-100 p-2 white-bg b-rad-5 box-shadow">
                    <h4><?php esc_html_e('Navigation menu behaviour', 'mini'); ?></h4>
                    <label>
                        <input type="checkbox"
                               name="mini_translations_settings[mini_nav_show_untranslated]"
                               value="1"
                               <?php checked($show_untranslated, true); ?>>
                        <?php esc_html_e('Show menu items in their original language when no translation exists', 'mini'); ?>
                    </label>
                    <p class="S grey-text mt-05">
                        <?php esc_html_e('When unchecked (default), menu items with no translation in the active language are hidden.', 'mini'); ?>
                    </p>
                </div>

            </div>

            <?php submit_button(__('Save Settings', 'mini')); ?>
        </form>
    </div>
    <?php
}

/* END - Translations settings */

/* START - Translations meta box */

function mini_add_translations_meta_boxes() {
    if (!is_mini_option_enabled('mini_translations_settings', 'mini_enable_translations')) {
        return;
    }

    $post_types = ['post', 'page'];

    if (is_mini_option_enabled('mini_content_settings', 'mini_news')) {
        $post_types[] = 'news';
    }
    if (is_mini_option_enabled('mini_content_settings', 'mini_event')) {
        $post_types[] = 'event';
    }
    if (is_mini_option_enabled('mini_content_settings', 'mini_match')) {
        $post_types[] = 'match';
    }
    if (is_mini_option_enabled('mini_content_settings', 'mini_course')) {
        $post_types[] = 'course';
        $post_types[] = 'lesson';
    }
    if (is_mini_option_enabled('mini_content_settings', 'mini_landing_page')) {
        $post_types[] = 'landing_page';
    }

    foreach ($post_types as $post_type) {
        add_meta_box(
            'mini_translations_meta_box',
            __('mini Translations', 'mini'),
            'mini_translations_meta_box_callback',
            $post_type,
            'side',
            'default'
        );
    }
}
add_action('add_meta_boxes', 'mini_add_translations_meta_boxes');

function mini_translations_meta_box_callback($post) {
    wp_nonce_field('mini_translations_meta_box', 'mini_translations_nonce');

    $languages    = mini_translations_get_languages();
    $current_lang = mini_get_post_lang($post->ID);
    $translations = mini_get_translations($post->ID);
    $post_type    = $post->post_type;
    ?>
    <p style="margin-bottom: 5px;"><strong><?php esc_html_e('Post Language', 'mini'); ?></strong></p>
    <select name="mini_lang" id="mini_lang_select" style="width: 100%;">
        <option value=""><?php esc_html_e('— not set —', 'mini'); ?></option>
        <?php foreach ($languages as $lang) : ?>
            <option value="<?php echo esc_attr($lang['code']); ?>" <?php selected($current_lang, $lang['code']); ?>>
                <?php echo esc_html($lang['label']); ?> (<?php echo esc_html($lang['code']); ?>)
            </option>
        <?php endforeach; ?>
    </select>

    <?php if (!empty($languages)) : ?>
        <p style="margin-top: 12px; margin-bottom: 4px;"><strong><?php esc_html_e('Translations', 'mini'); ?></strong></p>
        <table style="width: 100%; border-collapse: collapse;">
            <?php foreach ($languages as $lang) : ?>
                <?php
                $tid        = isset($translations[$lang['code']]) ? (int) $translations[$lang['code']] : 0;
                $post_title = $tid > 0 ? get_the_title($tid) : '';
                $edit_url   = $tid > 0 ? (string) get_edit_post_link($tid) : '';
                $view_url   = $tid > 0 ? (string) get_permalink($tid) : '';
                $is_current = ($lang['code'] === $current_lang);
                ?>
                <tr
                    class="mini-trans-row"
                    data-lang="<?php echo esc_attr($lang['code']); ?>"
                    <?php if ($is_current) : ?>style="display:none;"<?php endif; ?>
                >
                    <td style="padding: 4px 0; font-size: 12px; width: 34px; vertical-align: top; padding-top: 8px;">
                        <code><?php echo esc_html($lang['code']); ?></code>
                    </td>
                    <td style="padding: 4px 0 4px 6px; vertical-align: top; position: relative;">
                        <input
                            type="text"
                            class="mini-trans-search"
                            data-lang="<?php echo esc_attr($lang['code']); ?>"
                            value="<?php echo esc_attr($post_title); ?>"
                            placeholder="<?php esc_attr_e('Search by title…', 'mini'); ?>"
                            style="width: 100%; box-sizing: border-box;"
                            autocomplete="off"
                        >
                        <input
                            type="hidden"
                            name="mini_translations[<?php echo esc_attr($lang['code']); ?>]"
                            class="mini-trans-id"
                            value="<?php echo $tid > 0 ? esc_attr($tid) : ''; ?>"
                        >
                        <div class="mini-trans-dropdown" style="display:none; position:absolute; left:6px; right:0; z-index:9999; background:#fff; border:1px solid #c3c4c7; border-top:0; max-height:160px; overflow-y:auto; box-shadow:0 2px 6px rgba(0,0,0,.12);"></div>
                        <div class="mini-trans-meta" style="font-size:10px; color:#888; margin-top:2px; min-height:14px;">
                            <?php if ($tid > 0) : ?>
                                <span>ID: <?php echo esc_html($tid); ?></span><br/>
                                &nbsp;<a href="<?php echo esc_url($view_url); ?>" target="_blank" class="btn white-text XS">↗ <?php esc_html_e('View', 'mini'); ?></a>
                                &nbsp;<a href="<?php echo esc_url($edit_url); ?>" target="_blank" class="btn warning-btn white-text XS">✎ <?php esc_html_e('Edit', 'mini'); ?></a>
                                &nbsp;<a href="#" class="mini-trans-clear btn danger-btn white-text XS" title="<?php esc_attr_e('Remove', 'mini'); ?>">✕ <?php esc_html_e('Remove', 'mini'); ?></a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>

        <div style="margin-top: 10px;">
            <a href="<?php echo esc_url(admin_url('post-new.php?post_type=' . $post_type)); ?>" target="_blank" class="button button-small">
                <?php esc_html_e('+ Create new translation', 'mini'); ?>
            </a>
        </div>
    <?php endif; ?>

    <script type="text/javascript">
    (function($) {
        var restUrl   = <?php echo wp_json_encode(rest_url('wp/v2/search')); ?>;
        var nonce     = <?php echo wp_json_encode(wp_create_nonce('wp_rest')); ?>;
        var postType  = <?php echo wp_json_encode($post_type); ?>;
        var adminUrl  = <?php echo wp_json_encode(admin_url()); ?>;
        var noResults = <?php echo wp_json_encode(__('No results found.', 'mini')); ?>;

        function updateRows() {
            var sel = $('#mini_lang_select').val();
            $('.mini-trans-row').each(function() {
                $(this).toggle($(this).data('lang') !== sel);
            });
        }
        $('#mini_lang_select').on('change', updateRows);
        updateRows();

        var timers = {};

        $(document).on('input', '.mini-trans-search', function() {
            var $input    = $(this);
            var lang      = $input.data('lang');
            var $hidden   = $input.siblings('.mini-trans-id');
            var $dropdown = $input.siblings('.mini-trans-dropdown');
            var $meta     = $input.siblings('.mini-trans-meta');
            var term      = $input.val().trim();

            $hidden.val('');
            $meta.empty();
            clearTimeout(timers[lang]);

            if (term.length < 2) {
                $dropdown.hide().empty();
                return;
            }

            timers[lang] = setTimeout(function() {
                $.ajax({
                    url: restUrl,
                    headers: { 'X-WP-Nonce': nonce },
                    data: { search: term, type: 'post', subtype: postType, per_page: 8, _fields: 'id,title,url' },
                    success: function(results) {
                        $dropdown.empty();
                        if (!results.length) {
                            $('<div>').text(noResults).css({ padding: '6px 8px', color: '#666', fontSize: '12px' }).appendTo($dropdown);
                            $dropdown.show();
                            return;
                        }
                        results.forEach(function(item) {
                            $('<div>')
                                .text(item.title + ' (#' + item.id + ')')
                                .css({ padding: '6px 8px', cursor: 'pointer', fontSize: '12px' })
                                .on('mouseenter', function() { $(this).css('background', '#f0f0f0'); })
                                .on('mouseleave', function() { $(this).css('background', ''); })
                                .on('mousedown', function(e) {
                                    e.preventDefault();
                                    $input.val(item.title);
                                    $hidden.val(item.id);
                                    $dropdown.hide().empty();
                                    var editUrl = adminUrl + 'post.php?post=' + item.id + '&action=edit';
                                    $meta.html(
                                        '<span>ID: ' + item.id + '</span>' +
                                        ' &nbsp;<a href="' + editUrl + '" target="_blank" style="text-decoration:none;">✎</a>' +
                                        ' &nbsp;<a href="' + item.url + '" target="_blank" style="text-decoration:none;">↗</a>' +
                                        ' &nbsp;<a href="#" class="mini-trans-clear" style="color:#a00;text-decoration:none;" title="Remove">✕</a>'
                                    );
                                })
                                .appendTo($dropdown);
                        });
                        $dropdown.show();
                    }
                });
            }, 300);
        });

        $(document).on('blur', '.mini-trans-search', function() {
            var $input = $(this);
            setTimeout(function() { $input.siblings('.mini-trans-dropdown').hide().empty(); }, 200);
        });

        $(document).on('click', '.mini-trans-clear', function(e) {
            e.preventDefault();
            var $cell = $(this).closest('td');
            $cell.find('.mini-trans-search').val('');
            $cell.find('.mini-trans-id').val('');
            $cell.find('.mini-trans-meta').empty();
        });
    }(jQuery));
    </script>
    <?php
}

/* END - Translations meta box */

/* START - Translations meta box save */

function mini_save_translations_meta_box($post_id) {
    if (!isset($_POST['mini_translations_nonce'])) {
        return;
    }

    if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['mini_translations_nonce'])), 'mini_translations_meta_box')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Save language
    if (array_key_exists('mini_lang', $_POST)) {
        $lang = sanitize_key(wp_unslash($_POST['mini_lang']));
        if ($lang !== '') {
            update_post_meta($post_id, '_mini_lang', $lang);
        } else {
            delete_post_meta($post_id, '_mini_lang');
        }
    }

    // Save translations map
    $translations = [];
    if (isset($_POST['mini_translations']) && is_array($_POST['mini_translations'])) {
        foreach ($_POST['mini_translations'] as $lang_code => $tid) {
            $lang_code = sanitize_key($lang_code);
            $tid       = (int) $tid;
            if ($lang_code !== '' && $tid > 0 && get_post($tid)) {
                $translations[$lang_code] = $tid;
            }
        }
    }

    if (!empty($translations)) {
        update_post_meta($post_id, '_mini_translations', $translations);
    } else {
        delete_post_meta($post_id, '_mini_translations');
    }
}
add_action('save_post', 'mini_save_translations_meta_box');

/* END - Translations meta box save */

/* START - hreflang and og:locale head output */

function mini_output_hreflang_tags() {
    if (!is_mini_option_enabled('mini_translations_settings', 'mini_enable_translations')) {
        return;
    }

    if (!is_singular()) {
        return;
    }

    $post = get_queried_object();
    if (!$post || !isset($post->ID)) {
        return;
    }

    $urls = mini_get_all_translation_urls($post->ID);
    if (empty($urls)) {
        return;
    }

    // hreflang alternates
    foreach ($urls as $lang => $url) {
        echo '<link rel="alternate" hreflang="' . esc_attr($lang) . '" href="' . esc_url($url) . '">' . "\n";
    }

    // x-default points to the post's own language URL, or the first available
    $own_lang = mini_get_post_lang($post->ID);
    $xdefault = ($own_lang !== '' && !empty($urls[$own_lang])) ? $urls[$own_lang] : reset($urls);
    echo '<link rel="alternate" hreflang="x-default" href="' . esc_url($xdefault) . '">' . "\n";

    // og:locale for the current post language
    if ($own_lang !== '') {
        $locale = str_replace('-', '_', $own_lang);
        echo '<meta property="og:locale" content="' . esc_attr($locale) . '">' . "\n";

        // og:locale:alternate for other languages
        foreach ($urls as $lang => $url) {
            if ($lang !== $own_lang) {
                echo '<meta property="og:locale:alternate" content="' . esc_attr(str_replace('-', '_', $lang)) . '">' . "\n";
            }
        }
    }
}
add_action('wp_head', 'mini_output_hreflang_tags', 2);

/* END - hreflang and og:locale head output */

/* START - URL language prefix */

/**
 * Register rewrite rules for each configured language code.
 * e.g. ^it/(.+?)/?$ → index.php?mini_lang=it&mini_lang_path=$matches[1]
 */
function mini_translations_rewrite_rules() {
    if (!is_mini_option_enabled('mini_translations_settings', 'mini_enable_translations')) {
        return;
    }
    foreach (mini_translations_get_languages() as $lang) {
        $code = preg_quote($lang['code'], '/');
        add_rewrite_rule(
            '^' . $code . '/(.+?)/?$',
            'index.php?mini_lang=' . $lang['code'] . '&mini_lang_path=$matches[1]',
            'top'
        );
        // Bare /lang/ with no slug (e.g. /it/ when the home page is Italian).
        // The request filter will redirect this to the site home page.
        add_rewrite_rule(
            '^' . $code . '/?$',
            'index.php?mini_lang=' . $lang['code'],
            'top'
        );
    }
}
add_action('init', 'mini_translations_rewrite_rules');

function mini_translations_lang_hash() {
    return md5(wp_json_encode(array_column(mini_translations_get_languages(), 'code')));
}

/**
 * Auto-flush rewrite rules when the language set changes.
 * Uses a stored hash instead of scanning the full rewrite_rules array.
 * Skipped on AJAX and REST API requests to avoid unnecessary DB writes on
 * high-frequency endpoints.
 */
function mini_translations_maybe_flush_rules() {
    if (wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) {
        return;
    }
    if (!is_mini_option_enabled('mini_translations_settings', 'mini_enable_translations')) {
        return;
    }
    if (empty(mini_translations_get_languages())) {
        return;
    }
    $current = mini_translations_lang_hash();
    if (get_option('mini_translations_lang_hash') !== $current) {
        flush_rewrite_rules(false);
        update_option('mini_translations_lang_hash', $current, false);
    }
}
add_action('init', 'mini_translations_maybe_flush_rules', 99);

/**
 * Register custom query vars used by the language prefix rules.
 */
function mini_translations_register_query_vars($vars) {
    $vars[] = 'mini_lang';
    $vars[] = 'mini_lang_path';
    return $vars;
}
add_filter('query_vars', 'mini_translations_register_query_vars');

/**
 * Resolve a lang-prefixed request to the correct post.
 * When mini_lang_path is present, look up the post by its slug/path
 * and replace the query vars with the matching post's standard vars.
 */
function mini_translations_resolve_request($query_vars) {
    // Bare /lang/ URL with no post slug (e.g. /it/ when the home page is in Italian).
    // Redirect to the site home page rather than letting WordPress 404.
    if (!empty($query_vars['mini_lang']) && !isset($query_vars['mini_lang_path'])) {
        add_action('template_redirect', function () {
            wp_safe_redirect(home_url('/'), 302);
            exit;
        }, 0);
        return $query_vars;
    }

    if (empty($query_vars['mini_lang_path'])) {
        return $query_vars;
    }

    $path = sanitize_text_field(urldecode($query_vars['mini_lang_path']));

    $post_types = ['post', 'page'];
    if (is_mini_option_enabled('mini_content_settings', 'mini_news'))         $post_types[] = 'news';
    if (is_mini_option_enabled('mini_content_settings', 'mini_event'))        $post_types[] = 'event';
    if (is_mini_option_enabled('mini_content_settings', 'mini_match'))        $post_types[] = 'match';
    if (is_mini_option_enabled('mini_content_settings', 'mini_course')) {
        $post_types[] = 'course';
        $post_types[] = 'lesson';
    }
    if (is_mini_option_enabled('mini_content_settings', 'mini_landing_page')) $post_types[] = 'landing_page';

    // Temporarily remove our permalink filters so url_to_postid() sees the
    // canonical (un-prefixed) URLs when matching against rewrite rules.
    remove_filter('post_link',      'mini_translations_filter_permalink',      10);
    remove_filter('post_type_link', 'mini_translations_filter_permalink',      10);
    remove_filter('page_link',      'mini_translations_filter_page_permalink', 10);
    $post_id = url_to_postid(home_url('/' . $path));
    add_filter('post_link',      'mini_translations_filter_permalink',      10, 2);
    add_filter('post_type_link', 'mini_translations_filter_permalink',      10, 2);
    add_filter('page_link',      'mini_translations_filter_page_permalink', 10, 2);

    $post = $post_id ? get_post($post_id) : null;

    // Fallback: get_page_by_path covers hierarchical pages and simple slugs.
    if (!$post) {
        $post = get_page_by_path($path, OBJECT, $post_types);
    }

    // Second fallback: direct name query for non-hierarchical CPTs that
    // get_page_by_path can miss when the path includes a rewrite-base prefix.
    if (!$post) {
        $slug = basename(rtrim($path, '/'));
        $q    = new WP_Query([
            'name'           => $slug,
            'post_type'      => $post_types,
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'no_found_rows'  => true,
        ]);
        $post = !empty($q->posts) ? $q->posts[0] : null;
    }

    if (!$post) {
        return $query_vars;
    }

    // Only block redirect_canonical when it would strip the lang prefix
    // (e.g. /it/my-page/ → /my-page/). Allow other canonical redirects through.
    $current_lang = $query_vars['mini_lang'];
    add_filter('redirect_canonical', function($redirect_url, $requested_url) use ($current_lang) {
        if ($redirect_url
            && strpos($requested_url, '/' . $current_lang . '/') !== false
            && strpos($redirect_url,  '/' . $current_lang . '/') === false) {
            return false;
        }
        return $redirect_url;
    }, 10, 2);

    unset($query_vars['mini_lang_path'], $query_vars['mini_lang']);

    if ($post->post_type === 'page') {
        $query_vars['page_id'] = $post->ID;
    } else {
        $query_vars['p']         = $post->ID;
        $query_vars['post_type'] = $post->post_type;
    }

    return $query_vars;
}
add_filter('request', 'mini_translations_resolve_request');

/**
 * Flush rewrite rules whenever the translations settings are saved,
 * so language prefix rules take effect immediately.
 */
function mini_translations_flush_on_save() {
    flush_rewrite_rules();
    update_option('mini_translations_lang_hash', mini_translations_lang_hash(), false);
}
add_action('update_option_mini_translations_settings', 'mini_translations_flush_on_save');

/**
 * Filter post/page permalinks to prepend /{lang}/ for posts that have a language set.
 */
function mini_translations_filter_permalink($url, $post) {
    if (!is_mini_option_enabled('mini_translations_settings', 'mini_enable_translations')) {
        return $url;
    }
    $post_id = is_object($post) ? $post->ID : (int) $post;
    $lang    = (string) get_post_meta($post_id, '_mini_lang', true);
    if ($lang === '') {
        return $url;
    }
    $home      = home_url('/');
    $slug_part = substr($url, strlen($home));
    // URL is not under this site — leave it alone.
    if (strpos($url, $home) !== 0) {
        return $url;
    }
    // Home page has an empty slug — never prefix it (would produce /lang/ with no slug).
    if ($slug_part === '' || rtrim($slug_part, '/') === '') {
        return $url;
    }
    // Already prefixed — avoid doubling.
    if (strpos($slug_part, $lang . '/') === 0) {
        return $url;
    }
    return $home . $lang . '/' . $slug_part;
}
add_filter('post_link',      'mini_translations_filter_permalink', 10, 2);
add_filter('post_type_link', 'mini_translations_filter_permalink', 10, 2);

function mini_translations_filter_page_permalink($url, $post_id) {
    if (!is_mini_option_enabled('mini_translations_settings', 'mini_enable_translations')) {
        return $url;
    }
    $lang = (string) get_post_meta((int) $post_id, '_mini_lang', true);
    if ($lang === '') {
        return $url;
    }
    $home      = home_url('/');
    $slug_part = substr($url, strlen($home));
    if (strpos($url, $home) !== 0) {
        return $url;
    }
    if ($slug_part === '' || rtrim($slug_part, '/') === '') {
        return $url;
    }
    if (strpos($slug_part, $lang . '/') === 0) {
        return $url;
    }
    return $home . $lang . '/' . $slug_part;
}
add_filter('page_link', 'mini_translations_filter_page_permalink', 10, 2);

/* END - URL language prefix */

/* START - Language preference cookie */

/**
 * Returns the visitor's validated language preference from the cookie.
 * Always validated against the configured language list.
 */
function mini_get_lang_preference() {
    $configured = array_column(mini_translations_get_languages(), 'code');

    if (!empty($_COOKIE['mini_lang_pref'])) {
        $pref = sanitize_key($_COOKIE['mini_lang_pref']);
        if (in_array($pref, $configured, true)) {
            return $pref;
        }
    }

    // Fall back to the WordPress site language (e.g. "it_IT" → "it").
    $site_lang = strtolower(substr(get_locale(), 0, 2));
    return in_array($site_lang, $configured, true) ? $site_lang : '';
}

/**
 * Handle an explicit ?set_lang=XX request from the language switcher.
 * Sets the cookie and redirects to the same URL without the query parameter,
 * so the switcher works even on pages that have no language tag (e.g. home).
 * Runs at priority 0, before any other template_redirect logic.
 */
function mini_translations_handle_set_lang() {
    if (empty($_GET['set_lang'])) {
        return;
    }
    if (!is_mini_option_enabled('mini_translations_settings', 'mini_enable_translations')) {
        return;
    }
    $lang       = sanitize_key($_GET['set_lang']);
    $configured = array_column(mini_translations_get_languages(), 'code');
    if (!in_array($lang, $configured, true)) {
        return;
    }
    setcookie('mini_lang_pref', $lang, [
        'expires'  => time() + 30 * DAY_IN_SECONDS,
        'path'     => COOKIEPATH ?: '/',
        'domain'   => COOKIE_DOMAIN ?: '',
        'secure'   => is_ssl(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    $_COOKIE['mini_lang_pref'] = $lang;
    wp_safe_redirect(remove_query_arg('set_lang'));
    exit;
}
add_action('template_redirect', 'mini_translations_handle_set_lang', 0);

/**
 * Set the language preference cookie whenever a visitor lands on a
 * language-tagged singular page (including via the lang-prefixed URL).
 * Runs at priority 1 so the cookie is updated before the redirect check.
 */
function mini_translations_set_lang_cookie() {
    // Don't overwrite a cookie the visitor just explicitly set via ?set_lang=.
    if (!empty($_GET['set_lang'])) {
        return;
    }
    if (!is_mini_option_enabled('mini_translations_settings', 'mini_enable_translations') || !is_singular()) {
        return;
    }
    $post = get_queried_object();
    if (!$post || !isset($post->ID)) {
        return;
    }
    $lang = mini_get_post_lang($post->ID);
    if ($lang === '') {
        return;
    }
    $configured = array_column(mini_translations_get_languages(), 'code');
    if (!in_array($lang, $configured, true)) {
        return;
    }
    $current_pref = isset($_COOKIE['mini_lang_pref']) ? sanitize_key($_COOKIE['mini_lang_pref']) : '';
    if ($current_pref !== $lang) {
        setcookie('mini_lang_pref', $lang, [
            'expires'  => time() + 30 * DAY_IN_SECONDS,
            'path'     => COOKIEPATH ?: '/',
            'domain'   => COOKIE_DOMAIN ?: '',
            'secure'   => is_ssl(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        $_COOKIE['mini_lang_pref'] = $lang; // keep in sync for this request
    }
}
add_action('template_redirect', 'mini_translations_set_lang_cookie', 1);

/**
 * Redirect the visitor to their preferred language version of the current page.
 * Only fires when:
 *   - the queried post has a language tag different from the stored preference
 *   - a published translation for the preferred language exists
 * Running at priority 5 means the cookie is already updated for this request
 * (priority 1 above), so clicking a language link never loops back.
 */
function mini_translations_lang_redirect() {
    if (!is_mini_option_enabled('mini_translations_settings', 'mini_enable_translations') || !is_singular()) {
        return;
    }
    $post = get_queried_object();
    if (!$post || !isset($post->ID)) {
        return;
    }
    $post_lang = mini_get_post_lang($post->ID);
    if ($post_lang === '') {
        return;
    }
    $pref = mini_get_lang_preference();
    if ($pref === '' || $pref === $post_lang) {
        return;
    }
    $translations = mini_get_translations($post->ID);
    if (empty($translations[$pref])) {
        return;
    }
    $tid = (int) $translations[$pref];
    if (get_post_status($tid) !== 'publish') {
        return;
    }
    $redirect_url = get_permalink($tid);
    if (!$redirect_url) {
        return;
    }
    wp_safe_redirect($redirect_url, 302);
    exit;
}
add_action('template_redirect', 'mini_translations_lang_redirect', 5);

/* END - Language preference cookie */

/* START - Language-aware navigation menus */

/**
 * Rewrite nav-menu item URLs to point to the current page's language.
 *
 * Build your menu once (using whichever language's pages you like).
 * When a visitor is on an Italian page, every menu item that links to a
 * post/page which has an Italian translation will automatically point to
 * that translation instead of the original.
 *
 * Requirements: the linked post and its translation must both have
 * _mini_lang set, and the translations map must include each other
 * (bidirectional linking via the Translations meta box).
 */
function mini_translations_nav_menu_objects($items, $args) {
    if (!is_mini_option_enabled('mini_translations_settings', 'mini_enable_translations')) {
        return $items;
    }

    // Prefer the queried post's language; fall back to the stored cookie so
    // menus also adapt on archives, the home page, etc.
    $current_lang = '';
    if (is_singular()) {
        $current_post = get_queried_object();
        if ($current_post && isset($current_post->ID)) {
            $current_lang = mini_get_post_lang($current_post->ID);
        }
    }
    if ($current_lang === '') {
        $current_lang = mini_get_lang_preference();
    }
    if ($current_lang === '') {
        return $items;
    }

    $filtered = [];
    foreach ($items as $item) {
        // Non-post items (custom links, terms, etc.) are always shown.
        if (empty($item->object_id) || $item->type !== 'post_type') {
            $filtered[] = $item;
            continue;
        }

        $linked_post_id = (int) $item->object_id;
        $post_lang      = mini_get_post_lang($linked_post_id);

        // No language tag → language-neutral, always show as-is.
        if ($post_lang === '') {
            $filtered[] = $item;
            continue;
        }

        // Already in the right language → show as-is.
        if ($post_lang === $current_lang) {
            $filtered[] = $item;
            continue;
        }

        // Different language: look for a published translation in current_lang.
        // If found → swap URL + title and show. If not found → hide.
        $translations = mini_get_translations($linked_post_id);
        if (!empty($translations[$current_lang])) {
            $translated_id = (int) $translations[$current_lang];
            if (get_post_status($translated_id) === 'publish') {
                $translated_url = get_permalink($translated_id);
                if ($translated_url) {
                    $item->url   = $translated_url;
                    $item->title = get_the_title($translated_id);
                    $filtered[]  = $item;
                }
            }
        } elseif (is_mini_option_enabled('mini_translations_settings', 'mini_nav_show_untranslated')) {
            // No translation found — show item in its original language if toggled on.
            $filtered[] = $item;
        }
        // else: item is silently dropped (default).
    }

    return $filtered;
}
add_filter('wp_nav_menu_objects', 'mini_translations_nav_menu_objects', 10, 2);

/* END - Language-aware navigation menus */

/* START - Language-aware content filtering */

/**
 * Filter the main query (and any secondary public query) so that posts tagged
 * with a language show only when that language is active.
 * Posts with no _mini_lang meta are always included (language-neutral content).
 */
function mini_translations_filter_query(WP_Query $query) {
    if (!is_mini_option_enabled('mini_translations_settings', 'mini_enable_translations')) {
        return;
    }

    // Only filter front-end, non-singular listing queries.
    if (is_admin() || $query->is_singular()) {
        return;
    }

    // Skip REST / AJAX / feed requests.
    if ((defined('REST_REQUEST') && REST_REQUEST) || wp_doing_ajax() || $query->is_feed()) {
        return;
    }

    $current_lang = mini_get_lang_preference();
    if ($current_lang === '') {
        return;
    }

    // Merge with any existing meta_query so we don't overwrite other filters.
    $existing = $query->get('meta_query') ?: [];
    $lang_clause = [
        'relation' => 'OR',
        [
            'key'     => '_mini_lang',
            'value'   => $current_lang,
            'compare' => '=',
        ],
        [
            'key'     => '_mini_lang',
            'compare' => 'NOT EXISTS',
        ],
        [
            'key'     => '_mini_lang',
            'value'   => '',
            'compare' => '=',
        ],
    ];

    if (!empty($existing)) {
        $query->set('meta_query', ['relation' => 'AND', $existing, $lang_clause]);
    } else {
        $query->set('meta_query', $lang_clause);
    }
}
add_action('pre_get_posts', 'mini_translations_filter_query');

/* END - Language-aware content filtering */

