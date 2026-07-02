import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:iconsax/iconsax.dart';
import 'package:flutter_animate/flutter_animate.dart';

import '../../../../core/theme/app_colors.dart';
import '../../../../core/services/biometric_service.dart';
import '../../../../core/utils/money_formatter.dart';
import '../../../../core/widgets/app_skeleton.dart';
import '../../../../core/widgets/app_ui.dart';
import '../../../../core/widgets/damascene_pattern.dart';
import '../../data/models/gold_models.dart';
import '../../data/repositories/gold_repository.dart';

/// Gold savings home — balance, live karat prices, buy/sell, history.
class GoldPage extends ConsumerWidget {
  const GoldPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final colors = context.appColors;
    final walletAsync = ref.watch(goldWalletProvider);
    final txAsync = ref.watch(goldTransactionsProvider);

    return AppScaffold(
      title: 'ادخار الذهب',
      onRefresh: () async {
        ref.invalidate(goldWalletProvider);
        ref.invalidate(goldPricesProvider);
        ref.invalidate(goldTransactionsProvider);
      },
      body: ListView(
        padding: const EdgeInsets.fromLTRB(
            AppSpacing.lg, AppSpacing.sm, AppSpacing.lg, AppSpacing.xxxl),
        children: [
          walletAsync.when(
            data: (wallet) => _GoldBalanceCard(wallet: wallet)
                .animate()
                .fadeIn()
                .slideY(begin: 0.1),
            loading: () => const _BalanceSkeleton(),
            error: (_, __) => _ErrorCard(
              onRetry: () => ref.invalidate(goldWalletProvider),
            ),
          ),
          const SizedBox(height: AppSpacing.lg),

          // Buy / Sell actions
          walletAsync.maybeWhen(
            data: (wallet) => Row(
              children: [
                Expanded(
                  child: _BigActionButton(
                    icon: Iconsax.add_circle,
                    label: 'شراء ذهب',
                    filled: true,
                    onTap: () => _openBuySheet(context, ref, wallet),
                  ),
                ),
                const SizedBox(width: AppSpacing.md),
                Expanded(
                  child: _BigActionButton(
                    icon: Iconsax.minus_cirlce,
                    label: 'بيع ذهب',
                    onTap: wallet.hasGold
                        ? () => _openSellSheet(context, ref, wallet)
                        : null,
                  ),
                ),
              ],
            ).animate(delay: 100.ms).fadeIn().slideY(begin: 0.1),
            orElse: () => const SizedBox.shrink(),
          ),
          const SizedBox(height: AppSpacing.xl),

          // Live prices
          const SectionHeader(title: 'أسعار الذهب اليوم'),
          ref.watch(goldPricesProvider).when(
                data: (prices) => Column(
                  children: prices
                      .map((p) => Padding(
                            padding: const EdgeInsets.only(bottom: AppSpacing.sm),
                            child: _PriceRow(price: p),
                          ))
                      .toList(),
                ),
                loading: () => const Padding(
                  padding: EdgeInsets.only(bottom: AppSpacing.sm),
                  child: SakkShimmer(
                    child: SkeletonCard(height: 80, margin: EdgeInsets.zero),
                  ),
                ),
                error: (_, __) => Text('تعذّر تحميل الأسعار',
                    style: TextStyle(color: colors.textSecondary)),
              ),
          const SizedBox(height: AppSpacing.xl),

          // History
          const SectionHeader(title: 'سجل العمليات'),
          txAsync.when(
            data: (txs) => txs.isEmpty
                ? _EmptyHistory()
                : Column(
                    children: txs
                        .take(10)
                        .map((t) => _TransactionRow(tx: t))
                        .toList(),
                  ),
            loading: () => const SakkShimmer(
              child: Column(
                children: [
                  SkeletonListItem(),
                  SkeletonListItem(),
                  SkeletonListItem(),
                ],
              ),
            ),
            error: (_, __) => Text('تعذّر تحميل السجل',
                style: TextStyle(color: colors.textSecondary)),
          ),
        ],
      ),
    );
  }

  void _openBuySheet(BuildContext context, WidgetRef ref, GoldWalletModel wallet) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      useSafeArea: true,
      backgroundColor: Colors.transparent,
      builder: (_) => _TradeSheet(wallet: wallet, isBuy: true),
    );
  }

  void _openSellSheet(BuildContext context, WidgetRef ref, GoldWalletModel wallet) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      useSafeArea: true,
      backgroundColor: Colors.transparent,
      builder: (_) => _TradeSheet(wallet: wallet, isBuy: false),
    );
  }
}

