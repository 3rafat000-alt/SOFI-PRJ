class DeviceModel {
  final int id;
  final String deviceId;
  final String deviceName;
  final String deviceType;
  final String status; // pending | approved | rejected
  final bool isCurrent;
  final bool isTrusted;
  final bool transactionLocked;
  final DateTime? transactionsLockedUntil;
  final DateTime? approvedAt;
  final DateTime? lastActiveAt;
  final DateTime? createdAt;

  const DeviceModel({
    required this.id,
    required this.deviceId,
    required this.deviceName,
    required this.deviceType,
    required this.status,
    required this.isCurrent,
    required this.isTrusted,
    required this.transactionLocked,
    this.transactionsLockedUntil,
    this.approvedAt,
    this.lastActiveAt,
    this.createdAt,
  });

  static DateTime? _date(dynamic v) =>
      v == null ? null : DateTime.tryParse(v.toString())?.toLocal();

  factory DeviceModel.fromJson(Map<String, dynamic> json) {
    return DeviceModel(
      id: json['id'] as int,
      deviceId: json['device_id']?.toString() ?? '',
      deviceName: json['device_name']?.toString() ?? 'جهاز',
      deviceType: json['device_type']?.toString() ?? 'android',
      status: json['status']?.toString() ?? 'approved',
      isCurrent: json['is_current'] == true,
      isTrusted: json['is_trusted'] == true,
      transactionLocked: json['transaction_locked'] == true,
      transactionsLockedUntil: _date(json['transactions_locked_until']),
      approvedAt: _date(json['approved_at']),
      lastActiveAt: _date(json['last_active_at']),
      createdAt: _date(json['created_at']),
    );
  }

  bool get isPending => status == 'pending';
  bool get isApproved => status == 'approved';
  bool get isRejected => status == 'rejected';
  bool get isIos => deviceType == 'ios';

  String get statusLabel {
    if (isPending) return 'بانتظار الموافقة';
    if (isRejected) return 'مرفوض';
    return 'موثوق';
  }

  /// Remaining time on the 48h security hold, e.g. "متبقٍّ 41 ساعة".
  String? get lockRemainingLabel {
    final until = transactionsLockedUntil;
    if (until == null || !transactionLocked) return null;
    final diff = until.difference(DateTime.now());
    if (diff.isNegative) return null;
    if (diff.inHours >= 1) return 'متبقٍّ ${diff.inHours} ساعة';
    return 'متبقٍّ ${diff.inMinutes} دقيقة';
  }
}
