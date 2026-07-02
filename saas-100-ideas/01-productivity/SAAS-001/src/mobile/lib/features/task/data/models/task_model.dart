import 'package:equatable/equatable.dart';

class TaskDTO extends Equatable {
  final String id;
  final String projectId;
  final String? projectName;
  final String title;
  final String? description;
  final String status;
  final String priority;
  final int position;
  final AssigneeDTO? assignee;
  final CreatorDTO? creator;
  final String? dueDate;
  final int estimatedMinutes;
  final int loggedMinutes;
  final List<TagDTO> tags;
  final int commentsCount;
  final int attachmentsCount;
  final bool isOverdue;
  final String? createdAt;
  final String? updatedAt;

  const TaskDTO({
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
    this.createdAt,
    this.updatedAt,
  });

  factory TaskDTO.fromJson(Map<String, dynamic> json) {
    return TaskDTO(
      id: json['id'] as String,
      projectId: json['project_id'] as String,
      projectName: json['project_name'] as String?,
      title: json['title'] as String,
      description: json['description'] as String?,
      status: json['status'] as String? ?? 'todo',
      priority: json['priority'] as String? ?? 'medium',
      position: json['position'] as int? ?? 0,
      assignee: json['assignee'] != null
          ? AssigneeDTO.fromJson(json['assignee'] as Map<String, dynamic>)
          : null,
      creator: json['creator'] != null
          ? CreatorDTO.fromJson(json['creator'] as Map<String, dynamic>)
          : null,
      dueDate: json['due_date'] as String?,
      estimatedMinutes: json['estimated_minutes'] as int? ?? 0,
      loggedMinutes: json['logged_minutes'] as int? ?? 0,
      tags: (json['tags'] as List<dynamic>?)
              ?.map((e) => TagDTO.fromJson(e as Map<String, dynamic>))
              .toList() ??
          [],
      commentsCount: json['comments_count'] as int? ?? 0,
      attachmentsCount: json['attachments_count'] as int? ?? 0,
      isOverdue: json['is_overdue'] as bool? ?? false,
      createdAt: json['created_at'] as String?,
      updatedAt: json['updated_at'] as String?,
    );
  }

  Map<String, dynamic> toJson() => {
        'id': id,
        'project_id': projectId,
        'project_name': projectName,
        'title': title,
        'description': description,
        'status': status,
        'priority': priority,
        'position': position,
        'assignee': assignee?.toJson(),
        'creator': creator?.toJson(),
        'due_date': dueDate,
        'estimated_minutes': estimatedMinutes,
        'logged_minutes': loggedMinutes,
        'tags': tags.map((e) => e.toJson()).toList(),
        'comments_count': commentsCount,
        'attachments_count': attachmentsCount,
        'is_overdue': isOverdue,
        'created_at': createdAt,
        'updated_at': updatedAt,
      };

  @override
  List<Object?> get props => [
        id, projectId, projectName, title, description, status, priority,
        position, assignee, creator, dueDate, estimatedMinutes, loggedMinutes,
        tags, commentsCount, attachmentsCount, isOverdue, createdAt, updatedAt,
      ];
}

class AssigneeDTO extends Equatable {
  final String id;
  final String name;
  final String? avatarUrl;

  const AssigneeDTO({required this.id, required this.name, this.avatarUrl});

  factory AssigneeDTO.fromJson(Map<String, dynamic> json) {
    return AssigneeDTO(
      id: json['id'] as String,
      name: json['name'] as String,
      avatarUrl: json['avatar_url'] as String?,
    );
  }

  Map<String, dynamic> toJson() => {
        'id': id,
        'name': name,
        'avatar_url': avatarUrl,
      };

  @override
  List<Object?> get props => [id, name, avatarUrl];
}

class CreatorDTO extends Equatable {
  final String id;
  final String name;

  const CreatorDTO({required this.id, required this.name});

  factory CreatorDTO.fromJson(Map<String, dynamic> json) {
    return CreatorDTO(
      id: json['id'] as String,
      name: json['name'] as String,
    );
  }

  Map<String, dynamic> toJson() => {'id': id, 'name': name};

  @override
  List<Object?> get props => [id, name];
}

class TagDTO extends Equatable {
  final String id;
  final String name;
  final String color;

  const TagDTO({
    required this.id,
    required this.name,
    this.color = '#3B82F6',
  });

  factory TagDTO.fromJson(Map<String, dynamic> json) {
    return TagDTO(
      id: json['id'] as String,
      name: json['name'] as String,
      color: json['color'] as String? ?? '#3B82F6',
    );
  }

  Map<String, dynamic> toJson() => {
        'id': id,
        'name': name,
        'color': color,
      };

  @override
  List<Object?> get props => [id, name, color];
}

class CreateTaskRequest extends Equatable {
  final String projectId;
  final String title;
  final String? description;
  final String priority;
  final String? assigneeId;
  final String? dueDate;
  final int estimatedMinutes;
  final List<String>? tags;

  const CreateTaskRequest({
    required this.projectId,
    required this.title,
    this.description,
    this.priority = 'medium',
    this.assigneeId,
    this.dueDate,
    this.estimatedMinutes = 0,
    this.tags,
  });

  Map<String, dynamic> toJson() => {
        'project_id': projectId,
        'title': title,
        if (description != null) 'description': description,
        'priority': priority,
        if (assigneeId != null) 'assignee_id': assigneeId,
        if (dueDate != null) 'due_date': dueDate,
        'estimated_minutes': estimatedMinutes,
        if (tags != null) 'tags': tags,
      };

  @override
  List<Object?> get props => [
        projectId, title, description, priority, assigneeId,
        dueDate, estimatedMinutes, tags,
      ];
}

class UpdateTaskRequest extends Equatable {
  final String? title;
  final String? description;
  final String? status;
  final String? priority;
  final String? assigneeId;
  final String? dueDate;
  final int? estimatedMinutes;

  const UpdateTaskRequest({
    this.title,
    this.description,
    this.status,
    this.priority,
    this.assigneeId,
    this.dueDate,
    this.estimatedMinutes,
  });

  Map<String, dynamic> toJson() => {
        if (title != null) 'title': title,
        if (description != null) 'description': description,
        if (status != null) 'status': status,
        if (priority != null) 'priority': priority,
        if (assigneeId != null) 'assignee_id': assigneeId,
        if (dueDate != null) 'due_date': dueDate,
        if (estimatedMinutes != null) 'estimated_minutes': estimatedMinutes,
      };

  @override
  List<Object?> get props => [
        title, description, status, priority, assigneeId, dueDate, estimatedMinutes,
      ];
}

class ReorderRequest extends Equatable {
  final String projectId;
  final List<OrderEntryDTO> orders;

  const ReorderRequest({
    required this.projectId,
    required this.orders,
  });

  Map<String, dynamic> toJson() => {
        'project_id': projectId,
        'orders': orders.map((o) => o.toJson()).toList(),
      };

  @override
  List<Object?> get props => [projectId, orders];
}

class OrderEntryDTO extends Equatable {
  final String id;
  final String status;
  final int position;

  const OrderEntryDTO({
    required this.id,
    required this.status,
    required this.position,
  });

  Map<String, dynamic> toJson() => {
        'id': id,
        'status': status,
        'position': position,
      };

  @override
  List<Object?> get props => [id, status, position];
}

class StatusChangeRequest extends Equatable {
  final String status;

  const StatusChangeRequest({required this.status});

  Map<String, dynamic> toJson() => {'status': status};

  @override
  List<Object?> get props => [status];
}
