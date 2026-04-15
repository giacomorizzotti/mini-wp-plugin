<?php
/* START - Dashboard Customization */

/**
 * Remove default dashboard widgets
 */
function mini_remove_dashboard_widgets() {
    // Remove WordPress default widgets
    remove_meta_box('dashboard_quick_press', 'dashboard', 'side');          // Quick Draft
    remove_meta_box('dashboard_recent_drafts', 'dashboard', 'side');        // Recent Drafts
    remove_meta_box('dashboard_primary', 'dashboard', 'side');              // WordPress Events and News
    remove_meta_box('dashboard_secondary', 'dashboard', 'side');            // Other WordPress News
    remove_meta_box('dashboard_incoming_links', 'dashboard', 'normal');     // Incoming Links
    remove_meta_box('dashboard_plugins', 'dashboard', 'normal');            // Plugins
    remove_meta_box('dashboard_right_now', 'dashboard', 'normal');          // At a Glance (Right Now)
    remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');    // Recent Comments
    remove_meta_box('dashboard_activity', 'dashboard', 'normal');           // Activity
    remove_meta_box('dashboard_site_health', 'dashboard', 'normal');        // Site Health Status

    // Remove Yoast SEO widget if it exists
    //remove_meta_box('wpseo-dashboard-overview', 'dashboard', 'side');

    // Remove Jetpack widget if it exists
    //remove_meta_box('jetpack_summary_widget', 'dashboard', 'normal');
}
add_action('wp_dashboard_setup', 'mini_remove_dashboard_widgets', 999);

/**
 * Add custom dashboard widgets
 */
function mini_add_custom_dashboard_widgets() {
    wp_add_dashboard_widget(
        'mini_content_stats',
        __('Content Statistics', 'mini'),
        'mini_content_stats_widget'
    );

    wp_add_dashboard_widget(
        'mini_recent_content',
        __('Recent Content', 'mini'),
        'mini_recent_content_widget'
    );
}
add_action('wp_dashboard_setup', 'mini_add_custom_dashboard_widgets');

/**
 * Content Statistics Widget
 */
