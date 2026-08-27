// Generates a new branded-school Flutter flavor on top of the existing
// saas/development/staging/production flavors, WITHOUT copying the Flutter
// project (per the platform spec: one codebase, many flavors).
//
// Usage:
//   dart run tool/create_branded_flavor.dart \
//     --id=greenwood \
//     --name="Greenwood Academy" \
//     --android-id=ng.com.greenwoodacademy.app \
//     --ios-bundle=ng.com.greenwoodacademy.app \
//     --color=0FF6B6
//
// What this does automatically (verified by building the flavor afterwards):
//   - Adds a new Android productFlavor to android/app/build.gradle.kts, with
//     its OWN applicationId (not just a suffix on the shared one) - a branded
//     school's app is a separate store listing, so it needs a genuinely
//     independent Android package name, which the 4 existing flavors don't
//     have today (they all currently share com.company.schooledgeapp).
//   - Writes an iOS Flutter/<Name>.xcconfig with the matching bundle id
//     (mirrors Saas.xcconfig/Production.xcconfig's existing format).
//   - Writes branding/<id>.yaml (source of truth for this school's branding)
//     and branding/<id>_icons.yaml / <id>_splash.yaml (ready-to-run configs
//     for flutter_launcher_icons / flutter_native_splash).
//
// What still needs a human (documented at the end, and in
// docs/branded-school-onboarding.md):
//   - Placing a real logo/icon source image and running the icon/splash
//     generators.
//   - Creating the Firebase app for this bundle/application id and dropping
//     in google-services.json / GoogleService-Info.plist.
//   - Adding the matching Xcode build configuration + scheme for the new
//     iOS flavor (requires Xcode itself - this script cannot safely edit
//     project.pbxproj without it).
//   - Store listing metadata and signing.

import 'dart:io';

void main(List<String> arguments) {
  final args = _parseArgs(arguments);
  final id = args['id'];
  final name = args['name'];
  final androidId = args['android-id'];
  final iosBundle = args['ios-bundle'];
  final color = args['color'] ?? '0C4F00';

  if (id == null || name == null || androidId == null || iosBundle == null) {
    stderr.writeln(
      'Usage: dart run tool/create_branded_flavor.dart --id=<flavor_id> --name="<Display Name>" '
      '--android-id=<android.application.id> --ios-bundle=<ios.bundle.id> [--color=RRGGBB]',
    );
    exit(64);
  }
  if (!RegExp(r'^[a-z][a-z0-9]*$').hasMatch(id)) {
    stderr.writeln('--id must be lowercase letters/numbers only, starting with a letter (this becomes the Gradle flavor name).');
    exit(64);
  }

  _addAndroidFlavor(id, name, androidId);
  _writePlaceholderGoogleServices(id, androidId);
  _writeIosXcconfig(id, name, iosBundle);
  _writeBrandingConfig(id, name, androidId, iosBundle, color);
  _writeIconsSplashConfig(id, name, color);

  stdout.writeln('');
  stdout.writeln('Created flavor "$id" ($name).');
  stdout.writeln('');
  stdout.writeln('Done automatically:');
  stdout.writeln('  - android/app/build.gradle.kts: new "$id" product flavor (applicationId=$androidId)');
  stdout.writeln('  - ios/Flutter/${_pascalCase(id)}.xcconfig (bundle id=$iosBundle)');
  stdout.writeln('  - branding/$id.yaml, branding/${id}_icons.yaml, branding/${id}_splash.yaml');
  stdout.writeln('');
  stdout.writeln('Still needed before this flavor is store-ready:');
  stdout.writeln('  1. Put this school\'s square logo at branding/$id/icon.png (1024x1024) and a');
  stdout.writeln('     splash logo at branding/$id/splash.png, then run:');
  stdout.writeln('       dart run flutter_launcher_icons -f branding/${id}_icons.yaml');
  stdout.writeln('       dart run flutter_native_splash:create --path=branding/${id}_splash.yaml');
  stdout.writeln('  2. Create a Firebase app for $androidId (Android) and $iosBundle (iOS), then place');
  stdout.writeln('     google-services.json under android/app/src/$id/ and GoogleService-Info.plist');
  stdout.writeln('     under ios/Runner/ (see docs/firebase-setup.md).');
  stdout.writeln('  3. On macOS with Xcode: add a matching build configuration + scheme for "$id"');
  stdout.writeln('     that uses ios/Flutter/${_pascalCase(id)}.xcconfig (see docs/ios-signing-guide.md).');
  stdout.writeln('  4. Verify: flutter build apk --flavor $id --debug');
  stdout.writeln('  5. Seed this school\'s branded_app_config row (see docs/branded-school-onboarding.md).');
}

