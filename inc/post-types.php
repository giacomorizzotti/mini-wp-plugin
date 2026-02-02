<?php
/**
 * Custom Post Types Registration
 *
 * @package mini
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Register a custom post type with mini defaults
 */
function register_mini_post_type($type, $singular, $plural, $icon, $has_archive = true, $hierarchical = false) {
    $supports = ['title', 'editor', 'thumbnail', 'excerpt', 'panels'];
    
    // Add page-attributes support for hierarchical post types (enables parent dropdown and menu_order)
    if ($hierarchical) {
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

/* Register News Post Type */
if (is_mini_option_enabled('mini_content_settings', 'mini_news')) {
    add_action('init', function() {
        register_mini_post_type('news', 'News', 'News', 'dashicons-text-page');
    });
}

/* Register Slide Post Type */
if (is_mini_option_enabled('mini_content_settings', 'mini_slide')) {
    add_action('init', function() {
        register_mini_post_type('slide', 'Slide', 'Slides', 'dashicons-slides', false);
    });
}

/* Register Event Post Type */
if (is_mini_option_enabled('mini_content_settings', 'mini_event')) {
    add_action('init', function() {
        register_mini_post_type('event', 'Event', 'Events', 'dashicons-calendar');
    });
}

/* Register Match Post Type */
if (is_mini_option_enabled('mini_content_settings', 'mini_match')) {
    add_action('init', function() {
        register_mini_post_type('match', 'Match', 'Matches', 'dashicons-superhero');
    });
}

/* Register Course and Lesson Post Types */
if (is_mini_option_enabled('mini_content_settings', 'mini_course')) {
    add_action('init', function() {
        register_mini_post_type('course', 'Course', 'Courses', 'dashicons-welcome-learn-more');
        
        // Register lesson post type as hierarchical (lessons can have courses as parents)
        register_mini_post_type('lesson', 'Lesson', 'Lessons', 'dashicons-book', false, true);
    });
}
