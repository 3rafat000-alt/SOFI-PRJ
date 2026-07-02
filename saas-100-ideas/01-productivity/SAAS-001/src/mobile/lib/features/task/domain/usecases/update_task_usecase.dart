import 'package:dartz/dartz.dart' hide Task;
import '../repositories/task_repository.dart';
import '../entities/task.dart';

class UpdateTaskUseCase {
  final TaskRepository _repository;

  UpdateTaskUseCase(this._repository);

  Future<Either<Exception, Task>> execute({
    required String id,
    String? title,
    String? description,
    String? status,
    String? priority,
    String? assigneeId,
    DateTime? dueDate,
    int? estimatedMinutes,
  }) {
    return _repository.updateTask(
      id: id,
      title: title,
      description: description,
      status: status,
      priority: priority,
      assigneeId: assigneeId,
      dueDate: dueDate,
      estimatedMinutes: estimatedMinutes,
    );
  }
}
