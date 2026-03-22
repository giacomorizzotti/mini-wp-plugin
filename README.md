# Mini WordPress Plugin

A comprehensive WordPress plugin that extends WordPress functionality with custom content types, shortcodes, SEO tools, and enhanced media support.

## Features

### 🎯 Custom Content Types
Enable and manage various content types to extend your WordPress site's capabilities:

- **Slides**: Create and manage slideshow content with the included slider.js library
- **News**: Manage news articles as a separate content type
- **Events**: Organize events with date, time, and location information
- **Matches**: Handle sports events and match scheduling
- **Courses**: Structure educational content with lessons and course management

### 🎨 Default Menus
Automatically creates and registers essential navigation menus:
- Main Menu
- Footer Menu
- User Menu

### ⚡ Shortcodes
Use these shortcodes to display dynamic content on your pages and posts:

#### Event Shortcodes
- `[next_event]` - Display the next upcoming event
- `[next_events]` - Display next 3 events in a 3-column layout
- `[next_3_events]` - Display next 3 events
- `[next_4_events]` - Display next 4 events in a 2-column layout

#### Match Shortcodes
- `[next_match]` - Display the next upcoming match
- `[next_matches]` - Display next 3 matches in a 3-column layout
- `[next_3_matches]` - Display next 3 matches
- `[next_4_matches]` - Display next 4 matches in a 2-column layout

#### Content Shortcodes
- `[latest_news]` - Display latest news articles
- `[mini_posts]` - Display posts with customizable parameters

### 🔍 SEO Enhancement
Built-in SEO tools for better search engine optimization:
- Meta title and description management
- Open Graph tags for social media sharing
- Twitter Card support
- Character counters for optimal title/description lengths
- Custom SEO image selection per post/page

### 📁 Media Upload Support
Enhanced media library with support for:
- `.af` file format uploads
- Custom MIME type handling
- Improved file type validation

### 🛠️ Utility Functions
Helper functions for developers:
- Italian date formatting with proper localization
- Option management utilities
- Checkbox option rendering
- Variable retrieval functions

## Installation

1. Download the plugin files
2. Upload the `mini-plugin` folder to `/wp-content/plugins/`
3. Activate the plugin through the WordPress admin dashboard
4. Configure settings under the "mini" menu in the admin panel

## Configuration

### Content Types Setup
1. Navigate to **mini → Contents** in the admin menu
2. Check the content types you want to enable:
   - Slides (loads slider.js library)
   - News
   - Events
   - Matches
   - Courses
3. Save your settings

### SEO Configuration
1. Go to **mini → SEO** in the admin menu
2. Configure default SEO settings
3. Set fallback images for social sharing

### Email Settings
1. Access **mini → Email** for email configuration options

## Usage

### Creating Content
After enabling content types, you'll see new menu items in your admin dashboard:
- **Slides** - Create slideshow content
- **News** - Write news articles
- **Events** - Schedule and manage events
- **Matches** - Organize sports matches
- **Courses** - Build course content

### Using Shortcodes
Add shortcodes to any post, page, or custom post type:

```
[next_events]
```

This will display the next 3 upcoming events in a responsive grid layout.

### SEO Optimization
For each post/page, you'll find a "Mini SEO" meta box where you can:
- Set custom meta titles and descriptions
- Configure Open Graph and Twitter Card data
- Upload or select custom social sharing images

## File Structure

```
mini-plugin/
├── mini-plugin.php          # Main plugin file
├── inc/
│   └── shortcodes.php       # Shortcode definitions
├── media-upload/
│   ├── media-upload.css     # Media upload styles
│   └── media-upload.js      # Media upload scripts
├── img/                     # Plugin assets and icons
├── LICENSE                  # Plugin license
└── README.md               # This documentation
```

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher

## Support

For support and feature requests, please visit: [https://mini.uwa.agency/](https://mini.uwa.agency/)

## Author

**Giacomo Rizzotti**
- Website: [https://www.giacomorizzotti.com/](https://www.giacomorizzotti.com/)
- Plugin URI: [https://mini.uwa.agency/](https://mini.uwa.agency/)

## License

This plugin is licensed under the terms included in the LICENSE file.

## Changelog

### Version 0.1
- Initial release
- Custom content types (Slides, News, Events, Matches, Courses)
- Shortcode system
- SEO meta box functionality
- Default menu creation
- Media upload enhancements
- Italian date formatting utilities
