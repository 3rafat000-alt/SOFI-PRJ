import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:iconsax/iconsax.dart';
import 'package:flutter_animate/flutter_animate.dart';
import '../../../../core/theme/app_colors.dart';
import '../../../../core/widgets/app_skeleton.dart';
import '../../../../core/widgets/app_ui.dart';
import '../../../../core/widgets/biometric_gate.dart';
import '../../data/repositories/card_repository.dart';
import '../../data/models/card_model.dart';
import '../../../wallets/data/repositories/wallet_repository.dart';
import '../../../wallets/data/models/wallet_model.dart';
import '../widgets/virtual_card_widget.dart';

class FundCardPage extends ConsumerStatefulWidget {
  final int cardId;

  const FundCardPage({super.key, required this.cardId});

  @override
  ConsumerState<FundCardPage> createState() => _FundCardPageState();
}

class _FundCardPageState extends ConsumerState<FundCardPage> {
  final _amountController = TextEditingController();
  final _formKey = GlobalKey<FormState>();
  bool _isLoading = false;
  bool _isLoad = true;
  int? _selectedWalletId;
  double _amount = 0;

  @override
  void dispose() {
    _amountController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    final amount = double.tryParse(_amountController.text.trim());
    if (amount == null || amount <= 0) {
      _snack('الرجاء إدخال مبلغ صالح');
      return;
    }
    if (_selectedWalletId == null) {
      _snack(_isLoad ? 'اختر المحفظة المصدر' : 'اختر المحفظة الوجهة');
      return;
    }

    final confirmed = await confirmWithBiometrics(
      context,
      reason: 'التحقق بالبصمة لتأكيد ${_isLoad ? 'شحن' : 'تفريغ'} البطاقة',
    );
    if (!confirmed) return;

    _amount = amount;
    setState(() => _isLoading = true);

    try {
      await ref
          .read(cardRepositoryProvider)
          .loadCard(
            cardId: widget.cardId,
            walletId: _selectedWalletId!,
            amount: amount,
            isLoad: _isLoad,
          );

      ref.invalidate(cardProvider(widget.cardId));
      ref.invalidate(walletsProvider);

      if (mounted) {
        _showSuccessDialog();
      }
    } catch (e) {
      if (mounted) _snack(e.toString());
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  void _snack(String msg) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(msg, style: const TextStyle(color: Colors.white)),
        backgroundColor: context.appColors.error,
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      ),
    );
  }

  void _showSuccessDialog() {
    final colors = context.appColors;
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (ctx) {
        final successIcon = Container(
          width: 72,
          height: 72,
          decoration: BoxDecoration(
            color: colors.successLight,
            shape: BoxShape.circle,
          ),
          child: Icon(
            _isLoad ? Iconsax.tick_circle : Iconsax.minus_cirlce,
            color: colors.success,
            size: 38,
          ),
        );
        final reduceMotion =
            MediaQuery.maybeOf(ctx)?.disableAnimations ?? false;
        return AlertDialog(
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(24),
          ),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              reduceMotion
                  ? successIcon
                  : successIcon.animate().scale(
                    begin: const Offset(0.5, 0.5),
                    curve: Curves.easeOutBack,
                  ),
              const SizedBox(height: 18),
              Text(
                _isLoad ? 'تم شحن البطاقة بنجاح' : 'تم تفريغ البطاقة بنجاح',
                textAlign: TextAlign.center,
                style: TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                  color: colors.textPrimary,
                ),
              ),
              const SizedBox(height: 8),
              Text(
                '\$${_amount.toStringAsFixed(2)}',
                textDirection: TextDirection.ltr,
                style: TextStyle(
                  fontSize: 30,
                  fontWeight: FontWeight.bold,
                  color: colors.primary,
                ),
              ),
              const SizedBox(height: 4),
              Text(
                _isLoad ? 'تمت إضافته إلى البطاقة' : 'تم تحويله إلى المحفظة',
                style: TextStyle(fontSize: 13, color: colors.textSecondary),
              ),
              const SizedBox(height: 24),
              SizedBox(
                width: double.infinity,
                height: 50,
                child: ElevatedButton(
                  onPressed: () {
                    Navigator.pop(ctx);
                    context.pop();
                  },
                  style: ElevatedButton.styleFrom(
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(14),
                    ),
                  ),
                  child: const Text('تم'),
                ),
              ),
            ],
          ),
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    final cardAsync = ref.watch(cardProvider(widget.cardId));
    final walletsAsync = ref.watch(walletsProvider);

    return AppScaffold(
      title: _isLoad ? 'شحن البطاقة' : 'تفريغ البطاقة',
      body: cardAsync.when(
        data:
            (card) => SingleChildScrollView(
              padding: const EdgeInsets.fromLTRB(20, 8, 20, 40),
              child: Form(
                key: _formKey,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    VirtualCardWidget(card: card),
                    const SizedBox(height: 16),
                    _balancePill(card),
                    const SizedBox(height: 22),
                    _buildToggle(),
                    const SizedBox(height: 22),
                    _amountSection(),
                    const SizedBox(height: 20),
                    _walletSection(walletsAsync),
                    const SizedBox(height: 16),
                    _infoNote(),
                    const SizedBox(height: 28),
                    _actionButton(),
                  ],
                ),
              ),
            ),
        loading:
            () => const SakkShimmer(
              child: SingleChildScrollView(
                padding: EdgeInsets.fromLTRB(20, 8, 20, 40),
                child: Column(
                  children: [
                    SkeletonCard(height: 200, margin: EdgeInsets.zero),
                    SizedBox(height: 24),
                    SkeletonField(),
                    SkeletonField(),
                    SizedBox(height: 8),
                    SkeletonButton(),
                  ],
                ),
              ),
            ),
        error:
            (e, _) => Center(
              child: Padding(
                padding: const EdgeInsets.all(24),
                child: Text(e.toString(), textAlign: TextAlign.center),
              ),
            ),
      ),
    );
  }

  // ───────── Current card balance pill ─────────
  Widget _balancePill(CardModel card) {
    final colors = context.appColors;
    return Center(
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
        decoration: BoxDecoration(
          color: colors.primaryLight,
          borderRadius: BorderRadius.circular(30),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(Iconsax.card, size: 16, color: colors.primary),
            const SizedBox(width: 8),
            Text(
              'رصيد البطاقة:',
              style: TextStyle(fontSize: 12.5, color: colors.textSecondary),
            ),
            const SizedBox(width: 6),
            Text(
              card.formattedBalance,
              textDirection: TextDirection.ltr,
              style: TextStyle(
                fontSize: 14,
                fontWeight: FontWeight.w700,
                color: colors.primary,
              ),
            ),
          ],
        ),
      ),
    );
  }

  // ───────── Load / Unload segmented toggle ─────────
  Widget _buildToggle() {
    final colors = context.appColors;
    return Container(
      padding: const EdgeInsets.all(4),
      decoration: BoxDecoration(
        color: colors.inputBackground,
        borderRadius: BorderRadius.circular(16),
      ),
      child: Row(
        children: [
          _toggleTab(
            label: 'شحن',
            icon: Iconsax.import_2,
            active: _isLoad,
            onTap: () => setState(() => _isLoad = true),
          ),
          _toggleTab(
            label: 'تفريغ',
            icon: Iconsax.export_3,
            active: !_isLoad,
            onTap: () => setState(() => _isLoad = false),
          ),
        ],
      ),
    );
  }

  Widget _toggleTab({
    required String label,
    required IconData icon,
    required bool active,
    required VoidCallback onTap,
  }) {
    final colors = context.appColors;
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final onActive = isDark ? colors.background : Colors.white;
    return Expanded(
      child: GestureDetector(
        onTap: onTap,
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 200),
          padding: const EdgeInsets.symmetric(vertical: 12),
          decoration: BoxDecoration(
            color: active ? colors.primary : Colors.transparent,
            borderRadius: BorderRadius.circular(13),
            boxShadow:
                active
                    ? [
                      BoxShadow(
                        color: Colors.black.withValues(alpha: 0.3),
                        blurRadius: 12,
                        offset: const Offset(0, 4),
                      ),
                    ]
                    : null,
          ),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(
                icon,
                size: 18,
                color: active ? onActive : colors.textSecondary,
              ),
              const SizedBox(width: 8),
              Text(
                label,
                style: TextStyle(
                  color: active ? onActive : colors.textSecondary,
                  fontWeight: FontWeight.w700,
                  fontSize: 14,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  // ───────── Amount input + quick chips ─────────
  Widget _amountSection() {
    final colors = context.appColors;
    return Container(
      padding: const EdgeInsets.fromLTRB(20, 18, 20, 20),
      decoration: BoxDecoration(
        color: colors.surface,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: colors.inputBackground),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.03),
            blurRadius: 14,
            offset: const Offset(0, 6),
          ),
        ],
      ),
      child: Column(
        children: [
          Text(
            _isLoad ? 'كم تريد أن تشحن؟' : 'كم تريد أن تُفرّغ؟',
            style: TextStyle(
              fontSize: 14,
              fontWeight: FontWeight.w600,
              color: colors.textSecondary,
            ),
          ),
          const SizedBox(height: 14),
          Directionality(
            textDirection: TextDirection.ltr,
            child: Row(
              mainAxisAlignment: MainAxisAlignment.center,
              crossAxisAlignment: CrossAxisAlignment.center,
              children: [
                Text(
                  '\$',
                  style: TextStyle(
                    fontSize: 30,
                    fontWeight: FontWeight.bold,
                    color: colors.primary,
                  ),
                ),
                const SizedBox(width: 6),
                IntrinsicWidth(
                  child: TextFormField(
                    controller: _amountController,
                    keyboardType: const TextInputType.numberWithOptions(
                      decimal: true,
                    ),
                    textAlign: TextAlign.center,
                    textDirection: TextDirection.ltr,
                    style: TextStyle(
                      fontSize: 42,
                      fontWeight: FontWeight.bold,
                      color: colors.textPrimary,
                    ),
                    decoration: InputDecoration(
                      isDense: true,
                      hintText: '0',
                      hintStyle: TextStyle(color: colors.textHint),
                      border: InputBorder.none,
                      enabledBorder: InputBorder.none,
                      focusedBorder: InputBorder.none,
                      contentPadding: EdgeInsets.zero,
                    ),
                    onChanged: (_) => setState(() {}),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 18),
          Wrap(
            alignment: WrapAlignment.center,
            spacing: 10,
            runSpacing: 10,
            children:
                const [
                  100,
                  200,
                  500,
                  1000,
                ].map((a) => _quickChip(a.toDouble())).toList(),
          ),
        ],
      ),
    );
  }

  Widget _quickChip(double amount) {
    final colors = context.appColors;
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final selected = double.tryParse(_amountController.text.trim()) == amount;
    return GestureDetector(
      onTap:
          () => setState(
            () => _amountController.text = amount.toStringAsFixed(0),
          ),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 150),
        padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 9),
        decoration: BoxDecoration(
          color:
              selected
                  ? colors.primary
                  : colors.primaryLight.withValues(alpha: 0.45),
          borderRadius: BorderRadius.circular(30),
        ),
        child: Text(
          '\$${amount.toInt()}',
          textDirection: TextDirection.ltr,
          style: TextStyle(
            fontSize: 13,
            fontWeight: FontWeight.w700,
            color:
                selected
                    ? (isDark ? colors.background : Colors.white)
                    : colors.primary,
          ),
        ),
      ),
    );
  }

  // ───────── Source / destination wallet tiles ─────────
  Widget _walletSection(AsyncValue<List<WalletModel>> walletsAsync) {
    final colors = context.appColors;
    return walletsAsync.when(
      data: (wallets) {
        // Cards operate on the USD wallet only.
        final active =
            wallets.where((w) => w.isActive && w.currency == 'USD').toList();
        if (active.isNotEmpty && _selectedWalletId != active.first.id) {
          WidgetsBinding.instance.addPostFrameCallback((_) {
            if (mounted && _selectedWalletId != active.first.id) {
              setState(() => _selectedWalletId = active.first.id);
            }
          });
        }
        return Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              _isLoad ? 'الخصم من محفظة الدولار' : 'الإضافة إلى محفظة الدولار',
              style: TextStyle(
                fontSize: 14,
                fontWeight: FontWeight.w600,
                color: colors.textPrimary,
              ),
            ),
            const SizedBox(height: 10),
            if (active.isEmpty)
              Text(
                'لا توجد محفظة دولار (USD) متاحة',
                style: TextStyle(color: colors.textSecondary, fontSize: 13),
              )
            else
              ...active.map(_walletTile),
          ],
        );
      },
      loading:
          () => const SakkShimmer(
            child: Padding(
              padding: EdgeInsets.all(12),
              child: SkeletonBox(height: 60),
            ),
          ),
      error:
          (e, _) => Text(
            e.toString(),
            style: TextStyle(color: colors.error, fontSize: 13),
          ),
    );
  }

  Widget _walletTile(WalletModel w) {
    final colors = context.appColors;
    final selected = _selectedWalletId == w.id;
    return GestureDetector(
      onTap: () => setState(() => _selectedWalletId = w.id),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 150),
        margin: const EdgeInsets.only(bottom: 10),
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color:
              selected
                  ? colors.primaryLight.withValues(alpha: 0.5)
                  : colors.surface,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(
            color: selected ? colors.primary : colors.inputBackground,
            width: selected ? 1.6 : 1,
          ),
        ),
        child: Row(
          children: [
            Container(
              width: 42,
              height: 42,
              decoration: BoxDecoration(
                gradient: LinearGradient(colors: colors.cardGradientVisa),
                borderRadius: BorderRadius.circular(12),
              ),
              child: const Icon(
                Iconsax.wallet_3,
                color: Colors.white,
                size: 20,
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'محفظة ${w.currency}',
                    style: TextStyle(
                      fontSize: 14,
                      fontWeight: FontWeight.w700,
                      color: colors.textPrimary,
                    ),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    w.formattedBalance,
                    textDirection: TextDirection.ltr,
                    style: TextStyle(
                      fontSize: 12.5,
                      color: colors.textSecondary,
                    ),
                  ),
                ],
              ),
            ),
            Icon(
              selected ? Icons.check_circle : Icons.radio_button_unchecked,
              color: selected ? colors.primary : colors.textHint,
              size: 24,
            ),
          ],
        ),
      ),
    );
  }

  // ───────── Info note ─────────
  Widget _infoNote() {
    final colors = context.appColors;
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: colors.primaryLight.withValues(alpha: 0.35),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Row(
        children: [
          Icon(Iconsax.info_circle, size: 18, color: colors.primary),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              _isLoad
                  ? 'سيُخصم المبلغ من محفظتك ويُضاف إلى رصيد البطاقة فوراً.'
                  : 'سيُحوّل المبلغ من رصيد البطاقة إلى محفظتك فوراً.',
              style: TextStyle(
                fontSize: 12,
                color: colors.textSecondary,
                height: 1.4,
              ),
            ),
          ),
        ],
      ),
    );
  }

  // ───────── Gradient action button ─────────
  Widget _actionButton() {
    final colors = context.appColors;
    return GestureDetector(
      onTap: _isLoading ? null : _submit,
      child: AnimatedOpacity(
        duration: const Duration(milliseconds: 150),
        opacity: _isLoading ? 0.7 : 1,
        child: Container(
          height: 56,
          decoration: BoxDecoration(
            gradient: LinearGradient(
              colors: colors.cardGradientVisa,
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
            ),
            borderRadius: BorderRadius.circular(16),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.32),
                blurRadius: 18,
                offset: const Offset(0, 10),
              ),
            ],
          ),
          child: Center(
            child:
                _isLoading
                    ? const SizedBox(
                      width: 24,
                      height: 24,
                      child: CircularProgressIndicator(
                        color: Colors.white,
                        strokeWidth: 2,
                      ),
                    )
                    : Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(
                          _isLoad ? Iconsax.import_2 : Iconsax.export_3,
                          color: Colors.white,
                          size: 20,
                        ),
                        const SizedBox(width: 10),
                        Text(
                          _isLoad ? 'شحن البطاقة' : 'تفريغ إلى المحفظة',
                          style: const TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.bold,
                            color: Colors.white,
                          ),
                        ),
                      ],
                    ),
          ),
        ),
      ),
    );
  }
}
