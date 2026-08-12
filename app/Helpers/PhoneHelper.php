<?php

namespace App\Helpers;

class PhoneHelper
{
    public static function clean($phone)
    {
        if (empty($phone)) {
            return 'N/A';
        }
        $cleaned = preg_replace('/[^\d+\-\s]/u', '', $phone);

        return trim(preg_replace('/\s+/', ' ', $cleaned));
    }
}