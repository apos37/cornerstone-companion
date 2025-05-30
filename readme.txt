=== Cornerstone Companion ===
Contributors: apos37
Tags: cornerstone, theme co, enhancements, add-ons, extended
Requires at least: 5.9
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.txt

A companion plugin that enhances and extends the functionality of the Cornerstone website builder by Theme Co.

== Description ==

**⚠️ This plugin requires the Cornerstone website builder by Theme Co. You must have Cornerstone installed and active. ⚠️**

**Cornerstone Companion** adds useful enhancements and extra features to the popular Cornerstone builder, giving you more control, flexibility, and efficiency when designing WordPress sites. Built specifically to complement Cornerstone, it blends seamlessly into the existing interface and supports both novice users and advanced workflows.

**Features:**

- **Visual Edit Indicator:** Adds a Cornerstone logo next to posts and pages in the WordPress dashboard to indicate whether the content was built in Cornerstone.
- **.hide Utility Class:** Adds support for a `.hide` class to hide any element on the live site while still displaying it with a striped gray background inside the Cornerstone preview. This is useful to hide things temporarily and not forget about them.
- **Edit Lock System:** Prevents multiple users from editing the same page in Cornerstone simultaneously by syncing with WordPress' native post lock system.
  - Shows who is currently editing via in-editor and dashboard notices.
  - Displays an alert dialog if another user is active, with options to “Go Back” or “Take Over”.
  - “Take Over” initiates a 30-second countdown and forces the previous user out.
- **Auto Boot for Inactivity:** If no rendering activity occurs for 30 minutes, shows a timeout dialog. Editors can confirm to stay active or will be automatically removed after 30 seconds of no response.
  - CSS/JS-only changes do not reset the timer.
  - Blinking tab title ("Inactive Timeout") to warn inactive users while on other tabs.
  - Configurable timeout duration.
  - Optionally disable saving changes when forcibly booting users.
- **File Element:** Integrates with the ERI File Library plugin by adding a new File element.

**Cornerstone must be installed and active for this plugin to work.**

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/cornerstone-companion/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Make sure Cornerstone is installed and active.

== Frequently Asked Questions ==

= Does this plugin work without Cornerstone? =  
No. It requires Cornerstone by Theme Co to be installed and active.

= Will it affect performance? =  
The plugin is designed to be lightweight and only loads features in the admin area and when editing Cornerstone content.

= Can I disable the inactivity timeout or customize the duration? =  
Yes, there are filter hooks and internal settings available to adjust the timeout behavior.

= Is there a way to tell if the editor is actually active or someone just left it open? =  
Yes. The dashboard banner includes the timestamp of last activity (render event) so you can judge whether the session is stale.

== Changelog ==
= 1.0.1 =
* Initial Release on May 15, 2025