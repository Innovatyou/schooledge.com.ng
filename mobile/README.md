# SchoolEdge Mobile

Feature-first Flutter foundation for the SchoolEdge mobile API.

Run a flavor with both the native flavor and Dart environment selected:

```sh
flutter run --flavor development --dart-define=APP_ENV=development --dart-define=API_BASE_URL=https://example.test/api/v1/mobile
```

Available environments are `saas`, `development`, `staging`, and `production`. Android product flavors are configured. The iOS xcconfig files are ready; schemes must be created in Xcode when the Apple team and bundle identifiers are confirmed.

Generate Freezed/JSON code after changing models:

```sh
dart run build_runner build --delete-conflicting-outputs
```

Push and store signing are intentionally not configured until Firebase, APNs, and developer accounts are available.
