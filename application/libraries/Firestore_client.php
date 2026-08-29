<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Minimal Firestore REST API client for the two things the PHP backend needs
 * to do server-side (everything else - message send/read, typing status - is
 * the Flutter client talking to Firestore directly, secured by Security
 * Rules): (1) mirror a block/unblock into the `blocks` collection so rules
 * can check it (this must be backend-authoritative - a blocked student must
 * never be able to write their own way around it), and (2) let an
 * authorized admin/teacher live-fetch a classroom's recent chat activity for
 * moderation ("chat oversight") without a continuous copy into MySQL.
 *
 * Authenticates as the service account itself (Google IAM), not as a
 * Firebase Auth user - so, per Firestore's security model, these calls
 * bypass Security Rules entirely (the rules only govern client SDK requests
 * carrying a Firebase Auth ID token). Reuses the exact same service-account
 * file and RS256 JWT-signing OAuth2 exchange as Fcm_push.php, just a
 * different scope/audience and a separate token cache file.
 *
 * Every method degrades to returning null/false until a real service-account
 * key is dropped at application/config/*-firebase-adminsdk-*.json (see
 * mobile/docs/firebase-setup.md).
 */
class Firestore_client
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

    /** Creates (or overwrites) one document. $fields is a plain assoc array of scalars/arrays - encoded to Firestore's typed-value JSON shape internally. */
    public function createDocument($collectionPath, $documentId, array $fields)
    {
        $account = $this->account();
        if (!$account) return false;
        $accessToken = $this->accessToken($account);
        if (!$accessToken) return false;

        $url = $this->baseUrl($account) . '/' . $collectionPath . '?documentId=' . rawurlencode($documentId);
        $response = $this->request('PATCH', $url, array('Authorization: Bearer ' . $accessToken, 'Content-Type: application/json'), json_encode(array('fields' => $this->encodeFields($fields))));
        if ($response['status'] >= 400) {
            log_message('error', 'Firestore_client: createDocument failed (' . $response['status'] . '): ' . $response['body']);
            return false;
        }
        return true;
    }

    public function deleteDocument($documentPath)
    {
        $account = $this->account();
        if (!$account) return false;
        $accessToken = $this->accessToken($account);
        if (!$accessToken) return false;

        $url = $this->baseUrl($account) . '/' . $documentPath;
        $response = $this->request('DELETE', $url, array('Authorization: Bearer ' . $accessToken));
        // A 404 here just means it was already gone (e.g. double-unblock) - not a failure.
        if ($response['status'] >= 400 && $response['status'] !== 404) {
            log_message('error', 'Firestore_client: deleteDocument failed (' . $response['status'] . '): ' . $response['body']);
            return false;
        }
        return true;
    }

    /** @return array|null Decoded document fields, or null if not found/on error. */
    public function getDocument($documentPath)
    {
        $account = $this->account();
        if (!$account) return null;
        $accessToken = $this->accessToken($account);
        if (!$accessToken) return null;

        $url = $this->baseUrl($account) . '/' . $documentPath;
        $response = $this->request('GET', $url, array('Authorization: Bearer ' . $accessToken));
        if ($response['status'] !== 200) return null;
        $decoded = json_decode($response['body'], true);
        return isset($decoded['fields']) ? $this->decodeFields($decoded['fields']) : array();
    }

    /**
     * Lists documents in a collection (optionally a subcollection path like
     * "conversations/abc_def/messages"), newest-first if $orderByDesc is set,
     * for the on-demand chat-oversight read path.
     * @return array<int, array{id: string, fields: array}>
     */
    public function listDocuments($collectionPath, $pageSize = 50, $orderByDesc = null)
    {
        $account = $this->account();
        if (!$account) return array();
        $accessToken = $this->accessToken($account);
        if (!$accessToken) return array();

        $query = array('pageSize' => (int)$pageSize);
        if ($orderByDesc) $query['orderBy'] = $orderByDesc . ' desc';
        $url = $this->baseUrl($account) . '/' . $collectionPath . '?' . http_build_query($query);
        $response = $this->request('GET', $url, array('Authorization: Bearer ' . $accessToken));
        if ($response['status'] !== 200) {
            if ($response['status'] !== 404) log_message('error', 'Firestore_client: listDocuments failed (' . $response['status'] . '): ' . $response['body']);
            return array();
        }
        $decoded = json_decode($response['body'], true);
        $out = array();
        foreach ((array)($decoded['documents'] ?? array()) as $doc) {
            $parts = explode('/', (string)$doc['name']);
            $out[] = array('id' => end($parts), 'fields' => $this->decodeFields($doc['fields'] ?? array()));
        }
        return $out;
    }

    private function account()
    {
        if (!$this->accountPath) return null;
        $account = json_decode(file_get_contents($this->accountPath), true);
        if (empty($account['client_email']) || empty($account['private_key']) || empty($account['project_id'])) {
            log_message('error', 'Firestore_client: service account file is missing required fields.');
            return null;
        }
        return $account;
    }

    private function baseUrl(array $account)
    {
        return 'https://firestore.googleapis.com/v1/projects/' . $account['project_id'] . '/databases/(default)/documents';
    }

    /** File-cached OAuth2 access token, scoped for Firestore rather than FCM - see Fcm_push::accessToken() for the identical pattern this mirrors. */
    private function accessToken(array $account)
    {
        $cacheFile = APPPATH . 'cache/firestore_access_token.json';
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
            'scope' => 'https://www.googleapis.com/auth/datastore',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        )));
        $signingInput = $header . '.' . $claims;
        $signature = '';
        if (!openssl_sign($signingInput, $signature, $account['private_key'], 'sha256WithRSAEncryption')) {
            log_message('error', 'Firestore_client: could not sign the OAuth JWT with the service account key.');
            return null;
        }
        $jwt = $signingInput . '.' . $this->b64($signature);

        $response = $this->request('POST', 'https://oauth2.googleapis.com/token', array('Content-Type: application/x-www-form-urlencoded'), http_build_query(array(
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        )));
        if ($response['status'] !== 200) {
            log_message('error', 'Firestore_client: OAuth token exchange failed (' . $response['status'] . '): ' . $response['body']);
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

    /** Firestore's REST API wraps every field in a type tag, e.g. {"stringValue":"x"} - this is the plain-PHP-array <-> that shape. */
    private function encodeFields(array $fields)
    {
        $out = array();
        foreach ($fields as $key => $value) $out[$key] = $this->encodeValue($value);
        return $out;
    }

    private function encodeValue($value)
    {
        if ($value === null) return array('nullValue' => null);
        if (is_bool($value)) return array('booleanValue' => $value);
        if (is_int($value)) return array('integerValue' => (string)$value);
        if (is_float($value)) return array('doubleValue' => $value);
        if (is_array($value)) {
            if (array_is_list($value)) return array('arrayValue' => array('values' => array_map(array($this, 'encodeValue'), $value)));
            return array('mapValue' => array('fields' => $this->encodeFields($value)));
        }
        return array('stringValue' => (string)$value);
    }

    private function decodeFields(array $fields)
    {
        $out = array();
        foreach ($fields as $key => $value) $out[$key] = $this->decodeValue($value);
        return $out;
    }

    private function decodeValue(array $value)
    {
        if (array_key_exists('nullValue', $value)) return null;
        if (array_key_exists('booleanValue', $value)) return (bool)$value['booleanValue'];
        if (array_key_exists('integerValue', $value)) return (int)$value['integerValue'];
        if (array_key_exists('doubleValue', $value)) return (float)$value['doubleValue'];
        if (array_key_exists('timestampValue', $value)) return (string)$value['timestampValue'];
        if (array_key_exists('arrayValue', $value)) return array_map(array($this, 'decodeValue'), $value['arrayValue']['values'] ?? array());
        if (array_key_exists('mapValue', $value)) return $this->decodeFields($value['mapValue']['fields'] ?? array());
        return $value['stringValue'] ?? null;
    }

    private function request($method, $url, array $headers, $body = null)
    {
        $ch = curl_init($url);
        $options = array(
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
        );
        if ($body !== null) $options[CURLOPT_POSTFIELDS] = $body;
        curl_setopt_array($ch, $options);
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
