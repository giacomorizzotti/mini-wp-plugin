<?php
/**
* Plugin Name: mini
* Plugin URI: https://mini.uwa.agency/
* Description: A "mini" plugin to extend WP features
* Version: 0.1
* Author: Giacomo Rizzotti
* Author URI: https://www.giacomorizzotti.com/
**/

/* START - Useful functions */
function is_mini_option_enabled($option_group, $option) {
    $options = get_option($option_group);
    return is_array($options) && !empty($options[$option]);
}

if (!function_exists('get_variable')) {
    function get_variable($option_group, $option) {
        $options = get_option( $option_group );
        return (is_array($options) && !empty($options[$option])) ? $options[$option] : false;
    }
}

function get_italian_date_formatters() {
    static $formatters = null;
    if ($formatters === null) {
        $formatters = [
            'month' => new IntlDateFormatter('it_IT', IntlDateFormatter::FULL, IntlDateFormatter::FULL, 'Europe/Rome', IntlDateFormatter::GREGORIAN, 'MMMM'),
            'day_name' => new IntlDateFormatter('it_IT', IntlDateFormatter::FULL, IntlDateFormatter::FULL, 'Europe/Rome', IntlDateFormatter::GREGORIAN, 'EEEE'),
            'day_number' => new IntlDateFormatter('it_IT', IntlDateFormatter::FULL, IntlDateFormatter::FULL, 'Europe/Rome', IntlDateFormatter::GREGORIAN, 'e'),
            'year' => new IntlDateFormatter('it_IT', IntlDateFormatter::FULL, IntlDateFormatter::FULL, 'Europe/Rome', IntlDateFormatter::GREGORIAN, 'yyyy'),
        ];
    }
    return $formatters;
}

function mini_allow_af_upload_mime_type($mime_types) {
    $mime_types['af']  = 'application/octet-stream';
    $mime_types['svg'] = 'image/svg+xml';

    return $mime_types;
}
add_filter('upload_mimes', 'mini_allow_af_upload_mime_type');

function mini_fix_af_filetype_check($data, $file, $filename, $mimes, $real_mime) {
    if (strtolower(pathinfo($filename, PATHINFO_EXTENSION)) !== 'af') {
        return $data;
    }

    $data['ext'] = 'af';
    $data['type'] = 'application/octet-stream';
    $data['proper_filename'] = $filename;

    return $data;
}
add_filter('wp_check_filetype_and_ext', 'mini_fix_af_filetype_check', 10, 5);
/* END - Useful functions */

/* Include shortcodes */
require_once plugin_dir_path(__FILE__) . 'inc/shortcodes.php';

/* START - Default menus */
function mini_create_default_menus() {
    // Register menu locations
    register_nav_menus(array(
        'main-menu' => __('Main Menu', 'mini'),
        'footer-menu' => __('Footer Menu', 'mini'),
        'user-menu' => __('User Menu', 'mini'),
    ));
}
add_action('after_setup_theme', 'mini_create_default_menus');

function mini_setup_default_menus() {
    // Check if menus already exist to avoid duplicates
    $main_menu = wp_get_nav_menu_object('Main Menu');
    $footer_menu = wp_get_nav_menu_object('Footer Menu');
    $user_menu = wp_get_nav_menu_object('User Menu');

    // Get locations once
    $locations = get_theme_mod('nav_menu_locations', []);
    $updated = false;

    // Create Main Menu if it doesn't exist
    if (!$main_menu) {
        $main_menu_id = wp_create_nav_menu('Main Menu');
        $locations['main-menu'] = $main_menu_id;
        $updated = true;
    }

    // Create Footer Menu if it doesn't exist
    if (!$footer_menu) {
        $footer_menu_id = wp_create_nav_menu('Footer Menu');
        $locations['footer-menu'] = $footer_menu_id;
        $updated = true;
    }

    // Create User Menu if it doesn't exist
    if (!$user_menu) {
        $user_menu_id = wp_create_nav_menu('User Menu');
        $locations['user-menu'] = $user_menu_id;
        $updated = true;
    }

    // Update locations once if any changes were made
    if ($updated) {
        set_theme_mod('nav_menu_locations', $locations);
    }
}
register_activation_hook(__FILE__, 'mini_setup_default_menus');
/* END - Default menus */

/* START - content settings */
function mini_content_settings_init() {
    register_setting( 'mini_content', 'mini_content_settings');
    add_settings_section(
        'mini_content_section',
        __( 'Content type settings', 'mini' ),
        'mini_content_section_callback',
        'mini-content'
    );
}
add_action( 'admin_init', 'mini_content_settings_init' );
function mini_content_section_callback( $args ) {
    $slide_enabled = is_mini_option_enabled('mini_content_settings', 'mini_slide');
    $news_enabled = is_mini_option_enabled('mini_content_settings', 'mini_news');
    $event_enabled = is_mini_option_enabled('mini_content_settings', 'mini_event');
    $match_enabled = is_mini_option_enabled('mini_content_settings', 'mini_match');
    $course_enabled = is_mini_option_enabled('mini_content_settings', 'mini_course');
    ?>
    <p id="<?php echo esc_attr( $args['id'] ); ?>">
        <i>mini</i> allows you to manage many custom content types to extend WordPress features.
    </p>
    <div class="boxes">
        <div class="box-50 p-2 white-bg b-rad-5 box-shadow">
            <label for="mini_slide" class="h5 bold bk-text">    
                <input
                    type="checkbox"
                    id="mini_slide"
                    name="mini_content_settings[mini_slide]"
                    class="me-1"
                    value="1"
                    <?php checked($slide_enabled, true); ?>
                >
                <?php esc_html_e( 'Slideshows', 'mini' ); ?>
            </label>
            <p class="mb-0" for="mini_slide">Enable the "Slideshow" content type to manage <u>slideshows</u> and <u>slides</u>.</p>
            <p class="mt-0">This option loads <i>mini</i> <b>slider.js</b> library.</p>
            <p class="grey-text" for="mini_slide">Use <code class="XS">[slideshow slideshow="<span class="acid-green-text">slug</span>"]</code> shortcode to embed.</p>
        </div>
        <div class="box-25 p-2 white-bg b-rad-5 box-shadow">
            <label for="mini_news" class="h5 bold bk-text">    
                <input
                    type="checkbox"
                    id="mini_news"
                    name="mini_content_settings[mini_news]"
                    class="me-1"
                    value="1"
                    <?php checked($news_enabled, true); ?>
                >
                <?php esc_html_e( 'News', 'mini' ); ?>
            </label>
            <p class="" for="mini_news">Enable the "News" content type to manage <u>news articles</u>.</p>
        </div>
        <div class="box-25 p-2 white-bg b-rad-5 box-shadow">
            <label for="mini_event" class="h5 bold bk-text">    
                <input
                    type="checkbox"
                    id="mini_event"
                    name="mini_content_settings[mini_event]"
                    class="me-1"
                    value="1"
                    <?php checked($event_enabled, true); ?>
                >
                <?php esc_html_e( 'Events', 'mini' ); ?>
            </label>
            <p class="" for="mini_event">Enable the "Event" content type to manage <u>events</u>.</p>
        </div>
        <div class="box-50 p-2 white-bg b-rad-5 box-shadow">
            <label for="mini_course" class="h5 bold bk-text">    
                <input
                    type="checkbox"
                    id="mini_course"
                    name="mini_content_settings[mini_course]"
                    class="me-1"
                    value="1"
                    <?php checked($course_enabled, true); ?>
                >
                <?php esc_html_e( 'Courses', 'mini' ); ?>
                
            </label>
            <p class="" for="mini_course">Enable "Course" content type to manage <u>courses</u> and <u>lessons</u>.</p>
        </div>
        <div class="box-25 p-2 white-bg b-rad-5 box-shadow">
            <label for="mini_match" class="h5 bold bk-text">    
                <input
                    type="checkbox"
                    id="mini_match"
                    name="mini_content_settings[mini_match]"
                    class="me-1"
                    value="1"
                    <?php checked($match_enabled, true); ?>
                >
                <?php esc_html_e( 'Matches', 'mini' ); ?>
            </label>
            <p class="" for="mini_match">Enable "Match" content type to manage sport <u>events</u>.</p>
        </div>
    </div>
    <?php
}
function mini_content_page_html() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    if ( isset( $_GET['settings-updated'] ) ) {
        add_settings_error( 'mini_messages', 'mini_message', __( 'Settings Saved', 'mini' ), 'updated' );
    }
    settings_errors( 'mini_messages' );

    $current_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'blogging';
    $page_url    = admin_url( 'admin.php?page=mini-content' );

    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

        <nav class="nav-tab-wrapper">
            <a href="<?php echo esc_url( $page_url . '&tab=blogging' ); ?>" class="nav-tab <?php echo $current_tab === 'blogging' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Blogging', 'mini' ); ?></a>
            <a href="<?php echo esc_url( $page_url . '&tab=content-types' ); ?>" class="nav-tab <?php echo $current_tab === 'content-types' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Content types', 'mini' ); ?></a>
            <a href="<?php echo esc_url( $page_url . '&tab=editor' ); ?>" class="nav-tab <?php echo $current_tab === 'editor' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Editor settings', 'mini' ); ?></a>
        </nav>

        <?php if ( $current_tab === 'blogging' ) : ?>

            <form action="options.php" method="post">
                <?php
                settings_fields( 'mini_blogging' );
                do_settings_sections( 'mini-blogging' );
                submit_button( 'Save Settings' );
                ?>
            </form>

        <?php elseif ( $current_tab === 'content-types' ) : ?>

            <form action="options.php" method="post">
                <?php
                settings_fields( 'mini_content' );
                do_settings_sections( 'mini-content' );
                submit_button( 'Save Settings' );
                ?>
            </form>

        <?php elseif ( $current_tab === 'editor' ) : ?>

            <form action="options.php" method="post">
                <?php
                settings_fields( 'mini_editor' );
                do_settings_sections( 'mini-editor' );
                submit_button( 'Save Settings' );
                ?>
            </form>

        <?php endif; ?>

    </div>
    <?php
}
/* END - content settings */

