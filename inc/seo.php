<?php
/* START - SEO settings */
function mini_seo_settings_init() {
    register_setting('mini_seo', 'mini_seo_settings', [
        'sanitize_callback' => 'mini_seo_settings_sanitize',
    ]);
    add_settings_section(
        'mini_seo_section',
        __( '<i>mini</i> SEO settings', 'mini' ),
        'mini_seo_section_callback',
        'mini-seo'
    );
}
add_action( 'admin_init', 'mini_seo_settings_init' );

function mini_seo_settings_sanitize($input) {
    $input = is_array($input) ? $input : [];

    // Always persist checkboxes as explicit 0/1 values.
    $input['mini_enable_seo'] = !empty($input['mini_enable_seo']) ? '1' : '0';
    $input['mini_enable_sitemap'] = !empty($input['mini_enable_sitemap']) ? '1' : '0';
    $input['mini_sitemap_include_empty'] = !empty($input['mini_sitemap_include_empty']) ? '1' : '0';

    if (isset($input['default_description'])) {
        $input['default_description'] = sanitize_textarea_field($input['default_description']);
    }

    if (isset($input['default_keywords'])) {
        $input['default_keywords'] = sanitize_textarea_field($input['default_keywords']);
    }

    if (isset($input['default_image'])) {
        $input['default_image'] = esc_url_raw($input['default_image']);
    }

    if (isset($input['robots_custom_rules'])) {
        $input['robots_custom_rules'] = sanitize_textarea_field($input['robots_custom_rules']);
    }

    mini_sitemap_flush_cache();

    return $input;
}

function mini_sitemap_get_default_robots_content($sitemap_enabled = null) {
    if ($sitemap_enabled === null) {
        $sitemap_enabled = mini_sitemap_is_enabled();
    }

    $robots_lines = [
        'User-agent: *',
    ];

    if (get_option('blog_public')) {
        $robots_lines[] = 'Disallow:';
        if ($sitemap_enabled) {
            $robots_lines[] = 'Sitemap: ' . home_url('/sitemap.xml');
        }
    } else {
        $robots_lines[] = 'Disallow: /';
    }

    return implode("\n", $robots_lines);
}

function mini_sitemap_get_configured_robots_content($sitemap_enabled = null) {
    if ($sitemap_enabled === null) {
        $sitemap_enabled = mini_sitemap_is_enabled();
    }

    $options = get_option('mini_seo_settings');
    $custom_rules = '';
    if (is_array($options) && isset($options['robots_custom_rules'])) {
        $custom_rules = trim((string) $options['robots_custom_rules']);
    }

    $content = $custom_rules !== '' ? $custom_rules : mini_sitemap_get_default_robots_content($sitemap_enabled);
    $content = trim(str_replace("\r\n", "\n", $content));

    if ($sitemap_enabled) {
        $sitemap_line = 'Sitemap: ' . home_url('/sitemap.xml');
        if (stripos($content, $sitemap_line) === false) {
            $content .= "\n" . $sitemap_line;
        }
    }

    return trim($content) . "\n";
}

