import 'dart:io';
import 'dart:typed_data';

import 'package:flutter/services.dart' show rootBundle;
import 'package:intl/intl.dart';
import 'package:path_provider/path_provider.dart';
import 'package:pdf/pdf.dart';
import 'package:pdf/widgets.dart' as pw;
import 'package:printing/printing.dart';

import '../../../core/utils/money_formatter.dart';
import 'models/transaction_model.dart';

/// Generates a clean, professional **black & white** A4 PDF receipt/invoice for
/// a transaction (Arabic, RTL, Cairo font) and hands it to share / print / save.
class ReceiptService {
  // Monochrome palette only — no brand colors.
  static const PdfColor _ink = PdfColor.fromInt(0xFF111111);
  static const PdfColor _muted = PdfColor.fromInt(0xFF6B7280);
  static const PdfColor _faint = PdfColor.fromInt(0xFF9AA0A6);
  static const PdfColor _line = PdfColor.fromInt(0xFFD6D6D6);
  static const PdfColor _hair = PdfColor.fromInt(0xFFECECEC);
  static const PdfColor _wash = PdfColor.fromInt(0xFFF5F5F5);

  static pw.Font? _regular;
  static pw.Font? _semi;
  static pw.Font? _bold;

  static Future<void> _ensureFonts() async {
    try {
      _regular ??= pw.Font.ttf(
          await rootBundle.load('assets/fonts/IBMPlexSansArabic-Regular.ttf'));
      _semi ??= pw.Font.ttf(
          await rootBundle.load('assets/fonts/IBMPlexSansArabic-SemiBold.ttf'));
      _bold ??= pw.Font.ttf(
          await rootBundle.load('assets/fonts/IBMPlexSansArabic-Bold.ttf'));
    } catch (_) {
      _regular = null;
      _semi = null;
      _bold = null;
      throw Exception('تعذّر تحميل خط الإيصال.');
    }
  }

  /// Build the receipt PDF bytes for a transaction.
  static Future<Uint8List> build(
    TransactionModel tx, {
    String? counterpartyName,
    String? counterpartyAccount,
    String? ownerName,
  }) async {
    await _ensureFonts();

    final theme = pw.ThemeData.withFont(base: _regular!, bold: _bold!);
    final doc = pw.Document(theme: theme, title: 'SAKK Receipt');

    final incoming = tx.isIncoming;
    final date = DateFormat('yyyy/MM/dd').format(tx.createdAt);
    final time = DateFormat('HH:mm').format(tx.createdAt);
    final ref = tx.reference ?? 'TXN-${tx.id}';
    final sign = incoming ? '+' : '−';

    doc.addPage(
      pw.Page(
        pageFormat: PdfPageFormat.a4,
        margin: pw.EdgeInsets.zero,
        textDirection: pw.TextDirection.rtl,
        build: (context) {
          return pw.Column(
            crossAxisAlignment: pw.CrossAxisAlignment.stretch,
            children: [
              _header(ref, date, time),
              pw.Padding(
                padding: const pw.EdgeInsets.fromLTRB(40, 30, 40, 0),
                child: pw.Column(
                  crossAxisAlignment: pw.CrossAxisAlignment.stretch,
                  children: [
                    _amountBlock(tx, sign, incoming),
                    pw.SizedBox(height: 28),
                    _sectionTitle('تفاصيل المعاملة'),
                    pw.SizedBox(height: 12),
                    _detailsTable(tx, ref, date, time, counterpartyName,
                        counterpartyAccount, ownerName, incoming),
                    pw.SizedBox(height: 24),
                    _sectionTitle('الملخّص المالي'),
                    pw.SizedBox(height: 12),
                    _totals(tx, incoming),
                  ],
                ),
              ),
              pw.Spacer(),
              _footer(),
            ],
          );
        },
      ),
    );

    return doc.save();
  }

  /// Build and open the platform share sheet with the receipt attached.
  static Future<void> share(
    TransactionModel tx, {
    String? counterpartyName,
    String? counterpartyAccount,
    String? ownerName,
  }) async {
    final bytes = await build(tx,
        counterpartyName: counterpartyName,
        counterpartyAccount: counterpartyAccount,
        ownerName: ownerName);
    await Printing.sharePdf(
      bytes: bytes,
      filename: 'SAKK-Receipt-${tx.reference ?? tx.id}.pdf',
    );
  }

  /// Build the receipt and save it as a PDF file inside the app's documents
  /// directory. Returns the absolute file path.
  static Future<String> save(
    TransactionModel tx, {
    String? counterpartyName,
    String? counterpartyAccount,
    String? ownerName,
  }) async {
    final bytes = await build(tx,
        counterpartyName: counterpartyName,
        counterpartyAccount: counterpartyAccount,
        ownerName: ownerName);
    final dir = await getApplicationDocumentsDirectory();
    final file = File('${dir.path}/SAKK-Receipt-${tx.reference ?? tx.id}.pdf');
    await file.writeAsBytes(bytes, flush: true);
    return file.path;
  }

