import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/network/api_client.dart';
import '../../../../core/constants/api_constants.dart';
import '../models/notification_model.dart';

final notificationRepositoryProvider = Provider<NotificationRepository>((ref) {
  return NotificationRepository(ref.read(dioProvider));
});

final notificationsProvider = FutureProvider<List<NotificationModel>>((ref) async {
  return ref.read(notificationRepositoryProvider).getNotifications();
});

/// Unread badge count for the dashboard bell.
final unreadNotificationsProvider = FutureProvider<int>((ref) async {
  return ref.read(notificationRepositoryProvider).unreadCount();
});

class NotificationRepository {
  final Dio _dio;

  NotificationRepository(this._dio);

  Future<List<NotificationModel>> getNotifications() async {
    try {
      final response = await _dio.get(ApiConstants.notifications);
      final List<dynamic> data = response.data['data'] ?? [];
      return data.map((json) => NotificationModel.fromJson(json)).toList();
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  Future<int> unreadCount() async {
    try {
      final response = await _dio.get(ApiConstants.notificationsUnreadCount);
      return (response.data['data']?['count'] as num?)?.toInt() ?? 0;
    } on DioException {
      return 0; // badge is non-critical — fail silently
    }
  }

  Future<void> markAsRead(int id) async {
    try {
      await _dio.put(ApiConstants.notificationRead(id));
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  Future<void> markAllAsRead() async {
    try {
      await _dio.put(ApiConstants.notificationsReadAll);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  /// Push the current FCM device token to the backend. Non-critical: a failure
  /// just means the token re-syncs on the next launch / refresh.
  Future<void> updateFcmToken(String token) async {
    try {
      await _dio.post(
        ApiConstants.notificationFcmToken,
        data: {'fcm_token': token},
      );
    } on DioException {
      // swallow — backend keeps the previous token until the next sync
    }
  }
}
