<?php
final class AdminPanelController
{
    private AdminPanelStore $store;
    private AdminPanelFiles $files;
    private array $config;
    private int $userId;
    public function __construct(array $config, string $root)
    {
        $this->config = $config;
        $this->userId = (int) $_SESSION["user_id"];
        $database = new Database($config["database"]);
        $this->store = new AdminPanelStore($database->getConnection());
        $this->files = new AdminPanelFiles($root, $config["app_url"]);
    }
    public function handle(): void
    {
        $method = $_SERVER["REQUEST_METHOD"] ?? "GET";
        $api = $_GET["api"] ?? null;
        $_SESSION["admin_panel_csrf"] ??= bin2hex(random_bytes(32));
        if ($api === null) {
            if ($method !== "GET") {
                Response::error("Method not allowed.", 405);
            }
            $user = $this->store->settings($this->userId);
            $bootstrap = [
                "appUrl" => rtrim($this->config["app_url"], "/"),
                "csrf" => $_SESSION["admin_panel_csrf"],
                "user" => $user,
                "avatar" => $this->files->avatar($this->userId),
                "preview" => false,
            ];
            require dirname(__DIR__, 2) . "/Views/admin/panel/index.php";
            return;
        }
        if (!is_string($api)) {
            Response::error("Unknown admin action.", 404);
        }
        try {
            if ($method === "GET") {
                $data = match ($api) {
                    "overview" => $this->store->overview($_GET),
                    "metadata" => $this->store->metadata(),
                    "users" => $this->store->users($_GET),
                    "user" => $this->store->user(AdminPanelStore::id($_GET["id"] ?? null)),
                    "products" => $this->store->products($_GET),
                    "product" => $this->store->product(AdminPanelStore::id($_GET["id"] ?? null)),
                    "orders" => $this->store->orders($_GET),
                    "order" => $this->store->order(AdminPanelStore::id($_GET["id"] ?? null)),
                    "reviews" => $this->store->reviews($_GET),
                    "promos" => $this->store->promos(),
                    "settings" => array_merge($this->store->settings($this->userId), [
                        "avatar" => $this->files->avatar($this->userId),
                    ]),
                    default => throw new OutOfBoundsException("Unknown admin action."),
                };
            } elseif ($method === "POST") {
                $contentType = strtolower(explode(";", $_SERVER["CONTENT_TYPE"] ?? "")[0]);
                if ($contentType === "application/json") {
                    $input = json_decode(file_get_contents("php://input"), true, 512, JSON_THROW_ON_ERROR);
                    if (!is_array($input)) {
                        throw new InvalidArgumentException("Invalid request body.");
                    }
                } else {
                    $input = $_POST;
                }
                $token = $input["csrf_token"] ?? null;
                if (!is_string($token) || !hash_equals($_SESSION["admin_panel_csrf"], $token)) {
                    Response::error("Your form expired. Reload this page and try again.", 403);
                }
                $data = match ($api) {
                    "user-status" => $this->store->setUserActive($input),
                    "product-save" => $this->store->saveProduct($input),
                    "product-archive" => $this->store->archiveProduct(
                        AdminPanelStore::id($input["id"] ?? null),
                    ),
                    "category-save" => $this->store->saveCategory($input),
                    "subcategory-save" => $this->store->saveSubcategory($input),
                    "product-type-save" => $this->store->saveProductType($input),
                    "product-type-delete" => $this->store->deleteProductType($input),
                    "category-delete" => $this->store->deleteCategory($input),
                    "products-move" => $this->store->moveProducts($input),
                    "review-delete" => $this->store->deleteReview(AdminPanelStore::id($input["id"] ?? null)),
                    "promo-save" => $this->store->savePromo($input),
                    "promo-expire" => $this->store->deletePromo(AdminPanelStore::id($input["id"] ?? null)),
                    "settings-save" => $this->store->saveSettings($this->userId, $input),
                    "avatar-save" => [
                        "avatar" => $this->files->saveAvatar(
                            $this->userId,
                            AdminPanelStore::text($input, "path", 500),
                        ),
                    ],
                    "upload" => $this->files->upload($_FILES["image"] ?? []),
                    default => throw new OutOfBoundsException("Unknown admin action."),
                };
            } else {
                Response::error("Method not allowed.", 405);
                return;
            }
            Response::success($data);
        } catch (JsonException | InvalidArgumentException $e) {
            Response::error($e->getMessage(), 422);
        } catch (OutOfBoundsException $e) {
            Response::error($e->getMessage(), 404);
        } catch (PDOException $e) {
            error_log((string) $e);
            $code = (int) ($e->errorInfo[1] ?? 0);
            Response::error(
                $code === 1062
                    ? "That name or code already exists."
                    : ($code === 1451
                        ? "This record is still in use. Move its related records first."
                        : "The database request could not be completed."),
                $code === 1062 || $code === 1451 ? 409 : 500,
            );
        } catch (Throwable $e) {
            error_log((string) $e);
            Response::error("The request could not be completed. Please try again.", 500);
        }
    }
}
