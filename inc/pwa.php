<?php
/* START - PWA settings */

function mini_pwa_settings_init() {
    register_setting( 'mini_pwa', 'mini_pwa_settings', [
        'sanitize_callback' => 'mini_pwa_sanitize_settings',
    ] );
    add_settings_section(
        'mini_pwa_section',
        __( 'PWA settings', 'mini' ),
        'mini_pwa_section_callback',
        'mini-pwa'
    );
}
add_action( 'admin_init', 'mini_pwa_settings_init' );

function mini_pwa_sanitize_settings( $input ) {
    $sanitized = [];

    $sanitized['mini_pwa_enabled']       = ! empty( $input['mini_pwa_enabled'] ) ? 1 : 0;
    $sanitized['mini_pwa_name']          = isset( $input['mini_pwa_name'] ) ? sanitize_text_field( $input['mini_pwa_name'] ) : '';
    $sanitized['mini_pwa_short_name']    = isset( $input['mini_pwa_short_name'] ) ? sanitize_text_field( $input['mini_pwa_short_name'] ) : '';
    $sanitized['mini_pwa_description']   = isset( $input['mini_pwa_description'] ) ? sanitize_text_field( $input['mini_pwa_description'] ) : '';
    $sanitized['mini_pwa_theme_color']   = isset( $input['mini_pwa_theme_color'] ) ? sanitize_hex_color( $input['mini_pwa_theme_color'] ) : '#ffffff';
    $sanitized['mini_pwa_bg_color']      = isset( $input['mini_pwa_bg_color'] ) ? sanitize_hex_color( $input['mini_pwa_bg_color'] ) : '#ffffff';
    $sanitized['mini_pwa_display']       = isset( $input['mini_pwa_display'] ) && in_array( $input['mini_pwa_display'], [ 'standalone', 'fullscreen', 'minimal-ui', 'browser' ], true )
        ? $input['mini_pwa_display']
        : 'standalone';
    $sanitized['mini_pwa_start_url']     = isset( $input['mini_pwa_start_url'] ) ? esc_url_raw( $input['mini_pwa_start_url'] ) : '/';
    $sanitized['mini_pwa_icon_192']      = isset( $input['mini_pwa_icon_192'] ) ? esc_url_raw( $input['mini_pwa_icon_192'] ) : '';
    $sanitized['mini_pwa_icon_512']      = isset( $input['mini_pwa_icon_512'] ) ? esc_url_raw( $input['mini_pwa_icon_512'] ) : '';
    $sanitized['mini_pwa_offline_page']  = isset( $input['mini_pwa_offline_page'] ) ? absint( $input['mini_pwa_offline_page'] ) : 0;

    return $sanitized;
}

