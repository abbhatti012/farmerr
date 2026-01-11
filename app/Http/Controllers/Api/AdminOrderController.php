<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Admin;
use App\Services\DotPeService;
use Illuminate\Support\Facades\Log;

class AdminOrderController extends Controller
{
      protected $dotPeService;

    public function __construct(DotPeService $dotPeService)
    {
        $this->dotPeService = $dotPeService;
    }

    /**
     * Return paginated order list for authenticated admin.
     */
    public function index(Request $request)
    {
        // Ensure authenticated user exists
        $user = $request->user();
        if (! $user || ! ($user instanceof Admin)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated or unauthorized.'
            ], 401);
        }

        $search = $request->input('search');
        $paymentStatus = $request->input('payment_status');
        $perPage = (int) $request->input('per_page', 20);
        $page = (int) $request->input('page', 1);

        // admin city filter (same as your web controller)
        $adminCity = $user->city ?? null;
        $today = now()->startOfDay();

        $query = Order::query();

        if (! empty($adminCity)) {
            $query->where(function ($q) use ($adminCity) {
                $q->whereHas('shippingAddress', function ($q2) use ($adminCity) {
                    $q2->where('province', $adminCity);
                })->orWhereHas('billingAddress', function ($q3) use ($adminCity) {
                    $q3->where('province', $adminCity);
                });
            });
        }

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($q2) use ($search) {
                      $q2->where('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  })
                  ->orWhereHas('shippingAddress', function ($q3) use ($search) {
                      $q3->where('address1', 'like', "%{$search}%")
                         ->orWhere('city', 'like', "%{$search}%")
                         ->orWhere('province', 'like', "%{$search}%");
                  })
                  ->orWhereHas('billingAddress', function ($q4) use ($search) {
                      $q4->where('address1', 'like', "%{$search}%")
                         ->orWhere('city', 'like', "%{$search}%")
                         ->orWhere('province', 'like', "%{$search}%");
                  })
                  ->orWhere('order_date', 'like', "%{$search}%")
                  ->orWhereHas('noteAttributes', function ($q5) use ($search) {
                      $q5->where('value', 'like', "%{$search}%");
                  });
            });
        }

        if (! empty($paymentStatus)) {
            $query->where('financial_status', $paymentStatus);
        }

        $orders = $query->with(['customer', 'lineItems', 'shippingAddress', 'billingAddress'])
                        ->orderBy('order_date', 'desc')
                        ->paginate($perPage, ['*'], 'page', $page);

        // Totals for today's orders in the admin city
        $todaysOrdersQuery = Order::where('order_date', '>=', $today);
        if (! empty($adminCity)) {
            $todaysOrdersQuery->whereHas('shippingAddress', function ($q) use ($adminCity) {
                $q->where('province', $adminCity);
            })->orWhereHas('billingAddress', function ($q) use ($adminCity) {
                $q->where('province', $adminCity);
            });
        }
        $todaysOrders = $todaysOrdersQuery->get();
        $totalAmountToday = $todaysOrders->sum('total_price');
        $totalOrdersToday = $todaysOrders->count();

        // Return paginated JSON including meta totals
        return response()->json([
            'status' => 'success',
            'data' => [
                'orders' => $orders->items(),
                'pagination' => [
                    'current_page' => $orders->currentPage(),
                    'per_page' => $orders->perPage(),
                    'last_page' => $orders->lastPage(),
                    'total' => $orders->total(),
                ],
                'totals' => [
                    'total_orders_today' => $totalOrdersToday,
                    'total_amount_today' => (float) $totalAmountToday,
                ],
            ],
        ]);
    }
     /**
     * GET /api/admin/orders/{id}
     * Return single order details, previous/next ids, and templates.
     */
    public function show(Request $request, $orderId)
    {
        $user = $request->user();
        if (! $user || ! ($user instanceof Admin)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated or unauthorized.'
            ], 401);
        }

        try {
            $order = Order::with(['customer', 'lineItems', 'shippingAddress', 'billingAddress', 'noteAttributes'])
                          ->findOrFail($orderId);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Order not found.'
            ], 404);
        }

        $previousOrder = Order::where('id', '<', $orderId)->orderBy('id', 'desc')->first();
        $nextOrder = Order::where('id', '>', $orderId)->orderBy('id', 'asc')->first();

        // Get dotPe templates (safe access to data key)
        $templates = $this->dotPeService->getTemplates();
        $allowedTemplates = [
            'final_order_confirmation_whatsapp_message',
            'order_tracking_whatsapp_message',
            'order_feedback_whatsapp_message'
        ];

        $processedTemplates = collect($templates['data'] ?? [])->filter(function ($template) use ($allowedTemplates) {
            return isset($template['templateName']) && in_array($template['templateName'], $allowedTemplates);
        })->map(function ($template) {
            return [
                'templateName' => $template['templateName'],
                'sampleText' => $template['sampleText'] ?? '',
            ];
        })->values();

        return response()->json([
            'status' => 'success',
            'data' => [
                'order' => $order,
                'previous_order_id' => $previousOrder ? $previousOrder->id : null,
                'next_order_id' => $nextOrder ? $nextOrder->id : null,
                'templates' => $processedTemplates,
            ],
        ], 200);
    }

    /**
     * Update financial status for an order via API (authenticated Admin)
     */
    public function updateFinancialStatus(Request $request, $orderId)
    {
        $user = $request->user();
        if (! $user || ! ($user instanceof \App\Models\Admin)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthenticated'], 401);
        }

        $validated = $request->validate([
            'financial_status' => 'required|string|max:50',
        ]);

        $allowed = [
            'pending', 'paid', 'refunded', 'partially_refunded',
            'authorized', 'voided', 'cancelled', 'partially_paid'
        ];

        $status = strtolower($validated['financial_status']);
        if (! in_array($status, $allowed, true)) {
            return response()->json(['status' => 'error', 'message' => 'Invalid financial_status', 'allowed' => $allowed], 422);
        }

        $order = Order::find($orderId);
        if (! $order) {
            return response()->json(['status' => 'error', 'message' => 'Order not found'], 404);
        }

        $previous = $order->financial_status;
        $order->financial_status = $status;
        $order->save();

        Log::info('API Order financial_status updated', [
            'admin_id' => $user->id,
            'order_id' => $order->id,
            'from' => $previous,
            'to' => $status,
        ]);

        return response()->json(['status' => 'success', 'message' => 'Financial status updated', 'order' => $order], 200);
    }

    /**
     * GET /api/admin/orders/details/{identifier}
     * Get order details by order ID (database ID) or order number
     * This endpoint can accept either the database ID or order number
     */
    public function getOrderDetails(Request $request, $identifier)
    {
        $user = $request->user();
        if (! $user || ! ($user instanceof Admin)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated or unauthorized.'
            ], 401);
        }

        try {
            // Try to find by database ID first, then by order number
            $order = Order::with([
                'customer', 
                'lineItems', 
                'shippingAddress', 
                'billingAddress', 
                'noteAttributes'
            ])
            ->where('id', $identifier)
            ->orWhere('order_number', $identifier)
            ->first();

            if (!$order) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Order not found with the provided identifier.'
                ], 404);
            }

            // Calculate totals
            $lineItemsTotal = $order->lineItems ? $order->lineItems->sum(function($item) {
                return $item->price * $item->quantity;
            }) : 0;

            // Format the response with comprehensive order details
            $orderData = [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'shopify_order_id' => $order->shopify_order_id,
                'order_date' => $order->order_date,
                'delivery_date' => $order->delivery_date,
                'financial_status' => $order->financial_status,
                'fulfillment_status' => $order->fulfillment_status,
                'total_price' => (float) $order->total_price,
                'subtotal_price' => (float) $order->subtotal_price,
                'total_tax' => (float) $order->total_tax,
                'total_shipping_price' => (float) $order->total_shipping_price,
                'total_discounts' => (float) $order->total_discounts,
                'currency' => $order->currency,
                'email' => $order->email,
                'phone' => $order->phone,
                'note' => $order->note,
                'description' => $order->description,
                'occasion' => $order->occasion,
                'zoho_status' => $order->zoho_status,
                'created_at' => $order->created_at,
                'updated_at' => $order->updated_at,
                
                // Customer details
                'customer' => $order->customer ? [
                    'id' => $order->customer->id,
                    'first_name' => $order->customer->first_name,
                    'last_name' => $order->customer->last_name,
                    'email' => $order->customer->email,
                    'phone' => $order->customer->phone,
                    'mobile' => $order->customer->mobile,
                    'company' => $order->customer->company,
                    'gstin' => $order->customer->gstin,
                ] : null,
                
                // Billing address
                'billing_address' => $order->billingAddress ? [
                    'name' => $order->billingAddress->name,
                    'address1' => $order->billingAddress->address1,
                    'address2' => $order->billingAddress->address2,
                    'city' => $order->billingAddress->city,
                    'province' => $order->billingAddress->province,
                    'country' => $order->billingAddress->country,
                    'zip' => $order->billingAddress->zip,
                    'phone' => $order->billingAddress->phone,
                ] : null,
                
                // Shipping address
                'shipping_address' => $order->shippingAddress ? [
                    'name' => $order->shippingAddress->name,
                    'address1' => $order->shippingAddress->address1,
                    'address2' => $order->shippingAddress->address2,
                    'city' => $order->shippingAddress->city,
                    'province' => $order->shippingAddress->province,
                    'country' => $order->shippingAddress->country,
                    'zip' => $order->shippingAddress->zip,
                    'phone' => $order->shippingAddress->phone,
                ] : null,
                
                // Line items
                'line_items' => $order->lineItems ? $order->lineItems->map(function($item) {
                    return [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'variant_id' => $item->variant_id,
                        'line_items_id' => $item->line_items_id,
                        'quantity' => (int) $item->quantity,
                        'price' => (float) $item->price,
                        'total_discount' => (float) $item->total_discount,
                        'name' => $item->name,
                        'title' => $item->title,
                        'sku' => $item->sku,
                        'fulfillment_status' => $item->fulfillment_status,
                        'requires_shipping' => (bool) $item->requires_shipping,
                        'taxable' => (bool) $item->taxable,
                        'tax_id' => $item->tax_id,
                    ];
                }) : [],
                
                // Note attributes - handle both JSON field and relationship
                'note_attributes' => $this->formatNoteAttributes($order),
                
                // Calculated totals
                'calculated_totals' => [
                    'line_items_total' => (float) $lineItemsTotal,
                    'items_count' => $order->lineItems ? $order->lineItems->count() : 0,
                ]
            ];

            return response()->json([
                'status' => 'success',
                'message' => 'Order details retrieved successfully.',
                'data' => $orderData
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error retrieving order details via API', [
                'identifier' => $identifier,
                'error' => $e->getMessage(),
                'admin_id' => $user->id ?? null
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while retrieving order details.'
            ], 500);
        }
    }

    /**
     * Format note attributes from both JSON field and relationship
     */
    private function formatNoteAttributes($order)
    {
        $noteAttributes = [];

        // Check if there's a noteAttributes relationship (single record)
        if ($order->noteAttributes) {
            $noteAttributes[] = [
                'name' => $order->noteAttributes->name,
                'value' => $order->noteAttributes->value,
            ];
        }

        // Check if there's a note_attributes JSON field
        if (!empty($order->note_attributes)) {
            try {
                $jsonAttributes = is_string($order->note_attributes) 
                    ? json_decode($order->note_attributes, true) 
                    : $order->note_attributes;
                
                if (is_array($jsonAttributes)) {
                    foreach ($jsonAttributes as $attr) {
                        if (isset($attr['name']) && isset($attr['value'])) {
                            $noteAttributes[] = [
                                'name' => $attr['name'],
                                'value' => $attr['value'],
                            ];
                        }
                    }
                }
            } catch (\Exception $e) {
                // If JSON parsing fails, just continue
            }
        }

        return $noteAttributes;
    }
}
