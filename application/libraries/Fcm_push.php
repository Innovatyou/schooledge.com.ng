<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Sends a push notification via Firebase Cloud Messaging's HTTP v1 API, using a
 * service account key dropped at application/config/*-firebase-adminsdk-*.json
 * (gitignored - see .gitignore's *-firebase-adminsdk-*.json pattern). No such
 * file exists in this repository; every method here degrades to a silent no-op
 * until a real Firebase project's key is placed there (see mobile/docs/firebase-setup.md),
 * so callers never need to check isConfigured() themselves before calling send().
 *
 * There is no Composer/Guzzle in this project, so the OAuth2 service-account
 * exchange (JWT-bearer grant) and the FCM call itself are both done with plain
 * cURL - see https://firebase.google.com/docs/reference/rest/message for the
 * v1 send payload shape and https://developers.google.com/identity/protocols/oauth2/service-account
 * for the token exchange this mirrors.
 */
class Fcm_push
{
    private $accountPath;

    public function __construct()
    {
        $matches = glob(APPPATH . 'config/*-firebase-adminsdk-*.json');
        $this->accountPath = !empty($matches) ? $matches[0] : null;
    }

    public function isConfigured()
    {
        return $this->accountPath !== null;
    }

    /**
     * @param string $deviceToken FCM registration token (mobile_devices.push_token)
     * @param string $title
     * @param string $body
     * @param array  $data       string-keyed extra payload (e.g. ['category'=>'fees', 'id'=>'123']) - every value is cast to string, FCM's data payload requires it
     * @return bool true on a successful send, false on any failure (never throws - a push failure must not break the in-app notification it accompanies)
     */
    public function send($deviceToken, $title, $body, array $data = array())
    {
        if (!$this->accountPath || empty($deviceToken)) return false;
        $account = json_decode(file_get_contents($this->accountPath), true);
        if (empty($account['client_email']) || empty($account['private_key']) || empty($account['project_id'])) {
            log_message('error', 'Fcm_push: service account file is missing required fields.');
            return false;
        }

        $accessToken = $this->accessToken($account);
        if (!$accessToken) return false;

        $stringData = array();
        foreach ($data as $key => $value) {
            $stringData[(string)$key] = is_scalar($value) ? (string)$value : json_encode($value);
        }

        $payload = array('message' => array(
            'token' => $deviceToken,
            'notification' => array('title' => (string)$title, 'body' => (string)$body),
            'data' => $stringData,
        ));

        $response = $this->post(
            'https://fcm.googleapis.com/v1/projects/' . $account['project_id'] . '/messages:send',
            array('Authorization: Bearer ' . $accessToken, 'Content-Type: application/json'),
            json_encode($payload)
        );

        if ($response['status'] >= 400) {
            log_message('error', 'Fcm_push: send failed (' . $response['status'] . '): ' . $response['body']);
            return false;
        }
        return true;
    }

    /** File-cached OAuth2 access token (application/cache is already gitignored) - avoids a token exchange round-trip on every single notification. */
    private function accessToken(array $account)
    {
        $cacheFile = APPPATH . 'cache/fcm_access_token.json';
        if (is_file($cacheFile)) {
            $cached = json_decode(file_get_contents($cacheFile), true);
            if (!empty($cached['expires_at']) && !empty($cached['access_token']) && $cached['expires_at'] > time() + 60) {
                return $cached['access_token'];
            }
        }

        $now = time();
        $header = $this->b64(json_encode(array('alg' => 'RS256', 'typ' => 'JWT')));
        $claims = $this->b64(json_encode(array(
            'iss' => $account['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        )));
        $signingInput = $header . '.' . $claims;
        $signature = '';
        if (!openssl_sign($signingInput, $signature, $account['private_key'], 'sha256WithRSAEncryption')) {
            log_message('error', 'Fcm_push: could not sign the OAuth JWT with the service account key.');
            return null;
        }
        $jwt = $signingInput . '.' . $this->b64($signature);

        $response = $this->post(
            'https://oauth2.googleapis.com/token',
            array('Content-Type: application/x-www-form-urlencoded'),
            http_build_query(array(
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ))
        );
        if ($response['status'] !== 200) {
            log_message('error', 'Fcm_push: OAuth token exchange failed (' . $response['status'] . '): ' . $response['body']);
            return null;
        }
        $decoded = json_decode($response['body'], true);
        if (empty($decoded['access_token'])) return null;

        @file_put_contents($cacheFile, json_encode(array(
            'access_token' => $decoded['access_token'],
            'expires_at' => $now + (int)($decoded['expires_in'] ?? 3300),
        )));
        return $decoded['access_token'];
    }

    private function post($url, array $headers, $body)
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
        ));
        $responseBody = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($responseBody === false) {
            $status = 0;
            $responseBody = curl_error($ch);
        }
        curl_close($ch);
        return array('status' => $status, 'body' => $responseBody);
    }

    private function b64($value)
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
