<?php
/**
 * Uninstall handler for Cornerstone Companion
 *
 * Deletes all plugin options
 */

// Exit if not called by WP uninstall routine
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

/**
 * Runs all uninstall cleanup logic in an isolated scope.
 */
function cscompanion_run_uninstall() {

    /**
     * Check the cleanup setting before proceeding.
     */
    $cscompanion_cleanup_key = 'cscompanion_uninstall_cleanup';
    if ( ! (bool) get_option( $cscompanion_cleanup_key, false ) ) {
        return;
    }

    global $wpdb;

    $cscompanion_opt_prefix = 'cscompanion_';

    /**
     * Clean up Options
     */
    $cscompanion_options = $wpdb->get_col( $wpdb->prepare( // phpcs:ignore
        "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
        $wpdb->esc_like( $cscompanion_opt_prefix ) . '%'
    ) );

    if ( ! empty( $cscompanion_options ) ) {
        foreach ( $cscompanion_options as $cscompanion_option_name ) {
            delete_option( $cscompanion_option_name );
        }
    }
}

cscompanion_run_uninstall();

// Finished.