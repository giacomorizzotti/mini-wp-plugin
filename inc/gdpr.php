<?php
/* START - GDPR settings */

function mini_gdpr_settings_init() {
    register_setting( 'mini_gdpr_privacy', 'mini_gdpr_privacy_settings', [
        'sanitize_callback' => 'mini_gdpr_privacy_sanitize_settings',
    ] );
    add_settings_section(
        'mini_gdpr_privacy_section',
        __( 'Privacy Policy page', 'mini' ),
        'mini_gdpr_privacy_section_callback',
        'mini-gdpr-privacy'
    );
}
add_action( 'admin_init', 'mini_gdpr_settings_init' );

function mini_gdpr_privacy_sanitize_settings( $input ) {
    $prev      = get_option( 'mini_gdpr_privacy_settings', [] );
    $sanitized = [];

    $sanitized['mini_gdpr_privacy_enabled']  = ! empty( $input['mini_gdpr_privacy_enabled'] ) ? 1 : 0;
    $sanitized['mini_gdpr_privacy_page_id']  = absint( $prev['mini_gdpr_privacy_page_id'] ?? 0 );

    // Owner fields
    $sanitized['mini_gdpr_owner']            = sanitize_text_field( $input['mini_gdpr_owner'] ?? '' );
    $sanitized['mini_gdpr_owner_address']    = sanitize_text_field( $input['mini_gdpr_owner_address'] ?? '' );
    $sanitized['mini_gdpr_owner_number']     = sanitize_text_field( $input['mini_gdpr_owner_number'] ?? '' );
    $sanitized['mini_gdpr_owner_city']       = sanitize_text_field( $input['mini_gdpr_owner_city'] ?? '' );
    $sanitized['mini_gdpr_owner_province']   = sanitize_text_field( $input['mini_gdpr_owner_province'] ?? '' );
    $sanitized['mini_gdpr_owner_cap']        = sanitize_text_field( $input['mini_gdpr_owner_cap'] ?? '' );
    $sanitized['mini_gdpr_owner_country']    = sanitize_text_field( $input['mini_gdpr_owner_country'] ?? '' );
    $sanitized['mini_gdpr_owner_email']      = sanitize_email( $input['mini_gdpr_owner_email'] ?? '' );
    $sanitized['mini_gdpr_owner_pec']        = sanitize_email( $input['mini_gdpr_owner_pec'] ?? '' );
    $sanitized['mini_gdpr_dpo']              = sanitize_text_field( $input['mini_gdpr_dpo'] ?? '' );
    $sanitized['mini_gdpr_dpo_email']        = sanitize_email( $input['mini_gdpr_dpo_email'] ?? '' );

    return $sanitized;
}

function mini_gdpr_ajax_fetch_privacy_page() {
    check_ajax_referer( 'mini_gdpr_fetch_privacy', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( [ 'message' => __( 'Permission denied.', 'mini' ) ] );
    }

    $opts    = get_option( 'mini_gdpr_privacy_settings', [] );
    $page_id = mini_gdpr_create_privacy_page( $opts );

    if ( $page_id ) {
        // Persist the page id
        $opts['mini_gdpr_privacy_page_id'] = $page_id;
        update_option( 'mini_gdpr_privacy_settings', $opts );
        wp_send_json_success( [
            'message'   => __( 'Privacy Policy page updated.', 'mini' ),
            'page_id'   => $page_id,
            'view_url'  => get_permalink( $page_id ),
            'edit_url'  => get_edit_post_link( $page_id ),
            'title'     => get_the_title( $page_id ),
        ] );
    } else {
        wp_send_json_error( [ 'message' => __( 'Could not fetch the privacy policy content from the API. Please try again.', 'mini' ) ] );
    }
}
add_action( 'wp_ajax_mini_gdpr_fetch_privacy_page', 'mini_gdpr_ajax_fetch_privacy_page' );

function mini_gdpr_create_privacy_page( $opts ) {
    $params = [];

    if ( ! empty( $opts['mini_gdpr_owner'] ) ) {
        $params['owner'] = $opts['mini_gdpr_owner'];
    }

    // Address: all six fields must be present for the API to use them
    $addr_fields = [ 'mini_gdpr_owner_address', 'mini_gdpr_owner_number', 'mini_gdpr_owner_city', 'mini_gdpr_owner_province', 'mini_gdpr_owner_cap', 'mini_gdpr_owner_country' ];
    $has_all_addr = true;
    foreach ( $addr_fields as $f ) {
        if ( empty( $opts[ $f ] ) ) { $has_all_addr = false; break; }
    }
    if ( $has_all_addr ) {
        $params['owner_address']        = $opts['mini_gdpr_owner_address'];
        $params['owner_address_number'] = $opts['mini_gdpr_owner_number'];
        $params['owner_city']           = $opts['mini_gdpr_owner_city'];
        $params['owner_province']       = $opts['mini_gdpr_owner_province'];
        $params['owner_cap']            = $opts['mini_gdpr_owner_cap'];
        $params['owner_country']        = $opts['mini_gdpr_owner_country'];
    }

    if ( ! empty( $opts['mini_gdpr_owner_email'] ) ) {
        $params['owner_email'] = $opts['mini_gdpr_owner_email'];
    }
    if ( ! empty( $opts['mini_gdpr_owner_pec'] ) ) {
        $params['owner_pec'] = $opts['mini_gdpr_owner_pec'];
    }
    if ( ! empty( $opts['mini_gdpr_dpo'] ) ) {
        $params['dpo'] = $opts['mini_gdpr_dpo'];
    }
    if ( ! empty( $opts['mini_gdpr_dpo_email'] ) ) {
        $params['dpo_email'] = $opts['mini_gdpr_dpo_email'];
    }

    $url = add_query_arg( $params, 'https://api.uwa.agency/privacy-policy/' );

    $response = wp_remote_get( $url, [
        'timeout' => 15,
    ] );

    if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
        add_settings_error(
            'mini_messages',
            'mini_gdpr_api_error',
            __( 'GDPR: Could not fetch the privacy policy content from the API. Please try again.', 'mini' ),
            'error'
        );
        return 0;
    }

    $body    = wp_remote_retrieve_body( $response );
    $content = wp_kses_post( $body );
    $title   = __( 'Privacy Policy', 'mini' );

    $page_id = absint( $opts['mini_gdpr_privacy_page_id'] ?? 0 );

    // Update existing page if we already created one, otherwise create new
    if ( $page_id && get_post( $page_id ) ) {
        wp_update_post( [
            'ID'           => $page_id,
            'post_content' => $content,
            'post_title'   => $title,
            'post_status'  => 'publish',
        ] );
        return $page_id;
    }

    // Check if WordPress already has a designated privacy policy page
    $wp_privacy_page_id = (int) get_option( 'wp_page_for_privacy_policy' );
    if ( $wp_privacy_page_id && get_post( $wp_privacy_page_id ) ) {
        wp_update_post( [
            'ID'           => $wp_privacy_page_id,
            'post_content' => $content,
            'post_title'   => $title,
            'post_status'  => 'publish',
        ] );
        return $wp_privacy_page_id;
    }

    $new_id = wp_insert_post( [
        'post_title'   => $title,
        'post_content' => $content,
        'post_status'  => 'publish',
        'post_type'    => 'page',
    ] );

    if ( $new_id && ! is_wp_error( $new_id ) ) {
        // Register it as WordPress's official privacy policy page
        update_option( 'wp_page_for_privacy_policy', $new_id );
        return $new_id;
    }

    return 0;
}

