<?php
/**
 * ERI Icon Library
 */


/**
 * Define Namespaces
 */
namespace PluginRx\CornerstoneCompanion;


/**
 * Exit if accessed directly.
 */
if ( !defined( 'ABSPATH' ) ) exit;


/**
 * Instantiate the class
 */
new Icons();


/**
 * The class
 */
class Icons {

    /**
     * Taxonomy key
     *
     * @var string
     */
    private $taxonomy = 'cscompanion_icon_group';


    /**
     * Term slug
     *
     * @var string
     */
    private $term = 'eri-icons';


    /**
     * Upload subdirectory (relative to uploads base)
     *
     * @var string
     */
    private $upload_subdir = 'cornerstone-companion';


    /**
     * Meta key storing an icon's modified datetime (as provided by the host on sync)
     *
     * @var string
     */
    private $meta_modified = '_cscompanion_icon_modified';


    /**
     * Meta key marking an attachment as an ERI icon (fast lookup, mirrors the taxonomy)
     *
     * @var string
     */
    private $meta_is_icon = '_cscompanion_is_icon';


    /**
     * Option key: is this site acting as a host
     *
     * @var string
     */
    private $option_is_host = 'cscompanion_icons_is_host';


    /**
     * Option key: this site's secret key (used when acting as host)
     *
     * @var string
     */
    private $option_secret_key = 'cscompanion_icons_secret_key';


    /**
     * Option key: remote host URL (used when acting as spoke)
     *
     * @var string
     */
    private $option_remote_url = 'cscompanion_icons_remote_url';


    /**
     * Option key: remote host secret key (used when acting as spoke)
     *
     * @var string
     */
    private $option_remote_key = 'cscompanion_icons_remote_key';


    /**
     * REST namespace
     *
     * @var string
     */
    private $rest_namespace = 'cscompanion/v1';


    /**
     * Ajax keys
     *
     * @var string
     */
    private $ajax_key_check = 'cscompanion_icons_check';
    private $ajax_key_import = 'cscompanion_icons_import_one';


    /**
     * Nonce
     *
     * @var string
     */
    private $nonce = 'cscompanion_icons_nonce';


    /**
     * Constructor
     */
    public function __construct() {

        // Taxonomy
        add_action( 'init', [ $this, 'register_taxonomy' ] );
        add_action( 'init', [ $this, 'ensure_term' ], 20 );

        // Uploads
        add_filter( 'upload_dir', [ $this, 'maybe_relocate_upload' ] );
        add_filter( 'upload_mimes', [ $this, 'allow_svg_mime' ] );
        add_filter( 'wp_check_filetype_and_ext', [ $this, 'fix_svg_filetype' ], 10, 4 );
        add_filter( 'wp_handle_upload_prefilter', [ $this, 'sanitize_svg_upload' ] );

        // Media modal / list table visibility
        add_action( 'pre_get_posts', [ $this, 'exclude_from_list_table' ] );
        add_filter( 'ajax_query_attachments_args', [ $this, 'exclude_from_modal' ] );

        // REST API (host side)
        add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );

        // Ajax (spoke side)
        add_action( 'wp_ajax_' . $this->ajax_key_check, [ $this, 'ajax_check' ] );
        add_action( 'wp_ajax_' . $this->ajax_key_import, [ $this, 'ajax_import_batch' ] );

