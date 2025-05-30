<?php
/**
 * Settings
 */


/**
 * Define Namespaces
 */
namespace Apos37\CornerstoneCompanion;
use Apos37\CornerstoneCompanion\EditLock;


/**
 * Exit if accessed directly.
 */
if ( !defined( 'ABSPATH' ) ) exit;


/**
 * Instantiate the class
 */
add_action( 'init', function() {
	(new Settings())->init();
} );


/**
 * The class
 */
class Settings {

	/**
	 * The options group
	 *
	 * @var string
	 */
	private $group = CSCOMPANION_TEXTDOMAIN . '-settings';


	/**
	 * Load on init only
	 */
	public function init() {

		// Settings page
        add_action( 'admin_menu', [ $this, 'submenu' ] );

		// Register the options
		add_action( 'admin_init', [  $this, 'register' ] );

		// Scripts
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		
    } // End init()


	/**
     * Add to menu
     */
    public function submenu() {
        add_submenu_page(
            'cornerstone-launch-editor',
            CSCOMPANION_NAME,
			__( 'Companion', 'cornerstone-companion' ),
            'manage_options',
            CSCOMPANION_TEXTDOMAIN,
            [ $this, 'page' ],
            null
        );
    } // End submenu()

    
    /**
     * Settings page
     */
    public function page() {
        global $current_screen;
        if ( $current_screen->id != CSCOMPANION_SETTINGS_SCREEN_ID ) {
            return;
        }
        ?>
		<div class="wrap">
			<form method="post" action="options.php">
				<?php settings_fields( $this->group ); ?>
				<div class="cscompanion-settings-wrapper">
					<div class="cscompanion-box-sections">
						<?php $this->sections(); ?>
					</div>
					<div class="cscompanion-sidebar">
						<div class="cscompanion-box-row">
							<div class="cscompanion-box-column">
								<header class="cscompanion-box-header"><h2><?php echo esc_html__( 'Save Settings', 'cornerstone-companion' ); ?></h2></header>
								<div class="cscompanion-box-content">
									<p><?php echo esc_html__( 'Once you are satisfied with your settings, click the button below to save them.', 'cornerstone-companion' ); ?></p>
									<?php submit_button( __( 'Update', 'cornerstone-companion' ) ); ?>
								</div>
							</div>
						</div>
					</div>
				</div>
			</form>
		</div>
        <?php
    } // End page()


	/**
	 * Get the settings sections
	 */
	public function sections() {
		$sections = [
			'general'   => __( 'General', 'cornerstone-companion' ),
			'edit_lock' => __( 'Edit Lock', 'cornerstone-companion' ),
		];

		// Iter the sections
        foreach ( $sections as $key => $title ) {
			?>
			<div class="cscompanion-box-row">
				<div class="cscompanion-box-column">
					<header class="cscompanion-box-header"><h2><?php echo esc_html( $title ); ?></h2></header>
					<?php $this->fields( $key ); ?>
				</div>
			</div>
			<?php
        }
	} // End sections()


	/**
	 * The options to register
	 *
	 * @return array
	 */
	public function options() {
		return [
			[ 
				'section'   => 'general',
				'type'      => 'checkbox',
				'sanitize'  => [ $this, 'sanitize_checkbox' ],
                'key'       => 'cscompanion_enable_cs_state',
                'title'     => __( 'Add CS Logo to Posts and Pages', 'cornerstone-companion' ),
				'desc'      => __( 'Adds the Cornerstone logo next to post and page titles on the admin list screens indicating that they are edited in Cornerstone.', 'cornerstone-companion' ),
				'default' 	=> true,
            ],
			[ 
				'section'   => 'edit_lock',
				'type'      => 'checkbox',
				'sanitize'  => [ $this, 'sanitize_checkbox' ],
                'key'       => 'cscompanion_enable_edit_lock',
                'title'     => __( 'Enable Edit Lock', 'cornerstone-companion' ),
				'desc'      => __( 'Prevents multiple users from editing the same post in Cornerstone at the same time by locking access to the editor.', 'cornerstone-companion' ),
				'default' 	=> true,
            ],
			[ 
				'section'   => 'edit_lock',
				'type'      => 'checkbox',
				'sanitize'  => [ $this, 'sanitize_checkbox' ],
                'key'       => 'cscompanion_edit_lock_save_exit',
                'title'     => __( 'Save Before Forcing User to Exit', 'cornerstone-companion' ),
				'desc'      => __( 'We recommend keeping this disabled to avoid publishing incomplete changes that visitors might see. Instead, it\'s best to save your progress regularly after each edit and communicate with other team members about when you are editing.', 'cornerstone-companion' ),
				'default' 	=> false,
				'conditions' => [ 'cscompanion_enable_edit_lock' ]
            ],
			[ 
				'section'   => 'edit_lock',
				'type'      => 'checkbox',
				'sanitize'  => [ $this, 'sanitize_checkbox' ],
                'key'       => 'cscompanion_edit_lock_enable_autoboot',
                'title'     => __( 'Enable Auto Boot', 'cornerstone-companion' ),
				'desc'      => __( 'Automatically exits inactive users from the editor after a set period of no changes. A warning will appear first, giving the user a chance to stay. If there\'s no response or they choose to exit, they\'ll be redirected to the previous page.', 'cornerstone-companion' ),
				'default' 	=> true,
				'conditions' => [ 'cscompanion_enable_edit_lock' ]
            ],
			[
				'section'   => 'edit_lock',
				'type'      => 'number',
				'sanitize'  => [ $this, 'absint' ],
                'key'       => 'cscompanion_edit_lock_autoboot_time',
                'title'     => __( 'Auto Boot Time (in minutes)', 'cornerstone-companion' ),
				'desc'      => __( 'Defines how many minutes of inactivity (based on last render of elements or options) are allowed before triggering the warning and auto-exit. Activity in the CSS or JavaScript editors does not count as rendering.', 'cornerstone-companion' ),
				'default' 	=> (new EditLock())->autoboot_time,
				'conditions' => [ 'cscompanion_enable_edit_lock', 'cscompanion_edit_lock_enable_autoboot' ]
            ],
			[ 
				'section'   => 'edit_lock',
				'type'      => 'text',
				'sanitize'  => [ $this, 'sanitize_text_field' ],
                'key'       => 'cscompanion_edit_lock_autoboot_msg',
                'title'     => __( 'Auto Boot Message', 'cornerstone-companion' ),
				'desc'      => __( 'This message appears when the user has been inactive in the Cornerstone editor. It will offer options to continue editing or exit.', 'cornerstone-companion' ),
				'width'     => '100%',
				'default' 	=> (new EditLock())->autoboot_msg,
				'conditions' => [ 'cscompanion_enable_edit_lock', 'cscompanion_edit_lock_enable_autoboot' ]
            ],
		];
	} // End options()


