<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Exception;

class ShopifyApiService
{
    protected $baseUrl;
    protected $accessToken;

    /**
     * Initialize the service with environment variables.
     */
    public function __construct()
    {
        $shopUrl = rtrim(env('SHOPIFY_SHOP_URL'), '/');
        $apiVersion = env('SHOPIFY_API_VERSION', '2024-04'); // default to 2024-04 if not set

        $this->baseUrl = "{$shopUrl}/admin/api/{$apiVersion}";
        $this->accessToken = env('SHOPIFY_ACCESS_TOKEN');
    }

    /**
     * Fetch live analytics data from Shopify using GraphQL.
     *
     * @return array The analytics data, including active visitors and page views.
     * @throws Exception if the request fails.
     */
    public function getLiveAnalyticsData()
    {
        $graphqlUrl = "{$this->baseUrl}/graphql.json";

        $query = <<<'GRAPHQL'
    {
        shop {
            analytics {
                activeVisitors
                pageViews
            }
        }
    }
    GRAPHQL;

        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $this->accessToken,
            'Content-Type' => 'application/json',
        ])->post($graphqlUrl, [
            'query' => $query,
        ]);

        \Log::info('Shopify GraphQL Response', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        if ($response->failed()) {
            \Log::error('Shopify GraphQL API Error', [
                'url' => $graphqlUrl,
                'response' => $response->body(),
                'status' => $response->status(),
            ]);
            throw new \Exception("Shopify GraphQL API Error: " . $response->body());
        }

        $data = $response->json()['data']['shop']['analytics'] ?? null;

        if (!$data) {
            \Log::error('Analytics data not found', [
                'response' => $response->body(),
            ]);
            throw new \Exception("Analytics data not found in response.");
        }

        return $data;
    }
}
