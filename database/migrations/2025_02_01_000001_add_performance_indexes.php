<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 11 performance pass. Most frequently-filtered columns already
 * got an index for free from Phase 1's foreignId()->constrained() FKs
 * (vendor_id, product_id, category_id, etc. all index automatically)
 * plus the explicit composite indexes already on products
 * (vendor_id+status, category_id+status). This migration adds the
 * handful of composite indexes profiling turned up as missing for
 * queries introduced in later phases — sales/report date-range scans
 * grouped by vendor, and review moderation counts — added as a new
 * migration rather than editing Phase 1's, so nothing already shipped
 * is altered.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Vendor/admin sales & report date-range aggregation
        // (SUM(subtotal) grouped by day, filtered by vendor_id/date —
        // Vendor\SalesController, Admin\ReportController).
        Schema::table('order_items', function (Blueprint $table) {
            $table->index(['vendor_id', 'created_at']);
        });

        // Admin/vendor commission filtering by vendor + payout status
        // (Vendor\EarningsController, Admin\CommissionController).
        Schema::table('vendor_commissions', function (Blueprint $table) {
            $table->index(['vendor_id', 'status']);
        });

        // Review moderation queue counts by status, and the per-product
        // approved-reviews average used on every product page
        // (Admin\ReviewController, Product::approvedReviews()).
        Schema::table('reviews', function (Blueprint $table) {
            $table->index(['product_id', 'status']);
        });

        // GMV-over-time report (Admin\ReportController) filters by
        // payment_status and groups by DATE(created_at).
        Schema::table('orders', function (Blueprint $table) {
            $table->index(['payment_status', 'created_at']);
        });

        // Sort-by-popularity / sort-by-rating on the public product
        // listing (Storefront\ProductController@buildListing).
        Schema::table('products', function (Blueprint $table) {
            $table->index('sold_count');
            $table->index('rating_average');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex(['vendor_id', 'created_at']);
        });

        Schema::table('vendor_commissions', function (Blueprint $table) {
            $table->dropIndex(['vendor_id', 'status']);
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex(['product_id', 'status']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['payment_status', 'created_at']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['sold_count']);
            $table->dropIndex(['rating_average']);
        });
    }
};
