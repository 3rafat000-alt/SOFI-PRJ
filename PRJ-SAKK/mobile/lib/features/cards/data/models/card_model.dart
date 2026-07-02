import 'package:equatable/equatable.dart';

/// Parses a value that can be either a String or a Map with a 'value' key.
/// Backend sends some fields as objects like `{"value": "active", "label": "Active"}`.
String _extractString(dynamic v, {String fallback = ''}) {
  if (v is String) return v;
  if (v is Map<String, dynamic>) return (v['value'] ?? fallback).toString();
  return v?.toString() ?? fallback;
}

class CardModel extends Equatable {
  final int id;
  final String brand;
  final String type;
  final String lastFour;
  final String expiryDate;
  final double balance;
  final double spendingLimit;
  final double dailyLimit;
  final double monthlyLimit;
  final String status;
  final String label;
  final String cardholderName;
  final DateTime createdAt;

  const CardModel({
    required this.id,
    required this.brand,
    required this.type,
    required this.lastFour,
    required this.expiryDate,
    required this.balance,
    required this.spendingLimit,
    required this.dailyLimit,
    required this.monthlyLimit,
    required this.status,
    required this.label,
    this.cardholderName = '',
    required this.createdAt,
  });

  factory CardModel.fromJson(Map<String, dynamic> json) {
    return CardModel(
      id: json['id'] as int,
      brand: _extractString(json['brand'], fallback: 'visa'),
      type: _extractString(json['card_type'], fallback: 'virtual'),
      cardholderName: (json['cardholder_name']?.toString() ?? ''),
      lastFour: (json['last_four']?.toString() ?? '****'),
      expiryDate: (json['expiry']?.toString() ??
          '${json['expiry_month'] ?? '--'}/${json['expiry_year'] ?? '--'}'),
      balance: (json['balance'] as num?)?.toDouble() ?? 0.0,
      spendingLimit: (json['spending_limit'] as num?)?.toDouble() ?? 500.0,
      dailyLimit: (json['daily_limit'] as num?)?.toDouble() ?? 500.0,
      monthlyLimit: (json['monthly_limit'] as num?)?.toDouble() ?? 5000.0,
      status: _extractString(json['status'], fallback: 'active'),
      label: (json['label']?.toString() ??
          json['nickname']?.toString() ??
          'بطاقتي'),
      createdAt: json['created_at'] != null
          ? DateTime.parse(json['created_at'].toString())
          : DateTime.now(),
    );
  }

  String get formattedBalance => '\$${balance.toStringAsFixed(2)}';

  String get maskedNumber => '**** **** **** $lastFour';

  bool get isActive => status == 'active';
  bool get isFrozen => status == 'frozen';
  bool get isCancelled => status == 'cancelled' || status == 'expired';
  bool get isVisa => brand == 'visa';

  String get statusLabel {
    switch (status) {
      case 'active':
        return 'نشطة';
      case 'frozen':
        return 'مجمدة';
      case 'expired':
        return 'منتهية';
      case 'cancelled':
        return 'ملغية';
      default:
        return status;
    }
  }

  @override
  List<Object?> get props => [id, brand, lastFour, status, balance];
}

class CardDetails extends Equatable {
  final String cardNumber;
  final String cvv;
  final String expiryMonth;
  final String expiryYear;
  final String cardholderName;

  const CardDetails({
    required this.cardNumber,
    required this.cvv,
    required this.expiryMonth,
    required this.expiryYear,
    required this.cardholderName,
  });

  factory CardDetails.fromJson(Map<String, dynamic> json) {
    return CardDetails(
      cardNumber: _extractString(json['card_number']),
      cvv: _extractString(json['cvv']),
      expiryMonth: (json['expiry_month']?.toString() ?? ''),
      expiryYear: (json['expiry_year']?.toString() ?? ''),
      cardholderName: _extractString(json['cardholder_name']),
    );
  }

  String get expiryDate =>
      '$expiryMonth/${expiryYear.length > 2 ? expiryYear.substring(2) : expiryYear}';

  String get formattedNumber {
    return cardNumber
        .replaceAllMapped(
          RegExp(r'.{4}'),
          (match) => '${match.group(0)} ',
        )
        .trim();
  }

  @override
  List<Object?> get props => [cardNumber, cvv, expiryDate];
}
