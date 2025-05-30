<?php
/**
 * Helpers
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
new Helpers();


/**
 * The class
 */
class Helpers {

    /**
     * Check if a post is edited in cornerstone
     *
     * @param int $post_id
     * @return boolean
     */
    public function is_edited_in_cornerstone( $post_id ) {
        $data = get_post_meta( $post_id, '_cornerstone_data', true );
        if ( $data && $data != '' && $data != [] ) {
            return true;
        }
        return false;
    } // End is_edited_in_cornerstone()


    /**
     * Check if we are currently on the previewer/editor
     *
     * @return boolean
     */
    public function is_preview() {
        // Check using their action (which doesn't always fire apparently)
        $is_cs_element_rendering = did_action( 'cs_element_rendering' );
        if ( $is_cs_element_rendering ) {
            return true;
        }

        // Check the url
        $request_uri = isset( $_SERVER[ 'HTTP_REFERER' ] ) ? sanitize_text_field( wp_unslash( $_SERVER[ 'HTTP_REFERER' ] ) ) : '';
        $site_path = wp_parse_url( site_url(), PHP_URL_PATH );
        $relative_uri = $site_path ? str_replace( $site_path, '', $request_uri ) : $request_uri;

        // Match /cornerstone/edit/{post_id}
        if ( strpos( $relative_uri, '/cornerstone/edit/' ) !== false ) {
            return true;
        }

        // Check if cs_preview_time exists in request (GET or POST)
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( isset( $_REQUEST[ 'cs_preview_time' ] ) ) { 
            return true;
        }

        return false;
    } // End is_preview()

}