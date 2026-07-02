import 'package:equatable/equatable.dart';

class User extends Equatable {
  final String id;
  final String name;
  final String email;
  final String? avatarUrl;
  final String locale;
  final String timezone;
  final String? currentWorkspaceId;
  final DateTime createdAt;

  const User({
    required this.id,
    required this.name,
    required this.email,
    this.avatarUrl,
    this.locale = 'ar',
    this.timezone = 'Asia/Riyadh',
    this.currentWorkspaceId,
    required this.createdAt,
  });

  @override
  List<Object?> get props => [
        id,
        name,
        email,
        avatarUrl,
        locale,
        timezone,
        currentWorkspaceId,
        createdAt,
      ];

  User copyWith({
    String? id,
    String? name,
    String? email,
    String? avatarUrl,
    String? locale,
    String? timezone,
    String? currentWorkspaceId,
    DateTime? createdAt,
  }) {
    return User(
      id: id ?? this.id,
      name: name ?? this.name,
      email: email ?? this.email,
      avatarUrl: avatarUrl ?? this.avatarUrl,
      locale: locale ?? this.locale,
      timezone: timezone ?? this.timezone,
      currentWorkspaceId: currentWorkspaceId ?? this.currentWorkspaceId,
      createdAt: createdAt ?? this.createdAt,
    );
  }
}