function mini_seo_section_callback( $args ) {
    $seo_enabled = is_mini_option_enabled('mini_seo_settings', 'mini_enable_seo');
    $seo_options = get_option('mini_seo_settings');
    $sitemap_explicit = is_array($seo_options) && array_key_exists('mini_enable_sitemap', $seo_options);
    $sitemap_enabled = $sitemap_explicit ? !empty($seo_options['mini_enable_sitemap']) : $seo_enabled;
    $include_empty = !isset($seo_options['mini_sitemap_include_empty']) || !empty($seo_options['mini_sitemap_include_empty']);

    $robots_preview = mini_sitemap_get_configured_robots_content($sitemap_enabled);
    ?>
    <div class="space"></div>
    <div class="mini-seo-settings-tabs">
        <h2 class="nav-tab-wrapper" style="margin-bottom: 12px;">
            <a href="#" class="nav-tab nav-tab-active mini-seo-settings-tab" data-tab="features"><?php esc_html_e('SEO Features', 'mini'); ?></a>
            <a href="#" class="nav-tab mini-seo-settings-tab" data-tab="general"><?php esc_html_e('General SEO Settings', 'mini'); ?></a>
            <a href="#" class="nav-tab mini-seo-settings-tab" data-tab="sitemap"><?php esc_html_e('Sitemap', 'mini'); ?></a>
            <a href="#" class="nav-tab mini-seo-settings-tab" data-tab="robots"><?php esc_html_e('Robots.txt', 'mini'); ?></a>
        </h2>

        <div id="mini-seo-settings-features" class="mini-seo-settings-tab-content" style="display: block;">
            <div class="boxes align-items-start">
                <div class="box-100 p-2 white-bg b-rad-5 box-shadow">
                    <h4><?php esc_html_e('Enable SEO Features', 'mini'); ?></h4>
                    <label for="mini_enable_seo">
                        <input
                            type="checkbox"
                            id="mini_enable_seo"
                            name="mini_seo_settings[mini_enable_seo]"
                            class="me-1"
                            value="1"
                            <?php checked($seo_enabled, true); ?>
                        >
                        <?php esc_html_e('Enable SEO meta tags for pages, posts and custom content types.', 'mini'); ?>
                    </label>
                    <p class="S grey-text"><?php esc_html_e("When enabled, you'll be able to customize title, description, keywords, robots directives, and social media tags for each page/post.", 'mini'); ?></p>
                </div>
            </div>
        </div>

        <div id="mini-seo-settings-general" class="mini-seo-settings-tab-content" style="display: none;">
            <div class="boxes align-items-start">
                <div class="box-100 p-2 white-bg b-rad-5 box-shadow">
                    <h4><?php esc_html_e('Default Settings', 'mini'); ?></h4>
                    <label for="mini_seo_image" class="bold"><?php _e('Default Meta Description', 'mini'); ?>:</label>
                    <textarea
                        name="mini_seo_settings[default_description]"
                        rows="3"
                        style="width: 100%;"
                        maxlength="160"
                        placeholder="Default site description (max 160 characters)"
                    ><?php echo esc_attr(get_variable('mini_seo_settings', 'default_description')); ?></textarea>
                    <p class="S grey-text mt-05"><?php esc_html_e("This will be used when individual pages don't have their own description.", 'mini'); ?></p>
                    <div class="sep my-2 light-grey-border"></div>
                    <label for="mini_default_keywords" class="bold"><?php _e('Default Website Keywords', 'mini'); ?>:</label>
                    <textarea
                        id="mini_default_keywords"
                        name="mini_seo_settings[default_keywords]"
                        rows="3"
                        style="width: 100%;"
                        placeholder="keyword one, keyword two, keyword three"
                    ><?php echo esc_textarea(get_variable('mini_seo_settings', 'default_keywords')); ?></textarea>
                    <p class="S grey-text mt-05"><?php esc_html_e('Used as fallback meta keywords when a page/post has no custom keywords.', 'mini'); ?></p>
                    <div class="sep my-2 light-grey-border"></div>
                    <label for="mini_seo_image" class="bold"><?php _e('Default SEO Image', 'mini'); ?>:</label>
                    <div class="flex mb-1">
                        <button type="button" class="button me-1" id="mini_seo_default_image_button" style="width: 120px;">Select Image</button>
                        <input
                            type="url"
                            name="mini_seo_settings[default_image]"
                            id="mini_seo_default_image"
                            value="<?php echo esc_url(get_variable('mini_seo_settings', 'default_image')); ?>"
                            placeholder="https://"
                        >
                    </div>
                    <p class="desc XS">
                        <?php _e('Fallback image for SEO/social media when no custom or featured image is set.<br><strong>Recommended: 1200*630px</strong>', 'mini'); ?>
                    </p>
                    <div class="space-2"></div>
                    <?php $default_image = get_variable('mini_seo_settings', 'default_image'); ?>
                    <div id="mini_seo_default_preview_container" class="b-rad-10 oh box-shadow-light" style="max-width: 480px;<?php echo !$default_image ? ' display: none;' : ''; ?>">
                        <div style="position: relative; width: 100%; padding-bottom: 52.5%; background: #eee; overflow: hidden;">
                            <img id="mini_seo_default_preview_image" src="<?php echo esc_url($default_image); ?>" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover;">
                        </div>
                        <div class="white-bg p-1">
                            <p class="desc XS mb-0">
                                <?php _e('Preview in Open Graph format (1.91:1 ratio)', 'mini'); ?>
                                <span id="mini_seo_default_dimensions" style="margin-left: 10px; color: #666;"></span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="mini-seo-settings-sitemap" class="mini-seo-settings-tab-content" style="display: none;">
            <div class="boxes align-items-start">
                <div class="box-100 p-2 white-bg b-rad-5 box-shadow">
                    <h4><?php esc_html_e('Sitemap', 'mini'); ?></h4>
                    <label for="mini_enable_sitemap">
                        <input
                            type="checkbox"
                            id="mini_enable_sitemap"
                            name="mini_seo_settings[mini_enable_sitemap]"
                            class="me-1"
                            value="1"
                            <?php checked($sitemap_enabled, true); ?>
                        >
                        <?php esc_html_e('Enable mini XML sitemap', 'mini'); ?>
                    </label>
                    <p class="S grey-text"><?php esc_html_e('Generates custom sitemap endpoints like', 'mini'); ?> <code><?php echo esc_html(home_url('/sitemap.xml')); ?></code>.</p>

                    <label for="mini_sitemap_include_empty">
                        <input
                            type="checkbox"
                            id="mini_sitemap_include_empty"
                            name="mini_seo_settings[mini_sitemap_include_empty]"
                            class="me-1"
                            value="1"
                            <?php checked($include_empty, true); ?>
                        >
                        <?php esc_html_e('Include empty post type sitemaps in index', 'mini'); ?>
                    </label>
                    <p class="S grey-text"><?php esc_html_e('If disabled, only post-type sitemaps containing at least one indexable URL are listed in the sitemap index.', 'mini'); ?></p>
                    <div class="space-2"></div>
                    <div class="sep"></div>
                    <div class="space-3"></div>
                    <button type="submit" class="button button-secondary" form="mini-refresh-sitemap-form">
                        <?php esc_html_e('Update Sitemap Now', 'mini'); ?>
                    </button>
                    <p class="description" style="margin-top: 8px;">
                        <?php esc_html_e('Clears mini sitemap cache and regenerates XML on next request.', 'mini'); ?>
                    </p>
                </div>
            </div>
        </div>

        <div id="mini-seo-settings-robots" class="mini-seo-settings-tab-content" style="display: none;">
            <div class="boxes align-items-start">
                <div class="box-100 p-2 white-bg b-rad-5 box-shadow">
                    <h4><?php esc_html_e('Robots.txt', 'mini'); ?></h4>
                    <label for="mini_robots_preview" class="bold"><?php _e('Robots.txt Rules', 'mini'); ?></label>
                    <textarea id="mini_robots_preview" name="mini_seo_settings[robots_custom_rules]" rows="10" style="width: 100%; font-family: monospace;"><?php echo esc_textarea($robots_preview); ?></textarea>
                    <p class="S grey-text"><?php esc_html_e('Edit robots.txt directives directly here. When sitemap is enabled, the sitemap line is automatically enforced.', 'mini'); ?></p>
                    <button type="submit" class="button button-secondary" form="mini-reset-robots-form" onclick="return confirm('Reset robots.txt rules to default?');">
                        <?php esc_html_e('Reset robots.txt to default', 'mini'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php
}

function mini_seo_page_html() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    
    if ( isset( $_GET['settings-updated'] ) ) {
        add_settings_error( 'mini_messages', 'mini_message', __( 'Settings Saved', 'mini' ), 'updated' );
    }

    if ( isset( $_GET['mini-sitemap-updated'] ) ) {
        add_settings_error( 'mini_messages', 'mini_sitemap_message', __( 'Sitemap cache updated.', 'mini' ), 'updated' );
    }

    if ( isset( $_GET['mini-robots-reset'] ) ) {
        add_settings_error( 'mini_messages', 'mini_robots_reset_message', __( 'Robots.txt rules reset to default.', 'mini' ), 'updated' );
    }

    settings_errors( 'mini_messages' );
    wp_enqueue_media();
    $default_image = (string) get_variable('mini_seo_settings', 'default_image');
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <br/>
        <form action="options.php" method="post">
            <?php
            settings_fields( 'mini_seo' );
            do_settings_sections( 'mini-seo' );
            submit_button( 'Save Settings' );
            ?>
        </form>

        <form id="mini-refresh-sitemap-form" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" style="display: none;">
            <?php wp_nonce_field('mini_refresh_sitemap_action', 'mini_refresh_sitemap_nonce'); ?>
            <input type="hidden" name="action" value="mini_refresh_sitemap">
        </form>

        <form id="mini-reset-robots-form" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" style="display: none;">
            <?php wp_nonce_field('mini_reset_robots_action', 'mini_reset_robots_nonce'); ?>
            <input type="hidden" name="action" value="mini_reset_robots">
        </form>
    </div>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        function miniSeoSettingsActivateTab(tab) {
            if (!tab || !$('#mini-seo-settings-' + tab).length) {
                tab = 'features';
            }

            $('.mini-seo-settings-tab').removeClass('nav-tab-active');
            $('.mini-seo-settings-tab[data-tab="' + tab + '"]').addClass('nav-tab-active');

            $('.mini-seo-settings-tab-content').hide();
            $('#mini-seo-settings-' + tab).show();

            try {
                localStorage.setItem('miniSeoSettingsActiveTab', tab);
            } catch (e) {}

            if (window.location.hash !== '#mini-seo-tab=' + tab) {
                window.location.hash = 'mini-seo-tab=' + tab;
            }
        }

        var tabFromHash = '';
        if (window.location.hash.indexOf('#mini-seo-tab=') === 0) {
            tabFromHash = window.location.hash.replace('#mini-seo-tab=', '');
        }

        var tabFromStorage = '';
        try {
            tabFromStorage = localStorage.getItem('miniSeoSettingsActiveTab') || '';
        } catch (e) {}

        miniSeoSettingsActivateTab(tabFromHash || tabFromStorage || 'features');

        $('.mini-seo-settings-tab').on('click', function(e) {
            e.preventDefault();
            var tab = $(this).data('tab');
            miniSeoSettingsActivateTab(tab);
        });

        // Function to check image dimensions
        function checkImageDimensions(imageUrl, callback) {
            var img = new Image();
            img.onload = function() {
                callback(this.width, this.height);
            };
            img.onerror = function() {
                callback(null, null);
            };
            img.src = imageUrl;
        }
        
        // Function to update dimension display
        function updateDimensionDisplay($element, width, height) {
            if (width && height) {
                var isOptimal = (width === 1200 && height === 630) || (width === 1200 && height === 628);
                var ratio = (width / height).toFixed(2);
                var color = isOptimal ? '#0a0' : (ratio >= 1.85 && ratio <= 1.95 ? '#f90' : '#d00');
                $element.html('(<span style="color: ' + color + ';">' + width + '×' + height + 'px</span>)');
            } else {
                $element.html('');
            }
        }
        
        // Media uploader for default image
        var defaultImageFrame;
        $('#mini_seo_default_image_button').on('click', function(e) {
            e.preventDefault();
            
            if (defaultImageFrame) {
                defaultImageFrame.open();
                return;
            }
            
            defaultImageFrame = wp.media({
                title: 'Select Default SEO Image',
                button: {
                    text: 'Use this image'
                },
                multiple: false
            });
            
            defaultImageFrame.on('select', function() {
                var attachment = defaultImageFrame.state().get('selection').first().toJSON();
                $('#mini_seo_default_image').val(attachment.url).trigger('change');
            });
            
            defaultImageFrame.open();
        });
        
        // Update preview when default image URL changes
        $('#mini_seo_default_image').on('change input', function() {
            var imageUrl = $(this).val().trim();
            var $previewContainer = $('#mini_seo_default_preview_container');
            var $previewImage = $('#mini_seo_default_preview_image');
            var $dimensions = $('#mini_seo_default_dimensions');
            
            if (imageUrl) {
                $previewImage.attr('src', imageUrl);
                $previewContainer.show();
                
                // Check dimensions
                checkImageDimensions(imageUrl, function(width, height) {
                    updateDimensionDisplay($dimensions, width, height);
                });
            } else {
                $previewContainer.hide();
                $dimensions.html('');
            }
        });
        
        // Check initial dimensions if image exists
        <?php if ($default_image): ?>
        checkImageDimensions('<?php echo esc_js($default_image); ?>', function(width, height) {
            updateDimensionDisplay($('#mini_seo_default_dimensions'), width, height);
        });
        <?php endif; ?>
    });
    </script>
    <?php
}

