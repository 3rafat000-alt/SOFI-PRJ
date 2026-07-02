import 'package:dartz/dartz.dart';
import '../repositories/task_repository.dart';

class ReorderTaskUseCase {
  final TaskRepository _repository;

  ReorderTaskUseCase(this._repository);

  Future<Either<Exception, void>> execute({
    required String projectId,
    required List<OrderEntry> orders,
  }) {
    return _repository.reorderTasks(
      projectId: projectId,
      orders: orders,
    );
  }
}