function mini_content_stats_widget() {
    $stats = array();

    // Get counts for enabled post types
    if (is_mini_option_enabled('mini_content_settings', 'mini_news')) {
        $news_count = wp_count_posts('news');
        $stats['news'] = array(
            'label' => __('News', 'mini'),
            'count' => $news_count->publish,
            'icon' => 'dashicons-text-page'
        );
    }

    if (is_mini_option_enabled('mini_content_settings', 'mini_event')) {
        $event_count = wp_count_posts('event');
        $stats['event'] = array(
            'label' => __('Events', 'mini'),
            'count' => $event_count->publish,
            'icon' => 'dashicons-calendar-alt'
        );
    }

    if (is_mini_option_enabled('mini_content_settings', 'mini_match')) {
        $match_count = wp_count_posts('match');
        $stats['match'] = array(
            'label' => __('Matches', 'mini'),
            'count' => $match_count->publish,
            'icon' => 'dashicons-awards'
        );
    }

    if (is_mini_option_enabled('mini_content_settings', 'mini_course')) {
        $course_count = wp_count_posts('course');
        $lesson_count = wp_count_posts('lesson');
        $stats['course'] = array(
            'label' => __('Courses', 'mini'),
            'count' => $course_count->publish,
            'icon' => 'dashicons-welcome-learn-more'
        );
        $stats['lesson'] = array(
            'label' => __('Lessons', 'mini'),
            'count' => $lesson_count->publish,
            'icon' => 'dashicons-book'
        );
    }

    if (is_mini_option_enabled('mini_content_settings', 'mini_slide')) {
        $slideshow_count = wp_count_posts('slideshow');
        $slide_count = wp_count_posts('slide');
        $stats['slideshow'] = array(
            'label' => __('Slideshows', 'mini'),
            'count' => $slideshow_count->publish,
            'icon' => 'dashicons-images-alt2'
        );
        $stats['slide'] = array(
            'label' => __('Slides', 'mini'),
            'count' => $slide_count->publish,
            'icon' => 'dashicons-slides'
        );
    }

    // Default post types
    $post_count = wp_count_posts('post');
    $page_count = wp_count_posts('page');

    echo '<div class="boxes">';

    // Default content

    echo '<div class="mini-stat-item box-50 false-white-border border-5 b-rad-10">';
    echo '<p class="m-0"><span class="dashicons dashicons-admin-page third-color-text big block mx-auto" style="height: 40px; width: 40px;"></span></p>';
    echo '<div class="space-05"></div>';
    echo '<p class="giant center m-0 mini-stat-count">' . esc_html($page_count->publish) . '</p>';
    echo '<p class="center m-0 mini-stat-label">' . esc_html__('Pages', 'mini') . '</p>';
    echo '</div>';

    // Show Posts only if blogging is not disabled
    if (!is_mini_option_enabled('mini_blogging_settings', 'mini_disable_blogging')) {
        echo '<div class="mini-stat-item box-50 false-white-border border-5 b-rad-10">';
        echo '<p class="m-0"><span class="dashicons dashicons-admin-post third-color-text big block mx-auto" style="height: 40px; width: 40px;"></span></p>';
        echo '<div class="space-05"></div>';
        echo '<p class="giant center m-0 mini-stat-count">' . esc_html($post_count->publish) . '</p>';
        echo '<p class="center m-0 mini-stat-label">' . esc_html__('Posts', 'mini') . '</p>';
        echo '</div>';
    }

    // Custom post types
    foreach ($stats as $stat) {
        echo '<div class="mini-stat-item box-50 false-white-border border-5 b-rad-10">';
        echo '<p class="m-0"><span class="dashicons ' . esc_attr($stat['icon']) . ' third-color-text big block mx-auto" style="height: 40px; width: 40px;"></span></p>';
        echo '<div class="space-05"></div>';
        echo '<p class="giant center m-0 mini-stat-count">' . esc_html($stat['count']) . '</p>';
        echo '<p class="center m-0 mini-stat-label">' . esc_html($stat['label']) . '</p>';
        echo '</div>';
    }

    echo '</div>';
}

/**
 * Recent Content Widget
 */
function mini_recent_content_widget() {
    $post_types = array('post', 'page');

    // Add enabled custom post types
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
    if (is_mini_option_enabled('mini_content_settings', 'mini_slide')) {
        $post_types[] = 'slideshow';
        $post_types[] = 'slide';
    }

    $recent_posts = new WP_Query(array(
        'post_type' => $post_types,
        'posts_per_page' => 10,
        'post_status' => 'publish',
        'orderby' => 'date',
        'order' => 'DESC'
    ));

    if ($recent_posts->have_posts()) {

        echo '<ul class="mini-recent-list">';
        while ($recent_posts->have_posts()) {
            $recent_posts->the_post();
            $post_type_obj = get_post_type_object(get_post_type());

            echo '<li class=" mb-1 mini-recent-item">';
            echo '<p class="bold mb-0 mini-recent-title">';
            echo '<a href="' . esc_url(get_edit_post_link()) . '">' . esc_html(get_the_title()) . '</a>';
            echo '&nbsp;&nbsp;<span class="flag third-color-bg b-rad-3 white-text mini-post-type-badge">' . esc_html($post_type_obj->labels->singular_name) . '</span>';
            echo '</p>';
            echo '<p class="mt-0 S grey-text mini-recent-meta">';
            echo esc_html(get_the_date());
            echo '</p>';
            echo '</li>';
        }
        echo '</ul>';
        wp_reset_postdata();
    } else {
        echo '<p>' . esc_html__('No content found.', 'mini') . '</p>';
    }
}

/**
 * Add favicon to admin and login areas
 * Uses plugin's img/favicon.ico
 */
function mini_plugin_favicon() {
    $plugin_root = dirname(__DIR__);
    $favicon_path = $plugin_root . '/img/favicon.ico';
    if (file_exists($favicon_path)) {
        $favicon_url = plugins_url('img/favicon.ico', $plugin_root . '/mini-plugin.php');
        echo "<link rel='shortcut icon' href='" . esc_url($favicon_url) . "' />\n";
    }
}
add_action('admin_head', 'mini_plugin_favicon');
add_action('login_head', 'mini_plugin_favicon');

