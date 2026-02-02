<?php
/**
 * Shortcodes
 *
 * @package mini
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
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
                                <span class="second-color-dark-box px-15 m-0">'.ucfirst($event_date_day_name).'</span>
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
                                <p class="m-0">
                                    <span class="second-color-dark-box wh-text px-15 XL bold"><i class="iconoir-clock"></i> '.$event_time.'</span>
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

/* MATCH Shortcodes */
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
                        <span class="square flex align-items-center justify-content-center second-color-box huge black py-1 px-2 m-0" style="min-width: 140px;">'.$match_date_day.'</span>
                    </p>
                    <div class="flex flex-direction-column">
                        <div class="flex">
                            <p class="m-0 up-case L"><span class="second-color-dark-box px-15 m-0">'.ucfirst($match_date_day_name).'</span></p>
                        </div>
                        <div class="flex">
                            <p class="m-0 bold XL"><span class="second-color-box px-15 m-0">'.ucfirst($match_date_month).'</span></p><p class="m-0 XL light"><span class="second-color-dark-box m-0">'.$match_date_year.'</span></p>
                        </div>
                        <div class="flex">
                        ';
                    }
                    if (get_post_meta(get_the_ID(), 'event_time')[0] != null) {
                        $match_time = date('H:i', strtotime(get_post_meta(get_the_ID(), 'event_time')[0]));
                        $match_list .= '
                            <div class="time-box">
                                <p class="m-0">
                                    <span class="second-color-dark-box wh-text px-15 XL bold"><i class="iconoir-clock"></i> '.$match_time.'</span>
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
