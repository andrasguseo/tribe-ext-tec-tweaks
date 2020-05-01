<?php
/**
 * Plugin Name:       The Events Calendar Extension: Tweaks
 * Plugin URI:        https://theeventscalendar.com/extensions/tec-tweaks/
 * GitHub Plugin URI: https://github.com/mt-support/tribe-ext-tec-tweaks
 * Description:       A combination of snippets and tweaks for The Events Calendar
 * Version:           1.0.0
 * Extension Class:   Tribe\Extensions\Tec_Tweaks\Main
 * Author:            Modern Tribe, Inc.
 * Author URI:        http://m.tri.be/1971
 * License:           GPL version 3 or any later version
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       tribe-ext-tec-tweaks
 *
 *     This plugin is free software: you can redistribute it and/or modify
 *     it under the terms of the GNU General Public License as published by
 *     the Free Software Foundation, either version 3 of the License, or
 *     any later version.
 *
 *     This plugin is distributed in the hope that it will be useful,
 *     but WITHOUT ANY WARRANTY; without even the implied warranty of
 *     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *     GNU General Public License for more details.
 */

namespace Tribe\Extensions\Tec_Tweaks;

use Tribe__Autoloader;
use Tribe__Dependency;
use Tribe__Extension;

// Do not load unless Tribe Common is fully loaded and our class does not yet exist.
if (
	class_exists( 'Tribe__Extension' )
	&& ! class_exists( Main::class )
) {
	/**
	 * Extension main class, class begins loading on init() function.
	 */
	class Main extends Tribe__Extension {

		/**
		 * @var Tribe__Autoloader
		 */
		private $class_loader;

		/**
		 * @var Settings
		 */
		private $settings;

		/**
		 * Is Events Calendar PRO active. If yes, we will add some extra functionality.
		 *
		 * @return bool
		 */
		public $ecp_active = false;
		/**
		 * Setup the Extension's properties.
		 *
		 * This always executes even if the required plugins are not present.
		 */
		public function construct() {
			// Dependency requirements and class properties can be defined here.

			/**
			 * Examples:
			 * All these version numbers are the ones on or after November 16, 2016, but you could remove the version
			 * number, as it's an optional parameter. Know that your extension code will not run at all (we won't even
			 * get this far) if you are not running The Events Calendar 4.3.3+ or Event Tickets 4.3.3+, as that is where
			 * the Tribe__Extension class exists, which is what we are extending.
			 *
			 * If using `tribe()`, such as with `Tribe__Dependency`, require TEC/ET version 4.4+ (January 9, 2017).
			 */
			//$this->add_required_plugin( 'Tribe__Tickets__Main', '5.0' );
			// $this->add_required_plugin( 'Tribe__Tickets_Plus__Main', '4.3.3' );
			$this->add_required_plugin( 'Tribe__Events__Main', '5.0' );
			// $this->add_required_plugin( 'Tribe__Events__Pro__Main', '4.3.3' );
			// $this->add_required_plugin( 'Tribe__Events__Community__Main', '4.3.2' );
			// $this->add_required_plugin( 'Tribe__Events__Community__Tickets__Main', '4.3.2' );
			// $this->add_required_plugin( 'Tribe__Events__Filterbar__View', '4.3.3' );
			// $this->add_required_plugin( 'Tribe__Events__Tickets__Eventbrite__Main', '4.3.2' );
			// $this->add_required_plugin( 'Tribe_APM', '4.4' );

			// Conditionally-require Events Calendar PRO. If it is active, run an extra bit of code.
			//add_action( 'tribe_plugins_loaded', [ $this, 'detect_tec_pro' ], 0 );
		}

		/**
		 * Check required plugins after all Tribe plugins have loaded.
		 *
		 * Useful for conditionally-requiring a Tribe plugin, whether to add extra functionality
		 * or require a certain version but only if it is active.
		 */
		public function detect_tec_pro() {
			/** @var Tribe__Dependency $dep */
			$dep = tribe( Tribe__Dependency::class );

			if ( $dep->is_plugin_active( 'Tribe__Events__Pro__Main' ) ) {
				$this->add_required_plugin( 'Tribe__Events__Pro__Main' );
				$this->ecp_active = true;
			}
		}

		/**
		 * Get this plugin's options prefix.
		 *
		 * Settings_Helper will append a trailing underscore before each option.
		 *
		 * TODO: Remove if not using Settings.
		 *
		 * @see \Tribe\Extensions\Tec_Tweaks\Settings::set_options_prefix()
		 *
		 * @return string
		 */
		private function get_options_prefix() {
			return (string) str_replace( '-', '_', 'tribe-ext-tec-tweaks' );
		}

		/**
		 * Get Settings instance.
		 *
		 * @return Settings
		 */
		private function get_settings() {
			if ( empty( $this->settings ) ) {
				$this->settings = new Settings( $this->get_options_prefix() );
			}

			return $this->settings;
		}

		/**
		 * Extension initialization and hooks.
		 */
		public function init() {
			// Load plugin textdomain
			// Don't forget to generate the 'languages/tribe-ext-tec-tweaks.pot' file
			load_plugin_textdomain( 'tribe-ext-tec-tweaks', false, basename( dirname( __FILE__ ) ) . '/languages/' );

			if ( ! $this->php_version_check() ) {
				return;
			}

			if ( ! $this->is_using_compatible_view_version() ) {
				return;
			}

			$this->class_loader();

			$this->get_settings();

			// TODO: Just a test. Remove this.
			$this->testing_hello_world();

			// Insert filter and action hooks here
			add_filter( 'thing_we_are_filtering', [ $this, 'my_custom_function' ] );

			$this->disable_latest_past_events();

		}

		/**
		 * Check if we have a sufficient version of PHP. Admin notice if we don't and user should see it.
		 *
		 * @return bool
		 */
		private function php_version_check() {
			$php_required_version = '5.6';

			if ( version_compare( PHP_VERSION, $php_required_version, '<' ) ) {
				if (
					is_admin()
					&& current_user_can( 'activate_plugins' )
				) {
					$message = '<p>';
					$message .= sprintf( __( '%s requires PHP version %s or newer to work. Please contact your website host and inquire about updating PHP.', 'tribe-ext-tec-tweaks' ), $this->get_name(), $php_required_version );
					$message .= sprintf( ' <a href="%1$s">%1$s</a>', 'https://wordpress.org/about/requirements/' );
					$message .= '</p>';
					tribe_notice( 'tribe-ext-tec-tweaks-php-version', $message, [ 'type' => 'error' ] );
				}
				return false;
			}
			return true;
		}

		/**
		 * Check if we have the required TEC view. Admin notice if we don't and user should see it.
		 *
		 * @return bool
		 */
		private function is_using_compatible_view_version() {

			$view_required_version = 2;

			$meets_req = true;

			// Is V2 enabled?
			if (
				function_exists( 'tribe_events_views_v2_is_enabled' )
				&& ! empty( tribe_events_views_v2_is_enabled() )
			) {
				$is_v2 = true;
			} else {
				$is_v2 = false;
			}

			// V1 compatibility check.
			if (
				1 === $view_required_version
				&& $is_v2
			) {
				$meets_req = false;
			}

			// V2 compatibility check.
			if (
				2 === $view_required_version
				&& ! $is_v2
			) {
				$meets_req = false;
			}

			// Notice, if should be shown.
			if (
				! $meets_req
				&& is_admin()
				&& current_user_can( 'activate_plugins' )
			) {
				if ( 1 === $view_required_version ) {
					$view_name = _x( 'Legacy Views', 'name of view', 'tribe-ext-tec-tweaks' );
				} else {
					$view_name = _x( 'New (V2) Views', 'name of view', 'tribe-ext-tec-tweaks' );
				}

				$view_name = sprintf(
					'<a href="%s">%s</a>',
					esc_url( admin_url( 'edit.php?page=tribe-common&tab=display&post_type=tribe_events' ) ),
					$view_name
				);

				// Translators: 1: Extension plugin name, 2: Name of required view, linked to Display tab.
				$message = sprintf(
					__(
						'%1$s requires the "%2$s" so this extension\'s code will not run until this requirement is met. You may want to deactivate this extension or visit its homepage to see if there are any updates available.',
						'tribe-ext-tec-tweaks'
					),
					$this->get_name(),
					$view_name
				);

				tribe_notice(
					'tribe-ext-tec-tweaks-view-mismatch',
					'<p>' . $message . '</p>',
					[ 'type' => 'error' ]
				);
			}

			return $meets_req;
		}

		/**
		 * Use Tribe Autoloader for all class files within this namespace in the 'src' directory.
		 *
		 * TODO: Delete this method and its usage throughout this file if there is no `src` directory, such as if there are no settings being added to the admin UI.
		 *
		 * @return Tribe__Autoloader
		 */
		public function class_loader() {
			if ( empty( $this->class_loader ) ) {
				$this->class_loader = new Tribe__Autoloader;
				$this->class_loader->set_dir_separator( '\\' );
				$this->class_loader->register_prefix(
					__NAMESPACE__ . '\\',
					__DIR__ . DIRECTORY_SEPARATOR . 'src'
				);
			}

			$this->class_loader->register_autoloader();

			return $this->class_loader;
		}

		/**
		 * TODO: Testing Hello World. Delete this for your new extension.
		 */
		public function testing_hello_world() {
			$message = sprintf( '<p>Hello World from %s. Make sure to remove this in your own new extension.</p>', '<strong>' . $this->get_name() . '</strong>' );

			$message .= sprintf( '<p><strong>Bonus!</strong> Get one of our own custom option values: %s</p><p><em>See the code to learn more.</em></p>', $this->get_one_custom_option() );

			tribe_notice( 'tribe-ext-tec-tweaks-hello-world', $message, [ 'type' => 'info' ] );
		}

		/**
		 * Demonstration of getting this extension's `a_setting` option value.
		 *
		 * TODO: Rework or remove this.
		 *
		 * @return mixed
		 */
		public function get_one_custom_option() {
			$settings = $this->get_settings();

			return $settings->get_option( 'a_setting', 'https://theeventscalendar.com/' );
		}

		public function get_disable_latest_past_events() {
			$settings = $this->get_settings();
var_dump($settings);
$opt = $settings->get_option( 'disable_recent_past_events', '0' );
var_dump($opt);
			return $settings->get_option( 'disable_recent_past_events', '1' );
		}

		/**
		 * Get all of this extension's options.
		 *
		 * @return array
		 */
		public function get_all_options() {
			$settings = $this->get_settings();

			return $settings->get_all_options();
		}

		/**
		 * Include a docblock for every class method and property.
		 */
		public function my_custom_function() {
			// do your custom stuff
		}

		public function disable_latest_past_events() {
			if ( $this->get_disable_latest_past_events() ) {
				add_filter( 'tribe_events_views_v2_show_latest_past_events_view', '__return_false' );
			}
		}

	} // end class
} // end if class_exists check