	/**
	 * Register the options
	 *
	 * @return array
	 */
	public function register() {
		$options = $this->options();
		foreach ( $options as $option ) {
			register_setting( $this->group, $option[ 'key' ], $option[ 'sanitize' ] );
		}
	} // End register()


	/**
	 * Get the setting fields
	 *
	 * @param string $section
	 */
	public function fields( $section ) {
		$options = $this->options();

		foreach ( $options as $option ) {
			if ( $option[ 'section' ] !== $section ) {
				continue;
			}

			// Determine visibility based on conditions
			$not_applicable = false;
			if ( isset( $option[ 'conditions' ] ) && is_array( $option[ 'conditions' ] ) ) {
				foreach ( $option[ 'conditions' ] as $condition_key ) {
					$condition_option = array_filter( $options, fn( $opt ) => $opt[ 'key' ] === $condition_key );
					$condition = reset( $condition_option );
					$val = sanitize_text_field( get_option( $condition_key, $condition[ 'default' ] ?? '' ) );
					if ( !filter_var( $val, FILTER_VALIDATE_BOOLEAN ) ) {
						$not_applicable = true;
						break;
					}
				}
			}

			$classes = 'cscompanion-box-content has-fields';
			if ( $not_applicable ) {
				$classes .= ' not-applicable';
			}
			?>
			<div class="<?php echo esc_attr( $classes ); ?>">
				<div class="cscompanion-box-left">
					<label for="<?php echo esc_html( $option[ 'key' ] ); ?>"><?php echo esc_html( $option[ 'title' ] ); ?></label>
					<?php if ( isset( $option[ 'desc' ] ) ) { ?>
						<p class="cscompanion-box-desc"><?php echo esc_html( $option[ 'desc' ] ); ?></p>
					<?php } ?>
				</div>
				
				<div class="cscompanion-box-right">
					<?php
					$add_field = 'settings_field_' . $option[ 'type' ];
					$this->$add_field( $option );
					?>
				</div>
			</div>
			<?php
		}
	} // End fields()
    
    
    /**
     * Custom callback function to print text field
     *
     * @param array $args
     */
    public function settings_field_text( $args ) {
        $width = isset( $args[ 'width' ] ) ? $args[ 'width' ] : '30rem';
        $value = sanitize_text_field( get_option( $args[ 'key' ], $args[ 'default' ] ) );
        printf(
			/* translators: %1$s is the input ID and name attribute, %2$s is the input value, %3$s is the CSS width */
            '<input type="text" id="%1$s" name="%1$s" value="%2$s" style="width: %3$s; max-width: %3$s" />',
            esc_attr( $args[ 'key' ] ),
            esc_html( $value ),
			esc_html( $width )
        );
    } // settings_field_text()


	/**
     * Custom callback function to print number field
     *
     * @param array $args
     */
    public function settings_field_number( $args ) {
		$width = isset( $args[ 'width' ] ) ? $args[ 'width' ] : '10rem';
		$value = absint( get_option( $args[ 'key' ], $args[ 'default' ] ) );
		printf(
			/* translators: %1$s is the input ID and name; %2$d is the numeric value */
			'<input type="number" id="%1$s" name="%1$s" value="%2$d" style="width: %3$s; max-width: %3$s" />',
			esc_attr( $args[ 'key' ] ),
			esc_attr( $value ),
			esc_html( $width )
		);
	} // End settings_field_number()