// ════════════════════════════════════════════════════════════════════
// Balance Card (gold gradient)
// ════════════════════════════════════════════════════════════════════
class _GoldBalanceCard extends StatelessWidget {
  final GoldWalletModel wallet;
  const _GoldBalanceCard({required this.wallet});

  @override
  Widget build(BuildContext context) {
    const gold = Color(0xFFD9B978);
    final plColor = wallet.isProfit ? const Color(0xFF6FCF97) : const Color(0xFFE8A0A0);
    return Container(
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: AppColors.cardGradientVisa, // مخمل عنابي
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(AppRadius.xl),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF4A1320).withValues(alpha: 0.35),
            blurRadius: 20,
            offset: const Offset(0, 10),
          ),
        ],
      ),
      child: Stack(
        children: [
          const DamasceneWatermark(
            color: gold,
            opacity: 0.16,
            radius: AppRadius.xl,
            alignment: Alignment(1.18, -0.05),
            medallionRadius: 150,
          ),
          Padding(
            padding: const EdgeInsets.all(AppSpacing.xl),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    const Icon(Iconsax.coin, color: gold, size: 22),
                    const SizedBox(width: AppSpacing.sm),
                    const Text('رصيد الذهب',
                        style: TextStyle(color: Colors.white70, fontSize: 14)),
                    const Spacer(),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                      decoration: BoxDecoration(
                        color: gold.withValues(alpha: 0.16),
                        borderRadius: BorderRadius.circular(AppRadius.pill),
                        border: Border.all(color: gold.withValues(alpha: 0.5)),
                      ),
                      child: const Text('عيار 24',
                          style: TextStyle(color: gold, fontSize: 11, fontWeight: FontWeight.w700)),
                    ),
                  ],
                ),
                const SizedBox(height: AppSpacing.lg),
                Row(
                  crossAxisAlignment: CrossAxisAlignment.baseline,
                  textBaseline: TextBaseline.alphabetic,
                  children: [
                    Text(
                      wallet.balanceGrams.toStringAsFixed(2),
                      style: const TextStyle(
                          color: gold, fontSize: 38, fontWeight: FontWeight.w800),
                    ),
                    const SizedBox(width: 6),
                    const Padding(
                      padding: EdgeInsets.only(bottom: 6),
                      child: Text('غرام',
                          style: TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.w600)),
                    ),
                  ],
                ),
                const SizedBox(height: 4),
                Text(
                  'القيمة الحالية: ${Money.format(wallet.currentValueUsd, 'USD')}',
                  style: const TextStyle(color: Colors.white, fontSize: 15, fontWeight: FontWeight.w600),
                ),
                const SizedBox(height: AppSpacing.lg),
                Container(height: 1, color: Colors.white.withValues(alpha: 0.15)),
                const SizedBox(height: AppSpacing.md),
                Row(
                  children: [
                    Expanded(
                      child: _MiniStat(
                        label: 'إجمالي الاستثمار',
                        value: Money.format(wallet.totalInvestedUsd, 'USD'),
                      ),
                    ),
                    Expanded(
                      child: _MiniStat(
                        label: 'الربح / الخسارة',
                        value:
                            '${wallet.isProfit ? '+' : ''}${Money.format(wallet.profitLossUsd, 'USD')}',
                        valueColor: Colors.white,
                        badge: '${wallet.isProfit ? '▲' : '▼'} ${wallet.profitLossPercent.abs().toStringAsFixed(1)}%',
                        badgeColor: plColor,
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _MiniStat extends StatelessWidget {
  final String label;
  final String value;
  final Color? valueColor;
  final String? badge;
  final Color? badgeColor;
  const _MiniStat({
    required this.label,
    required this.value,
    this.valueColor,
    this.badge,
    this.badgeColor,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label, style: const TextStyle(color: Colors.white70, fontSize: 11.5)),
        const SizedBox(height: 4),
        Row(
          children: [
            Flexible(
              child: Text(value,
                  style: TextStyle(
                      color: valueColor ?? Colors.white,
                      fontSize: 14.5,
                      fontWeight: FontWeight.w700)),
            ),
            if (badge != null) ...[
              const SizedBox(width: 6),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                decoration: BoxDecoration(
                  color: (badgeColor ?? Colors.green).withValues(alpha: 0.25),
                  borderRadius: BorderRadius.circular(AppRadius.sm),
                ),
                child: Text(badge!,
                    style: TextStyle(
                        color: Colors.white,
                        fontSize: 10,
                        fontWeight: FontWeight.w700)),
              ),
            ],
          ],
        ),
      ],
    );
  }
}

// ════════════════════════════════════════════════════════════════════
// Price Row
// ════════════════════════════════════════════════════════════════════
class _PriceRow extends StatelessWidget {
  final GoldPriceModel price;
  const _PriceRow({required this.price});

  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    return AppCard(
      padding: const EdgeInsets.symmetric(
          horizontal: AppSpacing.lg, vertical: AppSpacing.md),
      child: Row(
        children: [
          Container(
            width: 44,
            height: 44,
            decoration: BoxDecoration(
              color: const Color(0xFFB58A3C).withValues(alpha: 0.12),
              borderRadius: BorderRadius.circular(AppRadius.md),
            ),
            child: const Icon(Iconsax.coin, color: Color(0xFF8F6B2A), size: 22),
          ),
          const SizedBox(width: AppSpacing.md),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(price.karatLabel,
                    style: TextStyle(
                        fontSize: 15,
                        fontWeight: FontWeight.w700,
                        color: colors.textPrimary)),
                if (price.purity != null)
                  Text('نقاء ${price.purity}',
                      style: TextStyle(fontSize: 11.5, color: colors.textSecondary)),
              ],
            ),
          ),
          Column(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              Text(Money.format(price.buyPrice, 'USD'),
                  style: TextStyle(
                      fontSize: 15,
                      fontWeight: FontWeight.w800,
                      color: colors.textPrimary)),
              Text('بيع: ${Money.format(price.sellPrice, 'USD')}',
                  style: TextStyle(fontSize: 11, color: colors.textSecondary)),
            ],
          ),
        ],
      ),
    );
  }
}

