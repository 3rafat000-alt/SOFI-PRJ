import 'package:dartz/dartz.dart';
import 'package:flutter/foundation.dart';
import '../../../../core/network/api_exceptions.dart';
import '../../domain/entities/project.dart';
import '../../domain/repositories/project_repository.dart';
import '../datasources/project_remote_source.dart';
import '../models/project_models.dart';

class ProjectRepositoryImpl implements ProjectRepository {
  final ProjectRemoteSource _remoteSource;

  ProjectRepositoryImpl(this._remoteSource);

  @override
  Future<Either<Exception, List<Project>>> getProjects({
    required String workspaceId,
    String status = 'active',
    String? search,
    int page = 1,
    int perPage = 20,
  }) async {
    try {
      final dtos = await _remoteSource.getProjects(
        workspaceId: workspaceId,
        status: status,
        search: search,
        page: page,
        perPage: perPage,
      );
      final projects = dtos.map((dto) => _mapToDomain(dto)).toList();
      return Right(projects);
    } on ApiException catch (e) {
      return Left(e);
    } catch (e) {
      if (kDebugMode) debugPrint('getProjects error: $e');
      return Left(Exception('Failed to load projects'));
    }
  }

  @override
  Future<Either<Exception, Project>> getProject(String id) async {
    try {
      final dto = await _remoteSource.getProject(id);
      return Right(_mapToDomain(dto));
    } on ApiException catch (e) {
      return Left(e);
    } catch (e) {
      return Left(Exception('Failed to load project'));
    }
  }

  @override
  Future<Either<Exception, Project>> createProject({
    required String workspaceId,
    required String name,
    String? description,
    String color = '#4F46E5',
    DateTime? startDate,
    DateTime? endDate,
  }) async {
    try {
      final request = CreateProjectRequest(
        workspaceId: workspaceId,
        name: name,
        description: description,
        color: color,
        startDate: startDate?.toIso8601String(),
        endDate: endDate?.toIso8601String(),
      );
      final dto = await _remoteSource.createProject(request);
      return Right(_mapToDomain(dto));
    } on ApiException catch (e) {
      return Left(e);
    } catch (e) {
      return Left(Exception('Failed to create project'));
    }
  }

  @override
  Future<Either<Exception, Project>> updateProject({
    required String id,
    String? name,
    String? description,
    String? color,
    DateTime? endDate,
  }) async {
    try {
      final request = UpdateProjectRequest(
        name: name,
        description: description,
        color: color,
        endDate: endDate?.toIso8601String(),
      );
      final dto = await _remoteSource.updateProject(id, request);
      return Right(_mapToDomain(dto));
    } on ApiException catch (e) {
      return Left(e);
    } catch (e) {
      return Left(Exception('Failed to update project'));
    }
  }

  @override
  Future<Either<Exception, void>> deleteProject(String id) async {
    try {
      await _remoteSource.deleteProject(id);
      return const Right(null);
    } on ApiException catch (e) {
      return Left(e);
    } catch (e) {
      return Left(Exception('Failed to delete project'));
    }
  }

  Project _mapToDomain(ProjectDTO dto) {
    return Project(
      id: dto.id,
      workspaceId: dto.workspaceId,
      name: dto.name,
      description: dto.description,
      color: dto.color,
      status: dto.status,
      taskCount: TaskCount(
        total: dto.taskCount.total,
        todo: dto.taskCount.todo,
        inProgress: dto.taskCount.inProgress,
        done: dto.taskCount.done,
      ),
      memberCount: dto.memberCount,
      startDate: dto.startDate != null ? DateTime.tryParse(dto.startDate!) : null,
      endDate: dto.endDate != null ? DateTime.tryParse(dto.endDate!) : null,
      createdAt: dto.createdAt != null ? DateTime.parse(dto.createdAt!) : DateTime.now(),
      updatedAt: dto.updatedAt != null ? DateTime.parse(dto.updatedAt!) : DateTime.now(),
    );
  }
}