function mini_gdpr_privacy_section_callback( $args ) {
    $opts    = get_option( 'mini_gdpr_privacy_settings', [] );
    $enabled = ! empty( $opts['mini_gdpr_privacy_enabled'] );
    $page_id = absint( $opts['mini_gdpr_privacy_page_id'] ?? 0 );

    $f = function( $key ) use ( $opts ) {
        return esc_attr( $opts[ $key ] ?? '' );
    };
    ?>
    <p id="<?php echo esc_attr( $args['id'] ); ?>" class="grey-text">
        <?php esc_html_e( 'Manage your site\'s privacy policy page.', 'mini' ); ?>
    </p>
    <div class="boxes">

        <div class="box-100 p-2 white-bg b-rad-5 box-shadow">
            <h4 class=""><?php esc_html_e( 'Enable default Privacy Policy page', 'mini' ); ?></h4>
            <label for="mini_gdpr_privacy_enabled" class="black-text">
                <input type="checkbox" id="mini_gdpr_privacy_enabled" name="mini_gdpr_privacy_settings[mini_gdpr_privacy_enabled]" value="1" <?php checked( $enabled ); ?> class="me-1">
                <?php esc_html_e( 'Create and populate a Privacy Policy page using the default UWA template.', 'mini' ); ?>
            </label>
            <p class="S grey-text"><?php esc_html_e( 'Save your settings first, then click "Fetch from API" to create or refresh the page content.', 'mini' ); ?></p>

            <p class="S">
            <?php if ( $enabled && $page_id ) : ?>
                <span id="mini-gdpr-privacy-page-links">
                    <a href="<?php echo esc_url( get_permalink( $page_id ) ); ?>" target="_blank" class="btn white-text S" id="mini-gdpr-view-link"><?php echo esc_html( get_the_title( $page_id ) ); ?></a>
                    <a href="<?php echo esc_url( get_edit_post_link( $page_id ) ); ?>" target="_blank" class="btn warning-btn white-text S" id="mini-gdpr-edit-link"><?php esc_html_e( 'Edit', 'mini' ); ?></a>
                </span>
            <?php endif; ?>
                <button type="button" id="mini-gdpr-fetch-privacy" class="btn third-color-btn-invert S"><?php esc_html_e( 'Fetch from API', 'mini' ); ?></button>
                <span id="mini-gdpr-fetch-privacy-status"></span>
            </p>
            <script>
            (function(){
                document.getElementById('mini-gdpr-fetch-privacy').addEventListener('click', function() {
                    var btn    = this;
                    var status = document.getElementById('mini-gdpr-fetch-privacy-status');
                    btn.disabled = true;
                    status.textContent = '<?php echo esc_js( __( 'Fetching…', 'mini' ) ); ?>';
                    status.style.color = '';

                    var fd = new FormData();
                    fd.append('action', 'mini_gdpr_fetch_privacy_page');
                    fd.append('nonce',  '<?php echo esc_js( wp_create_nonce( 'mini_gdpr_fetch_privacy' ) ); ?>');

                    fetch('<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>', { method: 'POST', body: fd })
                        .then(function(r) { return r.json(); })
                        .then(function(data) {
                            btn.disabled = false;
                            if (data.success) {
                                status.style.color = 'green';
                                status.textContent = data.data.message;
                                // Update page links dynamically
                                var links = document.getElementById('mini-gdpr-privacy-page-links');
                                links.innerHTML =
                                    '<a href="' + data.data.view_url + '" target="_blank" class="btn white-text S" id="mini-gdpr-view-link">' + data.data.title + '</a> ' +
                                    '<a href="' + data.data.edit_url + '" class="btn warning-btn white-text S" id="mini-gdpr-edit-link"><?php echo esc_js( __( 'Edit', 'mini' ) ); ?></a>';
                            } else {
                                status.style.color = 'red';
                                status.textContent = data.data.message;
                            }
                        })
                        .catch(function() {
                            btn.disabled = false;
                            status.style.color = 'red';
                            status.textContent = '<?php echo esc_js( __( 'Request failed. Please try again.', 'mini' ) ); ?>';
                        });
                });
            })();
            </script>
        </div>

        <div class="box-33 p-2 white-bg b-rad-5 box-shadow">
            <h4 class=""><?php esc_html_e( 'Owner / Company name', 'mini' ); ?></h4>
            <input type="text" name="mini_gdpr_privacy_settings[mini_gdpr_owner]" value="<?php echo $f('mini_gdpr_owner'); ?>" placeholder="Acme S.r.l." class="regular-text">
        </div>

        <div class="box-33 p-2 white-bg b-rad-5 box-shadow">
            <h4 class=""><?php esc_html_e( 'Email', 'mini' ); ?></h4>
            <input type="email" name="mini_gdpr_privacy_settings[mini_gdpr_owner_email]" value="<?php echo $f('mini_gdpr_owner_email'); ?>" placeholder="info@example.com" class="regular-text">
            <p class="S grey-text"><?php esc_html_e( 'Used as both contact email and PEC unless PEC is set separately.', 'mini' ); ?></p>
        </div>

        <div class="box-33 p-2 white-bg b-rad-5 box-shadow">
            <h4 class=""><?php esc_html_e( 'PEC', 'mini' ); ?></h4>
            <input type="email" name="mini_gdpr_privacy_settings[mini_gdpr_owner_pec]" value="<?php echo $f('mini_gdpr_owner_pec'); ?>" placeholder="pec@pec.example.com" class="regular-text">
            <p class="S grey-text"><?php esc_html_e( 'Overrides the email for PEC-specific fields. Leave empty to use the email above.', 'mini' ); ?></p>
        </div>

        <div class="box-50 p-2 white-bg b-rad-5 box-shadow">
            <h4 class=""><?php esc_html_e( 'DPO name', 'mini' ); ?></h4>
            <input type="text" name="mini_gdpr_privacy_settings[mini_gdpr_dpo]" value="<?php echo $f('mini_gdpr_dpo'); ?>" placeholder="Mario Rossi" class="regular-text">
            <p class="S grey-text"><?php esc_html_e( 'Data Protection Officer full name. Leave empty if not applicable.', 'mini' ); ?></p>
        </div>

        <div class="box-50 p-2 white-bg b-rad-5 box-shadow">
            <h4 class=""><?php esc_html_e( 'DPO email', 'mini' ); ?></h4>
            <input type="email" name="mini_gdpr_privacy_settings[mini_gdpr_dpo_email]" value="<?php echo $f('mini_gdpr_dpo_email'); ?>" placeholder="dpo@example.com" class="regular-text">
            <p class="S grey-text"><?php esc_html_e( 'Data Protection Officer contact email.', 'mini' ); ?></p>
        </div>

        <div class="box-100 p-2 white-bg b-rad-5 box-shadow">
            <h4 class=""><?php esc_html_e( 'Address', 'mini' ); ?></h4>
            <div class="boxes">
                <div class="box-40 p-0">
                    <label class="S grey-text"><?php esc_html_e( 'Street', 'mini' ); ?></label>
                    <input type="text" name="mini_gdpr_privacy_settings[mini_gdpr_owner_address]" value="<?php echo $f('mini_gdpr_owner_address'); ?>" placeholder="Via Roma">
                </div>
                <div class="box-8 p-0">
                    <label class="S grey-text"><?php esc_html_e( 'Number', 'mini' ); ?></label>
                    <input type="text" name="mini_gdpr_privacy_settings[mini_gdpr_owner_number]" value="<?php echo $f('mini_gdpr_owner_number'); ?>" placeholder="1">
                </div>
                <div class="box-16 p-0">
                    <label class="S grey-text"><?php esc_html_e( 'City', 'mini' ); ?></label>
                    <input type="text" name="mini_gdpr_privacy_settings[mini_gdpr_owner_city]" value="<?php echo $f('mini_gdpr_owner_city'); ?>" placeholder="Milano">
                </div>
                <div class="box-8 p-0">
                    <label class="S grey-text"><?php esc_html_e( 'Province', 'mini' ); ?></label>
                    <input type="text" name="mini_gdpr_privacy_settings[mini_gdpr_owner_province]" value="<?php echo $f('mini_gdpr_owner_province'); ?>" placeholder="MI">
                </div>
                <div class="box-8 p-0">
                    <label class="S grey-text"><?php esc_html_e( 'CAP', 'mini' ); ?></label>
                    <input type="text" name="mini_gdpr_privacy_settings[mini_gdpr_owner_cap]" value="<?php echo $f('mini_gdpr_owner_cap'); ?>" placeholder="20100">
                </div>
                <div class="box-16 p-0">
                    <label class="S grey-text"><?php esc_html_e( 'Country', 'mini' ); ?></label>
                    <input type="text" name="mini_gdpr_privacy_settings[mini_gdpr_owner_country]" value="<?php echo $f('mini_gdpr_owner_country'); ?>" placeholder="Italy">
                </div>
            </div>
            <p class="S grey-text"><?php esc_html_e( 'All address fields must be filled for the address to appear in the policy.', 'mini' ); ?></p>
        </div>

    </div>
    <?php
}

