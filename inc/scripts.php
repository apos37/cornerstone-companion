<?php
/**
 * General scripts and styles
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
new Scripts();


/**
 * The class
 */
class Scripts {

	/**
	 * Constructor
	 */
	public function __construct() {

		// Enqueue
        add_action( 'wp_enqueue_scripts', [ $this, 'previewer' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'backend' ] );
        // add_action( 'cs_before_preview_frame', [ $this, 'themes' ] );
        // add_action( 'cs_preview_frame_load', [ $this, 'themes' ] ); // TODO:

    } // End __construct()


    /**
     * Enqueue previewer scripts and styles
     */
    public function previewer() {
        wp_enqueue_style( 'cscompanion-previewer-css', CSCOMPANION_CSS_URL . 'previewer.css', [], CSCOMPANION_VERSION );
    } // End previewer()


	/**
     * Enqueue backend scripts and styles
     */
    public function backend() {
        wp_enqueue_style( 'cscompanion-back-css', CSCOMPANION_CSS_URL . 'back.css', [], CSCOMPANION_VERSION );
    } // End backend()


    /**
     * Enqueue previewer themes
     */
    public function themes() {
        wp_enqueue_style( 'cscompanion-themes-css', CSCOMPANION_CSS_URL . 'themes.css', [], CSCOMPANION_VERSION );
    } // End themes()

}