    // /**
    //  * Custom callback function to print select field
    //  *
    //  * @param array $args
    //  * @return void
    //  */
    // public function settings_field_select( $args ) {
	// 	$default = isset( $args[ 'default' ] ) ? $args[ 'default' ] : '';
	// 	$value   = sanitize_text_field( get_option( $args[ 'key' ], $default ) );

	// 	if ( isset( $args[ 'revert' ] ) && $args[ 'revert' ] === true && trim( $value ) === '' ) {
	// 		$value = $default;
	// 	}

	// 	printf(
	// 		/* translators: %1$s is the select element ID and name attribute */
	// 		'<select id="%1$s" name="%1$s">',
	// 		esc_attr( $args[ 'key' ] )
	// 	);

	// 	if ( isset( $args[ 'options' ] ) ) {
	// 		foreach ( $args[ 'options' ] as $key => $option ) {
	// 			printf(
	// 				/* translators: %1$s is the option value, %2$s is the selected attribute if matched, %3$s is the option label */
	// 				'<option value="%1$s"%2$s>%3$s</option>',
	// 				esc_attr( $key ),
	// 				selected( $key, $value, false ),
	// 				esc_html( $option )
	// 			);
	// 		}
	// 	}

	// 	echo '</select>';
	// } // settings_field_select()


    // /**
    //  * Custom callback function to print textarea field
    //  *
    //  * @param array $args
    //  * @return void
    //  */
    // public function settings_field_textarea( $args ) {
	// 	$value = sanitize_textarea_field( get_option( $args[ 'key' ], $args[ 'default' ] ) );
	// 	printf(
	// 		/* translators: %1$s is the textarea ID and name attributes; %2$s is the escaped textarea content */
	// 		'<textarea id="%1$s" name="%1$s">%2$s</textarea>',
	// 		esc_attr( $args[ 'key' ] ),
	// 		esc_html( $value )
	// 	);
	// } // End settings_field_textarea()


	/**
     * Custom callback function to print checkbox field
     *
     * @param array $args
     */
    public function settings_field_checkbox( $args ) {
		$value = filter_var( get_option( $args[ 'key' ], $args[ 'default' ] ), FILTER_VALIDATE_BOOLEAN );
		$id    = esc_attr( $args[ 'key' ] );
		$label = $value ? __( 'On', 'cornerstone-companion' ) : __( 'Off', 'cornerstone-companion' );

		printf(
			'<label class="cscompanion-toggle">
				<input type="checkbox" id="%1$s" name="%1$s"%2$s />
				<span>
					<svg version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24">
						<path fill="none" stroke-width="2" stroke-linecap="square" stroke-miterlimit="10"
							d="M17,4.3c3,1.7,5,5,5,8.7 c0,5.5-4.5,10-10,10S2,18.5,2,13c0-3.7,2-6.9,5-8.7"
							stroke-linejoin="miter"></path>
						<line fill="none" stroke-width="2" stroke-linecap="square" stroke-miterlimit="10"
							x1="12" y1="1" x2="12" y2="8" stroke-linejoin="miter"></line>
					</svg>
					<span class="label">%3$s</span>
				</span>
			</label>',
			esc_attr( $id ),
			checked( $value, 1, false ),
			esc_html( $label )
		);
	} // End settings_field_checkbox()

    
    /**
     * Sanitize checkbox
     *
     * @param int $value
     * @return boolean
     */
    public function sanitize_checkbox( $value ) {
        return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
    } // End sanitize_checkbox()


	/**
     * Enqueue javascript
     *
     * @return void
     */
    public function enqueue_scripts( $hook ) {
        // Check if we are on the correct admin page
        if ( $hook !== CSCOMPANION_SETTINGS_SCREEN_ID ) {
            return;
        }

		// Get the options
		$options_with_conditions = array_values( array_filter( $this->options(), function( $option ) {
			return isset( $option[ 'conditions' ] );
		} ) );

		// JS
		$handle = 'cscompanion_settings';
		wp_enqueue_script( $handle, CSCOMPANION_JS_URL . 'settings.js', [ 'jquery' ], CSCOMPANION_VERSION, true );
		wp_localize_script( $handle, $handle, [
			'on'      => __( 'On', 'cornerstone-companion' ),
			'off'     => __( 'Off', 'cornerstone-companion' ),
			'options' => array_map( function( $option ) {
				return [
					'key'        => $option[ 'key' ],
					'conditions' => $option[ 'conditions' ],
				];
			}, $options_with_conditions ),
		] );

		// CSS
		wp_enqueue_style( CSCOMPANION_TEXTDOMAIN . '-styles', CSCOMPANION_CSS_URL . 'settings.css', [], CSCOMPANION_VERSION );
    } // End enqueue_scripts()

}