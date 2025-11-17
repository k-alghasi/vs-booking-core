<?php
defined('ABSPATH') || exit;

class VSBBM_Blacklist {
    
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_admin_menu'));
    }
    
    public static function create_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vsbbm_blacklist';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            national_code VARCHAR(20) NOT NULL,
            reason TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY national_code (national_code)
        ) $charset_collate;";
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
    
    public static function add_admin_menu() {
        add_menu_page(
            'مدیریت لیست سیاه',
            'لیست سیاه رزرو',
            'manage_options',
            'vsbbm-blacklist',
            array(__CLASS__, 'render_admin_page'),
            'dashicons-no-alt',
            56
        );
    }
    
    public static function render_admin_page() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vsbbm_blacklist';
        
        // پردازش فرم افزودن
        if (isset($_POST['vsbbm_add_blacklist']) && check_admin_referer('vsbbm_add_blacklist')) {
            $national_code = sanitize_text_field($_POST['national_code']);
            $reason = sanitize_textarea_field($_POST['reason']);
            
            if ($national_code) {
                $result = $wpdb->insert($table_name, array(
                    'national_code' => $national_code,
                    'reason' => $reason
                ));
                
                if ($result) {
                    echo '<div class="notice notice-success"><p>کد ملی با موفقیت اضافه شد.</p></div>';
                } else {
                    echo '<div class="notice notice-error"><p>خطا در افزودن کد ملی.</p></div>';
                }
            }
        }
        
        // پردازش حذف
        if (isset($_GET['delete']) && wp_verify_nonce($_GET['_wpnonce'], 'vsbbm_delete_blacklist')) {
            $wpdb->delete($table_name, array('id' => intval($_GET['delete'])));
            echo '<div class="notice notice-success"><p>کد ملی با موفقیت حذف شد.</p></div>';
        }
        
        // دریافت لیست
        $blacklist = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");
        
        ?>
        <div class="wrap">
            <h1>مدیریت لیست سیاه</h1>
            
            <form method="post" class="vsbbm-form">
                <?php wp_nonce_field('vsbbm_add_blacklist'); ?>
                <h2>افزودن کد ملی جدید</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="national_code">کد ملی</label></th>
                        <td>
                            <input type="text" name="national_code" id="national_code" 
                                   class="regular-text" required pattern="[0-9]{10}" 
                                   title="کد ملی باید 10 رقمی باشد">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="reason">دلیل (اختیاری)</label></th>
                        <td>
                            <textarea name="reason" id="reason" class="large-text" rows="3"></textarea>
                        </td>
                    </tr>
                </table>
                <button type="submit" name="vsbbm_add_blacklist" class="button button-primary">افزودن به لیست سیاه</button>
            </form>
            
            <hr style="margin: 20px 0;">
            
            <h2>کدهای ملی موجود در لیست سیاه</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>کد ملی</th>
                        <th>دلیل</th>
                        <th>تاریخ افزودن</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($blacklist) : ?>
                        <?php foreach ($blacklist as $item) : ?>
                            <tr>
                                <td><?php echo esc_html($item->national_code); ?></td>
                                <td><?php echo esc_html($item->reason); ?></td>
                                <td><?php echo date('Y/m/d H:i', strtotime($item->created_at)); ?></td>
                                <td>
                                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=vsbbm-blacklist&delete=' . $item->id), 'vsbbm_delete_blacklist'); ?>" 
                                       class="button button-secondary" 
                                       onclick="return confirm('آیا از حذف این کد ملی اطمینان دارید؟')">
                                        حذف
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="4" style="text-align: center;">هیچ کد ملی در لیست سیاه وجود ندارد.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <style>
            .vsbbm-form {
                background: #fff;
                padding: 20px;
                border-radius: 5px;
                margin-bottom: 20px;
            }
        </style>
        <?php
    }
    
    public static function is_blacklisted($national_code) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vsbbm_blacklist';
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE national_code = %s", 
            $national_code
        )) > 0;
    }
}