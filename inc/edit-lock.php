<?php
/**
 * Lock the post/page for editing if another user is editing in cornerstone
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
add_action( 'init', function() {
	(new EditLock())->init();
} );


/**
 * The class
 */
class EditLock {

	/**
	 * The option keys
	 *
	 * @var string
	 */
	private $option_key_editing = 'cscompanion_edit_lock_all';
	private $option_key_since = 'cscompanion_edit_lock_since';
	private $option_key_takeover = 'cscompanion_edit_lock_takeover';


	/**
	 * Nonce
	 *
	 * @var string
	 */
	private $nonce = 'cscompanion_nonce';


	/**
	 * Ajax key
	 *
	 * @var string
	 */
	private $ajax_key_previewer = 'cscompanion_edit_lock_previewer';
	private $ajax_key_since = 'cscompanion_edit_lock_since';
	private $ajax_key_takeover = 'cscompanion_edit_lock_takeover';
	private $ajax_key_exit = 'cscompanion_edit_lock_exit';
	private $ajax_key_close = 'cscompanion_edit_lock_close';
	private $ajax_key_autoboot = 'cscompanion_edit_lock_autoboot';


	/**
	 * How often should it check if the user is still on the page
	 *
	 * @var integer
	 */
	private $interval;


	/**
	 * Lock timeout in seconds
	 *
	 * @var integer
	 */
	private $timeout;


	/**
	 * Auto boot after how many minutes
	 *
	 * @var integer
	 */
	public $autoboot_time;


	/**
	 * The auto boot message
	 *
	 * @var string
	 */
	public $autoboot_msg;


	/**
	 * Exit if no response in seconds
	 *
	 * @var integer
	 */
	public $autoboot_no_response_time;

	
	/**
	 * Constructor
	 */
	public function __construct() {

		// Get settings
		$this->interval = absint( apply_filters( 'cscompanion_edit_lock_interval', 15 ) );
		$this->timeout = absint( apply_filters( 'cscompanion_edit_lock_timeout', 150 ) );
		$this->autoboot_time = absint( get_option( 'cscompanion_edit_lock_autoboot_time', 30 ) );
		$this->autoboot_msg = sanitize_text_field( get_option( 'cscompanion_edit_lock_autoboot_msg', __( 'We will boot you out of the editor if there is no response so that others can edit without issues.', 'cornerstone-companion' ) ) );
		$this->autoboot_no_response_time = absint( apply_filters( 'cscompanion_edit_lock_autoboot_time', 30 ) );
		
	} // End __construct()


    /**
     * Load on init
     */
    public function init() {
		if ( filter_var( get_option( 'cscompanion_enable_edit_lock', true ), FILTER_VALIDATE_BOOLEAN ) ) {

			// Heartbeat
			add_filter( 'heartbeat_received', [ $this, 'heartbeat' ], 10, 3 );

			// Ajax
			add_action( 'wp_ajax_' . $this->ajax_key_previewer, [ $this, 'ajax_previewer' ] );
			add_action( 'wp_ajax_' . $this->ajax_key_since, [ $this, 'ajax_since' ] );
			add_action( 'wp_ajax_' . $this->ajax_key_takeover, [ $this, 'ajax_takeover' ] );
			add_action( 'wp_ajax_' . $this->ajax_key_exit, [ $this, 'ajax_exit' ] );
			add_action( 'wp_ajax_' . $this->ajax_key_close, [ $this, 'ajax_close' ] );

			if ( filter_var( get_option( 'cscompanion_edit_lock_enable_autoboot', true ), FILTER_VALIDATE_BOOLEAN ) ) {
				add_action( 'wp_ajax_' . $this->ajax_key_autoboot, [ $this, 'ajax_autoboot' ] );
			}

			// Enqueue scripts
			add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_previewer' ] );
			add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_heartbeat' ] );
			
		}
    } // End init()


	/**
	 * Check if someone has taken over
	 *
	 * @return int|false User ID of the user who took over, or false if none.
	 */
	public function has_someone_taken_over() {
		$user_id = absint( get_option( $this->option_key_takeover ) );
		return $user_id > 0 ? $user_id : false;
	} // End has_someone_taken_over()


