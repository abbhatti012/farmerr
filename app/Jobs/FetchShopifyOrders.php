<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use App\Models\Order;
use App\Models\Customer;
use App\Models\LineItem;
use App\Models\ShippingLine;
use App\Models\DiscountCode;
use App\Models\BillingAddress;
use App\Models\ShippingAddress;
use App\Models\NoteAttributes;
use Carbon\Carbon;
use App\Events\OrderPlaced;

class FetchShopifyOrders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        $baseUrl = env('SHOPIFY_SHOP_URL') . '/admin/api/2024-04/orders.json';
        $limit = 250;
        $createdAtMin = "";
        //  $createdAtMin = '2024-04-01T00:00:00Z'; // ISO 8601 format for April 1, 2024
        $createdAtMin = date('Y-m-d\TH:i:s\Z', strtotime('-15 days'));
        $createdAtMax = date('Y-m-d\TH:i:s\Z'); // Current date and time in ISO 8601 format
        // $createdAtMax = '2024-03-31T23:59:59Z'; // ISO 8601 format for March 31, 2024

        $url = $baseUrl . "?limit={$limit}&created_at_min={$createdAtMin}&created_at_max={$createdAtMax}&status=any";

        //$url = $baseUrl . "?limit={$limit}";

        do {
            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => env('SHOPIFY_ACCESS_TOKEN'),
            ])->get($url);

            // dd($response);

            if ($response->successful()) {
                $orders = $response->json()['orders'];
                // dd($orders[0]);
                foreach ($orders as $shopifyOrder) {
                    $this->importOrder($shopifyOrder);
                }

                // Check if there is a next page
                $linkHeader = $response->header('Link');
                $url = $this->getNextPageUrl($linkHeader);
            } else {
                // Log the error or handle it as needed
                \Log::error('Failed to fetch Shopify orders: ' . $response->body());
                $url = null; // Stop fetching if there's an error
            }
        } while ($url);
    }
    private function getNextPageUrl($linkHeader)
    {
        if ($linkHeader) {
            // Match the next page URL in the Link header
            if (preg_match('/<([^>]+)>; rel="next"/', $linkHeader, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }
    private function importOrder($orderData)
    {
        $timezone = 'Asia/Kolkata';

        // Parse the dates using Carbon and set the timezone
        $createdAt = Carbon::parse($orderData['created_at'])->format('Y-m-d H:i:s');
        $updatedAt = Carbon::parse($orderData['updated_at'])->format('Y-m-d H:i:s');

        //  dd($orderData);
        // Format the date for MySQL
        // dd($createdAt);
        $order = Order::updateOrCreate(
            ['shopify_order_id' => $orderData['id']],
            [
                'created_at'                =>  now(),
                'updated_at'                =>  now(),
                'order_date'                => $createdAt,
                'order_number'              => $orderData['order_number'],
                'email'                     => $orderData['email'],
                'phone'                     => $orderData['phone'],
                'total_price'               => $orderData['total_price'],
                'subtotal_price'            => $orderData['subtotal_price'],
                'total_tax'                 => $orderData['total_tax'],
                'financial_status'          => $orderData['financial_status'],
                'fulfillment_status'        => $orderData['fulfillment_status'],
                'currency'                  => $orderData['currency'],
                'buyer_accepts_marketing'   => $orderData['buyer_accepts_marketing'],
                'confirmed'                 => $orderData['confirmed'],
                'total_discounts'           => $orderData['total_discounts'],
                'total_line_items_price'    => $orderData['total_line_items_price'],
                'contact_email'             => $orderData['contact_email'],
                'order_status_url'          => $orderData['order_status_url'],
                'note'                      => $orderData['note'],
                'note_attributes'           => json_encode($orderData['note_attributes']),
                'tags'                      => json_encode($orderData['tags']),
                'total_shipping_price'      => $orderData['total_shipping_price_set']['shop_money']['amount'] ?? 0,
            ]
        );
        /*
     * 👇 THIS is the part you added — use $orderData, not $shopifyOrder
     */
        if (!empty($orderData['line_items'])) {
            foreach ($orderData['line_items'] as $lineItem) {
                if (!empty($lineItem['properties'])) {
                    foreach ($lineItem['properties'] as $prop) {
                        $name  = strtolower($prop['name'] ?? '');
                        $value = $prop['value'] ?? null;

                        if ($name === 'occasion') {
                            $order->occasion = $value;
                        } elseif ($name === 'delivery date') {
                            $order->delivery_date = $value;
                        } elseif ($name === 'gift message') {
                            $order->gift_message = $value;
                        }
                    }
                }
            }
            // save once after looping all line items
            $order->save();
        }

        if (!empty($orderData['customer'])) {
            $this->importCustomer($orderData['customer'], $order->id);
        }
        if (!empty($orderData['shipping_address'])) {
            $this->importShipping($orderData['shipping_address'], $order->id);
        }
        if (!empty($orderData['billing_address'])) {
            $this->importBilling($orderData['billing_address'], $order->id);
        }
        if (!empty($orderData['note_attributes'][0])) {
            $this->importNoteAttributes($orderData['note_attributes'][0], $order->id);
        }

        foreach ($orderData['line_items'] as $item) {
            $this->importLineItem($item, $order->id);
        }

        foreach ($orderData['shipping_lines'] as $shippingLine) {
            $this->importShippingLine($shippingLine, $order->id);
        }


        foreach ($orderData['discount_codes'] as $discountCode) {
            $this->importDiscountCode($discountCode, $order->id);
        }
        // Dispatch event only if the order was recently created
        if ($order->wasRecentlyCreated) {
            event(new OrderPlaced($order));
        }
    }

    private function importCustomer($customerData, $orderId)
    {
        $timezone = 'Asia/Kolkata';

        // Parse the dates using Carbon and set the timezone
        $createdAt = Carbon::parse($customerData['created_at'])->format('Y-m-d H:i:s');
        $updatedAt = Carbon::parse($customerData['updated_at'])->format('Y-m-d H:i:s');

        Customer::updateOrCreate([
            'order_id' => $orderId,
        ], [
            'email'             => $customerData['email'],
            'created_at'        => $createdAt,
            'updated_at'        => $updatedAt,
            'first_name'        => $customerData['first_name'],
            'last_name'         => $customerData['last_name'],
            'state'             => $customerData['state'],
            'verified_email'    => $customerData['verified_email'],
            'phone'             => $customerData['phone'],
            'tags'              => $customerData['tags'],
            'currency'          => $customerData['currency'],
        ]);
    }
    private function importNoteAttributes($customerData, $orderId)
    {
        NoteAttributes::updateOrCreate([
            'order_id' => $orderId,
        ], [
            'name'              => $customerData['name'],
            'created_at'        => now(),
            'updated_at'        =>  now(),
            'value'             => $customerData['value'],
        ]);
    }
    private function importShipping($customerData, $orderId)
    {
        ShippingAddress::updateOrCreate([
            'order_id' => $orderId,
        ], [
            'first_name'        => $customerData['first_name'],
            'created_at'        => now(),
            'updated_at'        =>  now(),
            'address1'          => $customerData['address1'],
            'phone'             => $customerData['phone'],
            'city'              => $customerData['city'],
            'zip'               => $customerData['zip'],
            'province'          => $customerData['province'],
            'country'           => $customerData['country'],
            'last_name'         => $customerData['last_name'],
            'address2'          => $customerData['address2'],
            'company'           => $customerData['company'],
            'latitude'          => $customerData['latitude'],
            'longitude'         => $customerData['longitude'],
            'name'              => $customerData['name'],
            'country_code'      => $customerData['country_code'],
            'province_code'     => $customerData['province_code'],
        ]);
    }
    private function importBilling($customerData, $orderId)
    {
        BillingAddress::updateOrCreate([
            'order_id' => $orderId,
        ], [
            'first_name' => $customerData['first_name'],
            'created_at' =>  now(),
            'updated_at' =>  now(),
            'address1' => $customerData['address1'],
            'phone' => $customerData['phone'],
            'city' => $customerData['city'],
            'zip' => $customerData['zip'],
            'province' => $customerData['province'],
            'country' => $customerData['country'],
            'last_name' => $customerData['last_name'],
            'address2' => $customerData['address2'],
            'company' => $customerData['company'],
            'latitude' => $customerData['latitude'],
            'longitude' => $customerData['longitude'],
            'name' => $customerData['name'],
            'country_code' => $customerData['country_code'],
            'province_code' => $customerData['province_code'],
        ]);
    }

    private function importLineItem($itemData, $orderId)
    {
        LineItem::updateOrCreate(
            [
                'line_items_id' => $itemData['id'],
            ],
            [
                'order_id' => $orderId,
                'product_id' => $itemData['product_id'],
                'variant_id' => $itemData['variant_id'],
                'quantity' => $itemData['quantity'],
                'price' => $itemData['price'],
                'total_discount' => $itemData['total_discount'],
                'name' => $itemData['name'],
                'sku' => $itemData['sku'],
                'fulfillment_status' => $itemData['fulfillment_status'],
                'requires_shipping' => $itemData['requires_shipping'],
                'taxable' => $itemData['taxable'],
                'title' => $itemData['title'],
            ]
        );
    }

    private function importShippingLine($shippingLineData, $orderId)
    {
        ShippingLine::updateOrCreate([
            'order_id' => $orderId,
        ], [
            'id' => $shippingLineData['id'],
            'title' => $shippingLineData['title'],
            'price' => $shippingLineData['price'],
            'discounted_price' => $shippingLineData['discounted_price'],
        ]);
    }

    private function importDiscountCode($discountCodeData, $orderId)
    {
        DiscountCode::updateOrCreate([
            'order_id' => $orderId,
        ], [
            'code' => $discountCodeData['code'],
            'amount' => $discountCodeData['amount'],
            'type' => $discountCodeData['type'],
        ]);
    }
}
