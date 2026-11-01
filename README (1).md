# LootLab

### Discover your fandom. Find your next collectible.

LootLab is a team e-commerce project for fans of anime, manga, comics, gaming, movies, and television. It brings fandom merchandise and an AI shopping assistant into one store experience.

From everyday accessories to display-worthy collectibles, LootLab is designed to help fans discover products inspired by the characters and worlds they love.

> **Status:** In development. This README describes the agreed project scope and the AI work discussed during development. Database support does not imply that every corresponding interface or workflow is complete.

## Contents

- [About the Store](#about-the-store)
- [Project Scope](#project-scope)
- [AI Shopping Assistant](#ai-shopping-assistant)
- [Technical Foundation](#technical-foundation)
- [Local Development](#local-development)
- [AI Module Structure](#ai-module-structure)
- [Development Notes](#development-notes)
- [Team Collaboration](#team-collaboration)

## About the Store

LootLab covers anime and manga, DC and Marvel, video games, movies, television series, and their characters.

The planned product range includes:

- Posters and stickers
- Mugs
- Clothing
- Keychains and accessories
- Collectibles

## Project Scope

The agreed commerce scope and database design cover the following areas:

| Area | Scope |
| --- | --- |
| Accounts | Users, administrator roles, and account activation status |
| Catalog | Categories, subcategories, products, pricing, discounts, and stock |
| Shopping cart | Carts, cart items, and product quantities |
| Promotions | Promo codes |
| Orders | Orders, order items, and order statuses |
| Community | Product reviews |
| Personalization | Favorites and customer points |
| AI conversations | User conversations and user/assistant messages |

Clothing size and color variants are outside the current database scope.

## AI Shopping Assistant

The AI module integrates Google Gemini through a PHP service to support shopping conversations within LootLab's fandom and merchandise domain.

Its instructions are designed to:

- Keep responses relevant to LootLab and its product categories.
- Respond in the language of the user's current message.
- Help users explore fandom-related merchandise through conversation.

The integration uses server-side requests, JSON payloads, and a system instruction to define the assistant's behavior. An initial successful API response was confirmed during development; broader behavior and database integration still require end-to-end verification.

**Product accuracy:** Live prices, stock availability, and product recommendations should only be treated as authoritative when grounded in the store database. A successful AI response alone does not verify catalog accuracy.

## Technical Foundation

| Component | Technology |
| --- | --- |
| Server-side development | PHP |
| Database | MySQL / MariaDB-compatible SQL database, named `lootlab` |
| Dependency management | Composer |
| AI integration | Google Gemini API |
| Environment configuration | `vlucas/phpdotenv` |
| API requests | PHP cURL and JSON |
| Local server workflow | Apache through XAMPP |

## Local Development

The following is the local setup outline discussed for the project. Database configuration paths and the main storefront entry point depend on the team's current repository layout.

### 1. Prepare the environment

Install PHP with cURL support, Composer, and MySQL or MariaDB. XAMPP can provide Apache, PHP, and the database server for local development.

### 2. Place the project in the web directory

For an XAMPP setup, place the project in its `htdocs` directory. Start Apache and the database service from the XAMPP control panel.

### 3. Install PHP dependencies

From the project root containing `composer.json`, run:

```bash
composer install
```

### 4. Prepare the database

Import the team's `lootlab.sql` schema into the local database server. Configure the application's database connection to use the `lootlab` database and your local credentials.

### 5. Configure Gemini

Create a `.env` file in the location loaded by the application's dotenv configuration and add:

```dotenv
GEMINI_API_KEY=your_gemini_api_key
```

Keep the real key private. Do not commit `.env` or expose the key in browser-side code. Any shared environment example should contain placeholders only.

### 6. Open the application

Access the project through Apache using its local folder name. The AI entry point discussed during development is:

```text
http://localhost/<project-folder>/public/ai.php
```

Replace `<project-folder>` with the actual directory name under `htdocs`. PHP pages must be served by the PHP-enabled web server.

## AI Module Structure

The following paths were discussed for the AI module; this is not a complete repository tree.

```text
app/
├── Controllers/
│   └── AiController.php
├── Services/
│   ├── AiService.php
│   └── AiDatabase.php
└── Views/
    └── ai/
        └── chat.php
public/
└── ai.php
```

| File | Intended responsibility |
| --- | --- |
| `public/ai.php` | Web entry point for the AI module |
| `AiController.php` | Coordinate chat requests and responses |
| `AiService.php` | Communicate with Gemini and configure assistant instructions |
| `AiDatabase.php` | Handle database access used by the AI module |
| `chat.php` | Present the chat interface |

## Development Notes

Before release, verify the complete user journey against the current implementation:

- Account access and administrator permissions
- Product browsing, prices, discounts, and stock
- Cart quantities, promo codes, and order totals
- Order creation and status handling
- Reviews, favorites, and points behavior
- AI responses in Arabic and English
- AI behavior for unrelated questions and unavailable product information
- Conversation persistence and separation between user accounts
- Useful error handling when the database or AI service is unavailable

This checklist describes verification work, not a claim that these checks have passed.

## Team Collaboration

LootLab is developed collaboratively. The team is coordinating around a shared wireframe to keep page layouts and interface patterns consistent.

Contributions should follow the team's agreed structure, keep private configuration out of version control, and document any changes that affect installation or database setup.

---

**LootLab — A home for fandom finds.**
