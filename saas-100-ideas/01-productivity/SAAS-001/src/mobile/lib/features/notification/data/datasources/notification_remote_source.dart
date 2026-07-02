import 'package:dio/dio.dart';
import '../../../../core/network/dio_client.dart';
import '../../../../core/network/api_endpoints.dart';
import '../models/notification_models.dart';

class NotificationRemoteSource {
  final DioClient _client;

  NotificationRemoteSource(this._client);

  Future<NotificationListDTO> getNotifications({
    String? type,
    bool? read,
    int page = 1,
    int perPage = 20,
  }) async {
    final queryParams = <String, dynamic>{
      'page': page,
      'per_page': perPage,
    };
    if (type != null) queryParams['type'] = type;
    if (read != null) queryParams['read'] = read.toString();

    final response = await _client.get(
      ApiEndpoints.notifications,
      queryParameters: queryParams,
    );
    return NotificationListDTO.fromJson(response.data as Map<String, dynamic>);
  }

  Future<void> markAsRead(String id) async {
    await _client.put(ApiEndpoints.notificationRead(id));
  }

  Future<MarkAllReadResponseDTO> markAllAsRead() async {
    final response = await _client.put(ApiEndpoints.notificationsReadAll);
    return MarkAllReadResponseDTO.fromJson(response.data as Map<String, dynamic>);
  }
}
