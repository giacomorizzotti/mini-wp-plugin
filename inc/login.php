<?php
/* START - Login settings */

function mini_login_settings_init() {
    register_setting( 'mini_login', 'mini_login_settings', [
        'sanitize_callback' => 'mini_login_sanitize_settings',
        'default'           => [
            'mini_login_enabled' => 0,
            'mini_login_slug'    => 'login',
        ],
    ] );
    add_settings_section(
        'mini_login_section',
        __( 'Login URL settings', 'mini' ),
        'mini_login_section_callback',
        'mini-login'
    );
}
add_action( 'admin_init', 'mini_login_settings_init' );

function mini_login_sanitize_settings( $input ) {
    $sanitized = [];
    $sanitized['mini_login_enabled'] = ! empty( $input['mini_login_enabled'] ) ? 1 : 0;
    $slug = sanitize_title( trim( $input['mini_login_slug'] ?? 'login', '/' ) );
    $sanitized['mini_login_slug'] = $slug ?: 'login';
    return $sanitized;
}

function mini_login_section_callback( $args ) {
    $opts    = get_option( 'mini_login_settings', [] );
    $enabled = ! empty( $opts['mini_login_enabled'] );
    $slug    = esc_attr( $opts['mini_login_slug'] ?? 'login' );
    ?>
    <p id="<?php echo esc_attr( $args['id'] ); ?>" class="grey-text">
        <?php esc_html_e( 'Customize the WordPress login URL to improve security by hiding the default wp-login.php path.', 'mini' ); ?>
    </p>
    <div class="boxes">

        <div class="box-50 p-2 white-bg b-rad-5 box-shadow">
            <h4 class=""><?php esc_html_e( 'Enable custom login URL', 'mini' ); ?></h4>
            <label for="mini_login_enabled" class="black-text">
                <input type="checkbox" id="mini_login_enabled" name="mini_login_settings[mini_login_enabled]" value="1" <?php checked( $enabled ); ?> class="me-1">
                <?php esc_html_e( 'Disable the default wp-login.php path and redirect it to the home page.', 'mini' ); ?>
            </label>
        </div>

        <div class="box-50 p-2 white-bg b-rad-5 box-shadow">
            <h4 class=""><?php esc_html_e( 'Custom login path', 'mini' ); ?></h4>
            <div style="display:flex;align-items:center;gap:6px;">
                <span class="grey-text"><?php echo esc_html( trailingslashit( home_url() ) ); ?></span>
                <input type="text" id="mini_login_slug" name="mini_login_settings[mini_login_slug]" value="<?php echo $slug; ?>" placeholder="login" class="regular-text">
            </div>
            <p class="S grey-text"><?php esc_html_e( 'The path used to access the login page. Use only letters, numbers, and hyphens.', 'mini' ); ?></p>
        </div>

    </div>
    <?php
}

/* END - Login settings */

/* START - Login URL enforcement */

add_action( 'init', 'mini_login_intercept', 1 );
function mini_login_intercept() {
    $opts = get_option( 'mini_login_settings', [] );
    if ( empty( $opts['mini_login_enabled'] ) ) {
        return;
    }

    if ( ! isset( $_SERVER['REQUEST_URI'] ) ) {
        return;
    }

    $slug         = sanitize_title( trim( $opts['mini_login_slug'] ?? 'login', '/' ) ) ?: 'login';
    $request_path = trim( parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ), '/' );
    $base_path    = trim( parse_url( home_url(), PHP_URL_PATH ), '/' );
    if ( $base_path && strpos( $request_path, $base_path . '/' ) === 0 ) {
        $request_path = trim( substr( $request_path, strlen( $base_path ) ), '/' );
    } elseif ( $request_path === $base_path ) {
        $request_path = '';
    }

    if ( $request_path === $slug ) {
        global $pagenow;
        $pagenow = 'wp-login.php';
        // Only set action in GET so wp-login.php renders the form without treating it as a POST submission
        if ( ! isset( $_GET['action'] ) || '' === $_GET['action'] ) {
            $_GET['action'] = 'login';
        }
        $_REQUEST['action'] = $_GET['action'];
        define( 'MINI_LOGIN_CUSTOM_URL', true );
        require_once ABSPATH . 'wp-login.php';
        exit;
    }

    // If the default "login" path is visited but the custom slug is something else, redirect to home
    // so the real login URL is never leaked
    if ( $request_path === 'login' && $slug !== 'login' ) {
        wp_safe_redirect( home_url(), 302 );
        exit;
    }
}

add_filter( 'login_body_class', 'mini_login_fix_body_class', 99, 2 );
function mini_login_fix_body_class( $classes, $action ) {
    if ( ! defined( 'MINI_LOGIN_CUSTOM_URL' ) ) {
        return $classes;
    }
    // Remove whatever empty action class was generated and replace with the correct one
    $classes = array_filter( $classes, function ( $c ) {
        return 0 !== strpos( $c, 'login-action-' );
    } );
    $classes[] = 'login-action-login';
    return array_values( $classes );
}

add_action( 'login_init', 'mini_login_block_wplogin' );
function mini_login_block_wplogin() {
    $opts = get_option( 'mini_login_settings', [] );
    if ( empty( $opts['mini_login_enabled'] ) ) {
        return;
    }

    // Don't block when we are already serving through the custom URL
    if ( defined( 'MINI_LOGIN_CUSTOM_URL' ) ) {
        return;
    }

    // Allow automated/system requests
    if ( defined( 'DOING_CRON' ) && DOING_CRON ) return;
    if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) return;
    if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) return;

    // Allow POST requests — the login form action hardcodes wp-login.php,
    // so the actual credential submission must be allowed through
    if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === strtoupper( $_SERVER['REQUEST_METHOD'] ) ) {
        return;
    }

    // Allow logout, lostpassword, resetpass and other important actions
    $allowed_actions = [ 'logout', 'lostpassword', 'resetpass', 'rp', 'register' ];
    $action = isset( $_REQUEST['action'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ) : '';
    if ( in_array( $action, $allowed_actions, true ) ) {
        return;
    }

    // Redirect direct GET access to wp-login.php to home
    wp_safe_redirect( home_url(), 302 );
    exit;
}

add_filter( 'login_url', 'mini_login_filter_login_url', 10, 3 );
function mini_login_filter_login_url( $login_url, $redirect, $force_reauth ) {
    $opts = get_option( 'mini_login_settings', [] );
    if ( empty( $opts['mini_login_enabled'] ) ) {
        return $login_url;
    }

    $slug       = sanitize_title( trim( $opts['mini_login_slug'] ?? 'login', '/' ) ) ?: 'login';
    $custom_url = home_url( '/' . $slug . '/' );

    if ( $redirect ) {
        $custom_url = add_query_arg( 'redirect_to', rawurlencode( $redirect ), $custom_url );
    }
    if ( $force_reauth ) {
        $custom_url = add_query_arg( 'reauth', '1', $custom_url );
    }

    return $custom_url;
}

/* END - Login URL enforcement */
