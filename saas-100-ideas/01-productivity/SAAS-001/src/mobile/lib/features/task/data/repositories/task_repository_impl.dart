import 'package:dartz/dartz.dart' hide Task;
import 'package:flutter/foundation.dart';
import '../../../../core/network/api_exceptions.dart';
import '../../domain/entities/task.dart';
import '../../domain/repositories/task_repository.dart';
import '../datasources/task_remote_source.dart';
import '../models/task_model.dart';

class TaskRepositoryImpl implements TaskRepository {
  final TaskRemoteSource _remoteSource;

  TaskRepositoryImpl(this._remoteSource);

  @override
  Future<Either<Exception, List<Task>>> getTasks({
    required String workspaceId,
    String? projectId,
    String? assigneeId,
    String? status,
    String? priority,
    String? search,
    String? cursor,
    int limit = 50,
  }) async {
    try {
      final dtos = await _remoteSource.getTasks(
        workspaceId: workspaceId,
        projectId: projectId,
        assigneeId: assigneeId,
        status: status,
        priority: priority,
        search: search,
        cursor: cursor,
        limit: limit,
      );
      final tasks = dtos.map((dto) => _mapToDomain(dto)).toList();
      return Right(tasks);
    } on ApiException catch (e) {
      return Left(e);
    } catch (e) {
      if (kDebugMode) debugPrint('getTasks error: $e');
      return Left(Exception('Failed to load tasks'));
    }
  }

  @override
  Future<Either<Exception, Task>> getTask(String id) async {
    try {
      final dto = await _remoteSource.getTask(id);
      return Right(_mapToDomain(dto));
    } on ApiException catch (e) {
      return Left(e);
    } catch (e) {
      return Left(Exception('Failed to load task'));
    }
  }

  @override
  Future<Either<Exception, Task>> createTask({
    required String projectId,
    required String title,
    String? description,
    String priority = 'medium',
    String? assigneeId,
    DateTime? dueDate,
    int estimatedMinutes = 0,
    List<String>? tags,
  }) async {
    try {
      final request = CreateTaskRequest(
        projectId: projectId,
        title: title,
        description: description,
        priority: priority,
        assigneeId: assigneeId,
        dueDate: dueDate?.toIso8601String(),
        estimatedMinutes: estimatedMinutes,
        tags: tags,
      );
      final dto = await _remoteSource.createTask(request);
      return Right(_mapToDomain(dto));
    } on ApiException catch (e) {
      return Left(e);
    } catch (e) {
      return Left(Exception('Failed to create task'));
    }
  }

  @override
  Future<Either<Exception, Task>> updateTask({
    required String id,
    String? title,
    String? description,
    String? status,
    String? priority,
    String? assigneeId,
    DateTime? dueDate,
    int? estimatedMinutes,
  }) async {
    try {
      final request = UpdateTaskRequest(
        title: title,
        description: description,
        status: status,
        priority: priority,
        assigneeId: assigneeId,
        dueDate: dueDate?.toIso8601String(),
        estimatedMinutes: estimatedMinutes,
      );
      final dto = await _remoteSource.updateTask(id, request);
      return Right(_mapToDomain(dto));
    } on ApiException catch (e) {
      return Left(e);
    } catch (e) {
      return Left(Exception('Failed to update task'));
    }
  }

  @override
  Future<Either<Exception, void>> deleteTask(String id) async {
    try {
      await _remoteSource.deleteTask(id);
      return const Right(null);
    } on ApiException catch (e) {
      return Left(e);
    } catch (e) {
      return Left(Exception('Failed to delete task'));
    }
  }

  @override
  Future<Either<Exception, void>> reorderTasks({
    required String projectId,
    required List<OrderEntry> orders,
  }) async {
    try {
      final request = ReorderRequest(
        projectId: projectId,
        orders: orders
            .map((o) => OrderEntryDTO(id: o.id, status: o.status, position: o.position))
            .toList(),
      );
      await _remoteSource.reorderTasks(request);
      return const Right(null);
    } on ApiException catch (e) {
      return Left(e);
    } catch (e) {
      return Left(Exception('Failed to reorder tasks'));
    }
  }

  @override
  Future<Either<Exception, Task>> quickStatusChange({
    required String id,
    required String status,
  }) async {
    try {
      final request = StatusChangeRequest(status: status);
      final dto = await _remoteSource.quickStatusChange(id, request);
      return Right(_mapToDomain(dto));
    } on ApiException catch (e) {
      return Left(e);
    } catch (e) {
      return Left(Exception('Failed to change status'));
    }
  }

  Task _mapToDomain(TaskDTO dto) {
    return Task(
      id: dto.id,
      projectId: dto.projectId,
      projectName: dto.projectName,
      title: dto.title,
      description: dto.description,
      status: dto.status,
      priority: dto.priority,
      position: dto.position,
      assignee: dto.assignee != null
          ? TaskAssignee(
              id: dto.assignee!.id,
              name: dto.assignee!.name,
              avatarUrl: dto.assignee!.avatarUrl,
            )
          : null,
      creator: dto.creator != null
          ? TaskUser(id: dto.creator!.id, name: dto.creator!.name)
          : null,
      dueDate: dto.dueDate != null ? DateTime.tryParse(dto.dueDate!) : null,
      estimatedMinutes: dto.estimatedMinutes,
      loggedMinutes: dto.loggedMinutes,
      tags: dto.tags
          .map((t) => TaskTag(id: t.id, name: t.name, color: t.color))
          .toList(),
      commentsCount: dto.commentsCount,
      attachmentsCount: dto.attachmentsCount,
      isOverdue: dto.isOverdue,
      createdAt: dto.createdAt != null ? DateTime.parse(dto.createdAt!) : DateTime.now(),
      updatedAt: dto.updatedAt != null ? DateTime.parse(dto.updatedAt!) : DateTime.now(),
    );
  }
}
