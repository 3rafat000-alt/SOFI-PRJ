import 'package:dartz/dartz.dart' hide Task;
import '../repositories/task_repository.dart';
import '../entities/task.dart';

class GetTasksUseCase {
  final TaskRepository _repository;

  GetTasksUseCase(this._repository);

  Future<Either<Exception, List<Task>>> execute({
    required String workspaceId,
    String? projectId,
    String? assigneeId,
    String? status,
    String? priority,
    String? search,
    String? cursor,
    int limit = 50,
  }) {
    return _repository.getTasks(
      workspaceId: workspaceId,
      projectId: projectId,
      assigneeId: assigneeId,
      status: status,
      priority: priority,
      search: search,
      cursor: cursor,
      limit: limit,
    );
  }
}
