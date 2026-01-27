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

function mini_plugin_checkbox_option(
    string $option_group, 
    string $option, 
    string $status = '',
) {
    if (is_mini_option_enabled($option_group, $option)) {
        $status = 'checked';
    }
    return '
    <input
        type="checkbox"
        id="'.$option.'"
        name="'.$option_group.'['.$option.']"
        '.$status.'
    >
    ';
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

    // Create Main Menu if it doesn't exist
    if (!$main_menu) {
        $main_menu_id = wp_create_nav_menu('Main Menu');
        // Assign to location
        $locations = get_theme_mod('nav_menu_locations');
        $locations['main-menu'] = $main_menu_id;
        set_theme_mod('nav_menu_locations', $locations);
    }

    // Create Footer Menu if it doesn't exist
    if (!$footer_menu) {
        $footer_menu_id = wp_create_nav_menu('Footer Menu');
        // Assign to location
        $locations = get_theme_mod('nav_menu_locations');
        $locations['footer-menu'] = $footer_menu_id;
        set_theme_mod('nav_menu_locations', $locations);
    }

    // Create User Menu if it doesn't exist
    if (!$user_menu) {
        $user_menu_id = wp_create_nav_menu('User Menu');
        // Assign to location
        $locations = get_theme_mod('nav_menu_locations');
        $locations['user-menu'] = $user_menu_id;
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
    ?>
    <p id="<?php echo esc_attr( $args['id'] ); ?>">
        <i>mini</i> allows you to manage many custom content types to extend WordPress features.
    </p>
    <div class="boxes">
        <div class="box-50 p-2 white-bg b-rad-5 box-shadow">
            <h4 class="" for="mini_match"><?php esc_html_e( 'Slides', 'mini' ); ?></h4>
            <?= mini_plugin_checkbox_option('mini_content_settings','mini_slide'); ?>
            <p class="" for="mini_slide">Enable the "Slide" content type to manage slideshows.</p>
            <p class="S grey-text" for="mini_slide">It enables slides management (like posts or pages) and related admin menus.</p>
            <p class="S grey-text" for="mini_slide">This option loads <i>mini</i> <b>slider.js</b> library.</p>
        </div>
        <div class="box-50 p-2 white-bg b-rad-5 box-shadow">
            <h4 class="" for="mini_match"><?php esc_html_e( 'News', 'mini' ); ?></h4>
            <?= mini_plugin_checkbox_option('mini_content_settings','mini_news'); ?>
            <p class="" for="mini_news">Enable the "News" content type to manage news articles.</p>
            <p class="S grey-text" for="mini_news">It enables news management (like posts or pages) and related admin menus.</p>
        </div>
        <div class="box-50 p-2 white-bg b-rad-5 box-shadow">
            <h4 class="" for="mini_match"><?php esc_html_e( 'Events', 'mini' ); ?></h4>
            <?= mini_plugin_checkbox_option('mini_content_settings','mini_event'); ?>
            <p class="" for="mini_event">Enable the "Event" content type to manage events.</p>
            <p class="S grey-text" for="mini_event">It enables events management (like posts or pages) and related admin menus.</p>
        </div>
        <div class="box-50 p-2 white-bg b-rad-5 box-shadow">
            <h4 class="" for="mini_match"><?php esc_html_e( 'Matches', 'mini' ); ?></h4>
            <?= mini_plugin_checkbox_option('mini_content_settings','mini_match'); ?>
            <p class="" for="mini_match">Enable "Match" content type to manage sport events.</p>
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
}
add_action( 'admin_menu', 'mini_plugin_settings_pages' );
/* END - mini menu */

function mini_plugin_admin_styles() {
    echo '<style>
        #adminmenu .wp-menu-image img {
            /* your styles here */
            width: 20px;
            height: 20px;
            padding: 0;
        }
        input[type=checkbox]:checked::before {
            margin :0;
        }
    </style>';
}
add_action('admin_head', 'mini_plugin_admin_styles');

