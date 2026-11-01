<?php
/** Session preferences survive a page change; bounded cookies also survive logout/browser restarts. */
final class PreferenceController
{
    public function __construct(private array $config) {}

    public function update(): void
    {
        $input = $_POST;
        if (!hash_equals((string) ($_SESSION['csrf_token'] ?? ''), (string) ($input['csrf_token'] ?? ''))) {
            Response::error('Invalid security token. Refresh the page.', 419);
            return;
        }
        $type = $input['type'] ?? '';
        $value = $input['value'] ?? '';
        $allowed = [
            'theme' => ['plum-dark', 'inferno-light'],
            'lang' => ['en', 'ar'],
        ];
        if (!is_string($type) || !is_string($value) ||
            !isset($allowed[$type]) || !in_array($value, $allowed[$type], true)) {
            Response::error('Invalid preference.', 422);
            return;
        }
        $_SESSION['lootlab_' . $type] = $value;
        $path = parse_url((string) ($this->config['app_url'] ?? ''), PHP_URL_PATH) ?: '/';
        // Cookies contain only two whitelisted non-sensitive preference values.
        setcookie('lootlab_' . $type, $value, [
            'expires' => time() + 365 * 86400,
            'path' => $path,
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        Response::success(['type' => $type, 'value' => $value], 200, 'Preference saved.');
    }
}
