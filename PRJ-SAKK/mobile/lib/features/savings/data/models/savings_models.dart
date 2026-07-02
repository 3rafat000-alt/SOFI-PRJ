// Models for the (cash) savings-goals feature — separate from gold savings.

double _toDouble(dynamic v, [double fallback = 0]) {
  if (v == null) return fallback;
  if (v is num) return v.toDouble();
  return double.tryParse(v.toString()) ?? fallback;
}

/// A single savings goal (target + saved progress).
class SavingsGoalModel {
  final int id;
  final String uuid;
  final String name;
  final double? targetAmount;
  final double savedAmount;
  final double progressPercent;
  final String currency;
  final String status; // active | completed | closed
  final String statusLabel;
  final String? icon;
  final String? color;
  final DateTime? targetDate;

  const SavingsGoalModel({
    required this.id,
    required this.uuid,
    required this.name,
    this.targetAmount,
    required this.savedAmount,
    required this.progressPercent,
    required this.currency,
    required this.status,
    required this.statusLabel,
    this.icon,
    this.color,
    this.targetDate,
  });

  factory SavingsGoalModel.fromJson(Map<String, dynamic> json) {
    return SavingsGoalModel(
      id: json['id'] as int,
      uuid: json['uuid']?.toString() ?? '',
      name: json['name']?.toString() ?? '',
      targetAmount: json['target_amount'] == null ? null : _toDouble(json['target_amount']),
      savedAmount: _toDouble(json['saved_amount']),
      progressPercent: _toDouble(json['progress_percent']),
      currency: json['currency']?.toString() ?? 'USD',
      status: json['status']?.toString() ?? 'active',
      statusLabel: json['status_label']?.toString() ?? '',
      icon: json['icon']?.toString(),
      color: json['color']?.toString(),
      targetDate: json['target_date'] != null
          ? DateTime.tryParse(json['target_date'].toString())
          : null,
    );
  }

  bool get hasTarget => targetAmount != null && targetAmount! > 0;
  bool get isCompleted => status == 'completed';
}

/// Aggregate summary across all of the user's savings goals.
class SavingsSummary {
  final double totalSaved;
  final int goalsCount;
  final int completedCount;
  final double usdBalance;

  const SavingsSummary({
    required this.totalSaved,
    required this.goalsCount,
    required this.completedCount,
    required this.usdBalance,
  });

  factory SavingsSummary.fromJson(Map<String, dynamic> json) {
    return SavingsSummary(
      totalSaved: _toDouble(json['total_saved']),
      goalsCount: (json['goals_count'] as num?)?.toInt() ?? 0,
      completedCount: (json['completed_count'] as num?)?.toInt() ?? 0,
      usdBalance: _toDouble(json['usd_balance']),
    );
  }
}
