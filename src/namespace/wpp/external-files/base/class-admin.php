<?php namespace WPP\External_Files\Base;
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
/**
 * @author Michael Stutz <michaeljstutz@gmail.com>
 * @version 1.0.5
 */
abstract class Admin {

	/** Used to enable the action admin_menu */
	const ENABLE_ADMIN_MENU = FALSE;

	/** Used to enable the action admin_init */
	const ENABLE_ADMIN_INIT = FALSE;

	/** Used to enable the action save_post */
	const ENABLE_SAVE_POST = FALSE;

	/** Used to set if the class uses action_save_post */
	const ENABLE_SAVE_POST_AUTOSAVE_CHECK = FALSE;

	/** Used to set if the class uses action_save_post */
	const ENABLE_SAVE_POST_REVISION_CHECK = FALSE;

	/** Used to set if the class uses action_save_post */
	const ENABLE_SAVE_POST_CHECK_CAPABILITIES_CHECK = FALSE;

	/** Used to enable the admin footer */
	const ENABLE_SAVE_POST_SINGLE_RUN = FALSE;

	/** Used to set if the class uses action_save_post */
	const SAVE_POST_CHECK_CAPABILITIES = '';

	/** Used to keep the init state of the class */
	static private $_initialized = array();

	/** Used to store the class options */
	static private $_options = array();

	/** Used to store if save_post has run before */
	static private $_save_post = array();

	/**
	 * Initialization point for the static class
	 *
	 * @return void No return value
	 */
	static public function init( $options = array() ) {
		$static_instance = get_called_class();
		if ( ( ( defined('WP_CLI') && !WP_CLI ) && ! is_admin() ) || ! empty( self::$_initialized[ $static_instance ] ) ) {
			return;
		}
		static::set_options( $options );
		if ( static::ENABLE_SAVE_POST ) {
			add_action( 'save_post', array( $static_instance, 'action_save_post' ) );
		}
		if ( static::ENABLE_ADMIN_INIT ) {
			add_action( 'admin_init', array( $static_instance, 'action_admin_init' ) );
		}
		if ( static::ENABLE_ADMIN_MENU ) {
			add_action( 'admin_menu', array( $static_instance, 'action_admin_menu' ) );
		}
		self::$_initialized[ $static_instance ] = true;
	}

	/**
	 * Set method for the options
	 *
	 * @param string|array $options An array containing the meta box options
	 * @param boolean $merge Should the current options be merged in?
	 *
	 * @return void No return value
	 */
	static public function set_options( $options, $merge = FALSE ) {
		$static_instance = get_called_class();
		if ( empty( self::$_options[ $static_instance ] ) ) {
			self::$_options[ $static_instance ] = array(); //setup an empty instance if empty
		}
		self::$_options[ $static_instance ] = wpp_array_merge_nested(
			array( //Default options
			),
			( $merge ) ? self::$_options[ $static_instance ] : array(), //if merge, merge the excisting values
			(array) $options //Added options
		);
	}

	/*
	 * Get method for the option array
	 *
	 * @return array Returns the option array
	 */
	static public function get_options() {
		$static_instance = get_called_class();
		return self::$_options[ $static_instance ];
	}

	/**
	 * WordPress action for admin_init
	 *
	 * @return void No return value
	 */
	static public function action_admin_init( ) {
		// Placeholder for child
	}

	/**
	 * WordPress action for admin_menu
	 *
	 * @return void No return value
	 */
	static public function action_admin_menu( ) {
		// Placeholder for child
	}

	/**
	 * WordPress action for saving the post
	 *
	 * @return void No return value
	 */
	static public function action_save_post( $post_id ) {
		if ( static::ENABLE_SAVE_POST_AUTOSAVE_CHECK && defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )  {  // Check if is auto saving
			return;
		}
		if ( static::ENABLE_SAVE_POST_CHECK_CAPABILITIES_CHECK ) {
			foreach ( explode( ',', static::SAVE_POST_CHECK_CAPABILITIES ) as $capability ) {
				if ( ! empty( $capability ) && ! current_user_can( $capability, $post_id ) ) {  // Check user can edit
					return;
				}
			}
		}
		if ( static::ENABLE_SAVE_POST_REVISION_CHECK && wp_is_post_revision( $post_id ) ) {  // Check if is revision
			return;
		}
		if ( static::ENABLE_SAVE_POST_SINGLE_RUN ) {
			$static_instance = get_called_class();
			if ( ! empty( self::$_save_post[ $static_instance ][ $post_id ] ) ) {
				return;
			}
			if ( ! isset( self::$_save_post[ $static_instance ] ) ) {
				self::$_save_post[ $static_instance ] = array();
			}
			self::$_save_post[ $static_instance ][ $post_id ] = TRUE;
		}
		return TRUE;

		// Example usage
		//if ( ! parent::action_save_post( $post_id ) ) {
		//	return;
		//}
	}

}