function mini_refresh_sitemap_action_handler() {
    if ( ! current_user_can('manage_options') ) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'mini'));
    }

    check_admin_referer('mini_refresh_sitemap_action', 'mini_refresh_sitemap_nonce');

    mini_sitemap_flush_cache();

    $redirect = add_query_arg([
        'page' => 'mini-seo',
        'mini-sitemap-updated' => '1',
    ], admin_url('admin.php'));

    wp_safe_redirect($redirect);
    exit;
}
add_action('admin_post_mini_refresh_sitemap', 'mini_refresh_sitemap_action_handler');

function mini_reset_robots_action_handler() {
    if ( ! current_user_can('manage_options') ) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'mini'));
    }

    check_admin_referer('mini_reset_robots_action', 'mini_reset_robots_nonce');

    $options = get_option('mini_seo_settings');
    if (!is_array($options)) {
        $options = [];
    }

    $options['robots_custom_rules'] = mini_sitemap_get_default_robots_content(mini_sitemap_is_enabled());
    update_option('mini_seo_settings', $options);

    $redirect = add_query_arg([
        'page' => 'mini-seo',
        'mini-robots-reset' => '1',
    ], admin_url('admin.php'));

    wp_safe_redirect($redirect);
    exit;
}
add_action('admin_post_mini_reset_robots', 'mini_reset_robots_action_handler');
/* END - SEO settings */

/* START - SEO meta boxes */
function mini_add_seo_meta_boxes() {
    if (!is_mini_option_enabled('mini_seo_settings', 'mini_enable_seo')) {
        return;
    }
    
    $post_types = ['post', 'page'];
    
    // Add custom post types if enabled
    if (is_mini_option_enabled('mini_content_settings', 'mini_news')) {
        $post_types[] = 'news';
    }
    if (is_mini_option_enabled('mini_content_settings', 'mini_event')) {
        $post_types[] = 'event';
    }
    if (is_mini_option_enabled('mini_content_settings', 'mini_match')) {
        $post_types[] = 'match';
    }
    if (is_mini_option_enabled('mini_content_settings', 'mini_slide')) {
        $post_types[] = 'slide';
    }
    if (is_mini_option_enabled('mini_content_settings', 'mini_course')) {
        $post_types[] = 'course';
        $post_types[] = 'lesson';
    }
    
    foreach ($post_types as $post_type) {
        add_meta_box(
            'mini_seo_meta_box',
            __('mini SEO', 'mini'),
            'mini_seo_meta_box_callback',
            $post_type,
            'normal',
            'high'
        );
    }
}
add_action('add_meta_boxes', 'mini_add_seo_meta_boxes');

