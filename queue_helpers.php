<?php
/**
 * Shared helpers for creating email queue files safely.
 */

if (!function_exists('write_queue_file')) {
    function write_queue_file(string $path, array $payload): void
    {
        $options = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES;
        if (defined('JSON_UNESCAPED_UNICODE')) {
            $options |= JSON_UNESCAPED_UNICODE;
        }
        if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
            $options |= JSON_INVALID_UTF8_SUBSTITUTE;
        }

        $json = json_encode($payload, $options);
        if ($json === false) {
            throw new RuntimeException('Failed to encode queue payload: ' . json_last_error_msg());
        }

        $bytes = file_put_contents($path, $json, LOCK_EX);
        if ($bytes === false) {
            throw new RuntimeException('Failed to write queue file: ' . $path);
        }
    }
}
?>
