import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:iconsax/iconsax.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:intl/intl.dart' hide TextDirection;

import '../../../../core/theme/app_colors.dart';
import '../../../../core/widgets/app_skeleton.dart';
import '../../../../core/widgets/app_ui.dart';
import '../../data/models/transaction_model.dart';
import '../../data/repositories/transaction_repository.dart';
import '../widgets/transaction_detail_sheet.dart';

/// Transactions — a fresh "activity" view: a gradient summary header, icon
/// filter chips, and a clean day-grouped timeline (one card per day).
class TransactionsPage extends ConsumerStatefulWidget {
  const TransactionsPage({super.key});

  @override
  ConsumerState<TransactionsPage> createState() => _TransactionsPageState();
}

class _TransactionsPageState extends ConsumerState<TransactionsPage> {
  String _filter = 'all';

  static const _filters = [
    {'id': 'all', 'label': 'الكل', 'icon': Iconsax.category_2},
    {'id': 'deposit', 'label': 'إيداع', 'icon': Iconsax.arrow_down},
    {'id': 'withdrawal', 'label': 'سحب', 'icon': Iconsax.arrow_up_1},
    {'id': 'transfer', 'label': 'تحويلات', 'icon': Iconsax.arrow_swap_horizontal},
    {'id': 'exchange', 'label': 'صرف', 'icon': Iconsax.dollar_circle},
    {'id': 'card', 'label': 'بطاقة', 'icon': Iconsax.card},
  ];

  bool _matches(String type) {
    switch (_filter) {
      case 'all':
        return true;
      case 'transfer':
        return type == 'transfer_in' || type == 'transfer_out';
      case 'card':
        return type.startsWith('card_');
      default:
        return type == _filter;
    }
  }

  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    final async = ref.watch(transactionsProvider);