/* START - mini menu */
function mini_plugin_settings_pages() {
    if ( empty ( $GLOBALS['admin_page_hooks']['mini'] ) ) {
        add_menu_page(
            'mini plugin - Basic options',
            'mini',
            'manage_options',
            'mini',
            'mini_plugin_main_page_html',
            'https://cdn.jsdelivr.net/gh/giacomorizzotti/mini/img/brand/mini_emblem_wh.svg'
        );
    }
    add_submenu_page(
        'mini',
        'mini plugin - Contents',
        'Contents',
        'manage_options',
        'mini-content',
        'mini_content_page_html',
        9
    );
    add_submenu_page(
        'mini',
        'mini plugin - SEO',
        'SEO',
        'manage_options',
        'mini-seo',
        'mini_seo_page_html',
        9
    );
    add_submenu_page(
        'mini',
        'mini plugin - Settings',
        'Settings',
        'manage_options',
        'mini-email',
        'mini_email_page_html',
        9
    );
    add_submenu_page(
        'mini',
        'mini plugin - Security',
        'Security',
        'manage_options',
        'mini-security',
        'mini_security_page_html',
        9
    );
    add_submenu_page(
        'mini',
        'mini plugin - Backoffice',
        'Backoffice',
        'manage_options',
        'mini-backoffice',
        'mini_backoffice_page_html',
        9
    );
    add_submenu_page(
        'mini',
        'mini plugin - GDPR',
        'GDPR',
        'manage_options',
        'mini-gdpr',
        'mini_gdpr_page_html',
        9
    );
    add_submenu_page(
        'mini',
        'mini plugin - PWA',
        'PWA',
        'manage_options',
        'mini-pwa',
        'mini_pwa_page_html',
        9
    );
}
add_action( 'admin_menu', 'mini_plugin_settings_pages' );
/* END - mini menu */

function mini_plugin_admin_styles() {
    $screen = get_current_screen();
    if (!$screen) {
        return;
    }
    
    echo '<style>
        #adminmenu .wp-menu-image img {
            width: 20px;
            height: 20px;
            padding: 0;
        }
        input[type=checkbox]:checked::before {
            margin: 0;
        }';
    
    // SEO styles only on post edit screens
    if (in_array($screen->post_type, ['post', 'page', 'news', 'event', 'match', 'slide']) && in_array($screen->base, ['post', 'post-new'])) {
        echo '
        /* SEO Meta Box Styles */
        .mini-seo-tabs .nav-tab-wrapper {
            margin-bottom: 0;
        }
        .mini-seo-tab-content {
            padding: 10px 0;
        }';
    }
    
    echo '
    </style>';
}
add_action('admin_head', 'mini_plugin_admin_styles');

