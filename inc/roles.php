<?php
/**
 * Per-content-type roles & permissions
 *
 * Each mini content type group gets its own WordPress capability_type
 * (wired up in inc/content-types.php), so that access to e.g. Eventi can be
 * granted independently from access to Corsi. This file defines the
 * available groups, the WP-role-equivalent permission tiers, and the
 * settings UI + sync logic that turns a chosen tier into real role
 * capabilities.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * The 6 manageable content type groups, mapped to the capability_type
 * (singular/plural) used when registering their post type(s) and to the
 * mini_content_settings option key that toggles them on/off.
 *
 * Slideshow/Slide and Course/Lesson share one bundle each, matching how
 * they're presented as a single group in the UI.
 */
function mini_content_type_groups() {
    return [
        'slideshow' => [
            'label'      => __('Slideshow', 'mini'),
            'singular'   => 'mini_slide',
            'plural'     => 'mini_slides',
            'option_key' => 'mini_slide',
        ],
        'news' => [
            'label'      => __('News', 'mini'),
            'singular'   => 'mini_news_item',
            'plural'     => 'mini_news_items',
            'option_key' => 'mini_news',
        ],
        'event' => [
            'label'      => __('Eventi', 'mini'),
            'singular'   => 'mini_event',
            'plural'     => 'mini_events',
            'option_key' => 'mini_event',
        ],
        'course' => [
            'label'      => __('Corsi', 'mini'),
            'singular'   => 'mini_course_item',
            'plural'     => 'mini_course_items',
            'option_key' => 'mini_course',
        ],
        'match' => [
            'label'      => __('Partite', 'mini'),
            'singular'   => 'mini_match',
            'plural'     => 'mini_matches',
            'option_key' => 'mini_match',
        ],
        'landing_page' => [
            'label'      => __('Landing Pages', 'mini'),
            'singular'   => 'mini_landing_page',
            'plural'     => 'mini_landing_pages',
            'option_key' => 'mini_landing_page',
        ],
    ];
}

/**
 * [singular, plural] pair for register_post_type()'s 'capability_type' arg.
 */
function mini_get_capability_type($group_key) {
    $groups = mini_content_type_groups();
    if (!isset($groups[$group_key])) {
        return null;
    }
    return [$groups[$group_key]['singular'], $groups[$group_key]['plural']];
}

/**
 * The 10 primitive plural capabilities WordPress's map_meta_cap relies on
 * for a custom capability_type.
 */
function mini_content_type_capabilities($plural) {
    return [
        "edit_{$plural}",
        "edit_others_{$plural}",
        "edit_private_{$plural}",
        "edit_published_{$plural}",
        "publish_{$plural}",
        "read_private_{$plural}",
        "delete_{$plural}",
        "delete_private_{$plural}",
        "delete_published_{$plural}",
        "delete_others_{$plural}",
    ];
}

/**
 * Valid permission tiers, in increasing order of power.
 */
function mini_content_type_role_tiers() {
    return [
        'none'        => __('No access', 'mini'),
        'contributor' => __('Contributor (create + edit own drafts)', 'mini'),
        'author'      => __('Author (full control of own content)', 'mini'),
        'editor'      => __('Editor (full control of everyone\'s content)', 'mini'),
    ];
}

/**
 * Capability bundle for a given tier, mirroring WordPress's own built-in
 * role definitions for the 'post' capability type.
 */
function mini_capability_tier_bundle($tier, $plural) {
    switch ($tier) {
        case 'contributor':
            return ["edit_{$plural}", "delete_{$plural}"];
        case 'author':
            return ["edit_{$plural}", "edit_published_{$plural}", "publish_{$plural}", "delete_{$plural}", "delete_published_{$plural}"];
        case 'editor':
            return mini_content_type_capabilities($plural);
        case 'none':
        default:
            return [];
    }
}

/**
 * Default tier for a role that has never been configured yet, derived from
 * whatever generic 'post' capabilities it already has today. This preserves
 * current de facto access (these content types used to share 'post' caps)
 * so nobody loses access the moment per-type capabilities ship.
 */
function mini_default_tier_for_role($role) {
    if (!$role || empty($role->capabilities)) {
        return 'none';
    }
    if (!empty($role->capabilities['edit_others_posts'])) {
        return 'editor';
    }
    if (!empty($role->capabilities['publish_posts'])) {
        return 'author';
    }
    if (!empty($role->capabilities['edit_posts'])) {
        return 'contributor';
    }
    return 'none';
}

/**
 * Administrator is always locked to full control on every group, regardless
 * of stored settings, so it can never be misconfigured into a lockout.
 */
function mini_force_admin_full_control() {
    $admin = get_role('administrator');
    if (!$admin) {
        return;
    }
    foreach (mini_content_type_groups() as $group) {
        foreach (mini_content_type_capabilities($group['plural']) as $cap) {
            $admin->add_cap($cap);
        }
    }
}

/**
 * Apply the stored tier selections as real role capabilities. Mirrors the
 * diff-and-resync shape of mini_sync_main_menu_item() in mini-plugin.php.
 */
