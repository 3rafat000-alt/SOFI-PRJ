import 'package:dartz/dartz.dart';
import 'package:flutter/foundation.dart';
import '../../../../core/network/api_exceptions.dart';
import '../../domain/entities/workspace.dart';
import '../../domain/repositories/workspace_repository.dart';
import '../datasources/workspace_remote_source.dart';
import '../models/workspace_models.dart';

class WorkspaceRepositoryImpl implements WorkspaceRepository {
  final WorkspaceRemoteSource _remoteSource;

  WorkspaceRepositoryImpl(this._remoteSource);

  @override
  Future<Either<Exception, List<Workspace>>> getWorkspaces() async {
    try {
      final dtos = await _remoteSource.getWorkspaces();
      final workspaces = dtos.map((dto) => _mapToDomain(dto)).toList();
      return Right(workspaces);
    } on ApiException catch (e) {
      return Left(e);
    } catch (e) {
      if (kDebugMode) debugPrint('getWorkspaces error: $e');
      return Left(Exception('Failed to load workspaces'));
    }
  }

  @override
  Future<Either<Exception, Workspace>> createWorkspace({
    required String name,
    String? description,
    String timezone = 'Asia/Riyadh',
  }) async {
    try {
      final request = CreateWorkspaceRequest(
        name: name,
        description: description,
        timezone: timezone,
      );
      final dto = await _remoteSource.createWorkspace(request);
      return Right(_mapToDomain(dto));
    } on ApiException catch (e) {
      return Left(e);
    } catch (e) {
      if (kDebugMode) debugPrint('createWorkspace error: $e');
      return Left(Exception('Failed to create workspace'));
    }
  }

  @override
  Future<Either<Exception, Workspace>> updateWorkspace({
    required String id,
    String? name,
    String? description,
  }) async {
    try {
      final request = UpdateWorkspaceRequest(name: name, description: description);
      final dto = await _remoteSource.updateWorkspace(id, request);
      return Right(_mapToDomain(dto));
    } on ApiException catch (e) {
      return Left(e);
    } catch (e) {
      return Left(Exception('Failed to update workspace'));
    }
  }

  @override
  Future<Either<Exception, void>> deleteWorkspace(String id) async {
    try {
      await _remoteSource.deleteWorkspace(id);
      return const Right(null);
    } on ApiException catch (e) {
      return Left(e);
    } catch (e) {
      return Left(Exception('Failed to delete workspace'));
    }
  }

  @override
  Future<Either<Exception, List<WorkspaceMember>>> getMembers(String workspaceId) async {
    try {
      final dtos = await _remoteSource.getMembers(workspaceId);
      final members = dtos.map((dto) => WorkspaceMember(
            id: dto.id,
            name: dto.name,
            email: dto.email,
            avatarUrl: dto.avatarUrl,
            role: dto.role,
            joinedAt: dto.joinedAt != null ? DateTime.parse(dto.joinedAt!) : DateTime.now(),
            taskCount: dto.taskCount,
          )).toList();
      return Right(members);
    } on ApiException catch (e) {
      return Left(e);
    } catch (e) {
      return Left(Exception('Failed to load members'));
    }
  }

  @override
  Future<Either<Exception, Invitation>> inviteMember({
    required String workspaceId,
    required String email,
    String role = 'member',
    String? message,
    String channel = 'email',
  }) async {
    try {
      final request = InviteRequest(
        email: email,
        role: role,
        message: message,
        channel: channel,
      );
      final dto = await _remoteSource.inviteMember(workspaceId, request);
      return Right(Invitation(
        id: dto.id,
        email: dto.email,
        role: dto.role,
        status: dto.status,
        channel: dto.channel,
        expiresAt: dto.expiresAt != null ? DateTime.parse(dto.expiresAt!) : DateTime.now().add(const Duration(days: 30)),
        createdAt: dto.createdAt != null ? DateTime.parse(dto.createdAt!) : DateTime.now(),
      ));
    } on ApiException catch (e) {
      return Left(e);
    } catch (e) {
      return Left(Exception('Failed to invite member'));
    }
  }

  Workspace _mapToDomain(WorkspaceDTO dto) {
    return Workspace(
      id: dto.id,
      name: dto.name,
      slug: dto.slug,
      description: dto.description,
      logoUrl: dto.logoUrl,
      role: dto.role,
      memberCount: dto.memberCount,
      projectCount: dto.projectCount,
      plan: dto.plan,
      createdAt: dto.createdAt != null ? DateTime.parse(dto.createdAt!) : DateTime.now(),
    );
  }
}
