import 'package:equatable/equatable.dart';

import '../../../../core/utils/money_formatter.dart';

class WalletModel extends Equatable {
  final int id;
  final String currency;
  final double balance;
  final double availableBalance;
  final double pendingBalance;
  final bool isActive;
  final DateTime createdAt;
   
  const WalletModel({
    required this.id,
    required this.currency,
    required this.balance,
    required this.availableBalance,
    required this.pendingBalance,
    required this.isActive,
    required this.createdAt,
  });
  
  factory WalletModel.fromJson(Map<String, dynamic> json) {
    return WalletModel(
      id: json['id'],
      currency: json['currency'],
      balance: (json['balance'] as num).toDouble(),
      availableBalance: (json['available_balance'] as num).toDouble(),
      pendingBalance: (json['pending_balance'] as num).toDouble(),
      isActive: json['is_active'] ?? true,
      createdAt: DateTime.parse(json['created_at']),
    );
  }
  
  /// Currency-aware balance with thousand separators.
  /// USD -> "$1,234.00"   |   SYP -> "ل.س 1,234,567" (true scale, no decimals, symbol left)
  String get formattedBalance => Money.format(balance, currency);

  /// Pending (held) balance, currency-aware — never assume USD.
  String get formattedPending => Money.format(pendingBalance, currency);

  static String sakkTag(int userId) => Money.accountNumber(userId);
  
  @override
  List<Object?> get props => [id, currency, balance, availableBalance, isActive];
}
