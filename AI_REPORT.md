# AI review report

Date: 2026-10-31
Branch: `TEST`

Scope: read-only source review of AI controllers, services, model, views, entry points, and route integration. No AI code was changed. No Gemini requests, image generation, or database changes were performed.

## Findings

### Suggested fix owner from Git history

Suggested owner for AI-01 through AI-08: **MOOM09 / Tarifi**, branch `TARIFI`.

Evidence: `b527d20` introduced the AI shopping assistant, conversation model, catalog, and services; `4619112` added streaming; `afb47d5` added image generation and related services. Line authorship specifically traces the hardcoded `$userId = 1` to `b527d20`. These commits are authored by MOOM09; the repository also contains contributions/merges under the name Mohammad AL-Tarifi.

| Findings | Suggested lead | Coordination |
| --- | --- | --- |
| AI-01: Shared user identity | MOOM09 / Tarifi | Coordinate session/auth integration with Asmaa. |
| AI-02: Access and usage controls | MOOM09 / Tarifi | Coordinate middleware with Asmaa and deployment limits with the team. |
| AI-03: Demo product catalog | MOOM09 / Tarifi | Coordinate live product/pricing integration with Dania. |
| AI-04: Session locking | MOOM09 / Tarifi | Coordinate safe session updates with the auth owner. |
| AI-05: Response completion checks | MOOM09 / Tarifi | AI service implementation. |
| AI-06: Full history loading | MOOM09 / Tarifi | Conversation model/controller implementation. |
| AI-07: Helper/database construction | MOOM09 / Tarifi | Shared architecture changes require team agreement. |
| AI-08: Image backend and persistence | MOOM09 / Tarifi | Confirm product requirements; coordinate later cart/order integration with Asem/Ayman. |

These are suggested fix assignments based on locally available non-merge commits and line authorship, not confirmed team responsibilities or blame. Remote branch references are cached; no fetch was performed. Missing functionality and infrastructure policies cannot be attributed conclusively to a person from Git history alone.

### AI-01 — Critical: every chat visitor uses user ID 1

Location: `app/Controllers/AiController.php:12`, `routes/web.php`.

The controller hardcodes `$userId = 1`. Both AI chat routes are public. Conversation listing, reading, renaming, deletion, and saving therefore operate as user 1 for every visitor. Although the model checks ownership, all visitors supply the same identity. If user 1 does not exist, saving a new conversation also fails its user foreign key.

Recommended action: require authentication and resolve identity from the validated session. If guest chat is intended, give guest conversations separate server-controlled ownership rather than a shared user ID.

### AI-02 — High: generation endpoints lack access and usage controls

Location: `routes/web.php`, `public/aiImage.php`, `app/Controllers/AiImageController.php`.

Chat routes have no authentication middleware. The image entry point invokes its controller directly, bypassing router middleware. No application rate limits or per-user generation quotas were found in the reviewed code. CSRF tokens are present, but visitors can obtain their own tokens and repeatedly request external API or local GPU work.

Recommended action: define guest/account access policy and enforce rate limits, quotas, and appropriate concurrency limits at the entry points. Verify any infrastructure-level controls separately.

### AI-03 — Medium: product information is a hardcoded demo catalog

Location: `app/Services/AiProductCatalog.php:5`.

The catalog returns six fixed products rather than reading the application's product tables. The prompt identifies the data as experimental, but recommendations can still disagree with the storefront's actual product IDs, prices, availability, and stock.

Recommended action: use the current active-product catalog with the same pricing rules as the storefront, or clearly keep the feature marked as a demo until integration is complete.

### AI-04 — Medium: chat holds the session lock during generation

Location: `app/Controllers/AiController.php`, `app/Services/AiService.php:180`.

Chat keeps the PHP session open during an external request that can take up to 60 seconds. With PHP's usual locking session handler, other requests using that session can wait until generation finishes. The image controller already calls `session_write_close()` before its long-running work.

Recommended action: copy required session data, release the session lock before generation, and safely reopen/persist session changes afterward. Preserve the reply cache and account for concurrent requests.

### AI-05 — Medium: response completion checks are inconsistent

Location: `app/Services/AiService.php:171`, `app/Services/AiService.php:277`.

Streaming chat marks a response complete whenever any `finishReason` exists; it does not distinguish normal completion from a token-limit or other termination reason. A partial nonempty answer can therefore be saved as an ordinary completed exchange. The non-streaming chat path decodes the response without explicitly checking the HTTP status or completion reason. Image-prompt preparation already checks for `STOP`.

Recommended action: distinguish successful, truncated, blocked, and failed responses; apply consistent status/completion handling across streaming and non-streaming chat.

### AI-06 — Medium: full conversation histories are loaded before trimming

Location: `app/Models/AiConversation.php:26`, `app/Controllers/AiController.php:97`, `app/Controllers/AiController.php:164`.

`getMessages()` loads every message in the conversation. The controller then selects the last 20 for the provider request. Long conversations still increase database transfer, PHP memory use, and rendered page size. The full history is loaded again after saving an exchange.

Recommended action: separate paginated display history from a bounded query for provider context and fetch only the newly saved message when caching its raw reply.

### AI-07 — Structural: helper placement and database construction are inconsistent

Location: `app/Controllers/AiController.php:320`, `app/Services/AiDatabase.php`.

`escapeHtml()` is a global view helper declared in a controller file. `AiDatabase` duplicates database connection setup and reads environment variables directly rather than using the application's configured connection abstraction. These are maintainability issues, not confirmed runtime failures by themselves.

Recommended action: place rendering helpers in a shared view/helper layer and standardize explicit database construction in a coordinated refactor.

### AI-08 — Integration gaps: fixed image backend and no saved design

Location: `app/Services/AiImageService.php:27`, `app/Controllers/AiImageController.php`.

The image service calls the fixed URL `http://127.0.0.1:7861/generate`, requiring PhotoMaker on the same host/network namespace. Generated images are returned as base64 for display; the reviewed generation path does not persist an image or create a `custom_designs` record. Whether persistence is required needs product/team confirmation.

Recommended action: make backend configuration explicit and agree on design persistence, ownership, and later cart/order integration requirements before implementing them.

## Existing protections observed

- CSRF validation exists for chat mutations and image generation.
- Chat text and titles are escaped in HTML; streaming text uses `textContent`.
- Upload checks validate upload origin, size, MIME type, image readability, and dimensions.
- Conversation queries use prepared statements and ownership checks.
- User/assistant exchanges are saved in a database transaction.
- External requests have connection and execution timeouts.

## Verification and limits

All 11 AI-related PHP files passed PHP 8.2.12 syntax checks: two controllers, four services, one model, two views, and two public entry points.

Provider/model availability, actual streaming behavior, database permissions, PhotoMaker availability, deployment controls, and end-to-end generation were not tested. This report does not establish that those integrations work or fail. No files other than this report were changed for this reporting request.

## Suggested follow-up order

1. Fix shared chat identity and define endpoint access policy.
2. Add usage/concurrency controls.
3. Integrate the real product catalog.
4. Test response termination and failure handling.
5. Address session locking and history growth.
6. Agree on image persistence and perform the structural refactors.
