import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:iconsax/iconsax.dart';

import '../../../../core/theme/app_colors.dart';
import '../../../../core/services/screen_security_service.dart';
import '../../../../core/widgets/app_skeleton.dart';
import '../../../../core/widgets/app_ui.dart';
import '../../../../core/widgets/biometric_gate.dart';
import '../../data/repositories/card_repository.dart';
import '../../data/models/card_model.dart';
import '../widgets/virtual_card_widget.dart';

class CardDetailsPage extends ConsumerStatefulWidget {
  final int cardId;
  const CardDetailsPage({super.key, required this.cardId});

  @override
  ConsumerState<CardDetailsPage> createState() => _CardDetailsPageState();
}

class _CardDetailsPageState extends ConsumerState<CardDetailsPage>
    with WidgetsBindingObserver {
  CardDetails? _cardDetails;
  bool _isLoadingDetails = false;
  bool _isScreenSecured = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    _disableScreenSecurity();
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.paused ||
        state == AppLifecycleState.inactive) {
      if (_cardDetails != null) {
        setState(() => _cardDetails = null);
        _disableScreenSecurity();
      }
    }
  }

  Future<void> _enableScreenSecurity() async {
    if (!_isScreenSecured) {
      await ScreenSecurityService.enableSecureScreen();
      _isScreenSecured = true;
    }
  }

  Future<void> _disableScreenSecurity() async {
    if (_isScreenSecured) {
      await ScreenSecurityService.disableSecureScreen();
      _isScreenSecured = false;
    }
  }

  void _snack(String msg, {Color color = AppColors.error}) {
    ScaffoldMessenger.of(context)
        .showSnackBar(SnackBar(content: Text(msg), backgroundColor: color));
  }

  @override
  Widget build(BuildContext context) {
    final cardAsync = ref.watch(cardProvider(widget.cardId));

    return AppScaffold(
      title: 'تفاصيل البطاقة',
      body: cardAsync.when(
        data: (card) => SingleChildScrollView(
          padding: const EdgeInsets.fromLTRB(20, 8, 20, 40),
          child: Column(
            children: [
                      _cardPreview(card),
                      const SizedBox(height: 24),
                      if (_cardDetails != null)
                        _screenshotWarning(),
                      const SizedBox(height: 16),
                      _quickActions(card),
                      const SizedBox(height: 16),
                      if (!card.isCancelled) _featuredButton(card),
                      const SizedBox(height: 28),
                      _infoGrid(card),
                      const SizedBox(height: 24),
                      if (_cardDetails != null) _copyOptions(),
                      const SizedBox(height: 20),
                      _transactionButton(card),
                      if (!card.isFrozen) ...[
                        const SizedBox(height: 12),
                        _cancelButton(card),
                      ],
            ],
          ),
        ),
          loading: () => const SakkShimmer(
            child: SingleChildScrollView(
              padding: EdgeInsets.fromLTRB(20, 8, 20, 40),
              child: Column(children: [
                SkeletonCard(height: 200, margin: EdgeInsets.zero),
                SizedBox(height: 24),
                SkeletonActionRow(count: 4),
                SizedBox(height: 20),
                SkeletonButton(),
                SizedBox(height: 28),
                SkeletonLines(lines: 4),
              ]),
            ),
          ),
        error: (error, _) => Center(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const Icon(Iconsax.warning_2, size: 64, color: AppColors.error),
              const SizedBox(height: 16),
              Text(error.toString()),
              const SizedBox(height: 16),
              ElevatedButton(
                onPressed: () => ref.refresh(cardProvider(widget.cardId)),
                child: const Text('إعادة المحاولة'),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _cardPreview(CardModel card) {
    return Hero(
      tag: 'card_${card.id}',
      child: Container(
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(20),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.2),
              blurRadius: 20,
              offset: const Offset(0, 8),
            ),
          ],
        ),
        child: ClipRRect(
          borderRadius: BorderRadius.circular(20),
          child: VirtualCardWidget(
            card: card,
            showDetails: _cardDetails != null,
            cardDetails: _cardDetails,
          ),
        ),
      ),
    );
  }

  Widget _screenshotWarning() {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: AppColors.warning.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: AppColors.warning.withValues(alpha: 0.3)),
      ),
      child: const Row(children: [
        Icon(Iconsax.shield_tick, color: AppColors.warning, size: 20),
        SizedBox(width: 12),
        Expanded(child: Text('لقطات الشاشة معطلة لحماية بياناتك',
            style: TextStyle(fontSize: 12, color: AppColors.warning, fontWeight: FontWeight.w500))),
      ]),
    );
  }

  Widget _featuredButton(CardModel card) {
    final colors = context.appColors;
    final isFeatured = ref.watch(featuredCardIdProvider) == card.id;
    final accent = isFeatured ? colors.success : colors.primary;
    return SizedBox(
      width: double.infinity,
      child: OutlinedButton.icon(
        onPressed: isFeatured
            ? null
            : () async {
                await ref
                    .read(featuredCardIdProvider.notifier)
                    .setFeatured(card.id);
                if (mounted) {
                  _snack('تم تعيينها كبطاقة مميزة ⭐', color: colors.success);
                }
              },
        icon: Icon(
          isFeatured ? Icons.star_rounded : Icons.star_outline_rounded,
          size: 20,
          color: accent,
        ),
        label: Text(isFeatured ? 'هذه هي بطاقتك المميزة' : 'تعيين كبطاقة مميزة'),
        style: OutlinedButton.styleFrom(
          foregroundColor: accent,
          side: BorderSide(color: accent.withValues(alpha: 0.4)),
          padding: const EdgeInsets.symmetric(vertical: 14),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
        ),
      ),
    );
  }

  Widget _quickActions(CardModel card) {
    return Row(
      children: [
        _actionTile(
          icon: _cardDetails == null ? Iconsax.eye : Iconsax.eye_slash,
          label: _cardDetails == null ? 'عرض البيانات' : 'إخفاء',
          loading: _isLoadingDetails,
          onTap: () {
            if (_cardDetails == null) {
              _loadCardDetails(card);
            } else {
              setState(() => _cardDetails = null);
              _disableScreenSecurity();
            }
          },
        ),
        const SizedBox(width: 12),
        _actionTile(
          icon: card.isFrozen ? Iconsax.unlock : Iconsax.lock_1,
          label: card.isFrozen ? 'فك التجميد' : 'تجميد',
          onTap: () => _toggleFreeze(card),
        ),
        const SizedBox(width: 12),
        _actionTile(
          icon: Iconsax.money_add,
          label: 'شحن',
          onTap: () => context.push('/cards/${card.id}/fund', extra: card),
        ),
      ],
    );
  }

  Widget _actionTile({
    required IconData icon,
    required String label,
    required VoidCallback onTap,
    bool loading = false,
  }) {
    final colors = context.appColors;
    return Expanded(
      child: GestureDetector(
        onTap: loading ? null : onTap,
        behavior: HitTestBehavior.opaque,
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 16),
          decoration: BoxDecoration(
            color: colors.surface,
            borderRadius: BorderRadius.circular(AppRadius.lg),
            boxShadow: AppShadows.soft,
          ),
          child: Column(
            children: [
              SizedBox(
                height: 28,
                width: 28,
                child: loading
                    ? CircularProgressIndicator(
                        strokeWidth: 2, color: colors.primary)
                    : Icon(icon, color: colors.primary, size: 26),
              ),
              const SizedBox(height: 8),
              Text(label,
                  style: TextStyle(
                      fontSize: 12.5,
                      fontWeight: FontWeight.w600,
                      color: colors.textPrimary)),
            ],
          ),
        ),
      ),
    );
  }

  Widget _infoGrid(CardModel card) {
    final colors = context.appColors;
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: colors.surface,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: colors.inputBackground),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.03),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Expanded(
                child: _infoItem(
                  icon: Iconsax.card,
                  label: 'نوع البطاقة',
                  value: card.isVisa ? 'Visa Virtual' : 'Mastercard Virtual',
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: _infoItem(
                  icon: card.isActive ? Iconsax.tick_circle : Iconsax.warning_2,
                  label: 'الحالة',
                  value: card.statusLabel,
                  valueColor: card.isActive ? AppColors.success : AppColors.warning,
                ),
              ),
            ],
          ),
          const Divider(height: 28),
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Expanded(
                child: _infoItem(
                  icon: Iconsax.wallet_2,
                  label: 'الرصيد',
                  value: card.formattedBalance,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: _infoItem(
                  icon: Iconsax.chart_1,
                  label: 'حد الإنفاق',
                  value: '\$${card.dailyLimit.toStringAsFixed(0)}',
                ),
              ),
            ],
          ),
          const Divider(height: 28),
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Expanded(
                child: _infoItem(
                  icon: Iconsax.calendar_1,
                  label: 'صالحة حتى',
                  value: card.expiryDate,
                ),
              ),
              const SizedBox(width: 12),
              const Expanded(child: SizedBox()),
            ],
          ),
        ],
      ),
    );
  }

  Widget _infoItem({
    required IconData icon,
    required String label,
    required String value,
    Color? valueColor,
  }) {
    final colors = context.appColors;
    return Row(
      children: [
        Container(
          width: 40,
          height: 40,
          decoration: BoxDecoration(
            color: colors.primaryLight,
            borderRadius: BorderRadius.circular(12),
          ),
          child: Icon(icon, size: 20, color: colors.primary),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(label,
                  style: TextStyle(fontSize: 12, color: colors.textSecondary)),
              const SizedBox(height: 2),
              Text(value,
                  style: TextStyle(
                      fontSize: 14,
                      fontWeight: FontWeight.w600,
                      color: valueColor ?? colors.textPrimary)),
            ],
          ),
        ),
      ],
    );
  }

  Widget _copyOptions() {
    return Column(
      children: [
        Row(children: [
          Expanded(
            child: _CopyButton(icon: Iconsax.copy, label: 'نسخ الرقم', onTap: () {
              Clipboard.setData(ClipboardData(text: _cardDetails!.cardNumber));
              _snack('تم نسخ رقم البطاقة', color: AppColors.success);
            }),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: _CopyButton(icon: Iconsax.copy, label: 'نسخ CVV', onTap: () {
              Clipboard.setData(ClipboardData(text: _cardDetails!.cvv));
              _snack('تم نسخ CVV', color: AppColors.success);
            }),
          ),
        ]),
        const SizedBox(height: 12),
        SizedBox(
          width: double.infinity,
          child: TextButton.icon(
            onPressed: () {
              final txt = 'رقم: ${_cardDetails!.cardNumber}\nCVV: ${_cardDetails!.cvv}\nتاريخ: ${_cardDetails!.expiryMonth}/${_cardDetails!.expiryYear}';
              Clipboard.setData(ClipboardData(text: txt));
              _snack('تم نسخ التفاصيل', color: AppColors.success);
            },
            icon: const Icon(Iconsax.document_copy, size: 18),
            label: const Text('نسخ جميع التفاصيل'),
          ),
        ),
      ],
    );
  }

  Widget _transactionButton(CardModel card) {
    return SizedBox(
      width: double.infinity,
      child: OutlinedButton.icon(
        onPressed: () => context.push('/cards/${card.id}/transactions'),
        icon: const Icon(Iconsax.receipt_2),
        label: const Text('سجل المعاملات'),
        style: OutlinedButton.styleFrom(padding: const EdgeInsets.symmetric(vertical: 14)),
      ),
    );
  }

  Widget _cancelButton(CardModel card) {
    return SizedBox(
      width: double.infinity,
      child: TextButton.icon(
        onPressed: () => _cancelCard(card),
        icon: const Icon(Iconsax.slash, size: 18, color: AppColors.error),
        label: const Text('إلغاء البطاقة نهائياً',
            style: TextStyle(color: AppColors.error)),
        style: TextButton.styleFrom(padding: const EdgeInsets.symmetric(vertical: 14)),
      ),
    );
  }

  Future<void> _loadCardDetails(CardModel card) async {
    final confirmed = await confirmWithBiometrics(
      context,
      reason: 'التحقق بالبصمة للكشف عن رقم البطاقة و CVV',
    );
    if (!confirmed) return;

    setState(() => _isLoadingDetails = true);
    try {
      final details = await ref.read(cardRepositoryProvider).getCardDetails(card.id);
      await _enableScreenSecurity();
      setState(() => _cardDetails = details);
    } catch (e) {
      _snack(e.toString());
    } finally {
      setState(() => _isLoadingDetails = false);
    }
  }

  Future<void> _toggleFreeze(CardModel card) async {
    try {
      if (card.isFrozen) {
        final confirmed = await confirmWithBiometrics(
          context,
          reason: 'التحقق بالبصمة لتأكيد فك تجميد البطاقة',
        );
        if (!confirmed) return;
        await ref.read(cardRepositoryProvider).unfreezeCard(card.id);
        ref.invalidate(cardProvider(widget.cardId));
        ref.invalidate(cardsProvider);
        _snack('تم فك تجميد البطاقة', color: AppColors.success);
      } else {
        await ref.read(cardRepositoryProvider).freezeCard(card.id);
        ref.invalidate(cardProvider(widget.cardId));
        ref.invalidate(cardsProvider);
        _snack('تم تجميد البطاقة', color: AppColors.success);
      }
    } catch (e) {
      _snack(e.toString());
    }
  }

  Future<void> _cancelCard(CardModel card) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: const Text('إلغاء البطاقة'),
        content: const Text('هل أنت متأكد؟ سيتم تحويل الرصيد المتبقي إلى محفظتك. لا يمكن التراجع عن هذا الإجراء.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('تراجع')),
          ElevatedButton(
            onPressed: () => Navigator.pop(ctx, true),
            style: ElevatedButton.styleFrom(backgroundColor: AppColors.error),
            child: const Text('إلغاء البطاقة', style: TextStyle(color: Colors.white)),
          ),
        ],
      ),
    );
    if (confirmed != true || !mounted) return;

    final bioOk = await confirmWithBiometrics(
      context,
      reason: 'التحقق بالبصمة لتأكيد إلغاء البطاقة نهائياً',
    );
    if (!bioOk) return;

    try {
      await ref.read(cardRepositoryProvider).cancelCard(card.id);
      ref.invalidate(cardsProvider);
      if (mounted) {
        context.pop();
        _snack('تم إلغاء البطاقة', color: AppColors.success);
      }
    } catch (e) {
      if (mounted) _snack(e.toString());
    }
  }
}

class _CopyButton extends StatelessWidget {
  final IconData icon;
  final String label;
  final VoidCallback onTap;

  const _CopyButton({required this.icon, required this.label, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return OutlinedButton.icon(
      onPressed: onTap,
      icon: Icon(icon, size: 18),
      label: Text(label),
      style: OutlinedButton.styleFrom(padding: const EdgeInsets.symmetric(vertical: 12)),
    );
  }
}
