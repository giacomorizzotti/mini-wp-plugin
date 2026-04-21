<?php
/* START - Custom post types - Consolidated */
function register_mini_post_type($type, $singular, $plural, $icon, $has_archive = true, $hierarchical = false, $page_attributes = true, $show_in_menu = true) {
    $supports = ['title', 'editor', 'thumbnail', 'excerpt', 'panels'];

    // Add page-attributes support for hierarchical post types (enables parent dropdown and menu_order)
    // Pass $page_attributes = false when using a custom parent meta box instead
    if ($hierarchical && $page_attributes) {
        $supports[] = 'page-attributes';
    }

    register_post_type($type, [
        'labels' => [
            'name' => __($plural, 'mini'),
            'singular_name' => __($singular, 'mini'),
            'add_new' => __('Add ' . $singular, 'mini'),
            'add_new_item' => __('Add New ' . $singular, 'mini'),
            'edit' => __('Edit', 'mini'),
            'edit_item' => __('Edit ' . $singular, 'mini'),
            'new_item' => __('New ' . $singular, 'mini'),
            'view' => __('View ' . $singular, 'mini'),
            'view_item' => __('View ' . $singular, 'mini'),
            'search_items' => __('Search ' . $plural, 'mini'),
            'not_found' => __('No ' . $plural . ' found', 'mini'),
            'archives' => __($plural, 'mini'),
        ],
        'public' => true,
        'hierarchical' => $hierarchical,
        'has_archive' => $has_archive,
        'menu_icon' => $icon,
        'show_in_menu' => $show_in_menu,
        'rewrite' => ['slug' => $type],
        'show_in_rest' => true,
        'supports' => $supports
    ]);
}

/**
 * Register a hierarchical taxonomy with mini defaults
 */
function register_mini_taxonomy($slug, $singular, $plural, $post_types) {
    register_taxonomy($slug, (array) $post_types, [
        'labels' => [
            'name'              => __($plural, 'mini'),
            'singular_name'     => __($singular, 'mini'),
            'search_items'      => __('Search ' . $plural, 'mini'),
            'all_items'         => __('All ' . $plural, 'mini'),
            'parent_item'       => __('Parent ' . $singular, 'mini'),
            'parent_item_colon' => __('Parent ' . $singular . ':', 'mini'),
            'edit_item'         => __('Edit ' . $singular, 'mini'),
            'update_item'       => __('Update ' . $singular, 'mini'),
            'add_new_item'      => __('Add New ' . $singular, 'mini'),
            'new_item_name'     => __('New ' . $singular . ' Name', 'mini'),
            'menu_name'         => __($plural, 'mini'),
        ],
        'hierarchical'      => true,
        'show_ui'           => true,
        'show_in_rest'      => true,
        'show_admin_column' => true,
        'rewrite'           => ['slug' => $slug],
    ]);
}

function register_mini_tag_taxonomy($slug, $post_types) {
    register_taxonomy($slug, (array) $post_types, [
        'labels' => [
            'name'                       => __('Tags', 'mini'),
            'singular_name'              => __('Tag', 'mini'),
            'search_items'               => __('Search Tags', 'mini'),
            'popular_items'              => __('Popular Tags', 'mini'),
            'all_items'                  => __('All Tags', 'mini'),
            'edit_item'                  => __('Edit Tag', 'mini'),
            'update_item'                => __('Update Tag', 'mini'),
            'add_new_item'               => __('Add New Tag', 'mini'),
            'new_item_name'              => __('New Tag Name', 'mini'),
            'separate_items_with_commas' => __('Separate tags with commas', 'mini'),
            'add_or_remove_items'        => __('Add or remove tags', 'mini'),
            'choose_from_most_used'      => __('Choose from the most used tags', 'mini'),
            'menu_name'                  => __('Tags', 'mini'),
        ],
        'hierarchical'      => false,
        'show_ui'           => true,
        'show_in_rest'      => true,
        'show_admin_column' => true,
        'rewrite'           => ['slug' => $slug],
    ]);
}

if (is_mini_option_enabled('mini_content_settings', 'mini_news')) {
    add_action('init', function() {
        register_mini_post_type('news', 'News', 'News', 'dashicons-text-page');
        register_mini_taxonomy('news_category', 'Category', 'Categories', 'news');
        register_mini_tag_taxonomy('news_tag', 'news');
    });
}

