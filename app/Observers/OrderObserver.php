<?php

namespace App\Observers;

use App\Models\Order;
use Illuminate\Support\Facades\Http;

class OrderObserver
{
    public function updated(Order $order)
    {
        // Check if financial_status was changed
        if ($order->isDirty('financial_status') && $order->financial_status === 'paid') {

            // Prepare complete order data with relations
            $orderData = $order->load([
                'customer',
                'noteAttributes',
                'shippingAddress',
                'billingAddress',
                'lineItems',
                'shippingLines',
                'discountCodes'
            ])->toArray();

            // Send webhook
            Http::post('https://your-webhook-url.com/webhook-endpoint', $orderData);
        }
    }
}
