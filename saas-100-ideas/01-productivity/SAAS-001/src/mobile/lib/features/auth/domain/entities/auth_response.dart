import 'package:equatable/equatable.dart';
import 'user.dart';

class AuthResponse extends Equatable {
  final User user;
  final String token;
  final WorkspaceSummary? workspace;
  final List<WorkspaceSummary>? workspaces;

  const AuthResponse({
    required this.user,
    required this.token,
    this.workspace,
    this.workspaces,
  });

  @override
  List<Object?> get props => [user, token, workspace, workspaces];
}

class WorkspaceSummary extends Equatable {
  final String id;
  final String name;
  final String slug;
  final String role;
  final int memberCount;
  final String plan;
  final DateTime? createdAt;

  const WorkspaceSummary({
    required this.id,
    required this.name,
    required this.slug,
    required this.role,
    this.memberCount = 1,
    this.plan = 'free',
    this.createdAt,
  });

  @override
  List<Object?> get props => [id, name, slug, role, memberCount, plan, createdAt];
}