        // Admin page
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        add_action( 'admin_post_cscompanion_upload_icon', [ $this, 'handle_manual_upload' ] );
        add_action( 'admin_post_cscompanion_delete_icon', [ $this, 'handle_manual_delete' ] );
        add_action( 'admin_post_cscompanion_bulk_delete_icons', [ $this, 'handle_bulk_delete' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_media_filter' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_page_assets' ] );
        add_action( 'wp_ajax_cscompanion_regenerate_key', [ $this, 'ajax_regenerate_key' ] );
        add_action( 'wp_ajax_cscompanion_clear_key', [ $this, 'ajax_clear_key' ] );

    } // End __construct()


    /**
     * Register the taxonomy
     *
     * @return void
     */
    public function register_taxonomy() {
        register_taxonomy( $this->taxonomy, 'attachment', [
            'label'        => __( 'Icon Group', 'cornerstone-companion' ),
            'public'       => false,
            'show_ui'      => false,
            'hierarchical' => false,
        ] );
    } // End register_taxonomy()


    /**
     * Make sure our term exists so we can query against it
     *
     * @return void
     */
    public function ensure_term() {
        if ( !term_exists( $this->term, $this->taxonomy ) ) {
            wp_insert_term( __( 'ERI Icons', 'cornerstone-companion' ), $this->taxonomy, [ 'slug' => $this->term ] );
        }
    } // End ensure_term()


    /**
     * Relocate icon uploads into their own subdirectory
     *
     * @param array $dirs
     * @return array
     */
    public function maybe_relocate_upload( $dirs ) {
        if ( empty( $_REQUEST[ 'cscompanion_icon_upload' ] ) ) {
            return $dirs;
        }

        $dirs[ 'subdir' ] = '/' . $this->upload_subdir;
        $dirs[ 'path' ]   = $dirs[ 'basedir' ] . $dirs[ 'subdir' ];
        $dirs[ 'url' ]    = $dirs[ 'baseurl' ] . $dirs[ 'subdir' ];

        return $dirs;
    } // End maybe_relocate_upload()


    /**
     * Allow SVG uploads
     *
     * @param array $mimes
     * @return array
     */
    public function allow_svg_mime( $mimes ) {
        $mimes[ 'svg' ] = 'image/svg+xml';
        return $mimes;
    } // End allow_svg_mime()


    /**
     * Fix real-content filetype validation for SVGs
     *
     * @param array $data
     * @param string $file
     * @param string $filename
     * @param array $mimes
     * @return array
     */
    public function fix_svg_filetype( $data, $file, $filename, $mimes ) {
        if ( substr( $filename, -4 ) === '.svg' ) {
            $data[ 'ext' ]  = 'svg';
            $data[ 'type' ] = 'image/svg+xml';
        }
        return $data;
    } // End fix_svg_filetype()


    /**
     * Sanitize SVG uploads to strip scripts and unsafe attributes
     *
     * @param array $file
     * @return array
     */
    public function sanitize_svg_upload( $file ) {
        if ( $file[ 'type' ] !== 'image/svg+xml' ) {
            return $file;
        }

        $contents = file_get_contents( $file[ 'tmp_name' ] );

        if ( $contents === false ) {
            $file[ 'error' ] = __( 'Could not read the uploaded file.', 'cornerstone-companion' );
            return $file;
        }

        $clean = $this->sanitize_svg( $contents );

        if ( empty( $clean ) ) {
            $file[ 'error' ] = __( 'This SVG file could not be safely processed.', 'cornerstone-companion' );
            return $file;
        }

        file_put_contents( $file[ 'tmp_name' ], $clean );

        return $file;
    } // End sanitize_svg_upload()


    /**
     * Exclude icon attachments from the classic media list table
     *
     * @param \WP_Query $query
     * @return void
     */
    public function exclude_from_list_table( $query ) {
        if ( !is_admin() || !$query->is_main_query() ) {
            return;
        }

        if ( $query->get( 'post_type' ) !== 'attachment' ) {
            return;
        }

        $existing_tax_query = $query->get( 'tax_query' );
        $requesting_icons = is_array( $existing_tax_query ) && $this->tax_query_targets_icons( $existing_tax_query );

        if ( !$requesting_icons ) {
            $existing_tax_query = is_array( $existing_tax_query ) ? $existing_tax_query : [];
            $existing_tax_query[] = [
                'taxonomy' => $this->taxonomy,
                'field'    => 'slug',
                'terms'    => [ $this->term ],
                'operator' => 'NOT IN',
            ];
            $query->set( 'tax_query', $existing_tax_query );
        }
    } // End exclude_from_list_table()


    /**
     * Exclude icon attachments from media queries based on context:
     * - Always excluded outside the Cornerstone previewer
     * - Inside the previewer, excluded only when the "Images" type filter is active
     *
     * @param array $args
     * @return array
     */
    public function exclude_from_modal( $args ) {
        if ( !(new Helpers())->is_preview() ) {
            return $args;
        }

        if ( !isset( $args[ 'post_mime_type' ] ) || $args[ 'post_mime_type' ] !== 'image' ) {
            return $args;
        }

        $args[ 'tax_query' ][] = [
            'taxonomy' => $this->taxonomy,
            'field'    => 'slug',
            'terms'    => [ $this->term ],
            'operator' => 'NOT IN',
        ];

        return $args;
    } // End exclude_from_modal()


    /**
     * Check if a tax_query array already targets our icon taxonomy
     *
     * @param array $tax_query
     * @return boolean
     */
    private function tax_query_targets_icons( $tax_query ) {
        foreach ( $tax_query as $clause ) {
            if ( is_array( $clause ) && isset( $clause[ 'taxonomy' ] ) && $clause[ 'taxonomy' ] === $this->taxonomy ) {
                return true;
            }
        }
        return false;
    } // End tax_query_targets_icons()


    /**
     * Tag an attachment as an ERI icon and store its modified time
     *
     * @param int $attachment_id
     * @param string|null $modified A datetime string from the host, or null to use now
     * @return void
     */
    public function tag_as_icon( $attachment_id, $modified = null ) {
        wp_set_object_terms( $attachment_id, $this->term, $this->taxonomy );
        update_post_meta( $attachment_id, $this->meta_is_icon, '1' );
        update_post_meta( $attachment_id, $this->meta_modified, $modified ?: current_time( 'mysql', true ) );
    } // End tag_as_icon()


    /**
     * Whether this site is configured as a host
     *
     * @return boolean
     */
    public function is_host() {
        return filter_var( get_option( $this->option_is_host, false ), FILTER_VALIDATE_BOOLEAN );
    } // End is_host()


    /**
     * Get (or generate) this site's host secret key
     *
     * @param boolean $force_new
     * @return string
     */
    public function get_secret_key( $force_new = false ) {
        $key = get_option( $this->option_secret_key, '' );

        if ( empty( $key ) || $force_new ) {
            $key = wp_generate_password( 32, false );
            update_option( $this->option_secret_key, $key );
        }

        return $key;
    } // End get_secret_key()


    /**
     * Get the configured remote host URL (spoke side)
     *
     * @return string
     */
    public function get_remote_url() {
        return trailingslashit( sanitize_text_field( get_option( $this->option_remote_url, '' ) ) );
    } // End get_remote_url()


    /**
     * Get the configured remote host secret key (spoke side)
     *
     * @return string
     */
    public function get_remote_key() {
        return sanitize_text_field( get_option( $this->option_remote_key, '' ) );
    } // End get_remote_key()


    /**
     * Register REST routes exposed when this site is a host
     *
     * @return void
     */
    public function register_rest_routes() {
        register_rest_route( $this->rest_namespace, '/icons', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'rest_get_icons' ],
            'permission_callback' => [ $this, 'rest_permission_check' ],
        ] );

        register_rest_route( $this->rest_namespace, '/icons/(?P<id>\d+)/download', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'rest_download_icon' ],
            'permission_callback' => [ $this, 'rest_permission_check' ],
            'args'                => [
                'id' => [
                    'validate_callback' => function( $value ) {
                        return is_numeric( $value );
                    },
                ],
            ],
        ] );
    } // End register_rest_routes()


    /**
     * Verify the secret key on incoming REST requests
     *
     * @param \WP_REST_Request $request
     * @return boolean|\WP_Error
     */
    public function rest_permission_check( $request ) {
        if ( !$this->is_host() ) {
            return new \WP_Error( 'cscompanion_not_host', __( 'This site is not configured as a host.', 'cornerstone-companion' ), [ 'status' => 403 ] );
        }

        $provided = $request->get_header( 'x-cscompanion-key' );
        $expected = $this->get_secret_key();

        if ( empty( $provided ) || empty( $expected ) || !hash_equals( $expected, $provided ) ) {
            return new \WP_Error( 'cscompanion_bad_key', __( 'The secret key does not match.', 'cornerstone-companion' ), [ 'status' => 401 ] );
        }

        return true;
    } // End rest_permission_check()


    /**
     * Return the full list of hosted icons with their modified times
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function rest_get_icons( $request ) {
        $query = new \WP_Query( [
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'posts_per_page' => -1,
            'tax_query'      => [
                [
                    'taxonomy' => $this->taxonomy,
                    'field'    => 'slug',
                    'terms'    => [ $this->term ],
                ],
            ],
        ] );

        $icons = [];

        foreach ( $query->posts as $post ) {
            $icons[] = [
                'id'       => $post->ID,
                'slug'     => $post->post_name,
                'name'     => $post->post_title,
                'modified' => get_post_meta( $post->ID, $this->meta_modified, true ) ?: $post->post_modified_gmt,
            ];
        }

        return new \WP_REST_Response( $icons, 200 );
    } // End rest_get_icons()


    /**
     * Stream a hosted icon's raw file contents
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response|\WP_Error
     */
    public function rest_download_icon( $request ) {
        $id = absint( $request->get_param( 'id' ) );
        $path = get_attached_file( $id );

        if ( !$path || !file_exists( $path ) ) {
            return new \WP_Error( 'cscompanion_not_found', __( 'Icon file not found.', 'cornerstone-companion' ), [ 'status' => 404 ] );
        }

        $contents = file_get_contents( $path );

        if ( $contents === false ) {
            return new \WP_Error( 'cscompanion_read_failed', __( 'Could not read icon file.', 'cornerstone-companion' ), [ 'status' => 500 ] );
        }

        $contents = $this->sanitize_svg( $contents );

        if ( empty( $contents ) ) {
            return new \WP_Error( 'cscompanion_sanitize_failed', __( 'The icon could not be safely processed.', 'cornerstone-companion' ), [ 'status' => 500 ] );
        }

        $response = new \WP_REST_Response( $contents );
        $response->header( 'Content-Type', 'image/svg+xml' );

        return $response;
    } // End rest_download_icon()


    /**
     * Ajax: diff the remote host's icon list against what we have locally
     *
     * @return void
     */
    public function ajax_check() {
        check_ajax_referer( $this->nonce, 'nonce' );

        $remote_icons = $this->fetch_remote_icons();

        if ( is_wp_error( $remote_icons ) ) {
            wp_send_json_error( $remote_icons->get_error_message() );
        }

        $local_index = $this->get_local_icon_index();
        $needs_sync = [];

        foreach ( $remote_icons as $icon ) {
            $existing_modified = isset( $local_index[ $icon[ 'slug' ] ] ) ? $local_index[ $icon[ 'slug' ] ] : null;

            if ( empty( $existing_modified ) || strtotime( $icon[ 'modified' ] ) > strtotime( $existing_modified ) ) {
                $needs_sync[] = $icon;
            }
        }

        wp_send_json_success( [ 'icons' => $needs_sync ] );
    } // End ajax_check()


    /**
     * Ajax: import a batch of icons from the remote host (called in a loop by JS)
     *
     * @return void
     */
    public function ajax_import_batch() {
        check_ajax_referer( $this->nonce, 'nonce' );

        $icons = isset( $_POST[ 'icons' ] ) ? json_decode( wp_unslash( $_POST[ 'icons' ] ), true ) : [];

        if ( empty( $icons ) || !is_array( $icons ) ) {
            wp_send_json_error( 'No icons provided' );
        }

        $results = [];

        foreach ( $icons as $icon ) {
            $remote_id = isset( $icon[ 'id' ] ) ? absint( $icon[ 'id' ] ) : 0;
            $slug      = isset( $icon[ 'slug' ] ) ? sanitize_title( $icon[ 'slug' ] ) : '';
            $name      = isset( $icon[ 'name' ] ) ? sanitize_text_field( $icon[ 'name' ] ) : '';
            $modified  = isset( $icon[ 'modified' ] ) ? sanitize_text_field( $icon[ 'modified' ] ) : '';

            if ( !$remote_id || !$slug ) {
                $results[] = [ 'slug' => $slug, 'success' => false, 'message' => 'Missing icon data' ];
                continue;
            }

            $result = $this->import_icon( $remote_id, $slug, $name, $modified );

            if ( is_wp_error( $result ) ) {
                $results[] = [ 'slug' => $slug, 'success' => false, 'message' => $result->get_error_message() ];
            } else {
                $results[] = [ 'slug' => $slug, 'success' => true, 'attachment_id' => $result ];
            }
        }

        wp_send_json_success( [ 'results' => $results ] );
    } // End ajax_import_batch()


    /**
     * Fetch the remote host's icon list
     *
     * @return array|\WP_Error
     */
    private function fetch_remote_icons() {
        $url = $this->get_remote_url();
        $key = $this->get_remote_key();

        if ( empty( $url ) || empty( $key ) ) {
            return new \WP_Error( 'cscompanion_no_remote', __( 'No remote host configured.', 'cornerstone-companion' ) );
        }

        $response = wp_remote_get( $url . 'wp-json/' . $this->rest_namespace . '/icons', [
            'headers' => [ 'x-cscompanion-key' => $key ],
            'timeout' => 15,
        ] );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code === 401 ) {
            return new \WP_Error( 'cscompanion_bad_key', __( 'Sync failed: the secret key does not match.', 'cornerstone-companion' ) );
        }

        if ( $code === 403 ) {
            return new \WP_Error( 'cscompanion_not_host', __( 'Sync failed: the remote site is not configured as a host.', 'cornerstone-companion' ) );
        }

        if ( !is_array( $body ) ) {
            return new \WP_Error( 'cscompanion_bad_response', __( 'Unexpected response from host.', 'cornerstone-companion' ) );
        }

        return $body;
    } // End fetch_remote_icons()


    /**
     * Build a slug => modified map of icons already present locally
     *
     * @return array
     */
    private function get_local_icon_index() {
        $query = new \WP_Query( [
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'posts_per_page' => -1,
            'tax_query'      => [
                [
                    'taxonomy' => $this->taxonomy,
                    'field'    => 'slug',
                    'terms'    => [ $this->term ],
                ],
            ],
        ] );

        $index = [];

        foreach ( $query->posts as $post ) {
            $index[ $post->post_name ] = get_post_meta( $post->ID, $this->meta_modified, true ) ?: $post->post_modified_gmt;
        }

        return $index;
    } // End get_local_icon_index()


    /**
     * Download and import (or update) a single icon from the remote host
     *
     * @param int $remote_id
     * @param string $slug
     * @param string $name
     * @param string $modified
     * @return int|\WP_Error Attachment ID on success
     */
    private function import_icon( $remote_id, $slug, $name, $modified ) {
        $url = $this->get_remote_url();
        $key = $this->get_remote_key();

        $response = wp_remote_get( $url . 'wp-json/' . $this->rest_namespace . '/icons/' . $remote_id . '/download', [
            'headers' => [ 'x-cscompanion-key' => $key ],
            'timeout' => 20,
        ] );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );

        if ( $code === 401 ) {
            return new \WP_Error( 'cscompanion_bad_key', __( 'Import failed: the secret key does not match.', 'cornerstone-companion' ) );
        }

        if ( $code !== 200 ) {
            return new \WP_Error( 'cscompanion_download_failed', __( 'Failed to download the icon.', 'cornerstone-companion' ) );
        }

        $contents = wp_remote_retrieve_body( $response );

        if ( empty( $contents ) ) {
            return new \WP_Error( 'cscompanion_empty_download', __( 'Downloaded icon was empty.', 'cornerstone-companion' ) );
        }

        $decoded = json_decode( $contents );

        if ( $decoded !== null && is_string( $decoded ) ) {
            $contents = $decoded;
        }

        $contents = $this->sanitize_svg( $contents );

        if ( empty( $contents ) ) {
            return new \WP_Error( 'cscompanion_sanitize_failed', __( 'The downloaded icon could not be safely processed.', 'cornerstone-companion' ) );
        }

        $existing = $this->find_existing_attachment( $slug );

        if ( $existing ) {
            $existing_path = get_attached_file( $existing );

            if ( $existing_path && file_exists( $existing_path ) ) {
                file_put_contents( $existing_path, $contents );
            }

            wp_update_post( [
                'ID'         => $existing,
                'post_title' => $name ?: $slug,
            ] );

            $this->tag_as_icon( $existing, $modified );

            return $existing;
        }

        $_REQUEST[ 'cscompanion_icon_upload' ] = '1';

        $upload = wp_upload_bits( $slug . '.svg', null, $contents );

        if ( !empty( $upload[ 'error' ] ) ) {
            return new \WP_Error( 'cscompanion_upload_failed', $upload[ 'error' ] );
        }

        $attachment_id = wp_insert_attachment( [
            'post_title'     => $name ?: $slug,
            'post_name'      => $slug,
            'post_mime_type' => 'image/svg+xml',
            'post_status'    => 'inherit',
        ], $upload[ 'file' ] );

        if ( is_wp_error( $attachment_id ) || !$attachment_id ) {
            return new \WP_Error( 'cscompanion_attachment_failed', __( 'Could not save the attachment.', 'cornerstone-companion' ) );
        }

        $this->tag_as_icon( $attachment_id, $modified );

        return $attachment_id;
    } // End import_icon()


    /**
     * Find an existing local icon attachment by slug
     *
     * @param string $slug
     * @return int|false
     */
    private function find_existing_attachment( $slug ) {
        $query = new \WP_Query( [
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'name'           => $slug,
            'posts_per_page' => 1,
            'tax_query'      => [
                [
                    'taxonomy' => $this->taxonomy,
                    'field'    => 'slug',
                    'terms'    => [ $this->term ],
                ],
            ],
        ] );

        return !empty( $query->posts ) ? $query->posts[ 0 ]->ID : false;
    } // End find_existing_attachment()


    /**
     * Add the Icon Library submenu page
     *
     * @return void
     */
    public function add_admin_menu() {
        add_submenu_page(
            'upload.php',
            __( 'Custom Icons', 'cornerstone-companion' ),
            __( 'Custom Icons', 'cornerstone-companion' ),
            'manage_options',
            'cscompanion-icons',
            [ $this, 'render_admin_page' ]
        );
    } // End add_admin_menu()


    /**
     * Enqueue assets for the icon library admin page
     *
     * @param string $hook
     * @return void
     */
    public function enqueue_admin_page_assets( $hook ) {
        if ( strpos( $hook, 'cscompanion-icons' ) === false ) {
            return;
        }

        wp_enqueue_style( 'cscompanion-icons', CSCOMPANION_CSS_URL . 'icons.css', [], CSCOMPANION_VERSION );
        wp_enqueue_script( 'cscompanion-icons-admin', CSCOMPANION_JS_URL . 'icons-admin.js', [ 'jquery' ], CSCOMPANION_VERSION, true );
    } // End enqueue_admin_page_assets()


    /**
     * Enqueue the media modal icon filter on the Cornerstone previewer
     *
     * @return void
     */
    public function enqueue_media_filter() {
        if ( !(new Helpers())->is_preview() ) {
            return;
        }

        wp_enqueue_media();
    } // End enqueue_media_filter()


    /**
     * Render the Icon Library admin page
     *
     * @return void
     */
    public function render_admin_page() {
        if ( !current_user_can( 'manage_options' ) ) {
            return;
        }

        $icons = $this->get_all_local_icons();
        $notice_data = get_transient( 'cscompanion_icons_notice_' . get_current_user_id() );

        if ( $notice_data ) {
            delete_transient( 'cscompanion_icons_notice_' . get_current_user_id() );
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__( 'Cornerstone Companion Icon Library', 'cornerstone-companion' ); ?></h1>

            <?php if ( isset( $_GET[ 'cscompanion_notice' ] ) ) { ?>
                <div class="notice notice-<?php echo esc_attr( $_GET[ 'cscompanion_notice' ] === 'error' ? 'error' : 'success' ); ?> is-dismissible">
                    <p><?php echo esc_html( $this->get_notice_message( sanitize_text_field( wp_unslash( $_GET[ 'cscompanion_notice' ] ) ), isset( $_GET[ 'cscompanion_count' ] ) ? absint( $_GET[ 'cscompanion_count' ] ) : 0 ) ); ?></p>
                </div>
            <?php } ?>

            <div class="cscompanion-icons-upload-box">
                <h2><?php echo esc_html__( 'Upload New Icons', 'cornerstone-companion' ); ?></h2>
                <form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                    <input type="hidden" name="action" value="cscompanion_upload_icon" />
                    <?php wp_nonce_field( 'cscompanion_upload_icon' ); ?>

                    <p>
                        <label for="cscompanion_icon_file"><?php echo esc_html__( 'SVG File(s)', 'cornerstone-companion' ); ?></label><br />
                        <input type="file" id="cscompanion_icon_file" name="icon_file[]" accept=".svg" multiple="multiple" required />
                        <br />
                        <span class="description"><?php echo esc_html__( 'Icon names are generated from the file name. You can rename an icon afterward from the list below.', 'cornerstone-companion' ); ?></span>
                    </p>

                    <?php submit_button( __( 'Upload Icon(s)', 'cornerstone-companion' ) ); ?>
                </form>
            </div>

            <h2><?php echo esc_html__( 'Current Icons', 'cornerstone-companion' ); ?></h2>

            <?php if ( empty( $icons ) ) { ?>
                <p><?php echo esc_html__( 'No icons uploaded yet.', 'cornerstone-companion' ); ?></p>
            <?php } else { ?>
                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                    <input type="hidden" name="action" value="cscompanion_bulk_delete_icons" />
                    <?php wp_nonce_field( 'cscompanion_bulk_delete_icons' ); ?>

                    <div class="tablenav top">
                        <button type="submit" class="button" onclick="return confirm('<?php echo esc_js( __( 'Delete all selected icons? This cannot be undone.', 'cornerstone-companion' ) ); ?>');">
                            <?php echo esc_html__( 'Delete Selected', 'cornerstone-companion' ); ?>
                        </button>
                    </div>

                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <td class="manage-column column-cb check-column">
                                    <input type="checkbox" id="cscompanion-select-all" />
                                </td>
                                <th><?php echo esc_html__( 'Preview', 'cornerstone-companion' ); ?></th>
                                <th><?php echo esc_html__( 'Name', 'cornerstone-companion' ); ?></th>
                                <th><?php echo esc_html__( 'Slug', 'cornerstone-companion' ); ?></th>
                                <th><?php echo esc_html__( 'Last Modified', 'cornerstone-companion' ); ?></th>
                                <th><?php echo esc_html__( 'Actions', 'cornerstone-companion' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $icons as $icon ) { ?>
                                <tr>
                                    <th class="check-column">
                                        <input type="checkbox" name="icon_ids[]" value="<?php echo esc_attr( $icon[ 'id' ] ); ?>" class="cscompanion-icon-checkbox" />
                                    </th>
                                    <td><?php echo wp_kses( $this->get_icon_preview_markup( $icon[ 'id' ] ), $this->svg_kses_rules() ); ?></td>
                                    <td><?php echo esc_html( $icon[ 'name' ] ); ?></td>
                                    <td><?php echo esc_html( $icon[ 'slug' ] ); ?></td>
                                    <td><?php echo esc_html( $icon[ 'modified' ] ); ?></td>
                                    <td>
                                        <a href="<?php echo esc_url( admin_url( 'post.php?action=edit&post=' . $icon[ 'id' ] ) ); ?>">
                                            <?php echo esc_html__( 'Edit', 'cornerstone-companion' ); ?>
                                        </a>
                                        |
                                        <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=cscompanion_delete_icon&attachment_id=' . $icon[ 'id' ] ), 'cscompanion_delete_icon_' . $icon[ 'id' ] ) ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Delete this icon?', 'cornerstone-companion' ) ); ?>');">
                                            <?php echo esc_html__( 'Delete', 'cornerstone-companion' ); ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </form>
            <?php } ?>
        </div>
        <?php
    } // End render_admin_page()


    /**
     * Handle the manual upload form submission (supports multiple files)
     *
     * @return void
     */
    public function handle_manual_upload() {
        if ( !current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Unauthorized', 'cornerstone-companion' ) );
        }

        check_admin_referer( 'cscompanion_upload_icon' );

        if ( empty( $_FILES[ 'icon_file' ] ) || empty( $_FILES[ 'icon_file' ][ 'tmp_name' ][ 0 ] ) ) {
            $this->redirect_with_notice( 'error' );
        }

        if ( !function_exists( 'wp_handle_upload' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        $files = $_FILES[ 'icon_file' ];
        $count = count( $files[ 'name' ] );
        $success_count = 0;

        for ( $i = 0; $i < $count; $i++ ) {
            if ( empty( $files[ 'tmp_name' ][ $i ] ) ) {
                continue;
            }

            $single_file = [
                'name'     => $files[ 'name' ][ $i ],
                'type'     => $files[ 'type' ][ $i ],
                'tmp_name' => $files[ 'tmp_name' ][ $i ],
                'error'    => $files[ 'error' ][ $i ],
                'size'     => $files[ 'size' ][ $i ],
            ];

            if ( $this->process_single_icon_upload( $single_file ) ) {
                $success_count++;
            }
        }

        if ( $success_count === 0 ) {
            $this->redirect_with_notice( 'error' );
        }

        $this->redirect_with_notice( 'success', $success_count );
    } // End handle_manual_upload()


    /**
     * Process a single file from a bulk upload
     *
     * @param array $file A single $_FILES-style array
     * @return boolean
     */
    private function process_single_icon_upload( $file ) {
        $_REQUEST[ 'cscompanion_icon_upload' ] = '1';

        $upload = wp_handle_upload( $file, [ 'test_form' => false ] );

        if ( !empty( $upload[ 'error' ] ) ) {
            return false;
        }

        $name = pathinfo( $file[ 'name' ], PATHINFO_FILENAME );
        $name = ucwords( str_replace( [ '-', '_' ], ' ', $name ) );
        $slug = sanitize_title( $name );

        $attachment_id = wp_insert_attachment( [
            'post_title'     => $name,
            'post_name'      => $slug,
            'post_mime_type' => 'image/svg+xml',
            'post_status'    => 'inherit',
        ], $upload[ 'file' ] );

        if ( is_wp_error( $attachment_id ) || !$attachment_id ) {
            return false;
        }

        $this->tag_as_icon( $attachment_id );

        return true;
    } // End process_single_icon_upload()


    /**
     * Handle deleting an icon from the library
     *
     * @return void
     */
    public function handle_manual_delete() {
        if ( !current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Unauthorized', 'cornerstone-companion' ) );
        }

        $attachment_id = isset( $_GET[ 'attachment_id' ] ) ? absint( $_GET[ 'attachment_id' ] ) : 0;

        check_admin_referer( 'cscompanion_delete_icon_' . $attachment_id );

        if ( $attachment_id ) {
            wp_delete_attachment( $attachment_id, true );
        }

        $this->redirect_with_notice( 'deleted' );
    } // End handle_manual_delete()


    /**
     * Handle bulk deleting selected icons
     *
     * @return void
     */
    public function handle_bulk_delete() {
        if ( !current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Unauthorized', 'cornerstone-companion' ) );
        }

        check_admin_referer( 'cscompanion_bulk_delete_icons' );

        $ids = isset( $_POST[ 'icon_ids' ] ) ? array_map( 'absint', wp_unslash( $_POST[ 'icon_ids' ] ) ) : [];

        if ( empty( $ids ) ) {
            $this->redirect_with_notice( 'error' );
        }

        $deleted = 0;

        foreach ( $ids as $id ) {
            if ( wp_delete_attachment( $id, true ) ) {
                $deleted++;
            }
        }

        $this->redirect_with_notice( 'bulk_deleted', $deleted );
    } // End handle_bulk_delete()


    /**
     * Set a one-time admin notice for the current user
     *
     * @param string $notice
     * @param int $count
     * @return void
     */
    private function set_notice( $notice, $count = 0 ) {
        set_transient( 'cscompanion_icons_notice_' . get_current_user_id(), [
            'notice' => $notice,
            'count'  => $count,
        ], 30 );
    } // End set_notice()


    /**
     * Redirect back to the icon library page with a clean URL
     *
     * @param string $notice
     * @param int $count
     * @return void
     */
    private function redirect_with_notice( $notice, $count = 0 ) {
        $this->set_notice( $notice, $count );

        wp_safe_redirect( admin_url( 'upload.php?page=cscompanion-icons' ) );
        exit;
    } // End redirect_with_notice()


    /**
     * Map a notice key to its display message
     *
     * @param string $notice
     * @param int $count
     * @return string
     */
    private function get_notice_message( $notice, $count = 0 ) {
        if ( $notice === 'success' ) {
            return sprintf( _n( '%d icon uploaded successfully.', '%d icons uploaded successfully.', $count, 'cornerstone-companion' ), $count );
        }

        if ( $notice === 'bulk_deleted' ) {
            return sprintf( _n( '%d icon deleted.', '%d icons deleted.', $count, 'cornerstone-companion' ), $count );
        }

        $messages = [
            'deleted' => __( 'Icon deleted.', 'cornerstone-companion' ),
            'error'   => __( 'Something went wrong. Please try again.', 'cornerstone-companion' ),
        ];

        return isset( $messages[ $notice ] ) ? $messages[ $notice ] : $messages[ 'error' ];
    } // End get_notice_message()


    /**
     * Get all icons currently in the local library
     *
     * @return array
     */
    private function get_all_local_icons() {
        $query = new \WP_Query( [
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
            'tax_query'      => [
                [
                    'taxonomy' => $this->taxonomy,
                    'field'    => 'slug',
                    'terms'    => [ $this->term ],
                ],
            ],
        ] );

        $icons = [];

        foreach ( $query->posts as $post ) {
            $icons[] = [
                'id'       => $post->ID,
                'name'     => $post->post_title,
                'slug'     => $post->post_name,
                'modified' => get_post_meta( $post->ID, $this->meta_modified, true ) ?: $post->post_modified_gmt,
            ];
        }

        return $icons;
    } // End get_all_local_icons()


    /**
     * Get the raw SVG preview markup for an attachment
     *
     * @param int $attachment_id
     * @return string
     */
    private function get_icon_preview_markup( $attachment_id ) {
        $path = get_attached_file( $attachment_id );

        if ( !$path || !file_exists( $path ) ) {
            return '';
        }

        $contents = file_get_contents( $path );

        if ( $contents === false ) {
            return '';
        }

        $contents = preg_replace( '/<\?xml.*?\?>/s', '', $contents );
        $contents = trim( $contents );

        return '<div style="width:32px;height:32px;">' . $contents . '</div>';
    } // End get_icon_preview_markup()


    /**
     * Allowed tags for outputting raw SVG previews via wp_kses
     *
     * @return array
     */
    private function svg_kses_rules() {
        return [
            'div' => [ 'style' => true ],
            'svg' => [ 'xmlns' => true, 'viewbox' => true, 'width' => true, 'height' => true, 'fill' => true, 'id' => true, 'class' => true ],
            'g'   => [ 'id' => true, 'class' => true ],
            'path' => [ 'd' => true, 'fill' => true, 'class' => true ],
        ];
    } // End svg_kses_rules()


    /**
     * Ajax: regenerate the host secret key
     *
     * @return void
     */
    public function ajax_regenerate_key() {
        check_ajax_referer( $this->nonce, 'nonce' );

        if ( !current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Unauthorized' );
        }

        $new_key = $this->get_secret_key( true );

        wp_send_json_success( [ 'key' => $new_key ] );
    } // End ajax_regenerate_key()


    /**
     * Ajax: clear the host secret key
     *
     * @return void
     */
    public function ajax_clear_key() {
        check_ajax_referer( $this->nonce, 'nonce' );

        if ( !current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Unauthorized' );
        }

        delete_option( $this->option_secret_key );

        wp_send_json_success();
    } // End ajax_clear_key()



    /**
     * Sanitize raw SVG markup using the Safe SVG plugin's bundled sanitizer, if available
     *
     * @param string $svg
     * @return string
     */
    private function sanitize_svg( $svg ) {
        if ( !class_exists( '\enshrined\svgSanitize\Sanitizer' ) ) {
            return $svg;
        }

        $sanitizer = new \enshrined\svgSanitize\Sanitizer();
        $sanitizer->removeRemoteReferences( true );

        $clean = $sanitizer->sanitize( $svg );

        return $clean !== false ? $clean : '';
    } // End sanitize_svg()

}