function mini_seo_admin_scripts() {
    global $post;
    if (!isset($post)) {
        return;
    }

    $seo_title = (string) get_post_meta($post->ID, '_mini_seo_title', true);
    $seo_description = (string) get_post_meta($post->ID, '_mini_seo_description', true);
    $seo_og_title = (string) get_post_meta($post->ID, '_mini_seo_og_title', true);
    $seo_og_description = (string) get_post_meta($post->ID, '_mini_seo_og_description', true);
    $seo_twitter_title = (string) get_post_meta($post->ID, '_mini_seo_twitter_title', true);
    $seo_twitter_description = (string) get_post_meta($post->ID, '_mini_seo_twitter_description', true);
    $seo_image = (string) get_post_meta($post->ID, '_mini_seo_image', true);

    $preview_image = '';
    if (!empty($seo_image)) {
        $preview_image = $seo_image;
    } elseif (has_post_thumbnail($post->ID)) {
        $preview_image = (string) get_the_post_thumbnail_url($post->ID, 'large');
    } else {
        $preview_image = (string) get_variable('mini_seo_settings', 'default_image');
    }

    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Tab switching
        $('.mini-seo-tab').on('click', function(e) {
            e.preventDefault();
            var target = $(this).attr('href');
            
            $('.mini-seo-tab').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');
            
            $('.mini-seo-tab-content').hide();
            $(target).show();
        });
        
        // Character counter for title
        function updateTitleCount() {
            var count = $('#mini_seo_title').val().length;
            $('#mini_seo_title_count').text(count);
        }
        
        // Character counter for description
        function updateDescriptionCount() {
            var count = $('#mini_seo_description').val().length;
            $('#mini_seo_description_count').text(count);
        }
        
        $('#mini_seo_title').on('input', updateTitleCount);
        $('#mini_seo_description').on('input', updateDescriptionCount);
        
        // Initial count
        updateTitleCount();
        updateDescriptionCount();
        
        // Track if OG/Twitter fields have been manually edited
        var ogTitleManuallyEdited = <?php echo $seo_og_title && $seo_og_title !== $seo_title ? 'true' : 'false'; ?>;
        var ogDescManuallyEdited = <?php echo $seo_og_description && $seo_og_description !== $seo_description ? 'true' : 'false'; ?>;
        var twitterTitleManuallyEdited = <?php echo $seo_twitter_title && $seo_twitter_title !== $seo_title ? 'true' : 'false'; ?>;
        var twitterDescManuallyEdited = <?php echo $seo_twitter_description && $seo_twitter_description !== $seo_description ? 'true' : 'false'; ?>;
        
        // Initialize OG and Twitter fields with SEO values if empty
        if (!$('#mini_seo_og_title').val() && $('#mini_seo_title').val()) {
            $('#mini_seo_og_title').val($('#mini_seo_title').val());
        }
        if (!$('#mini_seo_og_description').val() && $('#mini_seo_description').val()) {
            $('#mini_seo_og_description').val($('#mini_seo_description').val());
        }
        if (!$('#mini_seo_twitter_title').val() && $('#mini_seo_title').val()) {
            $('#mini_seo_twitter_title').val($('#mini_seo_title').val());
        }
        if (!$('#mini_seo_twitter_description').val() && $('#mini_seo_description').val()) {
            $('#mini_seo_twitter_description').val($('#mini_seo_description').val());
        }
        
        // Mark fields as manually edited when user types in them
        $('#mini_seo_og_title').on('input', function() {
            ogTitleManuallyEdited = true;
        });
        $('#mini_seo_og_description').on('input', function() {
            ogDescManuallyEdited = true;
        });
        $('#mini_seo_twitter_title').on('input', function() {
            twitterTitleManuallyEdited = true;
        });
        $('#mini_seo_twitter_description').on('input', function() {
            twitterDescManuallyEdited = true;
        });
        
        // Update preview title and description in real-time
        $('#mini_seo_title').on('input', function() {
            var title = $(this).val().trim();
            $('#mini_seo_preview_title').text(title || '<?php echo esc_js(get_the_title($post->ID)); ?>');
            
            // Auto-sync OG and Twitter titles if not manually edited
            if (!ogTitleManuallyEdited) {
                $('#mini_seo_og_title').val(title);
            }
            if (!twitterTitleManuallyEdited) {
                $('#mini_seo_twitter_title').val(title);
            }
        });
        
        $('#mini_seo_description').on('input', function() {
            var description = $(this).val().trim();
            $('#mini_seo_preview_description').text(description || '<?php echo esc_js(get_variable('mini_seo_settings', 'default_description')); ?>');
            
            // Auto-sync OG and Twitter descriptions if not manually edited
            if (!ogDescManuallyEdited) {
                $('#mini_seo_og_description').val(description);
            }
            if (!twitterDescManuallyEdited) {
                $('#mini_seo_twitter_description').val(description);
            }
        });
        
        // Media uploader for SEO image
        var seoImageFrame;
        $('#mini_seo_image_button').on('click', function(e) {
            e.preventDefault();
            
            if (seoImageFrame) {
                seoImageFrame.open();
                return;
            }
            
            seoImageFrame = wp.media({
                title: 'Select SEO Image',
                button: {
                    text: 'Use this image'
                },
                multiple: false
            });
            
            seoImageFrame.on('select', function() {
                var attachment = seoImageFrame.state().get('selection').first().toJSON();
                $('#mini_seo_image').val(attachment.url).trigger('change');
            });
            
            seoImageFrame.open();
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
        function updateDimensionDisplay(width, height) {
            var $dimensions = $('#mini_seo_image_dimensions');
            if (width && height) {
                var isOptimal = (width === 1200 && height === 630) || (width === 1200 && height === 628);
                var ratio = (width / height).toFixed(2);
                var color = isOptimal ? '#0a0' : (ratio >= 1.85 && ratio <= 1.95 ? '#f90' : '#d00');
                $dimensions.html('(<span style="color: ' + color + ';">' + width + '×' + height + 'px</span>)');
            } else {
                $dimensions.html('');
            }
        }
        
        // Update preview when custom SEO image changes
        $('#mini_seo_image').on('change input', function() {
            var imageUrl = $(this).val().trim();
            var $previewContainer = $('#mini_seo_preview_container');
            var $previewImage = $('#mini_seo_preview_image');
            var $previewSource = $('#mini_seo_preview_source');
            
            if (imageUrl) {
                // Show custom image
                $previewImage.attr('src', imageUrl);
                $previewSource.text('<?php _e('Using custom SEO image', 'mini'); ?>');
                $previewContainer.show();
                
                // Check dimensions
                checkImageDimensions(imageUrl, function(width, height) {
                    updateDimensionDisplay(width, height);
                });
            } else {
                // Revert to featured or default image
                <?php if (has_post_thumbnail($post->ID)): ?>
                $previewImage.attr('src', '<?php echo esc_js(get_the_post_thumbnail_url($post->ID, 'large')); ?>');
                $previewSource.text('<?php _e('Using featured image', 'mini'); ?>');
                $previewContainer.show();
                checkImageDimensions('<?php echo esc_js(get_the_post_thumbnail_url($post->ID, 'large')); ?>', function(width, height) {
                    updateDimensionDisplay(width, height);
                });
                <?php elseif (get_variable('mini_seo_settings', 'default_image')): ?>
                $previewImage.attr('src', '<?php echo esc_js(get_variable('mini_seo_settings', 'default_image')); ?>');
                $previewSource.text('<?php _e('Using default SEO image', 'mini'); ?>');
                $previewContainer.show();
                checkImageDimensions('<?php echo esc_js(get_variable('mini_seo_settings', 'default_image')); ?>', function(width, height) {
                    updateDimensionDisplay(width, height);
                });
                <?php else: ?>
                $previewContainer.hide();
                $('#mini_seo_image_dimensions').html('');
                <?php endif; ?>
            }
        });
        
        // Check initial image dimensions
        <?php if ($preview_image): ?>
        checkImageDimensions('<?php echo esc_js($preview_image); ?>', function(width, height) {
            updateDimensionDisplay(width, height);
        });
        <?php endif; ?>
        
        // Keyword analysis functionality
        $('#mini_analyze_content').on('click', function() {
            var content = '';
            var title = $('#title').val() || '';
            
            // Get content from different editor types
            if (typeof tinymce !== 'undefined' && tinymce.get('content')) {
                // Classic editor
                content = tinymce.get('content').getContent({format: 'text'});
            } else if ($('#content').length) {
                // Textarea fallback
                content = $('#content').val();
            } else if (typeof wp !== 'undefined' && wp.data && wp.data.select('core/editor')) {
                // Gutenberg editor
                var blocks = wp.data.select('core/editor').getBlocks();
                content = blocks.map(function(block) {
                    return block.attributes.content || '';
                }).join(' ');
            }
            
            // Add title to content for analysis
            content = title + ' ' + content;
            
            if (!content.trim()) {
                alert('<?php _e('No content found to analyze. Please add some content first.', 'mini'); ?>');
                return;
            }
            
            // Process content to extract keywords
            var keywords = analyzeContent(content);
            displayKeywordSuggestions(keywords);
        });
        
        function analyzeContent(text) {
            // Common stop words to filter out
            var stopWords = ['the', 'be', 'to', 'of', 'and', 'a', 'in', 'that', 'have', 'i', 'it', 'for', 'not', 'on', 'with', 'he', 'as', 'you', 'do', 'at', 'this', 'but', 'his', 'by', 'from', 'they', 'we', 'say', 'her', 'she', 'or', 'an', 'will', 'my', 'one', 'all', 'would', 'there', 'their', 'what', 'so', 'up', 'out', 'if', 'about', 'who', 'get', 'which', 'go', 'me', 'when', 'make', 'can', 'like', 'time', 'no', 'just', 'him', 'know', 'take', 'people', 'into', 'year', 'your', 'good', 'some', 'could', 'them', 'see', 'other', 'than', 'then', 'now', 'look', 'only', 'come', 'its', 'over', 'think', 'also', 'back', 'after', 'use', 'two', 'how', 'our', 'work', 'first', 'well', 'way', 'even', 'new', 'want', 'because', 'any', 'these', 'give', 'day', 'most', 'us', 'is', 'was', 'are', 'been', 'has', 'had', 'were', 'said', 'did', 'having', 'may', 'should', 'am',
                // HTML tags and attributes
                'span', 'strong', 'href', 'target', 'blank', 'noreferrer', 'noopener', 'style', 'class', 'type', 'data', 'json', 'nofollow', 'html', 'head', 'body', 'div', 'section', 'article', 'header', 'footer', 'main', 'aside', 'figure', 'figcaption', 'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td', 'form', 'input', 'button', 'select', 'option', 'label', 'textarea', 'script', 'link', 'meta', 'title', 'width', 'height', 'align', 'color', 'font', 'size', 'text', 'decoration', 'underline', 'bold', 'italic', 'https', 'http', 'www', 'rel', 'src', 'alt', 'aria', 'role'];
            
            // Strip HTML tags before tokenizing
            // Clean and tokenize
            var words = text
                .replace(/<[^>]+>/g, ' ')
                .toLowerCase()
                .replace(/[^a-z0-9\s]/g, ' ')
                .split(/\s+/)
                .filter(function(word) {
                    return word.length > 3 && stopWords.indexOf(word) === -1;
                });
            
            // Count word frequency
            var frequency = {};
            words.forEach(function(word) {
                frequency[word] = (frequency[word] || 0) + 1;
            });
            
            // Sort by frequency and get top 20
            var sorted = Object.keys(frequency).sort(function(a, b) {
                return frequency[b] - frequency[a];
            });
            
            return sorted.slice(0, 20).map(function(word) {
                return {word: word, count: frequency[word]};
            });
        }
        
        function displayKeywordSuggestions(keywords) {
            var $list = $('#mini_keyword_suggestions_list');
            var $container = $('#mini_keyword_suggestions');
            
            $list.empty();
            
            keywords.forEach(function(item) {
                var $badge = $('<span></span>')
                    .css({
                        'display': 'inline-block',
                        'padding': '6px 12px',
                        'background': 'var(--dark-grey)',
                        'border': '1px solid var(--false-black)',
                        'border-radius': '5px',
                        'cursor': 'pointer',
                        'font-size': 'var(--p)',
                        'transition': 'all 0.2s',
                        'color': 'var(--white)',
                    })
                    .html(item.word + ' <span style="color: var(--grey);">(' + item.count + ')</span>')
                    .on('mouseenter', function() {
                        $(this).css({'background': 'var(--link-color)', 'color': 'var(--white)', 'border-color': 'var(--link-hover-color)'});
                        $(this).find('span').css('color', 'var(--light-grey)');
                    })
                    .on('mouseleave', function() {
                        $(this).css({'background': 'var(--dark-grey)', 'color': 'var(--white)', 'border-color': 'var(--false-black)'});
                        $(this).find('span').css('color', 'var(--grey)');
                    })
                    .on('click', function() {
                        addKeyword(item.word);
                    });
                
                $list.append($badge);
            });
            
            $container.show();
        }
        
        function addKeyword(keyword) {
            var $keywords = $('#mini_seo_keywords');
            var current = $keywords.val().trim();
            var keywordList = current ? current.split(',').map(function(k) { return k.trim(); }) : [];
            
            // Check if keyword already exists
            if (keywordList.indexOf(keyword) === -1) {
                keywordList.push(keyword);
                $keywords.val(keywordList.join(', '));
                
                // Visual feedback
                $keywords.css('background', 'var(--main-color-transp)').delay(200).queue(function() {
                    $(this).css('background', 'var(--white)').dequeue();
                });
            }
        }
    });
    </script>
    <?php
    wp_enqueue_media();
}

function mini_should_load_seo_scripts() {
    if (!is_mini_option_enabled('mini_seo_settings', 'mini_enable_seo')) {
        return;
    }
    mini_seo_admin_scripts();
}
add_action('admin_footer-post.php', 'mini_should_load_seo_scripts');
add_action('admin_footer-post-new.php', 'mini_should_load_seo_scripts');


