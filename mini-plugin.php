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

function mini_plugin_checkbox_option(string $option_group, string $option) {
    $checked = is_mini_option_enabled($option_group, $option) ? ' checked="checked"' : '';
    return sprintf(
        '<input type="checkbox" id="%s" name="%s[%s]" value="1"%s>',
        esc_attr($option),
        esc_attr($option_group),
        esc_attr($option),
        $checked
    );
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
/* END - Useful functions */

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

/* START - main mini settings */
// No additional settings needed for now
/* END - main mini settings */

/* START - content settings */
function mini_content_settings_init() {
    register_setting( 'mini_content', 'mini_content_settings');
    add_settings_section(
        'mini_content_section',
        __( '<i>mini</i> content type settings', 'mini' ),
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
    ?>
    <p id="<?php echo esc_attr( $args['id'] ); ?>">
        <i>mini</i> allows you to manage many custom content types to extend WordPress features.
    </p>
    <div class="boxes">
        <div class="box-50 p-2 white-bg b-rad-5 box-shadow">
            <h4 class="grey-text light" for="mini_match"><?php esc_html_e( 'Slides', 'mini' ); ?></h4>
            <label for="mini_slide" class="bold bk-text">    
                <input
                    type="checkbox"
                    id="mini_slide"
                    name="mini_content_settings[mini_slide]"
                    class="me-1"
                    value="1"
                    <?php checked($slide_enabled, true); ?>
                >
                Enable the "Slide" content type to manage slideshows.
            </label>
            <p class="S grey-text" for="mini_slide">It enables slides management (like posts or pages) and related admin menus.</p>
            <p class="S grey-text" for="mini_slide">This option loads <i>mini</i> <b>slider.js</b> library.</p>
        </div>
        <div class="box-50 p-2 white-bg b-rad-5 box-shadow">
            <h4  class="grey-text light" for="mini_match"><?php esc_html_e( 'News', 'mini' ); ?></h4>
            <label for="mini_news" class="bold bk-text">    
                <input
                    type="checkbox"
                    id="mini_news"
                    name="mini_content_settings[mini_news]"
                    class="me-1"
                    value="1"
                    <?php checked($news_enabled, true); ?>
                >
                Enable the "News" content type to manage news articles.
            </label>
            <p class="S grey-text" for="mini_news">It enables news management (like posts or pages) and related admin menus.</p>
        </div>
        <div class="box-50 p-2 white-bg b-rad-5 box-shadow">
            <h4 class="grey-text light" for="mini_match"><?php esc_html_e( 'Events', 'mini' ); ?></h4>
            <label for="mini_event" class="bold bk-text">    
                <input
                    type="checkbox"
                    id="mini_event"
                    name="mini_content_settings[mini_event]"
                    class="me-1"
                    value="1"
                    <?php checked($event_enabled, true); ?>
                >
                Enable the "Event" content type to manage events.
            </label>
            <p class="S grey-text" for="mini_event">It enables events management (like posts or pages) and related admin menus.</p>
        </div>
        <div class="box-50 p-2 white-bg b-rad-5 box-shadow">
            <h4 class="grey-text light" for="mini_match"><?php esc_html_e( 'Matches', 'mini' ); ?></h4>
            <label for="mini_match" class="bold bk-text">    
                <input
                    type="checkbox"
                    id="mini_match"
                    name="mini_content_settings[mini_match]"
                    class="me-1"
                    value="1"
                    <?php checked($match_enabled, true); ?>
                >
                Enable the "Match" content type to manage sport events.
            </label>
            <p class="S grey-text" for="mini_match">It enables matches management (like posts or pages) and related admin menus.</p>
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
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <br/>
        <form action="options.php" method="post">
            <?php
            settings_fields( 'mini_content' );
            do_settings_sections( 'mini-content' );
            submit_button( 'Save Settings' );
            ?>
        </form>
    </div>
    <?php
}
/* END - content settings */

/* START - mini menu */
function mini_plugin_settings_pages() {
    if ( empty ( $GLOBALS['admin_page_hooks']['mini'] ) ) {
        add_menu_page(
            'mini options',
            'mini',
            'manage_options',
            'mini',
            'mini_plugin_main_page_html',
            'https://cdn.jsdelivr.net/gh/giacomorizzotti/mini/img/brand/mini_emblem_wh.svg'
        );
    }
    add_submenu_page(
        'mini',
        'Content types',
        'Content types',
        'manage_options',
        'mini-content',
        'mini_content_page_html',
        9
    );
    add_submenu_page(
        'mini',
        'Blogging',
        'Blogging',
        'manage_options',
        'mini-blogging',
        'mini_blogging_page_html',
        9
    );
    add_submenu_page(
        'mini',
        'SEO',
        'SEO',
        'manage_options',
        'mini-seo',
        'mini_seo_page_html',
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
            var stopWords = ['the', 'be', 'to', 'of', 'and', 'a', 'in', 'that', 'have', 'i', 'it', 'for', 'not', 'on', 'with', 'he', 'as', 'you', 'do', 'at', 'this', 'but', 'his', 'by', 'from', 'they', 'we', 'say', 'her', 'she', 'or', 'an', 'will', 'my', 'one', 'all', 'would', 'there', 'their', 'what', 'so', 'up', 'out', 'if', 'about', 'who', 'get', 'which', 'go', 'me', 'when', 'make', 'can', 'like', 'time', 'no', 'just', 'him', 'know', 'take', 'people', 'into', 'year', 'your', 'good', 'some', 'could', 'them', 'see', 'other', 'than', 'then', 'now', 'look', 'only', 'come', 'its', 'over', 'think', 'also', 'back', 'after', 'use', 'two', 'how', 'our', 'work', 'first', 'well', 'way', 'even', 'new', 'want', 'because', 'any', 'these', 'give', 'day', 'most', 'us', 'is', 'was', 'are', 'been', 'has', 'had', 'were', 'said', 'did', 'having', 'may', 'should', 'am'];
            
            // Clean and tokenize
            var words = text.toLowerCase()
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
    
    // Recommended plugins array
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
    <div class="boxes py-2">
        <div class="box-100 p-2 white-bg b-rad-5 box-shadow mb-2">
            <div class="space"></div>
            <img src="https://cdn.jsdelivr.net/gh/giacomorizzotti/mini/img/brand/mini_logo_2.svg" alt="mini logo" style="max-width: 280px;" class="mb-2"/>
            <h1 class="mb-0"><i>mini</i> is a frontend framework</h1>
            <p class="mt-0">That allows you to build modern, responsive websites with ease.</p>
            <p class="">
                <a href="https://mini.uwa.agency/" target="_blank" rel="noopener noreferrer" class="btn fourth-color-btn white-text"><?php esc_html_e( 'Visit mini website', 'mini' ); ?></a>
            </p>
        </div>

        <div class="box-100 p-2 mb-2">
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
                            <span class="iconoir-check-circle"></span> <?php echo esc_html($button_text); ?>
                        </button>
                    <?php elseif ($is_installed) : ?>
                        <a href="<?php echo esc_url($activate_url); ?>" class="btn S <?php echo esc_attr($button_class); ?>">
                            <span class="iconoir-play-circle"></span> <?php echo esc_html($button_text); ?>
                        </a>
                    <?php else : ?>
                        <a href="<?php echo esc_url($install_url); ?>" class="btn S <?php echo esc_attr($button_class); ?>">
                            <span class="iconoir-download-circle"></span> <?php echo esc_html($button_text); ?>
                        </a>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php
}
/* END - mini settings*/

/* START - Custom post types - Consolidated */
function register_mini_post_type($type, $singular, $plural, $icon, $has_archive = true) {
    register_post_type($type, [
        'labels' => [
            'name' => __($plural, 'mini'),
            'singular_name' => __($singular, 'mini'),
            'add_new' => __('Add ' . $singular, 'mini'),
            'add_new_item' => __('Add New ' . $singular, 'mini'),
            'edit' => __('Edit', 'mini'),
            'edit_item' => __('Edit ' . $singular, 'mini'),
            'new_item' => __('New ' . $singular, 'mini'),
            'view' => __('View ' . $singular, 'mini'),
            'view_item' => __('View ' . $singular, 'mini'),
            'search_items' => __('Search ' . $plural, 'mini'),
            'not_found' => __('No ' . $plural . ' found', 'mini'),
            'archives' => __($plural, 'mini'),
        ],
        'public' => true,
        'has_archive' => $has_archive,
        'menu_icon' => $icon,
        'rewrite' => ['slug' => $type],
        'show_in_rest' => true,
        'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'panels']
    ]);
}

if (is_mini_option_enabled('mini_content_settings', 'mini_news')) {
    add_action('init', function() {
        register_mini_post_type('news', 'News', 'News', 'dashicons-text-page');
    });
}

/* NEWS - shortcodes */
function get_latest_news_callback() {
    $args = array(
        'posts_per_page' => 3,
        'orderby' => 'post_date',
        'order' => 'DESC',
        'post_type' => 'news',
        'post_status' => 'publish',
        'no_found_rows' => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
    );
    
    $query = new WP_Query($args);
    
    if (!$query->have_posts()) {
        wp_reset_postdata();
        return '';
    }
    
    ob_start();
    ?>
    <div class="boxes">
    <?php
    $n = 1;
    while ($query->have_posts()) : $query->the_post();
        $box_class = ($n === 1) ? 'box-100' : 'box-50';
        $delay = ($n > 1) ? 'data-aos-delay="' . esc_attr(150 * ($n - 1)) . '"' : '';
        $has_thumbnail = has_post_thumbnail();
        $inner_box_class = $has_thumbnail ? 'box-50' : 'box-100';
        ?>
        <div class="<?php echo esc_attr($box_class); ?> my-0 p-0" data-aos="fade-up" <?php echo $delay; ?>>
            <div class="boxes">
                <?php if ($has_thumbnail) : ?>
                    <div class="box-50">
                        <a href="<?php the_permalink(); ?>"><?php the_post_thumbnail('large', ['class' => 'img']); ?></a>
                    </div>
                <?php endif; ?>
                <div class="<?php echo esc_attr($inner_box_class); ?>">
                    <h3>
                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                    </h3>
                    <p><?php the_excerpt(); ?></p>
                    <p>
                        <a href="<?php the_permalink(); ?>" class="btn"><?php esc_html_e('Read more', 'mini'); ?></a>
                    </p>
                </div>
            </div>
        </div>
        <?php
        $n++;
    endwhile;
    ?>
    </div>
    <?php
    wp_reset_postdata();
    return ob_get_clean();
}
add_shortcode('latest_news', 'get_latest_news_callback');
/* END - Custom post type - NEWS */

/* START - Custom post type - SLIDE */
if (is_mini_option_enabled('mini_content_settings', 'mini_slide')) {
    add_action('init', function() {
        register_mini_post_type('slide', 'Slide', 'Slides', 'dashicons-slides', false);
    });
}

/* SLIDE - shortcodes */
function get_slides_callback($number=3) {
    $args = array(
        'posts_per_page' => absint($number),
        'orderby' => 'post_date',
        'order' => 'DESC',
        'post_type' => 'slide',
        'post_status' => 'publish',
        'no_found_rows' => true,
    );
    $query = new WP_Query($args);
    if ($query->have_posts()) :
        $slider = '';
        $slider .= '
<div class="container fw grad-fw-down-w">
    <div class="container fw">
        <div class="slider-wrapper">
            <i id="left" class="iconoir-arrow-left-circle slider-controls"></i>
            <ul class="slider fh">
        ';
        $n=1;
        while ($query->have_posts()) : $query->the_post();
            $slider .= '
                <li class="slide">
            ';
            if (get_the_post_thumbnail(get_the_ID())!=false) {
            $slider .= '
                    <div class="img">
                        <img src="'.get_the_post_thumbnail_url(get_the_ID()).'" alt="" draggable="false" />
                    </div>
            ';
            }
            $container_width = get_post_meta(get_the_ID(), 'page_container', true);
            $slider .= '                    
                    <div class="caption">
                        <div class="container '.$container_width.'">
                        '.get_the_content(get_the_ID()).'
                        </div>
                    </div>
                </li>
            ';
        $n++;
        endwhile;
        $slider .= '
            </ul>
            <i id="right" class="iconoir-arrow-right-circle slider-controls"></i>
        </div>
    </div>
</div>
        ';
        wp_reset_postdata();
        return $slider;
    endif;
    
    return '';
}
add_shortcode('slider', 'get_slides_callback');
/* END - Custom post type - SLIDE */

/* START - Custom post type - EVENT */
if (is_mini_option_enabled('mini_content_settings', 'mini_event')) {
    add_action('init', function() {
        register_mini_post_type('event', 'Event', 'Events', 'dashicons-calendar');
    });
}

/* EVENT shortcodes */
function get_next_event_callback($num = 1) {
    $args = array(
        'posts_per_page' => absint($num),
        'meta_key' => 'event_date',
        'orderby' => 'meta_value',
        'order' => 'DESC',
        'post_type' => 'event',
        'post_status' => 'publish',
        'no_found_rows' => true,
    );
    $query = new WP_Query($args);
    if ($query->have_posts()) :
        $n=1;
        $event_list = '';
        while ($query->have_posts()) : $query->the_post();
            $to_come=false;
            if ( get_post_meta(get_the_ID(), 'event_date') != null && get_post_meta(get_the_ID(), 'event_date')[0] >= date("Y-m-d H:i:s") ) { $to_come = True; }
            if ( $n <= $num && $to_come == true ) {
                $event_list .= '
<div class="boxes g-0">
    <div class="box-100 my-0">
        <h4 class="XL m-0">
            <a href="'.get_the_permalink().'" class="wh-box m-0">'.get_the_title().'</a>
        </h4>
    </div>
                ';
                if (
                    get_post_meta(get_the_ID(), 'event_date')[0] != null ||
                    get_post_meta(get_the_ID(), 'event_time')[0] != null) {
                    if (get_post_meta(get_the_ID(), 'event_date')[0] != null) {
                        $formatters = get_italian_date_formatters();
                        $event_date = strtotime(get_post_meta(get_the_ID(), 'event_date')[0]);
                        $event_date_day_name = $formatters['day_name']->format($event_date);
                        $event_date_day = $formatters['day_number']->format($event_date);
                        $event_date_month = $formatters['month']->format($event_date);
                        $event_date_year = $formatters['year']->format($event_date);
                        $event_list .= '
    <div class="box-50 my-0">
        <div class="date-time-box flex flex-wrap">
            <div class="block flex w-100 flex-direction-row flex-wrap">
                <div class="flex">
                    <p class="m-0" style="line-height: 1!important;">
                        <span class="square flex align-items-center justify-content-center second-color-box huge black py-1 px-2 m-0" style="min-width: 140px;">'.$event_date_day.'</span>
                    </p>
                    <div class="flex flex-direction-column">
                        <div class="flex">
                            <p class="m-0 up-case L">
                                <span class="second-color-dark-box px-15 m-0">'.$event_date_day_name.'</span>
                            </p>
                        </div>
                        <div class="flex">
                            <p class="m-0 bold XL"><span class="second-color-box px-15 m-0">'.ucfirst($event_date_month).'</span></p><p class="m-0 XL light"><span class="second-color-dark-box m-0">'.$event_date_year.'</span></p>
                        </div>
                        <div class="flex">
                        ';
                    }
                    if (get_post_meta(get_the_ID(), 'event_time')[0] != null) {
                        $event_time = date('H:i', strtotime(get_post_meta(get_the_ID(), 'event_time')[0]));
                        $event_list .= '
                            <div class="time-box">
                                <p class="m-0 wh-text up-case XL bold" >
                                    <span class="second-color-dark-box m-0">'.$event_time.'</span>
                                </p>
                            </div>
                        ';
                    }
                    $event_list .= '
                        </div>
                    </div>
                </div>
            </div>
        </div>
                    ';
                    if ( 
                        get_post_meta(get_the_ID(), 'location_name')[0] != null ||
                        get_post_meta(get_the_ID(), 'location_address')[0] != null
                    ) {
                        $event_list .= '
        <div class="location-box">
                        ';
                        if ( get_post_meta(get_the_ID(), 'location_name')[0] != null ) {
                            $event_list .= '
            <h4 class="m-0 bold XL">
                '.get_post_meta(get_the_ID(), 'location_name')[0].'
            </h4>
                            ';
                        }
                        if ( get_post_meta(get_the_ID(), 'location_address')[0] != null ) {
                            $event_list .= '
            <div class="sep"></div>
            <p class="m-0">
                '.get_post_meta(get_the_ID(), 'location_address')[0].'
            </p>
                        ';
                        }
                        $event_list .= '
        </div>
                        ';
                    }
                    /*
                    if (get_the_post_thumbnail(get_the_ID())!=false) {
                        if ($box < 100 ) {
                            $match_list .= '
                                <div class="box-100 my-0">
                                <a href="'.get_the_permalink().'">'.get_the_post_thumbnail(get_the_ID()).'</a>
                                </div>
                            ';
                        } else {
                            $match_list .= '
                                <div class="box-33">
                                <a href="'.get_the_permalink().'">'.get_the_post_thumbnail(get_the_ID()).'</a>
                                </div>
                            ';
                        }
                    } 
                    */
                }
                $event_list .= '
    </div>
</div>
                ';
            }
            if ($to_come == true) { $n++; }
        endwhile;
        wp_reset_postdata();
        return $event_list;
    endif;
    
    return '';
}
add_shortcode('next_event', 'get_next_event_callback');
add_shortcode('next_events', function() { return get_next_event_callback(3); });
add_shortcode('next_3_events', function() { return get_next_event_callback(3); });
add_shortcode('next_4_events', function() { return get_next_event_callback(4); });
/* END - Custom post type - EVENT */

/* START - Custom post type - MATCH */
if (is_mini_option_enabled('mini_content_settings', 'mini_match')) {
    add_action('init', function() {
        register_mini_post_type('match', 'Match', 'Matches', 'dashicons-superhero');
    });
}

/* MATCH shortcodes */
function get_next_match_callback($num = 1, $invert = false) {
    $text_color = 'col-text';
    $location_name_box_color = 'bk-box';
    $location_address_box_color = 'white-box';
    if ($invert == true) {
        $text_color = 'wh-text';
        $location_name_box_color = 'wh-box';
        $location_address_box_color = 'light-grey-box';
    }
    $args = array(
        'posts_per_page' => absint($num),
        'meta_key' => 'event_date',
        'orderby' => 'meta_value',
        'order' => 'DESC',
        'post_type' => 'match',
        'post_status' => 'publish',
        'no_found_rows' => true,
    );
    $query = new WP_Query($args);
    if ($query->have_posts()) :
        $n=1;
        $match_list = '';
        while ($query->have_posts()) : $query->the_post();
            $to_come=false;
            if ( get_post_meta(get_the_ID(), 'event_date') != null && get_post_meta(get_the_ID(), 'event_date')[0] >= date("Y-m-d") ) { $to_come = True; }
            if ( $n <= $num && $to_come == true ) {
                $match_list .= '
<div class="boxes g-0">
    <div class="box-100 my-0">
        <h4 class="XL m-0">
            <a href="'.get_the_permalink().'" class="'.$text_color.' wh-box m-0">'.get_the_title().'</a>
        </h4>
    </div>
                ';
				if (
					get_post_meta(get_the_ID(), 'team_1')[0] != null && 
					get_post_meta(get_the_ID(), 'team_2')[0] != null
				) {
                    $match_list .= '
    <div class="box-zero-50 box-sm-25">
        <div class="boxes g-0">
                    ';
                    if ( get_post_meta(get_the_ID(), 'team_1_logo')[0] ) {
                        $team_1_logo = get_post_meta(get_the_ID(), 'team_1_logo')[0];
                        $match_list .= '
            <div class="box-100 p-15 mb-1 square wh-bg">
                <div style="background-image: url(\''.$team_1_logo.'\'); background-position: center; background-size: contain; background-repeat: no-repeat; display: block; width: 100%; height: 100%;"></div>
            </div>
                        ';
                    }
                /*
                if ( get_post_meta(get_the_ID(), 'team_1_score')) {
                    $team_1_score = get_post_meta(get_the_ID(), 'team_1_score')[0];
                    $match_list .= '
            <div class="box-zero-50 second-color-bg flex align-items-center justify-content-center">
                <h3 class="huge center wh-text">'.$team_1_score.'</h3>
            </div>
                    ';
                }
                */
                    $team_1 = get_post_meta(get_the_ID(), 'team_1')[0];
                    $match_list .= '
            <div class="box-100 second-color-dark-bg">
                <h2 class="XL wh-text m-0">'.$team_1.'</h2>
            </div>
        </div>
    </div>
    <div class="box-zero-50 box-sm-25">
        <div class="boxes g-0">
                    ';
                    if ( get_post_meta(get_the_ID(), 'team_2_logo')[0] ) {
                        $team_2_logo = get_post_meta(get_the_ID(), 'team_2_logo')[0];
                        $match_list .= '
                        <div class="box-100 p-15 mb-1 square wh-bg">
                            <div style="background-image: url(\''.$team_2_logo.'\'); background-position: center; background-size: contain; background-repeat: no-repeat; display: block; width: 100%; height: 100%;"></div>
                        </div>
                        ';
                    }
                    /*
                    if ( get_post_meta(get_the_ID(), 'team_2_score')) {
                        $team_2_score = get_post_meta(get_the_ID(), 'team_2_score')[0];
                        $match_list .= '
                        <div class="box-zero-50 second-color-bg flex align-items-center justify-content-center">
                            <h3 class="huge center wh-text">'.$team_2_score.'</h3>
                        </div>
                        ';
                    }
                    */
                    $team_2 = get_post_meta(get_the_ID(), 'team_2')[0];
                    $match_list .= '
            <div class="box-100 second-color-dark-bg">
                <h2 class="XL wh-text m-0">'.$team_2.'</h2>
            </div>
                    ';
                    $match_list .= '
        </div>
    </div>
                    ';
                }
                if (
                    get_post_meta(get_the_ID(), 'event_date')[0] != null ||
                    get_post_meta(get_the_ID(), 'event_time')[0] != null) {
                    if (get_post_meta(get_the_ID(), 'event_date') != null) {
                        $formatters = get_italian_date_formatters();
                        $match_date = strtotime(get_post_meta(get_the_ID(), 'event_date')[0]);
                        $match_date_day_name = $formatters['day_name']->format($match_date);
                        $match_date_day = $formatters['day_number']->format($match_date);
                        $match_date_month = $formatters['month']->format($match_date);
                        $match_date_year = $formatters['year']->format($match_date);
                        $match_list .= '
    <div class="box-50 my-0">
        <div class="date-time-box flex flex-wrap">
            <div class="block flex w-100 flex-direction-row flex-wrap">
                <div class="flex">
                    <p class="m-0" style="line-height: 1!important;">
                        <span class="square flex align-items-center justify-content-center color-box huge black py-1 px-2 m-0" style="min-width: 140px;">'.$match_date_day.'</span>
                    </p>
                    <div class="flex flex-direction-column">
                        <div class="flex">
                            <p class="m-0 up-case L">
                                <span class="color-dark-box px-15 m-0">'.$match_date_day_name.'</span>
                            </p>
                        </div>
                        <div class="flex">
                            <p class="m-0 bold XL"><span class="color-box px-15 m-0">'.ucfirst($match_date_month).'</span></p><p class="m-0 XL light"><span class="color-dark-box m-0">'.$match_date_year.'</span></p>
                        </div>
                        <div class="flex">
                        ';
                    }
                    if (get_post_meta(get_the_ID(), 'event_time')[0] != null) {
                        $match_time = date('H:i', strtotime(get_post_meta(get_the_ID(), 'event_time')[0]));
                        $match_list .= '
                            <div class="time-box">
                                <p class="m-0 wh-text up-case XL bold" >
                                    <span class="color-dark-box m-0">'.$match_time.'</span>
                                </p>
                            </div>
                        ';
                    }
                    $match_list .= '
                        </div>
                    </div>
                </div>
            </div>
        </div>
                    ';
                    if ( 
                        get_post_meta(get_the_ID(), 'location_name')[0] != null ||
                        get_post_meta(get_the_ID(), 'location_address')[0] != null
                    ) {
                        $match_list .= '
        <div class="location-box">
                        ';
                        if ( get_post_meta(get_the_ID(), 'location_name') != null ) {
                            $match_list .= '
            <h4 class="m-0 bold XL '.$location_name_box_color.'">
                '.get_post_meta(get_the_ID(), 'location_name')[0].'
            </h4>
                            ';
                        }
                        if ( get_post_meta(get_the_ID(), 'location_address') != null ) {
                            $match_list .= '
            <div class="sep"></div>
            <p class="m-0 '.$location_address_box_color.'">
                '.get_post_meta(get_the_ID(), 'location_address')[0].'
            </p>
                        ';
                        }
                        $match_list .= '
        </div>
                        ';
                    }
                    /*
                    if (get_the_post_thumbnail(get_the_ID())!=false) {
                        if ($box < 100 ) {
                            $match_list .= '
                                <div class="box-100 my-0">
                                <a href="'.get_the_permalink().'">'.get_the_post_thumbnail(get_the_ID()).'</a>
                                </div>
                            ';
                        } else {
                            $match_list .= '
                                <div class="box-33">
                                <a href="'.get_the_permalink().'">'.get_the_post_thumbnail(get_the_ID()).'</a>
                                </div>
                            ';
                        }
                    } 
                        */
                }
                $match_list .= '
    </div>
</div>
                ';
            } else {
        $match_list = '
<div class="boxes g-0">
    <div class="box-100">
        <h4 class="m-0">
            <p> <span class="wh-box">'.__('No matches to show', 'mini').'</span></p>
        </h4>
    </div>
</div>
        ';
            }
            if ($to_come == true) { $n++; }
        endwhile;
        wp_reset_postdata();
        return $match_list;
    endif;
    
    return '';
}
add_shortcode('next_match', 'get_next_match_callback');
add_shortcode('next_match_inv', function() { return get_next_match_callback(1, true); });
add_shortcode('next_matches', function() { return get_next_match_callback(3, false); });
add_shortcode('next_3_matches', function() { return get_next_match_callback(3, false); });
add_shortcode('next_4_matches', function() { return get_next_match_callback(4, false); });
/* END - Custom post type - MATCH */


/* ADD Date and time options */
add_action( 'add_meta_boxes', 'add_date_time_box' );
add_action( 'save_post', 'date_time_save_postdata' );
function add_date_time_box() {
    add_meta_box(
        'date-time',
        esc_html__( 'Date', 'mini' ),
        'date_time_box_html',
        ['event', 'match'],
        'side'
    );
}
function date_time_box_html( $post, $meta ){
    $date_value = get_post_meta( $post->ID, 'event_date', true) ?: '';
    $end_date_value = get_post_meta( $post->ID, 'event_end_date', true) ?: '';
    $time_value = get_post_meta( $post->ID, 'event_time', true) ?: '';
    $end_time_value = get_post_meta( $post->ID, 'event_end_time', true) ?: '';
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
    <div style="display: flex; flex-flow: row wrap; margin-bottom: 1rem;">
        <div style="flex: 1; margin-bottom: 0.5rem;">
            <label for="event_time" style="margin-bottom: 0.5rem; display: block;"><?php _e('Time', 'mini'); ?>:</label>
            <input type="time" id="event_time" name="event_time" value="<?php echo esc_attr($time_value); ?>" style="min-width: 220px; display: block;" />
        </div>
        <div style="flex: 1; margin-bottom: 0.5rem;">
            <label for="event_end_time" style="margin-bottom: 0.5rem; display: block;"><?php _e('End time (optional)', 'mini'); ?>:</label>
            <input type="time" id="event_end_time" name="event_end_time" value="<?php echo esc_attr($end_time_value); ?>" style="min-width: 220px; display: block;" />
        </div>
    </div>
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
        ['event', 'match'],
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
        esc_html__( 'Teams', 'mini' ),
        'teams_box_html',
        ['match'],
        #'side'
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
    <div style="display: flex; flex-flow: row wrap;">
        <div style="flex: 1;">
            <h3><?php _e('Team one', 'mini'); ?></h3>
        </div>
    </div>
    <div style="display: flex; flex-flow: row wrap; margin-bottom: 0.5rem;">
        <div style="flex: 1; margin-bottom: 0.5rem;">
            <label for="team_1" style="margin-bottom: 0.5rem; display: block;"><?php _e('Team one', 'mini'); ?>:</label>
            <input type="text" id="team_1" name="team_1" value="<?php echo esc_attr($team_1_value); ?>" style="min-width: 220px;" />
        </div>
        <div style="flex: 1; margin-bottom: 0.5rem;">
            <label for="team_1_logo" style="margin-bottom: 0.5rem; display: block;"><?php _e('Team one logo', 'mini'); ?>:</label>
            <input id="team_1_logo" type="text" name="team_1_logo" value="<?php echo esc_url($team_1_logo_value); ?>" style="margin-bottom: 0.5rem; display: block; min-width: 220px;"/>
            <input id="team_1_logo_button" class="components-button editor-post-status__toggle is-compact is-tertiary has-text has-icon" type="button" value="<?php esc_attr_e('Upload logo', 'mini'); ?>" />
        </div>
        <div style="flex: 1; margin-bottom: 0.5rem;">
            <label for="team_1_score" style="margin-bottom: 0.5rem; display: block;"><?php _e('Team one score', 'mini'); ?>:</label>
            <input type="number" id="team_1_score" name="team_1_score" value="<?php echo esc_attr($team_1_score_value); ?>" style="min-width: 220px;" />
        </div>
    </div>
    <div style="display: flex; flex-flow: row wrap;">
        <div style="flex: 1;">
            <h3><?php _e('Team two', 'mini'); ?></h3>
        </div>
    </div>
    <div style="display: flex; flex-flow: row wrap; margin-bottom: 0.5rem;">
        <div style="flex: 1; margin-bottom: 0.5rem;">
            <label for="team_2" style="margin-bottom: 0.5rem; display: block;"><?php _e('Team two', 'mini'); ?>:</label>
            <input type="text" id="team_2" name="team_2" value="<?php echo esc_attr($team_2_value); ?>" style="min-width: 220px;" />
        </div>
        <div style="flex: 1; margin-bottom: 0.5rem;">
            <label for="team_2_logo" style="margin-bottom: 0.5rem; display: block;"><?php _e('Team two logo', 'mini'); ?>:</label>
            <input id="team_2_logo" type="text" name="team_2_logo" value="<?php echo esc_url($team_2_logo_value); ?>" style="margin-bottom: 0.5rem; display: block; min-width: 220px;"/>
            <input id="team_2_logo_button" class="components-button editor-post-status__toggle is-compact is-tertiary has-text has-icon" type="button" value="<?php esc_attr_e('Upload logo', 'mini'); ?>" />
        </div>
        <div style="flex: 1; margin-bottom: 0.5rem;">
            <label for="team_2_score" style="margin-bottom: 0.5rem; display: block;"><?php _e('Team two score', 'mini'); ?>:</label>
            <input type="number" id="team_2_score" name="team_2_score" value="<?php echo esc_attr($team_2_score_value); ?>" style="min-width: 220px;" />
        </div>
    </div>
    <?php
}

function media_upload_scripts() {
    $screen = get_current_screen();
    if (!$screen || !in_array($screen->post_type, ['match', 'event'])) {
        return;
    }
    
    wp_enqueue_script('media-upload');
    wp_enqueue_script('thickbox');
    wp_register_script('media-upload-script', plugins_url('media-upload/media-upload.js', __FILE__), array('jquery','media-upload','thickbox'), '1.0', true);
    wp_enqueue_script('media-upload-script');
}
add_action('admin_enqueue_scripts', 'media_upload_scripts');

function media_upload_styles() {
    $screen = get_current_screen();
    if (!$screen || !in_array($screen->post_type, ['match', 'event'])) {
        return;
    }
    
    wp_enqueue_style('thickbox');
    wp_register_style('media-upload-style', plugins_url('media-upload/media-upload.css', __FILE__), array(), '1.0');
    wp_enqueue_style('media-upload-style');
}
add_action('admin_enqueue_scripts', 'media_upload_styles');

function load_mini_css_in_mini_plugin_admin_pages() {
    $screen = get_current_screen();
    
    // Load on mini admin pages OR post/page edit screens
    if ($screen && (strpos($screen->id, 'mini') !== false || in_array($screen->base, ['post', 'post-new']))) {
        $options = get_option('mini_main_settings');
        $version = isset($options['mini_css_version']) ? sanitize_text_field($options['mini_css_version']) : 'latest';
         
        if ($version === 'latest') {
            $css_url = 'https://cdn.jsdelivr.net/gh/giacomorizzotti/mini/css/mini.min.css';
        } else {
            $css_url = 'https://cdn.jsdelivr.net/gh/giacomorizzotti/mini@' . esc_attr($version) . '/css/mini.min.css';
        }
        
        wp_enqueue_style('mini-css', $css_url, array(), $version);
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
function mini_comment_settings_init() {
    register_setting( 'mini_comment', 'mini_comment_settings');
    add_settings_section(
        'mini_comment_section',
        __( '<i>mini</i> comment settings', 'mini' ),
        'mini_comment_section_callback',
        'mini-comment'
    );
}
add_action( 'admin_init', 'mini_comment_settings_init' );
function mini_comment_section_callback( $args ) {
    ?>
    <div class="space"></div>
    <div class="boxes">
        <div class="box-33 p-2 white-bg b-rad-5 box-shadow">
            <h4 class="" for="mini_match"><?php esc_html_e( 'Disable comments', 'mini' ); ?></h4>
            <?= mini_plugin_checkbox_option('mini_comment_settings','mini_disable_comment'); ?>
            <p class="" for="mini_news">This option will disable comment features and related admin menus.</p>
        </div>
    </div>
    <?php
}

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
    add_settings_section(
        'mini_blogging_section',
        __( '<i>mini</i> blogging settings', 'mini' ),
        'mini_blogging_section_callback',
        'mini-blogging'
    );
}
add_action( 'admin_init', 'mini_blogging_settings_init' );
function mini_blogging_section_callback( $args ) {
    ?>
    <div class="space"></div>
    <div class="boxes">
        <div class="box-33 p-2 white-bg b-rad-5 box-shadow">
            <h4 class="danger-text" for="mini_match"><?php esc_html_e( 'Disable blogging', 'mini' ); ?></h4>
            <?= mini_plugin_checkbox_option('mini_blogging_settings','mini_disable_blogging'); ?>
            <p class="" for="mini_news">This option will <u>disable blogging features</u> including posts, blog archive pages and related admin menus.</p>
        </div>
    </div>
    <?php
}
function mini_blogging_page_html() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    
    $active_tab = $_GET['tab'] ?? 'blogging';
    
    if ( isset( $_GET['settings-updated'] ) ) {
        add_settings_error( 'mini_messages', 'mini_message', __( 'Settings Saved', 'mini' ), 'updated' );
    }
    settings_errors( 'mini_messages' );
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        
        <h2 class="nav-tab-wrapper">
            <a href="?page=mini-blogging&tab=blogging" class="nav-tab <?php echo $active_tab == 'blogging' ? 'nav-tab-active' : ''; ?>"><?php _e('Blogging', 'mini'); ?></a>
            <a href="?page=mini-blogging&tab=comments" class="nav-tab <?php echo $active_tab == 'comments' ? 'nav-tab-active' : ''; ?>"><?php _e('Comments', 'mini'); ?></a>
        </h2>
        
        <br/>
        
        <?php if ($active_tab == 'blogging') : ?>
            <form action="options.php" method="post">
                <?php
                settings_fields( 'mini_blogging' );
                do_settings_sections( 'mini-blogging' );
                submit_button( 'Save Settings' );
                ?>
            </form>
        <?php elseif ($active_tab == 'comments') : ?>
            <form action="options.php" method="post">
                <?php
                settings_fields( 'mini_comment' );
                do_settings_sections( 'mini-comment' );
                submit_button( 'Save Settings' );
                ?>
            </form>
        <?php endif; ?>
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

/* START - SEO settings */
function mini_seo_settings_init() {
    register_setting( 'mini_seo', 'mini_seo_settings');
    add_settings_section(
        'mini_seo_section',
        __( '<i>mini</i> SEO settings', 'mini' ),
        'mini_seo_section_callback',
        'mini-seo'
    );
}
add_action( 'admin_init', 'mini_seo_settings_init' );

function mini_seo_section_callback( $args ) {
    $seo_enabled = is_mini_option_enabled('mini_seo_settings', 'mini_enable_seo');
    ?>
    <div class="space"></div>
    <div class="boxes">
        <div class="box-50 p-2 white-bg b-rad-5 box-shadow">
            <h4><?php esc_html_e( 'Enable SEO Features', 'mini' ); ?></h4>
            <label for="mini_enable_seo">    
                <input
                    type="checkbox"
                    id="mini_enable_seo"
                    name="mini_seo_settings[mini_enable_seo]"
                    class="me-1"
                    value="1"
                    <?php checked($seo_enabled, true); ?>
                >
                Enable SEO meta tags for pages, posts and custom content types.
            </label>
            <p class="S grey-text">When enabled, you'll be able to customize title, description, keywords, robots directives, and social media tags for each page/post.</p>
        </div>
        <div class="box-50 p-2 white-bg b-rad-5 box-shadow">
            <h4><?php esc_html_e( 'Default Settings', 'mini' ); ?></h4>
            <label for="mini_seo_image" class="bold"><?php _e('Default Meta Description', 'mini'); ?>:</label>
            <textarea 
                name="mini_seo_settings[default_description]" 
                rows="3" 
                style="width: 100%;"
                maxlength="160"
                placeholder="Default site description (max 160 characters)"
            ><?php echo esc_attr( get_variable('mini_seo_settings', 'default_description') ); ?></textarea>
            <p class="S grey-text mt-05">This will be used when individual pages don't have their own description.</p>
            <div class="sep my-2 light-grey-border"></div>
            <label for="mini_seo_image" class="bold"><?php _e('Default SEO Image', 'mini'); ?>:</label>
            <div class="flex mb-1">
                <button type="button" class="button me-1" id="mini_seo_default_image_button" style="width: 120px;">Select Image</button>
                <input 
                    type="url" 
                    name="mini_seo_settings[default_image]" 
                    id="mini_seo_default_image"
                    value="<?php echo esc_url( get_variable('mini_seo_settings', 'default_image') ); ?>"
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
    <?php
}

function mini_seo_page_html() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    
    if ( isset( $_GET['settings-updated'] ) ) {
        add_settings_error( 'mini_messages', 'mini_message', __( 'Settings Saved', 'mini' ), 'updated' );
    }
    settings_errors( 'mini_messages' );
    wp_enqueue_media();
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
    </div>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
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
                        <input type="checkbox" name="mini_seo_robots_index" value="1" <?php checked($seo_robots_index, '1'); ?>>
                        <b><?php _e('Index', 'mini'); ?></b> <span class="S"><?php _e('(allow search engines to index this page)', 'mini'); ?></span>
                    </label>
                    <label>
                        <input type="checkbox" name="mini_seo_robots_follow" value="1" <?php checked($seo_robots_follow, '1'); ?>>
                        <b><?php _e('Follow', 'mini'); ?></b> <span class="S"><?php _e('(allow search engines to follow links on this page)', 'mini'); ?></span>
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
                        <option value=""><?php _e('-- Select --', 'mini'); ?></option>
                        <option value="summary" <?php selected($seo_twitter_card, 'summary'); ?>><?php _e('Summary', 'mini'); ?></option>
                        <option value="summary_large_image" <?php selected($seo_twitter_card, 'summary_large_image'); ?>><?php _e('Summary Large Image', 'mini'); ?></option>
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
        'mini_seo_robots_index',
        'mini_seo_robots_follow',
        'mini_seo_og_title',
        'mini_seo_og_description',
        'mini_seo_og_image',
        'mini_seo_twitter_card',
        'mini_seo_twitter_title',
        'mini_seo_twitter_description',
        'mini_seo_image',
        'mini_seo_canonical',
    ];
    
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, '_' . $field, sanitize_text_field($_POST[$field]));
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
    if ($seo_robots_index == '1') {
        $robots[] = 'index';
    } else {
        $robots[] = 'noindex';
    }
    
    if ($seo_robots_follow == '1') {
        $robots[] = 'follow';
    } else {
        $robots[] = 'nofollow';
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
    if (!empty($seo_twitter_card)) {
        echo '<meta name="twitter:card" content="' . esc_attr($seo_twitter_card) . '">' . "\n";
    }
    
    if (!empty($seo_twitter_title)) {
        echo '<meta name="twitter:title" content="' . esc_attr($seo_twitter_title) . '">' . "\n";
    }
    
    if (!empty($seo_twitter_description)) {
        echo '<meta name="twitter:description" content="' . esc_attr($seo_twitter_description) . '">' . "\n";
    }
    
    if (!empty($seo_og_image)) {
        echo '<meta name="twitter:image" content="' . esc_url($seo_og_image) . '">' . "\n";
    }
}
add_action('wp_head', 'mini_output_seo_meta_tags', 1);

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