function mini_sync_content_type_role_caps($old_value, $new_value) {
    $role_names = wp_roles()->get_names();

    foreach (mini_content_type_groups() as $group_key => $group) {
        $plural   = $group['plural'];
        $all_caps = mini_content_type_capabilities($plural);

        foreach ($role_names as $role_slug => $role_name) {
            if ($role_slug === 'administrator') {
                continue;
            }
            $role = get_role($role_slug);
            if (!$role) {
                continue;
            }

            $tier   = isset($new_value[$group_key][$role_slug]) ? $new_value[$group_key][$role_slug] : 'none';
            $bundle = mini_capability_tier_bundle($tier, $plural);

            foreach ($all_caps as $cap) {
                $role->remove_cap($cap);
            }
            foreach ($bundle as $cap) {
                $role->add_cap($cap);
            }
        }
    }

    mini_force_admin_full_control();
}
add_action('update_option_mini_content_roles_settings', 'mini_sync_content_type_role_caps', 10, 2);

/**
 * Seed mini_content_roles_settings with defaults derived from each role's
 * current capabilities, then apply them. Only runs once (first activation,
 * or first admin page load after updating from a version without this
 * option) — never overwrites an already-configured option.
 */
function mini_seed_content_type_role_defaults() {
    if (false !== get_option('mini_content_roles_settings')) {
        mini_force_admin_full_control();
        return;
    }

    $defaults   = [];
    $role_names = wp_roles()->get_names();

    foreach (mini_content_type_groups() as $group_key => $group) {
        foreach ($role_names as $role_slug => $role_name) {
            if ($role_slug === 'administrator') {
                continue;
            }
            $defaults[$group_key][$role_slug] = mini_default_tier_for_role(get_role($role_slug));
        }
    }

    update_option('mini_content_roles_settings', $defaults);
    mini_sync_content_type_role_caps([], $defaults);
}

/* START - Roles & permissions settings (rendered as a tab on the Contents & types page) */

function mini_content_roles_settings_init() {
    register_setting('mini_content_roles', 'mini_content_roles_settings', [
        'sanitize_callback' => 'mini_content_roles_sanitize_settings',
        'default'           => [],
    ]);
    add_settings_section(
        'mini_content_roles_section',
        __('Roles & permissions', 'mini'),
        'mini_content_roles_section_callback',
        'mini-content-roles'
    );
}
add_action('admin_init', 'mini_content_roles_settings_init');

function mini_content_roles_sanitize_settings($input) {
    $valid_tiers = array_keys(mini_content_type_role_tiers());
    $sanitized   = [];
    $role_names  = wp_roles()->get_names();

    foreach (mini_content_type_groups() as $group_key => $group) {
        foreach ($role_names as $role_slug => $role_name) {
            if ($role_slug === 'administrator') {
                continue;
            }
            $tier = isset($input[$group_key][$role_slug]) ? sanitize_key($input[$group_key][$role_slug]) : 'none';
            $sanitized[$group_key][$role_slug] = in_array($tier, $valid_tiers, true) ? $tier : 'none';
        }
    }

    return $sanitized;
}

function mini_content_roles_section_callback($args) {
    $settings   = get_option('mini_content_roles_settings', []);
    $role_names = wp_roles()->get_names();
    $tiers      = mini_content_type_role_tiers();
    ?>
    <p id="<?php echo esc_attr($args['id']); ?>" class="grey-text">
        <?php esc_html_e('Choose, for each content type and each WordPress role, how much that role can do — modeled on WordPress\'s own Contributor / Author / Editor hierarchy, scoped to just that content type. The Administrator role always has full control and can\'t be changed here.', 'mini'); ?>
    </p>
    <table class="widefat striped">
        <thead>
            <tr>
                <th><?php esc_html_e('Content type', 'mini'); ?></th>
                <?php foreach ($role_names as $role_slug => $role_name) : ?>
                    <th><?php echo esc_html($role_name); ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach (mini_content_type_groups() as $group_key => $group) :
                if (!is_mini_option_enabled('mini_content_settings', $group['option_key'])) {
                    continue;
                }
                ?>
                <tr>
                    <td><strong><?php echo esc_html($group['label']); ?></strong></td>
                    <?php foreach ($role_names as $role_slug => $role_name) : ?>
                        <td>
                            <?php if ($role_slug === 'administrator') : ?>
                                <?php esc_html_e('Full control', 'mini'); ?>
                            <?php else :
                                $current = isset($settings[$group_key][$role_slug]) ? $settings[$group_key][$role_slug] : 'none';
                                ?>
                                <select name="mini_content_roles_settings[<?php echo esc_attr($group_key); ?>][<?php echo esc_attr($role_slug); ?>]">
                                    <?php foreach ($tiers as $tier_key => $tier_label) : ?>
                                        <option value="<?php echo esc_attr($tier_key); ?>"<?php selected($current, $tier_key); ?>><?php echo esc_html($tier_label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}

/* END - Roles & permissions settings */
