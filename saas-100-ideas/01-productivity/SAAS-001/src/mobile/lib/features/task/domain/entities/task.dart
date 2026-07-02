import 'package:equatable/equatable.dart';

class Task extends Equatable {
  final String id;
  final String projectId;
  final String? projectName;
  final String title;
  final String? description;
  final String status;
  final String priority;
  final int position;
  final TaskAssignee? assignee;
  final TaskUser? creator;
  final DateTime? dueDate;
  final int estimatedMinutes;
  final int loggedMinutes;
  final List<TaskTag> tags;
  final int commentsCount;
  final int attachmentsCount;
  final bool isOverdue;
  final DateTime createdAt;
  final DateTime updatedAt;

  const Task({
    required this.id,
    required this.projectId,
    this.projectName,
    required this.title,
    this.description,
    this.status = 'todo',
    this.priority = 'medium',
    this.position = 0,
    this.assignee,
    this.creator,
    this.dueDate,
    this.estimatedMinutes = 0,
    this.loggedMinutes = 0,
    this.tags = const [],
    this.commentsCount = 0,
    this.attachmentsCount = 0,
    this.isOverdue = false,
    required this.createdAt,
    required this.updatedAt,
  });

  @override
  List<Object?> get props => [
        id, projectId, projectName, title, description, status, priority,
        position, assignee, creator, dueDate, estimatedMinutes, loggedMinutes,
        tags, commentsCount, attachmentsCount, isOverdue, createdAt, updatedAt,
      ];

  Task copyWith({
    String? id,
    String? projectId,
    String? projectName,
    String? title,
    String? description,
    String? status,
    String? priority,
    int? position,
    TaskAssignee? assignee,
    TaskUser? creator,
    DateTime? dueDate,
    int? estimatedMinutes,
    int? loggedMinutes,
    List<TaskTag>? tags,
    int? commentsCount,
    int? attachmentsCount,
    bool? isOverdue,
    DateTime? createdAt,
    DateTime? updatedAt,
  }) {
    return Task(
      id: id ?? this.id,
      projectId: projectId ?? this.projectId,
      projectName: projectName ?? this.projectName,
      title: title ?? this.title,
      description: description ?? this.description,
      status: status ?? this.status,
      priority: priority ?? this.priority,
      position: position ?? this.position,
      assignee: assignee ?? this.assignee,
      creator: creator ?? this.creator,
      dueDate: dueDate ?? this.dueDate,
      estimatedMinutes: estimatedMinutes ?? this.estimatedMinutes,
      loggedMinutes: loggedMinutes ?? this.loggedMinutes,
      tags: tags ?? this.tags,
      commentsCount: commentsCount ?? this.commentsCount,
      attachmentsCount: attachmentsCount ?? this.attachmentsCount,
      isOverdue: isOverdue ?? this.isOverdue,
      createdAt: createdAt ?? this.createdAt,
      updatedAt: updatedAt ?? this.updatedAt,
    );
  }
}

class TaskAssignee extends Equatable {
  final String id;
  final String name;
  final String? avatarUrl;

  const TaskAssignee({
    required this.id,
    required this.name,
    this.avatarUrl,
  });

  @override
  List<Object?> get props => [id, name, avatarUrl];
}

class TaskUser extends Equatable {
  final String id;
  final String name;

  const TaskUser({required this.id, required this.name});

  @override
  List<Object?> get props => [id, name];
}

class TaskTag extends Equatable {
  final String id;
  final String name;
  final String color;

  const TaskTag({
    required this.id,
    required this.name,
    this.color = '#3B82F6',
  });

  @override
  List<Object?> get props => [id, name, color];
}
