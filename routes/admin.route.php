<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Auth\AuthController;
use App\Http\Controllers\SmsController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ZohoController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('', [AuthController::class, 'redirectToLogin']);
Route::get('/login', [AuthController::class, 'loginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::get('/run-shopify-orders', function () {
    Artisan::call('fetch:shopify-orders');
    return 'Shopify orders fetched successfully!';
});
Route::get('/tax-id/{rate}', [ZohoController::class, 'getTaxIdByRateRoute']);
// Route::get('/zoho/branches', function () {
//     $branches = ZohoController::getBranches();
//     return response()->json($branches);
// });
Route::middleware(['admin.auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('admin/send-template-sms', [\App\Http\Controllers\Admin\OrderController::class, 'sendTemplateSms'])->name('send-template-sms');
});


Route::middleware(['admin.auth'])->group(function () {

    // WhatsApp Log routes
    Route::prefix('whatsapp-logs')->group(function () {
        Route::get('/', [WhatsAppLogController::class, 'index'])->name('whatsapp-logs.index');
        Route::get('/{id}', [WhatsAppLogController::class, 'show'])->name('whatsapp-logs.show');
        Route::get('/order/{orderId}', [WhatsAppLogController::class, 'orderLogs'])->name('whatsapp-logs.order');
        Route::post('/{id}/retry', [WhatsAppLogController::class, 'retry'])->name('admin.whatsapp-logs.retry');
    });

    Route::get('customers', [CustomerController::class, 'list'])->name('customers.list');

    // Route::get('/zoho/payment-methods', [ZohoController::class, 'getPaymentMethods'])
    // ->name('zoho.payment.methods');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/orders/list', [OrderController::class, 'list'])->name('orders.list');
    Route::get('/orders/list-test', [OrderController::class, 'listTest'])->name('orders.list.test');
    Route::get('/orders/create', [OrderController::class, 'create'])->name('orders.create');
    Route::post('/orders/create', [OrderController::class, 'store'])->name('orders.store');
    Route::get('/orders_update', [ShopifyController::class, 'fetchOrders']);
    Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('orders/{order}/fulfill', [OrderController::class, 'fulfillOrder'])->name('orders.fulfill');

    Route::get('create-zoho-order/{id}', [OrderController::class, 'sendOrderToZoho'])->name('create.zoho.order');

    // multiorders
    Route::post('/orders/create-zoho-orders', [OrderController::class, 'createOrdersInZoho'])->name('orders.create.zoho');

    // create zoho credit note
    Route::post('/orders/{orderId}/create-credit-note', [OrderController::class, 'createCreditNoteForRefund'])->name('orders.createCreditNote');

    Route::get('order-status', [OrderStatusController::class, 'index'])->name('order_status.index');
    Route::post('order-status', [OrderStatusController::class, 'store'])->name('order_status.store');
    Route::put('order-status/{id}', [OrderStatusController::class, 'update'])->name('order_status.update');
    Route::post('order-status/status', [OrderStatusController::class, 'updateStatus'])->name('order_status.status');
    Route::delete('order-status/{id}', [OrderStatusController::class, 'destroy'])->name('order_status.destroy');

    Route::post('/send-template-sms', [SmsController::class, 'sendTemplateSms']);

    Route::put('/products/{productId}/update-sku', [ProductController::class, 'updateBatchSkus'])->name('products.update.sku');
    Route::get('/products/export-all', [ProductController::class, 'exportAllProductsAndVariantsToCsv'])->name('products.export.all');


    Route::get('/live-analytics', [DashboardController::class, 'showLiveViews']);
    Route::get('/order/export-latest', [OrderController::class, 'exportLatestOrders'])->name('order.export');


    Route::get('/download-invoice/{orderId}', [OrderController::class, 'downloadInvoice'])->name('download.invoice');
    Route::get('/shopify/sync-products', [ProductController::class, 'syncFromShopify'])->name('admin.shopify.sync-products');
});
