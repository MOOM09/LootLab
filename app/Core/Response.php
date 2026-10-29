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
