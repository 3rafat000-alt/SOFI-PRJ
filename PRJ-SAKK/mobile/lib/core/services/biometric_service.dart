import 'package:flutter/services.dart';
import 'package:local_auth/local_auth.dart';
import 'package:local_auth/error_codes.dart' as auth_error;

/// Biometric authentication service
/// Provides fingerprint/face authentication for sensitive operations
class BiometricService {
  final LocalAuthentication _localAuth = LocalAuthentication();

  /// Check if device supports biometric authentication
  Future<bool> isSupported() async {
    try {
      return await _localAuth.canCheckBiometrics || await _localAuth.isDeviceSupported();
    } on PlatformException {
      return false;
    }
  }

  /// Check if biometric is enrolled on device
  Future<bool> hasEnrolledBiometrics() async {
    try {
      final biometrics = await _localAuth.getAvailableBiometrics();
      return biometrics.isNotEmpty;
    } on PlatformException {
      return false;
    }
  }

  /// Get available biometric types
  Future<List<BiometricType>> getAvailableBiometrics() async {
    try {
      return await _localAuth.getAvailableBiometrics();
    } on PlatformException {
      return [];
    }
  }

  /// Authenticate user with biometrics
  /// Returns true if authentication successful
  Future<BiometricResult> authenticate({
    String reason = 'الرجاء التحقق من هويتك',
    bool biometricOnly = false,
  }) async {
    try {
      final didAuthenticate = await _localAuth.authenticate(
        localizedReason: reason,
        options: AuthenticationOptions(
          stickyAuth: true,
          biometricOnly: biometricOnly,
          useErrorDialogs: true,
        ),
      );

      return BiometricResult(
        success: didAuthenticate,
        message: didAuthenticate ? 'تم التحقق بنجاح' : 'فشل التحقق',
      );
    } on PlatformException catch (e) {
      return BiometricResult(
        success: false,
        message: _getErrorMessage(e.code),
        errorCode: e.code,
      );
    }
  }

  /// Authenticate specifically for viewing card details
  Future<BiometricResult> authenticateForCardDetails() async {
    return authenticate(
      reason: 'التحقق لعرض تفاصيل البطاقة الحساسة',
      biometricOnly: false, // Allow PIN fallback
    );
  }

  /// Authenticate for sensitive financial operations
  Future<BiometricResult> authenticateForTransaction() async {
    return authenticate(
      reason: 'التحقق لإتمام العملية المالية',
      biometricOnly: false,
    );
  }

  /// Cancel ongoing authentication
  Future<void> cancelAuthentication() async {
    try {
      await _localAuth.stopAuthentication();
    } catch (_) {}
  }

  String _getErrorMessage(String code) {
    switch (code) {
      case auth_error.notAvailable:
        return 'التحقق البيومتري غير متاح على هذا الجهاز';
      case auth_error.notEnrolled:
        return 'لم يتم تسجيل بصمة أو وجه على هذا الجهاز';
      case auth_error.lockedOut:
        return 'تم قفل التحقق البيومتري. حاول لاحقاً';
      case auth_error.permanentlyLockedOut:
        return 'التحقق البيومتري مقفل بشكل دائم. استخدم قفل الشاشة';
      case auth_error.passcodeNotSet:
        return 'لم يتم تعيين قفل شاشة على هذا الجهاز';
      default:
        return 'حدث خطأ في التحقق';
    }
  }
}

/// Result of biometric authentication
class BiometricResult {
  final bool success;
  final String message;
  final String? errorCode;

  BiometricResult({
    required this.success,
    required this.message,
    this.errorCode,
  });

  bool get isLockedOut => 
      errorCode == auth_error.lockedOut || 
      errorCode == auth_error.permanentlyLockedOut;
}
