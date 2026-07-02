import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:iconsax/iconsax.dart';

import '../../../../core/theme/app_colors.dart';
import '../../../../core/utils/money_formatter.dart';
import '../../../../core/widgets/app_ui.dart';
import '../../../cashback/data/cashback_repository.dart';

/// Compact dashboard card showing total cashback earned → opens the cashback
/// operations page.
class CashbackCard extends ConsumerWidget {
  const CashbackCard({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final async = ref.watch(cashbackProvider);
    return async.when(
      data: (c) => _card(context, Money.format(c.total, c.currency), c.count),
      loading: () => _card(context, '—', null),
      error: (_, __) => const SizedBox.shrink(),
    );
  }

  Widget _card(BuildContext context, String amount, int? count) {
    final colors = context.appColors;
    return AppCard(
      onTap: () => context.push('/cashback'),
      child: Row(
        children: [
          Container(
            width: 48,
            height: 48,
            decoration: BoxDecoration(
              gradient: LinearGradient(
                colors: colors.cardGradientVisa,
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
              borderRadius: BorderRadius.circular(14),
            ),
            child: const Icon(Iconsax.gift, color: Colors.white, size: 24),
          ),
          const SizedBox(width: AppSpacing.md),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('الكاش باك المكتسب',
                    style:
                        TextStyle(fontSize: 12.5, color: colors.textSecondary)),
                const SizedBox(height: 3),
                Row(
                  crossAxisAlignment: CrossAxisAlignment.baseline,
                  textBaseline: TextBaseline.alphabetic,
                  children: [
                    Directionality(
                      textDirection: TextDirection.ltr,
                      child: Text(amount,
                          style: TextStyle(
                              fontSize: 19,
                              fontWeight: FontWeight.w800,
                              color: colors.textPrimary)),
                    ),
                    if (count != null && count > 0) ...[
                      const SizedBox(width: 8),
                      Text('$count عملية',
                          style: TextStyle(
                              fontSize: 12, color: colors.textHint)),
                    ],
                  ],
                ),
              ],
            ),
          ),
          Container(
            padding: const EdgeInsets.symmetric(
                horizontal: AppSpacing.md, vertical: 6),
            decoration: BoxDecoration(
              color: colors.primaryLight,
              borderRadius: BorderRadius.circular(AppRadius.pill),
            ),
            child: Row(mainAxisSize: MainAxisSize.min, children: [
              Text('عرض',
                  style: TextStyle(
                      fontSize: 12.5,
                      fontWeight: FontWeight.w700,
                      color: colors.primary)),
              const SizedBox(width: 2),
              Icon(Iconsax.arrow_left_2, size: 14, color: colors.primary),
            ]),
          ),
        ],
      ),
    );
  }
}
