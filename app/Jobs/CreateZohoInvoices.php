<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\Order;
use Carbon\Carbon;
use App\Http\Controllers\ZohoController;
use App\Models\Customer;
use App\Models\LineItem;
use App\Models\ShippingAddress;
use App\Models\BillingAddress;


class CreateZohoInvoices implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        // \Log::info('CreateZohoInvoices job started.');

        $startDate = Carbon::create(2025, 1, 1, 0, 0, 0); // January 1, 2025

        // Fetch orders where zoho_status = 0
        $orders = Order::where('zoho_status', 0) // Zoho invoice not created
            ->whereNotNull('order_number') // Ensure order_number is not null
            ->where('created_at', '>=', $startDate)
            ->get();

        // \Log::info('Orders fetched: ' . $orders->count());

        foreach ($orders as $order) {
            $this->createZohoInvoice($order);
        }

        // \Log::info('CreateZohoInvoices job completed.');
    }


    private function createZohoInvoice(Order $order)
    {
        if ($order->zoho_status === 1) {
            \Log::info('Zoho invoice already exists for order ' . $order->order_number);
            return;
        }
        
        \Log::info('=== ZOHO INVOICE CREATION START ===', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'timestamp' => now()->format('Y-m-d H:i:s')
        ]);
        
        $lineItems = $order->lineItems;

        $cartData = [];
        foreach ($lineItems as $lineItem) {
            $cartData[] = (object)[
                'qty' => $lineItem->quantity,
                'item_id' => $lineItem->sku,
                'price' => $lineItem->price,
                'title' => $lineItem->title,
            ];
        }

        $address = BillingAddress::where('order_id', $order->id)->first();
        if (!$address) {
            \Log::error('No billing address found for order ' . $order->order_number);
            return;
        }

        $addressPost = [
            "attention" => $address->name ?? 'Default Name',
            "address" => substr($address->address1, 0, 100),
            "city" => $address->city,
            "state" => $address->province,
            "zip" => $address->zip,
            "country" => $address->country,
        ];

        try {
            $customer = Customer::where('order_id', $order->id)->first();
            if (!$customer) {
                \Log::error('No customer found for order ' . $order->order_number);
                return;
            }

            // Log customer details before sending to Zoho
            \Log::info('Customer details for Zoho invoice', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'customer_id' => $customer->id,
                'customer_first_name' => $customer->first_name,
                'customer_last_name' => $customer->last_name,
                'customer_email' => $customer->email,
                'customer_phone' => $customer->phone,
                'customer_full_data' => $customer->toArray()
            ]);

            Log::info('About to call ZohoController::createInvoice', [
                'order_database_id' => $order->id,
                'order_number' => $order->order_number,
                'customer_id' => $customer->id,
                'customer_name_to_send' => $customer->first_name . ' ' . $customer->last_name
            ]);

            $customerFromZoho = ZohoController::createOrGetCustomer($customer->id, $addressPost);

            \Log::info('Received customer from Zoho', [
                'order_database_id' => $order->id,
                'order_number' => $order->order_number,
                'zoho_customer_name' => $customerFromZoho->contact_name ?? 'N/A',
                'zoho_customer_id' => $customerFromZoho->contact_id ?? 'N/A'
            ]);

            // Create the Zoho Invoice
            ZohoController::createInvoice(
                $customerFromZoho,
                $cartData,
                $order->total_discounts,
                $order->total_shipping_price ?? 0,
                $addressPost,
                $order->order_number
            );

            // Mark as processed
            $order->zoho_status = 1;
            $order->save();

            \Log::info('=== ZOHO INVOICE CREATION SUCCESS ===', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'zoho_customer_name' => $customerFromZoho->contact_name ?? 'N/A'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('=== ZOHO INVOICE CREATION FAILED ===', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
