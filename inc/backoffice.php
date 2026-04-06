<?php
/* START - Backoffice settings */

function mini_backoffice_settings_init() {
    register_setting( 'mini_backoffice', 'mini_backoffice_settings', [
        'sanitize_callback' => 'mini_backoffice_sanitize_settings',
    ] );
    add_settings_section(
        'mini_backoffice_section',
        __( 'Backoffice settings', 'mini' ),
        'mini_backoffice_section_callback',
        'mini-backoffice'
    );
}
add_action( 'admin_init', 'mini_backoffice_settings_init' );

function mini_backoffice_sanitize_settings( $input ) {
    $sanitized = [];
    $sanitized['mini_hide_update_nags'] = ! empty( $input['mini_hide_update_nags'] ) ? 1 : 0;
    return $sanitized;
}

function mini_backoffice_section_callback( $args ) {
    $opts             = get_option( 'mini_backoffice_settings', [] );
    $hide_update_nags = ! empty( $opts['mini_hide_update_nags'] );
    ?>
    <p id="<?php echo esc_attr( $args['id'] ); ?>" class="grey-text">
        <?php esc_html_e( 'Customize the WordPress admin experience for your clients and editors.', 'mini' ); ?>
    </p>
    <div class="boxes">

        <div class="box-100 p-2 white-bg b-rad-5 box-shadow">
            <h4 class=""><?php esc_html_e( 'Hide update nags for non-administrators', 'mini' ); ?></h4>
            <label for="mini_hide_update_nags" class="black-text">
                <input type="checkbox" id="mini_hide_update_nags" name="mini_backoffice_settings[mini_hide_update_nags]" value="1" <?php checked( $hide_update_nags ); ?> class="me-1">
                <?php esc_html_e( 'Hide WordPress core, plugin, and theme update notices for all roles except Administrator.', 'mini' ); ?>
            </label>
            <p class="S grey-text"><?php esc_html_e( 'Useful for client sites where you want to control the update process. Administrators will still see all update notices normally.', 'mini' ); ?></p>
        </div>

    </div>
    <?php
}

/* END - Backoffice settings */

/* START - Hide update nags */

add_action( 'admin_init', 'mini_maybe_hide_update_nags' );
function mini_maybe_hide_update_nags() {
    $opts = get_option( 'mini_backoffice_settings', [] );
    if ( empty( $opts['mini_hide_update_nags'] ) ) {
        return;
    }

    if ( current_user_can( 'administrator' ) ) {
        return;
    }

    remove_action( 'admin_notices', 'update_nag', 3 );
    remove_action( 'admin_notices', 'maintenance_nag', 10 );
    add_filter( 'pre_site_option_update_core', '__return_null' );
    add_filter( 'pre_option_update_core',      '__return_null' );
    add_filter( 'pre_site_option_update_plugins', '__return_null' );
    add_filter( 'pre_option_update_plugins',      '__return_null' );
    add_filter( 'pre_site_option_update_themes', '__return_null' );
    add_filter( 'pre_option_update_themes',       '__return_null' );
}

/* END - Hide update nags */

/* START - Editor settings */

add_action( 'admin_init', 'mini_editor_settings_init' );
function mini_editor_settings_init() {
    register_setting( 'mini_editor', 'mini_editor_settings', [
        'sanitize_callback' => 'mini_editor_sanitize_settings',
    ] );
    add_settings_section(
        'mini_editor_section',
        __( 'Editor settings', 'mini' ),
        'mini_editor_section_callback',
        'mini-editor'
    );
}

function mini_editor_sanitize_settings( $input ) {
    $sanitized = [];
    $post_types = get_post_types( [ 'show_ui' => true ], 'names' );
    $sanitized['mini_classic_editor_post_types'] = [];
    foreach ( $post_types as $pt ) {
        if ( ! empty( $input['mini_classic_editor_post_types'][ $pt ] ) ) {
            $sanitized['mini_classic_editor_post_types'][ $pt ] = 1;
        }
    }
    return $sanitized;
}

function mini_editor_section_callback( $args ) {
    $opts       = get_option( 'mini_editor_settings', [] );
    $disabled   = $opts['mini_classic_editor_post_types'] ?? [];
    $post_types = get_post_types( [ 'show_ui' => true ], 'objects' );
    // Exclude post types that don't support the editor — Gutenberg never runs on them anyway
    $post_types = array_filter( $post_types, function( $pt ) {
        return post_type_supports( $pt->name, 'editor' );
    } );
    // Exclude the attachment post type — it has no editor
    unset( $post_types['attachment'] );
    ?>
    <p id="<?php echo esc_attr( $args['id'] ); ?>">
        <?php esc_html_e( 'Choose which post types should use the Classic Editor instead of Gutenberg.', 'mini' ); ?>
    </p>
    <div class="boxes">
        <div class="box-100 p-2 white-bg b-rad-5 box-shadow">
            <h4 class="m-0"><?php esc_html_e( 'Disable Gutenberg per post type', 'mini' ); ?></h4>
            <p class="m-0 grey-text"><?php esc_html_e( 'Checked post types will use the Classic Editor instead of Gutenberg.', 'mini' ); ?></p>
            <div class="space-2"></div>
            <div class="boxes">
                <?php foreach ( $post_types as $pt ) :
                    $checked = ! empty( $disabled[ $pt->name ] );
                    $label   = $pt->labels->singular_name;
                ?>
                <div class="box-20">
                    <label class="bold bk-text">
                        <input type="checkbox"
                            name="mini_editor_settings[mini_classic_editor_post_types][<?php echo esc_attr( $pt->name ); ?>]"
                            value="1"
                            <?php checked( $checked ); ?>
                            class="me-1">
                        <?php echo wp_kses( $label, [ 'code' => [] ] ); ?>
                    </label>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php
}

add_filter( 'use_block_editor_for_post_type', 'mini_maybe_disable_gutenberg', 10, 2 );
function mini_maybe_disable_gutenberg( $use_block_editor, $post_type ) {
    $opts     = get_option( 'mini_editor_settings', [] );
    $disabled = $opts['mini_classic_editor_post_types'] ?? [];
    if ( ! empty( $disabled[ $post_type ] ) ) {
        return false;
    }
    return $use_block_editor;
}

/* END - Editor settings */