	/**
	 * Check if any Cornerstone editor is globally locked by another user
	 *
	 * @param int $timeout
	 * @return int|false
	 */
	public function check_lock_all_cornerstone_editors( $bypass_checks = false ) {
		$lock = sanitize_text_field( get_option( $this->option_key_editing ) );
		if ( !$lock ) {
			return false;
		}

		list( $time, $user_id, $post_id ) = explode( ':', $lock );

		$time     = absint( $time );
		$user_id  = absint( $user_id );
		$post_id  = absint( $post_id );

		if ( $bypass_checks ) {
			return [
				'user' => $user_id,
				'post' => $post_id
			];
		}

		$now     = time();
		$current = get_current_user_id();

		if ( $user_id && $user_id !== $current && ( $now - $time ) < $this->timeout ) {
			return [
				'user' => $user_id,
				'post' => $post_id
			];
		}

		return false;
	} // End check_lock_all_cornerstone_editors()


	/**
	 * Lock all editors
	 *
	 * @return array|false
	 */
	public function set_lock_all_cornerstone_editors( $post_id ) {
		$user_id = get_current_user_id();
		if ( !$user_id ) {
			return;
		}

		// Check if we have a lock yet before adding one
		$lock_exists = $this->check_lock_all_cornerstone_editors( true );

		$now  = time();

		// Store the heartbeat edit lock details
		$lock = "$now:$user_id:$post_id";
		$locked = update_option( $this->option_key_editing, $lock );

		// Store the start time and the since last rendered time
		$since = $this->get_since();
		if ( empty( $since ) || empty( $lock_exists ) ) {
			$since = "$now:$now";
			update_option( $this->option_key_since, $since );
		}

		return $locked ? [ $now, $user_id ] : false;
	} // End set_lock_all_cornerstone_editors()


	/**
	 * Get the last rendered time
	 *
	 * @return array|false
	 */
	public function get_since() {
		$since = sanitize_text_field( get_option( $this->option_key_since ) );
		if ( !$since ) {
			return false;
		}

		list( $start, $rendered ) = explode( ':', $since );

		return [
			'start'    => absint( $start ),
			'rendered' => absint( $rendered )
		];
	} // End get_since()


	/**
	 * Update the last rendered time
	 *
	 * @return void
	 */
	public function update_last_rendered_time() {
		$since = $this->get_since();
		if ( empty( $since ) ) {
			return false;
		}

		$start = $since[ 'start' ];
		$rendered = time();

		$new_since = "$start:$rendered";
		update_option( $this->option_key_since, $new_since );
	} // End update_last_rendered_time()


	/**
	 * Set the edit lock
	 *
	 * @param int $post_id
	 * @return mixed
	 */
	public function set_lock( $post_id ) {
		// Lock all cornerstone editors
		$lock_all = $this->set_lock_all_cornerstone_editors( $post_id );

		// Load wp_set_post_lock if not available
		if ( !function_exists( 'wp_set_post_lock' ) ) {
			require_once ABSPATH . 'wp-admin/includes/post.php';
		}

		// Lock the individual post
		$lock_post = wp_set_post_lock( $post_id );

		// Return the individual lock
		return [
			'post_id'   => $post_id,
			'lock_all'  => $lock_all,
			'lock_post' => $lock_post
		];
	} // End set_lock()


	/**
	 * Clear the options
	 *
	 * @return void
	 */
	public function clear_options( $also_clear_takeover = false, $also_clear_post = true ) {
		// Delete the options that indicate the original user is editing
		$lock_all = $this->check_lock_all_cornerstone_editors( true );
		if ( $lock_all ) {

			// Delete the global lock and since
			delete_option( $this->option_key_editing );
			delete_option( $this->option_key_since );

			// Get the post id
			$post_id = isset( $lock_all[ 'post' ] ) ? absint( $lock_all[ 'post' ] ) : 0;

			// Remove _edit_lock from the post being edited
			if ( $also_clear_post && $post_id ) {
				delete_post_meta( $post_id, '_edit_lock' );
			}

			// Return the locked post id
			return $post_id;
		}

		// Also clear the takeover
		if ( $also_clear_takeover ) {
			delete_option( $this->option_key_takeover );
		}
		return;
	} // End clear_options()


