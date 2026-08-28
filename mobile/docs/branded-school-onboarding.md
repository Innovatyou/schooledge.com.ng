# Onboarding a branded school

A branded app is a new Flutter **flavor** on the same codebase - never a copy of
the project. This is the end-to-end process for turning on a branded app for a
premium school.

## 1. Generate the flavor

From `mobile/`:

```sh
dart run tool/create_branded_flavor.dart \
  --id=greenwood \
  --name="Greenwood Academy" \
  --android-id=ng.com.greenwoodacademy.app \
  --ios-bundle=ng.com.greenwoodacademy.app \
  --color=1F6FEB
```

- `--id` becomes the Gradle/Flutter flavor name: lowercase letters/numbers only,
  starting with a letter.
- `--android-id` / `--ios-bundle` must be globally unique across the Play Store /
  App Store - not a suffix of `com.company.schooledgeapp`, a real independent id
  for this school's own listing.
- `--color` is a 6-digit hex (no `#`), used as the splash background.

This writes:

- A new Android product flavor in `android/app/build.gradle.kts` with its own
  `applicationId`.
- `ios/Flutter/<Name>.xcconfig` with the matching bundle id.
- `branding/<id>.yaml` (the branding source of truth for this school) and
  `branding/<id>_icons.yaml` / `branding/<id>_splash.yaml` (ready-to-run configs
  for the icon/splash generators below).
- A **placeholder** `android/app/src/<id>/google-services.json` so the flavor
  builds immediately, before a real Firebase project exists for it. Replace it
  in step 3 - it is not usable for actual push notifications as-is.

The flavor builds right away:

```sh
flutter build apk --flavor greenwood --debug
```

## 2. Icon and splash screen

Put the school's real assets at `branding/<id>/icon.png` (1024x1024, square) and
`branding/<id>/splash.png`, then:

```sh
dart run flutter_launcher_icons -f branding/greenwood_icons.yaml
dart run flutter_native_splash:create --path=branding/greenwood_splash.yaml
```

## 3. Firebase project

See [firebase-setup.md](firebase-setup.md). Once you have the school's own
`google-services.json` (Android) and `GoogleService-Info.plist` (iOS):

- Replace `android/app/src/<id>/google-services.json` with the real file.
- Place `GoogleService-Info.plist` under `ios/Runner/` per the Firebase console's
  instructions for that bundle id (iOS has no per-flavor plist convention as
  clean as Android's source sets - follow the platform-specific steps in
  firebase-setup.md).

## 4. iOS build configuration and scheme

This step needs Xcode on macOS - see [ios-signing-guide.md](ios-signing-guide.md).
The generator can only write the `.xcconfig` file; wiring it into a build
configuration and scheme inside `project.pbxproj` is not something to script
blindly without Xcode's own tooling verifying the result.

## 5. App store metadata and signing

Documented in the platform-specific guides
([android-signing-guide.md](android-signing-guide.md),
[ios-signing-guide.md](ios-signing-guide.md)) - a release keystore for Android,
a distribution certificate + provisioning profile for iOS, and each store's
listing (name, description, screenshots, privacy policy URL, support contact).

## 6. Runtime branding (database)

The build-time flavor controls the app's identity in the stores; the **runtime**
branding (logo shown in the app itself, primary colour used in the Flutter
`ColorScheme`, support contact shown in Profile > Help & Support, which modules
are enabled) is a `branded_app_config` row for the school's `branch_id`, read by
the mobile API - not by this build tooling. At minimum, set:

```sql
INSERT INTO branded_app_config
  (branch_id, enabled, app_name, primary_color, android_package, ios_bundle_id, status, created_at)
VALUES
  (<branch_id>, 1, 'Greenwood Academy', '#1F6FEB', 'ng.com.greenwoodacademy.app', 'ng.com.greenwoodacademy.app', 'active', NOW());
```

`school_mobile_config` is the equivalent table for the *shared SaaS app's* view
of that same school (its logo/colour/support info when a user picks it from
school search) - both tables can be populated independently since a premium
school typically still wants a presence in the SaaS app for parents who haven't
installed the branded app yet.

## 7. Verify before shipping

- [ ] `flutter analyze` and `flutter test` pass
- [ ] `flutter build apk --flavor <id> --release` succeeds with real signing
- [ ] Icon and splash show correctly on a device/emulator
- [ ] Login works against the real API and shows this school's branding
- [ ] Push notifications work with this flavor's real Firebase project - FCM
      sending (`application/libraries/Fcm_push.php`) and device-token
      registration (`mobile/lib/core/push/push_service.dart`) are both built
      and verified against the shared `schooledgeapp` project (see
      `firebase-setup.md`); a *branded* flavor still needs its own real
      `google-services.json` in place of the placeholder before this checks out
