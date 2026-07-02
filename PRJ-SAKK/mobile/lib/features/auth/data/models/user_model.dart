import 'package:equatable/equatable.dart';

import '../../../../core/constants/api_constants.dart';

class UserModel extends Equatable {
  final int id;
  final String uuid;
  final String firstName;
  final String lastName;
  final String fullName;
  final String email;
  final String? phone;
  final String? avatar;
  final String? dateOfBirth;
  final String? gender;
  final String? language;
  final String? countryCode;
  final String statusValue;
  final String statusLabel;
  final String kycStatusValue;
  final String kycStatusLabel;
  final bool isKycVerified;
  final bool isActive;
  final bool hasPin;
  final bool twoFactorEnabled;
  final bool emailVerified;
  final bool phoneVerified;
  final int kycLevel;
  final String? referralCode;
  final DateTime createdAt;
  
  const UserModel({
    required this.id,
    required this.uuid,
    required this.firstName,
    required this.lastName,
    required this.fullName,
    required this.email,
    this.phone,
    this.avatar,
    this.dateOfBirth,
    this.gender,
    this.language,
    this.countryCode,
    required this.statusValue,
    required this.statusLabel,
    required this.kycStatusValue,
    required this.kycStatusLabel,
    required this.isKycVerified,
    required this.isActive,
    required this.hasPin,
    required this.twoFactorEnabled,
    required this.emailVerified,
    required this.phoneVerified,
    this.kycLevel = 0,
    this.referralCode,
    required this.createdAt,
  });
  
  factory UserModel.fromJson(Map<String, dynamic> json) {
    // Handle status as object or string
    String statusValue;
    String statusLabel;
    if (json['status'] is Map) {
      statusValue = json['status']['value'] ?? 'pending';
      statusLabel = json['status']['label'] ?? 'Pending';
    } else {
      statusValue = json['status'] ?? 'pending';
      statusLabel = json['status'] ?? 'Pending';
    }
    
    // Handle kyc_status as object or string
    String kycStatusValue;
    String kycStatusLabel;
    if (json['kyc_status'] is Map) {
      kycStatusValue = json['kyc_status']['value'] ?? 'pending';
      kycStatusLabel = json['kyc_status']['label_ar'] ?? json['kyc_status']['label'] ?? 'Pending';
    } else {
      kycStatusValue = json['kyc_status'] ?? 'pending';
      kycStatusLabel = json['kyc_status'] ?? 'Pending';
    }
    
    return UserModel(
      id: json['id'],
      uuid: json['uuid'] ?? '',
      firstName: json['first_name'] ?? '',
      lastName: json['last_name'] ?? '',
      fullName: json['full_name'] ?? '',
      email: json['email'] ?? '',
      phone: json['phone'],
      avatar: json['avatar'],
      dateOfBirth: json['date_of_birth'],
      gender: json['gender'],
      language: json['language'],
      countryCode: json['country_code'],
      statusValue: statusValue,
      statusLabel: statusLabel,
      kycStatusValue: kycStatusValue,
      kycStatusLabel: kycStatusLabel,
      isKycVerified: json['is_kyc_verified'] ?? false,
      isActive: json['is_active'] ?? true,
      hasPin: json['has_pin'] ?? false,
      twoFactorEnabled: json['two_factor_enabled'] ?? false,
      emailVerified: json['email_verified'] ?? false,
      phoneVerified: json['phone_verified'] ?? false,
      kycLevel: json['kyc_level'] ?? 0,
      referralCode: json['referral_code'],
      createdAt: json['created_at'] != null 
          ? DateTime.parse(json['created_at']) 
          : DateTime.now(),
    );
  }
  
  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'uuid': uuid,
      'first_name': firstName,
      'last_name': lastName,
      'full_name': fullName,
      'email': email,
      'phone': phone,
      'avatar': avatar,
      'date_of_birth': dateOfBirth,
      'gender': gender,
      'language': language,
      'country_code': countryCode,
      'status': statusValue,
      'kyc_status': kycStatusValue,
      'is_kyc_verified': isKycVerified,
      'is_active': isActive,
      'has_pin': hasPin,
      'two_factor_enabled': twoFactorEnabled,
      'email_verified': emailVerified,
      'phone_verified': phoneVerified,
      'kyc_level': kycLevel,
      'referral_code': referralCode,
      'created_at': createdAt.toIso8601String(),
    };
  }
  
  String get initials {
    final f = firstName.isNotEmpty ? firstName[0] : '';
    final l = lastName.isNotEmpty ? lastName[0] : '';
    return '$f$l'.toUpperCase();
  }

  String get sakkTag => 'SK${id.toString().padLeft(8, '0')}';

  /// Full URL for the avatar (resolves relative storage paths). Null if no avatar.
  String? get avatarUrl => ApiConstants.resolveStorageUrl(avatar);

  UserModel copyWith({
    String? avatar,
    bool clearAvatar = false,
    String? firstName,
    String? lastName,
    String? phone,
    String? dateOfBirth,
    String? gender,
    String? language,
    String? countryCode,
    bool? hasPin,
    bool? twoFA,
  }) {
    return UserModel(
      id: id, uuid: uuid,
      firstName: firstName ?? this.firstName,
      lastName: lastName ?? this.lastName,
      fullName: fullName,
      email: email,
      phone: phone ?? this.phone,
      avatar: clearAvatar ? null : (avatar ?? this.avatar),
      dateOfBirth: dateOfBirth ?? this.dateOfBirth,
      gender: gender ?? this.gender,
      language: language ?? this.language,
      countryCode: countryCode ?? this.countryCode,
      statusValue: statusValue, statusLabel: statusLabel,
      kycStatusValue: kycStatusValue, kycStatusLabel: kycStatusLabel,
      isKycVerified: isKycVerified, isActive: isActive, hasPin: hasPin ?? this.hasPin,
      twoFactorEnabled: twoFA ?? twoFactorEnabled, emailVerified: emailVerified,
      phoneVerified: phoneVerified, kycLevel: kycLevel,
      referralCode: referralCode, createdAt: createdAt,
    );
  }

  @override
  List<Object?> get props => [
    id, uuid, firstName, lastName, email, phone, avatar, dateOfBirth, gender,
    statusValue, kycStatusValue, kycLevel, hasPin, twoFactorEnabled
  ];
}