// ════════════════════════════════════════════════════════════════════
// Transaction Row
// ════════════════════════════════════════════════════════════════════
class _TransactionRow extends StatelessWidget {
  final GoldTransactionModel tx;
  const _TransactionRow({required this.tx});

  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    final isBuy = tx.isBuy;
    final color = isBuy ? const Color(0xFF8F6B2A) : colors.primary;
    return Padding(
      padding: const EdgeInsets.only(bottom: AppSpacing.sm),
      child: AppCard(
        padding: const EdgeInsets.symmetric(
            horizontal: AppSpacing.lg, vertical: AppSpacing.md),
        child: Row(
          children: [
            Container(
              width: 42,
              height: 42,
              decoration: BoxDecoration(
                color: color.withValues(alpha: 0.12),
                borderRadius: BorderRadius.circular(AppRadius.md),
              ),
              child: Icon(isBuy ? Iconsax.arrow_down : Iconsax.arrow_up_3,
                  color: color, size: 20),
            ),
            const SizedBox(width: AppSpacing.md),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('${tx.typeLabel} ${tx.grams.toStringAsFixed(2)} غرام',
                      style: TextStyle(
                          fontSize: 14.5,
                          fontWeight: FontWeight.w600,
                          color: colors.textPrimary)),
                  Text('${tx.karatLabel} · ${tx.reference}',
                      style: TextStyle(fontSize: 11.5, color: colors.textSecondary)),
                ],
              ),
            ),
            Text(
              '${isBuy ? '-' : '+'}${Money.format(tx.totalUsd, 'USD')}',
              style: TextStyle(
                  fontSize: 14,
                  fontWeight: FontWeight.w700,
                  color: isBuy ? colors.textPrimary : colors.success),
            ),
          ],
        ),
      ),
    );
  }
}

// ════════════════════════════════════════════════════════════════════
// Trade Sheet (Buy / Sell)
// ════════════════════════════════════════════════════════════════════
class _TradeSheet extends ConsumerStatefulWidget {
  final GoldWalletModel wallet;
  final bool isBuy;
  const _TradeSheet({required this.wallet, required this.isBuy});

