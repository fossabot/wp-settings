<?php
/**
 * WPSettings
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
 *              - Rename class.
 *
 * @package Settings
 */

namespace NineCodes\WPSettings;

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
	 * valid admin pages and fields arrays.
	 *
	 * @since 2.0.0
	 * @access private
	 * @var boolean
	 */
	private $valid_pages;

	/**
	 * Constructor
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @param string $opts The option key name.
	 */
	public function __construct( $opts ) {

		$this->opts = $opts;
		$this->path_dir = plugin_dir_path( __FILE__ );

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
		require_once( $this->path_dir . 'wp-settings-fields.php' );
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
		$this->fields = new WPSettingsFields( get_settings_errors() );
	}

	/**
	 * Registers settings using the WordPress settings API.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @param array  $pages  Array with admin pages.
	 * @param string $screen Unique plugin admin page hook suffix.
	 */
	public function init( $screen = '', array $pages ) {

		$this->pages = $pages;
		$this->screen = trim( sanitize_title( $screen ) );

		// Debug strings don't use gettext functions for translation.
		if ( ! class_exists( 'WPSettingsFields' ) ) {
			$this->debug .= "Error: class WPSettingsFields doesn't exist<br/>";
		}

		if ( '' === $this->screen ) {
			$this->debug .= "Error: parameter 'screen' not provided in init()<br/>";
		}

		$this->debug .= apply_filters( "{$this->opts}_debug", $this->debug, $this->pages );

		if ( $this->debug ) {
			return $this->valid_pages = false; // Don't display the form and navigation.
		}

		// Passed validation (required to show form and navigation).
		$this->valid_pages = true;

		if ( isset( $this->current_page['multiform'] ) && $this->current_page['multiform'] ) {
			$this->multiform = ( count( $this->current_page['sections'] ) > 1 ) ? true : false;
		}

		// Array of fields that needs the 'label_for' parameter (add_settings_field()).
		$this->label_for = apply_filters( "{$this->opts}_label_for", $this->label_for );

		// Special field with type.
		$this->field_scripts = apply_filters( "{$this->opts}_field_scripts", array() );

		// Special field with type.
		$this->field_styles = apply_filters( "{$this->opts}_field_styles", array() );

		// Get Current ad min page
		$this->current_page = $this->get_current_admin_page();

		// Add setting sections.
		$this->add_settings_sections();

		// Register all the settings.
		$this->register_settings();

		// Only load javascript if it's needed for the current admin page
		if ( ! empty( $this->load_scripts ) ) {
			$this->load_scripts = array_unique( $this->load_scripts );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		}

		// Only load styles if it's needed for the current admin page.
		if ( ! empty( $this->load_styles ) ) {
			$this->load_styles = array_unique( $this->load_styles );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		}
	} // admin_init()


	/**
	 * Adds setting sections
	 *
	 * @since 2.0.0
	 * @access private
	 *
	 * @return void
	 */
	private function add_settings_sections() {

		foreach ( $this->current_page['sections'] as $section ) {
			$description = '__return_false';
			if ( isset( $section['description'] ) && ! empty( $section['description'] ) ) {
				$description = array( $this, 'render_section_description' );
			}

			$title = '';
			if ( isset( $section['title'] ) && ! empty( $section['title'] ) ) {
				$title = $section['title'];
			}

			$page = $this->multiform ? $section['id'] : $this->current_page['id'];

			$page = "{$this->opts}_{$page}";
			$id   = "{$this->opts}_{$section['id']}";

			add_settings_section( $id, $title, $description, $page );

			if ( isset( $section['fields'] ) && ! empty( $section['fields'] ) ) {
				$this->add_settings_fields( $id, $section['fields'], $page ); // Add fields to sections.
			}

			$this->debug .= '' === $this->debug ? 'Database option(s) created for this page:<br/>' : '';
			$this->debug .= "Database option: {$id}<br/>";
		}
	}

	/**
	 * Adds all fields to a settings section.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @param string $sections_id  ID of section to add fields to.
	 * @param array  $fields       Array with section fields
	 * @param string $page_id      Page id.
	 * @param bool   $use_defaults Use default values for the settings fields.
	 */
	private function add_settings_fields( $sections_id, $fields, $page_id ) {

		$opt_defaults = array();
		$defaults = array(
			'section' => $sections_id,
			'id' => '',
			'type' => '',
			'label' => '',
			'description' => '',
			'size' => false,
			'options' => '',
			'default' => '',
			'content' => '',
			'attr' => false,
			'before' => '',
			'after' => '',
			'_type' => '',
		);

		// Check if database option exist (use defaults if it doesn't).
		$use_defaults = ( false === get_option( $sections_id ) ) ? true : false;

		foreach ( $fields as $field ) {
			// Field (rows) can be added by external scripts.
			$multiple = isset( $field['fields'] ) && $field['fields'] ? true : false;
			$options  = $multiple ? (array) $field['fields'] : array( $field );

			foreach ( $options as $key => $opt ) {
				$args = wp_parse_args( $opt, $defaults );

				$args['default'] = $use_defaults ? $args['default'] : '';

				$opt_defaults[ $opt['id'] ] = $args['default'];

				if ( in_array( $args['type'], $this->label_for ) ) {
					$args['label_for'] = "{$sections_id}_{$args['id']}";
				}

				if ( key_exists( $args['type'], $this->field_scripts ) ) {
					$this->load_scripts[ $field['id'] ] = $this->field_scripts[ $args['type'] ];
				}

				if ( isset( $args['attr']['data-load-script'] ) ) {
					$this->load_scripts[ $field['id'] ] = $args['attr']['data-load-script'];
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

			// Ability to add fields with an action hook.
			if ( ! method_exists( $this->fields, 'callback_' . $field['type'] ) ) {
				$args['callback'] = $field['type'];
				$args['page_hook'] = $this->opts;
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
		}

		// add the option or validation errors show twice on the first submit (todo: Why?).
		if ( $use_defaults ) {
			add_option( $sections_id, $opt_defaults );
		}
	}

	/**
	 * Registers settings
	 *
	 * @since 2.0.0
	 * @access protected
	 *
	 * @return void
	 */
	protected function register_settings() {

		foreach ( $this->pages as $page ) {
			foreach ( $page['sections'] as $section ) {
				if ( isset( $page['multiform'] ) && $page['multiform'] ) {
					$page['id'] = count( $page['sections'] ) > 1 ? $section['id'] : $page['id'];
				}

				$group = "{$this->opts}_{$page['id']}";
				$option = "{$this->opts}_{$section['id']}";

				if ( isset( $section['validate_callback'] ) && $section['validate_callback'] ) {
					register_setting( $group, $option, $section['validate_callback'] );
				} else {
					register_setting( $group, $option );
				}
			}
		}
	}

	/**
	 * Gets all settings from all sections
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
			return get_option( "{$this->opts}_{$section}" );
		}

		foreach ( ( array ) $this->pages as $page ) {
			if ( ! isset( $page['sections'] ) ) {
				continue;
			}

			foreach ( $page['sections'] as $section ) {
				if ( ! isset( $section['id'] ) ) {
					continue;
				}

				$option = get_option( "{$this->opts}_{$section['id']}" );

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

		// Set the first settings page as current if it's not a tab.
		if ( empty( $current_page ) ) {
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
	 * @param array $page Page array.
	 * @return array Admin pages array with the page added.
	 */
	public function add_page( $page ) {
		return $this->pages[] = $page;
	}

	/**
	 * Adds multiple admin pages.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @param array $pages Array with pages.
	 * @return array Admin pages array with the pages added.
	 */
	public function add_pages( $pages ) {
		foreach ( $pages as $page ) {
			$this->add_page( $page );
		}
		return $this->pages;
	}

	/**
	 * Adds a section to an admin page.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @param string $page    Page id.
	 * @param array  $section Section array.
	 * @return array Admin pages array with the section added.
	 */
	public function add_section( $page, $section ) {

		foreach ( $this->pages as $key => $_page ) {
			if ( $page !== $_page['id'] ) {
				continue;
			}

			if ( isset( $this->pages[ $key ][ $page ]['sections'] ) ) {
				$this->pages[ $key ]['sections'] = array();
			}

			$this->pages[ $key ]['sections'][] = $section;
		}

		return $this->pages;
	}

	/**
	 * Adds multiple sections to an admin page.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @param array $string   Page id
	 * @param array $sections Array with sections.
	 * @return array Admin pages array with the sections added.
	 */
	public function add_sections( $page, $sections ) {
		foreach ( $sections as $section ) {
			$this->pages = $this->add_section( $page, $section );
		}
		return $this->pages;
	}

	/**
	 * Adds a form field to a section.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @param string $page    Page id.
	 * @param string $section Section id.
	 * @param array  $field   Field array.
	 * @return array Admin pages array with the field added.
	 */
	public function add_field( $page, $section, $field ) {

		foreach ( $this->pages as $key => $_page ) {
			if ( $page !== $_page['id'] ) {
				continue;
			}

			if ( ! isset( $this->pages[ $key ]['sections'] ) ) {
				continue;
			}

			$_sections = $this->pages[ $key ]['sections'];

			foreach ( $_sections as $_key => $_section ) {
				if ( $section !== $_section['id'] ) {
					continue;
				}

				if ( ! isset( $this->pages[ $key ]['sections'][ $_key ]['fields'] ) ) {
					$this->pages[ $key ]['sections'][ $_key ]['fields'] = array();
				}

				$this->pages[ $key ]['sections'][ $_key ]['fields'][] = $field;
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
		foreach ( $fields as $field ) {
			$this->pages = $this->add_field( $page, $section, $field );
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
			do_action( "{$this->opts}_admin_enqueue_scripts", $this->load_scripts );
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
			do_action( "{$this->opts}_admin_enqueue_styles", $this->load_styles );
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
			if ( $this->opts . '_' . $setting['id'] === $section['id'] ) {
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
	 * @param string $plugin_title Plugin title.
	 * @param string $tab_id       Page id. Manually set the active tab.
	 * @return void
	 */
	public function render_header( array $args, $tab_id = false ) {

		$title = isset( $args['title'] ) && true === $args['title'] ? esc_html( get_admin_page_title() ) : '';
		$title = $title ?  "<h1>{$title}</h1>" : $title;

		echo $title;

		$page_title_count = 0;

		foreach ( $this->pages as $page ) {
			if ( isset( $page['title'] ) && $page['title'] ) {
				++$page_title_count;
			}
		}

		$html = '';

		$current = $this->current_page;
		$page_ids = wp_list_pluck( $this->pages, 'id' );
		$cur_tab_id = $tab_id ? (string) $tab_id : $current['id'];
		$cur_tab_id = in_array( $cur_tab_id, $page_ids ) ? $cur_tab_id : $current['id'];
		$i = 0;

		foreach ( $this->pages as $page ) {
			if ( ( isset( $page['title'] ) && $page['title'] ) ) {
				if ( $page_title_count > 1 ) {
					$html .= ( 0 === $i ) ? '<nav class="nav-tab-wrapper">' : '';

					$active = '';
					if ( $cur_tab_id === $page['id'] ) {
						$active = ' nav-tab-active';
					}

					// Get the url of the current settings page.
					$tab_url = remove_query_arg( array( 'tab', 'settings-updated' ) );

					// Add query arg 'tab' if it's not the first settings page.
					if ( $this->pages[0]['id'] !== $page['id'] ) {
						$tab_url = add_query_arg( 'tab', $page['slug'], $tab_url );
					}

					$html .= sprintf(
						'<a href="%1$s" class="nav-tab%2$s" id="%3$s-tab">%4$s</a>',
						esc_url( $tab_url ),
						$active,
						esc_attr( $page['id'] ),
						$page['title']
					);

					$html .= ( ++$i === $page_title_count ) ? '</nav>' : '';
				}

				if ( $page_title_count === 1 ) {
					if ( isset( $current['title'] ) && $current['title'] === $page['title'] ) {
						$html .= '<h2>' . $page['title'] . '</h2>';
						break;
					}
				}
			}
		}

		echo $html;
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
			$ids   = wp_list_pluck( $page['sections'], 'id' );
			$forms = $this->multiform ? $page['sections'] : array( $page );

			foreach ( $forms as $form ) {
				echo '<form method="post" action="options.php">';

				$title = isset( $page['title'] ) && ! empty( $page['title'] ) ? esc_html__( $page['title'] ) : '';
				$title = $title ? "<h1 class='screen-reader-text'>{$title}</h1>" : '';

				echo $title;

				// Add additional fields.
				echo apply_filters( "{$this->opts}_form_fields", '', $form['id'], $form );

				settings_fields( $this->opts . '_' . $form['id'] );
				do_settings_sections( $this->opts . '_' . $form['id'] );

				$submit = ( isset( $form['submit'] ) && $form['submit'] ) ? $form['submit'] : '';

				if ( ( '' === $submit ) && isset( $page['submit'] ) && $page['submit'] ) {
					$submit = $page['submit'];
				}

				$text = isset( $submit['text'] )  ? $submit['text'] : null;
				$type = isset( $submit['$type'] ) ? $submit['text'] : 'primary';
				$name = isset( $submit['$name'] ) ? $submit['name'] : 'submit';
				$other_attributes = array( 'id' => $form['id'] );

				submit_button( $text, $type, $name, true, $other_attributes );
				echo '</form>';
			}
		}
	}

	public function install() {
		$install = new Install( $this->pages, $this->opts );
	}
}
