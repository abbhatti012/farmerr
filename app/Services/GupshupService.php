<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon; // ✅ needed for Carbon::parse

class GupshupService
{
    protected Client $client;
    protected string $mediaUrl = 'https://media.smsgupshup.com/GatewayAPI/rest';

    protected ?string $apiKey;
    protected ?string $appId;

    public function __construct()
    {
        $this->client = new Client(['timeout' => 30]);
        $this->apiKey = env('GUPSHUP_API_KEY');
        $this->appId  = env('GUPSHUP_APP_ID');
    }

    /**
     * Send WA template (media API)
     */
    public function sendTemplate(array $data): array
    {
        $url  = $this->mediaUrl;

        // map vars -> var1, var2, ...
        $vars = $this->prepareTemplateVars($data['vars'] ?? []);

        $payload = [
            'send_to'            => $data['send_to'],
            'msg_type'           => 'text',
            'userid'             => env('GUPSHUP_USER_ID'),
            'auth_scheme'        => 'plain',
            'password'           => env('GUPSHUP_PASSWORD'),
            'v'                  => '1.1',
            'format'             => 'text',
            'method'             => 'SendMessage',
            'isHSM'              => 'true',
            'isTemplate'         => 'false',
            'msg_id'             => $data['msg_id'] ?? 'tes_msg_id',
            'campaign_id'        => $data['campaign_id'] ?? 'default-campaign',
            'whatsAppTemplateId' => $data['template_id'],
        ] + $vars;

        // Log::info('Gupshup SendMessage payload', $payload);

        try {
            $response = $this->client->post($url, [
                'form_params' => $payload,
                'headers'     => ['Content-Type' => 'application/x-www-form-urlencoded'],
            ]);

            $body = (string) $response->getBody();

            // Log::info('Gupshup SendMessage RESPONSE', [
            //     'http_status' => $response->getStatusCode(),
            //     'body'        => $body,
            // ]);

            return json_decode($body, true) ?: ['raw' => $body];
        } catch (\Throwable $e) {
            Log::error('Gupshup API Error', ['message' => $e->getMessage()]);
            return ['error' => true, 'message' => $e->getMessage()];
        }
    }

    public function getTemplates(?string $appId = null): array
    {
        $appId = $appId ?: $this->appId;
        if (!$appId || !$this->apiKey) {
            return ['error' => true, 'message' => 'App ID or API key missing'];
        }

        $url = sprintf('https://api.gupshup.io/wa/app/%s/template', $appId);

        try {
            $resp = $this->client->get($url, [
                'headers' => ['apikey' => $this->apiKey],
            ]);

            $body = (string) $resp->getBody();
            // Log::info('Gupshup List Templates RESPONSE', ['body' => $body]);

            return json_decode($body, true) ?? [];
        } catch (RequestException $e) {
            Log::error('Gupshup List Templates Error', [
                'message'  => $e->getMessage(),
                'response' => $e->hasResponse() ? (string) $e->getResponse()->getBody() : null,
            ]);

            return ['error' => true, 'message' => $e->getMessage()];
        }
    }

    public function resolveTemplateIdByName(string $templateName): ?string
    {
        $raw  = $this->getTemplates();
        $list = $raw['templates'] ?? $raw ?? [];

        foreach ($list as $tpl) {
            $name = $tpl['elementName'] ?? ($tpl['name'] ?? $tpl['templateName'] ?? null);
            $id   = $tpl['id'] ?? ($tpl['templateId'] ?? null);
            if ($name && $id && strcasecmp($name, $templateName) === 0) {
                return (string) $id;
            }
        }
        return null;
    }

    protected function prepareTemplateVars(array $vars): array
    {
        $out = [];
        foreach ($vars as $i => $val) {
            $out['var' . ($i + 1)] = $val;
        }
        return $out;
    }