function mini_pwa_section_callback( $args ) {
    $opts         = get_option( 'mini_pwa_settings', [] );
    $enabled      = ! empty( $opts['mini_pwa_enabled'] );
    $name         = $opts['mini_pwa_name']        ?? get_bloginfo( 'name' );
    $short_name   = $opts['mini_pwa_short_name']  ?? get_bloginfo( 'name' );
    $description  = $opts['mini_pwa_description'] ?? get_bloginfo( 'description' );
    $theme_color  = $opts['mini_pwa_theme_color'] ?? '#ffffff';
    $bg_color     = $opts['mini_pwa_bg_color']    ?? '#ffffff';
    $display      = $opts['mini_pwa_display']     ?? 'standalone';
    $start_url    = $opts['mini_pwa_start_url']   ?? '/';
    $icon_192     = $opts['mini_pwa_icon_192']    ?? '';
    $icon_512     = $opts['mini_pwa_icon_512']    ?? '';
    $offline_page = $opts['mini_pwa_offline_page'] ?? 0;

    $display_modes = [
        'standalone'  => __( 'Standalone (recommended) — looks like a native app, no browser UI', 'mini' ),
        'fullscreen'  => __( 'Fullscreen — hides all browser and OS chrome', 'mini' ),
        'minimal-ui'  => __( 'Minimal UI — shows a minimal browser bar', 'mini' ),
        'browser'     => __( 'Browser — normal browser tab', 'mini' ),
    ];

    $pages = get_pages();
    ?>
    <p id="<?php echo esc_attr( $args['id'] ); ?>" class="grey-text">
        <?php esc_html_e( 'Turn your website into an installable Progressive Web App (PWA). This generates a Web App Manifest and registers a service worker for offline support.', 'mini' ); ?>
    </p>

    <div class="boxes">

        <!-- Enable toggle -->
        <div class="box-100 p-2 grad-3-to-4 b-rad-5 box-shadow">
            <h4 class="white-text"><?php esc_html_e( 'Enable PWA', 'mini' ); ?></h4>
            <label for="mini_pwa_enabled" class="white-text">
                <input type="checkbox" id="mini_pwa_enabled" name="mini_pwa_settings[mini_pwa_enabled]" value="1" <?php checked( $enabled ); ?> class="me-1">
                <?php esc_html_e( 'Activate the Progressive Web App features for this site.', 'mini' ); ?>
            </label>
            <p class="S false-white-text"><?php esc_html_e( 'When enabled, a manifest.json is served and a service worker is registered on all front-end pages. Requires HTTPS in production.', 'mini' ); ?></p>
        </div>

        <!-- App identity -->
        <div class="box-50 p-2 white-bg b-rad-5 box-shadow">
            <h4><?php esc_html_e( 'App name', 'mini' ); ?></h4>
            <input type="text" id="mini_pwa_name" name="mini_pwa_settings[mini_pwa_name]" value="<?php echo esc_attr( $name ); ?>" class="regular-text">
            <p class="S grey-text"><?php esc_html_e( 'Full name displayed on the splash screen and app store listings.', 'mini' ); ?></p>
        </div>

        <div class="box-50 p-2 white-bg b-rad-5 box-shadow">
            <h4><?php esc_html_e( 'Short name', 'mini' ); ?></h4>
            <input type="text" id="mini_pwa_short_name" name="mini_pwa_settings[mini_pwa_short_name]" value="<?php echo esc_attr( $short_name ); ?>" class="regular-text">
            <p class="S grey-text"><?php esc_html_e( 'Abbreviated name shown under the home screen icon (keep under 12 characters).', 'mini' ); ?></p>
        </div>

        <div class="box-50 p-2 white-bg b-rad-5 box-shadow">
            <h4><?php esc_html_e( 'Description', 'mini' ); ?></h4>
            <input type="text" id="mini_pwa_description" name="mini_pwa_settings[mini_pwa_description]" value="<?php echo esc_attr( $description ); ?>" class="large-text">
            <p class="S grey-text"><?php esc_html_e( 'Brief description of the app, used in install prompts.', 'mini' ); ?></p>
        </div>

        <!-- Start URL -->
        <div class="box-50 p-2 white-bg b-rad-5 box-shadow">
            <h4><?php esc_html_e( 'Start URL', 'mini' ); ?></h4>
            <input type="text" id="mini_pwa_start_url" name="mini_pwa_settings[mini_pwa_start_url]" value="<?php echo esc_attr( $start_url ); ?>" class="regular-text" placeholder="/">
            <p class="S grey-text"><?php esc_html_e( 'The URL loaded when the app is launched from the home screen. Use a relative path (e.g. /).', 'mini' ); ?></p>
        </div>

        <!-- Display mode -->
        <div class="box-33 p-2 white-bg b-rad-5 box-shadow">
            <h4><?php esc_html_e( 'Display mode', 'mini' ); ?></h4>
            <select id="mini_pwa_display" name="mini_pwa_settings[mini_pwa_display]">
                <?php foreach ( $display_modes as $value => $label ) : ?>
                    <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $display, $value ); ?>>
                        <?php echo esc_html( $label ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Colors -->
        <div class="box-33 p-2 white-bg b-rad-5 box-shadow">
            <h4><?php esc_html_e( 'Theme color', 'mini' ); ?></h4>
            <input type="color" id="mini_pwa_theme_color" name="mini_pwa_settings[mini_pwa_theme_color]" value="<?php echo esc_attr( $theme_color ); ?>" style="width:3rem; height:3rem; padding: calc(var(--padding) * 0.25); cursor:pointer;background: var(--false-white); border: 1px solid var(--light-grey);">
            <p class="S grey-text"><?php esc_html_e( 'Controls the browser toolbar color and the task switcher color on Android.', 'mini' ); ?></p>
        </div>

        <div class="box-33 p-2 white-bg b-rad-5 box-shadow">
            <h4><?php esc_html_e( 'Background color', 'mini' ); ?></h4>
            <input type="color" id="mini_pwa_bg_color" name="mini_pwa_settings[mini_pwa_bg_color]" value="<?php echo esc_attr( $bg_color ); ?>" style="width:3rem; height:3rem; padding: calc(var(--padding) * 0.25); cursor:pointer;background: var(--false-white); border: 1px solid var(--light-grey);">
            <p class="S grey-text"><?php esc_html_e( 'Splash screen background color shown before the app\'s CSS is loaded.', 'mini' ); ?></p>
        </div>

        <!-- Icons -->
        <div class="box-50 p-2 white-bg b-rad-5 box-shadow">
            <h4><?php esc_html_e( 'Icon 192×192', 'mini' ); ?></h4>
            <div style="display:flex; gap: 0.5rem; align-items: center;">
                <input type="text" id="mini_pwa_icon_192" name="mini_pwa_settings[mini_pwa_icon_192]" value="<?php echo esc_attr( $icon_192 ); ?>" class="large-text mini-pwa-icon-url" data-preview="mini_pwa_icon_192_preview">
                <button type="button" class="btn mini-pwa-media-upload" data-target="mini_pwa_icon_192"><?php esc_html_e( 'Select', 'mini' ); ?></button>
            </div>
            <?php if ( $icon_192 ) : ?>
                <img id="mini_pwa_icon_192_preview" src="<?php echo esc_url( $icon_192 ); ?>" style="margin-top:0.5rem; width:48px; height:48px; object-fit:contain;" alt="">
            <?php else : ?>
                <img id="mini_pwa_icon_192_preview" src="" style="margin-top:0.5rem; width:48px; height:48px; object-fit:contain; display:none;" alt="">
            <?php endif; ?>
            <p class="S grey-text"><?php esc_html_e( 'PNG icon, 192×192 px. Required for Android home screen.', 'mini' ); ?></p>
        </div>

        <div class="box-50 p-2 white-bg b-rad-5 box-shadow">
            <h4><?php esc_html_e( 'Icon 512×512', 'mini' ); ?></h4>
            <div style="display:flex; gap: 0.5rem; align-items: center;">
                <input type="text" id="mini_pwa_icon_512" name="mini_pwa_settings[mini_pwa_icon_512]" value="<?php echo esc_attr( $icon_512 ); ?>" class="large-text mini-pwa-icon-url" data-preview="mini_pwa_icon_512_preview">
                <button type="button" class="btn mini-pwa-media-upload" data-target="mini_pwa_icon_512"><?php esc_html_e( 'Select', 'mini' ); ?></button>
            </div>
            <?php if ( $icon_512 ) : ?>
                <img id="mini_pwa_icon_512_preview" src="<?php echo esc_url( $icon_512 ); ?>" style="margin-top:0.5rem; width:48px; height:48px; object-fit:contain;" alt="">
            <?php else : ?>
                <img id="mini_pwa_icon_512_preview" src="" style="margin-top:0.5rem; width:48px; height:48px; object-fit:contain; display:none;" alt="">
            <?php endif; ?>
            <p class="S grey-text"><?php esc_html_e( 'PNG icon, 512×512 px. Required for splash screens and app stores.', 'mini' ); ?></p>
        </div>

        <!-- Offline page -->
        <div class="box-100 p-2 white-bg b-rad-5 box-shadow">
            <h4><?php esc_html_e( 'Offline fallback page', 'mini' ); ?></h4>
            <select id="mini_pwa_offline_page" name="mini_pwa_settings[mini_pwa_offline_page]">
                <option value="0"><?php esc_html_e( '— None —', 'mini' ); ?></option>
                <?php foreach ( $pages as $page ) : ?>
                    <option value="<?php echo esc_attr( $page->ID ); ?>" <?php selected( $offline_page, $page->ID ); ?>>
                        <?php echo esc_html( $page->post_title ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="S grey-text"><?php esc_html_e( 'Page to show when the user is offline and the requested resource is not cached. Leave blank to use the browser default error page.', 'mini' ); ?></p>
        </div>

        <!-- Manifest preview -->
        <?php if ( $enabled ) : ?>
        <div class="box-100 p-2 white-bg b-rad-5 box-shadow">
            <h4><?php esc_html_e( 'Manifest preview', 'mini' ); ?></h4>
            <p>
                <a href="<?php echo esc_url( home_url( '/mini-manifest.json' ) ); ?>" target="_blank" rel="noopener">
                    <?php echo esc_url( home_url( '/mini-manifest.json' ) ); ?>
                </a>
            </p>
        </div>
        <?php endif; ?>

    </div>

    <script>
    (function () {
        // Media uploader for icon fields
        document.querySelectorAll('.mini-pwa-media-upload').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var targetId = btn.dataset.target;
                var frame = wp.media({
                    title: '<?php echo esc_js( __( 'Select Icon', 'mini' ) ); ?>',
                    button: { text: '<?php echo esc_js( __( 'Use this image', 'mini' ) ); ?>' },
                    multiple: false,
                    library: { type: 'image' }
                });
                frame.on('select', function () {
                    var attachment = frame.state().get('selection').first().toJSON();
                    var input   = document.getElementById(targetId);
                    var preview = document.getElementById(targetId + '_preview');
                    if (input)   { input.value = attachment.url; }
                    if (preview) { preview.src = attachment.url; preview.style.display = 'block'; }
                });
                frame.open();
            });
        });
    }());
    </script>
    <?php
}

