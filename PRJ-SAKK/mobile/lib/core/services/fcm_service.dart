import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';

/// Top-level FCM background/terminated handler. Runs in its own isolate, so it
/// must be a top-level (or static) function annotated for the AOT entry point.
/// Notification-type messages are rendered by the OS tray automatically; this
/// hook exists so data-only payloads can still be handled when the app is dead.
@pragma('vm:entry-point')
Future<void> sakkFcmBackgroundHandler(RemoteMessage message) async {
  await Firebase.initializeApp();
}

/// Manages FCM token lifecycle, foreground/background notification display,
/// and tap-to-navigate routing.
///
/// Initialisation is safe to call even when Firebase config files
/// (google-services.json / GoogleService-Info.plist) are missing — the
/// resulting [FirebaseException] is silently caught so the app never crashes.
class FCMService {
  FCMService._();

  static final FCMService instance = FCMService._();

  // NOTE: do NOT eagerly call FirebaseMessaging.instance here — it throws
  // [core/no-app] if Firebase isn't initialised yet, crashing app startup.
  // It is resolved lazily inside init() after Firebase.initializeApp().
  final _localNotifications = FlutterLocalNotificationsPlugin();
  bool _initialised = false;
  String? _currentToken;

  /// Value notifier so the UI can react to a new token.
  final ValueNotifier<String?> tokenNotifier = ValueNotifier(null);

  /// Called when the user taps a FCM payload while the app is in foreground
  /// or was brought from background.  Override in main.dart.
  void Function(Map<String, dynamic> data)? onNotificationTap;

  /// Initialise Firebase + local notifications + FCM stream handlers.
  /// Returns `false` when Firebase is not configured.
  Future<bool> init() async {
    if (_initialised) return true;
    try {
      await Firebase.initializeApp(
        options: kIsWeb
            ? null
            : null, // auto-loads google-services.json / GoogleService-Info.plist
      );

      // Safe now that a default Firebase app exists.
      final messaging = FirebaseMessaging.instance;

      // Local notifications channel (Android)
      const android = AndroidInitializationSettings('@mipmap/ic_launcher');
      const ios = DarwinInitializationSettings(
        requestAlertPermission: true,
        requestBadgePermission: true,
        requestSoundPermission: true,
      );
      await _localNotifications.initialize(
        InitializationSettings(android: android, iOS: ios),
        onDidReceiveNotificationResponse: _onLocalTap,
      );

      // Pre-create the high-importance channel so background/terminated
      // notification-messages render here instead of a low-priority fallback.
      // Must match the manifest's default_notification_channel_id.
      await _localNotifications
          .resolvePlatformSpecificImplementation<
              AndroidFlutterLocalNotificationsPlugin>()
          ?.createNotificationChannel(
            const AndroidNotificationChannel(
              'sakk_push',
              'إشعارات صكك',
              description: 'إشعارات التطبيق',
              importance: Importance.high,
            ),
          );

      // Request permissions (iOS)
      await messaging.requestPermission(
        alert: true,
        badge: true,
        sound: true,
      );

      // Register token
      _currentToken = await messaging.getToken();
      tokenNotifier.value = _currentToken;

      // Listen for token refresh
      messaging.onTokenRefresh.listen((token) {
        _currentToken = token;
        tokenNotifier.value = token;
      });

      // Background/terminated isolate handler. Notification messages are shown
      // by the OS automatically; this lets data-only messages be processed too.
      FirebaseMessaging.onBackgroundMessage(sakkFcmBackgroundHandler);

      // Foreground messages — show as local notification
      FirebaseMessaging.onMessage.listen(_showLocalNotification);

      // Background tap
      FirebaseMessaging.onMessageOpenedApp.listen((msg) {
        _onTap(msg.data);
      });

      // Cold-start tap
      final initialMsg = await messaging.getInitialMessage();
      if (initialMsg != null) {
        WidgetsBinding.instance.addPostFrameCallback((_) {
          _onTap(initialMsg.data);
        });
      }

      _initialised = true;
      return true;
    } catch (_) {
      debugPrint('FCMService: Firebase not configured — skipping');
      return false;
    }
  }

  /// The current FCM device token, or `null` if unavailable.
  String? get token => _currentToken;

  /// Show a local notification for a foreground push payload.
  Future<void> _showLocalNotification(RemoteMessage msg) async {
    final notification = msg.notification;
    if (notification == null) return;

    const androidDetails = AndroidNotificationDetails(
      'sakk_push',
      'إشعارات صكك',
      channelDescription: 'إشعارات التطبيق',
      importance: Importance.high,
      priority: Priority.high,
    );
    const iosDetails = DarwinNotificationDetails();
    const details = NotificationDetails(android: androidDetails, iOS: iosDetails);

    await _localNotifications.show(
      msg.hashCode,
      notification.title,
      notification.body,
      details,
      payload: msg.data.isNotEmpty ? msg.data.toString() : null,
    );
  }

  void _onLocalTap(NotificationResponse? response) {
    if (response?.payload != null && onNotificationTap != null) {
      // Forward to the same handler as FCM taps.
      onNotificationTap!({'payload': response!.payload});
    }
  }

  void _onTap(Map<String, dynamic> data) {
    if (onNotificationTap != null) {
      onNotificationTap!(data);
    }
  }

  /// Dispose & clean up.
  void dispose() {
    tokenNotifier.dispose();
  }
}
