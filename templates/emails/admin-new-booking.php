<?php
/**
 * Admin New Booking Notification Email Template
 */
if (!defined('ABSPATH')) exit;
?>

<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php _e('رزرو جدید صندلی', 'vs-bus-booking-manager'); ?></title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; }
        .header { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 30px 20px; text-align: center; }
        .content { padding: 30px 20px; color: #333; line-height: 1.6; }
        .booking-details { background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; border-right: 4px solid #28a745; }
        .passenger-info { background: #e8f4fd; padding: 15px; border-radius: 6px; margin: 10px 0; }
        .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #666; font-size: 14px; }
        .button { display: inline-block; background: #007cba; color: white; padding: 12px 25px; text-decoration: none; border-radius: 6px; margin: 10px 5px; }
        .alert { background: #fff3cd; padding: 15px; border-radius: 6px; border-right: 4px solid #ffc107; margin: 15px 0; }
        .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin: 20px 0; }
        .stat-box { background: #e9ecef; padding: 15px; border-radius: 6px; text-align: center; }
        .stat-number { font-size: 24px; font-weight: bold; color: #007cba; }
        .stat-label { font-size: 12px; color: #666; margin-top: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1 style="margin: 0; font-size: 28px;">🔔 رزرو جدید صندلی</h1>
            <p style="margin: 10px 0 0 0; font-size: 16px;">یک رزرو جدید ثبت شد</p>
        </div>

        <!-- Content -->
        <div class="content">
            <p>مدیر محترم،</p>

            <p>یک رزرو جدید صندلی در سیستم ثبت شد. لطفاً جزئیات را بررسی کنید.</p>

            <!-- Quick Stats -->
            <div class="stats">
                <?php
                $stats = VSBBM_Seat_Reservations::get_reservation_stats();
                ?>
                <div class="stat-box">
                    <div class="stat-number"><?php echo intval($stats->reserved_count); ?></div>
                    <div class="stat-label">رزرو فعال</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?php echo intval($stats->confirmed_count); ?></div>
                    <div class="stat-label">تایید شده</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?php echo intval($stats->expired_count); ?></div>
                    <div class="stat-label">منقضی شده</div>
                </div>
            </div>

            <!-- Booking Details -->
            <div class="booking-details">
                <h3 style="margin-top: 0; color: #28a745;">📋 جزئیات رزرو</h3>
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 8px 0; border-bottom: 1px solid #dee2e6;"><strong>شماره سفارش:</strong></td>
                        <td style="padding: 8px 0; border-bottom: 1px solid #dee2e6;">
                            <a href="<?php echo admin_url('post.php?post=' . $order->get_id() . '&action=edit'); ?>" style="color: #007cba;">
                                #<?php echo $order->get_id(); ?>
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; border-bottom: 1px solid #dee2e6;"><strong>تاریخ سفارش:</strong></td>
                        <td style="padding: 8px 0; border-bottom: 1px solid #dee2e6;"><?php echo date('Y/m/d H:i', strtotime($order->get_date_created())); ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; border-bottom: 1px solid #dee2e6;"><strong>مشتری:</strong></td>
                        <td style="padding: 8px 0; border-bottom: 1px solid #dee2e6;">
                            <?php echo esc_html($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()); ?>
                            (<a href="mailto:<?php echo esc_attr($order->get_billing_email()); ?>"><?php echo esc_html($order->get_billing_email()); ?></a>)
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; border-bottom: 1px solid #dee2e6;"><strong>شماره تماس:</strong></td>
                        <td style="padding: 8px 0; border-bottom: 1px solid #dee2e6;"><?php echo esc_html($order->get_billing_phone()); ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; border-bottom: 1px solid #dee2e6;"><strong>وضعیت پرداخت:</strong></td>
                        <td style="padding: 8px 0; border-bottom: 1px solid #dee2e6;">
                            <?php
                            $status = $order->get_status();
                            $status_labels = array(
                                'pending' => 'در انتظار پرداخت',
                                'processing' => 'در حال پردازش',
                                'completed' => 'تکمیل شده',
                                'cancelled' => 'لغو شده',
                                'refunded' => 'استرداد شده',
                                'failed' => 'ناموفق'
                            );
                            echo isset($status_labels[$status]) ? $status_labels[$status] : $status;
                            ?>
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
                <h3 style="margin-top: 0; color: #28a745;">🚌 اطلاعات سرویس</h3>
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
                <h3 style="margin-top: 0; color: #28a745;">👥 اطلاعات مسافران</h3>
                <?php foreach ($passengers as $index => $passenger): ?>
                <div class="passenger-info">
                    <strong>مسافر <?php echo $index + 1; ?>:</strong><br>
                    <?php echo nl2br(esc_html($passenger)); ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Action Buttons -->
            <div style="text-align: center; margin: 30px 0;">
                <a href="<?php echo admin_url('admin.php?page=vsbbm-reservations'); ?>" class="button">📊 مشاهده همه رزروها</a>
                <a href="<?php echo admin_url('post.php?post=' . $order->get_id() . '&action=edit'); ?>" class="button">📝 ویرایش سفارش</a>
                <a href="<?php echo admin_url('users.php?s=' . urlencode($order->get_billing_email())); ?>" class="button">👤 مشاهده مشتری</a>
            </div>

            <!-- Alert for low availability -->
            <?php
            $total_seats = 32; // This should be configurable
            $available_seats = $total_seats - intval($stats->reserved_count) - intval($stats->confirmed_count);
            if ($available_seats < 5):
            ?>
            <div class="alert">
                <h4 style="margin-top: 0; color: #856404;">⚠️ هشدار کمبود صندلی</h4>
                <p style="margin: 5px 0;">تنها <strong><?php echo $available_seats; ?> صندلی</strong> خالی باقی مانده است!</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>© <?php echo date('Y'); ?> <?php echo get_bloginfo('name'); ?> - سیستم مدیریت رزرواسیون</p>
            <p>این ایمیل به صورت خودکار ارسال شده است.</p>
        </div>
    </div>
</body>
</html>