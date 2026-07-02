import 'package:flutter/material.dart';
import 'package:iconsax/iconsax.dart';

import '../../../../core/theme/app_colors.dart';
import '../../../transactions/data/models/transaction_model.dart';
import '../../../transactions/presentation/widgets/transaction_detail_sheet.dart';

class RecentTransactionItem extends StatelessWidget {
  final TransactionModel transaction;
  
  const RecentTransactionItem({
    super.key,
    required this.transaction,
  });

  static IconData iconFor(String type) {
    switch (type) {
      case 'deposit':
        return Iconsax.receive_square;
      case 'withdrawal':
        return Iconsax.send_square;
      case 'transfer_in':
        return Iconsax.arrow_down_2;
      case 'transfer_out':
        return Iconsax.arrow_up_3;
      case 'salary_in':
      case 'payroll_out':
        return Iconsax.briefcase;
      case 'exchange':
        return Iconsax.arrow_swap_horizontal;
      case 'card_payment':
        return Iconsax.card;
      default:
        return Iconsax.money_send;
    }
  }

  static Color colorFor(BuildContext context, String type) {
    final colors = context.appColors;
    switch (type) {
      case 'deposit':
      case 'transfer_in':
      case 'salary_in':
        return colors.success;
      case 'withdrawal':
      case 'transfer_out':
      case 'payroll_out':
        return colors.error;
      case 'exchange':
        return colors.primary;
      case 'card_payment':
        return colors.secondary;
      default:
        return colors.textSecondary;
    }
  }

  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    final isIncoming = transaction.isIncoming;
    final icon = iconFor(transaction.type);
    final color = colorFor(context, transaction.type);

    return InkWell(
      borderRadius: BorderRadius.circular(12),
      onTap: () => TransactionDetailSheet.show(context, transaction),
      child: Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: colors.surface,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: colors.inputBackground),
      ),
      child: Row(
        children: [
          // Icon
          Container(
            width: 44,
            height: 44,
            decoration: BoxDecoration(
              color: color.withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Icon(icon, color: color, size: 22),
          ),
          const SizedBox(width: 12),
          // Details
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  transaction.displayLabel,
                  style: TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w600,
                    color: colors.textPrimary,
                  ),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
                const SizedBox(height: 4),
                Text(
                  transaction.formattedDate,
                  style: TextStyle(
                    fontSize: 12,
                    color: colors.textSecondary,
                  ),
                ),
              ],
            ),
          ),
          // Amount
          Column(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              Text(
                '${isIncoming ? '+' : '-'}${transaction.formattedAmount}',
                style: TextStyle(
                  fontSize: 15,
                  fontWeight: FontWeight.bold,
                  color: isIncoming ? colors.success : colors.error,
                ),
              ),
              const SizedBox(height: 4),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                decoration: BoxDecoration(
                  color: transaction.statusColor.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(4),
                ),
                child: Text(
                  transaction.statusLabel,
                  style: TextStyle(
                    fontSize: 10,
                    fontWeight: FontWeight.w500,
                    color: transaction.statusColor,
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
      ),
    );
  }
}