/* START - Custom post type - SLIDESHOW */
if (is_mini_option_enabled('mini_content_settings', 'mini_slide')) {
    add_action('init', function() {
        register_mini_post_type('slideshow', 'Slideshow', 'Slideshows', 'dashicons-images-alt2');

        // Slide is hierarchical for post_parent storage, but uses a custom parent meta box
        // (page_attributes=false) so we control the parent dropdown ourselves
        // show_in_menu=false hides the top-level entry; it's nested under Slideshows instead
        register_mini_post_type('slide', 'Slide', 'Slides', 'dashicons-slides', false, true, false, false);
    });

    // Remove "Add New Slideshow" from the submenu; slides appear embedded in the list
    add_action('admin_menu', function() {
        remove_submenu_page('edit.php?post_type=slideshow', 'post-new.php?post_type=slideshow');
    });

    // Inject slides under each slideshow in the native WP list table
    add_filter('the_posts', function($posts, $query) {
        if ( ! is_admin() || ! $query->is_main_query() ) return $posts;
        $screen = get_current_screen();
        if ( ! $screen || $screen->base !== 'edit' || $screen->post_type !== 'slideshow' ) return $posts;

        static $running = false;
        if ( $running ) return $posts;
        $running = true;

        $result = [];
        foreach ( $posts as $slideshow ) {
            $result[] = $slideshow;
            $slides = get_posts([
                'post_type'      => 'slide',
                'posts_per_page' => -1,
                'post_parent'    => $slideshow->ID,
                'post_status'    => ['publish', 'draft', 'pending', 'private'],
                'orderby'        => 'menu_order',
                'order'          => 'ASC',
            ]);
            foreach ( $slides as $slide ) {
                $result[] = $slide;
            }
        }

        // Append slides with no parent at the end
        $orphan_slides = get_posts([
            'post_type'      => 'slide',
            'posts_per_page' => -1,
            'post_parent'    => 0,
            'post_status'    => ['publish', 'draft', 'pending', 'private'],
            'orderby'        => 'menu_order',
            'order'          => 'ASC',
        ]);
        foreach ( $orphan_slides as $slide ) {
            $result[] = $slide;
        }

        $running = false;
        return $result;
    }, 10, 2);

    // After saving a slide, redirect to the slideshow list instead of the slide list
    add_filter('redirect_post_location', function($location, $post_id) {
        if ( get_post_type($post_id) === 'slide' ) {
            $location = admin_url('edit.php?post_type=slideshow');
        }
        return $location;
    }, 10, 2);

    // Redirect edit.php?post_type=slide → edit.php?post_type=slideshow
    add_action('load-edit.php', function() {
        if ( isset($_GET['post_type']) && $_GET['post_type'] === 'slide' ) {
            wp_redirect( admin_url('edit.php?post_type=slideshow'), 301 );
            exit;
        }
    });

    // Add slide-parent-{ID} class to slide rows so JS can group them for drag/drop
    add_filter('post_class', function($classes, $class, $post_id) {
        if ( ! is_admin() ) return $classes;
        $screen = get_current_screen();
        if ( ! $screen || $screen->base !== 'edit' || $screen->post_type !== 'slideshow' ) return $classes;
        if ( get_post_type($post_id) === 'slide' ) {
            $classes[] = 'slide-parent-' . (int) get_post_field('post_parent', $post_id);
        }
        return $classes;
    }, 10, 3);

    // AJAX: persist slide order after drag/drop
    add_action('wp_ajax_mini_save_slide_order', function() {
        check_ajax_referer('mini_slide_order', 'nonce');
        if ( ! current_user_can('edit_posts') ) wp_send_json_error('Unauthorized', 403);
        $order = isset($_POST['order']) ? array_map('absint', (array) $_POST['order']) : [];
        foreach ( $order as $menu_order => $post_id ) {
            if ( $post_id ) {
                wp_update_post(['ID' => $post_id, 'menu_order' => $menu_order]);
            }
        }
        wp_send_json_success();
    });

    // AJAX: assign a slide to a slideshow (set post_parent)
    add_action('wp_ajax_mini_assign_slide_parent', function() {
        check_ajax_referer('mini_slide_order', 'nonce');
        if ( ! current_user_can('edit_posts') ) wp_send_json_error('Unauthorized', 403);
        $slide_id     = isset($_POST['slide_id'])     ? absint($_POST['slide_id'])     : 0;
        $slideshow_id = isset($_POST['slideshow_id']) ? absint($_POST['slideshow_id']) : 0;
        if ( ! $slide_id || get_post_type($slide_id) !== 'slide' ) wp_send_json_error('Invalid slide', 400);
        if ( $slideshow_id && get_post_type($slideshow_id) !== 'slideshow' ) wp_send_json_error('Invalid slideshow', 400);
        wp_update_post(['ID' => $slide_id, 'post_parent' => $slideshow_id]);
        wp_send_json_success();
    });

    // "Add New Slide" button + drag/drop reordering on the slideshow list
    add_action('admin_head-edit.php', function() {
        $screen = get_current_screen();
        if ( ! $screen || $screen->post_type !== 'slideshow' ) return;
        $new_slide_url = esc_url( admin_url('post-new.php?post_type=slide') );
        $nonce = wp_create_nonce('mini_slide_order');
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // "Add New Slide" button next to "Add New Slideshow"
            var existing = document.querySelector('.page-title-action');
            if ( existing ) {
                var btn = document.createElement('a');
                btn.href = '<?php echo $new_slide_url; ?>';
                btn.className = 'page-title-action';
                btn.textContent = '<?php echo esc_js(__('Add New Slide', 'mini')); ?>';
                existing.after(btn);
            }

            // Drag & drop slide reordering + orphan → slideshow assignment
            var dragging = null;
            var sourceGroup = null;

            function getGroup(row) {
                return Array.from(row.classList).find(function(c) {
                    return c.indexOf('slide-parent-') === 0;
                }) || null;
            }

            function isOrphan(row) {
                return row.classList.contains('slide-parent-0');
            }

            function saveOrder(group) {
                var order = [];
                document.querySelectorAll('tr.' + group).forEach(function(row) {
                    var id = row.id.replace('post-', '');
                    if ( id ) order.push(id);
                });
                var params = new URLSearchParams({
                    action: 'mini_save_slide_order',
                    nonce: '<?php echo esc_js($nonce); ?>'
                });
                order.forEach(function(id) { params.append('order[]', id); });
                fetch(ajaxurl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: params
                });
            }

            function assignParent(slideRow, slideshowId, insertBeforeRow) {
                var slideId = parseInt(slideRow.id.replace('post-', ''), 10);
                // Update class
                Array.from(slideRow.classList).forEach(function(c) {
                    if ( c.indexOf('slide-parent-') === 0 ) slideRow.classList.remove(c);
                });
                slideRow.classList.add('slide-parent-' + slideshowId);
                // For orphans (parent 0), always insert right after the separator
                if ( slideshowId === 0 ) {
                    var sep = document.querySelector('tr.mini-orphan-separator');
                    insertBeforeRow = sep ? sep.nextSibling : insertBeforeRow;
                }
                // Move row visually
                if ( insertBeforeRow ) {
                    insertBeforeRow.parentNode.insertBefore(slideRow, insertBeforeRow);
                } else {
                    var tbl = document.querySelector('#the-list') || document.querySelector('table.wp-list-table tbody');
                    if ( tbl ) tbl.appendChild(slideRow);
                }
                // Persist via AJAX
                var params = new URLSearchParams({
                    action: 'mini_assign_slide_parent',
                    nonce: '<?php echo esc_js($nonce); ?>',
                    slide_id: slideId,
                    slideshow_id: slideshowId
                });
                fetch(ajaxurl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: params
                });
            }

            // Always show separator for orphan slides (also acts as a drop target)
            var tbody = document.querySelector('#the-list') || document.querySelector('table.wp-list-table tbody');
            if ( tbody ) {
                var anyRow = tbody.querySelector('tr');
                var colspan = anyRow ? anyRow.querySelectorAll('td,th').length : 2;
                var sep = document.createElement('tr');
                sep.className = 'mini-orphan-separator';
                sep.innerHTML = '<td colspan="' + colspan + '" style="padding:6px 10px;background:#f0f0f0;border-top:2px solid #c3c4c7;color:#50575e;font-style:italic;cursor:default;"><?php echo esc_js(__('Slides without a parent slideshow', 'mini')); ?></td>';
                // Insert before the first orphan, or append at the end
                var firstOrphan = tbody.querySelector('tr.slide-parent-0');
                if ( firstOrphan ) {
                    tbody.insertBefore(sep, firstOrphan);
                } else {
                    tbody.appendChild(sep);
                }
                // Drop target: drop any slide onto the separator to unassign it
                sep.addEventListener('dragover', function(e) {
                    if ( !dragging || !dragging.classList.contains('type-slide') ) return;
                    if ( isOrphan(dragging) ) return; // already orphan
                    e.preventDefault();
                    e.dataTransfer.dropEffect = 'move';
                    sep.querySelector('td').style.background = '#dde';
                });
                sep.addEventListener('dragleave', function() {
                    sep.querySelector('td').style.background = '#f0f0f0';
                });
                sep.addEventListener('drop', function(e) {
                    e.preventDefault();
                    sep.querySelector('td').style.background = '#f0f0f0';
                    if ( !dragging || !dragging.classList.contains('type-slide') || isOrphan(dragging) ) return;
                    assignParent(dragging, 0, sep.nextSibling || null);
                    dragging = null;
                    sourceGroup = null;
                });
            }

            // Allow dropping any slide onto a slideshow row to assign/reassign its parent
            document.querySelectorAll('tr.type-slideshow').forEach(function(swRow) {
                swRow.addEventListener('dragover', function(e) {
                    if ( !dragging || !dragging.classList.contains('type-slide') ) return;
                    var slideshowId = parseInt(swRow.id.replace('post-', ''), 10);
                    // Don't allow dropping onto the slide's current parent
                    if ( dragging.classList.contains('slide-parent-' + slideshowId) ) return;
                    e.preventDefault();
                    e.dataTransfer.dropEffect = 'move';
                    swRow.classList.add('mini-drag-over');
                });
                swRow.addEventListener('dragleave', function(e) {
                    if ( !swRow.contains(e.relatedTarget) ) swRow.classList.remove('mini-drag-over');
                });
                swRow.addEventListener('drop', function(e) {
                    e.preventDefault();
                    swRow.classList.remove('mini-drag-over');
                    if ( !dragging || !dragging.classList.contains('type-slide') ) return;
                    var slideshowId = parseInt(swRow.id.replace('post-', ''), 10);
                    if ( dragging.classList.contains('slide-parent-' + slideshowId) ) return;
                    // Insert after the last slide already belonging to this slideshow
                    var siblings = Array.from(swRow.parentNode.children);
                    var insertBefore = null;
                    var swIdx = siblings.indexOf(swRow);
                    for ( var i = swIdx + 1; i < siblings.length; i++ ) {
                        if ( !siblings[i].classList.contains('slide-parent-' + slideshowId) ) {
                            insertBefore = siblings[i];
                            break;
                        }
                    }
                    if ( !insertBefore ) insertBefore = swRow.nextSibling;
                    assignParent(dragging, slideshowId, insertBefore);
                    dragging = null;
                    sourceGroup = null;
                });
            });

            document.querySelectorAll('tr.type-slide').forEach(function(row) {
                // Insert drag handle at the start of the title cell
                var td = row.querySelector('td.column-title, td:first-child');
                if ( td ) {
                    var handle = document.createElement('span');
                    handle.className = 'mini-drag-handle dashicons dashicons-menu';
                    td.insertBefore(handle, td.firstChild);
                }

                row.setAttribute('draggable', 'true');

                row.addEventListener('dragstart', function(e) {
                    dragging = row;
                    sourceGroup = getGroup(row);
                    setTimeout(function() { row.classList.add('mini-dragging'); }, 0);
                    e.dataTransfer.effectAllowed = 'move';
                    e.dataTransfer.setData('text/plain', row.id);
                });

                row.addEventListener('dragend', function() {
                    row.classList.remove('mini-dragging');
                    document.querySelectorAll('.mini-drag-over').forEach(function(r) {
                        r.classList.remove('mini-drag-over');
                    });
                    // Only save order if the drop stayed within the same group (not a parent reassignment)
                    if ( dragging && sourceGroup ) saveOrder(sourceGroup);
                    dragging = null;
                    sourceGroup = null;
                });

                row.addEventListener('dragover', function(e) {
                    if ( !dragging || dragging === row || getGroup(row) !== sourceGroup ) return;
                    e.preventDefault();
                    e.dataTransfer.dropEffect = 'move';
                    document.querySelectorAll('.mini-drag-over').forEach(function(r) {
                        r.classList.remove('mini-drag-over');
                    });
                    row.classList.add('mini-drag-over');
                });

                row.addEventListener('dragleave', function(e) {
                    if ( !row.contains(e.relatedTarget) ) row.classList.remove('mini-drag-over');
                });

                row.addEventListener('drop', function(e) {
                    e.preventDefault();
                    row.classList.remove('mini-drag-over');
                    if ( !dragging || dragging === row || getGroup(row) !== sourceGroup ) return;
                    var tbody = row.parentNode;
                    var rows = Array.from(tbody.children);
                    var dragIdx = rows.indexOf(dragging);
                    var targetIdx = rows.indexOf(row);
                    if ( dragIdx < targetIdx ) {
                        tbody.insertBefore(dragging, row.nextSibling);
                    } else {
                        tbody.insertBefore(dragging, row);
                    }
                });
            });
        });
        </script>
        <?php
    });
}
/* END - Custom post type - SLIDESHOW */

