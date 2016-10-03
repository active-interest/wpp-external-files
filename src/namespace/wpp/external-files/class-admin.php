<?php namespace WPP\External_Files;
/**
 * Copyright (c) 2014, WP Poets and/or its affiliates <wppoets@gmail.com>
 * All rights reserved.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */
defined( 'WPP_EXTERNAL_FILES_VERSION_NUM' ) or die(); //If the base plugin is not used we should not be here
/**
 * @author Michael Stutz <michaeljstutz@gmail.com>
 */
class Admin extends \WPP\External_Files\Base\Admin {

	/** Used to enable the action admin_menu */
	const ENABLE_ADMIN_MENU = TRUE;

	/** Used to enable the action admin_init */
	const ENABLE_ADMIN_INIT = TRUE;

	/** Used to enable the action save_post */
	const ENABLE_SAVE_POST = TRUE;

	/** Used to set if the class uses action_save_post */
	const ENABLE_SAVE_POST_AUTOSAVE_CHECK = TRUE;

	/** Used to set if the class uses action_save_post */
	const ENABLE_SAVE_POST_REVISION_CHECK = TRUE;

	/** Used to set if the class uses action_save_post */
	const ENABLE_SAVE_POST_CHECK_CAPABILITIES_CHECK = TRUE;

	/** Used to enable the admin footer */
	const ENABLE_SAVE_POST_SINGLE_RUN = FALSE;

	/** Used to set if the class uses action_save_post */
	const SAVE_POST_CHECK_CAPABILITIES = '';

	/** */
	const PREG_EXTRACT_URL = '/(src|href)\s*=\s*[\"\']([^\"\']+)[\"\']/';

	/** */
	const STRING_REGEX_TOKEN = 'REGEX::';

	/** */
	const PHP_SET_TIME_LIMIT = 60;

	static private $_wp_options = array();

	/**
	 * Initialization point for the static class
	 *
	 * @return void No return value
	 */
	static public function init( $options = array() ) {
		parent::init( $options );
		$static_instance = get_called_class();
		add_filter('posts_join', array( $static_instance, 'filter_posts_join' ) );
		add_filter('posts_where', array( $static_instance, 'filter_posts_where' ) );
	}

	/**
	 *
	 */
	static public function filter_posts_join( $join ) {
		$options = static::get_options();
		global $pagenow, $wpdb;
		if ( is_admin() && $pagenow=='upload.php' && isset( $_GET['s'] ) ) {
			$join .= 'LEFT JOIN ' . $wpdb->postmeta . ' wpp_external_files ON ' . $wpdb->posts . '.ID = wpp_external_files.post_id AND ' .
				'wpp_external_files.meta_key = "' . $options[ 'metadata_key_external_url' ] . '" ';
		}
		return $join;
	}

	/**
	 *
	 */
	static public function filter_posts_where( $where ) {
		global $pagenow, $wpdb;
		if ( is_admin() && $pagenow=='upload.php' && isset( $_GET['s'] ) ) {
			$where = preg_replace(
				"/\(\s*" . $wpdb->posts . ".post_title\s+LIKE\s*(\'[^\']+\')\s*\)/",
				"(" . $wpdb->posts . ".post_title LIKE $1) OR (" . $wpdb->postmeta . ".meta_value LIKE $1)", $where );
		}
		return $where;
	}

