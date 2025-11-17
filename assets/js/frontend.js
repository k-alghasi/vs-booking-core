// اسکریپت‌های فرانت‌اند
jQuery(document).ready(function($) {
    // کدهای جاوااسکریپت می‌توانند اینجا باشند
    jQuery(document).ready(function($) {
    // مدیریت انتخاب صندلی
    $('.vsbbm-seat-available').on('click', function() {
        const seatNumber = $(this).data('seat');
        vsbbmHandleSeatSelection(seatNumber, $(this));
    });
});

function vsbbmHandleSeatSelection(seatNumber, element) {
    // اگر همین صندلی انتخاب شده، لغو انتخاب
    if (element.hasClass('vsbbm-seat-selected')) {
        element.removeClass('vsbbm-seat-selected');
        $('#vsbbm-selected-seat').hide();
        localStorage.removeItem('vsbbm_selected_seat');
        return;
    }
    
    // لغو انتخاب قبلی
    $('.vsbbm-seat-selected').removeClass('vsbbm-seat-selected');
    
    // انتخاب جدید
    element.addClass('vsbbm-seat-selected');
    $('#vsbbm-seat-number').text(seatNumber);
    $('#vsbbm-selected-seat').show();
    
    // ذخیره در localStorage
    localStorage.setItem('vsbbm_selected_seat', seatNumber);
}
});

