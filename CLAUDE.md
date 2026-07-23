# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this repo is

The canonical "mini" WordPress plugin — extends WordPress with custom content types (slides, news, events, matches, courses), shortcodes, SEO tooling, GDPR/contact-form helpers, SMTP/login/security tweaks, and enhanced media upload support. Plain PHP, no build step, no JS bundler — there's no `package.json`/`composer.json` here.

## Deployment topology — check this before editing anything

**This repo is symlinked into multiple live WordPress installs; it is not edited in place per-site.**

- Canonical source: `/home/projects/mini/wp/mini-plugin/` (this repo).
- Symlinked as `wp-content/plugins/mini-plugin` in `uwa/wp` and as `wp-content/plugins/mini` in the methodos site — **single source of truth, edit once here, changes are live everywhere immediately** (same inode, not a copy). There is no separate "synced copy" to maintain for the plugin — unlike the parent theme, there's no child/fork variant of the plugin itself.

Before editing, run `ls -la` (or `readlink -f`) on the target path if there's any doubt whether you're looking at this canonical source or a site's symlink to it.

**Default scope:** when a request is about one specific site, prefer a site-local solution (e.g. a snippet in that site's own `functions.php` overriding a shortcode callback after the plugin registers it) over changing this shared plugin — even for a backward-compatible change. Promoting a site-specific feature into this canonical plugin is a deliberate, separate decision made later, not a default.

## Structure

- `mini-plugin.php` — main plugin bootstrap (~2000 lines): plugin header, textdomain loading, and a substantial amount of shared helper functions defined inline (`is_mini_option_enabled()`, `get_variable()`, `get_italian_date_formatters()`, etc.) rather than split into `inc/`. Check here first for a utility function before assuming it must be in `inc/`.
- `inc/` — concern-specific includes:
  - `content-types.php` — registers custom post types (slideshow/slide, news, event, match, course, landing_page) and their admin UX (list-table grouping, drag-drop order, capability groups)
  - `shortcodes.php` — all shortcodes including `[slideshow]`/`[slider]` (language-aware), event/match/news/posts shortcodes
  - `seo.php` — per-post SEO meta box: title, description, OG tags, Twitter cards, image selection
  - `translations.php` — full multilingual module (see below)
  - `contact-form.php` — contact form handling
  - `gdpr.php` — GDPR / consent tooling
  - `smtp.php` — custom SMTP configuration
  - `login.php` — login page customization
  - `security.php` — security hardening
  - `pwa.php` — Progressive Web App manifest/service worker hooks
  - `dashboard.php` / `backoffice.php` — admin UI: registers the **mini** top-level menu and all sub-pages (Contents, SEO, Email, Backoffice, GDPR, Translations, …)
  - `user.php` — user-related helpers
  - `ai.php` — AI integration helpers
- `media-upload/` — `.af` file format + custom MIME type support assets (`media-upload.css`/`.js`).

## Translations module (`inc/translations.php`)

A complete multilingual system for posts, pages, and custom post types:
- **Language tagging** — `_mini_lang` post meta; set per post via a sidebar meta box in the editor.
- **Translation linking** — `_mini_translations` post meta maps lang codes to post IDs (bidirectional). Managed via the same meta box.
- **URL prefixes** — rewrite rules for `/{lang}/slug/` URLs; auto-flushed via a hash-based check (not a full rewrite_rules scan).
- **Request resolver** — `request` filter resolves lang-prefixed paths to the correct post and installs a targeted `redirect_canonical` guard (only blocks redirects that would strip the lang prefix).
- **Permalink filter** — `post_link`/`post_type_link`/`page_link` filters prepend `/{lang}/` for tagged posts.
- **hreflang + og:locale** — output automatically in `<head>`.
- **Language switcher** — rendered in `mini-theme/header.php`; reads `mini_translations_get_languages()` and `mini_get_all_translation_urls()`.
- **Language-aware nav menus** — `wp_nav_menu_objects` filter swaps menu item URLs/titles to the current language's translation. Configurable: items with no translation can be hidden (default) or shown in their original language.
- **Language-aware `[slideshow]` shortcode** — auto-swaps to the translated slideshow based on the current page's language.
- **Supported post types**: `post`, `page`, `news`, `event`, `match`, `course`, `lesson`, `landing_page`, `slideshow`, `slide` (each gated on its content-type toggle).
- **Settings**: `mini_translations_settings` option group (`mini_enable_translations`, `languages` textarea, `mini_nav_show_untranslated`).

## Shortcodes (registered in `inc/shortcodes.php`)

Slideshows: `[slideshow slideshow="slug"]` / `[slider slideshow="slug"]` — language-aware (auto-swaps to translated slideshow).
Events: `[next_event]`, `[next_events]`, `[next_3_events]`, `[next_4_events]`.
Matches: `[next_match]`, `[next_matches]`, `[next_3_matches]`, `[next_4_matches]`.
Content: `[latest_news]`, `[mini_posts]`.

## Notes

- Enabling a content type (Slides/News/Events/Matches/Courses/Landing Pages) is a per-site admin toggle under **mini → Contents**, not a code change — check whether a "missing" custom post type is actually just disabled in that site's settings before assuming it needs implementing.
- The Slides content type pulls in `slider.js` from the parent theme/mini framework, not from this plugin.
- SVG uploads are gated to `manage_options` users only (admins). `.af` uploads are unconditional.
- `get_italian_date_formatters()` reads `get_locale()` and `get_option('timezone_string')` — it is no longer hardcoded to `it_IT`/`Europe/Rome`, despite its legacy name.
