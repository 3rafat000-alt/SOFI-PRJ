import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:iconsax/iconsax.dart';
import 'package:flutter_animate/flutter_animate.dart';

import '../../../../core/theme/app_colors.dart';
import '../../../../core/utils/money_formatter.dart';
import '../../../../core/widgets/app_skeleton.dart';
import '../../../../core/widgets/app_ui.dart';
import '../../data/models/savings_models.dart';
import '../../data/repositories/savings_repository.dart';

/// Savings home — total saved, goals with progress, create/deposit/withdraw.
class SavingsPage extends ConsumerWidget {
  const SavingsPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final colors = context.appColors;
    final summaryAsync = ref.watch(savingsSummaryProvider);
    final goalsAsync = ref.watch(savingsGoalsProvider);

    return AppScaffold(
      title: 'الادخار',
      onRefresh: () async {
        ref.invalidate(savingsSummaryProvider);
        ref.invalidate(savingsGoalsProvider);
      },
      action: GestureDetector(
        onTap: () => _openCreateSheet(context, ref),
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 9),
          decoration: BoxDecoration(
            color: colors.primary,
            borderRadius: BorderRadius.circular(AppRadius.pill),
          ),
          child: const Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(Iconsax.add, color: Colors.white, size: 18),
              SizedBox(width: 4),
              Text('هدف جديد',
                  style: TextStyle(color: Colors.white, fontSize: 13, fontWeight: FontWeight.w700)),
            ],
          ),
        ),
      ),
      body: ListView(
        padding: const EdgeInsets.fromLTRB(
            AppSpacing.lg, AppSpacing.sm, AppSpacing.lg, AppSpacing.xxxl),
        children: [
          summaryAsync.when(
            data: (s) => _SummaryCard(summary: s).animate().fadeIn().slideY(begin: 0.1),
            loading: () => const _SummarySkeleton(),
            error: (_, __) => const SizedBox.shrink(),
          ),
          const SizedBox(height: AppSpacing.xl),
          const SectionHeader(title: 'أهدافي'),
          goalsAsync.when(
            data: (goals) => goals.isEmpty
                ? _EmptyGoals(onCreate: () => _openCreateSheet(context, ref))
                : Column(
                    children: goals
                        .map((g) => Padding(
                              padding: const EdgeInsets.only(bottom: AppSpacing.md),
                              child: _GoalCard(
                                goal: g,
                                onTap: () => _openManageSheet(context, ref, g),
                              ),
                            ))
                        .toList(),
                  ),
            loading: () => const SakkShimmer(
              child: Column(
                children: [
                  SkeletonCard(height: 100),
                  SkeletonCard(height: 100),
                ],
              ),
            ),
            error: (_, __) => Text('تعذّر تحميل الأهداف',
                style: TextStyle(color: colors.textSecondary)),
          ),
        ],
      ),
    );
  }

  void _openCreateSheet(BuildContext context, WidgetRef ref) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      useSafeArea: true,
      backgroundColor: Colors.transparent,
      builder: (_) => const _CreateGoalSheet(),
    );
  }

  void _openManageSheet(BuildContext context, WidgetRef ref, SavingsGoalModel goal) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      useSafeArea: true,
      backgroundColor: Colors.transparent,
      builder: (_) => _ManageGoalSheet(goal: goal),
    );
  }
}

// ════════════════════════════════════════════════════════════════════
// Summary Card
// ════════════════════════════════════════════════════════════════════
class _SummaryCard extends StatelessWidget {
  final SavingsSummary summary;
  const _SummaryCard({required this.summary});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(AppSpacing.xl),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: context.appColors.cardGradientVisa,
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(AppRadius.xl),
        boxShadow: AppShadows.card,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Icon(Iconsax.safe_home, color: Colors.white70, size: 20),
              const SizedBox(width: AppSpacing.sm),
              const Text('إجمالي المدخرات',
                  style: TextStyle(color: Colors.white70, fontSize: 13.5)),
            ],
          ),
          const SizedBox(height: AppSpacing.md),
          Text(
            Money.format(summary.totalSaved, 'USD'),
            style: const TextStyle(
                color: Colors.white, fontSize: 34, fontWeight: FontWeight.w800),
          ),
          const SizedBox(height: AppSpacing.lg),
          Row(
            children: [
              _chip(Iconsax.flag, '${summary.goalsCount} هدف'),
              const SizedBox(width: AppSpacing.sm),
              _chip(Iconsax.tick_circle, '${summary.completedCount} مكتمل'),
              const Spacer(),
              Text('الرصيد: ${Money.format(summary.usdBalance, 'USD')}',
                  style: const TextStyle(color: Colors.white70, fontSize: 12)),
            ],
          ),
        ],
      ),
    );
  }

  Widget _chip(IconData icon, String label) => Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
        decoration: BoxDecoration(
          color: Colors.white.withValues(alpha: 0.15),
          borderRadius: BorderRadius.circular(AppRadius.pill),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon, color: Colors.white, size: 13),
            const SizedBox(width: 4),
            Text(label,
                style: const TextStyle(color: Colors.white, fontSize: 11.5, fontWeight: FontWeight.w600)),
          ],
        ),
      );
}

