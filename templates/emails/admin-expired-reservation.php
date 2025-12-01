<?php
/**
 * Admin Expired Reservation Email Template
 */
if (!defined('ABSPATH')) exit;
?>

<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php _e('رزرو منقضی شده', 'vs-bus-booking-manager'); ?></title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; }
        .header { background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white; padding: 30px 20px; text-align: center; }
        .content { padding: 30px 20px; color: #333; line-height: 1.6; }
        .reservation-details { background: #f8d7da; padding: 20px; border-radius: 8px; margin: 20px 0; border-right: 4px solid #dc3545; }
        .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #666; font-size: 14px; }
        .button { display: inline-block; background: #dc3545; color: white; padding: 12px 25px; text-decoration: none; border-radius: 6px; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1 style="margin: 0; font-size: 28px;">⏰ رزرو منقضی شده</h1>
            <p style="margin: 10px 0 0 0; font-size: 16px;">یک رزرو به دلیل عدم پرداخت منقضی شد</p>
        </div>

        <!-- Content -->
        <div class="content">
            <p>مدیر محترم،</p>

            <p>یک رزرو صندلی به دلیل عدم تکمیل پرداخت در زمان مقرر منقضی شده است.</p>

            <!-- Reservation Details -->
            <div class="reservation-details">
                <h3 style="margin-top: 0; color: #721c24;">📋 جزئیات رزرو منقضی شده</h3>
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 8px 0; border-bottom: 1px solid #f5c6cb;"><strong>شناسه رزرو:</strong></td>
                        <td style="padding: 8px 0; border-bottom: 1px solid #f5c6cb;">#<?php echo $reservation->id; ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; border-bottom: 1px solid #f5c6cb;"><strong>محصول:</strong></td>
                        <td style="padding: 8px 0; border-bottom: 1px solid #f5c6cb;"><?php echo esc_html($reservation->product_name); ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; border-bottom: 1px solid #f5c6cb;"><strong>صندلی:</strong></td>
                        <td style="padding: 8px 0; border-bottom: 1px solid #f5c6cb;"><?php echo $reservation->seat_number; ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; border-bottom: 1px solid #f5c6cb;"><strong>کاربر:</strong></td>
                        <td style="padding: 8px 0; border-bottom: 1px solid #f5c6cb;">
                            <?php echo $reservation->user_id ? 'کاربر #' . $reservation->user_id : 'مهمان'; ?>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0;"><strong>زمان رزرو:</strong></td>
                        <td style="padding: 8px 0;"><?php echo date('Y/m/d H:i', strtotime($reservation->reserved_at)); ?></td>
                    </tr>
                </table>
            </div>

            <p>صندلی مربوطه اکنون برای رزرو مجدد آزاد شده است.</p>

            <div style="text-align: center; margin: 30px 0;">
                <a href="<?php echo admin_url('admin.php?page=vsbbm-reservations'); ?>" class="button">👁️ مشاهده رزروها</a>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>© <?php echo date('Y'); ?> <?php echo get_bloginfo('name'); ?> - سیستم مدیریت رزرواسیون</p>
            <p>این ایمیل به صورت خودکار ارسال شده است.</p>
        </div>
    </div>
</body>
</html>