<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Services\ShopifyApiService;

class DashboardController extends Controller
{
    public function showLiveViews()
    {
        $shopifyService = new ShopifyApiService();

        try {
            $analyticsData = $shopifyService->getLiveAnalyticsData();

            return response()->json([
                'activeVisitors' => $analyticsData['activeVisitors'] ?? 0,
                'pageViews' => $analyticsData['pageViews'] ?? 0,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // public function index()
    // {
    //     $today = now()->startOfDay();

    //     $totalOrders = Order::where('financial_status', 'paid')->count();
    //     $totalUniqueCustomers = Customer::distinct('email')->count('email');

    //     // Get today's orders
    //     $todaysOrders = Order::where('order_date', '>=', $today)
    //         ->where('financial_status', 'paid')
    //         ->get();
    //     $totalAmountToday = $todaysOrders->sum('total_price');
    //     $totalOrdersToday = $todaysOrders->count();

    //     $recentOrders = Order::orderBy('order_date', 'desc')
    //         ->where('financial_status', 'paid')
    //         ->take(6)
    //         ->get();

    //     // 1-Day Sales Data (Hourly)
    //     $oneDaySales = Order::where('financial_status', 'paid')
    //         ->where('order_date', '>=', $today)
    //         ->selectRaw('HOUR(order_date) as hour, SUM(total_price) as total_sales')
    //         ->groupBy('hour')
    //         ->get();

    //     // 5-Day Sales Data (Daily)
    //     $fiveDaysSales = Order::where('financial_status', 'paid')
    //         ->where('order_date', '>=', $today->copy()->subDays(5))
    //         ->selectRaw('DATE(order_date) as date, SUM(total_price) as total_sales')
    //         ->groupBy('date')
    //         ->get();

    //     // 1-Month Sales Data (Daily)
    //     $oneMonthSales = Order::where('financial_status', 'paid')
    //         ->where('order_date', '>=', $today->copy()->subDays(30))
    //         ->selectRaw('DATE(order_date) as date, SUM(total_price) as total_sales')
    //         ->groupBy('date')
    //         ->get();

    //     // 6-Month Sales Data (Monthly)
    //     $sixMonthsSales = Order::where('financial_status', 'paid')
    //         ->where('order_date', '>=', $today->copy()->subMonths(6))
    //         ->selectRaw('DATE_FORMAT(order_date, "%Y-%m") as month, SUM(total_price) as total_sales')
    //         ->groupBy('month')
    //         ->get();

    //     // 1-Year Sales Data (Monthly)
    //     $oneYearSales = Order::where('financial_status', 'paid')
    //         ->where('order_date', '>=', $today->copy()->subYear())
    //         ->selectRaw('DATE_FORMAT(order_date, "%Y-%m") as month, SUM(total_price) as total_sales')
    //         ->groupBy('month')
    //         ->get();

    //     // Convert data to format suitable for charts
    //     $oneDaySalesData = [
    //         'labels' => $oneDaySales->pluck('hour')->map(function ($hour) {
    //             return Carbon::createFromTime($hour)->format('gA');
    //         })->toArray(),
    //         'sales' => $oneDaySales->pluck('total_sales')->toArray(),
    //     ];

    //     $fiveDaysSalesData = [
    //         'labels' => $fiveDaysSales->pluck('date')->map(function ($date) {
    //             return Carbon::parse($date)->format('M d');
    //         })->toArray(),
    //         'sales' => $fiveDaysSales->pluck('total_sales')->toArray(),
    //     ];

    //     $oneMonthSalesData = [
    //         'labels' => $oneMonthSales->pluck('date')->map(function ($date) {
    //             return Carbon::parse($date)->format('M d');
    //         })->toArray(),
    //         'sales' => $oneMonthSales->pluck('total_sales')->toArray(),
    //     ];

    //     $sixMonthsSalesData = [
    //         'labels' => $sixMonthsSales->pluck('month')->map(function ($month) {
    //             return Carbon::parse($month . '-01')->format('M Y');
    //         })->toArray(),
    //         'sales' => $sixMonthsSales->pluck('total_sales')->toArray(),
    //     ];

    //     $oneYearSalesData = [
    //         'labels' => $oneYearSales->pluck('month')->map(function ($month) {
    //             return Carbon::parse($month . '-01')->format('M Y');
    //         })->toArray(),
    //         'sales' => $oneYearSales->pluck('total_sales')->toArray(),
    //     ];

    //     $startOfMonth = now()->startOfMonth();
    //     $startOfSixMonths = now()->subMonths(6)->startOfMonth();
    //     $startOfYear = now()->startOfYear();

    //     // Total Sales Amounts Calculation
    //     $totalAmountToday = Order::where('financial_status', 'paid')
    //         ->where('order_date', '>=', $today)
    //         ->sum('total_price');

    //     $totalAmountFiveDays = Order::where('financial_status', 'paid')
    //         ->where('order_date', '>=', $today->copy()->subDays(5))
    //         ->sum('total_price');

    //     $totalAmountOneMonth = Order::where('financial_status', 'paid')
    //         ->where('order_date', '>=', $startOfMonth)
    //         ->sum('total_price');

    //     $totalAmountSixMonths = Order::where('financial_status', 'paid')
    //         ->where('order_date', '>=', $startOfSixMonths)
    //         ->sum('total_price');

    //     $totalAmountOneYear = Order::where('financial_status', 'paid')
    //         ->where('order_date', '>=', $startOfYear)
    //         ->sum('total_price');

    //     // Prepare the data to pass to the view
    //     $salesAmounts = [
    //         'oneD' => $totalAmountToday,
    //         'fiveDd' => $totalAmountFiveDays,
    //         'oneM' => $totalAmountOneMonth,
    //         'sixM' => $totalAmountSixMonths,
    //         'oneY' => $totalAmountOneYear
    //     ];

    //     $province = ['Delhi', 'Karnataka', 'Telangana'];

    //     $salesByStateToday = DB::table('orders')
    //         ->join('billing_addresses', 'orders.id', '=', 'billing_addresses.order_id')
    //         ->where('orders.financial_status', 'paid')
    //         ->where('orders.order_date', '>=', $today)
    //         ->whereIn('billing_addresses.province', $province)
    //         ->select(
    //             'billing_addresses.province as state',
    //             DB::raw('SUM(orders.total_price) as total_sales'),
    //             DB::raw('COUNT(orders.id) as total_orders')
    //         )
    //         ->groupBy('billing_addresses.province')
    //         ->orderBy('total_sales', 'desc')
    //         ->get();

    //     $salesData = collect($province)->map(function ($state) use ($salesByStateToday) {
    //         $stateData = $salesByStateToday->firstWhere('state', $state);
    //         return (object) [
    //             'state' => $state,
    //             'total_sales' => $stateData->total_sales ?? 0,
    //             'total_orders' => $stateData->total_orders ?? 0,
    //         ];
    //     });

    //     // Total sales for Karnataka
    //     $totalSalesKarnataka = DB::table('orders')
    //         ->join('billing_addresses', 'orders.id', '=', 'billing_addresses.order_id')
    //         ->where('orders.financial_status', 'paid')
    //         ->where('billing_addresses.province', 'Karnataka')
    //         ->sum('orders.total_price');

    //     // Monthly sales for the last year
    //     $bangaloreSales = DB::table('orders')
    //         ->join('billing_addresses', 'orders.id', '=', 'billing_addresses.order_id')
    //         ->where('orders.financial_status', 'paid')
    //         ->where('billing_addresses.province', 'Karnataka')
    //         ->where('orders.order_date', '>=', Carbon::now()->subYear())
    //         ->selectRaw('DATE_FORMAT(order_date, "%Y-%m") as month, SUM(orders.total_price) as total_sales')
    //         ->groupBy('month')
    //         ->orderBy('month', 'asc')
    //         ->get();

    //     $bangaloreSalesData = [
    //         'labels' => $bangaloreSales->pluck('month')->toArray(),
    //         'sales' => $bangaloreSales->pluck('total_sales')->toArray(),
    //     ];


    //     $data = [
    //         'salesAmounts'              => $salesAmounts,
    //         'totalAmountToday'          => $totalAmountToday,
    //         'salesData'      => $salesData,
    //         'bangaloreSalesData'      => $bangaloreSalesData,
    //         'totalSalesKarnataka'      => $totalSalesKarnataka,
    //         'totalOrdersToday'          => $totalOrdersToday,
    //         'totalOrders'               => $totalOrders,
    //         'recentOrders'              => $recentOrders,
    //         'totalCustomers'            => $totalUniqueCustomers,
    //         'oneDaySalesData'           => $oneDaySalesData,
    //         'fiveDaysSalesData'         => $fiveDaysSalesData,
    //         'oneMonthSalesData'         => $oneMonthSalesData,
    //         'sixMonthsSalesData'        => $sixMonthsSalesData,
    //         'oneYearSalesData'          => $oneYearSalesData,
    //     ];

    //     // Pass the data to the view
    //     return view('admin.dashboard', $data);
    // }

    public function index()
    {
        $today = now()->startOfDay();
        $adminCity = Auth('admin')->user()->city;

        // Base query for orders
        $query = Order::where('financial_status', 'paid');

        // Filter orders by admin's city, if provided
        if (!empty($adminCity)) {
            $query->where(function ($query) use ($adminCity) {
                $query->whereHas('shippingAddress', function ($query) use ($adminCity) {
                    $query->where('province', $adminCity);
                })->orWhereHas('billingAddress', function ($query) use ($adminCity) {
                    $query->where('province', $adminCity);
                });
            });
        }

        $totalOrders = $query->count();
        $totalUniqueCustomers = Customer::distinct('email')->count('email');

        // Get today's orders
        $todaysOrders = $query->where('order_date', '>=', $today)->get();
        $totalAmountToday = $todaysOrders->sum('total_price');
        $totalOrdersToday = $todaysOrders->count();

        $recentOrders = $query->orderBy('order_date', 'desc')->take(6)->get();

        // 1-Day Sales Data (Hourly)
        $oneDaySales = (clone $query)
            ->where('order_date', '>=', $today)
            ->selectRaw('HOUR(order_date) as hour, SUM(total_price) as total_sales')
            ->groupBy('hour')
            ->get();

        // 5-Day Sales Data (Daily)
        $fiveDaysSales = (clone $query)
            ->where('order_date', '>=', $today->copy()->subDays(5))
            ->selectRaw('DATE(order_date) as date, SUM(total_price) as total_sales')
            ->groupBy('date')
            ->get();

        // 1-Month Sales Data (Daily)
        $oneMonthSales = (clone $query)
            ->where('order_date', '>=', $today->copy()->subDays(30))
            ->selectRaw('DATE(order_date) as date, SUM(total_price) as total_sales')
            ->groupBy('date')
            ->get();

        // 6-Month Sales Data (Monthly)
        $sixMonthsSales = (clone $query)
            ->where('order_date', '>=', $today->copy()->subMonths(6))
            ->selectRaw('DATE_FORMAT(order_date, "%Y-%m") as month, SUM(total_price) as total_sales')
            ->groupBy('month')
            ->get();

        // 1-Year Sales Data (Monthly)
        $oneYearSales = (clone $query)
            ->where('order_date', '>=', $today->copy()->subYear())
            ->selectRaw('DATE_FORMAT(order_date, "%Y-%m") as month, SUM(total_price) as total_sales')
            ->groupBy('month')
            ->get();

        // Convert data to format suitable for charts
        $oneDaySalesData = [
            'labels' => $oneDaySales->pluck('hour')->map(function ($hour) {
                return Carbon::createFromTime($hour)->format('gA');
            })->toArray(),
            'sales' => $oneDaySales->pluck('total_sales')->toArray(),
        ];

        $fiveDaysSalesData = [
            'labels' => $fiveDaysSales->pluck('date')->map(function ($date) {
                return Carbon::parse($date)->format('M d');
            })->toArray(),
            'sales' => $fiveDaysSales->pluck('total_sales')->toArray(),
        ];

        $oneMonthSalesData = [
            'labels' => $oneMonthSales->pluck('date')->map(function ($date) {
                return Carbon::parse($date)->format('M d');
            })->toArray(),
            'sales' => $oneMonthSales->pluck('total_sales')->toArray(),
        ];

        $sixMonthsSalesData = [
            'labels' => $sixMonthsSales->pluck('month')->map(function ($month) {
                return Carbon::parse($month . '-01')->format('M Y');
            })->toArray(),
            'sales' => $sixMonthsSales->pluck('total_sales')->toArray(),
        ];

        $oneYearSalesData = [
            'labels' => $oneYearSales->pluck('month')->map(function ($month) {
                return Carbon::parse($month . '-01')->format('M Y');
            })->toArray(),
            'sales' => $oneYearSales->pluck('total_sales')->toArray(),
        ];

        $startOfMonth = now()->startOfMonth();
        $startOfSixMonths = now()->subMonths(6)->startOfMonth();
        $startOfYear = now()->startOfYear();

        // Total Sales Amounts Calculation
        $totalAmountToday = (clone $query)
            ->where('order_date', '>=', $today)
            ->sum('total_price');

        $totalAmountFiveDays = (clone $query)
            ->where('order_date', '>=', now()->subDays(5))
            ->sum('total_price');

        $totalAmountOneMonth = (clone $query)
            ->where('order_date', '>=', now()->subDays(30))
            ->sum('total_price');

        $totalAmountSixMonths = (clone $query)
            ->where('order_date', '>=', now()->subMonths(6))
            ->sum('total_price');

        $totalAmountOneYear = (clone $query)
            ->where('order_date', '>=', now()->subYear())
            ->sum('total_price');

        // Prepare the data to pass to the view
        $salesAmounts = [
            'oneD' => $totalAmountToday,
            'fiveDd' => $totalAmountFiveDays,
            'oneM' => $totalAmountOneMonth,
            'sixM' => $totalAmountSixMonths,
            'oneY' => $totalAmountOneYear
        ];

        $province = ['Delhi', 'Karnataka', 'Telangana'];

        // Fetch sales data by state (filtered by admin's city)
        $salesByStateToday = DB::table('orders')
            ->join('billing_addresses', 'orders.id', '=', 'billing_addresses.order_id')
            ->where('orders.financial_status', 'paid')
            ->where('orders.order_date', '>=', $today);

        if (!empty($adminCity)) {
            // Get the province for admin's city
            $adminProvince = DB::table('billing_addresses')
                ->where('province', $adminCity)
                ->value('province');

            if (!empty($adminProvince)) {
                $salesByStateToday->where('billing_addresses.province', $adminProvince);
                // Filter province array to only include admin's province
                $province = array_filter($province, function ($p) use ($adminProvince) {
                    return $p === $adminProvince;
                });
            }
            $salesByStateToday->where('billing_addresses.city', $adminCity);
        }

        $salesByStateToday = $salesByStateToday->select(
            'billing_addresses.province as state',
            DB::raw('SUM(orders.total_price) as total_sales'),
            DB::raw('COUNT(orders.id) as total_orders')
        )
            ->groupBy('billing_addresses.province')
            ->orderBy('total_sales', 'desc')
            ->get();

        $salesData = collect($province)->map(function ($state) use ($salesByStateToday) {
            $stateData = $salesByStateToday->firstWhere('state', $state);
            return (object) [
                'state' => $state,
                'total_sales' => $stateData->total_sales ?? 0,
                'total_orders' => $stateData->total_orders ?? 0,
            ];
        });

        // Total sales for Karnataka (filtered by admin's city)
        $totalSalesKarnataka = DB::table('orders')
            ->join('billing_addresses', 'orders.id', '=', 'billing_addresses.order_id')
            ->where('orders.financial_status', 'paid')
            ->where('billing_addresses.province', 'Karnataka');

        if (!empty($adminCity)) {
            $totalSalesKarnataka->where('billing_addresses.city', $adminCity);
        }

        $totalSalesKarnataka = $totalSalesKarnataka->sum('orders.total_price');

        // Monthly sales for the last year (filtered by admin's city)
        $bangaloreSales = DB::table('orders')
            ->join('billing_addresses', 'orders.id', '=', 'billing_addresses.order_id')
            ->where('orders.financial_status', 'paid')
            ->where('billing_addresses.province', 'Karnataka')
            ->where('orders.order_date', '>=', Carbon::now()->subYear());

        if (!empty($adminCity)) {
            $bangaloreSales->where('billing_addresses.city', $adminCity);
        }

        $bangaloreSales = $bangaloreSales->selectRaw('DATE_FORMAT(order_date, "%Y-%m") as month, SUM(orders.total_price) as total_sales')
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->get();

        $bangaloreSalesData = [
            'labels' => $bangaloreSales->pluck('month')->toArray(),
            'sales' => $bangaloreSales->pluck('total_sales')->toArray(),
        ];

        $data = [
            'salesAmounts'              => $salesAmounts,
            'totalAmountToday'          => $totalAmountToday,
            'salesData'                 => $salesData,
            'bangaloreSalesData'        => $bangaloreSalesData,
            'totalSalesKarnataka'       => $totalSalesKarnataka,
            'totalOrdersToday'          => $totalOrdersToday,
            'totalOrders'               => $totalOrders,
            'recentOrders'              => $recentOrders,
            'totalCustomers'            => $totalUniqueCustomers,
            'oneDaySalesData'           => $oneDaySalesData,
            'fiveDaysSalesData'         => $fiveDaysSalesData,
            'oneMonthSalesData'         => $oneMonthSalesData,
            'sixMonthsSalesData'        => $sixMonthsSalesData,
            'oneYearSalesData'          => $oneYearSalesData,
        ];

        return view('admin.dashboard', $data);
    }




    public function new()
    {

        // Pass the data to the view
        return view('new-admin.dashboard');
    }
}