// ════════════════════════════════════════════════════════════════════
// Goal Card
// ════════════════════════════════════════════════════════════════════
class _GoalCard extends StatelessWidget {
  final SavingsGoalModel goal;
  final VoidCallback onTap;
  const _GoalCard({required this.goal, required this.onTap});

  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    final pct = (goal.progressPercent / 100).clamp(0.0, 1.0);
    return AppCard(
      onTap: onTap,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                width: 44,
                height: 44,
                decoration: BoxDecoration(
                  color: colors.primary.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(AppRadius.md),
                ),
                child: Icon(
                    goal.isCompleted ? Iconsax.tick_circle : Iconsax.safe_home,
                    color: goal.isCompleted ? colors.success : colors.primary,
                    size: 22),
              ),
              const SizedBox(width: AppSpacing.md),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(goal.name,
                        style: TextStyle(
                            fontSize: 15.5,
                            fontWeight: FontWeight.w700,
                            color: colors.textPrimary)),
                    if (goal.isCompleted)
                      Text('مكتمل 🎉',
                          style: TextStyle(fontSize: 12, color: colors.success)),
                  ],
                ),
              ),
              Icon(Iconsax.arrow_left_2, color: colors.textHint, size: 18),
            ],
          ),
          const SizedBox(height: AppSpacing.md),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(Money.format(goal.savedAmount, 'USD'),
                  style: TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.w800,
                      color: colors.textPrimary)),
              if (goal.hasTarget)
                Text('من ${Money.format(goal.targetAmount!, 'USD')}',
                    style: TextStyle(fontSize: 12.5, color: colors.textSecondary)),
            ],
          ),
          if (goal.hasTarget) ...[
            const SizedBox(height: AppSpacing.sm),
            ClipRRect(
              borderRadius: BorderRadius.circular(AppRadius.pill),
              child: LinearProgressIndicator(
                value: pct,
                minHeight: 8,
                backgroundColor: colors.inputBackground,
                valueColor: AlwaysStoppedAnimation(
                    goal.isCompleted ? colors.success : colors.primary),
              ),
            ),
            const SizedBox(height: 4),
            Text('${goal.progressPercent.toStringAsFixed(0)}%',
                style: TextStyle(fontSize: 11.5, color: colors.textSecondary)),
          ],
        ],
      ),
    );
  }
}

// ════════════════════════════════════════════════════════════════════
// Create Goal Sheet
// ════════════════════════════════════════════════════════════════════
class _CreateGoalSheet extends ConsumerStatefulWidget {
  const _CreateGoalSheet();

  @override
  ConsumerState<_CreateGoalSheet> createState() => _CreateGoalSheetState();
}

class _CreateGoalSheetState extends ConsumerState<_CreateGoalSheet> {
  final _nameController = TextEditingController();
  final _targetController = TextEditingController();
  final _initialController = TextEditingController();
  bool _loading = false;
  String? _error;

