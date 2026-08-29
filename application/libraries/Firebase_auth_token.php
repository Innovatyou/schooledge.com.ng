<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Mints Firebase custom auth tokens so the Flutter app can sign in to
 * Firestore (via FirebaseAuth.instance.signInWithCustomToken()) using the
 * app's OWN bearer-token identity, rather than adding a second real login
 * system. Reuses the exact same service-account file and RS256 JWT-signing
 * approach as Fcm_push.php (see that file's own doc comment) - just a
 * different JWT shape (a Firebase custom token, not an OAuth2 assertion) and
 * no token exchange call: the signed JWT itself IS the custom token, per
 * https://firebase.google.com/docs/auth/admin/create-custom-tokens#using_a_third-party_jwt_library.
 *
 * Every method degrades to returning null until a real service-account key
 * is dropped at application/config/*-firebase-adminsdk-*.json (see
 * mobile/docs/firebase-setup.md) - same convention as Fcm_push::isConfigured().
 */
class Firebase_auth_token
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
     * @param string $uid    The Firebase Auth uid this token signs in as (kept
     *                       stable per membership, e.g. "{branch_id}-7-{user_id}").
     * @param array  $claims Custom claims attached to the resulting ID token
     *                       (readable in Firestore Security Rules as
     *                       request.auth.token.*) - keep this small, Firebase
     *                       enforces a 1000-byte limit on the claims payload.
     * @return string|null   The signed JWT (the custom token itself), or null
     *                       if unconfigured or signing failed.
     */
    public function mint($uid, array $claims = array())
    {
        if (!$this->accountPath) return null;
        $account = json_decode(file_get_contents($this->accountPath), true);
        if (empty($account['client_email']) || empty($account['private_key'])) {
            log_message('error', 'Firebase_auth_token: service account file is missing required fields.');
            return null;
        }

        $now = time();
        $header = $this->b64(json_encode(array('alg' => 'RS256', 'typ' => 'JWT')));
        $payload = $this->b64(json_encode(array(
            'iss' => $account['client_email'],
            'sub' => $account['client_email'],
            'aud' => 'https://identitytoolkit.googleapis.com/google.identity.identitytoolkit.v1.IdentityToolkit',
            'iat' => $now,
            'exp' => $now + 3600,
            'uid' => (string)$uid,
            'claims' => $claims,
        )));
        $signingInput = $header . '.' . $payload;
        $signature = '';
        if (!openssl_sign($signingInput, $signature, $account['private_key'], 'sha256WithRSAEncryption')) {
            log_message('error', 'Firebase_auth_token: could not sign the custom token with the service account key.');
            return null;
        }
        return $signingInput . '.' . $this->b64($signature);
    }

    private function b64($value)
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
