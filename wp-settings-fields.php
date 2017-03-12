<?php
/**
 * Class: Fields
 *
 * The file to hold the class `Fields`.
 *
 * @version 2.1.0
 * @author Kees Meijer
 *
 * @since 1.0.0
 * @since 2.1.0 - Add namespace.
 *              - Adopt SemVer for version numbering.
 *
 * @package WPSettings\Fields
 */

namespace NineCodes\WPSettings;

if ( ! defined( 'WPINC' ) ) { // If this file is called directly.
	die; // Abort.
}

/**
 * The `WPSettings` library might be used in the other plugins,
 * so ensure whether the Settings class has not been defined.
 */
if ( ! class_exists( '\NineCodes\WPSettings\Fields' ) ) {

	/**
	 * Class to register input fields in the WordPress setting page.
	 *
	 * @since 1.0.0
	 * @since 2.1.0 - Class renamed.
	 */
	class Fields {

		/**
		 * Validated settings errors
		 *
		 * @since 1.0.0
		 * @access public
		 * @var array
		 */
		public $settings_errors;

		/**
		 * Constructor.
		 *
		 * @since 1.0.0
		 * @access public
		 *
		 * @param array $errors The array of settings errors from `get_settings_errors()` function.
		 */
		public function __construct( array $errors ) {

			$this->setting_errors = $errors;
		}

		/**
		 * Displays a text input setting field.
		 *
		 * @since 1.0.0
		 * @access public
		 *
		 * @param array $args The input arguments.
		 * @return void
		 */
		public function callback_text( array $args ) {

			$args = $this->get_arguments( $args ); // Escapes all attributes.
			$args = wp_parse_args( $args, array(
				'text_type' => 'text',
			) );

			$type  = $args['type'];
			$value = (string) esc_attr( $this->get_option( $args ) );
			$error = $this->get_setting_error( $args['id'] );

			$elem = sprintf( '<input type="%6$s" id="%1$s_%2$s" name="%1$s[%2$s]" value="%3$s"%4$s%5$s/>',
				$args['section'],
				esc_attr( $args['id'] ),
				esc_attr( $value ),
				$args['attr'],
				$error,
				esc_attr( $type )
			);

			$before = wp_kses_post( $args['before'] );
			$after = wp_kses_post( $args['after'] );
			$description = wp_kses_post( $this->description( $args['description'] ) );

			echo $before . $elem . $after . $description; // XSS ok.
		}

		/**
		 * Displays a textarea.
		 *
		 * @since 1.0.0
		 * @access public
		 *
		 * @param array $args The input arguments.
		 * @return void
		 */
		public function callback_textarea( array $args ) {

			$args  = $this->get_arguments( $args ); // Escapes all attributes.
			$value = $this->get_option( $args );
			$error = $this->get_setting_error( $args['id'] );

			$id = esc_attr( $args['id'] );
			$section = esc_attr( $args['section'] );
			$value = esc_textarea( $value );

			$elem = sprintf( '<textarea id="%1$s_%2$s" name="%1$s[%2$s]"%4$s%5$s>%3$s</textarea>', $section, $id, $value, $args['attr'], $error );

			$before = wp_kses_post( $args['before'] );
			$after = wp_kses_post( $args['after'] );
			$description = wp_kses_post( $this->description( $args['description'] ) );

			echo $before . $elem . $after . $description; // XSS ok.
		}

		/**
		 * Displays a select dropdown.
		 *
		 * @since 1.0.0
		 * @access public
		 *
		 * @param array $args The input arguments.
		 * @return void
		 */
		public function callback_select( array $args ) {

			$args = $this->get_arguments( $args ); // Escapes all attributes.

			/**
		 * Determine the suffix valud for multiple selection.
		 *
		 * @var string
		 */
			$multiple = preg_match( '/multiple="multiple"/', strtolower( $args['attr'] ) ) ? '[]' : '';

			/**
		 * Sanitize all the value.
		 *
		 * @var array
		 */
			$value = array_map( 'esc_attr', array_values( (array) $this->get_option( $args ) ) );
			$value = '[]' === $multiple ? $value : $value[0];

			$elem = sprintf( '<select id="%1$s_%2$s" name="%1$s[%2$s]%4$s"%3$s>',
				esc_attr( $args['section'] ),
				esc_attr( $args['id'] ),
				$args['attr'],
				esc_attr( $multiple )
			);

			foreach ( (array) $args['options'] as $option => $label ) {

				$option = esc_attr( $option );

				if ( '[]' === $multiple ) {
					$selected = ( in_array( $option, $value, true ) ) ? ' selected="selected" ' : '';
				} else {
					$selected = selected( $value, $option, false );
				}

				$elem .= sprintf( '<option value="%1$s"%2$s>%3$s</option>', $option, $selected, $label );
			}

			$elem .= '</select>';

			$before = wp_kses_post( $args['before'] );
			$after = wp_kses_post( $args['after'] );
			$description = wp_kses_post( $this->description( $args['description'] ) );

			echo $before . $elem . $after . $description; // XSS ok.
		}

		/**
		 * Displays a single checkbox.
		 *
		 * @since 1.0.0
		 * @access public
		 *
		 * @param array $args The input arguments.
		 * @return void
		 */
		public function callback_checkbox( array $args ) {

			$args = $this->get_arguments( $args ); // Escapes all attributes.

			$id = esc_attr( $args['id'] );
			$section = esc_attr( $args['section'] );
			$value = esc_attr( $this->get_option( $args ) );

			$checkbox = sprintf( '<input type="checkbox" id="%1$s_%2$s" name="%1$s[%2$s]" value="on"%4$s%5$s />',
				$section,
				$id,
				$value,
				checked( $value, 'on', false ),
				$args['attr']
			);

			$error = $this->get_setting_error( $id, ' style="border: 1px solid red; padding: 2px 1em 2px 0; "' );
			$description = wp_kses_post( $args['description'] );

			$elem = sprintf( '<label for="%1$s_%2$s"%5$s>%3$s %4$s</label>', $section, $id, $checkbox, $description, $error );

			echo $elem; // XSS ok.
		}

		/**
		 * Displays multiple checkboxes.
		 *
		 * @since 1.0.0
		 * @access public
		 *
		 * @param array $args The input arguments.
		 * @return void
		 */
		public function callback_multicheckbox( array $args ) {

			$args = $this->get_arguments( $args ); // Escapes all attributes.

			$id = esc_attr( $args['id'] );
			$section = esc_attr( $args['section'] );
			$value = (array) $this->get_option( $args );

			$count = count( $args['options'] );
			$elem = '<fieldset>';
			$i = 0;

			foreach ( (array) $args['options'] as $option => $label ) {

				$error = $this->get_setting_error( $option, ' style="border: 1px solid red; padding: 2px 1em 2px 0; "' );
				$checked = isset( $value[ $option ] ) && 'on' === $value[ $option ] ? ' checked="checked" ' : '';

				$option = esc_attr( $option );
				$label = esc_attr( $label );

				$input = sprintf( '<input type="checkbox" id="%1$s_%2$s_%3$s" name="%1$s[%2$s][%3$s]" value="on"%4$s%5$s />',
					$section,
					$id,
					$option,
					$checked,
					$args['attr']
				);

				$elem .= sprintf( '<label for="%1$s_%2$s_%4$s"%6$s>%3$s %5$s</label>', $section, $id, $input, $option, $label, $error );
				$elem .= isset( $args['row_after'][ $option ] ) && $args['row_after'][ $option ] ? $args['row_after'][ $option ] : '';
				$elem .= ++$i < $count ? '<br/>' : '';
			}

			$description = wp_kses_post( $this->description( $args['description'] ) );

			echo $elem . '</fieldset>' . $description; // XSS ok.
		}

		/**
		 * Displays radio buttons.
		 *
		 * @since 1.0.0
		 * @access public
		 *
		 * @param array $args The input arguments.
		 * @return void
		 */
		public function callback_radio( array $args ) {

			$args = $this->get_arguments( $args ); // Escapes all attributes.

			$id = esc_attr( $args['id'] );
			$section = esc_attr( $args['section'] );
			$value = esc_attr( $this->get_option( $args ) );

			$options = array_keys( (array) $args['options'] );

			if ( empty( $value ) && ( isset( $options[0] ) && $options[0] ) ) { // Make sure one radio button is checked.
				$value = $options[0];
			} elseif ( ! empty( $value ) && ( isset( $options[0] ) && $options[0] ) ) {
				if ( ! in_array( $value, $options, true ) ) {
					$value = $options[0];
				}
			}

			$elem = '<fieldset>';
			$i = 0;

			$count = count( $args['options'] );
			foreach ( (array) $args['options'] as $option => $label ) {

				$option = esc_attr( $option );
				$label = esc_attr( $label );

				$radio = sprintf( '<input type="radio" id="%1$s_%2$s_%3$s" name="%1$s[%2$s]" value="%3$s"%4$s%5$s />',
					$section,
					$id,
					$option,
					checked( $value, $option, false ),
					$args['attr']
				);

				$elem .= sprintf( '<label for="%1$s_%2$s_%4$s">%3$s%5$s</label>', $section, $id, $radio, $option, ' <span>' . $label . '</span>' );
				$elem .= isset( $args['row_after'][ $option ] ) && $args['row_after'][ $option ] ? $args['row_after'][ $option ] : '';
				$elem .= ++$i < $count ? '<br/>' : '';
			}

			$description = wp_kses_post( $this->description( $args['description'] ) );

			echo '</fieldset>' . $elem . $description; // XSS ok.
		}

		/**
		 * Displays type 'content' field.
		 *
		 * @since 1.0.0
		 * @access public
		 *
		 * @param array $args The input arguments.
		 * @return void
		 */
		public function callback_content( array $args ) {

			if ( isset( $args['content'] ) && ! empty( $args['content'] ) ) {
				echo wp_kses_post( $args['content'] );
			}

			if ( isset( $args['description'] ) && ! empty( $args['content'] ) ) {
				echo wp_kses_post( $this->description( $args['description'] ) );
			}
		}

		/**
		 * Displays field with the action hook '{$page_hook}_add_extra_field'.
		 *
		 * @since 1.0.0
		 * @access public
		 *
		 * @param array $args The input arguments.
		 * @return void
		 */
		final public function callback_extra_field( array $args ) {

			if ( isset( $args['callback'] ) && $args['callback'] ) {
				if ( isset( $args['page_hook'] ) && $args['page_hook'] ) {
					do_action( "{$args['page_hook']}_field_{$args['type']}", $args );
				}
			}
		}

		/**
		 * Returns a field description.
		 *
		 * @since 1.0.0
		 * @access public
		 *
		 * @param string $desc Description of field.
		 * @return string
		 */
		public function description( $desc = '' ) {

			if ( ! empty( $desc ) ) {
				return sprintf( '<p class="description">%s</p>', $desc );
			}
		}

		/**
		 * Returns validation errors for a settings field.
		 *
		 * @since 1.0.0
		 * @access protected
		 *
		 * @param string $setting_id Settings field ID.
		 * @param string $attr       The 'style' attribute to override the default error style.
		 * @return string Empty string or inline style attribute.
		 */
		final protected function get_setting_error( $setting_id, $attr = '' ) {

			$display_error = '';

			if ( ! empty( $this->setting_errors ) ) {
				foreach ( $this->setting_errors as $error ) {
					if ( isset( $error['setting'] ) && $error['setting'] === $setting_id ) {
						if ( '' === $attr ) {
							// TODO: Don't use inline styles.
							$display_error = ' style="border: 1px solid red;"';
						} else {
							$display_error = $attr;
						}
					}
				}
			}

			return $display_error;
		}

		/**
		 * Escapes and creates additional attributes for a setting field.
		 *
		 * @since 1.0.0
		 * @access protected
		 *
		 * @param string|array $args  Arguments of a setting field.
		 * @return array All arguments and attributes
		 */
		final protected function get_arguments( $args = '' ) {

			// Escape section, id, and options used in attributes.
			$args['section'] = esc_attr( $args['section'] );
			$args['id'] = esc_attr( $args['id'] );

			if ( isset( $args['options'] ) && $args['options'] ) {
				$options = array();
				foreach ( (array) $args['options'] as $key => $value ) {
					$options[ esc_attr( $key ) ] = $value;
				}
				$args['options'] = $options;
			}

			// Set the default output.
			$attr_string = '';

			$attr = array();

			if ( isset( $args['attr'] ) && $args['attr'] ) {
				$attr = $args['attr'];
			}

			if ( 'textarea' === $args['type'] ) { // Set defaults for a textarea field.
				$attr = wp_parse_args( $attr, array( 'rows' => '5', 'cols' => '55' ) );
			}

			/**
			 * Store extra clasess from the user definitions.
			 *
			 * TODO: Add action to add additional defaults.
			 *
			 * @var string
			 */
			$classes = isset( $attr['class'] ) ? trim( $attr['class'] ) : '';

			$attr['class'] = sprintf( ' field-%1$s', str_replace( '_', '-', $args['type'] ) );

			if ( isset( $args['size'] ) && $args['size'] ) {
				if ( 'text' === $args['type'] || 'textarea' === $args['type'] ) {
					$attr['class'] .= sprintf( ' %1$s-%2$s', $args['size'], $args['type'] );
				}
			}

			$attr['class'] .= " {$classes}";

			if ( '' === $attr['class'] ) {
				unset( $attr['class'] );
			}

			foreach ( $attr as $key => $arg ) { // Create attribute string.
				$arg = ( 'class' === $arg ) ? sanitize_html_class( $arg ) : esc_attr( $arg );
				$attr_string .= ' ' . trim( $key ) . '="' . trim( $arg ) . '"';
			}

			$args['attr'] = $attr_string;

			return $args;
		}

		/**
		 * Returns the value of a setting field.
		 *
		 * @since 1.0.0
		 * @access public
		 *
		 * @param array $args Arguments of setting field.
		 * @return string
		 */
		final public function get_option( array $args ) {

			if ( isset( $args['value'] ) ) {
				return $args['value'];
			}

			/**
			 * Get the value for the setting field from the database.
			 *
			 * @var mixed
			 */
			$options = get_option( $args['section'] );

			if ( isset( $options[ $args['id'] ] ) ) { // Return the value if it exists.
				return $options[ $args['id'] ];
			}

			// Return the default value.
			return ( isset( $args['default'] ) ) ? $args['default'] : '';
		}
	}
}
