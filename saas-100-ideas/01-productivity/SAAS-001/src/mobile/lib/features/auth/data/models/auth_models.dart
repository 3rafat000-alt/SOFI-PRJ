import 'package:equatable/equatable.dart';

class LoginRequest extends Equatable {
  final String email;
  final String password;

  const LoginRequest({
    required this.email,
    required this.password,
  });

  Map<String, dynamic> toJson() => {
        'email': email,
        'password': password,
      };

  @override
  List<Object?> get props => [email, password];
}

class RegisterRequest extends Equatable {
  final String name;
  final String email;
  final String password;
  final String passwordConfirmation;
  final String workspaceName;
  final String locale;
  final String timezone;

  const RegisterRequest({
    required this.name,
    required this.email,
    required this.password,
    required this.passwordConfirmation,
    required this.workspaceName,
    this.locale = 'ar',
    this.timezone = 'Asia/Riyadh',
  });

  Map<String, dynamic> toJson() => {
        'name': name,
        'email': email,
        'password': password,
        'password_confirmation': passwordConfirmation,
        'workspace_name': workspaceName,
        'locale': locale,
        'timezone': timezone,
      };

  @override
  List<Object?> get props => [
        name,
        email,
        password,
        passwordConfirmation,
        workspaceName,
        locale,
        timezone,
      ];
}

class UserDTO extends Equatable {
  final String id;
  final String name;
  final String email;
  final String? avatarUrl;
  final String locale;
  final String timezone;
  final String? currentWorkspaceId;
  final String? createdAt;

  const UserDTO({
    required this.id,
    required this.name,
    required this.email,
    this.avatarUrl,
    this.locale = 'ar',
    this.timezone = 'Asia/Riyadh',
    this.currentWorkspaceId,
    this.createdAt,
  });

  factory UserDTO.fromJson(Map<String, dynamic> json) {
    return UserDTO(
      id: json['id'] as String,
      name: json['name'] as String,
      email: json['email'] as String,
      avatarUrl: json['avatar_url'] as String?,
      locale: json['locale'] as String? ?? 'ar',
      timezone: json['timezone'] as String? ?? 'Asia/Riyadh',
      currentWorkspaceId: json['current_workspace_id'] as String?,
      createdAt: json['created_at'] as String?,
    );
  }

  Map<String, dynamic> toJson() => {
        'id': id,
        'name': name,
        'email': email,
        'avatar_url': avatarUrl,
        'locale': locale,
        'timezone': timezone,
        'current_workspace_id': currentWorkspaceId,
        'created_at': createdAt,
      };

  @override
  List<Object?> get props => [id, name, email, avatarUrl, locale, timezone, currentWorkspaceId];

  /// Map to domain entity
  toDomain() => null; // Mapped in repository impl
}

class WorkspaceSummaryDTO extends Equatable {
  final String id;
  final String name;
  final String slug;
  final String role;
  final int memberCount;
  final String plan;
  final String? createdAt;

  const WorkspaceSummaryDTO({
    required this.id,
    required this.name,
    required this.slug,
    required this.role,
    this.memberCount = 1,
    this.plan = 'free',
    this.createdAt,
  });

  factory WorkspaceSummaryDTO.fromJson(Map<String, dynamic> json) {
    return WorkspaceSummaryDTO(
      id: json['id'] as String,
      name: json['name'] as String,
      slug: json['slug'] as String,
      role: json['role'] as String? ?? 'member',
      memberCount: json['member_count'] as int? ?? 1,
      plan: json['plan'] as String? ?? 'free',
      createdAt: json['created_at'] as String?,
    );
  }

  @override
  List<Object?> get props => [id, name, slug, role, memberCount, plan];
}

class AuthResponseDTO extends Equatable {
  final UserDTO user;
  final String token;
  final WorkspaceSummaryDTO? workspace;
  final List<WorkspaceSummaryDTO>? workspaces;

  const AuthResponseDTO({
    required this.user,
    required this.token,
    this.workspace,
    this.workspaces,
  });

  factory AuthResponseDTO.fromJson(Map<String, dynamic> json) {
    final data = json['data'] as Map<String, dynamic>;
    final user = UserDTO.fromJson(data['user'] as Map<String, dynamic>);
    final token = data['token'] as String;
    final workspace = data['workspace'] != null
        ? WorkspaceSummaryDTO.fromJson(data['workspace'] as Map<String, dynamic>)
        : null;
    final workspaces = (data['workspaces'] as List<dynamic>?)
        ?.map((e) => WorkspaceSummaryDTO.fromJson(e as Map<String, dynamic>))
        .toList();

    return AuthResponseDTO(
      user: user,
      token: token,
      workspace: workspace,
      workspaces: workspaces,
    );
  }

  @override
  List<Object?> get props => [user, token, workspace, workspaces];
}

class ForgotPasswordRequest extends Equatable {
  final String email;

  const ForgotPasswordRequest({required this.email});

  Map<String, dynamic> toJson() => {'email': email};

  @override
  List<Object?> get props => [email];
}

class ResetPasswordRequest extends Equatable {
  final String token;
  final String email;
  final String password;
  final String passwordConfirmation;

  const ResetPasswordRequest({
    required this.token,
    required this.email,
    required this.password,
    required this.passwordConfirmation,
  });

  Map<String, dynamic> toJson() => {
        'token': token,
        'email': email,
        'password': password,
        'password_confirmation': passwordConfirmation,
      };

  @override
  List<Object?> get props => [token, email, password, passwordConfirmation];
}

class MessageResponseDTO extends Equatable {
  final String message;

  const MessageResponseDTO({required this.message});

  factory MessageResponseDTO.fromJson(Map<String, dynamic> json) {
    final data = json['data'] as Map<String, dynamic>;
    return MessageResponseDTO(message: data['message'] as String);
  }

  @override
  List<Object?> get props => [message];
}