/* START - Custom post type - EVENT */
if (is_mini_option_enabled('mini_content_settings', 'mini_event')) {
    add_action('init', function() {
        register_mini_post_type('event', 'Event', 'Events', 'dashicons-calendar');
        register_mini_taxonomy('event_category', 'Category', 'Categories', 'event');
        register_mini_tag_taxonomy('event_tag', 'event');
    });

    // Add "Date" and "Location" columns to the event list table
    add_filter('manage_event_posts_columns', function($columns) {
        $new = [];
        foreach ( $columns as $key => $label ) {
            $new[$key] = $label;
            if ( $key === 'title' ) {
                $new['event_date']     = __('Event date', 'mini');
                $new['event_location'] = __('Location', 'mini');
            }
        }
        return $new;
    });

    add_action('manage_event_posts_custom_column', function($column, $post_id) {
        if ( $column === 'event_date' ) {
            $date = get_post_meta($post_id, 'event_date', true);
            if ( $date ) {
                $timestamp = strtotime($date);
                echo $timestamp ? esc_html( date_i18n( get_option('date_format'), $timestamp ) ) : esc_html($date);
            } else {
                echo '—';
            }
        }
        if ( $column === 'event_location' ) {
            $name    = get_post_meta($post_id, 'location_name', true);
            $address = get_post_meta($post_id, 'location_address', true);
            if ( $name || $address ) {
                echo implode('<br/>', array_filter([$name, $address]) );
            } else {
                echo '—';
            }
        }
    }, 10, 2);

    add_filter('manage_edit-event_sortable_columns', function($columns) {
        $columns['event_date'] = 'event_date';
        return $columns;
    });

    add_action('pre_get_posts', function($query) {
        if ( ! is_admin() || ! $query->is_main_query() ) return;
        if ( $query->get('post_type') !== 'event' ) return;
        if ( $query->get('orderby') === 'event_date' ) {
            $query->set('meta_key', 'event_date');
            $query->set('orderby', 'meta_value');
        } elseif ( ! $query->get('orderby') ) {
            // Default order: by event date ascending
            $query->set('meta_key', 'event_date');
            $query->set('orderby', 'meta_value');
            $query->set('order', 'DESC');
        }
    });
}

