<?php
/**
 * Customer Booking Reminder Email Template
 */
if (!defined('ABSPATH')) exit;
?>

<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php _e('یادآوری رزرو', 'vs-bus-booking-manager'); ?></title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; }
        .header { background: linear-gradient(135deg, #ffc107 0%, #ff8f00 100%); color: white; padding: 30px 20px; text-align: center; }
        .content { padding: 30px 20px; color: #333; line-height: 1.6; }
        .reminder-details { background: #fff3cd; padding: 20px; border-radius: 8px; margin: 20px 0; border-right: 4px solid #ffc107; }
        .passenger-info { background: #e8f4fd; padding: 15px; border-radius: 6px; margin: 10px 0; }
        .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #666; font-size: 14px; }
        .button { display: inline-block; background: #ffc107; color: #333; padding: 12px 25px; text-decoration: none; border-radius: 6px; margin: 10px 0; font-weight: bold; }
        .important { color: #dc3545; font-weight: bold; }
        .countdown { font-size: 24px; font-weight: bold; color: #ff6f00; text-align: center; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1 style="margin: 0; font-size: 28px;">⏰ یادآوری رزرو</h1>
            <p style="margin: 10px 0 0 0; font-size: 16px;">فقط <?php echo $days_before; ?> روز تا حرکت باقی مانده</p>
        </div>

        <!-- Content -->
        <div class="content">
            <p>سلام <strong><?php echo esc_html($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()); ?></strong>،</p>

            <p>این ایمیل یادآوری برای رزرو صندلی شماست.</p>

            <div class="countdown">
                🚍 <?php echo $days_before; ?> روز دیگر
            </div>

            <!-- Reminder Details -->
            <div class="reminder-details">
                <h3 style="margin-top: 0; color: #856404;">📅 جزئیات حرکت</h3>
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 8px 0; border-bottom: 1px solid #ffeaa7;"><strong>شماره سفارش:</strong></td>
                        <td style="padding: 8px 0; border-bottom: 1px solid #ffeaa7;">#<?php echo $order->get_id(); ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; border-bottom: 1px solid #ffeaa7;"><strong>تاریخ حرکت:</strong></td>
                        <td style="padding: 8px 0; border-bottom: 1px solid #ffeaa7;">
                            <?php
                            // محاسبه تاریخ حرکت (این قسمت باید بر اساس محصول تنظیم شود)
                            echo date('Y/m/d', strtotime('+'.$days_before.' days'));
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0;"><strong>وضعیت رزرو:</strong></td>
                        <td style="padding: 8px 0;">
                            <span style="color: #28a745; font-weight: bold;">✅ تایید شده</span>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Product Information -->
            <?php if (!empty($product_info)): ?>
            <div class="reminder-details">
                <h3 style="margin-top: 0; color: #856404;">🚌 اطلاعات سرویس</h3>
                <?php foreach ($product_info as $product): ?>
                <div style="margin: 10px 0; padding: 10px; background: white; border-radius: 4px;">
                    <strong><?php echo esc_html($product['name']); ?></strong><br>
                    تعداد صندلی: <?php echo intval($product['quantity']); ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Passenger Information -->
            <?php if (!empty($passengers)): ?>
            <div class="reminder-details">
                <h3 style="margin-top: 0; color: #856404;">👥 اطلاعات مسافران</h3>
                <?php foreach ($passengers as $index => $passenger): ?>
                <div class="passenger-info">
                    <strong>مسافر <?php echo $index + 1; ?>:</strong><br>
                    <?php echo nl2br(esc_html($passenger)); ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Important Notes -->
            <div style="background: #f8d7da; padding: 20px; border-radius: 8px; border-right: 4px solid #dc3545; margin: 20px 0;">
                <h4 style="margin-top: 0; color: #721c24;">⚠️ نکات مهم برای روز حرکت</h4>
                <ul style="margin: 0; padding-right: 20px;">
                    <li>لطفاً ۳۰ دقیقه قبل از حرکت در محل سوار شدن حضور داشته باشید</li>
                    <li>کارت شناسایی معتبر به همراه داشته باشید</li>
                    <li>بلیط الکترونیکی خود را آماده نگه دارید</li>
                    <li>در صورت تأخیر با پشتیبانی تماس بگیرید</li>
                </ul>
            </div>

            <!-- Contact Information -->
            <div style="text-align: center; margin: 30px 0;">
                <p>برای مشاهده بلیط الکترونیکی و جزئیات کامل:</p>
                <a href="<?php echo wc_get_account_endpoint_url('tickets'); ?>" class="button">🎫 مشاهده بلیط‌ها</a>
            </div>

            <p style="text-align: center; color: #666;">
                اگر برنامه‌تان تغییر کرده، با ما تماس بگیرید: <strong><?php echo get_option('admin_email'); ?></strong>
            </p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>© <?php echo date('Y'); ?> <?php echo get_bloginfo('name'); ?> - تمامی حقوق محفوظ است</p>
            <p>این ایمیل یادآوری به صورت خودکار ارسال شده است.</p>
        </div>
    </div>
</body>
</html>