/* END - PWA settings */


/* START - PWA manifest endpoint */

function mini_pwa_manifest_rewrite_version() {
    return '1';
}

function mini_pwa_register_rewrite_rules() {
    add_rewrite_rule( '^mini-manifest\.json$', 'index.php?mini_pwa_manifest=1', 'top' );
}
add_action( 'init', 'mini_pwa_register_rewrite_rules' );

function mini_pwa_query_vars( $vars ) {
    $vars[] = 'mini_pwa_manifest';
    return $vars;
}
add_filter( 'query_vars', 'mini_pwa_query_vars' );

function mini_pwa_serve_manifest() {
    if ( ! get_query_var( 'mini_pwa_manifest' ) ) {
        return;
    }

    $opts = get_option( 'mini_pwa_settings', [] );

    if ( empty( $opts['mini_pwa_enabled'] ) ) {
        status_header( 404 );
        exit;
    }

    $name        = ! empty( $opts['mini_pwa_name'] )       ? $opts['mini_pwa_name']       : get_bloginfo( 'name' );
    $short_name  = ! empty( $opts['mini_pwa_short_name'] ) ? $opts['mini_pwa_short_name'] : get_bloginfo( 'name' );
    $description = ! empty( $opts['mini_pwa_description'] )? $opts['mini_pwa_description']: get_bloginfo( 'description' );
    $theme_color = ! empty( $opts['mini_pwa_theme_color'] )? $opts['mini_pwa_theme_color']: '#ffffff';
    $bg_color    = ! empty( $opts['mini_pwa_bg_color'] )   ? $opts['mini_pwa_bg_color']   : '#ffffff';
    $display     = ! empty( $opts['mini_pwa_display'] )    ? $opts['mini_pwa_display']     : 'standalone';
    $start_url   = ! empty( $opts['mini_pwa_start_url'] )  ? $opts['mini_pwa_start_url']   : '/';
    $icon_192    = ! empty( $opts['mini_pwa_icon_192'] )   ? $opts['mini_pwa_icon_192']    : '';
    $icon_512    = ! empty( $opts['mini_pwa_icon_512'] )   ? $opts['mini_pwa_icon_512']    : '';

    $manifest = [
        'name'             => $name,
        'short_name'       => $short_name,
        'description'      => $description,
        'start_url'        => $start_url,
        'display'          => $display,
        'theme_color'      => $theme_color,
        'background_color' => $bg_color,
        'icons'            => [],
    ];

    if ( $icon_192 ) {
        $manifest['icons'][] = [
            'src'     => $icon_192,
            'sizes'   => '192x192',
            'type'    => 'image/png',
            'purpose' => 'any maskable',
        ];
    }

    if ( $icon_512 ) {
        $manifest['icons'][] = [
            'src'     => $icon_512,
            'sizes'   => '512x512',
            'type'    => 'image/png',
            'purpose' => 'any maskable',
        ];
    }

    header( 'Content-Type: application/manifest+json; charset=utf-8' );
    header( 'Cache-Control: public, max-age=3600' );
    echo wp_json_encode( $manifest, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );
    exit;
}
add_action( 'template_redirect', 'mini_pwa_serve_manifest' );

