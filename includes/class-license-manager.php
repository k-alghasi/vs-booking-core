<?php
/**
 * License Manager برای VS Bus Booking Manager Pro
 *
 * @package VSBBM
 * @version 1.0.0
 */

defined('ABSPATH') || exit;

class VSBBM_License_Manager {

    private static $instance = null;
    private $license_server = 'https://api.vernasoft.ir/v1/license';
    private $product_id = 'vsbbm-pro';
    private $option_key = 'vsbbm_license_data';

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_init', array($this, 'check_license_status'));
        add_action('admin_notices', array($this, 'display_license_notices'));
        add_action('wp_ajax_vsbbm_activate_license', array($this, 'ajax_activate_license'));
        add_action('wp_ajax_vsbbm_deactivate_license', array($this, 'ajax_deactivate_license'));
    }

    /**
     * بررسی وضعیت license
     */
    public function check_license_status() {
        $license_data = $this->get_license_data();

        if (empty($license_data)) {
            return;
        }

        // بررسی انقضا
        if ($this->is_license_expired($license_data)) {
            $this->deactivate_license();
            return;
        }

        // بررسی اعتبار license هر 24 ساعت یک بار
        $last_check = get_option('vsbbm_license_last_check', 0);
        if (time() - $last_check > 86400) { // 24 hours
            $this->verify_license_remotely();
        }
    }

    /**
     * نمایش پیام‌های license
     */
    public function display_license_notices() {
        $license_data = $this->get_license_data();

        if (empty($license_data)) {
            $this->display_no_license_notice();
        } elseif ($this->is_license_expired($license_data)) {
            $this->display_expired_license_notice();
        } elseif (!$license_data['active']) {
            $this->display_inactive_license_notice();
        }
    }

    /**
     * فعال‌سازی license از طریق AJAX
     */
    public function ajax_activate_license() {
        check_ajax_referer('vsbbm_license_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('دسترسی غیرمجاز');
        }

        $license_key = sanitize_text_field($_POST['license_key']);
        $email = sanitize_email($_POST['email']);

        if (empty($license_key) || empty($email)) {
            wp_send_json_error('کلید license و ایمیل الزامی است');
        }

        $result = $this->activate_license($license_key, $email);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success(array(
            'message' => 'License با موفقیت فعال شد',
            'license_data' => $result
        ));
    }

    /**
     * غیرفعال‌سازی license از طریق AJAX
     */
    public function ajax_deactivate_license() {
        check_ajax_referer('vsbbm_license_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('دسترسی غیرمجاز');
        }

        $result = $this->deactivate_license();

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success('License غیرفعال شد');
    }

    /**
     * فعال‌سازی license
     */
    public function activate_license($license_key, $email) {
        $site_url = get_site_url();

        $response = wp_remote_post($this->license_server . '/activate', array(
            'timeout' => 15,
            'body' => array(
                'license_key' => $license_key,
                'email' => $email,
                'site_url' => $site_url,
                'product_id' => $this->product_id
            )
        ));

        if (is_wp_error($response)) {
            return new WP_Error('connection_error', 'خطا در اتصال به سرور license');
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!$data || !isset($data['success'])) {
            return new WP_Error('invalid_response', 'پاسخ نامعتبر از سرور');
        }

        if (!$data['success']) {
            return new WP_Error('activation_failed', $data['message'] ?? 'فعال‌سازی ناموفق');
        }

        // ذخیره اطلاعات license
        $license_data = array(
            'license_key' => $license_key,
            'email' => $email,
            'active' => true,
            'expires_at' => $data['expires_at'] ?? null,
            'activated_at' => current_time('mysql'),
            'site_url' => $site_url,
            'license_data' => $data
        );

        update_option($this->option_key, $license_data);
        update_option('vsbbm_license_last_check', time());

        return $license_data;
    }

    /**
     * غیرفعال‌سازی license
     */
    public function deactivate_license() {
        $license_data = $this->get_license_data();

        if (!empty($license_data) && $license_data['active']) {
            // اطلاع به سرور
            wp_remote_post($this->license_server . '/deactivate', array(
                'timeout' => 10,
                'body' => array(
                    'license_key' => $license_data['license_key'],
                    'site_url' => get_site_url()
                )
            ));
        }

        delete_option($this->option_key);
        delete_option('vsbbm_license_last_check');

        return true;
    }

    /**
     * بررسی اعتبار license با سرور
     */
    private function verify_license_remotely() {
        $license_data = $this->get_license_data();

        if (empty($license_data)) {
            return;
        }

        $response = wp_remote_post($this->license_server . '/verify', array(
            'timeout' => 10,
            'body' => array(
                'license_key' => $license_data['license_key'],
                'site_url' => get_site_url()
            )
        ));

        if (is_wp_error($response)) {
            return; // در صورت خطا، license رو غیرفعال نکن
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!$data || !isset($data['success'])) {
            return;
        }

        if (!$data['success']) {
            // license نامعتبر است
            $this->deactivate_license();
        } else {
            // بروزرسانی اطلاعات
            $license_data['license_data'] = $data;
            update_option($this->option_key, $license_data);
        }

        update_option('vsbbm_license_last_check', time());
    }

    /**
     * دریافت اطلاعات license
     */
    public function get_license_data() {
        return get_option($this->option_key, array());
    }

    /**
     * بررسی فعال بودن license
     */
    public function is_license_active() {
        $license_data = $this->get_license_data();

        if (empty($license_data) || !$license_data['active']) {
            return false;
        }

        if ($this->is_license_expired($license_data)) {
            return false;
        }

        return true;
    }

    /**
     * بررسی انقضای license
     */
    private function is_license_expired($license_data) {
        if (empty($license_data['expires_at'])) {
            return false; // license مادام العمر
        }

        return strtotime($license_data['expires_at']) < time();
    }

    /**
     * نمایش پیام عدم وجود license
     */
    private function display_no_license_notice() {
        if (!current_user_can('manage_options')) {
            return;
        }

        echo '<div class="notice notice-error is-dismissible">';
        echo '<p><strong>VS Bus Booking Manager Pro:</strong> ';
        echo 'برای استفاده از نسخه پرو، لطفاً license خود را فعال کنید. ';
        echo '<a href="' . admin_url('admin.php?page=vsbbm-license') . '" class="button button-primary">فعال‌سازی License</a>';
        echo '</p>';
        echo '</div>';
    }

    /**
     * نمایش پیام license منقضی شده
     */
    private function display_expired_license_notice() {
        if (!current_user_can('manage_options')) {
            return;
        }

        echo '<div class="notice notice-error is-dismissible">';
        echo '<p><strong>VS Bus Booking Manager Pro:</strong> ';
        echo 'License شما منقضی شده است. لطفاً برای تمدید اقدام کنید. ';
        echo '<a href="' . admin_url('admin.php?page=vsbbm-license') . '" class="button button-primary">تمدید License</a>';
        echo '</p>';
        echo '</div>';
    }

    /**
     * نمایش پیام license غیرفعال
     */
    private function display_inactive_license_notice() {
        if (!current_user_can('manage_options')) {
            return;
        }

        echo '<div class="notice notice-warning is-dismissible">';
        echo '<p><strong>VS Bus Booking Manager Pro:</strong> ';
        echo 'License شما غیرفعال است. ';
        echo '<a href="' . admin_url('admin.php?page=vsbbm-license') . '" class="button button-primary">فعال‌سازی License</a>';
        echo '</p>';
        echo '</div>';
    }

    /**
     * دریافت اطلاعات license برای نمایش
     */
    public function get_license_info() {
        $license_data = $this->get_license_data();

        if (empty($license_data)) {
            return array(
                'status' => 'inactive',
                'message' => 'License فعال نیست'
            );
        }

        $status = 'active';
        $message = 'License فعال است';

        if (!$license_data['active']) {
            $status = 'inactive';
            $message = 'License غیرفعال است';
        } elseif ($this->is_license_expired($license_data)) {
            $status = 'expired';
            $message = 'License منقضی شده است';
        }

        return array(
            'status' => $status,
            'message' => $message,
            'license_key' => substr($license_data['license_key'], 0, 8) . '****',
            'email' => $license_data['email'],
            'expires_at' => $license_data['expires_at'] ?? 'مادام العمر',
            'activated_at' => $license_data['activated_at'],
            'site_url' => $license_data['site_url']
        );
    }
}

// Initialize the license manager
VSBBM_License_Manager::get_instance();