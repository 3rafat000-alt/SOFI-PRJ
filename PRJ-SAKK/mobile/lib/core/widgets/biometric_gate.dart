import 'package:flutter/material.dart';

import '../services/biometric_service.dart';
import '../theme/app_colors.dart';

/// Prompts the user to confirm a sensitive operation with biometrics
/// (fingerprint / face). The OS provides the device-credential fallback.
///
/// Returns `true` if the user authenticated (or the device has no biometric
/// hardware at all — in which case we don't block the operation, since the PIN
/// flow has been removed). Shows a red SnackBar on an explicit failure.
Future<bool> confirmWithBiometrics(
  BuildContext context, {
  String reason = 'الرجاء التحقق من هويتك لتأكيد العملية',
}) async {
  final service = BiometricService();

  final supported = await service.isSupported();
  if (!supported) {
    // No biometric hardware — allow the action (no PIN fallback any more).
    return true;
  }

  final result = await service.authenticate(reason: reason);
  if (!result.success && context.mounted) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(result.message),
        backgroundColor: AppColors.error,
        behavior: SnackBarBehavior.floating,
      ),
    );
  }
  return result.success;
}
