import 'package:dio/dio.dart';
import '../../../../core/network/dio_client.dart';
import '../../../../core/network/api_endpoints.dart';
import '../models/workspace_models.dart';

class WorkspaceRemoteSource {
  final DioClient _client;

  WorkspaceRemoteSource(this._client);

  Future<List<WorkspaceDTO>> getWorkspaces() async {
    final response = await _client.get(ApiEndpoints.workspaces);
    final data = response.data as Map<String, dynamic>;
    final list = data['data'] as List<dynamic>;
    return list.map((e) => WorkspaceDTO.fromJson(e as Map<String, dynamic>)).toList();
  }

  Future<WorkspaceDTO> createWorkspace(CreateWorkspaceRequest request) async {
    final response = await _client.post(
      ApiEndpoints.workspaces,
      data: request.toJson(),
    );
    final data = response.data as Map<String, dynamic>;
    return WorkspaceDTO.fromJson(data['data'] as Map<String, dynamic>);
  }

  Future<WorkspaceDTO> updateWorkspace(String id, UpdateWorkspaceRequest request) async {
    final response = await _client.put(
      ApiEndpoints.workspace(id),
      data: request.toJson(),
    );
    final data = response.data as Map<String, dynamic>;
    return WorkspaceDTO.fromJson(data['data'] as Map<String, dynamic>);
  }

  Future<void> deleteWorkspace(String id) async {
    await _client.delete(ApiEndpoints.workspace(id));
  }

  Future<List<WorkspaceMemberDTO>> getMembers(String workspaceId) async {
    final response = await _client.get(ApiEndpoints.workspaceMembers(workspaceId));
    final data = response.data as Map<String, dynamic>;
    final list = data['data'] as List<dynamic>;
    return list.map((e) => WorkspaceMemberDTO.fromJson(e as Map<String, dynamic>)).toList();
  }

  Future<InvitationDTO> inviteMember(String workspaceId, InviteRequest request) async {
    final response = await _client.post(
      ApiEndpoints.workspaceInvite(workspaceId),
      data: request.toJson(),
    );
    final data = response.data as Map<String, dynamic>;
    final inviteData = data['data'] as Map<String, dynamic>;
    return InvitationDTO.fromJson(inviteData);
  }
}
