import 'package:flutter/material.dart';
import 'package:iconsax/iconsax.dart';

import '../../../../core/theme/app_colors.dart';

// Support-ticket models. Mirror the customer-facing
// `API\SupportTicketController` JSON shape. Internal notes are never sent here.

/// A selectable ticket category from GET /support/categories.
class TicketCategory {
  final String value;
  final String label;

  const TicketCategory({required this.value, required this.label});

  factory TicketCategory.fromJson(Map<String, dynamic> json) => TicketCategory(
        value: json['value'] as String,
        label: json['label'] as String,
      );
}

/// One public message in a ticket thread.
class TicketMessageModel {
  final int id;
  final String message;
  final bool isMine; // true = customer (me), false = support
  final DateTime createdAt;

  const TicketMessageModel({
    required this.id,
    required this.message,
    required this.isMine,
    required this.createdAt,
  });

  factory TicketMessageModel.fromJson(Map<String, dynamic> json) {
    return TicketMessageModel(
      id: json['id'] as int,
      message: (json['message'] ?? '') as String,
      isMine: (json['is_mine'] ?? false) as bool,
      createdAt: DateTime.tryParse((json['created_at'] ?? '') as String)?.toLocal() ??
          DateTime.now(),
    );
  }
}

/// A support ticket. List rows omit [description]/[messages]; the detail
/// endpoint fills them in.
class SupportTicketModel {
  final String uuid;
  final String ticketNumber;
  final String subject;
  final String category;
  final String priority;
  final String status;
  final int messagesCount;
  final DateTime? createdAt;
  final DateTime? updatedAt;
  final String? description;
  final DateTime? resolvedAt;
  final List<TicketMessageModel> messages;

  const SupportTicketModel({
    required this.uuid,
    required this.ticketNumber,
    required this.subject,
    required this.category,
    required this.priority,
    required this.status,
    required this.messagesCount,
    this.createdAt,
    this.updatedAt,
    this.description,
    this.resolvedAt,
    this.messages = const [],
  });

  bool get isOpenForReply => status != 'closed';

  factory SupportTicketModel.fromJson(Map<String, dynamic> json) {
    return SupportTicketModel(
      uuid: json['uuid'] as String,
      ticketNumber: (json['ticket_number'] ?? '') as String,
      subject: (json['subject'] ?? '') as String,
      category: (json['category'] ?? 'general') as String,
      priority: (json['priority'] ?? 'medium') as String,
      status: (json['status'] ?? 'open') as String,
      messagesCount: (json['messages_count'] ?? 0) as int,
      createdAt: _date(json['created_at']),
      updatedAt: _date(json['updated_at']),
      description: json['description'] as String?,
      resolvedAt: _date(json['resolved_at']),
      messages: (json['messages'] as List<dynamic>? ?? [])
          .map((e) => TicketMessageModel.fromJson(e as Map<String, dynamic>))
          .toList(),
    );
  }

  static DateTime? _date(dynamic v) =>
      v == null ? null : DateTime.tryParse(v as String)?.toLocal();
}

/// Arabic status presentation (label + colour role) for the UI.
class TicketStatusStyle {
  final String label;
  final Color Function(BuildContext) color;

  const TicketStatusStyle(this.label, this.color);

  static TicketStatusStyle of(String status) {
    switch (status) {
      case 'open':
        return TicketStatusStyle('مفتوحة', (c) => c.appColors.info);
      case 'in_progress':
        return TicketStatusStyle('قيد المعالجة', (c) => c.appColors.warning);
      case 'waiting_customer':
        return TicketStatusStyle('بانتظار ردّك', (c) => c.appColors.primary);
      case 'resolved':
        return TicketStatusStyle('تم الحل', (c) => c.appColors.success);
      case 'closed':
        return TicketStatusStyle('مغلقة', (c) => c.appColors.textHint);
      default:
        return TicketStatusStyle(status, (c) => c.appColors.textHint);
    }
  }
}

/// Category → icon + Arabic label fallback (labels also come from the API).
IconData ticketCategoryIcon(String category) {
  switch (category) {
    case 'transaction':
      return Iconsax.arrow_swap_horizontal;
    case 'card':
      return Iconsax.card;
    case 'kyc':
      return Iconsax.user_tick;
    case 'technical':
      return Iconsax.code;
    case 'billing':
      return Iconsax.receipt_item;
    default:
      return Iconsax.message_question;
  }
}
