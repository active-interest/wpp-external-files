WordPress Plugin: WPP Import External Files
===========================================

Description
-----------

Allows content from external sources to be downloaded and attached to there respected Post/Page/Custom Content
This helps to prevent the user experience from getting ruined by dead images and external 404 errors.

Installation
------------

1. Place the 'wpp-external-files' folder in your '/wp-content/plugins/' directory.
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Visit 'Settings > Import External Files' to enable importing and adjusting the configuration.

Versions
--------

### 0.10.0

* Introduced 'wpp_external_files_parse_url' filter to allow filtering the parse_url() results
* Added early exit on relative URL's without a host
* Defaulted to 'http' scheme for schemeless URL's

### 0.9.3

* Added support for the tmp_file hook to cancel the process by returning FALSE
* Added empty uninstall.php ( will be used later )
* Added misc defines for later use
* Updated base meta-box class
* Updated the gulpfile.js

### 0.9.2

* Updated various files

### 0.9.1

* Updated all the readme files
* Removed unneeded folders and files

### 0.9.0

* First pre release version


License
-------
GPLv2 (dual-licensed)
License URI: http://www.gnu.org/licenses/gpl-2.0.html
