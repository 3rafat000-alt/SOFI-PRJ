import 'dart:async';
import 'dart:convert';

import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:nfc_manager/nfc_manager.dart';
import 'package:nfc_manager/platform_tags.dart';

/// An NFC payment tapped from another phone, awaiting display once the user is
/// authenticated/unlocked. Consumed by the send page (or MainShell after a
/// cold-start unlock).
final pendingNfcPaymentProvider = StateProvider<NfcPayment?>((ref) => null);

/// Thin wrapper around nfc_manager for reading a SAKK tag from an NFC
/// NDEF text record (e.g. a programmed sticker/card or another device).
///
/// Degrades gracefully: returns false/null when NFC is unavailable so the
/// UI can fall back to QR / account-number entry.
class NfcReader {
  static Future<bool> isAvailable() async {
    try {
      return await NfcManager.instance.isAvailable();
    } catch (_) {
      return false;
    }
  }

  /// Starts a session and resolves with the first NDEF text payload found.
  /// Auto-stops on read or after [timeout]. Returns null on timeout/error.
  static Future<String?> readText({Duration timeout = const Duration(seconds: 25)}) async {
    final completer = Completer<String?>();

    try {
      await NfcManager.instance.startSession(
        onDiscovered: (NfcTag tag) async {
          final text = _extractText(tag);
          try {
            await NfcManager.instance.stopSession();
          } catch (e) {
            debugPrint('nfc_reader: stopSession after readText failed: $e');
          }
          if (!completer.isCompleted) completer.complete(text);
        },
      );
    } catch (_) {
      if (!completer.isCompleted) completer.complete(null);
    }

    return completer.future.timeout(timeout, onTimeout: () {
      _safeStop();
      return null;
    });
  }

  /// SELECT APDU for the SAKK AID (F0 53 41 4B 4B 01).
  static final Uint8List _selectSakkAid = Uint8List.fromList([
    0x00, 0xA4, 0x04, 0x00, 0x06, 0xF0, 0x53, 0x41, 0x4B, 0x4B, 0x01, 0x00,
  ]);

  /// Reads a SAKK account number from another phone running our HCE service
  /// (or a compatible card) via IsoDep SELECT-by-AID. Returns the account
  /// string (e.g. "SK00000002") or null on timeout/failure.
  static Future<String?> readSakkCard({Duration timeout = const Duration(seconds: 25)}) async {
    final completer = Completer<String?>();

    try {
      await NfcManager.instance.startSession(
        onDiscovered: (NfcTag tag) async {
          String? account;
          try {
            final isoDep = IsoDep.from(tag);
            if (isoDep != null) {
              final response = await isoDep.transceive(data: _selectSakkAid);
              account = _parseApduResponse(response);
            }
          } catch (_) {}
          try {
            await NfcManager.instance.stopSession();
          } catch (_) {}
          if (!completer.isCompleted) completer.complete(account);
        },
      );
    } catch (_) {
      if (!completer.isCompleted) completer.complete(null);
    }

    return completer.future.timeout(timeout, onTimeout: () {
      _safeStop();
      return null;
    });
  }

  /// Reads a SAKK payment broadcast from another phone running our HCE service.
  /// The receiver may attach a fixed amount/currency + their display name, so
  /// the sender can confirm "pay X to {name}" in one tap. Returns null on
  /// timeout / when nothing readable was tapped.
  static Future<NfcPayment?> readPayment(
      {Duration timeout = const Duration(seconds: 60)}) async {
    final raw = await readSakkCard(timeout: timeout);
    return NfcPayment.parse(raw);
  }

  /// Strip the trailing status word (90 00) and decode the account number.
  static String? _parseApduResponse(Uint8List response) {
    if (response.length < 2) return null;
    final sw1 = response[response.length - 2];
    final sw2 = response[response.length - 1];
    if (sw1 != 0x90 || sw2 != 0x00) return null;
    final payload = response.sublist(0, response.length - 2);
    if (payload.isEmpty) return null;
    final text = utf8.decode(payload, allowMalformed: true).trim();
    return text.isEmpty ? null : text;
  }

