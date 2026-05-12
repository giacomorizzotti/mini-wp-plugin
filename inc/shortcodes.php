<?php
/**
 * Shortcodes
 *
 * @package mini
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/* Helper Functions */

/**
 * Format and display date/time box
 */
function mini_render_date_time_box($post_id, $cols=1, $wrapper_class = 'my-0', $first_box_color_class = '', $second_box_color_class = '') {
    $event_date_meta = get_post_meta($post_id, 'event_date', true);
    $event_time_meta = get_post_meta($post_id, 'event_time', true);
    
    if (empty($event_date_meta) && empty($event_time_meta)) {
        return '';
    }
    
    switch ($cols) {
        case 1:
            $box_class = 'box-100';
            break;
        case 2:
            $box_class = 'box-50';
            break;
        case 3:
            $box_class = 'box-33';
            break;
        case 4:
            $box_class = 'box-25';
            break;
        default:
            $box_class = 'box-100';
            break;
    }
    $output = '
        <div class="'.$box_class.' '.$wrapper_class.'"><div class="date-time-box flex flex-wrap"><div class="block flex w-100 flex-direction-row flex-wrap"><div class="flex">
    ';
    
    if (!empty($event_date_meta)) {
        $formatters = get_italian_date_formatters();
        $event_date = strtotime($event_date_meta);
        $event_date_day_name = $formatters['day_name']->format($event_date);
        $event_date_day = $formatters['day_number']->format($event_date);
        $event_date_month = $formatters['month']->format($event_date);
        $event_date_year = $formatters['year']->format($event_date);
        
        $output .= '
            <p class="m-0" style="line-height: 1!important;">
                <span class="square flex align-items-center justify-content-center '.$first_box_color_class.' huge black py-1 px-2 m-0 box-shadow" style="min-width: 140px;">'.$event_date_day.'</span>
            </p>
            <div class="flex oh flex-direction-column">
                <div class="flex">
                    <p class="m-0 up-case L"><span class="'.$second_box_color_class.' py-1 px-15 m-0">'.ucfirst($event_date_day_name).'</span></p>
                </div>
                <div class="flex">
                    <p class="m-0 bold XL box-shadow"><span class="'.$first_box_color_class.' p-15 m-0">'.ucfirst($event_date_month).'</span></p><p class="m-0 XL light"><span class="'.$second_box_color_class.' m-0 p-1">'.$event_date_year.'</span></p>
                </div>
                <div class="flex">';
    }
    
    if (!empty($event_time_meta)) {
        $event_time = date('H:i', strtotime($event_time_meta));
        $output .= '
                    <div class="time-box">
                        <p class="m-0">
                            <span class="'.$second_box_color_class.' py-05 L m-0 bold"><i class="iconoir-clock S"></i> '.$event_time.'</span>
                        </p>
                    </div>';
    }
    
    $output .= '</div></div></div></div></div></div>';
    
    return $output;
}

/**
 * Format and display location box
 */
function mini_render_location_box($post_id, $cols=1, $wrapper_class = 'my-0', $name_box_color_class = 'second-color-dark-box', $address_box_color_class = 'second-color-dark-box') {
    $location_name = get_post_meta($post_id, 'location_name', true);
    $location_address = get_post_meta($post_id, 'location_address', true);
    
    if (empty($location_name) && empty($location_address)) {
        return '';
    }
    
    switch ($cols) {
        case 1:
            $box_class = 'box-100';
            break;
        case 2:
            $box_class = 'box-50';
            break;
        case 3:
            $box_class = 'box-33';
            break;
        case 4:
            $box_class = 'box-25';
            break;
        default:
            $box_class = 'box-100';
            break;
    }
    $output = '
        <div class="'.$box_class.' '.$wrapper_class.' location-box">';
    
    if (!empty($location_name)) {
        $output .= '<h4 class="m-0 bold XL py-1 px-15 box-shadow '.$name_box_color_class.'">'.$location_name.'</h4>';
    }
    
    if (!empty($location_address)) {
        if (!empty($location_name)) {
            $output .= '<div class="sep"></div>';
        }
        $output .= '<p class="m-0 p-1 '.$address_box_color_class.'"><i class="iconoir-map-pin"></i>&nbsp;&nbsp;'.$location_address.'</p>';
    }
    
    $output .= '</div>';
    
    return $output;
}

