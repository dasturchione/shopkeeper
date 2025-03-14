<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;

class ActionResource extends JsonResource
{
    public static function getName($model, $id, $default = 'Noma’lum')
    {
        return $model::find($id)?->name ?? $default;
    }

    public function toArray(Request $request): array
    {
        $data = json_decode($this->data, true);

        if ($this->action_type === 'edit_product' && isset($data['old'], $data['new'])) {
            foreach (['old', 'new'] as $type) {
                if (isset($data[$type]['brand_id'])) {
                    $data[$type]['brand_name'] = self::getName(Brand::class, $data[$type]['brand_id']);
                }
                if (isset($data[$type]['category_id'])) {
                    $data[$type]['category_name'] = self::getName(Category::class, $data[$type]['category_id']);
                }
                if (isset($data[$type]['supplier_id'])) {
                    $data[$type]['supplier_name'] = self::getName(Supplier::class, $data[$type]['supplier_id']);
                }
                if (isset($data[$type]['receiver_id'])) {
                    $data[$type]['receiver_name'] = self::getName(User::class, $data[$type]['receiver_id']);
                }
            }
        } elseif ($this->action_type === 'sale_product'){
            $data['product_name'] = self::getName(Product::class, $data['product_id']);
        } else {
            // Oddiy holatlar uchun
            if (isset($data['brand_id'])) {
                $data['brand_name'] = self::getName(Brand::class, $data['brand_id']);
            }
            if (isset($data['category_id'])) {
                $data['category_name'] = self::getName(Category::class, $data['category_id']);
            }
            if (isset($data['supplier_id'])) {
                $data['supplier_name'] = self::getName(Supplier::class, $data['supplier_id']);
            }
            if (isset($data['receiver_id'])) {
                $data['receiver_name'] = self::getName(User::class, $data['receiver_id']);
            }
        }

        return [
            'id' => $this->id,
            'product_id' => $this->actionable_id,
            'action_type' => $this->action_type,
            'data' => $data,
            'created_at' => $this->created_at->format('d-m-Y H:i:s'),
        ];
    }
}
