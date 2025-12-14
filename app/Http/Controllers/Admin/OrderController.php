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
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

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

            // If admin city is Delhi, include 3 provinces
            $allowedProvinces = ($adminCity === 'Delhi')
                ? ['Delhi', 'Haryana', 'Uttar Pradesh']
                : [$adminCity];

            $query->where(function ($query) use ($allowedProvinces) {
                $query->whereHas('shippingAddress', function ($query) use ($allowedProvinces) {
                    $query->whereIn('province', $allowedProvinces);
                })
                    ->orWhereHas('billingAddress', function ($query) use ($allowedProvinces) {
                        $query->whereIn('province', $allowedProvinces);
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

    public function listTest(Request $request)
    {
        $search = $request->input('search');
        $paymentStatus = $request->input('payment_status');
        $adminCity = Auth('admin')->user()->city ?? null;
        $today = now()->startOfDay();

        // Eager load relationships
        $query = Order::with(['customer', 'shippingAddress', 'billingAddress', 'noteAttributes']);

        // Allowed provinces filter (same as before)
        $allowedProvinces = [];
        if (!empty($adminCity)) {
            $allowedProvinces = ($adminCity === 'Delhi')
                ? ['Delhi', 'Haryana', 'Uttar Pradesh']
                : [$adminCity];

            $query->where(function ($q) use ($allowedProvinces) {
                $q->whereHas('shippingAddress', function ($q2) use ($allowedProvinces) {
                    $q2->whereIn('province', $allowedProvinces);
                })->orWhereHas('billingAddress', function ($q2) use ($allowedProvinces) {
                    $q2->whereIn('province', $allowedProvinces);
                });
            });
        }

        // Search
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($q2) use ($search) {
                        $q2->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('shippingAddress', function ($q2) use ($search) {
                        $q2->where('address1', 'like', "%{$search}%")
                            ->orWhere('city', 'like', "%{$search}%")
                            ->orWhere('province', 'like', "%{$search}%");
                    })
                    ->orWhereHas('billingAddress', function ($q2) use ($search) {
                        $q2->where('address1', 'like', "%{$search}%")
                            ->orWhere('city', 'like', "%{$search}%")
                            ->orWhere('province', 'like', "%{$search}%");
                    })
                    ->orWhere('order_date', 'like', "%{$search}%")
                    ->orWhereHas('noteAttributes', function ($q2) use ($search) {
                        $q2->where('value', 'like', "%{$search}%");
                    });
            });
        }

        // Payment status
        if (!empty($paymentStatus)) {
            $query->where('financial_status', $paymentStatus);
        }

        // Normalize separators in SQL and attempt multiple formats for STR_TO_DATE.
        $normalized = "TRIM(REPLACE(REPLACE(REPLACE(delivery_date, '-', '/'), '.', '/'), '  ', ' '))";

        $orderRaw = "
        CASE WHEN delivery_date IS NULL OR {$normalized} = '' THEN 1 ELSE 0 END ASC,
        COALESCE(
            STR_TO_DATE({$normalized}, '%d/%m/%Y %H:%i'),
            STR_TO_DATE({$normalized}, '%d/%m/%Y'),
            STR_TO_DATE({$normalized}, '%Y/%m/%d %H:%i:%s'),
            STR_TO_DATE({$normalized}, '%Y/%m/%d'),
            CAST(delivery_date AS DATETIME)
        ) DESC,
        order_date DESC
        ";

        $query->orderByRaw($orderRaw);

        // Paginate and preserve query params
        $orders = $query->paginate(20)->appends($request->query());

        // Process orders to add date group headers
        $processedOrders = $this->addDateGroupHeaders($orders->items());

        // Replace the items in the paginator
        $orders->setCollection(collect($processedOrders));

        // Today's orders (same allowedProvinces logic)
        $todaysOrdersQuery = Order::where('order_date', '>=', $today);
        if (!empty($allowedProvinces)) {
            $todaysOrdersQuery->where(function ($q) use ($allowedProvinces) {
                $q->whereHas('shippingAddress', function ($q2) use ($allowedProvinces) {
                    $q2->whereIn('province', $allowedProvinces);
                })->orWhereHas('billingAddress', function ($q2) use ($allowedProvinces) {
                    $q2->whereIn('province', $allowedProvinces);
                });
            });
        }
        $todaysOrders = $todaysOrdersQuery->get();
        $totalAmountToday = $todaysOrders->sum('total_price');
        $totalOrdersToday = $todaysOrders->count();

        return view('admin.orders.list-test', [
            'orders' => $orders,
            'totalOrdersToday' => $totalOrdersToday,
            'totalAmountToday' => $totalAmountToday
        ]);
    }

    /**
     * Add date group headers to orders array
     */
    protected function addDateGroupHeaders($orders)
    {
        $result = [];
        $lastDate = null;

        foreach ($orders as $order) {
            // Parse the delivery date
            $deliveryDate = $this->parseDeliveryDate($order->delivery_date ?? '');

            // Format for grouping (use d/m/Y format)
            $currentDate = $deliveryDate ? $deliveryDate->format('d/m/Y') : null;

            // If date changed, add a group header
            if ($currentDate !== $lastDate) {
                $result[] = [
                    '__group' => $currentDate,
                ];
                $lastDate = $currentDate;
            }

            // Add the order
            $result[] = $order;
        }

        return $result;
    }

    /**
     * Parse delivery date with multiple format support
     */
    protected function parseDeliveryDate($dateString)
    {
        if (empty(trim($dateString))) {
            return null;
        }

        $formats = [
            'd/m/Y H:i',
            'd/m/Y',
            'd-m-Y H:i',
            'd-m-Y',
            'd.m.Y H:i',
            'd.m.Y',
            'Y-m-d H:i:s',
            'Y-m-d',
            'Y/m/d H:i:s',
            'Y/m/d',
        ];

        foreach ($formats as $format) {
            try {
                $date = Carbon::createFromFormat($format, trim($dateString));
                if ($date) {
                    return $date;
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        // Last attempt with parse
        try {
            return Carbon::parse($dateString);
        } catch (\Exception $e) {
            return null;
        }
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
            ->filter(function ($t) {
                return strtoupper((string)($t['category'] ?? '')) === 'UTILITY';
            })
            ->reject(function ($t) {
                $name = strtolower($t['templateName'] ?? $t['elementName'] ?? $t['name'] ?? '');
                return preg_match('/^test($|[\s._-])/i', $name)
                    || strpos($name, 'greview_delhi') !== false;
            })
            ->values();
        // dd($order->billingAddress);

        return view('admin.orders.view', [
            'order' => $order,
            'templates' => $processedTemplates,
            'previousOrder' => $previousOrder,
            'nextOrder' => $nextOrder,
        ]);
    }

    /**
     * Show create order form
     */
    public function create()
    {
        // Load product variants for the product dropdown in the order form
        $variants = \App\Models\ProductVariant::with('product')->whereNotNull('sku')->get();
        return view('admin.orders.create', [
            'variants' => $variants,
        ]);
    }

    /**
     * Store a manually created order
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_first_name' => 'required|string|max:255',
            'customer_last_name' => 'required|string|max:255',
            'billing_name' => 'required|string|max:255',
            'phone' => 'required|string|max:32',
            'email' => 'required|email|max:255',

            'billing_address1' => 'required|string',
            'billing_city' => 'required|string',
            'billing_province' => 'required|string',
            'billing_country' => 'required|string',
            'billing_country_code' => 'nullable|string',

            'shipping_address1' => 'required|string',
            'shipping_city' => 'required|string',
            'shipping_province' => 'required|string',
            'shipping_country' => 'required|string',
            'shipping_country_code' => 'nullable|string',

            'payment_type' => 'required|string',
            'order_type' => 'required|string',
            'delivery_time_slot' => 'required|string',
            'delivery_date' => 'required|date',
            'order_date' => 'required|date',
            'order_notes' => 'required|string',
            'order_channel' => 'required|string',
            // pricing & status
            'subtotal_price' => 'nullable|numeric',
            'total_tax' => 'nullable|numeric',
            'total_shipping_price' => 'nullable|numeric',
            'total_discounts' => 'nullable|numeric',
            'total_line_items_price' => 'nullable|numeric',
            'total_price' => 'nullable|numeric',
            'currency' => 'nullable|string',
            'fulfillment_status' => 'nullable|string',
            'buyer_accepts_marketing' => 'nullable',
            'confirmed' => 'nullable',
            'contact_email' => 'nullable|email',
            'tags' => 'nullable|string',
            // line items
            'items' => 'nullable|array',
            'items.*.product_id' => 'required_with:items|integer',
            'items.*.variant_id' => 'required_with:items|integer',
            'items.*.sku' => 'required_with:items|string',
            'items.*.price' => 'required_with:items|numeric',
            'items.*.quantity' => 'required_with:items|integer|min:1',
            'items.*.title' => 'nullable|string',
        ]);

        // Create order
        $order = Order::create([
            'order_date' => $validated['order_date'],
            // upto six digits
            'order_number' => random_int(100000, 999999),
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'financial_status' => 'pending',
            'delivery_date' => $validated['delivery_date'],
            'note' => $validated['order_notes'],
            'occasion' => $validated['order_type'] ?? null,
            // pricing & status
            'subtotal_price' => $validated['subtotal_price'] ?? 0,
            'total_tax' => $validated['total_tax'] ?? 0,
            'total_shipping_price' => $validated['total_shipping_price'] ?? 0,
            'total_discounts' => $validated['total_discounts'] ?? 0,
            'total_line_items_price' => $validated['total_line_items_price'] ?? 0,
            'total_price' => $validated['total_price'] ?? ($validated['subtotal_price'] ?? 0),
            'currency' => $validated['currency'] ?? 'INR',
            'fulfillment_status' => $validated['fulfillment_status'] ?? 'unfulfilled',
            'buyer_accepts_marketing' => isset($validated['buyer_accepts_marketing']) ? 1 : 0,
            'confirmed' => isset($validated['confirmed']) ? 1 : 0,
            'contact_email' => $validated['contact_email'] ?? null,
            'tags' => $validated['tags'] ?? null,
        ]);

        // Create customer
        $customer = Customer::create([
            'order_id' => $order->id,
            'first_name' => $validated['customer_first_name'],
            'last_name' => $validated['customer_last_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
        ]);

        // Create billing address
        BillingAddress::create([
            'order_id' => $order->id,
            'first_name' => $validated['customer_first_name'],
            'last_name' => $validated['customer_last_name'],
            'address1' => $validated['billing_address1'],
            'city' => $validated['billing_city'],
            'province' => $validated['billing_province'],
            'country' => $validated['billing_country'],
            'country_code' => $validated['billing_country_code'] ?? 'IN',
            'phone' => $validated['phone'],
            'name' => $validated['customer_first_name'].' '.$validated['customer_last_name'],
        ]);

        // Create shipping address
        ShippingAddress::create([
            'order_id' => $order->id,
            'first_name' => $validated['customer_first_name'],
            'last_name' => $validated['customer_last_name'],
            'address1' => $validated['shipping_address1'],
            'city' => $validated['shipping_city'],
            'province' => $validated['shipping_province'],
            'country' => $validated['shipping_country'],
            'country_code' => $validated['shipping_country_code'] ?? 'IN',
            'phone' => $validated['phone'],
            'name' => $validated['customer_first_name'].' '.$validated['customer_last_name'],
        ]);
    
        // Create line items if provided
        if (!empty($validated['items']) && is_array($validated['items'])) {
            // Log the incoming items payload for debugging
            try {
                Log::info('ManualOrderItemsPayload', ['order_id' => $order->id, 'items' => $validated['items']]);
            } catch (\Exception $e) {
                // Safe guard: logging should not break flow
                Log::error('Failed to log ManualOrderItemsPayload: ' . $e->getMessage());
            }

            foreach ($validated['items'] as $idx => $it) {
                try {
                    // Determine a safe line_items_id. Existing values may be numeric or custom strings.
                    $lastLineItem = LineItem::orderBy('id', 'desc')->first();
                    if ($lastLineItem && is_numeric($lastLineItem->line_items_id)) {
                        $lineItemsId = $lastLineItem->line_items_id + 1;
                    } else {
                        // fallback to a manual identifier to avoid NULL constraints
                        $lineItemsId = 'manual-' . $order->id . '-' . ($idx + 1);
                    }

                    Log::info('Creating manual line item', [
                        'order_id' => $order->id,
                        'item_index' => $idx,
                        'item_payload' => $it,
                        'computed_line_items_id' => $lineItemsId,
                    ]);

                    $created = LineItem::create([
                        'order_id' => $order->id,
                        'product_id' => $it['product_id'] ?? null,
                        'line_items_id' => $lineItemsId,
                        'variant_id' => $it['variant_id'] ?? null,
                        'quantity' => $it['quantity'] ?? 1,
                        'price' => $it['price'] ?? 0,
                        'total_discount' => 0,
                        'name' => $it['title'] ?? null,
                        'sku' => $it['sku'] ?? null,
                        'fulfillment_status' => 'unfulfilled',
                        'requires_shipping' => 1,
                        'taxable' => 1,
                        'title' => $it['title'] ?? null,
                    ]);

                    Log::info('Manual line item created', ['id' => $created->id ?? null, 'line_items_id' => $created->line_items_id ?? null]);
                } catch (\Exception $e) {
                    // Log detailed exception information for debugging
                    Log::error('Failed to create line item for manual order', [
                        'order_id' => $order->id,
                        'item' => $it,
                        'computed_line_items_id' => $lineItemsId ?? null,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }
        } else {
            Log::info('No line items provided for manual order', ['order_id' => $order->id]);
        }

        // If the order has line items, attempt to create an invoice in Zoho automatically.
        $lineItemsCount = $order->lineItems()->count();
        if ($lineItemsCount > 0) {
            // call the existing sendOrderToZoho flow
            // For manual orders created via this form, do not mark financial status as paid when creating Zoho invoice
            return $this->sendOrderToZoho($request, $order->order_number, false);
        }

        // No line items — skip Zoho invoice creation and instruct the user
        return redirect()->route('admin.orders.show', $order->id)
            ->with('success', 'Order created successfully. Add line items or click "Create Zoho Order" to send invoice to Zoho.');
    }
    public function sendTemplateSms(Request $request, \App\Services\GupshupService $gupshup)
    {
        $validated = $request->validate([
            'order_id'       => 'required|integer|exists:orders,id',
            'template_id'    => 'nullable|string',
            'template_name'  => 'nullable|string',
            'customer_phone' => 'nullable|string',
        ]);

        $order = \App\Models\Order::with(['customer', 'shippingAddress', 'billingAddress', 'lineItems'])
            ->findOrFail($validated['order_id']);

        // 1) pick phone
        $recipient = $order->billingAddress->phone
            ?? $order->shippingAddress->phone
            ?? $order->customer->phone
            ?? $validated['customer_phone']
            ?? null;

        if ($recipient) {
            $recipient = preg_replace('/\D+/', '', $recipient);
            if (strlen($recipient) === 10) {
                $recipient = '91' . $recipient;
            }
        }
        if (!$recipient) {
            return back()->with('error', 'No phone number found to send WhatsApp.');
        }

        // 2) resolve template
        $templateId   = $validated['template_id'] ?? null;
        $templateName = $validated['template_name'] ?? '';

        if (!$templateId && $templateName) {
            $templateId = $gupshup->resolveTemplateIdByName($templateName);
        }
        if (!$templateId) {
            return back()->with('error', 'Could not resolve template ID.');
        }

        /*
     * 3) known template IDs
     */
        $deliveryTemplates = [
            // order_delie_5
            '30b95fca-9747-4fe3-9408-d3fe9ba1f5ec',
            '701274376370642',
            // order_del_5
            'dd20e623-778d-4044-b4f6-d0a56591b718',
            '838721201875496',
        ];

        $cancelTemplates = [
            // order_cancel_new
            '8ce42644-1ea5-45b4-8fb9-fd0afd11c2ff',
            '861308769673680',
            // image cancel
            '77f06628-aaec-4b23-9fb1-470b6e23d3c4',
            '1248763876979384',
        ];

        $refundTemplates = [
            '3af48e44-0ec8-472d-b0bc-c87ccc300359',
            '817566097803666',
        ];

        // 🚚 out for delivery (your screenshot)
        // 🚚 out for delivery (your screenshot)
        $outForDeliveryTemplates = [
            '6cf1eaeb-291a-4dfd-b8cf-59a5629edf34', // old one
            '1572786370714481',                     // fb/external
        ];

        /*
     * 4) common data
     */
        $orderNumber  = $order->order_number ?? $order->id;
        $orderDate    = $order->order_date
            ? \Carbon\Carbon::parse($order->order_date)->format('d-M-Y H:i')
            : '';

        // summary
        $orderSummary = '';
        if ($order->lineItems && $order->lineItems->count()) {
            $parts = $order->lineItems->map(function ($item) {
                $name = $item->title ?? $item->name ?? 'Item';
                $qty  = $item->quantity ?? 1;
                return $name . ' x' . $qty;
            })->toArray();
            $orderSummary = implode(', ', $parts);
            if (strlen($orderSummary) > 900) {
                $orderSummary = substr($orderSummary, 0, 900) . '...';
            }
        }

        $billingState = $order->billingAddress->province ?? '';
        $billingCity  = $order->billingAddress->city ?? $billingState ?? 'Team';

        /*
     * 5) build vars per template
     */
        if (in_array($templateId, $deliveryTemplates, true)) {

            $supportNumber = $this->getSupportNumberForOrder($order);

            $vars = [
                $orderNumber,   // {{1}}
                $supportNumber, // {{2}}
                $billingCity,   // {{3}}
            ];
        } elseif (in_array($templateId, $cancelTemplates, true)) {

            $supportNumber = $this->getSupportNumberForOrder($order);

            $vars = [
                $orderNumber,   // {{1}}
                $supportNumber, // {{2}}
            ];
        } elseif (in_array($templateId, $refundTemplates, true)) {

            $customerName = $order->customer->first_name
                ? trim($order->customer->first_name . ' ' . ($order->customer->last_name ?? ''))
                : ($order->billingAddress->name ?? $order->shippingAddress->name ?? 'Customer');

            // ✅ Take override from form, else fall back to full order amount
            $amountInput = $request->input('refund_amount');
            if ($amountInput === null || $amountInput === '') {
                $amount = $order->total_price ?? 0;
            } else {
                $amount = (float) $amountInput;
            }

            $refundMethod = $order->gateway ?? 'Original payment method';

            $vars = [
                $customerName,                           // {{1}}
                $orderNumber,                            // {{2}}
                '₹' . number_format($amount, 2),         // {{3}}
                $refundMethod,                           // {{4}}
                '3-5 business days',                     // {{5}}
                $billingCity,                            // {{6}}
            ];
        } elseif (in_array($templateId, $outForDeliveryTemplates, true)) {

            $customerName = $order->customer->first_name
                ? trim($order->customer->first_name . ' ' . ($order->customer->last_name ?? ''))
                : ($order->billingAddress->name ?? $order->shippingAddress->name ?? 'Customer');

            // 1) take from form first, else fall back
            $expectedDelivery = $request->input('expected_delivery')
                ?: ($order->delivery_date ?? ($order->noteAttributes->value ?? now()->addDay()->format('d-M-Y')));

            // 2) partner name: form -> default text
            $partnerName = $request->input('partner_name') ?: 'Delivery Partner';

            // 3) partner phone: form -> env fallback
            $partnerPhone = $request->input('partner_phone')
                ?: env('APP_DELIVERY_PHONE', env('APP_SUPPORT_PHONE', '6366356363'));

            $vars = [
                $customerName,   // {{1}}
                $orderNumber,    // {{2}}
                $expectedDelivery, // {{3}}
                $partnerName,    // {{4}}
                $partnerPhone,   // {{5}}
                $billingCity,    // {{6}}
            ];
        } else {
            // generic 4-var template
            $vars = [
                $orderNumber,   // {{1}}
                $orderDate,     // {{2}}
                $orderSummary,  // {{3}}
                $billingState,  // {{4}}
            ];
        }

        $resp = $gupshup->sendTemplate([
            'send_to'     => $recipient,
            'template_id' => $templateId,
            'vars'        => $vars,
            'campaign_id' => 'order-notify-' . $orderNumber,
        ]);

        // 🔥 7) LOG TO DATABASE
        try {
            \App\Models\WhatsAppMessageLog::create([
                'order_id' => $order->id,
                'recipient_phone' => $recipient,
                'template_id' => $templateId,
                'template_name' => $templateName ?: 'Manual Template',
                'template_vars' => $vars,
                'message_type' => 'manual',
                'trigger_event' => $triggerEvent ?? 'manual_send',
                'response' => $resp,
                'status' => !empty($resp['error']) ? 'failed' : 'sent',
                'gupshup_message_id' => $resp['response']['id'] ?? ($resp['id'] ?? null),
                'error_message' => $resp['message'] ?? null,
                'sent_by' => auth('admin')->id(),
            ]);

            // Log::info('WhatsApp message logged successfully', [
            //     'order_id' => $order->id,
            //     'template_id' => $templateId,
            //     'status' => !empty($resp['error']) ? 'failed' : 'sent',
            // ]);
        } catch (\Exception $e) {
            Log::error('Failed to log WhatsApp message', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }

        $gwId = data_get($resp, 'response.id') ?? data_get($resp, 'id');
        if ($gwId && method_exists($gupshup, 'getMessageStatus')) {
            $status = $gupshup->getMessageStatus($gwId);
            session()->flash('wa_status', $status);
        }

        if (!empty($resp['error'])) {
            return back()->with('error', 'Gupshup send failed: ' . ($resp['message'] ?? ''));
        }

        return back()->with('message', 'WhatsApp template sent successfully.');
    }
    protected function getSupportNumberForOrder($order): string
    {
        // read from billing
        $state = strtolower($order->billingAddress->province ?? '');

        // default = Bangalore
        $default = '+91 6366356363';

        if ($state === 'karnataka') {
            return '+91 6366356363'; // Bangalore
        }

        if ($state === 'delhi') {
            return '+91 7042112482'; // New Delhi
        }

        if ($state === 'telangana') {
            return '+91 8309848906'; // Hyderabad
        }

        return $default;
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

    // public function fetchFulfillmentOrders($orderId)
    // {

    //     $response = Http::withHeaders([
    //         'X-Shopify-Access-Token' => env('SHOPIFY_ACCESS_TOKEN'),
    //     ])->get("https://farmerr-in.myshopify.com/admin/api/2024-04/orders/{$orderId}/fulfillment_orders.json");

    //     $fulfillmentOrders = $response->json()['fulfillment_orders']; // Assuming fulfillment_orders is the key in the response JSON

    //     // For example, if you want to use the first fulfillment order's ID:
    //     $fulfillmentOrderId = $fulfillmentOrders[0]['id']; // Adjust as per your logic

    //     return $fulfillmentOrderId;
    // }
    protected function fetchFulfillmentOrders(string $shopifyOrderId)
    {
        $shopUrl = rtrim(env('SHOPIFY_SHOP_URL'), '/');
        $apiVersion = env('SHOPIFY_API_VERSION', '2024-04');

        $url = "{$shopUrl}/admin/api/{$apiVersion}/orders/{$shopifyOrderId}/fulfillment_orders.json";

        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => env('SHOPIFY_ACCESS_TOKEN'),
            'Content-Type' => 'application/json',
        ])->get($url);

        // \Log::info('fetchFulfillmentOrders response', [
        //     'status' => $response->status(),
        //     'body' => $response->body(),
        // ]);

        if ($response->failed()) {
            \Log::error("Failed to fetch fulfillment_orders for shopify order {$shopifyOrderId}: " . $response->body());
            return null;
        }

        $data = $response->json();
        $fulfillmentOrders = $data['fulfillment_orders'] ?? null;

        if (empty($fulfillmentOrders) || !is_array($fulfillmentOrders)) {
            \Log::warning("No fulfillment_orders found for Shopify order {$shopifyOrderId}");
            return null;
        }

        // return the first fulfillment_order id (adjust logic if you need to pick a specific one)
        return $fulfillmentOrders[0]['id'] ?? null;
    }


    public function fulfillOrder(Request $request, Order $order)
    {
        // Ensure we have the shopify order id saved locally
        $shopifyOrderId = $order->shopify_order_id ?? null;
        if (!$shopifyOrderId) {
            return redirect()->back()->with('error', 'Shopify order id not available for this order.');
        }

        try {
            // Get first fulfillment_order id for this shopify order
            $fulfillmentOrderId = $this->fetchFulfillmentOrders($shopifyOrderId);

            if (!$fulfillmentOrderId) {
                return redirect()->back()->with('error', 'Could not fetch fulfillment order from Shopify. Check logs.');
            }

            $shopUrl = rtrim(env('SHOPIFY_SHOP_URL'), '/');
            $apiVersion = env('SHOPIFY_API_VERSION', '2024-04');
            $url = "{$shopUrl}/admin/api/{$apiVersion}/fulfillments.json";

            $payload = [
                'fulfillment' => [
                    'line_items_by_fulfillment_order' => [
                        [
                            'fulfillment_order_id' => $fulfillmentOrderId,
                        ],
                    ],
                    'notify_customer' => true,
                ],
            ];

            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => env('SHOPIFY_ACCESS_TOKEN'),
                'Content-Type' => 'application/json',
            ])->post($url, $payload);

            // Log::info('fulfillOrder - Shopify response', [
            //     'order_id' => $order->id,
            //     'shopify_order_id' => $shopifyOrderId,
            //     'status' => $response->status(),
            //     'body' => $response->body(),
            // ]);

            if ($response->successful()) {
                // Optionally you can parse the response for fulfillment id etc.
                $respJson = $response->json();

                // Update local DB
                $order->fulfillment_status = 'fulfilled';
                $order->save();

                // Return success with a compact response message (you can include returned fulfillment id)
                $fulfillmentId = data_get($respJson, 'fulfillment.id') ?? null;
                $message = 'Fulfillment created in Shopify and order marked fulfilled locally.';
                if ($fulfillmentId) {
                    $message .= " (fulfillment id: {$fulfillmentId})";
                }

                return redirect()->back()->with('success', $message);
            }

            // Not successful — include a trimmed Shopify error in flash
            $body = $response->json();
            $msg = isset($body['errors']) ? json_encode($body['errors']) : $response->body();
            Log::error("Shopify fulfill creation failed for order {$shopifyOrderId}: {$msg}");

            // Trim long messages to avoid session size issues
            $short = strlen($msg) > 500 ? substr($msg, 0, 500) . '...' : $msg;
            return redirect()->back()->with('error', 'Shopify fulfillment failed: ' . $short);
        } catch (\Exception $e) {
            Log::error('Exception in fulfillOrder: ' . $e->getMessage(), ['exception' => $e]);
            return redirect()->back()->with('error', 'An exception occurred while creating fulfillment. Check logs.');
        }
    }

    public function sendOrderToZoho(Request $request, $order_number, $markPaid = true)
    {


        $order = Order::with('lineItems')->where('order_number', $order_number)->first();
        if (!$order) {
            // Handle the case where the order is not found
            return redirect()->back()->with('message', 'Order not found.');
        }

        $order_id = $order->id;
        // dd($order_id);
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
            $res = ZohoController::createInvoice($customerFromZoho, $cartData, $promo_code_amount, $shipping_amt, $addressPost, $order_number, $markPaid);
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
                    // \Log::info("Shipping refund added for order {$order->order_number}");
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
