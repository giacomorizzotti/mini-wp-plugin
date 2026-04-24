<?php
/* START - Contact Form */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* Settings */

function mini_contact_form_settings_init() {
    register_setting( 'mini_contact_form', 'mini_contact_form_settings', [
        'sanitize_callback' => 'mini_contact_form_sanitize_settings',
    ] );
    add_settings_section(
        'mini_contact_form_section',
        __( 'Contact Form settings', 'mini' ),
        'mini_contact_form_section_callback',
        'mini-contact-form'
    );
}
add_action( 'admin_init', 'mini_contact_form_settings_init' );

function mini_contact_form_sanitize_settings( $input ) {
    $sanitized = [];
    $sanitized['mini_cf_enabled']        = ! empty( $input['mini_cf_enabled'] ) ? 1 : 0;
    $sanitized['mini_cf_recipient_email'] = sanitize_email( $input['mini_cf_recipient_email'] ?? '' );
    $sanitized['mini_cf_email_subject']   = sanitize_text_field( $input['mini_cf_email_subject'] ?? '' );
    $sanitized['mini_cf_success_message'] = sanitize_text_field( $input['mini_cf_success_message'] ?? '' );
    $sanitized['mini_cf_error_message']   = sanitize_text_field( $input['mini_cf_error_message'] ?? '' );
    $sanitized['mini_cf_gdpr_consent']    = ! empty( $input['mini_cf_gdpr_consent'] ) ? 1 : 0;
    $sanitized['mini_cf_autoreply_body']  = wp_kses_post( $input['mini_cf_autoreply_body'] ?? '' );
    return $sanitized;
}

