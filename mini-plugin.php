<?php
/**
* Plugin Name: mini
* Plugin URI: https://mini.uwa.agency/
* Description: A mini plugin to manage mini frontend framework settings
* Version: 0.1
* Author: Giacomo Rizzotti
* Author URI: https://www.giacomorizzotti.com/
**/

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

function mini_content_settings_init() {

    register_setting( 'mini-settings', 'mini_content_settings');

    add_settings_section(
        'mini_content_settings_section',
        __( 'Mini content settings', 'mini' ),
        'mini_content_settings_section_callback',
        'mini-content-settings'
    );

    add_settings_field(
        'mini_field',
        __( 'Content settings', 'mini' ),
        'mini_content_settings_fields_callback',
        'mini-content-settings',
        'mini_content_settings_section',
        array(
            'label_for'         => 'mini',
            'class'             => 'mini_row',
            'mini_custom_data'  => 'custom',
        )
    );

}

add_action( 'admin_init', 'mini_content_settings_init' );

function mini_content_settings_fields_callback( $args ) {
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

function mini_content_settings_section_callback( $args ) {
    ?>
    <p id="<?php echo esc_attr( $args['id'] ); ?>"><?php esc_html_e( 'This is the Content type section', 'mini' ); ?></p>
    <?php
}

add_action( 'admin_menu', 'mini_content_settings_page' );
function mini_content_settings_page() {
    add_menu_page(
        'mini options',
        'mini',
        'manage_options',
        'mini',
        'mini_plugin_main_page_html',
        'https://cdn.jsdelivr.net/gh/giacomorizzotti/mini/img/brand/mini_emblem_space_around.svg'
    );
    add_submenu_page(
        'mini',
        'Content types',
        'Content types',
        'manage_options',
        'mini-content-settings',
        'mini_content_settings_page_html',
        9
    );
}

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

function mini_content_settings_page_html() {
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
            settings_fields( 'mini-settings' );
            do_settings_sections( 'mini-content-settings' );
            submit_button( 'Save Settings' );
            ?>
        </form>
    </div>
    <?php
}


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

/**
 * EVENT | ADD Date and time options
 */

add_action( 'add_meta_boxes', 'add_date_time_box' );
add_action( 'save_post', 'date_time_save_postdata' );

function add_date_time_box() {
    add_meta_box(
        'date-time',
        'Date and time',
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

/* END - Custom post type - EVENT */
