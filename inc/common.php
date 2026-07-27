<?php
/**
 * Version check, activation, deactivation, uninstallation, etc.
 */

namespace PluginRx\CornerstoneCompanion;

if ( ! defined( 'ABSPATH' ) ) exit;

class Common {


    /**
     * Constructor
     */
    public function __construct() {

        // PHP Version check
		$this->check_php_version();

		// Cornerstone check
		$this->check_cornerstone_dependency();

		// Check for updates
		// add_action( 'plugins_loaded', [ $this, 'check_for_updates' ] );
        
    } // End __construct()


	/**
	 * Prevent loading the plugin if PHP version is not minimum
	 *
	 * @return void
	 */
	public function check_php_version() {
		if ( version_compare( PHP_VERSION, CSCOMPANION_MIN_PHP_VERSION, '<=' ) ) {
			add_action( 'admin_init', function() {
				deactivate_plugins( CSCOMPANION_BASENAME );
			} );
			add_action( 'admin_notices', function() {
				/* translators: 1: Plugin name, 2: Required PHP version */
				$notice = sprintf( __( '%1$s requires PHP %2$s or newer.', 'cornerstone-companion' ),
					CSCOMPANION_NAME,
					CSCOMPANION_MIN_PHP_VERSION
				);
				echo wp_kses_post(
					'<div class="notice notice-error"><p>' . esc_html( $notice ) . '</p></div>'
				);
			} );
			return;
		}
	} // End check_php_version()


	/**
	 * Prevent loading the plugin if Cornerstone is not active
	 *
	 * @return void
	 */
	public function check_cornerstone_dependency() {
		if ( !function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$has_plugin = is_plugin_active( 'cornerstone/cornerstone.php' );
		$theme      = wp_get_theme();
		$parent     = $theme->parent();

		$has_pro_theme = false;

		$check_theme = $parent ?: $theme;
		$name        = strtolower( $check_theme->get( 'Name' ) );
		$template    = strtolower( $check_theme->get( 'Template' ) );
		$author      = $check_theme->get( 'Author' );
		$author_uri  = $check_theme->get( 'AuthorURI' );

		if (
			( $name === 'pro' || $template === 'pro' ) &&
			(
				stripos( $author, 'Themeco' ) !== false ||
				stripos( $author_uri, 'theme.co' ) !== false
			)
		) {
			$has_pro_theme = true;
		}

		if ( !$has_plugin && !$has_pro_theme ) {
			add_action( 'admin_init', function() {
				deactivate_plugins( CSCOMPANION_BASENAME );
			} );
			add_action( 'admin_notices', function() {
				// Translators: %1$s is the name of the Cornerstone Companion plugin.
				$notice = sprintf( __( '%1$s requires the Cornerstone plugin or Pro theme to be active.', 'cornerstone-companion' ),
					CSCOMPANION_NAME
				);
				echo wp_kses_post(
					'<div class="notice notice-error"><p>' . esc_html( $notice ) . '</p></div>'
				);
			} );
		}
	} // End check_cornerstone_dependency()


	/**
     * Check for plugin updates
     */
    public function check_for_updates() : void {
        require_once __DIR__ . '/inc/updater.php';

        $args = [
            'name'            => cscompanion_plugin_data( 'name' ),
            'text_domain'     => cscompanion_plugin_data( 'textdomain' ),
            'basename'        => cscompanion_plugin_data( 'basename' ),
            'version'         => cscompanion_plugin_data( 'version' ),
            'author_uri'      => cscompanion_plugin_data( 'author_uri' ),
            'plugin_uri'      => cscompanion_plugin_data( 'plugin_uri' ),
            'prefix'          => 'css_organizer',
        ];
        new Updater( $args );
    } // End check_for_updates()

}


new Common();