/* START - mini settings */
function mini_plugin_main_page_html() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    if ( isset( $_GET['settings-updated'] ) ) {
        add_settings_error( 'mini_messages', 'mini_message', __( 'Settings Saved', 'mini' ), 'updated' );
    }
    settings_errors( 'mini_messages' );

    $current_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'mini';
    $page_url    = admin_url( 'admin.php?page=mini' );

    $recommended_plugins = array(
        array(
            'name' => 'Contact Form 7',
            'slug' => 'contact-form-7',
            'file' => 'contact-form-7/wp-contact-form-7.php',
            'description' => 'Simple and flexible contact form plugin.'
        ),
        array(
            'name' => 'Flamingo',
            'slug' => 'flamingo',
            'description' => 'Store Contact Form 7 submissions in your database.'
        ),
        array(
            'name' => 'Iubenda',
            'slug' => 'iubenda-cookie-law-solution',
            'file' => 'iubenda-cookie-law-solution/iubenda_cookie_solution.php',
            'description' => 'Cookie consent and privacy policy compliance solution.'
        ),
        array(
            'name' => 'Ninja GDPR',
            'slug' => 'ninja-gdpr-compliance',
            'file' => 'ninja-gdpr-compliance/njt-gdpr.php',
            'description' => 'GDPR and CCPA compliance with cookie consent banner.'
        ),
        array(
            'name' => 'Altcha',
            'slug' => 'altcha-wordpress-next',
            'file' => 'altcha-wordpress-next-2.5.0/altcha.php',
            'description' => 'Anti-spam and bot protection.'
        ),
        array(
            'name' => 'Amministrazione Trasparente',
            'slug' => 'amministrazione-trasparente',
            'file' => 'amministrazione-trasparente/amministrazionetrasparente.php',
            'description' => 'Italian public administration transparency compliance.'
        ),
        array(
            'name' => 'Dashboard Widgets Suite',
            'slug' => 'dashboard-widgets-suite',
            'file' => 'dashboard-widgets-suite/dashboard-widgets.php',
            'description' => 'Customize your WordPress dashboard with useful widgets.'
        ),
        array(
            'name' => 'Login Logout Menu',
            'slug' => 'login-logout-menu',
            'file' => 'login-logout-menu/login-logout-menu.php',
            'description' => 'Add login/logout links to your navigation menus.'
        )
    );
    
    // Check which plugins are installed/active
    if (!function_exists('get_plugins')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    
    $all_plugins = get_plugins();
    $active_plugins = get_option('active_plugins', array());
    
    ?>
    <div class="wrap">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

    <nav class="nav-tab-wrapper">
        <a href="<?php echo esc_url( $page_url . '&tab=mini' ); ?>" class="nav-tab <?php echo $current_tab === 'mini' ? 'nav-tab-active' : ''; ?>"><?php _e( 'mini', 'mini' ); ?></a>
        <a href="<?php echo esc_url( $page_url . '&tab=useful-plugins' ); ?>" class="nav-tab <?php echo $current_tab === 'useful-plugins' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Useful plugins', 'mini' ); ?></a>
    </nav>

    <?php if ( $current_tab === 'useful-plugins' ) : ?>

        <div class="boxes">
            <div class="box-100 mb-2">
                <h4 class="grey-text regular"><?php _e('Useful Plugins', 'mini'); ?></h4>
                <p><?php _e('Here are some useful plugins that work great with mini:', 'mini'); ?></p>            
                <div class="boxes">
                    <?php foreach ($recommended_plugins as $plugin) : 
                        // Use custom file path if specified, otherwise use default pattern
                        $plugin_file = isset($plugin['file']) ? $plugin['file'] : $plugin['slug'] . '/' . $plugin['slug'] . '.php';
                        $is_installed = isset($all_plugins[$plugin_file]);
                        $is_active = in_array($plugin_file, $active_plugins);
                        
                        // Button configuration
                        if ($is_active) {
                            $button_text = __('Active', 'mini');
                            $button_class = 'light-grey-btn grey-text';
                            $button_disabled = 'disabled';
                            $status_color = 'success-text success-border';
                            $status = __('Active', 'mini');
                        } elseif ($is_installed) {
                            $activate_url = wp_nonce_url(
                                admin_url('plugins.php?action=activate&plugin=' . urlencode($plugin_file)),
                                'activate-plugin_' . $plugin_file
                            );
                            $button_text = __('Activate', 'mini');
                            $button_class = 'success-btn white-text';
                            $button_disabled = '';
                            $status_color = 'grey-text grey-border';
                            $status = __('Installed', 'mini');
                        } else {
                            $install_url = wp_nonce_url(
                                admin_url('update.php?action=install-plugin&plugin=' . urlencode($plugin['slug'])),
                                'install-plugin_' . $plugin['slug']
                            );
                            $button_text = __('Install', 'mini');
                            $button_class = 'success-btn';
                            $button_disabled = '';
                            $status_color = 'grey-text grey-border';
                            $status = __('Not installed', 'mini');
                        }
                    ?>
                    <div class="box-25 p-2 white-bg b-rad-5 box-shadow">
                        <h5 class="m-0">
                            <?php echo esc_html($plugin['name']); ?>
                            <span class="flag px-05 b-rad-5 <?php echo esc_attr($status_color); ?> XS"><?php echo esc_html($status); ?></span>
                        </h5>
                        <p class="m-0 S"><?php echo esc_html($plugin['description']); ?></p>
                        <div class="space-15"></div>
                        <?php if ($is_active) : ?>
                            <button class="btn S <?php echo esc_attr($button_class); ?>" <?php echo $button_disabled; ?>>
                                <span class="iconoir-check" width="14px" height="14px" style="vertical-align: text-bottom;"></span> <?php echo esc_html($button_text); ?>
                            </button>
                        <?php elseif ($is_installed) : ?>
                            <a href="<?php echo esc_url($activate_url); ?>" class="btn S <?php echo esc_attr($button_class); ?>">
                                <span class="iconoir-play" width="14px" height="14px" style="vertical-align: text-bottom;"></span> <?php echo esc_html($button_text); ?>
                            </a>
                        <?php else : ?>
                            <a href="<?php echo esc_url($install_url); ?>" class="btn S <?php echo esc_attr($button_class); ?>">
                                <span class="iconoir-download" width="14px" height="14px" style="vertical-align: text-bottom;"></span> <?php echo esc_html($button_text); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

    <?php else : /* mini tab */ ?>

        <div class="boxes">
            <div class="box-100 p-2 white-bg b-rad-5 box-shadow mb-2">
                <div class="space"></div>
                <img src="https://cdn.jsdelivr.net/gh/giacomorizzotti/mini/img/brand/mini_logo_2.svg" alt="mini logo" style="max-width: 280px;" class="mb-2"/>
                <h1 class="mb-0"><i>mini</i> is a frontend framework</h1>
                <p class="mt-0">That allows you to build modern, responsive websites with ease.</p>
                <p class="">
                    <a href="https://mini.uwa.agency/" target="_blank" rel="noopener noreferrer" class="btn fourth-color-btn white-text"><?php esc_html_e( 'Visit mini website', 'mini' ); ?></a>
                </p>
            </div>
            <div class="sep mb-2 light-grey-border"></div>
            <div class="box-25 p-2 white-bg b-rad-5 box-shadow mb-2">
                <h2 class="mb-0"><i class="iconoir-cube third-color-text" width="24px" height="24px" style="vertical-align: text-top;"></i>&nbsp;Contents</h2>
                <p class="mt-0">Manage content types and related features.</p>
                <p class="">
                    <a href="<?php echo esc_url( admin_url('admin.php?page=mini-content') ); ?>" rel="noopener noreferrer" class="btn fourth-color-btn white-text"><?php esc_html_e( 'Contents', 'mini' ); ?></a>
                </p>
            </div>
            <div class="box-25 p-2 white-bg b-rad-5 box-shadow mb-2">
                <h2 class="mb-0"><i class="iconoir-search third-color-text" width="24px" height="24px" style="vertical-align: text-top;"></i>&nbsp;SEO</h2>
                <p class="mt-0">Manage SEO settings.</p>
                <p class="">
                    <a href="<?php echo esc_url( admin_url('admin.php?page=mini-seo') ); ?>" rel="noopener noreferrer" class="btn fourth-color-btn white-text"><?php esc_html_e( 'SEO', 'mini' ); ?></a>
                </p>
            </div>
            <div class="box-25 p-2 white-bg b-rad-5 box-shadow mb-2">
                <h2 class="mb-0"><i class="iconoir-settings third-color-text" width="24px" height="24px" style="vertical-align: text-top;"></i>&nbsp;General settings</h2>
                <p class="mt-0">Manage general settings.</p>
                <p class="">
                    <a href="<?php echo esc_url( admin_url('admin.php?page=mini-general') ); ?>" rel="noopener noreferrer" class="btn fourth-color-btn white-text"><?php esc_html_e( 'General', 'mini' ); ?></a>
                </p>
            </div>
            <div class="box-25 p-2 white-bg b-rad-5 box-shadow mb-2">
                <h2 class="mb-0"><i class="iconoir-shield third-color-text" width="24px" height="24px" style="vertical-align: text-top;"></i>&nbsp;Security settings</h2>
                <p class="mt-0">Manage security settings.</p>
                <p class="">
                    <a href="<?php echo esc_url( admin_url('admin.php?page=mini-security') ); ?>" rel="noopener noreferrer" class="btn fourth-color-btn white-text"><?php esc_html_e( 'Security', 'mini' ); ?></a>
                </p>
            </div>
            <div class="box-25 p-2 white-bg b-rad-5 box-shadow mb-2">
                <h2 class="mb-0"><i class="iconoir-task-list third-color-text" width="24px" height="24px" style="vertical-align: text-top;"></i>&nbsp;Backoffice settings</h2>
                <p class="mt-0">Manage backoffice settings.</p>
                <p class="">
                    <a href="<?php echo esc_url( admin_url('admin.php?page=mini-backoffice') ); ?>" rel="noopener noreferrer" class="btn fourth-color-btn white-text"><?php esc_html_e( 'Backoffice', 'mini' ); ?></a>
                </p>
            </div>
            <div class="box-25 p-2 white-bg b-rad-5 box-shadow mb-2">
                <h2 class="mb-0"><i class="iconoir-half-cookie third-color-text" width="24px" height="24px" style="vertical-align: text-top;"></i>&nbsp;GDPR settings</h2>
                <p class="mt-0">Manage GDPR settings.</p>
                <p class="">
                    <a href="<?php echo esc_url( admin_url('admin.php?page=mini-gdpr') ); ?>" rel="noopener noreferrer" class="btn fourth-color-btn white-text"><?php esc_html_e( 'GDPR', 'mini' ); ?></a>
                </p>
            </div>
            <div class="box-25 p-2 white-bg b-rad-5 box-shadow mb-2">
                <h2 class="mb-0"><i class="iconoir-window-check third-color-text" width="24px" height="24px" style="vertical-align: text-top;"></i>&nbsp;PWA settings</h2>
                <p class="mt-0">Manage PWA (Progressive Web App) settings.</p>
                <p class="">
                    <a href="<?php echo esc_url( admin_url('admin.php?page=mini-pwa') ); ?>" rel="noopener noreferrer" class="btn fourth-color-btn white-text"><?php esc_html_e( 'PWA', 'mini' ); ?></a>
                </p>
            </div>
            <div class="box-25 p-2 white-bg b-rad-5 box-shadow mb-2">
                <h2 class="mb-0"><i class="iconoir-text third-color-text" width="24px" height="24px" style="vertical-align: text-top;"></i>&nbsp;Fonts settings</h2>
                <p class="mt-0">Manage fonts settings.</p>
                <p class="">
                    <a href="<?php echo esc_url( admin_url('admin.php?page=mini-fonts') ); ?>" rel="noopener noreferrer" class="btn fourth-color-btn white-text"><?php esc_html_e( 'Fonts', 'mini' ); ?></a>
                </p>
            </div>
            <div class="box-25 p-2 white-bg b-rad-5 box-shadow mb-2">
                <h2 class="mb-0"><i class="iconoir-stats-report third-color-text" width="24px" height="24px" style="vertical-align: text-top;"></i>&nbsp;Analytics settings</h2>
                <p class="mt-0">Manage analytics settings.</p>
                <p class="">
                    <a href="<?php echo esc_url( admin_url('admin.php?page=mini-analytics') ); ?>" rel="noopener noreferrer" class="btn fourth-color-btn white-text"><?php esc_html_e( 'Analytics', 'mini' ); ?></a>
                </p>
            </div>
            <div class="sep mb-2 light-grey-border"></div>
            <div class="box-20 p-2 white-bg b-rad-5 box-shadow mb-2">
                <h3 class="mb-0"><i class="iconoir-favourite-book third-color-text" width="24px" height="24px" style="vertical-align: text-top;"></i>&nbsp;External libraries</h3>
                <p class="mt-0">Manage external libraries settings.</p>
                <p class="">
                    <a href="<?php echo esc_url( admin_url('admin.php?page=mini-ext-lib') ); ?>" rel="noopener noreferrer" class="btn fourth-color-btn white-text"><?php esc_html_e( 'External Libraries', 'mini' ); ?></a>
                </p>
            </div>
            <div class="box-20 p-2 white-bg b-rad-5 box-shadow mb-2">
                <h3 class="mb-0"><i class="iconoir-spock-hand-gesture third-color-text" width="24px" height="24px" style="vertical-align: text-top;"></i>&nbsp;Credits</h3>
                <p class="mt-0">Manage credits settings.</p>
                <p class="">
                    <a href="<?php echo esc_url( admin_url('admin.php?page=mini-credits') ); ?>" rel="noopener noreferrer" class="btn fourth-color-btn white-text"><?php esc_html_e( 'Credits', 'mini' ); ?></a>
                </p>
            </div>
            <div class="sep mb-2 light-grey-border"></div>
            <div class="box-25 p-2 white-bg b-rad-5 box-shadow mb-2">
                <h2 class="mb-0"><i class="iconoir-building third-color-text" width="24px" height="24px" style="vertical-align: text-top;"></i>&nbsp;Company settings</h2>
                <p class="mt-0">Manage company settings.</p>
                <p class="">
                    <a href="<?php echo esc_url( admin_url('admin.php?page=mini-company') ); ?>" rel="noopener noreferrer" class="btn fourth-color-btn white-text"><?php esc_html_e( 'Company', 'mini' ); ?></a>
                </p>
            </div>
        </div>

    <?php endif; ?>

    </div>
    <?php
}
/* END - mini settings*/

