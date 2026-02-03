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
                <span class="square flex align-items-center justify-content-center '.$first_box_color_class.' huge black py-1 px-2 m-0" style="min-width: 140px;">'.$event_date_day.'</span>
            </p>
            <div class="flex flex-direction-column">
                <div class="flex">
                    <p class="m-0 up-case L"><span class="'.$second_box_color_class.' py-1 px-15 m-0">'.ucfirst($event_date_day_name).'</span></p>
                </div>
                <div class="flex">
                    <p class="m-0 bold XL"><span class="'.$first_box_color_class.' p-15 m-0">'.ucfirst($event_date_month).'</span></p><p class="m-0 XL light"><span class="'.$second_box_color_class.' m-0 p-1">'.$event_date_year.'</span></p>
                </div>
                <div class="flex">';
    }
    
    if (!empty($event_time_meta)) {
        $event_time = date('H:i', strtotime($event_time_meta));
        $output .= '
                    <div class="time-box">
                        <p class="m-0">
                            <span class="'.$second_box_color_class.' wh-text px-15 XL bold"><i class="iconoir-clock S"></i> '.$event_time.'</span>
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
        $output .= '<h4 class="m-0 bold XL py-1 px-15 '.$name_box_color_class.'">'.$location_name.'</h4>';
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

/* SLIDE Shortcodes */
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

/* EVENT Shortcodes */
function get_next_event_callback($num = 1, $cols=3) {
    $args = array(
        'posts_per_page' => absint($num),
        'meta_key' => 'event_date',
        'orderby' => 'meta_value',
        'order' => 'DESC',
        'post_type' => 'event',
        'post_status' => 'publish',
        'no_found_rows' => true,
    );
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
            $box_class = 'box-33';
            break;
    }
    $query = new WP_Query($args);
    if ($query->have_posts()) :
        $n=1;
        $event_list = '';
        $event_list .= '
        <div class="boxes g-0">
        ';
        while ($query->have_posts()) : $query->the_post();
            $to_come=false;
            if ( get_post_meta(get_the_ID(), 'event_date') != null && get_post_meta(get_the_ID(), 'event_date')[0] >= date("Y-m-d H:i:s") ) { $to_come = True; }
            if ( $n <= $num && $to_come == true ) {
                $event_list .= '
            <div class="'.$box_class.'">';
                if (get_the_post_thumbnail(get_the_ID())!=false):
                $event_list .= '
                <div class="box-100 hh" style="background-image: url(\''.get_the_post_thumbnail_url(get_the_ID()).'\'); background-position: center; background-size: cover; background-repeat: no-repeat; height: 200px;">
                    <div class="boxes align-content-start align-items-start">';
                else:
                $event_list .= '
                <div class="box-100">
                    <div class="boxes align-content-start align-items-start">';
                endif;
                $event_list .= '
                            <div class="box-100 my-0 p-0">
                                <h4 class="XL m-0 p-15 white-bg">
                                    <a href="'.get_the_permalink().'" class="m-0">'.get_the_title().'</a>
                                </h4>
                            </div>
                ';
                if (
                    get_post_meta(get_the_ID(), 'event_date', true) != null ||
                    get_post_meta(get_the_ID(), 'event_time', true) != null) {
                    $event_list .= mini_render_date_time_box(get_the_ID(), 1, 'p-0', 'second-color-box', 'second-color-dark-box');
                    $event_list .= mini_render_location_box(get_the_ID(), 1, 'p-0', 'second-color-box', 'second-color-dark-box');
                }
                if (
                    get_the_excerpt(get_the_ID()) != null ) {
                    $event_list .= '
                            <div class="box-100 my-0 white-bg">
                                <p>'.get_the_excerpt(get_the_ID()).'</p>
                            </div>
                            <div class="box-100 p-0">
                                <p class="m-0">
                                    <a href="'.get_the_permalink().'" class="btn L b-rad-0 m-0">'.__('Event details', 'mini').'</a>
                                </p>
                            </div>
                    ';
                }
                if (get_the_post_thumbnail(get_the_ID())!=false):
                $event_list .= '
                    </div>
                </div>
                ';
                endif;
                $event_list .= '
            </div>
                ';
            }
            if ($to_come == true) { $n++; }
        endwhile;
        wp_reset_postdata();
                $event_list .= '
        </div>
                ';
        return $event_list;
    endif;
    
    return '';
}
add_shortcode('next_event', function() { return get_next_event_callback(1, 1); });
add_shortcode('next_events', function() { return get_next_event_callback(3, 3); });
add_shortcode('next_3_events', function() { return get_next_event_callback(3, 3); });
add_shortcode('next_4_events', function() { return get_next_event_callback(4, 2); });

/* MATCH Shortcodes */
function get_next_match_callback($num = 1, $cols=3) {
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
            $box_class = 'box-33';
            break;
    }
    if ($query->have_posts()) :
        $n=1;
        $match_list = '';
        while ($query->have_posts()) : $query->the_post();
            $to_come=false;
            if ( get_post_meta(get_the_ID(), 'event_date') != null && get_post_meta(get_the_ID(), 'event_date')[0] >= date("Y-m-d") ) { $to_come = True; }
            if ( $n <= $num && $to_come == true ) {
                $match_list .= '
<div class="boxes g-0">
    <div class="'.$box_class.' my-0">
        <div class="boxes align-content-start align-items-start">
                ';
				if (
					get_post_meta(get_the_ID(), 'team_1')[0] != null && 
					get_post_meta(get_the_ID(), 'team_2')[0] != null
				) {
                    $match_list .= '
            <div class="box-zero-50" style="max-width: 380px;">
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
                    $team_1 = get_post_meta(get_the_ID(), 'team_1')[0];
                    $match_list .= '
                    <div class="box-100 second-color-dark-bg">
                        <h2 class="XL wh-text m-0"><a href="'.get_the_permalink().'" class="">'.$team_1.'</a></h2>
                    </div>
                </div>
            </div>
            <div class="box-zero-50" style="max-width: 380px;">
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
                    $team_2 = get_post_meta(get_the_ID(), 'team_2')[0];
                    $match_list .= '
                    <div class="box-100 second-color-dark-bg">
                        <h2 class="XL wh-text m-0"><a href="'.get_the_permalink().'" class="">'.$team_2.'</a></h2>
                    </div>
                    ';
                    $match_list .= '
                </div>
            </div>
                    ';
                }
                if (
                    get_post_meta(get_the_ID(), 'event_date', true) != null ||
                    get_post_meta(get_the_ID(), 'event_time', true) != null) {
                    $match_list .= mini_render_date_time_box(get_the_ID(), 1, 'p-0', 'second-color-box', 'second-color-dark-box');
                    $match_list .= mini_render_location_box(get_the_ID(), 1, 'p-0', 'second-color-box', 'second-color-dark-box');
                }
                $match_list .= '
            <div class="box-100 p-0">
                <p class=" m-0">
                    <a href="'.get_the_permalink().'" class="btn L b-rad-0 m-0">'.__('Match details', 'mini').'</a>
                </p>
            </div>
        </div>
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
add_shortcode('next_match', function() { return get_next_match_callback(1, 1); });
add_shortcode('next_matches', function() { return get_next_match_callback(3, 3); });
add_shortcode('next_3_matches', function() { return get_next_match_callback(3, 3); });
add_shortcode('next_4_matches', function() { return get_next_match_callback(4, 2); });
