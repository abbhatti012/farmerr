<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Models\ProductImage;
use Illuminate\Support\Facades\DB;

class ShopifyProductImportService
{
    /**
     * Save all Shopify products (and related data) into DB.
     */
    public function import(array $shopifyProducts): void
    {
        foreach ($shopifyProducts as $productData) {
            DB::transaction(function () use ($productData) {
                // ---- 1) Product ----
                $product = Product::updateOrCreate(
                    ['id' => $productData['id']],
                    [
                        'title' => $productData['title'] ?? null,
                        'body_html' => $productData['body_html'] ?? null,
                        'vendor' => $productData['vendor'] ?? null,
                        'product_type' => $productData['product_type'] ?? null,
                        'handle' => $productData['handle'] ?? null,
                        'status' => $productData['status'] ?? null,
                        'published_scope' => $productData['published_scope'] ?? null,
                        'template_suffix' => $productData['template_suffix'] ?? null,
                        'admin_graphql_api_id' => $productData['admin_graphql_api_id'] ?? null,
                        'shopify_created_at' => $productData['created_at'] ?? null,
                        'shopify_updated_at' => $productData['updated_at'] ?? null,
                        'shopify_published_at' => $productData['published_at'] ?? null,
                    ]
                );

                // ---- 2) Options ----
                if (!empty($productData['options'])) {
                    foreach ($productData['options'] as $opt) {
                        $option = ProductOption::updateOrCreate(
                            ['id' => $opt['id']],
                            [
                                'product_id' => $product->id,
                                'name' => $opt['name'] ?? null,
                                'position' => $opt['position'] ?? null,
                            ]
                        );

                        if (!empty($opt['values'])) {
                            foreach ($opt['values'] as $idx => $val) {
                                ProductOptionValue::updateOrCreate(
                                    ['product_option_id' => $option->id, 'value' => $val],
                                    ['position' => $idx + 1]
                                );
                            }
                        }
                    }
                }

                // ---- 3) Variants ----
                if (!empty($productData['variants'])) {
                    foreach ($productData['variants'] as $variant) {
                        ProductVariant::updateOrCreate(
                            ['id' => $variant['id']],
                            [
                                'product_id' => $product->id,
                                'title' => $variant['title'] ?? null,
                                'price' => $variant['price'] ?? null,
                                'compare_at_price' => $variant['compare_at_price'] ?? null,
                                'position' => $variant['position'] ?? null,
                                'sku' => $variant['sku'] ?? null,
                                'taxable' => $variant['taxable'] ?? false,
                                'fulfillment_service' => $variant['fulfillment_service'] ?? null,
                                'inventory_management' => $variant['inventory_management'] ?? null,
                                'inventory_policy' => $variant['inventory_policy'] ?? null,
                                'requires_shipping' => $variant['requires_shipping'] ?? true,
                                'grams' => $variant['grams'] ?? 0,
                                'weight' => $variant['weight'] ?? 0,
                                'weight_unit' => $variant['weight_unit'] ?? 'kg',
                                'inventory_item_id' => $variant['inventory_item_id'] ?? null,
                                'inventory_quantity' => $variant['inventory_quantity'] ?? 0,
                                'old_inventory_quantity' => $variant['old_inventory_quantity'] ?? 0,
                                'option1' => $variant['option1'] ?? null,
                                'option2' => $variant['option2'] ?? null,
                                'option3' => $variant['option3'] ?? null,
                                'admin_graphql_api_id' => $variant['admin_graphql_api_id'] ?? null,
                                'shopify_created_at' => $variant['created_at'] ?? null,
                                'shopify_updated_at' => $variant['updated_at'] ?? null,
                            ]
                        );
                    }
                }

                // ---- 4) Images ----
                if (!empty($productData['images'])) {
                    foreach ($productData['images'] as $img) {
                        $image = ProductImage::updateOrCreate(
                            ['id' => $img['id']],
                            [
                                'product_id' => $product->id,
                                'src' => $img['src'] ?? null,
                                'alt' => $img['alt'] ?? null,
                                'position' => $img['position'] ?? null,
                                'width' => $img['width'] ?? null,
                                'height' => $img['height'] ?? null,
                                'admin_graphql_api_id' => $img['admin_graphql_api_id'] ?? null,
                                'shopify_created_at' => $img['created_at'] ?? null,
                                'shopify_updated_at' => $img['updated_at'] ?? null,
                                'is_main' => 0,
                            ]
                        );

                        if (!empty($img['variant_ids'])) {
                            $image->variants()->syncWithoutDetaching($img['variant_ids']);
                        }
                    }
                }

                // ---- 5) Featured image ----
                if (!empty($productData['image'])) {
                    ProductImage::where('id', $productData['image']['id'] ?? 0)
                        ->where('product_id', $product->id)
                        ->update(['is_main' => true]);
                }
            });
        }
    }
}
