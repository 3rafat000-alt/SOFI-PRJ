import 'package:dio/dio.dart';
import '../../../../core/network/dio_client.dart';
import '../../../../core/network/api_endpoints.dart';
import '../models/project_models.dart';

class ProjectRemoteSource {
  final DioClient _client;

  ProjectRemoteSource(this._client);

  Future<List<ProjectDTO>> getProjects({
    required String workspaceId,
    String status = 'active',
    String? search,
    int page = 1,
    int perPage = 20,
  }) async {
    final queryParams = <String, dynamic>{
      'workspace_id': workspaceId,
      'status': status,
      'page': page,
      'per_page': perPage,
    };
    if (search != null) queryParams['search'] = search;

    final response = await _client.get(
      ApiEndpoints.projects,
      queryParameters: queryParams,
    );
    final data = response.data as Map<String, dynamic>;
    final list = data['data'] as List<dynamic>;
    return list.map((e) => ProjectDTO.fromJson(e as Map<String, dynamic>)).toList();
  }

  Future<ProjectDTO> getProject(String id) async {
    final response = await _client.get(ApiEndpoints.project(id));
    final data = response.data as Map<String, dynamic>;
    return ProjectDTO.fromJson(data['data'] as Map<String, dynamic>);
  }

  Future<ProjectDTO> createProject(CreateProjectRequest request) async {
    final response = await _client.post(
      ApiEndpoints.projects,
      data: request.toJson(),
    );
    final data = response.data as Map<String, dynamic>;
    return ProjectDTO.fromJson(data['data'] as Map<String, dynamic>);
  }

  Future<ProjectDTO> updateProject(String id, UpdateProjectRequest request) async {
    final response = await _client.put(
      ApiEndpoints.project(id),
      data: request.toJson(),
    );
    final data = response.data as Map<String, dynamic>;
    return ProjectDTO.fromJson(data['data'] as Map<String, dynamic>);
  }

  Future<void> deleteProject(String id) async {
    await _client.delete(ApiEndpoints.project(id));
  }
}