/* START - Custom post type - MATCH */
if (is_mini_option_enabled('mini_content_settings', 'mini_match')) {
    add_action('init', function() {
        register_mini_post_type('match', 'Match', 'Matches', 'dashicons-superhero');
        register_mini_taxonomy('match_category', 'Category', 'Categories', 'match');
        register_mini_tag_taxonomy('match_tag', 'match');
    });
}

/* START - Custom post type - COURSE */
if (is_mini_option_enabled('mini_content_settings', 'mini_course')) {
    add_action('init', function() {
        register_mini_post_type('course', 'Course', 'Courses', 'dashicons-welcome-learn-more');

        // Lesson uses post_parent for course relationship (admin UI only), not for hierarchical URLs
        register_mini_post_type('lesson', 'Lesson', 'Lessons', 'dashicons-book', false, false, false);

        register_mini_taxonomy('course_category', 'Category', 'Categories', ['course', 'lesson']);
        register_mini_tag_taxonomy('course_tag', ['course', 'lesson']);
    });

    // Shared callback: render date + location columns for course and lesson
    $mini_course_columns_cb = function($columns) {
        $new = [];
        foreach ( $columns as $key => $label ) {
            $new[$key] = $label;
            if ( $key === 'title' ) {
                $new['event_date']     = __('Date', 'mini');
                $new['event_location'] = __('Location', 'mini');
            }
        }
        return $new;
    };
    add_filter('manage_course_posts_columns', $mini_course_columns_cb);
    add_filter('manage_lesson_posts_columns', $mini_course_columns_cb);

    $mini_course_column_content_cb = function($column, $post_id) {
        if ( $column === 'event_date' ) {
            $date = get_post_meta($post_id, 'event_date', true);
            if ( $date ) {
                $timestamp = strtotime($date);
                echo $timestamp ? esc_html( date_i18n( get_option('date_format'), $timestamp ) ) : esc_html($date);
            } else {
                echo '—';
            }
        }
        if ( $column === 'event_location' ) {
            $name    = get_post_meta($post_id, 'location_name', true);
            $address = get_post_meta($post_id, 'location_address', true);
            if ( $name || $address ) {
                echo esc_html( implode(', ', array_filter([$name, $address])) );
            } else {
                echo '—';
            }
        }
    };
    add_action('manage_course_posts_custom_column', $mini_course_column_content_cb, 10, 2);
    add_action('manage_lesson_posts_custom_column', $mini_course_column_content_cb, 10, 2);

    $mini_course_sortable_cb = function($columns) {
        $columns['event_date'] = 'event_date';
        return $columns;
    };
    add_filter('manage_edit-course_sortable_columns', $mini_course_sortable_cb);
    add_filter('manage_edit-lesson_sortable_columns', $mini_course_sortable_cb);

    add_action('pre_get_posts', function($query) {
        if ( ! is_admin() || ! $query->is_main_query() ) return;
        $pt = $query->get('post_type');
        if ( ! in_array($pt, ['course', 'lesson'], true) ) return;
        if ( $query->get('orderby') === 'event_date' || ! $query->get('orderby') ) {
            $query->set('meta_key', 'event_date');
            $query->set('orderby', 'meta_value');
            if ( ! $query->get('order') ) $query->set('order', 'ASC');
        }
    });
}
/* END - Custom post type - COURSE */