  @override
  ConsumerState<_TradeSheet> createState() => _TradeSheetState();
}

class _TradeSheetState extends ConsumerState<_TradeSheet> {
  final _gramsController = TextEditingController();
  final _pinController = TextEditingController();
  final _pinFocusNode = FocusNode();
  String _karat = '24';
  bool _loading = false;
  String? _error;

  GoldPriceModel? get _selectedPrice {
    for (final p in widget.wallet.prices) {
      if (p.karat == _karat) return p;
    }
    return widget.wallet.prices.isNotEmpty ? widget.wallet.prices.first : null;
  }

  double get _grams => double.tryParse(Money.normalizeAmountInput(_gramsController.text)) ?? 0;

  double get _unitPrice {
    final p = _selectedPrice;
    if (p == null) return 0;
    return widget.isBuy ? p.buyPrice : p.sellPrice;
  }

  double get _subtotal => _grams * _unitPrice;
  double get _fee => widget.isBuy ? _subtotal * 0.01 : _subtotal * 0.005;
  double get _total => widget.isBuy ? _subtotal + _fee : _subtotal - _fee;

  @override
  void dispose() {
    _gramsController.dispose();
    _pinController.dispose();
    _pinFocusNode.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    final grams = _grams;
    if (grams < 0.1) {
      setState(() => _error = 'أدخل كمية صحيحة (0.1 غرام على الأقل)');
      return;
    }
    if (widget.isBuy && _total > widget.wallet.usdBalance) {
      setState(() => _error = 'رصيد الدولار غير كافٍ لإتمام الشراء');
      return;
    }
    if (!widget.isBuy && grams > widget.wallet.balanceGrams) {
      setState(() => _error = 'لا تملك هذه الكمية من الذهب');
      return;
    }

    // Verify device biometric as local UX gate (fail-fast on wrong device).
    final bio = await BiometricService().authenticateForTransaction();
    if (!bio.success) {
      if (mounted && bio.message.isNotEmpty) {
        setState(() => _error = bio.message);
      }
      return;
    }

    // Read PIN for server-side verification. The PIN is the real auth factor;
    // biometric is supplemental local UX.
    final pin = _pinController.text.trim();
    if (pin.isEmpty || pin.length < 4) {
      setState(() => _error = 'يرجى إدخال رمز PIN لإتمام العملية');
      return;
    }

    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final repo = ref.read(goldRepositoryProvider);
      // 🔒 FIXED: Previously sent hardcoded 'biometric_placeholder' which
      // provided zero security. Now sends the user's PIN for server-side
      // verification. Backend verifies the PIN against the stored bcrypt hash.
      // The on-device biometric above is a local UX gate only.
      if (widget.isBuy) {
        await repo.buy(karat: _karat, grams: grams, pin: pin);
      } else {
        await repo.sell(karat: _karat, grams: grams, pin: pin);
      }
      ref.invalidate(goldWalletProvider);
      ref.invalidate(goldTransactionsProvider);
      if (!mounted) return;
      Navigator.pop(context);
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(
        content: Text(widget.isBuy
            ? '✅ تم شراء ${grams.toStringAsFixed(2)} غرام ذهب'
            : '✅ تم بيع ${grams.toStringAsFixed(2)} غرام ذهب'),
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
    final available = widget.isBuy
        ? 'رصيد الدولار: ${Money.format(widget.wallet.usdBalance, 'USD')}'
        : 'رصيدك: ${widget.wallet.balanceGrams.toStringAsFixed(2)} غرام';

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
              Center(
                child: Container(
                  width: 40,
                  height: 4,
                  decoration: BoxDecoration(
                    color: colors.textHint.withValues(alpha: 0.4),
                    borderRadius: BorderRadius.circular(2),
                  ),
                ),
              ),
              const SizedBox(height: AppSpacing.lg),
              Row(
                children: [
                  Container(
                    width: 44,
                    height: 44,
                    decoration: BoxDecoration(
                      gradient: const LinearGradient(colors: AppColors.cardGradientGold),
                      borderRadius: BorderRadius.circular(AppRadius.md),
                    ),
                    child: Icon(widget.isBuy ? Iconsax.add : Iconsax.minus,
                        color: Colors.white),
                  ),
                  const SizedBox(width: AppSpacing.md),
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(widget.isBuy ? 'شراء ذهب' : 'بيع ذهب',
                          style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                      Text(available,
                          style: TextStyle(fontSize: 12, color: colors.textSecondary)),
                    ],
                  ),
                ],
              ),
              const SizedBox(height: AppSpacing.xl),

              // Karat selector
              Text('العيار', style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: colors.textSecondary)),
              const SizedBox(height: AppSpacing.sm),
              Wrap(
                spacing: AppSpacing.sm,
                children: widget.wallet.prices.map((p) {
                  final selected = p.karat == _karat;
                  return ChoiceChip(
                    label: Text(p.karatLabel),
                    selected: selected,
                    onSelected: (_) => setState(() => _karat = p.karat),
                    selectedColor: colors.primary,
                    labelStyle: TextStyle(
                        color: selected ? Colors.white : colors.textPrimary,
                        fontWeight: FontWeight.w600),
                    backgroundColor: colors.inputBackground,
                  );
                }).toList(),
              ),
              const SizedBox(height: AppSpacing.lg),

