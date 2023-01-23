<?php
namespace Clicalmani\Flesco\Auth;

class JWT
{
    private $jti,
            $payload,
            $jwt,
            $secret,
            $expiry,
            $headers;

    function __construct( $jti = null, $expiry = 1 )
    {
        $this->jti     = $jti;
        $this->expiry  = is_null($expiry) ? 0: (int) $expiry;

        $this->headers = (object) [
            'alg' => 'HS256',
            'typ' => 'JWT'
        ];
        $this->payload = [
            'iss' => env('APP_URL', ''), // Issuer claim
            'iat' => time(),             // Issued at claim
            'jti' => $this->jti,         // JWT ID claim
            'exp' => time() + 60 * 60 * 24 * $this->expiry // Expiration time claim
        ];
        $this->secret  = env('APP_KEY', 'Clicalmani Flesco');
    }

    function setExpiry($expiry)
    {
        $this->expiry = $expiry;
    }

    function setJti($new_jti)
    {
        $this->jti = $new_jti;

        $this->payload = [
            'iss' => env('APP_URL', ''), // Issuer claim
            'iat' => time(),             // Issued at claim
            'jti' => $this->jti,         // JWT ID claim
            'exp' => time() + 60 * 60 * 24 * $this->expiry // Expiration time claim
        ];
    }

    function generateToken()
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

    private function base64urlEncode($str)
    {
        return rtrim(strtr(base64_encode($str), '+/', '-_'), '=');
    }

    function verifyToken($token)
    {
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
        
        if ( $payload->exp > 0 AND ( $payload->exp - time() ) < 0 ) { // token expired
            return false;
        }
        
        if ( $signature !== $parts[2] ) { // Invalid signature
            return false;
        }

        return $payload;
    }
}