/* START - Settings page */
function mini_email_page_html() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    if ( isset( $_GET['settings-updated'] ) ) {
        add_settings_error( 'mini_messages', 'mini_message', __( 'Settings Saved', 'mini' ), 'updated' );
    }
    settings_errors( 'mini_messages' );

    $current_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'login';
    $page_url    = admin_url( 'admin.php?page=mini-email' );

    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

        <nav class="nav-tab-wrapper">
            <a href="<?php echo esc_url( $page_url . '&tab=login' ); ?>" class="nav-tab <?php echo $current_tab === 'login' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Login settings', 'mini' ); ?></a>
            <a href="<?php echo esc_url( $page_url . '&tab=smtp' ); ?>" class="nav-tab <?php echo $current_tab === 'smtp' ? 'nav-tab-active' : ''; ?>"><?php _e( 'SMTP settings', 'mini' ); ?></a>
        </nav>

        <?php if ( $current_tab === 'smtp' ) : ?>

            <form action="options.php" method="post">
                <?php
                settings_fields( 'mini_smtp' );
                do_settings_sections( 'mini-smtp' );
                submit_button( 'Save Settings' );
                ?>
            </form>

        <?php elseif ( $current_tab === 'login' ) : ?>

            <form action="options.php" method="post">
                <?php
                settings_fields( 'mini_login' );
                do_settings_sections( 'mini-login' );
                submit_button( 'Save Settings' );
                ?>
            </form>

        <?php endif; ?>

    </div>
    <?php
}
/* END - Settings page */

/* START - Security page */
function mini_security_page_html() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    if ( isset( $_GET['settings-updated'] ) ) {
        add_settings_error( 'mini_messages', 'mini_message', __( 'Settings Saved', 'mini' ), 'updated' );
    }
    settings_errors( 'mini_messages' );
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

        <form action="options.php" method="post">
            <?php
            settings_fields( 'mini_security' );
            do_settings_sections( 'mini-security' );
            submit_button( 'Save Settings' );
            ?>
        </form>
    </div>
    <?php
}
/* END - Security page */

/* START - Backoffice page */
function mini_backoffice_page_html() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    if ( isset( $_GET['settings-updated'] ) ) {
        add_settings_error( 'mini_messages', 'mini_message', __( 'Settings Saved', 'mini' ), 'updated' );
    }
    settings_errors( 'mini_messages' );
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

        <form action="options.php" method="post">
            <?php
            settings_fields( 'mini_backoffice' );
            do_settings_sections( 'mini-backoffice' );
            submit_button( 'Save Settings' );
            ?>
        </form>
    </div>
    <?php
}
/* END - Backoffice page */

/* START - PWA page */
function mini_pwa_page_html() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    if ( isset( $_GET['settings-updated'] ) ) {
        add_settings_error( 'mini_messages', 'mini_message', __( 'Settings Saved', 'mini' ), 'updated' );
    }
    settings_errors( 'mini_messages' );
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

        <form action="options.php" method="post">
            <?php
            settings_fields( 'mini_pwa' );
            do_settings_sections( 'mini-pwa' );
            submit_button( 'Save Settings' );
            ?>
        </form>
    </div>
    <?php
}
/* END - PWA page */

/* START - GDPR page */
function mini_gdpr_page_html() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    if ( isset( $_GET['settings-updated'] ) ) {
        add_settings_error( 'mini_messages', 'mini_message', __( 'Settings Saved', 'mini' ), 'updated' );
    }
    settings_errors( 'mini_messages' );

    $current_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'privacy';
    $page_url    = admin_url( 'admin.php?page=mini-gdpr' );
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

        <nav class="nav-tab-wrapper">
            <a href="<?php echo esc_url( $page_url . '&tab=privacy' ); ?>" class="nav-tab <?php echo $current_tab === 'privacy' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Privacy', 'mini' ); ?></a>
            <a href="<?php echo esc_url( $page_url . '&tab=cookie' ); ?>" class="nav-tab <?php echo $current_tab === 'cookie' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Cookie policy', 'mini' ); ?></a>
        </nav>

        <?php if ( $current_tab === 'privacy' ) : ?>

            <form action="options.php" method="post">
                <?php
                settings_fields( 'mini_gdpr_privacy' );
                do_settings_sections( 'mini-gdpr-privacy' );
                submit_button( 'Save Settings' );
                ?>
            </form>

        <?php elseif ( $current_tab === 'cookie' ) : ?>

            <form action="options.php" method="post">
                <?php
                settings_fields( 'mini_gdpr_cookie' );
                do_settings_sections( 'mini-gdpr-cookie' );
                submit_button( 'Save Settings' );
                ?>
            </form>

        <?php endif; ?>

    </div>
    <?php
}
/* END - GDPR page */

