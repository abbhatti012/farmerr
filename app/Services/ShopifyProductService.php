<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Exception;

class ShopifyProductService
{
    protected $baseUrl;
    protected $accessToken;

    public function __construct()
    {
        $shopUrl = rtrim(env('SHOPIFY_SHOP_URL'), '/');
        $apiVersion = env('SHOPIFY_API_VERSION', '2024-04');

        $this->baseUrl = "{$shopUrl}/admin/api/{$apiVersion}";
        $this->accessToken = env('SHOPIFY_ACCESS_TOKEN');
    }

    /**
     * ORIGINAL: get everything from start.
     */
    public function getAllProducts(): array
    {
        $products = [];
        $url = "{$this->baseUrl}/products.json?limit=250";

        while ($url) {
            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $this->accessToken,
            ])->get($url);

            if ($response->failed()) {
                throw new Exception("Failed to fetch products: " . $response->body());
            }

            $data = $response->json();
            $products = array_merge($products, $data['products'] ?? []);

            $nextLink = $this->getNextLink($response->headers());
            $url = $nextLink;
        }

        return $products;
    }

    /**
     * NEW: get products AFTER a specific product id.
     * Pass the last stored Shopify product id here.
     */
    public function getProductsAfterId(int $sinceId): array
    {
        $products = [];
        // start from the given id
        $url = "{$this->baseUrl}/products.json?limit=250&since_id={$sinceId}";

        while ($url) {
            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $this->accessToken,
            ])->get($url);

            if ($response->failed()) {
                throw new Exception("Failed to fetch products (since_id={$sinceId}): " . $response->body());
            }

            $data = $response->json();
            $batch = $data['products'] ?? [];
            $products = array_merge($products, $batch);

            // if Shopify returns nothing, break
            if (empty($batch)) {
                break;
            }

            // get the last ID from this batch to build the next request
            $last = end($batch);
            $lastId = $last['id'];

            // build next URL manually using since_id again
            $url = "{$this->baseUrl}/products.json?limit=250&since_id={$lastId}";
        }

        return $products;
    }

    protected function getNextLink($headers)
    {
        // sometimes Laravel returns lowercase keys, sometimes uppercase
        $linkHeader = $headers['Link'] ?? $headers['link'] ?? null;
        if (!$linkHeader) {
            return null;
        }

        $links = is_array($linkHeader) ? $linkHeader[0] : $linkHeader;

        $parts = explode(',', $links);
        foreach ($parts as $part) {
            if (strpos($part, 'rel="next"') !== false) {
                preg_match('/<(.*)>/', $part, $matches);
                return $matches[1] ?? null;
            }
        }

        return null;
    }
}
