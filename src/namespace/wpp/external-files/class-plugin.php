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
 * Starting point for the plugin
 *
 * Everything about the plugin starts here.
 *
 * @author Michael Stutz <michaeljstutz@gmail.com>
 *
 */
class Plugin extends \WPP\External_Files\Base\Plugin {

	/** Used to set the plugins ID */
	const ID = 'wpp-external-files';

	/** Used to set the wordpress option id */
	const WP_OPTION_ID = 'wpp_external_files_options';

	/** Used to set the plugins ID */
	const CACHE_GROUP = self::ID;

	/** Used to store the text domain */
	const TEXT_DOMAIN = WPP_EXTERNAL_FILES_TEXT_DOMAIN;

	/** Used to enable shortcode function */
	const SHORTCODE_ENABLE = FALSE;

	/** Used to store the metadata key prefix **/
	const METADATA_KEY_EXTERNAL_URL = '_wpp_external_url';

	/** Used to store the metadata key prefix **/
	const WP_FILTER_PRE_TAG = 'wpp_external_files_';

	/**
	 * Initialization point for the static class
	 *
	 * @return void No return value
	 */
	static public function init( $options = array() ) {
		parent::init( array(
			'admin_controllers' => array(
				"\WPP\External_Files\Admin",
			),
			'admin_controller_options' => array(
				"\WPP\External_Files\Admin" => array(
					'cache_group' => static::CACHE_GROUP,
					'metadata_key_external_url' => static::METADATA_KEY_EXTERNAL_URL,
					'wp_option_id' => static::WP_OPTION_ID,
					'wp_filter_pre_tag' => static::WP_FILTER_PRE_TAG,
				),
			),
			'meta_boxes' => array(
				"\WPP\External_Files\Meta_Boxes\External_Files_Url_Meta_Box",
			),
			'meta_box_options' => array(
				"\WPP\External_Files\Meta_Boxes\External_Files_Url_Meta_Box" => array(
					'include_post_types' => 'attachment',
					'metadata_key_external_url' => static::METADATA_KEY_EXTERNAL_URL,
				),
			),
		) );
	}
}
