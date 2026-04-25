<?php
/* START - Contact Form */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* START - Submissions database */

function mini_cf_create_submissions_table() {
    global $wpdb;
    $table_name      = $wpdb->prefix . 'mini_cf_submissions';
    $charset_collate = $wpdb->get_charset_collate();
    $sql             = "CREATE TABLE $table_name (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        submitted_at datetime NOT NULL,
        name varchar(100) NOT NULL DEFAULT '',
        surname varchar(100) NOT NULL DEFAULT '',
        email varchar(200) NOT NULL DEFAULT '',
        phone varchar(50) NOT NULL DEFAULT '',
        message text NOT NULL,
        consent tinyint(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (id)
    ) $charset_collate;";
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
}

add_action( 'admin_init', function() {
    if ( get_option( 'mini_cf_db_version' ) === '1.0' ) {
        return;
    }
    mini_cf_create_submissions_table();
    update_option( 'mini_cf_db_version', '1.0', false );
} );

/* END - Submissions database */

/* Settings */

function mini_contact_form_settings_init() {
    register_setting( 'mini_contact_form', 'mini_contact_form_settings', [
        'sanitize_callback' => 'mini_contact_form_sanitize_settings',
    ] );
    add_settings_section(
        'mini_contact_form_section',
        __( 'Contact form settings', 'mini' ),
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

add_action( 'admin_init', 'mini_cf_handle_submission_deletes' );
function mini_cf_handle_submission_deletes() {
    if ( ! isset( $_GET['page'] ) || 'mini-contact-form' !== $_GET['page'] ) {
        return;
    }
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    // Handle single-row delete
    if ( isset( $_GET['action'], $_GET['id'] ) && 'delete' === $_GET['action'] ) {
        $id = absint( $_GET['id'] );
        if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'mini_cf_delete_' . $id ) ) {
            wp_die( esc_html__( 'Security check failed.', 'mini' ) );
        }
        global $wpdb;
        $wpdb->delete( $wpdb->prefix . 'mini_cf_submissions', [ 'id' => $id ], [ '%d' ] );
        wp_safe_redirect( add_query_arg( [ 'page' => 'mini-contact-form', 'tab' => 'submissions', 'deleted' => '1' ], admin_url( 'admin.php' ) ) );
        exit;
    }

    // Handle delete-all
    if ( isset( $_POST['mini_cf_delete_all_nonce'] ) ) {
        if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mini_cf_delete_all_nonce'] ) ), 'mini_cf_delete_all' ) ) {
            wp_die( esc_html__( 'Security check failed.', 'mini' ) );
        }
        global $wpdb;
        $table = $wpdb->prefix . 'mini_cf_submissions';
        $wpdb->query( "TRUNCATE TABLE `{$table}`" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        wp_safe_redirect( add_query_arg( [ 'page' => 'mini-contact-form', 'tab' => 'submissions', 'deleted' => 'all' ], admin_url( 'admin.php' ) ) );
        exit;
    }
}