/**
 * Custom parent meta boxes for slide → slideshow and lesson → course.
 *
 * WordPress's built-in parent dropdown (via page-attributes) uses get_pages(),
 * which only works for hierarchical post types. Since slideshow and course are
 * not hierarchical, we replace the meta box entirely with a custom one that uses
 * get_posts() directly.
 *
 * WordPress core saves 'parent_id' → post_parent and 'menu_order' automatically,
 * so no custom save_post hook is needed.
 */
add_action('add_meta_boxes', function() {
    if (is_mini_option_enabled('mini_content_settings', 'mini_event')) {
        add_meta_box(
            'mini_event_poster',
            __('Poster', 'mini'),
            'mini_event_poster_meta_box',
            'event',
            'side',
            'default'
        );
    }
    if (is_mini_option_enabled('mini_content_settings', 'mini_slide')) {
        add_meta_box(
            'mini_slide_parent',
            __('Slideshow', 'mini'),
            'mini_slide_parent_meta_box',
            'slide',
            'side',
            'high'
        );
        add_meta_box(
            'mini_page_slideshow',
            __('Slideshow', 'mini'),
            'mini_page_slideshow_meta_box',
            'page',
            'side',
            'default'
        );
        add_meta_box(
            'mini_slideshow_layout',
            __('Layout', 'mini'),
            'mini_slideshow_layout_meta_box',
            'slideshow',
            'side',
            'default'
        );
    }
    if (is_mini_option_enabled('mini_content_settings', 'mini_course')) {
        add_meta_box(
            'mini_lesson_parent',
            __('Course', 'mini'),
            'mini_lesson_parent_meta_box',
            'lesson',
            'side',
            'high'
        );
    }
});

