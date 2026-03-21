<?php
/* START - SMTP settings */
function mini_smtp_settings_init() {
    register_setting( 'mini_smtp', 'mini_smtp_settings', [
        'sanitize_callback' => 'mini_smtp_sanitize_settings',
    ]);
    add_settings_section(
        'mini_smtp_section',
        __( 'SMTP email settings', 'mini' ),
        'mini_smtp_section_callback',
        'mini-smtp'
    );
}
add_action( 'admin_init', 'mini_smtp_settings_init' );

function mini_smtp_sanitize_settings( $input ) {
    $sanitized = [];
    $sanitized['mini_smtp_enabled']    = !empty( $input['mini_smtp_enabled'] ) ? 1 : 0;
    $sanitized['mini_smtp_host']       = sanitize_text_field( $input['mini_smtp_host'] ?? '' );
    $sanitized['mini_smtp_port']       = absint( $input['mini_smtp_port'] ?? 587 );
    $sanitized['mini_smtp_encryption'] = in_array( $input['mini_smtp_encryption'] ?? '', [ 'tls', 'ssl', '' ] ) ? $input['mini_smtp_encryption'] : 'tls';
    $sanitized['mini_smtp_auth']       = !empty( $input['mini_smtp_auth'] ) ? 1 : 0;
    $sanitized['mini_smtp_username']   = sanitize_text_field( $input['mini_smtp_username'] ?? '' );
    // Only update password if a new one is provided, otherwise keep the existing one
    if ( !empty( $input['mini_smtp_password'] ) ) {
        $sanitized['mini_smtp_password'] = $input['mini_smtp_password'];
    } else {
        $existing = get_option( 'mini_smtp_settings' );
        $sanitized['mini_smtp_password'] = $existing['mini_smtp_password'] ?? '';
    }
    $sanitized['mini_smtp_from_email'] = sanitize_email( $input['mini_smtp_from_email'] ?? '' );
    $sanitized['mini_smtp_from_name']  = sanitize_text_field( $input['mini_smtp_from_name'] ?? '' );
    return $sanitized;
}

function mini_smtp_section_callback( $args ) {
    $opts = get_option( 'mini_smtp_settings', [] );
    $enabled    = !empty( $opts['mini_smtp_enabled'] );
    $host       = esc_attr( $opts['mini_smtp_host'] ?? '' );
    $port       = esc_attr( $opts['mini_smtp_port'] ?? 587 );
    $encryption = esc_attr( $opts['mini_smtp_encryption'] ?? 'tls' );
    $auth       = !empty( $opts['mini_smtp_auth'] );
    $username   = esc_attr( $opts['mini_smtp_username'] ?? '' );
    $from_email = esc_attr( $opts['mini_smtp_from_email'] ?? '' );
    $from_name  = esc_attr( $opts['mini_smtp_from_name'] ?? '' );
    ?>
    <p id="<?php echo esc_attr( $args['id'] ); ?>">
        Configure WordPress to send all emails (including Contact Form 7 and other plugins) through an external SMTP server.
    </p>

    <div class="boxes">
        <div class="box-100 p-2 white-bg b-rad-5 box-shadow">
            <h4 class="grey-text light"><?php esc_html_e( 'Enable SMTP', 'mini' ); ?></h4>
            <label for="mini_smtp_enabled" class="bold bk-text">
                <input type="checkbox" id="mini_smtp_enabled" name="mini_smtp_settings[mini_smtp_enabled]" value="1" <?php checked( $enabled ); ?> class="me-1">
                <?php esc_html_e( 'Send emails via SMTP instead of the default PHP mail function.', 'mini' ); ?>
            </label>
        </div>

        <div class="box-33 p-2 white-bg b-rad-5 box-shadow">
            <h4 class="grey-text light"><?php esc_html_e( 'SMTP Host', 'mini' ); ?></h4>
            <input type="text" id="mini_smtp_host" name="mini_smtp_settings[mini_smtp_host]" value="<?php echo $host; ?>" placeholder="smtp.example.com" class="regular-text">
            <p class="S grey-text"><?php esc_html_e( 'The hostname of your SMTP server.', 'mini' ); ?></p>
        </div>

        <div class="box-33 p-2 white-bg b-rad-5 box-shadow">
            <h4 class="grey-text light"><?php esc_html_e( 'SMTP Port', 'mini' ); ?></h4>
            <input type="number" id="mini_smtp_port" name="mini_smtp_settings[mini_smtp_port]" value="<?php echo $port; ?>" placeholder="587" class="small-text">
            <p class="S grey-text"><?php esc_html_e( 'Common ports: 25 (no encryption), 465 (SSL), 587 (TLS/STARTTLS).', 'mini' ); ?></p>
        </div>

        <div class="box-33 p-2 white-bg b-rad-5 box-shadow">
            <h4 class="grey-text light"><?php esc_html_e( 'Encryption', 'mini' ); ?></h4>
            <select id="mini_smtp_encryption" name="mini_smtp_settings[mini_smtp_encryption]">
                <option value="tls"  <?php selected( $encryption, 'tls' );  ?>>TLS / STARTTLS (recommended)</option>
                <option value="ssl"  <?php selected( $encryption, 'ssl' );  ?>>SSL</option>
                <option value=""     <?php selected( $encryption, '' );     ?>>None</option>
            </select>
            <p class="S grey-text"><?php esc_html_e( 'Select the encryption method supported by your SMTP server.', 'mini' ); ?></p>
        </div>

        <div class="box-20 p-2 white-bg b-rad-5 box-shadow">
            <h4 class="grey-text light"><?php esc_html_e( 'Authentication', 'mini' ); ?></h4>
            <label for="mini_smtp_auth" class="bold bk-text">
                <input type="checkbox" id="mini_smtp_auth" name="mini_smtp_settings[mini_smtp_auth]" value="1" <?php checked( $auth ); ?> class="me-1">
                <?php esc_html_e( 'Enable SMTP authentication (username & password).', 'mini' ); ?>
            </label>
        </div>

        <div class="box-40 p-2 white-bg b-rad-5 box-shadow">
            <h4 class="grey-text light"><?php esc_html_e( 'Username', 'mini' ); ?></h4>
            <input type="text" id="mini_smtp_username" name="mini_smtp_settings[mini_smtp_username]" value="<?php echo $username; ?>" placeholder="user@example.com" class="regular-text" autocomplete="off">
            <p class="S grey-text"><?php esc_html_e( 'SMTP account username (usually your email address).', 'mini' ); ?></p>
        </div>

        <div class="box-40 p-2 white-bg b-rad-5 box-shadow">
            <h4 class="grey-text light"><?php esc_html_e( 'Password', 'mini' ); ?></h4>
            <input type="password" id="mini_smtp_password" name="mini_smtp_settings[mini_smtp_password]" value="" placeholder="Leave blank to keep current password" class="regular-text" autocomplete="new-password">
            <p class="S grey-text"><?php esc_html_e( 'SMTP account password. Leave blank to keep the existing value.', 'mini' ); ?></p>
        </div>

        <div class="box-50 p-2 white-bg b-rad-5 box-shadow">
            <h4 class="grey-text light"><?php esc_html_e( 'From Email', 'mini' ); ?></h4>
            <input type="email" id="mini_smtp_from_email" name="mini_smtp_settings[mini_smtp_from_email]" value="<?php echo $from_email; ?>" placeholder="noreply@example.com" class="regular-text">
            <p class="S grey-text"><?php esc_html_e( 'The email address that will appear in the From field.', 'mini' ); ?></p>
        </div>

        <div class="box-50 p-2 white-bg b-rad-5 box-shadow">
            <h4 class="grey-text light"><?php esc_html_e( 'From Name', 'mini' ); ?></h4>
            <input type="text" id="mini_smtp_from_name" name="mini_smtp_settings[mini_smtp_from_name]" value="<?php echo $from_name; ?>" placeholder="My Website" class="regular-text">
            <p class="S grey-text"><?php esc_html_e( 'The name that will appear in the From field.', 'mini' ); ?></p>
        </div>

    </div>
    <?php
}

