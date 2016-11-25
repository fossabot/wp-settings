<?php
/**
 * WPSettingsFields
 *
 * @version 2.1.0
 * @author Kees Meijer
 * @author Thoriq Firdaus <tfirdaus@outlook.com>
 * @link https://github.com/keesiemeijer/WP-Settings
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 *
 * @since 1.0.0
 * @since 2.1.0 - Add namespace.
 *              - Adopt SemVer for version numbering.
 *              - Rename class to WPSettingsFields.
 *
 * @package Settings
 * @subpackage Fields
 */

namespace NineCodes\WPSettings;

/**
 * Class to register input fields in the WordPress setting page.
 *
 * @since 1.0.0
 * @since 2.1.0 - Class renamed.
 */
class WPSettingsFields {

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
	public function callback_text( $args ) {
		$args['size'] = ( isset( $args['size'] ) && $args['size'] ) ? $args['size'] : 'regular';
		$type  = ! empty( $args['text_type'] ) ? esc_attr( $args['text_type'] ) : 'text';
		$args  = $this->get_arguments( $args ); // escapes all attributes
		$value = (string) esc_attr( $this->get_option( $args ) );
		$error = $this->get_setting_error( $args['id'] );
		$html  = sprintf( '<input type="%6$s" id="%1$s_%2$s" name="%1$s[%2$s]" value="%3$s"%4$s%5$s/>', $args['section'], $args['id'], $value, $args['attr'], $error, $type );

		echo $args['before'] . $html . $args['after'] . $this->description( $args['description'] );
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
	public function callback_textarea( $args ) {
		$size  = ( isset( $args['size'] ) && $args['size'] ) ? $args['size'] : 'regular';
		$args  = $this->get_arguments( $args ); // escapes all attributes
		$value = (string) esc_textarea( $this->get_option( $args ) );
		$error = $this->get_setting_error( $args['id'] );
		$html  = sprintf( '<textarea id="%1$s_%2$s" name="%1$s[%2$s]"%4$s%5$s>%3$s</textarea>', $args['section'], $args['id'], $value, $args['attr'], $error );

		echo $args['before'] . $html . $args['after'] . $this->description( $args['description'] );
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
	public function callback_select( $args ) {
		$args  = $this->get_arguments( $args ); // escapes all attributes
		$value = array_map( 'esc_attr', array_values( (array) $this->get_option( $args ) ) );
		$multiple = ( preg_match( '/multiple="multiple"/', strtolower( $args['attr'] ) ) ) ? '[]' : '';
		$value = ( '[]' === $multiple ) ? $value : $value[0];
		$html  = sprintf( '<select id="%1$s_%2$s" name="%1$s[%2$s]%4$s"%3$s>', $args['section'], $args['id'], $args['attr'], $multiple );
		foreach ( (array) $args['options'] as $opt => $label ) {
			if ( '[]' === $multiple ) {
				$selected = ( in_array( $opt, $value ) ) ? ' selected="selected" ' : '';
			} else {
				$selected = selected( $value, $opt, false );
			}
			$html .= sprintf( '<option value="%s"%s>%s</option>', $opt, $selected, $label );
		}
		$html .= sprintf( '</select>' );
		echo $args['before'] . $html . $args['after'] . $this->description( $args['description'] );
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
	public function callback_checkbox( $args ) {
		$args  = $this->get_arguments( $args ); // escapes all attributes
		$value = (string) esc_attr( $this->get_option( $args ) );

		$error = $this->get_setting_error( $args['id'], ' style="border: 1px solid red; padding: 2px 1em 2px 0; "' );
		$html  = '';

		$input = sprintf( '<input type="checkbox" id="%1$s_%2$s" name="%1$s[%2$s]" value="on"%4$s%5$s />', $args['section'], $args['id'], $value, checked( $value, 'on', false ), $args['attr'] );
		$html .= sprintf( '<label for="%1$s_%2$s"%5$s>%3$s %4$s</label>', $args['section'], $args['id'], $input, $args['description'], $error );

		echo $html . '';
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
	public function callback_multicheckbox( $args ) {
		$args  = $this->get_arguments( $args ); // escapes all attributes
		$value = array_map( 'esc_attr', array_values( (array) $this->get_option( $args ) ) );
		$count = count( $args['options'] );
		$html  = '<fieldset>';
		$i = 0;
		foreach ( (array) $args['options'] as $opt => $label ) {
			$error = $this->get_setting_error( $opt, ' style="border: 1px solid red; padding: 2px 1em 2px 0; "' );
			$checked = ( in_array( $opt, $value ) ) ? ' checked="checked" ' : '';
			$input = sprintf( '<input type="checkbox" id="%1$s_%2$s_%3$s" name="%1$s[%2$s][%3$s]" value="%3$s"%4$s%5$s />', $args['section'], $args['id'], $opt, $checked, $args['attr'] );
			$html .= sprintf( '<label for="%1$s_%2$s_%4$s"%6$s>%3$s %5$s</label>', $args['section'], $args['id'], $input, $opt, $label, $error );
			$html .= ( isset( $args['row_after'][ $opt ] ) && $args['row_after'][ $opt ] ) ? $args['row_after'][ $opt ] : '';
			$html .= ( ++$i < $count ) ? '<br/>' : '';
		}

		echo $html . '</fieldset>' . $this->description( $args['description'] );
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
	public function callback_radio( $args ) {
		$args = $this->get_arguments( $args ); // escapes all attributes
		$value = (string) esc_attr( $this->get_option( $args ) );
		$options = array_keys( (array) $args['options'] );
		// make sure one radio button is checked
		if ( empty( $value ) && ( isset( $options[0] ) && $options[0] ) ) {
			$value = $options[0];
		} elseif ( ! empty( $value ) && ( isset( $options[0] ) && $options[0] ) ) {
			if ( ! in_array( $value, $options ) ) {
				$value = $options[0];
			}
		}
		$html = '<fieldset>';
		$i = 0;
		$count = count( $args['options'] );
		foreach ( (array) $args['options'] as $opt => $label ) {
			$input = sprintf( '<input type="radio" id="%1$s_%2$s_%3$s" name="%1$s[%2$s]" value="%3$s"%4$s%5$s />', $args['section'], $args['id'], $opt, checked( $value, $opt, false ), $args['attr'] );
			$html .= sprintf( '<label for="%1$s_%2$s_%4$s">%3$s%5$s</label>', $args['section'], $args['id'], $input, $opt, ' <span>' . $label . '</span>' );
			$html .= ( isset( $args['row_after'][ $opt ] ) && $args['row_after'][ $opt ] ) ? $args['row_after'][ $opt ] : '';
			$html .= ( ++$i < $count ) ? '<br/>' : '';
		}

		echo '</fieldset>' . $html . $this->description( $args['description'] );
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
	public function callback_content( $args ) {
		if ( isset( $args['content'] ) ) {
			echo $args['content'];
		}
		if ( isset( $args['description'] ) ) {
			echo $this->description( $args['description'] );
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
	final public function callback_extra_field( $args ) {
		if ( isset( $args['callback'] ) && $args['callback'] ) {
			if ( isset( $args['page_hook'] ) && $args['page_hook'] ) {
				do_action( "{$args['page_hook']}_add_extra_field", $args );
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
	 * @return void
	 */
	public function description( $desc = '' ) {
		if ( $desc ) {
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
	 * @param string $style      Style to override the default error style.
	 * @return string Empty string or inline style attribute.
	 */
	final protected function get_setting_error( $setting_id, $attr = '' ) {
		$display_error = '';

		if ( ! empty( $this->setting_errors ) ) {
			foreach ( $this->setting_errors as $error ) {
				if ( isset( $error['setting'] ) && $error['setting'] === $setting_id ) {
					if ( '' === $attr ) {
						// TODO: Don't use inline styles
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
	 * @param string       $input Type of field.
	 * @param string       $size  Size of field (class name).
	 * @return array All arguments and attributes
	 */
	final protected function get_arguments( $args = '', $class = false ) {

		// Escape section, id and options used in attributes.
		$args['section'] = esc_attr( $args['section'] );
		$args['id'] = esc_attr( $args['id'] );

		if ( isset( $args['options'] ) && $args['options'] ) {
			$options = array();
			foreach ( (array) $args['options'] as $key => $value ) {
				$options[ esc_attr( $key ) ] = $value;
			}
			$args['options'] = $options;
		}

		// Additional parameters.
		$attr_string = '';
		$defaults = $attr = array();

		if ( isset( $args['attr'] ) && $args['attr'] ) {
			$attr = $args['attr'];
		}

		// Set defaults for a textarea field.
		if ( 'textarea' === $args['type'] ) {
			$defaults = array( 'rows' => '5', 'cols' => '55' );
		}

		// TODO: Add action to add additional defaults
		$attr['class'] = isset( $attr['class'] ) ? trim( $attr['class'] ) : '';

		if ( isset( $args['size'] ) &&  $args['size'] ) {
			if ( 'text' === $args['type'] || 'textarea' === $args['type'] ) {
				$attr['class'] .= sprintf( ' %1$s-%2$s', $args['size'], $args['type'] );
			}
		}

		if ( $class ) {
			if ( ! preg_match( '/\s' . preg_quote( (string) $class, '/' ) . '\s/', $attr['class'] ) ) {
				$attr['class'] = ' ' . (string) $class;
			}
		}

		if ( '' === $attr['class'] ) {
			unset( $attr['class'] );
		}

		// Create attribute string.
		foreach ( $attr as $key => $arg ) {
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
	 * @param array $args Arguments of setting field
	 * @return string
	 */
	final public function get_option( $args ) {

		if ( isset( $args['value'] ) ) {
			return $args['value'];
		}

		/**
		 * Get the value for the setting field from the database.
		 *
		 * @var mixed
		 */
		$options = get_option( $args['section'] );

		// Return the value if it exists.
		if ( isset( $options[ $args['id'] ] ) ) {
			return $options[ $args['id'] ];
		}

		// Return the default value.
		return ( isset( $args['default'] ) ) ? $args['default'] : '';
	}
}