function mini_event_poster_meta_box($post) {
    $poster_id = (int) get_post_meta($post->ID, 'event_poster_id', true);
    $poster_url = $poster_id ? wp_get_attachment_image_url($poster_id, 'medium') : '';
    wp_nonce_field('mini_event_poster_save', 'mini_event_poster_nonce');
    ?>
    <div class="mini-meta-row">
        <div class="mini-meta-field">
            <div id="mini-event-poster-preview" style="<?php echo $poster_url ? '' : 'display:none;'; ?>margin-bottom:8px;">
                <img src="<?php echo esc_url($poster_url); ?>" style="max-width:100%;height:auto;display:block;" />
            </div>
            <input type="hidden" name="event_poster_id" id="mini-event-poster-id" value="<?php echo esc_attr($poster_id ?: ''); ?>" />
            <button type="button" class="button" id="mini-event-poster-select"><?php esc_html_e('Select / Upload Poster', 'mini'); ?></button>
            <button type="button" class="button" id="mini-event-poster-remove" style="<?php echo $poster_url ? '' : 'display:none;'; ?>margin-top:4px;"><?php esc_html_e('Remove', 'mini'); ?></button>
        </div>
    </div>
    <script>
    (function() {
        var frame;
        document.getElementById('mini-event-poster-select').addEventListener('click', function(e) {
            e.preventDefault();
            if ( frame ) { frame.open(); return; }
            frame = wp.media({
                title: '<?php echo esc_js(__('Select Poster', 'mini')); ?>',
                button: { text: '<?php echo esc_js(__('Use this image', 'mini')); ?>' },
                multiple: false,
                library: { type: 'image' }
            });
            frame.on('select', function() {
                var att = frame.state().get('selection').first().toJSON();
                document.getElementById('mini-event-poster-id').value = att.id;
                var preview = document.getElementById('mini-event-poster-preview');
                preview.querySelector('img').src = att.sizes && att.sizes.medium ? att.sizes.medium.url : att.url;
                preview.style.display = '';
                document.getElementById('mini-event-poster-remove').style.display = '';
            });
            frame.open();
        });
        document.getElementById('mini-event-poster-remove').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('mini-event-poster-id').value = '';
            var preview = document.getElementById('mini-event-poster-preview');
            preview.querySelector('img').src = '';
            preview.style.display = 'none';
            this.style.display = 'none';
        });
    })();
    </script>
    <?php
}

