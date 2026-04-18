<?php
/* START - Security settings */

function mini_security_settings_init() {
    register_setting( 'mini_security', 'mini_security_settings', [
        'sanitize_callback' => 'mini_security_sanitize_settings',
    ] );
    add_settings_section(
        'mini_security_section',
        __( 'Security settings', 'mini' ),
        'mini_security_section_callback',
        'mini-security'
    );
}
add_action( 'admin_init', 'mini_security_settings_init' );

function mini_security_sanitize_settings( $input ) {
    $sanitized = [];
    $sanitized['mini_disable_rest_api']      = ! empty( $input['mini_disable_rest_api'] ) ? 1 : 0;
    $sanitized['mini_disable_xmlrpc']        = ! empty( $input['mini_disable_xmlrpc'] ) ? 1 : 0;
    $sanitized['mini_limit_login_enabled']   = ! empty( $input['mini_limit_login_enabled'] ) ? 1 : 0;
    $sanitized['mini_limit_login_attempts']  = max( 1, absint( $input['mini_limit_login_attempts'] ?? 5 ) );
    $sanitized['mini_limit_login_lockout']   = max( 1, absint( $input['mini_limit_login_lockout'] ?? 15 ) );
    return $sanitized;
}

function mini_security_section_callback( $args ) {
    $opts           = get_option( 'mini_security_settings', [] );
    $disable_rest   = ! empty( $opts['mini_disable_rest_api'] );
    $disable_xmlrpc = ! empty( $opts['mini_disable_xmlrpc'] );
    $limit_enabled  = ! empty( $opts['mini_limit_login_enabled'] );
    $max_attempts   = absint( $opts['mini_limit_login_attempts'] ?? 5 );
    $lockout_mins   = absint( $opts['mini_limit_login_lockout'] ?? 15 );
    ?>
    <p id="<?php echo esc_attr( $args['id'] ); ?>" class="grey-text">
        <?php esc_html_e( 'Harden your WordPress installation against common attack vectors.', 'mini' ); ?>
    </p>
    <div class="boxes">

        <div class="box-50 p-2 white-bg b-rad-5 box-shadow">
            <h4 class=""><?php esc_html_e( 'Disable REST API for non-logged-in users', 'mini' ); ?></h4>
            <label for="mini_disable_rest_api" class="black-text">
                <input type="checkbox" id="mini_disable_rest_api" name="mini_security_settings[mini_disable_rest_api]" value="1" <?php checked( $disable_rest ); ?> class="me-1">
                <?php esc_html_e( 'Return a 401 error for all REST API requests made by unauthenticated visitors.', 'mini' ); ?>
            </label>
            <p class="S grey-text"><?php esc_html_e( 'Prevents user enumeration via /wp-json/wp/v2/users and reduces the exposed attack surface. Does not affect logged-in users or internal WordPress requests.', 'mini' ); ?></p>
        </div>

        <div class="box-50 p-2 white-bg b-rad-5 box-shadow">
            <h4 class=""><?php esc_html_e( 'Disable XML-RPC', 'mini' ); ?></h4>
            <label for="mini_disable_xmlrpc" class="black-text">
                <input type="checkbox" id="mini_disable_xmlrpc" name="mini_security_settings[mini_disable_xmlrpc]" value="1" <?php checked( $disable_xmlrpc ); ?> class="me-1">
                <?php esc_html_e( 'Disable the XML-RPC endpoint and remove its advertised header.', 'mini' ); ?>
            </label>
            <p class="S grey-text"><?php esc_html_e( 'Blocks all XML-RPC requests and removes the pingback link tag from the page head. Recommended unless you use the WordPress mobile app, Jetpack, or another XML-RPC-dependent service.', 'mini' ); ?></p>
        </div>

        <div class="box-100 p-2 white-bg b-rad-5 box-shadow">
            <h4 class=""><?php esc_html_e( 'Limit login attempts', 'mini' ); ?></h4>
                <div class="boxes">
                    <div class="box-50 p-0">
                        <label for="mini_limit_login_enabled" class="black-text">
                            <input type="checkbox" id="mini_limit_login_enabled" name="mini_security_settings[mini_limit_login_enabled]" value="1" <?php checked( $limit_enabled ); ?> class="me-1">
                            <?php esc_html_e( 'Lock out an IP address after too many failed login attempts.', 'mini' ); ?>
                        </label>
                    </div>
                    <div class="box-25 p-0">
                        <label class="grey-text">
                            <?php esc_html_e( 'Max attempts', 'mini' ); ?><br>
                            <input type="number" min="1" id="mini_limit_login_attempts" name="mini_security_settings[mini_limit_login_attempts]" value="<?php echo esc_attr( $max_attempts ); ?>" class="S">
                        </label>
                    </div>
                    <div class="box-25 p-0">
                        <label class="grey-text">
                            <?php esc_html_e( 'Lockout duration (minutes)', 'mini' ); ?><br>
                            <input type="number" min="1" id="mini_limit_login_lockout" name="mini_security_settings[mini_limit_login_lockout]" value="<?php echo esc_attr( $lockout_mins ); ?>" class="S">
                        </label>
                    </div>
                </div>
            <p class="S grey-text"><?php esc_html_e( 'After the maximum number of failed attempts, the IP is blocked for the specified duration. The counter resets on a successful login.', 'mini' ); ?></p>
        </div>

    </div>
    <?php
}

