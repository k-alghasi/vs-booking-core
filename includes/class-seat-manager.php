<?php
/**
 * Class VSBBM_Seat_Manager
 *
 * Manages seat configuration, admin meta boxes, and frontend seat selection logic.
 *
 * @package VSBBM
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

class VSBBM_Seat_Manager {

    /**
     * Initialize the class and register hooks.
     */
    public static function init() {
        // جلوگیری از اجرای چندباره
        static $initialized = false;
        if ( $initialized ) {
            return;
        }
        $initialized = true;

        // Admin Product Hooks
        add_action( 'woocommerce_product_options_general_product_data', array( __CLASS__, 'add_product_fields' ) );
        add_action( 'woocommerce_process_product_meta', array( __CLASS__, 'save_product_fields' ) );

        // Seat Layout Meta Box
        add_action( 'add_meta_boxes', array( __CLASS__, 'add_seat_meta_box' ) );
        add_action( 'save_post_product', array( __CLASS__, 'save_seat_numbers' ) );

        // Schedule Meta Box (Phase 2 Feature integrated in Phase 1 for stability)
        add_action( 'add_meta_boxes', array( __CLASS__, 'add_schedule_meta_box' ) );
        add_action( 'save_post_product', array( __CLASS__, 'save_schedule_settings' ) );

        // Assets
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_scripts' ) );
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_frontend_scripts' ) );

        // AJAX Handlers
        self::register_ajax_handlers();

        // Frontend Display
        add_action( 'woocommerce_single_product_summary', array( __CLASS__, 'display_seat_selection' ), 25 );
    }

    /**
     * Enqueue Admin Scripts and Styles.
     *
     * @param string $hook Current admin page hook.
     */
    public static function enqueue_admin_scripts( $hook ) {
        $screen = get_current_screen();

        if ( $screen && 'product' === $screen->id ) {
            // Admin CSS
            wp_enqueue_style( 'vsbbm-admin-seat', VSBBM_PLUGIN_URL . 'assets/css/admin.css', array(), VSBBM_VERSION );

            // Admin JS
            wp_enqueue_script( 'vsbbm-admin-seat', VSBBM_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery', 'jquery-ui-datepicker' ), VSBBM_VERSION, true );

            // Datepicker Styles
            wp_enqueue_style( 'jquery-ui-style', '//ajax.googleapis.com/ajax/libs/jqueryui/1.13.2/themes/smoothness/jquery-ui.css', array(), '1.13.2' );

            // Inline styles for seat editor (Moved from admin_head to inline style)
            $custom_css = "
                .vsbbm-seat-layout-editor { display: flex; flex-direction: column; gap: 10px; padding: 10px; background: #fcfcfc; border: 1px solid #ddd; border-radius: 4px; }
                .vsbbm-seat-layout-editor .vsbbm-seat-row { display: flex; gap: 5px; justify-content: center; }
                .vsbbm-seat { display: inline-block; width: 30px; height: 30px; line-height: 30px; text-align: center; border: 1px solid #ccc; border-radius: 4px; cursor: pointer; background-color: #fff; font-size: 10px; user-select: none; transition: all 0.1s; }
                .vsbbm-seat:hover { background-color: #f0f0f0; }
                .vsbbm-seat.active { background-color: #5cb85c; color: #fff; border-color: #4cae4c; }
                .vsbbm-seat.aisle { background-color: transparent; border: none; cursor: default; }
                .vsbbm-seat.reserved { background-color: #d9534f; color: #fff; cursor: not-allowed; }
                .vsbbm-seat.toilet, .vsbbm-seat.door, .vsbbm-seat.none { background-color: #eee; border-color: #ddd; cursor: default; font-size: 8px; }
            ";
            wp_add_inline_style( 'vsbbm-admin-seat', $custom_css );
        }
    }

    /**
     * Enqueue Frontend Scripts.
     */
    public static function enqueue_frontend_scripts() {
        if ( ! is_product() ) {
            return;
        }

        global $post;
        if ( ! self::is_seat_booking_enabled( $post->ID ) ) {
            return;
        }

        wp_enqueue_style( 'vsbbm-frontend-seat', VSBBM_PLUGIN_URL . 'assets/css/frontend.css', array(), VSBBM_VERSION );
        wp_enqueue_script( 'vsbbm-frontend', VSBBM_PLUGIN_URL . 'assets/js/frontend.js', array( 'jquery' ), VSBBM_VERSION, true );

        wp_localize_script(
            'vsbbm-frontend',
            'vsbbm_ajax_object',
            array(
                'ajax_url'          => admin_url( 'admin-ajax.php' ),
                'product_id'        => $post->ID,
                'nonce'             => wp_create_nonce( 'vsbbm_seat_nonce' ),
                'schedule_settings' => self::get_schedule_settings( $post->ID ),
                'current_time'      => current_time( 'timestamp' ),
                'currency_symbol'   => get_woocommerce_currency_symbol(),
                'i18n'              => array(
                    'select_departure_date' => __( 'Please select a departure date.', 'vs-bus-booking-manager' ),
                    'seat_reserved'         => __( 'Seat Reserved', 'vs-bus-booking-manager' ),
                    'seat_selected'         => __( 'Selected', 'vs-bus-booking-manager' ),
                    'seat_available'        => __( 'Available', 'vs-bus-booking-manager' ),
                    'fill_passenger_data'   => __( 'Please fill in all passenger details.', 'vs-bus-booking-manager' ),
                    'max_seats_reached'     => __( 'Maximum seat limit reached.', 'vs-bus-booking-manager' ),
                ),
            )
        );
    }

    /**
     * Add general product options tab fields.
     */
    public static function add_product_fields() {
        global $product_object;

        if ( ! $product_object ) {
            return;
        }

        echo '<div class="options_group">';

        woocommerce_wp_checkbox(
            array(
                'id'          => '_vsbbm_enable_seat_booking',
                'value'       => get_post_meta( $product_object->get_id(), '_vsbbm_enable_seat_booking', true ),
                'label'       => __( 'Enable Bus Seat Booking', 'vs-bus-booking-manager' ),
                'description' => __( 'Enable seat selection system for this product.', 'vs-bus-booking-manager' ),
            )
        );

        woocommerce_wp_select(
            array(
                'id'          => '_vsbbm_bus_type',
                'value'       => get_post_meta( $product_object->get_id(), '_vsbbm_bus_type', true ),
                'label'       => __( 'Bus Type', 'vs-bus-booking-manager' ),
                'description' => __( 'Select the graphical layout.', 'vs-bus-booking-manager' ),
                'options'     => array(
                    ''    => __( 'None', 'vs-bus-booking-manager' ),
                    '2x2' => __( '2x2 (Standard)', 'vs-bus-booking-manager' ),
                    '1x2' => __( '1x2 (VIP)', 'vs-bus-booking-manager' ),
                    '1x1' => __( '1x1 (Custom)', 'vs-bus-booking-manager' ),
                ),
            )
        );

        woocommerce_wp_text_input(
            array(
                'id'                => '_vsbbm_bus_rows',
                'value'             => get_post_meta( $product_object->get_id(), '_vsbbm_bus_rows', true ),
                'label'             => __( 'Rows', 'vs-bus-booking-manager' ),
                'description'       => __( 'Maximum number of seat rows.', 'vs-bus-booking-manager' ),
                'type'              => 'number',
                'custom_attributes' => array(
                    'min'  => '1',
                    'step' => '1',
                ),
            )
        );

        woocommerce_wp_text_input(
            array(
                'id'                => '_vsbbm_bus_columns',
                'value'             => get_post_meta( $product_object->get_id(), '_vsbbm_bus_columns', true ),
                'label'             => __( 'Columns', 'vs-bus-booking-manager' ),
                'description'       => __( 'Columns per side (e.g., 2 for 2x2).', 'vs-bus-booking-manager' ),
                'type'              => 'number',
                'custom_attributes' => array(
                    'min'  => '1',
                    'step' => '1',
                ),
            )
        );

        echo '</div>';
    }

    /**
     * Save general product fields.
     *
     * @param int $post_id Product ID.
     */
    public static function save_product_fields( $post_id ) {
        // Security check handled by WooCommerce mostly, but we verify existence
        if ( isset( $_POST['_vsbbm_enable_seat_booking'] ) ) {
            update_post_meta( $post_id, '_vsbbm_enable_seat_booking', 'yes' );
        } else {
            update_post_meta( $post_id, '_vsbbm_enable_seat_booking', 'no' );
        }

        if ( isset( $_POST['_vsbbm_bus_type'] ) ) {
            update_post_meta( $post_id, '_vsbbm_bus_type', sanitize_text_field( $_POST['_vsbbm_bus_type'] ) );
        }

        if ( isset( $_POST['_vsbbm_bus_rows'] ) ) {
            update_post_meta( $post_id, '_vsbbm_bus_rows', absint( $_POST['_vsbbm_bus_rows'] ) );
        }

        if ( isset( $_POST['_vsbbm_bus_columns'] ) ) {
            update_post_meta( $post_id, '_vsbbm_bus_columns', absint( $_POST['_vsbbm_bus_columns'] ) );
        }
    }

    /**
     * Add Seat Layout Meta Box.
     */
    public static function add_seat_meta_box() {
        add_meta_box(
            'vsbbm_seat_layout',
            __( 'Bus Seat Layout', 'vs-bus-booking-manager' ),
            array( __CLASS__, 'render_seat_meta_box' ),
            'product',
            'normal',
            'high'
        );
    }

    /**
     * Render Seat Layout Meta Box.
     *
     * @param WP_Post $post Current post object.
     */
    public static function render_seat_meta_box( $post ) {
        wp_nonce_field( 'vsbbm_save_seat_numbers', 'vsbbm_seat_numbers_nonce' );

        $seat_numbers = self::get_seat_numbers( $post->ID );
        $settings     = self::get_product_settings( $post->ID );
        ?>
        <div id="vsbbm-seat-manager">
            <p><?php esc_html_e( 'To enable, first check "Enable Bus Seat Booking" in the General tab.', 'vs-bus-booking-manager' ); ?></p>
            
            <div class="vsbbm-seat-layout-editor" 
                 data-rows="<?php echo esc_attr( $settings['rows'] ); ?>" 
                 data-cols="<?php echo esc_attr( $settings['columns'] ); ?>" 
                 data-type="<?php echo esc_attr( $settings['type'] ); ?>">
                <div id="vsbbm-seat-grid"></div>
            </div>

            <input type="hidden" name="_vsbbm_seat_numbers" id="vsbbm_seat_numbers_input" value="<?php echo esc_attr( json_encode( $seat_numbers ) ); ?>" />
            
            <p>
                <strong><?php esc_html_e( 'Defined Seats:', 'vs-bus-booking-manager' ); ?></strong> 
                <span id="vsbbm-current-seats"><?php echo esc_html( implode( ', ', array_keys( $seat_numbers ) ) ); ?></span>
            </p>
            <p><small><?php esc_html_e( 'To change dimensions, update the General tab and save.', 'vs-bus-booking-manager' ); ?></small></p>
        </div>
        <?php
    }

    /**
     * Save Seat Numbers (Layout).
     *
     * @param int $post_id Product ID.
     */
    public static function save_seat_numbers( $post_id ) {
        if ( ! isset( $_POST['vsbbm_seat_numbers_nonce'] ) || ! wp_verify_nonce( $_POST['vsbbm_seat_numbers_nonce'], 'vsbbm_save_seat_numbers' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( isset( $_POST['_vsbbm_seat_numbers'] ) ) {
            // FIX: Don't use wp_kses_post on JSON string before decoding.
            $json_raw = wp_unslash( $_POST['_vsbbm_seat_numbers'] );
            $seat_numbers = json_decode( $json_raw, true );

            $clean_seats = array();
            if ( is_array( $seat_numbers ) ) {
                foreach ( $seat_numbers as $seat_key => $seat_data ) {
                    $clean_seats[ sanitize_key( $seat_key ) ] = array(
                        'label' => sanitize_text_field( $seat_data['label'] ?? $seat_key ),
                        'price' => floatval( $seat_data['price'] ?? 0 ),
                        'type'  => sanitize_key( $seat_data['type'] ?? 'default' ),
                    );
                }
            }

            update_post_meta( $post_id, '_vsbbm_seat_numbers', $clean_seats );

            if ( class_exists( 'VSBBM_Cache_Manager' ) ) {
                VSBBM_Cache_Manager::get_instance()->clear_product_cache( $post_id );
            }
        }
    }

    /**
     * Add Schedule Meta Box.
     */
    public static function add_schedule_meta_box() {
        add_meta_box(
            'vsbbm_schedule_meta_box',
            __( 'Trip Schedule', 'vs-bus-booking-manager' ),
            array( __CLASS__, 'render_schedule_meta_box' ),
            'product',
            'side',
            'high'
        );
    }

    /**
     * Render Schedule Meta Box.
     *
     * @param WP_Post $post Current post object.
     */
    public static function render_schedule_meta_box( $post ) {
        $settings = self::get_schedule_settings( $post->ID );
        ?>
        <div class="vsbbm-schedule-settings">
            <p>
                <label for="vsbbm_enable_schedule">
                    <input type="checkbox" id="vsbbm_enable_schedule" name="vsbbm_enable_schedule" value="yes" <?php checked( 'yes', $settings['enable_schedule'] ); ?> />
                    <?php esc_html_e( 'Enable Trip Scheduling', 'vs-bus-booking-manager' ); ?>
                </label>
            </p>
            <div id="vsbbm_schedule_fields" style="<?php echo ( 'yes' !== $settings['enable_schedule'] ) ? 'display: none;' : ''; ?>">
                <p>
                    <label for="vsbbm_min_days_advance"><?php esc_html_e( 'Min Days in Advance:', 'vs-bus-booking-manager' ); ?></label>
                    <input type="number" id="vsbbm_min_days_advance" name="vsbbm_min_days_advance" value="<?php echo esc_attr( $settings['min_days_advance'] ); ?>" min="0" step="1" class="widefat" />
                </p>
                <p>
                    <label for="vsbbm_max_days_advance"><?php esc_html_e( 'Max Days in Advance:', 'vs-bus-booking-manager' ); ?></label>
                    <input type="number" id="vsbbm_max_days_advance" name="vsbbm_max_days_advance" value="<?php echo esc_attr( $settings['max_days_advance'] ); ?>" min="0" step="1" class="widefat" />
                </p>
                <p>
                    <label for="vsbbm_departure_time"><?php esc_html_e( 'Departure Time:', 'vs-bus-booking-manager' ); ?></label>
                    <input type="text" id="vsbbm_departure_time" name="vsbbm_departure_time" value="<?php echo esc_attr( $settings['departure_time'] ); ?>" placeholder="e.g. 14:30" class="widefat" />
                </p>
            </div>
            <?php
            // Move small inline script to wp_add_inline_script in enqueue if possible, 
            // but for small toggle logic inside meta box, this is acceptable if escaped.
            ?>
            <script>
                jQuery(document).ready(function($) {
                    $('#vsbbm_enable_schedule').on('change', function() {
                        $('#vsbbm_schedule_fields').toggle(this.checked);
                    });
                });
            </script>
        </div>
        <?php
    }

    /**
     * Save Schedule Settings.
     *
     * @param int $post_id Product ID.
     */
    public static function save_schedule_settings( $post_id ) {
        // Since this is hooked to 'save_post_product', we share the nonce check from save_seat_numbers 
        // OR rely on WC/WP save_post security. For strict security:
        if ( isset( $_POST['vsbbm_seat_numbers_nonce'] ) && ! wp_verify_nonce( $_POST['vsbbm_seat_numbers_nonce'], 'vsbbm_save_seat_numbers' ) ) {
             return;
        }

        $enable_schedule = isset( $_POST['vsbbm_enable_schedule'] ) ? 'yes' : 'no';
        $min_days        = isset( $_POST['vsbbm_min_days_advance'] ) ? absint( $_POST['vsbbm_min_days_advance'] ) : 0;
        $max_days        = isset( $_POST['vsbbm_max_days_advance'] ) ? absint( $_POST['vsbbm_max_days_advance'] ) : 365;
        $departure_time  = isset( $_POST['vsbbm_departure_time'] ) ? sanitize_text_field( $_POST['vsbbm_departure_time'] ) : '12:00';

        update_post_meta( $post_id, '_vsbbm_enable_schedule', $enable_schedule );
        update_post_meta( $post_id, '_vsbbm_min_days_advance', $min_days );
        update_post_meta( $post_id, '_vsbbm_max_days_advance', $max_days );
        update_post_meta( $post_id, '_vsbbm_departure_time', $departure_time );
    }

    /**
     * Get Schedule Settings Helper.
     *
     * @param int $product_id Product ID.
     * @return array
     */
    public static function get_schedule_settings( $product_id ) {
        return array(
            'enable_schedule'  => get_post_meta( $product_id, '_vsbbm_enable_schedule', true ) ?: 'no',
            'min_days_advance' => get_post_meta( $product_id, '_vsbbm_min_days_advance', true ) ?: 0,
            'max_days_advance' => get_post_meta( $product_id, '_vsbbm_max_days_advance', true ) ?: 365,
            'departure_time'   => get_post_meta( $product_id, '_vsbbm_departure_time', true ) ?: '12:00',
        );
    }

    /**
     * Display Seat Selection on Frontend.
     */
    public static function display_seat_selection() {
        global $product;

        if ( ! self::is_seat_booking_enabled( $product->get_id() ) ) {
            return;
        }

        $settings          = self::get_product_settings( $product->get_id() );
        $schedule_settings = self::get_schedule_settings( $product->get_id() );
        $seat_numbers      = self::get_seat_numbers( $product->get_id() );

        if ( empty( $seat_numbers ) ) {
            echo '<p class="vsbbm-warning">' . esc_html__( 'Seat layout is not defined for this product.', 'vs-bus-booking-manager' ) . '</p>';
            return;
        }
        ?>
        <div id="vsbbm-seat-selector" class="vsbbm-seat-selector-container"
            data-product-id="<?php echo esc_attr( $product->get_id() ); ?>"
            data-rows="<?php echo esc_attr( $settings['rows'] ); ?>"
            data-cols="<?php echo esc_attr( $settings['columns'] ); ?>"
            data-type="<?php echo esc_attr( $settings['type'] ); ?>"
            data-price="<?php echo esc_attr( $product->get_price() ); ?>"
            data-schedule-enabled="<?php echo esc_attr( $schedule_settings['enable_schedule'] ); ?>">

            <?php if ( 'yes' === $schedule_settings['enable_schedule'] ) : ?>
            <div class="vsbbm-departure-schedule-picker">
                <h3><?php esc_html_e( 'Select Departure Date', 'vs-bus-booking-manager' ); ?></h3>
                <input type="text" id="vsbbm_departure_date" placeholder="<?php esc_attr_e( 'Date', 'vs-bus-booking-manager' ); ?>" readonly />
                <input type="text" id="vsbbm_departure_time" value="<?php echo esc_attr( $schedule_settings['departure_time'] ); ?>" readonly />
                <p id="vsbbm-date-warning" class="vsbbm-warning" style="display: none;"></p>
                <input type="hidden" id="vsbbm_departure_timestamp" />
            </div>
            <?php endif; ?>

            <div class="vsbbm-seat-layout-display">
                <h3><?php esc_html_e( 'Select Seats', 'vs-bus-booking-manager' ); ?></h3>
                <p class="vsbbm-seat-info-message" style="display: none;"><?php esc_html_e( 'Please select a date first.', 'vs-bus-booking-manager' ); ?></p>
                <div id="vsbbm-seat-map">
                    <div class="vsbbm-loading-overlay">
                        <div class="vsbbm-spinner"></div>
                        <p><?php esc_html_e( 'Loading seat map...', 'vs-bus-booking-manager' ); ?></p>
                    </div>
                </div>
                <div class="vsbbm-legend">
                    <span><i class="vsbbm-seat available"></i> <?php esc_html_e( 'Available', 'vs-bus-booking-manager' ); ?></span>
                    <span><i class="vsbbm-seat selected"></i> <?php esc_html_e( 'Your Selection', 'vs-bus-booking-manager' ); ?></span>
                    <span><i class="vsbbm-seat reserved"></i> <?php esc_html_e( 'Reserved', 'vs-bus-booking-manager' ); ?></span>
                </div>
            </div>

            <div class="vsbbm-passenger-data-form" style="display: none;">
                <h3><?php esc_html_e( 'Passenger Details', 'vs-bus-booking-manager' ); ?></h3>
                <div id="vsbbm-passenger-fields"></div>
            </div>

            <div class="vsbbm-summary-bar">
                <span class="vsbbm-summary-item">
                    <?php esc_html_e( 'Selected:', 'vs-bus-booking-manager' ); ?> <strong id="vsbbm-selected-seats-count">0</strong>
                </span>
                <span class="vsbbm-summary-item">
                    <?php esc_html_e( 'Total:', 'vs-bus-booking-manager' ); ?> <strong id="vsbbm-total-price"><?php echo wp_kses_post( wc_price( 0 ) ); ?></strong>
                </span>
                <button type="button" id="vsbbm-add-to-cart-button" class="single_add_to_cart_button button alt" disabled>
                    <?php esc_html_e( 'Book & Add to Cart', 'vs-bus-booking-manager' ); ?>
                </button>
            </div>
        </div>
        <?php
    }

    /**
     * Check if seat booking is enabled.
     *
     * @param int $product_id Product ID.
     * @return bool
     */
    public static function is_seat_booking_enabled( $product_id ) {
        return 'yes' === get_post_meta( $product_id, '_vsbbm_enable_seat_booking', true );
    }

    /**
     * Get Product Settings.
     *
     * @param int $product_id Product ID.
     * @return array
     */
    public static function get_product_settings( $product_id ) {
        return array(
            'rows'    => (int) get_post_meta( $product_id, '_vsbbm_bus_rows', true ) ?: 10,
            'columns' => (int) get_post_meta( $product_id, '_vsbbm_bus_columns', true ) ?: 2,
            'type'    => get_post_meta( $product_id, '_vsbbm_bus_type', true ) ?: '2x2',
        );
    }

    /**
     * Get defined seat numbers.
     *
     * @param int $product_id Product ID.
     * @return array
     */
    public static function get_seat_numbers( $product_id ) {
        $seats = get_post_meta( $product_id, '_vsbbm_seat_numbers', true );
        return is_array( $seats ) ? $seats : array();
    }

    /**
     * Get reserved seats for a timestamp.
     *
     * @param int $product_id Product ID.
     * @param int|null $departure_timestamp Departure timestamp.
     * @return array
     */
    public static function get_reserved_seats( $product_id, $departure_timestamp = null ) {
        if ( ! class_exists( 'VSBBM_Seat_Reservations' ) ) {
            return array();
        }

        if ( empty( $departure_timestamp ) ) {
            $settings            = self::get_schedule_settings( $product_id );
            list( $hour, $minute ) = explode( ':', $settings['departure_time'] );
            $current_date        = gmdate( 'Y-m-d' );
            $departure_timestamp = strtotime( "$current_date $hour:$minute" );
        }

        return VSBBM_Seat_Reservations::get_reserved_seats_by_product_and_time( $product_id, $departure_timestamp );
    }

    // --- AJAX HANDLERS ---

    public static function register_ajax_handlers() {
        add_action( 'wp_ajax_vsbbm_get_passenger_fields', array( __CLASS__, 'get_passenger_fields_ajax' ) );
        add_action( 'wp_ajax_nopriv_vsbbm_get_passenger_fields', array( __CLASS__, 'get_passenger_fields_ajax' ) );

        add_action( 'wp_ajax_vsbbm_get_reserved_seats', array( __CLASS__, 'get_reserved_seats_ajax' ) );
        add_action( 'wp_ajax_nopriv_vsbbm_get_reserved_seats', array( __CLASS__, 'get_reserved_seats_ajax' ) );

        add_action( 'wp_ajax_vsbbm_add_to_cart', array( __CLASS__, 'add_to_cart_ajax' ) );
        add_action( 'wp_ajax_nopriv_vsbbm_add_to_cart', array( __CLASS__, 'add_to_cart_ajax' ) );
    }

    public static function get_reserved_seats_ajax() {
        check_ajax_referer( 'vsbbm_seat_nonce', 'nonce' );

        $product_id          = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
        $departure_timestamp = isset( $_POST['departure_timestamp'] ) ? absint( $_POST['departure_timestamp'] ) : 0;

        if ( ! $product_id || ! $departure_timestamp ) {
            wp_send_json_error( array( 'message' => __( 'Invalid product or date.', 'vs-bus-booking-manager' ) ) );
        }

        if ( class_exists( 'VSBBM_Cache_Manager' ) ) {
            $cache_key   = 'reserved_seats_' . $product_id . '_' . $departure_timestamp;
            $cached_data = VSBBM_Cache_Manager::get_instance()->get_cache( $cache_key );

            if ( false !== $cached_data ) {
                VSBBM_Cache_Manager::get_instance()->increment_cache_hit();
                wp_send_json_success( $cached_data );
            }
            VSBBM_Cache_Manager::get_instance()->increment_total_requests();
        }

        $reserved_seats = self::get_reserved_seats( $product_id, $departure_timestamp );

        if ( class_exists( 'VSBBM_Seat_Reservations' ) ) {
            $temp_reserved_seats = VSBBM_Seat_Reservations::get_temp_reserved_seats_for_product_and_time( $product_id, $departure_timestamp );
            $reserved_seats      = array_merge( $reserved_seats, $temp_reserved_seats );
        }

        $all_seats = self::get_seat_numbers( $product_id );

        $response_data = array(
            'reserved'  => array_keys( $reserved_seats ),
            'all_seats' => $all_seats,
        );

        if ( class_exists( 'VSBBM_Cache_Manager' ) ) {
            VSBBM_Cache_Manager::get_instance()->set_cache( $cache_key, $response_data, 60 );
        }

        wp_send_json_success( $response_data );
    }

    public static function get_passenger_fields_ajax() {
        // Public data, but check referer loosely if needed.
        // check_ajax_referer( 'vsbbm_seat_nonce', 'nonce' ); // Optional for pure read-only public config

        $fields = apply_filters(
            'vsbbm_passenger_fields',
            array(
                array(
                    'type'        => 'text',
                    'label'       => __( 'Full Name', 'vs-bus-booking-manager' ),
                    'required'    => true,
                    'placeholder' => __( 'Full Name', 'vs-bus-booking-manager' ),
                ),
                array(
                    'type'        => 'text',
                    'label'       => __( 'National ID', 'vs-bus-booking-manager' ),
                    'required'    => true,
                    'placeholder' => __( '10-digit National ID', 'vs-bus-booking-manager' ),
                ),
                array(
                    'type'        => 'tel',
                    'label'       => __( 'Phone Number', 'vs-bus-booking-manager' ),
                    'required'    => false,
                    'placeholder' => '09xxxxxxxxx',
                ),
            )
        );

        wp_send_json_success( $fields );
    }

    public static function add_to_cart_ajax() {
        check_ajax_referer( 'vsbbm_seat_nonce', 'nonce' );

        $product_id          = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
        $departure_timestamp = isset( $_POST['departure_timestamp'] ) ? absint( $_POST['departure_timestamp'] ) : 0;
        
        // Fix: Properly decode JSON
        $selected_seats  = isset( $_POST['selected_seats'] ) ? json_decode( wp_unslash( $_POST['selected_seats'] ), true ) : array();
        $passengers_data = isset( $_POST['passengers_data'] ) ? json_decode( wp_unslash( $_POST['passengers_data'] ), true ) : array();

        if ( ! $product_id || empty( $selected_seats ) || count( $selected_seats ) !== count( $passengers_data ) ) {
            wp_send_json_error( array( 'message' => __( 'Please select seats and fill all passenger details.', 'vs-bus-booking-manager' ) ) );
        }

        if ( ! class_exists( 'VSBBM_Seat_Reservations' ) ) {
            wp_send_json_error( array( 'message' => __( 'Booking module not loaded.', 'vs-bus-booking-manager' ) ) );
        }

        // Reserve Logic
        $reservation_keys = array();
        foreach ( $selected_seats as $seat_number ) {
            $seat_number = sanitize_text_field( $seat_number );
            
            $reservation_key = VSBBM_Seat_Reservations::reserve_seat( $product_id, $seat_number, $departure_timestamp );
            
            if ( is_wp_error( $reservation_key ) ) {
                if ( ! empty( $reservation_keys ) ) {
                    VSBBM_Seat_Reservations::cancel_reservations_by_keys( $reservation_keys );
                }
                wp_send_json_error( array( 'message' => sprintf( __( 'Seat %s is already reserved.', 'vs-bus-booking-manager' ), $seat_number ) ) );
            }
            $reservation_keys[] = $reservation_key;
        }

        // Add to Cart
        $cart_item_data = array(
            'vsbbm_reservation_keys'    => $reservation_keys,
            'vsbbm_departure_timestamp' => $departure_timestamp,
            'vsbbm_seats'               => $selected_seats,
            'vsbbm_passengers'          => $passengers_data, // Should be sanitized further in booking handler
        );

        $add_to_cart_result = WC()->cart->add_to_cart(
            $product_id,
            count( $selected_seats ),
            0,
            array(),
            $cart_item_data
        );

        if ( $add_to_cart_result ) {
            wp_send_json_success(
                array(
                    'message'      => __( 'Seats reserved and added to cart.', 'vs-bus-booking-manager' ),
                    'cart_url'     => wc_get_cart_url(),
                    'checkout_url' => wc_get_checkout_url(),
                )
            );
        } else {
            VSBBM_Seat_Reservations::cancel_reservations_by_keys( $reservation_keys );
            wp_send_json_error( array( 'message' => __( 'Error adding to cart.', 'vs-bus-booking-manager' ) ) );
        }
    }
}