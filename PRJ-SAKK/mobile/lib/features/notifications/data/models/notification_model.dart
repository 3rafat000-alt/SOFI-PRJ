import 'package:equatable/equatable.dart';
import 'package:intl/intl.dart';

class NotificationModel extends Equatable {
  final int id;
  final String title;
  final String body;
  final bool isRead;
  final String? templateCode;
  final Map<String, dynamic>? data;
  final DateTime createdAt;

  const NotificationModel({
    required this.id,
    required this.title,
    required this.body,
    required this.isRead,
    this.templateCode,
    this.data,
    required this.createdAt,
  });

  factory NotificationModel.fromJson(Map<String, dynamic> json) {
    return NotificationModel(
      id: json['id'],
      title: json['title'] ?? '',
      body: json['body'] ?? '',
      isRead: json['is_read'] == true || json['is_read'] == 1,
      templateCode: json['template_code'],
      data: json['data'] is Map ? Map<String, dynamic>.from(json['data']) : null,
      createdAt: json['created_at'] != null
          ? DateTime.parse(json['created_at'])
          : DateTime.now(),
    );
  }

  String get formattedDate {
    final now = DateTime.now();
    final diff = now.difference(createdAt);
    if (diff.inMinutes < 1) return 'الآن';
    if (diff.inMinutes < 60) return 'منذ ${diff.inMinutes} دقيقة';
    if (diff.inHours < 24) return 'منذ ${diff.inHours} ساعة';
    if (diff.inDays < 7) return 'منذ ${diff.inDays} يوم';
    return DateFormat('yyyy/MM/dd').format(createdAt);
  }

  NotificationModel copyWith({bool? isRead}) => NotificationModel(
        id: id,
        title: title,
        body: body,
        isRead: isRead ?? this.isRead,
        templateCode: templateCode,
        data: data,
        createdAt: createdAt,
      );

  @override
  List<Object?> get props => [id, isRead, title, body, createdAt];
}
