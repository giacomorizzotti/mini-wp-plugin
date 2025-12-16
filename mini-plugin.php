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
function mini_plugin_checkbox_option(
    string $option_group, 
    string $option, 
    string $status = '',
) {
    $options = get_option( $option_group );
    if (is_array($options) && array_key_exists($option, $options)) {
        if ($options[$option] == true) {
            $status = 'checked';
        }
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
        $variable = false;
        if ( 
            is_array($options) && array_key_exists($option, $options ) && $options[$option] != null 
        ) {
            $variable = $options[$option];
        }
        return $variable;
    }
}
/* START - Useful functions */

/* START - content settings */
function mini_content_settings_init() {
    register_setting( 'mini_content', 'mini_content_settings');
    add_settings_section(
        'mini_content_section',
        __( 'Mini content settings', 'mini' ),
        'mini_content_section_callback',
        'mini-content'
    );
    add_settings_field(
        'mini_content_field',
        __( 'Content settings', 'mini' ),
        'mini_content_fields_callback',
        'mini-content',
        'mini_content_section',
        array(
            'label_for'         => 'mini',
            'class'             => 'mini_row',
            'mini_custom_data'  => 'custom',
        )
    );
}
add_action( 'admin_init', 'mini_content_settings_init' );
function mini_content_fields_callback( $args ) {
    ?>
    <?= mini_plugin_checkbox_option('mini_content_settings','mini_slide'); ?>
    <p class="description">
        <?php esc_html_e( 'Slide content type', 'mini' ); ?>
    </p>
    <br/><br/>
    <?= mini_plugin_checkbox_option('mini_content_settings','mini_news'); ?>
    <p class="description">
        <?php esc_html_e( 'News content type', 'mini' ); ?>
    </p>
    <br/><br/>
    <?= mini_plugin_checkbox_option('mini_content_settings','mini_event'); ?>
    <p class="description">
        <?php esc_html_e( 'Event content type', 'mini' ); ?>
    </p>
    <br/><br/>
    <?= mini_plugin_checkbox_option('mini_content_settings','mini_match'); ?>
    <p class="description">
        <?php esc_html_e( 'Match content type', 'mini' ); ?>
    </p>
    <?php
}
function mini_content_section_callback( $args ) {
    ?>
    <p id="<?php echo esc_attr( $args['id'] ); ?>"><?php esc_html_e( 'This is the Content type section', 'mini' ); ?></p>
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
            'https://cdn.jsdelivr.net/gh/giacomorizzotti/mini/img/brand/mini_emblem_space_around.svg'
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

/* START - mini settings */
function mini_plugin_main_page_html() {
    // check user capabilities
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <p class=""><span class="bold">mini</span> is a frontend framework.</p>
    </div>
    <?php
}
/* START - mini settings*/

/* START - Custom post type - NEWS */
$options = get_option( 'mini_content_settings' );
if (is_array($options) && array_key_exists('mini_news', $options)) {
    if ($options['mini_news'] == true) {
        add_action('init', 'news_custom_post_type');
    }
}
function news_custom_post_type() {
	register_post_type('news',
		array(
			'labels'      => array(
				'name'          => __('News', 'mini'),
				'singular_name' => __('News', 'mini'),
                'add_new' => __( 'Add News' ),
                'add_new_item' => __( 'Add New News' ),
                'edit' => __( 'Edit' ),
                'edit_item' => __( 'Edit News' ),
                'new_item' => __( 'New News' ),
                'view' => __( 'View News' ),
                'view_item' => __( 'View News' ),
                'search_items' => __( 'Search News' ),
                'not_found' => __( 'No News found' ),
				'archives' => __('News', 'mini'),
			),
            'public'      => true,
            'has_archive' => true,
            'menu_icon' => 'dashicons-text-page',
            'rewrite' => array('slug' => 'news'),
            'show_in_rest' => true,
            'supports' => array( 'title', 'editor', 'thumbnail', 'excerpt', 'panels' )

		)
	);
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
$options = get_option( 'mini_content_settings' );
if (is_array($options) && array_key_exists('mini_slide', $options)) {
    if ($options['mini_slide'] == true) {
        add_action('init', 'slide_custom_post_type');
    }
}
function slide_custom_post_type() {
	register_post_type('slide',
		array(
			'labels'      => array(
				'name'          => __('Slides', 'mini'),
				'singular_name' => __('Slide', 'mini'),
                'add_new' => __( 'Add Slide' ),
                'add_new_item' => __( 'Add New Slide' ),
                'edit' => __( 'Edit' ),
                'edit_item' => __( 'Edit Slide' ),
                'new_item' => __( 'New Slide' ),
                'view' => __( 'View Slide' ),
                'view_item' => __( 'View Slide' ),
                'search_items' => __( 'Search Slide' ),
                'not_found' => __( 'No Slides found' ),
				'archives' => __('Slide', 'mini'),
			),
            'public'      => true,
            'has_archive' => false,
            'menu_icon' => 'dashicons-slides',
            'rewrite' => array('slug' => 'slide'),
            'show_in_rest' => true,
            'supports' => array( 'title', 'editor', 'thumbnail', 'excerpt', 'panels' )

		)
	);
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
$options = get_option( 'mini_content_settings' );
if (is_array($options) && array_key_exists('mini_event', $options)) {
    if ($options['mini_event'] == true) {
        add_action('init', 'event_custom_post_type');
    }
}
function event_custom_post_type() {
	register_post_type('event',
		array(
			'labels'      => array(
				'name'          => __('Events', 'mini'),
				'singular_name' => __('Event', 'mini'),
                'add_new' => __( 'Add Event' ),
                'add_new_item' => __( 'Add New Event' ),
                'edit' => __( 'Edit' ),
                'edit_item' => __( 'Edit Event' ),
                'new_item' => __( 'New Event' ),
                'view' => __( 'View Event' ),
                'view_item' => __( 'View Event' ),
                'search_items' => __( 'Search Event' ),
                'not_found' => __( 'No Events found' ),
				'archives' => __('Events', 'mini'),
			),
            'public'      => true,
            'has_archive' => true,
            'menu_icon' => 'dashicons-calendar',
            'rewrite' => array('slug' => 'event'),
            'show_in_rest' => true,
            'supports' => array( 'title', 'editor', 'thumbnail', 'excerpt', 'panels' )

		)
	);
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
                        $it_date_month = new IntlDateFormatter(
                            'it_IT',
                            IntlDateFormatter::FULL,
                            IntlDateFormatter::FULL,
                            'Europe/Rome',
                            IntlDateFormatter::GREGORIAN,
                            'MMMM'
                        );
                        $it_date_day_name = new IntlDateFormatter(
                            'it_IT',
                            IntlDateFormatter::FULL,
                            IntlDateFormatter::FULL,
                            'Europe/Rome',
                            IntlDateFormatter::GREGORIAN,
                            'EEEE'
                        );
                        $it_date_day_number = new IntlDateFormatter(
                            'it_IT',
                            IntlDateFormatter::FULL,
                            IntlDateFormatter::FULL,
                            'Europe/Rome',
                            IntlDateFormatter::GREGORIAN,
                            'e'
                        );
                        $it_date_year = new IntlDateFormatter(
                            'it_IT',
                            IntlDateFormatter::FULL,
                            IntlDateFormatter::FULL,
                            'Europe/Rome',
                            IntlDateFormatter::GREGORIAN,
                            'yyyy'
                        );
                        $event_date = strtotime(get_post_meta(get_the_ID(), 'event_date')[0]);
                        $event_date_day_name = $it_date_day_name->format($event_date);
                        $event_date_day = $it_date_day_number->format($event_date);
                        $event_date_month = $it_date_month->format($event_date);
                        $event_date_year = $it_date_year->format($event_date);
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
function get_next_event_inv_callback() {
    return get_next_event_callback(1);
}
function get_next_3_events_callback() {
    return get_next_event_callback(3);
}
function get_next_4_events_callback() {
    return get_next_event_callback(4);
}
add_shortcode('next_event', 'get_next_event_callback');
add_shortcode('next_events', 'get_next_3_events_callback');
add_shortcode('next_3_events', 'get_next_3_events_callback');
add_shortcode('next_4_events', 'get_next_4_events_callback');
/* END - Custom post type - EVENT */

/* START - Custom post type - MATCH */
$options = get_option( 'mini_content_settings' );
if (is_array($options) && array_key_exists('mini_match', $options)) {
    if ($options['mini_match'] == true) {
        add_action('init', 'match_custom_post_type');
    }
}
function match_custom_post_type() {
	register_post_type('match',
		array(
			'labels'      => array(
				'name'          => __('Match', 'mini'),
				'singular_name' => __('Match', 'mini'),
                'add_new' => __( 'Add Match' ),
                'add_new_item' => __( 'Add New Match' ),
                'edit' => __( 'Edit' ),
                'edit_item' => __( 'Edit Match' ),
                'new_item' => __( 'New Match' ),
                'view' => __( 'View Match' ),
                'view_item' => __( 'View Match' ),
                'search_items' => __( 'Search Match' ),
                'not_found' => __( 'No Matches found' ),
				'archives' => __('Matches', 'mini'),
			),
            'public'      => true,
            'has_archive' => true,
            'menu_icon' => 'dashicons-superhero',
            'rewrite' => array('slug' => 'match'),
            'show_in_rest' => true,
            'supports' => array( 'title', 'editor', 'thumbnail', 'excerpt', 'panels' )

		)
	);
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
                        $it_date_month = new IntlDateFormatter(
                            'it_IT',
                            IntlDateFormatter::FULL,
                            IntlDateFormatter::FULL,
                            'Europe/Rome',
                            IntlDateFormatter::GREGORIAN,
                            'MMMM'
                        );
                        $it_date_day_name = new IntlDateFormatter(
                            'it_IT',
                            IntlDateFormatter::FULL,
                            IntlDateFormatter::FULL,
                            'Europe/Rome',
                            IntlDateFormatter::GREGORIAN,
                            'EEEE'
                        );
                        $it_date_day_number = new IntlDateFormatter(
                            'it_IT',
                            IntlDateFormatter::FULL,
                            IntlDateFormatter::FULL,
                            'Europe/Rome',
                            IntlDateFormatter::GREGORIAN,
                            'e'
                        );
                        $it_date_year = new IntlDateFormatter(
                            'it_IT',
                            IntlDateFormatter::FULL,
                            IntlDateFormatter::FULL,
                            'Europe/Rome',
                            IntlDateFormatter::GREGORIAN,
                            'yyyy'
                        );
                        $match_date = strtotime(get_post_meta(get_the_ID(), 'event_date')[0]);
                        $match_date_day_name = $it_date_day_name->format($match_date);
                        $match_date_day = $it_date_day_number->format($match_date);
                        $match_date_month = $it_date_month->format($match_date);
                        $match_date_year = $it_date_year->format($match_date);
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
function get_next_match_inv_callback() {
    return get_next_match_callback(1,true);
}
function get_next_3_matches_callback() {
    return get_next_match_callback(3,false, 33);
}
function get_next_4_matches_callback() {
    return get_next_match_callback(4,false, 25);
}
add_shortcode('next_match', 'get_next_match_callback');
add_shortcode('next_match_inv', 'get_next_match_inv_callback');
add_shortcode('next_matches', 'get_next_3_matches_callback');
add_shortcode('next_3_matches', 'get_next_3_matches_callback');
add_shortcode('next_4_matches', 'get_next_4_matches_callback');
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
    $date = get_post_meta( $post->ID, 'event_date', true);
    $date_value = null;
    if ($date != null)
        $date_value = $date;
    $end_date = get_post_meta( $post->ID, 'event_end_date', true);
    $end_date_value = null;
    if ($end_date != null)
        $end_date_value = $end_date;
    $time = get_post_meta( $post->ID, 'event_time', true);
    $time_value = null;
    if ($time != null)
        $time_value = $time;
    $end_time = get_post_meta( $post->ID, 'event_end_time', true);
    $end_time_value = null;
    if ($end_time != null)
        $end_time_value = $end_time;
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
    
    $date = null;
    if ( isset($_POST['event_date']) ) {
        $date = $_POST['event_date'];
    }
    $end_date = null;
    if ( isset($_POST['event_end_date']) ) {
        $end_date = $_POST['event_end_date'];
    } 
    $time = null;
    if ( isset($_POST['event_time']) ) {
        $time = $_POST['event_time'];
    }
    $end_time = null;
    if ( isset($_POST['event_end_time']) ) {
        $end_time = $_POST['event_end_time'];
    } 
    update_post_meta( $post_id, 'event_date', $date );
    update_post_meta( $post_id, 'event_end_date', $end_date );
    update_post_meta( $post_id, 'event_time', $time );
    update_post_meta( $post_id, 'event_end_time', $end_time );
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
    $location_name = get_post_meta( $post->ID, 'location_name', true);
    $location_name_value = null;
    if ($location_name != null)
        $location_name_value = $location_name;
    $location_address = get_post_meta( $post->ID, 'location_address', true);
    $location_address_value = null;
    if ($location_address != null)
        $location_address_value = $location_address;
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
    
    $location_name = null;
    if ( isset($_POST['location_name']) ) {
        $location_name = $_POST['location_name'];
    }
    $location_address = null;
    if ( isset($_POST['location_address']) ) {
        $location_address = $_POST['location_address'];
    }
    update_post_meta( $post_id, 'location_name', $location_name );
    update_post_meta( $post_id, 'location_address', $location_address );
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
    $team_1 = get_post_meta( $post->ID, 'team_1', true);
    $team_1_value = null;
    if ($team_1 != null)
        $team_1_value = $team_1;
    $team_1_logo = get_post_meta( $post->ID, 'team_1_logo', true);
    $team_1_logo_value = null;
    if ($team_1_logo != null)
        $team_1_logo_value = $team_1_logo;
    $team_1_score = get_post_meta( $post->ID, 'team_1_score', true);
    $team_1_score_value = null;
    if ($team_1_score != null)
        $team_1_score_value = $team_1_score;
    $team_2 = get_post_meta( $post->ID, 'team_2', true);
    $team_2_value = null;
    if ($team_2 != null)
        $team_2_value = $team_2;
    $team_2_logo = get_post_meta( $post->ID, 'team_2_logo', true);
    $team_2_logo_value = null;
    if ($team_2_logo != null)
        $team_2_logo_value = $team_2_logo;
    $team_2_score = get_post_meta( $post->ID, 'team_2_score', true);
    $team_2_score_value = null;
    if ($team_2_score != null)
        $team_2_score_value = $team_2_score;
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

function teams_save_postdata( $post_id ) {
 
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) { return; }

    if( ! current_user_can( 'edit_post', $post_id ) ) { return; }
    
    $team_1 = null;
    if ( isset($_POST['team_1']) ) {
        $team_1 = $_POST['team_1'];
    }
    $team_1_logo = null;
    if ( isset($_POST['team_1_logo']) ) {
        $team_1_logo = $_POST['team_1_logo'];
    }
    $team_1_score = null;
    if ( isset($_POST['team_1_score']) ) {
        $team_1_score = $_POST['team_1_score'];
    }
    $team_2 = null;
    if ( isset($_POST['team_2']) ) {
        $team_2 = $_POST['team_2'];
    } 
    $team_2_logo = null;
    if ( isset($_POST['team_2_logo']) ) {
        $team_2_logo = $_POST['team_2_logo'];
    }
    $team_2_score = null;
    if ( isset($_POST['team_2_score']) ) {
        $team_2_score = $_POST['team_2_score'];
    }
    update_post_meta( $post_id, 'team_1', $team_1 );
    update_post_meta( $post_id, 'team_1_logo', $team_1_logo );
    update_post_meta( $post_id, 'team_1_score', $team_1_score );
    update_post_meta( $post_id, 'team_2', $team_2 );
    update_post_meta( $post_id, 'team_2_logo', $team_2_logo );
    update_post_meta( $post_id, 'team_2_score', $team_2_score );
}

/* START - DISABLE comments */
function mini_comment_settings_init() {
    register_setting( 'mini_comment', 'mini_comment_settings');
    add_settings_section(
        'mini_comment_section',
        __( 'Mini comment settings', 'mini' ),
        'mini_comment_section_callback',
        'mini-comment'
    );
    add_settings_field(
        'mini_comment_field',
        __( 'Comment settings', 'mini' ),
        'mini_comment_fields_callback',
        'mini-comment',
        'mini_comment_section',
        array(
            'label_for'         => 'mini',
            'class'             => 'mini_row',
            'mini_custom_data'  => 'custom',
        )
    );
}
add_action( 'admin_init', 'mini_comment_settings_init' );
function mini_comment_fields_callback( $args ) {
    ?>
    <?= mini_plugin_checkbox_option('mini_comment_settings','mini_disable_comment'); ?>
    <p class="description">
        <?php esc_html_e( 'Disable comments', 'mini' ); ?>
    </p>
    <?php
}
function mini_comment_section_callback( $args ) {
    ?>
    <p id="<?php echo esc_attr( $args['id'] ); ?>"><?php esc_html_e( 'This is the Comment section', 'mini' ); ?></p>
    <?php
}
function mini_comment_page_html() {
    // This function is no longer used as Comments is now a tab
}

$options = get_option( 'mini_comment_settings' );
if (is_array($options) && array_key_exists('mini_disable_comment', $options)) {
    if ($options['mini_disable_comment'] == true) {
        add_action('admin_init', 'disable_comments_post_types_support');
        add_filter('comments_open', 'disable_comments_status', 20, 2);
        add_filter('pings_open', 'disable_comments_status', 20, 2);
        add_action('admin_menu', 'disable_comments_admin_menu');
        add_action( 'wp_before_admin_bar_render', 'disable_comments_admin_bar' );
        add_action('admin_init', 'disable_comments_admin_menu_redirect');
        add_action('admin_init', 'disable_comments_dashboard');
    }
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
        __( 'Mini blogging settings', 'mini' ),
        'mini_blogging_section_callback',
        'mini-blogging'
    );
    add_settings_field(
        'mini_blogging_field',
        __( 'Blogging settings', 'mini' ),
        'mini_blogging_fields_callback',
        'mini-blogging',
        'mini_blogging_section',
        array(
            'label_for'         => 'mini',
            'class'             => 'mini_row',
            'mini_custom_data'  => 'custom',
        )
    );
}
add_action( 'admin_init', 'mini_blogging_settings_init' );
function mini_blogging_fields_callback( $args ) {
    ?>
    <?= mini_plugin_checkbox_option('mini_blogging_settings','mini_disable_blogging'); ?>
    <p class="description">
        <?php esc_html_e( 'Disable blogging', 'mini' ); ?>
    </p>
    <?php
}
function mini_blogging_section_callback( $args ) {
    ?>
    <p id="<?php echo esc_attr( $args['id'] ); ?>"><?php esc_html_e( 'This is the Blogging section', 'mini' ); ?></p>
    <?php
}
function mini_blogging_page_html() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    
    // Get active tab
    $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'blogging';
    
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

$options = get_option( 'mini_blogging_settings' );
if (is_array($options) && array_key_exists('mini_disable_blogging', $options)) {
    if ($options['mini_disable_blogging'] == true) {
        add_action( 'admin_menu', 'remove_post_admin_menus' );
        add_action( 'wp_before_admin_bar_render', 'remove_post_toolbar_menus' );
        add_action( 'wp_dashboard_setup', 'remove_post_dashboard_widgets' );
    }
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