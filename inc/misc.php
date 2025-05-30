<?php
/**
 * Miscellaneous
 */


/**
 * Define Namespaces
 */
namespace Apos37\CornerstoneCompanion;
use Apos37\CornerstoneCompanion\Helpers;


/**
 * Exit if accessed directly.
 */
if ( !defined( 'ABSPATH' ) ) exit;


/**
 * Instantiate the class
 */
new Misc();


/**
 * The class
 */
class Misc {

    /**
     * Instantiate
     */
    private $HELPERS;


    /**
     * Constructor
     */
    public function __construct() {

        // Instantiate
        $this->HELPERS = new Helpers();

        // TODO: Disable cache while editing, comment out before release
		// add_filter( 'cs_disable_style_cache', '__return_true' );
        // add_filter( 'cs_debug_css', '__return_true' );

        // Add CS logo next to post/page as a post state if edited in Cornerstone
        if ( filter_var( get_option( 'cscompanion_enable_cs_state', true ), FILTER_VALIDATE_BOOLEAN ) ) {
            add_filter( 'display_post_states', [ $this, 'post_state' ], 10, 2 );
        }

        
        
    } // End __construct()


    /**
     * Add CS logo next to post/page as a post state if edited in Cornerstone
     *
     * @param array $post_states
     * @param object $post
     * @return array
     */
    public function post_state( $post_states, $post ) {
        if ( $this->HELPERS->is_edited_in_cornerstone( $post->ID ) ) {
            $post_states[] = '<span class="cornerstone-icon" aria-hidden="true"></span>';
        }
        return $post_states;
    } // End post_state()


    

}