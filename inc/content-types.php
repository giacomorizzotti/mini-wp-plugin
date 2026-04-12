<?php
/* START - Custom post types - Consolidated */
function register_mini_post_type($type, $singular, $plural, $icon, $has_archive = true, $hierarchical = false, $page_attributes = true) {
    $supports = ['title', 'editor', 'thumbnail', 'excerpt', 'panels'];

    // Add page-attributes support for hierarchical post types (enables parent dropdown and menu_order)
    // Pass $page_attributes = false when using a custom parent meta box instead
    if ($hierarchical && $page_attributes) {
        $supports[] = 'page-attributes';
    }

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
        'hierarchical' => $hierarchical,
        'has_archive' => $has_archive,
        'menu_icon' => $icon,
        'rewrite' => ['slug' => $type],
        'show_in_rest' => true,
        'supports' => $supports
    ]);
}

if (is_mini_option_enabled('mini_content_settings', 'mini_news')) {
    add_action('init', function() {
        register_mini_post_type('news', 'News', 'News', 'dashicons-text-page');
    });
}

/* START - Custom post type - SLIDESHOW */
if (is_mini_option_enabled('mini_content_settings', 'mini_slide')) {
    add_action('init', function() {
        register_mini_post_type('slideshow', 'Slideshow', 'Slideshows', 'dashicons-images-alt2');

        // Slide is hierarchical for post_parent storage, but uses a custom parent meta box
        // (page_attributes=false) so we control the parent dropdown ourselves
        register_mini_post_type('slide', 'Slide', 'Slides', 'dashicons-slides', false, true, false);
    });
}
/* END - Custom post type - SLIDESHOW */

/* START - Custom post type - EVENT */
if (is_mini_option_enabled('mini_content_settings', 'mini_event')) {
    add_action('init', function() {
        register_mini_post_type('event', 'Event', 'Events', 'dashicons-calendar');
    });
}

/* START - Custom post type - MATCH */
if (is_mini_option_enabled('mini_content_settings', 'mini_match')) {
    add_action('init', function() {
        register_mini_post_type('match', 'Match', 'Matches', 'dashicons-superhero');
    });
}

/* START - Custom post type - COURSE */
if (is_mini_option_enabled('mini_content_settings', 'mini_course')) {
    add_action('init', function() {
        register_mini_post_type('course', 'Course', 'Courses', 'dashicons-welcome-learn-more');

        // Lesson is hierarchical for post_parent storage, but uses a custom parent meta box
        // (page_attributes=false) so we control the parent dropdown ourselves
        register_mini_post_type('lesson', 'Lesson', 'Lessons', 'dashicons-book', false, true, false);
    });
}
/* END - Custom post type - COURSE */

/**
 * Custom parent meta boxes for slide → slideshow and lesson → course.
 *
 * WordPress's built-in parent dropdown (via page-attributes) uses get_pages(),
 * which only works for hierarchical post types. Since slideshow and course are
 * not hierarchical, we replace the meta box entirely with a custom one that uses
 * get_posts() directly.
 *
 * WordPress core saves 'parent_id' → post_parent and 'menu_order' automatically,
 * so no custom save_post hook is needed.
 */
add_action('add_meta_boxes', function() {
    if (is_mini_option_enabled('mini_content_settings', 'mini_slide')) {
        add_meta_box(
            'mini_slide_parent',
            __('Slideshow', 'mini'),
            'mini_slide_parent_meta_box',
            'slide',
            'side',
            'high'
        );
    }
    if (is_mini_option_enabled('mini_content_settings', 'mini_course')) {
        add_meta_box(
            'mini_lesson_parent',
            __('Course', 'mini'),
            'mini_lesson_parent_meta_box',
            'lesson',
            'side',
            'high'
        );
    }
});

function mini_slide_parent_meta_box($post) {
    $slideshows = get_posts([
        'post_type'      => 'slideshow',
        'posts_per_page' => -1,
        'post_status'    => ['publish', 'draft'],
        'orderby'        => 'title',
        'order'          => 'ASC',
    ]);
    ?>
    <div style="display:flex;flex-flow:row wrap;margin-bottom:1rem;">
        <div style="flex:1;">
            <label for="mini_slide_parent" style="margin-bottom:0.5rem;display:block;"><?php esc_html_e('Parent Slideshow', 'mini'); ?>:</label>
            <select name="parent_id" id="mini_slide_parent" style="width:100%;">
                <option value="0"><?php esc_html_e('— No slideshow —', 'mini'); ?></option>
                <?php foreach ($slideshows as $sw) : ?>
                <option value="<?php echo esc_attr($sw->ID); ?>"<?php selected($post->post_parent, $sw->ID); ?>>
                    <?php echo esc_html($sw->post_title); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div style="display:flex;flex-flow:row wrap;">
        <div style="flex:1;">
            <label for="mini_slide_order" style="margin-bottom:0.5rem;display:block;"><?php esc_html_e('Order', 'mini'); ?>:</label>
            <input type="number" name="menu_order" id="mini_slide_order" value="<?php echo esc_attr($post->menu_order); ?>" style="width:100%;" min="0" />
        </div>
    </div>
    <?php
}

function mini_lesson_parent_meta_box($post) {
    $courses = get_posts([
        'post_type'      => 'course',
        'posts_per_page' => -1,
        'post_status'    => ['publish', 'draft'],
        'orderby'        => 'title',
        'order'          => 'ASC',
    ]);
    ?>
    <div style="display:flex;flex-flow:row wrap;margin-bottom:1rem;">
        <div style="flex:1;">
            <label for="mini_lesson_parent" style="margin-bottom:0.5rem;display:block;"><?php esc_html_e('Parent Course', 'mini'); ?>:</label>
            <select name="parent_id" id="mini_lesson_parent" style="width:100%;">
                <option value="0"><?php esc_html_e('— No course —', 'mini'); ?></option>
                <?php foreach ($courses as $course) : ?>
                <option value="<?php echo esc_attr($course->ID); ?>"<?php selected($post->post_parent, $course->ID); ?>>
                    <?php echo esc_html($course->post_title); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div style="display:flex;flex-flow:row wrap;">
        <div style="flex:1;">
            <label for="mini_lesson_order" style="margin-bottom:0.5rem;display:block;"><?php esc_html_e('Order', 'mini'); ?>:</label>
            <input type="number" name="menu_order" id="mini_lesson_order" value="<?php echo esc_attr($post->menu_order); ?>" style="width:100%;" min="0" />
        </div>
    </div>
    <?php
}
