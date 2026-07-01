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

- `mini-plugin.php` — main plugin bootstrap (~2000 lines): plugin header, textdomain loading, and a substantial amount of shared helper functions defined inline (`is_mini_option_enabled()`, `get_variable()`, etc.) rather than split into `inc/`. Check here first for a utility function before assuming it must be in `inc/`.
- `inc/` — concern-specific includes: `content-types.php`/`post-types.php` (the custom content types), `shortcodes.php`, `seo.php`, `contact-form.php`, `gdpr.php`, `smtp.php`, `login.php`, `security.php`, `pwa.php`, `dashboard.php`/`backoffice.php` (admin UI), `user.php`, `translations.php`, `ai.php`.
- `media-upload/` — `.af` file format + custom MIME type support assets (`media-upload.css`/`.js`).
- Admin config lives under a **mini** top-level menu in wp-admin, with sub-pages per concern (Contents, SEO, Email, etc.) — `inc/dashboard.php`/`inc/backoffice.php` register these.

## Shortcodes (registered in `inc/shortcodes.php`)

Events: `[next_event]`, `[next_events]`, `[next_3_events]`, `[next_4_events]`.
Matches: `[next_match]`, `[next_matches]`, `[next_3_matches]`, `[next_4_matches]`.
Content: `[latest_news]`, `[mini_posts]`.

## Notes

- Enabling a content type (Slides/News/Events/Matches/Courses) is a per-site admin toggle under **mini → Contents**, not a code change — check whether a "missing" custom post type is actually just disabled in that site's settings before assuming it needs implementing.
- The Slides content type pulls in `slider.js` from the parent theme/mini framework, not from this plugin.
