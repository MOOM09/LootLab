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

        $options = [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'x-api-key: ' . $this->apiKey,
                'Content-Type: application/json'
            ],
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 10,

            // Verify the server certificate (never disable these in production).
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2
        ];

        // Some PHP/cURL builds on Windows have no system CA store. Use a configured
        // bundle when one is available; otherwise verification fails loudly below.
        $caBundle = $this->caBundlePath();

        if ($caBundle !== null) {
            $options[CURLOPT_CAINFO] = $caBundle;
        }

        curl_setopt_array($curl, $options);

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

    //? First existing CA bundle: curl.cainfo, then openssl.cafile, then the Stripe SDK's copy.
    private function caBundlePath(): ?string
    {
        $candidates = [
            (string) ini_get('curl.cainfo'),
            (string) ini_get('openssl.cafile'),
            str_replace('\\', '/', dirname(__DIR__, 2) . '/vendor/stripe/stripe-php/data/ca-certificates.crt'),
        ];

        foreach ($candidates as $path) {
            if ($path !== '' && is_file($path)) {
                return $path;
            }
        }

        return null;
    }
}
