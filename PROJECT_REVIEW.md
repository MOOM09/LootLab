# Model and controller review

Date: 2026-10-31. Branch: `TEST`.

Scope: initial source review of non-AI models/controllers, with supporting route, bootstrap, middleware, view availability and schema checks, followed by Asmaa's fixes and guided manual tests on `TEST`. AI implementation changes are excluded. The initial review made no database changes; later database checks and changes were performed manually by Asmaa as recorded below. This is not a completed integration test suite or security audit.

Follow-up: AI code was subsequently reviewed read-only at the user's request. Findings are recorded separately in [AI_REPORT.md](AI_REPORT.md); no AI implementation was changed.

## Current status

- Small fixes below are applied in the working tree on `TEST`; changes remain uncommitted.
- Middleware assignments were checked across all 37 registered routes. Order listing, update, and deletion now require both authentication and admin access.
- Order detail ownership remains unresolved (R1), and checkout still trusts client-supplied financial data/status (R2).
- Guest favorites remain public by design. AI access findings are in the separate AI report; AI routes were not changed.
- Asmaa confirmed completion and success of the remaining guided manual tests listed below. Automated regression tests and full-project integration coverage remain outstanding.
- R6 fixes are implemented: password login/registration merge guest favorites before session regeneration; collection uniqueness is enforced in the schema, and creation methods return the existing collection ID on duplicate ownership. Manual evidence and remaining test gaps are recorded below.
- Asmaa confirmed Asem will handle cart routes and middleware (R4); this work is not assigned to Asmaa for implementation here.
- Guest-page follow-up: `GET /login` and `GET /register` now use `GuestMiddleware`. Active authenticated accounts redirect to the configured application's `/products` URL; missing, invalid, deleted, or inactive identities are cleared and can access the forms. Existing POST login/register guards continue returning 409 for authenticated sessions. Syntax and targeted checks with a stubbed user lookup passed; Asmaa also confirmed the guided logged-in redirect tests passed.

## Small fixes applied

1. `app/Models/OrderItem.php`: constructor now accepts `PDO`, matching `Order`; previously it called `new Database()` without required configuration and a nonexistent `getconnect()` method. Removed a leading newline that emitted output when loaded.
2. `routes/web.php`: added authentication and admin middleware to `POST /admin/categories/update`. A CSRF token alone does not authorize an admin operation.
3. `app/Models/Order.php`, `OrderItem.php`, `Review.php`: removed closing PHP tags to prevent trailing whitespace output before headers/JSON. These models are loaded by the main entry point.
4. `app/Controllers/ReviewController.php`: duplicate reviews now return HTTP 409 for MySQL duplicate-key error 1062; other database errors are rethrown. The schema permits one review per user/product.
5. `routes/web.php`: added authentication and admin middleware to `GET /orders`, `POST /orders/update`, and `POST /orders/delete`. Existing account-specific routes retain authentication; public browsing and guest favorites retain their intended access.
6. Asmaa updated `AuthController::login()` and `register()` to merge guest favorites before regenerating the session. Google login already used this sequence.
7. `database/lootlab.sql` now declares `uq_favorites_user` and `uq_favorites_session`. Asmaa's live database screenshot confirms both unique indexes are applied. Duplicate-collection queries returned no rows before applying the constraints.
8. Asmaa updated `Favorite::createForUser()` and `createForSession()` to use `ON DUPLICATE KEY UPDATE fav_id = LAST_INSERT_ID(fav_id)`, so repeated creation returns the existing collection ID. AuthController and Favorite passed PHP syntax checks after her edits.

## Larger issues for teammates

### Suggested fix owners from Git history

These are suggested owners based on non-merge commits and line authorship, not confirmed team assignments or blame. Evidence uses locally available history and cached remote branches; no fetch was performed. Uploading an initial placeholder or merging someone else's work does not establish feature ownership.