function mini_slide_parent_meta_box($post) {
    $slideshows = get_posts([
        'post_type'      => 'slideshow',
        'posts_per_page' => -1,
        'post_status'    => ['publish', 'draft'],
        'orderby'        => 'title',
        'order'          => 'ASC',
    ]);
    ?>
    <div class="mini-meta-row">
        <div class="mini-meta-field">
            <label for="mini_slide_parent"><?php esc_html_e('Parent Slideshow', 'mini'); ?>:</label>
            <?php
            $preselected = $post->post_parent;
            if ( ! $preselected && isset($_GET['slideshow']) ) {
                $preselected = absint($_GET['slideshow']);
            }
            ?>
            <select name="parent_id" id="mini_slide_parent_select">
                <option value="0"><?php esc_html_e('— No slideshow —', 'mini'); ?></option>
                <?php foreach ($slideshows as $sw) : ?>
                <option value="<?php echo esc_attr($sw->ID); ?>"<?php selected($preselected, $sw->ID); ?>>
                    <?php echo esc_html($sw->post_title); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="mini-meta-row">
        <div class="mini-meta-field">
            <label for="mini_slide_order"><?php esc_html_e('Order', 'mini'); ?>:</label>
            <input type="number" name="menu_order" id="mini_slide_order" value="<?php echo esc_attr($post->menu_order); ?>" min="0" />
        </div>
    </div>
    <?php
}

function mini_lesson_parent_meta_box($post) {
    $courses = get_posts([
        'post_type'      => 'course',
        'posts_per_page' => -1,
        'post_status'    => ['publish', 'draft'],
        'orderby'        => 'title',
        'order'          => 'ASC',
    ]);
    ?>
    <div class="mini-meta-row">
        <div class="mini-meta-field">
            <label for="mini_lesson_parent_select"><?php esc_html_e('Parent Course', 'mini'); ?>:</label>
            <select name="parent_id" id="mini_lesson_parent_select">
                <option value="0"><?php esc_html_e('— No course —', 'mini'); ?></option>
                <?php foreach ($courses as $course) : ?>
                <option value="<?php echo esc_attr($course->ID); ?>"<?php selected($post->post_parent, $course->ID); ?>>
                    <?php echo esc_html($course->post_title); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="mini-meta-row">
        <div class="mini-meta-field">
            <label for="mini_lesson_order"><?php esc_html_e('Order', 'mini'); ?>:</label>
            <input type="number" name="menu_order" id="mini_lesson_order" value="<?php echo esc_attr($post->menu_order); ?>" min="0" />
        </div>
    </div>
    <?php
}

function mini_page_slideshow_meta_box($post) {
    $slideshows = get_posts([
        'post_type'      => 'slideshow',
        'posts_per_page' => -1,
        'post_status'    => ['publish', 'draft'],
        'orderby'        => 'title',
        'order'          => 'ASC',
    ]);
    $current = get_post_meta($post->ID, '_mini_page_slideshow', true);
    wp_nonce_field('mini_page_slideshow_save', 'mini_page_slideshow_nonce');
    ?>
    <div class="mini-meta-row">
        <div class="mini-meta-field">
            <label for="mini_page_slideshow_select"><?php esc_html_e('Slideshow', 'mini'); ?>:</label>
            <select name="mini_page_slideshow" id="mini_page_slideshow_select">
                <option value=""><?php esc_html_e('— None —', 'mini'); ?></option>
                <?php foreach ($slideshows as $sw) : ?>
                <option value="<?php echo esc_attr($sw->ID); ?>"<?php selected($current, $sw->ID); ?>>
                    <?php echo esc_html($sw->post_title); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <?php
}

function mini_slideshow_layout_meta_box($post) {
    $container = get_post_meta($post->ID, 'page_container', true);
    $content_width = get_post_meta($post->ID, 'content_width', true);
    wp_nonce_field('mini_slideshow_layout_save', 'mini_slideshow_layout_nonce');
    ?>
    <div class="mini-meta-row">
        <div class="mini-meta-field">
            <label for="mini_slideshow_container"><?php esc_html_e('Container', 'mini'); ?>:</label>
            <select name="page_container" id="mini_slideshow_container">
                <option value="fw"<?php selected($container, 'fw'); ?>><?php esc_html_e('Full width', 'mini'); ?></option>
                <option value=""<?php selected($container, ''); ?>><?php esc_html_e('Standard', 'mini'); ?></option>
                <option value="wide"<?php selected($container, 'wide'); ?>><?php esc_html_e('Wide', 'mini'); ?></option>
                <option value="thin"<?php selected($container, 'thin'); ?>><?php esc_html_e('Thin', 'mini'); ?></option>
            </select>
        </div>
    </div>
    <div class="mini-meta-row">
        <div class="mini-meta-field">
            <label for="mini_slideshow_content_width"><?php esc_html_e('Content width', 'mini'); ?>:</label>
            <select name="content_width" id="mini_slideshow_content_width">
                <option value="box-100"<?php selected($content_width !== 'box-66', true); ?>><?php esc_html_e('Full', 'mini'); ?></option>
                <option value="box-66"<?php selected($content_width, 'box-66'); ?>><?php esc_html_e('2/3', 'mini'); ?></option>
            </select>
        </div>
    </div>
    <?php
}

