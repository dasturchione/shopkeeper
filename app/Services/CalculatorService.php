<?php

namespace App\Services;

class CalculatorService
{
    public static function convertPrice($price, $currency, $type = "UZS")
    {
        if ($currency <= 0) {
            return 0; // Xatolikning oldini olish uchun
        }

        return $type === "UZS" ? $price * $currency : $price / $currency;
    }

    public static function calculateItemsInfo($items, $main, $convert, $currency = null)
    {
        $total_price = $items->sum(fn($item) => ($item->sale_price * $item->quantity) - $item->discount);
        $total_quantity = $items->sum('quantity');

        $convertedToUSD = self::convertPrice($convert ?: 0, $currency, "USD");

        return [
            'total_price'         => $total_price,
            'paid_price'          => ($main ?: 0) + round($convertedToUSD, 2),
            'total_convert_price' => self::convertPrice($total_price, $currency),
            'total_quantity'      => $total_quantity,
        ];
    }
}