| Issue | Suggested fix owner | Branch | Supporting commits / rationale |
| --- | --- | --- | --- |
| R1: Order authorization | Ayman | `Ayman` | `39747a0`, `92c09a5`: order controller implementation and updates. |
| R2: Checkout totals, status, missing order items | Ayman, coordinating with Asem | `Ayman`, `Asem` | `39747a0`, `fd89726`, `92c09a5`: orders/items; `52764c9`: Asem's cart models. |
| R3: Missing product/category views and redirects | Dania | `Dania` | `3e3f787`, `2f75d2b`: product/category implementation; `a41b864`: product views. Shared home/error pages need team coordination. |
| R4: Missing cart routes | Asem (confirmed by Asmaa for routes and middleware) | `Asem` | `01b4312`: Asem's cart controller. Asmaa confirmed she asked Asem to implement routes/middleware. Missing code itself has no author to blame. |
| R5: Cart stock, pricing, validation | Asem | `Asem` | `52764c9`, `69b99ed`, `01b4312`: cart models, interface, controller. |
| R6: Favorites merge and collection uniqueness | Asmaa | `Asmaa` | `983a5d0`, `ab6b5e7`: favorites/auth integration. Schema changes require coordination. |
| R7: Product/category request validation | Dania | `Dania` | `3e3f787`, `2f75d2b`: main feature implementation. |
| R8: Empty implementations | Needs team assignment | Unassigned | `0f87016`, `8b1ea8d`: initial placeholders uploaded by Asmaa; this is not evidence she owns every unfinished feature. |
| R9: Architecture/construction consistency | Dania and Asmaa, with team review | `Dania`, `Asmaa` | `3e3f787`: feature construction; `ceb3324`: shared MVC infrastructure. This is a shared refactor recommendation. |

Already-fixed issues: OrderItem's broken constructor traces to Ayman's `39747a0` and `fd89726`; duplicate-review handling belongs with Ayman's review feature (`39747a0`, `92c09a5`). Middleware fixes span feature routes and shared authentication infrastructure and are already applied on `TEST`.

AI findings and their suggested owner are recorded in [AI_REPORT.md](AI_REPORT.md).

### R1 — Critical: order authorization

Files: `app/Controllers/OrderController.php`, `app/Models/Order.php`, `routes/web.php`.

Follow-up middleware fix: `GET /orders`, `POST /orders/update`, and `POST /orders/delete` now require both authentication and admin access. Listing returns every order, and update/delete operate on arbitrary order IDs, so these operations are restricted to administrators.

Remaining issue: `show` fetches orders only by ID; a logged-in user can read another user's order. Authentication does not establish ownership. Customer-specific listing and permitted customer mutations still require scoped queries and an agreed permissions policy.

Team action: define separate customer/admin permissions, scope customer queries and mutations by the session user ID at the SQL level, and restrict administrative listing and actions. Test with two distinct customers and an admin before release.

### R2 — Critical: checkout trusts client financial data and lifecycle

Files: `app/Controllers/OrderController.php`, `app/Models/Order.php`, `app/Models/OrderItem.php`.

Order creation accepts `total_amount`, `promo_discount`, and `status` from the client. It inserts only an order header: no order items are created, stock is not decremented, and the cart is not cleared. Updates also accept arbitrary nonempty status strings and replacement amounts; valid enum values can still be unauthorized transitions. Invalid enum values may raise database errors.

Team action: implement a checkout service that computes prices/promotions on the server and creates header/items, updates stock, and clears the cart in one transaction, with concurrent stock protection. Define cancellation/deletion and status-transition policy. Do not treat simple input validation as a complete repair.

### R3 — High: missing views and application navigation

Files: `app/Controllers/ProductController.php`, `app/Controllers/Admin/CategoryController.php`, `routes/web.php`.

Product controller references missing `products/show.php`, `products/edit.php`, and `errors/404.php`. Category controller references missing `admin/categories/index.php`, `show.php`, `create.php`, and `edit.php`, plus `errors/404.php`. Those paths fail at `require_once` rather than producing usable pages. `/` has no registered route.

Product/category redirects use root paths such as `/products` and `/admin/categories`; the documented XAMPP deployment uses `/projects/LootLab/public`, so redirects leave the application base path. Choose page or JSON contracts and finish the intended views/navigation consistently.

### R4 — High: cart controller is unreachable

Files: `app/Controllers/CartController.php`, `routes/web.php`.

The entry point loads CartController, but no cart routes are registered. Define endpoint methods/paths and add AuthMiddleware to every account cart route. Provide a CSRF token on the cart read endpoint for writes. Current identity resolution only casts the session ID and does not itself verify an existing active user.

### R5 — High: cart availability and pricing rules are incomplete

Files: `app/Models/Cart.php`, `app/Models/CartItem.php`, `app/Controllers/CartController.php`.

Direct cart additions/quantity updates do not enforce active product status or aggregate stock limits. Cart lines and totals use `products.price` even when `discount_price` is present. Confirm the intended sale-price policy and apply one pricing rule consistently through checkout.

Mutation methods return statement execution success even when the requested item does not belong to the cart or does not exist, so the API can report success without changing anything. Controller integer casts accept malformed values by truncation/coercion. Define validation and idempotency semantics. `CartInterface` says quantities below one remove an item, while controller update rejects those values; agree on a single contract.