/* END - Security settings */

/* START - REST API restriction */

// Remove the REST-API blocker for ALTCHA before authentication runs.
// rest_api_init fires inside rest_get_server(), before check_authentication().
add_action( 'rest_api_init', 'mini_altcha_rest_api_init', 1 );
function mini_altcha_rest_api_init() {
    $uri   = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
    $route = isset( $_GET['rest_route'] )     ? wp_unslash( $_GET['rest_route'] )     : '';
    if ( false !== strpos( $uri, '/altcha/v1/' ) || false !== strpos( $route, '/altcha/v1/' ) ) {
        remove_filter( 'rest_authentication_errors', 'mini_restrict_rest_api' );
    }
}

add_filter( 'rest_authentication_errors', 'mini_restrict_rest_api' );
function mini_restrict_rest_api( $result ) {
    // If another plugin already set an error or granted access, respect it
    if ( ! is_null( $result ) ) {
        return $result;
    }

    $opts = get_option( 'mini_security_settings', [] );
    if ( empty( $opts['mini_disable_rest_api'] ) ) {
        return $result;
    }

    if ( ! is_user_logged_in() ) {
        return new WP_Error(
            'rest_not_logged_in',
            __( 'You must be logged in to access the REST API.', 'mini' ),
            [ 'status' => 401 ]
        );
    }

    return $result;
}

/* END - REST API restriction */

/* START - XML-RPC restriction */

add_filter( 'xmlrpc_enabled', 'mini_disable_xmlrpc' );
function mini_disable_xmlrpc( $enabled ) {
    $opts = get_option( 'mini_security_settings', [] );
    if ( ! empty( $opts['mini_disable_xmlrpc'] ) ) {
        return false;
    }
    return $enabled;
}

add_filter( 'wp_headers', 'mini_remove_xmlrpc_header' );
function mini_remove_xmlrpc_header( $headers ) {
    $opts = get_option( 'mini_security_settings', [] );
    if ( ! empty( $opts['mini_disable_xmlrpc'] ) ) {
        unset( $headers['X-Pingback'] );
    }
    return $headers;
}

add_action( 'wp_head', 'mini_remove_xmlrpc_pingback_link', 1 );
function mini_remove_xmlrpc_pingback_link() {
    $opts = get_option( 'mini_security_settings', [] );
    if ( ! empty( $opts['mini_disable_xmlrpc'] ) ) {
        remove_action( 'wp_head', 'rsd_link' );
        remove_action( 'wp_head', 'wlwmanifest_link' );
    }
}

/* END - XML-RPC restriction */

/* START - Login attempt limiting */

function mini_login_get_ip() {
    // Use REMOTE_ADDR as the authoritative source; proxy headers are spoofable
    return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '0.0.0.0';
}

function mini_login_transient_key( $ip ) {
    return 'mini_login_fails_' . md5( $ip );
}

add_action( 'wp_login_failed', 'mini_login_record_failure' );
function mini_login_record_failure( $username ) {
    $opts = get_option( 'mini_security_settings', [] );
    if ( empty( $opts['mini_limit_login_enabled'] ) ) {
        return;
    }

    $ip      = mini_login_get_ip();
    $key     = mini_login_transient_key( $ip );
    $lockout = max( 1, absint( $opts['mini_limit_login_lockout'] ?? 15 ) ) * MINUTE_IN_SECONDS;
    $count   = (int) get_transient( $key );

    set_transient( $key, $count + 1, $lockout );
}

add_filter( 'authenticate', 'mini_login_check_lockout', 30 );
function mini_login_check_lockout( $user ) {
    $opts = get_option( 'mini_security_settings', [] );
    if ( empty( $opts['mini_limit_login_enabled'] ) ) {
        return $user;
    }

    $ip          = mini_login_get_ip();
    $key         = mini_login_transient_key( $ip );
    $count       = (int) get_transient( $key );
    $max         = max( 1, absint( $opts['mini_limit_login_attempts'] ?? 5 ) );
    $lockout_min = max( 1, absint( $opts['mini_limit_login_lockout'] ?? 15 ) );

    if ( $count >= $max ) {
        return new WP_Error(
            'mini_too_many_attempts',
            sprintf(
                __( 'Too many failed login attempts. Please try again in %d minute(s).', 'mini' ),
                $lockout_min
            )
        );
    }

    return $user;
}

add_action( 'wp_login', 'mini_login_clear_failures', 10, 2 );
function mini_login_clear_failures( $user_login, $user ) {
    $opts = get_option( 'mini_security_settings', [] );
    if ( empty( $opts['mini_limit_login_enabled'] ) ) {
        return;
    }
    delete_transient( mini_login_transient_key( mini_login_get_ip() ) );
}

/* END - Login attempt limiting */
