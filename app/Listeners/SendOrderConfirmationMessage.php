<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Models\WhatsAppMessageLog;
use App\Services\GupshupService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SendOrderConfirmationMessage
{
    protected GupshupService $gupshup;

    public function __construct(GupshupService $gupshup)
    {
        $this->gupshup = $gupshup;
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\OrderPlaced  $event
     * @return void
     */
    public function handle(OrderPlaced $event): void
    {
        try {
            // Log::info('[GUPSHUP LISTENER] OrderPlaced event triggered', [
            //     'order_id' => $event->order->id ?? null,
            // ]);

            $order = $event->order->load(['customer', 'billingAddress', 'shippingAddress', 'lineItems']);

            // (1) PHONE (billing → shipping → customer)
            $recipient = $order->billingAddress->phone
                ?? $order->shippingAddress->phone
                ?? $order->customer->phone
                ?? null;

            if (!$recipient) {
                Log::warning('[GUPSHUP LISTENER] No phone number found', [
                    'order_id' => $order->id,
                ]);
                return;
            }

            // normalize
            $recipient = preg_replace('/\D+/', '', $recipient);
            if (strlen($recipient) === 10) {
                $recipient = '91' . $recipient;
            }

            // Log::info('[GUPSHUP LISTENER] Recipient resolved', [
            //     'recipient' => $recipient,
            // ]);

            // (2) Prepare 5 vars
            $customerName = $order->customer->first_name
                ? trim($order->customer->first_name . ' ' . ($order->customer->last_name ?? ''))
                : ($order->billingAddress->name ?? $order->shippingAddress->name ?? 'Customer');

            $orderNumber = $order->order_number ?? $order->id;
            $orderDate = $order->order_date
                ? Carbon::parse($order->order_date)->format('d-M-Y H:i')
                : now()->format('d-M-Y H:i');

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

            $vars = [
                // $customerName,
                $orderNumber,
                $orderDate,
                $orderSummary,
                $billingState,
            ];

            // Log::info('[GUPSHUP LISTENER] Message variables prepared', [
            //     'vars' => $vars,
            // ]);

            // (3) Template ID
            $templateId = env('GUPSHUP_ORDER_TEMPLATE_ID');
            if (!$templateId) {
                Log::error('[GUPSHUP LISTENER] Missing GUPSHUP_ORDER_TEMPLATE_ID in .env');
                return;
            }

            // (4) SEND
            $payload = [
                'send_to'     => $recipient,
                'template_id' => $templateId,
                'vars'        => $vars,
                'campaign_id' => 'order-confirm-' . $orderNumber,
            ];

            // Log::info('[GUPSHUP LISTENER] Sending template message', [
            //     'payload' => $payload,
            // ]);

            $resp = $this->gupshup->sendTemplate($payload);

            WhatsAppMessageLog::create([
                'order_id' => $order->id,
                'recipient_phone' => $recipient,
                'template_id' => $templateId,
                'template_name' => 'Order Confirmation',
                'template_vars' => $vars,
                'message_type' => 'automatic',
                'trigger_event' => 'order_placed',
                'response' => $resp,
                'status' => !empty($resp['error']) ? 'failed' : 'sent',
                'gupshup_message_id' => $resp['response']['id'] ?? ($resp['id'] ?? null),
                'error_message' => $resp['message'] ?? null,
                'sent_by' => null, // automatic, no admin
            ]);

            // Log::info('[GUPSHUP LISTENER] Gupshup response', [
            //     'response' => $resp,
            // ]);

            $gwId = data_get($resp, 'response.id') ?? data_get($resp, 'id');
            if ($gwId) {
                // Log::info('[GUPSHUP LISTENER] Fetching message status', [
                //     'gw_id' => $gwId,
                // ]);
                $status = $this->gupshup->getMessageStatus($gwId);
                // Log::info('[GUPSHUP LISTENER] Message status', [
                //     'status' => $status,
                // ]);
            }
        } catch (\Throwable $e) {
            Log::error('[GUPSHUP LISTENER] Exception occurred', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