function mini_contact_form_page_html() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    if ( isset( $_GET['settings-updated'] ) ) {
        add_settings_error( 'mini_messages', 'mini_message', __( 'Settings Saved', 'mini' ), 'updated' );
    }
    if ( isset( $_GET['deleted'] ) ) {
        $msg = 'all' === $_GET['deleted']
            ? __( 'All submissions deleted.', 'mini' )
            : __( 'Submission deleted.', 'mini' );
        add_settings_error( 'mini_messages', 'mini_message', $msg, 'updated' );
    }
    settings_errors( 'mini_messages' );

    $current_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'settings';
    $page_url    = admin_url( 'admin.php?page=mini-contact-form' );
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

        <nav class="nav-tab-wrapper">
            <a href="<?php echo esc_url( $page_url . '&tab=settings' ); ?>" class="nav-tab <?php echo 'settings' === $current_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Settings', 'mini' ); ?></a>
            <a href="<?php echo esc_url( $page_url . '&tab=submissions' ); ?>" class="nav-tab <?php echo 'submissions' === $current_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Submissions', 'mini' ); ?></a>
        </nav>

        <?php if ( 'submissions' === $current_tab ) : ?>

            <?php mini_cf_render_submissions_tab( $page_url ); ?>

        <?php else : ?>

            <form action="options.php" method="post">
                <?php
                settings_fields( 'mini_contact_form' );
                do_settings_sections( 'mini-contact-form' );
                submit_button( 'Save Settings' );
                ?>
            </form>

        <?php endif; ?>
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

    wp_enqueue_script(
        'mini-altcha',
        'https://cdn.jsdelivr.net/npm/altcha/dist/altcha.min.js',
        [],
        null,
        true
    );
    // The Altcha widget is an ES module — without type="module" the custom
    // element is never registered and the <altcha-widget> tag stays inert.
    add_filter( 'script_loader_tag', 'mini_altcha_add_module_type', 10, 2 );

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

    // Verify Altcha proof-of-work
    $altcha_payload = sanitize_text_field( wp_unslash( $_POST['altcha'] ?? '' ) );
    if ( empty( $altcha_payload ) || ! mini_altcha_verify_payload( $altcha_payload ) ) {
        wp_send_json_error( [ 'message' => __( 'Security check failed. Please try again.', 'mini' ) ], 403 );
    }

    // Sanitize inputs
    $name    = sanitize_text_field( wp_unslash( $_POST['mini_cf_name']    ?? '' ) );
    $surname = sanitize_text_field( wp_unslash( $_POST['mini_cf_surname'] ?? '' ) );
    $email   = sanitize_email( wp_unslash( $_POST['mini_cf_email']        ?? '' ) );
    $phone   = sanitize_text_field( wp_unslash( $_POST['mini_cf_phone']   ?? '' ) );
    $message = sanitize_textarea_field( wp_unslash( $_POST['mini_cf_message'] ?? '' ) );

    // Collect per-field validation errors
    $field_errors = [];

    if ( empty( $name ) ) {
        $field_errors['mini_cf_name'] = __( 'Name is required.', 'mini' );
    }
    if ( empty( $surname ) ) {
        $field_errors['mini_cf_surname'] = __( 'Surname is required.', 'mini' );
    }
    if ( empty( $email ) ) {
        $field_errors['mini_cf_email'] = __( 'Email is required.', 'mini' );
    } elseif ( ! is_email( $email ) ) {
        $field_errors['mini_cf_email'] = __( 'Please enter a valid email address.', 'mini' );
    }
    if ( ! empty( $phone ) && ! preg_match( '/^\+?[\d\s\-().]{6,20}$/', $phone ) ) {
        $field_errors['mini_cf_phone'] = __( 'Please enter a valid phone number.', 'mini' );
    }

    // Check GDPR consent if required
    $opts = get_option( 'mini_contact_form_settings', [] );
    if ( ! empty( $opts['mini_cf_gdpr_consent'] ) && empty( $_POST['mini_cf_consent'] ) ) {
        $field_errors['mini_cf_consent'] = __( 'You must accept the Privacy Policy to proceed.', 'mini' );
    }

    if ( ! empty( $field_errors ) ) {
        wp_send_json_error( [
            'message' => __( 'Please correct the errors below.', 'mini' ),
            'fields'  => $field_errors,
        ] );
    }

    // Save submission to database
    global $wpdb;
    $wpdb->insert(
        $wpdb->prefix . 'mini_cf_submissions',
        [
            'submitted_at' => current_time( 'mysql' ),
            'name'         => $name,
            'surname'      => $surname,
            'email'        => $email,
            'phone'        => $phone,
            'message'      => $message,
            'consent'      => ! empty( $_POST['mini_cf_consent'] ) ? 1 : 0,
        ],
        [ '%s', '%s', '%s', '%s', '%s', '%s', '%d' ]
    );

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

