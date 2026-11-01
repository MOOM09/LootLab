<?php


class GoogleAuthService
{
    //? Holds the Google library's client object.
    // Holds LootLab’s OAuth settings and provides methods for communicating with Google.
    private ?\Google\Client $client = null;
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    //? Build the client on first use, so email/password login still works when
    //? google/apiclient is not installed or not configured.
    private function client(): \Google\Client
    {
        if ($this->client !== null) {
            return $this->client;
        }

        if (!class_exists('Google\\Client')) {
            throw new RuntimeException('Google login is not available (google/apiclient is not installed).');
        }

        $this->client = new \Google\Client();

        //? Identify our application to Google.
        $this->client->setClientId($this->config['client_id']);

        //? Set the server credential used when exchanging an authorization code.
        $this->client->setClientSecret($this->config['client_secret']);

        //? Set the callback URL Google redirects the visitor to.
        $this->client->setRedirectUri($this->config['redirect_uri']);

        //? Request sign-in identity through OpenID Connect, the email, and the profile.
        $this->client->addScope('openid');
        $this->client->addScope('email');
        $this->client->addScope('profile');

        return $this->client;
    }

    // ? Build the Google login URL
    public function getAuthorizationUrl(string $state): string
    {
        //? Adds a random security value to the Google login request.
        //  Include the security value we will check when Google sends the user back.
        $this->client()->setState($state);

        //? Build and return the Google sign-in URL.
        return $this->client()->createAuthUrl();
    }

    // ? Exchange Google's authorization code for tokens
    public function exchangeCode(string $code): array
    {

        // Will get code from the url after user approved

        //? Exchange Google's temporary authorization code for tokens.
        $tokens = $this->client()->fetchAccessTokenWithAuthCode($code);

        //? Reject an unsuccessful exchange.
        if (isset($tokens['error']))
            throw new RuntimeException('Google sign-in could not be completed.');

        //؟ Login requires an ID token to verify the user's identity.
        if (
            !isset($tokens['id_token']) ||
            !is_string($tokens['id_token']) ||
            $tokens['id_token'] === ''
        ) {
            throw new RuntimeException('Google did not return an ID token.');
        }

        return $tokens;
    }

    // ? Verify the ID token and return the user's identity
    public function verifyIdToken(string $idToken): array
    {
        // Verify Google's token using the configured client ID.
        $identity = $this->client()->verifyIdToken($idToken);

        if ($identity === false) {
            throw new RuntimeException('Invalid Google ID token.');
        }

        //? Require Google's unique account ID and a verified email.
        if (
            !isset($identity['sub']) ||
            !is_string($identity['sub']) ||
            $identity['sub'] === '' ||
            !isset($identity['email']) ||
            !is_string($identity['email']) ||
            ($identity['email_verified'] ?? false) !== true
        ) {
            throw new RuntimeException('Google identity could not be verified.');
        }

        // $identity = [
        //     'sub' => '109876543210123456789',
        //     'email' => 'userEmail@gmail.com',
        //     'name' => 'username',
        // ];
        return $identity;
    }
}
