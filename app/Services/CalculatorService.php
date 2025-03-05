<?php

namespace App\Services;

use App\Models\Product;

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

    public function generateUniqueBarcode() {
        do {
            $barcodeBase = '478' . str_pad(rand(0, 999999999), 9, '0', STR_PAD_LEFT);
            $checksum = $this->calculateEAN13Checksum($barcodeBase);
            $barcode = $barcodeBase . $checksum;
        } while (Product::where('barcode', $barcode)->exists());
    
        return $barcode;
    }
    
    private function calculateEAN13Checksum($code) {
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int)$code[$i] * ($i % 2 == 0 ? 1 : 3);
        }
        return (10 - ($sum % 10)) % 10;
    }
    
}