  @override
  void dispose() {
    _nameController.dispose();
    _targetController.dispose();
    _initialController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    final name = _nameController.text.trim();
    if (name.isEmpty) {
      setState(() => _error = 'أدخل اسم الهدف');
      return;
    }
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      await ref.read(savingsRepositoryProvider).createGoal(
            name: name,
            targetAmount: double.tryParse(Money.normalizeAmountInput(_targetController.text)),
            initialAmount: double.tryParse(Money.normalizeAmountInput(_initialController.text)),
          );
      ref.invalidate(savingsGoalsProvider);
      ref.invalidate(savingsSummaryProvider);
      if (!mounted) return;
      Navigator.pop(context);
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(
        content: const Text('✅ تم إنشاء هدف الادخار'),
        backgroundColor: context.appColors.success,
        behavior: SnackBarBehavior.floating,
      ));
    } catch (e) {
      setState(() => _error = e.toString());
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    return Padding(
      padding: EdgeInsets.only(bottom: MediaQuery.of(context).viewInsets.bottom),
      child: Container(
        padding: const EdgeInsets.all(AppSpacing.xl),
        decoration: BoxDecoration(
          color: colors.surface,
          borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
        ),
        child: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              _handle(context),
              const SizedBox(height: AppSpacing.lg),
              const Text('هدف ادخار جديد',
                  style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
              const SizedBox(height: AppSpacing.xl),
              _field(context, _nameController, 'اسم الهدف', 'مثال: سيارة، سفر، طوارئ',
                  Iconsax.flag),
              const SizedBox(height: AppSpacing.md),
              _field(context, _targetController, 'المبلغ المستهدف (اختياري)', '0.00',
                  Iconsax.coin, number: true),
              const SizedBox(height: AppSpacing.md),
              _field(context, _initialController, 'إيداع أولي (اختياري)', '0.00',
                  Iconsax.wallet_add, number: true),
              if (_error != null) ...[
                const SizedBox(height: AppSpacing.md),
                Text(_error!, style: TextStyle(color: colors.error, fontSize: 12.5)),
              ],
              const SizedBox(height: AppSpacing.xl),
              AppButton(label: 'إنشاء الهدف', loading: _loading, onPressed: _submit),
              const SizedBox(height: AppSpacing.sm),
            ],
          ),
        ),
      ),
    );
  }

  Widget _field(BuildContext context, TextEditingController c, String label,
      String hint, IconData icon,
      {bool number = false}) {
    final colors = context.appColors;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label, style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: colors.textSecondary)),
        const SizedBox(height: AppSpacing.sm),
        TextField(
          controller: c,
          keyboardType: number ? const TextInputType.numberWithOptions(decimal: true) : TextInputType.text,
          inputFormatters: number
              ? [FilteringTextInputFormatter.allow(RegExp(r'^\d*\.?\d{0,2}'))]
              : null,
          decoration: InputDecoration(
            hintText: hint,
            prefixIcon: Icon(icon, size: 20),
            filled: true,
            fillColor: colors.inputBackground,
            border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(AppRadius.md),
                borderSide: BorderSide.none),
          ),
        ),
      ],
    );
  }
}

// ════════════════════════════════════════════════════════════════════
// Manage (deposit / withdraw / close) Sheet
// ════════════════════════════════════════════════════════════════════
class _ManageGoalSheet extends ConsumerStatefulWidget {
  final SavingsGoalModel goal;
  const _ManageGoalSheet({required this.goal});

  @override
  ConsumerState<_ManageGoalSheet> createState() => _ManageGoalSheetState();
}

class _ManageGoalSheetState extends ConsumerState<_ManageGoalSheet> {
  final _amountController = TextEditingController();
  bool _isDeposit = true;
  bool _loading = false;
  String? _error;

  @override
  void dispose() {
    _amountController.dispose();
    super.dispose();
  }

