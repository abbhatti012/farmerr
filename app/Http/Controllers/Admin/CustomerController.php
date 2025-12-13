<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function list(Request $request)
    {
        $search = $request->input('search');

        // Query for customers with aggregated orders
        $customersQuery = Customer::query()
        ->select('email', 'first_name', 'last_name')
        ->withCount(['orders as total_orders' => function ($query) {
            $query->where('financial_status', 'paid');
        }])
        ->withSum(['orders as total_order_amount' => function ($query) {
            $query->where('financial_status', 'paid');
        }], 'total_price')
        ->groupBy('email', 'first_name', 'last_name');
    
    // Apply search filter
    if (!empty($search)) {
        $customersQuery->where(function ($query) use ($search) {
            $query->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
        });
    }
    
    // Paginate the results
    $customers = $customersQuery->orderBy('total_orders', 'desc')
        ->paginate(20);
    
    return view('admin.customers.list', [
        'customers' => $customers
    ]);
    
    }
}