  static Future<void> stop() async => _safeStop();

  static Future<void> _safeStop() async {
    try {
      await NfcManager.instance.stopSession();
    } catch (_) {}
  }

  static String? _extractText(NfcTag tag) {
    try {
      final ndef = Ndef.from(tag);
      final message = ndef?.cachedMessage;
      if (message == null) return null;
      for (final record in message.records) {
        final text = _decodeTextRecord(record);
        if (text != null && text.isNotEmpty) return text;
      }
    } catch (_) {}
    return null;
  }

  /// Decode an NFC Well-Known Text (RTD_TEXT) record payload:
  /// byte0 = status (bits0-5 = language-code length), then lang, then UTF-8/UTF-16 text.
  static String? _decodeTextRecord(NdefRecord record) {
    try {
      final payload = record.payload;
      if (payload.isEmpty) return null;
      final status = payload[0];
      final langLen = status & 0x3F;
      final isUtf16 = (status & 0x80) != 0;
      final textBytes = payload.sublist(1 + langLen);
      return isUtf16
          ? String.fromCharCodes(textBytes)
          : utf8.decode(textBytes, allowMalformed: true);
    } catch (_) {
      return null;
    }
  }
}

/// A payment broadcast read over NFC. The receiver (POS / merchant) prepares
/// the amount and taps "receive"; the sender reads {account, amount, currency,
/// name} and confirms.
///
/// Wire format (echoed verbatim by the HCE service):
///   `SAKKPAY|{account}|{amount}|{currency}|{name}`
/// where amount/currency/name may be empty ("any amount"). A bare account
/// number (legacy / programmed sticker) is also accepted.
class NfcPayment {
  final String account;
  final double? amount;
  final String? currency;
  final String? name;

  const NfcPayment({
    required this.account,
    this.amount,
    this.currency,
    this.name,
  });

  bool get hasAmount => amount != null && amount! > 0;

  static NfcPayment? parse(String? raw) {
    final s = raw?.trim() ?? '';
    if (s.isEmpty) return null;

    if (s.toUpperCase().startsWith('SAKKPAY|')) {
      final parts = s.split('|');
      // parts[0] = SAKKPAY, [1] = account, [2] = amount, [3] = currency, [4..] = name
      if (parts.length < 2 || parts[1].trim().isEmpty) return null;
      final account = parts[1].trim();
      final amountStr = parts.length > 2 ? parts[2].trim() : '';
      final currencyStr = parts.length > 3 ? parts[3].trim().toUpperCase() : '';
      final name = parts.length > 4 ? parts.sublist(4).join('|').trim() : '';
      return NfcPayment(
        account: account,
        amount: amountStr.isEmpty ? null : double.tryParse(amountStr),
        currency: currencyStr.isEmpty ? null : currencyStr,
        name: name.isEmpty ? null : name,
      );
    }

    // Legacy / programmed sticker: a bare account number.
    return NfcPayment(account: s);
  }

  /// Parse the NDEF URI the app is launched with on an NFC tap:
  ///   `sakk://nfcpay?a={account}&amt={amount}&cur={currency}&n={name}`
  static NfcPayment? fromUri(String? raw) {
    final uri = Uri.tryParse(raw?.trim() ?? '');
    if (uri == null || uri.scheme.toLowerCase() != 'sakk') return null;
    final q = uri.queryParameters;
    final account = (q['a'] ?? '').trim();
    if (account.isEmpty) return null;
    final amountStr = (q['amt'] ?? '').trim();
    final currency = (q['cur'] ?? '').trim();
    final name = (q['n'] ?? '').trim();
    return NfcPayment(
      account: account,
      amount: amountStr.isEmpty ? null : double.tryParse(amountStr),
      currency: currency.isEmpty ? null : currency.toUpperCase(),
      name: name.isEmpty ? null : name,
    );
  }
}
