# Firebase setup

**Status: a real Firebase project (`schooledgeapp`) exists.** Its Admin SDK
service account key lives at
`application/config/schooledgeapp-firebase-adminsdk-fbsvc-*.json` (gitignored,
never committed - see `.gitignore`'s `*-firebase-adminsdk-*.json` pattern).
`Fcm_push` (`application/libraries/Fcm_push.php`) reads it from there and was
verified directly against Google's real OAuth2 + FCM v1 endpoints in this
environment: the service-account JWT exchange returned a real access token
(HTTP 200), and an FCM `messages:send` call authenticated with it reached the
real `schooledgeapp` project (rejected only for using a syntactically invalid
device token, not for an auth failure) - the credential and project are live
and correctly wired. What's still needed is a **real device token to send
to** - `mobile/lib/core/push/push_service.dart` now registers one via
`PATCH profile/push-token` whenever the app has Firebase configured for the
running flavor (see the per-flavor `google-services.json` note below), but
this hasn't been exercised on a real device/emulator inside this environment.

## Creating the project

1. [Firebase console](https://console.firebase.google.com) → Add project.
2. For the **shared SaaS app**, add one Android app (package
   `com.company.schooledgeapp` - or whatever it's renamed to before release,
   see the TODO in `android/app/build.gradle.kts`) and one iOS app (bundle
   `ng.schooledge.mobile`, from `ios/Flutter/Production.xcconfig`).
3. Download `google-services.json` → replace
   `mobile/android/app/google-services.json` (already committed - it's client
   config, not a secret, safe to keep in git; the API key it contains is
   restricted to this project by Firebase's own rules, not a credential on its
   own).
4. Download `GoogleService-Info.plist` → place under `mobile/ios/Runner/`.
5. For each **branded school flavor**, add a *separate* Android + iOS app
   inside the same Firebase project (or a separate project per school, if you
   want fully isolated analytics/billing) using that school's
   `--android-id`/`--ios-bundle` from `tool/create_branded_flavor.dart`, then
   replace the placeholder at `android/app/src/<id>/google-services.json`.

## Current state

- **Sending** a push: done. `Api_Controller::notifyMembership()` writes the
  in-app inbox row, then loops every `mobile_devices` row for that membership
  with a non-null `push_token` and `push_enabled=1` and calls
  `Fcm_push::send()` (`application/libraries/Fcm_push.php`), which does the
  service-account JWT → OAuth2 access token → FCM v1 `messages:send` call
  itself (plain cURL, no Composer/Guzzle). Verified live against the real
  `schooledgeapp` project (see Status above). The OAuth access token is cached
  in `application/cache/fcm_access_token.json` (already gitignored) to avoid
  re-exchanging it on every notification.
- **Registering** a device's push token: `mobile/lib/core/push/push_service.dart`
  calls `Firebase.initializeApp()`, requests notification permission, reads
  `FirebaseMessaging.instance.getToken()`, and `PATCH`es it to
  `profile/push-token` (`Profile::register_push_token()`, keyed by the
  installation id embedded in the access token). Wired to run after
  login/OTP-verify and once on app start for an already-signed-in session
  (`HomePage.initState`). Every step is wrapped so a missing/misconfigured
  Firebase setup for a given flavor degrades to a silent no-op rather than
  crashing the app.
- **Per-flavor `google-services.json`**: only the shared app's and
  `development`'s are real; every other flavor (including `sampleacademy`) still
  has the placeholder from `tool/create_branded_flavor.dart` - push registration
  will silently fail on those until their real config is dropped in, same as any
  other Firebase app feature.
- **iOS APNs**: once the Firebase iOS app exists, upload an APNs authentication
  key (or certificate) in the Firebase console's Cloud Messaging settings - this
  is an Apple Developer Program action, needs that account to exist first, and
  iOS as a whole is unverified in this environment regardless (see
  `ios-signing-guide.md`).
- **Not yet built**: unregistering the token on logout (a stale token just sits
  disabled-by-neglect rather than actively cleared - low priority, since a new
  login on the same device reuses the same `installation_id` and simply
  overwrites it), and no local/foreground notification banner (a backgrounded
  or terminated app still gets the native system notification from FCM's own
  `notification` payload with zero extra code - only an in-foreground toast is
  missing).

## Classmate chat (Firestore)

Classmate chat (`Chat.php`, `mobile/lib/features/classmate_chat/`) uses two
Firebase products that push notifications never needed: **Firestore** (message
content, conversations, typing status - written directly by the Flutter
client for instant delivery) and **Firebase Auth custom tokens** (so the app
can sign in to Firestore using its own bearer-token identity, without a
second real login system - minted server-side by
`application/libraries/Firebase_auth_token.php`, reusing the exact same
service-account key `Fcm_push` already uses).

**Custom-token minting itself needs no setup beyond the existing service
account file** - it's pure local JWT signing, no network call, and was
verified live against the real `schooledgeapp` project while building this
feature (`Chat::token()` returns a correctly-shaped, correctly-signed token
today). What's NOT enabled yet:

1. **Enable Cloud Firestore** for the `schooledgeapp` project: [Firebase
   console](https://console.firebase.google.com/project/schooledgeapp/firestore)
   → Create database → **Native mode** → any region. Confirmed via a live
   call while building this feature: Firestore currently returns `403
   PERMISSION_DENIED` / `SERVICE_DISABLED` for this project - every chat
   endpoint that touches Firestore (`Chat::oversight()`, and the block/unblock
   mirror) degrades gracefully in the meantime (same not-yet-configured
   philosophy as `Fcm_push`), but the Flutter app's own direct Firestore
   reads/writes (actual message sending) will fail client-side until this is
   done. The Spark (free) plan's quota is expected to be more than enough for
   this feature - no Blaze/billing upgrade needed.
2. **Deploy the Security Rules** at `firestore.rules` (repo root) via the
   Firebase CLI: `firebase deploy --only firestore:rules --project
   schooledgeapp` (requires `firebase login` once, interactively - not
   something this environment can do). Validate the rules in the console's
   Rules Playground or the Firebase Emulator Suite's rules test harness
   before relying on them in production; they haven't been runtime-tested
   here since no live Firestore project is enabled to test against yet.
3. Once both are done, mint a token via a logged-in student
   (`POST chat/token`) and exercise `signInWithCustomToken` +
   a Firestore read/write from the app to confirm the whole chain end-to-end.

No new Android/iOS config is needed for either product - both ride on the
same `google-services.json`/`GoogleService-Info.plist` already in place for
push, since they're the same Firebase project/app registration.

## Local/CI environment variables

None of the backend or Flutter code reads Firebase credentials from environment
variables - `google-services.json`/`GoogleService-Info.plist` are read directly
by the platform build tooling by convention (file presence, not env vars), and
the backend locates its service-account key by globbing
`application/config/*-firebase-adminsdk-*.json` rather than a configured path.
Keep that file server-side only, exactly as today - never add it to the Flutter
app, never commit it (already gitignored).
