<?php
/**
 * Encryption/Decryption utility for sensitive data
 * 
 * Uses WordPress native encryption functions when available (WP 6.0+),
 * falls back to secure alternative for older versions.
 *
 * @package Delice_Recipe_Manager
 * @since 3.9.16
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Delice_Recipe_Crypto')) {
class Delice_Recipe_Crypto {

    /**
     * Encrypt sensitive data
     *
     * @param string $value Data to encrypt
     * @return string Encrypted data (base64 encoded) or original if encryption fails
     */
    public static function encrypt($value) {
        if (empty($value)) {
            return $value;
        }

        // Use WordPress native encryption if available (WP 6.0+)
        if (function_exists('wp_encrypt')) {
            $encrypted = wp_encrypt($value);
            if (!is_wp_error($encrypted)) {
                return 'wp:' . base64_encode($encrypted);
            }
        }

        // Fallback: Use sodium if available (PHP 7.2+)
        if (function_exists('sodium_crypto_secretbox') && function_exists('sodium_crypto_secretbox_keygen')) {
            return self::encrypt_sodium($value);
        }

        // Final fallback: Return as-is but marked (not ideal but prevents data loss)
        // In production, this should log a warning
        return 'plain:' . base64_encode($value);
    }

    /**
     * Decrypt sensitive data
     *
     * @param string $value Data to decrypt (base64 encoded)
     * @return string Decrypted data or original if decryption fails
     */
    public static function decrypt($value) {
        if (empty($value)) {
            return $value;
        }

        // Check if it's a WordPress encrypted value
        if (strpos($value, 'wp:') === 0) {
            $value = substr($value, 3);
            $encrypted = base64_decode($value, true);
            if ($encrypted === false) {
                return '';
            }
            
            if (function_exists('wp_decrypt')) {
                $decrypted = wp_decrypt($encrypted);
                if (!is_wp_error($decrypted)) {
                    return $decrypted;
                }
            }
            return '';
        }

        // Check if it's sodium encrypted
        if (strpos($value, 'sodium:') === 0) {
            return self::decrypt_sodium($value);
        }

        // Check if it's plain (legacy)
        if (strpos($value, 'plain:') === 0) {
            $value = substr($value, 6);
            $decoded = base64_decode($value, true);
            return ($decoded !== false) ? $decoded : '';
        }

        // Legacy: No prefix, return as-is for backward compatibility
        // This handles existing unencrypted API keys
        return $value;
    }

    /**
     * Encrypt using sodium (fallback for older WordPress)
     *
     * @param string $value
     * @return string
     */
    private static function encrypt_sodium($value) {
        $key = self::get_encryption_key();
        if (empty($key)) {
            return 'plain:' . base64_encode($value);
        }

        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $encrypted = sodium_crypto_secretbox($value, $nonce, $key);
        
        return 'sodium:' . base64_encode($nonce . $encrypted);
    }

    /**
     * Decrypt using sodium (fallback for older WordPress)
     *
     * @param string $value
     * @return string
     */
    private static function decrypt_sodium($value) {
        $value = substr($value, 7); // Remove 'sodium:' prefix
        $decoded = base64_decode($value, true);
        
        if ($decoded === false || strlen($decoded) < SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
            return '';
        }

        $key = self::get_encryption_key();
        if (empty($key)) {
            return '';
        }

        $nonce = substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $encrypted = substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        $decrypted = sodium_crypto_secretbox_open($encrypted, $nonce, $key);
        
        return ($decrypted !== false) ? $decrypted : '';
    }

    /**
     * Get or generate encryption key
     *
     * @return string 32-byte key
     */
    private static function get_encryption_key() {
        $key = get_option('delice_recipe_encryption_key');
        
        if (empty($key)) {
            // Generate new key if none exists
            if (function_exists('sodium_crypto_secretbox_keygen')) {
                $key = sodium_crypto_secretbox_keygen();
                update_option('delice_recipe_encryption_key', base64_encode($key), false);
            } else {
                // Fallback: Use WordPress salts
                $key = substr(hash('sha256', AUTH_KEY . SECURE_AUTH_KEY, true), 0, 32);
            }
        } else {
            $key = base64_decode($key);
            if ($key === false || strlen($key) !== 32) {
                // Invalid key, regenerate
                if (function_exists('sodium_crypto_secretbox_keygen')) {
                    $key = sodium_crypto_secretbox_keygen();
                    update_option('delice_recipe_encryption_key', base64_encode($key), false);
                } else {
                    $key = substr(hash('sha256', AUTH_KEY . SECURE_AUTH_KEY, true), 0, 32);
                }
            }
        }

        return $key;
    }

    /**
     * Check if encryption is available and working
     *
     * @return bool
     */
    public static function is_encryption_available() {
        if (function_exists('wp_encrypt') && function_exists('wp_decrypt')) {
            return true;
        }
        
        if (function_exists('sodium_crypto_secretbox')) {
            return true;
        }

        return false;
    }

    /**
     * Migrate existing plain text API key to encrypted format
     *
     * @param string $option_name The option name containing the API key
     * @return bool True if migration occurred, false otherwise
     */
    public static function migrate_api_key($option_name = 'delice_recipe_ai_api_key') {
        $existing_value = get_option($option_name);
        
        if (empty($existing_value)) {
            return false;
        }

        // Check if already encrypted
        if (strpos($existing_value, 'wp:') === 0 || 
            strpos($existing_value, 'sodium:') === 0 ||
            strpos($existing_value, 'plain:') === 0) {
            return false;
        }

        // Encrypt and save
        $encrypted = self::encrypt($existing_value);
        if ($encrypted !== $existing_value) {
            update_option($option_name, $encrypted);
            return true;
        }

        return false;
    }
}
}