/* START - Altcha (self-hosted proof-of-work) */

/**
 * Add type="module" to the Altcha widget script tag.
 * Without it the browser won't register the <altcha-widget> custom element.
 */
function mini_altcha_add_module_type( $tag, $handle ) {
    if ( 'mini-altcha' === $handle ) {
        return str_replace( '<script ', '<script type="module" ', $tag );
    }
    return $tag;
}

/**
 * Return (and lazily generate) the HMAC secret key for Altcha challenges.
 * Stored in wp_options and never exposed to the client.
 */
function mini_altcha_get_secret_key() {
    $key = get_option( 'mini_altcha_secret_key' );
    if ( ! $key ) {
        $key = bin2hex( random_bytes( 32 ) );
        update_option( 'mini_altcha_secret_key', $key, false );
    }
    return $key;
}

/**
 * AJAX endpoint: generate and return a fresh Altcha challenge.
 */
add_action( 'wp_ajax_nopriv_mini_altcha_challenge', 'mini_altcha_get_challenge' );
add_action( 'wp_ajax_mini_altcha_challenge',        'mini_altcha_get_challenge' );

function mini_altcha_get_challenge() {
    $max_number = 100000;
    $expires    = time() + 3600; // 1-hour TTL
    $salt       = bin2hex( random_bytes( 12 ) ) . '?expires=' . $expires;
    $number     = random_int( 0, $max_number );
    $challenge  = hash( 'sha256', $salt . $number );
    $signature  = hash_hmac( 'sha256', $challenge, mini_altcha_get_secret_key() );

    wp_send_json( [
        'algorithm'  => 'SHA-256',
        'challenge'  => $challenge,
        'maxnumber'  => $max_number,
        'salt'       => $salt,
        'signature'  => $signature,
    ] );
}

/**
 * Verify the Altcha payload (base64-encoded JSON) submitted by the widget.
 *
 * Checks:
 *  1. JSON is well-formed and all required fields are present.
 *  2. Challenge has not expired (TTL encoded in the salt).
 *  3. SHA-256(salt + number) matches the challenge (proof-of-work).
 *  4. HMAC-SHA-256(challenge, secret_key) matches the signature (origin).
 *
 * @param  string $payload_b64 Value of the 'altcha' POST field.
 * @return bool
 */
function mini_altcha_verify_payload( $payload_b64 ) {
    // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
    $json = base64_decode( $payload_b64, true );
    if ( false === $json ) {
        return false;
    }

    $data = json_decode( $json, true );
    if ( ! is_array( $data ) ) {
        return false;
    }

    $algorithm = $data['algorithm'] ?? '';
    $challenge = $data['challenge'] ?? '';
    $number    = $data['number']    ?? null;
    $salt      = $data['salt']      ?? '';
    $signature = $data['signature'] ?? '';

    if ( 'SHA-256' !== $algorithm || '' === $challenge || null === $number
         || '' === $salt || '' === $signature ) {
        return false;
    }

    // Check expiry embedded in salt (?expires=<unix_timestamp>)
    if ( preg_match( '/[?&]expires=(\d+)/', $salt, $m ) ) {
        if ( time() > (int) $m[1] ) {
            return false; // challenge expired
        }
    }

    // Verify proof-of-work
    $expected_challenge = hash( 'sha256', $salt . $number );
    if ( ! hash_equals( $expected_challenge, $challenge ) ) {
        return false;
    }

    // Verify HMAC signature to confirm this challenge was issued by us
    $expected_sig = hash_hmac( 'sha256', $challenge, mini_altcha_get_secret_key() );
    if ( ! hash_equals( $expected_sig, $signature ) ) {
        return false;
    }

    return true;
}

/* END - Altcha (self-hosted proof-of-work) */

/* START - Submissions tab */

