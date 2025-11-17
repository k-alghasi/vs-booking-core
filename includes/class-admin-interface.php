<?php
defined('ABSPATH') || exit;

class VSBBM_Admin_Interface {
    
    private static $instance = null;
    
    public static function init() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menus'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_vsbbm_get_booking_details', array($this, 'get_booking_details_ajax'));
        add_action('wp_ajax_vsbbm_update_booking_status', array($this, 'update_booking_status_ajax'));
        add_action('wp_ajax_vsbbm_export_bookings', array($this, 'export_bookings_ajax'));
        
        // اضافه کردن hook برای نمایش اطلاعات مسافر در صفحه سفارش
        add_action('woocommerce_before_order_itemmeta', array($this, 'display_order_passenger_info'), 10, 3);
        
        // اضافه کردن هوک‌های جدید برای فیلدهای مسافر
        add_action('admin_menu', array($this, 'add_passenger_fields_settings'));
        add_action('admin_init', array($this, 'register_passenger_fields_settings'));
    }
    
    public function add_admin_menus() {
        // منوی اصلی
        add_menu_page(
            'مدیریت رزرو اتوبوس',
            'رزرو اتوبوس',
            'manage_options',
            'vsbbm-dashboard',
            array($this, 'render_dashboard'),
            'dashicons-bus',
            30
        );
        
        // زیرمنوها
        add_submenu_page(
            'vsbbm-dashboard',
            'داشبورد',
            'داشبورد',
            'manage_options',
            'vsbbm-dashboard',
            array($this, 'render_dashboard')
        );
        
        add_submenu_page(
            'vsbbm-dashboard',
            'همه رزروها',
            'همه رزروها',
            'manage_options',
            'vsbbm-bookings',
            array($this, 'render_bookings_page')
        );
        
        add_submenu_page(
            'vsbbm-dashboard',
            'گزارش‌گیری',
            'گزارش‌گیری',
            'manage_options',
            'vsbbm-reports',
            array($this, 'render_reports_page')
        );
        
        add_submenu_page(
            'vsbbm-dashboard',
            'لیست سیاه',
            'لیست سیاه',
            'manage_options',
            'vsbbm-blacklist',
            array($this, 'render_blacklist_page')
        );
        
        add_submenu_page(
            'vsbbm-dashboard',
            'رزروها',
            'رزروها',
            'manage_options',
            'vsbbm-reservations',
            array($this, 'render_reservations_page')
        );

        add_submenu_page(
            'vsbbm-dashboard',
            'تنظیمات ایمیل',
            'تنظیمات ایمیل',
            'manage_options',
            'vsbbm-email-settings',
            array($this, 'render_email_settings_page')
        );

        add_submenu_page(
            'vsbbm-dashboard',
            'تنظیمات',
            'تنظیمات',
            'manage_options',
            'vsbbm-settings',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * اضافه کردن منوی تنظیمات فیلدهای مسافر
     */
    public function add_passenger_fields_settings() {
        add_submenu_page(
            'vsbbm-dashboard',
            'تنظیمات فیلدهای مسافر',
            'فیلدهای مسافر',
            'manage_options',
            'vsbbm-passenger-fields',
            array($this, 'render_passenger_fields_settings')
        );
    }

    /**
     * ثبت تنظیمات فیلدهای مسافر
     */
    public function register_passenger_fields_settings() {
        register_setting('vsbbm_passenger_fields', 'vsbbm_passenger_fields', array(
            'sanitize_callback' => array($this, 'sanitize_passenger_fields')
        ));
    }
    
    public function enqueue_admin_scripts($hook) {
        // فقط در صفحات پلاگین ما لود شود
        if (strpos($hook, 'vsbbm-') !== false) {
            wp_enqueue_style('vsbbm-admin', VSBBM_PLUGIN_URL . 'assets/css/admin.css', array(), VSBBM_VERSION);
            wp_enqueue_script('vsbbm-admin', VSBBM_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), VSBBM_VERSION, true);
            
            // Chart.js برای نمودارها
            wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), null, true);
            
            // DataTables برای جدول‌ها
            wp_enqueue_style('data-tables', 'https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css');
            wp_enqueue_script('data-tables', 'https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js', array('jquery'), null, true);
            wp_enqueue_script('data-tables-bootstrap', 'https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js', array('data-tables'), null, true);
            
            // localize script برای AJAX
            wp_localize_script('vsbbm-admin', 'vsbbm_admin', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('vsbbm_admin_nonce'),
                'i18n' => array(
                    'confirm_delete' => 'آیا از حذف این رزرو مطمئن هستید؟',
                    'loading' => 'در حال بارگذاری...',
                    'exporting' => 'در حال آماده‌سازی گزارش...'
                )
            ));
        }
    }
    
    public function render_dashboard() {
        $stats = $this->get_dashboard_stats();
        $recent_bookings = $this->get_recent_bookings(10);
        $weekly_data = $this->get_weekly_stats();
        
        include VSBBM_PLUGIN_PATH . 'templates/admin/dashboard.php';
    }
    
    public function render_bookings_page() {
        // پردازش actions
        $this->process_booking_actions();
        $this->process_bulk_booking_actions();

        // دریافت پارامترهای فیلتر
        $filters = array(
            'status' => isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '',
            'date_from' => isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '',
            'date_to' => isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '',
            'search' => isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '',
            'product_id' => isset($_GET['product_id']) ? intval($_GET['product_id']) : ''
        );

        $bookings = $this->get_all_bookings($filters);
        $statuses = $this->get_booking_statuses();
        $products = $this->get_bus_products();

        include VSBBM_PLUGIN_PATH . 'templates/admin/bookings.php';
    }
    
    public function render_reports_page() {
        $report_type = isset($_GET['report_type']) ? sanitize_text_field($_GET['report_type']) : 'daily';
        $report_data = $this->generate_report($report_type);
        
        include VSBBM_PLUGIN_PATH . 'templates/admin/reports.php';
    }
    
    public function render_reservations_page() {
        // پردازش actions
        $this->process_reservation_actions();

        // دریافت پارامترهای فیلتر
        $filters = array(
            'status' => isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '',
            'product_id' => isset($_GET['product_id']) ? intval($_GET['product_id']) : '',
            'date_from' => isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '',
            'date_to' => isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : ''
        );

        $reservations = $this->get_reservations($filters);
        $statuses = array(
            'reserved' => 'رزرو شده',
            'confirmed' => 'تایید شده',
            'cancelled' => 'لغو شده',
            'expired' => 'منقضی شده'
        );

        include VSBBM_PLUGIN_PATH . 'templates/admin/reservations.php';
    }

    public function render_email_settings_page() {
        // ذخیره تنظیمات
        if (isset($_POST['vsbbm_save_email_settings'])) {
            $this->save_email_settings();
        }

        $settings = $this->get_email_settings();

        ?>
        <div class="wrap">
            <h1>⚙️ تنظیمات اعلان‌های ایمیلی</h1>

            <div class="notice notice-info">
                <p>💡 <strong>توجه:</strong> تنظیمات ایمیل برای اطلاع‌رسانی خودکار رزروها و تغییرات سفارشات.</p>
            </div>

            <form method="post" action="">
                <?php wp_nonce_field('vsbbm_save_email_settings'); ?>

                <div class="card" style="max-width: 800px;">
                    <h3>📧 تنظیمات عمومی ایمیل</h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="from_name">نام فرستنده</label></th>
                            <td>
                                <input type="text" name="from_name" id="from_name"
                                       value="<?php echo esc_attr($settings['from_name']); ?>"
                                       class="regular-text" required>
                                <p class="description">نامی که در فرستنده ایمیل نمایش داده می‌شود</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="from_email">ایمیل فرستنده</label></th>
                            <td>
                                <input type="email" name="from_email" id="from_email"
                                       value="<?php echo esc_attr($settings['from_email']); ?>"
                                       class="regular-text" required>
                                <p class="description">آدرس ایمیلی که ایمیل‌ها از آن ارسال می‌شود</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="admin_email">ایمیل مدیر</label></th>
                            <td>
                                <input type="email" name="admin_email" id="admin_email"
                                       value="<?php echo esc_attr($settings['admin_email']); ?>"
                                       class="regular-text" required>
                                <p class="description">آدرس ایمیلی که اعلان‌های ادمین به آن ارسال می‌شود</p>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="card" style="max-width: 800px; margin-top: 20px;">
                    <h3>👤 ایمیل‌های مشتری</h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row">تایید رزرو</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="enable_customer_confirmation_email"
                                           value="1" <?php checked($settings['enable_customer_confirmation_email'], true); ?>>
                                    ارسال ایمیل تایید رزرو پس از تکمیل سفارش
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">لغو رزرو</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="enable_customer_cancellation_email"
                                           value="1" <?php checked($settings['enable_customer_cancellation_email'], true); ?>>
                                    ارسال ایمیل اطلاع‌رسانی لغو رزرو
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">پردازش سفارش</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="enable_customer_processing_email"
                                           value="1" <?php checked($settings['enable_customer_processing_email'], false); ?>>
                                    ارسال ایمیل تایید رزرو برای سفارشات در حال پردازش
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">یادآوری رزرو</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="enable_customer_reminder_email"
                                           value="1" <?php checked($settings['enable_customer_reminder_email'], false); ?>>
                                    ارسال ایمیل یادآوری قبل از تاریخ حرکت (نیاز به تنظیم cron job)
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">BCC به ادمین</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="bcc_admin_on_customer_emails"
                                           value="1" <?php checked($settings['bcc_admin_on_customer_emails'], false); ?>>
                                    ارسال کپی ایمیل‌های مشتری به ادمین
                                </label>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="card" style="max-width: 800px; margin-top: 20px;">
                    <h3>👨‍💼 ایمیل‌های ادمین</h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row">رزرو جدید</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="enable_admin_new_booking_email"
                                           value="1" <?php checked($settings['enable_admin_new_booking_email'], true); ?>>
                                    ارسال اعلان رزرو جدید به ادمین
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">رزرو منقضی شده</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="enable_admin_expired_reservation_email"
                                           value="1" <?php checked($settings['enable_admin_expired_reservation_email'], false); ?>>
                                    ارسال اعلان رزروهای منقضی شده به ادمین
                                </label>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="card" style="max-width: 800px; margin-top: 20px;">
                    <h3>📝 موضوع‌های ایمیل</h3>
                    <p>می‌توانید موضوع پیش‌فرض ایمیل‌ها را تغییر دهید:</p>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="customer_confirmation_subject">تایید رزرو مشتری</label></th>
                            <td>
                                <input type="text" name="customer_confirmation_subject" id="customer_confirmation_subject"
                                       value="<?php echo esc_attr($settings['customer_confirmation_subject'] ?: 'تایید رزرو صندلی'); ?>"
                                       class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="customer_cancellation_subject">لغو رزرو مشتری</label></th>
                            <td>
                                <input type="text" name="customer_cancellation_subject" id="customer_cancellation_subject"
                                       value="<?php echo esc_attr($settings['customer_cancellation_subject'] ?: 'لغو رزرو صندلی'); ?>"
                                       class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="admin_new_booking_subject">رزرو جدید ادمین</label></th>
                            <td>
                                <input type="text" name="admin_new_booking_subject" id="admin_new_booking_subject"
                                       value="<?php echo esc_attr($settings['admin_new_booking_subject'] ?: 'رزرو جدید صندلی'); ?>"
                                       class="regular-text">
                            </td>
                        </tr>
                    </table>
                </div>

                <p class="submit">
                    <input type="submit" name="vsbbm_save_email_settings" class="button button-primary"
                           value="💾 ذخیره تنظیمات">
                </p>
            </form>
        </div>

        <style>
            .card {
                background: #fff;
                border: 1px solid #ccd0d4;
                border-radius: 4px;
                padding: 20px;
                margin-bottom: 20px;
            }
            .card h3 {
                margin-top: 0;
                padding-bottom: 10px;
                border-bottom: 1px solid #eee;
                color: #23282d;
            }
        </style>
        <?php
    }

    public function render_blacklist_page() {
        // این متد از کلاس blacklist استفاده می‌کند
        VSBBM_Blacklist::render_admin_page();
    }
    
    public function render_settings_page() {
        // ذخیره تنظیمات
        if (isset($_POST['vsbbm_save_settings'])) {
            $this->save_settings();
        }
        
        $settings = $this->get_settings();
        
        include VSBBM_PLUGIN_PATH . 'templates/admin/settings.php';
    }
    
    /**
     * نمایش صفحه تنظیمات فیلدهای مسافر
     */
    public function render_passenger_fields_settings() {
        $fields = get_option('vsbbm_passenger_fields', array(
            array('type' => 'text', 'label' => 'نام کامل', 'required' => true, 'placeholder' => 'نام و نام خانوادگی', 'locked' => false),
            array('type' => 'text', 'label' => 'کد ملی', 'required' => true, 'placeholder' => 'کد ملی ۱۰ رقمی', 'locked' => true),
            array('type' => 'tel', 'label' => 'شماره تماس', 'required' => true, 'placeholder' => '09xxxxxxxxx', 'locked' => false),
        ));
        ?>
        <div class="wrap">
            <h1>⚙️ تنظیمات فیلدهای اطلاعات مسافر</h1>
            
            <div class="notice notice-info">
                <p>💡 <strong>توجه:</strong> فیلد "کد ملی" قفل شده است زیرا سیستم لیست سیاه بر اساس آن کار می‌کند.</p>
            </div>
            
            <form method="post" action="options.php">
                <?php settings_fields('vsbbm_passenger_fields'); ?>
                
                <div class="card" style="max-width: 800px;">
                    <h3>فیلدهای اطلاعات مسافر</h3>
                    <p>فیلدهایی که در فرم رزرو صندلی نمایش داده می‌شوند را مدیریت کنید.</p>
                    
                    <div id="vsbbm-fields-container">
    <?php foreach ($fields as $index => $field): 
        $is_locked = ($field['label'] === 'کد ملی'); // فقط کد ملی قفل شود
        $is_national_code = ($field['label'] === 'کد ملی');
    ?>
    <div class="field-group <?php echo $is_locked ? 'locked-field' : ''; ?>" 
         style="background: <?php echo $is_locked ? '#fff3cd' : '#f9f9f9'; ?>; 
                padding: 15px; margin: 10px 0; border-radius: 5px; 
                border-left: 4px solid <?php echo $is_locked ? '#ffc107' : '#0073aa'; ?>;">
        
        <?php if ($is_locked): ?>
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px; padding: 5px 10px; background: #fff8e1; border-radius: 3px;">
            <span style="color: #856404;">🔒 این فیلد قفل شده است (سیستم لیست سیاه)</span>
        </div>
        <?php endif; ?>
        
        <div style="display: grid; grid-template-columns: 2fr 2fr 1fr 1fr <?php echo $is_locked ? '0.5fr' : '1fr'; ?>; gap: 10px; align-items: end;">
            <div>
                <label>عنوان فیلد</label>
                <input type="text" 
                       name="vsbbm_passenger_fields[<?php echo $index; ?>][label]" 
                       value="<?php echo esc_attr($field['label']); ?>" 
                       style="width: 100%; <?php echo $is_locked ? 'background: #f8f9fa;' : ''; ?>" 
                       <?php echo $is_locked ? 'readonly' : 'required'; ?>>
            </div>
            
            <div>
                <label>Placeholder</label>
                <input type="text" 
                       name="vsbbm_passenger_fields[<?php echo $index; ?>][placeholder]" 
                       value="<?php echo esc_attr($field['placeholder']); ?>" 
                       style="width: 100%; <?php echo $is_locked ? 'background: #f8f9fa;' : ''; ?>" 
                       <?php echo $is_locked ? 'readonly' : ''; ?>>
            </div>
            
            <div>
                <label>نوع فیلد</label>
                <select name="vsbbm_passenger_fields[<?php echo $index; ?>][type]" 
                        style="width: 100%; <?php echo $is_locked ? 'background: #f8f9fa;' : ''; ?>" 
                        <?php echo $is_locked ? 'disabled' : ''; ?>>
                    <option value="text" <?php selected($field['type'], 'text'); ?>>متنی</option>
                    <option value="tel" <?php selected($field['type'], 'tel'); ?>>تلفن</option>
                    <option value="email" <?php selected($field['type'], 'email'); ?>>ایمیل</option>
                    <option value="number" <?php selected($field['type'], 'number'); ?>>عدد</option>
                    <option value="select" <?php selected($field['type'], 'select'); ?>>انتخابگر</option>
                </select>
                <?php if ($is_locked): ?>
                <input type="hidden" name="vsbbm_passenger_fields[<?php echo $index; ?>][type]" value="<?php echo esc_attr($field['type']); ?>">
                <?php endif; ?>
            </div>
            
            <div>
                <label>
                    <input type="checkbox" 
                           name="vsbbm_passenger_fields[<?php echo $index; ?>][required]" 
                           value="1" <?php checked($field['required'], true); ?>
                           <?php echo $is_locked ? 'disabled' : ''; ?>>
                    اجباری
                    <?php if ($is_locked): ?>
                    <input type="hidden" name="vsbbm_passenger_fields[<?php echo $index; ?>][required]" value="1">
                    <?php endif; ?>
                </label>
            </div>
            
            <div>
                <?php if (!$is_locked): ?>
                <button type="button" class="button button-secondary remove-field" 
                        style="background: #dc3232; color: white; border: none;">
                    حذف
                </button>
                <?php else: ?>
                <span style="color: #666; font-size: 12px;">غیرقابل حذف</span>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Options for select field -->
        <div class="select-options" style="margin-top: 10px; <?php echo $field['type'] !== 'select' ? 'display: none;' : ''; ?>">
            <label>گزینه‌ها (با کاما جدا کنید)</label>
            <input type="text" 
                   name="vsbbm_passenger_fields[<?php echo $index; ?>][options]" 
                   value="<?php echo esc_attr(isset($field['options']) ? $field['options'] : ''); ?>" 
                   placeholder="مرد, زن" 
                   style="width: 100%; <?php echo $is_locked ? 'background: #f8f9fa;' : ''; ?>" 
                   <?php echo $is_locked ? 'readonly' : ''; ?>>
        </div>
        
        <!-- فیلد مخفی برای locked -->
        <input type="hidden" name="vsbbm_passenger_fields[<?php echo $index; ?>][locked]" value="<?php echo $is_locked ? '1' : '0'; ?>">
    </div>
    <?php endforeach; ?>
</div>
                    
                    <button type="button" id="add-field" class="button button-primary" style="margin-top: 15px;">
                        ➕ افزودن فیلد جدید
                    </button>
                    
                    <?php submit_button('ذخیره تغییرات'); ?>
                </div>
            </form>
        </div>

        <script>
        jQuery(document).ready(function($) {
            let fieldIndex = <?php echo count($fields); ?>;
            
            // افزودن فیلد جدید
            $('#add-field').on('click', function() {
                const newField = `
                    <div class="field-group" style="background: #f9f9f9; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #0073aa;">
                        <div style="display: grid; grid-template-columns: 2fr 2fr 1fr 1fr 1fr; gap: 10px; align-items: end;">
                            <div>
                                <label>عنوان فیلد</label>
                                <input type="text" name="vsbbm_passenger_fields[${fieldIndex}][label]" 
                                       style="width: 100%;" required>
                            </div>
                            
                            <div>
                                <label>Placeholder</label>
                                <input type="text" name="vsbbm_passenger_fields[${fieldIndex}][placeholder]" 
                                       style="width: 100%;">
                            </div>
                            
                            <div>
                                <label>نوع فیلد</label>
                                <select name="vsbbm_passenger_fields[${fieldIndex}][type]" style="width: 100%;">
                                    <option value="text">متنی</option>
                                    <option value="tel">تلفن</option>
                                    <option value="email">ایمیل</option>
                                    <option value="number">عدد</option>
                                    <option value="select">انتخابگر</option>
                                </select>
                            </div>
                            
                            <div>
                                <label>
                                    <input type="checkbox" name="vsbbm_passenger_fields[${fieldIndex}][required]" value="1">
                                    اجباری
                                </label>
                            </div>
                            
                            <div>
                                <button type="button" class="button button-secondary remove-field" 
                                        style="background: #dc3232; color: white; border: none;">
                                    حذف
                                </button>
                            </div>
                        </div>
                        
                        <div class="select-options" style="margin-top: 10px; display: none;">
                            <label>گزینه‌ها (با کاما جدا کنید)</label>
                            <input type="text" name="vsbbm_passenger_fields[${fieldIndex}][options]" 
                                   style="width: 100%;" placeholder="مرد, زن">
                        </div>
                        
                        <input type="hidden" name="vsbbm_passenger_fields[${fieldIndex}][locked]" value="0">
                    </div>
                `;
                
                $('#vsbbm-fields-container').append(newField);
                fieldIndex++;
            });
            
            // حذف فیلد - جلوگیری از حذف فیلد کد ملی
$(document).on('click', '.remove-field', function() {
    const fieldGroup = $(this).closest('.field-group');
    const fieldLabel = fieldGroup.find('input[name$="[label]"]').val();
    
    // فقط جلوگیری از حذف فیلد کد ملی
    if (fieldLabel === 'کد ملی') {
        alert('فیلد "کد ملی" قفل شده و قابل حذف نیست.');
        return;
    }
    
    if ($('.field-group').length > 1) {
        fieldGroup.remove();
    } else {
        alert('حداقل یک فیلد باید وجود داشته باشد.');
    }
});
            
            // نمایش/پنهان کردن گزینه‌های select
            $(document).on('change', 'select[name$="[type]"]', function() {
                const optionsDiv = $(this).closest('.field-group').find('.select-options');
                if ($(this).val() === 'select') {
                    optionsDiv.show();
                } else {
                    optionsDiv.hide();
                }
            });
            
            // جلوگیری از تغییر فیلدهای قفل شده
            $(document).on('input change', '.locked-field input, .locked-field select', function(e) {
                if ($(this).closest('.locked-field').length) {
                    e.preventDefault();
                    $(this).blur();
                    alert('این فیلد قفل شده و قابل تغییر نیست.');
                }
            });
        });
        </script>
        <style>
        .field-group {
            transition: all 0.3s ease;
        }
        .field-group:hover {
            background: #f0f0f0 !important;
        }
        .locked-field:hover {
            background: #fff3cd !important;
        }
        .locked-field input:read-only,
        .locked-field select:disabled {
            cursor: not-allowed;
            opacity: 0.7;
        }
        </style>
        <?php
    }

    /**
     * سانیتیزه کردن فیلدها و حفظ فیلد کد ملی
     */
    public function sanitize_passenger_fields($input) {
    if (!is_array($input)) {
        return $input;
    }
    
    $sanitized = array();
    $has_national_code = false;
    
    foreach ($input as $index => $field) {
        $sanitized_field = array(
            'label' => sanitize_text_field($field['label'] ?? ''),
            'placeholder' => sanitize_text_field($field['placeholder'] ?? ''),
            'type' => sanitize_text_field($field['type'] ?? 'text'),
            'required' => isset($field['required']) ? true : false,
            'locked' => ($field['label'] === 'کد ملی') ? true : false, // فقط کد ملی قفل شود
            'options' => isset($field['options']) ? sanitize_text_field($field['options']) : ''
        );
        
        // بررسی فیلد کد ملی
        if ($sanitized_field['label'] === 'کد ملی') {
            $has_national_code = true;
            $sanitized_field['required'] = true; // کد ملی همیشه اجباری
        }
        
        $sanitized[] = $sanitized_field;
    }
    
    // اگر فیلد کد ملی وجود نداشت، اضافهش کن
    if (!$has_national_code) {
        array_unshift($sanitized, array(
            'type' => 'text',
            'label' => 'کد ملی',
            'required' => true,
            'placeholder' => 'کد ملی ۱۰ رقمی',
            'locked' => true,
            'options' => ''
        ));
    }
    
    return $sanitized;
}
    
    public function display_order_passenger_info($item_id, $item, $product) {
        if (!$product) return;
        
        // فقط برای محصولات رزرو صندلی
        if (!VSBBM_Seat_Manager::is_seat_booking_enabled($product->get_id())) {
            return;
        }
        
        echo '<div class="vsbbm-order-passengers" style="margin-top: 10px; padding: 10px; background: #f9f9f9; border-radius: 5px;">';
        echo '<strong>اطلاعات مسافران:</strong><br>';
        
        // دریافت اطلاعات مسافران از متادیتای آیتم
        $passenger_meta = $item->get_meta_data();
        
        foreach ($passenger_meta as $meta) {
            if (strpos($meta->key, 'مسافر') !== false) {
                echo '<div style="margin: 5px 0; padding: 5px; background: white; border-radius: 3px;">';
                echo '<strong>' . esc_html($meta->key) . ':</strong> ' . esc_html($meta->value);
                echo '</div>';
            }
        }
        
        echo '</div>';
    }
    
    private function get_dashboard_stats() {
        global $wpdb;
        
        $today = date('Y-m-d');
        $week_start = date('Y-m-d', strtotime('monday this week'));
        
        return array(
            'total_bookings' => $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->posts} 
                 WHERE post_type = 'shop_order' 
                 AND post_status IN ('wc-completed', 'wc-processing', 'wc-on-hold')"
            ),
            'today_bookings' => $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->posts} 
                     WHERE post_type = 'shop_order' 
                     AND post_status IN ('wc-completed', 'wc-processing', 'wc-on-hold')
                     AND DATE(post_date) = %s",
                    $today
                )
            ),
            'total_revenue' => $this->calculate_total_revenue(),
            'weekly_revenue' => $this->calculate_revenue_period($week_start, $today),
            'total_passengers' => $this->calculate_total_passengers(),
            'occupancy_rate' => $this->calculate_occupancy_rate()
        );
    }
    
    private function get_weekly_stats() {
        global $wpdb;
        
        $weekly_data = array(
            'labels' => array(),
            'data' => array()
        );
        
        $days = array('شنبه', 'یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه');
        
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $day_name = $days[date('w', strtotime($date))];
            
            $count = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->posts} 
                     WHERE post_type = 'shop_order' 
                     AND post_status IN ('wc-completed', 'wc-processing', 'wc-on-hold')
                     AND DATE(post_date) = %s",
                    $date
                )
            );
            
            $weekly_data['labels'][] = $day_name;
            $weekly_data['data'][] = $count ?: 0;
        }
        
        return $weekly_data;
    }
    
    private function get_recent_bookings($limit = 10) {
        global $wpdb;
        
        $query = "
            SELECT p.ID, p.post_date, p.post_status, p.post_title,
                   u.display_name, u.user_email,
                   (SELECT meta_value FROM {$wpdb->postmeta} 
                    WHERE post_id = p.ID AND meta_key = '_order_total') as order_total
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->users} u ON p.post_author = u.ID
            WHERE p.post_type = 'shop_order'
            AND p.post_status IN ('wc-completed', 'wc-processing', 'wc-on-hold')
            ORDER BY p.post_date DESC
            LIMIT %d
        ";
        
        return $wpdb->get_results($wpdb->prepare($query, $limit));
    }

    private function get_all_bookings($filters = array()) {
        // استفاده از WooCommerce functions به جای query مستقیم
        $args = array(
            'limit' => -1, // همه سفارش‌ها
            'orderby' => 'date',
            'order' => 'DESC',
            'return' => 'objects'
        );
        
        // اضافه کردن فیلترها
        if (!empty($filters['status'])) {
            $args['status'] = $filters['status'];
        }
        
        if (!empty($filters['date_from']) || !empty($filters['date_to'])) {
            $args['date_created'] = '';
            if (!empty($filters['date_from'])) {
                $args['date_created'] .= '>=' . $filters['date_from'];
            }
            if (!empty($filters['date_to'])) {
                if (!empty($args['date_created'])) $args['date_created'] .= '...';
                $args['date_created'] .= '<=' . $filters['date_to'];
            }
        }
        
        // گرفتن سفارش‌ها
        $orders = wc_get_orders($args);
        
        // تبدیل به فرمت مورد نیاز ما
        $bookings = array();
        foreach ($orders as $order) {
            $booking = new stdClass();
            $booking->ID = $order->get_id();
            $booking->post_date = $order->get_date_created()->format('Y-m-d H:i:s');
            $booking->post_status = 'wc-' . $order->get_status(); // اضافه کردن prefix
            $booking->post_title = 'Order #' . $order->get_id();
            $booking->display_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
            $booking->user_email = $order->get_billing_email();
            $booking->order_total = $order->get_total();
            
            $bookings[] = $booking;
        }
        
        error_log('VSBBM - Found ' . count($bookings) . ' bookings via wc_get_orders()');
        
        return $bookings;
    }    

    private function get_booking_statuses() {
        // استفاده از statusهای واقعی WooCommerce
        $wc_statuses = wc_get_order_statuses();
        $statuses = array();
        
        foreach ($wc_statuses as $key => $label) {
            $clean_key = str_replace('wc-', '', $key);
            $statuses[$clean_key] = $label;
        }
        
        return $statuses;
    }

    // ... سایر متدهای موجود (calculate_total_revenue, process_booking_actions, etc.)
    
    private function calculate_total_revenue() {
        global $wpdb;
        
        return $wpdb->get_var(
            "SELECT SUM(meta_value) FROM {$wpdb->postmeta} 
             WHERE meta_key = '_order_total' 
             AND post_id IN (
                 SELECT ID FROM {$wpdb->posts} 
                 WHERE post_type = 'shop_order' 
                 AND post_status IN ('wc-completed', 'wc-processing', 'wc-on-hold')
             )"
        ) ?: 0;
    }
    
    private function calculate_revenue_period($start_date, $end_date) {
        global $wpdb;
        
        return $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(meta_value) FROM {$wpdb->postmeta} 
                 WHERE meta_key = '_order_total' 
                 AND post_id IN (
                     SELECT ID FROM {$wpdb->posts} 
                     WHERE post_type = 'shop_order' 
                     AND post_status IN ('wc-completed', 'wc-processing', 'wc-on-hold')
                     AND DATE(post_date) BETWEEN %s AND %s
                 )",
                $start_date, $end_date
            )
        ) ?: 0;
    }
    
    private function calculate_total_passengers() {
        global $wpdb;
        
        $total = 0;
        
        // شمردن تعداد مسافران از طریق آیتم‌های سفارش
        $order_items = $wpdb->get_results(
            "SELECT order_item_id FROM {$wpdb->prefix}woocommerce_order_items 
             WHERE order_item_type = 'line_item'"
        );
        
        foreach ($order_items as $item) {
            $passenger_count = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}woocommerce_order_itemmeta 
                     WHERE order_item_id = %d 
                     AND meta_key LIKE %s",
                    $item->order_item_id,
                    '%مسافر%'
                )
            );
            
            $total += $passenger_count ?: 0;
        }
        
        return $total;
    }
    
    private function calculate_occupancy_rate() {
        // محاسبه نرخ اشغال بر اساس تعداد صندلی‌های رزرو شده
        $total_seats = 32; // تعداد کل صندلی‌ها (فرضی)
        $reserved_seats = $this->calculate_total_passengers();
        
        if ($total_seats > 0) {
            return round(($reserved_seats / $total_seats) * 100, 2);
        }
        
        return 0;
    }
    
    private function process_booking_actions() {
        if (!isset($_GET['action']) || !isset($_GET['booking_id']) || !wp_verify_nonce($_GET['_wpnonce'], 'vsbbm_booking_action')) {
            return;
        }
        
        $action = sanitize_text_field($_GET['action']);
        $booking_id = intval($_GET['booking_id']);
        
        switch ($action) {
            case 'delete':
                $this->delete_booking($booking_id);
                break;
                
            case 'cancel':
                $this->cancel_booking($booking_id);
                break;
        }
    }
    
    private function delete_booking($booking_id) {
        // حذف سفارش و داده‌های مرتبط
        wp_delete_post($booking_id, true);
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success"><p>رزرو با موفقیت حذف شد.</p></div>';
        });
    }
    
    private function cancel_booking($booking_id) {
        // تغییر وضعیت به لغو شده
        wp_update_post(array(
            'ID' => $booking_id,
            'post_status' => 'wc-cancelled'
        ));
        
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success"><p>رزرو با موفقیت لغو شد.</p></div>';
        });
    }
    
    private function generate_report($report_type) {
        switch ($report_type) {
            case 'daily':
                return $this->generate_daily_report();
            case 'weekly':
                return $this->generate_weekly_report();
            case 'monthly':
                return $this->generate_monthly_report();
            default:
                return $this->generate_daily_report();
        }
    }
    
    private function generate_daily_report() {
        global $wpdb;
        
        $report = array();
        
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            
            $bookings = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->posts} 
                     WHERE post_type = 'shop_order' 
                     AND post_status IN ('wc-completed', 'wc-processing', 'wc-on-hold')
                     AND DATE(post_date) = %s",
                    $date
                )
            );
            
            $revenue = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT SUM(meta_value) FROM {$wpdb->postmeta} 
                     WHERE meta_key = '_order_total' 
                     AND post_id IN (
                         SELECT ID FROM {$wpdb->posts} 
                         WHERE post_type = 'shop_order' 
                         AND post_status IN ('wc-completed', 'wc-processing', 'wc-on-hold')
                         AND DATE(post_date) = %s
                     )",
                    $date
                )
            );
            
            $report[] = array(
                'date' => $date,
                'bookings' => $bookings ?: 0,
                'revenue' => $revenue ?: 0
            );
        }
        
        return $report;
    }
    
    private function generate_weekly_report() {
        global $wpdb;
        
        $report = array();
        
        for ($i = 3; $i >= 0; $i--) {
            $week_start = date('Y-m-d', strtotime("monday -$i weeks"));
            $week_end = date('Y-m-d', strtotime("sunday -$i weeks"));
            
            $bookings = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->posts} 
                     WHERE post_type = 'shop_order' 
                     AND post_status IN ('wc-completed', 'wc-processing', 'wc-on-hold')
                     AND DATE(post_date) BETWEEN %s AND %s",
                    $week_start, $week_end
                )
            );
            
            $revenue = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT SUM(meta_value) FROM {$wpdb->postmeta} 
                     WHERE meta_key = '_order_total' 
                     AND post_id IN (
                         SELECT ID FROM {$wpdb->posts} 
                         WHERE post_type = 'shop_order' 
                         AND post_status IN ('wc-completed', 'wc-processing', 'wc-on-hold')
                         AND DATE(post_date) BETWEEN %s AND %s
                     )",
                    $week_start, $week_end
                )
            );
            
            $report[] = array(
                'week' => "هفته " . (4 - $i),
                'period' => $week_start . ' تا ' . $week_end,
                'bookings' => $bookings ?: 0,
                'revenue' => $revenue ?: 0
            );
        }
        
        return $report;
    }
    
    private function generate_monthly_report() {
        global $wpdb;
        
        $report = array();
        
        for ($i = 5; $i >= 0; $i--) {
            $month_start = date('Y-m-01', strtotime("-$i months"));
            $month_end = date('Y-m-t', strtotime("-$i months"));
            
            $bookings = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->posts} 
                     WHERE post_type = 'shop_order' 
                     AND post_status IN ('wc-completed', 'wc-processing', 'wc-on-hold')
                     AND DATE(post_date) BETWEEN %s AND %s",
                    $month_start, $month_end
                )
            );
            
            $revenue = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT SUM(meta_value) FROM {$wpdb->postmeta} 
                     WHERE meta_key = '_order_total' 
                     AND post_id IN (
                         SELECT ID FROM {$wpdb->posts} 
                         WHERE post_type = 'shop_order' 
                         AND post_status IN ('wc-completed', 'wc-processing', 'wc-on-hold')
                         AND DATE(post_date) BETWEEN %s AND %s
                     )",
                    $month_start, $month_end
                )
            );
            
            $report[] = array(
                'month' => $this->get_persian_month_name(date('m', strtotime($month_start))),
                'period' => $month_start . ' تا ' . $month_end,
                'bookings' => $bookings ?: 0,
                'revenue' => $revenue ?: 0
            );
        }
        
        return $report;
    }
    
    private function get_persian_month_name($month_number) {
        $months = array(
            '01' => 'فروردین', '02' => 'اردیبهشت', '03' => 'خرداد',
            '04' => 'تیر', '05' => 'مرداد', '06' => 'شهریور',
            '07' => 'مهر', '08' => 'آبان', '09' => 'آذر',
            '10' => 'دی', '11' => 'بهمن', '12' => 'اسفند'
        );
        
        return $months[$month_number] ?? $month_number;
    }
    
    public function get_booking_details_ajax() {
        check_ajax_referer('vsbbm_admin_nonce', 'nonce');
        
        $booking_id = intval($_POST['booking_id']);
        $booking = $this->get_booking_details($booking_id);
        
        if ($booking) {
            wp_send_json_success($booking);
        } else {
            wp_send_json_error('رزرو یافت نشد');
        }
    }
    
    private function get_booking_details($booking_id) {
        $order = wc_get_order($booking_id);
        
        if (!$order) {
            return false;
        }
        
        $passengers = array();
        foreach ($order->get_items() as $item) {
            $item_passengers = array();
            foreach ($item->get_meta_data() as $meta) {
                if (strpos($meta->key, 'مسافر') !== false) {
                    $item_passengers[] = $meta->value;
                }
            }
            if (!empty($item_passengers)) {
                $passengers = array_merge($passengers, $item_passengers);
            }
        }
        
        return array(
            'id' => $order->get_id(),
            'date' => $order->get_date_created()->format('Y-m-d H:i:s'),
            'status' => $order->get_status(),
            'customer_name' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
            'customer_email' => $order->get_billing_email(),
            'customer_phone' => $order->get_billing_phone(),
            'passengers' => $passengers,
            'total_amount' => $order->get_total(),
            'payment_method' => $order->get_payment_method_title()
        );
    }
    
    public function update_booking_status_ajax() {
        check_ajax_referer('vsbbm_admin_nonce', 'nonce');
        
        $booking_id = intval($_POST['booking_id']);
        $status = sanitize_text_field($_POST['status']);
        
        $order = wc_get_order($booking_id);
        if ($order) {
            $order->update_status($status);
            wp_send_json_success('وضعیت با موفقیت به‌روزرسانی شد');
        } else {
            wp_send_json_error('سفارش یافت نشد');
        }
    }
    
    public function export_bookings_ajax() {
        check_ajax_referer('vsbbm_admin_nonce', 'nonce');
        
        $filters = array(
            'status' => isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '',
            'date_from' => isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : '',
            'date_to' => isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : ''
        );
        
        $bookings = $this->get_all_bookings($filters);
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=bookings-export-' . date('Y-m-d') . '.csv');
        
        $output = fopen('php://output', 'w');
        
        // هدر CSV
        fputcsv($output, array(
            'شماره سفارش', 'تاریخ', 'نام مشتری', 'ایمیل', 'مبلغ', 'وضعیت'
        ));
        
        // داده‌ها
        foreach ($bookings as $booking) {
            fputcsv($output, array(
                $booking->ID,
                $booking->post_date,
                $booking->display_name,
                $booking->user_email,
                $booking->order_total,
                $this->get_status_label($booking->post_status)
            ));
        }
        
        fclose($output);
        exit;
    }
    
    private function get_status_label($status) {
        $wc_statuses = wc_get_order_statuses();
        return $wc_statuses[$status] ?? $status;
    }
    
    private function get_settings() {
        return get_option('vsbbm_settings', array(
            'enable_email_notifications' => true,
            'reservation_timeout' => 15,
            'max_seats_per_booking' => 10
        ));
    }
    
    private function save_settings() {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'vsbbm_save_settings')) {
            return;
        }
        
        $settings = array(
            'enable_email_notifications' => isset($_POST['enable_email_notifications']),
            'reservation_timeout' => intval($_POST['reservation_timeout']),
            'max_seats_per_booking' => intval($_POST['max_seats_per_booking'])
        );
        
        update_option('vsbbm_settings', $settings);
        
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success"><p>تنظیمات با موفقیت ذخیره شد.</p></div>';
        });
    }
    
    private function calculate_passengers_from_bookings($bookings) {
        $total = 0;
        foreach ($bookings as $booking) {
            $total += $this->get_passenger_count_for_booking($booking->ID);
        }
        return $total;
    }

    private function get_passenger_count_for_booking($booking_id) {
        global $wpdb;
        
        return $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}woocommerce_order_itemmeta 
                 WHERE order_item_id IN (
                     SELECT order_item_id FROM {$wpdb->prefix}woocommerce_order_items 
                     WHERE order_id = %d
                 )
                 AND meta_key LIKE %s",
                $booking_id,
                '%مسافر%'
            )
        ) ?: 0;
    }
    
    private function get_active_bookings_count() {
        global $wpdb;
        
        return $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts} 
             WHERE post_type = 'shop_order' 
             AND post_status IN ('wc-completed', 'wc-processing', 'wc-on-hold')"
        ) ?: 0;
    }

    private function get_comparison_class($current, $previous) {
        if ($previous == 0) return 'neutral';
        return $current > $previous ? 'positive' : 'negative';
    }

    private function get_comparison_percentage($current, $previous) {
        if ($previous == 0) return 0;
        $change = (($current - $previous) / $previous) * 100;
        return round($change, 1);
    }

    private function get_most_popular_day($report_data) {
        if (empty($report_data)) return '---';

        $max_booking = max(array_column($report_data, 'bookings'));
        foreach ($report_data as $report) {
            if ($report['bookings'] == $max_booking) {
                return $report['date'] ?? $report['week'] ?? $report['month'] ?? '---';
            }
        }

        return '---';
    }

    private function get_email_settings() {
        $defaults = array(
            'from_name' => get_bloginfo('name'),
            'from_email' => get_option('admin_email'),
            'admin_email' => get_option('admin_email'),
            'enable_customer_confirmation_email' => true,
            'enable_customer_cancellation_email' => true,
            'enable_customer_processing_email' => false,
            'enable_customer_reminder_email' => false,
            'enable_admin_new_booking_email' => true,
            'enable_admin_expired_reservation_email' => false,
            'bcc_admin_on_customer_emails' => false,
            'customer_confirmation_subject' => '',
            'customer_cancellation_subject' => '',
            'admin_new_booking_subject' => '',
        );

        $settings = get_option('vsbbm_email_settings', array());
        return wp_parse_args($settings, $defaults);
    }

    private function save_email_settings() {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'vsbbm_save_email_settings')) {
            return;
        }

        $settings = array(
            'from_name' => sanitize_text_field($_POST['from_name']),
            'from_email' => sanitize_email($_POST['from_email']),
            'admin_email' => sanitize_email($_POST['admin_email']),
            'enable_customer_confirmation_email' => isset($_POST['enable_customer_confirmation_email']),
            'enable_customer_cancellation_email' => isset($_POST['enable_customer_cancellation_email']),
            'enable_customer_processing_email' => isset($_POST['enable_customer_processing_email']),
            'enable_customer_reminder_email' => isset($_POST['enable_customer_reminder_email']),
            'enable_admin_new_booking_email' => isset($_POST['enable_admin_new_booking_email']),
            'enable_admin_expired_reservation_email' => isset($_POST['enable_admin_expired_reservation_email']),
            'bcc_admin_on_customer_emails' => isset($_POST['bcc_admin_on_customer_emails']),
            'customer_confirmation_subject' => sanitize_text_field($_POST['customer_confirmation_subject']),
            'customer_cancellation_subject' => sanitize_text_field($_POST['customer_cancellation_subject']),
            'admin_new_booking_subject' => sanitize_text_field($_POST['admin_new_booking_subject']),
        );

        update_option('vsbbm_email_settings', $settings);

        add_action('admin_notices', function() {
            echo '<div class="notice notice-success"><p>تنظیمات ایمیل با موفقیت ذخیره شد.</p></div>';
        });
    }

    private function get_bus_products() {
        return get_posts(array(
            'post_type' => 'product',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_vsbbm_enable_seat_booking',
                    'value' => 'yes',
                    'compare' => '='
                )
            )
        ));
    }

    private function get_reservations($filters = array()) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'vsbbm_seat_reservations';
        $where_parts = array('1=1');
        $where_values = array();

        if (!empty($filters['status'])) {
            $where_parts[] = 'status = %s';
            $where_values[] = $filters['status'];
        }

        if (!empty($filters['product_id'])) {
            $where_parts[] = 'product_id = %d';
            $where_values[] = $filters['product_id'];
        }

        if (!empty($filters['date_from'])) {
            $where_parts[] = 'DATE(reserved_at) >= %s';
            $where_values[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where_parts[] = 'DATE(reserved_at) <= %s';
            $where_values[] = $filters['date_to'];
        }

        $where_clause = implode(' AND ', $where_parts);

        $query = "SELECT r.*, p.post_title as product_name, u.display_name as user_name
                  FROM $table_name r
                  LEFT JOIN {$wpdb->posts} p ON r.product_id = p.ID
                  LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID
                  WHERE $where_clause
                  ORDER BY r.reserved_at DESC";

        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, $where_values);
        }

        return $wpdb->get_results($query);
    }

    private function process_reservation_actions() {
        if (!isset($_GET['action']) || !isset($_GET['reservation_id']) || !wp_verify_nonce($_GET['_wpnonce'], 'vsbbm_reservation_action')) {
            return;
        }

        $action = sanitize_text_field($_GET['action']);
        $reservation_id = intval($_GET['reservation_id']);

        switch ($action) {
            case 'cancel':
                VSBBM_Seat_Reservations::cancel_reservation_by_id($reservation_id);
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-success"><p>رزرو با موفقیت لغو شد.</p></div>';
                });
                break;

            case 'confirm':
                VSBBM_Seat_Reservations::confirm_reservation_by_id($reservation_id);
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-success"><p>رزرو با موفقیت تایید شد.</p></div>';
                });
                break;
        }
    }

    private function process_bulk_booking_actions() {
        if (!isset($_POST['action']) || !isset($_POST['booking_ids']) || !wp_verify_nonce($_POST['_wpnonce'], 'vsbbm_bulk_action')) {
            return;
        }

        $action = sanitize_text_field($_POST['action']);
        $booking_ids = array_map('intval', $_POST['booking_ids']);

        if (empty($booking_ids)) {
            return;
        }

        $processed = 0;

        switch ($action) {
            case 'status_completed':
                foreach ($booking_ids as $booking_id) {
                    $order = wc_get_order($booking_id);
                    if ($order) {
                        $order->update_status('completed');
                        $processed++;
                    }
                }
                break;

            case 'status_cancelled':
                foreach ($booking_ids as $booking_id) {
                    $order = wc_get_order($booking_id);
                    if ($order) {
                        $order->update_status('cancelled');
                        $processed++;
                    }
                }
                break;

            case 'export':
                // Handle export - this will be processed separately
                break;
        }

        if ($processed > 0) {
            add_action('admin_notices', function() use ($processed, $action) {
                $action_labels = array(
                    'status_completed' => 'تکمیل شده',
                    'status_cancelled' => 'لغو شده'
                );
                $label = isset($action_labels[$action]) ? $action_labels[$action] : $action;
                echo '<div class="notice notice-success"><p>' . sprintf('%d رزرو به وضعیت "%s" تغییر یافت.', $processed, $label) . '</p></div>';
            });
        }
    }
    
} // پایان کلاس

// Initialize the class
VSBBM_Admin_Interface::init();