	/**
	 * WordPress action for saving the post
	 *
	 * @return void No return value
	 */
	static public function action_save_post( $post_id ) {
		if ( ! parent::action_save_post( $post_id ) ) {
			return;
		}
		$static_instance = get_called_class();
		$options = static::get_options();
		$wp_options = get_option( $options[ 'wp_option_id' ] );
		if ( empty( $wp_options['enabled'] ) ) {
			return;
		}
		if ( empty( $wp_options['tag_regex'] ) ) {
			return;
		}
		add_filter('sanitize_file_name', array( $static_instance, 'filter_sanitize_file_name_16330' ), 100000);
		remove_action( 'save_post', array( $static_instance, 'action_save_post' ) );
    	$post = get_post( $post_id );
		$check_content = array(
			'items' => array(
				0 => &$post->post_content,
				1 => &$post->post_excerpt,
			),
			'checksums' => array(
				0 => hash( 'crc32', $post->post_content ),
				1 => hash( 'crc32', $post->post_excerpt ),
			),
		);
		foreach ( $check_content['items'] as &$content ) {
			$matched_elements = array();
			if ( preg_match_all( $wp_options[ 'tag_regex' ], $content, $matched_elements ) ) {
				foreach ( $matched_elements[0] as $element ) {
					$matched_url = array();
					if ( preg_match( static::PREG_EXTRACT_URL, $element, $matched_url ) ) {
						$url = empty( $matched_url[2] ) ? NULL : $matched_url[2];
						if ( empty( $url ) ) {
							continue;
						}
						if ( empty( $wp_options[ 'import_extensions' ] ) ) {
							continue;
						}
						$url_parts = parse_url( $url );
						if ( empty( $url_parts['path'] ) ) {
							continue;
						}
						$path_parts = pathinfo( $url_parts['path'] );
						if ( empty( $path_parts['extension'] ) ) {
							continue;
						}
						// Check to see if it is a valid file extentions
						if ( ! static::custom_find_match( explode( PHP_EOL, $wp_options[ 'import_extensions' ] ), $path_parts['extension'] ) ) {
							continue; // If we didnt find a matching extention skip url
						}
						// Make sure it is not an excluded url
						$excluded_urls = explode( PHP_EOL, $wp_options[ 'excluded_urls' ] );
						$home_url_parts = parse_url( home_url() );
						$excluded_urls[] = '//' . $home_url_parts[ 'host' ]; // Add the current hostname of the home_url()
						if ( static::custom_find_match( $excluded_urls, $url ) ) {
							continue; // If we didnt find a matching extention skip url
						}
						$attachment_id = static::find_attachment_id_by_external_url( $url );
						if ( empty( $attachment_id ) ) { // We cant find it so we need to add it
							defined('STDIN') or set_time_limit( static::PHP_SET_TIME_LIMIT ); //If we are not running from the command line change the time limit
							$tmp_name = apply_filters( $options[ 'wp_filter_pre_tag' ] . 'tmp_name_download_url', NULL, $url );
							if ( $tmp_name === FALSE ) { //
								continue;
							} else if ( empty( $tmp_name ) ) {
								$tmp_name = download_url( $url );
							} else if ( ! is_readable( $tmp_name ) ) {
								continue;
							}
							$file_array = array(
								'name' => basename( $url_parts['path'] ),
								'tmp_name' => $tmp_name,
							);
							// If error storing temporarily, unlink
							if ( is_wp_error( $tmp_name ) ) {
								@unlink( $file_array[ 'tmp_name' ] );
								$file_array[ 'tmp_name' ] = '';
							}
							$attachment_id = media_handle_sideload( $file_array, $post_id, '' );
							// If error storing permanently, unlink
							if ( is_wp_error( $attachment_id ) ) {
								@unlink( $file_array['tmp_name'] );
								$attachment_id = NULL;
							} else {
								add_post_meta( $attachment_id, $options[ 'metadata_key_external_url' ], $url, TRUE ) or update_post_meta( $attachment_id, $options[ 'metadata_key_external_url' ], $url );
							}
						}
						if ( empty ( $attachment_id ) ) {
							continue; // Something went wrong, just skip
						}
						$attachment_url = wp_get_attachment_url( $attachment_id );
						if ( empty ( $attachment_url ) ) {
							continue; // Something went wrong, just skip
						}
						$new_element = str_replace( $url, $attachment_url, $element );
						$content = str_replace( $element, $new_element, $content );
					}
				}
			}
		}
		$changed = FALSE;
		foreach ( $check_content['items'] as $item_key => &$content ) {
			if ( hash( 'crc32', $content ) !== $check_content[ 'checksums' ][ $item_key ] ) {
				$changed = TRUE;
			}
		}
		if ( $changed ) {
			wp_update_post( $post );
		}
		add_action( 'save_post', array( $static_instance, 'action_save_post' ) );
		remove_filter('sanitize_file_name', array( $static_instance, 'filter_sanitize_file_name_16330' ) );
	}


