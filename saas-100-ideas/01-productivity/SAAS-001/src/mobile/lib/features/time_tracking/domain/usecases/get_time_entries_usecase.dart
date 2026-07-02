import 'package:dartz/dartz.dart';
import '../repositories/time_entry_repository.dart';
import '../entities/time_entry.dart';

class GetTimeEntriesUseCase {
  final TimeEntryRepository _repository;

  GetTimeEntriesUseCase(this._repository);

  Future<Either<Exception, List<TimeEntry>>> execute({
    String? userId,
    String? taskId,
    DateTime? from,
    DateTime? to,
    int page = 1,
  }) {
    return _repository.getTimeEntries(
      userId: userId,
      taskId: taskId,
      from: from,
      to: to,
      page: page,
    );
  }
}
