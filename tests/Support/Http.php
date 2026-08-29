<?php
namespace Tests\Support;

/**
 * Thin cURL wrapper for hitting the real mobile API over HTTP - no Composer
 * HTTP client, matching the app's own no-Composer-at-runtime stance; this only
 * exists inside dev-only test tooling. Every path is relative to
 * `api/v1/mobile/` (mirroring the Flutter app's own AppConfig.apiBaseUrl
 * convention) - pass e.g. 'auth/login' or 'profile', never the full prefix.
 */
final class Http
{
    private const API_PREFIX = 'api/v1/mobile/';

    public static function request($method, $path, $body = null, $token = null, array $extraHeaders = array())
    {
        $headers = array('Accept: application/json');
        if ($body !== null) $headers[] = 'Content-Type: application/json';
        if ($token) $headers[] = 'Authorization: Bearer ' . $token;
        foreach ($extraHeaders as $key => $value) $headers[] = $key . ': ' . $value;

        $ch = curl_init(rtrim(TEST_BASE_URL, '/') . '/' . self::API_PREFIX . ltrim($path, '/'));
        curl_setopt_array($ch, array(
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 20,
        ));
        if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        $raw = curl_exec($ch);
        if ($raw === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException('HTTP request failed: ' . $error);
        }
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $decoded = json_decode($raw, true);
        return array('status' => $status, 'body' => is_array($decoded) ? $decoded : array('raw' => $raw));
    }

    public static function get($path, $token = null) { return self::request('GET', $path, null, $token); }
    public static function post($path, $body = array(), $token = null) { return self::request('POST', $path, $body, $token); }
    public static function patch($path, $body = array(), $token = null) { return self::request('PATCH', $path, $body, $token); }

    /**
     * Classic application/x-www-form-urlencoded POST (populates $_POST), for
     * the handful of endpoints (e.g. Homework::submit()) that read
     * $this->input->post() directly instead of Api_Controller::body() -
     * because they also accept a real file upload ($_FILES) alongside the
     * fields, which only a form-encoded body (not a JSON one) can carry.
     */
    public static function postForm($path, array $fields = array(), $token = null)
    {
        $headers = array('Accept: application/json');
        if ($token) $headers[] = 'Authorization: Bearer ' . $token;
        $ch = curl_init(rtrim(TEST_BASE_URL, '/') . '/' . self::API_PREFIX . ltrim($path, '/'));
        curl_setopt_array($ch, array(
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($fields),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 20,
        ));
        $raw = curl_exec($ch);
        if ($raw === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException('HTTP request failed: ' . $error);
        }
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $decoded = json_decode($raw, true);
        return array('status' => $status, 'body' => is_array($decoded) ? $decoded : array('raw' => $raw));
    }

    /**
     * Real multipart/form-data POST (populates both $_POST and $_FILES), for
     * endpoints that accept an actual file upload alongside other fields
     * (e.g. Chat::submitVoiceNote()) - postForm() above can't exercise the
     * $_FILES path at all since it's x-www-form-urlencoded.
     * @param array<string,string> $filePaths field name => local file path to upload
     */
    public static function postMultipart($path, array $fields, array $filePaths, $token = null)
    {
        $headers = array('Accept: application/json');
        if ($token) $headers[] = 'Authorization: Bearer ' . $token;
        $payload = $fields;
        foreach ($filePaths as $field => $filePath) {
            $payload[$field] = new \CURLFile($filePath);
        }
        $ch = curl_init(rtrim(TEST_BASE_URL, '/') . '/' . self::API_PREFIX . ltrim($path, '/'));
        curl_setopt_array($ch, array(
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 20,
        ));
        $raw = curl_exec($ch);
        if ($raw === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException('HTTP request failed: ' . $error);
        }
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $decoded = json_decode($raw, true);
        return array('status' => $status, 'body' => is_array($decoded) ? $decoded : array('raw' => $raw));
    }
}