function mini_smtp_page_html() {
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
            settings_fields( 'mini_smtp' );
            do_settings_sections( 'mini-smtp' );
            submit_button( 'Save Settings' );
            ?>
        </form>
    </div>
    <?php
}

/**
 * Apply SMTP settings to WordPress PHPMailer.
 * This hooks into every email sent by WP and plugins (CF7, WooCommerce, etc.).
 */
function mini_smtp_configure_phpmailer( $phpmailer ) {
    $opts = get_option( 'mini_smtp_settings', [] );

    if ( empty( $opts['mini_smtp_enabled'] ) || empty( $opts['mini_smtp_host'] ) ) {
        return;
    }

    $phpmailer->isSMTP();
    $phpmailer->Host       = $opts['mini_smtp_host'];
    $phpmailer->Port       = !empty( $opts['mini_smtp_port'] ) ? (int) $opts['mini_smtp_port'] : 587;
    $phpmailer->SMTPSecure = $opts['mini_smtp_encryption'] ?? 'tls';
    $phpmailer->SMTPAuth   = !empty( $opts['mini_smtp_auth'] );

    if ( $phpmailer->SMTPAuth ) {
        $phpmailer->Username = $opts['mini_smtp_username'] ?? '';
        $phpmailer->Password = $opts['mini_smtp_password'] ?? '';
    }

    if ( !empty( $opts['mini_smtp_from_email'] ) ) {
        $phpmailer->setFrom(
            $opts['mini_smtp_from_email'],
            !empty( $opts['mini_smtp_from_name'] ) ? $opts['mini_smtp_from_name'] : ''
        );
    }
}
add_action( 'phpmailer_init', 'mini_smtp_configure_phpmailer' );

/**
 * Override the WordPress From email/name filters as well,
 * so headers set by CF7 or other plugins are also overridden.
 */
add_filter( 'wp_mail_from', function( $email ) {
    $opts = get_option( 'mini_smtp_settings', [] );
    if ( !empty( $opts['mini_smtp_enabled'] ) && !empty( $opts['mini_smtp_from_email'] ) ) {
        return $opts['mini_smtp_from_email'];
    }
    return $email;
});

add_filter( 'wp_mail_from_name', function( $name ) {
    $opts = get_option( 'mini_smtp_settings', [] );
    if ( !empty( $opts['mini_smtp_enabled'] ) && !empty( $opts['mini_smtp_from_name'] ) ) {
        return $opts['mini_smtp_from_name'];
    }
    return $name;
});
/* END - SMTP settings */
