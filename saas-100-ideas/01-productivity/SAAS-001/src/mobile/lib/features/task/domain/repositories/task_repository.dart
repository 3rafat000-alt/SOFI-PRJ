import 'package:dartz/dartz.dart' hide Task;
import '../entities/task.dart';

abstract class TaskRepository {
  Future<Either<Exception, List<Task>>> getTasks({
    required String workspaceId,
    String? projectId,
    String? assigneeId,
    String? status,
    String? priority,
    String? search,
    String? cursor,
    int limit = 50,
  });

  Future<Either<Exception, Task>> getTask(String id);

  Future<Either<Exception, Task>> createTask({
    required String projectId,
    required String title,
    String? description,
    String priority = 'medium',
    String? assigneeId,
    DateTime? dueDate,
    int estimatedMinutes = 0,
    List<String>? tags,
  });

  Future<Either<Exception, Task>> updateTask({
    required String id,
    String? title,
    String? description,
    String? status,
    String? priority,
    String? assigneeId,
    DateTime? dueDate,
    int? estimatedMinutes,
  });

  Future<Either<Exception, void>> deleteTask(String id);

  Future<Either<Exception, void>> reorderTasks({
    required String projectId,
    required List<OrderEntry> orders,
  });

  Future<Either<Exception, Task>> quickStatusChange({
    required String id,
    required String status,
  });
}

class OrderEntry {
  final String id;
  final String status;
  final int position;

  const OrderEntry({
    required this.id,
    required this.status,
    required this.position,
  });
}
