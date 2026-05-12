<?php
/**
 * JWT Authentication handler
 */

if (!defined('ABSPATH')) {
    exit;
}

class BLOCKids_Configurator_Auth {
    
    public static function init() {
        // Hooks
    }
    
    /**
     * Generate JWT token for user
     */
    public static function generate_token($user_id) {
        $user = get_user_by('id', $user_id);
        
        if (!$user) {
            return false;
        }
        
        $secret_key = get_option('blockids_jwt_secret_key');
        $expiration = get_option('blockids_jwt_expiration', 3600);
        
        $issued_at = time();
        $expire = $issued_at + $expiration;
        
        $payload = array(
            'iss' => get_bloginfo('url'),
            'iat' => $issued_at,
            'exp' => $expire,
            'data' => array(
                'user_id' => $user->ID,
                'email' => $user->user_email,
                'name' => $user->display_name,
            )
        );
        
        $token = self::jwt_encode($payload, $secret_key);
        
        return $token;
    }
    
    /**
     * Validate JWT token
     */
    public static function validate_token($token) {
        try {
            $secret_key = get_option('blockids_jwt_secret_key');
            $decoded = self::jwt_decode($token, $secret_key);
            
            if (!$decoded || !isset($decoded->data->user_id)) {
                return false;
            }
            
            return $decoded->data;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Simple JWT encode
     * Pro produkci doporučuji použít knihovnu firebase/php-jwt
     */
    private static function jwt_encode($payload, $secret) {
        $header = array(
            'typ' => 'JWT',
            'alg' => 'HS256'
        );
        
        $header_encoded = self::base64url_encode(json_encode($header));
        $payload_encoded = self::base64url_encode(json_encode($payload));
        
        $signature = hash_hmac('sha256', "$header_encoded.$payload_encoded", $secret, true);
        $signature_encoded = self::base64url_encode($signature);
        
        return "$header_encoded.$payload_encoded.$signature_encoded";
    }
    
    /**
     * Simple JWT decode
     */
    private static function jwt_decode($jwt, $secret) {
        $parts = explode('.', $jwt);
        
        if (count($parts) !== 3) {
            throw new Exception('Invalid token format');
        }
        
        list($header_encoded, $payload_encoded, $signature_encoded) = $parts;
        
        // Verify signature
        $signature = self::base64url_decode($signature_encoded);
        $expected_signature = hash_hmac('sha256', "$header_encoded.$payload_encoded", $secret, true);
        
        if (!hash_equals($signature, $expected_signature)) {
            throw new Exception('Invalid signature');
        }
        
        $payload = json_decode(self::base64url_decode($payload_encoded));
        
        // Check expiration
        if (isset($payload->exp) && $payload->exp < time()) {
            throw new Exception('Token expired');
        }
        
        return $payload;
    }
    
    private static function base64url_encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    private static function base64url_decode($data) {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
