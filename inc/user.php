<?php
/* START - User settings */

function mini_user_settings_init() {
    register_setting( 'mini_user', 'mini_user_settings', [
        'sanitize_callback' => 'mini_user_sanitize_settings',
        'default'           => [
            'mini_user_login_logout' => 0,
        ],
    ] );
    add_settings_section(
        'mini_user_section',
        __( 'User settings', 'mini' ),
        'mini_user_section_callback',
        'mini-user'
    );
}
add_action( 'admin_init', 'mini_user_settings_init' );

function mini_user_sanitize_settings( $input ) {
    $sanitized = [];
    $sanitized['mini_user_login_logout'] = ! empty( $input['mini_user_login_logout'] ) ? 1 : 0;
    return $sanitized;
}

function mini_user_section_callback( $args ) {
    $opts    = get_option( 'mini_user_settings', [] );
    $enabled = ! empty( $opts['mini_user_login_logout'] );
    ?>
    <p id="<?php echo esc_attr( $args['id'] ); ?>" class="grey-text">
        <?php esc_html_e( 'Manage user-facing features such as login and logout navigation.', 'mini' ); ?>
    </p>
    <div class="boxes">

        <div class="box-100 p-2 white-bg b-rad-5 box-shadow">
            <h4><?php esc_html_e( 'Login / Logout button in User Menu', 'mini' ); ?></h4>
            <label for="mini_user_login_logout" class="black-text">
                <input type="checkbox" id="mini_user_login_logout" name="mini_user_settings[mini_user_login_logout]" value="1" <?php checked( $enabled ); ?> class="me-1">
                <?php esc_html_e( 'Add a Login/Logout link at the end of the User Menu.', 'mini' ); ?>
            </label>
            <p class="S grey-text"><?php esc_html_e( 'Shows "Login" for guests (using the configured login URL) and "Logout" for logged-in users. Requires a menu assigned to the "User Menu" location.', 'mini' ); ?></p>
        </div>

    </div>
    <?php
}

/* END - User settings */

/* START - Login/Logout in User Menu */

add_filter( 'wp_nav_menu_items', 'mini_user_menu_login_logout', 10, 2 );
function mini_user_menu_login_logout( $items, $args ) {
    if ( ! isset( $args->theme_location ) || $args->theme_location !== 'user-menu' ) {
        return $items;
    }

    $opts = get_option( 'mini_user_settings', [] );
    if ( empty( $opts['mini_user_login_logout'] ) ) {
        return $items;
    }

    if ( is_user_logged_in() ) {
        $label = __( 'Logout', 'mini' );
        $url   = wp_logout_url( get_permalink() );
    } else {
        $label      = __( 'Login', 'mini' );
        $login_opts = get_option( 'mini_login_settings', [] );
        if ( ! empty( $login_opts['mini_login_enabled'] ) && ! empty( $login_opts['mini_login_slug'] ) ) {
            $url = trailingslashit( home_url( sanitize_title( $login_opts['mini_login_slug'] ) ) );
        } else {
            $url = wp_login_url( get_permalink() );
        }
    }

    $items .= '<li class="menu-item mini-login-logout"><a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a></li>';

    return $items;
}

/* END - Login/Logout in User Menu */
