# Verification and scope

## Completed locally

- PHP 8.2 compatibility parsing for all five new PHP files.
- Actual PHP 8.5 WebAssembly syntax checks; PHP 8.2 remains the intended XAMPP minimum.
- 40 executable model checks with disposable SQLite fixtures. Only the test fixture adapts MySQL-specific SHOW COLUMNS, DATE_FORMAT, FOR UPDATE and DATE_SUB syntax; production queries remain MySQL/MariaDB SQL.
- 9 controller/middleware checks: reject guests, non-admins, inactive accounts, outdated sessions, missing/wrong CSRF, and GET mutations; verify private user fields, pagination and account deactivation.
- Browser QA with a separate, clearly labeled in-memory demo adapter: dashboard, ten-row pagination, customer search/detail, product creation and live preview, searchable category/subcategory selection, bulk product reassignment, order details, global search, promotion editor, review list, inventory filters and settings fields.
- Mobile layout at a 390 px iframe viewport: no document-wide horizontal overflow; navigation closes after selection; customer page shows ten records with table-local scrolling.
- JavaScript syntax checks.

## Must still be verified on the user's XAMPP installation

- Live MySQL/MariaDB connectivity and the exact installed database structure.
- Real browser sessions with the existing authentication system.
- Multipart image uploads, directory permissions and Apache .htaccess behavior.
- Profile password/photo changes using the user's own account.
- Storefront rendering and checkout behavior after editing prices, sale prices and stock.

No production database, live account, payment, customer image or favorite data was accessed in these checks. The preview adapter is not used by public/admin.php. Native MySQL integration was not executed here. The included Check-Admin.ps1 checks installation dependencies and PHP syntax without modifying the database or Git.
