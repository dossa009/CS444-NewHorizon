<?php
/**
 * Simple JWT implementation for New Horizon
 * Handles token creation and validation
 */

class JWT {
    /**
     * Generate a JWT token
     */
    public static function encode($payload, $secret, $expirationTime = null) {
        $header = [
            'typ' => 'JWT',
            'alg' => 'HS256'
        ];

        // Add expiration time if provided
        if ($expirationTime) {
            $payload['exp'] = time() + $expirationTime;
        }

        // Add issued at time
        $payload['iat'] = time();

        // Encode header
        $headerEncoded = self::base64UrlEncode(json_encode($header));

        // Encode payload
        $payloadEncoded = self::base64UrlEncode(json_encode($payload));

        // Create signature
        $signature = hash_hmac('sha256', "$headerEncoded.$payloadEncoded", $secret, true);
        $signatureEncoded = self::base64UrlEncode($signature);

        // Create JWT
        return "$headerEncoded.$payloadEncoded.$signatureEncoded";
    }

    /**
     * Decode and verify a JWT token
     */
    public static function decode($token, $secret) {
        // Split the token
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            throw new Exception('Invalid token format');
        }

        list($headerEncoded, $payloadEncoded, $signatureEncoded) = $parts;

        // Verify signature
        $signature = self::base64UrlDecode($signatureEncoded);
        $expectedSignature = hash_hmac('sha256', "$headerEncoded.$payloadEncoded", $secret, true);

        if (!hash_equals($signature, $expectedSignature)) {
            throw new Exception('Invalid token signature');
        }

        // Decode payload
        $payload = json_decode(self::base64UrlDecode($payloadEncoded), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid token payload');
        }

        // Check expiration
        if (isset($payload['exp']) && time() > $payload['exp']) {
            throw new Exception('Token has expired');
        }

        return $payload;
    }

    /**
     * Base64 URL encode
     */
    private static function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Base64 URL decode
     */
    private static function base64UrlDecode($data) {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}

/**
 * Verify JWT token from request and return user data
 */
function verifyToken($token, $secret) {
    try {
        $payload = JWT::decode($token, $secret);
        return $payload;
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Require authentication middleware
 */
function requireAuth($mysqli) {
    $token = getAuthToken();

    if (!$token) {
        sendError('Authentication required', 401);
    }

    $payload = verifyToken($token, JWT_SECRET_KEY);

    if (!$payload || !isset($payload['user_id'])) {
        sendError('Invalid or expired token', 401);
    }

    // Verify user still exists and is active
    $stmt = $mysqli->prepare(
        "SELECT User_ID, Email, First_Name, Last_Name, Account_Type, Is_Active
         FROM Users
         WHERE User_ID = ? AND Is_Active = TRUE"
    );
    $stmt->bind_param('i', $payload['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!$user) {
        sendError('User not found or inactive', 401);
    }

    return $user;
}

/**
 * Require admin role
 */
function requireAdmin($mysqli) {
    $user = requireAuth($mysqli);

    if ($user['Account_Type'] !== 'admin') {
        sendError('Admin privileges required', 403);
    }

    return $user;
}
