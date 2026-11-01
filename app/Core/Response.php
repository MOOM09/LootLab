<?php

final class Response
{
    /**
     * Send a successful JSON response.
     */
    public static function success(
        mixed $data = null,
        int $status = 200,
        ?string $message = null,
        array $headers = []
    ): void {
        $payload = [
            'success' => true,
            'data' => $data,
        ];

        if ($message !== null) {
            $payload['message'] = $message;
        }

        self::json($payload, $status, $headers);
    }

    /**
     * Send a failed JSON response.
     */
    public static function error(
        string $message,
        int $status = 400,
        mixed $errors = null,
        array $headers = []
    ): void {
        $payload = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        self::json($payload, $status, $headers);
    }

    /**
     * Send any JSON-serializable payload.
     */
    public static function json(
        mixed $payload,
        int $status = 200,
        array $headers = []
    ): void {
        self::validateStatusCode($status);

        // A normal browser navigation should never show raw JSON to a customer.
        // API/XHR calls still receive the original JSON response.
        if (str_contains((string)($_SERVER['HTTP_ACCEPT'] ?? ''), 'text/html')) {
            $message = is_array($payload) ? (string)($payload['message'] ?? 'Something went wrong.') : 'Something went wrong.';
            http_response_code($status);
            $escape = static fn(string $v): string => htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $url = rtrim((string)($_ENV['APP_URL'] ?? ''), '/');
            header('Content-Type: text/html; charset=utf-8');
            echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>LootHub — Notice</title><style>body{margin:0;min-height:100vh;display:grid;place-items:center;background:#090711;color:#f5efff;font-family:system-ui}main{width:min(480px,90vw);padding:48px;border:1px solid #37264e;border-radius:24px;background:#171121;text-align:center}h1{font-size:48px;color:#bd91ff}p{line-height:1.8;color:#cbc0da}a{display:inline-block;padding:14px 24px;margin:12px 6px;border-radius:12px;background:#b58bff;color:#090711;font-weight:700;text-decoration:none}</style></head><body><main><h1>LOOT HUB ✦</h1><h2>Oops, something happened</h2><p>'.$escape($message).'</p><a href="'.$escape($url.'/login').'">Log in</a><a href="'.$escape($url.'/products').'">Home</a></main></body></html>';
            exit;
        }
        $encodedPayload = self::encode($payload);

        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');

        foreach ($headers as $name => $value) {
            if (!is_string($name) || !is_string($value)) {
                throw new InvalidArgumentException(
                    'Response headers must be a string-to-string array'
                );
            }

            header($name . ': ' . $value);
        }

        echo $encodedPayload;
        exit;
    }

    /**
     * Send a response without a body.
     */
    public static function noContent(array $headers = []): void
    {
        self::validateStatusCode(204);

        http_response_code(204);

        foreach ($headers as $name => $value) {
            if (!is_string($name) || !is_string($value)) {
                throw new InvalidArgumentException(
                    'Response headers must be a string-to-string array'
                );
            }

            header($name . ': ' . $value);
        }

        exit;
    }

    private static function encode(mixed $payload): string
    {
        try {
            return json_encode(
                $payload,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
            );
        } catch (JsonException $e) {
            error_log('Failed to encode JSON response: ' . $e->getMessage());

            http_response_code(500);

            return '{"success":false,"message":"Unable to encode response"}';
        }
    }

    private static function validateStatusCode(int $status): void
    {
        if ($status < 100 || $status > 599) {
            throw new InvalidArgumentException(
                'HTTP status code must be between 100 and 599'
            );
        }
    }
}