	/**
	 * WordPress filter for sanitizing the file name
	 *
	 * Added becuase of WP bug http://core.trac.wordpress.org/ticket/16330
	 *
	 * @return void No return value
	 */
	public static function filter_sanitize_file_name_16330( $file_name ) {
		$decoded_file_name = urldecode( $file_name );
		$new_file_name = preg_replace( '/[^a-zA-Z0-9_.\-]/','-', $decoded_file_name );
		return $new_file_name;
	}

	/**
	 * WordPress action for admin_init
	 *
	 * @return void No return value
	 */
	static public function action_admin_init( ) {
		$static_instance = get_called_class();
		$options = static::get_options();

		register_setting(
			$options[ 'wp_option_id' ], // Option group
			$options[ 'wp_option_id' ], // Option name
			array( $static_instance, 'callback_sanitize_options' ) // Sanitize
		);
		add_settings_section(
			'settings_section', // ID
			'Import Settings', // Title
			array( $static_instance, 'callback_print_settings_section' ), // Callback
			'wpp-external-files-options' // Page
		);
		add_settings_field(
			'enable', // ID
			'Enabled', // Title
			array( $static_instance, 'callback_print_enabled' ), // Callback
			'wpp-external-files-options', // Page
			'settings_section' // Section
		);
		add_settings_field(
			'import_tags', // ID
			'Included Tags', // Title
			array( $static_instance, 'callback_print_import_tags' ), // Callback
			'wpp-external-files-options', // Page
			'settings_section' // Section
		);
		add_settings_field(
			'import_extensions', // ID
			'File Extensions', // Title
			array( $static_instance, 'callback_print_import_extensions' ), // Callback
			'wpp-external-files-options', // Page
			'settings_section' // Section
		);
		add_settings_field(
			'excluded_urls', // ID
			'Excluded URLs*', // Title
			array( $static_instance, 'callback_print_excluded_urls' ), // Callback
			'wpp-external-files-options', // Page
			'settings_section' // Section
		);
	}

	/**
	 * WordPress action for admin_menu
	 *
	 * @return void No return value
	 */
	static public function action_admin_menu( ) {
		$static_instance = get_called_class();
		$options = static::get_options();
		// This page will be under "Settings"
		add_options_page(
			'Settings Admin',
			'Import External Files',
			'manage_options',
			$options[ 'wp_option_id' ],
			array( $static_instance, 'callback_create_admin_page' )
		);
	}

	static public function callback_create_admin_page() {
		$static_instance = get_called_class();
		$options = static::get_options();
		self::$_wp_options[ $static_instance ] = get_option( $options[ 'wp_option_id' ] );
		?>
		<div class="wrap">
			<h2>WPP Import External Files</h2>
			<form method="post" action="options.php">
			<?php
				// This prints out all hidden setting fields
				settings_fields( $options[ 'wp_option_id' ] );
				do_settings_sections( 'wpp-external-files-options' );
				submit_button();
			?>
			</form>
		</div>
		<?php
	}

	static public function callback_sanitize_options( $input ) {
		$output = $input;
		if ( ! empty( $output[ 'import_tags' ] ) ) {
			$tag_regex = '/<(';
			//'/<(a|img)[^>]*>/i'
			$after_first_tag = FALSE;
			foreach ( $output[ 'import_tags' ] as $import_tag => $import_tag_enabled ) {
				if ( $after_first_tag ) {
					$tag_regex .= '|';
				}
				$tag_regex .= $import_tag;
				$after_first_tag = TRUE;
			}
			$tag_regex .= ')[^>]*>/i';
			$output['tag_regex'] = $tag_regex;
		}
		return $output;
	}

	static public function callback_print_settings_section() {
		//echo( 'Enter the urls you would like to exclude from import' );
	}

