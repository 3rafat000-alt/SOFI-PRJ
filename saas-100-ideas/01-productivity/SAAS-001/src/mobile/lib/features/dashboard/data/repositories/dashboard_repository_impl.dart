import 'package:dartz/dartz.dart';
import 'package:flutter/foundation.dart';
import '../../../../core/network/api_exceptions.dart';
import '../../domain/entities/dashboard.dart';
import '../../domain/repositories/dashboard_repository.dart';
import '../datasources/dashboard_remote_source.dart';

class DashboardRepositoryImpl implements DashboardRepository {
  final DashboardRemoteSource _remoteSource;

  DashboardRepositoryImpl(this._remoteSource);

  @override
  Future<Either<Exception, DashboardStats>> getStats({
    required String workspaceId,
  }) async {
    try {
      final dto = await _remoteSource.getStats(workspaceId);
      return Right(DashboardStats(
        tasks: TaskStats(
          total: dto.tasks.total,
          todo: dto.tasks.todo,
          inProgress: dto.tasks.inProgress,
          done: dto.tasks.done,
          overdue: dto.tasks.overdue,
          upcomingWeek: dto.tasks.upcomingWeek,
        ),
        projects: ProjectStats(
          active: dto.projects.active,
          archived: dto.projects.archived,
        ),
        time: TimeStats(
          todayMinutes: dto.time.todayMinutes,
          weekMinutes: dto.time.weekMinutes,
          monthMinutes: dto.time.monthMinutes,
        ),
        members: MemberStats(
          total: dto.members.total,
          activeToday: dto.members.activeToday,
        ),
      ));
    } on ApiException catch (e) {
      return Left(e);
    } catch (e) {
      return Left(Exception('Failed to load dashboard stats'));
    }
  }

  @override
  Future<Either<Exception, List<ActivityItem>>> getActivity({
    required String workspaceId,
    int limit = 20,
  }) async {
    try {
      final dtos = await _remoteSource.getActivity(workspaceId, limit);
      final items = dtos.map((dto) => ActivityItem(
            id: dto.id,
            type: dto.type,
            userName: dto.user.name,
            description: dto.description,
            projectName: dto.projectName,
            createdAt: dto.createdAt != null
                ? DateTime.parse(dto.createdAt!)
                : DateTime.now(),
          )).toList();
      return Right(items);
    } on ApiException catch (e) {
      return Left(e);
    } catch (e) {
      return Left(Exception('Failed to load activity'));
    }
  }
}
