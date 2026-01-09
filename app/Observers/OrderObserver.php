<?php

namespace App\Observers;

use App\Models\Order;
use Illuminate\Support\Facades\Http;

class OrderObserver
{
    public function updated(Order $order)
    {
        Log::info('OrderObserver: Order updated event triggered', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'dirty_attributes' => $order->getDirty(),
            'original_attributes' => $order->getOriginal()
        ]);
        
        // Check if financial_status was changed
        if ($order->isDirty('financial_status') && $order->financial_status === 'paid') {
            Log::info('OrderObserver: Financial status changed to paid, sending webhook', [
                'order_id' => $order->id,
                'order_number' => $order->order_number
            ]);

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
            try {
                Http::post('https://your-webhook-url.com/webhook-endpoint', $orderData);
                Log::info('OrderObserver: Webhook sent successfully', [
                    'order_id' => $order->id
                ]);
            } catch (\Exception $e) {
                Log::error('OrderObserver: Failed to send webhook', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
}
