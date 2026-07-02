import '../../../../core/constants/api_constants.dart';

/// An authorized cash agent (صرافة / مكتب) where users perform cash in/out.
class AgentModel {
  final int id;
  final String uuid;
  final String name;
  final String agentCode;
  final String? ownerName;
  final String? phone;
  final String? avatar;
  final String address;
  final String city;
  final String? governorate;
  final double latitude;
  final double longitude;
  final List<String> services;
  final String? workingHours;
  final double commissionRate;
  final double minAmount;
  final double? maxAmount;
  final double rating;
  final int reviewsCount;
  final bool isFeatured;
  final bool isVerified;

  /// Distance from the user in km (only present when the request supplied a location).
  final double? distanceKm;

  const AgentModel({
    required this.id,
    required this.uuid,
    required this.name,
    required this.agentCode,
    this.ownerName,
    this.phone,
    this.avatar,
    required this.address,
    required this.city,
    this.governorate,
    required this.latitude,
    required this.longitude,
    required this.services,
    this.workingHours,
    required this.commissionRate,
    required this.minAmount,
    this.maxAmount,
    required this.rating,
    required this.reviewsCount,
    required this.isFeatured,
    required this.isVerified,
    this.distanceKm,
  });

  static double _toDouble(dynamic v, [double fallback = 0]) {
    if (v == null) return fallback;
    if (v is num) return v.toDouble();
    return double.tryParse(v.toString()) ?? fallback;
  }

  factory AgentModel.fromJson(Map<String, dynamic> json) {
    return AgentModel(
      id: json['id'] as int,
      uuid: json['uuid']?.toString() ?? '',
      name: json['name']?.toString() ?? '',
      agentCode: json['agent_code']?.toString() ?? '',
      ownerName: json['owner_name']?.toString(),
      phone: json['phone']?.toString(),
      avatar: json['avatar']?.toString(),
      address: json['address']?.toString() ?? '',
      city: json['city']?.toString() ?? '',
      governorate: json['governorate']?.toString(),
      latitude: _toDouble(json['latitude']),
      longitude: _toDouble(json['longitude']),
      services: (json['services'] as List?)?.map((e) => e.toString()).toList() ?? const [],
      workingHours: json['working_hours']?.toString(),
      commissionRate: _toDouble(json['commission_rate']),
      minAmount: _toDouble(json['min_amount']),
      maxAmount: json['max_amount'] == null ? null : _toDouble(json['max_amount']),
      rating: _toDouble(json['rating'], 5),
      reviewsCount: (json['reviews_count'] as num?)?.toInt() ?? 0,
      isFeatured: json['is_featured'] == true,
      isVerified: json['is_verified'] == true,
      distanceKm: json['distance_km'] == null ? null : _toDouble(json['distance_km']),
    );
  }

  bool get supportsCashIn => services.contains('cash_in');
  bool get supportsCashOut => services.contains('cash_out');

  String? get avatarUrl => ApiConstants.resolveStorageUrl(avatar);

  /// Human friendly distance (e.g. "320 م" / "3.8 كم").
  String? get distanceLabel {
    final d = distanceKm;
    if (d == null) return null;
    if (d < 1) return '${(d * 1000).round()} م';
    return '${d.toStringAsFixed(1)} كم';
  }

  /// First two initials of the agent name for the avatar fallback.
  String get initials {
    final parts = name.trim().split(RegExp(r'\s+')).where((p) => p.isNotEmpty).toList();
    if (parts.isEmpty) return '؟';
    if (parts.length == 1) return parts.first.substring(0, 1);
    return parts[0].substring(0, 1) + parts[1].substring(0, 1);
  }
}