Custom-design handling is excluded from this review because it overlaps the excluded feature scope.

### R6 — Medium: favorites login integration and collection uniqueness — fixes implemented

Files: `app/Controllers/AuthController.php`, `app/Models/Favorite.php`.

Original finding: password login and registration did not merge guest favorites before session regeneration, while Google login did. Asmaa has now added the merge to both flows. Manual password-login evidence shows the existing product 1 and guest product 2 together in account collection 2. Browser testing after Google login shows guest product 2 present in the resulting favorites. Asmaa subsequently confirmed the registration favorites-merge test also passed; its individual response was not supplied.

Original finding: multiple collections could be created for one user/session. Duplicate checks returned no rows; unique indexes are now present in the SQL schema and were confirmed on the live database. Both creation methods now use an upsert with `LAST_INSERT_ID` to return the same collection under duplicate ownership. A sequential SQL test returned IDs 9 and 9 (`same_id = 1`); a later read confirmed the test row was absent after rollback. Actual simultaneous-connection testing was offered and declined, so it is not marked passed. This does not establish that every concurrent merge/item-add scenario is covered.

### R7 — Medium: product/category request and validation contracts

Files: `app/Controllers/ProductController.php`, `app/Controllers/Admin/CategoryController.php`.

Update/delete parse the body but look for the ID only in `$_GET`. Invalid/missing IDs throw uncaught exceptions after setting HTTP 400. Decide and document whether IDs belong in the URL or body, and return consistent controlled errors.

Product/category fields cast arrays to strings rather than rejecting them. Product validation does not enforce schema lengths or verify subcategory existence, so database constraint failures can escape as server errors. Category writes catch every PDO exception as a possible duplicate; category deletion maps every database failure to a dependency conflict. Match specific constraint errors and rethrow unexpected failures.

### R8 — Medium: missing implementations

Empty models: `app/Models/Subcategory.php`, `app/Models/PromoCode.php`.

Empty controllers: `app/Controllers/HomeController.php`, `app/Controllers/Admin/DashboardController.php`, `OrderController.php`, `ProductController.php`, `PromoCodeController.php`, `ReviewController.php`.

These files define no class or behavior. Confirm feature ownership and requirements before implementing them. Existing route registration does not make these empty controllers functional.

### R9 — Medium: responsibility and construction consistency

`ProductController` handles both public browsing and admin writes, while the admin ProductController is empty. Admin CategoryController is in the Admin directory but uses a global class; Admin UserController has a namespace. This currently works through explicit includes, but inconsistent naming/loading makes future collisions and autoload failures likely.

Product/Category controllers ignore router-provided config and their models fall back to global configuration; other controllers inject a connection explicitly. Prefer explicit construction consistently. Checkout orchestration belongs in a service, with SQL in models and HTTP/session handling in controllers. These are coordinated refactors, not a reason to move working methods arbitrarily.

## Coverage by model/controller

| Area | Reviewed result |
| --- | --- |
| User / AuthController | R6 fixes implemented; password/Google favorites flow evidence supplied. Registration merge, banned login and logged-in guest-page redirects subsequently reported passed by Asmaa. |
| User / ProfileController | Profile update success and saved name verified manually. Guided negative validation, guest write and deletion cases subsequently reported passed by Asmaa. Google-account deletion deliberately returns 409 pending identity confirmation. |
| User / Admin UserController | User reported ordinary-account rejection and successful admin listing; guided self-ban/ban/unban tests subsequently reported passed. Actual concurrency remains untested. |
| Category / Admin CategoryController | Route protection repaired; missing views, redirects, validation, construction: R3, R7, R9. |
| Subcategory | Empty: R8. |
| Product / ProductController | Missing views, redirects, validation and mixed public/admin responsibilities: R3, R7, R9. |
| Cart / CartItem / CartController | Missing routes, stock/pricing and API contracts: R4, R5. |
| Favorite / FavoriteController | R6 fixes implemented with manual evidence below. Move stock check occurs before its transaction and is not locked; include concurrency in the cart stock design. Concurrent merge/item-add behavior remains unverified. |
| Order / OrderController | Listing/update/delete restricted to admins. Order detail ownership and checkout gaps remain: R1, R2. |
| OrderItem | Fatal constructor repaired. Not integrated into order creation: R2. |
| Review / ReviewController | Owned deletion and rating validation are present. Duplicate error handling repaired. `productExists` includes inactive products; confirm policy. Purchase eligibility is not enforced; confirm whether required. |
| PromoCode | Empty: R8; promo calculation/expiry checks need implementation requirements. |
| Home / empty admin controllers | R8. |