/* END - GDPR settings */

/* START - GDPR Cookie Policy settings */

function mini_gdpr_cookie_settings_init() {
    register_setting( 'mini_gdpr_cookie', 'mini_gdpr_cookie_settings', [
        'sanitize_callback' => 'mini_gdpr_cookie_sanitize_settings',
    ] );
    add_settings_section(
        'mini_gdpr_cookie_section',
        __( 'Cookie Policy page', 'mini' ),
        'mini_gdpr_cookie_section_callback',
        'mini-gdpr-cookie'
    );
}
add_action( 'admin_init', 'mini_gdpr_cookie_settings_init' );

function mini_gdpr_cookie_sanitize_settings( $input ) {
    $prev      = get_option( 'mini_gdpr_cookie_settings', [] );
    $sanitized = [];

    $sanitized['mini_gdpr_cookie_enabled'] = ! empty( $input['mini_gdpr_cookie_enabled'] ) ? 1 : 0;
    $sanitized['mini_gdpr_cookie_page_id'] = absint( $prev['mini_gdpr_cookie_page_id'] ?? 0 );

    $sanitized['mini_gdpr_cookies'] = [];
    if ( ! empty( $input['mini_gdpr_cookies'] ) && is_array( $input['mini_gdpr_cookies'] ) ) {
        $allowed_types = [ 'necessary', 'functional', 'analytics', 'marketing' ];
        foreach ( $input['mini_gdpr_cookies'] as $cookie ) {
            $name     = sanitize_text_field( $cookie['name'] ?? '' );
            if ( $name === '' ) continue;
            $type     = in_array( $cookie['type'] ?? '', $allowed_types, true ) ? $cookie['type'] : 'necessary';
            $provider = sanitize_text_field( $cookie['provider'] ?? '' );
            $purpose  = sanitize_text_field( $cookie['purpose'] ?? '' );
            $duration = sanitize_text_field( $cookie['duration'] ?? '' );
            $sanitized['mini_gdpr_cookies'][] = compact( 'name', 'type', 'provider', 'purpose', 'duration' );
        }
    }

    return $sanitized;
}

function mini_gdpr_ajax_fetch_cookie_page() {
    check_ajax_referer( 'mini_gdpr_fetch_cookie', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( [ 'message' => __( 'Permission denied.', 'mini' ) ] );
    }

    $opts    = get_option( 'mini_gdpr_cookie_settings', [] );
    $page_id = mini_gdpr_create_cookie_page( $opts );

    if ( $page_id ) {
        $opts['mini_gdpr_cookie_page_id'] = $page_id;
        update_option( 'mini_gdpr_cookie_settings', $opts );
        wp_send_json_success( [
            'message'  => __( 'Cookie Policy page updated.', 'mini' ),
            'page_id'  => $page_id,
            'view_url' => get_permalink( $page_id ),
            'edit_url' => get_edit_post_link( $page_id ),
            'title'    => get_the_title( $page_id ),
        ] );
    } else {
        wp_send_json_error( [ 'message' => __( 'Could not fetch the cookie policy content from the API. Please try again.', 'mini' ) ] );
    }
}
add_action( 'wp_ajax_mini_gdpr_fetch_cookie_page', 'mini_gdpr_ajax_fetch_cookie_page' );

