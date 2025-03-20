<?php

namespace App\Http\Controllers;

use App\Http\Resources\InventorySnapshotGroupResource;
use App\Http\Resources\InventorySnapshotItemResource;
use App\Http\Resources\ProductResource;
use App\Models\InventorySnapshotGroup;
use App\Models\InventorySnapshotItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class InventorySnapshotController extends Controller
{
    public function index()
    {
        return InventorySnapshotGroupResource::collection(InventorySnapshotGroup::latest()->paginate(10));
    }

    public function indexItems($id)
    {
        $group = InventorySnapshotGroup::find($id);
        if (!$group) {
            return response()->json(['message' => 'Inventory snapshot topilmadi.'], 404);
        }

        $items = InventorySnapshotItem::where('inventory_snapshot_group_id', $id)->get();

        return InventorySnapshotItemResource::collection($items);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $existingActive = InventorySnapshotGroup::where('store_id', $user->store_id)
            ->where('is_active', true)
            ->exists();

        if ($existingActive) {
            return response()->json([
                'message' => 'Tugallanmagan hisobot mavjud'
            ], 400);
        }

        $group = InventorySnapshotGroup::create([
            'user_id'  => $user->id,
            'store_id' => $user->store_id,
        ]);

        return response()->json([
            'message' => 'Inventory snapshot muvaffaqiyatli yaratildi!',
            'data' => $group
        ], 201);
    }

    public function addItem(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'stock_quantity' => 'required|numeric|min:0',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $product = Product::find($request->product_id);
        if (!$product) {
            return response()->json(['message' => 'Mahsulot topilmadi.'], 404);
        }

        $existingItem = InventorySnapshotItem::where('inventory_snapshot_group_id', $id)
            ->where('product_id', $request->product_id)
            ->exists();

        if ($existingItem) {
            return response()->json(['message' => 'Bu mahsulot ushbu hisobotda allaqachon mavjud!'], 400);
        }

        $item = InventorySnapshotItem::create([
            'inventory_snapshot_group_id' => $id,
            'product_id'                  => $product->id,
            'base_quantity'               => $product->quantity,
            'stock_quantity'              => $request->stock_quantity
        ]);

        return response()->json([
            'message' => 'Mahsulot muvaffaqiyatli qo‘shildi!',
            'data' => $item
        ], 201);
    }

    public function showBarcode($id)
    {
        $product = Product::where('barcode', $id)->where('is_active', true)->first();
        if (!$product) {
            return response()->json([
                'error' => "Product not found"
            ], 404);
        }

        return new ProductResource($product);
    }

    public function completeInventoryGroup($group_id)
    {
        $items = InventorySnapshotItem::where('inventory_snapshot_group_id', $group_id)->get();

        $products = Product::where('quantity', '>', 0)->where('is_active', true)->get();

        $itemProductIds = $items->pluck('product_id')->toArray();

        $newItems = [];

        foreach ($products as $product) {
            $isInItems = in_array($product->id, $itemProductIds);

            if (!$isInItems) {
                $newItems[] = [
                    'inventory_snapshot_group_id' => $group_id,
                    'product_id' => $product->id,
                    'base_quantity' => $product->quantity,
                    'stock_quantity' => 0,
                    'not_selected' => true,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }
        }

        if (!empty($newItems)) {
            InventorySnapshotItem::insert($newItems);
        }

        InventorySnapshotGroup::where('id', $group_id)->update([
            'is_active' => false
        ]);

        return response()->json(['message' => 'Inventory group completed successfully'], 200);
    }

    public function exportXlsx($id)
    {
        $products = InventorySnapshotItem::where('inventory_snapshot_group_id', $id)->get();

        if ($products->isEmpty()) {
            return response()->json(['message' => 'Malumot topilmadi'], 404);
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = [
            'ID',
            'Brend',
            'Kategoriya',
            'Taminotchi',
            'Qabul qiluvchi',
            'Holati',
            'Nomi',
            'Barkod',
            'Asosiy miqdori',
            'Ombordagi miqdori'
        ];

        $sheet->fromArray([$headers], null, 'A1');

        $row = 2;
        foreach ($products as $item) {
            $data = [
                $item->product_id,
                optional($item->product->brand)->name ?? 'Noma’lum',
                optional($item->product->category)->name ?? 'Noma’lum',
                optional($item->product->supplier)->name ?? 'Noma’lum',
                optional($item->product->user)->name ?? 'Noma’lum',
                match ($item->product->condition) {
                    'new' => 'Yangi',
                    'used' => 'Ishlatilgan',
                    'openbox' => 'Ochilgan',
                    default => 'Noma’lum'
                },
                $item->product->name,
                " " . $item->product->barcode,
                $item->base_quantity === 0 ? '0' : $item->base_quantity,
                $item->not_selected ? '---' : ($item->stock_quantity === 0 ? '0' : $item->stock_quantity),

            ];

            $sheet->fromArray([$data], null, 'A' . $row);

            if ($item->base_quantity !== $item->stock_quantity) {
                $sheet->getStyle('J' . $row)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB(Color::COLOR_YELLOW);
            }
            if ($item->not_selected) {
                $sheet->getStyle('J' . $row)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB(Color::COLOR_RED);
            }

            $row++;
        }

        foreach (range('A', 'J') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        $sheet->getStyle('A1:J' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $fileName = 'remaining_stock_' . now()->format("d-m-Y_H-i-s") . '.xlsx';

        $writer = new Xlsx($spreadsheet);
        $filePath = storage_path('app/public/' . $fileName);
        $writer->save($filePath);

        return response()->download($filePath)->deleteFileAfterSend(true);
    }
}
