# iOS build, signing, and per-flavor schemes

**This all requires macOS + Xcode.** Nothing here can be verified or completed
from this Windows environment - the `ios/` project has never been opened in
Xcode during this work, only its text files (`.xcconfig`, `Info.plist`) were
edited directly. Treat the Android side as the proven path and this as the
documented-but-unverified one.

## Current state

- `ios/Flutter/{Saas,Development,Staging,Production}.xcconfig` exist, each
  setting `PRODUCT_BUNDLE_IDENTIFIER` and `APP_DISPLAY_NAME` for that flavor.
  `tool/create_branded_flavor.dart` adds one more per branded school the same
  way.
- Only **one** Xcode scheme exists (`ios/Runner.xcodeproj/xcshareddata/xcschemes/Runner.xcscheme`).
  None of the flavors - including the 4 original ones - have their own Xcode
  build configuration or scheme yet. `flutter build ipa --flavor <x>` will not
  work until this is done, for any flavor.

## What needs to happen in Xcode, once, per flavor

1. Open `ios/Runner.xcworkspace` (not the `.xcodeproj` - CocoaPods/Flutter
   plugins need the workspace).
2. Project settings → Info → Configurations: duplicate the Debug/Release/Profile
   configurations for each flavor (e.g. "Debug-saas", "Release-saas"), and under
   each one's Build Settings, set "Based on Configuration File" to the matching
   `ios/Flutter/<Name>.xcconfig`.
3. Product → Scheme → Manage Schemes → duplicate `Runner` per flavor, pointing
   its Run/Archive actions at that flavor's Debug/Release configurations.
4. Signing & Capabilities tab, per flavor's Release configuration: select the
   correct Team and a provisioning profile matching that flavor's bundle id
   (from `PRODUCT_BUNDLE_IDENTIFIER` in its `.xcconfig`).

Flutter's own docs cover the mechanics in more depth:
<https://docs.flutter.dev/deployment/flavors> (iOS section).

## Signing prerequisites (not yet available)

- An Apple Developer Program account (individual or organization) - status
  unconfirmed, see the open question from the original project audit.
- Per flavor: an App ID registered for that bundle id, a distribution
  certificate, and a provisioning profile (App Store or Ad Hoc, depending on
  distribution channel).
- Xcode Cloud or a manual `xcodebuild archive` + `xcodebuild -exportArchive` on
  a Mac for CI - this repo has no iOS CI configured yet.

## Verifying a flavor once schemes exist

```sh
flutter build ipa --flavor <id> --release
```

Do not consider a branded iOS app "done" until this succeeds on a real Mac -
the Android build succeeding is not evidence the iOS side works.