add_action('admin_enqueue_scripts', function($hook) {
    if ( ! is_mini_option_enabled('mini_content_settings', 'mini_event') ) return;
    if ( ! in_array($hook, ['post.php', 'post-new.php'], true) ) return;
    $screen = get_current_screen();
    if ( $screen && $screen->post_type === 'event' ) {
        wp_enqueue_media();
    }
});

add_action('save_post_event', function($post_id) {
    if ( ! isset($_POST['mini_event_poster_nonce']) ) return;
    if ( ! wp_verify_nonce($_POST['mini_event_poster_nonce'], 'mini_event_poster_save') ) return;
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
    if ( ! current_user_can('edit_post', $post_id) ) return;

    if ( ! empty($_POST['event_poster_id']) ) {
        update_post_meta($post_id, 'event_poster_id', absint($_POST['event_poster_id']));
    } else {
        delete_post_meta($post_id, 'event_poster_id');
    }
});

add_action('save_post_slideshow', function($post_id) {
    if ( ! isset($_POST['mini_slideshow_layout_nonce']) ) return;
    if ( ! wp_verify_nonce($_POST['mini_slideshow_layout_nonce'], 'mini_slideshow_layout_save') ) return;
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
    if ( ! current_user_can('edit_post', $post_id) ) return;

    $allowed_containers = ['fw', '', 'wide', 'thin'];
    $container = isset($_POST['page_container']) ? sanitize_text_field($_POST['page_container']) : '';
    if ( in_array($container, $allowed_containers, true) ) {
        update_post_meta($post_id, 'page_container', $container);
    }

    $allowed_widths = ['box-100', 'box-66'];
    $content_width = isset($_POST['content_width']) ? sanitize_text_field($_POST['content_width']) : 'box-100';
    if ( in_array($content_width, $allowed_widths, true) ) {
        update_post_meta($post_id, 'content_width', $content_width);
    }
});

add_action('save_post_page', function($post_id) {
    if ( ! isset($_POST['mini_page_slideshow_nonce']) ) return;
    if ( ! wp_verify_nonce($_POST['mini_page_slideshow_nonce'], 'mini_page_slideshow_save') ) return;
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
    if ( ! current_user_can('edit_post', $post_id) ) return;

    if ( ! empty($_POST['mini_page_slideshow']) ) {
        update_post_meta($post_id, '_mini_page_slideshow', absint($_POST['mini_page_slideshow']));
    } else {
        delete_post_meta($post_id, '_mini_page_slideshow');
    }
});

/* START - Sync Main Menu items with content type toggles */
/**
 * Content types that should appear in the Main Menu when enabled.
 * Key  = mini_content_settings option key.
 * label = default menu item title.
 * post_type = the registered post type slug (used for archive link + deduplication).
 */
function mini_content_type_menu_map() {
    return [
        'mini_news'   => [ 'label' => __('News', 'mini'),    'post_type' => 'news' ],
        'mini_event'  => [ 'label' => __('Events', 'mini'),  'post_type' => 'event' ],
        'mini_match'  => [ 'label' => __('Matches', 'mini'), 'post_type' => 'match' ],
        'mini_course' => [ 'label' => __('Courses', 'mini'), 'post_type' => 'course' ],
    ];
}

/**
 * Add or remove a post-type-archive item in the Main Menu.
 */
function mini_sync_main_menu_item( $post_type, $label, $add ) {
    $menu = wp_get_nav_menu_object( 'Main Menu' );
    if ( ! $menu ) return;

    $menu_id    = $menu->term_id;
    $menu_items = wp_get_nav_menu_items( $menu_id, [ 'post_status' => 'any' ] ) ?: [];

    // Remove any existing item pointing to this post type archive
    foreach ( $menu_items as $item ) {
        if ( $item->type === 'post_type_archive' && $item->object === $post_type ) {
            wp_delete_post( $item->ID, true );
        }
    }

    if ( $add ) {
        wp_update_nav_menu_item( $menu_id, 0, [
            'menu-item-title'  => $label,
            'menu-item-type'   => 'post_type_archive',
            'menu-item-object' => $post_type,
            'menu-item-status' => 'publish',
        ] );
    }
}

/**
 * Fires after mini_content_settings is saved.
 * Syncs Main Menu items for each managed content type.
 */
add_action( 'update_option_mini_content_settings', function( $old_value, $new_value ) {
    foreach ( mini_content_type_menu_map() as $key => $config ) {
        $was_enabled = ! empty( $old_value[ $key ] );
        $is_enabled  = ! empty( $new_value[ $key ] );
        if ( $was_enabled !== $is_enabled ) {
            mini_sync_main_menu_item( $config['post_type'], $config['label'], $is_enabled );
        }
    }
}, 10, 2 );
/* END - Sync Main Menu items with content type toggles */