/* START - Content types module include */
require_once plugin_dir_path(__FILE__) . 'inc/content-types.php';
/* END - Content types module include */


/* ADD Date-only options for courses */
add_action( 'add_meta_boxes', 'add_date_only_box' );
add_action( 'save_post', 'date_only_save_postdata' );
function add_date_only_box() {
    add_meta_box(
        'date-only',
        esc_html__( 'Date', 'mini' ),
        'date_only_box_html',
        ['course'],
        'side'
    );
}
function date_only_box_html( $post, $meta ){
    $date_value = get_post_meta( $post->ID, 'event_date', true) ?: '';
    $end_date_value = get_post_meta( $post->ID, 'event_end_date', true) ?: '';
    ?>
    <div style="display: flex; flex-flow: row wrap; margin-bottom: 1rem;">
        <div style="flex: 1; margin-bottom: 0.5rem;">
            <label for="event_date" style="margin-bottom: 0.5rem; display: block;"><?php _e('Date', 'mini'); ?>:</label>
            <input type="date" id="event_date" name="event_date" value="<?php echo esc_attr($date_value); ?>" style="min-width: 220px; display: block;" />
        </div>
        <div style="flex: 1; margin-bottom: 0.5rem;">
            <label for="event_end_date" style="margin-bottom: 0.5rem; display: block;"><?php _e('End date (optional)', 'mini'); ?>:</label>
            <input type="date" id="event_end_date" name="event_end_date" value="<?php echo esc_attr($end_date_value); ?>" style="min-width: 220px; display: block;" />
        </div>
    </div>
    <?php
}
function date_only_save_postdata( $post_id ) {
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) { return; }
    if ( ! current_user_can( 'edit_post', $post_id ) ) { return; }
    if ( ! isset($_POST['event_date']) ) { return; }
    
    // Sanitize and save date fields
    if ( isset($_POST['event_date']) ) {
        update_post_meta( $post_id, 'event_date', sanitize_text_field($_POST['event_date']) );
    }
    if ( isset($_POST['event_end_date']) ) {
        update_post_meta( $post_id, 'event_end_date', sanitize_text_field($_POST['event_end_date']) );
    }
}

/* ADD Date and time options */
add_action( 'add_meta_boxes', 'add_date_time_box' );
add_action( 'save_post', 'date_time_save_postdata' );
function add_date_time_box() {
    add_meta_box(
        'date-time',
        esc_html__( 'Date & Time', 'mini' ),
        'date_time_box_html',
        ['event', 'match', 'lesson'],
        'side'
    );
}
function date_time_box_html( $post, $meta ){
    $date_value = get_post_meta( $post->ID, 'event_date', true) ?: '';
    $end_date_value = get_post_meta( $post->ID, 'event_end_date', true) ?: '';
    $time_value = get_post_meta( $post->ID, 'event_time', true) ?: '';
    $end_time_value = get_post_meta( $post->ID, 'event_end_time', true) ?: '';
    $has_end_date = !empty($end_date_value);
    $has_end_time = !empty($end_time_value);
    ?>
    <div style="display: flex; flex-flow: row wrap; margin-bottom: 1rem;">
        <div style="flex: 1; margin-bottom: 0.5rem;">
            <label for="event_date" style="margin-bottom: 0.5rem; display: block;"><?php _e('Date', 'mini'); ?>:</label>
            <input type="date" id="event_date" name="event_date" value="<?php echo esc_attr($date_value); ?>" style="min-width: 220px; display: block;" />
        </div>
    </div>
    <div style="margin-bottom: 1rem;">
        <label>
            <input type="checkbox" id="event_has_end_date" <?php checked($has_end_date); ?> />
            <?php _e('End date', 'mini'); ?>
        </label>
    </div>
    <div id="event_end_date_wrapper" style="display: <?php echo $has_end_date ? 'block' : 'none'; ?>; margin-bottom: 1rem;">
        <div style="flex: 1; margin-bottom: 0.5rem;">
            <label for="event_end_date" style="margin-bottom: 0.5rem; display: block;"><?php _e('End date', 'mini'); ?>:</label>
            <input type="date" id="event_end_date" name="event_end_date" value="<?php echo esc_attr($end_date_value); ?>" style="min-width: 220px; display: block;" />
        </div>
    </div>
    <div style="display: flex; flex-flow: row wrap; margin-bottom: 1rem;">
        <div style="flex: 1; margin-bottom: 0.5rem;">
            <label for="event_time" style="margin-bottom: 0.5rem; display: block;"><?php _e('Time', 'mini'); ?>:</label>
            <input type="time" id="event_time" name="event_time" value="<?php echo esc_attr($time_value); ?>" style="min-width: 220px; display: block;" />
        </div>
    </div>
    <div style="margin-bottom: 1rem;">
        <label>
            <input type="checkbox" id="event_has_end_time" <?php checked($has_end_time); ?> />
            <?php _e('End time', 'mini'); ?>
        </label>
    </div>
    <div id="event_end_time_wrapper" style="display: <?php echo $has_end_time ? 'block' : 'none'; ?>; margin-bottom: 1rem;">
        <div style="flex: 1; margin-bottom: 0.5rem;">
            <label for="event_end_time" style="margin-bottom: 0.5rem; display: block;"><?php _e('End time', 'mini'); ?>:</label>
            <input type="time" id="event_end_time" name="event_end_time" value="<?php echo esc_attr($end_time_value); ?>" style="min-width: 220px; display: block;" />
        </div>
    </div>
    <script>
    (function() {
        var endDateCheckbox = document.getElementById('event_has_end_date');
        var endDateWrapper = document.getElementById('event_end_date_wrapper');
        var endDateInput = document.getElementById('event_end_date');
        
        var endTimeCheckbox = document.getElementById('event_has_end_time');
        var endTimeWrapper = document.getElementById('event_end_time_wrapper');
        var endTimeInput = document.getElementById('event_end_time');
        
        if (endDateCheckbox) {
            endDateCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    endDateWrapper.style.display = 'block';
                } else {
                    endDateWrapper.style.display = 'none';
                    endDateInput.value = '';
                }
            });
        }
        
        if (endTimeCheckbox) {
            endTimeCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    endTimeWrapper.style.display = 'block';
                } else {
                    endTimeWrapper.style.display = 'none';
                    endTimeInput.value = '';
                }
            });
        }
    })();
    </script>
    <?php
}
function date_time_save_postdata( $post_id ) {
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) { return; }
    if ( ! current_user_can( 'edit_post', $post_id ) ) { return; }
    if ( ! isset($_POST['event_date']) && ! isset($_POST['event_time']) ) { return; }
    
    // Sanitize and save date/time fields
    if ( isset($_POST['event_date']) ) {
        update_post_meta( $post_id, 'event_date', sanitize_text_field($_POST['event_date']) );
    }
    if ( isset($_POST['event_end_date']) ) {
        update_post_meta( $post_id, 'event_end_date', sanitize_text_field($_POST['event_end_date']) );
    }
    if ( isset($_POST['event_time']) ) {
        update_post_meta( $post_id, 'event_time', sanitize_text_field($_POST['event_time']) );
    }
    if ( isset($_POST['event_end_time']) ) {
        update_post_meta( $post_id, 'event_end_time', sanitize_text_field($_POST['event_end_time']) );
    }
}

/* ADD Location options */
add_action( 'add_meta_boxes', 'add_location_box' );
add_action( 'save_post', 'location_save_postdata' );
function add_location_box() {
    add_meta_box(
        'location',
        esc_html__( 'Location', 'mini' ),
        'location_box_html',
        ['event', 'match', 'course', 'lesson'],
        'side'
    );
}
function location_box_html( $post, $meta ){
    $location_name_value = get_post_meta( $post->ID, 'location_name', true) ?: '';
    $location_address_value = get_post_meta( $post->ID, 'location_address', true) ?: '';
    ?>
    <div style="display: flex; flex-flow: row wrap; margin-bottom: 1rem;">
        <div style="flex: 1;">
            <label for="location_name" style="margin-bottom: 0.5rem; display: block;"><?php _e('Location', 'mini'); ?>:</label>
            <input type="text" id="location_name" name="location_name" value="<?php echo esc_attr($location_name_value); ?>" style="min-width: 220px; width: 100%;" />
        </div>
    </div>
    <div style="display: flex; flex-flow: row wrap; margin-bottom: 1rem;">
        <div style="flex: 1;">
            <label for="location_address" style="margin-bottom: 0.5rem; display: block;"><?php _e('Location address', 'mini'); ?>:</label>
            <input type="text" id="location_address" name="location_address" value="<?php echo esc_attr($location_address_value); ?>" style="min-width: 220px; width: 100%;" />
        </div>
    </div>
    <?php
}
function location_save_postdata( $post_id ) {
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) { return; }
    if ( ! current_user_can( 'edit_post', $post_id ) ) { return; }
    if ( ! isset($_POST['location_name']) && ! isset($_POST['location_address']) ) { return; }
    
    // Sanitize and save location fields
    if ( isset($_POST['location_name']) ) {
        update_post_meta( $post_id, 'location_name', sanitize_text_field($_POST['location_name']) );
    }
    if ( isset($_POST['location_address']) ) {
        update_post_meta( $post_id, 'location_address', sanitize_text_field($_POST['location_address']) );
    }
}