function mini_seo_meta_box_callback($post) {
    wp_nonce_field('mini_seo_meta_box', 'mini_seo_nonce');
    
    $active_tab = isset($_GET['seo_tab']) ? $_GET['seo_tab'] : 'seo';
    
    // Get current values
    $seo_title = get_post_meta($post->ID, '_mini_seo_title', true);
    $seo_description = get_post_meta($post->ID, '_mini_seo_description', true);
    $seo_keywords = get_post_meta($post->ID, '_mini_seo_keywords', true);
    $seo_robots_index = get_post_meta($post->ID, '_mini_seo_robots_index', true);
    $seo_robots_follow = get_post_meta($post->ID, '_mini_seo_robots_follow', true);
    $seo_og_title = get_post_meta($post->ID, '_mini_seo_og_title', true);
    $seo_og_description = get_post_meta($post->ID, '_mini_seo_og_description', true);
    $seo_og_image = get_post_meta($post->ID, '_mini_seo_og_image', true);
    $seo_twitter_card = get_post_meta($post->ID, '_mini_seo_twitter_card', true);
    $seo_twitter_title = get_post_meta($post->ID, '_mini_seo_twitter_title', true);
    $seo_twitter_description = get_post_meta($post->ID, '_mini_seo_twitter_description', true);
    $seo_image = get_post_meta($post->ID, '_mini_seo_image', true);
    $seo_canonical = get_post_meta($post->ID, '_mini_seo_canonical', true);
    
    $preview_url = get_permalink($post->ID) ?: home_url();

    // Treat unset value as indexable/followable by default; only explicit "0" means noindex/nofollow.
    $seo_noindex_checked = ($seo_robots_index === '0');
    $seo_nofollow_checked = ($seo_robots_follow === '0');
    $seo_twitter_card_value = !empty($seo_twitter_card) ? $seo_twitter_card : 'summary';
    
    // Determine preview image: custom SEO image → featured image → default image
    $preview_image = '';
    if ($seo_image) {
        $preview_image = $seo_image;
    } elseif (has_post_thumbnail($post->ID)) {
        $preview_image = get_the_post_thumbnail_url($post->ID, 'large');
    } else {
        $preview_image = get_variable('mini_seo_settings', 'default_image');
    }
    
    ?>
    <div class="mini-seo-tabs">
        <h2 class="nav-tab-wrapper">
            <a href="#seo" class="nav-tab mini-seo-tab <?php echo $active_tab == 'seo' ? 'nav-tab-active' : ''; ?>"><?php _e('SEO', 'mini'); ?></a>
            <a href="#keywords" class="nav-tab mini-seo-tab <?php echo $active_tab == 'keywords' ? 'nav-tab-active' : ''; ?>"><?php _e('Keywords', 'mini'); ?></a>
            <a href="#robots" class="nav-tab mini-seo-tab <?php echo $active_tab == 'robots' ? 'nav-tab-active' : ''; ?>"><?php _e('Robots', 'mini'); ?></a>
            <a href="#facebook" class="nav-tab mini-seo-tab <?php echo $active_tab == 'facebook' ? 'nav-tab-active' : ''; ?>"><?php _e('Facebook', 'mini'); ?></a>
            <a href="#twitter" class="nav-tab mini-seo-tab <?php echo $active_tab == 'twitter' ? 'nav-tab-active' : ''; ?>"><?php _e('X (Twitter)', 'mini'); ?></a>
            <a href="#advanced" class="nav-tab mini-seo-tab <?php echo $active_tab == 'advanced' ? 'nav-tab-active' : ''; ?>"><?php _e('Advanced', 'mini'); ?></a>
        </h2>
        
        <div id="seo" class="mini-seo-tab-content" style="display: <?php echo $active_tab == 'seo' ? 'block' : 'none'; ?>;">
            <div class="boxes">
                <div class="box-40">
                    <label class="bold mb-0"><?php _e('Preview', 'mini'); ?></label>
                    <p class="mt-0" style="color: #006621;"><?php echo esc_html($preview_url); ?></p>
                    <div class="space"></div>
                    <?php if ($preview_image): ?>
                    <div id="mini_seo_preview_container" class="b-rad-10 oh box-shadow-light" style="max-width: 480px;">
                        <div style="position: relative; width: 100%; padding-bottom: 52.5%; background: #eee; overflow: hidden;">
                            <img id="mini_seo_preview_image" src="<?php echo esc_url($preview_image); ?>" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover;">
                        </div>
                        <div class="white-bg p-2">
                            <p id="mini_seo_preview_title" class="bold L mb-05">
                                <?php echo esc_html($seo_title ?: get_the_title($post->ID)); ?>
                            </p>
                            <p id="mini_seo_preview_description" class="S grey-text mt-0">
                                <?php echo esc_html($seo_description ?: get_variable('mini_seo_settings', 'default_description')); ?>
                            </p>
                            <p class="S light-grey-text up-case">
                                <?php echo esc_html(parse_url($preview_url, PHP_URL_HOST)); ?>
                            </p>
                        </div>
                    </div>
                    <div class="space-2"></div>
                        <p class="desc XS">
                            <span id="mini_seo_preview_source">
                                <?php _e('Using:', 'mini'); ?> <b><?php 
                                if ($seo_image) {
                                    _e('custom SEO image', 'mini');
                                } elseif (has_post_thumbnail($post->ID)) {
                                    _e('featured image', 'mini');
                                } else {
                                    _e('default SEO image', 'mini');
                                }
                                ?></b>
                            </span>
                            <span id="mini_seo_image_dimensions" class="grey-text"></span>
                        </p>
                        <p class="desc XS">
                            <?php _e('Preview shown in Open Graph format (1.91:1 ratio). Recommended: 1200×630px', 'mini'); ?>
                        </p>
                    <?php else: ?>
                    <div id="mini_seo_preview_container" style="max-width: 500px; margin-top: 15px; border: 1px solid #ddd; border-radius: 8px; overflow: hidden; background: #fff; display: none;">
                        <div style="position: relative; width: 100%; padding-bottom: 52.5%; background: #eee; overflow: hidden;">
                            <img id="mini_seo_preview_image" src="" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover;">
                        </div>
                        <div style="padding: 12px 16px; background: #f6f7f9;">
                            <div id="mini_seo_preview_title" style="font-size: 16px; font-weight: 600; color: #1d2129; margin-bottom: 4px; line-height: 1.3; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;"></div>
                            <div id="mini_seo_preview_description" style="font-size: 14px; color: #606770; line-height: 1.4; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;"></div>
                            <div style="font-size: 12px; color: #8a8d91; margin-top: 8px; text-transform: uppercase;">
                                <?php echo esc_html(parse_url($preview_url, PHP_URL_HOST)); ?>
                            </div>
                        </div>
                        <p class="desc">
                            <span id="mini_seo_preview_source"></span>
                            <span id="mini_seo_image_dimensions" style="margin-left: 10px; color: #666;"></span>
                        </p>
                        <p class="desc">
                            <?php _e('Preview shown in Open Graph format (1.91:1 ratio). Recommended: 1200×630px', 'mini'); ?>
                        </p>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="box-60">
                
                    <label for="mini_seo_title" class="bold"><?php _e('Title', 'mini'); ?></label>
                    <input type="text" id="mini_seo_title" class="mb-05" name="mini_seo_title" value="<?php echo esc_attr($seo_title); ?>" maxlength="60">
                    <p class="desc">
                        <span id="mini_seo_title_count">0</span> <?php _e('characters. Most search engines use a maximum of 60 chars for the title.', 'mini'); ?>
                    </p>
                    <div class="space-2"></div>
                    <label for="mini_seo_description" class="bold"><?php _e('Description', 'mini'); ?></label>
                    <textarea id="mini_seo_description" class="mb-05" name="mini_seo_description" rows="4" style="width: 100%;" maxlength="160"><?php echo esc_textarea($seo_description); ?></textarea>
                    <p class="desc">
                        <span id="mini_seo_description_count" class="bold">0</span> <?php _e('characters. Most search engines use a maximum of 160 chars for the description.', 'mini'); ?>
                    </p>
                    <div class="space-2"></div>
                    <label for="mini_seo_image" class="bold"><?php _e('Custom SEO Image', 'mini'); ?></label>
                    <div class="flex mb-1">
                        <button type="button" class="button me-1" id="mini_seo_image_button" style="width: 120px;">Select Image</button>
                        <input type="url" id="mini_seo_image" name="mini_seo_image" value="<?php echo esc_url($seo_image); ?>" placeholder="https://">
                    </div>
                    <p class="desc XS">
                        <span class="bold"><?php _e('Optional:', 'mini'); ?></span><br/>
                        <?php _e('Set a custom image for SEO/social media. <br/>If not set, the featured image will be used. <br/>If no featured image exists, the default SEO image will be used.', 'mini'); ?>
                    </p>
                    <p class="desc XS">
                        <span class="bold"><?php _e('Recommended dimensions:', 'mini'); ?></span><br/>
                        <strong></strong> 1200x630px (Open Graph) | 1200x628px (Twitter) | 400x400px (Twitter Summary)
                    </p>

                </div>
                </div>
            </div>
        </div>
        
        <div id="keywords" class="mini-seo-tab-content" style="display: <?php echo $active_tab == 'keywords' ? 'block' : 'none'; ?>;">
            <div class="boxes">
                <div class="box-66">
                    <label for="mini_seo_keywords" class="bold"><?php _e('Keywords', 'mini'); ?></label>
                    <textarea id="mini_seo_keywords" name="mini_seo_keywords" rows="4" style="width: 100%;"><?php echo esc_textarea($seo_keywords); ?></textarea>
                    <p class="desc"><?php _e('Separate keywords with commas. Example: <i>keyword1</i>, <i>keyword2</i>, <i>keyword3</i>', 'mini'); ?></p>
                    
                    <div style="margin-top: 15px;">
                        <button type="button" class="button" id="mini_analyze_content"><?php _e('Analyze Content for Keywords', 'mini'); ?></button>
                        <p class="desc" style="margin-top: 8px;"><?php _e('Click to extract the most frequently used words from your content', 'mini'); ?></p>
                    </div>
                    
                    <div id="mini_keyword_suggestions" style="margin-top: 15px; display: none;">
                        <p class="label bold"><?php _e('Suggested Keywords', 'mini'); ?> <span class="S light"><?php _e('(click to add)', 'mini'); ?></span>:</p>
                        <div id="mini_keyword_suggestions_list" style="display: flex; flex-wrap: wrap; gap: 8px;"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div id="robots" class="mini-seo-tab-content" style="display: <?php echo $active_tab == 'robots' ? 'block' : 'none'; ?>;">
            <div class="boxes">
                <div class="box-66">
                    <label class="bold"><?php _e('Robots Meta Tag', 'mini'); ?></label>
                    <div class="space-2"></div>
                    <label>
                        <input type="checkbox" name="mini_seo_noindex" value="1" <?php checked($seo_noindex_checked, true); ?>>
                        <b><?php _e('Noindex', 'mini'); ?></b> <span class="S"><?php _e('(prevent search engines from indexing this page)', 'mini'); ?></span>
                    </label>
                    <label>
                        <input type="checkbox" name="mini_seo_nofollow" value="1" <?php checked($seo_nofollow_checked, true); ?>>
                        <b><?php _e('Nofollow', 'mini'); ?></b> <span class="S"><?php _e('(prevent search engines from following links on this page)', 'mini'); ?></span>
                    </label>
                </div>
            </div>
        </div>
        
        <div id="facebook" class="mini-seo-tab-content" style="display: <?php echo $active_tab == 'facebook' ? 'block' : 'none'; ?>;">
            <div class="boxes">
                <div class="box-66">
                    <h5 class=""><?php _e('Open Graph (Facebook) Tags', 'mini'); ?></h5>
                    <div class="space-2"></div>
                    <label for="mini_seo_og_title" class="bold"><?php _e('OG Title', 'mini'); ?></label>
                    <input type="text" id="mini_seo_og_title" name="mini_seo_og_title" value="<?php echo esc_attr($seo_og_title); ?>" placeholder="<?php _e('Uses SEO Title if left empty', 'mini'); ?>">
                    <p class="desc XS"><?php _e('Leave empty to automatically use the SEO Title. Updates automatically unless you manually edit this field.', 'mini'); ?></p>
                    <div class="space-2"></div>
                    <label for="mini_seo_og_description" class="bold"><?php _e('OG Description', 'mini'); ?></label>
                    <textarea id="mini_seo_og_description" name="mini_seo_og_description" rows="3" style="width: 100%;" placeholder="<?php _e('Uses SEO Description if left empty', 'mini'); ?>"><?php echo esc_textarea($seo_og_description); ?></textarea>
                    <p class="desc XS"><?php _e('Leave empty to automatically use the SEO Description. Updates automatically unless you manually edit this field.', 'mini'); ?></p>
                    <div class="space-2"></div>
                    <label for="mini_seo_og_image" class="bold"><?php _e('OG Image URL', 'mini'); ?></label>
                    <input type="url" id="mini_seo_og_image" name="mini_seo_og_image" value="<?php echo esc_url($seo_og_image); ?>" style="width: 100%;" placeholder="https://">
                </div>
            </div>
        </div>
        
        <div id="twitter" class="mini-seo-tab-content" style="display: <?php echo $active_tab == 'twitter' ? 'block' : 'none'; ?>;">
            <div class="boxes">
                <div class="box-66">
                    <h5 class=""><?php _e('X (Twitter) Card Tags', 'mini'); ?></h5>
                    <div class="space-2"></div>
                    <label for="mini_seo_twitter_card" class="bold"><?php _e('Card Type', 'mini'); ?></label>
                    <select id="mini_seo_twitter_card" name="mini_seo_twitter_card" style="width: 100%;">
                        <option value="summary" <?php selected($seo_twitter_card_value, 'summary'); ?>><?php _e('Summary', 'mini'); ?></option>
                        <option value="summary_large_image" <?php selected($seo_twitter_card_value, 'summary_large_image'); ?>><?php _e('Summary Large Image', 'mini'); ?></option>
                    </select>
                    <div class="space-2"></div>
                    <label for="mini_seo_twitter_title" class="bold"><?php _e('Twitter Title', 'mini'); ?></label>
                    <input type="text" id="mini_seo_twitter_title" name="mini_seo_twitter_title" value="<?php echo esc_attr($seo_twitter_title); ?>" style="width: 100%;" placeholder="<?php _e('Uses SEO Title if left empty', 'mini'); ?>">
                    <p class="desc XS"><?php _e('Leave empty to automatically use the SEO Title. Updates automatically unless you manually edit this field.', 'mini'); ?></p>
                    <div class="space-2"></div>
                    <label for="mini_seo_twitter_description" class="bold"><?php _e('Twitter Description', 'mini'); ?></label>
                    <textarea id="mini_seo_twitter_description" name="mini_seo_twitter_description" rows="3" placeholder="<?php _e('Uses SEO Description if left empty', 'mini'); ?>"><?php echo esc_textarea($seo_twitter_description); ?></textarea>
                    <p class="desc XS"><?php _e('Leave empty to automatically use the SEO Description. Updates automatically unless you manually edit this field.', 'mini'); ?></p>
                </div>
            </div>
        </div>
        
        <div id="advanced" class="mini-seo-tab-content" style="display: <?php echo $active_tab == 'advanced' ? 'block' : 'none'; ?>;">
            <div class="boxes">
                <div class="box-66">
                    <h5><?php _e('Advanced SEO Settings', 'mini'); ?></h5>
                    <div class="space-2"></div>
                    
                    <label for="mini_seo_canonical" class="bold"><?php _e('Canonical URL', 'mini'); ?></label>
                    <input type="url" id="mini_seo_canonical" name="mini_seo_canonical" value="<?php echo esc_url($seo_canonical); ?>" style="width: 100%;" placeholder="<?php echo esc_url(get_permalink($post->ID)); ?>">
                    <p class="desc">
                        <?php _e('The canonical URL tells search engines which URL is the primary/preferred version of this content.', 'mini'); ?>
                    </p>
                    <p class="desc XS">
                        <strong><?php _e('Leave empty to use the default:', 'mini'); ?></strong> <code><?php echo esc_url(get_permalink($post->ID)); ?></code>
                    </p>
                    <p class="desc XS">
                        <strong><?php _e('When to use:', 'mini'); ?></strong><br>
                        • <?php _e('To prevent duplicate content issues', 'mini'); ?><br>
                        • <?php _e('If this content exists on another URL/domain', 'mini'); ?><br>
                        • <?php _e('To consolidate ranking signals to one URL', 'mini'); ?>
                    </p>
                    <p class="desc XS" style="color: #d63638;">
                        <strong>⚠ <?php _e('Warning:', 'mini'); ?></strong> <?php _e('Setting this to another domain will tell search engines to ignore this page and index the other URL instead.', 'mini'); ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
    <?php
}
/* END - SEO meta boxes */