/**
 * Custom Welcome Message
 */
function mini_custom_welcome_panel() {
    $current_user = wp_get_current_user();
    $user_name = $current_user->display_name;

    ?>
    <div class="boxes welcome-panel-content mini-second-grainy-grad b-rad-10 box-shadow">
        <div class="box-100 p-2 mini-welcome-panel">
            <h2 class="white-text regular mb-0 mini-welcome-header">
                <?php
                if (!empty($user_name)) {
                    printf(__('Ciao, <span class="bold">%s!</span>', 'mini'), esc_html($user_name));
                } else {
                    _e('Ciao!', 'mini');
                }
                ?>
            </h2>
            <p class="white-text L mt-0 mini-welcome-text">
                <?php _e('Ready to create something amazing? Here are some quick actions to get you started.', 'mini'); ?>
            </p>
            <div class="space-2"></div>
            <div class="mini-welcome-actions">
                <?php
                // Show buttons based on enabled post types
                if (is_mini_option_enabled('mini_content_settings', 'mini_news')) {
                    echo '<a href="' . esc_url(admin_url('post-new.php?post_type=news')) . '" class="btn white-btn-invert transparent-bg white-text mini-welcome-button">';
                    echo '<span class="dashicons dashicons-text-page"></span>';
                    echo __('Add News', 'mini');
                    echo '</a>';
                }

                if (is_mini_option_enabled('mini_content_settings', 'mini_event')) {
                    echo '<a href="' . esc_url(admin_url('post-new.php?post_type=event')) . '" class="btn white-btn-invert transparent-bg white-text mini-welcome-button">';
                    echo '<span class="dashicons dashicons-calendar-alt"></span>';
                    echo __('Add Event', 'mini');
                    echo '</a>';
                }

                if (is_mini_option_enabled('mini_content_settings', 'mini_course')) {
                    echo '<a href="' . esc_url(admin_url('post-new.php?post_type=course')) . '" class="btn white-btn-invert transparent-bg white-text mini-welcome-button">';
                    echo '<span class="dashicons dashicons-welcome-learn-more"></span>';
                    echo __('Add Course', 'mini');
                    echo '</a>';
                }

                // Show Add Post button only if blogging is not disabled
                if (!is_mini_option_enabled('mini_blogging_settings', 'mini_disable_blogging')) {
                    echo '<a href="' . esc_url(admin_url('post-new.php')) . '" class="btn white-btn-invert transparent-bg white-text mini-welcome-button">';
                    echo '<span class="dashicons dashicons-admin-post"></span>';
                    echo __('Add Post', 'mini');
                    echo '</a>';
                }
                ?>

                <a href="<?php echo esc_url(admin_url('post-new.php?post_type=page')); ?>" class="btn white-btn-invert transparent-bg white-text mini-welcome-button">
                    <span class="dashicons dashicons-admin-page"></span>
                    <?php _e('Add Page', 'mini'); ?>
                </a>

                <a href="<?php echo esc_url(home_url('/')); ?>" class="btn white-btn-invert transparent-bg white-text mini-welcome-button" target="_blank">
                    <span class="dashicons dashicons-visibility"></span>
                    <?php _e('View Site', 'mini'); ?>
                </a>

            </div>
        </div>
    </div>
    <?php
}

/**
 * Replace default welcome panel with custom one
 */
function mini_replace_welcome_panel() {
    remove_action('welcome_panel', 'wp_welcome_panel');
    add_action('welcome_panel', 'mini_custom_welcome_panel');

    // Ensure the welcome panel is visible for current user
    $user_id = get_current_user_id();
    if ( get_user_meta( $user_id, 'show_welcome_panel', true ) != 1 ) {
        update_user_meta( $user_id, 'show_welcome_panel', 1 );
    }
}
add_action('load-index.php', 'mini_replace_welcome_panel');

/* END - Dashboard Customization */