function mini_contact_form_section_callback( $args ) {
    $opts           = get_option( 'mini_contact_form_settings', [] );
    $enabled        = ! empty( $opts['mini_cf_enabled'] );
    $recipient      = esc_attr( $opts['mini_cf_recipient_email'] ?? '' );
    $email_subject  = esc_attr( $opts['mini_cf_email_subject'] ?? '' );
    $success_msg    = esc_attr( $opts['mini_cf_success_message'] ?? '' );
    $error_msg      = esc_attr( $opts['mini_cf_error_message'] ?? '' );
    $gdpr_consent    = ! empty( $opts['mini_cf_gdpr_consent'] );
    $autoreply_body  = $opts['mini_cf_autoreply_body'] ?? '';
    ?>
    <p id="<?php echo esc_attr( $args['id'] ); ?>" class="grey-text">
        <?php esc_html_e( 'Configure contact form. Use the shortcode', 'mini' ); ?>
        <code>[mini_contact_form]</code>
        <?php esc_html_e( 'to embed the form in any page or post.', 'mini' ); ?>
    </p>

    <div class="boxes align-items-start">

        <div class="box-50 p-2 white-bg b-rad-5 box-shadow grad-3-to-4">
            <h4 class="white-text"><?php esc_html_e( 'Enable contact form', 'mini' ); ?></h4>
            <label for="mini_cf_enabled" class="white-text">
                <input type="checkbox" id="mini_cf_enabled" name="mini_contact_form_settings[mini_cf_enabled]" value="1" <?php checked( $enabled ); ?> class="me-1">
                <?php esc_html_e( 'Enable the contact form and make it available via shortcode.', 'mini' ); ?>
            </label>
        </div>

        <div class="sep light-grey-border"></div>

        <div class="box-50 p-2 white-bg b-rad-5 box-shadow">
            <h4 class=""><?php esc_html_e( 'Recipient email', 'mini' ); ?></h4>
            <input type="email" id="mini_cf_recipient_email" name="mini_contact_form_settings[mini_cf_recipient_email]" value="<?php echo $recipient; ?>" placeholder="admin-website-email@yourdomain.com" class="regular-text">
            <p class="description"><?php esc_html_e( 'The address that will receive submissions from users. Defaults to the site admin email if left empty.', 'mini' ); ?></p>
        </div>

        <div class="box-50 p-2 white-bg b-rad-5 box-shadow">
            <h4><?php esc_html_e( 'Email subject', 'mini' ); ?></h4>
            <input type="text" id="mini_cf_email_subject" name="mini_contact_form_settings[mini_cf_email_subject]" value="<?php echo $email_subject; ?>" placeholder="<?php esc_attr_e( 'New message from your website', 'mini' ); ?>" class="regular-text">
            <p class="description"><?php esc_html_e( 'The subject line of the notification email. Leave empty to use the default.', 'mini' ); ?></p>
        </div>

        <div class="box-50 p-2 white-bg b-rad-5 box-shadow">
            <h4><?php esc_html_e( 'Success message', 'mini' ); ?></h4>
            <input type="text" id="mini_cf_success_message" name="mini_contact_form_settings[mini_cf_success_message]" value="<?php echo $success_msg; ?>" placeholder="<?php esc_attr_e( 'Thank you! Your message has been sent.', 'mini' ); ?>" class="regular-text">
            <p class="description"><?php esc_html_e( 'Message shown to the user after a successful submission. Leave empty to use the default.', 'mini' ); ?></p>
        </div>

        <div class="box-50 p-2 white-bg b-rad-5 box-shadow">
            <h4><?php esc_html_e( 'Error message', 'mini' ); ?></h4>
            <input type="text" id="mini_cf_error_message" name="mini_contact_form_settings[mini_cf_error_message]" value="<?php echo $error_msg; ?>" placeholder="<?php esc_attr_e( 'The message could not be sent. Please try again later.', 'mini' ); ?>" class="regular-text">
            <p class="description"><?php esc_html_e( 'Message shown to the user when sending fails. Leave empty to use the default.', 'mini' ); ?></p>
        </div>

        <div class="box-50 p-2 white-bg b-rad-5 box-shadow">
            <h4><?php esc_html_e( 'Auto-reply message', 'mini' ); ?></h4>
            <textarea id="mini_cf_autoreply_body" name="mini_contact_form_settings[mini_cf_autoreply_body]" rows="6" class="large-text" placeholder="<?php echo __( "Hi {name},\nThank you for getting in touch! We have received your message and will get back to you as soon as possible.\n\nBest regards", 'mini' ); ?>"><?php echo $autoreply_body; ?></textarea>
            <p class="description"><?php esc_html_e( 'Courtesy email sent automatically to the sender. You can use {name} as a placeholder for the sender\'s name. Leave empty to disable the auto-reply.', 'mini' ); ?></p>
        </div>

        <div class="box-50 p-2 white-bg b-rad-5 box-shadow">
            <h4><?php esc_html_e( 'GDPR consent checkbox', 'mini' ); ?></h4>
            <label for="mini_cf_gdpr_consent" class="black-text">
                <input type="checkbox" id="mini_cf_gdpr_consent" name="mini_contact_form_settings[mini_cf_gdpr_consent]" value="1" <?php checked( $gdpr_consent ); ?> class="me-1">
                <?php esc_html_e( 'Show a required Privacy Policy consent checkbox on the form.', 'mini' ); ?>
            </label>
            <p class="description"><?php esc_html_e( 'When enabled, the form links to the Privacy Policy page configured in GDPR settings.', 'mini' ); ?></p>
        </div>

    </div>
    <?php
}

/* Admin page */

function mini_contact_form_page_html() {
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
            settings_fields( 'mini_contact_form' );
            do_settings_sections( 'mini-contact-form' );
            submit_button( 'Save Settings' );
            ?>
        </form>
    </div>
    <?php
}

/* Shortcode */

add_shortcode( 'mini_contact_form', 'mini_contact_form_shortcode' );

function mini_contact_form_shortcode( $atts ) {
    $atts   = shortcode_atts( [], $atts, 'mini_contact_form' );
    $opts   = get_option( 'mini_contact_form_settings', [] );
    
    // Check if contact form is enabled
    if ( empty( $opts['mini_cf_enabled'] ) ) {
        return '';
    }
    
    $uid    = 'mini-cf-' . wp_unique_id();
    $fields = mini_get_contact_form_fields();

    ob_start();
    mini_load_contact_form_tpl( mini_get_contact_form_template(), $uid, $opts, $fields );
    return ob_get_clean();
}

/* AJAX handler */

add_action( 'wp_ajax_mini_contact_form_submit',        'mini_contact_form_handle_ajax' );
add_action( 'wp_ajax_nopriv_mini_contact_form_submit', 'mini_contact_form_handle_ajax' );

