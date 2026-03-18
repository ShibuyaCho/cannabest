<?php

if (! function_exists('getDiscountedPrice')) {
    /**
     * Returns the discounted price for a product based on quantity.
     *
     * It checks all discount tiers (stored globally) and returns the price
     * from the pricing option with the highest min_quantity threshold that the quantity meets.
     *
     * @param float $basePrice
     * @param float $quantity
     * @return float
     */
    function getDiscountedPrice($basePrice, $quantity)
    {
        // Retrieve the discount tiers setting
        $discountSetting = \App\Setting::where('key', 'discount_tiers')->first();
        if (!$discountSetting) {
            return $basePrice;
        }
    
        $tiers = json_decode($discountSetting->value, true);
        if (!is_array($tiers)) {
            return $basePrice;
        }
    
        // We'll loop through all pricing options in all tiers and find the one with the highest min_quantity that qualifies.
        $bestOption = null;
        $highestThreshold = 0;
    
        foreach ($tiers as $tier) {
            if (isset($tier['pricing']) && is_array($tier['pricing'])) {
                foreach ($tier['pricing'] as $option) {
                    // Convert the min_quantity to a float.
                    $minQty = floatval($option['min_quantity'] ?? 0);
                    // Check if the purchased quantity meets the threshold and if it's higher than our current best threshold.
                    if ($quantity >= $minQty && $minQty > $highestThreshold) {
                        $highestThreshold = $minQty;
                        $bestOption = $option;
                    }
                }
            }
        }
    
        // If we found an option that qualifies, return its price; otherwise, return the base price.
        if ($bestOption !== null) {
            return floatval($bestOption['price']);
        }
    
        return $basePrice;
    }
}