              // Grams input
              Text('الكمية (غرام)', style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: colors.textSecondary)),
              const SizedBox(height: AppSpacing.sm),
              TextField(
                controller: _gramsController,
                keyboardType: const TextInputType.numberWithOptions(decimal: true),
                inputFormatters: [
                  FilteringTextInputFormatter.allow(RegExp(r'^\d*\.?\d{0,2}')),
                ],
                onChanged: (_) => setState(() => _error = null),
                style: const TextStyle(fontSize: 22, fontWeight: FontWeight.bold),
                decoration: InputDecoration(
                  hintText: '0.00',
                  suffixText: 'غرام',
                  filled: true,
                  fillColor: colors.inputBackground,
                  border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(AppRadius.md),
                      borderSide: BorderSide.none),
                ),
              ),
              const SizedBox(height: AppSpacing.sm),
              Wrap(
                spacing: AppSpacing.sm,
                children: [1, 5, 10, 25].map((g) {
                  return ActionChip(
                    label: Text('$g غ'),
                    onPressed: () {
                      _gramsController.text = g.toString();
                      setState(() => _error = null);
                    },
                    backgroundColor: colors.inputBackground,
                  );
                }).toList(),
              ),
              const SizedBox(height: AppSpacing.lg),

              // PIN entry (required for server-side verification)
              Text('رمز PIN',
                  style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: colors.textSecondary)),
              const SizedBox(height: AppSpacing.sm),
              TextField(
                controller: _pinController,
                focusNode: _pinFocusNode,
                obscureText: true,
                keyboardType: TextInputType.number,
                maxLength: 6,
                inputFormatters: [
                  FilteringTextInputFormatter.digitsOnly,
                ],
                onChanged: (_) => setState(() => _error = null),
                style: const TextStyle(fontSize: 22, fontWeight: FontWeight.bold, letterSpacing: 8),
                textAlign: TextAlign.center,
                decoration: InputDecoration(
                  hintText: '••••••',
                  filled: true,
                  fillColor: colors.inputBackground,
                  border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(AppRadius.md),
                      borderSide: BorderSide.none),
                ),
              ),
              const SizedBox(height: AppSpacing.lg),

              // Summary
              _summaryRow('سعر الغرام', Money.format(_unitPrice, 'USD')),
              const SizedBox(height: 6),
              _summaryRow('القيمة', Money.format(_subtotal, 'USD')),
              const SizedBox(height: 6),
              _summaryRow(
                  'الرسوم (${widget.isBuy ? '1%' : '0.5%'})', Money.format(_fee, 'USD')),
              const Divider(height: AppSpacing.xl),
              _summaryRow(
                widget.isBuy ? 'الإجمالي المطلوب' : 'صافي المبلغ',
                Money.format(_total, 'USD'),
                bold: true,
              ),
              const SizedBox(height: AppSpacing.lg),

              if (_error != null) ...[
                Container(
                  padding: const EdgeInsets.all(AppSpacing.md),
                  decoration: BoxDecoration(
                    color: colors.errorLight.withValues(alpha: 0.3),
                    borderRadius: BorderRadius.circular(AppRadius.md),
                  ),
                  child: Row(
                    children: [
                      Icon(Iconsax.warning_2, color: colors.error, size: 18),
                      const SizedBox(width: AppSpacing.sm),
                      Expanded(
                          child: Text(_error!,
                              style: TextStyle(color: colors.error, fontSize: 12.5))),
                    ],
                  ),
                ),
                const SizedBox(height: AppSpacing.md),
              ],

              AppButton(
                label: widget.isBuy ? 'تأكيد الشراء' : 'تأكيد البيع',
                icon: Iconsax.finger_scan,
                loading: _loading,
                onPressed: _submit,
              ),
              const SizedBox(height: AppSpacing.sm),
              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(Iconsax.finger_scan, size: 14, color: colors.textHint),
                  const SizedBox(width: 6),
                  Text('بصمة + PIN',
                      style: TextStyle(fontSize: 11.5, color: colors.textHint)),
                ],
              ),
              const SizedBox(height: AppSpacing.sm),
            ],
          ),
        ),
      ),
    );
  }

  Widget _summaryRow(String label, String value, {bool bold = false}) {
    final colors = context.appColors;
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(label,
            style: TextStyle(
                fontSize: bold ? 15 : 13.5,
                fontWeight: bold ? FontWeight.w700 : FontWeight.w500,
                color: bold ? colors.textPrimary : colors.textSecondary)),
        Text(value,
            style: TextStyle(
                fontSize: bold ? 17 : 14,
                fontWeight: bold ? FontWeight.w800 : FontWeight.w600,
                color: colors.textPrimary)),
      ],
    );
  }
}