/* START - mini settings */
function mini_plugin_main_page_html() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    ?>
    <div class="boxes py-2">
        <div class="box-100 p-2 white-bg b-rad-5 box-shadow mb-2">
            <div class="space"></div>
            <img src="https://cdn.jsdelivr.net/gh/giacomorizzotti/mini/img/brand/mini_logo_2.svg" alt="mini logo" style="max-width: 280px;" class="mb-2"/>
            <h1 class="mb-0"><i>mini</i> is a frontend framework</h1>
            <p class="mt-0">That allows you to build modern, responsive websites with ease.</p>
            <p class="">
                <a href="https://mini.uwa.agency/" target="_blank" rel="noopener noreferrer" class="btn fourth-color-btn"><?php esc_html_e( 'Visit mini website', 'mini' ); ?></a>
            </p>
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
        'post_per_page' => 3, /* how many post you need to display */
        'offset' => 0,
        'orderby' => 'post_date',
        'order' => 'DESC',
        'post_type' => 'news', /* your post type name */
        'post_status' => 'publish'
    );
    $query = new WP_Query($args);
    if ($query->have_posts()) :
        $news_list = '';
        $news_list .= '
        <div class="boxes">
        ';
        $n = 1;
        while ($query->have_posts()) : $query->the_post();
        if ($n==1) {
            $news_list .= '
            <div class="box-100 my-0 p-0" data-aos="fade-up">
            ';
        } else {
            $news_list .= '
            <div class="box-50 my-0 p-0" data-aos="fade-up" data-aos-delay="'.(150*($n-1)).'">
            ';
        }
        $news_list .= '
                <div class="boxes">
        ';
        if (get_the_post_thumbnail(get_the_ID())!=false) {
            $news_list .= '
                    <div class="box-50">
                    <a href="'.get_the_permalink().'">'.get_the_post_thumbnail(get_the_ID(), 'large', ['class' => 'img']).'</a>
                    </div>
                    <div class="box-50">
            ';
        } else {
            $news_list .= '
                    <div class="box-100">
            ';
        }
        $news_list .= '
                        <h3>
                            <a href="'.get_the_permalink().'">'.get_the_title().'</a>
                        </h3>
                        <p class="">'.get_the_excerpt(get_the_ID()).'</p>
                        <p class="">
                            <a href="'.get_the_permalink().'" class="btn">'.esc_html__( 'Read more', 'mini' ).'</a>
                        </p>
                    </div>
                </div>
        ';
        $news_list .= '
        </div>
        ';
        $n++;
        endwhile;
        return $news_list;
    endif;
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
        'post_per_page' => $number, /* how many post you need to display */
        'offset' => 0,
        'orderby' => 'post_date',
        'order' => 'DESC',
        'post_type' => 'slide', /* your post type name */
        'post_status' => 'publish'
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
        return $slider;
    endif;
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
        'post_per_page' => $num, /* how many post you need to display */
        'offset' => 0,
        'meta_key'	=> 'event_date',
        'orderby'	=> 'meta_value',
        'order' => 'DESC',
        'post_type' => 'event', /* your post type name */
        'post_status' => 'publish'
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
        return $event_list;
    endif;
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
        'post_per_page' => $num, /* how many post you need to display */
        'offset' => 0,
        'meta_key'	=> 'event_date',
        'orderby'	=> 'meta_value',
        'order' => 'DESC',
        'post_type' => 'match', /* your post type name */
        'post_status' => 'publish'
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
        return $match_list;
    endif;
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
    
    echo '
    <div style="display: flex; flex-flow: row wrap; margin-bottom: 1rem;">
        <div style="flex: 1; margin-bottom: 0.5rem;">
            <label for="event_date" style="margin-bottom: 0.5rem; display: block;">' . __("Date", 'mini' ) . ':</label>
            <input type="date" id="event_date" name="event_date" value="'.$date_value.'" style="min-width: 220px; display: block;" />
        </div>
        <div style="flex: 1; margin-bottom: 0.5rem;">
            <label for="event_end_date" style="margin-bottom: 0.5rem; display: block;">' . __("End date (optional)", 'mini' ) . ':</label>
            <input type="date" id="event_end_date" name="event_end_date" value="'.$end_date_value.'" style="min-width: 220px; display: block;" />
        </div>
    </div>
    ';
    echo '
    <div style="display: flex; flex-flow: row wrap; margin-bottom: 1rem;">
        <div style="flex: 1; margin-bottom: 0.5rem;">
            <label for="event_time" style="margin-bottom: 0.5rem; display: block;">' . __("Time", 'mini' ) . ':</label>
            <input type="time" id="event_time" name="event_time" value="'.$time_value.'" style="min-width: 220px; display: block;" />
        </div>
        <div style="flex: 1; margin-bottom: 0.5rem;">
            <label for="event_end_time" style="margin-bottom: 0.5rem; display: block;">' . __("End time (optional)", 'mini' ) . ':</label>
            <input type="time" id="event_end_time" name="event_end_time" value="'.$end_time_value.'" style="min-width: 220px; display: block;" />
        </div>
    </div>
    ';
}
function date_time_save_postdata( $post_id ) {
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) { return; }
    if( ! current_user_can( 'edit_post', $post_id ) ) { return; }
    
    update_post_meta( $post_id, 'event_date', $_POST['event_date'] ?? null );
    update_post_meta( $post_id, 'event_end_date', $_POST['event_end_date'] ?? null );
    update_post_meta( $post_id, 'event_time', $_POST['event_time'] ?? null );
    update_post_meta( $post_id, 'event_end_time', $_POST['event_end_time'] ?? null );
}

