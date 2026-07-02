import 'package:dio/dio.dart';
import '../../../../core/network/dio_client.dart';
import '../../../../core/network/api_endpoints.dart';
import '../models/dashboard_models.dart';

class DashboardRemoteSource {
  final DioClient _client;

  DashboardRemoteSource(this._client);

  Future<DashboardStatsDTO> getStats(String workspaceId) async {
    final response = await _client.get(
      ApiEndpoints.dashboardStats,
      queryParameters: {'workspace_id': workspaceId},
    );
    return DashboardStatsDTO.fromJson(response.data as Map<String, dynamic>);
  }

  Future<List<ActivityItemDTO>> getActivity(
      String workspaceId, int limit) async {
    final response = await _client.get(
      ApiEndpoints.dashboardActivity,
      queryParameters: {'workspace_id': workspaceId, 'limit': limit},
    );
    final data = response.data as Map<String, dynamic>;
    final list = data['data'] as List<dynamic>;
    return list
        .map((e) => ActivityItemDTO.fromJson(e as Map<String, dynamic>))
        .toList();
  }
}