/* NEWS Shortcodes */
function get_latest_news_callback( $num = 3, $cols = 3, $opts = [] ) {
    $category_id     = isset( $opts['categoryId'] )     ? absint( $opts['categoryId'] ) : 0;
    $order           = isset( $opts['order'] ) && strtoupper( $opts['order'] ) === 'ASC' ? 'ASC' : 'DESC';
    $highlight_first = isset( $opts['highlightFirst'] ) ? (bool) $opts['highlightFirst'] : false;

    switch ( (int) $cols ) {
        case 1:  $box_class = 'box-100'; break;
        case 2:  $box_class = 'box-50';  break;
        case 3:  $box_class = 'box-33';  break;
        case 4:  $box_class = 'box-25';  break;
        case 5:  $box_class = 'box-20';  break;
        case 6:  $box_class = 'box-16';  break;
        default: $box_class = 'box-33';  break;
    }

    $args = array(
        'posts_per_page'         => absint( $num ),
        'orderby'                => 'post_date',
        'order'                  => $order,
        'post_type'              => 'news',
        'post_status'            => 'publish',
        'no_found_rows'          => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
    );

    if ( $category_id ) {
        $args['tax_query'] = [[
            'taxonomy' => 'news_category',
            'field'    => 'term_id',
            'terms'    => $category_id,
        ]];
    }

    $query = new WP_Query( $args );

    if ( ! $query->have_posts() ) {
        wp_reset_postdata();
        return '';
    }

    ob_start();
    echo '<div class="boxes">';
    $n = 0;
    while ( $query->have_posts() ) {
        $query->the_post();
        $item_class = ( $highlight_first && $n === 0 ) ? 'box-100' : $box_class;
        echo '<div class="' . esc_attr( $item_class ) . ' p-0">';
        echo '<div class="boxes">';
        get_template_part( 'template-parts/content', 'news', [ 'is_shortcode' => true ] );
        echo '</div>';
        echo '</div>';
        $n++;
    }
    echo '</div>';
    wp_reset_postdata();

    return ob_get_clean();
}
add_shortcode( 'latest_news', function( $atts ) {
    $atts = shortcode_atts( [
        'number'          => 3,
        'cols'            => 3,
        'highlight_first' => 0,
    ], $atts, 'latest_news' );
    return get_latest_news_callback( absint( $atts['number'] ), absint( $atts['cols'] ), [
        'highlightFirst' => (bool) $atts['highlight_first'],
    ] );
} );

/* POSTS archive-style Shortcodes */
/**
 * Renders posts using the same template part as the archive page.
 *
 * Usage: [posts] [posts number="5"] [posts type="news"] [posts number="4" type="event"]
 */
function mini_posts_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'number' => 5,
        'type'   => 'post',
    ), $atts, 'posts' );

    $query = new WP_Query( array(
        'post_type'              => sanitize_key( $atts['type'] ),
        'posts_per_page'         => absint( $atts['number'] ),
        'post_status'            => 'publish',
        'no_found_rows'          => true,
        'update_post_term_cache' => false,
    ) );

    if ( ! $query->have_posts() ) {
        wp_reset_postdata();
        return '';
    }

    ob_start();
    echo '<div class="boxes">';
    while ( $query->have_posts() ) {
        $query->the_post();
        get_template_part( 'template-parts/content', get_post_type(), [ 'is_shortcode' => true ] );
        echo '<div class="space-5"></div>';
    }
    echo '</div>';
    wp_reset_postdata();

    return ob_get_clean();
}
add_shortcode( 'posts', 'mini_posts_shortcode' );

