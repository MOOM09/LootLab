<?php
/** Run from command line: php scripts/translate_existing.php */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
$root = dirname(__DIR__);
require_once $root . '/vendor/autoload.php';
$config = require $root . '/config/config.php';
require_once $root . '/app/Core/Database.php';
require_once $root . '/app/Services/ContentTranslationService.php';
$db = (new Database($config['database']))->getConnection();
$service = new ContentTranslationService($db);
$tables = [
    ['product', 'products', ['name', 'description']],
    ['category', 'categories', ['name']],
    ['subcategory', 'subcategories', ['name']],
];
foreach ($tables as [$entity, $table, $fields]) {
    $query = $db->query('SELECT id,' . implode(',', $fields) . ' FROM ' . $table . ' ORDER BY id');
    $count = 0;
    foreach ($query as $item) {
        $values = [];
        foreach ($fields as $field) $values[$field] = (string) ($item[$field] ?? '');
        $service->save($entity, (int) $item['id'], $values);
        $count++;
        echo "\r{$entity}: {$count} processed";
    }
    echo "\n";
}
echo "Finished. If Gemini was unavailable, original text was kept. Check PHP error log.\n";