/* END - PWA manifest endpoint */


/* START - PWA front-end injection */

function mini_pwa_head_tags() {
    $opts = get_option( 'mini_pwa_settings', [] );
    if ( empty( $opts['mini_pwa_enabled'] ) ) {
        return;
    }

    $theme_color = ! empty( $opts['mini_pwa_theme_color'] ) ? $opts['mini_pwa_theme_color'] : '#ffffff';

    echo '<link rel="manifest" href="' . esc_url( home_url( '/mini-manifest.json' ) ) . '">' . "\n";
    echo '<meta name="theme-color" content="' . esc_attr( $theme_color ) . '">' . "\n";
    echo '<meta name="mobile-web-app-capable" content="yes">' . "\n";
    echo '<meta name="apple-mobile-web-app-capable" content="yes">' . "\n";
    echo '<meta name="apple-mobile-web-app-status-bar-style" content="default">' . "\n";

    $icon_192 = ! empty( $opts['mini_pwa_icon_192'] ) ? $opts['mini_pwa_icon_192'] : '';
    if ( $icon_192 ) {
        echo '<link rel="apple-touch-icon" href="' . esc_url( $icon_192 ) . '">' . "\n";
    }
}
add_action( 'wp_head', 'mini_pwa_head_tags' );

function mini_pwa_register_service_worker() {
    $opts = get_option( 'mini_pwa_settings', [] );
    if ( empty( $opts['mini_pwa_enabled'] ) ) {
        return;
    }

    // Bypass service worker caching for logged-in users so admins always
    // see fresh content without having to clear the SW cache manually.
    if ( is_user_logged_in() ) {
        return;
    }

    $offline_page_id  = ! empty( $opts['mini_pwa_offline_page'] ) ? (int) $opts['mini_pwa_offline_page'] : 0;
    $offline_url      = $offline_page_id ? esc_url( get_permalink( $offline_page_id ) ) : '';
    $cache_name       = 'mini-pwa-v1';
    ?>
    <script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register('<?php echo esc_js( home_url( '/mini-sw.js' ) ); ?>', { scope: '/' })
                .catch(function (err) { console.warn('[mini PWA] SW registration failed:', err); });
        });
    }
    </script>
    <?php
}
add_action( 'wp_footer', 'mini_pwa_register_service_worker' );

