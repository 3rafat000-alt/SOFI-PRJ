import 'package:dartz/dartz.dart';
import '../entities/time_entry.dart';

abstract class TimeEntryRepository {
  Future<Either<Exception, TimeEntry>> startTimer({
    required String taskId,
    String? note,
  });

  Future<Either<Exception, TimeEntry>> stopTimer({
    String? note,
  });

  Future<Either<Exception, List<TimeEntry>>> getTimeEntries({
    String? userId,
    String? taskId,
    DateTime? from,
    DateTime? to,
    int page = 1,
  });

  Future<Either<Exception, TimeEntry>> createManualEntry({
    required String taskId,
    required DateTime startedAt,
    required DateTime endedAt,
    String? note,
  });

  Future<Either<Exception, TimeEntry>> updateTimeEntry({
    required String id,
    DateTime? startedAt,
    DateTime? endedAt,
    String? note,
  });

  Future<Either<Exception, void>> deleteTimeEntry(String id);

  Future<Either<Exception, TimeReport>> getTimeReport({
    required String workspaceId,
    required DateTime from,
    required DateTime to,
    String? groupBy,
    String? userId,
    String? projectId,
  });
}
