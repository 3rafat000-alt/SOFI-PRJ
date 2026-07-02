import 'package:dartz/dartz.dart';
import 'package:flutter/foundation.dart';
import '../../../../core/network/api_exceptions.dart';
import '../../domain/entities/time_entry.dart';
import '../../domain/repositories/time_entry_repository.dart';
import '../datasources/time_entry_remote_source.dart';
import '../models/time_entry_models.dart';

class TimeEntryRepositoryImpl implements TimeEntryRepository {
  final TimeEntryRemoteSource _remoteSource;

  TimeEntryRepositoryImpl(this._remoteSource);

  @override
  Future<Either<Exception, TimeEntry>> startTimer({
    required String taskId,
    String? note,
  }) async {
    try {
      final request = StartTimerRequest(taskId: taskId, note: note);
      final dto = await _remoteSource.startTimer(request);
      return Right(_mapToDomain(dto));
    } on ApiException catch (e) {
      return Left(e);
    } catch (e) {
      return Left(Exception('Failed to start timer'));
    }
  }

  @override
  Future<Either<Exception, TimeEntry>> stopTimer({String? note}) async {
    try {
      final request = StopTimerRequest(note: note);
      final dto = await _remoteSource.stopTimer(request);
      return Right(_mapToDomain(dto));
    } on ApiException catch (e) {
      return Left(e);
    } catch (e) {
      return Left(Exception('Failed to stop timer'));
    }
  }

  @override
  Future<Either<Exception, List<TimeEntry>>> getTimeEntries({
    String? userId,
    String? taskId,
    DateTime? from,
    DateTime? to,
    int page = 1,
  }) async {
    try {
      final dtos = await _remoteSource.getTimeEntries(
        userId: userId,
        taskId: taskId,
        from: from?.toIso8601String(),
        to: to?.toIso8601String(),
        page: page,
      );
      final entries = dtos.map((dto) => _mapToDomain(dto)).toList();
      return Right(entries);
    } on ApiException catch (e) {
      return Left(e);
    } catch (e) {
      return Left(Exception('Failed to load time entries'));
    }
  }

  @override
  Future<Either<Exception, TimeEntry>> createManualEntry({
    required String taskId,
    required DateTime startedAt,
    required DateTime endedAt,
    String? note,
  }) async {
    try {
      final request = ManualTimeEntryRequest(
        taskId: taskId,
        startedAt: startedAt.toIso8601String(),
        endedAt: endedAt.toIso8601String(),
        note: note,
      );
      final dto = await _remoteSource.createManualEntry(request);
      return Right(_mapToDomain(dto));
    } on ApiException catch (e) {
      return Left(e);
    } catch (e) {
      return Left(Exception('Failed to create time entry'));
    }
  }

  @override
  Future<Either<Exception, TimeEntry>> updateTimeEntry({
    required String id,
    DateTime? startedAt,
    DateTime? endedAt,
    String? note,
  }) async {
    try {
      // Partial update via manual entry shape
      final request = ManualTimeEntryRequest(
        taskId: '',
        startedAt: startedAt?.toIso8601String() ?? '',
        endedAt: endedAt?.toIso8601String() ?? '',
        note: note,
      );
      final dto = await _remoteSource.updateTimeEntry(id, request);
      return Right(_mapToDomain(dto));
    } on ApiException catch (e) {
      return Left(e);
    } catch (e) {
      return Left(Exception('Failed to update time entry'));
    }
  }

  @override
  Future<Either<Exception, void>> deleteTimeEntry(String id) async {
    try {
      await _remoteSource.deleteTimeEntry(id);
      return const Right(null);
    } on ApiException catch (e) {
      return Left(e);
    } catch (e) {
      return Left(Exception('Failed to delete time entry'));
    }
  }

  @override
  Future<Either<Exception, TimeReport>> getTimeReport({
    required String workspaceId,
    required DateTime from,
    required DateTime to,
    String? groupBy,
    String? userId,
    String? projectId,
  }) async {
    try {
      final dto = await _remoteSource.getTimeReport(
        workspaceId: workspaceId,
        from: from.toIso8601String(),
        to: to.toIso8601String(),
        groupBy: groupBy,
        userId: userId,
        projectId: projectId,
      );
      return Right(TimeReport(
        totalMinutes: dto.totalMinutes,
        totalHours: dto.totalHours,
        billableMinutes: dto.billableMinutes,
        avgDailyHours: dto.avgDailyHours,
        periodFrom: dto.periodFrom != null ? DateTime.parse(dto.periodFrom!) : from,
        periodTo: dto.periodTo != null ? DateTime.parse(dto.periodTo!) : to,
        entries: dto.entries.map((e) => _mapReportEntry(e)).toList(),
      ));
    } on ApiException catch (e) {
      return Left(e);
    } catch (e) {
      return Left(Exception('Failed to load report'));
    }
  }

  TimeEntry _mapToDomain(TimeEntryDTO dto) {
    return TimeEntry(
      id: dto.id,
      taskId: dto.taskId,
      taskTitle: dto.taskTitle,
      projectName: dto.projectName,
      userId: dto.userId,
      userName: dto.userName,
      startedAt: dto.startedAt != null ? DateTime.parse(dto.startedAt!) : null,
      endedAt: dto.endedAt != null ? DateTime.parse(dto.endedAt!) : null,
      durationMinutes: dto.durationMinutes,
      note: dto.note,
      isRunning: dto.isRunning,
      isManual: dto.isManual,
      createdAt: dto.createdAt != null ? DateTime.parse(dto.createdAt!) : DateTime.now(),
    );
  }

  ReportEntry _mapReportEntry(ReportEntryDTO dto) {
    return ReportEntry(
      date: dto.date != null ? DateTime.parse(dto.date!) : DateTime.now(),
      minutes: dto.minutes,
      projects: dto.projects
          .map((p) => ReportProject(
                projectId: p.projectId,
                projectName: p.projectName,
                minutes: p.minutes,
              ))
          .toList(),
    );
  }
}
