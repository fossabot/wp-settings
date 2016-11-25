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
 *
 * TODO: Add function to install option from extension.
 */

namespace NineCodes\WPSettings;

if ( ! defined( 'WPINC' ) ) { // If this file is called directly.
	die; // Abort.
}

/**
 * Class to Install registered option into the database.
 *
 * @since 2.1.0
 */
final class Install {

	/**
	 * The Registered Options in the Settings.
	 *
	 * @since 2.1.0
	 * @access protected
	 * @var array
	 */
	protected $options;

    /**
     * The option key name.
     *
     * @since 2.1.0
	 * @access protected
     * @var string
     */
    protected $option_key;

	/**
	 * Constructor.
	 *
     * @since 2.1.0
	 * @access public
	 *
	 * @param array  $pages The registered setting pages / sections.
	 * @param string $opts  The option key name.
	 */
	public function __construct( array $pages, $option_key = '' ) {

		if ( empty( $pages ) || empty( $option_key ) ) {
			return;
		}

		$this->pages = $pages;
		$this->option_key = $option_key;

		$this->setups(); // Run the setup.
	}

	/**
	 * Function to setup the installation.
	 *
     * @since 2.1.0
	 * @access protected
	 *
	 * @return void
	 */
	protected function setups() {
		$this->get_options( $this->pages );
		$this->save_options();
	}

	/**
	 * Function to construct option name and their default in array.
	 *
     * @since 2.1.0
	 * @access protected
	 *
	 * @return void
	 */
	protected function get_options( array $pages ) {

		$sections = array();
		$fields   = array();
		$options  = array();
		$default  = array();

		foreach ( $pages as $key => $value ) {
			$sections[] = $value[ 'sections' ];
		}

		foreach ( $sections as $key => $section ) {
			foreach ( $section as $key => $s ) {
				$fields[ "{$this->option_key}_{$s['id']}" ] = $s['fields'];
			}
		}

		foreach ( $fields as $opt => $field ) {
			foreach ( $field as $key => $f ) {
				if ( isset( $f[ 'default' ] ) ) {
					$default[ $f[ 'id' ] ] = $f[ 'default' ];
				} else {
					unset( $field[ $key ] );
				}
			}

			if ( ! empty( $default ) ) {
				$options[ $opt ] = $default;
			}
		}

		$this->options = $options;
	}

	/**
	 * Function to add value to the database.
	 *
     * @since 2.1.0
	 * @access protected
	 *
	 * @return void
	 */
	protected function save_options() {
		foreach ( $this->options as $name => $default ) {
			$options = get_option( $name );
			if ( ! $options ) {
				add_option( $name, $default );
			}
		}
	}
}
