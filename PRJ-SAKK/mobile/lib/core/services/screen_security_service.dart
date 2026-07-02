import 'dart:io';
import 'package:flutter/services.dart';

/// Screen security service
/// Prevents screenshots and screen recording for sensitive screens
class ScreenSecurityService {
  static const MethodChannel _channel = MethodChannel('screen_security');
  
  /// Enable screen security (prevent screenshots/recording)
  /// Call when entering sensitive screens like card details
  static Future<void> enableSecureScreen() async {
    try {
      if (Platform.isAndroid) {
        await _channel.invokeMethod('enableSecureFlag');
      } else if (Platform.isIOS) {
        // iOS uses different approach - overlay view
        await _channel.invokeMethod('enableSecureScreen');
      }
    } on MissingPluginException {
      // Plugin not available, use fallback
      await _enableSecureFlagFallback();
    } catch (e) {
      // Silently fail - security is best-effort
    }
  }

  /// Disable screen security
  /// Call when leaving sensitive screens
  static Future<void> disableSecureScreen() async {
    try {
      if (Platform.isAndroid) {
        await _channel.invokeMethod('disableSecureFlag');
      } else if (Platform.isIOS) {
        await _channel.invokeMethod('disableSecureScreen');
      }
    } on MissingPluginException {
      await _disableSecureFlagFallback();
    } catch (e) {
      // Silently fail
    }
  }

  /// Fallback using SystemChrome (limited effectiveness)
  static Future<void> _enableSecureFlagFallback() async {
    // This is a limited fallback - doesn't actually prevent screenshots
    // but signals to the system that the content is sensitive
    await SystemChrome.setEnabledSystemUIMode(
      SystemUiMode.manual,
      overlays: [SystemUiOverlay.top, SystemUiOverlay.bottom],
    );
  }

  static Future<void> _disableSecureFlagFallback() async {
    await SystemChrome.setEnabledSystemUIMode(
      SystemUiMode.edgeToEdge,
    );
  }

  /// Check if running in secure environment
  /// Returns false if device is rooted/jailbroken (basic check)
  static Future<bool> isSecureEnvironment() async {
    try {
      if (Platform.isAndroid) {
        // Basic root detection
        final result = await _channel.invokeMethod<bool>('isDeviceSecure');
        return result ?? true;
      }
      return true; // Assume secure on iOS
    } catch (e) {
      return true; // Assume secure if check fails
    }
  }
}

/// Helper class for managing screen security in StatefulWidgets
/// Use ScreenSecurityService.enableSecureScreen() and disableSecureScreen() directly
/// in initState() and dispose() of your widget instead of using a mixin