/* ADD Teams options */
add_action( 'add_meta_boxes', 'add_teams_box' );
add_action( 'save_post', 'teams_save_postdata' );
function add_teams_box() {
    add_meta_box(
        'teams',
        __( 'Teams', 'mini' ),
        'teams_box_html',
        ['match'],
        'normal'
    );
}
function teams_box_html( $post, $meta ){
    $team_1_value = get_post_meta( $post->ID, 'team_1', true) ?: '';
    $team_1_logo_value = get_post_meta( $post->ID, 'team_1_logo', true) ?: '';
    $team_1_score_value = get_post_meta( $post->ID, 'team_1_score', true) ?: '';
    $team_2_value = get_post_meta( $post->ID, 'team_2', true) ?: '';
    $team_2_logo_value = get_post_meta( $post->ID, 'team_2_logo', true) ?: '';
    $team_2_score_value = get_post_meta( $post->ID, 'team_2_score', true) ?: '';
    ?>
    <div class="boxes">
        <div class="box-50 p-0">
            <div class="boxes">
                <div class="box-100">
                    <h3><?php _e('Team one', 'mini'); ?></h3>
                </div>
                <div class="box-100">
                    <label for="team_1" style="margin-bottom: 0.5rem; display: block;"><?php _e('Team one', 'mini'); ?>:</label>
                    <input type="text" id="team_1" name="team_1" value="<?php echo esc_attr($team_1_value); ?>"/>
                </div>
                <div class="box-100">
                    <label for="team_1_logo" style="margin-bottom: 0.5rem; display: block;"><?php _e('Team one logo', 'mini'); ?>:</label>
                    <div style="display: flex; gap: 0.5rem; margin-bottom: 0.5rem;">
                        <input type="text" id="team_1_logo" name="team_1_logo" value="<?php echo esc_attr($team_1_logo_value); ?>" style="flex: 1;"/>
                        <button type="button" class="button" id="team_1_logo_button"><?php _e('Select Image', 'mini'); ?></button>
                    </div>
                    <?php if ($team_1_logo_value): ?>
                    <div id="team_1_logo_preview" style="margin-top: 0.5rem;">
                        <img src="<?php echo esc_url($team_1_logo_value); ?>" style="max-width: 150px; height: auto; border: 1px solid #ddd; border-radius: 4px;"/>
                    </div>
                    <?php else: ?>
                    <div id="team_1_logo_preview" style="margin-top: 0.5rem; display: none;">
                        <img src="" style="max-width: 150px; height: auto; border: 1px solid #ddd; border-radius: 4px;"/>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="box-50">
                    <label for="team_1_score" style="margin-bottom: 0.5rem; display: block;"><?php _e('Team one score', 'mini'); ?>:</label>
                    <input type="text" id="team_1_score" name="team_1_score" value="<?php echo esc_attr($team_1_score_value); ?>"/>
                </div>
            </div>
        </div>
        <div class="box-50 p-0">
            <div class="boxes">
                <div class="box-100">
                    <h3><?php _e('Team two', 'mini'); ?></h3>
                </div>
                <div class="box-100">
                    <label for="team_2" style="margin-bottom: 0.5rem; display: block;"><?php _e('Team two', 'mini'); ?>:</label>
                    <input type="text" id="team_2" name="team_2" value="<?php echo esc_attr($team_2_value); ?>"/>
                </div>
                <div class="box-100">
                    <label for="team_2_logo" style="margin-bottom: 0.5rem; display: block;"><?php _e('Team two logo', 'mini'); ?>:</label>
                    <div style="display: flex; gap: 0.5rem; margin-bottom: 0.5rem;">
                        <input type="text" id="team_2_logo" name="team_2_logo" value="<?php echo esc_attr($team_2_logo_value); ?>" style="flex: 1;"/>
                        <button type="button" class="button" id="team_2_logo_button"><?php _e('Select Image', 'mini'); ?></button>
                    </div>
                    <?php if ($team_2_logo_value): ?>
                    <div id="team_2_logo_preview" style="margin-top: 0.5rem;">
                        <img src="<?php echo esc_url($team_2_logo_value); ?>" style="max-width: 150px; height: auto; border: 1px solid #ddd; border-radius: 4px;"/>
                    </div>
                    <?php else: ?>
                    <div id="team_2_logo_preview" style="margin-top: 0.5rem; display: none;">
                        <img src="" style="max-width: 150px; height: auto; border: 1px solid #ddd; border-radius: 4px;"/>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="box-50">
                    <label for="team_2_score" style="margin-bottom: 0.5rem; display: block;"><?php _e('Team two score', 'mini'); ?>:</label>
                    <input type="text" id="team_2_score" name="team_2_score" value="<?php echo esc_attr($team_2_score_value); ?>"/>
                </div>
            </div>
        </div>
    </div>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Media uploader for team 1 logo
        var team1LogoFrame;
        $('#team_1_logo_button').on('click', function(e) {
            e.preventDefault();
            
            if (team1LogoFrame) {
                team1LogoFrame.open();
                return;
            }
            
            team1LogoFrame = wp.media({
                title: '<?php _e('Select Team Logo', 'mini'); ?>',
                button: {
                    text: '<?php _e('Use this image', 'mini'); ?>'
                },
                multiple: false
            });
            
            team1LogoFrame.on('select', function() {
                var attachment = team1LogoFrame.state().get('selection').first().toJSON();
                $('#team_1_logo').val(attachment.url);
                $('#team_1_logo_preview img').attr('src', attachment.url);
                $('#team_1_logo_preview').show();
            });
            
            team1LogoFrame.open();
        });
        
        // Media uploader for team 2 logo
        var team2LogoFrame;
        $('#team_2_logo_button').on('click', function(e) {
            e.preventDefault();
            
            if (team2LogoFrame) {
                team2LogoFrame.open();
                return;
            }
            
            team2LogoFrame = wp.media({
                title: '<?php _e('Select Team Logo', 'mini'); ?>',
                button: {
                    text: '<?php _e('Use this image', 'mini'); ?>'
                },
                multiple: false
            });
            
            team2LogoFrame.on('select', function() {
                var attachment = team2LogoFrame.state().get('selection').first().toJSON();
                $('#team_2_logo').val(attachment.url);
                $('#team_2_logo_preview img').attr('src', attachment.url);
                $('#team_2_logo_preview').show();
            });
            
            team2LogoFrame.open();
        });
        
        // Update preview when URL is manually changed
        $('#team_1_logo').on('change input', function() {
            var url = $(this).val();
            if (url) {
                $('#team_1_logo_preview img').attr('src', url);
                $('#team_1_logo_preview').show();
            } else {
                $('#team_1_logo_preview').hide();
            }
        });
        
        $('#team_2_logo').on('change input', function() {
            var url = $(this).val();
            if (url) {
                $('#team_2_logo_preview img').attr('src', url);
                $('#team_2_logo_preview').show();
            } else {
                $('#team_2_logo_preview').hide();
            }
        });
    });
    </script>
    <?php
}

function media_upload_scripts() {
    $screen = get_current_screen();
    if ( ! $screen ) {
        return;
    }

    if (
        in_array( $screen->post_type, [ 'match', 'event' ], true ) ||
        strpos( $screen->id, 'mini' ) !== false
    ) {
        wp_enqueue_media();
    }
}
add_action('admin_enqueue_scripts', 'media_upload_scripts');

function media_upload_styles() {
    $screen = get_current_screen();
    if (!$screen || !in_array($screen->post_type, ['match', 'event'])) {
        return;
    }
    
    wp_enqueue_style('thickbox');
}
add_action('admin_enqueue_scripts', 'media_upload_styles');