/* START - SEO meta box save */
function mini_save_seo_meta_box($post_id) {
    // Check if nonce is set
    if (!isset($_POST['mini_seo_nonce'])) {
        return;
    }
    
    // Verify nonce
    if (!wp_verify_nonce($_POST['mini_seo_nonce'], 'mini_seo_meta_box')) {
        return;
    }
    
    // Check if autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    // Check user permissions
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // Save SEO fields
    $fields = [
        'mini_seo_title',
        'mini_seo_description',
        'mini_seo_keywords',
        'mini_seo_og_title',
        'mini_seo_og_description',
        'mini_seo_og_image',
        'mini_seo_twitter_card',
        'mini_seo_twitter_title',
        'mini_seo_twitter_description',
        'mini_seo_image',
        'mini_seo_canonical',
    ];
    
    // Keep stored robots index meta backwards-compatible while using a noindex checkbox in UI.
    $robots_index_value = isset($_POST['mini_seo_noindex']) ? '0' : '1';
    update_post_meta($post_id, '_mini_seo_robots_index', $robots_index_value);

    // Keep stored robots follow meta backwards-compatible while using a nofollow checkbox in UI.
    $robots_follow_value = isset($_POST['mini_seo_nofollow']) ? '0' : '1';
    update_post_meta($post_id, '_mini_seo_robots_follow', $robots_follow_value);

    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            $value = wp_unslash($_POST[$field]);

            if (in_array($field, ['mini_seo_og_image', 'mini_seo_image', 'mini_seo_canonical'], true)) {
                $sanitized = esc_url_raw($value);
            } elseif ($field === 'mini_seo_twitter_card') {
                $allowed_cards = ['', 'summary', 'summary_large_image'];
                $card = sanitize_text_field($value);
                $sanitized = in_array($card, $allowed_cards, true) ? $card : '';
            } else {
                $sanitized = sanitize_text_field($value);
            }

            update_post_meta($post_id, '_' . $field, $sanitized);
        } else {
            delete_post_meta($post_id, '_' . $field);
        }
    }
}
add_action('save_post', 'mini_save_seo_meta_box');
/* END - SEO meta box save */

