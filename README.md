# mini WordPress Plugin

A WordPress plugin that extends WordPress with custom content types, multilingual support, SEO tooling, shortcodes, and a collection of site-management utilities. It is the companion plugin to the [mini WordPress theme](https://mini.uwa.agency/).

## Features

### Custom Content Types

Each type is individually toggleable under **mini → Contents**:

| Type | Description |
|------|-------------|
| **Slideshows / Slides** | Slideshow and slide management; loads `slider.js`. Use `[slideshow slideshow="slug"]` to embed. |
| **News** | News articles with their own archive. |
| **Events** | Events with date, end date, time, end time, and location meta boxes. |
| **Matches** | Sports matches with team names, logos, and scores. |
| **Courses / Lessons** | Hierarchical course content. |
| **Landing Pages** | Dedicated landing pages with optional header/nav suppression per page. |

### Multilingual Support (Translations)

A complete multilingual system without requiring a third-party plugin:

- **Language tagging** — assign a language to any post, page, or custom post type via a sidebar meta box in the editor.
- **Translation linking** — link posts to their translations in other languages (bidirectional, stored as post meta).
- **Language-prefixed URLs** — pages are served at `/{lang}/slug/` (e.g. `/it/chi-siamo/`). Rewrite rules are flushed automatically when the language list changes.
- **hreflang + og:locale** — correct tags output automatically in `<head>`.
- **Language switcher** — rendered by the mini theme in the site header; switches the visitor to the same page in their chosen language.
- **Language-aware nav menus** — menu items automatically point to the current language's translation. Items with no translation can be hidden (default) or shown in their original language — configurable under **mini → Translations**.
- **Language-aware `[slideshow]` shortcode** — when embedded on a translated page, the shortcode automatically shows the slideshow in the current language.

Configure under **mini → Translations**. Supported post types: posts, pages, news, events, matches, courses, lessons, landing pages, slideshows, and slides.

### SEO

Per-post SEO meta box with:
- Custom meta title and description with character counters
- Open Graph tags (og:title, og:description, og:image, og:locale)
- Twitter Card tags
- Custom SEO image per post/page (with dimension feedback for optimal 1200×630 px)
- Keyword analysis tool

Configure global defaults under **mini → SEO**.

### Shortcodes

| Shortcode | Output |
|-----------|--------|
| `[slideshow slideshow="slug"]` | Embeds the named slideshow (language-aware) |
| `[next_event]` | Next upcoming event |
| `[next_events]` | Next 3 events, 3-column layout |
| `[next_3_events]` | Next 3 events |
| `[next_4_events]` | Next 4 events, 2-column layout |
| `[next_match]` | Next upcoming match |
| `[next_matches]` | Next 3 matches, 3-column layout |
| `[next_3_matches]` | Next 3 matches |
| `[next_4_matches]` | Next 4 matches, 2-column layout |
| `[latest_news]` | Latest news articles |
| `[mini_posts]` | Posts with customizable parameters |

### Media Uploads

- `.af` file format support (custom proprietary format)
- SVG upload support — restricted to administrators (`manage_options`) to prevent XSS from untrusted users

### Navigation Menus

Automatically registers three menu locations on activation:
- **Main Menu**
- **Footer Menu**
- **User Menu**

### Site Management Utilities

- **Email / SMTP** — custom SMTP configuration (**mini → Email**)
- **Login** — login page customization
- **Backoffice** — admin UI tweaks, Gutenberg block settings, legacy editor settings
- **GDPR** — privacy policy, cookie policy, and consent banner management
- **Security** — security hardening options
- **PWA** — Progressive Web App manifest and service worker hooks
- **Comments** — global comment disabling toggle

### Developer Utilities

- `is_mini_option_enabled($group, $option)` — check a plugin option
- `get_variable($group, $option)` — retrieve a plugin option value
- `get_italian_date_formatters()` — locale-aware `IntlDateFormatter` instances (reads WP site locale and timezone, despite the legacy name)
- `mini_get_post_lang($post_id)`, `mini_get_translations($post_id)`, `mini_get_all_translation_urls($post_id)` — translation helpers

## Installation

This plugin is distributed by filesystem symlink — it is not published on wordpress.org. To install on a new site, symlink the canonical source into `wp-content/plugins/`.

After activating, configure each feature area under the **mini** top-level menu in wp-admin.

## Requirements

- WordPress 5.0+
- PHP 7.4+
- `intl` PHP extension (for locale-aware date formatting)

## Author

**Giacomo Rizzotti** — [giacomorizzotti.com](https://www.giacomorizzotti.com/) · [mini.uwa.agency](https://mini.uwa.agency/)
