# Android build and signing

## Current state

`android/app/build.gradle.kts` reads `android/key.properties` (gitignored, not
committed - see `.gitignore`) if it exists, and uses it to sign the `release`
build type for every flavor; if the file is absent - the case today, no
keystore has been generated yet - it falls back to the **debug** keystore
exactly as before, so `flutter build apk/appbundle --release` keeps working
with no setup. Both paths (present/absent `key.properties`) have been verified
directly in this environment. A debug-signed release build is fine for local
testing but **must not** be used to publish to the Play Store - Google will
reject an app signed with the well-known public debug key, and it provides no
update integrity guarantee to users anyway.

## Setting up real signing, per flavor

Each branded school (and the shared SaaS app) needs its own upload keystore,
since Play App Signing ties a keystore to one app listing:

```sh
keytool -genkey -v -keystore <id>-upload-key.jks -keyalg RSA -keysize 2048 -validity 10000 -alias upload
```

Keep the resulting `.jks` file and its passwords **out of the repository
entirely** - store them in your CI secret manager or a password manager, never
committed (`android/*.jks`/`android/*.keystore` are gitignored as a backstop,
but treat the real files as living outside the repo entirely).

Create `android/key.properties` (also gitignored) pointing at it:

```properties
storeFile=/absolute/path/to/<id>-upload-key.jks
storePassword=...
keyAlias=upload
keyPassword=...
```

`build.gradle.kts` picks this up automatically on the next build - no code
change needed. Today this is **one shared signing config for every flavor**
(matching how `saas`/`development`/`staging`/`production` already share one
`applicationId`); once a branded school flavor needs its own separate Play
Store listing and keystore, extend the `signingConfigs`/`buildTypes.release`
block in `build.gradle.kts` to select per-flavor properties (e.g. a
`key-<flavor>.properties` file per branded id) instead of the single shared one.

## Building a release artifact

```sh
flutter build appbundle --flavor <id> --release   # Play Store upload format
flutter build apk --flavor <id> --release          # for sideloading/testing
```

Play Store review requires the `.aab` (App Bundle) format, not a raw APK.

## Verifying

```sh
flutter build apk --flavor <id> --debug
```

succeeds today for every flavor including a freshly-generated branded one (see
`docs/branded-school-onboarding.md`) - that's been verified directly in this
environment, both with and without `android/key.properties` present. The
`--release` flag with a *real* keystore's `key.properties` in place has not
been exercised (no real upload keystore has been generated for any flavor yet).
