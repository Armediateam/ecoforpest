<?php

/**
 * Temporary NumberToWords helper
 * This is a placeholder to resolve autoload issues
 */

if (!function_exists('numberToWords')) {
    function numberToWords($number)
    {
        return 'Number: ' . number_format($number);
    }
}