/* END - PWA front-end injection */


/* START - PWA service worker endpoint */

function mini_pwa_sw_rewrite_rules() {
    add_rewrite_rule( '^mini-sw\.js$', 'index.php?mini_pwa_sw=1', 'top' );
}
add_action( 'init', 'mini_pwa_sw_rewrite_rules' );

function mini_pwa_sw_query_vars( $vars ) {
    $vars[] = 'mini_pwa_sw';
    return $vars;
}
add_filter( 'query_vars', 'mini_pwa_sw_query_vars' );

function mini_pwa_serve_sw() {
    if ( ! get_query_var( 'mini_pwa_sw' ) ) {
        return;
    }

    $opts = get_option( 'mini_pwa_settings', [] );

    if ( empty( $opts['mini_pwa_enabled'] ) ) {
        status_header( 404 );
        exit;
    }

    $cache_name      = 'mini-pwa-v1';
    $offline_page_id = ! empty( $opts['mini_pwa_offline_page'] ) ? (int) $opts['mini_pwa_offline_page'] : 0;
    $offline_url     = $offline_page_id ? get_permalink( $offline_page_id ) : '';

    header( 'Content-Type: application/javascript; charset=utf-8' );
    header( 'Service-Worker-Allowed: /' );
    header( 'Cache-Control: no-store' );

    $offline_js = $offline_url
        ? "'" . esc_js( $offline_url ) . "'"
        : 'null';

    echo <<<JS
const CACHE_NAME = '{$cache_name}';
const OFFLINE_URL = {$offline_js};

self.addEventListener('install', function (event) {
    event.waitUntil(
        caches.open(CACHE_NAME).then(function (cache) {
            if (OFFLINE_URL) {
                return cache.add(OFFLINE_URL);
            }
        }).then(function () {
            return self.skipWaiting();
        })
    );
});

self.addEventListener('activate', function (event) {
    event.waitUntil(
        caches.keys().then(function (keys) {
            return Promise.all(
                keys.filter(function (k) { return k !== CACHE_NAME; })
                    .map(function (k) { return caches.delete(k); })
            );
        }).then(function () {
            return self.clients.claim();
        })
    );
});

self.addEventListener('fetch', function (event) {
    if (event.request.method !== 'GET') return;

    event.respondWith(
        fetch(event.request)
            .then(function (response) {
                return response;
            })
            .catch(function () {
                return caches.match(event.request).then(function (cached) {
                    if (cached) return cached;
                    if (OFFLINE_URL) return caches.match(OFFLINE_URL);
                    return new Response('Offline', { status: 503, statusText: 'Service Unavailable' });
                });
            })
    );
});
JS;
    exit;
}
add_action( 'template_redirect', 'mini_pwa_serve_sw' );

/* END - PWA service worker endpoint */


/* START - PWA activation / deactivation hooks */

function mini_pwa_activation_setup() {
    mini_pwa_register_rewrite_rules();
    mini_pwa_sw_rewrite_rules();
    flush_rewrite_rules();
}

function mini_pwa_deactivation_teardown() {
    flush_rewrite_rules();
}

/* END - PWA activation / deactivation hooks */
