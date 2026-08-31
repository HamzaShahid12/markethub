<?php

use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\CommissionController as AdminCommissionController;
use App\Http\Controllers\Admin\CouponController as AdminCouponController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\ReviewController as AdminReviewController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\VendorController as AdminVendorController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Api\CartController as ApiCartController;
use App\Http\Controllers\Api\CouponController as ApiCouponController;
use App\Http\Controllers\Api\NotificationController as ApiNotificationController;
use App\Http\Controllers\Api\WishlistController as ApiWishlistController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\Customer\ConversationController as CustomerConversationController;
use App\Http\Controllers\Customer\DashboardController as CustomerDashboardController;
use App\Http\Controllers\Customer\OrderController as CustomerOrderController;
use App\Http\Controllers\Customer\ReviewController as CustomerReviewController;
use App\Http\Controllers\DashboardRedirectController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\Storefront\CategoryController as StorefrontCategoryController;
use App\Http\Controllers\Storefront\ProductController as StorefrontProductController;
use App\Http\Controllers\Vendor\ConversationController as VendorConversationController;
use App\Http\Controllers\Vendor\DashboardController as VendorDashboardController;
use App\Http\Controllers\Vendor\EarningsController as VendorEarningsController;
use App\Http\Controllers\Vendor\InventoryController as VendorInventoryController;
use App\Http\Controllers\Vendor\OrderController as VendorOrderController;
use App\Http\Controllers\Vendor\ProductController as VendorProductController;
use App\Http\Controllers\Vendor\SalesController as VendorSalesController;
use App\Http\Controllers\Vendor\StoreProfileController as VendorStoreProfileController;
use App\Http\Controllers\WishlistPageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web / Inertia routes (section 8)
|--------------------------------------------------------------------------
| Phase 1: home page. Phase 2: auth (via Breeze) + role-gated dashboards.
| Phase 3: public product/category browsing + vendor product CRUD.
| Phase 4: cart, wishlist, coupons, checkout + transactional order
| creation. Phase 5: order history/detail for customers, scoped order
| management for vendors, full order oversight for admins. Phase 6:
| vendor sales/earnings/inventory/store-profile. Phase 7: admin users,
| product moderation, coupons, review moderation, commissions, reports.
| Phase 8: queued notifications (order confirmation, new-order alerts,
| low-stock, status updates), a shared notifications inbox, and the
| customer review submission flow. Phase 9: real-time notification
| delivery via Reverb + customer/vendor chat.
*/

Route::get('/', HomeController::class)->name('home');

Route::get('/products', [StorefrontProductController::class, 'index'])->name('products.index');
Route::get('/products/{product:slug}', [StorefrontProductController::class, 'show'])->name('products.show');
Route::get('/categories', [StorefrontCategoryController::class, 'index'])->name('categories.index');
Route::get('/categories/{category:slug}', [StorefrontCategoryController::class, 'show'])->name('categories.show');

// --- Placeholder below is added in a later phase; kept commented so
//     the intended route map from section 8 stays visible in one place.
// Route::get('/vendors/{vendor:slug}', [VendorController::class, 'show'])->name('vendors.show');

