import 'package:dartz/dartz.dart' hide Task;
import '../repositories/task_repository.dart';
import '../entities/task.dart';

class CreateTaskUseCase {
  final TaskRepository _repository;

  CreateTaskUseCase(this._repository);

  Future<Either<Exception, Task>> execute({
    required String projectId,
    required String title,
    String? description,
    String priority = 'medium',
    String? assigneeId,
    DateTime? dueDate,
    int estimatedMinutes = 0,
    List<String>? tags,
  }) {
    return _repository.createTask(
      projectId: projectId,
      title: title,
      description: description,
      priority: priority,
      assigneeId: assigneeId,
      dueDate: dueDate,
      estimatedMinutes: estimatedMinutes,
      tags: tags,
    );
  }
}
