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
    <?= mini_plugin_checkbox_option('mini_content_settings','mini_news'); ?>
    <p class="description">
        <?php esc_html_e( 'News content type', 'mini' ); ?>
    </p>
    <br/><br/>
    <?= mini_plugin_checkbox_option('mini_content_settings','mini_event'); ?>
    <p class="description">
        <?php esc_html_e( 'Event content type', 'mini' ); ?>
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
        'Comments',
        'Comments',
        'manage_options',
        'mini-comment',
        'mini_comment_page_html',
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
				'name'          => __('News', 'textdomain'),
				'singular_name' => __('News', 'textdomain'),
                'add_new' => __( 'Add News' ),
                'add_new_item' => __( 'Add New News' ),
                'edit' => __( 'Edit' ),
                'edit_item' => __( 'Edit News' ),
                'new_item' => __( 'New News' ),
                'view' => __( 'View News' ),
                'view_item' => __( 'View News' ),
                'search_items' => __( 'Search News' ),
                'not_found' => __( 'No News found' )
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
                    <a href="'.get_the_permalink().'">'.get_the_post_thumbnail(get_the_ID()).'</a>
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
            </div>';
            $n++;
        endwhile;
        return $news_list;
    endif;
}
add_shortcode('latest_news', 'get_latest_news_callback');
/* END - Custom post type - NEWS */

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
				'name'          => __('Events', 'textdomain'),
				'singular_name' => __('Event', 'textdomain'),
                'add_new' => __( 'Add Event' ),
                'add_new_item' => __( 'Add New Event' ),
                'edit' => __( 'Edit' ),
                'edit_item' => __( 'Edit Event' ),
                'new_item' => __( 'New Event' ),
                'view' => __( 'View Event' ),
                'view_item' => __( 'View Event' ),
                'search_items' => __( 'Search Event' ),
                'not_found' => __( 'No Events found' )
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

/* ADD Date and time options */
add_action( 'add_meta_boxes', 'add_date_time_box' );
add_action( 'save_post', 'date_time_save_postdata' );
function add_date_time_box() {
    add_meta_box(
        'date-time',
        esc_html__( 'Date and time', 'mini' ),
        'date_time_box_html',
        ['event'],
        'normal'
    );
}
function date_time_box_html( $post, $meta ){
    $event_date = get_post_meta( $post->ID, 'event_date', true);
    $event_date_value = null;
    if ($event_date != null)
        $event_date_value = $event_date;
    $event_end_date = get_post_meta( $post->ID, 'event_end_date', true);
    $event_end_date_value = null;
    if ($event_end_date != null)
        $event_end_date_value = $event_end_date;
    $event_time = get_post_meta( $post->ID, 'event_time', true);
    $event_time_value = null;
    if ($event_time != null)
        $event_time_value = $event_time;
    $event_end_time = get_post_meta( $post->ID, 'event_end_time', true);
    $event_end_time_value = null;
    if ($event_end_time != null)
        $event_end_time_value = $event_end_time;
    echo '
    <div style="display: flex; flex-flow: row wrap; margin-bottom: 1rem;">
        <div style="flex: 1;">
            <label for="event_date" style="margin-bottom: 0.5rem; display: block;">' . __("Date", 'date_time_textdomain' ) . ':</label>
            <input type="date" id="event_date" name="event_date" value="'.$event_date_value.'" style="min-width: 220px;" />
        </div>
        <div style="flex: 1;">
            <label for="event_end_date" style="margin-bottom: 0.5rem; display: block;">' . __("End date (optional)", 'date_time_textdomain' ) . ':</label>
            <input type="date" id="event_end_date" name="event_end_date" value="'.$event_end_date_value.'" style="min-width: 220px;" />
        </div>
    </div>
    ';
    echo '
    <div style="display: flex; flex-flow: row wrap; margin-bottom: 1rem;">
        <div style="flex: 1;">
            <label for="event_time" style="margin-bottom: 0.5rem; display: block;">' . __("Time", 'date_time_textdomain' ) . ':</label>
            <input type="time" id="event_time" name="event_time" value="'.$event_time_value.'" style="min-width: 220px;" />
        </div>
        <div style="flex: 1;">
            <label for="event_end_time" style="margin-bottom: 0.5rem; display: block;">' . __("End time (optional)", 'date_time_textdomain' ) . ':</label>
            <input type="time" id="event_end_time" name="event_end_time" value="'.$event_end_time_value.'" style="min-width: 220px;" />
        </div>
    </div>
    ';
}
function date_time_save_postdata( $post_id ) {
 
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) { return; }

    if( ! current_user_can( 'edit_post', $post_id ) ) { return; }
    
    $event_date = null;
    if ( isset($_POST['event_date']) ) {
        $event_date = $_POST['event_date'];
    }
    $event_end_date = null;
    if ( isset($_POST['event_end_date']) ) {
        $event_end_date = $_POST['event_end_date'];
    } 
    $event_time = null;
    if ( isset($_POST['event_time']) ) {
        $event_time = $_POST['event_time'];
    }
    $event_end_time = null;
    if ( isset($_POST['event_end_time']) ) {
        $event_end_time = $_POST['event_end_time'];
    } 
    update_post_meta( $post_id, 'event_date', $event_date );
    update_post_meta( $post_id, 'event_end_date', $event_end_date );
    update_post_meta( $post_id, 'event_time', $event_time );
    update_post_meta( $post_id, 'event_end_time', $event_end_time );
}

