<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\SoldGroup;
use App\Models\SoldItem;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{

    public function getWidgetInfo()
    {
        $user = Auth::user();
        $products = Product::where('store_id', $user->store_id)
            ->where('is_active', true)->sum('quantity');

        $soldGroupReal = SoldItem::whereHas('soldGroup', function ($query) use ($user) {
            $query->where('is_real', true)
                ->where('store_id', $user->store_id);
        })->sum('quantity');

        return response()->json([
            'product'   =>  (int) $products,
            'real'      =>  (int) $soldGroupReal
        ]);
    }

    public function getOrdersByDay(Request $request)
    {

        $user = Auth::user();
        $filter = $request->query('filter', Carbon::now()->format('Y-m'));

        [$currentYear, $currentMonth] = explode('-', $filter);

        $daysInMonth = Carbon::create($currentYear, $currentMonth, 1)->daysInMonth;

        $dates = [];
        $days = [];
        for ($day = 1; $day <= $daysInMonth; $day++) {

            $dates[] = Carbon::create($currentYear, $currentMonth, $day)->format('Y-m-d');
            $days[] = Carbon::createFromFormat('Y-m-d', "{$currentYear}-{$currentMonth}-{$day}")->format('d-M');
        }

        $ordersCount = [
            'paid' => [],
            'unpaid' => [],
        ];

        foreach ($dates as $date) {
            $formattedDate = $date;
            $ordersCount['total_income_cash_register'][] = SoldGroup::whereDate('sold_groups.created_at', $formattedDate)
                ->where('sold_groups.status', true)
                ->where('sold_groups.store_id', $user->store_id)
                ->join('courses', 'sold_groups.course_id', '=', 'courses.id')
                ->sum(DB::raw('(COALESCE(sold_groups.convertcurrency, 0) / COALESCE(courses.rate, 1)) + COALESCE(sold_groups.maincurrency, 0)'));

            $ordersCount['paid'][] = SoldItem::whereHas('soldGroup', function ($query) use ($formattedDate, $user) {
                $query->whereDate('created_at', $formattedDate)
                    ->where('status', true)
                    ->where('store_id', $user->store_id);
            })
                ->sum(DB::raw('(sale_price * quantity) - COALESCE(discount, 0)'));

            $ordersCount['expenses'][] = SoldItem::whereHas('soldGroup', function ($query) use ($formattedDate, $user) {
                $query->whereDate('created_at', $formattedDate)
                    ->where('status', true)
                    ->where('store_id', $user->store_id);
            })
                ->sum(DB::raw('(in_price * quantity)'));

            $ordersCount['unpaid'][] = SoldItem::whereHas('soldGroup', function ($query) use ($formattedDate, $user) {
                $query->whereDate('created_at', $formattedDate)
                    ->where('status', false)
                    ->where('store_id', $user->store_id);
            })
                ->sum(DB::raw('(sale_price * quantity) - COALESCE(discount, 0)'));
        }

        return response()->json([
            'dates' => $days,
            'total_income' => array_sum($ordersCount['paid']),
            'total_income_cash_register' => array_sum($ordersCount['total_income_cash_register']),
            'total_expenses' => array_sum($ordersCount['expenses']),
            'total_profit' => array_sum($ordersCount['paid']) - array_sum($ordersCount['expenses']),
            'total_profit_cash_register' => array_sum($ordersCount['total_income_cash_register']) - array_sum($ordersCount['expenses']),
            'unplanned_profit' => max(
                0,
                (array_sum($ordersCount['total_income_cash_register']) - array_sum($ordersCount['expenses'])) -
                    (array_sum($ordersCount['paid']) - array_sum($ordersCount['expenses']))
            ),
            'total_unpaid' => array_sum($ordersCount['unpaid']),
            'series' => [
                [
                    'name' => 'paid',
                    'data' => $ordersCount['paid']
                ],
                [
                    'name' => 'un_paid',
                    'data' => $ordersCount['unpaid']
                ]
            ],
        ]);
    }

    public function exportTopSoldProducts(Request $request)
    {
        $month = $request->query('filter'); // Masalan, "03-2025"

        if (!$month) {
            return response()->json(['error' => 'Oy va yil berilishi shart!'], 400);
        }

        [$monthNum, $year] = explode('-', $month);

        // Eng ko‘p sotilgan mahsulotlar ro‘yxati
        $products = SoldItem::select('product_id', DB::raw('SUM(quantity) as total_sold'))
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $monthNum)
            ->groupBy('product_id')
            ->orderByDesc('total_sold')
            ->with('product') // Mahsulot ma'lumotlarini olish
            ->get();

        return response()->json([
            'data' => $products->map(function ($item) {
                return [
                    'id'   => $item->product_id,
                    'name' => optional($item->product)->name ?? 'Noma’lum',
                    'quantity' => (int) $item->total_sold,
                    'in_price'  => (int) $item->product->in_price,
                    'sale_price'  => (int)  $item->product->sale_price,
                    'total_in_price'  => $item->product->in_price * $item->total_sold,
                    'total_sale_price'  => $item->product->sale_price * $item->total_sold,
                ];
            }),
        ]);
    }

    public function getFilterOptions()
    {
        $soldMonths = SoldGroup::selectRaw("DATE_FORMAT(created_at, '%Y-%m') as value, DATE_FORMAT(created_at, '%M %Y') as label")
            ->groupBy('value', 'label')
            ->orderByRaw("MIN(created_at) Desc")
            ->get();

        return response()->json([
            "filter" => $soldMonths
        ]);
    }

    public function getOrdersByYear(Request $request)
    {
        $user = Auth::user();
        $currentYear = $request->query('filter', Carbon::now()->format('Y')); // Yilni olish

        $months = [];
        for ($month = 1; $month <= 12; $month++) {
            $months[] = Carbon::create($currentYear, $month, 1)->format('M'); // Yan, Fev, Mart...
        }

        $ordersCount = [
            'paid' => [],
            'unpaid' => [],
            'expenses' => [],
            'total_income_cash_register' => []
        ];

        for ($month = 1; $month <= 12; $month++) {
            $formattedMonth = Carbon::create($currentYear, $month, 1)->format('Y-m');

            $ordersCount['total_income_cash_register'][] = SoldGroup::whereYear('sold_groups.created_at', $currentYear)
                ->whereMonth('sold_groups.created_at', $month)
                ->where('sold_groups.status', true)
                ->where('sold_groups.store_id', $user->store_id)
                ->join('courses', 'sold_groups.course_id', '=', 'courses.id')
                ->sum(DB::raw('(COALESCE(sold_groups.convertcurrency, 0) / COALESCE(courses.rate, 1)) + COALESCE(sold_groups.maincurrency, 0)'));

            $ordersCount['paid'][] = SoldItem::whereHas('soldGroup', function ($query) use ($currentYear, $month, $user) {
                $query->whereYear('created_at', $currentYear)
                    ->whereMonth('created_at', $month)
                    ->where('status', true)
                    ->where('store_id', $user->store_id);
            })
                ->sum(DB::raw('(sale_price * quantity) - COALESCE(discount, 0)'));

            $ordersCount['expenses'][] = SoldItem::whereHas('soldGroup', function ($query) use ($currentYear, $month, $user) {
                $query->whereYear('created_at', $currentYear)
                    ->whereMonth('created_at', $month)
                    ->where('status', true)
                    ->where('store_id', $user->store_id);
            })
                ->sum(DB::raw('(in_price * quantity)'));

            $ordersCount['unpaid'][] = SoldItem::whereHas('soldGroup', function ($query) use ($currentYear, $month, $user) {
                $query->whereYear('created_at', $currentYear)
                    ->whereMonth('created_at', $month)
                    ->where('status', false)
                    ->where('store_id', $user->store_id);
            })
                ->sum(DB::raw('(sale_price * quantity) - COALESCE(discount, 0)'));
        }

        return response()->json([
            'months' => $months,
            'total_income' => array_sum($ordersCount['paid']),
            'total_income_cash_register' => array_sum($ordersCount['total_income_cash_register']),
            'total_expenses' => array_sum($ordersCount['expenses']),
            'total_profit' => array_sum($ordersCount['paid']) - array_sum($ordersCount['expenses']),
            'total_profit_cash_register' => array_sum($ordersCount['total_income_cash_register']) - array_sum($ordersCount['expenses']),
            'unplanned_profit' => max(
                0,
                (array_sum($ordersCount['total_income_cash_register']) - array_sum($ordersCount['expenses'])) -
                    (array_sum($ordersCount['paid']) - array_sum($ordersCount['expenses']))
            ),
            'total_unpaid' => array_sum($ordersCount['unpaid']),
            'series' => [
                [
                    'name' => 'paid',
                    'data' => $ordersCount['paid']
                ],
                [
                    'name' => 'un_paid',
                    'data' => $ordersCount['unpaid']
                ]
            ],
        ]);
    }

    public function getFilterOptionsYear()
    {
        $soldYears = SoldGroup::selectRaw("DATE_FORMAT(created_at, '%Y') as value, DATE_FORMAT(created_at, '%Y') as label")
            ->groupBy('value', 'label')
            ->orderByRaw("MIN(created_at) DESC")
            ->get();

        return response()->json([
            "filter" => $soldYears
        ]);
    }
}
