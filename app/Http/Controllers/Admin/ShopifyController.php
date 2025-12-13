<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\FetchShopifyOrders;

use Illuminate\Http\Request;

class ShopifyController extends Controller
{
    //
    public function fetchOrders()
    {  
         FetchShopifyOrders::dispatchSync();
         return redirect()->route('admin.orders.list')->with('success', 'Orders updated successfully!');
   }
}
