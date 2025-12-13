<?php

namespace App\Http\Controllers\Admin;

use App\Models\Order;

use App\Enums\VendorStatusType;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ZohoController;
use App\Models\Stack;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Symfony\Component\Intl\Countries;
use Illuminate\Support\Facades\Http;
use App\Jobs\FetchShopifyOrders;
use App\Models\Customer;
use App\Models\LineItem;
use App\Models\ShippingAddress;
use App\Models\BillingAddress;
use App\Services\GupshupService;
use Barryvdh\DomPDF\Facade\Pdf;

class OrderController extends Controller
{
    protected GupshupService $gupshup;

    public function __construct(GupshupService $gupshup)
    {
        $this->gupshup = $gupshup;
    }

    public function list(Request $request)
    {
        $search = $request->input('search');
        $adminCity = Auth('admin')->user()->city;
        $today = now()->startOfDay();

        $query = Order::query();


        // Filter by admin city if it's not empty
        if (!empty($adminCity)) {
            $query->where(function ($query) use ($adminCity) {
                $query->whereHas('shippingAddress', function ($query) use ($adminCity) {
                    $query->where('province', $adminCity);
                })
                    ->orWhereHas('billingAddress', function ($query) use ($adminCity) {
                        $query->where('province', $adminCity); // Fallback to billing address city if shipping address is null
                    });
            });
        }

        // Search functionality
        if (!empty($search)) {
            $query->where(function ($query) use ($search) {
                $query->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($query) use ($search) {
                        $query->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('shippingAddress', function ($query) use ($search) {
                        $query->where('address1', 'like', "%{$search}%")
                            ->orWhere('city', 'like', "%{$search}%")
                            ->orWhere('province', 'like', "%{$search}%");
                    })
                    ->orWhereHas('billingAddress', function ($query) use ($search) {
                        $query->where('address1', 'like', "%{$search}%")
                            ->orWhere('city', 'like', "%{$search}%")
                            ->orWhere('province', 'like', "%{$search}%");
                    })
                    ->orWhere('order_date', 'like', "%{$search}%")
                    ->orWhereHas('noteAttributes', function ($query) use ($search) {
                        $query->where('value', 'like', "%{$search}%");
                    });
            });
        }
        $paymentStatus = $request->input('payment_status');

        if (!empty($paymentStatus)) {
            $query->where('financial_status', $paymentStatus);
        }


        // Order results by order date and filter by financial status
        $orders = $query->orderBy('order_date', 'desc')
            ->paginate(20);

        // Get today's orders
        $todaysOrders = Order::where('order_date', '>=', $today)
            ->whereHas('shippingAddress', function ($query) use ($adminCity) {
                $query->where('province', $adminCity);
            })
            ->get();

        // Calculate the total amount for today's orders
        $totalAmountToday = $todaysOrders->sum('total_price');

        // Get the total number of today's orders
        $totalOrdersToday = $todaysOrders->count();


        return view('admin.orders.list', [
            'orders' => $orders,
            'totalOrdersToday' => $totalOrdersToday,
            'totalAmountToday' => $totalAmountToday
        ]);
    }


    public function show($orderId)
    {
        $order = Order::findOrFail($orderId);

        // Get previous and next order IDs
        $previousOrder = Order::where('id', '<', $orderId)->orderBy('id', 'desc')->first();
        $nextOrder = Order::where('id', '>', $orderId)->orderBy('id', 'asc')->first();

        // Fetch templates from Gupshup
        $raw = $this->gupshup->getTemplates();

        // Gupshup returns: ["status" => "...", "templates" => [ ... ]]
        $tplList = $raw['templates'] ?? [];   // <<< IMPORTANT

        $allowedTemplates = [/* keep empty to allow all approved */];

        $processedTemplates = collect($tplList)
            ->map(function ($tpl) {
                // meta can be a JSON string; decode safely
                $meta = $tpl['meta'] ?? null;
                if (is_string($meta)) {
                    $decoded = json_decode($meta, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $meta = $decoded;
                    } else {
                        $meta = null;
                    }
                }

                // Try to extract a nice sample/preview
                $sample = $meta['example'] ?? null;
                if (!$sample) {
                    // fallbacks: "data" or "containerMeta" (which can also be a JSON string)
                    $sample = $tpl['data'] ?? '';
                    if (!$sample && !empty($tpl['containerMeta'])) {
                        $cm = $tpl['containerMeta'];
                        if (is_string($cm)) {
                            $cm = json_decode($cm, true);
                        }
                        if (is_array($cm) && !empty($cm['data'])) {
                            $sample = $cm['data'];
                        }
                    }
                }

                return [
                    'id'           => $tpl['id'] ?? null,
                    'tempId'           => $tpl['externalId'] ?? null,
                    'templateName' => $tpl['elementName'] ?? ($tpl['name'] ?? 'Unnamed'),
                    'status'       => $tpl['status'] ?? null,
                    'language'     => $tpl['languageCode'] ?? ($tpl['language'] ?? null),
                    'category'     => $tpl['category'] ?? ($tpl['oldCategory'] ?? null),
                    'sampleText'   => trim((string)$sample),
                ];
            })
            ->filter(function ($t) use ($allowedTemplates) {
                if (strtoupper((string)$t['status']) !== 'APPROVED') return false;
                if ($allowedTemplates && !in_array($t['templateName'], $allowedTemplates, true)) return false;
                return !empty($t['id']);
            })
            ->values();
        $processedTemplates = $processedTemplates
            ->reject(function ($t) {
                $name = strtolower($t['templateName'] ?? $t['elementName'] ?? $t['name'] ?? '');
                return preg_match('/^test($|[\s._-])/i', $name)
                    || strpos($name, 'greview_delhi') !== false;
            })
            ->values();

        // dd($raw, $processedTemplates);
        return view('admin.orders.view', [
            'order' => $order,
            'templates' => $processedTemplates,
            'previousOrder' => $previousOrder,
            'nextOrder' => $nextOrder,
        ]);
    }
    public function sendTemplateSms(\Illuminate\Http\Request $request, \App\Services\GupshupService $gupshup)
    {
        $validated = $request->validate([
            'order_id'       => 'required|integer|exists:orders,id',
            'template_id'    => 'nullable|string',
            'template_name'  => 'nullable|string',
            'customer_phone' => 'nullable|string',
        ]);

        if (empty($validated['template_id']) && empty($validated['template_name'])) {
            return back()->withErrors(['template_name' => 'The template field is required.'])->withInput();
        }

        $order = \App\Models\Order::with(['customer', 'shippingAddress', 'billingAddress', 'lineItems'])
            ->findOrFail($validated['order_id']);

        // Resolve recipient (no hard-code)
        // $recipient = preg_replace(
        //     '/\D+/',
        //     '',
        //     $validated['customer_phone']
        //         ?: ($order->shippingAddress->phone ?? $order->billingAddress->phone ?? $order->customer->phone ?? '')
        // );
        $recipient = '917536011971';
        if (!$recipient) return back()->with('error', 'No phone number found.');
        if (\strlen($recipient) === 10) $recipient = '91' . $recipient;

        // Resolve template id from name if needed
        $templateId = $validated['template_id'] ?: $gupshup->resolveTemplateIdByName($validated['template_name']);

        if (!$templateId) {
            return back()->with('error', 'Could not resolve template ID.');
        }

        // Build template vars for your template key
        $vars = [];
        $tplKey = strtolower($validated['template_name'] ?? '');

        if (str_contains($tplKey, 'operational_utility_order_received')) {
            $vars = [
                $order->customer->first_name,
                $order->order_number,
                \Carbon\Carbon::parse($order->order_date)->format('d-M-Y H:i'),
                '₹' . number_format($order->total_price, 2),
                'Farmerr',
            ];
        }
        // add more template cases as needed

        // SEND
        $resp = $gupshup->sendTemplate([
            'send_to'     => '917536011971',
            'template_id' => '1995637037940624',
            'vars'        => ['test', 'test2', 'test3', 'test4', 'test5'],
            'campaign_id' => 'testCampaignid',
        ]);


        // If accepted, resp contains id -> check status
        $gwId = data_get($resp, 'response.id') ?? data_get($resp, 'id');
        if ($gwId) {
            // optional: call status now or store $gwId to check later via cron/webhook
            $status = $gupshup->getMessageStatus($gwId);
            // You can flash or store this:
            session()->flash('wa_status', $status);
        }

        if (!empty($resp['error'])) {
            return back()->with('error', 'Gupshup send failed: ' . $resp['message']);
        }

        return back()->with('message', 'WhatsApp template sent. (Accepted by gateway — delivery status available in logs)');
    }

    /**
     * Resolve templateId by template name using the existing getTemplates() service.
     */
    protected function resolveTemplateIdByName(string $templateName): ?string
    {
        $raw = $this->gupshup->getTemplates();
        $list = $raw['templates'] ?? $raw ?? [];
        foreach ($list as $tpl) {
            $name = $tpl['elementName'] ?? $tpl['name'] ?? $tpl['templateName'] ?? null;
            $id   = $tpl['id'] ?? $tpl['templateId'] ?? null;
            if ($name && $id && strtolower($name) === strtolower($templateName)) {
                return (string)$id;
            }
        }
        return null;
    }
    public function downloadInvoice($orderId)
    {
        $order = Order::findOrFail($orderId);

        // Optional: format or enrich data if needed
        return Pdf::loadView('invoice', compact('order'))
            ->download("packing_slip_{$order->order_number}.pdf");
    }

    public function fetchFulfillmentOrders($orderId)
    {

        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => env('SHOPIFY_ACCESS_TOKEN'),
        ])->get("https://farmerr-in.myshopify.com/admin/api/2024-04/orders/{$orderId}/fulfillment_orders.json");

