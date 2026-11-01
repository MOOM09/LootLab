<?php
/** Read/write store for the separate admin panel. All callers must be authenticated admins. */
final class AdminPanelStore
{
    private PDO $db;
    private string $subcategoryColumn;
    public function __construct(PDO $db)
    {
        $this->db = $db;
        $columns = $this->db->query("SHOW COLUMNS FROM products")->fetchAll(PDO::FETCH_COLUMN);
        $this->subcategoryColumn = in_array("subcategorys_id", $columns, true)
            ? "subcategorys_id"
            : "subcategory_id";
        if (!in_array($this->subcategoryColumn, $columns, true)) {
            throw new RuntimeException("The product subcategory column is missing.");
        }
    }
    private function rows(string $sql, array $parameters = []): array
    {
        $q = $this->db->prepare($sql);
        $q->execute($parameters);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }
    private function row(string $sql, array $parameters = []): ?array
    {
        return $this->rows($sql, $parameters)[0] ?? null;
    }
    private function run(string $sql, array $parameters = []): void
    {
        $q = $this->db->prepare($sql);
        $q->execute($parameters);
    }
    public static function text(array $data, string $key, int $max = 150): string
    {
        $v = $data[$key] ?? "";
        if (!is_string($v) || mb_strlen(trim($v)) > $max) {
            throw new InvalidArgumentException("Invalid " . str_replace("_", " ", $key) . ".");
        }
        return trim($v);
    }
    public static function id(mixed $value): int
    {
        $id =
            is_string($value) || is_int($value)
                ? filter_var($value, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]])
                : false;
        if ($id === false) {
            throw new InvalidArgumentException("A valid ID is required.");
        }
        return $id;
    }
    private static function money(mixed $value): string
    {
        if (!is_scalar($value) || !preg_match("/\A\d{1,8}(?:\.\d{1,2})?\z/", (string) $value)) {
            throw new InvalidArgumentException("Enter a valid amount with up to two decimal places.");
        }
        return number_format((float) $value, 2, ".", "");
    }
    private function dated(array $query, string $column, array &$parameters): string
    {
        $period = self::text($query, "period", 10);
        if ($period === "today") {
            $parameters[] = date("Y-m-d 00:00:00");
            return " AND {$column} >= ?";
        }
        $days = ["week" => 7, "month" => 30][$period] ?? null;
        if ($days !== null) {
            $parameters[] = date("Y-m-d H:i:s", time() - $days * 86400);
            return " AND {$column} >= ?";
        }
        return "";
    }
    private function paginate(string $countSql, string $selectSql, array $parameters, array $query): array
    {
        $total = (int) ($this->row($countSql, $parameters)["n"] ?? 0);
        $pages = max(1, (int) ceil($total / 10));
        $page = min($pages, max(1, (int) ($query["page"] ?? 1)));
        $offset = ($page - 1) * 10;
        return [
            "items" => $this->rows($selectSql . " LIMIT 10 OFFSET {$offset}", $parameters),
            "total" => $total,
            "page" => $page,
            "pages" => $pages,
            "per_page" => 10,
        ];
    }
    public function metadata(): array
    {
        $col = $this->subcategoryColumn;
        return [
            "categories" => $this->rows("SELECT c.id,c.name,c.image,COUNT(p.id) AS products_count,
                COALESCE(SUM(p.stock_quantity),0) AS stock_quantity
                FROM categories c LEFT JOIN subcategories s ON s.category_id=c.id
                LEFT JOIN products p ON p.{$col}=s.id AND p.is_active=1
                GROUP BY c.id,c.name,c.image ORDER BY c.name,c.id"),
            "subcategories" => $this->rows("SELECT id,category_id,name FROM subcategories ORDER BY name,id"),
        ];
    }
    public function users(array $query): array
    {
        $parameters = [];
        $where = " WHERE u.role='user'";
        $search = self::text($query, "q", 150);
        if ($search !== "") {
            $where .= " AND (u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
            array_push($parameters, "%{$search}%", "%{$search}%", "%{$search}%");
        }
        $where .= $this->dated($query, "u.created_at", $parameters);
        $active = self::text($query, "active", 10);
        if (in_array($active, ["0", "1"], true)) {
            $where .= " AND u.is_active=?";
            $parameters[] = $active;
        }
        $orders = self::text($query, "orders", 10);
        if ($orders === "yes") {
            $where .= " AND EXISTS (SELECT 1 FROM orders o WHERE o.user_id=u.id)";
        }
        if ($orders === "no") {
            $where .= " AND NOT EXISTS (SELECT 1 FROM orders o WHERE o.user_id=u.id)";
        }
        $result = $this->paginate(
            "SELECT COUNT(*) n FROM users u" . $where,
            'SELECT u.id,u.name,u.email,u.phone,u.is_active,u.created_at,
            (SELECT COUNT(*) FROM orders o WHERE o.user_id=u.id) AS orders_count
            FROM users u' .
                $where .
                " ORDER BY u.created_at DESC,u.id DESC",
            $parameters,
            $query,
        );
        $result["summary"] = $this->row("SELECT COUNT(*) AS total,COALESCE(SUM(is_active=1),0) AS active,
            COALESCE(SUM(created_at >= CURRENT_DATE),0) AS today FROM users WHERE role='user'");
        return $result;
    }
    public function user(int $id): array
    {
        // Deliberate allowlist: never read a customer's photo, custom designs, favorites or password.
        $user = $this->row(
            "SELECT id,name,email,phone,is_active,created_at,updated_at,points FROM users WHERE id=? AND role='user'",
            [$id],
        );
        if ($user === null) {
            throw new OutOfBoundsException("Customer not found.");
        }
        $user["orders"] = $this->rows(
            "SELECT id,total_amount,status,created_at FROM orders WHERE user_id=? ORDER BY created_at DESC,id DESC LIMIT 10",
            [$id],
        );
        $user["summary"] = $this->row(
            "SELECT COUNT(*) AS orders_count,COALESCE(SUM(CASE WHEN status='completed' THEN total_amount ELSE 0 END),0) AS completed_value FROM orders WHERE user_id=?",
            [$id],
        );
        return $user;
    }
    public function setUserActive(array $input): array
    {
        $id = self::id($input["id"] ?? null);
        if (!in_array($input["active"] ?? null, ["0", "1", 0, 1], true)) {
            throw new InvalidArgumentException("Select an account status.");
        }
        $active = (int) $input["active"];
        $this->user($id);
        // Expire all earlier sessions on either status change. Reactivation never revives an old session.
        $this->run(
            "UPDATE users SET is_active=?,session_version=session_version+1 WHERE id=? AND role='user' AND is_active<>?",
            [$active, $id, $active],
        );
        return ["id" => $id, "active" => $active];
    }
    public function products(array $query): array
    {
        $col = $this->subcategoryColumn;
        $parameters = [];
        $where = " WHERE 1=1";
        $search = self::text($query, "q", 150);
        if ($search !== "") {
            $where .= " AND (p.name LIKE ? OR p.description LIKE ?)";
            array_push($parameters, "%{$search}%", "%{$search}%");
        }
        $active = self::text($query, "active", 10);
        if ($active !== "all") {
            $where .= " AND p.is_active=?";
            $parameters[] = $active === "0" ? 0 : 1;
        }
        foreach (["category_id" => "c.id", "subcategory_id" => "s.id"] as $key => $column) {
            if (!empty($query[$key])) {
                $where .= " AND {$column}=?";
                $parameters[] = self::id($query[$key]);
            }
        }
        $where .= $this->dated($query, "p.created_at", $parameters);
        $stock = self::text($query, "stock", 10);
        if ($stock === "attention") {
            $where .= " AND p.stock_quantity<=5";
        }
        if ($stock === "low") {
            $where .= " AND p.stock_quantity BETWEEN 1 AND 5";
        }
        if ($stock === "out") {
            $where .= " AND p.stock_quantity=0";
        }
        $sorts = [
            "newest" => "p.created_at DESC,p.id DESC",
            "oldest" => "p.created_at,p.id",
            "price-desc" => "COALESCE(p.discount_price,p.price) DESC,p.id DESC",
            "price-asc" => "COALESCE(p.discount_price,p.price),p.id DESC",
            "sold-desc" => "units_sold DESC,p.id DESC",
            "sold-asc" => "units_sold,p.id DESC",
        ];
        $sort = $sorts[self::text($query, "sort", 20)] ?? $sorts["newest"];
        $joins = " FROM products p JOIN subcategories s ON s.id=p.{$col} JOIN categories c ON c.id=s.category_id";
        return $this->paginate(
            "SELECT COUNT(*) n" . $joins . $where,
            "SELECT p.id,p.name,p.description,p.price,p.discount_price,p.image,p.stock_quantity,p.is_active,p.created_at,
            s.id AS subcategory_id,s.name AS subcategory_name,c.id AS category_id,c.name AS category_name,
            COALESCE((SELECT SUM(oi.quantity) FROM order_items oi JOIN orders o ON o.id=oi.order_id
              WHERE oi.product_id=p.id AND o.status IN ('processing','shipped','completed')),0) AS units_sold" .
                $joins .
                $where .
                " ORDER BY " .
                $sort,
            $parameters,
            $query,
        );
    }
    public function product(int $id): array
    {
        $col = $this->subcategoryColumn;
        $row = $this->row(
            "SELECT p.*,s.category_id,s.name AS subcategory_name,p.{$col} AS subcategory_id
            FROM products p JOIN subcategories s ON s.id=p.{$col} WHERE p.id=?",
            [$id],
        );
        if ($row === null) {
            throw new OutOfBoundsException("Product not found.");
        }
        return $row;
    }
    public function saveProduct(array $input): array
    {
        $id = !empty($input["id"]) ? self::id($input["id"]) : null;
        if ($id !== null) {
            $this->product($id);
        }
        $name = self::text($input, "name");
        $description = self::text($input, "description", 20000);
        if ($name === "" || $description === "") {
            throw new InvalidArgumentException("Product name and description are required.");
        }
        $price = self::money($input["price"] ?? "");
        $discount = ($input["discount_price"] ?? "") === "" ? null : self::money($input["discount_price"]);
        if ($discount !== null && (float) $discount >= (float) $price) {
            throw new InvalidArgumentException("The sale price must be lower than the regular price.");
        }
        $stock = filter_var($input["stock_quantity"] ?? null, FILTER_VALIDATE_INT, [
            "options" => ["min_range" => 0, "max_range" => 100000000],
        ]);
        if ($stock === false) {
            throw new InvalidArgumentException("Enter a valid stock quantity.");
        }
        $categoryId = self::id($input["category_id"] ?? null);
        $subcategoryId = self::id($input["subcategory_id"] ?? null);
        if (
            $this->row("SELECT id FROM subcategories WHERE id=? AND category_id=?", [
                $subcategoryId,
                $categoryId,
            ]) === null
        ) {
            throw new InvalidArgumentException("Choose a subcategory belonging to the selected category.");
        }
        $image = self::text($input, "image", 500);
        if ($image !== "" && !preg_match("~\A(?:https?://|/?(?:uploads|images)/)~i", $image)) {
            throw new InvalidArgumentException("Use an HTTP image URL or an uploaded image.");
        }
        $active = ($input["is_active"] ?? "1") === "0" ? 0 : 1;
        $values = [$subcategoryId, $name, $description, $price, $discount, $image ?: null, $stock, $active];
        $col = $this->subcategoryColumn;
        if ($id === null) {
            $this->run(
                "INSERT INTO products ({$col},name,description,price,discount_price,image,stock_quantity,is_active) VALUES (?,?,?,?,?,?,?,?)",
                $values,
            );
            $id = (int) $this->db->lastInsertId();
        } else {
            $values[] = $id;
            $this->run(
                "UPDATE products SET {$col}=?,name=?,description=?,price=?,discount_price=?,image=?,stock_quantity=?,is_active=? WHERE id=?",
                $values,
            );
        }
        require_once __DIR__ . '/../Services/ContentTranslationService.php';
        (new ContentTranslationService($this->db))->save('product', $id, [
            'name' => $name, 'description' => $description
        ]);
        return $this->product($id);
    }
    public function archiveProduct(int $id): array
    {
        $this->product($id);
        $this->run("UPDATE products SET is_active=0 WHERE id=?", [$id]);
        return ["id" => $id];
    }
    public function saveCategory(array $input): array
    {
        $name = self::text($input, "name", 100);
        if ($name === "") {
            throw new InvalidArgumentException("A category name is required.");
        }
        $id = !empty($input["id"]) ? self::id($input["id"]) : null;
        if ($id === null) {
            $this->run("INSERT INTO categories (name) VALUES (?)", [$name]);
            $id = (int) $this->db->lastInsertId();
        } else {
            if ($this->row("SELECT id FROM categories WHERE id=?", [$id]) === null) {
                throw new OutOfBoundsException("Category not found.");
            }
            $this->run("UPDATE categories SET name=? WHERE id=?", [$name, $id]);
        }
        require_once __DIR__ . '/../Services/ContentTranslationService.php';
        (new ContentTranslationService($this->db))->save('category', $id, ['name' => $name]);
        return ["id" => $id, "name" => $name];
    }
    public function saveSubcategory(array $input): array
    {
        $name = self::text($input, "name", 100);
        $categoryId = self::id($input["category_id"] ?? null);
        if ($name === "" || $this->row("SELECT id FROM categories WHERE id=?", [$categoryId]) === null) {
            throw new InvalidArgumentException("Select a category and enter a subcategory name.");
        }
        $id = !empty($input["id"]) ? self::id($input["id"]) : null;
        if ($id === null) {
            $this->run("INSERT INTO subcategories (category_id,name) VALUES (?,?)", [$categoryId, $name]);
            $id = (int) $this->db->lastInsertId();
        } else {
            $old = $this->row("SELECT category_id FROM subcategories WHERE id=?", [$id]);
            if ($old === null) {
                throw new OutOfBoundsException("Subcategory not found.");
            }
            if ((int) $old["category_id"] !== $categoryId) {
                throw new InvalidArgumentException(
                    "Move products individually; a subcategory cannot be silently moved between categories.",
                );
            }
            $this->run("UPDATE subcategories SET name=? WHERE id=?", [$name, $id]);
        }
        require_once __DIR__ . '/../Services/ContentTranslationService.php';
        (new ContentTranslationService($this->db))->save('subcategory', $id, ['name' => $name]);
        return ["id" => $id, "name" => $name, "category_id" => $categoryId];
    }
    public function deleteCategory(array $input): array
    {
        $id = self::id($input["id"] ?? null);
        $type = self::text($input, "type", 20);
        if ($type === "subcategory") {
            $col = $this->subcategoryColumn;
            if ($this->row("SELECT id FROM products WHERE {$col}=? LIMIT 1", [$id]) !== null) {
                throw new InvalidArgumentException(
                    "Move all products, including archived products, before deleting this subcategory.",
                );
            }
            $this->run("DELETE FROM subcategories WHERE id=?", [$id]);
        } else {
            if ($this->row("SELECT id FROM subcategories WHERE category_id=? LIMIT 1", [$id]) !== null) {
                throw new InvalidArgumentException(
                    "Remove empty subcategories or move their products before deleting this category.",
                );
            }
            $this->run("DELETE FROM categories WHERE id=?", [$id]);
        }
        return ["id" => $id];
    }
    public function moveProducts(array $input): array
    {
        $ids = $input["ids"] ?? null;
        if (!is_array($ids) || count($ids) < 1 || count($ids) > 100) {
            throw new InvalidArgumentException("Select between 1 and 100 products.");
        }
        $ids = array_values(array_unique(array_map([self::class, "id"], $ids)));
        $sub = self::id($input["subcategory_id"] ?? null);
        if ($this->row("SELECT id FROM subcategories WHERE id=?", [$sub]) === null) {
            throw new InvalidArgumentException("Choose a destination subcategory.");
        }
        $col = $this->subcategoryColumn;
        $marks = implode(",", array_fill(0, count($ids), "?"));
        $this->run("UPDATE products SET {$col}=? WHERE id IN ({$marks})", array_merge([$sub], $ids));
        return ["moved" => count($ids)];
    }
    public function orders(array $query): array
    {
        $parameters = [];
        $where = " WHERE 1=1";
        $search = self::text($query, "q", 150);
        if ($search !== "") {
            $where .= " AND (u.name LIKE ? OR u.email LIKE ? OR CAST(o.id AS CHAR)=?)";
            array_push($parameters, "%{$search}%", "%{$search}%", ltrim($search, "#"));
        }
        $where .= $this->dated($query, "o.created_at", $parameters);
        $status = self::text($query, "status", 20);
        if (in_array($status, ["pending", "processing", "shipped", "completed", "cancelled"], true)) {
            $where .= " AND o.status=?";
            $parameters[] = $status;
        }
        if (!empty($query["user_id"])) {
            $where .= " AND o.user_id=?";
            $parameters[] = self::id($query["user_id"]);
        }
        return $this->paginate(
            "SELECT COUNT(*) n FROM orders o JOIN users u ON u.id=o.user_id" . $where,
            'SELECT o.id,o.user_id,o.total_amount,o.promo_discount,o.status,o.created_at,u.name,u.email,
            (SELECT SUM(quantity) FROM order_items oi WHERE oi.order_id=o.id) AS items_count
            FROM orders o JOIN users u ON u.id=o.user_id' .
                $where .
                " ORDER BY o.created_at DESC,o.id DESC",
            $parameters,
            $query,
        );
    }
    public function order(int $id): array
    {
        $order = $this->row(
            "SELECT o.id,o.user_id,o.promo_discount,o.total_amount,o.status,o.shipping_address,o.delivery_lat,o.delivery_lng,o.meeting_point_label,o.phone,o.created_at,u.name,u.email FROM orders o JOIN users u ON u.id=o.user_id WHERE o.id=?",
            [$id],
        );
        if ($order === null) {
            throw new OutOfBoundsException("Order not found.");
        }
        // Do not expose face images or generated design images in the admin customer view.
        $order["items"] = $this->rows(
            "SELECT oi.id,oi.product_id,oi.quantity,oi.unit_price,p.name,p.image FROM order_items oi JOIN products p ON p.id=oi.product_id WHERE oi.order_id=? ORDER BY oi.id",
            [$id],
        );
        return $order;
    }
    public function reviews(array $query): array
    {
        $parameters = [];
        $where = " WHERE 1=1";
        $search = self::text($query, "q", 150);
        if ($search !== "") {
            $where .= " AND (p.name LIKE ? OR u.name LIKE ? OR r.comment LIKE ?)";
            array_push($parameters, "%{$search}%", "%{$search}%", "%{$search}%");
        }
        return $this->paginate(
            "SELECT COUNT(*) n FROM reviews r JOIN products p ON p.id=r.product_id JOIN users u ON u.id=r.user_id" .
                $where,
            "SELECT r.id,r.rating,r.comment,r.created_at,u.name,p.name AS product_name FROM reviews r JOIN products p ON p.id=r.product_id JOIN users u ON u.id=r.user_id" .
                $where .
                " ORDER BY r.created_at DESC,r.id DESC",
            $parameters,
            $query,
        );
    }
    public function deleteReview(int $id): array
    {
        $this->run("DELETE FROM reviews WHERE id=?", [$id]);
        return ["id" => $id];
    }
    public function promos(): array
    {
        return [
            "items" => $this->rows(
                "SELECT promo_id AS id,code,discount_type,discount_value,expires_at,created_at FROM promo_codes ORDER BY created_at DESC,promo_id DESC",
            ),
        ];
    }
    public function savePromo(array $input): array
    {
        $code = strtoupper(self::text($input, "code", 100));
        if (!preg_match("/\A[A-Z0-9_-]{3,100}\z/", $code)) {
            throw new InvalidArgumentException(
                "Use at least three letters, numbers, hyphens or underscores for the code.",
            );
        }
        $type = self::text($input, "discount_type", 20);
        if (!in_array($type, ["percentage", "fixed"], true)) {
            throw new InvalidArgumentException("Choose a discount type.");
        }
        $value = self::money($input["discount_value"] ?? "");
        if ((float) $value <= 0 || ($type === "percentage" && (float) $value > 100)) {
            throw new InvalidArgumentException("Enter a positive discount; percentages cannot exceed 100.");
        }
        $expires = self::text($input, "expires_at", 30);
        $date = null;
        if ($expires !== "") {
            $d = DateTimeImmutable::createFromFormat("!Y-m-d\TH:i", $expires);
            if ($d === false || $d->format("Y-m-d\TH:i") !== $expires) {
                throw new InvalidArgumentException("Enter a valid expiration date.");
            }
            $date = $d->format("Y-m-d H:i:s");
        }
        if (empty($input["id"])) {
            $this->run(
                "INSERT INTO promo_codes (code,discount_type,discount_value,expires_at) VALUES (?,?,?,?)",
                [$code, $type, $value, $date],
            );
            $id = (int) $this->db->lastInsertId();
        } else {
            $id = self::id($input["id"]);
            $this->run(
                "UPDATE promo_codes SET code=?,discount_type=?,discount_value=?,expires_at=? WHERE promo_id=?",
                [$code, $type, $value, $date, $id],
            );
        }
        return ["id" => $id];
    }
    public function deletePromo(int $id): array
    {
        $this->run("UPDATE promo_codes SET expires_at=DATE_SUB(NOW(),INTERVAL 1 SECOND) WHERE promo_id=?", [
            $id,
        ]);
        return ["id" => $id];
    }
    public function overview(array $query): array
    {
        $range = self::text($query, "range", 10);
        $days = match ($range) { "7d" => 7, "30d" => 30, "33d" => 33, default => 0 };
        $start = $days
            ? new DateTimeImmutable("-" . ($days - 1) . " days midnight")
            : new DateTimeImmutable("first day of -11 months midnight");
        $format = $days ? "%Y-%m-%d" : "%Y-%m";
        $orderSeries = $this->rows(
            "SELECT DATE_FORMAT(created_at,'{$format}') AS bucket,COUNT(*) AS orders,
            COALESCE(SUM(CASE WHEN status='completed' THEN total_amount ELSE 0 END),0) AS completed,
            COALESCE(SUM(CASE WHEN status<>'cancelled' THEN total_amount ELSE 0 END),0) AS ordered
            FROM orders WHERE created_at>=? GROUP BY bucket ORDER BY bucket",
            [$start->format("Y-m-d H:i:s")],
        );
        $customerSeries = $this->rows(
            "SELECT DATE_FORMAT(created_at,'{$format}') AS bucket,COUNT(*) n FROM users WHERE role='user' AND created_at>=? GROUP BY bucket",
            [$start->format("Y-m-d H:i:s")],
        );
        $series = [];
        $byOrder = array_column($orderSeries, null, "bucket");
        $byCustomer = array_column($customerSeries, null, "bucket");
        for ($i = 0; $i < ($days ?: 12); $i++) {
            $d = $start->modify("+" . $i . ($days ? " days" : " months"));
            $key = $d->format($days ? "Y-m-d" : "Y-m");
            $series[] = [
                "label" => $d->format($days ? "M j" : "M"),
                "date" => $key,
                "completed" => (float) ($byOrder[$key]["completed"] ?? 0),
                "ordered" => (float) ($byOrder[$key]["ordered"] ?? 0),
                "orders" => (int) ($byOrder[$key]["orders"] ?? 0),
                "customers" => (int) ($byCustomer[$key]["n"] ?? 0),
            ];
        }
        $stats = $this->row("SELECT (SELECT COUNT(*) FROM users WHERE role='user') AS customers,
          (SELECT COUNT(*) FROM products WHERE is_active=1) AS products,
          (SELECT COUNT(*) FROM orders) AS orders,
          (SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE status='completed') AS revenue,
          (SELECT COUNT(*) FROM orders WHERE status='pending') AS pending,
          (SELECT COUNT(*) FROM products WHERE is_active=1 AND stock_quantity<=5) AS low_stock");
        $top = $this->products(["sort" => "sold-desc"]);
        return [
            "stats" => $stats,
            "series" => $series,
            "range" => $days === 7 ? "7d" : ($days === 30 ? "30d" : ($days === 33 ? "33d" : "12m")),
            "recent_orders" => $this->orders([])["items"],
            "top_products" => array_slice($top["items"], 0, 4),
            "status" => $this->rows("SELECT status,COUNT(*) AS n FROM orders GROUP BY status"),
        ];
    }
    public function settings(int $userId): array
    {
        return $this->row(
            "SELECT id,name,email,phone,created_at,(password IS NOT NULL) AS has_password FROM users WHERE id=? AND role='admin'",
            [$userId],
        ) ?? [];
    }
    public function saveSettings(int $userId, array $input): array
    {
        $name = self::text($input, "name", 100);
        $phone = self::text($input, "phone", 25);
        if ($name === "") {
            throw new InvalidArgumentException("Your name is required.");
        }
        if (
            $phone !== "" &&
            (!preg_match("/\A\+?[0-9 ()-]{3,25}\z/", $phone) || !preg_match("/[0-9]/", $phone))
        ) {
            throw new InvalidArgumentException("Enter a valid phone number.");
        }
        $password = $input["new_password"] ?? "";
        if (!is_string($password) || strlen($password) > 128) {
            throw new InvalidArgumentException("Invalid password.");
        }
        $newVersion = null;
        $this->db->beginTransaction();
        try {
            $owner = $this->row(
                "SELECT password,session_version FROM users WHERE id=? AND role='admin' AND is_active=1 FOR UPDATE",
                [$userId],
            );
            if ($owner === null) {
                throw new OutOfBoundsException("Admin account unavailable.");
            }
            if ($password !== "") {
                $current = $input["current_password"] ?? "";
                $confirm = $input["confirm_password"] ?? "";
                if (
                    !is_string($current) ||
                    !is_string($owner["password"]) ||
                    !password_verify($current, $owner["password"])
                ) {
                    throw new InvalidArgumentException("Your current password is incorrect.");
                }
                if (strlen($password) < 12 || $password !== $confirm) {
                    throw new InvalidArgumentException(
                        "Passwords must match and contain at least 12 characters.",
                    );
                }
                if (password_verify($password, $owner["password"])) {
                    throw new InvalidArgumentException("Choose a password different from your current password.");
                }
                $this->run("UPDATE users SET password=?,session_version=session_version+1 WHERE id=?", [
                    password_hash($password, PASSWORD_DEFAULT),
                    $userId,
                ]);
                $this->run("DELETE FROM password_reset_tokens WHERE user_id=?", [$userId]);
                $newVersion = (int) $owner["session_version"] + 1;
            }
            $this->run("UPDATE users SET name=?,phone=? WHERE id=?", [$name, $phone, $userId]);
            $this->db->commit();
            if ($newVersion !== null) {
                $_SESSION["session_version"] = $newVersion;
                session_regenerate_id(true);
            }
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
        return $this->settings($userId);
    }
}
