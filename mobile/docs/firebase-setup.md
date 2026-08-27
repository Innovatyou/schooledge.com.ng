# Firebase setup

**Status: no Firebase project exists for this app yet.** Nothing in this repo
can send a push notification until one is created - `Notifications.php` on the
backend only implements the in-app inbox half
(`mobile_notification_inbox`/`mobile_notification_preferences`), deliberately
built to work independently of push so it isn't blocked on this step.

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

## What still needs building (not in this repo yet)

- **Sending** a push: no server-side FCM-sending code exists. The natural next
  step is a small addition to `Api_Controller::notifyMembership()` (already the
  single place every notification is created) that also POSTs to FCM's HTTP v1
  API for any `mobile_devices` row with a non-null `push_token` and
  `push_enabled=1`, using a Firebase service-account key kept **only** as a
  server-side secret (never in the Flutter app, never committed - see
  `.gitignore`'s `*-firebase-adminsdk-*.json` pattern, already in place from an
  earlier pass).
- **Registering** a device's push token: `mobile_devices.push_token` exists as a
  column but nothing in the Flutter app calls `firebase_messaging`'s
  `getToken()`/`onTokenRefresh` and posts it anywhere yet. That's a new
  `firebase_messaging` dependency plus a small `PATCH profile/sessions/<id>`-
  style endpoint (or extending `Mobile::newTokenPair()`'s device upsert) to
  store it.
- **iOS APNs**: once the Firebase iOS app exists, upload an APNs authentication
  key (or certificate) in the Firebase console's Cloud Messaging settings - this
  is an Apple Developer Program action, needs that account to exist first.

## Local/CI environment variables

None of the backend or Flutter code reads Firebase credentials from environment
variables today - `google-services.json`/`GoogleService-Info.plist` are read
directly by the platform build tooling by convention (file presence, not env
vars). The one secret to keep out of source control entirely, whenever the FCM-
sending backend code above gets built, is the Firebase **service account** JSON
(server-side only) - store it outside the repo (e.g. as a deployment secret) and
reference its path via a new entry in a server-side config file, the same way
payment gateway secrets already live in `payment_config` rather than in code.