/* ADD Location options */
add_action( 'add_meta_boxes', 'add_location_box' );
add_action( 'save_post', 'location_save_postdata' );
function add_location_box() {
    add_meta_box(
        'location',
        esc_html__( 'Location', 'mini' ),
        'Location_box_html',
        ['event', 'match'],
        'side'
    );
}
function Location_box_html( $post, $meta ){
    $location_name_value = get_post_meta( $post->ID, 'location_name', true) ?: '';
    $location_address_value = get_post_meta( $post->ID, 'location_address', true) ?: '';
    
    echo '
    <div style="display: flex; flex-flow: row wrap; margin-bottom: 1rem;">
        <div style="flex: 1;">
            <label for="location_name" style="margin-bottom: 0.5rem; display: block;">' . __("Location", 'mini' ) . ':</label>
            <input type="text" id="location_name" name="location_name" value="'.$location_name_value.'" style="min-width: 220px; width: 50%;" />
        </div>
    </div>
    <div style="display: flex; flex-flow: row wrap; margin-bottom: 1rem;">
        <div style="flex: 1;">
            <label for="location_address" style="margin-bottom: 0.5rem; display: block;">' . __("Location address", 'mini' ) . ':</label>
            <input type="text" id="location_address" name="location_address" value="'.$location_address_value.'" style="min-width: 220px; width: 50%;" />
        </div>
    </div>
    ';
}
function location_save_postdata( $post_id ) {
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) { return; }
    if( ! current_user_can( 'edit_post', $post_id ) ) { return; }
    
    update_post_meta( $post_id, 'location_name', $_POST['location_name'] ?? null );
    update_post_meta( $post_id, 'location_address', $_POST['location_address'] ?? null );
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
    
    echo '
    <div style="display: flex; flex-flow: row wrap;">
        <div style="flex: 1;">
            <h3>' . __("Team one", 'mini' ) . '</h3>
        </div>
    </div>
    <div style="display: flex; flex-flow: row wrap; margin-bottom: 0.5rem;">
        <div style="flex: 1; margin-bottom: 0.5rem;">
            <label for="team_1" style="margin-bottom: 0.5rem; display: block;">' . __("Team one", 'mini' ) . ':</label>
            <input type="text" id="team_1" name="team_1" value="'.$team_1_value.'" style="min-width: 220px;" />
        </div>
        <div style="flex: 1; margin-bottom: 0.5rem;">
            <label for="team_1_logo" style="margin-bottom: 0.5rem; display: block;">' . __("Team one logo", 'mini' ) . ':</label>
            <input id="team_1_logo" type="text" name="team_1_logo" value="'.$team_1_logo_value.'" style="margin-bottom: 0.5rem; display: block; min-width: 220px;"/>
            <input id="team_1_logo_button" class="components-button editor-post-status__toggle is-compact is-tertiary has-text has-icon" type="button" value="' . __("Upload logo", 'mini' ) . '" />
        </div>
        <div style="flex: 1; margin-bottom: 0.5rem;">
            <label for="team_1_score" style="margin-bottom: 0.5rem; display: block;">' . __("Team one score", 'mini' ) . ':</label>
            <input type="number" id="team_1_score" name="team_1_score" value="'.$team_1_score_value.'" style="min-width: 220px;" />
        </div>
    </div>
    ';
    echo '
    <div style="display: flex; flex-flow: row wrap;">
        <div style="flex: 1;">
            <h3>' . __("Team two", 'mini' ) . '</h3>
        </div>
    </div>
    <div style="display: flex; flex-flow: row wrap; margin-bottom: 0.5rem;">
        <div style="flex: 1; margin-bottom: 0.5rem;">
            <label for="team_2" style="margin-bottom: 0.5rem; display: block;">' . __("Team two", 'mini' ) . ':</label>
            <input type="text" id="team_2" name="team_2" value="'.$team_2_value.'" style="min-width: 220px;" />
        </div>
        <div style="flex: 1; margin-bottom: 0.5rem;">
            <label for="team_2_logo" style="margin-bottom: 0.5rem; display: block;">' . __("Team two logo", 'mini' ) . ':</label>
            <input id="team_2_logo" type="text" name="team_2_logo" value="'.$team_2_logo_value.'" style="margin-bottom: 0.5rem; display: block; min-width: 220px;"/>
            <input id="team_2_logo_button" class="components-button editor-post-status__toggle is-compact is-tertiary has-text has-icon" type="button" value="' . __("Upload logo", 'mini' ) . '" />
        </div>
        <div style="flex: 1; margin-bottom: 0.5rem;">
            <label for="team_2_score" style="margin-bottom: 0.5rem; display: block;">' . __("Team two score", 'mini' ) . ':</label>
            <input type="number" id="team_2_score" name="team_2_score" value="'.$team_2_score_value.'" style="min-width: 220px;" />
        </div>
    </div>
    ';
}

