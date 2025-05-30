<?php
/**
 * Element: ERI File Library File
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
(new File())->init();


/**
 * The class
 */
class File {

    /**
     * Post Type
     *
     * @var string
     */
    private $post_type = 'erifl-files';


    /**
     * Shortcode for file downloads
     *
     * @var string
     */
    private $shortcode = 'erifl_file';


    /**
     * Download class
     *
     * @var string
     */
    private $download_class = 'erifl-file';


    /**
     * Loader
     */
    public function init() {
        
        // If the ERI File Library plugin is active or if eri-files post type exists
        if ( is_plugin_active( 'eri-file-library/eri-file-library.php' ) || post_type_exists( 'eri-files' ) ) {
            $this->customize();
            $this->register();
        }

    } // End init()


    /**
     * Customize default identifiers for special cases
     *
     * @return void
     */
    private function customize() {
        if ( post_type_exists( 'eri-files' ) ) {
            $this->post_type = 'eri-files';
            $this->shortcode = 'eri_file';
            $this->download_class = 'efl-download';
        }
    } // End customize()


    /**
     * Register the element
     */
    private function register() {
        // Create values
        $values = cs_compose_values(
            [
                'type'                   => cs_value( 'link', 'attr', false ),
                'align_content'          => cs_value( 'left', 'attr', false ),
                'content_margin'         => cs_value( '0rem', 'attr', false ),
                'content_padding'        => cs_value( '10px', 'attr', false ),
                'border_radius'          => cs_value( '0px', 'attr', false ),
                'background_color'       => cs_value( 'transparent', 'attr', false ),
                'background_color_hover' => cs_value( 'transparent', 'attr', false ),
                'text_color'             => cs_value( 'transparent', 'attr', false ),
                'text_color_hover'       => cs_value( 'transparent', 'attr', false ),
            ],
            'omega',
        );

        // The args
        $args = [
            'title'    => __( 'CS Companion: File', 'cornerstone-companion' ),
            // 'icon' => '<svg>/* your SVG code */</svg>', // Let's make one and add it globally for all elements
            'values'   => $values,
            'builder'  => [ $this, 'builder' ],
            'style'    => [ $this, 'style' ],
            'render'   => [ $this, 'render' ]
        ];

        // Register
        cs_register_element( 'cscompanion-eri-file-library', $args );
    } // End register()


