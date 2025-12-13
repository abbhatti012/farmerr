<?php

namespace App\Http\Controllers;

use App\Services\ShopifyApiService;
use App\Services\ShopifyProductService;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Reader\Exception;
use App\Services\ShopifyProductImportService;

class ProductController extends Controller
{
    protected $shopifyApiService;
    protected $shopifyProductService;
    protected $importService;

    public function __construct(ShopifyApiService $shopifyApiService, ShopifyProductService $shopifyProductService, ShopifyProductImportService $importService)
    {
        $this->shopifyApiService = $shopifyApiService;
        $this->shopifyProductService = $shopifyProductService;
        $this->importService  = $importService;
    }
    /**
     * One-click: fetch all Shopify products and save to DB.
     */
    public function syncFromShopify()
    {
        try {
            $products = $this->shopifyProductService->getAllProducts();
            $this->importService->import($products);

            return response()->json([
                'message' => '✅ All Shopify products synced successfully.',
                'count' => count($products),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update SKUs for the first 10 products in Shopify.
     */
    // public function updateBatchSkus()
    // {
    //     try {
    //         // Specify the file path for the CSV file
    //         $filePath = storage_path('app/public/Filtered_Active_Products.csv');

    //         // Ensure the file exists and is readable
    //         if (!file_exists($filePath) || !is_readable($filePath)) {
    //             throw new \Exception("File not found or not readable");
    //         }

    //         // Open the file
    //         $file = fopen($filePath, 'r');
    //         $header = fgetcsv($file); // Read the header row
    //         $rows = [];
    //         $count = 0;

    //         // Process each row, limiting to the first 10
    //         while (($data = fgetcsv($file)) !== false && $count < 700) {
    //             $row = array_combine($header, $data); // Combine header with row data

    //             // Ensure product_id and variant_id are treated as raw strings
    //             $row['product_id'] = $row['product_id'];
    //             $row['variant_id'] = $row['variant_id'];

    //             $rows[] = $row;
    //             $count++;
    //         }

    //         fclose($file); // Close the file after reading

    //         // dd($rows);

    //         // Iterate through rows and update SKUs
    //         foreach ($rows as $row) {
    //             $productId = $row['product_id'];
    //             $variantId = $row['variant_id'];
    //             $newSku = $row['variant_sku'];

    //             try {
    //                 if (!empty($variantId)) {
    //                     // Try updating SKU by variant ID
    //                     $response = $this->shopifyApiService->updateProductVariantSku($productId, $variantId, $newSku);
    //                 } else {
    //                     throw new \Exception("Variant ID not found, falling back to product ID");
    //                 }
    //             } catch (\Exception $e) {
    //                 try {
    //                     // Fallback: Update SKU by product ID
    //                     $response = $this->shopifyApiService->updateProductSku($productId, $newSku);
    //                     echo "SKU updated successfully for Product ID: $productId (Fallback)\n";
    //                 } catch (\Exception $ex) {
    //                     echo "Error updating SKU for Product ID: $productId, Variant ID: $variantId: " . $ex->getMessage() . "\n";
    //                 }
    //             }
    //         }
    //     } catch (\Exception $e) {
    //         return response()->json(['error' => $e->getMessage()], 500);
    //     }
    // }
    public function updateBatchSkus()
    {
        try {
            // Specify the file path for the CSV file
            $filePath = storage_path('app/public/Filtered_Active_Products.csv');

            // Ensure the file exists and is readable
            if (!file_exists($filePath) || !is_readable($filePath)) {
                throw new \Exception("File not found or not readable");
            }

            // Define the range
            $startRow = 271; // Starting row (inclusive)
            $endRow = 340; // Ending row (inclusive)

            // Open the file
            $file = fopen($filePath, 'r');
            $header = fgetcsv($file); // Read the header row
            $rows = [];
            $currentRow = 0;

            // Process each row within the specified range
            while (($data = fgetcsv($file)) !== false) {
                $currentRow++;

                // Skip rows outside the range
                if ($currentRow < $startRow) {
                    continue;
                }
                if ($currentRow > $endRow) {
                    break;
                }

                $row = array_combine($header, $data); // Combine header with row data

                // Ensure product_id and variant_id are treated as raw strings
                $row['product_id'] = $row['product_id'];
                $row['variant_id'] = $row['variant_id'];

                $rows[] = $row;
            }

            fclose($file); // Close the file after reading

            // Iterate through rows and update SKUs
            foreach ($rows as $row) {
                $productId = $row['product_id'];
                $variantId = $row['variant_id'];
                $newSku = $row['variant_sku'];

                try {
                    if (!empty($variantId)) {
                        // Try updating SKU by variant ID
                        $response = $this->shopifyApiService->updateProductVariantSku($productId, $variantId, $newSku);
                    } else {
                        throw new \Exception("Variant ID not found, falling back to product ID");
                    }
                } catch (\Exception $e) {
                    try {
                        // Fallback: Update SKU by product ID
                        $response = $this->shopifyApiService->updateProductSku($productId, $newSku);
                        echo "SKU updated successfully for Product ID: $productId (Fallback)\n";
                    } catch (\Exception $ex) {
                        echo "Error updating SKU for Product ID: $productId, Variant ID: $variantId: " . $ex->getMessage() . "\n";
                    }
                }
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    /**
     * Clean IDs by removing scientific notation or other unwanted formatting.
     *
     * @param string $id
     * @return string
     */
    private function cleanId($id)
    {
        // If the ID contains scientific notation, convert to plain string
        if (strpos($id, 'E') !== false || strpos($id, 'e') !== false) {
            return rtrim(sprintf('%.0f', $id), '.'); // Convert and remove any decimals
        }

        // Otherwise, ensure it's a string
        return strval($id);
    }






    /**
     * Download all products and their variants in a CSV file.
     *
     * CSV Columns: 
     * - product_id
     * - product_title
     * - product_status
     * - variant_id
     * - variant_title
     * - variant_sku
     * - variant_price
     *
     * Modify columns as needed.
     */
    public function exportAllProductsAndVariantsToCsv()
    {
        $products = $this->shopifyProductService->getAllProducts();


        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="all_products_variants.csv"',
        ];

        $callback = function () use ($products) {
            $file = fopen('php://output', 'w');

            // CSV column headers (including 'product_handle')
            fputcsv($file, [
                'product_id',
                'product_handle',
                'product_title',
                'product_status',
                'variant_id',
                'variant_title',
                'variant_sku',
                'variant_price',
            ]);

            foreach ($products as $product) {
                $productId = $product['id'];
                $productHandle = $product['handle'] ?? '';
                $productTitle = $product['title'];
                $productStatus = $product['status'] ?? 'unknown';

                $variants = $product['variants'] ?? [];

                // Filter out "Default Title" variants if you don't want them
                $filteredVariants = array_filter($variants, function ($variant) {
                    return strtolower($variant['title']) !== 'default title';
                });

                // If no variants remain after filtering, skip this product
                if (empty($filteredVariants)) {
                    continue;
                }

                // Print each remaining variant
                foreach ($filteredVariants as $variant) {
                    fputcsv($file, [
                        $productId,
                        $productHandle,
                        $productTitle,
                        $productStatus,
                        $variant['id'],
                        $variant['title'],
                        $variant['sku'] ?? '',
                        $variant['price'] ?? '',
                    ]);
                }
            }

            fclose($file);
        };

        return response()->streamDownload($callback, 'all_products_variants.csv', $headers);
    }
}
