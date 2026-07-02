import 'package:intl/intl.dart';

/// Unified money formatting for the whole app.
///
/// Rules (per product spec — TRUE SCALE, no ÷100 anywhere):
/// - Thousand separators (e.g. 1,234,567)
/// - USD: exactly 2 decimals (e.g. $1,234.50)
/// - SYP: stored and displayed at true scale, no decimals
///   (1 USD ≈ 13,000 SYP → "ل.س 13,000", symbol LEFT). Matches backend, admin, and KYC limits.
/// - Accepts and normalizes Arabic/English numerals + decimal separators
class Money {
  Money._();

  static final NumberFormat _usdFmt = NumberFormat('#,##0.00', 'en_US');
  static final NumberFormat _sypFmt = NumberFormat('#,##0', 'en_US');

  /// Format a raw number with thousand separators.
  /// USD -> 2 decimals | SYP -> no decimals, true scale (no ÷100).
  static String number(num amount, String currency) {
    if (currency.toUpperCase() == 'USD') {
      return _usdFmt.format(amount);
    }
    // SYP at true scale — the value stored is the value shown.
    return _sypFmt.format(amount);
  }

  /// Format an amount with its currency symbol/suffix.
  /// USD -> "$1,234.50"   |   SYP -> "ل.س 13,000" (true scale, symbol LEFT)
  static String format(num amount, String currency) {
    final formatted = number(amount, currency);
    if (currency.toUpperCase() == 'USD') {
      return '⁦\$$formatted⁩';
    }
    return '⁦ل.س $formatted⁩';
  }

  /// Short currency label in Arabic.
  static String currencyLabel(String currency) =>
      currency.toUpperCase() == 'USD' ? 'دولار أمريكي' : 'ليرة سورية';

  /// SAKK account number from a numeric user id: 2 -> "SK00000002".
  static String accountNumber(int userId) =>
      'SK${userId.toString().padLeft(8, '0')}';

  // ─────────── Arabic/normalization helpers ───────────

  /// Normalize user-typed amount text to a parseable double string.
  /// Handles: Arabic numerals (٠-٩), Arabic decimal (٫→.), and the comma as a
  /// THOUSANDS separator (stripped) — at true scale users type "13,000" meaning
  /// thirteen thousand, not 13.0.
  static String normalizeAmountInput(String text) {
    return text
        .replaceAllMapped(RegExp(r'[٠١٢٣٤٥٦٧٨٩]'), _arabicToEnglishDigit)
        .replaceAll('٫', '.')
        .replaceAll(',', '')
        .trim();
  }

  static String _arabicToEnglishDigit(Match m) {
    const map = {'٠': '0', '١': '1', '٢': '2', '٣': '3', '٤': '4',
                 '٥': '5', '٦': '6', '٧': '7', '٨': '8', '٩': '9'};
    return map[m.group(0)] ?? m.group(0)!;
  }

  /// Parse an amount from user input.
  /// True scale — both currencies parsed as-is (no ×100 for SYP).
  /// USD allows decimals; SYP is a whole number.
  static double? parseAmount(String text, {String currency = 'USD'}) {
    if (text.trim().isEmpty) return null;
    final normalized = normalizeAmountInput(text);
    final value = double.tryParse(normalized);
    if (value == null) return null;
    return value;
  }

  /// Input formatter pattern that accepts digits + ONE decimal point.
  static String amountInputPattern(String currency) {
    return currency.toUpperCase() == 'USD'
        ? r'^\d*\.?\d{0,2}'   // USD: up to 2 decimals
        : r'^\d*';            // SYP: whole number, true scale (no subunit)
  }
}