/*
|--------------------------------------------------------------------------
| Authenticated + role-gated routes (Phase 2)
|--------------------------------------------------------------------------
| Breeze's own routes (login, register, password reset, email
| verification) live in routes/auth.php, included by Breeze's
| installer — that file is untouched. We only override the
| RegisteredUserController it points to (see app/Http/Controllers/Auth).
*/

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardRedirectController::class)->name('dashboard');

    // --- Shopping (Phase 4): cart page, checkout, order confirmation.
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::get('/wishlist', [WishlistPageController::class, 'index'])->name('wishlist.index');
    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('throttle:10,1')->name('checkout.store');
    Route::get('/orders/{orderNumber}/success', [OrderController::class, 'success'])->name('orders.success');

    // --- Notifications (Phase 8): shared across all roles — the Vue
    //     page itself picks Customer/Vendor/Admin layout based on role.
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');

    // --- In-page JSON endpoints used by the cart/wishlist Pinia stores
    //     (section 9's API plan, served from the session-authenticated
    //     web app rather than routes/api.php since this is same-origin
    //     SPA traffic, not a separate token-authenticated client).
    //     Rate-limited per section 10 ("Use rate limiting for
    //     authentication and sensitive API endpoints") — cart/wishlist
    //     get a generous general limit; coupon validation gets a
    //     tighter one since it's the one endpoint that lets an
    //     unauthenticated-feeling loop brute-force guess active codes.
    Route::middleware('throttle:120,1')->prefix('api')->name('api.')->group(function () {
        Route::get('/cart', [ApiCartController::class, 'index'])->name('cart.index');
        Route::post('/cart/items', [ApiCartController::class, 'store'])->name('cart.items.store');
        Route::put('/cart/items/{item}', [ApiCartController::class, 'update'])->name('cart.items.update');
        Route::delete('/cart/items/{item}', [ApiCartController::class, 'destroy'])->name('cart.items.destroy');

        Route::get('/wishlist', [ApiWishlistController::class, 'index'])->name('wishlist.index');
        Route::post('/wishlist/toggle', [ApiWishlistController::class, 'toggle'])->name('wishlist.toggle');
        Route::delete('/wishlist/items/{item}', [ApiWishlistController::class, 'destroy'])->name('wishlist.items.destroy');

        Route::post('/coupons/validate', [ApiCouponController::class, 'validateCoupon'])
            ->middleware('throttle:20,1')
            ->name('coupons.validate');

        Route::get('/notifications/unread-count', [ApiNotificationController::class, 'unreadCount'])->name('notifications.unread-count');
    });

    Route::middleware('role:customer')->prefix('customer')->name('customer.')->group(function () {
        Route::get('/dashboard', CustomerDashboardController::class)->name('dashboard');
        Route::get('/orders', [CustomerOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [CustomerOrderController::class, 'show'])->name('orders.show');
        Route::post('/orders/{order}/cancel', [CustomerOrderController::class, 'cancel'])->name('orders.cancel');
        Route::get('/reviews', [CustomerReviewController::class, 'index'])->name('reviews.index');
        Route::post('/reviews', [CustomerReviewController::class, 'store'])->name('reviews.store');

        Route::get('/messages', [CustomerConversationController::class, 'index'])->name('messages.index');
        Route::get('/messages/{conversation}', [CustomerConversationController::class, 'show'])->name('messages.show');
        Route::post('/messages/{conversation}', [CustomerConversationController::class, 'sendMessage'])->name('messages.send');
        Route::post('/messages/start/{vendor}', [CustomerConversationController::class, 'start'])->name('messages.start');
        // Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist');
        // Route::get('/addresses', [AddressController::class, 'index'])->name('addresses');
        // Route::get('/reviews', [ReviewController::class, 'index'])->name('reviews');
        // Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    });

    Route::middleware('role:vendor')->prefix('vendor')->name('vendor.')->group(function () {
        Route::get('/dashboard', VendorDashboardController::class)->name('dashboard');
        Route::resource('products', VendorProductController::class)->except('show');

        Route::get('/inventory', [VendorInventoryController::class, 'index'])->name('inventory');
        Route::put('/inventory/products/{product}', [VendorInventoryController::class, 'updateProduct'])->name('inventory.products.update');
        Route::put('/inventory/variants/{variant}', [VendorInventoryController::class, 'updateVariant'])->name('inventory.variants.update');

        Route::get('/orders', [VendorOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [VendorOrderController::class, 'show'])->name('orders.show');
        Route::put('/orders/{order}/items/{item}/status', [VendorOrderController::class, 'updateItemStatus'])->name('orders.items.status');

        Route::get('/sales', [VendorSalesController::class, 'index'])->name('sales');
        Route::get('/earnings', [VendorEarningsController::class, 'index'])->name('earnings');

        Route::get('/messages', [VendorConversationController::class, 'index'])->name('messages.index');
        Route::get('/messages/{conversation}', [VendorConversationController::class, 'show'])->name('messages.show');
        Route::post('/messages/{conversation}', [VendorConversationController::class, 'sendMessage'])->name('messages.send');

        Route::get('/store-profile', [VendorStoreProfileController::class, 'edit'])->name('store-profile.edit');
        Route::put('/store-profile', [VendorStoreProfileController::class, 'update'])->name('store-profile.update');
    });

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', AdminDashboardController::class)->name('dashboard');

        Route::get('/vendors', [AdminVendorController::class, 'index'])->name('vendors.index');
        Route::post('/vendors/{vendor}/approve', [AdminVendorController::class, 'approve'])->name('vendors.approve');
        Route::post('/vendors/{vendor}/reject', [AdminVendorController::class, 'reject'])->name('vendors.reject');
        Route::post('/vendors/{vendor}/suspend', [AdminVendorController::class, 'suspend'])->name('vendors.suspend');

        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::post('/users/{user}/toggle-status', [AdminUserController::class, 'toggleStatus'])->name('users.toggle-status');

        Route::get('/products', [AdminProductController::class, 'index'])->name('products.index');
        Route::put('/products/{product}/status', [AdminProductController::class, 'updateStatus'])->name('products.status');

        Route::get('/categories', [AdminCategoryController::class, 'index'])->name('categories.index');
        Route::post('/categories', [AdminCategoryController::class, 'store'])->name('categories.store');
        Route::put('/categories/{category}', [AdminCategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{category}', [AdminCategoryController::class, 'destroy'])->name('categories.destroy');

        Route::get('/banners', [BannerController::class, 'index'])->name('banners.index');
        Route::post('/banners', [BannerController::class, 'store'])->name('banners.store');
        Route::post('/banners/{banner}', [BannerController::class, 'update'])->name('banners.update');
        Route::delete('/banners/{banner}', [BannerController::class, 'destroy'])->name('banners.destroy');
        Route::post('/banners/{banner}/toggle', [BannerController::class, 'toggleActive'])->name('banners.toggle');

        Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
        Route::put('/orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('orders.status');

        Route::get('/coupons', [AdminCouponController::class, 'index'])->name('coupons.index');
        Route::post('/coupons', [AdminCouponController::class, 'store'])->name('coupons.store');
        Route::put('/coupons/{coupon}', [AdminCouponController::class, 'update'])->name('coupons.update');
        Route::delete('/coupons/{coupon}', [AdminCouponController::class, 'destroy'])->name('coupons.destroy');

        Route::get('/reviews', [AdminReviewController::class, 'index'])->name('reviews.index');
        Route::put('/reviews/{review}/status', [AdminReviewController::class, 'updateStatus'])->name('reviews.status');

        Route::get('/commissions', [AdminCommissionController::class, 'index'])->name('commissions.index');
        Route::get('/reports', [AdminReportController::class, 'index'])->name('reports.index');
        Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
    });
});
require __DIR__.'/auth.php';