import 'package:equatable/equatable.dart';

class Project extends Equatable {
  final String id;
  final String workspaceId;
  final String name;
  final String? description;
  final String color;
  final String status;
  final TaskCount taskCount;
  final int memberCount;
  final DateTime? startDate;
  final DateTime? endDate;
  final DateTime createdAt;
  final DateTime updatedAt;

  const Project({
    required this.id,
    required this.workspaceId,
    required this.name,
    this.description,
    this.color = '#4F46E5',
    this.status = 'active',
    TaskCount? taskCount,
    this.memberCount = 0,
    this.startDate,
    this.endDate,
    required this.createdAt,
    required this.updatedAt,
  }) : taskCount = taskCount ?? const TaskCount();

  @override
  List<Object?> get props => [
        id, workspaceId, name, description, color, status,
        taskCount, memberCount, startDate, endDate, createdAt, updatedAt,
      ];
}

class TaskCount extends Equatable {
  final int total;
  final int todo;
  final int inProgress;
  final int done;

  const TaskCount({
    this.total = 0,
    this.todo = 0,
    this.inProgress = 0,
    this.done = 0,
  });

  @override
  List<Object?> get props => [total, todo, inProgress, done];
}
