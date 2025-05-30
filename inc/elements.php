<?php
/**
 * Register elements
 */


/**
 * Define Namespaces
 */
namespace Apos37\CornerstoneCompanion;


/**
 * Exit if accessed directly.
 */
if ( !defined( 'ABSPATH' ) ) exit;


/**
 * Instantiate the class
 */
new Elements();


/**
 * The class
 */
class Elements {

    /**
     * Constructor
     */
    public function __construct() {

        // Register the elements
		add_action( 'cs_register_elements', [ $this, 'register' ] );
        
    } // End __construct()


	/**
	 * Register the elements
	 *
	 * @return void
	 */
	public function register() {

		// File
		require_once CSCOMPANION_ELEMENTS_ABSPATH . 'file.php';

	} // End register()

}