function mini_cf_render_submissions_tab( $page_url ) {
    global $wpdb;
    $table        = $wpdb->prefix . 'mini_cf_submissions';
    $per_page     = 20;
    $current_page = max( 1, absint( $_GET['paged'] ?? 1 ) );
    $offset       = ( $current_page - 1 ) * $per_page;

    // phpcs:disable WordPress.DB.DirectDatabaseQuery
    $total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}`" );
    $rows  = $wpdb->get_results(
        $wpdb->prepare( "SELECT * FROM `{$table}` ORDER BY submitted_at DESC LIMIT %d OFFSET %d", $per_page, $offset )
    );
    // phpcs:enable WordPress.DB.DirectDatabaseQuery

    $total_pages = max( 1, (int) ceil( $total / $per_page ) );
    ?>
    <br>
    <?php if ( $total > 0 ) : ?>

        <form method="post" class="mb-1 flex align-items-center">
            <?php wp_nonce_field( 'mini_cf_delete_all', 'mini_cf_delete_all_nonce' ); ?>
            <button type="submit" class="danger-btn"
                onclick="return confirm('<?php esc_attr_e( 'Delete all submissions? This cannot be undone.', 'mini' ); ?>')">
                <?php esc_html_e( 'Delete all submissions', 'mini' ); ?>
            </button>
            <span class="">
                <?php
                /* translators: %d: total number of submissions */
                printf( esc_html__( '%d submission(s) recorded.', 'mini' ), $total );
                ?>
            </span>
        </form>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col" style="width: 160px;"><?php esc_html_e( 'Date', 'mini' ); ?></th>
                    <th scope="col" style="width: 200px;"><?php esc_html_e( 'Name', 'mini' ); ?></th>
                    <th scope="col" style="width: 220px;"><?php esc_html_e( 'Email', 'mini' ); ?></th>
                    <th scope="col" style="width: 160px;"><?php esc_html_e( 'Phone', 'mini' ); ?></th>
                    <th scope="col"><?php esc_html_e( 'Message', 'mini' ); ?></th>
                    <th scope="col" style="width: 100px;"><?php esc_html_e( 'Consent', 'mini' ); ?></th>
                    <th scope="col" style="width: 120px;"><?php esc_html_e( 'Actions', 'mini' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $rows as $row ) : ?>
                <tr>
                    <td><?php echo esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $row->submitted_at ) ) ); ?></td>
                    <td><?php echo esc_html( $row->name . ' ' . $row->surname ); ?></td>
                    <td><a href="mailto:<?php echo esc_attr( $row->email ); ?>"><?php echo esc_html( $row->email ); ?></a></td>
                    <td><?php echo esc_html( $row->phone ); ?></td>
                    <td title="<?php echo esc_attr( $row->message ); ?>"><?php echo esc_html( wp_trim_words( $row->message, 15, '…' ) ); ?></td>
                    <td><?php echo $row->consent ? '&#10003;' : '&mdash;'; ?></td>
                    <td>
                        <a href="<?php echo esc_url( wp_nonce_url( add_query_arg( [ 'page' => 'mini-contact-form', 'tab' => 'submissions', 'action' => 'delete', 'id' => $row->id ], admin_url( 'admin.php' ) ), 'mini_cf_delete_' . $row->id ) ); ?>"
                           onclick="return confirm('<?php esc_attr_e( 'Delete this submission?', 'mini' ); ?>')"
                           class="btn danger-btn S white-text">
                            <?php esc_html_e( 'Delete', 'mini' ); ?>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ( $total_pages > 1 ) : ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <?php
                echo paginate_links( [
                    'base'      => esc_url( add_query_arg( 'paged', '%#%', $page_url . '&tab=submissions' ) ),
                    'format'    => '',
                    'current'   => $current_page,
                    'total'     => $total_pages,
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                ] );
                ?>
            </div>
        </div>
        <?php endif; ?>

    <?php else : ?>
        <p><?php esc_html_e( 'No submissions yet.', 'mini' ); ?></p>
    <?php endif; ?>
    <?php
}

/* END - Submissions tab */

/* END - Contact Form */
