<?php
namespace Clicalmani\Flesco\Auth;

/**
 * JWT Class
 * 
 * @package clicalmani\flesco
 * @author @clicalmani
 */
class JWT
{
    private $payload,    // JWT payload
            $secret,     // Encryption key
            $headers;    // Headers

    /**
     * Constructor
     * 
     * @param mixed $jti JWT ID claim
     * @param mixed $expiry Expiration time in days
     */
    public function __construct(private mixed $jti = null, private mixed $expiry = 1)
    {
        $this->headers = (object) [
            'alg' => 'HS256',
            'typ' => 'JWT'
        ];
        $this->payload = [
            'iss' => env('APP_URL', ''), // Issuer claim
            'iat' => time(),             // Issued at claim
            'jti' => $this->jti,         // JWT ID claim
            'exp' => time() + 60*60*24*$this->expiry // Expiration time claim
        ];
        $this->secret  = env('APP_KEY', '$2y$10$iuSS1cFgKgEV4yuHAZmH6.lilZyppcJAMmyLeviCxvWEaAmxXmIA2');
    }

    /**
     * Set expiry time claim
     * 
     * @param miexed $expiry
     * @return void
     */
    public function setExpiry(mixed $expiry) : void
    {
        $this->expiry = $expiry;
    }

    /**
     * Set JWT ID claim
     * 
     * @param mixed $new_jti
     * @return void
     */
    public function setJti(mixed $new_jti) : void
    {
        $this->jti = $new_jti;
        
        $this->payload = [
            'iss' => env('APP_URL', ''), // Issuer claim
            'iat' => time(),             // Issued at claim
            'jti' => $this->jti,         // JWT ID claim
            'exp' => time() + 60 * 60 * 24 * $this->expiry // Expiration time claim
        ];
    }

    /**
     * Generate token
     * 
     * @return string
     */
    public function generateToken() : string
    {
        $headers = $this->base64urlEncode(
            json_encode($this->headers)
        );
        $payload = $this->base64urlEncode(
            json_encode($this->payload)
        );
        $signature = $this->base64urlEncode(
            hash_hmac(
                "SHA256",
                "$headers.$payload",
                $this->secret,
                true
            )
        );

        return "$headers.$payload.$signature";
    }

    /**
     * Base 64 URL encode
     * 
     * @param string $url
     * @return string
     */
    private function base64urlEncode(string $url) : string
    {
        return rtrim(strtr(base64_encode($url), '+/', '-_'), '=');
    }

    /**
     * Verify token
     * 
     * @param string $token
     * @return miexed Payload if success, false if failure.
     */
    public function verifyToken(string $token)
    {
        if (!$token) {
            return false;
        }

        $parts = explode('.', $token);

        if ( count( $parts ) < 3 ) {
            return false;
        }

        $signature = $this->base64urlEncode(
            hash_hmac(
                "SHA256",
                "{$parts[0]}.{$parts[1]}",
                $this->secret,
                true
            )
        );
        $payload = json_decode(
            base64_decode($parts[1])
        );
        
        if (JSON_ERROR_NONE !== json_last_error()) {
            return false;
        }
        
        if ( $payload->exp > 0 AND ( $payload->exp - time() ) <= 0 ) { // token expired
            return false;
        }
        
        if ( $signature !== $parts[2] ) { // Invalid signature
            return false;
        }

        return $payload;
    }
}