# Android build and signing

## Current state

`android/app/build.gradle.kts` signs the `release` build type with the **debug**
keystore for every flavor (`signingConfig = signingConfigs.getByName("debug")`),
explicitly called out as a TODO in that file. This is fine for local
`flutter build apk --release` testing but **must not** be used to publish to the
Play Store - Google will reject an app signed with the well-known public debug
key, and it provides no update integrity guarantee to users anyway.

## Setting up real signing, per flavor

Each branded school (and the shared SaaS app) needs its own upload keystore,
since Play App Signing ties a keystore to one app listing:

```sh
keytool -genkey -v -keystore <id>-upload-key.jks -keyalg RSA -keysize 2048 -validity 10000 -alias upload
```

Keep the resulting `.jks` file and its passwords **out of the repository
entirely** - store them in your CI secret manager or a password manager, never
committed (there is no keystore in this repo today, and none should ever be
added to git history).

Reference it from a `key.properties` file (also gitignored - add
`key.properties` to `.gitignore` if it isn't already covered) that
`build.gradle.kts` reads at build time:

```properties
storeFile=/absolute/path/to/<id>-upload-key.jks
storePassword=...
keyAlias=upload
keyPassword=...
```

Then add a real `signingConfigs { create("release") { ... } }` block reading
those properties, and point each flavor's release build type at it instead of
the shared debug config - ideally one signing config per flavor once branded
schools exist, since they're separate Play Store listings with separate
keystores.

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
environment. The `--release` + real-signing path above has not been, since no
release keystore exists yet.
