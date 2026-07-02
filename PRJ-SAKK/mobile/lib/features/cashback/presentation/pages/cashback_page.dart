import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:iconsax/iconsax.dart';

import '../../../../core/theme/app_colors.dart';
import '../../../../core/utils/money_formatter.dart';
import '../../../../core/widgets/app_skeleton.dart';
import '../../../../core/widgets/app_ui.dart';
import '../../../transactions/data/models/transaction_model.dart';
import '../../data/cashback_repository.dart';

/// Cashback operations — a black hero with the total earned + the list of
/// reward (cashback) operations.
class CashbackPage extends ConsumerWidget {
  const CashbackPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final async = ref.watch(cashbackProvider);
    final colors = context.appColors;

    return AppScaffold(
      title: 'الكاش باك',
      subtitle: 'مكافآتك المكتسبة',
      onRefresh: () async => ref.invalidate(cashbackProvider),
      body: async.when(
        loading: () => const SkeletonListScene(
          items: 4,
          header: SkeletonCard(height: 100),
        ),
        error: (_, __) => _fill(EmptyState(
          icon: Iconsax.warning_2,
          title: 'تعذّر تحميل الكاش باك',
          subtitle: 'تحقّق من اتصالك وحاول مجدداً',
          actionLabel: 'إعادة المحاولة',
          onAction: () => ref.invalidate(cashbackProvider),
        )),
        data: (c) => ListView(
          padding: const EdgeInsets.fromLTRB(
              AppSpacing.lg, AppSpacing.md, AppSpacing.lg, AppSpacing.xxl),
          children: [
            _hero(context, c.total, c.currency, c.count)
                .animate()
                .fadeIn(duration: 350.ms)
                .slideY(begin: 0.06),
            const SizedBox(height: AppSpacing.xl),
            if (c.transactions.isEmpty)
              Padding(
                padding: const EdgeInsets.only(top: AppSpacing.xxl),
                child: const EmptyState(
                  icon: Iconsax.gift,
                  title: 'لا يوجد كاش باك بعد',
                  subtitle:
                      'اكسب كاش باك مع كل تحويل ودفعة. ستظهر مكافآتك هنا.',
                ),
              )
            else ...[
              Align(
                alignment: AlignmentDirectional.centerStart,
                child: Text('العمليات',
                    style: TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.w700,
                        color: colors.textPrimary)),
              ),
              const SizedBox(height: AppSpacing.md),
              ...c.transactions.asMap().entries.map((e) => _opTile(context, e.value)
                  .animate(delay: (e.key * 40).ms)
                  .fadeIn(duration: 280.ms)
                  .slideX(begin: 0.05)),
            ],
          ],
        ),
      ),
    );
  }

  Widget _fill(Widget child) => LayoutBuilder(
        builder: (ctx, cns) => SingleChildScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          child: ConstrainedBox(
            constraints: BoxConstraints(minHeight: cns.maxHeight),
            child: Center(child: child),
          ),
        ),
      );

  Widget _hero(BuildContext context, double total, String currency, int count) {
    final colors = context.appColors;
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(AppSpacing.xl),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: colors.cardGradientVisa,
          begin: Alignment.topRight,
          end: Alignment.bottomLeft,
        ),
        borderRadius: BorderRadius.circular(AppRadius.xl),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.25),
            blurRadius: 24,
            offset: const Offset(0, 12),
          ),
        ],
      ),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(AppRadius.xl),
        child: Stack(
          children: [
            Positioned(
              left: -16,
              bottom: -20,
              child: Icon(Iconsax.gift,
                  size: 120, color: Colors.white.withValues(alpha: 0.08)),
            ),
            Column(
              crossAxisAlignment: CrossAxisAlignment.center,
              children: [
                Container(
                  width: 52,
                  height: 52,
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: 0.16),
                    shape: BoxShape.circle,
                  ),
                  child: const Icon(Iconsax.gift, color: Colors.white, size: 26),
                ),
                const SizedBox(height: AppSpacing.md),
                const Text('إجمالي الكاش باك المكتسب',
                    style: TextStyle(color: Colors.white70, fontSize: 13)),
                const SizedBox(height: 6),
                Directionality(
                  textDirection: TextDirection.ltr,
                  child: Text(Money.format(total, currency),
                      style: const TextStyle(
                          color: Colors.white,
                          fontSize: 36,
                          fontWeight: FontWeight.w800)),
                ),
                const SizedBox(height: AppSpacing.sm),
                Container(
                  padding: const EdgeInsets.symmetric(
                      horizontal: AppSpacing.md, vertical: 5),
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: 0.16),
                    borderRadius: BorderRadius.circular(AppRadius.pill),
                  ),
                  child: Text('$count عملية كاش باك',
                      style: const TextStyle(
                          color: Colors.white,
                          fontSize: 12,
                          fontWeight: FontWeight.w600)),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _opTile(BuildContext context, TransactionModel tx) {
    final colors = context.appColors;
    return AppCard(
      margin: const EdgeInsets.only(bottom: AppSpacing.sm),
      child: Row(
        children: [
          Container(
            width: 44,
            height: 44,
            decoration: BoxDecoration(
              color: colors.success.withValues(alpha: 0.12),
              borderRadius: BorderRadius.circular(13),
            ),
            child: Icon(Iconsax.gift, color: colors.success, size: 21),
          ),
          const SizedBox(width: AppSpacing.md),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(tx.title?.isNotEmpty == true ? tx.title! : 'كاش باك',
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(
                        fontSize: 14.5,
                        fontWeight: FontWeight.w700,
                        color: colors.textPrimary)),
                const SizedBox(height: 3),
                Text(tx.formattedDate,
                    style: TextStyle(
                        fontSize: 12, color: colors.textSecondary)),
              ],
            ),
          ),
          const SizedBox(width: AppSpacing.sm),
          Directionality(
            textDirection: TextDirection.ltr,
            child: Text('+${tx.formattedAmount}',
                style: TextStyle(
                    fontSize: 14.5,
                    fontWeight: FontWeight.w800,
                    color: colors.success)),
          ),
        ],
      ),
    );
  }
}
