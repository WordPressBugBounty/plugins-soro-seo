=== Soro - SEO Autopilot & AI Content Writer ===
Contributors: soroseo
Tags: seo, content, automation, ai, publishing
Requires at least: 5.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.3.6
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Connect your WordPress site to Soro for automatic AI-powered article publishing and SEO content automation.

== Description ==

**Soro** is a lightweight plugin that connects your WordPress site to [Soro](https://trysoro.com), an AI-powered SEO content platform that automatically generates and publishes optimized articles to your blog.

= Features =

* **One-Click Setup** – Install the plugin, copy your API key, and you're connected
* **Secure API** – All communication is authenticated with a unique API key
* **Featured Images** – Automatically downloads and sets featured images
* **SEO Integration** – Works with Yoast SEO, Rank Math, and All in One SEO
* **Lightweight** – No bloat, no database tables, minimal footprint

= How It Works =

1. Install and activate this plugin
2. Copy your API key from Settings → Soro
3. Paste the key in your Soro dashboard under Settings → Integrations → WordPress
4. Soro will automatically publish articles to your WordPress site based on your schedule

= Requirements =

* WordPress 5.0 or higher
* PHP 7.4 or higher
* A Soro account ([sign up at trysoro.com](https://trysoro.com))

= Privacy =

This plugin communicates with the Soro service (trysoro.com) to receive article content for publishing. No personal data is sent from your WordPress site to Soro. The plugin only receives content that you have configured in your Soro dashboard.

== Installation ==

= From WordPress Dashboard =

1. Go to Plugins → Add New
2. Search for "Soro SEO"
3. Click "Install Now" and then "Activate"
4. Go to Settings → Soro to get your API key

= Manual Installation =

1. Download the plugin zip file
2. Go to Plugins → Add New → Upload Plugin
3. Upload the zip file and click "Install Now"
4. Activate the plugin
5. Go to Settings → Soro to get your API key

== Frequently Asked Questions ==

= Do I need a Soro account? =

Yes, you need a Soro account to use this plugin. Visit [trysoro.com](https://trysoro.com) to create an account.

= Is my API key secure? =

Yes, your API key is stored securely in your WordPress database and is only used to authenticate requests from Soro. We use timing-safe comparison to prevent timing attacks.

= Can I regenerate my API key? =

Yes, you can regenerate your API key at any time from Settings → Soro. Note that you will need to update the key in your Soro dashboard after regenerating.

= Does this plugin work with page builders? =

Yes, Soro publishes standard WordPress content that works with any theme or page builder.

= What SEO plugins are supported? =

The plugin automatically sets meta descriptions and focus keywords for Yoast SEO, Rank Math, and All in One SEO.

= Can I review articles before publishing? =

Yes! In your Soro dashboard, you can set articles to be published as "Draft" instead of "Published", allowing you to review and edit before making them live.

== Screenshots ==

1. Settings page with API key and connection status
2. Step-by-step connection instructions
3. API key regeneration option

== Changelog ==

= 1.3.6 =
* Fixed connection failures on sites where the REST API is restricted to authenticated users

= 1.3.5 =
* Improved SEO plugin compatibility – focus keywords and meta descriptions are now correctly recognized by Yoast SEO, Rank Math, and All in One SEO when articles are published

= 1.3.4 =
* Added post author selection in plugin settings
* Added post category selection in plugin settings
* Choose which WordPress user and category are used for Soro-published articles

= 1.3.3 =
* Added focus keyword support for Rank Math, Yoast SEO, and All in One SEO
* Focus keywords are now automatically set when publishing articles from Soro

= 1.3.2 =
* Fixed featured image upload on hosts without PHP fileinfo extension
* Added multiple fallback methods for image mime type detection

= 1.3.1 =
* Updated plugin name for better discoverability in WordPress plugin directory

= 1.3.0 =
* Added IndexNow support for instant Bing/search engine indexing
* New REST API endpoints for IndexNow setup and status
* Automatic key file management in site root
* Improved search engine discoverability for new articles

= 1.2.0 =
* Added internationalization support for translations
* Improved security with timing-safe API key comparison
* Enhanced image validation for featured images
* Updated admin UI with improved branding
* Added proper capability checks throughout
* Improved nonce verification handling

= 1.1.0 =
* Added featured image support
* SEO plugin integration (Yoast, Rank Math, AIOSEO)
* Improved error handling

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.3.6 =
Fixes "invalid API key" errors on sites that restrict the REST API. Recommended for all users.

= 1.3.5 =
Improved SEO plugin compatibility for focus keywords. Recommended for all users.

= 1.3.4 =
Post author and category selection. Configure directly in WordPress plugin settings.

= 1.3.3 =
Adds focus keyword support for Rank Math, Yoast SEO, and AIOSEO. Recommended for all users.

= 1.3.2 =
Fixes featured image uploads on some hosting environments. Recommended for all users.

= 1.3.1 =
Updated plugin name for better discoverability.

= 1.3.0 =
New IndexNow integration for instant Bing indexing. Recommended for all users.

= 1.2.0 =
Security improvements and internationalization support. Recommended for all users.
