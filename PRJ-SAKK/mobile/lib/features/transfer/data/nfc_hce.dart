import 'package:flutter/foundation.dart';
import 'package:flutter/services.dart';

/// Host Card Emulation control: makes THIS phone act like an NFC card that
/// holds the user's SAKK account number, so another SAKK phone can read it by
/// tapping. Backed by a native Android HostApduService.
class NfcHce {
  static const MethodChannel _channel = MethodChannel('sakk/nfc_hce');

  /// Whether the device has NFC (HCE) at all.
  static Future<bool> isSupported() async {
    try {
      return await _channel.invokeMethod<bool>('isSupported') ?? false;
    } catch (_) {
      return false;
    }
  }

  /// Start emulating a card that returns the raw [payload] string verbatim.
  static Future<bool> startEmulation(String payload) async {
    try {
      return await _channel.invokeMethod<bool>('startEmulation', {'account': payload}) ?? false;
    } catch (_) {
      return false;
    }
  }

  /// Broadcast a payment request as an NDEF tag. The PAYING phone reads it via
  /// the OS (no app/foreground needed) and auto-launches SAKK to a confirm
  /// screen. [amount]/[currency]/[name] are optional — omit amount for an
  /// "any amount" receive.
  ///
  /// Emitted URI: `sakk://nfcpay?a={account}&amt={amount}&cur={currency}&n={name}`
  static Future<bool> startPaymentBroadcast({
    required String account,
    double? amount,
    String? currency,
    String? name,
  }) async {
    final amountStr = (amount != null && amount > 0)
        ? (amount == amount.roundToDouble()
            ? amount.toStringAsFixed(0)
            : amount.toStringAsFixed(2))
        : '';
    final uri = Uri(
      scheme: 'sakk',
      host: 'nfcpay',
      queryParameters: {
        'a': account,
        if (amountStr.isNotEmpty) 'amt': amountStr,
        if ((currency ?? '').trim().isNotEmpty) 'cur': currency!.trim(),
        if ((name ?? '').trim().isNotEmpty) 'n': name!.trim(),
      },
    ).toString();
    final ok = await startEmulation(uri);
    if (ok) await setPreferred(true);
    return ok;
  }

  /// Make our HCE service the preferred one (resolves NDEF AID conflicts with
  /// the system NFC service while the receive screen is foreground).
  static Future<void> setPreferred(bool enable) async {
    try {
      await _channel.invokeMethod('setPreferred', {'enable': enable});
    } catch (e) {
      debugPrint('nfc_hce: setPreferred($enable) failed: $e');
    }
  }

  /// Stop emulating (clears the stored payload).
  static Future<void> stopEmulation() async {
    try {
      await setPreferred(false);
      await _channel.invokeMethod('stopEmulation');
    } catch (_) {}
  }
}

/// Receives SAKK payment URIs delivered by an NFC tap that launched (or
/// resumed) the app — works even when the app was completely closed.
class NfcLaunch {
  static const MethodChannel _channel = MethodChannel('sakk/nfc_hce');

  /// The URI the app was cold-launched with via an NFC tap, if any. Consumes
  /// it (returns null on subsequent calls).
  static Future<String?> consumeInitialUri() async {
    try {
      return await _channel.invokeMethod<String>('getInitialNfcUri');
    } catch (_) {
      return null;
    }
  }

  /// Register a callback for NFC taps that arrive while the app is running.
  static void setHandler(void Function(String uri) onUri) {
    _channel.setMethodCallHandler((call) async {
      if (call.method == 'onNfcUri' && call.arguments is String) {
        onUri(call.arguments as String);
      }
      return null;
    });
  }
}
