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
    $sanitized['mini_cf_recipient_email'] = sanitize_email( $input['mini_cf_recipient_email'] ?? '' );
    $sanitized['mini_cf_email_subject']   = sanitize_text_field( $input['mini_cf_email_subject'] ?? '' );
    $sanitized['mini_cf_success_message'] = sanitize_text_field( $input['mini_cf_success_message'] ?? '' );
    $sanitized['mini_cf_error_message']   = sanitize_text_field( $input['mini_cf_error_message'] ?? '' );
    $sanitized['mini_cf_gdpr_consent']    = ! empty( $input['mini_cf_gdpr_consent'] ) ? 1 : 0;
    $sanitized['mini_cf_autoreply_body']  = sanitize_textarea_field( $input['mini_cf_autoreply_body'] ?? '' );
    return $sanitized;
}

function mini_contact_form_section_callback( $args ) {
    $opts           = get_option( 'mini_contact_form_settings', [] );
    $recipient      = esc_attr( $opts['mini_cf_recipient_email'] ?? '' );
    $email_subject  = esc_attr( $opts['mini_cf_email_subject'] ?? '' );
    $success_msg    = esc_attr( $opts['mini_cf_success_message'] ?? '' );
    $error_msg      = esc_attr( $opts['mini_cf_error_message'] ?? '' );
    $gdpr_consent    = ! empty( $opts['mini_cf_gdpr_consent'] );
    $autoreply_body  = esc_textarea( $opts['mini_cf_autoreply_body'] ?? '' );
    ?>
    <p id="<?php echo esc_attr( $args['id'] ); ?>" class="grey-text">
        <?php esc_html_e( 'Configure the built-in contact form. Use the shortcode', 'mini' ); ?>
        <code>[mini_contact_form]</code>
        <?php esc_html_e( 'to embed the form in any page or post.', 'mini' ); ?>
    </p>

    <div class="boxes">

        <div class="box-50 p-2 white-bg b-rad-5 box-shadow color-bg">
            <h4 class="white-text"><?php esc_html_e( 'Recipient email', 'mini' ); ?></h4>
            <input type="email" id="mini_cf_recipient_email" name="mini_contact_form_settings[mini_cf_recipient_email]" value="<?php echo $recipient; ?>" placeholder="contact@example.com" class="regular-text">
            <p class="S white-text"><?php esc_html_e( 'The address that will receive submissions. Defaults to the site admin email if left empty.', 'mini' ); ?></p>
        </div>

        <div class="box-50 p-2 white-bg b-rad-5 box-shadow">
            <h4><?php esc_html_e( 'Email subject', 'mini' ); ?></h4>
            <input type="text" id="mini_cf_email_subject" name="mini_contact_form_settings[mini_cf_email_subject]" value="<?php echo $email_subject; ?>" placeholder="<?php esc_attr_e( 'New message from your website', 'mini' ); ?>" class="regular-text">
            <p class="S grey-text"><?php esc_html_e( 'The subject line of the notification email. Leave empty to use the default.', 'mini' ); ?></p>
        </div>

        <div class="box-50 p-2 white-bg b-rad-5 box-shadow">
            <h4><?php esc_html_e( 'Success message', 'mini' ); ?></h4>
            <input type="text" id="mini_cf_success_message" name="mini_contact_form_settings[mini_cf_success_message]" value="<?php echo $success_msg; ?>" placeholder="<?php esc_attr_e( 'Thank you! Your message has been sent.', 'mini' ); ?>" class="regular-text">
            <p class="S grey-text"><?php esc_html_e( 'Message shown to the user after a successful submission.', 'mini' ); ?></p>
        </div>

        <div class="box-50 p-2 white-bg b-rad-5 box-shadow">
            <h4><?php esc_html_e( 'Error message', 'mini' ); ?></h4>
            <input type="text" id="mini_cf_error_message" name="mini_contact_form_settings[mini_cf_error_message]" value="<?php echo $error_msg; ?>" placeholder="<?php esc_attr_e( 'The message could not be sent. Please try again later.', 'mini' ); ?>" class="regular-text">
            <p class="S grey-text"><?php esc_html_e( 'Message shown to the user when sending fails.', 'mini' ); ?></p>
        </div>

        <div class="box-100 p-2 white-bg b-rad-5 box-shadow">
            <h4><?php esc_html_e( 'Auto-reply message', 'mini' ); ?></h4>
            <textarea id="mini_cf_autoreply_body" name="mini_contact_form_settings[mini_cf_autoreply_body]" rows="6" class="large-text" placeholder="<?php esc_attr_e( 'Hi {name},\n\nThank you for getting in touch! We have received your message and will get back to you as soon as possible.\n\nBest regards', 'mini' ); ?>"><?php echo $autoreply_body; ?></textarea>
            <p class="S grey-text"><?php esc_html_e( 'Courtesy email sent automatically to the sender. You can use {name} as a placeholder for the sender\'s name. Leave empty to disable the auto-reply.', 'mini' ); ?></p>
        </div>

        <div class="box-50 p-2 white-bg b-rad-5 box-shadow">
            <h4><?php esc_html_e( 'GDPR consent checkbox', 'mini' ); ?></h4>
            <label for="mini_cf_gdpr_consent" class="black-text">
                <input type="checkbox" id="mini_cf_gdpr_consent" name="mini_contact_form_settings[mini_cf_gdpr_consent]" value="1" <?php checked( $gdpr_consent ); ?> class="me-1">
                <?php esc_html_e( 'Show a required Privacy Policy consent checkbox on the form.', 'mini' ); ?>
            </label>
            <p class="S grey-text"><?php esc_html_e( 'When enabled, the form links to the Privacy Policy page configured in GDPR settings.', 'mini' ); ?></p>
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
    $atts = shortcode_atts( [], $atts, 'mini_contact_form' );
    $uid  = 'mini-cf-' . wp_unique_id();
    $opts = get_option( 'mini_contact_form_settings', [] );

    ob_start();
    ?>
    <div id="<?php echo esc_attr( $uid ); ?>" class="mini-contact-form-wrap">
        <form class="mini-contact-form" novalidate>
            <?php wp_nonce_field( 'mini_contact_form_nonce', 'mini_cf_nonce' ); ?>
            <div class="boxes">

                <div class="box-100 mini-cf-response-wrap" style="display:none;">
                    <p class="mini-cf-response" role="alert" aria-live="polite"></p>
                </div>

                <div class="box-50 mini-cf-field">
                    <label for="<?php echo esc_attr( $uid ); ?>-name"><?php esc_html_e( 'Name', 'mini' ); ?> <span class="color-text" aria-hidden="true">*</span></label>
                    <input type="text" id="<?php echo esc_attr( $uid ); ?>-name" name="mini_cf_name" required autocomplete="given-name">
                </div>

                <div class="box-50 mini-cf-field">
                    <label for="<?php echo esc_attr( $uid ); ?>-surname"><?php esc_html_e( 'Surname', 'mini' ); ?> <span class="color-text" aria-hidden="true">*</span></label>
                    <input type="text" id="<?php echo esc_attr( $uid ); ?>-surname" name="mini_cf_surname" required autocomplete="family-name">
                </div>

                <div class="box-50 mini-cf-field">
                    <label for="<?php echo esc_attr( $uid ); ?>-email"><?php esc_html_e( 'Email', 'mini' ); ?> <span class="color-text" aria-hidden="true">*</span></label>
                    <input type="email" id="<?php echo esc_attr( $uid ); ?>-email" name="mini_cf_email" required autocomplete="email">
                </div>

                <div class="box-50 mini-cf-field">
                    <label for="<?php echo esc_attr( $uid ); ?>-phone"><?php esc_html_e( 'Phone number', 'mini' ); ?></label>
                    <input type="tel" id="<?php echo esc_attr( $uid ); ?>-phone" name="mini_cf_phone" autocomplete="tel">
                </div>

                <div class="box-100 mini-cf-field">
                    <label for="<?php echo esc_attr( $uid ); ?>-message"><?php esc_html_e( 'Message', 'mini' ); ?></label>
                    <textarea id="<?php echo esc_attr( $uid ); ?>-message" name="mini_cf_message" rows="5"></textarea>
                </div>

                <?php if ( ! empty( $opts['mini_cf_gdpr_consent'] ) ) :
                    $gdpr_settings = get_option( 'mini_gdpr_privacy_settings', [] );
                    $gdpr_page_id  = absint( $gdpr_settings['mini_gdpr_privacy_page_id'] ?? 0 );
                    if ( ! $gdpr_page_id ) {
                        $gdpr_page_id = (int) get_option( 'wp_page_for_privacy_policy' );
                    }
                    $privacy_url = $gdpr_page_id ? esc_url( get_permalink( $gdpr_page_id ) ) : esc_url( get_privacy_policy_url() );
                ?>
                <div class="box-100 mini-cf-field mini-cf-consent">
                    <label>
                        <input type="checkbox" name="mini_cf_consent" value="1" class="inline-block" style="vertical-align: middle;" required>
                        <?php if ( $privacy_url ) :
                            printf(
                                wp_kses(
                                    /* translators: %s: URL to the privacy policy page */
                                    __( 'I have read and accept the <a href="%s" target="_blank" rel="noopener noreferrer">Privacy Policy</a>.', 'mini' ),
                                    [ 'a' => [ 'href' => [], 'target' => [], 'rel' => [] ] ]
                                ),
                                $privacy_url
                            );
                        else :
                            esc_html_e( 'I have read and accept the Privacy Policy.', 'mini' );
                        endif; ?>
                    </label>
                </div>
                <?php endif; ?>

                <div class="box-100 mini-cf-field mini-cf-submit">
                    <button type="submit" class="btn"><?php esc_html_e( 'Send message', 'mini' ); ?></button>
                </div>

            </div>
        </form>
    </div>
    <script>
    (function() {
        var wrap      = document.getElementById('<?php echo esc_js( $uid ); ?>');
        if ( ! wrap ) return;
        var form         = wrap.querySelector('.mini-contact-form');
        var responseWrap  = wrap.querySelector('.mini-cf-response-wrap');
        var response      = wrap.querySelector('.mini-cf-response');
        var submitBtn     = wrap.querySelector('button[type="submit"]');

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            var data = new FormData(form);
            data.append('action', 'mini_contact_form_submit');

            submitBtn.disabled = true;
            responseWrap.style.display = 'none';
            responseWrap.className = 'box-100 mini-cf-response-wrap';
            response.textContent = '';

            fetch('<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>', {
                method: 'POST',
                credentials: 'same-origin',
                body: data
            })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                responseWrap.style.display = '';
                if ( res.success ) {
                    responseWrap.classList.add('success-bg');
                    response.textContent = res.data.message;
                    form.reset();
                } else {
                    responseWrap.classList.add('danger-bg');
                    response.textContent = res.data.message;
                    submitBtn.disabled = false;
                }
            })
            .catch(function() {
                responseWrap.style.display = '';
                responseWrap.classList.add('danger-bg');
                response.textContent = '<?php echo esc_js( __( 'An error occurred. Please try again.', 'mini' ) ); ?>';
                submitBtn.disabled = false;
            });
        });
    })();
    </script>
    <?php
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
