import 'package:dio/dio.dart';
import '../../../../core/network/dio_client.dart';
import '../../../../core/network/api_endpoints.dart';
import '../models/task_model.dart';

class TaskRemoteSource {
  final DioClient _client;

  TaskRemoteSource(this._client);

  Future<List<TaskDTO>> getTasks({
    required String workspaceId,
    String? projectId,
    String? assigneeId,
    String? status,
    String? priority,
    String? search,
    String? cursor,
    int limit = 50,
  }) async {
    final queryParams = <String, dynamic>{
      'workspace_id': workspaceId,
      'limit': limit,
    };
    if (projectId != null) queryParams['project_id'] = projectId;
    if (assigneeId != null) queryParams['assignee_id'] = assigneeId;
    if (status != null) queryParams['status'] = status;
    if (priority != null) queryParams['priority'] = priority;
    if (search != null) queryParams['search'] = search;
    if (cursor != null) queryParams['cursor'] = cursor;

    final response = await _client.get(
      ApiEndpoints.tasks,
      queryParameters: queryParams,
    );
    final data = response.data as Map<String, dynamic>;
    final list = data['data'] as List<dynamic>;
    return list.map((e) => TaskDTO.fromJson(e as Map<String, dynamic>)).toList();
  }

  Future<TaskDTO> getTask(String id) async {
    final response = await _client.get(ApiEndpoints.task(id));
    final data = response.data as Map<String, dynamic>;
    return TaskDTO.fromJson(data['data'] as Map<String, dynamic>);
  }

  Future<TaskDTO> createTask(CreateTaskRequest request) async {
    final response = await _client.post(
      ApiEndpoints.tasks,
      data: request.toJson(),
    );
    final data = response.data as Map<String, dynamic>;
    return TaskDTO.fromJson(data['data'] as Map<String, dynamic>);
  }

  Future<TaskDTO> updateTask(String id, UpdateTaskRequest request) async {
    final response = await _client.put(
      ApiEndpoints.task(id),
      data: request.toJson(),
    );
    final data = response.data as Map<String, dynamic>;
    return TaskDTO.fromJson(data['data'] as Map<String, dynamic>);
  }

  Future<void> deleteTask(String id) async {
    await _client.delete(ApiEndpoints.task(id));
  }

  Future<void> reorderTasks(ReorderRequest request) async {
    await _client.put(
      ApiEndpoints.reorderTasks,
      data: request.toJson(),
    );
  }

  Future<TaskDTO> quickStatusChange(String id, StatusChangeRequest request) async {
    final response = await _client.patch(
      ApiEndpoints.taskStatus(id),
      data: request.toJson(),
    );
    final data = response.data as Map<String, dynamic>;
    return TaskDTO.fromJson(data['data'] as Map<String, dynamic>);
  }
}