        $fulfillmentOrders = $response->json()['fulfillment_orders']; // Assuming fulfillment_orders is the key in the response JSON

        // For example, if you want to use the first fulfillment order's ID:
        $fulfillmentOrderId = $fulfillmentOrders[0]['id']; // Adjust as per your logic

        return $fulfillmentOrderId;
    }

    public function fulfillOrder(Request $request, $orderId)
    {
        $baseUrl = env('SHOPIFY_SHOP_URL') . '/admin/api/2024-04/fulfillments.json';
        //FetchShopifyOrders::dispatchSync();
        $notifyCustomer =  true;
        // Fetch fulfillment_order_id (assuming you have a method like fetchFulfillmentOrders)
        $fulfillmentOrderId = $this->fetchFulfillmentOrders($orderId);
        // Prepare data for the Shopify API request
        $data = [
            'fulfillment' => [
                'line_items_by_fulfillment_order' => [
                    [
                        'fulfillment_order_id' => $fulfillmentOrderId,
                    ],
                ],
                'tracking_info' => [
                    'number' => $request->input('number'),
                    'url' => $request->input('url'),
                ],
                'notify_customer' =>  true,
            ],
        ];

        // Make the POST request to Shopify API
        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => env('SHOPIFY_ACCESS_TOKEN'),
            'Content-Type' => 'application/json',
        ])->post($baseUrl, $data);

        if ($response->successful()) {
            // Flash success message to session
            $order = Order::where('shopify_order_id', $orderId)->first();
            if ($order) {
                $order->fulfillment_status = 'fulfilled'; // or whatever status you want to set
                $order->save();
            }
            session()->flash('success', 'Fulfillment created successfully!');
        } else {
            // Handle error scenario if needed
            session()->flash('error', 'Failed to create fulfillment.');
        }
        //  dd($response );
        // Redirect back to the form page
        return redirect()->back();
    }

    public function sendOrderToZoho(Request $request, $order_number)
    {


        $order              = Order::with('lineItems')->where('order_number', $order_number)->first();
        if (!$order) {
            // Handle the case where the order is not found
            return redirect()->back()->with('message', 'Order not found.');
        }

        $order_id = $order->id;
        $customer        = Customer::where('order_id', $order_id)->first();
        $customer_id    =   $customer->id;
        $promo_code_amount  = $order->promo_code_amount ?? null;
        $shipping_amt       = $order->total_shipping_price;
        //  prx($shipping_amt);
        try {
            $cartData = [];
            $lineItems = $order->lineItems;

            $total_price = 0;
            foreach ($lineItems as $lineItem) {
                $product = LineItem::where('line_items_id', $lineItem->line_items_id)->first();


                if (!$product || !$product->sku) {
                    $request->session()->flash('message', 'Error: Zoho SKU not added for product id.');
                    return redirect('admin/orders/' . $order_id);
                }

                // Add to total price
                $total_price += $lineItem->price * $lineItem->quantity;

                // Prepare cart data for future use
                $cartData[] = (object)[
                    'qty' => $lineItem->quantity,
                    'item_id' => $product->sku,
                    'price' => $lineItem->price,
                    'title' => $product->title,
                ];
            }
            // $address = ShippingAddress::where('order_id', $order_id)->first();
            $address = BillingAddress::where('order_id', $order_id)->first();
            // dd($address);
            $addressPost = [
                "attention" => $address->name,
                "address" => substr($address->address1, 0, 100),
                "city" => $address->city,
                "state" => $address->province,
                "zip" => $address->zip,
                "country" => $address->country,
            ];

            $customerFromZoho = ZohoController::createOrGetCustomer($customer_id, $addressPost);
            // dd($customerFromZoho);
            // prx($customerFromZoho);
            $res = ZohoController::createInvoice($customerFromZoho, $cartData, $promo_code_amount, $shipping_amt, $addressPost, $order_number);
            // dd($res);
        } catch (\Exception $error) {
            dd($error);
            logger()->error($error->getMessage());
        }


        $request->session()->flash('message', 'Invoice Generated in Zoho');
        return redirect('admin/orders/' . $order_id);
    }
    public function createCreditNoteForRefund($orderId)
    {
        // dd($orderId);
        $order = Order::with(['lineItems', 'customer'])->findOrFail($orderId);
        // dd($order);

        // Check refund status
        if ($order->financial_status !== 'refunded' && $order->financial_status !== 'partially_refunded') {
            return redirect()->back()->with('message', 'This order has no refunds.');
        }

        try {
            // Fetch the associated invoice from Zoho
            $invoice = ZohoController::getInvoiceByOrderNumber($order->order_number);
            if (!$invoice) {
                return redirect()->back()->with('message', 'Invoice not found for this order in Zoho.');
            }

            // Step 3: Check if a credit note already exists for this invoice
            // $existingCreditNotes = ZohoController::getCreditNotesForInvoice($invoice['invoice_id']);
            // // dd($existingCreditNotes);
            // if (!empty($existingCreditNotes)) {
            //     return redirect()->back()->with('message', 'Credit note already created for this invoice.');
            // }

            // Prepare refund data
            $lineItems = [];
            foreach ($order->lineItems as $lineItem) {
                $refundedQty = $lineItem->refunded_quantity ?? $lineItem->quantity; // Assume full quantity if refunded_quantity is missing

                if ($refundedQty > 0 && $lineItem->sku) {
                    try {
                        // Fetch item details using SKU
                        $itemDetails = ZohoController::findItemBySKU($lineItem->sku);

                        // dd($itemDetails);

                        if ($itemDetails) {
                            $lineItems[] = [
                                'item_id' => $itemDetails['item_id'],
                                'quantity' => $refundedQty,
                                'rate' => $lineItem->price,
                                'discount' => $lineItem->discount ?? 0,
                                'tax_id' => $itemDetails['tax_by_specification']['tax_id'] ?? null,
                                'tax_exemption_code' => 'tax_exempt',
                            ];
                        } else {
                            \Log::warning("No item found for SKU: {$lineItem->sku}");
                        }
                    } catch (\Exception $e) {
                        \Log::error("Error fetching item for SKU {$lineItem->sku}: " . $e->getMessage());
                    }
                } else {
                    \Log::warning("Line item {$lineItem->id} has invalid refund data. Refunded Quantity: {$refundedQty}");
                }
            }
            // Add shipping refund if applicable
            $shippingCharge = $order->total_shipping_price ?? 0;
            if ($shippingCharge > 0) {
                try {
                    $shippingItem = ZohoController::findItemBySKU($lineItem->sku); // This SKU must exist in Zoho
                    $lineItems[] = [
                        'item_id' => $shippingItem['item_id'],
                        'quantity' => 1,
                        'rate' => $shippingCharge,
                        'discount' => 0,
                        'tax_id' => null,
                        'tax_exemption_code' => 'tax_exempt',
                    ];
                    \Log::info("Shipping refund added for order {$order->order_number}");
                } catch (\Exception $e) {
                    \Log::error("Error adding shipping refund: " . $e->getMessage());
                }
            }

            // Calculate total refund
            $totalRefund = array_reduce($lineItems, function ($carry, $item) {
                return $carry + ($item['quantity'] * $item['rate']);
            }, 0);

            // Validate refund data
            if (empty($lineItems) || $totalRefund <= 0) {
                return redirect()->back()->with('message', 'Refund data is incomplete.');
            }

            // Refund data structure
            $refundData = [
                'customer_id' => $invoice['customer_id'],
                'line_items' => $lineItems,
                'total' => $totalRefund,
                'order_id' => $order->order_number,
            ];

            // Call Zoho to create the credit note
            $creditNote = ZohoController::createCreditNote(
                $invoice['invoice_id'],
                $refundData,
                'Refund processed for order cancellation'
            );

            // ✅ Update local order record
            $order->credit_note_status = 1;
            $order->save();


            return redirect()->back()->with('message', 'Credit note created successfully.');
        } catch (\Exception $e) {
            \Log::error('Error creating credit note for order ' . $orderId . ': ' . $e->getMessage());
            return redirect()->back()->with('message', 'Failed to create credit note.');
        }
    }
    public function exportLatestOrders()
    {
        // Fetch the latest 500 orders with related data
        $orders = Order::with(['customer', 'lineItems', 'shippingAddress', 'billingAddress'])
            ->latest('order_date')
            ->take(500)
            ->get();

        // Prepare data for JSON export
        $jsonData = $orders->map(function ($order) {
            return [
                'order_number' => $order->order_number,
                'order_date' => $order->order_date,
                'tags' => $order->tags,
                'note' => $order->note,
                'note_attributes' => $order->note_attributes,
                'email' => $order->email,
                'phone' => $order->phone,
                'total_price' => $order->total_price,
                'subtotal_price' => $order->subtotal_price,
                'total_tax' => $order->total_tax,
                'financial_status' => $order->financial_status,
                'fulfillment_status' => $order->fulfillment_status,
                'currency' => $order->currency,
                'buyer_accepts_marketing' => $order->buyer_accepts_marketing,
                'confirmed' => $order->confirmed,
                'total_discounts' => $order->total_discounts,
                'total_line_items_price' => $order->total_line_items_price,
                'contact_email' => $order->contact_email,
                'zoho_invoice' => $order->zoho_invoice,
                'order_status_url' => $order->order_status_url,
                'total_shipping_price' => $order->total_shipping_price,
                'zoho_status' => $order->zoho_status,
                'customer' => $order->customer ? [
                    'email' => $order->customer->email,
                    'first_name' => $order->customer->first_name,
                    'last_name' => $order->customer->last_name,
                    'state' => $order->customer->state,
                    'verified_email' => $order->customer->verified_email,
                    'phone' => $order->customer->phone,
                    'tags' => $order->customer->tags,
                    'currency' => $order->customer->currency,
                ] : null,
                'shipping_address' => $order->shippingAddress ? [
                    'first_name' => $order->shippingAddress->first_name,
                    'last_name' => $order->shippingAddress->last_name,
                    'address1' => $order->shippingAddress->address1,
                    'address2' => $order->shippingAddress->address2,
                    'phone' => $order->shippingAddress->phone,
                    'city' => $order->shippingAddress->city,
                    'zip' => $order->shippingAddress->zip,
                    'province' => $order->shippingAddress->province,
                    'country' => $order->shippingAddress->country,
                    'company' => $order->shippingAddress->company,
                    'latitude' => $order->shippingAddress->latitude,
                    'longitude' => $order->shippingAddress->longitude,
                    'name' => $order->shippingAddress->name,
                    'country_code' => $order->shippingAddress->country_code,
                    'province_code' => $order->shippingAddress->province_code,
                ] : null,
                'billing_address' => $order->billingAddress ? [
                    'first_name' => $order->billingAddress->first_name,
                    'last_name' => $order->billingAddress->last_name,
                    'address1' => $order->billingAddress->address1,
                    'address2' => $order->billingAddress->address2,
                    'phone' => $order->billingAddress->phone,
                    'city' => $order->billingAddress->city,
                    'zip' => $order->billingAddress->zip,
                    'province' => $order->billingAddress->province,
                    'country' => $order->billingAddress->country,
                    'company' => $order->billingAddress->company,
                    'latitude' => $order->billingAddress->latitude,
                    'longitude' => $order->billingAddress->longitude,
                    'name' => $order->billingAddress->name,
                    'country_code' => $order->billingAddress->country_code,
                    'province_code' => $order->billingAddress->province_code,
                ] : null,
                'line_items' => $order->lineItems->map(function ($item) {
                    return [
                        'product_id' => $item->product_id,
                        'line_items_id' => $item->line_items_id,
                        'variant_id' => $item->variant_id,
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                        'total_discount' => $item->total_discount,
                        'name' => $item->name,
                        'sku' => $item->sku,
                        'fulfillment_status' => $item->fulfillment_status,
                        'requires_shipping' => $item->requires_shipping,
                        'taxable' => $item->taxable,
                        'title' => $item->title,
                    ];
                }),
            ];
        });

        // Save JSON file
        $jsonFileName = 'latest_500_orders_' . now()->format('Y_m_d_His') . '.json';
        $jsonFilePath = storage_path('app/public/' . $jsonFileName);
        file_put_contents($jsonFilePath, $jsonData->toJson(JSON_PRETTY_PRINT));

        // Return response with download link
        return response()->json([
            'message' => 'JSON file exported successfully.',
            'json_file' => asset('storage/' . $jsonFileName),
        ]);
    }
}
