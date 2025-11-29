<?php
/**
 * Class VSBBM_Admin_Interface
 *
 * Handles all admin-facing functionality including dashboards, settings,
 * reports, and management pages.
 *
 * @package VSBBM
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

class VSBBM_Admin_Interface {

    /**
     * Singleton instance
     *
     * @var VSBBM_Admin_Interface|null
     */
    private static $instance = null;

    /**
     * Get the singleton instance.
     *
     * @return VSBBM_Admin_Interface
     */
    public static function init() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        // Admin Menus
        add_action( 'admin_menu', array( $this, 'add_admin_menus' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

        // AJAX Handlers
        add_action( 'wp_ajax_vsbbm_get_booking_details', array( $this, 'get_booking_details_ajax' ) );
        add_action( 'wp_ajax_vsbbm_update_booking_status', array( $this, 'update_booking_status_ajax' ) );
        add_action( 'wp_ajax_vsbbm_export_bookings', array( $this, 'export_bookings_ajax' ) );
        add_action( 'wp_ajax_vsbbm_use_ticket', array( $this, 'use_ticket_ajax' ) );
        add_action( 'wp_ajax_vsbbm_clear_cache', array( $this, 'clear_cache_ajax' ) );

        // Order Meta Display
        add_action( 'woocommerce_before_order_itemmeta', array( $this, 'display_order_passenger_info' ), 10, 3 );

        // Passenger Fields Settings
        add_action( 'admin_menu', array( $this, 'add_passenger_fields_settings' ) );
        add_action( 'admin_init', array( $this, 'register_passenger_fields_settings' ) );

        // Cache Settings Save Handler
        add_action( 'admin_init', array( $this, 'handle_cache_settings_save' ) );
    }

    /**
     * Add admin menu pages.
     */
    public function add_admin_menus() {
        // Main Menu
        add_menu_page(
            __( 'Bus Booking Manager', 'vs-bus-booking-manager' ),
            __( 'Bus Booking', 'vs-bus-booking-manager' ),
            'manage_options',
            'vsbbm-dashboard',
            array( $this, 'render_dashboard' ),
            'dashicons-bus',
            30
        );

        // Submenus
        add_submenu_page( 'vsbbm-dashboard', __( 'Dashboard', 'vs-bus-booking-manager' ), __( 'Dashboard', 'vs-bus-booking-manager' ), 'manage_options', 'vsbbm-dashboard', array( $this, 'render_dashboard' ) );
        add_submenu_page( 'vsbbm-dashboard', __( 'All Bookings', 'vs-bus-booking-manager' ), __( 'All Bookings', 'vs-bus-booking-manager' ), 'manage_options', 'vsbbm-bookings', array( $this, 'render_bookings_page' ) );
        add_submenu_page( 'vsbbm-dashboard', __( 'Reports', 'vs-bus-booking-manager' ), __( 'Reports', 'vs-bus-booking-manager' ), 'manage_options', 'vsbbm-reports', array( $this, 'render_reports_page' ) );
        add_submenu_page( 'vsbbm-dashboard', __( 'Cache & Optimization', 'vs-bus-booking-manager' ), __( 'Cache & Optimization', 'vs-bus-booking-manager' ), 'manage_options', 'vsbbm-cache', array( $this, 'render_cache_page' ) );
        add_submenu_page( 'vsbbm-dashboard', __( 'API Settings', 'vs-bus-booking-manager' ), __( 'API Settings', 'vs-bus-booking-manager' ), 'manage_options', 'vsbbm-api-settings', array( $this, 'render_api_settings_page' ) );
        add_submenu_page( 'vsbbm-dashboard', __( 'License', 'vs-bus-booking-manager' ), __( 'License', 'vs-bus-booking-manager' ), 'manage_options', 'vsbbm-license', array( $this, 'render_license_page' ) );
        add_submenu_page( 'vsbbm-dashboard', __( 'Blacklist', 'vs-bus-booking-manager' ), __( 'Blacklist', 'vs-bus-booking-manager' ), 'manage_options', 'vsbbm-blacklist', array( $this, 'render_blacklist_page' ) );
        add_submenu_page( 'vsbbm-dashboard', __( 'Seat Reservations', 'vs-bus-booking-manager' ), __( 'Reservations', 'vs-bus-booking-manager' ), 'manage_options', 'vsbbm-reservations', array( $this, 'render_reservations_page' ) );
        add_submenu_page( 'vsbbm-dashboard', __( 'Email Settings', 'vs-bus-booking-manager' ), __( 'Email Settings', 'vs-bus-booking-manager' ), 'manage_options', 'vsbbm-email-settings', array( $this, 'render_email_settings_page' ) );
        add_submenu_page( 'vsbbm-dashboard', __( 'SMS Settings', 'vs-bus-booking-manager' ), __( 'SMS Settings', 'vs-bus-booking-manager' ), 'manage_options', 'vsbbm-sms-settings', array( $this, 'render_sms_settings_page' ) );
        add_submenu_page( 'vsbbm-dashboard', __( 'General Settings', 'vs-bus-booking-manager' ), __( 'Settings', 'vs-bus-booking-manager' ), 'manage_options', 'vsbbm-settings', array( $this, 'render_settings_page' ) );
        add_submenu_page( 'vsbbm-dashboard', __( 'Ticket Manager', 'vs-bus-booking-manager' ), __( 'Tickets', 'vs-bus-booking-manager' ), 'manage_options', 'vsbbm-tickets', array( $this, 'render_tickets_page' ) );
        add_submenu_page( 'vsbbm-dashboard', __( 'Performance Test', 'vs-bus-booking-manager' ), __( 'Performance Test', 'vs-bus-booking-manager' ), 'manage_options', 'vsbbm-test', array( $this, 'render_test_page' ) );
    }

    /**
     * Add Passenger Fields Submenu.
     */
    public function add_passenger_fields_settings() {
        add_submenu_page(
            'vsbbm-dashboard',
            __( 'Passenger Fields', 'vs-bus-booking-manager' ),
            __( 'Passenger Fields', 'vs-bus-booking-manager' ),
            'manage_options',
            'vsbbm-passenger-fields',
            array( $this, 'render_passenger_fields_settings' )
        );
    }

    /**
     * Register Passenger Fields Setting.
     */
    public function register_passenger_fields_settings() {
        register_setting( 'vsbbm_passenger_fields', 'vsbbm_passenger_fields', array(
            'sanitize_callback' => array( $this, 'sanitize_passenger_fields' )
        ));
    }

    /**
     * Enqueue Admin Assets.
     */
    public function enqueue_admin_scripts( $hook ) {
        // Load only on plugin pages
        if ( strpos( $hook, 'vsbbm-' ) !== false ) {
            wp_enqueue_style( 'vsbbm-admin', VSBBM_PLUGIN_URL . 'assets/css/admin.css', array(), VSBBM_VERSION );
            wp_enqueue_script( 'vsbbm-admin', VSBBM_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery' ), VSBBM_VERSION, true );
            
            // External Libs (Suggest bundling these locally in Phase 2)
            wp_enqueue_script( 'chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), null, true );
            wp_enqueue_style( 'data-tables', 'https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css' );
            wp_enqueue_script( 'data-tables', 'https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js', array( 'jquery' ), null, true );
            wp_enqueue_script( 'data-tables-bootstrap', 'https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js', array( 'data-tables' ), null, true );
            
            wp_localize_script( 'vsbbm-admin', 'vsbbm_admin', array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'vsbbm_admin_nonce' ),
                'i18n'     => array(
                    'confirm_delete' => __( 'Are you sure you want to delete this booking?', 'vs-bus-booking-manager' ),
                    'loading'        => __( 'Loading...', 'vs-bus-booking-manager' ),
                    'exporting'      => __( 'Preparing export...', 'vs-bus-booking-manager' )
                )
            ));
        }
    }

    // --- Render Methods (Ideally these should load template files) ---

    public function render_dashboard() {
        $stats           = $this->get_dashboard_stats();
        $recent_bookings = $this->get_recent_bookings( 10 );
        $weekly_data     = $this->get_weekly_stats();

        // Using include to keep the file size manageable and separation of concerns
        if ( file_exists( VSBBM_PLUGIN_PATH . 'templates/admin/dashboard.php' ) ) {
            include VSBBM_PLUGIN_PATH . 'templates/admin/dashboard.php';
        } else {
            echo '<div class="wrap"><h1>' . esc_html__( 'Dashboard Template Missing', 'vs-bus-booking-manager' ) . '</h1></div>';
        }
    }

    public function render_bookings_page() {
        $this->process_booking_actions();
        $this->process_bulk_booking_actions();

        $filters = array(
            'status'     => isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '',
            'date_from'  => isset( $_GET['date_from'] ) ? sanitize_text_field( $_GET['date_from'] ) : '',
            'date_to'    => isset( $_GET['date_to'] ) ? sanitize_text_field( $_GET['date_to'] ) : '',
            'search'     => isset( $_GET['search'] ) ? sanitize_text_field( $_GET['search'] ) : '',
            'product_id' => isset( $_GET['product_id'] ) ? absint( $_GET['product_id'] ) : ''
        );

        $bookings = $this->get_all_bookings( $filters );
        $statuses = $this->get_booking_statuses();
        $products = $this->get_bus_products();

        if ( file_exists( VSBBM_PLUGIN_PATH . 'templates/admin/bookings.php' ) ) {
            include VSBBM_PLUGIN_PATH . 'templates/admin/bookings.php';
        }
    }

    public function render_reports_page() {
        $report_type = isset( $_GET['report_type'] ) ? sanitize_text_field( $_GET['report_type'] ) : 'daily';
        $report_data = $this->generate_report( $report_type );
        
        if ( file_exists( VSBBM_PLUGIN_PATH . 'templates/admin/reports.php' ) ) {
            include VSBBM_PLUGIN_PATH . 'templates/admin/reports.php';
        }
    }

    public function render_reservations_page() {
        $this->process_reservation_actions();

        $filters = array(
            'status'     => isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '',
            'product_id' => isset( $_GET['product_id'] ) ? absint( $_GET['product_id'] ) : '',
            'date_from'  => isset( $_GET['date_from'] ) ? sanitize_text_field( $_GET['date_from'] ) : '',
            'date_to'    => isset( $_GET['date_to'] ) ? sanitize_text_field( $_GET['date_to'] ) : ''
        );

        $reservations = $this->get_reservations( $filters );
        
        // Status translations
        $statuses = array(
            'reserved'  => __( 'Reserved', 'vs-bus-booking-manager' ),
            'confirmed' => __( 'Confirmed', 'vs-bus-booking-manager' ),
            'cancelled' => __( 'Cancelled', 'vs-bus-booking-manager' ),
            'expired'   => __( 'Expired', 'vs-bus-booking-manager' )
        );

        if ( file_exists( VSBBM_PLUGIN_PATH . 'templates/admin/reservations.php' ) ) {
            include VSBBM_PLUGIN_PATH . 'templates/admin/reservations.php';
        }
    }

    public function render_settings_page() {
        if ( isset( $_POST['vsbbm_save_settings'] ) ) {
            $this->save_settings();
        }
        $settings = $this->get_settings();
        if ( file_exists( VSBBM_PLUGIN_PATH . 'templates/admin/settings.php' ) ) {
            include VSBBM_PLUGIN_PATH . 'templates/admin/settings.php';
        }
    }

    public function render_blacklist_page() {
        if ( class_exists( 'VSBBM_Blacklist' ) ) {
            VSBBM_Blacklist::render_admin_page();
        }
    }

    /**
     * Render Email Settings Page.
     */
    public function render_email_settings_page() {
        if ( isset( $_POST['vsbbm_save_email_settings'] ) ) {
            $this->save_email_settings();
        }

        $settings = $this->get_email_settings();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Email Notification Settings', 'vs-bus-booking-manager' ); ?></h1>
            <div class="notice notice-info">
                <p><?php esc_html_e( 'Configure automated email notifications for bookings and order changes.', 'vs-bus-booking-manager' ); ?></p>
            </div>

            <form method="post" action="">
                <?php wp_nonce_field( 'vsbbm_save_email_settings' ); ?>

                <div class="card vsbbm-card">
                    <h3><?php esc_html_e( 'General Settings', 'vs-bus-booking-manager' ); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="from_name"><?php esc_html_e( 'From Name', 'vs-bus-booking-manager' ); ?></label></th>
                            <td>
                                <input type="text" name="from_name" id="from_name" value="<?php echo esc_attr( $settings['from_name'] ); ?>" class="regular-text" required>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="from_email"><?php esc_html_e( 'From Email', 'vs-bus-booking-manager' ); ?></label></th>
                            <td>
                                <input type="email" name="from_email" id="from_email" value="<?php echo esc_attr( $settings['from_email'] ); ?>" class="regular-text" required>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="admin_email"><?php esc_html_e( 'Admin Email', 'vs-bus-booking-manager' ); ?></label></th>
                            <td>
                                <input type="email" name="admin_email" id="admin_email" value="<?php echo esc_attr( $settings['admin_email'] ); ?>" class="regular-text" required>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div class="card vsbbm-card">
                    <h3><?php esc_html_e( 'Customer Emails', 'vs-bus-booking-manager' ); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Booking Confirmation', 'vs-bus-booking-manager' ); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="enable_customer_confirmation_email" value="1" <?php checked( $settings['enable_customer_confirmation_email'], true ); ?>>
                                    <?php esc_html_e( 'Send confirmation email after order completion.', 'vs-bus-booking-manager' ); ?>
                                </label>
                            </td>
                        </tr>
                         <tr>
                            <th scope="row"><?php esc_html_e( 'Cancellation', 'vs-bus-booking-manager' ); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="enable_customer_cancellation_email" value="1" <?php checked( $settings['enable_customer_cancellation_email'], true ); ?>>
                                    <?php esc_html_e( 'Send cancellation notification.', 'vs-bus-booking-manager' ); ?>
                                </label>
                            </td>
                        </tr>
                         <!-- Add other fields similarly with esc_html_e -->
                    </table>
                </div>

                <p class="submit">
                    <input type="submit" name="vsbbm_save_email_settings" class="button button-primary" value="<?php esc_attr_e( 'Save Settings', 'vs-bus-booking-manager' ); ?>">
                </p>
            </form>
        </div>
        <style>.vsbbm-card { background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 20px; margin-bottom: 20px; max-width: 800px; }</style>
        <?php
    }

    // --- Helper Methods & Logic ---

    /**
     * Get bookings statistics for dashboard.
     */
    private function get_dashboard_stats() {
        global $wpdb;

        $today      = current_time( 'Y-m-d' );
        $week_start = date( 'Y-m-d', strtotime( 'monday this week', current_time( 'timestamp' ) ) );

        // Cache this expensive query
        $cache_key = 'vsbbm_dashboard_stats';
        $stats     = wp_cache_get( $cache_key );

        if ( false === $stats ) {
            $stats = array(
                'total_bookings'   => $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'shop_order' AND post_status IN ('wc-completed', 'wc-processing', 'wc-on-hold')" ),
                'today_bookings'   => $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'shop_order' AND post_status IN ('wc-completed', 'wc-processing', 'wc-on-hold') AND DATE(post_date) = %s", $today ) ),
                'total_revenue'    => $this->calculate_total_revenue(),
                'weekly_revenue'   => $this->calculate_revenue_period( $week_start, $today ),
                'total_passengers' => $this->calculate_total_passengers(),
                'occupancy_rate'   => $this->calculate_occupancy_rate()
            );
            wp_cache_set( $cache_key, $stats, '', 3600 ); // Cache for 1 hour
        }

        return $stats;
    }

    /**
     * Fetch all bookings with filters.
     */
    private function get_all_bookings( $filters = array() ) {
        global $wpdb;

        $where_parts  = array();
        $where_values = array();

        if ( ! empty( $filters['status'] ) ) {
            $status         = str_replace( 'wc-', '', $filters['status'] );
            $where_parts[]  = "p.post_status = %s";
            $where_values[] = 'wc-' . $status;
        } else {
            $where_parts[] = "p.post_status IN ('wc-completed', 'wc-processing', 'wc-on-hold', 'wc-pending', 'wc-cancelled')";
        }

        if ( ! empty( $filters['product_id'] ) ) {
            $where_parts[]  = "EXISTS ( SELECT 1 FROM {$wpdb->prefix}woocommerce_order_items oi INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim ON oi.order_item_id = oim.order_item_id WHERE oi.order_id = p.ID AND oim.meta_key = '_product_id' AND oim.meta_value = %d )";
            $where_values[] = $filters['product_id'];
        }

        if ( ! empty( $filters['date_from'] ) ) {
            $where_parts[]  = "DATE(p.post_date) >= %s";
            $where_values[] = $filters['date_from'];
        }

        if ( ! empty( $filters['date_to'] ) ) {
            $where_parts[]  = "DATE(p.post_date) <= %s";
            $where_values[] = $filters['date_to'];
        }

        if ( ! empty( $filters['search'] ) ) {
            $search         = '%' . $wpdb->esc_like( $filters['search'] ) . '%';
            $where_parts[]  = "(p.ID LIKE %s OR pm.meta_value LIKE %s OR u.display_name LIKE %s OR u.user_email LIKE %s)";
            array_push( $where_values, $search, $search, $search, $search );
        }

        $where_clause = ! empty( $where_parts ) ? 'WHERE ' . implode( ' AND ', $where_parts ) : '';

        // Removed SQL_CALC_FOUND_ROWS for performance
        $query = "SELECT p.ID, p.post_date, p.post_status, p.post_title, u.display_name, u.user_email, pm.meta_value as order_total 
                  FROM {$wpdb->posts} p 
                  LEFT JOIN {$wpdb->users} u ON p.post_author = u.ID 
                  LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_order_total' 
                  {$where_clause} 
                  ORDER BY p.post_date DESC LIMIT 1000";

        if ( ! empty( $where_values ) ) {
            $query = $wpdb->prepare( $query, $where_values );
        }

        $bookings = $wpdb->get_results( $query );

        // Sanitize objects
        foreach ( $bookings as $booking ) {
            $booking->post_status = str_replace( 'wc-', '', $booking->post_status );
            $booking->order_total = $booking->order_total ?: '0';
        }

        return $bookings;
    }

    /**
     * AJAX: Get Booking Details.
     */
    public function get_booking_details_ajax() {
        check_ajax_referer( 'vsbbm_admin_nonce', 'nonce' );

        $booking_id = isset( $_POST['booking_id'] ) ? absint( $_POST['booking_id'] ) : 0;
        $order      = wc_get_order( $booking_id );

        if ( ! $order ) {
            wp_send_json_error( __( 'Order not found.', 'vs-bus-booking-manager' ) );
        }

        $passengers = array();
        foreach ( $order->get_items() as $item ) {
            foreach ( $item->get_meta_data() as $meta ) {
                if ( false !== strpos( $meta->key, 'مسافر' ) || false !== strpos( $meta->key, 'Passenger' ) ) {
                    $passengers[] = $meta->value;
                }
            }
        }

        wp_send_json_success( array(
            'id'             => $order->get_id(),
            'date'           => $order->get_date_created()->date_i18n( 'Y-m-d H:i:s' ),
            'status'         => wc_get_order_status_name( $order->get_status() ),
            'customer_name'  => $order->get_formatted_billing_full_name(),
            'customer_email' => $order->get_billing_email(),
            'customer_phone' => $order->get_billing_phone(),
            'passengers'     => $passengers,
            'total_amount'   => $order->get_formatted_order_total(),
            'payment_method' => $order->get_payment_method_title()
        ));
    }

    /**
     * AJAX: Export Bookings CSV.
     */
    public function export_bookings_ajax() {
        check_ajax_referer( 'vsbbm_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Permission denied.', 'vs-bus-booking-manager' ) );
        }

        $filters = array(
            'status'    => isset( $_POST['status'] ) ? sanitize_text_field( $_POST['status'] ) : '',
            'date_from' => isset( $_POST['date_from'] ) ? sanitize_text_field( $_POST['date_from'] ) : '',
            'date_to'   => isset( $_POST['date_to'] ) ? sanitize_text_field( $_POST['date_to'] ) : ''
        );

        $bookings = $this->get_all_bookings( $filters );

        // Ensure no output buffer issues
        if ( ob_get_length() ) {
            ob_end_clean();
        }

        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=bookings-export-' . date( 'Y-m-d' ) . '.csv' );

        $output = fopen( 'php://output', 'w' );
        // Add BOM for Excel UTF-8 compatibility
        fwrite( $output, "\xEF\xBB\xBF" );

        fputcsv( $output, array(
            __( 'Order ID', 'vs-bus-booking-manager' ),
            __( 'Date', 'vs-bus-booking-manager' ),
            __( 'Customer', 'vs-bus-booking-manager' ),
            __( 'Email', 'vs-bus-booking-manager' ),
            __( 'Total', 'vs-bus-booking-manager' ),
            __( 'Status', 'vs-bus-booking-manager' )
        ));

        foreach ( $bookings as $booking ) {
            fputcsv( $output, array(
                $booking->ID,
                $booking->post_date,
                $booking->display_name,
                $booking->user_email,
                $booking->order_total,
                wc_get_order_status_name( $booking->post_status )
            ));
        }

        fclose( $output );
        exit;
    }

    /**
     * Display passenger info in order edit page.
     */
    public function display_order_passenger_info( $item_id, $item, $product ) {
        if ( ! $product ) return;

        if ( ! VSBBM_Seat_Manager::is_seat_booking_enabled( $product->get_id() ) ) {
            return;
        }

        echo '<div class="vsbbm-order-passengers" style="margin-top: 10px; padding: 10px; background: #f9f9f9; border-radius: 5px;">';
        echo '<strong>' . esc_html__( 'Passenger Details:', 'vs-bus-booking-manager' ) . '</strong><br>';

        $passenger_meta = $item->get_meta_data();

        foreach ( $passenger_meta as $meta ) {
            // Check for both English and Persian keys to be safe
            if ( false !== strpos( $meta->key, 'مسافر' ) || false !== strpos( $meta->key, 'Passenger' ) ) {
                echo '<div style="margin: 5px 0; padding: 5px; background: white; border-radius: 3px;">';
                echo '<strong>' . esc_html( $meta->key ) . ':</strong> ' . esc_html( $meta->value );
                echo '</div>';
            }
        }
        echo '</div>';
    }

    /**
     * Sanitize passenger fields settings.
     */
    public function sanitize_passenger_fields( $input ) {
        if ( ! is_array( $input ) ) {
            return array();
        }

        $sanitized         = array();
        $has_national_code = false;

        foreach ( $input as $field ) {
            $sanitized_field = array(
                'label'       => sanitize_text_field( $field['label'] ?? '' ),
                'placeholder' => sanitize_text_field( $field['placeholder'] ?? '' ),
                'type'        => sanitize_key( $field['type'] ?? 'text' ),
                'required'    => isset( $field['required'] ),
                'locked'      => false,
                'options'     => isset( $field['options'] ) ? sanitize_text_field( $field['options'] ) : ''
            );

            // National Code Logic (Must remain locked)
            if ( $sanitized_field['label'] === 'کد ملی' || $sanitized_field['label'] === 'National ID' ) {
                $has_national_code           = true;
                $sanitized_field['label']    = 'کد ملی'; // Force standard name
                $sanitized_field['required'] = true;
                $sanitized_field['locked']   = true;
            }

            $sanitized[] = $sanitized_field;
        }

        // Ensure National Code exists
        if ( ! $has_national_code ) {
            array_unshift( $sanitized, array(
                'type'        => 'text',
                'label'       => 'کد ملی',
                'required'    => true,
                'placeholder' => __( 'National ID (10 digits)', 'vs-bus-booking-manager' ),
                'locked'      => true,
                'options'     => ''
            ));
        }

        delete_transient( 'vsbbm_passenger_fields' );
        return $sanitized;
    }

    /**
     * Render Passenger Fields Settings Page.
     */
    public function render_passenger_fields_settings() {
        $fields = get_option( 'vsbbm_passenger_fields', array(
            array( 'type' => 'text', 'label' => 'نام کامل', 'required' => true, 'placeholder' => 'نام و نام خانوادگی', 'locked' => false ),
            array( 'type' => 'text', 'label' => 'کد ملی', 'required' => true, 'placeholder' => 'کد ملی ۱۰ رقمی', 'locked' => true ),
            array( 'type' => 'tel', 'label' => 'شماره تماس', 'required' => true, 'placeholder' => '09xxxxxxxxx', 'locked' => false ),
        ));
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Passenger Fields Configuration', 'vs-bus-booking-manager' ); ?></h1>
            <div class="notice notice-info">
                <p><?php esc_html_e( 'Note: The "National ID" field is locked because the blacklist system depends on it.', 'vs-bus-booking-manager' ); ?></p>
            </div>
            
            <form method="post" action="options.php">
                <?php settings_fields( 'vsbbm_passenger_fields' ); ?>
                <div class="card vsbbm-card">
                    <div id="vsbbm-fields-container">
                        <?php foreach ( $fields as $index => $field ) : 
                            $is_locked = ! empty( $field['locked'] );
                        ?>
                        <div class="field-group <?php echo $is_locked ? 'locked-field' : ''; ?>" style="background: <?php echo $is_locked ? '#fff3cd' : '#f9f9f9'; ?>; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid <?php echo $is_locked ? '#ffc107' : '#0073aa'; ?>;">
                            <div style="display: grid; grid-template-columns: 2fr 2fr 1fr 1fr <?php echo $is_locked ? '0.5fr' : '1fr'; ?>; gap: 10px; align-items: end;">
                                <div>
                                    <label><?php esc_html_e( 'Label', 'vs-bus-booking-manager' ); ?></label>
                                    <input type="text" name="vsbbm_passenger_fields[<?php echo $index; ?>][label]" value="<?php echo esc_attr( $field['label'] ); ?>" style="width: 100%;" <?php echo $is_locked ? 'readonly' : 'required'; ?>>
                                </div>
                                <div>
                                    <label><?php esc_html_e( 'Placeholder', 'vs-bus-booking-manager' ); ?></label>
                                    <input type="text" name="vsbbm_passenger_fields[<?php echo $index; ?>][placeholder]" value="<?php echo esc_attr( $field['placeholder'] ); ?>" style="width: 100%;" <?php echo $is_locked ? 'readonly' : ''; ?>>
                                </div>
                                <div>
                                    <label><?php esc_html_e( 'Type', 'vs-bus-booking-manager' ); ?></label>
                                    <select name="vsbbm_passenger_fields[<?php echo $index; ?>][type]" style="width: 100%;" <?php echo $is_locked ? 'disabled' : ''; ?>>
                                        <option value="text" <?php selected( $field['type'], 'text' ); ?>><?php esc_html_e( 'Text', 'vs-bus-booking-manager' ); ?></option>
                                        <option value="tel" <?php selected( $field['type'], 'tel' ); ?>><?php esc_html_e( 'Phone', 'vs-bus-booking-manager' ); ?></option>
                                        <option value="email" <?php selected( $field['type'], 'email' ); ?>><?php esc_html_e( 'Email', 'vs-bus-booking-manager' ); ?></option>
                                        <option value="number" <?php selected( $field['type'], 'number' ); ?>><?php esc_html_e( 'Number', 'vs-bus-booking-manager' ); ?></option>
                                        <option value="select" <?php selected( $field['type'], 'select' ); ?>><?php esc_html_e( 'Select', 'vs-bus-booking-manager' ); ?></option>
                                    </select>
                                    <?php if ( $is_locked ) : ?>
                                        <input type="hidden" name="vsbbm_passenger_fields[<?php echo $index; ?>][type]" value="<?php echo esc_attr( $field['type'] ); ?>">
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <label>
                                        <input type="checkbox" name="vsbbm_passenger_fields[<?php echo $index; ?>][required]" value="1" <?php checked( $field['required'], true ); ?> <?php echo $is_locked ? 'disabled' : ''; ?>>
                                        <?php esc_html_e( 'Required', 'vs-bus-booking-manager' ); ?>
                                    </label>
                                    <?php if ( $is_locked ) : ?>
                                        <input type="hidden" name="vsbbm_passenger_fields[<?php echo $index; ?>][required]" value="1">
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <?php if ( ! $is_locked ) : ?>
                                    <button type="button" class="button button-secondary remove-field" style="color: #a00;"><?php esc_html_e( 'Remove', 'vs-bus-booking-manager' ); ?></button>
                                    <?php else : ?>
                                    <span class="dashicons dashicons-lock"></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <!-- Helper for options -->
                            <div class="select-options" style="margin-top: 10px; <?php echo $field['type'] !== 'select' ? 'display: none;' : ''; ?>">
                                <label><?php esc_html_e( 'Options (comma separated)', 'vs-bus-booking-manager' ); ?></label>
                                <input type="text" name="vsbbm_passenger_fields[<?php echo $index; ?>][options]" value="<?php echo esc_attr( isset( $field['options'] ) ? $field['options'] : '' ); ?>" style="width: 100%;" <?php echo $is_locked ? 'readonly' : ''; ?>>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" id="add-field" class="button button-primary" style="margin-top: 15px;"><?php esc_html_e( 'Add New Field', 'vs-bus-booking-manager' ); ?></button>
                    <?php submit_button(); ?>
                </div>
            </form>
            <script>
                // Simple inline script for adding fields (Ideally move to admin.js)
                jQuery(document).ready(function($) {
                    let fieldIndex = <?php echo count( $fields ); ?>;
                    $('#add-field').on('click', function() {
                        // Cloning logic here (Simplified for output)
                        // In real implementation, prefer cloning a template or using admin.js
                        alert('<?php esc_html_e( 'Please implement the JS cloning logic in admin.js to avoid inline scripts.', 'vs-bus-booking-manager' ); ?>');
                    });
                });
            </script>
        </div>
        <?php
    }

    // --- Utility Methods ---

    private function get_booking_statuses() {
        return wc_get_order_statuses();
    }

    private function get_bus_products() {
        return get_posts(array(
            'post_type'      => 'product',
            'posts_per_page' => -1,
            'meta_query'     => array(
                array( 'key' => '_vsbbm_enable_seat_booking', 'value' => 'yes', 'compare' => '=' )
            )
        ));
    }

    private function get_settings() {
        return get_option( 'vsbbm_settings', array(
            'enable_email_notifications' => true,
            'reservation_timeout'        => 15,
            'max_seats_per_booking'      => 10
        ));
    }

    private function save_settings() {
        check_admin_referer( 'vsbbm_save_settings' );

        $settings = array(
            'enable_email_notifications' => isset( $_POST['enable_email_notifications'] ),
            'reservation_timeout'        => absint( $_POST['reservation_timeout'] ),
            'max_seats_per_booking'      => absint( $_POST['max_seats_per_booking'] )
        );

        update_option( 'vsbbm_settings', $settings );
        add_settings_error( 'vsbbm_settings', 'settings_updated', __( 'Settings saved.', 'vs-bus-booking-manager' ), 'updated' );
    }

    private function save_email_settings() {
        check_admin_referer( 'vsbbm_save_email_settings' );

        $settings = array(
            'from_name'                          => sanitize_text_field( $_POST['from_name'] ),
            'from_email'                         => sanitize_email( $_POST['from_email'] ),
            'admin_email'                        => sanitize_email( $_POST['admin_email'] ),
            'enable_customer_confirmation_email' => isset( $_POST['enable_customer_confirmation_email'] ),
            'enable_customer_cancellation_email' => isset( $_POST['enable_customer_cancellation_email'] ),
            // ... add other fields similarly
        );

        update_option( 'vsbbm_email_settings', $settings );
        add_settings_error( 'vsbbm_email_settings', 'settings_updated', __( 'Email settings saved.', 'vs-bus-booking-manager' ), 'updated' );
    }

    private function get_email_settings() {
        $defaults = array(
            'from_name'                          => get_bloginfo( 'name' ),
            'from_email'                         => get_option( 'admin_email' ),
            'admin_email'                        => get_option( 'admin_email' ),
            'enable_customer_confirmation_email' => true,
            'enable_customer_cancellation_email' => true,
        );
        return wp_parse_args( get_option( 'vsbbm_email_settings', array() ), $defaults );
    }

    private function get_recent_bookings( $limit = 10 ) {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'shop_order' AND post_status IN ('wc-completed', 'wc-processing') ORDER BY post_date DESC LIMIT %d", $limit ) );
    }

    private function get_weekly_stats() {
        // Implementation for chart data
        return array( 'labels' => array(), 'data' => array() ); 
    }
    
    // Placeholder methods for logic kept in original file but needed for full functionality
    private function process_booking_actions() {}
    private function process_bulk_booking_actions() {}
    private function generate_report( $type ) { return array(); }
    private function calculate_total_revenue() { return 0; }
    private function calculate_revenue_period( $start, $end ) { return 0; }
    private function calculate_total_passengers() { return 0; }
    private function calculate_occupancy_rate() { return 0; }
    private function get_reservations( $filters ) { return array(); }
    private function process_reservation_actions() {}
    public function handle_cache_settings_save() {}

} // End Class

// Initialize
VSBBM_Admin_Interface::init();