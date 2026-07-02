import 'package:equatable/equatable.dart';

class ProjectDTO extends Equatable {
  final String id;
  final String workspaceId;
  final String name;
  final String? description;
  final String color;
  final String status;
  final TaskCountDTO taskCount;
  final int memberCount;
  final String? startDate;
  final String? endDate;
  final String? createdAt;
  final String? updatedAt;

  const ProjectDTO({
    required this.id,
    required this.workspaceId,
    required this.name,
    this.description,
    this.color = '#4F46E5',
    this.status = 'active',
    TaskCountDTO? taskCount,
    this.memberCount = 0,
    this.startDate,
    this.endDate,
    this.createdAt,
    this.updatedAt,
  }) : taskCount = taskCount ?? const TaskCountDTO();

  factory ProjectDTO.fromJson(Map<String, dynamic> json) {
    final tc = json['task_count'] as Map<String, dynamic>?;
    return ProjectDTO(
      id: json['id'] as String,
      workspaceId: json['workspace_id'] as String,
      name: json['name'] as String,
      description: json['description'] as String?,
      color: json['color'] as String? ?? '#4F46E5',
      status: json['status'] as String? ?? 'active',
      taskCount: tc != null ? TaskCountDTO.fromJson(tc) : const TaskCountDTO(),
      memberCount: json['member_count'] as int? ?? 0,
      startDate: json['start_date'] as String?,
      endDate: json['end_date'] as String?,
      createdAt: json['created_at'] as String?,
      updatedAt: json['updated_at'] as String?,
    );
  }

  @override
  List<Object?> get props => [
        id, workspaceId, name, description, color, status,
        taskCount, memberCount, startDate, endDate, createdAt, updatedAt,
      ];
}

class TaskCountDTO extends Equatable {
  final int total;
  final int todo;
  final int inProgress;
  final int done;

  const TaskCountDTO({
    this.total = 0,
    this.todo = 0,
    this.inProgress = 0,
    this.done = 0,
  });

  factory TaskCountDTO.fromJson(Map<String, dynamic> json) {
    return TaskCountDTO(
      total: json['total'] as int? ?? 0,
      todo: json['todo'] as int? ?? 0,
      inProgress: json['in_progress'] as int? ?? 0,
      done: json['done'] as int? ?? 0,
    );
  }

  @override
  List<Object?> get props => [total, todo, inProgress, done];
}

class CreateProjectRequest extends Equatable {
  final String workspaceId;
  final String name;
  final String? description;
  final String color;
  final String? startDate;
  final String? endDate;

  const CreateProjectRequest({
    required this.workspaceId,
    required this.name,
    this.description,
    this.color = '#4F46E5',
    this.startDate,
    this.endDate,
  });

  Map<String, dynamic> toJson() => {
        'workspace_id': workspaceId,
        'name': name,
        if (description != null) 'description': description,
        'color': color,
        if (startDate != null) 'start_date': startDate,
        if (endDate != null) 'end_date': endDate,
      };

  @override
  List<Object?> get props => [workspaceId, name, description, color, startDate, endDate];
}

class UpdateProjectRequest extends Equatable {
  final String? name;
  final String? description;
  final String? color;
  final String? endDate;

  const UpdateProjectRequest({
    this.name,
    this.description,
    this.color,
    this.endDate,
  });

  Map<String, dynamic> toJson() => {
        if (name != null) 'name': name,
        if (description != null) 'description': description,
        if (color != null) 'color': color,
        if (endDate != null) 'end_date': endDate,
      };

  @override
  List<Object?> get props => [name, description, color, endDate];
}