    /**
     * Builder function to define the controls for the element
     *
     * @return array
     */
    public function builder() {
        // Get all of the files
        $args = [
            'post_type'      => $this->post_type,
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'title',
            'order'          => 'ASC',
        ];
        
        // Get the posts
        $file_choices = [];
        $files = get_posts( $args );
        if ( !empty( $files ) ) {
            foreach ( $files as $file ) {
                $file_choices[] = [
                    'value' => $file->ID,
                    'label' => $file->post_title
                ];
            }
        }

        // Set the controls
        $controls = cs_compose_controls(
            [
                // Define the control navigation used to organize controls in the Inspector
                'control_nav' => [
                    'file'        => __( 'File Manager', 'cornerstone-companion' ),
                    'file:setup'  => __( 'Setup', 'cornerstone-companion' ),
                    'file:design' => __( 'Design', 'cornerstone-companion' ),
                ],

                // Define the controls that connect to our values.
                'controls' => [
                    [
                        'type'     => 'group',
                        'group'    => 'file:setup',
                        'controls' => [
                            [
                                'key'     => 'file_id',
                                'type'    => 'select',
                                'label'   => __( 'File', 'cornerstone-companion' ),
                                'options' => [
                                    'choices' => $file_choices
                                ]
                            ],
                            [
                                'key'     => 'type',
                                'type'    => 'choose',
                                'label'   => __( 'Type', 'cornerstone-companion' ),
                                'options' => [
                                    'choices' => [
                                        [ 'value' => 'link', 'label' => __( 'Link', 'cornerstone-companion' ) ],
                                        [ 'value' => 'button', 'label' => __( 'Btn', 'cornerstone-companion' ) ],
                                        [ 'value' => 'title', 'label' => __( 'Title', 'cornerstone-companion' ) ],
                                        [ 'value' => 'desc', 'label' => __( 'Desc', 'cornerstone-companion' ) ],
                                        [ 'value' => 'count', 'label' => __( 'Count', 'cornerstone-companion' ) ],
                                        [ 'value' => 'full', 'label' => __( 'Full', 'cornerstone-companion' ) ],
                                    ]
                                ]
                            ],
                            [
                                'key'       => 'title',
                                'type'      => 'text',
                                'label'     => __( 'Title', 'cornerstone-companion' ),
                                'conditions' => [ 
                                    [ 'key' => 'type', 'op' => 'IN', 'value' => [ 'link', 'button', 'full' ], 'or' => true ]
                                ],
                            ],
                            [
                                'key'       => 'desc',
                                'type'      => 'text',
                                'label'     => __( 'Description', 'cornerstone-companion' ),
                                'condition' => [ 'type' => 'full' ],
                            ],
                            [
                                'key'       => 'formats',
                                'type'      => 'text',
                                'label'     => __( 'Formats', 'cornerstone-companion' ),
                                'conditions' => [ 
                                    [ 'key' => 'type', 'op' => 'IN', 'value' => [ 'link', 'button' ], 'or' => true ]
                                ],
                            ],
                        ]
                    ],
                    [
                        'type'     => 'group',
                        'group'    => 'file:design',
                        'controls' => [
                            [
                                'keys'    => [
                                    'color' => 'background_color',
                                    'alt'   => 'background_color_hover',
                                ],
                                'type'    => 'color',
                                'label'   => __( 'Background Color', 'cornerstone-companion' ),
                                'options' => [
                                    'label'     => __( 'Base', 'cornerstone-companion' ),
                                    'alt_label' => __( 'Interaction', 'cornerstone-companion' ),
                                ],
                                'condition' => [ 'type' => 'button' ],
                            ],
                            [
                                'keys'    => [
                                    'color' => 'text_color',
                                    'alt'   => 'text_color_hover',
                                ],
                                'type'    => 'color',
                                'label'   => __( 'Text Color', 'cornerstone-companion' ),
                                'options' => [
                                    'label'     => __( 'Base', 'cornerstone-companion' ),
                                    'alt_label' => __( 'Interaction', 'cornerstone-companion' ),
                                ],
                                'condition' => [ 'type' => 'button' ],
                            ],
                            
                        ],
                        'condition' => [ 'type' => 'button' ],
                    ],
                    [
                        'key'     => 'align_content',
                        'type'    => 'text-align',
                        'label'   => __( 'Align', 'cornerstone-companion' ),
                        'group'   => 'file:design',
                    ],
                    [
                        'key'   => 'content_margin',
                        'type'  => 'margin',
                        'label' => __( 'Margin', 'cornerstone-companion' ),
                        'group' => 'file:design',
                    ],
                    [
                        'key'   => 'content_padding',
                        'type'  => 'padding',
                        'label' => __( 'Padding', 'cornerstone-companion' ),
                        'group' => 'file:design',
                        'condition' => [ 'type' => 'button' ],
                    ],
                    [
                        'key'   => 'border_radius',
                        'type'  => 'border-radius',
                        'label' => __( 'Border Radius', 'cornerstone-companion' ),
                        'group' => 'file:design',
                        'condition' => [ 'type' => 'button' ],
                    ],
                ],
            ],
            cs_partial_controls( 'omega' )
        );

        // Return the controls
        return $controls;
    } // End builder()


    /**
     * Styles
     *
     * @return string
     */
    public function style() {
        // The file download class
        $download_class = $this->download_class;

        // Add the styles
        ob_start();
        ?>
        & {
            margin: get(content_margin);
            text-align: get(align_content);
        }

        @if $type == button {
            & .<?php echo esc_attr( $download_class ); ?>.button {
                padding: get(content_padding);
            }
        }

        @if $type == button {
            & .<?php echo esc_attr( $download_class ); ?>.button {
                border-radius: get(border_radius);
            }
        }

        @if not empty-or-transparent($background_color) {
            & .<?php echo esc_attr( $download_class ); ?>.button {
                background-color: get(background_color) !important;
            }
        }

        @if not empty-or-transparent($text_color) {
            & .<?php echo esc_attr( $download_class ); ?>.button {
                color: get(text_color) !important;
            }
        }

        @if not empty-or-transparent($background_color_hover) {
            & .<?php echo esc_attr( $download_class ); ?>.button:hover {
                background-color: get(background_color_hover) !important;
            }
        }

        @if not empty-or-transparent($text_color_hover) {
            & .<?php echo esc_attr( $download_class ); ?>.button:hover {
                color: get(text_color_hover) !important;
            }
        }
        <?php
        return ob_get_clean();
    } // End style()


    /**
     * Render the data
     *
     * @param array $data
     * @return string
     */
    public function render( $data ) {
        // Extract the data
        extract( $data );

        // Includes
        $file_id = ( isset( $file_id ) && absint( $file_id ) ) ? absint( $file_id ) : 0;
        $incl_id = ( isset( $id ) && absint( $id ) ) ? ' id="' . absint( $id ) . '"' : '';
        $incl_classes = implode( ' ', $data[ 'classes' ] );
        $incl_formats = ( isset( $formats ) && sanitize_text_field( $formats ) ) ? ' formats="' . sanitize_text_field( $formats ) . '"' : '';

        // Print
        return '<div' . $incl_id . ' class="' . $incl_classes . '">' .
            do_shortcode( '[' . $this->shortcode . ' id="' . $file_id . '" type="' . $type . '"' . $incl_formats . ']' ) .
        '</div>';
    } // End render()

}