function mini_gdpr_create_cookie_page( $opts ) {
    $cookies = $opts['mini_gdpr_cookies'] ?? [];

    // Fetch intro content from API, passing the same owner params as the privacy policy
    $privacy_opts = get_option( 'mini_gdpr_privacy_settings', [] );
    $params = [];

    if ( ! empty( $privacy_opts['mini_gdpr_owner'] ) ) {
        $params['owner'] = $privacy_opts['mini_gdpr_owner'];
    }

    $addr_fields = [ 'mini_gdpr_owner_address', 'mini_gdpr_owner_number', 'mini_gdpr_owner_city', 'mini_gdpr_owner_province', 'mini_gdpr_owner_cap', 'mini_gdpr_owner_country' ];
    $has_all_addr = true;
    foreach ( $addr_fields as $f ) {
        if ( empty( $privacy_opts[ $f ] ) ) { $has_all_addr = false; break; }
    }
    if ( $has_all_addr ) {
        $params['owner_address']        = $privacy_opts['mini_gdpr_owner_address'];
        $params['owner_address_number'] = $privacy_opts['mini_gdpr_owner_number'];
        $params['owner_city']           = $privacy_opts['mini_gdpr_owner_city'];
        $params['owner_province']       = $privacy_opts['mini_gdpr_owner_province'];
        $params['owner_cap']            = $privacy_opts['mini_gdpr_owner_cap'];
        $params['owner_country']        = $privacy_opts['mini_gdpr_owner_country'];
    }

    if ( ! empty( $privacy_opts['mini_gdpr_owner_email'] ) ) {
        $params['owner_email'] = $privacy_opts['mini_gdpr_owner_email'];
    }
    if ( ! empty( $privacy_opts['mini_gdpr_owner_pec'] ) ) {
        $params['owner_pec'] = $privacy_opts['mini_gdpr_owner_pec'];
    }
    if ( ! empty( $privacy_opts['mini_gdpr_dpo'] ) ) {
        $params['dpo'] = $privacy_opts['mini_gdpr_dpo'];
    }
    if ( ! empty( $privacy_opts['mini_gdpr_dpo_email'] ) ) {
        $params['dpo_email'] = $privacy_opts['mini_gdpr_dpo_email'];
    }

    $api_url  = add_query_arg( $params, 'https://api.uwa.agency/cookie-policy/' );
    $response = wp_remote_get( $api_url, [ 'timeout' => 15, 'sslverify' => true ] );

    if ( is_wp_error( $response ) ) {
        add_settings_error(
            'mini_messages',
            'mini_gdpr_cookie_api_error',
            sprintf(
                /* translators: %s: error message */
                __( 'GDPR: Could not fetch the cookie policy intro from the API (%s). Please try again.', 'mini' ),
                $response->get_error_message()
            ),
            'error'
        );
        return 0;
    }

    $http_code = (int) wp_remote_retrieve_response_code( $response );
    if ( $http_code !== 200 ) {
        add_settings_error(
            'mini_messages',
            'mini_gdpr_cookie_api_error',
            sprintf(
                /* translators: %d: HTTP status code */
                __( 'GDPR: Cookie policy API returned HTTP %d. Please try again.', 'mini' ),
                $http_code
            ),
            'error'
        );
        return 0;
    }

    $api_body = wp_remote_retrieve_body( $response );
    $content  = wp_kses_post( $api_body );

    $types = [
        'necessary'  => 'Necessari',
        'functional' => 'Funzionali',
        'analytics'  => 'Analitici',
        'marketing'  => 'Marketing',
    ];

    $type_descriptions = [
        'necessary'  => 'I cookie necessari sono indispensabili per il corretto funzionamento del sito web e non possono essere disabilitati.',
        'functional' => 'I cookie funzionali migliorano le funzionalità e la personalizzazione del sito. Possono essere impostati da noi o da fornitori terzi.',
        'analytics'  => 'I cookie analitici ci aiutano a capire come i visitatori interagiscono con il sito, raccogliendo informazioni in forma anonima.',
        'marketing'  => 'I cookie di marketing vengono utilizzati per tracciare i visitatori sui siti web al fine di mostrare annunci pubblicitari pertinenti e mirati.',
    ];

    $content .= '<h2 class="">I cookie presenti sul sito</h2>';
    $content .= '<div class="space-2"></div>';
    
    foreach ( $types as $key => $label ) {
        $group = array_values( array_filter( $cookies, fn( $c ) => ( $c['type'] ?? '' ) === $key ) );
        if ( empty( $group ) ) continue;

        $content .= '<h3>' . esc_html( $label ) . '</h3>';
        $content .= '<p>' . esc_html( $type_descriptions[ $key ] ) . '</p>';
        $content .= '<table style="width:100%;border-collapse:collapse;">';
        $content .= '<thead><tr>';
        $content .= '<th style="text-align:left;padding:8px;border-bottom:2px solid #ddd;">Nome</th>';
        $content .= '<th style="text-align:left;padding:8px;border-bottom:2px solid #ddd;">Fornitore</th>';
        $content .= '<th style="text-align:left;padding:8px;border-bottom:2px solid #ddd;">Finalità</th>';
        $content .= '<th style="text-align:left;padding:8px;border-bottom:2px solid #ddd;">Durata</th>';
        $content .= '</tr></thead><tbody>';

        foreach ( $group as $i => $cookie ) {
            $bg = $i % 2 === 0 ? '#f9f9f9' : '#fff';
            $content .= '<tr style="background:' . $bg . ';">';
            $content .= '<td style="padding:8px;border-bottom:1px solid #eee;font-weight:bold;">' . esc_html( $cookie['name'] ) . '</td>';
            $content .= '<td style="padding:8px;border-bottom:1px solid #eee;">' . esc_html( $cookie['provider'] ) . '</td>';
            $content .= '<td style="padding:8px;border-bottom:1px solid #eee;">' . esc_html( $cookie['purpose'] ) . '</td>';
            $content .= '<td style="padding:8px;border-bottom:1px solid #eee;">' . esc_html( $cookie['duration'] ) . '</td>';
            $content .= '</tr>';
        }
        $content .= '</tbody></table>';
        $content .= '<div class="space-5"></div>';
    }

    $title   = __( 'Cookie Policy', 'mini' );
    $page_id = absint( $opts['mini_gdpr_cookie_page_id'] ?? 0 );

    if ( $page_id && get_post( $page_id ) ) {
        wp_update_post( [
            'ID'           => $page_id,
            'post_content' => $content,
            'post_title'   => $title,
            'post_status'  => 'publish',
        ] );
        return $page_id;
    }

    $new_id = wp_insert_post( [
        'post_title'   => $title,
        'post_content' => $content,
        'post_status'  => 'publish',
        'post_type'    => 'page',
    ] );

    return ( $new_id && ! is_wp_error( $new_id ) ) ? $new_id : 0;
}

