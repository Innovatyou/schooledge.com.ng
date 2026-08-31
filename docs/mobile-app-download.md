# Mobile app download (Android APK)

The public marketing site (`/` &rarr; `saas_website/index.php`, "Mobile App"
section) and the logged-in dashboard (`/mobile_app`, `Mobile_app.php`) both
link directly to a static file:

```
uploads/mobile_app/SchoolEdge.apk
```

That file is **not committed to git** (see `.gitignore`) - a release APK is
80-100MB+, GitHub hard-blocks any single file over 100MB, and binaries don't
delta-compress so every rebuild would permanently grow `.git` by another
80-100MB. Both pages check `is_file()`/`file_exists()` before showing the
Android download button, so a missing APK degrades to a "check back shortly"
message instead of a broken link - deploying code changes without the binary
present is safe.

## Deploying a build

1. Build a signed release APK (see `mobile/docs/android-signing-guide.md` for
   generating `mobile/android/key.properties` and the upload keystore first):
   ```sh
   cd mobile
   flutter build apk --flavor production --release --dart-define=APP_ENV=production
   ```
2. Copy `mobile/build/app/outputs/flutter-apk/app-production-release.apk` to
   `uploads/mobile_app/SchoolEdge.apk` on the **production** server (SCP/FTP/
   whatever you use to sync the rest of this app - it does not travel with a
   `git pull` since it's gitignored).

The filename is fixed (`SchoolEdge.apk`) so the download links never need to
change between releases - just overwrite the file in place.

## iOS

No iOS build exists yet - both pages show an "iOS - Coming Soon" badge
instead of a download link. See `mobile/docs/ios-signing-guide.md` when that
changes.
