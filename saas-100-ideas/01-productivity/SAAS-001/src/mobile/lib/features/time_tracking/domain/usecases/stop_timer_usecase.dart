import 'package:dartz/dartz.dart';
import '../repositories/time_entry_repository.dart';
import '../entities/time_entry.dart';

class StopTimerUseCase {
  final TimeEntryRepository _repository;

  StopTimerUseCase(this._repository);

  Future<Either<Exception, TimeEntry>> execute({String? note}) {
    return _repository.stopTimer(note: note);
  }
}
