import 'package:dio/dio.dart';
import '../../../../core/network/dio_client.dart';
import '../../../../core/network/api_endpoints.dart';
import '../models/time_entry_models.dart';

class TimeEntryRemoteSource {
  final DioClient _client;

  TimeEntryRemoteSource(this._client);

  Future<TimeEntryDTO> startTimer(StartTimerRequest request) async {
    final response = await _client.post(
      ApiEndpoints.timeEntryStart,
      data: request.toJson(),
    );
    final data = response.data as Map<String, dynamic>;
    return TimeEntryDTO.fromJson(data['data'] as Map<String, dynamic>);
  }

  Future<TimeEntryDTO> stopTimer(StopTimerRequest request) async {
    final response = await _client.post(
      ApiEndpoints.timeEntryStop,
      data: request.toJson(),
    );
    final data = response.data as Map<String, dynamic>;
    return TimeEntryDTO.fromJson(data['data'] as Map<String, dynamic>);
  }

  Future<List<TimeEntryDTO>> getTimeEntries({
    String? userId,
    String? taskId,
    String? from,
    String? to,
    int page = 1,
  }) async {
    final queryParams = <String, dynamic>{'page': page};
    if (userId != null) queryParams['user_id'] = userId;
    if (taskId != null) queryParams['task_id'] = taskId;
    if (from != null) queryParams['from'] = from;
    if (to != null) queryParams['to'] = to;

    final response = await _client.get(
      ApiEndpoints.timeEntries,
      queryParameters: queryParams,
    );
    final data = response.data as Map<String, dynamic>;
    final list = data['data'] as List<dynamic>;
    return list.map((e) => TimeEntryDTO.fromJson(e as Map<String, dynamic>)).toList();
  }

  Future<TimeEntryDTO> createManualEntry(ManualTimeEntryRequest request) async {
    final response = await _client.post(
      ApiEndpoints.timeEntries,
      data: request.toJson(),
    );
    final data = response.data as Map<String, dynamic>;
    return TimeEntryDTO.fromJson(data['data'] as Map<String, dynamic>);
  }

  Future<TimeEntryDTO> updateTimeEntry(String id, ManualTimeEntryRequest request) async {
    final response = await _client.put(
      ApiEndpoints.timeEntry(id),
      data: request.toJson(),
    );
    final data = response.data as Map<String, dynamic>;
    return TimeEntryDTO.fromJson(data['data'] as Map<String, dynamic>);
  }

  Future<void> deleteTimeEntry(String id) async {
    await _client.delete(ApiEndpoints.timeEntry(id));
  }

  Future<TimeReportDTO> getTimeReport({
    required String workspaceId,
    required String from,
    required String to,
    String? groupBy,
    String? userId,
    String? projectId,
  }) async {
    final queryParams = <String, dynamic>{
      'workspace_id': workspaceId,
      'from': from,
      'to': to,
    };
    if (groupBy != null) queryParams['group_by'] = groupBy;
    if (userId != null) queryParams['user_id'] = userId;
    if (projectId != null) queryParams['project_id'] = projectId;

    final response = await _client.get(
      ApiEndpoints.timeEntryReport,
      queryParameters: queryParams,
    );
    final data = response.data as Map<String, dynamic>;
    return TimeReportDTO.fromJson(data['data'] as Map<String, dynamic>);
  }
}
