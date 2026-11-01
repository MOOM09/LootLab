# Favorite stock notifications

This backend feature records database events when a favorited product crosses
the configured low-stock thresholds or returns from zero stock. It stores
events for later processing; it does not send email or make SMTP/PHPMailer
connections.

## Notification types

- `stock_reached_5`: a product's stock changes from above 5 to 5 or less.
- `stock_reached_1`: a product's stock changes from above 1 to 1 or less.
- `stock_restocked`: a product's stock changes from 0 to a positive quantity.

An update that jumps across both thresholds creates one event of each
applicable type. Events are addressed to the distinct users who currently
favorite that product. Guest favorites are excluded. Each event stores the
resulting `stock_quantity`.

## Database migration

For an existing LootLab database, apply the additive migration once:

```sh
mysql -u <database-user> -p lootlab < database/migrations/20261101_create_favorite_stock_notifications.sql
```

The migration creates `favorite_stock_notifications` if it does not already
exist and does not delete or recreate existing tables or data. Fresh database
setups can use the matching table definition in `database/lootlab.sql`.

## Processing pending events

Create `FavoriteStockNotificationService` with the application's shared PDO
connection. A downstream worker can retrieve pending events and record its
delivery result:

```php
$service = new FavoriteStockNotificationService($pdo);
$pending = $service->getPendingNotifications(100);

foreach ($pending as $notification) {
    // Send through a separately configured delivery system, if desired.
    // Do not mark the event sent until that system confirms success.
    $service->markNotificationAsSent((int) $notification['id']);
}
```

If processing fails, record a reason:

```php
$service->markNotificationAsFailed(
    (int) $notification['id'],
    'Delivery provider rejected the request.'
);
```

`getPendingNotifications()` defaults to 100 and permits limits from 1 through
500. The status methods only update records still in `pending`; they return
`false` if the event does not exist or is no longer pending.

## Application integration

The existing product update route locks and reads the old stock quantity,
updates product fields, and calls
`FavoriteStockNotificationService::handleStockChange()` in the same database
transaction. Both stock and events commit together or roll back together.
Unchanged stock does not create events. Create one UUID with
`createStockEventKey()` for the update and pass it to
`handleStockChange($productId, $previousStock, $newStock, $stockEventKey)`.
A unique constraint on recipient, product, notification type, and stock event
prevents duplicate records when event insertion is repeated within that stock
update.

The service methods available to a worker are:

- `getPendingNotifications(int $limit = 100): array`
- `markNotificationAsSent(int $notificationId): bool`
- `markNotificationAsFailed(int $notificationId, string $failureReason): bool`

SMTP, PHPMailer, email credentials, and email delivery are intentionally not
implemented.
