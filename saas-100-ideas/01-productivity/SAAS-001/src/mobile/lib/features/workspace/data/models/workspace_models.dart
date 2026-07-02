import 'package:equatable/equatable.dart';

class WorkspaceDTO extends Equatable {
  final String id;
  final String name;
  final String slug;
  final String? description;
  final String? logoUrl;
  final String role;
  final int memberCount;
  final int projectCount;
  final String plan;
  final String? createdAt;

  const WorkspaceDTO({
    required this.id,
    required this.name,
    required this.slug,
    this.description,
    this.logoUrl,
    this.role = 'owner',
    this.memberCount = 1,
    this.projectCount = 0,
    this.plan = 'free',
    this.createdAt,
  });

  factory WorkspaceDTO.fromJson(Map<String, dynamic> json) {
    return WorkspaceDTO(
      id: json['id'] as String,
      name: json['name'] as String,
      slug: json['slug'] as String,
      description: json['description'] as String?,
      logoUrl: json['logo_url'] as String?,
      role: json['role'] as String? ?? 'member',
      memberCount: json['member_count'] as int? ?? 1,
      projectCount: json['project_count'] as int? ?? 0,
      plan: json['plan'] as String? ?? 'free',
      createdAt: json['created_at'] as String?,
    );
  }

  Map<String, dynamic> toJson() => {
        'id': id,
        'name': name,
        'slug': slug,
        'description': description,
        'logo_url': logoUrl,
        'role': role,
        'member_count': memberCount,
        'project_count': projectCount,
        'plan': plan,
        'created_at': createdAt,
      };

  @override
  List<Object?> get props => [id, name, slug, description, logoUrl, role, memberCount, projectCount, plan];
}

class WorkspaceMemberDTO extends Equatable {
  final String id;
  final String name;
  final String email;
  final String? avatarUrl;
  final String role;
  final String? joinedAt;
  final int taskCount;

  const WorkspaceMemberDTO({
    required this.id,
    required this.name,
    required this.email,
    this.avatarUrl,
    required this.role,
    this.joinedAt,
    this.taskCount = 0,
  });

  factory WorkspaceMemberDTO.fromJson(Map<String, dynamic> json) {
    return WorkspaceMemberDTO(
      id: json['id'] as String,
      name: json['name'] as String,
      email: json['email'] as String,
      avatarUrl: json['avatar_url'] as String?,
      role: json['role'] as String? ?? 'member',
      joinedAt: json['joined_at'] as String?,
      taskCount: json['task_count'] as int? ?? 0,
    );
  }

  @override
  List<Object?> get props => [id, name, email, avatarUrl, role, joinedAt, taskCount];
}

class InvitationDTO extends Equatable {
  final String id;
  final String email;
  final String role;
  final String status;
  final String channel;
  final String? expiresAt;
  final String? createdAt;

  const InvitationDTO({
    required this.id,
    required this.email,
    required this.role,
    required this.status,
    required this.channel,
    this.expiresAt,
    this.createdAt,
  });

  factory InvitationDTO.fromJson(Map<String, dynamic> json) {
    final invite = json['invitation'] as Map<String, dynamic>? ?? json;
    return InvitationDTO(
      id: invite['id'] as String,
      email: invite['email'] as String,
      role: invite['role'] as String? ?? 'member',
      status: invite['status'] as String? ?? 'pending',
      channel: invite['channel'] as String? ?? 'email',
      expiresAt: invite['expires_at'] as String?,
      createdAt: invite['created_at'] as String?,
    );
  }

  @override
  List<Object?> get props => [id, email, role, status, channel, expiresAt, createdAt];
}

class CreateWorkspaceRequest extends Equatable {
  final String name;
  final String? description;
  final String timezone;

  const CreateWorkspaceRequest({
    required this.name,
    this.description,
    this.timezone = 'Asia/Riyadh',
  });

  Map<String, dynamic> toJson() => {
        'name': name,
        if (description != null) 'description': description,
        'timezone': timezone,
      };

  @override
  List<Object?> get props => [name, description, timezone];
}

class UpdateWorkspaceRequest extends Equatable {
  final String? name;
  final String? description;

  const UpdateWorkspaceRequest({this.name, this.description});

  Map<String, dynamic> toJson() => {
        if (name != null) 'name': name,
        if (description != null) 'description': description,
      };

  @override
  List<Object?> get props => [name, description];
}

class InviteRequest extends Equatable {
  final String email;
  final String role;
  final String? message;
  final String channel;

  const InviteRequest({
    required this.email,
    this.role = 'member',
    this.message,
    this.channel = 'email',
  });

  Map<String, dynamic> toJson() => {
        'email': email,
        'role': role,
        if (message != null) 'message': message,
        'channel': channel,
      };

  @override
  List<Object?> get props => [email, role, message, channel];
}