  /// Build the receipt and open the system print dialog.
  static Future<void> printReceipt(
    TransactionModel tx, {
    String? counterpartyName,
    String? counterpartyAccount,
    String? ownerName,
  }) async {
    await Printing.layoutPdf(
      name: 'SAKK-Receipt-${tx.reference ?? tx.id}',
      onLayout: (format) async => build(tx,
          counterpartyName: counterpartyName,
          counterpartyAccount: counterpartyAccount,
          ownerName: ownerName),
    );
  }

  // ─────────────────────────── sections ───────────────────────────

  static pw.Widget _header(String ref, String date, String time) {
    return pw.Container(
      width: double.infinity,
      color: _ink,
      padding: const pw.EdgeInsets.fromLTRB(40, 32, 40, 28),
      child: pw.Row(
        mainAxisAlignment: pw.MainAxisAlignment.spaceBetween,
        crossAxisAlignment: pw.CrossAxisAlignment.start,
        children: [
          pw.Column(
            crossAxisAlignment: pw.CrossAxisAlignment.start,
            children: [
              pw.Text('صكّ',
                  style: pw.TextStyle(
                      font: _bold, color: PdfColors.white, fontSize: 28)),
              pw.SizedBox(height: 3),
              pw.Text('SAKK WALLET',
                  style: pw.TextStyle(
                      font: _regular,
                      color: const PdfColor.fromInt(0xFFB8B8B8),
                      fontSize: 8,
                      letterSpacing: 4)),
            ],
          ),
          pw.Column(
            crossAxisAlignment: pw.CrossAxisAlignment.end,
            children: [
              pw.Text('إيصال معاملة',
                  style: pw.TextStyle(
                      font: _semi, color: PdfColors.white, fontSize: 14)),
              pw.SizedBox(height: 8),
              _headerMeta('رقم الإيصال', ref),
              pw.SizedBox(height: 3),
              _headerMeta('التاريخ', '$date  •  $time'),
            ],
          ),
        ],
      ),
    );
  }

  static pw.Widget _headerMeta(String label, String value) {
    return pw.Row(
      mainAxisSize: pw.MainAxisSize.min,
      children: [
        pw.Text('$label: ',
            style: pw.TextStyle(
                font: _regular,
                color: const PdfColor.fromInt(0xFF9A9A9A),
                fontSize: 8.5)),
        _ltr(
          pw.Text(value,
              style: pw.TextStyle(
                  font: _semi,
                  color: const PdfColor.fromInt(0xFFE6E6E6),
                  fontSize: 8.5)),
        ),
      ],
    );
  }

  static pw.Widget _amountBlock(
      TransactionModel tx, String sign, bool incoming) {
    return pw.Container(
      width: double.infinity,
      padding: const pw.EdgeInsets.symmetric(vertical: 24),
      decoration: pw.BoxDecoration(border: pw.Border.all(color: _ink, width: 1.2)),
      child: pw.Column(
        children: [
          pw.Text(incoming ? 'مبلغ وارد' : 'مبلغ صادر',
              style: pw.TextStyle(
                  font: _regular, color: _muted, fontSize: 10, letterSpacing: 2)),
          pw.SizedBox(height: 10),
          _ltr(
            pw.Text('$sign ${Money.format(tx.amount.abs(), tx.currency)}',
                style: pw.TextStyle(font: _bold, color: _ink, fontSize: 32)),
          ),
          pw.SizedBox(height: 14),
          pw.Container(
            padding: const pw.EdgeInsets.symmetric(horizontal: 16, vertical: 5),
            decoration: pw.BoxDecoration(
              border: pw.Border.all(color: _ink, width: 0.8),
              borderRadius: pw.BorderRadius.circular(20),
            ),
            child: pw.Text(tx.statusLabel,
                style: pw.TextStyle(
                    font: _semi, color: _ink, fontSize: 9, letterSpacing: 1)),
          ),
        ],
      ),
    );
  }

  static pw.Widget _detailsTable(
    TransactionModel tx,
    String ref,
    String date,
    String time,
    String? counterpartyName,
    String? counterpartyAccount,
    String? ownerName,
    bool incoming,
  ) {
    final rows = <List<dynamic>>[
      ['نوع المعاملة', tx.typeLabel, false],
      ['الحالة', tx.statusLabel, false],
      ['رقم العملية', ref, true],
      ['التاريخ والوقت', '$date  $time', true],
      ['العملة', Money.currencyLabel(tx.currency), false],
      if (ownerName != null && ownerName.isNotEmpty)
        ['صاحب الحساب', ownerName, false],
      if (counterpartyName != null && counterpartyName.isNotEmpty)
        [incoming ? 'المُرسِل' : 'المُستلِم', counterpartyName, false],
      if (counterpartyAccount != null && counterpartyAccount.isNotEmpty)
        ['رقم الحساب', counterpartyAccount, true],
      if (tx.description != null && tx.description!.isNotEmpty)
        ['ملاحظة', tx.description!, false],
      ['معرّف المعاملة', '#${tx.id}', true],
    ];

    return pw.Container(
      decoration: pw.BoxDecoration(border: pw.Border.all(color: _line, width: 1)),
      child: pw.Column(
        children: [
          for (int i = 0; i < rows.length; i++)
            _row(rows[i][0] as String, rows[i][1] as String,
                ltr: rows[i][2] as bool, last: i == rows.length - 1),
        ],
      ),
    );
  }

