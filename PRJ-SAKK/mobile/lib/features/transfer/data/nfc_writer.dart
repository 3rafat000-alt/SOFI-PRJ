import 'dart:async';

import 'package:flutter/foundation.dart';
import 'package:nfc_manager/nfc_manager.dart';

/// Writes a SAKK account number to a writable NFC tag/sticker as an NDEF
/// text record, so it can later be read by another device via [NfcReader].
///
/// Degrades gracefully: returns a result enum instead of throwing so the UI
/// can show a clear Arabic message and fall back to QR / account number.
enum NfcWriteResult { success, notWritable, unavailable, timeout, error }

class NfcWriter {
  static Future<bool> isAvailable() async {
    try {
      return await NfcManager.instance.isAvailable();
    } catch (_) {
      return false;
    }
  }

  /// Starts a session and writes [text] (e.g. "SAKK:SK00000002") to the first
  /// discovered writable NDEF tag. Auto-stops on completion or [timeout].
  static Future<NfcWriteResult> writeText(
    String text, {
    Duration timeout = const Duration(seconds: 25),
  }) async {
    if (!await isAvailable()) return NfcWriteResult.unavailable;

    final completer = Completer<NfcWriteResult>();

    try {
      await NfcManager.instance.startSession(
        onDiscovered: (NfcTag tag) async {
          var result = NfcWriteResult.error;
          try {
            final ndef = Ndef.from(tag);
            if (ndef == null || !ndef.isWritable) {
              result = NfcWriteResult.notWritable;
            } else {
              final message = NdefMessage([NdefRecord.createText(text)]);
              await ndef.write(message);
              result = NfcWriteResult.success;
            }
          } catch (e) {
            debugPrint('nfc_writer: writeText failed: $e');
            result = NfcWriteResult.error;
          }
          try {
            await NfcManager.instance.stopSession();
          } catch (e) {
            debugPrint('nfc_writer: stopSession after writeText failed: $e');
          }
          if (!completer.isCompleted) completer.complete(result);
        },
      );
    } catch (_) {
      if (!completer.isCompleted) completer.complete(NfcWriteResult.error);
    }

    return completer.future.timeout(timeout, onTimeout: () {
      _safeStop();
      return NfcWriteResult.timeout;
    });
  }

  static Future<void> stop() async => _safeStop();

  static Future<void> _safeStop() async {
    try {
      await NfcManager.instance.stopSession();
    } catch (_) {}
  }
}
