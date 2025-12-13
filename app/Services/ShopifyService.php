<?php

namespace App\Services;

use Osiset\ShopifyApp\Util;
use Osiset\ShopifyApp\Contracts\ApiHelper as IApiHelper;

class ShopifyService
{
    protected $apiHelper;

    public function __construct(IApiHelper $apiHelper)
    {
        $this->apiHelper = $apiHelper;
    }

    public function getOrders()
    {
        $shopDomain = config('shopify-app.host');
        $shop = Util::getShopByDomain($shopDomain);

        if (!$shop) {
            throw new \Exception("Shop not found.");
        }

        $api = $this->apiHelper->getApi($shop);
        $response = $api->rest('GET', '/admin/orders.json');

        return $response->body->orders;
    }

    public function fulfillOrder($orderId)
    {
        $shopDomain = config('shopify-app.host');
        $shop = Util::getShopByDomain($shopDomain);

        if (!$shop) {
            throw new \Exception("Shop not found.");
        }

        $api = $this->apiHelper->getApi($shop);
        $response = $api->rest('POST', "/admin/orders/{$orderId}/fulfillments.json", [
            'fulfillment' => [
                'location_id' => config('shopify-app.location_id'),
            ],
        ]);
        return $response->body;
    }
}