## Verification and next test phase

### Guided manual test results supplied by Asmaa

Results below distinguish supplied response evidence from user-reported outcomes. Saying "next" was not treated as proof of a pass; Asmaa subsequently confirmed she completed the outstanding guided checks successfully after they were enumerated. These are manual checks, not an automated regression suite. The confirmation does not cover tests that were declined or never performed, including actual simultaneous connections and existing-session revocation after an admin ban.

| Scenario | Status | Evidence / limit |
| --- | --- | --- |
| Guest favorite addition | Confirmed | POST response: success, "Product added to favorites." |
| Password-login favorites merge preserves old/new items | Confirmed | GET response shows products 1 and 2 in account collection `fav_id: 2` after login. |
| Registration favorites merge | Passed, user-reported | Asmaa confirmed completion/success after the remaining-test checklist; no individual response supplied. Code reviewed and syntax checked. |
| Google-login favorites merge | Confirmed in guided browser flow | After guest addition and Google login, supplied response contains product 2 in collection 12. Full pre-existing favorites/deduplication matrix not exercised. |
| Existing duplicate collections | None found in supplied query results | Both user and session duplicate queries returned zero rows. |
| Live unique indexes | Confirmed | Screenshot shows both `uq_favorites_user` and `uq_favorites_session` with `Non_unique = 0`. |
| Repeated collection creation SQL | Confirmed sequentially | `first_id = 9`, `second_id = 9`, `same_id = 1`. Subsequent test-row lookup returned nothing after rollback. This is not a simultaneous-request test or a PHP PDO integration test. |
| Actual concurrent connections | Not run | User declined this test. |
| Profile update and saved name | Confirmed | PUT returned "Profile updated successfully."; GET HTML displayed "Asmaa Test's Profile". Phone persistence was not independently shown. |
| Ordinary-user access to admin listing | User-reported rejection | User reported access not accepted; exact response/status was not supplied. |
| Admin access to user listing | User-reported success | User reported "done get all users" after creating/logging in with a local test admin. |
| Blank name / invalid phone / missing CSRF / guest profile write | Passed, user-reported | Asmaa confirmed the guided tests succeeded; individual responses not supplied. |
| Self-ban / ban / repeat ban / unban / repeat unban | Passed, user-reported | Asmaa confirmed the guided ban/unban checks succeeded; individual responses not supplied. |
| Wrong-password deletion / successful deletion / post-delete access | Passed, user-reported | Asmaa confirmed the guided account-deletion checks succeeded; individual responses not supplied. |
| Banned-account login | Passed, user-reported | Asmaa confirmed the guided banned-login check succeeded; individual response not supplied. |
| Existing-session access after admin ban | Not tested | Requires a separate test with a session established before the ban; not covered by testing a new login. |
| Logged-in GET login/register redirect | Passed, user-reported | Asmaa confirmed the guided redirects succeeded; individual 302/Location results not supplied. Middleware source/syntax and stubbed checks passed earlier. |

Asmaa performed database tests and created a local test admin manually. No account password, session cookie, or CSRF token is stored in this report. This documentation update does not modify application code or the database.

### Earlier checks and remaining work

- PHP 8.2.12 syntax checks passed for all 33 non-AI PHP files in Core, Middleware, Interfaces, Models and Controllers (including empty placeholders).
- Targeted PHP runtime checks passed: loading Order/OrderItem/Review produces no output; OrderItem retains the injected PDO; category update has both required middleware classes.
- Follow-up route inspection passed across all 37 registered routes, checking expected authentication/admin assignments and public browsing access. This inspected registrations without executing middleware against a database. `routes/web.php` also passed PHP syntax checking.
- Separate AI review: all 11 AI-related PHP files passed syntax checks. Findings and verification limits are in [AI_REPORT.md](AI_REPORT.md); no provider requests or AI implementation changes were made.
- Earlier review checks passed `git diff --check` and made no database writes or AI calls. Later user-run database/manual checks are listed above; a full application suite has not been run. Duplicate-review HTTP behavior still needs a database integration test.
- No existing test suite or test configuration was found in the reviewed repository. Test-case implementation is the next phase, after this initial check.
- Priority order: order access with guest/two users/admin; checkout totals/status/stock transaction; missing page/route smoke tests; cart stock/pricing/ownership; favorites merging/concurrency; duplicate reviews; auth/profile/admin-user behavior; invalid input and database constraint handling.
- Use an isolated test database with synthetic data; do not run destructive fixture setup against the application's existing database.