/* START - SEO frontend output */
function mini_output_seo_meta_tags() {
    if (!is_mini_option_enabled('mini_seo_settings', 'mini_enable_seo')) {
        return;
    }
    
    global $post;
    
    // Only output on singular pages
    if (!is_singular()) {
        return;
    }
    
    $seo_title = get_post_meta($post->ID, '_mini_seo_title', true);
    $seo_description = get_post_meta($post->ID, '_mini_seo_description', true);
    $seo_keywords = get_post_meta($post->ID, '_mini_seo_keywords', true);
    $seo_robots_index = get_post_meta($post->ID, '_mini_seo_robots_index', true);
    $seo_robots_follow = get_post_meta($post->ID, '_mini_seo_robots_follow', true);
    $seo_og_title = get_post_meta($post->ID, '_mini_seo_og_title', true);
    $seo_og_description = get_post_meta($post->ID, '_mini_seo_og_description', true);
    $seo_og_image = get_post_meta($post->ID, '_mini_seo_og_image', true);
    $seo_twitter_card = get_post_meta($post->ID, '_mini_seo_twitter_card', true);
    $seo_twitter_title = get_post_meta($post->ID, '_mini_seo_twitter_title', true);
    $seo_twitter_description = get_post_meta($post->ID, '_mini_seo_twitter_description', true);
    $seo_canonical = get_post_meta($post->ID, '_mini_seo_canonical', true);
    
    // Output canonical URL
    $canonical_url = !empty($seo_canonical) ? $seo_canonical : get_permalink($post->ID);
    echo '<link rel="canonical" href="' . esc_url($canonical_url) . '">' . "\n";
    
    // Fallback to defaults
    if (empty($seo_description)) {
        $seo_description = get_variable('mini_seo_settings', 'default_description');
    }

    if (empty($seo_keywords)) {
        $seo_keywords = get_variable('mini_seo_settings', 'default_keywords');
    }
    
    // Output meta description
    if (!empty($seo_description)) {
        echo '<meta name="description" content="' . esc_attr($seo_description) . '">' . "\n";
    }
    
    // Output meta keywords
    if (!empty($seo_keywords)) {
        echo '<meta name="keywords" content="' . esc_attr($seo_keywords) . '">' . "\n";
    }
    
    // Output robots meta
    $robots = [];
    if ($seo_robots_index === '0') {
        $robots[] = 'noindex';
    } else {
        $robots[] = 'index';
    }
    
    if ($seo_robots_follow === '0') {
        $robots[] = 'nofollow';
    } else {
        $robots[] = 'follow';
    }
    
    if (!empty($robots)) {
        echo '<meta name="robots" content="' . esc_attr(implode(', ', $robots)) . '">' . "\n";
    }
    
    // Open Graph tags
    if (!empty($seo_og_title)) {
        echo '<meta property="og:title" content="' . esc_attr($seo_og_title) . '">' . "\n";
    }
    
    if (!empty($seo_og_description)) {
        echo '<meta property="og:description" content="' . esc_attr($seo_og_description) . '">' . "\n";
    }
    
    if (!empty($seo_og_image)) {
        echo '<meta property="og:image" content="' . esc_url($seo_og_image) . '">' . "\n";
    }
    
    echo '<meta property="og:url" content="' . esc_url(get_permalink()) . '">' . "\n";
    echo '<meta property="og:type" content="website">' . "\n";
    
    // Twitter Card tags
    $twitter_card = !empty($seo_twitter_card) ? $seo_twitter_card : 'summary';
    echo '<meta name="twitter:card" content="' . esc_attr($twitter_card) . '">' . "\n";
    
    if (!empty($seo_twitter_title)) {
        echo '<meta name="twitter:title" content="' . esc_attr($seo_twitter_title) . '">' . "\n";
    }
    
    if (!empty($seo_twitter_description)) {
        echo '<meta name="twitter:description" content="' . esc_attr($seo_twitter_description) . '">' . "\n";
    }
    
    if (!empty($seo_og_image)) {
        echo '<meta name="twitter:image" content="' . esc_url($seo_og_image) . '">' . "\n";
    }

    mini_output_schema_markup($post, $canonical_url, $seo_title, $seo_description, $seo_og_image);
}
add_action('wp_head', 'mini_output_seo_meta_tags', 1);

