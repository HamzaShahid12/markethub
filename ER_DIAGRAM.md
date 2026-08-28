# MarketHub — Entity Relationship Diagram

GitHub renders Mermaid natively, so this diagram displays as-is on the
repo's page — no external tool needed. It covers the core commerce
schema from `database/migrations/` (Phase 1); the users/cache/jobs
tables are omitted since they're Laravel's own scaffolding, not
MarketHub's domain model.

```mermaid
erDiagram
    USERS ||--o| VENDORS : "has one"
    USERS ||--o{ ORDERS : "places"
    USERS ||--o| CARTS : "has one"
    USERS ||--o| WISHLISTS : "has one"
    USERS ||--o{ REVIEWS : "writes"
    USERS ||--o{ CONVERSATIONS : "starts (as customer)"
    USERS ||--o{ MESSAGES : "sends"

    VENDORS ||--o{ PRODUCTS : "sells"
    VENDORS ||--o{ ORDER_ITEMS : "fulfills"
    VENDORS ||--o{ VENDOR_COMMISSIONS : "earns"
    VENDORS ||--o{ CONVERSATIONS : "receives"

    CATEGORIES ||--o{ CATEGORIES : "parent of"
    CATEGORIES ||--o{ PRODUCTS : "classifies"

    PRODUCTS ||--o{ PRODUCT_IMAGES : "has"
    PRODUCTS ||--o{ PRODUCT_VARIANTS : "has"
    PRODUCTS ||--o{ REVIEWS : "receives"
    PRODUCTS ||--o{ CART_ITEMS : "added as"
    PRODUCTS ||--o{ WISHLIST_ITEMS : "saved as"
    PRODUCTS ||--o{ ORDER_ITEMS : "ordered as"

    ATTRIBUTES ||--o{ ATTRIBUTE_VALUES : "has"
    ATTRIBUTE_VALUES }o--o{ PRODUCT_VARIANTS : "product_variant_values"

    CARTS ||--o{ CART_ITEMS : "contains"
    WISHLISTS ||--o{ WISHLIST_ITEMS : "contains"

    ORDERS ||--o{ ORDER_ITEMS : "contains"
    ORDERS ||--o{ REVIEWS : "unlocks"
    ORDERS ||--o{ VENDOR_COMMISSIONS : "generates"
    ORDERS }o--o| COUPONS : "may apply"

    ORDER_ITEMS ||--o| VENDOR_COMMISSIONS : "generates one"
    ORDER_ITEMS }o--o| PRODUCT_VARIANTS : "may reference"

    CONVERSATIONS ||--o{ MESSAGES : "contains"

    USERS {
        bigint id PK
        string name
        string email UK
        enum role "customer|vendor|admin"
        enum status "active|suspended"
    }
    VENDORS {
        bigint id PK
        bigint user_id FK
        string shop_name
        string slug UK
        enum status "pending|approved|suspended|rejected"
        decimal commission_rate
    }
    CATEGORIES {
        bigint id PK
        bigint parent_id FK "nullable, self-referencing"
        string name
        string slug UK
    }
    PRODUCTS {
        bigint id PK
        bigint vendor_id FK
        bigint category_id FK
        string sku UK
        decimal price
        decimal sale_price "nullable"
        int stock
        enum status "draft|published|archived"
    }
    PRODUCT_VARIANTS {
        bigint id PK
        bigint product_id FK
        string sku UK
        decimal price "nullable, overrides product"
        int stock
    }
    ORDERS {
        bigint id PK
        bigint user_id FK
        bigint coupon_id FK "nullable"
        string order_number UK
        decimal total
        enum status "pending|processing|shipped|delivered|cancelled|refunded"
        json shipping_address
    }
    ORDER_ITEMS {
        bigint id PK
        bigint order_id FK
        bigint vendor_id FK
        bigint product_id FK "nullable, product may be deleted later"
        string product_name "historical snapshot"
        string sku "historical snapshot"
        decimal price "historical snapshot"
        enum status "pending|processing|shipped|delivered|cancelled"
    }
    VENDOR_COMMISSIONS {
        bigint id PK
        bigint vendor_id FK
        bigint order_id FK
        bigint order_item_id FK
        decimal commission_rate
        decimal commission_amount
        decimal vendor_amount
        enum status "pending|payable|paid"
    }
    REVIEWS {
        bigint id PK
        bigint user_id FK
        bigint product_id FK
        bigint order_id FK
        tinyint rating
        enum status "pending|approved|rejected"
    }
    COUPONS {
        bigint id PK
        string code UK
        enum type "fixed|percentage"
        decimal value
        int usage_limit "nullable"
    }
    CONVERSATIONS {
        bigint id PK
        bigint customer_id FK
        bigint vendor_id FK
    }
    MESSAGES {
        bigint id PK
        bigint conversation_id FK
        bigint sender_id FK
        text body
    }
```

## Notable design decisions this diagram makes visible

- **`order_items` denormalizes `product_name`, `sku`, and `price`**
  rather than only foreign-keying to `products` — section 6.3's
  business rule that historical orders must stay immutable even after
  a product is edited or deleted. `product_id` is nullable for exactly
  this reason.
- **`vendor_commissions` foreign-keys to both `order_id` and
  `order_item_id`** — one commission row per line item, not per order,
  because a single order can span multiple vendors and each vendor
  earns only on their own items.
- **`categories.parent_id` self-references `categories`** for the
  two-level category tree (top-level + subcategories) used throughout
  the storefront and admin.
- **`product_variant_values` is a pure pivot** (composite primary key,
  no own `id`) between `product_variants` and `attribute_values` — a
  variant is defined entirely by which attribute values it combines
  (e.g. Color: Navy + Size: M).