/* POST GRID Shortcode */
/**
 * Renders a configurable grid of standard WordPress posts.
 *
 * Usage: [post_grid] [post_grid number="6" cols="3" order="DESC"]
 *        [post_grid number="4" cols="2" category="5" highlight_first="1"]
 */
function mini_post_grid_callback( $num = 6, $cols = 3, $opts = [] ) {
    $category_id     = isset( $opts['categoryId'] )     ? absint( $opts['categoryId'] )     : 0;
    $order           = isset( $opts['order'] ) && strtoupper( $opts['order'] ) === 'ASC' ? 'ASC' : 'DESC';
    $highlight_first = isset( $opts['highlightFirst'] ) ? (bool) $opts['highlightFirst']    : false;

    switch ( (int) $cols ) {
        case 1:  $box_class = 'box-100'; break;
        case 2:  $box_class = 'box-50';  break;
        case 3:  $box_class = 'box-33';  break;
        case 4:  $box_class = 'box-25';  break;
        case 5:  $box_class = 'box-20';  break;
        case 6:  $box_class = 'box-16';  break;
        default: $box_class = 'box-33';  break;
    }

    $args = [
        'posts_per_page'         => absint( $num ),
        'orderby'                => 'post_date',
        'order'                  => $order,
        'post_type'              => 'post',
        'post_status'            => 'publish',
        'no_found_rows'          => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
    ];

    if ( $category_id ) {
        $args['cat'] = $category_id;
    }

    $query = new WP_Query( $args );

    if ( ! $query->have_posts() ) {
        wp_reset_postdata();
        return '';
    }

    ob_start();
    echo '<div class="boxes">';
    $n = 0;
    while ( $query->have_posts() ) {
        $query->the_post();
        $item_class = ( $highlight_first && $n === 0 ) ? 'box-100' : $box_class;
        echo '<div class="' . esc_attr( $item_class ) . ' p-0">';
        get_template_part( 'template-parts/content', 'post', [ 'is_shortcode' => true ] );
        echo '</div>';
        $n++;
    }
    echo '</div>';
    wp_reset_postdata();

    return ob_get_clean();
}
add_shortcode( 'post_grid', function( $atts ) {
    $atts = shortcode_atts( [
        'number'          => 6,
        'cols'            => 3,
        'order'           => 'DESC',
        'category'        => 0,
        'highlight_first' => 0,
    ], $atts, 'post_grid' );
    return mini_post_grid_callback( absint( $atts['number'] ), absint( $atts['cols'] ), [
        'categoryId'     => absint( $atts['category'] ),
        'order'          => sanitize_key( $atts['order'] ),
        'highlightFirst' => (bool) $atts['highlight_first'],
    ] );
} );

