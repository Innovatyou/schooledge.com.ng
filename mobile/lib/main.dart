import 'package:flutter/widgets.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'app/app.dart';
import 'core/config/app_environment.dart';
import 'core/config/native_flavor.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  AppConfig.applyNativeFlavor(await readNativeBuildFlavor());
  runApp(const ProviderScope(child: SchoolEdgeApp()));
}
