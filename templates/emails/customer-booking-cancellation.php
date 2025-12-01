<?php
/**
 * Customer Booking Cancellation Email Template
 */
if (!defined('ABSPATH')) exit;
?>

<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php _e('لغو رزرو صندلی', 'vs-bus-booking-manager'); ?></title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; }
        .header { background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white; padding: 30px 20px; text-align: center; }
        .content { padding: 30px 20px; color: #333; line-height: 1.6; }
        .booking-details { background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; border-right: 4px solid #dc3545; }
        .passenger-info { background: #ffe6e6; padding: 15px; border-radius: 6px; margin: 10px 0; }
        .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #666; font-size: 14px; }
        .button { display: inline-block; background: #dc3545; color: white; padding: 12px 25px; text-decoration: none; border-radius: 6px; margin: 10px 0; }
        .refund-info { background: #d4edda; padding: 15px; border-radius: 6px; border-right: 4px solid #28a745; margin: 15px 0; }
        .important { color: #dc3545; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1 style="margin: 0; font-size: 28px;">❌ لغو رزرو صندلی</h1>
            <p style="margin: 10px 0 0 0; font-size: 16px;">رزرو شما لغو شد</p>
        </div>

        <!-- Content -->
        <div class="content">
            <p>سلام <strong><?php echo esc_html($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()); ?></strong>،</p>

            <p>با توجه به درخواست شما یا بنا به دلایل فنی، رزرو صندلی شما لغو شد.</p>

            <!-- Booking Details -->
            <div class="booking-details">
                <h3 style="margin-top: 0; color: #dc3545;">📋 جزئیات رزرو لغو شده</h3>
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 8px 0; border-bottom: 1px solid #dee2e6;"><strong>شماره سفارش:</strong></td>
                        <td style="padding: 8px 0; border-bottom: 1px solid #dee2e6;">#<?php echo $order->get_id(); ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; border-bottom: 1px solid #dee2e6;"><strong>تاریخ سفارش:</strong></td>
                        <td style="padding: 8px 0; border-bottom: 1px solid #dee2e6;"><?php echo date('Y/m/d H:i', strtotime($order->get_date_created())); ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; border-bottom: 1px solid #dee2e6;"><strong>وضعیت:</strong></td>
                        <td style="padding: 8px 0; border-bottom: 1px solid #dee2e6;">
                            <span style="color: #dc3545; font-weight: bold;">❌ لغو شده</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0;"><strong>مجموع مبلغ:</strong></td>
                        <td style="padding: 8px 0;"><strong><?php echo wc_price($order->get_total()); ?></strong></td>
                    </tr>
                </table>
            </div>

            <!-- Product Information -->
            <?php if (!empty($product_info)): ?>
            <div class="booking-details">
                <h3 style="margin-top: 0; color: #dc3545;">🚌 اطلاعات سرویس</h3>
                <?php foreach ($product_info as $product): ?>
                <div style="margin: 10px 0; padding: 10px; background: white; border-radius: 4px;">
                    <strong><?php echo esc_html($product['name']); ?></strong><br>
                    تعداد صندلی: <?php echo intval($product['quantity']); ?><br>
                    مبلغ: <?php echo wc_price($product['price']); ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Passenger Information -->
            <?php if (!empty($passengers)): ?>
            <div class="booking-details">
                <h3 style="margin-top: 0; color: #dc3545;">👥 اطلاعات مسافران</h3>
                <?php foreach ($passengers as $index => $passenger): ?>
                <div class="passenger-info">
                    <strong>مسافر <?php echo $index + 1; ?>:</strong><br>
                    <?php echo nl2br(esc_html($passenger)); ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Refund Information -->
            <?php
            $refund_amount = $order->get_total() - $order->get_total_refunded();
            if ($refund_amount > 0):
            ?>
            <div class="refund-info">
                <h4 style="margin-top: 0; color: #155724;">💰 اطلاعات استرداد</h4>
                <p style="margin: 5px 0;">مبلغ <strong><?php echo wc_price($refund_amount); ?></strong> به حساب شما استرداد خواهد شد.</p>
                <p style="margin: 5px 0; font-size: 14px;">زمان پردازش استرداد معمولاً ۳-۵ روز کاری است.</p>
            </div>
            <?php endif; ?>

            <!-- Important Notes -->
            <div style="background: #fff3cd; padding: 15px; border-radius: 6px; border-right: 4px solid #ffc107; margin: 15px 0;">
                <h4 style="margin-top: 0; color: #856404;">⚠️ نکات مهم</h4>
                <ul style="margin: 0; padding-right: 20px;">
                    <li>صندلی‌های رزرو شده شما آزاد شده و قابل خرید توسط دیگران است</li>
                    <li>در صورت داشتن سوال درباره استرداد، با پشتیبانی تماس بگیرید</li>
                    <li>برای رزرو مجدد می‌توانید از وبسایت ما استفاده کنید</li>
                </ul>
            </div>

            <!-- Contact Information -->
            <div style="text-align: center; margin: 30px 0;">
                <p>برای مشاهده جزئیات سفارش و پیگیری استرداد، وارد حساب کاربری خود شوید:</p>
                <a href="<?php echo wc_get_account_endpoint_url('orders'); ?>" class="button">👤 مشاهده سفارشات</a>
            </div>

            <p style="text-align: center; color: #666;">
                اگر سوالی دارید، با ما تماس بگیرید: <strong><?php echo get_option('admin_email'); ?></strong>
            </p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>© <?php echo date('Y'); ?> <?php echo get_bloginfo('name'); ?> - تمامی حقوق محفوظ است</p>
            <p>این ایمیل به صورت خودکار ارسال شده است. لطفاً به آن پاسخ ندهید.</p>
        </div>
    </div>
</body>
</html>