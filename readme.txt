=== WPP Import External Files ===
Contributors: wppoets, michaeljstutz
Tags: import, external, media, files
Requires at least: 3.9
Tested up to: 3.9
Stable tag: 1.0
License: GPLv2 (dual-licensed)
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Allows content from external sources to be downloaded and attached to there respected Post/Page/Custom Content
This helps to prevent the user experience from getting ruined by dead images and external 404 errors.

== Description ==

Allows content from external sources to be downloaded and attached to there respected Post/Page/Custom Content
This helps to prevent the user experience from getting ruined by dead images and external 404 errors.

== Installation ==

1. Place the 'wpp-external-files' folder in your '/wp-content/plugins/' directory.
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Visit 'Settings > Import External Files' to enable importing and adjusting the configuration.

== Frequently Asked Questions ==

= Why is this empty? =

Because we have not had the time to complete this? and or people have not asked...

== Screenshots ==

1. Configuration Screen
2. Metabox Screen

== Changelog ==
= 0.9.4 =
* Fixed bug in LEFT JOIN statement that assumed `postmeta` table wasn't already being joined.
= 0.9.3 =
* Added support for the tmp_file hook to cancel the process by returning FALSE
* Added empty uninstall.php for later use
* Added misc defines for later use
* Updated base meta-box class
= 0.9.2 =
* Updated various files
= 0.9.1 =
* Updated all the readme files
* Removed unneeded folders and files
= 0.9.0 =
* First pre release version

== Upgrade Notice ==
