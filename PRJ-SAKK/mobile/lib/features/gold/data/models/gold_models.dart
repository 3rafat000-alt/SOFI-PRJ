// Models for the gold-savings feature (buy/sell grams at live karat prices).

double _toDouble(dynamic v, [double fallback = 0]) {
  if (v == null) return fallback;
  if (v is num) return v.toDouble();
  return double.tryParse(v.toString()) ?? fallback;
}

/// A live gold price for a single karat (24/22/21/18).
class GoldPriceModel {
  final String karat;
  final String karatLabel;
  final String? purity;
  final double buyPrice;
  final double sellPrice;
  final double spread;

  const GoldPriceModel({
    required this.karat,
    required this.karatLabel,
    this.purity,
    required this.buyPrice,
    required this.sellPrice,
    required this.spread,
  });

  factory GoldPriceModel.fromJson(Map<String, dynamic> json) {
    return GoldPriceModel(
      karat: json['karat']?.toString() ?? '',
      karatLabel: json['karat_label']?.toString() ?? 'عيار ${json['karat']}',
      purity: json['purity']?.toString(),
      buyPrice: _toDouble(json['buy_price']),
      sellPrice: _toDouble(json['sell_price']),
      spread: _toDouble(json['spread']),
    );
  }
}

/// The user's gold wallet (balance in grams + current valuation).
class GoldWalletModel {
  final double balanceGrams;
  final double currentValueUsd;
  final double totalInvestedUsd;
  final double totalBoughtGrams;
  final double totalSoldGrams;
  final double profitLossUsd;
  final double usdBalance;
  final List<GoldPriceModel> prices;

  const GoldWalletModel({
    required this.balanceGrams,
    required this.currentValueUsd,
    required this.totalInvestedUsd,
    required this.totalBoughtGrams,
    required this.totalSoldGrams,
    required this.profitLossUsd,
    required this.usdBalance,
    required this.prices,
  });

  factory GoldWalletModel.fromJson(Map<String, dynamic> json) {
    return GoldWalletModel(
      balanceGrams: _toDouble(json['balance_grams']),
      currentValueUsd: _toDouble(json['current_value_usd']),
      totalInvestedUsd: _toDouble(json['total_invested_usd']),
      totalBoughtGrams: _toDouble(json['total_bought_grams']),
      totalSoldGrams: _toDouble(json['total_sold_grams']),
      profitLossUsd: _toDouble(json['profit_loss_usd']),
      usdBalance: _toDouble(json['usd_balance']),
      prices: (json['prices'] as List?)
              ?.map((e) => GoldPriceModel.fromJson(e as Map<String, dynamic>))
              .toList() ??
          const [],
    );
  }

  /// Profit/loss as a percentage of the invested amount.
  double get profitLossPercent {
    if (totalInvestedUsd <= 0) return 0;
    return (profitLossUsd / totalInvestedUsd) * 100;
  }

  bool get isProfit => profitLossUsd >= 0;
  bool get hasGold => balanceGrams > 0;
}

/// A single gold buy/sell transaction.
class GoldTransactionModel {
  final String reference;
  final String type; // buy | sell
  final String karat;
  final double grams;
  final double pricePerGramUsd;
  final double totalUsd;
  final double feeUsd;
  final String status;
  final DateTime? createdAt;

  const GoldTransactionModel({
    required this.reference,
    required this.type,
    required this.karat,
    required this.grams,
    required this.pricePerGramUsd,
    required this.totalUsd,
    required this.feeUsd,
    required this.status,
    this.createdAt,
  });

  factory GoldTransactionModel.fromJson(Map<String, dynamic> json) {
    return GoldTransactionModel(
      reference: json['reference']?.toString() ?? '',
      type: json['type']?.toString() ?? 'buy',
      karat: json['karat']?.toString() ?? '',
      grams: _toDouble(json['grams']),
      pricePerGramUsd: _toDouble(json['price_per_gram_usd']),
      totalUsd: _toDouble(json['total_usd']),
      feeUsd: _toDouble(json['fee_usd']),
      status: json['status']?.toString() ?? 'completed',
      createdAt: json['created_at'] != null
          ? DateTime.tryParse(json['created_at'].toString())
          : null,
    );
  }

  bool get isBuy => type == 'buy';
  String get typeLabel => isBuy ? 'شراء' : 'بيع';
  String get karatLabel => 'عيار $karat';
}
