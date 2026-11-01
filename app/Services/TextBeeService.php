<?php
/** TextBee REST SMS gateway. API key must never reach browser/client JS. */
final class TextBeeService
{
    private string $apiKey;
    private string $deviceId;

    public function __construct()
    {
        $this->apiKey = trim((string) ($_ENV['TEXTBEE_API_KEY'] ?? getenv('TEXTBEE_API_KEY') ?: ''));
        $this->deviceId = trim((string) ($_ENV['TEXTBEE_DEVICE_ID'] ?? getenv('TEXTBEE_DEVICE_ID') ?: ''));
    }

    public function sendOtp(string $phone, string $code): void
    {
        if ($this->apiKey === '') {
            throw new RuntimeException('SMS service is not configured. Please contact support.');
        }
        if (!function_exists('curl_init')) {
            throw new RuntimeException('PHP cURL extension is not enabled.');
        }
        $payload = [
            'recipients' => [$phone],
            'message' => "LootLab verification code: {$code}. Expires in 5 minutes. Do not share this code."
        ];
        if ($this->deviceId !== '') $payload['deviceId'] = $this->deviceId;
        $handle = curl_init('https://api.textbee.dev/api/v1/gateway/send-sms');
        curl_setopt_array($handle, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_THROW_ON_ERROR),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-api-key: ' . $this->apiKey,
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 25,
        ]);
        $response = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
        $curlError = curl_error($handle);
        curl_close($handle);
        if ($response === false || $status !== 200) {
            error_log('TextBee SMS request failed. HTTP ' . $status . '; cURL: ' . $curlError);
            throw new RuntimeException('Could not send the verification SMS. Check TextBee, phone connectivity, and try again.');
        }
        $decoded = json_decode($response, true);
        if (!is_array($decoded) || ($decoded['success'] ?? true) === false || ($decoded['data']['success'] ?? true) === false) {
            throw new RuntimeException('TextBee did not accept the SMS request.');
        }
    }
}