    return AppScaffold(
      title: 'المعاملات',
      subtitle: 'كل حركاتك المالية في مكان واحد',
      body: Column(
        children: [
          _filterBar(),
          Expanded(
            child: RefreshIndicator(
              color: colors.primary,
              onRefresh: () async => ref.invalidate(transactionsProvider),
              child: async.when(
                data: _buildContent,
                loading: () => const SkeletonListScene(items: 6),
                error: (_, __) => _fill(EmptyState(
                  icon: Iconsax.warning_2,
                  title: 'تعذّر تحميل المعاملات',
                  subtitle: 'تحقّق من اتصالك وحاول مجدداً',
                  actionLabel: 'إعادة المحاولة',
                  onAction: () => ref.invalidate(transactionsProvider),
                )),
              ),
            ),
          ),
        ],
      ),
    );
  }

  // ───────────────────────── Filter chips ─────────────────────────
  Widget _filterBar() {
    return SizedBox(
      height: 56,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.symmetric(
            horizontal: AppSpacing.xl, vertical: AppSpacing.sm),
        itemCount: _filters.length,
        separatorBuilder: (_, __) => const SizedBox(width: AppSpacing.sm),
        itemBuilder: (context, i) {
          final colors = context.appColors;
          final isDark = Theme.of(context).brightness == Brightness.dark;
          final f = _filters[i];
          final selected = _filter == f['id'];
          final onPrimary = isDark ? colors.background : Colors.white;
          return GestureDetector(
            onTap: () => setState(() => _filter = f['id'] as String),
            child: AnimatedContainer(
              duration: const Duration(milliseconds: 200),
              curve: Curves.easeOut,
              padding: const EdgeInsets.symmetric(horizontal: 16),
              decoration: BoxDecoration(
                color: selected ? colors.primary : colors.surface,
                borderRadius: BorderRadius.circular(AppRadius.pill),
                border: Border.all(
                    color:
                        selected ? colors.primary : colors.inputBackground),
                boxShadow: selected ? AppShadows.soft : null,
              ),
              child: Row(
                children: [
                  Icon(f['icon'] as IconData,
                      size: 16,
                      color: selected ? onPrimary : colors.textSecondary),
                  const SizedBox(width: 6),
                  Text(
                    f['label'] as String,
                    style: TextStyle(
                      fontSize: 13,
                      fontWeight:
                          selected ? FontWeight.w700 : FontWeight.w600,
                      color:
                          selected ? onPrimary : colors.textSecondary,
                    ),
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }

  // ───────────────────────── Content ─────────────────────────
  Widget _buildContent(List<TransactionModel> all) {
    if (all.isEmpty) {
      return _fill(const EmptyState(
        icon: Iconsax.receipt_2,
        title: 'لا توجد معاملات',
        subtitle: 'ستظهر هنا جميع عملياتك المالية',
      ));
    }

    final filtered =
        _filter == 'all' ? all : all.where((t) => _matches(t.type)).toList();
    final grouped = _groupByDate(filtered);

    return ListView(
      padding: const EdgeInsets.fromLTRB(
          AppSpacing.xl, AppSpacing.xs, AppSpacing.xl, AppSpacing.xxxl),
      children: [
        _summaryCard(filtered)
            .animate()
            .fadeIn(duration: 350.ms)
            .slideY(begin: 0.08),
        const SizedBox(height: AppSpacing.xl),
        if (filtered.isEmpty)
          Padding(
            padding: const EdgeInsets.only(top: AppSpacing.xxxl),
            child: EmptyState(
              icon: Iconsax.filter_remove,
              title: 'لا نتائج لهذا التصنيف',
              subtitle: 'جرّب تصنيفاً آخر أو اعرض "الكل".',
            ),
          )
        else
          ...grouped.entries.toList().asMap().entries.map((e) {
            final group = e.value;
            return Padding(
              padding: EdgeInsets.only(
                  top: e.key == 0 ? 0 : AppSpacing.xl),
              child: _dayGroup(group.key, group.value),
            )
                .animate(delay: (e.key * 60).ms)
                .fadeIn(duration: 300.ms)
                .slideX(begin: 0.04);
          }),
      ],
    );
  }

  // ───────────────────────── Summary (gradient hero) ─────────────────────────
  Widget _summaryCard(List<TransactionModel> txs) {
    final colors = context.appColors;
    final incoming = txs.where((t) => t.isIncoming).length;
    final outgoing = txs.length - incoming;

    return Container(
      padding: const EdgeInsets.all(AppSpacing.xl),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: colors.cardGradientVisa,
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(AppRadius.xl),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.3),
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
              right: -16,
              bottom: -22,
              child: Icon(Iconsax.chart_21,
                  size: 110, color: Colors.white.withValues(alpha: 0.10)),
            ),
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text('إجمالي العمليات',
                    style: TextStyle(color: Colors.white70, fontSize: 13)),
                const SizedBox(height: 4),
                Row(
                  crossAxisAlignment: CrossAxisAlignment.baseline,
                  textBaseline: TextBaseline.alphabetic,
                  children: [
                    Text('${txs.length}',
                        style: const TextStyle(
                            color: Colors.white,
                            fontSize: 36,
                            fontWeight: FontWeight.w800,
                            height: 1.1)),
                    const SizedBox(width: 6),
                    const Text('عملية',
                        style: TextStyle(color: Colors.white70, fontSize: 14)),
                  ],
                ),
                const SizedBox(height: AppSpacing.lg),
                Row(
                  children: [
                    Expanded(
                        child: _miniStat(
                            Iconsax.arrow_down_1, 'وارد', incoming)),
                    const SizedBox(width: AppSpacing.sm),
                    Expanded(
                        child:
                            _miniStat(Iconsax.arrow_up_3, 'صادر', outgoing)),
                  ],
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _miniStat(IconData icon, String label, int count) {
    return Container(
      padding:
          const EdgeInsets.symmetric(horizontal: AppSpacing.md, vertical: 10),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.16),
        borderRadius: BorderRadius.circular(AppRadius.md),
      ),
      child: Row(
        children: [
          Container(
            width: 30,
            height: 30,
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.22),
              shape: BoxShape.circle,
            ),
            child: Icon(icon, size: 16, color: Colors.white),
          ),
          const SizedBox(width: AppSpacing.sm),
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(label,
                  style: const TextStyle(color: Colors.white70, fontSize: 11)),
              Text('$count',
                  style: const TextStyle(
                      color: Colors.white,
                      fontSize: 15,
                      fontWeight: FontWeight.w800)),
            ],
          ),
        ],
      ),
    );
  }

  // ───────────────────────── Day group (one card) ─────────────────────────
  Widget _dayGroup(String dateLabel, List<TransactionModel> txs) {
    final colors = context.appColors;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.only(
              right: 4, bottom: AppSpacing.sm, left: 4),
          child: Row(
            children: [
              Icon(Iconsax.calendar_1,
                  size: 14, color: colors.textHint),
              const SizedBox(width: 6),
              Text(dateLabel,
                  style: TextStyle(
                      fontSize: 13.5,
                      fontWeight: FontWeight.w700,
                      color: colors.textPrimary)),
              const Spacer(),
              Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                decoration: BoxDecoration(
                  color: colors.inputBackground,
                  borderRadius: BorderRadius.circular(AppRadius.pill),
                ),
                child: Text('${txs.length}',
                    style: TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.w700,
                        color: colors.textSecondary)),
              ),
            ],
          ),
        ),
        AppCard(
          padding: EdgeInsets.zero,
          child: Column(
            children: [
              for (int i = 0; i < txs.length; i++) ...[
                _txRow(txs[i]),
                if (i != txs.length - 1)
                  const Divider(
                      height: 1, indent: 64, endIndent: AppSpacing.lg),
              ],
            ],
          ),
        ),
      ],
    );
  }

  Widget _txRow(TransactionModel tx) {
    final colors = context.appColors;
    final (icon, color) = _meta(tx);
    final incoming = tx.isIncoming;
    final title = tx.displayLabel;
    final completed = tx.status == 'completed';

    return InkWell(
      onTap: () => TransactionDetailSheet.show(context, tx),
      borderRadius: BorderRadius.circular(AppRadius.lg),
      child: Padding(
        padding: const EdgeInsets.all(AppSpacing.md),
        child: Row(
          children: [
            // Icon + direction badge.
            SizedBox(
              width: 44,
              height: 44,
              child: Stack(
                clipBehavior: Clip.none,
                children: [
                  Container(
                    width: 44,
                    height: 44,
                    decoration: BoxDecoration(
                      color: color.withValues(alpha: 0.12),
                      borderRadius: BorderRadius.circular(13),
                    ),
                    child: Icon(icon, color: color, size: 21),
                  ),
                  Positioned(
                    bottom: -2,
                    left: -2,
                    child: Container(
                      width: 18,
                      height: 18,
                      decoration: BoxDecoration(
                        color: incoming ? colors.success : colors.error,
                        shape: BoxShape.circle,
                        border: Border.all(color: colors.surface, width: 2),
                      ),
                      child: Icon(
                          incoming
                              ? Iconsax.arrow_down_1
                              : Iconsax.arrow_up_3,
                          size: 9,
                          color: Colors.white),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(width: AppSpacing.md),
            // Title + meta.
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(title,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: TextStyle(
                          fontSize: 14.5,
                          fontWeight: FontWeight.w700,
                          color: colors.textPrimary)),
                  const SizedBox(height: 3),
                  Row(
                    children: [
                      Text(_time(tx.createdAt),
                          style: TextStyle(
                              fontSize: 12, color: colors.textSecondary)),
                      if (!completed) ...[
                        const SizedBox(width: 6),
                        Container(
                            width: 3,
                            height: 3,
                            decoration: BoxDecoration(
                                color: colors.textHint,
                                shape: BoxShape.circle)),
                        const SizedBox(width: 6),
                        Text(tx.statusLabel,
                            style: TextStyle(
                                fontSize: 11.5,
                                fontWeight: FontWeight.w600,
                                color: tx.statusColor)),
                      ],
                    ],
                  ),
                ],
              ),
            ),
            const SizedBox(width: AppSpacing.sm),
            // Amount.
            Directionality(
              textDirection: TextDirection.ltr,
              child: Text(
                '${incoming ? '+' : '−'}${tx.formattedAmount}',
                style: TextStyle(
                  fontSize: 14.5,
                  fontWeight: FontWeight.w800,
                  color: incoming ? colors.success : colors.textPrimary,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  // ───────────────────────── helpers ─────────────────────────
  Widget _fill(Widget child) => LayoutBuilder(
        builder: (ctx, c) => SingleChildScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          child: ConstrainedBox(
            constraints: BoxConstraints(minHeight: c.maxHeight),
            child: Center(child: child),
          ),
        ),
      );

  String _time(DateTime dt) => DateFormat('HH:mm').format(dt);

  (IconData, Color) _meta(TransactionModel tx) {
    final colors = context.appColors;
    switch (tx.type) {
      case 'deposit':
        return (Iconsax.money_recive, colors.success);
      case 'withdrawal':
        return (Iconsax.money_send, colors.error);
      case 'transfer_in':
        return (Iconsax.arrow_down_2, colors.success);
      case 'transfer_out':
        return (Iconsax.arrow_up_3, colors.error);
      case 'salary_in':
        return (Iconsax.briefcase, colors.success);
      case 'payroll_out':
        return (Iconsax.briefcase, colors.error);
      case 'exchange':
        return (Iconsax.arrow_swap_horizontal, colors.primary);
      case 'card_payment':
        return (Iconsax.card, colors.secondary);
      case 'card_load':
        return (Iconsax.card_add, colors.secondary);
      case 'card_unload':
        return (Iconsax.card_remove, colors.secondary);
      case 'card_refund':
        return (Iconsax.refresh_left_square, colors.success);
      case 'reward':
        return (Iconsax.gift, colors.warning);
      case 'fee':
        return (Iconsax.receipt_minus, colors.textSecondary);
      default:
        return (Iconsax.wallet_money, colors.textSecondary);
    }
  }

  Map<String, List<TransactionModel>> _groupByDate(
      List<TransactionModel> transactions) {
    final Map<String, List<TransactionModel>> grouped = {};
    final now = DateTime.now();

    for (final tx in transactions) {
      final d = tx.createdAt;
      String key;
      if (d.year == now.year && d.month == now.month && d.day == now.day) {
        key = 'اليوم';
      } else if (d.year == now.year &&
          d.month == now.month &&
          d.day == now.day - 1) {
        key = 'أمس';
      } else if (now.difference(d).inDays < 7) {
        key = 'هذا الأسبوع';
      } else if (d.year == now.year && d.month == now.month) {
        key = 'هذا الشهر';
      } else {
        key = DateFormat('yyyy/MM').format(d);
      }
      grouped.putIfAbsent(key, () => []).add(tx);
    }
    return grouped;
  }
}