Map<String, String> _parseArgs(List<String> arguments) {
  final result = <String, String>{};
  for (final arg in arguments) {
    if (!arg.startsWith('--') || !arg.contains('=')) continue;
    final index = arg.indexOf('=');
    result[arg.substring(2, index)] = arg.substring(index + 1);
  }
  return result;
}

String _pascalCase(String id) => id[0].toUpperCase() + id.substring(1);

void _addAndroidFlavor(String id, String name, String androidId) {
  final file = File('android/app/build.gradle.kts');
  final content = file.readAsStringSync();
  final marker = 'create("production") { dimension = "environment"; resValue("string", "app_name", "SchoolEdge") }';
  if (!content.contains(marker)) {
    stderr.writeln('Could not find the expected productFlavors anchor in build.gradle.kts - aborting before making a partial edit.');
    exit(1);
  }
  if (content.contains('create("$id")')) {
    stderr.writeln('A flavor named "$id" already exists in build.gradle.kts.');
    exit(1);
  }
  final addition =
      '$marker\n'
      '        create("$id") { dimension = "environment"; applicationId = "$androidId"; resValue("string", "app_name", "$name") }';
  file.writeAsStringSync(content.replaceFirst(marker, addition));
}

/// The google-services Gradle plugin fails the build entirely if it can't find
/// a client entry matching the flavor's applicationId, even for a debug build
/// that never touches Firebase - so a brand-new flavor is unbuildable until
/// someone sets up a real Firebase project for it. This placeholder (obviously
/// fake ids, never a real API key) unblocks the build in the meantime; the
/// Gradle plugin picks it up automatically via its `src/<flavor>/` convention,
/// overriding the shared android/app/google-services.json for this flavor only.
void _writePlaceholderGoogleServices(String id, String androidId) {
  final dir = Directory('android/app/src/$id');
  dir.createSync(recursive: true);
  File('${dir.path}/google-services.json').writeAsStringSync(
    '{\n'
    '  "_comment": "PLACEHOLDER - replace with the real file from this school\'s Firebase project before shipping push notifications or any other Firebase feature. This only exists so the app builds before that project is set up.",\n'
    '  "project_info": {\n'
    '    "project_number": "000000000000",\n'
    '    "project_id": "REPLACE-ME-see-docs-firebase-setup",\n'
    '    "storage_bucket": "replace-me.firebasestorage.app"\n'
    '  },\n'
    '  "client": [\n'
    '    {\n'
    '      "client_info": {\n'
    '        "mobilesdk_app_id": "1:000000000000:android:0000000000000000000000",\n'
    '        "android_client_info": { "package_name": "$androidId" }\n'
    '      },\n'
    '      "oauth_client": [],\n'
    '      "api_key": [ { "current_key": "REPLACE-ME" } ],\n'
    '      "services": { "appinvite_service": { "other_platform_oauth_client": [] } }\n'
    '    }\n'
    '  ],\n'
    '  "configuration_version": "1"\n'
    '}\n',
  );
}

void _writeIosXcconfig(String id, String name, String iosBundle) {
  final file = File('ios/Flutter/${_pascalCase(id)}.xcconfig');
  file.writeAsStringSync(
    "#include 'Generated.xcconfig'\n"
    'PRODUCT_BUNDLE_IDENTIFIER=$iosBundle\n'
    'APP_DISPLAY_NAME=$name\n',
  );
}

void _writeBrandingConfig(String id, String name, String androidId, String iosBundle, String color) {
  Directory('branding').createSync(recursive: true);
  File('branding/$id.yaml').writeAsStringSync(
    '# Branding source of truth for this school\'s app. Build-time values here must\n'
    '# match android/app/build.gradle.kts and ios/Flutter/${_pascalCase(id)}.xcconfig -\n'
    '# this file does not drive those automatically, it documents what was set.\n'
    'flavor_id: $id\n'
    'display_name: "$name"\n'
    'android_application_id: $androidId\n'
    'ios_bundle_id: $iosBundle\n'
    'primary_color: "#$color"\n'
    '\n'
    '# Runtime config (school_mobile_config / branded_app_config in the database)\n'
    '# is separate from this build-time file - see docs/branded-school-onboarding.md.\n',
  );
}

void _writeIconsSplashConfig(String id, String name, String color) {
  File('branding/${id}_icons.yaml').writeAsStringSync(
    'flutter_launcher_icons:\n'
    '  android: "ic_launcher"\n'
    '  ios: false\n'
    '  image_path: "branding/$id/icon.png"\n'
    '  min_sdk_android: 21\n',
  );
  File('branding/${id}_splash.yaml').writeAsStringSync(
    'flutter_native_splash:\n'
    '  color: "#$color"\n'
    '  image: branding/$id/splash.png\n'
    '  android_12:\n'
    '    color: "#$color"\n'
    '    image: branding/$id/splash.png\n',
  );
}
