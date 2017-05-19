<?php
/**
 * Plugin Name: WP Settings API
 * Plugin URI: https://github.com/ninecodes/social-manager
 * Description: Classes to easily add plugin pages using the WordPress Settings API.
 * Version: 1.2.0
 * Author: NineCodes
 * Author URI: https://github.com/ninecodes
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Requires at least: 4.5
 * Tested up to: 4.7
 *
 * Copyright (c) 2017 NineCodes (https://ninecodes.com/)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * @author Kees Meijer <keesie.meijer@gmail.com>
 * @package WP\Settings
 */

namespace NineCodes\WP\Settings;

if ( ! defined( 'WPINC' ) ) { // If this file is called directly.
	die; // Abort.
}

/**
 * The `WP\Settings` library might be used in the other plugins,
 * so ensure whether the Settings class has not been defined.
 */
if ( ! class_exists( __NAMESPACE__ . '\Settings' ) ) :

	/**
	 * Class for registering settings and sections and for display of the settings form(s).
	 *
	 * @since 1.0.0
	 * @since 2.1.0 Class renamed.
	 */
	final class Settings {

		/**
		 * The settings directory.
		 *
		 * @since 2.1.0
		 * @access protected
		 * @var string
		 */
		protected $path_dir = '';

		/**
		 * Current settings page.
		 *
		 * @since 2.0.0
		 * @access public
		 * @var array
		 */
		public $current_page = array();

		/**
		 * Debug errors and notices.
		 *
		 * @since 2.0.0
		 * @access public
		 * @var string
		 */
		public $debug = '';

		/**
		 * Admin pages.
		 *
		 * @since 2.0.0
		 * @access public
		 * @var array
		 */
		public $pages = array();

		/**
		 * Admin pages.
		 *
		 * @since 2.0.0
		 * @access private
		 * @var array
		 */
		private $fields;

		/**
		 * Unique plugin admin page hook suffix.
		 *
		 * @since 2.0.0
		 * @access private
		 * @var array
		 */
		private $screen;

		/**
		 * Fields that need the label_argument in add_settings_field()
		 *
		 * @since 2.0.0
		 * @access private
		 * @var array
		 */
		private $label_for = array( 'text', 'select', 'textarea' );

		/**
		 * JavaScripts handles to load for fields.
		 *
		 * @since 2.0.0
		 * @access private
		 * @var array
		 */
		private $field_scripts = array();

		/**
		 * Stylesheets handles to load for fields.
		 *
		 * @since 2.0.0
		 * @access private
		 * @var array
		 */
		private $field_styles = array();

		/**
		 * Array of JavaScripts needed for the current settings page.
		 *
		 * @since 2.0.0
		 * @access private
		 * @var array
		 */
		private $load_scripts = array();

		/**
		 * Array of Stylesheets needed for the current settings page.
		 *
		 * @since 2.0.0
		 * @access private
		 * @var array
		 */
		private $load_styles = array();

		/**
		 * Multiple forms on one settings page.
		 *
		 * @since 2.0.0
		 * @access private
		 * @var boolean
		 */
		private $multiform;

		/**
		 * Valid admin pages and fields arrays.
		 *
		 * @since 2.0.0
		 * @access private
		 * @var boolean
		 */
		private $valid_pages = true;

		/**
		 * Constructor
		 *
		 * @since 2.0.0
		 * @access public
		 *
		 * @param string $option_slug The option key slug.
		 */
		public function __construct( $option_slug = '' ) {

			$this->option_slug = $option_slug;
			$this->path_dir    = plugin_dir_path( __FILE__ );

			$this->requires();
			$this->setups();
		}

		/**
		 * Load required files.
		 *
		 * @since 2.0.0
		 * @access protected
		 *
		 * @return void
		 */
		protected function requires() {

			require_once( $this->path_dir . 'class-fields.php' );
		}

		/**
		 * Run the setups.
		 *
		 * @since 2.0.0
		 * @access protected
		 *
		 * @return void
		 */
		protected function setups() {

			/**
			 * Fetch settings errors registered by `add_settings_error()`.
			 *
			 * @var array
			 */
			$errors = get_settings_errors();

			$this->fields = new Fields( $errors );
		}

		/**
		 * Registers settings using the WordPress settings API.
		 *
		 * @since 2.0.0
		 * @access public
		 *
		 * @param string $screen The unique slug of the plugin setting page.
		 * @param array  $pages  The array of fields and inputs to register in the setting page.
		 */
		public function init( $screen = '', array $pages ) {

			$this->pages = $pages;
			$this->screen = trim( sanitize_key( $screen ) );

			/**
			 * ================================================================
			 * Debugging Message
			 * Show message in the setting page in case of error.
			 *
			 * NOTE Debug strings don't use gettext functions for translation.
			 * ================================================================
			 */
			if ( ! class_exists( 'NineCodes\WP\Settings\Fields' ) ) {
				$this->debug .= "Error: The class `Fields` doesn't exist.\n";
			}

			if ( empty( $this->screen ) ) {
				$this->debug .= "Error: The parameter `$screen` is not provided in `init()` method.\n";
			}

			if ( empty( $this->pages ) ) {
				$this->debug .= "Error: The parameter `$pages` is not provided or empty; no settings and inputs to register in this Setting page.\n";
			}

			$this->debug .= apply_filters( "{$this->screen}_debug", $this->debug, $this->pages );

			if ( ! empty( $this->debug ) ) {
				$this->valid_pages = false;
				return;
			}

			if ( isset( $this->current_page['multiform'] ) && $this->current_page['multiform'] ) {
				$this->multiform = ( count( $this->current_page['sections'] ) > 1 ) ? true : false;
			}

			/**
			 * Fields that needs the `label_for` arguments, which sets a label so that
			 * the setting title can be clicked on to focus on the field.
			 *
			 * @link https://codex.wordpress.org/Function_Reference/add_settings_field#With_Label
			 *
			 * @var array
			 */
			$this->label_for = apply_filters( "{$this->screen}_label_for", $this->label_for );

			/**
			 * JavaScripts handles to load for fields
			 *
			 * @var array
			 */
			$this->field_scripts = apply_filters( "{$this->screen}_field_scripts", array() );

			/**
			 * Stylesheets handles to load for fields
			 *
			 * @var array
			 */
			$this->field_styles = apply_filters( "{$this->screen}_field_styles", array() );

			/**
			 * Get input fields of the current admin page or
			 * current tab of the admin page.
			 *
			 * @var array
			 */
			$this->current_page = $this->get_current_admin_page();

			$this->add_settings_sections();
			$this->register_settings();

			$this->load_scripts = array_unique( $this->load_scripts );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

			$this->load_styles = array_unique( $this->load_styles );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		}

		/**
		 * The method to add Setting sections.
		 *
		 * @since 2.0.0
		 * @access private
		 *
		 * @return void
		 */
		private function add_settings_sections() {

			if ( empty( $this->current_page['sections'] ) ) {
				return;
			}

			foreach ( $this->current_page['sections'] as $section ) {
				$description = '__return_false';
				if ( isset( $section['description'] ) && ! empty( $section['description'] ) ) {
					$description = array( $this, 'render_section_description' );
				}

				$title = '';
				if ( isset( $section['title'] ) && ! empty( $section['title'] ) ) {
					$title = $section['title'];
				}

				$page_slug = $this->multiform ? $section['id'] : $this->current_page['id'];

				$option_key = "{$this->option_slug}_{$page_slug}";
				$section_id = "{$this->option_slug}_{$section['id']}";

				add_settings_section( $section_id, $title, $description, $option_key );

				if ( isset( $section['fields'] ) && ! empty( $section['fields'] ) ) {
					$this->add_settings_fields( $section_id, $section['fields'], $option_key ); // Add fields to sections.
				}

				/**
				 * ================================================================
				 * Debugging Message
				 * Show message in the setting page in case of error.
				 *
				 * NOTE Debug strings don't use gettext functions for translation.
				 * ================================================================
				 */
				$this->debug .= '' === $this->debug ? "Database option(s) created for this page:\n" : '';
				$this->debug .= "Database Option: {$section_id}\n";
			}
		}

		/**
		 * Adds all fields to a settings section.
		 *
		 * @since 2.0.0
		 * @access public
		 *
		 * @param string $sections_id  ID of section to add fields to.
		 * @param array  $fields       Array fields to add in the section.
		 * @param string $page_id      Page ID.
		 */
		private function add_settings_fields( $sections_id, $fields, $page_id ) {

			$defaults = array(
				'section' => $sections_id,
				'id' => '',
				'type' => '',
				'label' => '',
				'description' => '',
				'size' => 'regular',
				'options' => '',
				'default' => '',
				'content' => '',
				'attr' => null,
				'before' => '',
				'after' => '',
				'_type' => '',
			);

			foreach ( $fields as $field ) {

				$multiple = isset( $field['fields'] ) && $field['fields'] ? true : false;
				$options  = $multiple ? (array) $field['fields'] : array( $field );

				foreach ( $options as $key => $option ) {
					$args = wp_parse_args( $option, $defaults );

					if ( in_array( $args['type'], $this->label_for, true ) ) {
						$args['label_for'] = "{$sections_id}_{$args['id']}";
					}

					if ( key_exists( $args['type'], $this->field_scripts ) ) {
						$this->load_scripts[ $field['id'] ] = $this->field_scripts[ $args['type'] ];
					}

					if ( key_exists( $args['type'], $this->field_styles ) ) {
						$this->load_styles[ $field['id'] ] = $this->field_styles[ $args['type'] ];
					}

					if ( $multiple ) {
						$field['fields'][ $key ] = $args;
					}
				}

				if ( $multiple ) {
					$args = $field;
				}

				if ( ! method_exists( $this->fields, 'callback_' . $field['type'] ) ) {
					$args['callback'] = $field['type'];
					$args['page_hook'] = $this->screen;
					$field['type'] = 'extra_field';
				}

				if ( method_exists( $this->fields, 'callback_' . $field['type'] ) ) {
					add_settings_field(
						$sections_id . '[' . $field['id'] . ']',
						isset( $args['label'] ) ? $args['label'] : '',
						array( $this->fields, 'callback_' . $field['type'] ),
						$page_id,
						$sections_id,
						$args
					);
				}
			}// End foreach().
		}

		/**
		 * Registers Settings
		 *
		 * @since 2.0.0
		 * @access protected
		 *
		 * @return void
		 */
		protected function register_settings() {

			foreach ( $this->pages as $page ) {

				if ( empty( $page['sections'] ) ) {
					continue;
				}

				foreach ( $page['sections'] as $section ) {

					if ( isset( $page['multiform'] ) && $page['multiform'] ) {
						$page['id'] = count( $page['sections'] ) > 1 ? $section['id'] : $page['id'];
					}

					$group  = "{$this->option_slug}_{$page['id']}";
					$option = "{$this->option_slug}_{$section['id']}";

					if ( isset( $section['validate_callback'] ) && $section['validate_callback'] ) {
						register_setting( $group, $option, $section['validate_callback'] );
					} else {
						register_setting( $group, $option );
					}
				}
			}
		}

		/**
		 * Gets all settings from all sections.
		 *
		 * @since 2.0.0
		 * @access public
		 *
		 * @param string $section The setting section ID.
		 * @return array Array with settings.
		 */
		public function get_settings( $section = '' ) {

			$settings = array();

			if ( ! empty( $section ) ) {
				return get_option( "{$this->option_slug}_{$section}" );
			}

			foreach ( (array) $this->pages as $page ) {
				if ( ! isset( $page['sections'] ) ) {
					continue;
				}

				foreach ( $page['sections'] as $section ) {
					if ( ! isset( $section['id'] ) ) {
						continue;
					}

					$option = get_option( "{$this->option_slug}_{$section['id']}" );

					if ( $option ) {
						$settings[ $section['id'] ] = $option;
					}
				}
			}

			return $settings;
		}

		/**
		 * Returns the current settings page.
		 *
		 * @since 2.0.0
		 * @access public
		 *
		 * @return array Current settings page.
		 */
		public function get_current_admin_page() {

			foreach ( (array) $this->pages as $page ) {
				if ( isset( $_GET['tab'] ) && $_GET['tab'] ) {
					if ( ( $_GET['tab'] === $page['id'] ) || ( $_GET['tab'] === $page['slug'] ) ) {
						$current_page = $page;
					}
				}
			}

			if ( empty( $current_page ) ) { // Set the first settings page as current if it's not a tab.
				$current_page = $this->pages[0];
			}

			return $current_page;
		}

		/**
		 * Adds a admin page.
		 *
		 * @since 2.0.0
		 * @access public
		 *
		 * @param array $arr Page array.
		 * @return array Admin pages array with the page added.
		 */
		public function add_page( array $arr ) {

			$pages = array();

			foreach ( $arr as $key => $value ) {
				$pages[] = array(
					'id' => sanitize_key( $key ),
					'slug' => sanitize_key( $key ),
					'title' => wp_kses( $value, array() ),
				);
			}

			$this->pages = $pages;

			return $pages;
		}

		/**
		 * Adds multiple admin pages (Alias).
		 *
		 * @since 2.0.0
		 * @access public
		 *
		 * @param array $arr List pages to add in the Setting page.
		 * @return array
		 */
		public function add_pages( array $arr ) {

			$pages = array();

			foreach ( $arr as $key => $value ) {

				$slug = str_replace( '-', '_', sanitize_key( $key ) );
				$title = wp_kses( $value, array() );

				$pages[ $slug ] = $title;
			}

			$this->pages = $this->add_page( $arr );

			return $pages;
		}

		/**
		 * Adds a section to an admin page.
		 *
		 * @since 2.0.0
		 * @access public
		 *
		 * @param string $page_slug Page (tab) slug.
		 * @param array  $section Section array.
		 * @return array Admin pages array with the section added.
		 */
		public function add_section( $page_slug, $section ) {

			$add_section = array();
			foreach ( $section as $id => $s ) {
				$add_section = array_merge( array(
					'id' => $id,
				), $s );
			}

			foreach ( $this->pages as $key => $page ) {
				if ( $page_slug !== $page['id'] ) {
					continue;
				}

				if ( isset( $this->pages[ $key ][ $page_slug ]['sections'] ) ) {
					$this->pages[ $key ]['sections'] = array();
				}

				$this->pages[ $key ]['sections'][] = $add_section;
			}

			return $this->pages;
		}

		/**
		 * Adds multiple sections to an admin page.
		 *
		 * @since 2.0.0
		 * @access public
		 *
		 * @param array $page_slug  The unique page (tab) slug.
		 * @param array $sections   The section in the page (tab).
		 * @return array Admin pages array with the sections added.
		 */
		public function add_sections( $page_slug, $sections ) {

			foreach ( $sections as $id => $section ) {
				$this->pages = $this->add_section( $page_slug, array(
					$id => $section,
				) );
			}

			return $this->pages;
		}

		/**
		 * Adds a form field to a section.
		 *
		 * @since 2.0.0
		 * @access public
		 *
		 * @param string $page_slug     The unique page (tab) slug.
		 * @param string $section_slug  The setting section slug.
		 * @param array  $fields        The array of inputs to add in the setting page.
		 * @return array Admin pages array with the field added.
		 */
		public function add_field( $page_slug, $section_slug, $fields ) {

			$add_field = array();
			foreach ( $fields as $name => $f ) {

				if ( ! is_array( $f ) ) {
					continue;
				}

				$add_field = array_merge( array(
					'id' => $name,
				), $f );
			}

			foreach ( $this->pages as $p => $page ) {
				if ( $page_slug !== $page['id'] ) {
					continue;
				}

				if ( ! isset( $this->pages[ $p ]['sections'] ) ) {
					continue;
				}

				$sections = $this->pages[ $p ]['sections'];

				foreach ( $sections as $s => $section ) {
					if ( $section_slug !== $section['id'] ) {
						continue;
					}

					if ( ! isset( $this->pages[ $p ]['sections'][ $s ]['fields'] ) ) {
						$this->pages[ $p ]['sections'][ $s ]['fields'] = array();
					}

					$this->pages[ $p ]['sections'][ $s ]['fields'][] = $add_field;
				}
			}

			return $this->pages;
		}

		/**
		 * Adds multiple form fields to a section.
		 *
		 * @since 2.0.0
		 * @access public
		 *
		 * @param string $page    Page id.
		 * @param string $section Section id.
		 * @param array  $fields  Array with fields.
		 * @return array Admin pages array with the fields added.
		 */
		public function add_fields( $page, $section, $fields ) {

			foreach ( $fields as $name => $field ) {
				$this->pages = $this->add_field( $page, $section, array(
					$name => $field,
				) );
			}

			return $this->pages;
		}

		/**
		 * Enqueue JavaScripts for fields that need them.
		 *
		 * @since 2.0.0
		 * @access public
		 *
		 * @return void
		 */
		public function enqueue_scripts() {

			$screen = get_current_screen();

			if ( $screen->id === $this->screen ) {
				do_action( "{$this->screen}_enqueue_scripts", $this->load_scripts );
			}
		}

		/**
		 * Enqueue Stylesheets for fields that need them.
		 *
		 * @since 2.1.0
		 * @access public
		 *
		 * @return void
		 */
		public function enqueue_styles() {

			$screen = get_current_screen();

			if ( $screen->id === $this->screen ) {
				do_action( "{$this->screen}_enqueue_styles", $this->load_styles );
			}
		}

		/**
		 * Display the description of a section.
		 *
		 * @since 2.0.0
		 * @access public
		 *
		 * @param array $section Setting section.
		 * @return void
		 */
		public function render_section_description( $section ) {

			foreach ( $this->current_page['sections'] as $setting ) {
				if ( $this->option_slug . '_' . $setting['id'] === $section['id'] ) {
					echo '<p>' . wp_kses_post( $setting['description'] ) . '</p>';
				}
			}
		}

		/**
		 * Display Plugin Title and if needed tabbed navigation.
		 *
		 * @since 2.0.0
		 * @access public
		 *
		 * @param array  $args Header arguments.
		 * @param string $page_slug Page ID. Manually set the active tab.
		 * @return void
		 */
		public function render_header( array $args, $page_slug = '' ) {

			$title = isset( $args['title'] ) && true === $args['title'] ? esc_html( get_admin_page_title() ) : '';
			$title = $title ?  "<h1>{$title}</h1>" : $title;

			echo wp_kses( $title, array(
				'h1' => true,
			) );

			$page_title_count = 0;
			foreach ( $this->pages as $page ) {
				if ( isset( $page['title'] ) && $page['title'] ) {
					++$page_title_count;
				}
			}

			$html = '';

			$current    = $this->current_page;
			$page_slugs = wp_list_pluck( $this->pages, 'id' );

			$current_page_slug = $page_slug ? (string) $page_slug : $current['id'];
			$current_page_slug = in_array( $current_page_slug, $page_slugs, true ) ? $current_page_slug : $current['id'];

			$i = 0;

			foreach ( $this->pages as $page ) {
				if ( ( isset( $page['title'] ) && $page['title'] ) ) {
					if ( $page_title_count > 1 ) {
						$html .= ( 0 === $i ) ? '<nav class="nav-tab-wrapper">' : '';

						$active = '';
						if ( $current_page_slug === $page['id'] ) {
							$active = ' nav-tab-active';
						}

						$page_url = remove_query_arg( array( 'tab', 'settings-updated' ) ); // Get the url of the current settings page.

						if ( $this->pages[0]['id'] !== $page['id'] ) { // Add query arg 'tab' if it's not the first settings page.
							$page_url = add_query_arg( 'tab', $page['slug'], $page_url );
						}

						$html .= sprintf(
							'<a href="%1$s" class="nav-tab%2$s" id="%3$s-tab">%4$s</a>',
							esc_url( $page_url ),
							$active,
							esc_attr( $page['id'] ),
							$page['title']
						);

						$html .= ( ++$i === $page_title_count ) ? '</nav>' : '';
					}

					if ( 1 === $page_title_count ) {
						if ( isset( $current['title'] ) && $current['title'] === $page['title'] ) {
							$html .= '<h2>' . $page['title'] . '</h2>';
							break;
						}
					}
				}
			}

			echo wp_kses_post( $html );
		}

		/**
		 * Displays the form(s) and sections.
		 *
		 * @since 2.0.0
		 * @access public
		 *
		 * @return void
		 */
		public function render_form() {

			if ( ! $this->valid_pages ) {
				return;
			}

			$page = $this->current_page;

			if ( ! empty( $page ) ) {
				$forms = $this->multiform ? $page['sections'] : array( $page );

				foreach ( $forms as $form ) {
					echo '<form method="post" action="options.php">';

					$title = isset( $page['title'] ) && ! empty( $page['title'] ) ? esc_html( $page['title'] ) : '';
					$title = $title ? "<h1 class='screen-reader-text'>{$title}</h1>" : '';

					echo wp_kses($title, array(
							'h1' => array(
							'class' => true,
						),
					));

					do_action( "pre_{$this->screen}_form", $form['id'], $form );

					settings_fields( $this->option_slug . '_' . $form['id'] );
					do_settings_sections( $this->option_slug . '_' . $form['id'] );

					do_action( "{$this->screen}_form", $form['id'], $form );

					$submit = ( isset( $form['submit'] ) && $form['submit'] ) ? $form['submit'] : '';

					if ( ( '' === $submit ) && isset( $page['submit'] ) && $page['submit'] ) {
						$submit = $page['submit'];
					}

					$text = isset( $submit['text'] )  ? $submit['text'] : null;
					$type = isset( $submit['type'] ) ? $submit['type'] : 'primary';
					$name = isset( $submit['name'] ) ? $submit['name'] : 'submit';

					$other_attributes = array(
						'id' => $form['id'],
					);

					submit_button( $text, $type, $name, true, $other_attributes );
					echo '</form>';
				}
			}// End if().
		}
	}
endif; // End if().
