<div class="wrap vsbbm-admin-dashboard">
    <h1>داشبورد مدیریت رزرو اتوبوس</h1>
    
    <!-- کارت‌های آماری -->
    <div class="vsbbm-stats-grid">
        <div class="stat-card">
            <div class="stat-icon">📊</div>
            <div class="stat-content">
                <h3>تعداد رزروها</h3>
                <span class="stat-number"><?php echo number_format($stats['total_bookings']); ?></span>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">💰</div>
            <div class="stat-content">
                <h3>درآمد کل</h3>
                <span class="stat-number"><?php echo wc_price($stats['total_revenue']); ?></span>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">👥</div>
            <div class="stat-content">
                <h3>تعداد مسافران</h3>
                <span class="stat-number"><?php echo number_format($stats['total_passengers']); ?></span>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">🪑</div>
            <div class="stat-content">
                <h3>نرخ اشغال</h3>
                <span class="stat-number"><?php echo $stats['occupancy_rate']; ?>%</span>
            </div>
        </div>
    </div>
    
    <!-- نمودارها -->
    <div class="vsbbm-charts">
        <div class="chart-container">
            <h3>رزروهای ۷ روز اخیر</h3>
            <canvas id="bookingsChart"></canvas>
        </div>
        
        <div class="chart-container">
            <h3>توزیع وضعیت رزروها</h3>
            <canvas id="statusChart"></canvas>
        </div>
    </div>
    
    <!-- آخرین رزروها -->
    <div class="recent-bookings">
        <h3>آخرین رزروها</h3>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>شماره سفارش</th>
                    <th>تاریخ</th>
                    <th>مسافر</th>
                    <th>صندلی‌ها</th>
                    <th>مبلغ</th>
                    <th>وضعیت</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_bookings as $booking) : ?>
                <tr>
                    <td><?php echo $booking->order_id; ?></td>
                    <td><?php echo date('Y/m/d H:i', strtotime($booking->post_date)); ?></td>
                    <td><?php echo $booking->display_name; ?></td>
                    <td>...</td>
                    <td>...</td>
                    <td><span class="status-badge status-<?php echo str_replace('wc-', '', $booking->post_status); ?>">
                        <?php echo self::get_status_label($booking->post_status); ?>
                    </span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // نمودار رزروهای ۷ روز اخیر
    const bookingsCtx = document.getElementById('bookingsChart').getContext('2d');
    new Chart(bookingsCtx, {
        type: 'line',
        data: {
            labels: ['شنبه', 'یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه'],
            datasets: [{
                label: 'تعداد رزروها',
                data: [12, 19, 8, 15, 22, 18, 14],
                borderColor: '#4caf50',
                tension: 0.3
            }]
        }
    });
    
    // نمودار وضعیت رزروها
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: ['تکمیل شده', 'در حال انجام', 'در انتظار پرداخت'],
            datasets: [{
                data: [45, 30, 25],
                backgroundColor: ['#4caf50', '#2196f3', '#ff9800']
            }]
        }
    });
});
</script>