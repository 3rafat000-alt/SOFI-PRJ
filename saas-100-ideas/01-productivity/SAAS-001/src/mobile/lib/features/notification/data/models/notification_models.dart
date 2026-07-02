import 'package:equatable/equatable.dart';

class NotificationDTO extends Equatable {
  final String id;
  final String type;
  final String title;
  final String body;
  final Map<String, dynamic>? data;
  final String? readAt;
  final String? createdAt;

  const NotificationDTO({
    required this.id,
    required this.type,
    required this.title,
    required this.body,
    this.data,
    this.readAt,
    this.createdAt,
  });

  factory NotificationDTO.fromJson(Map<String, dynamic> json) {
    return NotificationDTO(
      id: json['id'] as String,
      type: json['type'] as String? ?? 'general',
      title: json['title'] as String,
      body: json['body'] as String? ?? '',
      data: json['data'] as Map<String, dynamic>?,
      readAt: json['read_at'] as String?,
      createdAt: json['created_at'] as String?,
    );
  }

  bool get isRead => readAt != null;

  @override
  List<Object?> get props => [id, type, title, body, data, readAt, createdAt];
}

class NotificationListDTO extends Equatable {
  final List<NotificationDTO> notifications;
  final int total;
  final int unreadCount;

  const NotificationListDTO({
    required this.notifications,
    this.total = 0,
    this.unreadCount = 0,
  });

  factory NotificationListDTO.fromJson(Map<String, dynamic> json) {
    final data = json['data'] as List<dynamic>;
    final meta = json['meta'] as Map<String, dynamic>?;
    return NotificationListDTO(
      notifications: data
          .map((e) => NotificationDTO.fromJson(e as Map<String, dynamic>))
          .toList(),
      total: meta?['total'] as int? ?? 0,
      unreadCount: meta?['unread_count'] as int? ?? 0,
    );
  }

  @override
  List<Object?> get props => [notifications, total, unreadCount];
}

class MarkAllReadResponseDTO extends Equatable {
  final String message;
  final int count;

  const MarkAllReadResponseDTO({required this.message, required this.count});

  factory MarkAllReadResponseDTO.fromJson(Map<String, dynamic> json) {
    final data = json['data'] as Map<String, dynamic>;
    return MarkAllReadResponseDTO(
      message: data['message'] as String,
      count: data['count'] as int? ?? 0,
    );
  }

  @override
  List<Object?> get props => [message, count];
}
