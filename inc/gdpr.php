<?php
/* START - GDPR settings */

function mini_gdpr_settings_init() {
    register_setting( 'mini_gdpr_privacy', 'mini_gdpr_privacy_settings', [
        'sanitize_callback' => 'mini_gdpr_privacy_sanitize_settings',
    ] );
    add_settings_section(
        'mini_gdpr_privacy_section',
        __( 'Privacy page', 'mini' ),
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

    // Refresh page content whenever the feature is enabled
    if ( $sanitized['mini_gdpr_privacy_enabled'] ) {
        $page_id = mini_gdpr_create_privacy_page( $sanitized );
        if ( $page_id ) {
            $sanitized['mini_gdpr_privacy_page_id'] = $page_id;
        }
    }

    return $sanitized;
}

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
            <p class="S grey-text"><?php esc_html_e( 'The page is created (or updated) every time you save with this option enabled. Changing any field below and saving will refresh the content.', 'mini' ); ?></p>
            <?php if ( $enabled && $page_id ) : ?>
            <p class="S">
                <a href="<?php echo esc_url( get_permalink( $page_id ) ); ?>" target="_blank" class="btn white-text S"><?php echo esc_html( get_the_title( $page_id ) ); ?></a>
                <a href="<?php echo esc_url( get_edit_post_link( $page_id ) ); ?>" class="btn warning-btn white-text S"><?php esc_html_e( 'Edit', 'mini' ); ?></a>
            </p>
            <?php endif; ?>
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