function mini_output_schema_markup($post, $canonical_url, $seo_title, $seo_description, $seo_og_image) {
    if (!is_object($post) || empty($post->ID)) {
        return;
    }

    $post_type = get_post_type($post);
    $schema_type = ($post_type === 'post') ? 'Article' : 'WebPage';

    $headline = !empty($seo_title) ? $seo_title : get_the_title($post);
    $description = !empty($seo_description) ? $seo_description : '';

    $image_url = '';
    if (!empty($seo_og_image)) {
        $image_url = $seo_og_image;
    } else {
        $seo_image = get_post_meta($post->ID, '_mini_seo_image', true);
        if (!empty($seo_image)) {
            $image_url = $seo_image;
        } elseif (has_post_thumbnail($post->ID)) {
            $image_url = get_the_post_thumbnail_url($post->ID, 'full');
        }
    }

    $schema = [
        '@context' => 'https://schema.org',
        '@type' => $schema_type,
        '@id' => $canonical_url,
        'url' => $canonical_url,
        'name' => $headline,
        'headline' => $headline,
        'description' => $description,
        'inLanguage' => str_replace('_', '-', get_locale()),
        'dateModified' => get_the_modified_date('c', $post),
        'mainEntityOfPage' => [
            '@type' => 'WebPage',
            '@id' => $canonical_url,
        ],
    ];

    if (!empty($image_url)) {
        $schema['image'] = [
            '@type' => 'ImageObject',
            'url' => esc_url_raw($image_url),
        ];
    }

    if ($schema_type === 'Article') {
        $schema['datePublished'] = get_the_date('c', $post);
        $schema['author'] = [
            '@type' => 'Person',
            'name' => get_the_author_meta('display_name', $post->post_author),
        ];

        $publisher = [
            '@type' => 'Organization',
            'name' => get_bloginfo('name'),
        ];

        if (function_exists('has_custom_logo') && has_custom_logo()) {
            $logo_id = get_theme_mod('custom_logo');
            $logo_src = wp_get_attachment_image_src($logo_id, 'full');
            if (!empty($logo_src[0])) {
                $publisher['logo'] = [
                    '@type' => 'ImageObject',
                    'url' => esc_url_raw($logo_src[0]),
                ];
            }
        }

        $schema['publisher'] = $publisher;
    }

    $schema = array_filter($schema, function($value) {
        return $value !== '' && $value !== null;
    });

    echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
}

function mini_override_document_title($title) {
    if (!is_mini_option_enabled('mini_seo_settings', 'mini_enable_seo')) {
        return $title;
    }
    
    if (!is_singular()) {
        return $title;
    }
    
    global $post;
    $seo_title = get_post_meta($post->ID, '_mini_seo_title', true);
    
    if (!empty($seo_title)) {
        return $seo_title;
    }
    
    return $title;
}
add_filter('pre_get_document_title', 'mini_override_document_title');
/* END - SEO frontend output */

/* START - SEO sitemap */
function mini_sitemap_is_enabled() {
    if (!is_mini_option_enabled('mini_seo_settings', 'mini_enable_seo')) {
        return false;
    }

    $options = get_option('mini_seo_settings');
    if (is_array($options) && array_key_exists('mini_enable_sitemap', $options)) {
        return !empty($options['mini_enable_sitemap']);
    }

    // Backward compatibility: if option is missing on existing installs, keep sitemap enabled.
    return true;
}

function mini_sitemap_disable_core_sitemaps($enabled) {
    if (mini_sitemap_is_enabled()) {
        return false;
    }

    return $enabled;
}
add_filter('wp_sitemaps_enabled', 'mini_sitemap_disable_core_sitemaps');

function mini_sitemap_rewrite_version() {
    return '4';
}

function mini_sitemap_register_rewrite_rules() {
    add_rewrite_rule('^sitemap\\.xml$', 'index.php?mini_sitemap=index', 'top');
    add_rewrite_rule('^sitemap-([a-z0-9_-]+)\\.xml$', 'index.php?mini_sitemap=$matches[1]', 'top');

    // Legacy aliases kept for backward compatibility.
    add_rewrite_rule('^mini-sitemap\\.xml$', 'index.php?mini_sitemap=index', 'top');
    add_rewrite_rule('^mini-sitemap-([a-z0-9_-]+)\\.xml$', 'index.php?mini_sitemap=$matches[1]', 'top');
}
add_action('init', 'mini_sitemap_register_rewrite_rules');

function mini_sitemap_maybe_flush_rewrite_rules() {
    if (!is_admin() || !current_user_can('manage_options')) {
        return;
    }

    $current = get_option('mini_sitemap_rules_version');
    $target = mini_sitemap_rewrite_version();

    if ($current === $target) {
        return;
    }

    mini_sitemap_register_rewrite_rules();
    flush_rewrite_rules(false);
    update_option('mini_sitemap_rules_version', $target, false);
}
add_action('admin_init', 'mini_sitemap_maybe_flush_rewrite_rules');

function mini_sitemap_register_query_vars($vars) {
    $vars[] = 'mini_sitemap';
    return $vars;
}
add_filter('query_vars', 'mini_sitemap_register_query_vars');

function mini_sitemap_is_post_type_enabled($post_type_name) {
    if ($post_type_name === 'post') {
        return !is_mini_option_enabled('mini_blogging_settings', 'mini_disable_blogging');
    }

    if ($post_type_name === 'news') {
        return is_mini_option_enabled('mini_content_settings', 'mini_news');
    }

    if ($post_type_name === 'event') {
        return is_mini_option_enabled('mini_content_settings', 'mini_event');
    }

    if ($post_type_name === 'match') {
        return is_mini_option_enabled('mini_content_settings', 'mini_match');
    }

    if ($post_type_name === 'slide') {
        return is_mini_option_enabled('mini_content_settings', 'mini_slide');
    }

    if ($post_type_name === 'course' || $post_type_name === 'lesson') {
        return is_mini_option_enabled('mini_content_settings', 'mini_course');
    }

    // For post types not managed by mini settings, keep default behavior.
    return true;
}

function mini_sitemap_include_empty_providers() {
    $options = get_option('mini_seo_settings');

    // Backward compatibility: include empty providers by default.
    if (!is_array($options) || !array_key_exists('mini_sitemap_include_empty', $options)) {
        return true;
    }

    return !empty($options['mini_sitemap_include_empty']);
}

function mini_sitemap_get_public_post_types() {
    $post_types = get_post_types([
        'public' => true,
    ], 'objects');

    $supported = [];

    foreach ($post_types as $post_type) {
        if ($post_type->name === 'attachment') {
            continue;
        }

        if (!mini_sitemap_is_post_type_enabled($post_type->name)) {
            continue;
        }

        // Include all public/enabled post types in the sitemap index,
        // even when currently empty.
        $supported[$post_type->name] = $post_type->label;
    }

    return $supported;
}

function mini_sitemap_get_post_image_urls($post_id) {
    $images = [];

    $seo_image = get_post_meta($post_id, '_mini_seo_image', true);
    if (!empty($seo_image)) {
        $images[] = esc_url_raw($seo_image);
    }

    if (has_post_thumbnail($post_id)) {
        $thumb_url = get_the_post_thumbnail_url($post_id, 'full');
        if (!empty($thumb_url)) {
            $images[] = esc_url_raw($thumb_url);
        }
    }

    return array_values(array_unique(array_filter($images)));
}

