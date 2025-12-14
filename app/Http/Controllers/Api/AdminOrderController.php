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
}