/* ADD Location options */
add_action( 'add_meta_boxes', 'add_location_box' );
add_action( 'save_post', 'location_save_postdata' );
function add_location_box() {
    add_meta_box(
        'location',
        esc_html__( 'Location', 'mini' ),
        'Location_box_html',
        ['event'],
        'normal'
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
            <label for="location_name" style="margin-bottom: 0.5rem; display: block;">' . __("Location", 'date_time_textdomain' ) . ':</label>
            <input type="text" id="location_name" name="location_name" value="'.$location_name_value.'" style="min-width: 220px; width: 50%;" />
        </div>
    </div>
    <div style="display: flex; flex-flow: row wrap; margin-bottom: 1rem;">
        <div style="flex: 1;">
            <label for="location_address" style="margin-bottom: 0.5rem; display: block;">' . __("Location address", 'date_time_textdomain' ) . ':</label>
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

/* shortcodes */
function get_next_event_callback($num = 1, $invert = false, $box = 100) {
    $text_color = 'fb-text';
    $location_name_box_color = 'bk-box';
    $location_address_box_color = 'dark-grey-box';
    if ($invert == true) {
        $text_color = 'wh-text';
        $location_name_box_color = 'wh-box';
        $location_address_box_color = 'light-grey-box';
    }
    $args = array(
        'post_per_page' => $num, /* how many post you need to display */
        'offset' => 0,
        'orderby' => 'event_date',
        'order' => 'DESC',
        'post_type' => 'event', /* your post type name */
        'post_status' => 'publish'
    );
    $query = new WP_Query($args);
    if ($query->have_posts()) :
        $n=1;
        $event_list = '';
        while ($query->have_posts()) : $query->the_post();
            if ($n <= $num) {
                $event_list .= '
                    <div class="box-'.$box.' my-0 p-0" data-aos="fade-up" data-aos-delay="'.(150*($n-1)).'">
                        <div class="boxes">
                ';
                $event_list .= '
                            <div class="box-100 my-0">
                ';
                if ($box < 100 ) {
                    $event_list .= '
                                    <h3 class="m-0 XXL">
                                        <a href="'.get_the_permalink().'" class="'.$text_color.' under-bg inline">'.get_the_title().'</a>
                                    </h3>
                    ';
                } else {
                    $event_list .= '
                                    <h3 class="big m-0">
                                        <a href="'.get_the_permalink().'" class="'.$text_color.' under-bg inline">'.get_the_title().'</a>
                                    </h3>
                    ';
                }
                $event_list .= '
                            </div>
                ';
                if ( 
                    get_post_meta(get_the_ID(), 'location_name') != null ||
                    get_post_meta(get_the_ID(), 'location_address') != null
                ) {
                    $event_list .= '
                                <div class="box-100 my-0">
                    ';
                    if ( get_post_meta(get_the_ID(), 'location_name') != null ) {
                        if ($box < 100 ) {
                            $event_list .= '
                                    <h4 class="m-0 bold '.$location_name_box_color.'">
                                        '.get_post_meta(get_the_ID(), 'location_name')[0].'
                                    </h4>
                            ';
                        } else {
                            $event_list .= '
                                    <h4 class="m-0 bold XL '.$location_name_box_color.'">
                                        '.get_post_meta(get_the_ID(), 'location_name')[0].'
                                    </h4>
                            ';
                        }
                    }
                    if ( get_post_meta(get_the_ID(), 'location_address') != null ) {
                        if ($box < 100 ) {
                            $event_list .= '
                                    <div class="sep"></div>
                                    <p class="m-0 '.$location_address_box_color.'">
                                        '.get_post_meta(get_the_ID(), 'location_address')[0].'
                                    </p>
                            ';
                        } else {
                            $event_list .= '
                                    <div class="sep"></div>
                                    <p class="m-0 L '.$location_address_box_color.'">
                                        '.get_post_meta(get_the_ID(), 'location_address')[0].'
                                    </p>
                            ';
                        }
                    }
                    $event_list .= '
                                </div>
                    ';
                }
                if (get_the_post_thumbnail(get_the_ID())!=false) {
                    if ($box < 100 ) {
                        $event_list .= '
                            <div class="box-100 my-0">
                            <a href="'.get_the_permalink().'">'.get_the_post_thumbnail(get_the_ID()).'</a>
                            </div>
                        ';
                    } else {
                        $event_list .= '
                            <div class="box-33">
                            <a href="'.get_the_permalink().'">'.get_the_post_thumbnail(get_the_ID()).'</a>
                            </div>
                        ';
                    }
                } 
                if ($box < 100 ) {
                    $event_list .= '
                        <div class="box-100 my-0">
                    ';
                } else {
                    $event_list .= '
                        <div class="box-66">
                    ';
                }
                if (
                    get_post_meta(get_the_ID(), 'event_date') != null ||
                    get_post_meta(get_the_ID(), 'event_time') != null) {
                    $event_list .= ' <div class="mb-2 flex flex-flow-column-wrap justify-content-start align-items-start">';
                    if (get_post_meta(get_the_ID(), 'event_date') != null) {
                        $event_date = strtotime(get_post_meta(get_the_ID(), 'event_date')[0]);
                        $event_date_day_name = date('l', $event_date);
                        $event_date_day = date('j', $event_date);
                        $event_date_month = date('F', $event_date);
                        $event_date_year = date('Y', $event_date);
                        if ($box < 100 ) {
                            $event_list .= '
                                        <div class="date-box p-1 color-bg pe-4">
                                            <p class="m-0 wh-text up-case light" style="line-height: 1.4!important;">
                                            '.$event_date_day_name.'
                                            </p>
                                            <p class="m-0 wh-text bold XL" style="line-height: 1!important;">
                                            '.$event_date_day.'
                                            </p>
                                            <p class="m-0 wh-text bold L" style="line-height: 1.2!important;">
                                            '.$event_date_month.'
                                            </p>
                                            <p class="m-0 wh-text S">
                                            '.$event_date_year.'
                                            </p>
                                        </div>
                            ';
                        } else {
                            $event_list .= '
                                        <div class="date-box p-2 color-bg pe-4">
                                            <p class="m-0 wh-text up-case light L" style="line-height: 1.4!important;">
                                            '.$event_date_day_name.'
                                            </p>
                                            <p class="m-0 wh-text bold big" style="line-height: 1!important;">
                                            '.$event_date_day.'
                                            </p>
                                            <p class="m-0 wh-text bold XXL" style="line-height: 1.2!important;">
                                            '.$event_date_month.'
                                            </p>
                                            <p class="m-0 wh-text ">
                                            '.$event_date_year.'
                                            </p>
                                        </div>
                            ';
                        }
                    }
                    if (get_post_meta(get_the_ID(), 'event_time') != null) {
                        $event_time = date('H:i', strtotime(get_post_meta(get_the_ID(), 'event_date')[0]));
                        if ($box < 100 ) {
                            $event_list .= '
                                    <div class="time-box py-05 px-1 color-dark-bg">
                                        <p class="m-0 wh-text up-case" >
                                        '.esc_html__( 'Time', 'mini' ).': '.$event_time.'
                                        </p>
                                    </div>
                            ';
                        } else {
                            $event_list .= '
                                    <div class="time-box py-1 px-2 color-dark-bg">
                                        <p class="L m-0 wh-text up-case" >
                                        '.esc_html__( 'Time', 'mini' ).': '.$event_time.'
                                        </p>
                                    </div>
                            ';
                        }
                    }
                    if (
                        get_post_meta(get_the_ID(), 'event_end_date') != null || 
                        get_post_meta(get_the_ID(), 'event_end_time') != null
                    ) {
                        if ($box < 100 ) {
                        $event_list .= '
                                    <div class="day-box py-05 px-1 light-grey-bg">
                                        <p class="m-0 dark-grey-text up-case S">
                        ';
                        } else {
                            $event_list .= '
                                    <div class="day-box py-1 px-2 light-grey-bg">
                                        <p class="m-0 dark-grey-text up-case">
                            ';
                        }
                        $event_list .= esc_html__( 'End', 'mini' ).': ';
                        if ( get_post_meta(get_the_ID(), 'event_end_date') != null ) {
                            $event_end_date = date('j F Y', strtotime(get_post_meta(get_the_ID(), 'event_end_date')[0]));
                            $event_list .= $event_end_date;
                        }
                        if (
                            get_post_meta(get_the_ID(), 'event_end_date') != null && 
                            get_post_meta(get_the_ID(), 'event_end_time') != null
                        ) {
                            $event_list .= '&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;';
                        }
                        if ( get_post_meta(get_the_ID(), 'event_end_time') != null ) {
                            $event_end_time = date('H:i', strtotime(get_post_meta(get_the_ID(), 'event_date')[0]));
                            $event_list .= $event_end_time;
                        }
                        $event_list .= '
                                        </p>
                                    </div>
                        ';
                    }
                    $event_list .= '</div>';
        
                }
                    $event_list .= '
                                <p class="'.$text_color.'">'.get_the_excerpt(get_the_ID()).'</p>
                                <p class="">
                                    <a href="'.get_the_permalink().'" class="btn btn-bg">'.esc_html__( 'Read more', 'mini' ).'</a>
                                </p>
                            </div>
                        </div>
                    </div>';
            }
            $n++;
        endwhile;
        return $event_list;
    endif;
}
function get_next_event_inv_callback() {
    return get_next_event_callback(1,true);
}
function get_next_3_events_callback() {
    return get_next_event_callback(3,false, 33);
}
function get_next_4_events_callback() {
    return get_next_event_callback(4,false, 25);
}
add_shortcode('next_event', 'get_next_event_callback');
add_shortcode('next_event_inv', 'get_next_event_inv_callback');
add_shortcode('next_events', 'get_next_3_events_callback');
add_shortcode('next_3_events', 'get_next_3_events_callback');
add_shortcode('next_4_events', 'get_next_4_events_callback');
/* END - Custom post type - EVENT */


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
            settings_fields( 'mini_comment' );
            do_settings_sections( 'mini-comment' );
            submit_button( 'Save Settings' );
            ?>
        </form>
    </div>
    <?php
}

$options = get_option( 'mini_comment_settings' );
if (is_array($options) && array_key_exists('mini_disable_comment', $options)) {
    if ($options['mini_disable_comment'] == true) {
        add_action('admin_init', 'disable_comments_post_types_support');
        add_filter('comments_open', 'disable_comments_status', 20, 2);
        add_filter('pings_open', 'disable_comments_status', 20, 2);
        add_action('admin_menu', 'disable_comments_admin_menu');
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
            settings_fields( 'mini_blogging' );
            do_settings_sections( 'mini-blogging' );
            submit_button( 'Save Settings' );
            ?>
        </form>
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
       remove_menu_page('tools'); // Contact Form 7 Menu
    }
}
add_action( 'admin_init', 'remove_tools_settings_menu_page' );
/* END - DISABLE CF7 settings for non-admins */