function mini_sitemap_xml_escape($value) {
    return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function mini_sitemap_is_post_indexable($post) {
    if (!is_object($post) || empty($post->ID)) {
        return false;
    }

    if (!empty($post->post_password)) {
        return false;
    }

    $robots_index = get_post_meta($post->ID, '_mini_seo_robots_index', true);
    // Default behavior: include URLs unless explicitly set to noindex.
    if ($robots_index === '0') {
        return false;
    }

    $canonical = trim((string) get_post_meta($post->ID, '_mini_seo_canonical', true));
    if ($canonical !== '') {
        $canonical_normalized = untrailingslashit($canonical);
        $permalink_normalized = untrailingslashit(get_permalink($post->ID));
        if ($permalink_normalized && $canonical_normalized !== $permalink_normalized) {
            return false;
        }
    }

    return true;
}

function mini_sitemap_get_indexable_posts($post_type) {
    $posts = get_posts([
        'post_type' => $post_type,
        'post_status' => 'publish',
        'numberposts' => -1,
        'orderby' => 'modified',
        'order' => 'DESC',
        'suppress_filters' => false,
    ]);

    $indexable = [];
    foreach ($posts as $post) {
        if (mini_sitemap_is_post_indexable($post)) {
            $indexable[] = $post;
        }
    }

    return $indexable;
}

function mini_sitemap_build_index_xml($post_types) {
    $xml = [];
    $xml[] = '<?xml version="1.0" encoding="UTF-8"?>';
    $xml[] = '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

    $include_empty = mini_sitemap_include_empty_providers();

    foreach ($post_types as $post_type => $label) {
        $indexable_posts = mini_sitemap_get_indexable_posts($post_type);
        if (!$include_empty && empty($indexable_posts)) {
            continue;
        }

        $lastmod = gmdate('c');
        if (!empty($indexable_posts)) {
            $post = $indexable_posts[0];
            if (!empty($post->post_modified_gmt)) {
                $lastmod = gmdate('c', strtotime($post->post_modified_gmt . ' GMT'));
            }
        }

        $loc = home_url('/sitemap-' . $post_type . '.xml');

        $xml[] = '  <sitemap>';
        $xml[] = '    <loc>' . mini_sitemap_xml_escape($loc) . '</loc>';
        $xml[] = '    <lastmod>' . mini_sitemap_xml_escape($lastmod) . '</lastmod>';
        $xml[] = '  </sitemap>';
    }

    $xml[] = '</sitemapindex>';

    return implode("\n", $xml);
}

function mini_sitemap_build_post_type_xml($post_type) {
    $posts = mini_sitemap_get_indexable_posts($post_type);

    $xml = [];
    $xml[] = '<?xml version="1.0" encoding="UTF-8"?>';
    $xml[] = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">';

    foreach ($posts as $post) {
        if (!is_object($post)) {
            continue;
        }

        $loc = get_permalink($post->ID);
        if (!$loc) {
            continue;
        }

        $lastmod = gmdate('c', strtotime($post->post_modified_gmt . ' GMT'));
        $images = mini_sitemap_get_post_image_urls($post->ID);

        $xml[] = '  <url>';
        $xml[] = '    <loc>' . mini_sitemap_xml_escape($loc) . '</loc>';
        $xml[] = '    <lastmod>' . mini_sitemap_xml_escape($lastmod) . '</lastmod>';

        foreach ($images as $image_url) {
            $xml[] = '    <image:image>';
            $xml[] = '      <image:loc>' . mini_sitemap_xml_escape($image_url) . '</image:loc>';
            $xml[] = '    </image:image>';
        }

        $xml[] = '  </url>';
    }

    $xml[] = '</urlset>';

    return implode("\n", $xml);
}

function mini_sitemap_get_cached_xml($key, $callback) {
    $cache_key = 'mini_sitemap_' . sanitize_key($key);
    $xml = get_transient($cache_key);

    if ($xml !== false) {
        return $xml;
    }

    $xml = call_user_func($callback);
    set_transient($cache_key, $xml, HOUR_IN_SECONDS);

    return $xml;
}

function mini_sitemap_send_xml($xml) {
    nocache_headers();
    header('Content-Type: application/xml; charset=UTF-8');
    header('X-Robots-Tag: noindex, follow', true);
    echo $xml;
    exit;
}

function mini_sitemap_output() {
    if (!mini_sitemap_is_enabled()) {
        return;
    }

    $request = get_query_var('mini_sitemap');
    if (empty($request)) {
        return;
    }

    $post_types = mini_sitemap_get_public_post_types();

    if ($request === 'index') {
        $xml = mini_sitemap_get_cached_xml('index', function () use ($post_types) {
            return mini_sitemap_build_index_xml($post_types);
        });
        mini_sitemap_send_xml($xml);
    }

    if (!array_key_exists($request, $post_types)) {
        global $wp_query;
        $wp_query->set_404();
        status_header(404);
        return;
    }

    $xml = mini_sitemap_get_cached_xml($request, function () use ($request) {
        return mini_sitemap_build_post_type_xml($request);
    });

    mini_sitemap_send_xml($xml);
}
add_action('template_redirect', 'mini_sitemap_output', 0);

function mini_sitemap_fallback_to_core_endpoint() {
    if (mini_sitemap_is_enabled()) {
        return;
    }

    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $path = untrailingslashit((string) $path);

    if ($path === '/sitemap.xml') {
        wp_safe_redirect(home_url('/wp-sitemap.xml'), 301);
        exit;
    }
}
add_action('template_redirect', 'mini_sitemap_fallback_to_core_endpoint', 1);

function mini_sitemap_redirect_wp_core_endpoint() {
    if (!mini_sitemap_is_enabled()) {
        return;
    }

    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if ($path === '/wp-sitemap.xml') {
        wp_safe_redirect(home_url('/sitemap.xml'), 301);
        exit;
    }
}
add_action('template_redirect', 'mini_sitemap_redirect_wp_core_endpoint', 0);

function mini_sitemap_robots_txt($output, $public) {
    $sitemap_enabled = mini_sitemap_is_enabled();

    // If site visibility is off, keep a strict disallow file.
    if (!$public) {
        return "User-agent: *\nDisallow: /\n";
    }

    $configured = mini_sitemap_get_configured_robots_content($sitemap_enabled);
    if (!empty(trim($configured))) {
        return $configured;
    }

    return $output;
}
add_filter('robots_txt', 'mini_sitemap_robots_txt', 10, 2);

function mini_sitemap_flush_cache() {
    delete_transient('mini_sitemap_index');

    $post_types = get_post_types([
        'public' => true,
    ], 'names');

    foreach ($post_types as $post_type) {
        delete_transient('mini_sitemap_' . sanitize_key($post_type));
    }
}
add_action('save_post', 'mini_sitemap_flush_cache');
add_action('deleted_post', 'mini_sitemap_flush_cache');
add_action('trashed_post', 'mini_sitemap_flush_cache');

/* END - SEO sitemap */