function load_mini_css_in_mini_plugin_admin_pages() {
    $screen = get_current_screen();
    
    // Load on mini admin pages OR post/page edit screens OR list tables OR dashboard
    if ($screen && (strpos($screen->id, 'mini') !== false || in_array($screen->base, ['post', 'post-new', 'edit', 'dashboard']))) {
        // Check theme CDN options first
        $cdn_options = get_option('mini_cdn_options');
        $theme_options = get_option('mini_theme_options');
        $plugin_options = get_option('mini_main_settings');
        
        // Check if DEV CDN is enabled
        if (is_array($cdn_options) && isset($cdn_options['cdn']) && $cdn_options['cdn'] && 
            isset($cdn_options['cdn_dev']) && $cdn_options['cdn_dev']) {
            // Use DEV CDN
            $css_url = 'https://serversaur.doingthings.space/mini/css/mini.min.css';
            $version = 'dev';
        } else {
            // Get version from theme or plugin options
            if (isset($theme_options['mini_css_version'])) {
                $version = sanitize_text_field($theme_options['mini_css_version']);
            } elseif (isset($plugin_options['mini_css_version'])) {
                $version = sanitize_text_field($plugin_options['mini_css_version']);
            } else {
                $version = 'latest';
            }
             
            if ($version === 'latest') {
                $css_url = 'https://cdn.jsdelivr.net/gh/giacomorizzotti/mini/css/mini.min.css';
            } else {
                $css_url = 'https://cdn.jsdelivr.net/gh/giacomorizzotti/mini@' . esc_attr($version) . '/css/mini.min.css';
            }
        }
        
        wp_enqueue_style('mini-css', $css_url, array(), $version);
        wp_enqueue_style('mini-iconoir', 'https://cdn.jsdelivr.net/gh/iconoir-icons/iconoir@main/css/iconoir.css', array(), null);
        wp_enqueue_style('mini-plugin-admin', plugin_dir_url(__FILE__) . 'css/admin.css', array('mini-css'), '1.0');
    }
}
add_action('admin_enqueue_scripts', 'load_mini_css_in_mini_plugin_admin_pages');

function teams_save_postdata( $post_id ) {
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) { return; }
    if ( ! current_user_can( 'edit_post', $post_id ) ) { return; }
    if ( ! isset($_POST['team_1']) && ! isset($_POST['team_2']) ) { return; }
    
    // Sanitize and save team fields
    if ( isset($_POST['team_1']) ) {
        update_post_meta( $post_id, 'team_1', sanitize_text_field($_POST['team_1']) );
    }
    if ( isset($_POST['team_1_logo']) ) {
        update_post_meta( $post_id, 'team_1_logo', esc_url_raw($_POST['team_1_logo']) );
    }
    if ( isset($_POST['team_1_score']) ) {
        update_post_meta( $post_id, 'team_1_score', absint($_POST['team_1_score']) );
    }
    if ( isset($_POST['team_2']) ) {
        update_post_meta( $post_id, 'team_2', sanitize_text_field($_POST['team_2']) );
    }
    if ( isset($_POST['team_2_logo']) ) {
        update_post_meta( $post_id, 'team_2_logo', esc_url_raw($_POST['team_2_logo']) );
    }
    if ( isset($_POST['team_2_score']) ) {
        update_post_meta( $post_id, 'team_2_score', absint($_POST['team_2_score']) );
    }
}

/* START - DISABLE comments */
if (is_mini_option_enabled('mini_comment_settings', 'mini_disable_comment')) {
    add_action('admin_init', 'disable_comments_post_types_support');
    add_filter('comments_open', 'disable_comments_status', 20, 2);
    add_filter('pings_open', 'disable_comments_status', 20, 2);
    add_action('admin_menu', 'disable_comments_admin_menu');
    add_action( 'wp_before_admin_bar_render', 'disable_comments_admin_bar' );
    add_action('admin_init', 'disable_comments_admin_menu_redirect');
    add_action('admin_init', 'disable_comments_dashboard');
}
function disable_comments_post_types_support() {
    $post_types = get_post_types();
 
    foreach ($post_types as $post_type) {
        if(post_type_supports($post_type, 'comments')) {
            remove_post_type_support($post_type, 'comments');
            remove_post_type_support($post_type, 'trackbacks');
        }
    }
}
function disable_comments_status() {
    return false;
}
function disable_comments_hide_existing_comments($comments) {
    $comments = array();
    return $comments;
}
add_filter('comments_array', 'disable_comments_hide_existing_comments', 10, 2);
function disable_comments_admin_menu() {
    remove_menu_page('edit-comments.php');
}
function disable_comments_admin_bar() {
    global $wp_admin_bar;
    $wp_admin_bar->remove_menu('comments');
}
function disable_comments_admin_menu_redirect() {
    global $pagenow;
    if ($pagenow === 'edit-comments.php') {
        wp_redirect(admin_url()); exit;
    }
}
function disable_comments_dashboard() {
    remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
}
/* END - DISABLE comments */

/* START - DISABLE blogging */
function mini_blogging_settings_init() {
    register_setting( 'mini_blogging', 'mini_blogging_settings');
    register_setting( 'mini_blogging', 'mini_comment_settings');
    add_settings_section(
        'mini_blogging_section',
        __( 'Blogging settings', 'mini' ),
        'mini_blogging_section_callback',
        'mini-blogging'
    );
}
add_action( 'admin_init', 'mini_blogging_settings_init' );
function mini_blogging_section_callback( $args ) {
    ?>
    <div class="space"></div>
    <div class="boxes">
        <div class="box-50 p-2 white-bg b-rad-5 box-shadow">
            <label for="mini_disable_blogging" class="h5 black-text bold">
                <input type="checkbox" id="mini_disable_blogging" name="mini_blogging_settings[mini_disable_blogging]" value="1"<?php echo is_mini_option_enabled('mini_blogging_settings', 'mini_disable_blogging') ? ' checked="checked"' : ''; ?>>
                <?php esc_html_e( 'Disable blogging', 'mini' ); ?>
            </label>
            <p class="">This option will <u>disable blogging features</u> including <b>posts</b>, <b>blog archive pages</b> and related admin menus.</p>
        </div>
        <div class="box-50 p-2 white-bg b-rad-5 box-shadow">
            <label for="mini_disable_comment" class="h5 black-text bold">
                <input type="checkbox" id="mini_disable_comment" name="mini_comment_settings[mini_disable_comment]" value="1"<?php echo is_mini_option_enabled('mini_comment_settings', 'mini_disable_comment') ? ' checked="checked"' : ''; ?>>
                <?php esc_html_e( 'Disable comments', 'mini' ); ?>
            </label>
            <p class="">This option will disable comment features and related admin menus.</p>
        </div>
    </div>
    <?php
}
if (is_mini_option_enabled('mini_blogging_settings', 'mini_disable_blogging')) {
    add_action( 'admin_menu', 'remove_post_admin_menus' );
    add_action( 'wp_before_admin_bar_render', 'remove_post_toolbar_menus' );
    add_action( 'wp_dashboard_setup', 'remove_post_dashboard_widgets' );
}
function remove_post_admin_menus() {
    remove_menu_page( 'edit.php' );
}

function remove_post_toolbar_menus() {
    global $wp_admin_bar;
    $wp_admin_bar->remove_menu( 'new-post' );
}

function remove_post_dashboard_widgets() {
    global $wp_meta_boxes;
    unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_quick_press']);
    unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_recent_comments']);
}
/* END - DISABLE blogging */

/* START - DISABLE CF7 settings for non-admins */
function remove_cf7_settings_menu_page() {
    if ( !current_user_can('administrator') ) {
       remove_menu_page('wpcf7'); // Contact Form 7 Menu
    }
}
add_action( 'admin_init', 'remove_cf7_settings_menu_page' );
/* END - DISABLE CF7 settings for non-admins */

/* START - DISABLE TOOLS settings for non-admins */
function remove_tools_settings_menu_page() {
    if ( !current_user_can('administrator') ) {
       remove_menu_page('tools.php'); // Contact Form 7 Menu
    }
}
add_action( 'admin_init', 'remove_tools_settings_menu_page' );
/* END - DISABLE CF7 settings for non-admins */

/* START - SEO module include */
require_once plugin_dir_path(__FILE__) . 'inc/seo.php';

function mini_sitemap_activation_setup() {
    mini_sitemap_register_rewrite_rules();
    flush_rewrite_rules();
    update_option('mini_sitemap_rules_version', mini_sitemap_rewrite_version(), false);
}
register_activation_hook(__FILE__, 'mini_sitemap_activation_setup');

function mini_sitemap_deactivation_teardown() {
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'mini_sitemap_deactivation_teardown');
/* END - SEO module include */


/* START - Dashboard module include */
require_once plugin_dir_path(__FILE__) . 'inc/dashboard.php';
/* END - Dashboard module include */

/* START - SMTP module include */
require_once plugin_dir_path(__FILE__) . 'inc/smtp.php';
/* END - SMTP module include */

/* START - Login module include */
require_once plugin_dir_path(__FILE__) . 'inc/login.php';
/* END - Login module include */

/* START - Security module include */
require_once plugin_dir_path(__FILE__) . 'inc/security.php';
/* END - Security module include */

/* START - Backoffice module include */
require_once plugin_dir_path(__FILE__) . 'inc/backoffice.php';
/* END - Backoffice module include */

/* START - GDPR module include */
require_once plugin_dir_path(__FILE__) . 'inc/gdpr.php';
/* END - GDPR module include */

/* START - PWA module include */
require_once plugin_dir_path(__FILE__) . 'inc/pwa.php';
register_activation_hook( __FILE__, 'mini_pwa_activation_setup' );
register_deactivation_hook( __FILE__, 'mini_pwa_deactivation_teardown' );
/* END - PWA module include */