/* SLIDESHOW Shortcodes */
function get_slides_callback( $atts = [] ) {
    // Support both direct calls (legacy: first arg is a number) and shortcode attributes
    if ( is_array( $atts ) ) {
        $atts = shortcode_atts( [
            'slideshow' => '',
            'number'    => -1,
        ], $atts, 'slider' );
        $slideshow_ref = sanitize_text_field( $atts['slideshow'] );
        $number        = intval( $atts['number'] );
    } else {
        // Legacy direct call: get_slides_callback(3)
        $number        = absint( $atts ) ?: -1;
        $slideshow_ref = '';
    }

    // Resolve the slideshow post and delegate to template part
    if ( ! empty( $slideshow_ref ) ) {
        if ( is_numeric( $slideshow_ref ) ) {
            $slideshow_post = get_post( absint( $slideshow_ref ) );
        } else {
            $slideshow_post = get_page_by_path( $slideshow_ref, OBJECT, 'slideshow' );
        }
        if ( ! $slideshow_post ) {
            return '';
        }

        $prev_post           = $GLOBALS['post'] ?? null;
        $GLOBALS['post']     = $slideshow_post;
        setup_postdata( $slideshow_post );

        ob_start();
        get_template_part( 'template-parts/content', 'slideshow', [
            'number' => $number > 0 ? $number : -1,
        ] );
        $output = ob_get_clean();

        if ( $prev_post ) {
            $GLOBALS['post'] = $prev_post;
            setup_postdata( $prev_post );
        } else {
            wp_reset_postdata();
        }

        return $output;
    }

    // Legacy fallback: no slideshow specified, query all slides
    $args = [
        'posts_per_page' => $number > 0 ? $number : -1,
        'orderby'        => 'menu_order',
        'order'          => 'ASC',
        'post_type'      => 'slide',
        'post_status'    => 'publish',
        'no_found_rows'  => true,
    ];
    $query = new WP_Query( $args );
    if ( ! $query->have_posts() ) {
        return '';
    }

    ob_start();
    echo '<div class="container fw"><div class="slider-wrapper"><ul class="slider fh">';
    while ( $query->have_posts() ) {
        $query->the_post();
        get_template_part( 'template-parts/content', 'slide' );
    }
    echo '</ul></div></div>';
    wp_reset_postdata();

    return ob_get_clean();
}
add_shortcode('slider', 'get_slides_callback');
add_shortcode('slideshow', 'get_slides_callback');

/* EVENT Shortcodes */
function get_next_event_callback($num = 1, $cols = 3, $opts = []) {
    $upcoming_only = isset($opts['upcomingOnly']) ? (bool) $opts['upcomingOnly'] : true;
    $category_id   = isset($opts['categoryId'])   ? absint($opts['categoryId'])  : 0;
    $order         = isset($opts['order']) && strtoupper($opts['order']) === 'DESC' ? 'DESC' : 'ASC';
    $show_location = isset($opts['showLocation'])  ? (bool) $opts['showLocation'] : true;

    $args = array(
        'posts_per_page' => absint($num),
        'meta_key'       => 'event_date',
        'orderby'        => 'meta_value',
        'order'          => $order,
        'post_type'      => 'event',
        'post_status'    => 'publish',
        'no_found_rows'  => true,
    );

    if ($upcoming_only) {
        $args['meta_query'] = [[
            'key'     => 'event_date',
            'value'   => date('Y-m-d'),
            'compare' => '>=',
            'type'    => 'DATE',
        ]];
    }

    if ($category_id) {
        $args['tax_query'] = [[
            'taxonomy' => 'event_category',
            'field'    => 'term_id',
            'terms'    => $category_id,
        ]];
    }

    switch ($cols) {
        case 1:  $box_class = 'box-100'; break;
        case 2:  $box_class = 'box-50';  break;
        case 3:  $box_class = 'box-33';  break;
        case 4:  $box_class = 'box-25';  break;
        case 5:  $box_class = 'box-20';  break;
        case 6:  $box_class = 'box-16';  break;
        default: $box_class = 'box-33';  break;
    }

    $query = new WP_Query($args);

    if ( ! $query->have_posts() ) {
        wp_reset_postdata();
        return '';
    }

    ob_start();
    echo '<div class="boxes">';
    while ( $query->have_posts() ) {
        $query->the_post();
        echo '<div class="' . esc_attr( $box_class ) . ' p-0">';
        echo '<div class="boxes">';
        get_template_part( 'template-parts/content', 'event', [
            'is_shortcode'  => true,
            'show_location' => $show_location,
        ] );
        echo '</div>';
        echo '</div>';
    }
    echo '</div>';
    wp_reset_postdata();

    return ob_get_clean();
}
function mini_events_shortcode( $atts, $defaults = [] ) {
    $atts = shortcode_atts( array_merge( [
        'number'        => 3,
        'cols'          => 3,
        'order'         => 'ASC',
        'category'      => 0,
        'upcoming_only' => 1,
        'show_location' => 1,
    ], $defaults ), $atts, 'next_events' );
    return get_next_event_callback( absint( $atts['number'] ), absint( $atts['cols'] ), [
        'upcomingOnly' => (bool) $atts['upcoming_only'],
        'categoryId'   => absint( $atts['category'] ),
        'order'        => sanitize_key( $atts['order'] ),
        'showLocation' => (bool) $atts['show_location'],
    ] );
}
add_shortcode( 'next_event',    function( $atts ) { return mini_events_shortcode( $atts, [ 'number' => 1, 'cols' => 1 ] ); } );
add_shortcode( 'next_events',   function( $atts ) { return mini_events_shortcode( $atts, [ 'number' => 3, 'cols' => 3 ] ); } );
add_shortcode( 'next_3_events', function( $atts ) { return mini_events_shortcode( $atts, [ 'number' => 3, 'cols' => 3 ] ); } );
add_shortcode( 'next_4_events', function( $atts ) { return mini_events_shortcode( $atts, [ 'number' => 4, 'cols' => 2 ] ); } );