  static pw.Widget _row(String label, String value,
      {bool ltr = false, bool last = false}) {
    return pw.Container(
      padding: const pw.EdgeInsets.symmetric(horizontal: 16, vertical: 10),
      decoration: pw.BoxDecoration(
        border: last
            ? null
            : const pw.Border(bottom: pw.BorderSide(color: _hair, width: 0.8)),
      ),
      child: pw.Row(
        crossAxisAlignment: pw.CrossAxisAlignment.start,
        children: [
          pw.SizedBox(
            width: 120,
            child: pw.Text(label,
                style: pw.TextStyle(font: _regular, color: _muted, fontSize: 10)),
          ),
          pw.Expanded(
            child: pw.Align(
              alignment: pw.Alignment.centerLeft,
              child: ltr
                  ? _ltr(pw.Text(value,
                      style: pw.TextStyle(
                          font: _semi, color: _ink, fontSize: 10.5)))
                  : pw.Text(value,
                      textAlign: pw.TextAlign.left,
                      style: pw.TextStyle(
                          font: _semi, color: _ink, fontSize: 10.5)),
            ),
          ),
        ],
      ),
    );
  }

  static pw.Widget _totals(TransactionModel tx, bool incoming) {
    final gross = tx.amount.abs();
    final fee = tx.fee;
    final total = incoming ? gross - fee : gross + fee;
    final totalLabel = incoming ? 'الصافي المستلَم' : 'الإجمالي المخصوم';

    return pw.Container(
      decoration: pw.BoxDecoration(
        color: _wash,
        border: pw.Border.all(color: _line, width: 1),
      ),
      padding: const pw.EdgeInsets.symmetric(horizontal: 18, vertical: 8),
      child: pw.Column(
        children: [
          _totalRow('المبلغ', Money.format(gross, tx.currency)),
          _totalRow('الرسوم', fee > 0 ? Money.format(fee, tx.currency) : 'مجاناً'),
          pw.Container(
            margin: const pw.EdgeInsets.symmetric(vertical: 8),
            height: 1,
            color: _line,
          ),
          _totalRow(totalLabel, Money.format(total, tx.currency), bold: true),
        ],
      ),
    );
  }

  static pw.Widget _totalRow(String label, String value, {bool bold = false}) {
    return pw.Padding(
      padding: const pw.EdgeInsets.symmetric(vertical: 5),
      child: pw.Row(
        mainAxisAlignment: pw.MainAxisAlignment.spaceBetween,
        children: [
          pw.Text(label,
              style: pw.TextStyle(
                  font: bold ? _bold : _regular,
                  color: bold ? _ink : _muted,
                  fontSize: bold ? 12 : 10.5)),
          _ltr(pw.Text(value,
              style: pw.TextStyle(
                  font: bold ? _bold : _semi,
                  color: _ink,
                  fontSize: bold ? 13 : 11))),
        ],
      ),
    );
  }

  static pw.Widget _sectionTitle(String title) {
    return pw.Row(
      crossAxisAlignment: pw.CrossAxisAlignment.center,
      children: [
        pw.Text(title,
            style: pw.TextStyle(font: _bold, color: _ink, fontSize: 12)),
        pw.SizedBox(width: 12),
        pw.Expanded(child: pw.Container(height: 1, color: _hair)),
      ],
    );
  }

  static pw.Widget _footer() {
    final gen = DateFormat('yyyy/MM/dd HH:mm').format(DateTime.now());
    return pw.Container(
      width: double.infinity,
      padding: const pw.EdgeInsets.fromLTRB(40, 18, 40, 26),
      decoration:
          const pw.BoxDecoration(border: pw.Border(top: pw.BorderSide(color: _line, width: 1))),
      child: pw.Column(
        children: [
          pw.Text('شكراً لاستخدامك محفظة صكّ',
              style: pw.TextStyle(font: _semi, color: _ink, fontSize: 10.5)),
          pw.SizedBox(height: 5),
          pw.Text(
              'هذا إيصال إلكتروني صادر تلقائياً، ولا يتطلّب توقيعاً أو ختماً.',
              style: pw.TextStyle(font: _regular, color: _muted, fontSize: 8.5)),
          pw.SizedBox(height: 12),
          pw.Row(
            mainAxisAlignment: pw.MainAxisAlignment.spaceBetween,
            children: [
              pw.Text('SAKK WALLET',
                  style: pw.TextStyle(
                      font: _semi, color: _faint, fontSize: 8, letterSpacing: 2)),
              _ltr(pw.Text('Generated $gen',
                  style: pw.TextStyle(font: _regular, color: _faint, fontSize: 8))),
            ],
          ),
        ],
      ),
    );
  }

  /// Force LTR for numbers / references / accounts inside the RTL page.
  static pw.Widget _ltr(pw.Widget child) =>
      pw.Directionality(textDirection: pw.TextDirection.ltr, child: child);
}
