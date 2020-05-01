<?php

namespace Tribe\Extensions\Tec_Tweaks;

use Tribe__Settings_Manager;
use Tribe__Settings_Tab;

if ( ! class_exists( Settings::class ) ) {
	/**
	 * Do the Settings.
	 */
	class Settings {

		/**
		 * The Settings Helper class.
		 *
		 * @var Settings_Helper
		 */
		protected $settings_helper;

		/**
		 * The prefix for our settings keys.
		 *
		 * @see get_options_prefix() Use this method to get this property's value.
		 *
		 * @var string
		 */
		private $options_prefix = '';

		/**
		 * @var Tribe__Settings_Tab
		 */
		private $settings_tab;

		/**
		 * Settings constructor.
		 *
		 * TODO: Update this entire class for your needs, or remove the entire `src` directory this file is in and do not load it in the main plugin file.
		 *
		 * @param string $options_prefix Recommended: the plugin text domain, with hyphens converted to underscores.
		 */
		public function __construct( $options_prefix ) {
			$this->settings_helper = new Settings_Helper();

			$this->set_options_prefix( $options_prefix );

			// Remove settings specific to Google Maps
			//add_action( 'admin_init', [ $this, 'remove_settings' ] );

			add_action( 'admin_init', [ $this, 'add_settings_tab' ] );

			// Add settings specific to OSM
			add_action( 'admin_init', [ $this, 'add_settings' ] );
		}

		/**
		 * Allow access to set the Settings Helper property.
		 *
		 * @see get_settings_helper()
		 *
		 * @param Settings_Helper $helper
		 *
		 * @return Settings_Helper
		 */
		public function set_settings_helper( Settings_Helper $helper ) {
			$this->settings_helper = $helper;

			return $this->get_settings_helper();
		}

		/**
		 * Allow access to get the Settings Helper property.
		 *
		 * @see set_settings_helper()
		 */
		public function get_settings_helper() {
			return $this->settings_helper;
		}

		/**
		 * Set the options prefix to be used for this extension's settings.
		 *
		 * Recommended: the plugin text domain, with hyphens converted to underscores.
		 * Is forced to end with a single underscore. All double-underscores are converted to single.
		 *
		 * @see get_options_prefix()
		 *
		 * @param string $options_prefix
		 */
		private function set_options_prefix( $options_prefix ) {
			$options_prefix = $options_prefix . '_';

			$this->options_prefix = str_replace( '__', '_', $options_prefix );
		}

		/**
		 * Get this extension's options prefix.
		 *
		 * @see set_options_prefix()
		 *
		 * @return string
		 */
		public function get_options_prefix() {
			return $this->options_prefix;
		}

		/**
		 * Given an option key, get this extension's option value.
		 *
		 * This automatically prepends this extension's option prefix so you can just do `$this->get_option( 'a_setting' )`.
		 *
		 * @see tribe_get_option()
		 *
		 * @param string $key
		 *
		 * @return mixed
		 */
		public function get_option( $key = '', $default = '' ) {
			$key = $this->sanitize_option_key( $key );

			return tribe_get_option( $key, $default );
		}

		/**
		 * Get an option key after ensuring it is appropriately prefixed.
		 *
		 * @param string $key
		 *
		 * @return string
		 */
		private function sanitize_option_key( $key = '' ) {
			$prefix = $this->get_options_prefix();

			if ( 0 === strpos( $key, $prefix ) ) {
				$prefix = '';
			}

			return $prefix . $key;
		}

		/**
		 * Get an array of all of this extension's options without array keys having the redundant prefix.
		 *
		 * @return array
		 */
		public function get_all_options() {
			$raw_options = $this->get_all_raw_options();

			$result = [];

			$prefix = $this->get_options_prefix();

			foreach ( $raw_options as $key => $value ) {
				$abbr_key            = str_replace( $prefix, '', $key );
				$result[ $abbr_key ] = $value;
			}

			return $result;
		}

		/**
		 * Get an array of all of this extension's raw options (i.e. the ones starting with its prefix).
		 *
		 * @return array
		 */
		public function get_all_raw_options() {
			$tribe_options = Tribe__Settings_Manager::get_options();

			if ( ! is_array( $tribe_options ) ) {
				return [];
			}

			$result = [];

			foreach ( $tribe_options as $key => $value ) {
				if ( 0 === strpos( $key, $this->get_options_prefix() ) ) {
					$result[ $key ] = $value;
				}
			}

			return $result;
		}

		/**
		 * Given an option key, delete this extension's option value.
		 *
		 * This automatically prepends this extension's option prefix so you can just do `$this->delete_option( 'a_setting' )`.
		 *
		 * @param string $key
		 *
		 * @return mixed
		 */
		public function delete_option( $key = '' ) {
			$key = $this->sanitize_option_key( $key );

			$options = Tribe__Settings_Manager::get_options();

			unset( $options[ $key ] );

			return Tribe__Settings_Manager::set_options( $options );
		}

		/**
		 * Here is an example of removing settings from Events > Settings > General tab > "Map Settings" section
		 * that are specific to Google Maps.
		 */
		public function remove_settings() {
			// "Enable Google Maps" checkbox
			$this->settings_helper->remove_field( 'embedGoogleMaps', 'general' );
			// "Map view search distance limit" (default of 25)
			$this->settings_helper->remove_field( 'geoloc_default_geofence', 'general' );
			// "Google Maps default zoom level" (0-21, default of 10)
			$this->settings_helper->remove_field( 'embedGoogleMapsZoom', 'general' );
		}

		/**
		 * Adds a new section of fields to Events > Settings > General tab, appearing after the "Map Settings" section
		 * and before the "Miscellaneous Settings" section.
		 *
		 * TODO: Move it to where you want and update this docblock. If you like it here, just delete this TODO.
		 */
		public function add_settings() {
			$fields = [
				// TODO: Settings heading start. Remove this element if not needed. Also remove the corresponding `get_example_intro_text()` method below.
				'Example'   => [
					'type' => 'html',
					'html' => $this->get_example_intro_text(),
				],
				// TODO: Settings heading end.
				'a_setting' => [ // TODO
					'type'            => 'text',
					'label'           => esc_html__( 'xxx try this', 'tribe-ext-tec-tweaks' ),
					'tooltip'         => sprintf( esc_html__( 'Enter your custom URL, including "http://" or "https://", for example %s.', 'tribe-ext-tec-tweaks' ), '<code>https://wpshindig.com/events/</code>' ),
					'validation_type' => 'html',
				],
			];

			$this->settings_helper->add_fields(
				$this->prefix_settings_field_keys( $fields ),
				'tec-tweaks',
/*				'a_start',
				true*/
			);
		}

		/**
		 * Add the options prefix to each of the array keys.
		 *
		 * @param array $fields
		 *
		 * @return array
		 */
		private function prefix_settings_field_keys( array $fields ) {
			$prefixed_fields = array_combine(
				array_map(
					function ( $key ) {
						return $this->get_options_prefix() . $key;
					}, array_keys( $fields )
				),
				$fields
			);

			return (array) $prefixed_fields;
		}

		/**
		 * Here is an example of getting some HTML for the Settings Header.
		 *
		 * TODO: Delete this method if you do not need a heading for your settings. Also remove the corresponding element in the the $fields array in the `add_settings()` method above.
		 *
		 * @return string
		 */
		private function get_example_intro_text() {
			$result = '<h3>' . esc_html_x( 'Example Extension Setup', 'Settings header', 'tribe-ext-tec-tweaks' ) . '</h3>';
			$result .= '<div style="margin-left: 20px;">';
			$result .= '<p>';
			$result .= esc_html_x( 'Some text here about this settings section.', 'Settings', 'tribe-ext-tec-tweaks' );
			$result .= '</p>';
			$result .= '</div>';

			return $result;
		}

		/**
		 * Setting up the Tweaks setting tab in admin
		 */
		public function add_settings_tab() {
			$TabFields = [
				'a_start' => [
					'type' => 'text',
					'label'           => esc_html__( 'xxx try this', 'tribe-ext-tec-tweaks' ),
					'tooltip'         => sprintf( esc_html__( 'Enter your custom URL, including "http://" or "https://", for example %s.', 'tribe-ext-tec-tweaks' ), '<code>https://wpshindig.com/events/</code>' ),
					'validation_type' => 'html',
				],
			];
			$args = [
				'priority' => 110,
				'fields'   => $TabFields,
			];
			if ( empty ( $this->settings_tab ) ) {
				$this->settings_tab = new Tribe__Settings_Tab( 'tec-tweaks', esc_html__( 'Tweaks', 'tribe-common' ), $args );
			}
		}


	} // class
}
