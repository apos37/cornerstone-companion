<?php
/**
 * Plugin Name:         Cornerstone Companion
 * Plugin URI:          https://github.com/apos37/cornerstone-companion
 * Description:         Enhance and extend the functionality of the Cornerstone website builder by Theme Co.
 * Version:             1.0.1
 * Requires at least:   5.9
 * Tested up to:        6.8
 * Requires PHP:        7.4
 * Author:              PluginRx
 * Author URI:          https://pluginrx.com/
 * Support URI:         https://discord.gg/3HnzNEJVnR
 * Text Domain:         cornerstone-companion
 * License:             GPLv2 or later
 * License URI:         http://www.gnu.org/licenses/gpl-2.0.txt
 * Created on:          May 15, 2025
 */


/**
 * Define Namespace
 */
namespace Apos37\CornerstoneCompanion;


/**
 * Exit if accessed directly.
 */
if ( !defined( 'ABSPATH' ) ) exit;


/**
 * Defines
 */
$plugin_data = get_file_data( __FILE__, [
    'name'         => 'Plugin Name',
    'version'      => 'Version',
    'requires_php' => 'Requires PHP',
    'textdomain'   => 'Text Domain',
    'support_uri'  => 'Support URI',
] );

// Versions
define( 'CSCOMPANION_VERSION', time() ); // $plugin_data[ 'version' ]
define( 'CSCOMPANION_MIN_PHP_VERSION', $plugin_data[ 'requires_php' ] );

// Names
define( 'CSCOMPANION_NAME', $plugin_data[ 'name' ] );
define( 'CSCOMPANION_BASENAME', plugin_basename( __FILE__ ) );
define( 'CSCOMPANION_TEXTDOMAIN', $plugin_data[ 'textdomain' ] );
define( 'CSCOMPANION__TEXTDOMAIN', str_replace( '-', '_', CSCOMPANION_TEXTDOMAIN ) );
define( 'CSCOMPANION_DISCORD_SUPPORT_URL', $plugin_data[ 'support_uri' ] );

// Paths
define( 'CSCOMPANION_INCLUDES_ABSPATH', plugin_dir_path( __FILE__ ) . 'inc/' );
define( 'CSCOMPANION_ELEMENTS_ABSPATH', plugin_dir_path( __FILE__ ) . 'inc/elements/' );
define( 'CSCOMPANION_CSS_URL', plugin_dir_url( __FILE__ ) . 'inc/css/' );
define( 'CSCOMPANION_JS_URL', plugin_dir_url( __FILE__ ) . 'inc/js/' );

// Screen IDs
define( 'CSCOMPANION_SETTINGS_SCREEN_ID', 'cornerstone_page_' . CSCOMPANION_TEXTDOMAIN );


/**
 * Includes
 */
require_once CSCOMPANION_INCLUDES_ABSPATH . 'common.php';
require_once CSCOMPANION_INCLUDES_ABSPATH . 'helpers.php';
require_once CSCOMPANION_INCLUDES_ABSPATH . 'scripts.php';
require_once CSCOMPANION_INCLUDES_ABSPATH . 'misc.php';
require_once CSCOMPANION_INCLUDES_ABSPATH . 'elements.php';
require_once CSCOMPANION_INCLUDES_ABSPATH . 'themes.php';
require_once CSCOMPANION_INCLUDES_ABSPATH . 'edit-lock.php';
require_once CSCOMPANION_INCLUDES_ABSPATH . 'settings.php';
