<?php

namespace App\Helpers;

class CurrencyHelper
{
    /**
     * Determines the appropriate currency symbol based on application configuration or country.
     * For this application, we default to UGX and can be extended later.
     *
     * @param float $amount The numerical value to format.
     * @return string The formatted currency string.
     */
    public static function format($amount)
    {
        // 1. Define the currency settings (Defaulting to Uganda)
        // In a real application, this logic would check the user's IP/locale.
        $currencySymbol = env('APP_CURRENCY_SYMBOL', 'UGX');
        
        // You could use a more dynamic system here:
        /*
        $locale = app()->getLocale(); // e.g., 'en'
        $country = geoip()->getLocation()->iso_code; // Requires a package like stevebauman/location

        $currencyMap = [
            'US' => ['symbol' => '$', 'code' => 'USD', 'format' => 2],
            'KE' => ['symbol' => 'KSh', 'code' => 'KES', 'format' => 0],
            'UG' => ['symbol' => 'UGX', 'code' => 'UGX', 'format' => 0],
            // ... add more countries
        ];
        
        $settings = $currencyMap[$country] ?? $currencyMap['UG']; // Fallback
        $formattedAmount = number_format($amount, $settings['format']);
        return $settings['symbol'] . ' ' . $formattedAmount;
        */

        // For now, we stick to the basic required format used in your views:
        $formattedAmount = number_format($amount, 0); // Assuming 0 decimal places for UGX
        return $currencySymbol . ' ' . $formattedAmount;
    }
}