function mini_gdpr_known_cookies() {
    return [
        // Google Analytics
        '_ga'                         => [ 'type' => 'analytics',  'provider' => 'Google Analytics', 'purpose' => 'Registers a unique ID used to generate statistical data on how the visitor uses the website.', 'duration' => '2 years' ],
        '_gid'                        => [ 'type' => 'analytics',  'provider' => 'Google Analytics', 'purpose' => 'Registers a unique ID used to generate statistical data on how the visitor uses the website.', 'duration' => '24 hours' ],
        '_gat'                        => [ 'type' => 'analytics',  'provider' => 'Google Analytics', 'purpose' => 'Used to throttle request rate.', 'duration' => '1 minute' ],
        '_ga_'                        => [ 'type' => 'analytics',  'provider' => 'Google Analytics', 'purpose' => 'Used by Google Analytics 4 to persist session state.', 'duration' => '2 years' ],
        '_gat_gtag_'                  => [ 'type' => 'analytics',  'provider' => 'Google Tag Manager', 'purpose' => 'Used to throttle request rate.', 'duration' => '1 minute' ],
        '_gcl_au'                     => [ 'type' => 'marketing',  'provider' => 'Google Ads',       'purpose' => 'Used by Google AdSense to experiment with advertisement efficiency.', 'duration' => '3 months' ],
        '__utma'                      => [ 'type' => 'analytics',  'provider' => 'Google Analytics', 'purpose' => 'Calculates visitor, session and campaign data.', 'duration' => '2 years' ],
        '__utmb'                      => [ 'type' => 'analytics',  'provider' => 'Google Analytics', 'purpose' => 'Registers a timestamp of when the visitor entered the website.', 'duration' => '30 minutes' ],
        '__utmc'                      => [ 'type' => 'analytics',  'provider' => 'Google Analytics', 'purpose' => 'Registers a timestamp of when the visitor left the website.', 'duration' => 'Session' ],
        '__utmt'                      => [ 'type' => 'analytics',  'provider' => 'Google Analytics', 'purpose' => 'Used to throttle request rate.', 'duration' => '10 minutes' ],
        '__utmz'                      => [ 'type' => 'analytics',  'provider' => 'Google Analytics', 'purpose' => 'Stores the traffic source or campaign that explains how the visitor reached the site.', 'duration' => '6 months' ],
        // WordPress core (admin_only = true means they are never shown in the auto-detect panel
        // because they only appear for logged-in admins, not regular visitors)
        'wordpress_logged_in_'        => [ 'type' => 'necessary',  'provider' => 'WordPress', 'purpose' => 'Keeps the user logged in to the website.', 'duration' => 'Session',  'admin_only' => true ],
        'wordpress_sec_'              => [ 'type' => 'necessary',  'provider' => 'WordPress', 'purpose' => 'Secures the user login.', 'duration' => 'Session',  'admin_only' => true ],
        'wp-settings-'                => [ 'type' => 'functional', 'provider' => 'WordPress', 'purpose' => 'Stores user preferences such as whether to show the admin toolbar.', 'duration' => '1 year', 'admin_only' => true ],
        'wp-settings-time-'           => [ 'type' => 'functional', 'provider' => 'WordPress', 'purpose' => 'Stores the time user preferences were saved.', 'duration' => '1 year', 'admin_only' => true ],
        'wordpressuser_'              => [ 'type' => 'necessary',  'provider' => 'WordPress', 'purpose' => 'Saves the username to make login easier.', 'duration' => '1 year', 'admin_only' => true ],
        'wordpresspass_'              => [ 'type' => 'necessary',  'provider' => 'WordPress', 'purpose' => 'Saves the login hash to keep the user logged in.', 'duration' => '1 year', 'admin_only' => true ],
        'wordpress_test_cookie'       => [ 'type' => 'necessary',  'provider' => 'WordPress', 'purpose' => 'Checks if cookies are enabled in the browser.', 'duration' => 'Session', 'admin_only' => true ],
        // PHP / Generic
        'PHPSESSID'                   => [ 'type' => 'necessary',  'provider' => 'Website', 'purpose' => 'Preserves user session state across page requests.', 'duration' => 'Session' ],
        // Cookie consent
        'cookie_notice_accepted'      => [ 'type' => 'necessary',  'provider' => 'Website', 'purpose' => "Stores the user's cookie consent status.", 'duration' => '1 year' ],
        'cookie_law_info_bar_ppd'     => [ 'type' => 'necessary',  'provider' => 'Cookie Law Info', 'purpose' => "Stores the user's cookie consent preferences.", 'duration' => '1 year' ],
        'viewed_cookie_policy'        => [ 'type' => 'necessary',  'provider' => 'Website', 'purpose' => "Records that the user has viewed the cookie policy.", 'duration' => '1 year' ],
        // Meta / Facebook
        '_fbp'                        => [ 'type' => 'marketing',  'provider' => 'Meta (Facebook)', 'purpose' => 'Used by Facebook to deliver advertisement products.', 'duration' => '3 months' ],
        '_fbc'                        => [ 'type' => 'marketing',  'provider' => 'Meta (Facebook)', 'purpose' => 'Stores the last Facebook ad clicked by the user.', 'duration' => '2 years' ],
        'fr'                          => [ 'type' => 'marketing',  'provider' => 'Meta (Facebook)', 'purpose' => 'Used by Facebook to deliver, measure and improve the relevancy of ads.', 'duration' => '3 months' ],
        // Hotjar
        '_hjid'                       => [ 'type' => 'analytics',  'provider' => 'Hotjar', 'purpose' => 'Sets a unique ID for the session to track user behaviour.', 'duration' => '1 year' ],
        '_hjFirstSeen'                => [ 'type' => 'analytics',  'provider' => 'Hotjar', 'purpose' => "Identifies a new user's first session.", 'duration' => 'Session' ],
        '_hjSession_'                 => [ 'type' => 'analytics',  'provider' => 'Hotjar', 'purpose' => 'Holds current session data for Hotjar.', 'duration' => '30 minutes' ],
        '_hjSessionUser_'             => [ 'type' => 'analytics',  'provider' => 'Hotjar', 'purpose' => 'Ensures data from subsequent visits to the same site are attributed to the same user ID.', 'duration' => '1 year' ],
        // Cloudflare
        'cf_clearance'                => [ 'type' => 'necessary',  'provider' => 'Cloudflare', 'purpose' => 'Used by Cloudflare to bypass the bot-check security challenge.', 'duration' => '1 year' ],
        '__cf_bm'                     => [ 'type' => 'necessary',  'provider' => 'Cloudflare', 'purpose' => 'Used by Cloudflare to identify trusted web traffic.', 'duration' => '30 minutes' ],
        // WooCommerce
        'woocommerce_cart_hash'       => [ 'type' => 'necessary',  'provider' => 'WooCommerce', 'purpose' => 'Helps WooCommerce determine when cart contents change.', 'duration' => 'Session' ],
        'woocommerce_items_in_cart'   => [ 'type' => 'necessary',  'provider' => 'WooCommerce', 'purpose' => 'Helps WooCommerce determine when cart contents change.', 'duration' => 'Session' ],
        'wp_woocommerce_session_'     => [ 'type' => 'necessary',  'provider' => 'WooCommerce', 'purpose' => 'Contains a unique code for each customer so that cart data is maintained between pages.', 'duration' => '2 days' ],
        'woocommerce_recently_viewed' => [ 'type' => 'functional', 'provider' => 'WooCommerce', 'purpose' => 'Stores products the user has recently viewed.', 'duration' => 'Session' ],
        // Intercom (admin_only: injected by hosting panels like Hostinger, not by the site itself)
        'intercom-id-'                => [ 'type' => 'functional', 'provider' => 'Intercom', 'purpose' => 'Stores a unique anonymous visitor identifier for Intercom chat and CRM.', 'duration' => '9 months', 'admin_only' => true ],
        'intercom-device-id-'         => [ 'type' => 'functional', 'provider' => 'Intercom', 'purpose' => 'Stores a unique device identifier used by Intercom to link conversations across sessions.', 'duration' => '9 months', 'admin_only' => true ],
        'intercom-session-'           => [ 'type' => 'functional', 'provider' => 'Intercom', 'purpose' => 'Stores the active Intercom session token.', 'duration' => '1 week', 'admin_only' => true ],
    ];
}

