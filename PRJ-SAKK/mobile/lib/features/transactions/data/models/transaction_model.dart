import 'package:equatable/equatable.dart';
import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../../../../core/theme/app_colors.dart';
import '../../../../core/utils/money_formatter.dart';

class TransactionModel extends Equatable {
  final int id;
  final String type;
  final String status;
  final double amount;
  final double fee;
  final String currency;
  final String? title;
  final String? description;
  final String? reference;
  final String? category;
  final Map<String, dynamic>? metadata;
  final DateTime createdAt;

  /// Server-provided Arabic labels & credit flag (when available).
  final String? typeLabelArServer;
  final String? statusLabelArServer;
  final bool? isCreditServer;

  const TransactionModel({
    required this.id,
    required this.type,
    required this.status,
    required this.amount,
    required this.fee,
    required this.currency,
    this.title,
    this.description,
    this.reference,
    this.category,
    this.metadata,
    required this.createdAt,
    this.typeLabelArServer,
    this.statusLabelArServer,
    this.isCreditServer,
  });

  /// Extract a string `value` whether the field is an object {value,...} or a plain string.
  static String _val(dynamic field, [String fallback = '']) {
    if (field is Map) return (field['value'] ?? fallback).toString();
    if (field == null) return fallback;
    return field.toString();
  }

  static String? _labelAr(dynamic field) {
    if (field is Map) return field['label_ar'] as String?;
    return null;
  }

  factory TransactionModel.fromJson(Map<String, dynamic> json) {
    final typeField = json['type'];
    final statusField = json['status'];

    return TransactionModel(
      id: json['id'],
      type: _val(typeField, 'unknown'),
      status: _val(statusField, 'completed'),
      amount: (json['amount'] as num).toDouble(),
      fee: (json['fee'] as num?)?.toDouble() ?? 0,
      currency: json['currency'] ?? 'USD',
      title: json['title'],
      description: json['description'],
      reference: json['reference'],
      category: _val(json['category'], ''),
      metadata: json['metadata'] is Map ? Map<String, dynamic>.from(json['metadata']) : null,
      createdAt: json['created_at'] != null ? DateTime.parse(json['created_at']) : DateTime.now(),
      typeLabelArServer: _labelAr(typeField),
      statusLabelArServer: _labelAr(statusField),
      isCreditServer: typeField is Map ? typeField['is_credit'] as bool? : null,
    );
  }

  /// Currency-aware amount with thousand separators and 2 decimals.
  /// USD -> "$1,234.00"   |   SYP -> "ل.س 1,234,567" (symbol left)
  String get formattedAmount => Money.format(amount.abs(), currency);

  String get formattedDate {
    final now = DateTime.now();
    final diff = now.difference(createdAt);

    if (diff.inMinutes < 1) return 'الآن';
    if (diff.inMinutes < 60) return 'منذ ${diff.inMinutes} دقيقة';
    if (diff.inHours < 24) return 'منذ ${diff.inHours} ساعة';
    if (diff.inDays < 7) return 'منذ ${diff.inDays} يوم';

    return DateFormat('yyyy/MM/dd').format(createdAt);
  }

  /// Matches any Arabic character — used to keep the UI fully Arabic by
  /// ignoring English titles/descriptions coming from the backend.
  static final RegExp _arabic = RegExp(r'[\u0600-\u06FF]');

  /// The note to show only when it's written in Arabic (else null).
  String? get note =>
      (description != null && description!.trim().isNotEmpty && _arabic.hasMatch(description!))
          ? description
          : null;

  /// Arabic-only label for lists (note → counterparty → type label).
  String get displayLabel {
    if (note != null) return note!;
    final cp = counterpartyName;
    if (cp != null && (type == 'transfer_in' || type == 'transfer_out')) {
      return type == 'transfer_in' ? 'تحويل من $cp' : 'تحويل إلى $cp';
    }
    return typeLabel;
  }

  String get typeLabel {
    if (title != null && title!.isNotEmpty && _arabic.hasMatch(title!)) return title!;
    if (typeLabelArServer != null && typeLabelArServer!.isNotEmpty) return typeLabelArServer!;
    switch (type) {
      case 'deposit': return 'إيداع';
      case 'withdrawal': return 'سحب';
      case 'card_payment': return 'دفع بالبطاقة';
      case 'card_load': return 'شحن بطاقة';
      case 'card_unload': return 'تفريغ بطاقة';
      case 'card_refund': return 'استرداد';
      case 'exchange': return 'تحويل عملة';
      case 'transfer_out': return 'تحويل صادر';
      case 'transfer_in': return 'تحويل وارد';
      case 'salary_in': return 'راتب';
      case 'payroll_out': return 'دفع رواتب';
      case 'fee': return 'رسوم';
      case 'reward': return 'مكافأة';
      default: return type;
    }
  }

  String get statusLabel {
    if (statusLabelArServer != null && statusLabelArServer!.isNotEmpty) return statusLabelArServer!;
    switch (status) {
      case 'completed': return 'مكتملة';
      case 'pending': return 'معلقة';
      case 'processing': return 'قيد المعالجة';
      case 'failed': return 'فاشلة';
      case 'cancelled': return 'ملغية';
      default: return status;
    }
  }

  Color get statusColor {
    switch (status) {
      case 'completed': return AppColors.success;
      case 'pending': return AppColors.warning;
      case 'processing': return AppColors.info;
      case 'failed': return AppColors.error;
      case 'cancelled': return AppColors.textHint;
      default: return AppColors.textSecondary;
    }
  }

  /// Counterparty name from metadata (the other party in a P2P transfer).
  String? get counterpartyName {
    final v = metadata?['counterparty_name'] ?? metadata?['recipient_name'] ?? metadata?['sender_name'];
    return (v is String && v.isNotEmpty) ? v : null;
  }

  /// True when this transaction increases the user's balance.
  bool get isIncoming {
    if (isCreditServer != null) return isCreditServer!;
    return amount > 0 ||
        type == 'deposit' ||
        type == 'transfer_in' ||
        type == 'card_refund' ||
        type == 'reward';
  }

  @override
  List<Object?> get props => [id, type, status, amount, createdAt];
}