    /**
     * ⚠️ you still have this form-handling method here.
     * Leaving it since it's what you're using right now.
     */
    // public function sendTemplateSms(\Illuminate\Http\Request $request, GupshupService $gupshup)
    // {
    //     $validated = $request->validate([
    //         'order_id'       => 'required|integer|exists:orders,id',
    //         'template_id'    => 'nullable|string',
    //         'template_name'  => 'nullable|string',
    //         'customer_phone' => 'nullable|string',
    //     ]);

    //     $order = \App\Models\Order::with(['customer', 'shippingAddress', 'billingAddress', 'lineItems'])
    //         ->findOrFail($validated['order_id']);

    //     // 1) pick phone
    //     $recipient = $order->billingAddress->phone
    //         ?? $order->shippingAddress->phone
    //         ?? $order->customer->phone
    //         ?? $validated['customer_phone']
    //         ?? null;

    //     if ($recipient) {
    //         $recipient = preg_replace('/\D+/', '', $recipient);
    //         if (strlen($recipient) === 10) {
    //             $recipient = '91' . $recipient;
    //         }
    //     }
    //     if (!$recipient) {
    //         return back()->with('error', 'No phone number found to send WhatsApp.');
    //     }

    //     // 2) template id / name
    //     $templateId   = $validated['template_id'];
    //     $templateName = $validated['template_name'] ?? '';

    //     if (!$templateId && $templateName) {
    //         $templateId = $gupshup->resolveTemplateIdByName($templateName);
    //     }
    //     if (!$templateId) {
    //         return back()->with('error', 'Could not resolve template ID.');
    //     }

    //     // 3) common data
    //     $orderNumber = $order->order_number ?? $order->id;
    //     $tplKey = strtolower(str_replace(' ', '', trim($templateName)));

    //     // 4) build vars
    //     if (in_array($tplKey, ['order_delie_5', 'order_del_5'])) {
    //         // template with EXACTLY 3 vars
    //         $supportNumber = env('APP_SUPPORT_PHONE', '+91-7536011971');

    //         $vars = [
    //             $orderNumber,    // {{1}}
    //             $supportNumber,  // {{2}}
    //             'Team',          // {{3}} -> — Team, Farmerr
    //         ];
    //     } else {
    //         // generic / other templates
    //         $orderDate   = $order->order_date
    //             ? Carbon::parse($order->order_date)->format('d-M-Y H:i')
    //             : '';

    //         // build order summary
    //         $orderSummary = '';
    //         if ($order->lineItems && $order->lineItems->count()) {
    //             $parts = $order->lineItems->map(function ($item) {
    //                 $name = $item->title ?? $item->name ?? 'Item';
    //                 $qty  = $item->quantity ?? 1;
    //                 return $name . ' x' . $qty;
    //             })->toArray();
    //             $orderSummary = implode(', ', $parts);
    //             if (strlen($orderSummary) > 900) {
    //                 $orderSummary = substr($orderSummary, 0, 900) . '...';
    //             }
    //         }

    //         $billingState = $order->billingAddress->province ?? '';

    //         // ✅ NO NAME HERE ANYMORE
    //         $vars = [
    //             $orderNumber,   // {{1}}
    //             $orderDate,     // {{2}}
    //             $orderSummary,  // {{3}}
    //             $billingState,  // {{4}}
    //         ];
    //     }

    //     // 5) send
    //     Log::info('Sending WA template', [
    //         'template' => $templateName,
    //         'tplKey'   => $tplKey,
    //         'vars'     => $vars,
    //     ]);

    //     $resp = $gupshup->sendTemplate([
    //         'send_to'     => $recipient,
    //         'template_id' => $templateId,
    //         'vars'        => $vars,
    //         'campaign_id' => 'order-notify-' . $orderNumber,
    //     ]);

    //     if (!empty($resp['error'])) {
    //         return back()->with('error', 'Gupshup send failed: ' . ($resp['message'] ?? ''));
    //     }

    //     return back()->with('message', 'WhatsApp template sent successfully.');
    // }
}
