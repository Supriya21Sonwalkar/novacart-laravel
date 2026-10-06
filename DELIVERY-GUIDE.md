# Catalog and delivery features

Open http://127.0.0.1:8007/shop. Browsing is public. Shopping still requires sign-in and a complete customer profile.

## Catalog

Admin → Categories: use Parent ID to create subcategories. Parent filters include descendants. Cycles are rejected.
Admin → Products: choose delivery type, preparation/delivery minutes, unit, expiry date and refrigerated storage. Put search synonyms such as `milk,dudh,दूध` in Tags. Search matches product names, descriptions, tags, SKU, category descendants and brand names. Header suggestions update while typing. Price, brand, rating, availability, unit and delivery type filters can be combined.

## Configure service areas and delivery slots

Admin → Delivery operations: enter actual pincodes, opening hours, estimated travel minutes, express availability and dated slots with order capacities. Deactivate demo areas before operating a real store. Demo areas cover 400001/400002 and 411001/411002; they do not represent a real delivery service.

- Dairy: same-day express, at most 60 minutes, only with an available active rider, valid stock, sufficient estimated travel time and opening hours.
- Fresh: available same-day scheduled slots or express.
- Grocery: scheduled slots or express.
- Standard: scheduled slots at least 24 hours away.
- Mixed carts: only times compatible with all items; separate incompatible fresh and standard items into different orders.
- Expiry dates and available stock are rechecked at checkout. No payment or stock is consumed if reservation fails.
- Slot capacity is reserved inside the checkout transaction under a service-area lock. Cancelled/refunded orders release availability.

Choose the address first; delivery options refresh for its pincode. All selections are checked again by the server at order placement. Existing standard-product orders remain viewable.

## Rider and tracking

Create a delivery partner under Admin → Users using the delivery partner role. Sign in at the normal login page; this role goes to /rider and has no administration permissions by default.

Local demo account: rider@novacart.test / DemoRider!2026. Use only for testing; replace it before deployment.

Express orders automatically reserve an available rider. Other orders can be assigned under Delivery operations. A rider cannot be assigned two active orders. Rider stages: Picked up → On the way → Delivered. Admin can set Confirmed → Packing → Packed before pickup.

On the rider page press Start sharing live location and allow device location access. Keep the page open. Customer order tracking refreshes every 10 seconds and shows a map, rider name, update time and rider-entered ETA. Stop sharing closes the watch. Closed orders reject new GPS updates and hide location from the customer. Only the assigned rider may post GPS; only the owning customer or order administrator may read tracking.

The current ETA is an operational/rider estimate, not traffic-aware routing. Browser GPS is a foreground prototype; continuous background phone tracking needs a rider mobile app. Remote phone access needs a deployed HTTPS server: 127.0.0.1 on a phone points to that phone, not this computer. Payments remain in test mode. A live launch also needs actual stock, dispatch staff, delivery partners, payment and messaging providers, HTTPS and a supported PHP/Laravel version.

Run `php artisan db:seed --class=GroceryDemoSeeder` to add demonstration groceries, areas, rider and upcoming slots without replacing existing products/users. Run it again to replenish upcoming demo slots.

Tests: `php vendor/phpunit/phpunit/phpunit` (SQLite in-memory, does not alter MySQL data).