function media_upload_scripts() {    
    wp_enqueue_script('media-upload');
    wp_enqueue_script('thickbox');
    wp_register_script('media-upload-script', WP_PLUGIN_URL.'/mini/media-upload/media-upload.js', array('jquery','media-upload','thickbox'));
    wp_enqueue_script('media-upload-script');
}
add_action('admin_enqueue_scripts', 'media_upload_scripts');

function media_upload_styles() {
    wp_enqueue_style('thickbox');
    wp_register_style('media-upload-style', plugins_url( 'mini/media-upload/media-upload.css' ));
    wp_enqueue_style('media-upload-style');
}
add_action('admin_enqueue_scripts', 'media_upload_styles');

function load_mini_css_in_mini_plugin_admin_pages() {
    $options = get_option('mini_main_settings');
    $version = isset($options['mini_css_version']) ? $options['mini_css_version'] : 'latest';
    
    if ($version === 'latest') {
        $css_url = 'https://cdn.jsdelivr.net/gh/giacomorizzotti/mini/css/mini.min.css';
    } else {
        $css_url = 'https://cdn.jsdelivr.net/gh/giacomorizzotti/mini@' . $version . '/css/mini.min.css';
    }
    
    wp_register_style('mini-css', $css_url);
    wp_enqueue_style('mini-css');
}
add_action('admin_enqueue_scripts', 'load_mini_css_in_mini_plugin_admin_pages');

function teams_save_postdata( $post_id ) {
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) { return; }
    if( ! current_user_can( 'edit_post', $post_id ) ) { return; }
    
    update_post_meta( $post_id, 'team_1', $_POST['team_1'] ?? null );
    update_post_meta( $post_id, 'team_1_logo', $_POST['team_1_logo'] ?? null );
    update_post_meta( $post_id, 'team_1_score', $_POST['team_1_score'] ?? null );
    update_post_meta( $post_id, 'team_2', $_POST['team_2'] ?? null );
    update_post_meta( $post_id, 'team_2_logo', $_POST['team_2_logo'] ?? null );
    update_post_meta( $post_id, 'team_2_score', $_POST['team_2_score'] ?? null );
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