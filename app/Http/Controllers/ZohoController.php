<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Facades\DB;
use App\Models\ShippingAddress;
use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZohoController extends Controller
{

    public static function generateRefreshToken()
    {
        $client = new Client();
        $response = $client->post('https://accounts.zoho.com/oauth/v2/token', [
            'form_params' => [
                'client_id' => env('ZOHO_API_CLIENT_ID'),
                'grant_type' => 'authorization_code',
                'client_secret' => env('ZOHO_API_CLIENT_SECRET'),
                'redirect_uri' => 'https://zoho.com/auth/callback',
                'code' => env('ZOHO_API_REFRESH_CODE')
            ]
        ]);
        $body = json_decode($response->getBody());
        // dd($body);
    }
    public static function generateToken()
    {

        $access_token = \Cache::remember('zoho_access_token12', '3500', function () {
            $client = new Client();
            $response = $client->request('POST', 'https://accounts.zoho.com/oauth/v2/token', [
                'form_params' => [
                    'client_id' => env('ZOHO_API_CLIENT_ID'),
                    'grant_type' => 'refresh_token',
                    'client_secret' => env('ZOHO_API_CLIENT_SECRET'),
                    'redirect_uri' => env('ZOHO_REDIRECT_URI'),
                    'refresh_token' => env('ZOHO_API_REFRESH_CODE')
                ]
            ]);

            $body = json_decode($response->getBody());

            // Check if the API response contains an error
            if (isset($body->error)) {
                // Check if error_description is set, if not use a default message
                $error_description = isset($body->error_description) ? $body->error_description : 'No error description provided';
                \Log::error("Zoho API Error: " . $body->error . " - " . $error_description);
                throw new \Exception("Zoho API Error: " . $body->error . " - " . $error_description);
            }

            // Check if access_token exists
            if (!isset($body->access_token)) {
                \Log::error("Zoho API did not return access_token", ['response' => $body]);
                throw new \Exception("Zoho API did not return an access token");
            }

            return $body->access_token;
        });

        return $access_token;
    }




    private static function skuToItemId($sku)
    {
        $client = new Client();
        $response = $client->get('https://www.zohoapis.com/books/v3/items', [
            'headers' => [
                'Authorization' => 'Zoho-oauthtoken ' . self::generateToken(),
                'Content-Type' => 'application/json'
            ],
            'query' => [
                'organization_id' => env('ZOHO_API_ORGANIZATION_ID'),
                'criteria' => "(SKU:equals:" . $sku . ")",
                'per_page' => 10,
                'sku' => $sku,
            ]
        ]);

        $data = json_decode($response->getBody(), true);

        if (empty($data['items']) || count($data['items']) != 1) {
            // SKU not found, log error or handle accordingly
            \Log::error("Zoho SKU Not Found for SKU: " . $sku);
            throw new \Exception("Zoho SKU Not Found for SKU: " . $sku);
        }

        $item = $data['items'][0];

        $tax_by_specification = [];
        if (isset($item['item_tax_preferences'])) {
            foreach ($item['item_tax_preferences'] as $item_tax_preferences) {
                $tax_by_specification[$item_tax_preferences['tax_specification']] = [
                    "tax_id" => $item_tax_preferences['tax_id'],
                    "tax_percentage" => $item_tax_preferences['tax_percentage']
                ];
            }
        } else {
            // Handle the case where 'item_tax_preferences' is not set
            \Log::warning("No tax preferences found for SKU: " . $sku);
        }

        return [
            "item_id" => $item['item_id'],
            "item_name" => $item['item_name'],
            'hsn_or_sac'         => $item['hsn_or_sac'] ?? null,
            "tax_by_specification" => $tax_by_specification
        ];
    }
    // public static function createInvoice($customer, $cartData, $discount = 0, $shipping_amt = 0, $addressPost, $orderId)
    // {
    //     $getOrder = Order::where('order_number', $orderId)->first();

    //     $client = new Client();
    //     $branchDetails = [
    //         'Karnataka' => [
    //             'gst_no' => '29AALCK2953Q1ZM',
    //             'prefix' => 'BLR',
    //             'stateCode' => 'KA',
    //             'location_id' => '5630788000000144199'
    //         ],
    //         'Delhi' => [
    //             'gst_no' => '07AALCK2953Q1ZS',
    //             'prefix' => 'DEL',
    //             'stateCode' => 'DL',
    //             'location_id' => '5630788000000144349'
    //         ],
    //         'Uttar Pradesh' => [
    //             'gst_no' => '07AALCK2953Q1ZS',
    //             'prefix' => 'DEL',
    //             'stateCode' => 'DL',
    //             'location_id' => '5630788000000144349'
    //         ],
    //         'Haryana' => [
    //             'gst_no' => '07AALCK2953Q1ZS',
    //             'prefix' => 'DEL',
    //             'stateCode' => 'DL',
    //             'location_id' => '5630788000000144349'
    //         ],
    //         'Telangana' => [
    //             'gst_no' => '36AALCK2953Q1ZR',
    //             'prefix' => 'HYD',
    //             'stateCode' => 'TS',
    //             'location_id' => '5630788000000144368'
    //         ]
    //     ];

    //     // $state = $addressPost['state'];
    //     // if (!isset($branchDetails[$state])) {
    //     //     throw new \Exception("No branch found for city: " . $state);
    //     // }

    //     // Extract state from billing address
    //     $state = $addressPost['state'] ?? null;
    //     if (!isset($branchDetails[$state])) {
    //         $shippingAddress = ShippingAddress::where('order_id', $getOrder->id)->first();
    //         if ($shippingAddress && isset($branchDetails[$shippingAddress->province])) {
    //             $state = $shippingAddress->province;
    //         } else {
    //             throw new \Exception("No branch found for billing or shipping state.");
    //         }
    //     }

    //     $branch = $branchDetails[$state];
    //     $gst_no = $branch['gst_no'];
    //     $branchPrefix = $branch['prefix'];
    //     $location_id = $branch['location_id'];
    //     $staeteCode = $branch['stateCode'];

    //     $line_items = [];
    //     foreach ($cartData as $cart) {
    //         $itemFromZoho = self::skuToItemId($cart->item_id);
    //         $line_items[] = [
    //             'name' => $itemFromZoho['item_name'],
    //             'rate' => $cart->price,
    //             'quantity' => $cart->qty,
    //             'item_id' => $itemFromZoho['item_id'],
    //             'tax_exemption_code' => 'tax_exempt',
    //         ];
    //     }
    //     // After building $line_items from cart, add:
    //     if ($shipping_amt > 0) {
    //         $shippingSku = env('ZOHO_SHIPPING_SKU', 'SHIPPING');
    //         $shippingItem = self::skuToItemId($shippingSku); // reuses your helper

    //         $line_items[] = [
    //             'name' => $shippingItem['item_name'] ?? 'Shipping Charges',
    //             'rate' => (float) $shipping_amt,
    //             'quantity' => 1,
    //             'item_id' => $shippingItem['item_id'],
    //             // keep it 0% / exempt (match your current behavior)
    //             'tax_exemption_code' => 'tax_exempt',
    //             // If you ever want to tax shipping according to Zoho’s item tax:
    //             // 'tax_id' => $shippingItem['tax_by_specification']['INTRA'] ?? null,
    //         ];
    //     }

    //     $seriesName = 'Shopify';

    //     try {
    //         $response = $client->post('https://www.zohoapis.com/books/v3/invoices?organization_id=' . env('ZOHO_API_ORGANIZATION_ID'), [
    //             'headers' => [
    //                 'Authorization' => 'Zoho-oauthtoken ' . self::generateToken(),
    //                 'Content-Type' => 'application/json',
    //             ],
    //             'json' => [
    //                 'customer_id' => $customer->contact_id,
    //                 'location_id' => $location_id,
    //                 'line_items' => $line_items,
    //                 'description' => 'Invoice generated by API',
    //                 'discount' => $discount,
    //                 'discount_type' => 'entity_level',
    //                 'order_id' => $orderId,
    //                 // 'shipping_charge' => $shipping_amt,
    //                 // 'shipping_charge_tax_id' => null,
    //                 'reference_number' => $orderId,
    //                 'tax_exemption_code' => 'tax_exempt',
    //                 'place_of_supply' => $staeteCode,
    //                 'state_code' => $staeteCode,
    //                 'gst_no' => $gst_no,
    //                 'series_name' => $seriesName,
    //                 'status' => 'paid'
    //             ]
    //         ]);

    //         $invoiceData = json_decode($response->getBody()->getContents(), true);

    //         if (!isset($invoiceData['invoice']['invoice_id']) || !isset($invoiceData['invoice']['customer_id'])) {
    //             throw new \Exception("Invoice creation failed or missing data.");
    //         }

    //         // Mark as paid
    //         self::markInvoiceAsPaid(
    //             $invoiceData['invoice']['invoice_id'],
    //             $invoiceData['invoice']['customer_id'],
    //             $invoiceData['invoice']['total']
    //         );

    //         // Update the order table with a remark for success
    //         DB::table('orders')
    //             ->where('id', $orderId) // Replace 'order_id' with the correct column name
    //             ->update(['zoho_status' => 1]);

    //         return $invoiceData;
    //     } catch (\Exception $e) {
    //         // Log error and update the order table with a failure remark
    //         // \Log::error("Error creating invoice: " . $e->getMessage());
    //         DB::table('orders')
    //             ->where('id', $orderId) // Replace 'order_id' with the correct column name
    //             ->update(['zoho_status' => 0]);

    //         throw new \Exception("Error creating invoice: " . $e->getMessage());
    //     }
    // }

    /** Return the fixed 0% tax ids (env wins; falls back to the ids you shared). */
    private static function fixedZeroTaxIds(): array
    {
        $igst = env('ZOHO_IGST0_ID', '5630788000000144071');   // IGST 0 (single)
        $gstg = env('ZOHO_GST0_ID',  '5630788000000144161');   // GST 0 (compound / tax group)

        \Log::info('ZERO-TAX-IDS-FIXED', ['IGST0' => $igst, 'GST0_GROUP' => $gstg]);
        return ['IGST0' => $igst, 'GST0_GROUP' => $gstg];
    }

    /**
     * Fetch organization taxes from Zoho and return array keyed by tax_id.
     * Cached for short duration to avoid repeated API calls.
     */
    private static function fetchOrgTaxes(): array
    {
        return \Cache::remember('zoho_org_taxes_map', 300, function () {
            try {
                $client = new Client();
                $resp = $client->get('https://www.zohoapis.com/books/v3/settings/taxes', [
                    'headers' => ['Authorization' => 'Zoho-oauthtoken ' . self::generateToken()],
                    'query' => ['organization_id' => env('ZOHO_API_ORGANIZATION_ID')],
                ]);
                $body = json_decode($resp->getBody()->getContents(), true);
                $taxes = $body['taxes'] ?? [];
                $out = [];
                foreach ($taxes as $t) {
                    $out[$t['tax_id']] = $t;
                }
                return $out;
            } catch (\Exception $e) {
                \Log::error('fetchOrgTaxes failed: ' . $e->getMessage());
                return [];
            }
        });
    }

    /**
     * Find an organization tax id by matching percentage (best-effort).
     */
    private static function findOrgTaxIdByPercentage(float $percentage): ?string
    {
        $taxes = self::fetchOrgTaxes();
        foreach ($taxes as $taxId => $t) {
            $p = isset($t['tax_percentage']) ? (float)$t['tax_percentage'] : null;
            if ($p === $percentage) return $taxId;
            if (!empty($t['taxes']) && is_array($t['taxes'])) {
                foreach ($t['taxes'] as $component) {
                    if (isset($component['tax_percentage']) && (float)$component['tax_percentage'] === $percentage) {
                        return $taxId;
                    }
                }
            }
        }
        return null;
    }

    /**
     * Choose a tax_id from item tax preferences based on whether the order is interstate.
     */
    private static function selectTaxIdFromItemTaxPreferences(array $taxBySpec, bool $isInter): ?string
    {
        $keys = array_keys($taxBySpec);
        $lowerKeys = array_map('strtolower', $keys);

        if ($isInter) {
            foreach ($lowerKeys as $i => $k) {
                if (strpos($k, 'igst') !== false || strpos($k, 'inter') !== false) {
                    return $taxBySpec[$keys[$i]]['tax_id'] ?? null;
                }
            }
        } else {
            foreach ($lowerKeys as $i => $k) {
                if (strpos($k, 'sgst') !== false || strpos($k, 'cgst') !== false || strpos($k, 'intra') !== false) {
                    return $taxBySpec[$keys[$i]]['tax_id'] ?? null;
                }
            }
        }

        foreach ($taxBySpec as $spec => $meta) {
            if (!empty($meta['tax_id'])) return $meta['tax_id'];
        }
        return null;
    }

    /**
     * Admin endpoint: return organization taxes from Zoho (cached)
     */
    public function getTaxes(Request $request)
    {
        $cacheKey = 'zoho_org_taxes_list';
        $taxes = \Cache::remember($cacheKey, 300, function () {
            try {
                $client = new Client();
                $resp = $client->get('https://www.zohoapis.com/books/v3/settings/taxes', [
                    'headers' => ['Authorization' => 'Zoho-oauthtoken ' . self::generateToken()],
                    'query' => ['organization_id' => env('ZOHO_API_ORGANIZATION_ID')],
                ]);
                $body = json_decode($resp->getBody()->getContents(), true);
                return $body['taxes'] ?? [];
            } catch (\Exception $e) {
                \Log::error('getTaxes failed: ' . $e->getMessage());
                return [];
            }
        });

        return response()->json(['taxes' => array_values($taxes)]);
    }


    public static function createInvoice($customer, $cartData, $discount = 0, $shipping_amt = 0, $addressPost, $orderId, $markPaid = true)
    {
        $getOrder = Order::where('order_number', $orderId)->first();
        $client   = new \GuzzleHttp\Client();

        // ==== Your registered branches only ====
        $registered = [
            'DL' => ['gst_no' => '07AALCK2953Q1ZS', 'location_id' => '5630788000000144349', 'series_name' => 'Shopify'],
            'TS' => ['gst_no' => '36AALCK2953Q1ZR', 'location_id' => '5630788000000144368', 'series_name' => 'Shopify'],
            'KA' => ['gst_no' => '29AALCK2953Q1ZM', 'location_id' => '5630788000000144199', 'series_name' => 'Shopify'],
        ];

        // States that should be billed from a specific registered branch (routing)
        $routeMap = [
            'UP' => 'DL',
            'HR' => 'DL',
            // add more non-registered states here if needed -> 'XX' => 'DL'
        ];

        // --- normalize to 2-letter GST codes ---
        $norm = function ($val) {
            if (!$val) return '';
            $v = strtoupper(trim($val));
            $numToCode = ['07' => 'DL', '36' => 'TS', '29' => 'KA', '09' => 'UP', '06' => 'HR', '27' => 'MH', '24' => 'GJ', '33' => 'TN', '32' => 'KL', '08' => 'RJ', '03' => 'PB', '23' => 'MP', '19' => 'WB'];
            if (isset($numToCode[$v])) return $numToCode[$v];
            $two = ['AN', 'AP', 'AR', 'AS', 'BR', 'CH', 'CT', 'DH', 'DL', 'GA', 'GJ', 'HR', 'HP', 'JK', 'JH', 'KA', 'KL', 'LA', 'LD', 'MP', 'MH', 'MN', 'ML', 'MZ', 'NL', 'OR', 'PY', 'PB', 'RJ', 'SK', 'TN', 'TS', 'TR', 'UP', 'UT', 'WB'];
            if (in_array($v, $two, true)) return $v;
            $nameToCode = [
                'ANDAMAN AND NICOBAR ISLANDS' => 'AN',
                'ANDHRA PRADESH' => 'AP',
                'ARUNACHAL PRADESH' => 'AR',
                'ASSAM' => 'AS',
                'BIHAR' => 'BR',
                'CHANDIGARH' => 'CH',
                'CHHATTISGARH' => 'CT',
                'DADRA AND NAGAR HAVELI AND DAMAN AND DIU' => 'DH',
                'DELHI' => 'DL',
                'GOA' => 'GA',
                'GUJARAT' => 'GJ',
                'HARYANA' => 'HR',
                'HIMACHAL PRADESH' => 'HP',
                'JAMMU AND KASHMIR' => 'JK',
                'JHARKHAND' => 'JH',
                'KARNATAKA' => 'KA',
                'KERALA' => 'KL',
                'LADAKH' => 'LA',
                'LAKSHADWEEP' => 'LD',
                'MADHYA PRADESH' => 'MP',
                'MAHARASHTRA' => 'MH',
                'MANIPUR' => 'MN',
                'MEGHALAYA' => 'ML',
                'MIZORAM' => 'MZ',
                'NAGALAND' => 'NL',
                'ODISHA' => 'OR',
                'ORISSA' => 'OR',
                'PUDUCHERRY' => 'PY',
                'PONDICHERRY' => 'PY',
                'PUNJAB' => 'PB',
                'RAJASTHAN' => 'RJ',
                'SIKKIM' => 'SK',
                'TAMIL NADU' => 'TN',
                'TELANGANA' => 'TS',
                'TRIPURA' => 'TR',
                'UTTAR PRADESH' => 'UP',
                'UTTARAKHAND' => 'UT',
                'WEST BENGAL' => 'WB'
            ];
            $vName = preg_replace('/\s+/', ' ', $v);
            return $nameToCode[$vName] ?? '';
        };

        // --- BUYER state (billing → shipping fallback) ---
        // $buyer = $norm($addressPost['state_code'] ?? '') ?: $norm($addressPost['state'] ?? '');
        // if ($buyer === '') {
        //     $shippingAddress = ShippingAddress::where('order_id', $getOrder->id)->first();
        //     if ($shippingAddress) $buyer = $norm($shippingAddress->province ?? '');
        // }
        // First try shipping state (GST requires place of delivery)
        $shippingAddress = ShippingAddress::where('order_id', $getOrder->id)->first();
        $buyer = $norm($shippingAddress->province ?? '');

        // If shipping is not available, fallback to billing data passed in via $addressPost
        if ($buyer === '') {
            $buyer = $norm($addressPost['state_code'] ?? '') ?: $norm($addressPost['state'] ?? '');
        }

        // If still missing, try the stored billing address record
        if ($buyer === '') {
            try {
                $billingAddress = \App\Models\BillingAddress::where('order_id', $getOrder->id)->first();
                if ($billingAddress && !empty($billingAddress->province)) {
                    $buyer = $norm($billingAddress->province);
                    \Log::info('createInvoice - resolved buyer from DB billingAddress', ['order_number' => $orderId, 'billing_province' => $billingAddress->province, 'buyer' => $buyer]);
                }
            } catch (\Exception $e) {
                \Log::warning('createInvoice - error fetching BillingAddress for fallback', ['order_number' => $orderId, 'error' => $e->getMessage()]);
            }
        }

        // Final safeguard: if still empty, log full context and default to 'DL' to avoid hard failures
        if ($buyer === '') {
            \Log::warning('createInvoice - Could not determine buyer state; defaulting to DL', [
                'order_number' => $orderId,
                'shippingAddress' => $shippingAddress ? $shippingAddress->toArray() : null,
                'addressPost' => $addressPost,
            ]);
            $buyer = 'DL';
        }

        // --- Choose seller branch (same state if we are registered there; else route via map; else default DL) ---
        $sellerCode = array_key_exists($buyer, $registered) ? $buyer : ($routeMap[$buyer] ?? 'DL');
        $seller     = $registered[$sellerCode];

        $sellerGst  = $seller['gst_no'];
        $locationId = $seller['location_id'];
        $seriesName = $seller['series_name'];

        $isInter = ($buyer !== $sellerCode);

        // --- Fixed 0% tax ids (no auto-detect) ---
        $zero   = self::fixedZeroTaxIds();
        $igst0  = $zero['IGST0'];
        $gst0g  = $zero['GST0_GROUP'];
        $taxId  = $isInter ? $igst0 : $gst0g;   // IGST0 for interstate, GST0-group for intrastate

        \Log::info('INVOICE-TAX-DECISION', [
            'buyer' => $buyer,
            'seller' => $sellerCode,
            'isInter' => $isInter,
            'tax_used' => $taxId,
            'igst0' => $igst0,
            'gst0_group' => $gst0g
        ]);

        // --- Build line items (respect any per-item tax_id supplied by the caller) ---
        $line_items = [];
        foreach ($cartData as $cart) {
            // Check if this is a description-only order (MISC-SERVICE)
            if ($cart->item_id === 'MISC-SERVICE') {
                // Handle description orders without SKU lookup
                $resolvedTaxId = !empty($cart->tax_id) ? $cart->tax_id : $taxId; // Use default tax
                
                \Log::info('Processing description order item', [
                    'order_id' => $orderId,
                    'title' => $cart->title,
                    'price' => $cart->price,
                    'qty' => $cart->qty,
                    'tax_id' => $resolvedTaxId
                ]);
                
                $line_items[] = [
                    'name'       => $cart->title ?? 'Miscellaneous Service',
                    'rate'       => $cart->price,
                    'quantity'   => $cart->qty,
                    'tax_id'     => $resolvedTaxId,
                    'hsn_or_sac' => null, // No HSN/SAC for misc services
                    // Don't include item_id for description orders - let Zoho create as text item
                ];
            } else {
                // Regular product items - do SKU lookup
                $item = self::skuToItemId($cart->item_id);

                // If the caller provided an explicit Zoho tax_id for the item, use it
                $resolvedTaxId = null;
                if (!empty($cart->tax_id)) {
                    $resolvedTaxId = $cart->tax_id;
                }

                // If not supplied, fall back to existing resolution logic
                if (!$resolvedTaxId) {
                    // Prefer item-level tax preferences returned by Zoho for the SKU
                    if (!empty($item['tax_by_specification']) && is_array($item['tax_by_specification'])) {
                        $resolvedTaxId = self::selectTaxIdFromItemTaxPreferences($item['tax_by_specification'], $isInter);
                    }

                    // If not resolved from item, try to match org taxes by percentage
                    if (!$resolvedTaxId) {
                        $taxPercentage = null;
                        if (!empty($item['tax_by_specification'])) {
                            foreach ($item['tax_by_specification'] as $spec => $meta) {
                                if (!empty($meta['tax_percentage'])) {
                                    $taxPercentage = (float)$meta['tax_percentage'];
                                    break;
                                }
                            }
                        }
                        if ($taxPercentage !== null) {
                            $resolvedTaxId = self::findOrgTaxIdByPercentage($taxPercentage);
                        }
                    }

                    // final fallback to configured zero-tax id
                    if (!$resolvedTaxId) {
                        $resolvedTaxId = $taxId;
                    }
                }

                $line_items[] = [
                    'name'       => $item['item_name'],
                    'rate'       => $cart->price,
                    'quantity'   => $cart->qty,
                    'item_id'    => $item['item_id'],
                    'tax_id'     => $resolvedTaxId,
                    'hsn_or_sac' => $item['hsn_or_sac'],
                ];
            }
        }
        // For shipping, prefer using Zoho's `shipping_charge` field so it appears as shipping
        // in the invoice summary. Keep shipping item fallback commented for traceability.
        $shipping_charge = 0.0;
        if ($shipping_amt > 0) {
            $shipping_charge = (float)$shipping_amt;
            // Fallback: you can also add a shipping line item if desired, but using the
            // `shipping_charge` field makes the charge appear in the invoice summary.
            // $shippingSku  = env('ZOHO_SHIPPING_SKU', 'SHIPPING');
            // $shipItem     = self::skuToItemId($shippingSku);
            // $line_items[] = [ ... ];
        }

        try {
            // Calculate subtotal from line items
            $subtotal = 0;
            foreach ($line_items as $item) {
                $subtotal += ($item['rate'] * $item['quantity']);
            }

            // Ensure numeric discount
            $discount = is_null($discount) ? 0 : (float)$discount;
            
            // Validate discount: don't apply if subtotal is zero or negative, or if discount is zero/negative
            $applyDiscount = ($subtotal > 0 && $discount > 0);
            
            \Log::info('Zoho invoice discount validation', [
                'order_id' => $orderId,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'apply_discount' => $applyDiscount
            ]);

            $payload = [
                'customer_id'      => $customer->contact_id,
                'location_id'      => $locationId,
                'line_items'       => $line_items,
                'description'      => "Order ID: {$getOrder->id} | Order #$orderId - Invoice generated by API",
                // explicit shipping fields so Zoho displays shipping separately
                'shipping_charge'  => $shipping_charge,
                'shipping_charge_tax_id' => $shipping_charge > 0 ? $taxId : null,
                'order_id'         => $getOrder->id,              // Database ID (e.g., 11341)
                'reference_number' => $orderId,                   // Order Number (e.g., 20273)
                // Removed 'invoice_number' to avoid conflict with Zoho auto-generation
                'po_number'        => "ID-{$getOrder->id}",       // Database ID with prefix
                'notes'            => "Order ID: {$getOrder->id} | Order Number: $orderId", // Both IDs
                // IMPORTANT:
                'place_of_supply'  => $buyer,     // buyer’s 2-letter code
                'gst_no'           => $sellerGst, // seller branch’s GSTIN
                'series_name'      => $seriesName,
            ];

            // Only add discount fields if discount should be applied
            if ($applyDiscount) {
                $payload['discount'] = $discount;
                $payload['discount_type'] = 'entity_level';
            }

            // Only set status 'paid' when caller requests marking paid. For manual orders we keep draft/unpaid.
            if ($markPaid) {
                $payload['status'] = 'paid';
            } else {
                // omit marking as paid; create as draft
                $payload['status'] = 'draft';
            }
            
            // Log the payload to see what's being sent to Zoho
            Log::info('Zoho invoice payload with order IDs', [
                'order_database_id' => $getOrder->id,
                'order_number' => $orderId,
                'payload' => $payload
            ]);

            $resp = $client->post('https://www.zohoapis.com/books/v3/invoices', [
                'headers' => [
                    'Authorization' => 'Zoho-oauthtoken ' . self::generateToken(),
                    'Content-Type'  => 'application/json',
                ],
                'query' => ['organization_id' => env('ZOHO_API_ORGANIZATION_ID')],
                'json'  => $payload,
            ]);

            $invoiceData = json_decode($resp->getBody()->getContents(), true);

            if (!isset($invoiceData['invoice']['invoice_id'], $invoiceData['invoice']['customer_id'])) {
                throw new \Exception("Invoice creation failed or missing data.");
            }

            // Mark invoice as paid in Zoho only when requested
            if ($markPaid) {
                self::markInvoiceAsPaid(
                    $invoiceData['invoice']['invoice_id'],
                    $invoiceData['invoice']['customer_id'],
                    $invoiceData['invoice']['total']
                );
            }

            \DB::table('orders')->where('id', $orderId)->update(['zoho_status' => 1]);

            \Log::info('INVOICE-SUCCESS', [
                'invoice_id' => $invoiceData['invoice']['invoice_id'],
                'buyer' => $buyer,
                'seller' => $sellerCode,
                'isInter' => $isInter,
                'tax_id_used' => $taxId
            ]);

            return $invoiceData;
        } catch (\Exception $e) {
            \DB::table('orders')->where('id', $orderId)->update(['zoho_status' => 0]);

            // Try to extract response body from Guzzle RequestException if present
            $responseBody = null;
            if ($e instanceof \GuzzleHttp\Exception\RequestException && $e->hasResponse()) {
                try {
                    $responseBody = (string)$e->getResponse()->getBody();
                } catch (\Exception $_) {
                    $responseBody = null;
                }
            }

            \Log::error('ZOHO INVOICE TAX ERROR', [
                'buyer' => $buyer,
                'seller' => $sellerCode,
                'isInter' => $isInter,
                'tax_id_used' => $taxId,
                'exception_message' => $e->getMessage(),
                'response_body' => $responseBody,
            ]);

            $userMessage = $e->getMessage();
            if ($responseBody) {
                // include response body for debugging (it may be JSON)
                $userMessage .= ' | response: ' . $responseBody;
            }

            throw new \Exception('Error creating invoice: ' . $userMessage);
        }
    }


    public static function markInvoiceAsPaid($invoiceId, $customerId, $amount)
    {
        $client = new Client();
        $organizationId = env('ZOHO_API_ORGANIZATION_ID');

        try {
            // First, get the current invoice details to check balance due
            $invoiceResponse = $client->get("https://www.zohoapis.com/books/v3/invoices/{$invoiceId}", [
                'headers' => [
                    'Authorization' => 'Zoho-oauthtoken ' . self::generateToken(),
                    'Content-Type' => 'application/json',
                ],
                'query' => ['organization_id' => $organizationId],
            ]);

            $invoiceData = json_decode($invoiceResponse->getBody()->getContents(), true);
            $invoice = $invoiceData['invoice'] ?? null;

            if (!$invoice) {
                throw new \Exception("Could not fetch invoice details for payment");
            }

            $balanceDue = (float)($invoice['balance'] ?? $invoice['total'] ?? 0);
            $invoiceStatus = $invoice['status'] ?? '';

            \Log::info('Invoice payment check', [
                'invoice_id' => $invoiceId,
                'status' => $invoiceStatus,
                'total' => $invoice['total'] ?? 0,
                'balance_due' => $balanceDue,
                'requested_amount' => $amount
            ]);

            // If invoice is already paid or balance is zero/negative, skip payment
            if ($invoiceStatus === 'paid' || $balanceDue <= 0) {
                \Log::info('Invoice already paid or no balance due, skipping payment', [
                    'invoice_id' => $invoiceId,
                    'status' => $invoiceStatus,
                    'balance_due' => $balanceDue
                ]);
                return ['message' => 'Invoice already paid or no balance due'];
            }

            // Use the smaller of the requested amount or balance due
            $paymentAmount = min((float)$amount, $balanceDue);

            // Fetch Razorpay Account's deposit_to_account_id
            $depositAccountId = self::getDepositAccountIdByName('Razorpay Clearing');

            $response = $client->post("https://www.zohoapis.com/books/v3/customerpayments?organization_id={$organizationId}", [
                'headers' => [
                    'Authorization' => 'Zoho-oauthtoken ' . self::generateToken(),
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'customer_id' => $customerId,
                    'amount' => $paymentAmount,
                    'date' => now()->toDateString(),
                    'payment_mode' => 'Razorpay Account',
                    'account_id' => $depositAccountId, // Explicit deposit account
                    'invoices' => [
                        [
                            'invoice_id' => $invoiceId,
                            'amount_applied' => $paymentAmount,
                        ]
                    ],
                ],
            ]);

            $paymentResponse = json_decode($response->getBody()->getContents(), true);

            \Log::info("Payment applied successfully", [
                'invoice_id' => $invoiceId,
                'amount_applied' => $paymentAmount,
                'response' => $paymentResponse
            ]);

            return $paymentResponse;
        } catch (\Exception $e) {
            \Log::error("Error marking invoice as paid", [
                'invoice_id' => $invoiceId,
                'customer_id' => $customerId,
                'requested_amount' => $amount,
                'error' => $e->getMessage()
            ]);
            throw new \Exception("Error marking invoice as paid: " . $e->getMessage());
        }
    }

    public static function getDepositAccountIdByName($accountName)
    {
        $accessToken = self::generateToken(); // Generate the Zoho access token
        $organizationId = env('ZOHO_API_ORGANIZATION_ID'); // Get the organization ID
        $apiUrl = "https://www.zohoapis.com/books/v3/chartofaccounts";

        try {
            // Fetch all chart of accounts with retries and extended timeout to avoid intermittent cURL timeouts
            $response = Http::withHeaders([
                'Authorization' => 'Zoho-oauthtoken ' . $accessToken,
            ])->retry(3, 2000)->timeout(60)->get($apiUrl, [
                'organization_id' => $organizationId,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $accounts = $data['chartofaccounts'] ?? [];

                // Find the account with the specified name
                foreach ($accounts as $account) {
                    if (isset($account['account_name']) && $account['account_name'] === $accountName) {
                        return $account['account_id']; // Return the account ID
                    }
                }

                // If no matching account is found, throw an exception
                \Log::error('Zoho getDepositAccountIdByName - account not found', ['accountName' => $accountName, 'organization_id' => $organizationId, 'returned_count' => count($accounts)]);
                throw new \Exception("Account not found: $accountName");
            }

            // Non-successful response
            \Log::error('Zoho getDepositAccountIdByName - non-success response', ['status' => $response->status(), 'body' => $response->body()]);
            throw new \Exception('Error fetching chart of accounts: HTTP ' . $response->status() . ' - ' . substr($response->body(), 0, 1000));
        } catch (\Exception $e) {
            // Log detailed error including stack for diagnostics
            \Log::error('Error in getDepositAccountIdByName', [
                'accountName' => $accountName,
                'organization_id' => $organizationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw a clearer exception for callers
            throw new \Exception('Error in getDepositAccountIdByName: ' . $e->getMessage());
        }
    }
    // public static function createCreditNote($invoiceId, $refundData, $reason = 'Refund for excess payment')
    // {
    //     $client = new \GuzzleHttp\Client();
    //     $organizationId = env('ZOHO_API_ORGANIZATION_ID');
    //     $applyToInvoice = (bool)($refundData['apply_to_invoice'] ?? false);
    //     $localOrderId = $refundData['local_order_id'] ?? null;

    //     try {
    //         // Validate required fields
    //         if (empty($refundData['customer_id'])) {
    //             throw new \Exception('customer_id is required in refundData');
    //         }
    //         if (empty($refundData['line_items']) || !is_array($refundData['line_items'])) {
    //             throw new \Exception('line_items array is required in refundData');
    //         }

    //         // First, get the original invoice details
    //         $invoiceResp = $client->get("https://www.zohoapis.com/books/v3/invoices/{$invoiceId}", [
    //             'headers' => [
    //                 'Authorization' => 'Zoho-oauthtoken ' . self::generateToken(),
    //                 'Content-Type' => 'application/json',
    //             ],
    //             'query' => ['organization_id' => $organizationId],
    //         ]);

    //         $invoiceData = json_decode($invoiceResp->getBody()->getContents(), true);
    //         $invoice = $invoiceData['invoice'] ?? null;

    //         if (!$invoice) {
    //             throw new \Exception("Could not fetch invoice details for invoice ID: {$invoiceId}");
    //         }

    //         // Map location_id to state codes
    //         $locationToState = [
    //             '5630788000000144349' => 'DL', // Delhi
    //             '5630788000000144368' => 'TS', // Telangana
    //             '5630788000000144199' => 'KA', // Karnataka
    //         ];

    //         $placeOfSupply = strtoupper(trim($invoice['place_of_supply'] ?? ''));
    //         $locationId = $invoice['location_id'] ?? '';

    //         // Get seller state from location_id
    //         $sellerState = $locationToState[$locationId] ?? '';

    //         // If we still don't have seller state, try from gst_no
    //         if (empty($sellerState) && !empty($invoice['gst_no'])) {
    //             $gstNo = $invoice['gst_no'];
    //             $sellerStateCode = substr($gstNo, 0, 2);

    //             $numToCode = [
    //                 '07' => 'DL',
    //                 '36' => 'TS',
    //                 '29' => 'KA',
    //                 '09' => 'UP',
    //                 '06' => 'HR',
    //                 '27' => 'MH',
    //                 '24' => 'GJ',
    //                 '33' => 'TN',
    //                 '32' => 'KL',
    //                 '08' => 'RJ',
    //                 '03' => 'PB',
    //                 '23' => 'MP',
    //                 '19' => 'WB'
    //             ];

    //             $sellerState = $numToCode[$sellerStateCode] ?? '';
    //         }

    //         // If still empty, throw error
    //         if (empty($sellerState)) {
    //             throw new \Exception("Could not determine seller state from invoice. Location ID: {$locationId}");
    //         }

    //         // Determine if interstate
    //         $isInterstate = ($placeOfSupply !== $sellerState);

    //         \Log::info('Original Invoice Data', [
    //             'invoice_id' => $invoiceId,
    //             'place_of_supply' => $placeOfSupply,
    //             'location_id' => $locationId,
    //             'seller_state' => $sellerState,
    //             'gst_no' => $invoice['gst_no'] ?? 'N/A',
    //             'line_items_count' => count($invoice['line_items'] ?? []),
    //         ]);

    //         // Get the correct tax IDs
    //         $zero = self::fixedZeroTaxIds();
    //         $taxId = $isInterstate ? $zero['IGST0'] : $zero['GST0_GROUP'];

    //         \Log::info('Credit Note Tax Decision', [
    //             'buyer_state' => $placeOfSupply,
    //             'seller_state' => $sellerState,
    //             'is_interstate' => $isInterstate,
    //             'tax_id_to_use' => $taxId,
    //         ]);

    //         // Build Credit Note line items with correct tax
    //         $lineItems = [];
    //         foreach ($refundData['line_items'] as $item) {
    //             if (!empty($item['item_id']) && !empty($item['quantity'])) {
    //                 $lineItem = [
    //                     'item_id' => $item['item_id'],
    //                     'quantity' => $item['quantity'],
    //                     'rate' => $item['rate'],
    //                     'discount' => $item['discount'] ?? 0,
    //                     'tax_id' => $taxId,
    //                 ];

    //                 // Get HSN from original invoice line items if available
    //                 if (!empty($invoice['line_items'])) {
    //                     foreach ($invoice['line_items'] as $invItem) {
    //                         if ($invItem['item_id'] === $item['item_id']) {
    //                             if (!empty($invItem['hsn_or_sac'])) {
    //                                 $lineItem['hsn_or_sac'] = $invItem['hsn_or_sac'];
    //                             }
    //                             break;
    //                         }
    //                     }
    //                 }

    //                 $lineItems[] = $lineItem;
    //             }
    //         }

    //         if (!$lineItems) {
    //             throw new \Exception("No valid line items for credit note creation.");
    //         }

    //         \Log::info('Credit Note Payload', [
    //             'customer_id' => $refundData['customer_id'],
    //             'invoice_id' => $invoiceId,
    //             'date' => now()->toDateString(),
    //             'line_items' => $lineItems,
    //             'notes' => $reason,
    //             'reference_number' => $refundData['order_id'] ?? null,
    //             'is_interstate' => $isInterstate,
    //             'tax_id_used' => $taxId,
    //             'place_of_supply' => $placeOfSupply,
    //         ]);

    //         // Create Credit Note
    //         $cnResp = $client->post("https://www.zohoapis.com/books/v3/creditnotes", [
    //             'headers' => [
    //                 'Authorization' => 'Zoho-oauthtoken ' . self::generateToken(),
    //                 'Content-Type' => 'application/json',
    //             ],
    //             'query' => ['organization_id' => $organizationId],
    //             'json' => [
    //                 'customer_id' => $refundData['customer_id'],
    //                 'invoice_id' => $invoiceId,
    //                 'date' => now()->toDateString(),
    //                 'line_items' => $lineItems,
    //                 'notes' => $reason,
    //                 'reference_number' => $refundData['order_id'] ?? null,
    //                 'place_of_supply' => $placeOfSupply,
    //             ],
    //         ]);

    //         $cnData = json_decode($cnResp->getBody()->getContents(), true);
    //         $creditNote = $cnData['creditnote'] ?? null;
    //         $creditNoteId = $creditNote['creditnote_id'] ?? null;

    //         if (!$creditNoteId) {
    //             \Log::error('Credit Note Creation Failed', ['response' => $cnData]);
    //             throw new \Exception('Credit note creation failed: ' . json_encode($cnData));
    //         }

    //         \Log::info('Credit Note Created Successfully', [
    //             'creditnote_id' => $creditNoteId,
    //             'invoice_id' => $invoiceId,
    //             'creditnote_number' => $creditNote['creditnote_number'] ?? 'N/A',
    //         ]);

    //         // Apply credit note to invoice if requested
    //         if ($applyToInvoice && !empty($refundData['total'])) {
    //             try {
    //                 $applyResp = $client->post("https://www.zohoapis.com/books/v3/creditnotes/{$creditNoteId}/invoices", [
    //                     'headers' => [
    //                         'Authorization' => 'Zoho-oauthtoken ' . self::generateToken(),
    //                         'Content-Type' => 'application/json',
    //                     ],
    //                     'query' => ['organization_id' => $organizationId],
    //                     'json' => [
    //                         'invoices' => [[
    //                             'invoice_id' => $invoiceId,
    //                             'amount_applied' => round((float)$refundData['total'], 2),
    //                         ]],
    //                     ],
    //                 ]);

    //                 $applyData = json_decode($applyResp->getBody()->getContents(), true);
    //                 \Log::info('Credit Note Applied to Invoice', [
    //                     'creditnote_id' => $creditNoteId,
    //                     'invoice_id' => $invoiceId,
    //                     'response' => $applyData,
    //                 ]);
    //             } catch (\Exception $e) {
    //                 \Log::warning('Failed to apply credit note to invoice (non-critical)', [
    //                     'creditnote_id' => $creditNoteId,
    //                     'invoice_id' => $invoiceId,
    //                     'error' => $e->getMessage(),
    //                 ]);
    //             }
    //         }

    //         // Create refund directly from credit note (NEW APPROACH)
    //         $refundCreated = false;
    //         $refundId = null;

    //         if (!empty($refundData['total'])) {
    //             try {
    //                 $refundAmount = round((float)$refundData['total'], 2);

    //                 // Get Razorpay Clearing account ID
    //                 $refundAccountId = self::getDepositAccountIdByName('Razorpay Clearing');

    //                 if (empty($refundAccountId)) {
    //                     throw new \Exception('Razorpay Clearing account not found');
    //                 }

    //                 \Log::info('Creating Refund from Credit Note', [
    //                     'creditnote_id' => $creditNoteId,
    //                     'amount' => $refundAmount,
    //                     'account_id' => $refundAccountId,
    //                 ]);

    //                 // Create refund directly from credit note
    //                 $refundResp = $client->post("https://www.zohoapis.com/books/v3/creditnotes/{$creditNoteId}/refunds", [
    //                     'headers' => [
    //                         'Authorization' => 'Zoho-oauthtoken ' . self::generateToken(),
    //                         'Content-Type' => 'application/json',
    //                     ],
    //                     'query' => ['organization_id' => $organizationId],
    //                     'json' => [
    //                         'date' => now()->toDateString(),
    //                         'refund_mode' => 'cash',
    //                         'reference_number' => $refundData['order_id'] ?? null,
    //                         'amount' => $refundAmount,
    //                         'from_account_id' => $refundAccountId,
    //                     ],
    //                 ]);

    //                 $refundData = json_decode($refundResp->getBody()->getContents(), true);
    //                 $refundId = $refundData['creditnote_refund']['creditnote_refund_id'] ?? null;
    //                 $refundCreated = true;

    //                 \Log::info('Refund Created Successfully', [
    //                     'creditnote_id' => $creditNoteId,
    //                     'refund_id' => $refundId,
    //                     'amount' => $refundAmount,
    //                 ]);
    //             } catch (\GuzzleHttp\Exception\ClientException $e) {
    //                 $response = $e->getResponse();
    //                 $body = json_decode($response->getBody()->getContents(), true);

    //                 \Log::error('Refund Creation Failed', [
    //                     'creditnote_id' => $creditNoteId,
    //                     'status' => $response->getStatusCode(),
    //                     'error_code' => $body['code'] ?? 'N/A',
    //                     'error_message' => $body['message'] ?? 'N/A',
    //                 ]);
    //             } catch (\Exception $e) {
    //                 \Log::warning('Refund Creation Failed (non-critical)', [
    //                     'creditnote_id' => $creditNoteId,
    //                     'error' => $e->getMessage(),
    //                 ]);
    //             }
    //         }

    //         // Update local order status if local_order_id is provided
    //         if ($localOrderId) {
    //             try {
    //                 \DB::table('orders')->where('id', $localOrderId)->update([
    //                     'zoho_status' => 2,
    //                 ]);
    //             } catch (\Exception $e) {
    //                 \Log::warning('Failed to update local order status', [
    //                     'local_order_id' => $localOrderId,
    //                     'error' => $e->getMessage(),
    //                 ]);
    //             }
    //         }

    //         \Log::info('CREDIT-NOTE SUCCESS', [
    //             'invoice_id' => $invoiceId,
    //             'creditnote_id' => $creditNoteId,
    //             'creditnote_number' => $creditNote['creditnote_number'] ?? 'N/A',
    //             'refund_created' => $refundCreated,
    //             'refund_id' => $refundId,
    //             'is_interstate' => $isInterstate,
    //             'local_order_updated' => !is_null($localOrderId),
    //         ]);

    //         return [
    //             'success' => true,
    //             'credit_note' => $creditNote,
    //             'creditnote_id' => $creditNoteId,
    //             'refund_id' => $refundId,
    //             'refund_created' => $refundCreated,
    //             'is_interstate' => $isInterstate,
    //             'message' => $refundCreated
    //                 ? 'Credit note and refund created successfully'
    //                 : 'Credit note created. Please process refund manually in Zoho Books.',
    //         ];
    //     } catch (\Exception $e) {
    //         \Log::error('CREDIT-NOTE-ERROR', [
    //             'invoice_id' => $invoiceId,
    //             'msg' => $e->getMessage(),
    //             'file' => $e->getFile(),
    //             'line' => $e->getLine(),
    //         ]);

    //         // Update local order with error status if possible
    //         if ($localOrderId) {
    //             try {
    //                 \DB::table('orders')->where('id', $localOrderId)->update([
    //                     'zoho_status' => 0,
    //                     'zoho_note' => 'Credit Note Error: ' . substr($e->getMessage(), 0, 250),
    //                 ]);
    //             } catch (\Exception $dbError) {
    //                 \Log::error('Failed to update order status on error', [
    //                     'local_order_id' => $localOrderId,
    //                     'error' => $dbError->getMessage(),
    //                 ]);
    //             }
    //         }

    //         throw new \Exception('Error creating credit note: ' . $e->getMessage());
    //     }
    // }
    public static function createCreditNote($invoiceId, $refundData, $reason = 'Refund for excess payment')
    {
        $client         = new \GuzzleHttp\Client();
        $organizationId = env('ZOHO_API_ORGANIZATION_ID');

        // we mostly care about: customer_id, line_items[], total, local_order_id
        $localOrderId = $refundData['local_order_id'] ?? null;
        // if caller sends this, we’ll refund; otherwise we can skip
        $createRefund = (bool)($refundData['create_refund'] ?? true);

        try {
            /**
             * 1) GET ORIGINAL INVOICE
             */
            $invoiceResp = $client->get("https://www.zohoapis.com/books/v3/invoices/{$invoiceId}", [
                'headers' => [
                    'Authorization' => 'Zoho-oauthtoken ' . self::generateToken(),
                    'Content-Type'  => 'application/json',
                ],
                'query' => ['organization_id' => $organizationId],
            ]);

            $invoiceData = json_decode($invoiceResp->getBody()->getContents(), true);
            $invoice     = $invoiceData['invoice'] ?? null;

            if (!$invoice) {
                throw new \Exception("Could not fetch invoice details for invoice ID: {$invoiceId}");
            }

            /**
             * 2) DETERMINE PLACE OF SUPPLY & TAX (0% IGST vs 0% GST group)
             */
            $locationToState = [
                '5630788000000144349' => 'DL', // Delhi
                '5630788000000144368' => 'TS', // Telangana
                '5630788000000144199' => 'KA', // Karnataka
            ];

            $placeOfSupply = strtoupper(trim($invoice['place_of_supply'] ?? ''));
            $locationId    = $invoice['location_id'] ?? '';

            // seller state from location_id
            $sellerState = $locationToState[$locationId] ?? '';

            // fallback: derive from GSTIN if location didn’t match
            if (empty($sellerState) && !empty($invoice['gst_no'])) {
                $gstNo       = $invoice['gst_no'];
                $stateDigits = substr($gstNo, 0, 2);
                $numToCode   = [
                    '07' => 'DL',
                    '36' => 'TS',
                    '29' => 'KA',
                    '09' => 'UP',
                    '06' => 'HR',
                    '27' => 'MH',
                    '24' => 'GJ',
                    '33' => 'TN',
                    '32' => 'KL',
                    '08' => 'RJ',
                    '03' => 'PB',
                    '23' => 'MP',
                    '19' => 'WB'
                ];
                $sellerState = $numToCode[$stateDigits] ?? '';
            }

            if (empty($sellerState)) {
                throw new \Exception("Could not determine seller state from invoice. Location ID: {$locationId}");
            }

            $isInterstate = ($placeOfSupply !== $sellerState);

            // always use your fixed 0% tax ids
            $zero  = self::fixedZeroTaxIds();
            $taxId = $isInterstate ? $zero['IGST0'] : $zero['GST0_GROUP'];

            \Log::info('CREDIT-NOTE TAX DECISION (paid-invoice flow)', [
                'invoice_id'      => $invoiceId,
                'place_of_supply' => $placeOfSupply,
                'seller_state'    => $sellerState,
                'is_interstate'   => $isInterstate,
                'tax_id_used'     => $taxId,
            ]);

            /**
             * 3) BUILD LINE ITEMS (from refundData)
             */
            if (empty($refundData['customer_id'])) {
                throw new \Exception('customer_id is required in refundData');
            }
            if (empty($refundData['line_items']) || !is_array($refundData['line_items'])) {
                throw new \Exception('line_items array is required in refundData');
            }

            $lineItems = [];
            foreach ($refundData['line_items'] as $item) {
                if (empty($item['item_id']) || empty($item['quantity'])) {
                    continue;
                }

                $lineItem = [
                    'item_id'  => $item['item_id'],
                    'quantity' => $item['quantity'],
                    'rate'     => $item['rate'],
                    'discount' => $item['discount'] ?? 0,
                    'tax_id'   => $taxId,
                ];

                // copy HSN/SAC from original invoice item if present
                foreach ($invoice['line_items'] ?? [] as $invItem) {
                    if ($invItem['item_id'] === $item['item_id'] && !empty($invItem['hsn_or_sac'])) {
                        $lineItem['hsn_or_sac'] = $invItem['hsn_or_sac'];
                        break;
                    }
                }

                $lineItems[] = $lineItem;
            }

            if (!$lineItems) {
                throw new \Exception("No valid line items for credit note creation.");
            }

            \Log::info('CREDIT-NOTE PAYLOAD (before create) (paid-invoice flow)', [
                'customer_id'     => $refundData['customer_id'],
                'invoice_id'      => $invoiceId,
                'place_of_supply' => $placeOfSupply,
                'line_items'      => $lineItems,
            ]);

            /**
             * 4) CREATE CREDIT NOTE — always linked to invoice
             *    because our use-case is “invoice was already paid, now refund”.
             */
            $cnResp = $client->post("https://www.zohoapis.com/books/v3/creditnotes", [
                'headers' => [
                    'Authorization' => 'Zoho-oauthtoken ' . self::generateToken(),
                    'Content-Type'  => 'application/json',
                ],
                'query' => ['organization_id' => $organizationId],
                'json'  => [
                    'customer_id'      => $refundData['customer_id'],
                    'invoice_id'       => $invoiceId,                        // 👈 important in your org
                    'date'             => now()->toDateString(),
                    'line_items'       => $lineItems,
                    'notes'            => $reason,
                    'reference_number' => $refundData['order_id'] ?? null,
                    'place_of_supply'  => $placeOfSupply,
                ],
            ]);

            $cnData       = json_decode($cnResp->getBody()->getContents(), true);
            $creditNote   = $cnData['creditnote'] ?? null;
            $creditNoteId = $creditNote['creditnote_id'] ?? null;

            if (!$creditNoteId) {
                \Log::error('Credit note creation failed (paid-invoice flow)', ['response' => $cnData]);
                throw new \Exception('Credit note creation failed: ' . json_encode($cnData));
            }

            \Log::info('CREDIT-NOTE CREATED (paid-invoice flow)', [
                'creditnote_id' => $creditNoteId,
                'invoice_id'    => $invoiceId,
            ]);

            /**
             * 5) REFUND THE CREDIT NOTE (this is what actually “returns” the money in Zoho)
             */
            $refundCreated = false;
            $refundId      = null;

            // amount to refund
            $refundAmount = !empty($refundData['total'])
                ? round((float)$refundData['total'], 2)
                : 0.00;

            if ($createRefund && $refundAmount > 0) {
                try {
                    // get the “Razorpay Clearing” account you used
                    $refundAccountId = self::getDepositAccountIdByName('Razorpay Clearing');
                    if (empty($refundAccountId)) {
                        throw new \Exception('Razorpay Clearing account not found');
                    }

                    $refundResp = $client->post("https://www.zohoapis.com/books/v3/creditnotes/{$creditNoteId}/refunds", [
                        'headers' => [
                            'Authorization' => 'Zoho-oauthtoken ' . self::generateToken(),
                            'Content-Type'  => 'application/json',
                        ],
                        'query' => ['organization_id' => $organizationId],
                        'json'  => [
                            'date'             => now()->toDateString(),
                            'refund_mode'      => 'cash', // or the name you mapped
                            'reference_number' => $refundData['order_id'] ?? null,
                            'amount'           => $refundAmount,
                            'from_account_id'  => $refundAccountId,
                        ],
                    ]);

                    $refundResData = json_decode($refundResp->getBody()->getContents(), true);
                    $refundId      = $refundResData['creditnote_refund']['creditnote_refund_id'] ?? null;
                    $refundCreated = true;

                    \Log::info('CREDIT-NOTE REFUND CREATED (paid-invoice flow)', [
                        'creditnote_id' => $creditNoteId,
                        'refund_id'     => $refundId,
                        'amount'        => $refundAmount,
                    ]);
                } catch (\Exception $e) {
                    \Log::warning('Refund Creation Failed (non-critical) (paid-invoice flow)', [
                        'creditnote_id' => $creditNoteId,
                        'error'         => $e->getMessage(),
                    ]);
                }
            }

            /**
             * 6) UPDATE LOCAL ORDER IF NEEDED
             */
            if ($localOrderId) {
                try {
                    \DB::table('orders')->where('id', $localOrderId)->update([
                        'zoho_status' => $refundCreated ? 2 : 1,
                    ]);
                } catch (\Exception $e) {
                    \Log::warning('Failed to update local order status (paid-invoice flow)', [
                        'local_order_id' => $localOrderId,
                        'error'          => $e->getMessage(),
                    ]);
                }
            }

            return [
                'success'        => true,
                'credit_note'    => $creditNote,
                'creditnote_id'  => $creditNoteId,
                'refund_id'      => $refundId,
                'refund_created' => $refundCreated,
                'is_interstate'  => $isInterstate,
                'message'        => $refundCreated
                    ? 'Credit note created and refunded (paid invoice).'
                    : 'Credit note created (paid invoice).',
            ];
        } catch (\Exception $e) {
            \Log::error('CREDIT-NOTE-ERROR (paid-invoice flow)', [
                'invoice_id' => $invoiceId,
                'msg'        => $e->getMessage(),
                'file'       => $e->getFile(),
                'line'       => $e->getLine(),
            ]);

            // on error, mark local order
            if (!empty($refundData['local_order_id'])) {
                try {
                    \DB::table('orders')->where('id', $refundData['local_order_id'])->update([
                        'zoho_status' => 0,
                        'zoho_note'   => 'Credit Note Error: ' . substr($e->getMessage(), 0, 250),
                    ]);
                } catch (\Exception $dbError) {
                    \Log::error('Failed to update order status on error (paid-invoice flow)', [
                        'local_order_id' => $refundData['local_order_id'],
                        'error'          => $dbError->getMessage(),
                    ]);
                }
            }

            throw new \Exception('Error creating credit note: ' . $e->getMessage());
        }
    }


    /**
     * Reverse a customer payment - now with better error handling for account type issues
     */
    private static function reverseCustomerPayment(string $paymentId, float $amount, string $reason, ?string $referenceNumber = null)
    {
        $client = new \GuzzleHttp\Client();
        $organizationId = env('ZOHO_API_ORGANIZATION_ID');

        try {
            // Get the original payment details first
            $paymentResp = $client->get("https://www.zohoapis.com/books/v3/customerpayments/{$paymentId}", [
                'headers' => [
                    'Authorization' => 'Zoho-oauthtoken ' . self::generateToken(),
                    'Content-Type' => 'application/json',
                ],
                'query' => ['organization_id' => $organizationId],
            ]);

            $paymentData = json_decode($paymentResp->getBody()->getContents(), true);
            $payment = $paymentData['customerpayment'] ?? null;

            if (!$payment) {
                throw new \Exception('Could not fetch original payment details');
            }

            // Use the same account that was used for the original payment
            $originalAccountId = $payment['account_id'] ?? null;

            if (!$originalAccountId) {
                throw new \Exception('Original payment account_id not found');
            }

            $payload = [
                'amount' => round($amount, 2),
                'date' => now()->toDateString(),
                'refund_mode' => 'cash',
                'description' => $reason,
                'account_id' => $originalAccountId, // Use the original payment's account
            ];

            if (!empty($referenceNumber)) {
                $payload['reference_number'] = $referenceNumber;
            }

            \Log::info('Payment Reversal Request', [
                'payment_id' => $paymentId,
                'original_account_id' => $originalAccountId,
                'payload' => $payload,
            ]);

            $resp = $client->post("https://www.zohoapis.com/books/v3/customerpayments/{$paymentId}/refunds", [
                'headers' => [
                    'Authorization' => 'Zoho-oauthtoken ' . self::generateToken(),
                    'Content-Type' => 'application/json',
                ],
                'query' => ['organization_id' => $organizationId],
                'json' => $payload,
            ]);

            $responseData = json_decode($resp->getBody()->getContents(), true);

            \Log::info('Payment Reversal Response', ['response' => $responseData]);

            // Check for errors in response
            if (isset($responseData['code']) && $responseData['code'] !== 0) {
                $errorMsg = $responseData['message'] ?? 'Unknown error';
                throw new \Exception("Zoho refund error (code {$responseData['code']}): {$errorMsg}");
            }

            return $responseData;
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
            $body = json_decode($response->getBody()->getContents(), true);

            \Log::error('Payment Reversal Client Exception', [
                'payment_id' => $paymentId,
                'status' => $response->getStatusCode(),
                'error_code' => $body['code'] ?? 'N/A',
                'error_message' => $body['message'] ?? 'N/A',
            ]);

            throw new \Exception("Payment reversal failed: " . ($body['message'] ?? $e->getMessage()));
        } catch (\Exception $e) {
            \Log::error('Payment Reversal Exception', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
    /**
     * Find the Zoho customer payment that was applied to the given invoice.
     * We first try the invoice detail (which includes 'payments'), then fall back to a search.
     */
    private static function findPaymentIdForInvoice(string $invoiceId): ?string
    {
        $client         = new \GuzzleHttp\Client();
        $organizationId = env('ZOHO_API_ORGANIZATION_ID');

        // Try reading the invoice with its payments
        try {
            $resp = $client->get("https://www.zohoapis.com/books/v3/invoices/{$invoiceId}", [
                'headers' => [
                    'Authorization' => 'Zoho-oauthtoken ' . self::generateToken(),
                    'Content-Type'  => 'application/json',
                ],
                'query' => ['organization_id' => $organizationId],
            ]);
            $data = json_decode($resp->getBody()->getContents(), true);
            $payments = $data['invoice']['payments'] ?? [];
            if (!empty($payments)) {
                // choose the most recent/full payment
                usort($payments, function ($a, $b) {
                    return strtotime($b['date'] ?? '') <=> strtotime($a['date'] ?? '');
                });
                return $payments[0]['payment_id'] ?? null;
            }
        } catch (\Exception $e) {
            \Log::warning('Could not read invoice payments; will try fallback search', ['invoice_id' => $invoiceId, 'msg' => $e->getMessage()]);
        }

        // Fallback: query customer payments filtered by invoice_id (if supported)
        try {
            $resp = $client->get("https://www.zohoapis.com/books/v3/customerpayments", [
                'headers' => [
                    'Authorization' => 'Zoho-oauthtoken ' . self::generateToken(),
                    'Content-Type'  => 'application/json',
                ],
                'query' => [
                    'organization_id' => $organizationId,
                    'invoice_id'      => $invoiceId,
                    'per_page'        => 200,
                ],
            ]);
            $data = json_decode($resp->getBody()->getContents(), true);
            $pays = $data['customerpayments'] ?? [];
            if (!empty($pays)) {
                usort($pays, function ($a, $b) {
                    return strtotime($b['date'] ?? '') <=> strtotime($a['date'] ?? '');
                });
                return $pays[0]['payment_id'] ?? null;
            }
        } catch (\Exception $e) {
            \Log::warning('Customerpayments fallback search failed', ['invoice_id' => $invoiceId, 'msg' => $e->getMessage()]);
        }

        return null;
    }

    public static function findItemBySKU($sku)
    {
        $client = new Client();
        $accessToken = self::generateToken();
        $organizationId = env('ZOHO_API_ORGANIZATION_ID');

        try {
            // Fetch item using the SKU
            $response = $client->get("https://www.zohoapis.com/books/v3/items", [
                'headers' => [
                    'Authorization' => 'Zoho-oauthtoken ' . $accessToken,
                    'Content-Type' => 'application/json',
                ],
                'query' => [
                    'organization_id' => $organizationId,
                    'criteria' => "(SKU:equals:$sku)", // Primary method using criteria
                    'per_page' => 1,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (!empty($data['items'])) {
                // \Log::info("Item found for SKU {$sku}: ", $data['items'][0]);
                return $data['items'][0]; // Return the first matching item
            } else {
                \Log::warning("No item found for SKU {$sku} using criteria.");
                return null;
            }
        } catch (\Exception $e) {
            \Log::error("Error finding item for SKU {$sku}: " . $e->getMessage());
            throw new \Exception("Error finding item for SKU {$sku}: " . $e->getMessage());
        }
    }
    public function searchItemBySKU(Request $request)
    {
        $sku = $request->input('sku');

        try {
            $item = ZohoController::findItemBySKU($sku);

            if ($item) {
                return response()->json([
                    'success' => true,
                    'message' => 'Item found.',
                    'data' => $item,
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Item not found for the given SKU.',
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error occurred while searching for the item: ' . $e->getMessage(),
            ]);
        }
    }

    public static function getInvoiceByOrderNumber($orderNumber)
    {
        $client = new Client();
        $organizationId = env('ZOHO_API_ORGANIZATION_ID');

        // dd($orderNumber);

        try {
            // Fetch invoices from Zoho by order number
            $response = $client->get("https://www.zohoapis.com/books/v3/invoices", [
                'headers' => [
                    'Authorization' => 'Zoho-oauthtoken ' . self::generateToken(),
                    'Content-Type' => 'application/json',
                ],
                'query' => [
                    'organization_id' => $organizationId,
                    'reference_number' => $orderNumber, // Filter by reference number (order number)
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (isset($data['invoices']) && count($data['invoices']) > 0) {
                // \Log::info("Invoice found for order number {$orderNumber}: ", $data['invoices'][0]);
                return $data['invoices'][0]; // Return the first matching invoice
            } else {
                \Log::warning("No invoice found in Zoho for order number {$orderNumber}");
                return null;
            }
        } catch (\Exception $e) {
            \Log::error("Error fetching invoice for order number {$orderNumber}: " . $e->getMessage());
            throw new \Exception("Error fetching invoice for order number {$orderNumber}: " . $e->getMessage());
        }
    }

    public static function updateInvoice($invoiceId, $customer, $cartData, $discount = 0, $shipping_amt = 0, $addressPost, $orderId, $markPaid = true)
    {
        $getOrder = Order::where('order_number', $orderId)->first();
        $client   = new \GuzzleHttp\Client();

        // ==== Your registered branches only ====
        $registered = [
            'DL' => ['gst_no' => '07AALCK2953Q1ZS', 'location_id' => '5630788000000144349', 'series_name' => 'Shopify'],
            'TS' => ['gst_no' => '36AALCK2953Q1ZR', 'location_id' => '5630788000000144368', 'series_name' => 'Shopify'],
            'KA' => ['gst_no' => '29AALCK2953Q1ZM', 'location_id' => '5630788000000144199', 'series_name' => 'Shopify'],
        ];

        // States that should be billed from a specific registered branch (routing)
        $routeMap = [
            'UP' => 'DL',
            'HR' => 'DL',
            // add more non-registered states here if needed -> 'XX' => 'DL'
        ];

        // --- normalize to 2-letter GST codes ---
        $norm = function ($val) {
            if (!$val) return '';
            $v = strtoupper(trim($val));
            $numToCode = ['07' => 'DL', '36' => 'TS', '29' => 'KA', '09' => 'UP', '06' => 'HR', '27' => 'MH', '24' => 'GJ', '33' => 'TN', '32' => 'KL', '08' => 'RJ', '03' => 'PB', '23' => 'MP', '19' => 'WB'];
            if (isset($numToCode[$v])) return $numToCode[$v];
            $two = ['AN', 'AP', 'AR', 'AS', 'BR', 'CH', 'CT', 'DH', 'DL', 'GA', 'GJ', 'HR', 'HP', 'JK', 'JH', 'KA', 'KL', 'LA', 'LD', 'MP', 'MH', 'MN', 'ML', 'MZ', 'NL', 'OR', 'PY', 'PB', 'RJ', 'SK', 'TN', 'TS', 'TR', 'UP', 'UT', 'WB'];
            if (in_array($v, $two, true)) return $v;
            $nameToCode = [
                'ANDAMAN AND NICOBAR ISLANDS' => 'AN',
                'ANDHRA PRADESH' => 'AP',
                'ARUNACHAL PRADESH' => 'AR',
                'ASSAM' => 'AS',
                'BIHAR' => 'BR',
                'CHANDIGARH' => 'CH',
                'CHHATTISGARH' => 'CT',
                'DADRA AND NAGAR HAVELI AND DAMAN AND DIU' => 'DH',
                'DELHI' => 'DL',
                'GOA' => 'GA',
                'GUJARAT' => 'GJ',
                'HARYANA' => 'HR',
                'HIMACHAL PRADESH' => 'HP',
                'JAMMU AND KASHMIR' => 'JK',
                'JHARKHAND' => 'JH',
                'KARNATAKA' => 'KA',
                'KERALA' => 'KL',
                'LADAKH' => 'LA',
                'LAKSHADWEEP' => 'LD',
                'MADHYA PRADESH' => 'MP',
                'MAHARASHTRA' => 'MH',
                'MANIPUR' => 'MN',
                'MEGHALAYA' => 'ML',
                'MIZORAM' => 'MZ',
                'NAGALAND' => 'NL',
                'ODISHA' => 'OR',
                'ORISSA' => 'OR',
                'PUDUCHERRY' => 'PY',
                'PONDICHERRY' => 'PY',
                'PUNJAB' => 'PB',
                'RAJASTHAN' => 'RJ',
                'SIKKIM' => 'SK',
                'TAMIL NADU' => 'TN',
                'TELANGANA' => 'TS',
                'TRIPURA' => 'TR',
                'UTTAR PRADESH' => 'UP',
                'UTTARAKHAND' => 'UT',
                'WEST BENGAL' => 'WB'
            ];
            $vName = preg_replace('/\s+/', ' ', $v);
            return $nameToCode[$vName] ?? '';
        };

        // --- BUYER state (billing → shipping fallback) ---
        $shippingAddress = ShippingAddress::where('order_id', $getOrder->id)->first();
        $buyer = $norm($shippingAddress->province ?? '');

        // If shipping is not available, fallback to billing data passed in via $addressPost
        if ($buyer === '') {
            $buyer = $norm($addressPost['state_code'] ?? '') ?: $norm($addressPost['state'] ?? '');
        }

        // If still missing, try the stored billing address record
        if ($buyer === '') {
            try {
                $billingAddress = \App\Models\BillingAddress::where('order_id', $getOrder->id)->first();
                if ($billingAddress && !empty($billingAddress->province)) {
                    $buyer = $norm($billingAddress->province);
                }
            } catch (\Exception $e) {
                \Log::warning('updateInvoice - error fetching BillingAddress for fallback', ['order_number' => $orderId, 'error' => $e->getMessage()]);
            }
        }

        // Final safeguard: if still empty, default to 'DL'
        if ($buyer === '') {
            \Log::warning('updateInvoice - Could not determine buyer state; defaulting to DL', ['order_number' => $orderId]);
            $buyer = 'DL';
        }

        // --- Choose seller branch ---
        $sellerCode = array_key_exists($buyer, $registered) ? $buyer : ($routeMap[$buyer] ?? 'DL');
        $seller     = $registered[$sellerCode];

        $sellerGst  = $seller['gst_no'];
        $locationId = $seller['location_id'];
        $seriesName = $seller['series_name'];

        $isInter = ($buyer !== $sellerCode);

        // --- Fixed 0% tax ids ---
        $zero   = self::fixedZeroTaxIds();
        $igst0  = $zero['IGST0'];
        $gst0g  = $zero['GST0_GROUP'];
        $taxId  = $isInter ? $igst0 : $gst0g;

        // --- Build line items ---
        $line_items = [];
        foreach ($cartData as $cart) {
            // Check if this is a description-only order (MISC-SERVICE)
            if ($cart->item_id === 'MISC-SERVICE') {
                // Handle description orders without SKU lookup
                $resolvedTaxId = !empty($cart->tax_id) ? $cart->tax_id : $taxId; // Use default tax
                
                \Log::info('Processing description order item for update', [
                    'order_id' => $orderId,
                    'title' => $cart->title,
                    'price' => $cart->price,
                    'qty' => $cart->qty,
                    'tax_id' => $resolvedTaxId
                ]);
                
                $line_items[] = [
                    'name'       => $cart->title ?? 'Miscellaneous Service',
                    'rate'       => $cart->price,
                    'quantity'   => $cart->qty,
                    'tax_id'     => $resolvedTaxId,
                    'hsn_or_sac' => null, // No HSN/SAC for misc services
                    // Don't include item_id for description orders - let Zoho create as text item
                ];
            } else {
                // Regular product items - do SKU lookup
                $item = self::skuToItemId($cart->item_id);

                // If the caller provided an explicit Zoho tax_id for the item, use it
                $resolvedTaxId = null;
                if (!empty($cart->tax_id)) {
                    $resolvedTaxId = $cart->tax_id;
                }

                // If not supplied, fall back to existing resolution logic
                if (!$resolvedTaxId) {
                    // Prefer item-level tax preferences returned by Zoho for the SKU
                    if (!empty($item['tax_by_specification']) && is_array($item['tax_by_specification'])) {
                        $resolvedTaxId = self::selectTaxIdFromItemTaxPreferences($item['tax_by_specification'], $isInter);
                    }

                    // If not resolved from item, try to match org taxes by percentage
                    if (!$resolvedTaxId) {
                        $taxPercentage = null;
                        if (!empty($item['tax_by_specification'])) {
                            foreach ($item['tax_by_specification'] as $spec => $meta) {
                                if (!empty($meta['tax_percentage'])) {
                                    $taxPercentage = (float)$meta['tax_percentage'];
                                    break;
                                }
                            }
                        }
                        if ($taxPercentage !== null) {
                            $resolvedTaxId = self::findOrgTaxIdByPercentage($taxPercentage);
                        }
                    }

                    // final fallback to configured zero-tax id
                    if (!$resolvedTaxId) {
                        $resolvedTaxId = $taxId;
                    }
                }

                $line_items[] = [
                    'name'       => $item['item_name'],
                    'rate'       => $cart->price,
                    'quantity'   => $cart->qty,
                    'item_id'    => $item['item_id'],
                    'tax_id'     => $resolvedTaxId,
                    'hsn_or_sac' => $item['hsn_or_sac'],
                ];
            }
        }

        // For shipping
        $shipping_charge = 0.0;
        if ($shipping_amt > 0) {
            $shipping_charge = (float)$shipping_amt;
        }

        try {
            // First, get the current invoice details to check for existing payments
            $currentInvoiceResponse = $client->get("https://www.zohoapis.com/books/v3/invoices/{$invoiceId}", [
                'headers' => [
                    'Authorization' => 'Zoho-oauthtoken ' . self::generateToken(),
                    'Content-Type' => 'application/json',
                ],
                'query' => ['organization_id' => env('ZOHO_API_ORGANIZATION_ID')],
            ]);

            $currentInvoiceData = json_decode($currentInvoiceResponse->getBody()->getContents(), true);
            $currentInvoice = $currentInvoiceData['invoice'] ?? null;

            if (!$currentInvoice) {
                throw new \Exception("Could not fetch current invoice details for update");
            }

            $currentTotal = (float)($currentInvoice['total'] ?? 0);
            $currentBalance = (float)($currentInvoice['balance'] ?? 0);
            $existingPayments = $currentTotal - $currentBalance;

            // Calculate subtotal from line items
            $subtotal = 0;
            foreach ($line_items as $item) {
                $subtotal += ($item['rate'] * $item['quantity']);
            }

            // Ensure numeric discount
            $discount = is_null($discount) ? 0 : (float)$discount;
            
            // Validate discount: don't apply if subtotal is zero or negative, or if discount is zero/negative
            $applyDiscount = ($subtotal > 0 && $discount > 0);

            // Calculate new total
            $newTotal = $subtotal + $shipping_charge;
            if ($applyDiscount) {
                $newTotal -= $discount;
            }

            \Log::info('Invoice update - payment validation', [
                'invoice_id' => $invoiceId,
                'current_total' => $currentTotal,
                'current_balance' => $currentBalance,
                'existing_payments' => $existingPayments,
                'new_total' => $newTotal,
                'invoice_status' => $currentInvoice['status'] ?? ''
            ]);

            // Check if existing payments exceed new total
            if ($existingPayments > $newTotal && $existingPayments > 0) {
                \Log::warning('Existing payments exceed new invoice total - will not mark as paid', [
                    'invoice_id' => $invoiceId,
                    'existing_payments' => $existingPayments,
                    'new_total' => $newTotal,
                    'overpayment' => $existingPayments - $newTotal
                ]);

                // Don't mark as paid to avoid payment conflicts
                $markPaid = false;
            }
            
            \Log::info('Zoho invoice update discount validation', [
                'order_id' => $orderId,
                'invoice_id' => $invoiceId,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'apply_discount' => $applyDiscount
            ]);

            $payload = [
                'customer_id'      => $customer->contact_id,
                'location_id'      => $locationId,
                'line_items'       => $line_items,
                'description'      => 'Invoice updated by API',
                'reason'           => 'Order details updated - line items, pricing, or shipping modified',
                // explicit shipping fields so Zoho displays shipping separately
                'shipping_charge'  => $shipping_charge,
                'shipping_charge_tax_id' => $shipping_charge > 0 ? $taxId : null,
                'order_id'         => $orderId,
                'reference_number' => "ID:{$getOrder->id} | #{$orderId}",
                // IMPORTANT:
                'place_of_supply'  => $buyer,     // buyer's 2-letter code
                'gst_no'           => $sellerGst, // seller branch's GSTIN
                'series_name'      => $seriesName,
            ];

            // Only add discount fields if discount should be applied
            if ($applyDiscount) {
                $payload['discount'] = $discount;
                $payload['discount_type'] = 'entity_level';
            }

            // Only set status 'paid' when caller requests marking paid. For manual orders we keep draft/unpaid.
            if ($markPaid) {
                $payload['status'] = 'paid';
            } else {
                // omit marking as paid; create as draft
                $payload['status'] = 'draft';
            }

            $resp = $client->put("https://www.zohoapis.com/books/v3/invoices/{$invoiceId}", [
                'headers' => [
                    'Authorization' => 'Zoho-oauthtoken ' . self::generateToken(),
                    'Content-Type'  => 'application/json',
                ],
                'query' => ['organization_id' => env('ZOHO_API_ORGANIZATION_ID')],
                'json'  => $payload,
            ]);

            $invoiceData = json_decode($resp->getBody()->getContents(), true);

            if (!isset($invoiceData['invoice']['invoice_id'], $invoiceData['invoice']['customer_id'])) {
                throw new \Exception("Invoice update failed or missing data.");
            }

            // Mark invoice as paid in Zoho only when requested
            if ($markPaid) {
                self::markInvoiceAsPaid(
                    $invoiceData['invoice']['invoice_id'],
                    $invoiceData['invoice']['customer_id'],
                    $invoiceData['invoice']['total']
                );
            }

            \Log::info('INVOICE-UPDATE-SUCCESS', [
                'invoice_id' => $invoiceData['invoice']['invoice_id'],
                'order_number' => $orderId,
                'total' => $invoiceData['invoice']['total']
            ]);

            return $invoiceData;
        } catch (\Exception $e) {
            // Try to extract response body from Guzzle RequestException if present
            $responseBody = '';
            if (method_exists($e, 'getResponse') && $e->getResponse()) {
                $responseBody = $e->getResponse()->getBody()->getContents();
            }

            \Log::error('INVOICE-UPDATE-ERROR', [
                'order_number' => $orderId,
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage(),
                'response' => $responseBody
            ]);

            throw new \Exception("Error updating invoice: " . $e->getMessage() . " | response: " . $responseBody);
        }
    }


    public static function createEstimate($contactId, $allQuotationItems, $productDetails)
    {
        $client = new Client();

        $line_items = [];
        foreach ($allQuotationItems as $items) {

            $itemFromZoho = self::skuToItemId($items->zoho_sku);
            $line_items[] = [
                'name' => $items->model,
                'rate' => $productDetails[$items->product_id][0]->price,
                'rate' => round($productDetails[$items->product_id][0]->price / (1 + ($itemFromZoho['tax_percentage'] / 100)), 2),
                'quantity' => $items->quotation_qty,
                'item_id' => $itemFromZoho['item_id'],
                'tax_id' => $itemFromZoho['tax_id']
            ];
        }
        $response = $client->post('https://www.zohoapis.com/books/v3/estimates?organization_id=' . env('ZOHO_API_ORGANIZATION_ID'), [
            'headers' => [
                'Authorization' => 'Zoho-oauthtoken ' . self::generateToken(),
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'customer_id' => $contactId,
                'line_items' => $line_items,
                'description' => 'Estimate generated by API',
                'discount_type' => 'entity_level',
            ]
        ]);
        // dd(json_decode($response->getBody(), true));
    }
    public static function createOrGetCustomer($customerId, $address = [])
    {
        $customer = Customer::where('id', $customerId)->first();
        
        // Add detailed logging for customer name debugging
        Log::info('=== ZOHO CUSTOMER CREATION DEBUG START ===', [
            'customer_id' => $customerId,
            'customer_data' => $customer ? $customer->toArray() : null,
            'address_data' => $address
        ]);
        
        if (!$customer) {
            Log::error('Customer not found in database', ['customer_id' => $customerId]);
            throw new \Exception("Customer not found with ID: {$customerId}");
        }
        
        $customerName = $customer->first_name . " " . $customer->last_name;
        Log::info('Customer name constructed', [
            'customer_id' => $customerId,
            'first_name' => $customer->first_name,
            'last_name' => $customer->last_name,
            'full_name' => $customerName,
            'email' => $customer->email,
            'phone' => $customer->phone ?? $customer->mobile ?? 'N/A'
        ]);
        
        // Skip Zoho customer lookup if email is null - always create new customer
        if (empty($customer->email)) {
            Log::info('Customer has no email - creating new customer in Zoho instead of searching', [
                'customer_id' => $customerId,
                'customer_name' => $customerName
            ]);
            
            $client = new Client();
            $customerSendData = [
                "contact_name" => $customerName,
                "email" => '', // Empty string instead of null
                "phone" => $customer->phone ?? $customer->mobile ?? '',
                "billing_address" => $address,
                "shipping_address" => $address,
                "contact_persons" => [
                    [
                        "email" => '',
                        "phone" => $customer->phone ?? $customer->mobile ?? '',
                    ]
                ],
            ];
            
            Log::info('Creating new customer in Zoho (no email)', [
                'customer_id' => $customerId,
                'zoho_payload' => $customerSendData
            ]);
            
            if ($customer->gstin != "") {
                $customerSendData['company_name'] = $customer->company;
                $customerSendData['gst_treatment'] = "business_gst";
                $customerSendData['gst_no'] = $customer->gstin;
            }
            
            try {
                $response = $client->post('https://www.zohoapis.com/books/v3/contacts?organization_id=' . env('ZOHO_API_ORGANIZATION_ID'), [
                    'headers' => [
                        'Authorization' => 'Zoho-oauthtoken ' . self::generateToken(),
                        'Content-Type' => 'application/json',
                    ],
                    'json' => $customerSendData
                ]);
                
                $responseBody = json_decode($response->getBody(), true);
                Log::info('Zoho customer creation response (no email)', [
                    'customer_id' => $customerId,
                    'status_code' => $response->getStatusCode(),
                    'response_body' => $responseBody
                ]);
                
                // Return the newly created customer
                $newCustomer = $responseBody['contact'] ?? null;
                if ($newCustomer) {
                    Log::info('=== ZOHO CUSTOMER CREATION DEBUG END ===', [
                        'customer_id' => $customerId,
                        'final_zoho_contact_name' => $newCustomer['contact_name'] ?? 'N/A'
                    ]);
                    return (object)$newCustomer;
                }
                
            } catch (\Exception $e) {
                Log::error('Failed to create customer in Zoho (no email)', [
                    'customer_id' => $customerId,
                    'error' => $e->getMessage(),
                    'customer_data_sent' => $customerSendData
                ]);
                throw $e;
            }
        }
        
        // Original logic for customers with email
        $isExists = self::isCustomerExists($customer->email);
        Log::info('Checking if customer exists in Zoho', [
            'customer_id' => $customerId,
            'email' => $customer->email,
            'exists_in_zoho' => $isExists
        ]);
        
        if ($isExists) {
            Log::info('Customer already exists in Zoho, retrieving existing customer', [
                'customer_id' => $customerId,
                'email' => $customer->email
            ]);
        } else {
            $client = new Client();
            $customerSendData = [
                "contact_name" => $customerName,
                "email" => $customer->email,
                "phone" => $customer->phone ?? $customer->mobile ?? '',
                "billing_address" => $address,
                "shipping_address" => $address,
                "contact_persons" => [
                    [
                        "email" => $customer->email,
                        "phone" => $customer->phone ?? $customer->mobile ?? '',
                    ]
                ],
            ];
            
            Log::info('Creating new customer in Zoho', [
                'customer_id' => $customerId,
                'zoho_payload' => $customerSendData
            ]);
            
            if ($customer->gstin != "") {
                $customerSendData['company_name'] = $customer->company;
                $customerSendData['gst_treatment'] = "business_gst";
                $customerSendData['gst_no'] = $customer->gstin;
                
                Log::info('Adding GST details to Zoho customer', [
                    'customer_id' => $customerId,
                    'company' => $customer->company,
                    'gstin' => $customer->gstin
                ]);
            }
            
            try {
                $response = $client->post('https://www.zohoapis.com/books/v3/contacts?organization_id=' . env('ZOHO_API_ORGANIZATION_ID'), [
                    'headers' => [
                        'Authorization' => 'Zoho-oauthtoken ' . self::generateToken(),
                        'Content-Type' => 'application/json',
                    ],
                    'json' => $customerSendData
                ]);
                
                $responseBody = json_decode($response->getBody(), true);
                Log::info('Zoho customer creation response', [
                    'customer_id' => $customerId,
                    'status_code' => $response->getStatusCode(),
                    'response_body' => $responseBody
                ]);
                
            } catch (\Exception $e) {
                Log::error('Failed to create customer in Zoho', [
                    'customer_id' => $customerId,
                    'error' => $e->getMessage(),
                    'customer_data_sent' => $customerSendData
                ]);
                throw $e;
            }
        }

        $customerFromZoho = self::isCustomerFirst($customer->email);
        
        Log::info('Retrieved customer from Zoho', [
            'customer_id' => $customerId,
            'zoho_customer' => $customerFromZoho,
            'zoho_contact_name' => $customerFromZoho->contact_name ?? 'N/A'
        ]);
        
        Log::info('=== ZOHO CUSTOMER CREATION DEBUG END ===', [
            'customer_id' => $customerId,
            'final_zoho_contact_name' => $customerFromZoho->contact_name ?? 'N/A'
        ]);
        
        return $customerFromZoho;
    }
    public static function isCustomerExists($email)
    {
        $access_token = self::generateToken();
        $client = new Client();

        $response = $client->get('https://www.zohoapis.com/books/v3/contacts?organization_id=' . env("ZOHO_API_ORGANIZATION_ID") . '&email=' . $email, [
            'headers' => [
                'Authorization' => 'Zoho-oauthtoken ' . $access_token,
                'Content-Type' => 'application/json'
            ]
        ]);
        $body = json_decode($response->getBody());
        return count($body->contacts);
    }

    public static function isCustomerFirst($email)
    {
        $access_token = self::generateToken();
        $client = new Client();

        $response = $client->get('https://www.zohoapis.com/books/v3/contacts?organization_id=' . env("ZOHO_API_ORGANIZATION_ID") . '&email=' . $email, [
            'headers' => [
                'Authorization' => 'Zoho-oauthtoken ' . $access_token,
                'Content-Type' => 'application/json'
            ]
        ]);
        $body = json_decode($response->getBody());
        return $body->contacts[0];
    }

    public static function updateCustomerInZoho($zohoContactId, $localCustomerId, $address = [])
    {
        $customer = Customer::where('id', $localCustomerId)->first();
        if (!$customer) {
            throw new \Exception("Customer not found with ID: {$localCustomerId}");
        }

        $client = new Client();
        $organizationId = env('ZOHO_API_ORGANIZATION_ID');

        try {
            $customerUpdateData = [
                "contact_name" => $customer->first_name . " " . $customer->last_name,
                "email" => $customer->email,
                "phone" => $customer->phone,
                "billing_address" => $address,
                "shipping_address" => $address,
                "contact_persons" => [
                    [
                        "email" => $customer->email,
                        "phone" => $customer->phone,
                    ]
                ],
            ];

            // Add GST details if available
            if (!empty($customer->gstin)) {
                $customerUpdateData['company_name'] = $customer->company ?? '';
                $customerUpdateData['gst_treatment'] = "business_gst";
                $customerUpdateData['gst_no'] = $customer->gstin;
            }

            \Log::info('Updating customer in Zoho', [
                'zoho_contact_id' => $zohoContactId,
                'local_customer_id' => $localCustomerId,
                'customer_name' => $customerUpdateData['contact_name']
            ]);

            $response = $client->put("https://www.zohoapis.com/books/v3/contacts/{$zohoContactId}", [
                'headers' => [
                    'Authorization' => 'Zoho-oauthtoken ' . self::generateToken(),
                    'Content-Type' => 'application/json',
                ],
                'query' => ['organization_id' => $organizationId],
                'json' => $customerUpdateData,
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);

            \Log::info('Customer updated successfully in Zoho', [
                'zoho_contact_id' => $zohoContactId,
                'response' => $responseData
            ]);

            return $responseData;

        } catch (\Exception $e) {
            \Log::error('Error updating customer in Zoho', [
                'zoho_contact_id' => $zohoContactId,
                'local_customer_id' => $localCustomerId,
                'error' => $e->getMessage()
            ]);
            throw new \Exception("Error updating customer in Zoho: " . $e->getMessage());
        }
    }

    public static function changeInvoiceStatusToDraft($invoiceId)
    {
        $client = new Client();
        $organizationId = env('ZOHO_API_ORGANIZATION_ID');

        try {
            \Log::info('Changing invoice status to draft', [
                'invoice_id' => $invoiceId
            ]);

            $response = $client->post("https://www.zohoapis.com/books/v3/invoices/{$invoiceId}/status/draft", [
                'headers' => [
                    'Authorization' => 'Zoho-oauthtoken ' . self::generateToken(),
                    'Content-Type' => 'application/json',
                ],
                'query' => ['organization_id' => $organizationId],
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);

            \Log::info('Invoice status changed to draft successfully', [
                'invoice_id' => $invoiceId,
                'response' => $responseData
            ]);

            return $responseData;

        } catch (\Exception $e) {
            \Log::error('Error changing invoice status to draft', [
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage()
            ]);
            throw new \Exception("Error changing invoice status to draft: " . $e->getMessage());
        }
    }
    public static function allOrganizations()
    {
        $access_token = self::generateToken();
        $client = new Client();

        $response = $client->get('https://www.zohoapis.com/books/v3/organizations', [
            'headers' => [
                'Authorization' => 'Zoho-oauthtoken ' . $access_token,
                'Content-Type' => 'application/json'
            ]
        ]);
        $body = json_decode($response->getBody());
        dd($body);
    }
    public static function getItems($page = 1, $perPage = 20, $searchCriteria = null)
    {
        $access_token = self::generateToken();
        $client = new Client();

        $queryParams = [
            'organization_id' => env('ZOHO_API_ORGANIZATION_ID'),
            'per_page' => $perPage,
            'page' => $page,
        ];

        // If search criteria is provided, include it in the query parameters
        if ($searchCriteria) {
            $queryParams['criteria'] = $searchCriteria;
        }

        try {
            $response = $client->get('https://www.zohoapis.com/books/v3/items', [
                'headers' => [
                    'Authorization' => 'Zoho-oauthtoken ' . $access_token,
                    'Content-Type' => 'application/json'
                ],
                'query' => $queryParams
            ]);

            $data = json_decode($response->getBody(), true);

            // Check if items are returned
            if (isset($data['items'])) {
                return $data['items'];
            } else {
                \Log::error("Error fetching items", ['response' => $data]);
                throw new \Exception("Failed to fetch items");
            }
        } catch (\Exception $e) {
            \Log::error("Zoho API Error: " . $e->getMessage());
            throw new \Exception("Zoho API Error: " . $e->getMessage());
        }
    }
    public static function getItemBySKU()
    {
        $sku    =   'HT00012';

        $access_token = self::generateToken(); // Generate the Zoho access token
        $client = new Client();

        $organizationId = env('ZOHO_API_ORGANIZATION_ID');
        $queryParams = [
            'organization_id' => $organizationId,
            'per_page' => 1, // We only need one item
            'criteria' => "(SKU:equals:$sku)" // Primary method using criteria
        ];

        try {
            // Fetch item using criteria
            $response = $client->get('https://www.zohoapis.com/books/v3/items', [
                'headers' => [
                    'Authorization' => 'Zoho-oauthtoken ' . $access_token,
                    'Content-Type' => 'application/json'
                ],
                'query' => $queryParams
            ]);

            $data = json_decode($response->getBody(), true);

            // Log API response for debugging
            \Log::info("Zoho Books API Response", ['response' => $data]);

            // Check if items are returned
            if (!empty($data['items'])) {
                return $data['items'][0]; // Return the first matching item
            }

            // If no items are found using criteria, fallback to search_text
            \Log::warning("No item found using criteria. Trying search_text for SKU: $sku");

            // Retry using search_text
            $response = $client->get('https://www.zohoapis.com/books/v3/items', [
                'headers' => [
                    'Authorization' => 'Zoho-oauthtoken ' . $access_token,
                    'Content-Type' => 'application/json'
                ],
                'query' => [
                    'organization_id' => $organizationId,
                    'search_text' => $sku, // Fallback to search_text
                    'per_page' => 10,
                    'sku' => $sku,
                ]
            ]);

            $data = json_decode($response->getBody(), true);

            // Log fallback API response
            \Log::info("Zoho Books Fallback API Response", ['response' => $data]);

            if (!empty($data['items'])) {
                return $data['items'][0]; // Return the first matching item
            }

            // If no items are found, log and return null
            \Log::error("No item found for SKU: $sku in Zoho Books");
            return null;
        } catch (\Exception $e) {
            // Log the exception and rethrow it
            \Log::error("Zoho API Error while fetching item: " . $e->getMessage());
            throw new \Exception("Zoho API Error while fetching item: " . $e->getMessage());
        }
    }
}
