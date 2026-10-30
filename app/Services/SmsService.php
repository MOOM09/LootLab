
<?php

class SmsService
{
    private string $apiKey;

    public function __construct()
    {
        $this->apiKey = $_ENV['TEXTBEE_API_KEY']
            ?? getenv('TEXTBEE_API_KEY')
            ?: '';

        if ($this->apiKey === '') {
            throw new Exception('Textbee API key is missing.');
        }
    }

    public function send(string $phone, string $message): array
    {
        $url = 'https://api.textbee.dev/api/v1/gateway/send-sms';

        $data = [
            'recipients' => [$phone],
            'message' => $message
        ];

        $curl = curl_init($url);

        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'x-api-key: ' . $this->apiKey,
                'Content-Type: application/json'
            ],
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_TIMEOUT => 15,

            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0
        ]);

        $response = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);

        curl_close($curl);

        if ($response === false) {
            throw new Exception('SMS connection failed: ' . $error);
        }

        $result = json_decode($response, true);

        if ($status < 200 || $status >= 300) {
            throw new Exception(
                $result['message'] ?? 'SMS request failed. HTTP ' . $status
            );
        }

        return $result;
    }
}
