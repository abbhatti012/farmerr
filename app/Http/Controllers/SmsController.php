<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DotPeService;
use App\Models\Order;

class SmsController extends Controller
{
    protected $dotPeService;

    public function __construct(DotPeService $dotPeService)
    {
        $this->dotPeService = $dotPeService;
    }

    public function sendTemplateSms(Request $request)
    {

        // Sanitize phone number input
        $request->merge([
            'customer_phone' => preg_replace('/\s+/', '', $request->input('customer_phone'))
        ]);

        // Validate the input after sanitization
        $validated = $request->validate([
            'template_name' => 'required|string',
            'customer_phone' => 'required|string|regex:/^\+?[1-9]\d{9,14}$/',
            'tracking_link' => 'nullable'
        ]);

        // Adjust the phone number format
        $customerPhone = $validated['customer_phone'];

        // If the number is 10 digits (Indian mobile number), prepend '+91'
        if (preg_match('/^\d{10}$/', $customerPhone)) {
            $customerPhone = '91' . $customerPhone;
        }
        // If the number starts with '91' but no '+', add '+'
        elseif (preg_match('/^91\d{10}$/', $customerPhone)) {
            $customerPhone = '' . $customerPhone;
        }

        try {
            $order = Order::findOrFail($request->order_id);

            // Prepare parameters dynamically based on the template
            $params = [];
            switch ($validated['template_name']) {
                case 'final_order_confirmation_whatsapp_message':
                    $params['body'] = [
                        $order->customer->first_name ?? 'Customer',
                        $order->order_number ?? 'Order Number',
                        implode(', ', $order->lineItems->pluck('name')->toArray()),
                        '₹' . number_format($order->total_price),
                        $order->shippingAddress->address1 . ', ' . $order->shippingAddress->city . ', ' . $order->shippingAddress->zip,
                    ];
                    break;

                case 'order_tracking_whatsapp_message':
                    $params['body'] = [
                        $order->customer->first_name ?? 'Customer',
                        $order->order_number,
                        $request->tracking_link,
                    ];
                    break;

                case 'order_feedback_whatsapp_message':
                    $params['body'] = [
                        $order->customer->first_name ?? 'Customer',
                        $order->feedback_url ?? 'https://example.com/feedback',
                    ];
                    break;
            }

            // Send the template message
            $response = $this->dotPeService->sendTemplateMessage(
                '918651134826', // WABA number
                [$customerPhone],
                $validated['template_name'],
                'en',
                $params
            );

            // Log the response for debugging
            \Log::info('Template SMS Response', [
                'template_name' => $validated['template_name'],
                'customer_phone' => $customerPhone,
                'params' => $params,
                'response' => $response,
            ]);

            if (isset($response['error'])) {
                return back()->with('error', $response['error']);
            }

            session()->flash('success', 'Fulfillment created successfully!');
            return back()->with('success', 'Message sent successfully!');
        } catch (\Exception $e) {
            // Log the exception for debugging
            // \Log::error('Error sending template SMS', [
            //     'error_message' => $e->getMessage(),
            //     'stack_trace' => $e->getTraceAsString(),
            // ]);

            return back()->with('error', 'Failed to send the message. Please try again later.');
        }
    }
}