/* COURSE Shortcodes */

/**
 * Render a grid of course cards.
 *
 * @param int   $num  Number of courses to show.
 * @param int   $cols Number of columns (1–3).
 * @param array $opts {
 *   showLocation bool  Show location box.      Default true.
 *   showLessons  bool  List lessons in card.   Default false.
 *   categoryId   int   Filter by taxonomy term. Default 0 (all).
 *   order        string ASC|DESC by publish date. Default ASC.
 * }
 */
function get_courses_callback( $num = 3, $cols = 3, $opts = [] ) {
    $show_location = isset( $opts['showLocation'] ) ? (bool) $opts['showLocation'] : true;
    $show_lessons  = isset( $opts['showLessons'] )  ? (bool) $opts['showLessons']  : false;
    $category_id   = isset( $opts['categoryId'] )   ? absint( $opts['categoryId'] ) : 0;
    $order         = isset( $opts['order'] ) && strtoupper( $opts['order'] ) === 'DESC' ? 'DESC' : 'ASC';

    switch ( $cols ) {
        case 1:  $box_class = 'box-100'; break;
        case 2:  $box_class = 'box-50';  break;
        case 3:  $box_class = 'box-33';  break;
        default: $box_class = 'box-33';  break;
    }

    $args = [
        'posts_per_page' => absint( $num ),
        'orderby'        => 'date',
        'order'          => $order,
        'post_type'      => 'course',
        'post_status'    => 'publish',
        'no_found_rows'  => true,
    ];

    if ( $category_id ) {
        $args['tax_query'] = [[
            'taxonomy' => 'course_category',
            'field'    => 'term_id',
            'terms'    => $category_id,
        ]];
    }

    $query = new WP_Query( $args );
    if ( ! $query->have_posts() ) {
        return '';
    }

    ob_start();
    echo '<div class="boxes">';
    while ( $query->have_posts() ) {
        $query->the_post();
        echo '<div class="' . esc_attr( $box_class ) . ' my-0 p-0">';
        get_template_part( 'template-parts/content', 'course', [
            'is_shortcode'  => true,
            'show_location' => $show_location,
            'show_lessons'  => $show_lessons,
        ] );
        echo '</div>';
    }
    wp_reset_postdata();
    echo '</div>';
    return ob_get_clean();
}
function mini_courses_shortcode( $atts, $defaults = [] ) {
    $atts = shortcode_atts( array_merge( [
        'number'        => 3,
        'cols'          => 3,
        'order'         => 'ASC',
        'category'      => 0,
        'show_location' => 1,
        'show_lessons'  => 0,
    ], $defaults ), $atts, 'courses' );
    return get_courses_callback( absint( $atts['number'] ), absint( $atts['cols'] ), [
        'categoryId'   => absint( $atts['category'] ),
        'order'        => sanitize_key( $atts['order'] ),
        'showLocation' => (bool) $atts['show_location'],
        'showLessons'  => (bool) $atts['show_lessons'],
    ] );
}
add_shortcode( 'courses',   function( $atts ) { return mini_courses_shortcode( $atts, [ 'number' => 3, 'cols' => 3 ] ); } );
add_shortcode( 'courses_2', function( $atts ) { return mini_courses_shortcode( $atts, [ 'number' => 2, 'cols' => 2 ] ); } );
add_shortcode( 'courses_4', function( $atts ) { return mini_courses_shortcode( $atts, [ 'number' => 4, 'cols' => 2 ] ); } );

