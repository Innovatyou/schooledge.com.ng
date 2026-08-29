import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../network/api_client.dart';

final pushServiceProvider = Provider((ref) => PushService(ref));

/// Registers this installation's FCM token with the backend
/// (`PATCH profile/push-token`, see `Profile::register_push_token()`) so
/// `Api_Controller::notifyMembership()` can deliver a real push alongside the
/// in-app inbox entry it already writes for every notification.
///
/// Not every flavor has a real Firebase project behind its
/// `google-services.json` yet (see `docs/firebase-setup.md`) - every step here
/// is wrapped in its own try/catch and never rethrows, so a flavor without one
/// simply ends up with no push token registered instead of crashing the app.
class PushService {
  PushService(this._ref);
  final Ref _ref;
  bool _listening = false;

  Future<void> registerForCurrentSession() async {
    try {
      if (Firebase.apps.isEmpty) await Firebase.initializeApp();
    } catch (e) {
      debugPrint('PushService: Firebase.initializeApp failed - $e');
      return;
    }

    try {
      final messaging = FirebaseMessaging.instance;
      await messaging.requestPermission(alert: true, badge: true, sound: true);
      final token = await messaging.getToken();
      if (token != null) await _send(token);
      if (!_listening) {
        _listening = true;
        messaging.onTokenRefresh.listen(_send);
      }
    } catch (e) {
      debugPrint('PushService: token registration failed - $e');
    }
  }

  Future<void> _send(String token) async {
    try {
      await _ref
          .read(dioProvider)
          .patch(
            'profile/push-token',
            data: {'push_token': token, 'push_enabled': true},
          );
    } catch (e) {
      debugPrint('PushService: could not send token to backend - $e');
    }
  }
}