// ════════════════════════════════════════════════════════════════════
// Helpers
// ════════════════════════════════════════════════════════════════════
class _BigActionButton extends StatelessWidget {
  final IconData icon;
  final String label;
  final VoidCallback? onTap;
  final bool filled;
  const _BigActionButton({
    required this.icon,
    required this.label,
    this.onTap,
    this.filled = false,
  });

  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    final disabled = onTap == null;
    return Opacity(
      opacity: disabled ? 0.5 : 1,
      child: GestureDetector(
        onTap: onTap,
        child: Container(
          height: 56,
          decoration: BoxDecoration(
            gradient: filled
                ? const LinearGradient(colors: AppColors.cardGradientGold)
                : null,
            color: filled ? null : colors.surface,
            borderRadius: BorderRadius.circular(AppRadius.lg),
            border: filled ? null : Border.all(color: colors.inputBackground),
            boxShadow: AppShadows.soft,
          ),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(icon, color: filled ? Colors.white : colors.primary, size: 22),
              const SizedBox(width: AppSpacing.sm),
              Text(label,
                  style: TextStyle(
                      color: filled ? Colors.white : colors.textPrimary,
                      fontSize: 15,
                      fontWeight: FontWeight.w700)),
            ],
          ),
        ),
      ),
    );
  }
}

class _BalanceSkeleton extends StatelessWidget {
  const _BalanceSkeleton();
  @override
  Widget build(BuildContext context) => const SakkShimmer(
        child: SkeletonBalanceCard(margin: EdgeInsets.zero),
      );
}

class _ErrorCard extends StatelessWidget {
  final VoidCallback onRetry;
  const _ErrorCard({required this.onRetry});
  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    return AppCard(
      child: Column(
        children: [
          Icon(Iconsax.warning_2, color: colors.error, size: 36),
          const SizedBox(height: AppSpacing.sm),
          Text('تعذّر تحميل محفظة الذهب',
              style: TextStyle(color: colors.textSecondary)),
          const SizedBox(height: AppSpacing.sm),
          TextButton(onPressed: onRetry, child: const Text('إعادة المحاولة')),
        ],
      ),
    );
  }
}

class _EmptyHistory extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    return Padding(
      padding: const EdgeInsets.all(AppSpacing.xl),
      child: Column(
        children: [
          Icon(Iconsax.receipt_2, size: 44, color: colors.textHint),
          const SizedBox(height: AppSpacing.sm),
          Text('لا توجد عمليات بعد',
              style: TextStyle(color: colors.textSecondary)),
        ],
      ),
    );
  }
}
