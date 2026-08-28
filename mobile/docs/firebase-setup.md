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

## Local/CI environment variables

None of the backend or Flutter code reads Firebase credentials from environment
variables - `google-services.json`/`GoogleService-Info.plist` are read directly
by the platform build tooling by convention (file presence, not env vars), and
the backend locates its service-account key by globbing
`application/config/*-firebase-adminsdk-*.json` rather than a configured path.
Keep that file server-side only, exactly as today - never add it to the Flutter
app, never commit it (already gitignored).