  Future<String?> _askPin() async {
    final controller = TextEditingController();
    return showDialog<String>(
      context: context,
      builder: (c) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: const Text('تأكيد برمز PIN'),
        content: TextField(
          controller: controller,
          keyboardType: TextInputType.number,
          obscureText: true,
          maxLength: 6,
          textAlign: TextAlign.center,
          style: const TextStyle(fontSize: 24, letterSpacing: 8),
          decoration: const InputDecoration(counterText: '', hintText: '••••••'),
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(c), child: const Text('إلغاء')),
          ElevatedButton(
              onPressed: () => Navigator.pop(c, controller.text.trim()),
              child: const Text('تأكيد')),
        ],
      ),
    );
  }

  Future<void> _submit() async {
    final amount = double.tryParse(Money.normalizeAmountInput(_amountController.text)) ?? 0;
    if (amount <= 0) {
      setState(() => _error = 'أدخل مبلغاً صحيحاً');
      return;
    }
    if (!_isDeposit && amount > widget.goal.savedAmount) {
      setState(() => _error = 'المبلغ أكبر من رصيد الهدف');
      return;
    }
    final pin = await _askPin();
    if (pin == null) return;

    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final repo = ref.read(savingsRepositoryProvider);
      if (_isDeposit) {
        await repo.deposit(widget.goal.id, amount, pin);
      } else {
        await repo.withdraw(widget.goal.id, amount, pin);
      }
      ref.invalidate(savingsGoalsProvider);
      ref.invalidate(savingsSummaryProvider);
      if (!mounted) return;
      Navigator.pop(context);
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(
        content: Text(_isDeposit ? '✅ تم الإيداع' : '✅ تم السحب'),
        backgroundColor: context.appColors.success,
        behavior: SnackBarBehavior.floating,
      ));
    } catch (e) {
      setState(() => _error = e.toString());
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _close() async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (c) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: const Text('إغلاق الهدف'),
        content: const Text('سيتم إرجاع الرصيد المتبقي إلى محفظتك. متابعة؟'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(c, false), child: const Text('إلغاء')),
          ElevatedButton(
              onPressed: () => Navigator.pop(c, true),
              style: ElevatedButton.styleFrom(backgroundColor: context.appColors.error),
              child: const Text('إغلاق')),
        ],
      ),
    );
    if (ok != true) return;
    setState(() => _loading = true);
    try {
      await ref.read(savingsRepositoryProvider).close(widget.goal.id);
      ref.invalidate(savingsGoalsProvider);
      ref.invalidate(savingsSummaryProvider);
      if (!mounted) return;
      Navigator.pop(context);
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(
        content: Text('تم إغلاق الهدف'),
        behavior: SnackBarBehavior.floating,
      ));
    } catch (e) {
      setState(() {
        _error = e.toString();
        _loading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    final goal = widget.goal;
    return Padding(
      padding: EdgeInsets.only(bottom: MediaQuery.of(context).viewInsets.bottom),
      child: Container(
        padding: const EdgeInsets.all(AppSpacing.xl),
        decoration: BoxDecoration(
          color: colors.surface,
          borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
        ),
        child: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              _handle(context),
              const SizedBox(height: AppSpacing.lg),
              Text(goal.name,
                  style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
              const SizedBox(height: 2),
              Text('الرصيد: ${Money.format(goal.savedAmount, 'USD')}',
                  style: TextStyle(fontSize: 13, color: colors.textSecondary)),
              const SizedBox(height: AppSpacing.lg),

              // Toggle deposit / withdraw
              Container(
                padding: const EdgeInsets.all(4),
                decoration: BoxDecoration(
                  color: colors.inputBackground,
                  borderRadius: BorderRadius.circular(AppRadius.md),
                ),
                child: Row(
                  children: [
                    _toggle('إيداع', _isDeposit, () => setState(() => _isDeposit = true)),
                    _toggle('سحب', !_isDeposit, () => setState(() => _isDeposit = false)),
                  ],
                ),
              ),
              const SizedBox(height: AppSpacing.lg),

              TextField(
                controller: _amountController,
                keyboardType: const TextInputType.numberWithOptions(decimal: true),
                inputFormatters: [
                  FilteringTextInputFormatter.allow(RegExp(r'^\d*\.?\d{0,2}')),
                ],
                onChanged: (_) => setState(() => _error = null),
                style: const TextStyle(fontSize: 22, fontWeight: FontWeight.bold),
                decoration: InputDecoration(
                  hintText: '0.00',
                  prefixText: '\$ ',
                  filled: true,
                  fillColor: colors.inputBackground,
                  border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(AppRadius.md),
                      borderSide: BorderSide.none),
                ),
              ),
              if (_error != null) ...[
                const SizedBox(height: AppSpacing.md),
                Text(_error!, style: TextStyle(color: colors.error, fontSize: 12.5)),
              ],
              const SizedBox(height: AppSpacing.xl),
              AppButton(
                label: _isDeposit ? 'إيداع' : 'سحب',
                loading: _loading,
                onPressed: _submit,
              ),
              const SizedBox(height: AppSpacing.sm),
              TextButton(
                onPressed: _loading ? null : _close,
                child: Text('إغلاق الهدف وإرجاع الرصيد',
                    style: TextStyle(color: colors.error, fontSize: 13)),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _toggle(String label, bool active, VoidCallback onTap) {
    final colors = context.appColors;
    return Expanded(
      child: GestureDetector(
        onTap: onTap,
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 10),
          decoration: BoxDecoration(
            color: active ? colors.surface : Colors.transparent,
            borderRadius: BorderRadius.circular(AppRadius.sm),
            boxShadow: active ? AppShadows.soft : null,
          ),
          child: Text(label,
              textAlign: TextAlign.center,
              style: TextStyle(
                  fontWeight: FontWeight.w700,
                  color: active ? colors.textPrimary : colors.textSecondary)),
        ),
      ),
    );
  }
}

// ════════════════════════════════════════════════════════════════════
// Helpers
// ════════════════════════════════════════════════════════════════════
Widget _handle(BuildContext context) => Center(
      child: Container(
        width: 40,
        height: 4,
        decoration: BoxDecoration(
          color: context.appColors.textHint.withValues(alpha: 0.4),
          borderRadius: BorderRadius.circular(2),
        ),
      ),
    );

class _SummarySkeleton extends StatelessWidget {
  const _SummarySkeleton();
  @override
  Widget build(BuildContext context) =>
      const SakkShimmer(child: SkeletonCard(height: 150, margin: EdgeInsets.zero));
}

class _EmptyGoals extends StatelessWidget {
  final VoidCallback onCreate;
  const _EmptyGoals({required this.onCreate});
  @override
  Widget build(BuildContext context) {
    return EmptyState(
      icon: Iconsax.safe_home,
      title: 'لا توجد أهداف ادخار',
      subtitle: 'ابدأ بإنشاء هدف وادّخر تدريجياً للوصول إليه',
      actionLabel: 'إنشاء هدف',
      onAction: onCreate,
    );
  }
}
