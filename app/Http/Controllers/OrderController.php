<?php

namespace App\Http\Controllers;

use App\Services\ShopifyService;

class OrderController extends Controller
{
    protected $shopifyService;

    public function __construct(ShopifyService $shopifyService)
    {
        $this->shopifyService = $shopifyService;
    }

    public function show($orderId)
    {  
        $orderData = $this->shopifyService->getOrder($orderId);
         $order_json_decode = json_decode($orderData);
        $order = $order_json_decode->order ?? null;
        return view('order.show', compact('order'));
    }
}
