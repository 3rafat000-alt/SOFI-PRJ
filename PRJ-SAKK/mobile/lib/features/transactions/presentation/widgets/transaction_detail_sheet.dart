import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:iconsax/iconsax.dart';

import '../../../../core/theme/app_colors.dart';
import '../../../../core/utils/money_formatter.dart';
import '../../../../core/widgets/app_ui.dart';
import '../../../dashboard/presentation/widgets/recent_transaction_item.dart';
import '../../data/models/transaction_model.dart';
import '../../data/receipt_service.dart';

class TransactionDetailSheet extends StatelessWidget {
  final TransactionModel tx;
  const TransactionDetailSheet(this.tx, {super.key});

  static void show(BuildContext context, TransactionModel tx) {
    final colors = context.appColors;
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      useSafeArea: true,
      backgroundColor: colors.surface,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (_) => TransactionDetailSheet(tx),
    );
  }

  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    final incoming = tx.isIncoming;
    final color = incoming ? colors.success : colors.error;
    final icon = RecentTransactionItem.iconFor(tx.type);
    final counterparty = tx.counterpartyName;

    return SingleChildScrollView(
      padding: const EdgeInsets.fromLTRB(24, 12, 24, 28),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Center(
            child: Container(
              width: 40, height: 4,
              decoration: BoxDecoration(color: colors.textHint.withValues(alpha: 0.4), borderRadius: BorderRadius.circular(2)),
            ),
          ),
          const SizedBox(height: 20),

          Center(
            child: Column(
              children: [
                Container(
                  width: 64, height: 64,
                  decoration: BoxDecoration(color: color.withValues(alpha: 0.1), borderRadius: BorderRadius.circular(18)),
                  child: Icon(icon, color: color, size: 30),
                ),
                const SizedBox(height: 14),
                Text(
                  '${incoming ? '+' : '-'}${Money.format(tx.amount.abs(), tx.currency)}',
                  style: TextStyle(fontSize: 28, fontWeight: FontWeight.bold, color: color),
                ),
                const SizedBox(height: 6),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 5),
                  decoration: BoxDecoration(
                    color: tx.statusColor.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: Text(tx.statusLabel,
                      style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: tx.statusColor)),
                ),
              ],
            ),
          ),
          const SizedBox(height: 24),

          Container(
            decoration: BoxDecoration(
              color: colors.background,
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: colors.inputBackground),
            ),
            padding: const EdgeInsets.symmetric(horizontal: 16),
            child: Column(
              children: [
                _row(context, 'النوع', tx.typeLabel),
                _row(context, 'رقم المرجع', tx.reference ?? 'TXN-${tx.id}', mono: true, copyable: true),
                _row(context, 'التاريخ', tx.formattedDate),
                _row(context, 'العملة', Money.currencyLabel(tx.currency)),
                if (tx.fee > 0) _row(context, 'الرسوم', Money.format(tx.fee, tx.currency)),
                if (counterparty != null) _row(context, incoming ? 'من' : 'إلى', counterparty),
                if (tx.note != null) _row(context, 'ملاحظة', tx.note!, last: true),
              ],
            ),
          ),
          const SizedBox(height: 24),

          // ───── إجراءات الإيصال (PDF) ─────
          Row(
            children: [
              Icon(Iconsax.receipt_2, size: 16, color: colors.textSecondary),
              const SizedBox(width: 6),
              Text(
                'الإيصال (PDF)',
                style: TextStyle(
                  fontSize: 13,
                  fontWeight: FontWeight.w700,
                  color: colors.textSecondary,
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          Container(
            decoration: BoxDecoration(
              color: colors.background,
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: colors.inputBackground),
            ),
            padding: const EdgeInsets.symmetric(vertical: 8, horizontal: 4),
            child: Row(
              children: [
                AppActionButton(
                  icon: Iconsax.share,
                  label: 'مشاركة',
                  onTap: () => _runReceiptAction(
                    context,
                    () => ReceiptService.share(
                      tx,
                      counterpartyName: tx.counterpartyName,
                    ),
                  ),
                ),
                AppActionButton(
                  icon: Iconsax.printer,
                  label: 'طباعة',
                  onTap: () => _runReceiptAction(
                    context,
                    () => ReceiptService.printReceipt(
                      tx,
                      counterpartyName: tx.counterpartyName,
                    ),
                  ),
                ),
                AppActionButton(
                  icon: Iconsax.document_download,
                  label: 'حفظ',
                  onTap: () => _runReceiptAction(
                    context,
                    () => ReceiptService.save(
                      tx,
                      counterpartyName: tx.counterpartyName,
                    ),
                    successMessage: 'تم حفظ الإيصال في مستندات التطبيق',
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  /// Runs a receipt action (share / print) safely.
  /// On failure shows a floating red SnackBar with the error details.
  Future<void> _runReceiptAction(
    BuildContext context,
    Future<void> Function() action, {
    String? successMessage,
  }) async {
    final messenger = ScaffoldMessenger.of(context);
    try {
      await action();
      // The sheet may have been dismissed during the async work — bail out
      // before touching the (possibly deactivated) widget tree.
      if (successMessage != null && context.mounted) {
        messenger.showSnackBar(
          SnackBar(
            content: Text(
              successMessage,
              style: const TextStyle(color: Colors.white),
            ),
            backgroundColor: AppColors.success,
            behavior: SnackBarBehavior.floating,
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(AppRadius.md),
            ),
          ),
        );
      }
    } catch (e) {
      if (!context.mounted) return;
      messenger.showSnackBar(
        SnackBar(
          content: Text(
            'تعذّر إنشاء الإيصال: ${e.toString()}',
            style: const TextStyle(color: Colors.white),
          ),
          backgroundColor: AppColors.error,
          behavior: SnackBarBehavior.floating,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(AppRadius.md),
          ),
        ),
      );
    }
  }

  Widget _row(BuildContext context, String label, String value,
      {bool mono = false, bool last = false, bool copyable = false}) {
    final colors = context.appColors;
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 13),
      decoration: BoxDecoration(
        border: last ? null : Border(bottom: BorderSide(color: colors.inputBackground, width: 0.6)),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label, style: TextStyle(fontSize: 13, color: colors.textSecondary)),
          const SizedBox(width: 16),
          Expanded(
            child: Text(
              value,
              textAlign: TextAlign.end,
              style: TextStyle(
                fontSize: 13.5,
                fontWeight: FontWeight.w600,
                color: colors.textPrimary,
                fontFamily: mono ? 'monospace' : null,
              ),
            ),
          ),
          if (copyable)
            GestureDetector(
              onTap: () {
                Clipboard.setData(ClipboardData(text: value));
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(content: Text('تم النسخ'), backgroundColor: AppColors.success),
                );
              },
              child: Padding(
                padding: const EdgeInsets.only(right: 8),
                child: Icon(Iconsax.copy, size: 16, color: colors.primary),
              ),
            ),
        ],
      ),
    );
  }
}
