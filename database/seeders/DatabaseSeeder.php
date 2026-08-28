<?php

namespace Database\Seeders;

use App\Models\AttributeValue;
use App\Models\Attribute as AttributeModel;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Conversation;
use App\Models\Coupon;
use App\Models\Message;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorCommission;
use App\Models\Wishlist;
use App\Models\WishlistItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ---------------------------------------------------------------
        // Demo accounts (fixed credentials so the README/demo video works)
        // ---------------------------------------------------------------
        $admin = User::factory()->admin()->create([
            'name' => 'Admin User',
            'email' => 'admin@markethub.test',
        ]);

        $customer = User::factory()->create([
            'name' => 'Jordan Customer',
            'email' => 'customer@markethub.test',
        ]);

        $vendorUser = User::factory()->vendor()->create([
            'name' => 'Sam Vendor',
            'email' => 'vendor@markethub.test',
        ]);

        $vendor = Vendor::factory()->create([
            'user_id' => $vendorUser->id,
            'shop_name' => 'Northwind Goods',
            'slug' => 'northwind-goods',
        ]);

        // A pending vendor, for testing the approval workflow.
        $pendingVendorUser = User::factory()->vendor()->create([
            'name' => 'Alex Pending',
            'email' => 'pending-vendor@markethub.test',
        ]);
        Vendor::factory()->pending()->create([
            'user_id' => $pendingVendorUser->id,
            'shop_name' => 'New Arrivals Co',
            'slug' => 'new-arrivals-co',
        ]);

        // 8 more approved vendors with random users, for a populated storefront.
        $vendors = Vendor::factory(8)->create();
        $vendors->push($vendor);

        // ---------------------------------------------------------------
        // Catalog: categories, attributes, products, variants, images
        // ---------------------------------------------------------------
        $categoryNames = [
            'Electronics', 'Fashion', 'Home & Living', 'Beauty & Health',
            'Sports & Outdoors', 'Toys & Games', 'Books', 'Groceries',
        ];

        $categories = collect($categoryNames)->map(fn ($name) => Category::create([
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => "Shop the best of {$name} from trusted vendors.",
            'status' => 'active',
        ]));

        $colorAttribute = AttributeModel::create(['name' => 'Color', 'slug' => 'color']);
        $sizeAttribute = AttributeModel::create(['name' => 'Size', 'slug' => 'size']);

        $colors = collect(['Black', 'White', 'Navy', 'Red'])->map(
            fn ($value) => AttributeValue::create(['attribute_id' => $colorAttribute->id, 'value' => $value])
        );
        $sizes = collect(['S', 'M', 'L', 'XL'])->map(
            fn ($value) => AttributeValue::create(['attribute_id' => $sizeAttribute->id, 'value' => $value])
        );

        $products = collect();

        $vendors->each(function (Vendor $vendor) use ($categories, &$products, $colors, $sizes) {
            Product::factory(6)
                ->for($vendor)
                ->create(['category_id' => $categories->random()->id])
                ->each(function (Product $product) use (&$products, $colors, $sizes) {
                    ProductImage::factory(3)->for($product)->create();

                    // Roughly a third of products get variants (e.g. apparel).
                    if (fake()->boolean(35)) {
                        foreach ($colors->random(2) as $color) {
                            $variant = ProductVariant::factory()->create([
                                'product_id' => $product->id,
                                'sku' => $product->sku.'-'.strtoupper(Str::random(4)),
                                'price' => $product->price,
                                'stock' => fake()->numberBetween(0, 50),
                            ]);
                            $variant->attributeValues()->attach($color->id);
                            $variant->attributeValues()->attach($sizes->random()->id);
                        }
                    }

                    $products->push($product);
                });
        });

        // A handful of featured products for the homepage.
        Product::whereIn('id', $products->random(6)->pluck('id'))->update(['featured' => true]);

        // ---------------------------------------------------------------
        // Coupons
        // ---------------------------------------------------------------
        Coupon::factory()->create([
            'code' => 'WELCOME10',
            'type' => 'percentage',
            'value' => 10,
            'minimum_amount' => 30,
        ]);
        Coupon::factory()->create([
            'code' => 'FLAT20',
            'type' => 'fixed',
            'value' => 20,
            'minimum_amount' => 100,
        ]);

        // ---------------------------------------------------------------
        // Demo customer's cart and wishlist
        // ---------------------------------------------------------------
        $cart = Cart::create(['user_id' => $customer->id]);
        foreach ($products->random(2) as $product) {
            CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'quantity' => fake()->numberBetween(1, 2),
                'price' => $product->currentPrice(),
            ]);
        }

        $wishlist = Wishlist::create(['user_id' => $customer->id]);
        foreach ($products->random(3) as $product) {
            WishlistItem::create(['wishlist_id' => $wishlist->id, 'product_id' => $product->id]);
        }

        // ---------------------------------------------------------------
        // A delivered order for the demo customer, so reviews are unlockable
        // and the vendor dashboard has real sales/commission data.
        // ---------------------------------------------------------------
        $orderProducts = $products->random(3);
        $subtotal = $orderProducts->sum(fn (Product $p) => $p->currentPrice());

        $order = Order::create([
            'user_id' => $customer->id,
            'order_number' => 'MH-'.strtoupper(Str::random(8)),
            'subtotal' => $subtotal,
            'discount' => 0,
            'shipping_fee' => 10,
            'total' => $subtotal + 10,
            'status' => 'delivered',
            'payment_status' => 'paid',
            'payment_method' => 'card',
            'shipping_address' => [
                'name' => $customer->name,
                'line1' => '123 Market Street',
                'city' => 'Lahore',
                'state' => 'Punjab',
                'postal_code' => '54000',
                'country' => 'Pakistan',
                'phone' => '+92 300 0000000',
            ],
        ]);

        foreach ($orderProducts as $product) {
            $orderItem = OrderItem::create([
                'order_id' => $order->id,
                'vendor_id' => $product->vendor_id,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'sku' => $product->sku,
                'quantity' => 1,
                'price' => $product->currentPrice(),
                'subtotal' => $product->currentPrice(),
                'status' => 'delivered',
            ]);

            $vendorForItem = $vendors->firstWhere('id', $product->vendor_id);
            $rate = (float) $vendorForItem->commission_rate;
            $commissionAmount = round($orderItem->subtotal * ($rate / 100), 2);

            VendorCommission::create([
                'vendor_id' => $product->vendor_id,
                'order_id' => $order->id,
                'order_item_id' => $orderItem->id,
                'order_amount' => $orderItem->subtotal,
                'commission_rate' => $rate,
                'commission_amount' => $commissionAmount,
                'vendor_amount' => $orderItem->subtotal - $commissionAmount,
                'status' => 'paid',
            ]);

            Review::create([
                'user_id' => $customer->id,
                'product_id' => $product->id,
                'order_id' => $order->id,
                'rating' => fake()->numberBetween(4, 5),
                'comment' => fake()->sentence(12),
                'status' => 'approved',
            ]);
        }

        // A few more historical orders for reporting/analytics variety.
        Order::factory(15)->create(['user_id' => $customer->id]);

        // ---------------------------------------------------------------
        // A demo conversation between the customer and the main vendor
        // ---------------------------------------------------------------
        $conversation = Conversation::create([
            'customer_id' => $customer->id,
            'vendor_id' => $vendor->id,
            'last_message_at' => now(),
        ]);

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $customer->id,
            'body' => 'Hi! Does this come in a larger size?',
        ]);
        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $vendorUser->id,
            'body' => 'Yes, we have M, L and XL in stock right now.',
        ]);

        $this->command->info('Demo accounts (password: "password"):');
        $this->command->info('  Admin:    admin@markethub.test');
        $this->command->info('  Vendor:   vendor@markethub.test');
        $this->command->info('  Customer: customer@markethub.test');
    }
}
