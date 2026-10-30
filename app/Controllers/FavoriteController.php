<?php

class FavoriteController extends Controller
{
    public function __construct(array $config)
    {
    }

    public function index(): void
    {
        // TODO: Resolve the session owner and list only their favorites.
        // TODO: Make a CSRF token available for guest and user favorite forms.
    }

    public function store(): void
    {
        // TODO: Read request data, validate CSRF and product ID, then resolve the owner.
        // TODO: Check product availability and prevent duplicate items.
    }

    public function destroy(): void
    {
        // TODO: Validate CSRF and product ID; remove only from the session owner's collection.
    }

    // ? Coordinate with the Cart model; remove the favorite only after adding succeeds.
    public function moveToCart(): void
    {
    }

    // ? Use the logged-in user ID or the server's current session ID for a guest.
    // TODO: Validate any logged-in account's current existence and active status.
    // Never accept user_id, session_id, or a collection ID from the request as ownership proof.
    private function resolveFavoriteId(bool $createIfMissing = false): ?int
    {
    }
}
