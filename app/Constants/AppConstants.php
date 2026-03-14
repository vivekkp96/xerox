<?php

namespace App\Constants;

class AppConstants
{
    const OTP_VALIDITY_MINUTES = 10;
    const TOKEN_VALIDITY_DAYS = 2 * 24 * 60 * 60;
    const ADMIN_TOKEN_VALIDITY_DAYS = 2 * 24 * 60 * 60;
    const PUBLIC='public';
    const PRIVATE='private';

    const ORDER_PAYMENT_STATUS_PENDING = 'Pending';
    const ORDER_PAYMENT_STATUS_PAID = 'Paid';
    const ORDER_PAYMENT_STATUS_REFUNDED = 'Refunded';
    const ORDER_PAYMENT_STATUS_PAYMENT_VERIFIED = 'Payment Verified';

    const ORDER_STATUS_CANCELLED = 'Cancelled';
    const ORDER_STATUS_COMPLETED = 'Completed';
    const ORDER_STATUS_PENDING = 'Pending';
    const ORDER_STATUS_PROCESSING = 'Processing';

    const ADMIN_ROLE_ADMIN = 'Admin';
    const ADMIN_ROLE_SUPER_ADMIN = 'Super Admin';

    const USER_LAST_ACTIVITY_MINUTES = 5;


    const CALCULATE_USER_PEAK_LIST_MINUTES = 30;
    
    const CALCULATE_USER_PEAK_LIST_ACTIVE_COUNT = 10;
    
}
