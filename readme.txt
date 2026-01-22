=== WP Cloud Deployer by SFLWA ===
Contributors: sflwa
Tags: deployment, automation, elementor, gravity-forms, sflwa
Requires at least: 6.0
Tested up to: 6.9.2
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Description ==

The Master "Warehouse" plugin for the WP Cloud Deployer system by SFLWA. This plugin transforms a standard WordPress installation into a deployment hub. 

Curate a library of Elementor pages, Gravity Forms, and Code Snippets. The plugin automatically packages these assets (including zipping premium plugins) and serves them via a secure REST API to authorized "Client" build sites.

== Installation ==

1. Upload the `wp-cloud-deployer-sflwa` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to the **WP Cloud Deployer** menu in the sidebar.
4. Set your **Agency Brand Name** and select **Core Plugins** in the Global Defaults tab.
5. Create your first **Package** and assign the specific pages/forms you wish to deploy.
6. Ensure **Application Passwords** are enabled on your user profile to allow the Client plugin to connect.

== Frequently Asked Questions ==

= How do the ZIPs update? =
The plugin schedules a weekly cron job (`wpcd_weekly_plugin_refresh`) that crawls your selected core and package plugins, zips them up, and stores them in your uploads directory for API delivery.

= Is this secure? =
Yes. All data delivery is handled via the WordPress REST API. Access is restricted to authenticated requests using WordPress Application Passwords.

== Changelog ==

= 1.0.0 =
* Initial release of the Master Library system.
* Support for Elementor Page JSON and Gravity Form XML bundling.
* Automated weekly plugin zipping service.
* White-label branding for the Master dashboard.
