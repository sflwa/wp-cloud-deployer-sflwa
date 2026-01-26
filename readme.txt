=== WP Cloud Deployer by SFLWA ===
Contributors: sflwa
Tags: deployment, automation, elementor, gravity-forms, sflwa
Requires at least: 6.0
Tested up to: 6.9.2
Requires PHP: 7.4
Stable tag: 1.2.0
License: GPLv2 or later

== Description ==

The Master "Warehouse" plugin for the WP Cloud Deployer system. This plugin serves as the central hub for your entire cloud architecture, allowing you to curate a library of Elementor pages, Gravity Forms, and Code Snippets.

Assets are automatically packaged (including premium plugin zipping) and served via a secure REST API to authorized "Client" sites. Version 1.2.0 establishes the foundation for the Agent ID dynamic replacement system.

== Installation ==

1. Upload the `wp-cloud-deployer-sflwa` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to the **WP Cloud Deployer** menu in the sidebar.
4. Set your **Agency Brand Name** and select **Core Plugins** in the Global Defaults tab.
5. Create your first **Package** and assign the specific pages/forms you wish to deploy.
6. Use **Application Passwords** to authorize Client-side connections.

== Content Logic ==

The Master plugin prepares content for injection with specific placeholders:
* **Elementor Data:** Prepared for JSON-safe search and replace.
* **Code Snippets:** Exported with standard scope and activation flags.
* **Gravity Forms:** Served via JSON manifest for direct API injection.

== Frequently Asked Questions ==

= How do the ZIPs update? =
The plugin schedules a weekly cron job (`wpcd_weekly_plugin_refresh`) that crawls your selected core and package plugins, zips them up, and stores them in your uploads directory for API delivery.

= Is this secure? =
Yes. All data delivery is handled via the WordPress REST API. Access is restricted to authenticated requests using WordPress Application Passwords.

== Changelog ==

= 1.2.0 =
* Optimized REST API endpoints for V2.0 Client compatibility.
* Refined manifest structure for Gravity Forms and Code Snippets.

= 1.0.0 =
* Initial release of the Master Library system.
