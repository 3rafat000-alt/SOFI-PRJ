import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:iconsax/iconsax.dart';
import 'package:flutter_animate/flutter_animate.dart';

import '../../../../core/widgets/app_skeleton.dart';
import '../../../../core/widgets/app_ui.dart';
import '../../../dashboard/presentation/widgets/recent_transaction_item.dart';
import '../../data/repositories/card_repository.dart';

class CardTransactionsPage extends ConsumerWidget {
  final int cardId;
  const CardTransactionsPage({super.key, required this.cardId});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final txAsync = ref.watch(cardTransactionsProvider(cardId));

    return AppScaffold(
      title: 'سجل معاملات البطاقة',
      onRefresh: () async => ref.invalidate(cardTransactionsProvider(cardId)),
      body: txAsync.when(
        loading: () => const SkeletonListScene(items: 6),
        error:
            (e, _) => ListView(
              children: [
                const SizedBox(height: 60),
                EmptyState(
                  icon: Iconsax.warning_2,
                  title: 'تعذّر تحميل المعاملات',
                  subtitle: 'تحقّق من اتصالك وحاول مجدداً',
                  actionLabel: 'إعادة المحاولة',
                  onAction:
                      () => ref.invalidate(cardTransactionsProvider(cardId)),
                ),
              ],
            ),
        data: (items) {
          if (items.isEmpty) {
            return ListView(
              children: const [
                SizedBox(height: 60),
                EmptyState(
                  icon: Iconsax.receipt_2,
                  title: 'لا توجد معاملات',
                  subtitle: 'لم تُسجَّل أي عملية على هذه البطاقة بعد',
                ),
              ],
            );
          }
          return ListView.separated(
            padding: const EdgeInsets.fromLTRB(
              AppSpacing.lg,
              AppSpacing.md,
              AppSpacing.lg,
              AppSpacing.xxl,
            ),
            itemCount: items.length,
            separatorBuilder: (_, __) => const SizedBox(height: AppSpacing.sm),
            itemBuilder: (context, i) {
              final item = RecentTransactionItem(transaction: items[i]);
              final reduceMotion =
                  MediaQuery.maybeOf(context)?.disableAnimations ?? false;
              return reduceMotion
                  ? item
                  : item
                      .animate(delay: (i * 40).ms)
                      .fadeIn(duration: 300.ms)
                      .slideX(begin: 0.05);
            },
          );
        },
      ),
    );
  }
}
