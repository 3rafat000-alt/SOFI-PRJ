import 'package:dartz/dartz.dart';
import '../repositories/time_entry_repository.dart';
import '../entities/time_entry.dart';

class StartTimerUseCase {
  final TimeEntryRepository _repository;

  StartTimerUseCase(this._repository);

  Future<Either<Exception, TimeEntry>> execute({
    required String taskId,
    String? note,
  }) {
    return _repository.startTimer(taskId: taskId, note: note);
  }
}
