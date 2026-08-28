import java.io.FileInputStream
import java.util.Properties

plugins {
    id("com.android.application")
    // The Flutter Gradle Plugin must be applied after the Android and Kotlin Gradle plugins.
    id("dev.flutter.flutter-gradle-plugin")
    id("com.google.gms.google-services")
}

// Real release signing, only when a keystore has actually been provisioned -
// see docs/android-signing-guide.md. `android/key.properties` is gitignored
// and does not exist in this repo; every flavor's release build falls back to
// the debug keystore (as before) until it's created, so this is safe to build
// against right now with no keystore present.
val keystorePropertiesFile = rootProject.file("key.properties")
val keystoreProperties = Properties()
val hasReleaseKeystore = keystorePropertiesFile.exists()
if (hasReleaseKeystore) {
    keystoreProperties.load(FileInputStream(keystorePropertiesFile))
}

android {
    namespace = "com.company.schooledgeapp"
    compileSdk = 37
    ndkVersion = flutter.ndkVersion

    buildFeatures {
        resValues = true
    }

    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_17
        targetCompatibility = JavaVersion.VERSION_17
    }

    defaultConfig {
        // TODO: Specify your own unique Application ID (https://developer.android.com/studio/build/application-id.html).
        applicationId = "com.company.schooledgeapp"
        // You can update the following values to match your application needs.
        // For more information, see: https://flutter.dev/to/review-gradle-config.
        minSdk = flutter.minSdkVersion
        targetSdk = flutter.targetSdkVersion
        versionCode = flutter.versionCode
        versionName = flutter.versionName
    }

    flavorDimensions += "environment"
    productFlavors {
        create("saas") { dimension = "environment"; resValue("string", "app_name", "SchoolEdge SaaS") }
        create("development") { dimension = "environment"; resValue("string", "app_name", "SchoolEdge Dev") }
        create("staging") { dimension = "environment"; resValue("string", "app_name", "SchoolEdge Staging") }
        create("production") { dimension = "environment"; resValue("string", "app_name", "SchoolEdge") }
        create("sampleacademy") { dimension = "environment"; applicationId = "ng.com.sampleacademy.app"; resValue("string", "app_name", "Sample Academy") }
    }

    signingConfigs {
        if (hasReleaseKeystore) {
            create("release") {
                keyAlias = keystoreProperties["keyAlias"] as String?
                keyPassword = keystoreProperties["keyPassword"] as String?
                storeFile = (keystoreProperties["storeFile"] as String?)?.let { file(it) }
                storePassword = keystoreProperties["storePassword"] as String?
            }
        }
    }

    buildTypes {
        release {
            // Real signing once android/key.properties exists (see
            // docs/android-signing-guide.md); falls back to the debug keystore
            // so `flutter build apk/appbundle --release` keeps working with no
            // keystore provisioned - fine for local/testing builds, but never
            // for a real Play Store upload.
            signingConfig = if (hasReleaseKeystore) signingConfigs.getByName("release") else signingConfigs.getByName("debug")
        }
    }
}

kotlin {
    compilerOptions {
        jvmTarget = org.jetbrains.kotlin.gradle.dsl.JvmTarget.JVM_17
    }
}

flutter {
    source = "../.."
}
