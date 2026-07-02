import 'package:equatable/equatable.dart';

class Workspace extends Equatable {
  final String id;
  final String name;
  final String slug;
  final String? description;
  final String? logoUrl;
  final String role;
  final int memberCount;
  final int projectCount;
  final String plan;
  final DateTime createdAt;

  const Workspace({
    required this.id,
    required this.name,
    required this.slug,
    this.description,
    this.logoUrl,
    this.role = 'owner',
    this.memberCount = 1,
    this.projectCount = 0,
    this.plan = 'free',
    required this.createdAt,
  });

  @override
  List<Object?> get props => [
        id, name, slug, description, logoUrl, role,
        memberCount, projectCount, plan, createdAt,
      ];

  Workspace copyWith({
    String? id,
    String? name,
    String? slug,
    String? description,
    String? logoUrl,
    String? role,
    int? memberCount,
    int? projectCount,
    String? plan,
    DateTime? createdAt,
  }) {
    return Workspace(
      id: id ?? this.id,
      name: name ?? this.name,
      slug: slug ?? this.slug,
      description: description ?? this.description,
      logoUrl: logoUrl ?? this.logoUrl,
      role: role ?? this.role,
      memberCount: memberCount ?? this.memberCount,
      projectCount: projectCount ?? this.projectCount,
      plan: plan ?? this.plan,
      createdAt: createdAt ?? this.createdAt,
    );
  }
}

class WorkspaceMember extends Equatable {
  final String id;
  final String name;
  final String email;
  final String? avatarUrl;
  final String role;
  final DateTime joinedAt;
  final int taskCount;

  const WorkspaceMember({
    required this.id,
    required this.name,
    required this.email,
    this.avatarUrl,
    required this.role,
    required this.joinedAt,
    this.taskCount = 0,
  });

  @override
  List<Object?> get props => [id, name, email, avatarUrl, role, joinedAt, taskCount];
}

class Invitation extends Equatable {
  final String id;
  final String email;
  final String role;
  final String status;
  final String channel;
  final DateTime expiresAt;
  final DateTime createdAt;

  const Invitation({
    required this.id,
    required this.email,
    required this.role,
    required this.status,
    required this.channel,
    required this.expiresAt,
    required this.createdAt,
  });

  @override
  List<Object?> get props => [id, email, role, status, channel, expiresAt, createdAt];
}
