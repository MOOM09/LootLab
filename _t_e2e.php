<?php
// End-to-end Stripe sandbox test: UI -> session -> Stripe hosted page -> test card -> return.
// Uses Stripe's official test card 4242 4242 4242 4242. Test mode only.
$config = require 'config/config.php';
$base = rtrim($config['app_url'], '/');
require 'app/Core/Database.php';
$db = new Database($config['database']);
$pdo = $db->getConnection();

$pass = 0;
$fail = 0;
function check(string $l, bool $ok, string $d = ''): void
{
    global $pass, $fail;
    $ok ? $pass++ : $fail++;
    echo ($ok ? 'PASS ' : 'FAIL ') . $l . ($d !== '' ? "  -> $d" : '') . PHP_EOL;
}
$jar = tempnam(sys_get_temp_dir(), 'll');
function req(string $method, string $url, array $data = [], bool $follow = true): array
{
    global $jar;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $jar);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $jar);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $follow);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 LootLabAudit');
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    }
    $body = (string) curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $eff = (string) curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);
    return [$code, $body, $eff];
}
function token(string $html): string
{
    if (preg_match_all('/<input[^>]+>/i', $html, $m)) {
        foreach ($m[0] as $i) {
            if (strpos($i, 'csrf_token') !== false && preg_match('/value="([^"]+)"/', $i, $v)) {
                return $v[1];
            }
        }
    }
    return '';
}

$email = 'audit_stripe@example.invalid';
$ids = [];
$orderId = null;

try {
    $pdo->prepare('DELETE FROM users WHERE email = ?')->execute([$email]);
    $pdo->prepare('INSERT INTO users (name,email,password) VALUES (?,?,?)')->execute(['audit', $email, password_hash('AuditPass123', PASSWORD_DEFAULT)]);
    $uidRow = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $uidRow->execute([$email]);
    $userId = (int) $uidRow->fetchColumn();

    $cat = $pdo->query('SELECT id FROM categories ORDER BY id LIMIT 1')->fetchColumn();
    if ($cat === false) {
        $pdo->exec("INSERT INTO categories (name) VALUES ('audit-s-cat')");
        $cat = (int) $pdo->lastInsertId();
    }
    $sub = $pdo->query('SELECT id FROM subcategories ORDER BY id LIMIT 1')->fetchColumn();
    if ($sub === false) {
        $pdo->prepare("INSERT INTO subcategories (category_id,name) VALUES (?, 'audit-s-sub')")->execute([(int) $cat]);
        $sub = (int) $pdo->lastInsertId();
    }
    $ins = $pdo->prepare('INSERT INTO products (subcategory_id,name,description,price,image,stock_quantity) VALUES (?,?,?,?,NULL,?)');
    $ins->execute([(int) $sub, 'audit-stripe A', 'x', 4.50, 10]);
    $pa = (int) $pdo->lastInsertId();
    $ids[] = $pa;
    $ins->execute([(int) $sub, 'audit-stripe B', 'x', 4.46, 10]);
    $pb = (int) $pdo->lastInsertId();
    $ids[] = $pb;
    // Cart total = 8.96 JOD -> 1263 cents -> 12.63 USD

    [$c, $html] = req('GET', "$base/login");
    $csrf = token($html);
    check('login page loaded', $c === 200 && $csrf !== '', "HTTP $c");
    [$c, $body] = req('POST', "$base/login", ['email' => $email, 'password' => 'AuditPass123', 'csrf_token' => $csrf], false);
    check('logged in', $c === 200 && strpos($body, '"success":true') !== false, "HTTP $c");

    req('POST', "$base/cart/add", ['product_id' => $pa, 'quantity' => 1, 'csrf_token' => $csrf], false);
    [$c, $body] = req('POST', "$base/cart/add", ['product_id' => $pb, 'quantity' => 1, 'csrf_token' => $csrf], false);
    check('cart has two products (8.96 JOD)', $c === 201, "HTTP $c");
    $cart = json_decode($body, true);

    [$c, $form] = req('GET', "$base/checkout");
    check('checkout page loads', $c === 200, "HTTP $c");
    $hasCod = strpos($form, 'value="cod"') !== false;
    $hasStripe = strpos($form, 'value="stripe"') !== false;
    $formToken = token($form);
    check('checkout page offers COD', $hasCod);
    check('checkout page offers Stripe', $hasStripe);
    check('Stripe button targets POST /checkout/stripe', strpos($form, htmlspecialchars("$base/checkout/stripe")) !== false || strpos($form, "$base/checkout/stripe") !== false);
    check('checkout form still has CSRF token', $formToken !== '');

    // Submit to the Stripe route (follow the redirect to Stripe).
    [$c, $stripePage, $eff] = req('POST', "$base/checkout/stripe", [
        'shipping_address' => 'Test Street 1',
        'phone' => '0790000000',
        'payment_method' => 'stripe',
        'csrf_token' => $formToken,
    ], true);

    check('redirected to Stripe Checkout', strpos($eff, 'checkout.stripe.com') !== false, $eff);
    check('Stripe page loads', $c === 200 && stripos($stripePage, 'LootLab Order #') !== false, "HTTP $c len=" . strlen($stripePage));
    preg_match('/(?:USD|\$)\s?([0-9]+\.[0-9]{2})/', $stripePage, $m);
    check('Stripe page shows USD 12.63', isset($m[1]) && $m[1] === '12.63', 'shown=' . ($m[1] ?? 'not found'));

    // Find the order created for this attempt.
    $q = $pdo->prepare('SELECT id, total_amount FROM orders WHERE user_id = ? ORDER BY id DESC LIMIT 1');
    $q->execute([$userId]);
    $order = $q->fetch();
    $orderId = $order ? (int) $order['id'] : null;
    check('order created (pending) before payment', $order !== false, 'id=' . $orderId . ' total=' . ($order['total_amount'] ?? '-'));

    file_put_contents(__DIR__ . '/_e2e_state.json', json_encode([
        'email' => $email,
        'userId' => $userId,
        'productIds' => $ids,
        'orderId' => $orderId,
        'stripeUrl' => $eff,
        'jar' => $jar,
    ]));
    echo PHP_EOL . 'Stripe URL saved for the payment phase.' . PHP_EOL;
} catch (Throwable $e) {
    echo 'HARNESS ERROR: ' . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL . "RESULT: $pass passed, $fail failed" . PHP_EOL;
