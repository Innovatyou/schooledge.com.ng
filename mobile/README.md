# SchoolEdge Mobile

Feature-first Flutter app (Riverpod + GoRouter + Dio) sharing one codebase
across the shared "SchoolEdge" SaaS app and per-school branded builds, talking
to the CodeIgniter 3 backend's `/api/v1/mobile/*` API in `../application/controllers/api/v1/`.

## Architecture

```
lib/
  app/            MaterialApp + GoRouter (auth-gated redirect logic)
  core/
    config/       AppConfig - flavor + API base URL (--dart-define, not hardcoded)
    network/      Dio + auth interceptor (attaches bearer token, retries once on 401 via refresh)
    session/      currentUserProvider (/me) + studentContextProvider (parent's active child)
    storage/      TokenStorage (flutter_secure_storage: access/refresh tokens, installation id)
    theme/        Light/dark ThemeData + persisted ThemeMode
    widgets/      Shared DepthCard/ModulePage/InfoRow/SectionTitle building blocks
  features/       One folder per module (auth, home, fees, library, live_classes,
                  attendance, homework, messages, results, planner, learning,
                  notifications, profile, admin) - each with data/ (Dio calls +
                  Riverpod providers) and presentation/ (screens)
```

Every feature talks to the backend through relative paths appended to
`AppConfig.apiBaseUrl` (e.g. `dioProvider.get('fees/summary')`) - never scrapes
HTML, never calls the database directly.

## Running

```sh
flutter run --flavor development --dart-define=APP_ENV=development --dart-define=API_BASE_URL=http://10.0.2.2/schooledge.ng/api/v1/mobile
```

- `--flavor` picks the **native** flavor (Android applicationId/app name, iOS
  bundle id) - `saas`, `development`, `staging`, `production`, or a branded
  school's flavor id (see below).
- `--dart-define=APP_ENV=...` picks the **Dart-level** environment enum
  (`AppEnvironment` in `core/config/app_environment.dart`) - keep it matching
  `--flavor` in practice.
- `--dart-define=API_BASE_URL=...` points at the backend; `10.0.2.2` is the
  Android emulator's alias for the host machine's `localhost`.

## Environment variables (`--dart-define`)

| Name | Default | Purpose |
|---|---|---|
| `APP_ENV` | `development` | Selects `AppEnvironment` (`saas`/`development`/`staging`/`production`) |
| `API_BASE_URL` | `http://10.0.2.2/schooledge.ng/api/v1/mobile` | Base URL every Dio call is relative to |

No secrets are read via `--dart-define` or committed anywhere in `lib/` -
payment gateway keys, Firebase service-account keys, and signing credentials
all stay server-side or outside the repository (see `docs/firebase-setup.md`,
`docs/android-signing-guide.md`).

## Branded schools (white-label)

```sh
dart run tool/create_branded_flavor.dart --id=<id> --name="<Name>" --android-id=<id> --ios-bundle=<id>
```

Full walkthrough: [docs/branded-school-onboarding.md](docs/branded-school-onboarding.md).
A sample branded flavor (`sampleacademy`) is included and builds today
(`flutter build apk --flavor sampleacademy --debug`) as a working reference -
replace its placeholder Firebase config and branding assets before using it for
anything beyond a build proof-of-concept.

## Testing

```sh
flutter analyze
flutter test
```

There is no `integration_test/` suite yet - manual verification against a real
backend (`docs/branded-school-onboarding.md`'s checklist, or the equivalent for
a non-branded flavor) is the current release gate for UI behaviour.

## Code generation

Only `features/auth/domain/auth_tokens.dart` uses Freezed/json_serializable
today. After changing it (or adding another Freezed model):

```sh
dart run build_runner build --delete-conflicting-outputs
```

## Building a release

```sh
flutter build appbundle --flavor <id> --release   # Android, Play Store format
flutter build apk --flavor <id> --release          # Android, sideload/testing
flutter build ipa --flavor <id> --release          # iOS - needs a Mac, see docs/ios-signing-guide.md
```

Release signing is not configured yet for either platform - see
[docs/android-signing-guide.md](docs/android-signing-guide.md) and
[docs/ios-signing-guide.md](docs/ios-signing-guide.md). Push notifications are
wired to a real Firebase project (sending from the backend and device-token
registration from the app) - see [docs/firebase-setup.md](docs/firebase-setup.md).