	/**
	 * Should we save the post before kicking out the other user
	 *
	 * @param int|null $post_id
	 * @param int|null $user_id
	 * @return boolean
	 */
	public function should_save( $post_id = null, $user_id = null ) {
		if ( is_null( $post_id ) ) {
			$post_id = get_the_ID();
		}

		if ( is_null( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		$should_save = filter_var( get_option( 'cscompanion_edit_lock_save_exit', false ), FILTER_VALIDATE_BOOLEAN );

		return apply_filters( 'cscompanion_edit_lock_should_save', $should_save, $post_id, $user_id );
	} // End should_save()


	/**
	 * Check if other users are editing
	 *
	 * @param array $response
	 * @param array $data
	 * @param string $screen
	 * @return array
	 */
	public function heartbeat( $response, $data, $screen ) {
		$lock = $this->check_lock_all_cornerstone_editors();

		if ( $lock ) {
			$user_id = $lock[ 'user' ];
			$user    = get_userdata( $user_id );
			$name    = $user ? esc_html( $user->display_name ) : __( 'Another user', 'cornerstone-companion' );

			$response[ 'cscompanion_lock_notice' ] = sprintf(
				/* translators: %s: display name of the user editing */
				__( '%s is currently editing in Cornerstone.', 'cornerstone-companion' ),
				$name
			);

			$since = $this->get_since();
			if ( $since ) {
				$start_local    = get_date_from_gmt( gmdate( 'Y-m-d H:i:s', $since[ 'start' ] ), 'M jS @ g:ia' );
				$rendered_local = get_date_from_gmt( gmdate( 'Y-m-d H:i:s', $since[ 'rendered' ] ), 'M jS @ g:ia' );

				/* translators: 1: start time, 2: last rendered time */
				$response[ 'cscompanion_lock_notice' ] .= ' ' . sprintf(
					// Translators: 1 = human-readable time the lock started, 2 = human-readable time the lock was last rendered.
					__( '(Started %1$s | Last rendered %2$s)', 'cornerstone-companion' ),
					$start_local,
					$rendered_local
				);
			}
		} else {
			$response[ 'cscompanion_lock_notice' ] = false;

			// Let's also delete the options
			$this->clear_options( true );
		}

		return $response;
	} // End heartbeat()


	/**
	 * Ajax for the Previewer
	 *
	 * @return void
	 */
	public function ajax_previewer() {
		// Verify nonce
		check_ajax_referer( $this->nonce, 'nonce' );

		// Get the post id
		$post_id = isset( $_GET[ 'post_id' ] ) ? absint( $_GET[ 'post_id' ] ) : 0;
		if ( !$post_id ) {
			wp_send_json_error( 'No post ID' );
		}

		// Make sure they have access
		if ( !current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		// Determine lock status
		if ( $user_id = $this->has_someone_taken_over() ) {
			$user = get_userdata( $user_id );
			$name = $user ? $user->display_name : __( 'Another user', 'cornerstone-companion' );
			wp_send_json_success( [
				'taken_over' => true,
				'user_id'    => $user_id,
				'name'       => $name,
			] );
		} else {
			$lock = $this->set_lock( $post_id );
			wp_send_json_success( [
				'taken_over' => false,
				'lock'       => $lock
			] );
		}
	} // End ajax_previewer()


	/**
	 * Ajax for the Since
	 *
	 * @return void
	 */
	public function ajax_since() {
		check_ajax_referer( $this->nonce, 'nonce' );

		// Update the last rendered time
		$this->update_last_rendered_time();

		wp_send_json_success();
	} // End ajax_since()


	/**
	 * Ajax for the Take Over
	 *
	 * @return void
	 */
	public function ajax_takeover() {
		check_ajax_referer( $this->nonce, 'nonce' );

		// Update the takeover with the current user
		update_option( $this->option_key_takeover, get_current_user_id() );

		// Delete the options that indicate the original user is editing
		$this->clear_options();

		wp_send_json_success();
	} // End ajax_takeover()


	/**
	 * Ajax from the takeover
	 *
	 * @return void
	 */
	public function ajax_exit() {
		check_ajax_referer( $this->nonce, 'nonce' );

		if ( isset( $_POST[ 'post_id' ] ) ) {
			delete_option( $this->option_key_takeover );
			
			if ( isset( $_POST[ 'autoboot' ] ) && filter_var( wp_unslash( $_POST[ 'autoboot' ] ), FILTER_VALIDATE_BOOLEAN ) ) {
				$this->clear_options();
			}

			wp_send_json_success();
		}

		wp_send_json_error( 'Invalid request' );
	} // End ajax_exit()


	/**
	 * Close the editor
	 *
	 * @return void
	 */
	public function ajax_close() {
		check_ajax_referer( $this->nonce, 'nonce' );

		// Clear the options
		$post_id = $this->clear_options( true );

		// Log it
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$user = wp_get_current_user();
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( sprintf(
				/* translators: 1 = user display name, 2 = post ID */
				__( 'Cornerstone Editor closed by %1$s on post ID %2$d', 'cornerstone-companion' ),
				$user->display_name,
				$post_id
			) );

		}

		wp_send_json_success();
	} // End ajax_close()


	/**
	 * Auto boot the user if no activity for a long time
	 *
	 * @return void
	 */
	public function ajax_autoboot() {
		check_ajax_referer( $this->nonce, 'nonce' );

		// Clear the options
		$post_id = $this->clear_options( true );

		// Log it
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$user = wp_get_current_user();
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( sprintf(
				/* translators: 1 = minutes of inactivity, 2 = user display name, 3 = post ID */
				__( 'Cornerstone Editor closed automatically after %1$s minutes of no activity. %2$s left the editor open while editing post ID %3$d.', 'cornerstone-companion' ),
				absint( $this->autoboot_time ),
				$user->display_name,
				$post_id
			) );
		}

		wp_send_json_success();
	} // End ajax_autoboot()


	/**
	 * Enqueue JS for the Previewer
	 */
	public function enqueue_previewer() {
		// Get the post id
		$post_id = get_the_ID();

		// Only load script on Cornerstone editor page
		if ( !(new Helpers())->is_preview() || !$post_id || !current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$has_taken_over = $this->has_someone_taken_over();
		$locked = $this->check_lock_all_cornerstone_editors();
		if ( $locked && !$has_taken_over ) {

			$locked_user_id = isset( $locked[ 'user' ] ) ? $locked[ 'user' ] : 0;
			if ( $locked_user_id ) {
				$locked_user = get_userdata( $locked_user_id );
				$display_name = $locked_user->display_name;
			} else {
				$display_name = __( 'Another user', 'cornerstone-companion' );
			}

			$locked_post_id = isset( $locked[ 'post' ] ) ? $locked[ 'post' ] : 0;
			if ( $locked_post_id ) {
				$post_title = get_the_title( $locked_post_id );
			} else {
				$post_title = __( 'another post', 'cornerstone-companion' );
			}
			
			$message = sprintf(
				/* translators: %s = user display name */
				__( '%s is currently editing a page in Cornerstone.', 'cornerstone-companion' ),
				$display_name
			);

			if ( $post_title ) {
				$message .= ' ' . sprintf(
					/* translators: %s = post title */
					__( 'The post being edited is titled "%s".', 'cornerstone-companion' ),
					$post_title
				);
			}

			$message .= ' ' . __( 'Editing at the same time can result in global CSS and JS being overwritten if both users make changes and save independently.', 'cornerstone-companion' );

			// Enqueue
			$handle = 'cscompanion_edit_lock_takeover';
			wp_enqueue_style( $handle, CSCOMPANION_CSS_URL . 'edit-lock-takeover.css', [], CSCOMPANION_VERSION );
			wp_enqueue_script( $handle, CSCOMPANION_JS_URL . 'edit-lock-takeover.js', [ 'jquery' ], CSCOMPANION_VERSION, true );
			wp_localize_script( $handle, $handle, [
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( $this->nonce ),
				'action'   => $this->ajax_key_takeover,
				'text'     => [
					'message'          => $message,
					'title'            => __( 'Cornerstone Editing Lock', 'cornerstone-companion' ),
					'go_back'          => __( 'Go Back', 'cornerstone-companion' ),
					'take_over'        => __( 'Take Over', 'cornerstone-companion' ),
					'taking_over_btn'  => __( 'Taking over...', 'cornerstone-companion' ),
					'taking_over_msg'  => __( 'Please wait while we ensure a smooth take over.', 'cornerstone-companion' ),
					'confirm_takeover' => __( 'Warning: Taking over will forcibly disconnect the current user from Cornerstone editing. Are you sure you want to proceed?', 'cornerstone-companion' )
				]
			] );

		} else {

			// Set initial lock immediately
			$locked = $this->set_lock( $post_id );

			// Translators: %s is the name of the user who has taken over editing.
			$user_take_over_msg = __( '%s has taken over editing.', 'cornerstone-companion' );
			if ( $this->should_save( $post_id ) ) {
				$user_take_over_msg .= ' ' . __( 'Your progress has been saved.', 'cornerstone-companion' );
			}

			// Enqueue
			$handle = 'cscompanion_edit_lock_previewer';
			wp_enqueue_style( $handle, CSCOMPANION_CSS_URL . 'edit-lock-takeover.css', [], CSCOMPANION_VERSION );
			wp_enqueue_script( $handle, CSCOMPANION_JS_URL . 'edit-lock-previewer.js', [ 'jquery' ], CSCOMPANION_VERSION, true );
			wp_localize_script( $handle, $handle, [
				'ajax_url'                 => admin_url( 'admin-ajax.php' ),
				'nonce'                    => wp_create_nonce( $this->nonce ),
				'action_previewer'         => $this->ajax_key_previewer,
				'action_since'             => $this->ajax_key_since,
				'action_exit'              => $this->ajax_key_exit,
				'action_autoboot'          => $this->ajax_key_autoboot,
				'post_id'                  => $post_id,
				'interval'                 => $this->interval * 1000,
				'locked'                   => $locked,
				'autoboot_enabled'		   => filter_var( get_option( 'cscompanion_edit_lock_enable_autoboot', true ), FILTER_VALIDATE_BOOLEAN ),
				'autoboot_time'            => $this->autoboot_time * MINUTE_IN_SECONDS,
				'autoboot_no_reponse_time' => $this->autoboot_no_response_time * 1000,
				'should_save'			   => $this->should_save( $post_id ),
				'text'                     => [
					'title'                   => __( 'Cornerstone Editing Lock', 'cornerstone-companion' ),
					'user_taken_over'         => $user_take_over_msg,
					'exit'                    => __( 'Exit', 'cornerstone-companion' ),
					'inactive_title'          => __( 'Inactivity Timeout', 'cornerstone-companion' ),
					'autoboot_title'          => __( 'Are you still there?', 'cornerstone-companion' ),
					'autoboot_msg'            => $this->autoboot_msg,
					'autoboot_exit_msg'       => __( 'You left the editor open without being active for too long. Please exit.', 'cornerstone-companion' ),
					'autoboot_continue'       => __( 'Continue Editing', 'cornerstone-companion' ),
					'autoboot_exit'           => __( 'Exit', 'cornerstone-companion' ),
					'autoboot_save'           => __( 'Saving', 'cornerstone-companion' ),
				]
			] );

		}
	} // End enqueue_previewer()

	
	/**
	 * Enqueue JS for the Heartbeat
	 */
	public function enqueue_heartbeat() {
		// Enqueue
		$handle = 'cscompanion_edit_lock_heartbeat';
		wp_enqueue_style( $handle, CSCOMPANION_CSS_URL . 'edit-lock-notice.css', [], CSCOMPANION_VERSION );
		wp_enqueue_script( $handle, CSCOMPANION_JS_URL . 'edit-lock-heartbeat.js', [ 'jquery', 'heartbeat' ], CSCOMPANION_VERSION, true );
		wp_localize_script( $handle, $handle, [
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( $this->nonce ),
			'action'   => $this->ajax_key_close,
			'text' 	   => [
				'button'  => __( 'They\'re Out', 'cornerstone-companion' ),
				'confirm' => __( 'Are you sure they are out of the Cornerstone Editor? If they are still editing, in some instances this may force-close the editor for this user without allowing them to save changes.', 'cornerstone-companion' )
			]
		] );
	} // End enqueue_heartbeat()
}