function mini_contact_form_handle_ajax() {
    // Verify nonce
    if ( ! isset( $_POST['mini_cf_nonce'] ) ||
         ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mini_cf_nonce'] ) ), 'mini_contact_form_nonce' ) ) {
        wp_send_json_error( [ 'message' => __( 'Security check failed. Please refresh the page and try again.', 'mini' ) ], 403 );
    }

    // Sanitize inputs
    $name    = sanitize_text_field( wp_unslash( $_POST['mini_cf_name']    ?? '' ) );
    $surname = sanitize_text_field( wp_unslash( $_POST['mini_cf_surname'] ?? '' ) );
    $email   = sanitize_email( wp_unslash( $_POST['mini_cf_email']        ?? '' ) );
    $phone   = sanitize_text_field( wp_unslash( $_POST['mini_cf_phone']   ?? '' ) );
    $message = sanitize_textarea_field( wp_unslash( $_POST['mini_cf_message'] ?? '' ) );

    // Validate required fields
    if ( empty( $name ) || empty( $surname ) || empty( $email ) ) {
        wp_send_json_error( [ 'message' => __( 'Please fill in all required fields.', 'mini' ) ] );
    }

    if ( ! is_email( $email ) ) {
        wp_send_json_error( [ 'message' => __( 'Please enter a valid email address.', 'mini' ) ] );
    }

    // Check GDPR consent if required
    $opts = get_option( 'mini_contact_form_settings', [] );
    if ( ! empty( $opts['mini_cf_gdpr_consent'] ) && empty( $_POST['mini_cf_consent'] ) ) {
        wp_send_json_error( [ 'message' => __( 'You must accept the Privacy Policy to proceed.', 'mini' ) ] );
    }

    // Build and send email
    $to      = ! empty( $opts['mini_cf_recipient_email'] ) ? $opts['mini_cf_recipient_email'] : get_option( 'admin_email' );
    /* translators: %s: sender full name */
    $subject = ! empty( $opts['mini_cf_email_subject'] )
        ? $opts['mini_cf_email_subject']
        : sprintf( __( 'New message from %s', 'mini' ), $name . ' ' . $surname );

    $body  = sprintf( __( 'Name: %s', 'mini' ), $name . ' ' . $surname ) . "\n";
    $body .= sprintf( __( 'Email: %s', 'mini' ), $email ) . "\n";
    if ( ! empty( $phone ) ) {
        $body .= sprintf( __( 'Phone: %s', 'mini' ), $phone ) . "\n";
    }
    if ( ! empty( $message ) ) {
        $body .= "\n" . __( 'Message:', 'mini' ) . "\n" . $message;
    }

    $headers = [
        'Content-Type: text/plain; charset=UTF-8',
        'Reply-To: ' . $name . ' ' . $surname . ' <' . $email . '>',
    ];

    $sent = wp_mail( $to, $subject, $body, $headers );

    if ( $sent ) {
        // Send courtesy auto-reply to the sender if a body is configured
        if ( ! empty( $opts['mini_cf_autoreply_body'] ) ) {
            $autoreply_body    = str_replace( '{name}', $name, $opts['mini_cf_autoreply_body'] );
            $autoreply_body    = str_replace( '{surname}', $surname, $autoreply_body );
            $autoreply_subject = ! empty( $opts['mini_cf_email_subject'] )
                ? $opts['mini_cf_email_subject']
                : sprintf( __( 'New message from %s', 'mini' ), $name . ' ' . $surname );
            $autoreply_headers = [ 'Content-Type: text/plain; charset=UTF-8' ];
            wp_mail( $email, $autoreply_subject, $autoreply_body, $autoreply_headers );
        }

        $success_msg = ! empty( $opts['mini_cf_success_message'] )
            ? $opts['mini_cf_success_message']
            : __( 'Thank you! Your message has been sent.', 'mini' );
        wp_send_json_success( [ 'message' => $success_msg ] );
    } else {
        $error_msg = ! empty( $opts['mini_cf_error_message'] )
            ? $opts['mini_cf_error_message']
            : __( 'The message could not be sent. Please try again later.', 'mini' );
        wp_send_json_error( [ 'message' => $error_msg ] );
    }
}

/* END - Contact Form */