	static public function callback_print_enabled() {
		$static_instance = get_called_class();
		$options = static::get_options();
		printf(
			'<input type="checkbox" value="checked" name="' . $options[ 'wp_option_id' ] .'[enabled]" %s /> Should the plugin import on save_post? <br />',
			isset( self::$_wp_options[ $static_instance ]['enabled'] ) ? 'checked' : ''
		);
	}

	static public function callback_print_import_tags() {
		$static_instance = get_called_class();
		$options = static::get_options();
		printf(
			'<input type="checkbox" value="checked" name="' . $options[ 'wp_option_id' ] .'[import_tags][img]" %s /> Images <br />',
			isset( self::$_wp_options[ $static_instance ]['import_tags']['img'] ) ? 'checked' : ''
		);
		printf(
			'<input type="checkbox" value="checked" name="' . $options[ 'wp_option_id' ] .'[import_tags][a]" %s /> Links <br />',
			isset( self::$_wp_options[ $static_instance ]['import_tags']['a'] ) ? 'checked' : ''
		);
	}

	static public function callback_print_import_extensions() {
		$static_instance = get_called_class();
		$options = static::get_options();
		echo('<em>Enter each extension on a single line. Regex is supported but must start with "REGEX::" ie "REGEX::/(jpg|jpeg|gif|png|pdf)/i"</em><br />');
		printf(
			'<textarea rows="6" id="import_extensions" class="widefat" name="' . $options[ 'wp_option_id' ] .'[import_extensions]" >%s</textarea>',
			isset( self::$_wp_options[ $static_instance ]['import_extensions'] ) ? esc_attr( self::$_wp_options[ $static_instance ]['import_extensions'] ) : ''
		);
	}

	static public function callback_print_excluded_urls() {
		$static_instance = get_called_class();
		$options = static::get_options();
		echo('<em>Enter each url on a single line. Regex is supported but must start with "REGEX::" ie "REGEX::/www\.mydomain\.com/i"</em><br />');
		printf(
			'<textarea rows="6" id="excluded_urls" class="widefat" name="' . $options[ 'wp_option_id' ] .'[excluded_urls]" >%s</textarea>',
			isset( self::$_wp_options[ $static_instance ]['excluded_urls'] ) ? esc_attr( self::$_wp_options[ $static_instance ]['excluded_urls'] ) : ''
		);
		$home_url_parts = parse_url( home_url() );
		$auto_excluded_url = '//' . $home_url_parts[ 'host' ]; // Add the current hostname of the home_url()
		echo('<em>*Please note that "' . $auto_excluded_url . '" will be added automaticly</em><br />');
	}

	static private function custom_find_match( $needles, $haystack ) {
		$found = FALSE;
		foreach ( (array) $needles as $needle ) {
			$needle = trim( $needle ); //Just in case
			if ( empty( $needle ) ) {
				continue;
			}
			if ( substr( $needle, 0, strlen( static::STRING_REGEX_TOKEN ) ) === static::STRING_REGEX_TOKEN ) {
				$needle = str_replace( static::STRING_REGEX_TOKEN, '', $needle ); // Remove the token
				if ( @preg_match( $needle, $haystack ) ) {
					$found = TRUE;
					break;
				}
			} else {
				if ( strpos( $haystack, $needle ) !== FALSE ) {
					$found = TRUE;
					break;
				}
			}
		}
		return $found;
	}

	static private function find_attachment_id_by_external_url( $external_url ) {
		$options = static::get_options();
		$find_posts = array(
			'post_type'        => 'attachment',
			'post_status'      => 'any',
			'numberposts'      => '1',
			'suppress_filters' => true,
			'fields'           => 'ids',
			'meta_query'       => array(
				array(
					'key'      => $options[ 'metadata_key_external_url' ],
					'value'    => $external_url,
					'compare'  => '=',
				),
			),
		);
		$wp_query = new \WP_Query( $find_posts );
		if ( ! empty( $wp_query->posts[0] ) ) {
			return $wp_query->posts[0];
		}
		return NULL;
	}
}
