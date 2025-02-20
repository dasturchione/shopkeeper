<?php
namespace App\Services;

class CalculatorService
{
    public static function convertPrice($price, $currency)
    {
        return $price * ($currency ?? 0);
    }

    public static function calculateItemsInfo($items)
    {
        $total_price = $items->sum(function ($item) {
            return ($item->sale_price * $item->quantity) - $item->discount;
        });

        $total_quantity = $items->sum('quantity');

        return [
            'total_price'    => $total_price,
            'total_quantity' => $total_quantity,
        ];
    }
}