/* MATCH Shortcodes */
function get_next_match_callback($num = 1, $cols = 3, $opts = []) {
    $order       = isset($opts['order']) && strtoupper($opts['order']) === 'ASC' ? 'ASC' : 'DESC';
    $category_id = isset($opts['categoryId']) ? absint($opts['categoryId']) : 0;

    $args = array(
        'posts_per_page' => absint($num),
        'meta_key'       => 'event_date',
        'orderby'        => 'meta_value',
        'order'          => $order,
        'post_type'      => 'match',
        'post_status'    => 'publish',
        'no_found_rows'  => true,
    );

    if ($category_id) {
        $args['tax_query'] = [[
            'taxonomy' => 'match_category',
            'field'    => 'term_id',
            'terms'    => $category_id,
        ]];
    }

    $query = new WP_Query($args);

    // Accept string CSS values ('33', '50', etc.) or legacy integers
    if (is_string($cols) && ctype_digit($cols)) {
        $box_class = 'box-' . $cols;
    } else {
        switch ((int) $cols) {
            case 1:  $box_class = 'box-100'; break;
            case 2:  $box_class = 'box-50';  break;
            case 3:  $box_class = 'box-33';  break;
            case 4:  $box_class = 'box-25';  break;
            case 5:  $box_class = 'box-20';  break;
            case 6:  $box_class = 'box-16';  break;
            default: $box_class = 'box-33';  break;
        }
    }
    if ( ! $query->have_posts() ) {
        wp_reset_postdata();
        return '';
    }

    ob_start();
    echo '<div class="boxes">';
    while ( $query->have_posts() ) {
        $query->the_post();
        echo '<div class="' . esc_attr( $box_class ) . ' my-0 p-0">';
        echo '<div class="boxes">';
        get_template_part( 'template-parts/content', 'match', [ 'is_shortcode' => true ] );
        echo '</div>';
        echo '</div>';
    }
    echo '</div>';
    wp_reset_postdata();

    return ob_get_clean();
}
function mini_matches_shortcode( $atts, $defaults = [] ) {
    $atts = shortcode_atts( array_merge( [
        'number'   => 3,
        'cols'     => 3,
        'order'    => 'DESC',
        'category' => 0,
    ], $defaults ), $atts, 'next_matches' );
    return get_next_match_callback( absint( $atts['number'] ), absint( $atts['cols'] ), [
        'categoryId' => absint( $atts['category'] ),
        'order'      => sanitize_key( $atts['order'] ),
    ] );
}
add_shortcode( 'next_match',    function( $atts ) { return mini_matches_shortcode( $atts, [ 'number' => 1, 'cols' => 1 ] ); } );
add_shortcode( 'next_matches',  function( $atts ) { return mini_matches_shortcode( $atts, [ 'number' => 3, 'cols' => 3 ] ); } );
add_shortcode( 'next_3_matches',function( $atts ) { return mini_matches_shortcode( $atts, [ 'number' => 3, 'cols' => 3 ] ); } );
add_shortcode( 'next_4_matches',function( $atts ) { return mini_matches_shortcode( $atts, [ 'number' => 4, 'cols' => 2 ] ); } );