function mini_gdpr_lookup_known_cookie( $name ) {
    $db = mini_gdpr_known_cookies();
    // Exact match first
    if ( isset( $db[ $name ] ) ) {
        return array_merge( [ 'name' => $name ], $db[ $name ] );
    }
    // Prefix match: keys ending in '_' or '-' are treated as prefixes.
    // Use the prefix key as the canonical name so e.g. all _ga_XXXX
    // cookies collapse into a single _ga_ entry.
    foreach ( $db as $key => $data ) {
        $last = substr( $key, -1 );
        if ( ( $last === '_' || $last === '-' ) && strpos( $name, $key ) === 0 ) {
            return array_merge( [ 'name' => $key ], $data );
        }
    }
    return null;
}

function mini_gdpr_cookie_section_callback( $args ) {
    $opts    = get_option( 'mini_gdpr_cookie_settings', [] );
    $enabled = ! empty( $opts['mini_gdpr_cookie_enabled'] );
    $page_id = absint( $opts['mini_gdpr_cookie_page_id'] ?? 0 );
    $cookies = $opts['mini_gdpr_cookies'] ?? [];

    $types = [
        'necessary'  => __( 'Necessary', 'mini' ),
        'functional' => __( 'Functional', 'mini' ),
        'analytics'  => __( 'Analytics', 'mini' ),
        'marketing'  => __( 'Marketing', 'mini' ),
    ];

    // Auto-detect: read $_COOKIE, exclude ones already in the list
    $configured_names = array_map( fn( $c ) => $c['name'] ?? '', $cookies );
    $detected      = [];
    $detected_seen = []; // tracks canonical names already queued
    foreach ( array_keys( $_COOKIE ) as $cookie_name ) {
        $cookie_name = sanitize_text_field( $cookie_name );
        // Skip if exact name already configured
        if ( in_array( $cookie_name, $configured_names, true ) ) continue;
        $known     = mini_gdpr_lookup_known_cookie( $cookie_name );
        // Skip cookies that only appear for logged-in admins or hosting panel tools
        if ( $known && ! empty( $known['admin_only'] ) ) continue;
        $canonical = $known ? $known['name'] : $cookie_name;
        // Skip if the canonical/prefix name is already configured
        if ( in_array( $canonical, $configured_names, true ) ) continue;
        // Deduplicate: multiple _ga_XXXX cookies → one _ga_ row
        if ( isset( $detected_seen[ $canonical ] ) ) continue;
        $detected_seen[ $canonical ] = true;
        $detected[] = $known ?? [
            'name'     => $cookie_name,
            'type'     => 'necessary',
            'provider' => '',
            'purpose'  => '',
            'duration' => '',
        ];
    }
    ?>
    <p id="<?php echo esc_attr( $args['id'] ); ?>" class="grey-text">
        <?php esc_html_e( 'Manage your site\'s cookie policy page.', 'mini' ); ?>
    </p>
    <div class="boxes">

        <div class="box-100 p-2 white-bg b-rad-5 box-shadow">
            <h4><?php esc_html_e( 'Enable Cookie Policy page', 'mini' ); ?></h4>
            <label for="mini_gdpr_cookie_enabled" class="black-text">
                <input type="checkbox" id="mini_gdpr_cookie_enabled" name="mini_gdpr_cookie_settings[mini_gdpr_cookie_enabled]" value="1" <?php checked( $enabled ); ?> class="me-1">
                <?php esc_html_e( 'Create and populate a Cookie Policy page from the list below.', 'mini' ); ?>
            </label>
            <p class="S grey-text"><?php esc_html_e( 'Save your settings first, then click "Fetch from API" to create or refresh the page content.', 'mini' ); ?></p>
            <p class="S" >
            <?php if ( $enabled && $page_id ) : ?>
                <span id="mini-gdpr-cookie-page-links">
                    <a href="<?php echo esc_url( get_permalink( $page_id ) ); ?>" target="_blank" class="btn white-text S" id="mini-gdpr-cookie-view-link"><?php echo esc_html( get_the_title( $page_id ) ); ?></a>
                    <a href="<?php echo esc_url( get_edit_post_link( $page_id ) ); ?>" class="btn warning-btn white-text S" id="mini-gdpr-cookie-edit-link"><?php esc_html_e( 'Edit', 'mini' ); ?></a>
                </span>
            <?php endif; ?>
                <button type="button" id="mini-gdpr-fetch-cookie" class="btn third-color-btn-invert S"><?php esc_html_e( 'Fetch from API', 'mini' ); ?></button>
                <span id="mini-gdpr-fetch-cookie-status" style="margin-left:10px;vertical-align:middle;"></span>
            </p>
            <script>
            (function(){
                document.getElementById('mini-gdpr-fetch-cookie').addEventListener('click', function() {
                    var btn    = this;
                    var status = document.getElementById('mini-gdpr-fetch-cookie-status');
                    btn.disabled = true;
                    status.textContent = '<?php echo esc_js( __( 'Fetching…', 'mini' ) ); ?>';
                    status.style.color = '';

                    var fd = new FormData();
                    fd.append('action', 'mini_gdpr_fetch_cookie_page');
                    fd.append('nonce',  '<?php echo esc_js( wp_create_nonce( 'mini_gdpr_fetch_cookie' ) ); ?>');

                    fetch('<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>', { method: 'POST', body: fd })
                        .then(function(r) { return r.json(); })
                        .then(function(data) {
                            btn.disabled = false;
                            if (data.success) {
                                status.style.color = 'green';
                                status.textContent = data.data.message;
                                var links = document.getElementById('mini-gdpr-cookie-page-links');
                                links.innerHTML =
                                    '<a href="' + data.data.view_url + '" target="_blank" class="btn white-text S">' + data.data.title + '</a> ' +
                                    '<a href="' + data.data.edit_url + '" class="btn warning-btn white-text S"><?php echo esc_js( __( 'Edit', 'mini' ) ); ?></a>';
                            } else {
                                status.style.color = 'red';
                                status.textContent = data.data.message;
                            }
                        })
                        .catch(function() {
                            btn.disabled = false;
                            status.style.color = 'red';
                            status.textContent = '<?php echo esc_js( __( 'Request failed. Please try again.', 'mini' ) ); ?>';
                        });
                });
            })();
            </script>
        </div>

        <?php if ( ! empty( $detected ) ) : ?>
        <div class="box-100 p-2 white-bg b-rad-5 box-shadow">
            <h4><?php esc_html_e( 'Detected cookies not yet in your list', 'mini' ); ?></h4>
            <p class="S grey-text"><?php esc_html_e( 'These cookies were found in the current browser session. Click "Add" to append them to your list below, then review and save.', 'mini' ); ?></p>
            <table style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr>
                        <th style="text-align:left;padding:6px 8px;width:18%;"><?php esc_html_e( 'Name', 'mini' ); ?></th>
                        <th style="text-align:left;padding:6px 8px;width:14%;"><?php esc_html_e( 'Type', 'mini' ); ?></th>
                        <th style="text-align:left;padding:6px 8px;width:18%;"><?php esc_html_e( 'Provider', 'mini' ); ?></th>
                        <th style="text-align:left;padding:6px 8px;"><?php esc_html_e( 'Purpose', 'mini' ); ?></th>
                        <th style="text-align:left;padding:6px 8px;width:13%;"><?php esc_html_e( 'Duration', 'mini' ); ?></th>
                        <th style="width:80px;"></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ( $detected as $d ) : ?>
                    <tr id="mini-detected-<?php echo esc_attr( $d['name'] ); ?>">
                        <td style="padding:6px 8px;font-weight:bold;"><?php echo esc_html( $d['name'] ); ?></td>
                        <td style="padding:6px 8px;"><?php echo esc_html( $types[ $d['type'] ] ?? $d['type'] ); ?></td>
                        <td style="padding:6px 8px;"><?php echo esc_html( $d['provider'] ); ?></td>
                        <td style="padding:6px 8px;"><?php echo esc_html( $d['purpose'] ); ?></td>
                        <td style="padding:6px 8px;"><?php echo esc_html( $d['duration'] ); ?></td>
                        <td style="padding:6px 8px;">
                            <button type="button" class="button button-primary mini-cookie-detect-add"
                                data-name="<?php echo esc_attr( $d['name'] ); ?>"
                                data-type="<?php echo esc_attr( $d['type'] ); ?>"
                                data-provider="<?php echo esc_attr( $d['provider'] ); ?>"
                                data-purpose="<?php echo esc_attr( $d['purpose'] ); ?>"
                                data-duration="<?php echo esc_attr( $d['duration'] ); ?>"
                            ><?php esc_html_e( 'Add', 'mini' ); ?></button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <div style="margin-top:10px;">
                <button type="button" id="mini-cookie-add-all-detected" class="button"><?php esc_html_e( 'Add all', 'mini' ); ?></button>
            </div>
        </div>
        <?php endif; ?>

        <div class="box-100 p-2 white-bg b-rad-5 box-shadow">
            <h4><?php esc_html_e( 'Cookies list', 'mini' ); ?></h4>
            <p class="S grey-text"><?php esc_html_e( 'Add all cookies used by your website. Rows with an empty name will be discarded.', 'mini' ); ?></p>

            <table id="mini-cookie-table" style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr>
                        <th style="text-align:left;padding:6px 8px;width:18%;"><?php esc_html_e( 'Name', 'mini' ); ?></th>
                        <th style="text-align:left;padding:6px 8px;width:14%;"><?php esc_html_e( 'Type', 'mini' ); ?></th>
                        <th style="text-align:left;padding:6px 8px;width:18%;"><?php esc_html_e( 'Provider', 'mini' ); ?></th>
                        <th style="text-align:left;padding:6px 8px;"><?php esc_html_e( 'Purpose', 'mini' ); ?></th>
                        <th style="text-align:left;padding:6px 8px;width:13%;"><?php esc_html_e( 'Duration', 'mini' ); ?></th>
                        <th style="width:40px;"></th>
                    </tr>
                </thead>
                <tbody id="mini-cookie-rows">
                <?php foreach ( $cookies as $i => $cookie ) : ?>
                    <tr>
                        <td style="padding:4px 8px;"><input type="text" name="mini_gdpr_cookie_settings[mini_gdpr_cookies][<?php echo $i; ?>][name]" value="<?php echo esc_attr( $cookie['name'] ); ?>" placeholder="_ga" style="width:100%;"></td>
                        <td style="padding:4px 8px;">
                            <select name="mini_gdpr_cookie_settings[mini_gdpr_cookies][<?php echo $i; ?>][type]" style="width:100%;">
                                <?php foreach ( $types as $key => $label ) : ?>
                                <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $cookie['type'] ?? '', $key ); ?>><?php echo esc_html( $label ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td style="padding:4px 8px;"><input type="text" name="mini_gdpr_cookie_settings[mini_gdpr_cookies][<?php echo $i; ?>][provider]" value="<?php echo esc_attr( $cookie['provider'] ); ?>" placeholder="Google" style="width:100%;"></td>
                        <td style="padding:4px 8px;"><input type="text" name="mini_gdpr_cookie_settings[mini_gdpr_cookies][<?php echo $i; ?>][purpose]" value="<?php echo esc_attr( $cookie['purpose'] ); ?>" placeholder="<?php esc_attr_e( 'Tracks unique visitors', 'mini' ); ?>" style="width:100%;"></td>
                        <td style="padding:4px 8px;"><input type="text" name="mini_gdpr_cookie_settings[mini_gdpr_cookies][<?php echo $i; ?>][duration]" value="<?php echo esc_attr( $cookie['duration'] ); ?>" placeholder="2 years" style="width:100%;"></td>
                        <td style="padding:4px 8px;"><button type="button" class="button mini-cookie-remove" title="<?php esc_attr_e( 'Remove', 'mini' ); ?>" style="color:#b00;">&times;</button></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <div style="margin-top:12px;">
                <button type="button" id="mini-cookie-add" class="button"><?php esc_html_e( '+ Add cookie', 'mini' ); ?></button>
            </div>
        </div>

    </div>

    <script>
    (function() {
        var tbody  = document.getElementById('mini-cookie-rows');
        var addBtn = document.getElementById('mini-cookie-add');
        var types  = <?php echo wp_json_encode( $types ); ?>;

        function buildOptions(selected) {
            return Object.entries(types).map(function(entry) {
                var val = entry[0], label = entry[1];
                return '<option value="' + val + '"' + (val === selected ? ' selected' : '') + '>' + label + '</option>';
            }).join('');
        }

        function reindex() {
            Array.from(tbody.querySelectorAll('tr')).forEach(function(tr, i) {
                tr.querySelectorAll('input, select').forEach(function(el) {
                    el.name = el.name.replace(/\[\d+\]/, '[' + i + ']');
                });
            });
        }

        addBtn.addEventListener('click', function() {
            var idx = tbody.querySelectorAll('tr').length;
            var tr  = document.createElement('tr');
            tr.innerHTML =
                '<td style="padding:4px 8px;"><input type="text" name="mini_gdpr_cookie_settings[mini_gdpr_cookies][' + idx + '][name]" value="" placeholder="_ga" style="width:100%;"></td>' +
                '<td style="padding:4px 8px;"><select name="mini_gdpr_cookie_settings[mini_gdpr_cookies][' + idx + '][type]" style="width:100%;">' + buildOptions('necessary') + '</select></td>' +
                '<td style="padding:4px 8px;"><input type="text" name="mini_gdpr_cookie_settings[mini_gdpr_cookies][' + idx + '][provider]" value="" placeholder="Google" style="width:100%;"></td>' +
                '<td style="padding:4px 8px;"><input type="text" name="mini_gdpr_cookie_settings[mini_gdpr_cookies][' + idx + '][purpose]" value="" placeholder="<?php echo esc_js( __( 'Tracks unique visitors', 'mini' ) ); ?>" style="width:100%;"></td>' +
                '<td style="padding:4px 8px;"><input type="text" name="mini_gdpr_cookie_settings[mini_gdpr_cookies][' + idx + '][duration]" value="" placeholder="2 years" style="width:100%;"></td>' +
                '<td style="padding:4px 8px;"><button type="button" class="button mini-cookie-remove" title="<?php echo esc_js( __( 'Remove', 'mini' ) ); ?>" style="color:#b00;">&times;</button></td>';
            tbody.appendChild(tr);
        });

        tbody.addEventListener('click', function(e) {
            if (e.target.classList.contains('mini-cookie-remove')) {
                e.target.closest('tr').remove();
                reindex();
            }
        });

        function addDetectedCookie(btn) {
            var name     = btn.dataset.name;
            var type     = btn.dataset.type;
            var provider = btn.dataset.provider;
            var purpose  = btn.dataset.purpose;
            var duration = btn.dataset.duration;
            var idx      = tbody.querySelectorAll('tr').length;
            var tr       = document.createElement('tr');
            tr.innerHTML =
                '<td style="padding:4px 8px;"><input type="text" name="mini_gdpr_cookie_settings[mini_gdpr_cookies][' + idx + '][name]" value="' + name.replace(/"/g,'&quot;') + '" placeholder="_ga" style="width:100%;"></td>' +
                '<td style="padding:4px 8px;"><select name="mini_gdpr_cookie_settings[mini_gdpr_cookies][' + idx + '][type]" style="width:100%;">' + buildOptions(type) + '</select></td>' +
                '<td style="padding:4px 8px;"><input type="text" name="mini_gdpr_cookie_settings[mini_gdpr_cookies][' + idx + '][provider]" value="' + provider.replace(/"/g,'&quot;') + '" placeholder="Google" style="width:100%;"></td>' +
                '<td style="padding:4px 8px;"><input type="text" name="mini_gdpr_cookie_settings[mini_gdpr_cookies][' + idx + '][purpose]" value="' + purpose.replace(/"/g,'&quot;') + '" placeholder="<?php echo esc_js( __( 'Tracks unique visitors', 'mini' ) ); ?>" style="width:100%;"></td>' +
                '<td style="padding:4px 8px;"><input type="text" name="mini_gdpr_cookie_settings[mini_gdpr_cookies][' + idx + '][duration]" value="' + duration.replace(/"/g,'&quot;') + '" placeholder="2 years" style="width:100%;"></td>' +
                '<td style="padding:4px 8px;"><button type="button" class="button mini-cookie-remove" title="<?php echo esc_js( __( 'Remove', 'mini' ) ); ?>" style="color:#b00;">&times;</button></td>';
            tbody.appendChild(tr);
            // Hide the detected row and disable its button
            var detectedRow = document.getElementById('mini-detected-' + name);
            if (detectedRow) detectedRow.style.display = 'none';
            btn.disabled = true;
        }

        document.querySelectorAll('.mini-cookie-detect-add').forEach(function(btn) {
            btn.addEventListener('click', function() { addDetectedCookie(this); });
        });

        var addAllBtn = document.getElementById('mini-cookie-add-all-detected');
        if (addAllBtn) {
            addAllBtn.addEventListener('click', function() {
                document.querySelectorAll('.mini-cookie-detect-add:not([disabled])').forEach(function(btn) {
                    addDetectedCookie(btn);
                });
            });
        }
    })();
    </script>
    <?php
}

/* END - GDPR Cookie Policy settings */
