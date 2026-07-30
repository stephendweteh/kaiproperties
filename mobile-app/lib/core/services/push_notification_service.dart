import 'dart:async';

import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';

import 'api_service.dart';

@pragma('vm:entry-point')
Future<void> firebaseMessagingBackgroundHandler(RemoteMessage message) async {
  final options = firebaseOptionsFromEnvironment();

  if (Firebase.apps.isEmpty) {
    if (options != null) {
      await Firebase.initializeApp(options: options);
    } else {
      await Firebase.initializeApp();
    }
  }
}

FirebaseOptions? firebaseOptionsFromEnvironment() {
  const apiKey = String.fromEnvironment('FIREBASE_API_KEY');
  const appId = String.fromEnvironment('FIREBASE_APP_ID');
  const messagingSenderId = String.fromEnvironment('FIREBASE_MESSAGING_SENDER_ID');
  const projectId = String.fromEnvironment('FIREBASE_PROJECT_ID');
  const storageBucket = String.fromEnvironment('FIREBASE_STORAGE_BUCKET');
  const iosBundleId = String.fromEnvironment('FIREBASE_IOS_BUNDLE_ID');

  if (apiKey.isEmpty || appId.isEmpty || messagingSenderId.isEmpty || projectId.isEmpty) {
    return null;
  }

  return FirebaseOptions(
    apiKey: apiKey,
    appId: appId,
    messagingSenderId: messagingSenderId,
    projectId: projectId,
    storageBucket: storageBucket.isEmpty ? null : storageBucket,
    iosBundleId: iosBundleId.isEmpty ? null : iosBundleId,
  );
}

class PushNotificationService {
  PushNotificationService._();
  static final PushNotificationService instance = PushNotificationService._();

  final FlutterLocalNotificationsPlugin _localNotifications =
      FlutterLocalNotificationsPlugin();

  bool _initialized = false;
  Future<void> Function(int? ticketId)? _onNotificationTap;

  static const AndroidNotificationChannel _androidChannel =
      AndroidNotificationChannel(
    'kai_ticket_updates',
    'Ticket Updates',
    description: 'Ticket and workflow updates for KAI Properties',
    importance: Importance.high,
  );

  Future<void> initialize({
    required Future<void> Function(int? ticketId) onNotificationTap,
  }) async {
    _onNotificationTap = onNotificationTap;

    if (_initialized) {
      return;
    }

    final options = firebaseOptionsFromEnvironment();

    try {
      if (Firebase.apps.isEmpty) {
        if (options != null) {
          await Firebase.initializeApp(options: options);
        } else {
          await Firebase.initializeApp();
        }
      }
    } catch (e) {
      debugPrint('Push init skipped: Firebase is not configured yet ($e).');
      return;
    }

    FirebaseMessaging.onBackgroundMessage(firebaseMessagingBackgroundHandler);

    await _initializeLocalNotifications();

    final settings = await FirebaseMessaging.instance.requestPermission(
      alert: true,
      badge: true,
      sound: true,
      provisional: false,
    );

    if (settings.authorizationStatus == AuthorizationStatus.denied) {
      debugPrint('Push permission denied by user.');
    }

    FirebaseMessaging.onMessage.listen((RemoteMessage message) async {
      await _showForegroundNotification(message);
    });

    FirebaseMessaging.onMessageOpenedApp.listen((RemoteMessage message) {
      unawaited(_handleNotificationTap(message.data));
    });

    final initialMessage = await FirebaseMessaging.instance.getInitialMessage();
    if (initialMessage != null) {
      unawaited(_handleNotificationTap(initialMessage.data));
    }

    FirebaseMessaging.instance.onTokenRefresh.listen((token) {
      unawaited(_registerToken(token));
    });

    _initialized = true;
  }

  Future<void> syncDeviceToken() async {
    if (! _initialized) {
      return;
    }

    final token = await FirebaseMessaging.instance.getToken();
    if (token == null || token.isEmpty) {
      return;
    }

    await _registerToken(token);
  }

  Future<void> unregisterDeviceToken() async {
    if (! _initialized) {
      return;
    }

    try {
      final token = await FirebaseMessaging.instance.getToken();
      if (token == null || token.isEmpty) {
        return;
      }

      await ApiService.instance.unregisterDeviceToken(token);
    } catch (e) {
      debugPrint('Unable to unregister push token: $e');
    }
  }

  Future<void> _registerToken(String token) async {
    final platform = switch (defaultTargetPlatform) {
      TargetPlatform.android => 'android',
      TargetPlatform.iOS => 'ios',
      TargetPlatform.macOS => 'ios',
      _ => 'android',
    };

    try {
      await ApiService.instance.registerDeviceToken(
        token,
        platform,
        'Flutter ${defaultTargetPlatform.name}',
      );
    } catch (e) {
      debugPrint('Unable to register push token: $e');
    }
  }

  Future<void> _initializeLocalNotifications() async {
    const androidSettings = AndroidInitializationSettings('@mipmap/ic_launcher');
    const iosSettings = DarwinInitializationSettings();

    await _localNotifications.initialize(
      const InitializationSettings(
        android: androidSettings,
        iOS: iosSettings,
      ),
      onDidReceiveNotificationResponse: (response) {
        unawaited(_handleNotificationTap(response.payload == null ? {} : {'ticket_id': response.payload}));
      },
    );

    await _localNotifications
        .resolvePlatformSpecificImplementation<AndroidFlutterLocalNotificationsPlugin>()
        ?.createNotificationChannel(_androidChannel);
  }

  Future<void> _showForegroundNotification(RemoteMessage message) async {
    final notification = message.notification;
    if (notification == null) {
      return;
    }

    final ticketId = message.data['ticket_id']?.toString();

    await _localNotifications.show(
      notification.hashCode,
      notification.title ?? 'KAI Properties',
      notification.body ?? 'You have a new update.',
      NotificationDetails(
        android: AndroidNotificationDetails(
          _androidChannel.id,
          _androidChannel.name,
          channelDescription: _androidChannel.description,
          importance: Importance.high,
          priority: Priority.high,
          icon: '@mipmap/ic_launcher',
        ),
        iOS: const DarwinNotificationDetails(),
      ),
      payload: ticketId,
    );
  }

  Future<void> _handleNotificationTap(Map<String, dynamic> data) async {
    final rawTicketId = data['ticket_id'];
    final ticketId = rawTicketId == null ? null : int.tryParse(rawTicketId.toString());

    await _onNotificationTap?.call(ticketId);
  }
}
