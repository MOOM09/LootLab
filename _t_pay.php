<?php
// Phase 2: open the saved Stripe Checkout URL and pay with the official test card.
// Test mode only: card 4242 4242 4242 4242, any future expiry, any CVC.
$stateFile = __DIR__ . '/_e2e_state.json';
$state = json_decode(file_get_contents($stateFile), true);
$jar = $state['jar'];

function browser(string $method, string $url, ?array $fields = null): array
{
    global $jar;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $jar);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $jar);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 15);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_ENCODING, '');
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) LootLabAudit');
    // This PHP build has no system CA store; use the bundle already in the project.
    $bundle = __DIR__ . '/vendor/stripe/stripe-php/data/ca-certificates.crt';
    if (is_file($bundle)) {
        curl_setopt($ch, CURLOPT_CAINFO, $bundle);
    }
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields ?? []));
    }
    $body = (string) curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $eff = (string) curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    $err = curl_error($ch);
    $errno = curl_errno($ch);
    curl_close($ch);
    if ($err !== '') { echo '  curl error ' . $errno . ': ' . $err . PHP_EOL; }
    return [$code, $body, $eff];
}

for ($i = 1; $i <= 3; $i++) {
    [$c, $html, $eff] = browser('GET', $state['stripeUrl']);
    echo "attempt $i: HTTP $c url=$eff len=" . strlen($html) . PHP_EOL;
    if (strlen($html) > 2000) {
        file_put_contents(__DIR__ . '/_stripe_page.html', $html);
        echo 'saved page; title: ';
        if (preg_match('/<title>(.*?)<\/title>/is', $html, $t)) {
            echo trim($t[1]);
        }
        echo PHP_EOL;
        echo 'mentions amount: ' . (preg_match('/(?:\$|USD)\s?(\d+\.\d{2})/', $html, $m) ? $m[0] : 'no') . PHP_EOL;
        echo 'mentions total: ' . (stripos($html, 'Total') !== false ? 'yes' : 'no') . PHP_EOL;
        break;
    }
    sleep(2);
}
