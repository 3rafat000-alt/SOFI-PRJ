import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:iconsax/iconsax.dart';
import 'package:share_plus/share_plus.dart';

import '../../../../core/theme/app_colors.dart';
import '../../../../core/utils/money_formatter.dart';
import '../../../../core/widgets/app_ui.dart';
import '../../../../shared/widgets/branded_qr.dart';
import '../../../auth/data/repositories/auth_repository.dart';

/// Receive money — choose a method from a clean, selectable list: account
/// number or QR code expand inline (accordion); NFC opens its own dedicated
/// "tap to pay" terminal screen.
class QRReceivePage extends ConsumerStatefulWidget {
  const QRReceivePage({super.key});

  @override
  ConsumerState<QRReceivePage> createState() => _QRReceivePageState();
}

class _QRReceivePageState extends ConsumerState<QRReceivePage> {
  int _method = 0; // 0=account, 1=QR

  void _snack(String msg, {Color color = AppColors.success}) {
    final icon = color == AppColors.success
        ? Iconsax.tick_circle
        : color == AppColors.warning
            ? Iconsax.info_circle
            : Iconsax.warning_2;
    ScaffoldMessenger.of(context)
      ..hideCurrentSnackBar()
      ..showSnackBar(SnackBar(
        behavior: SnackBarBehavior.floating,
        backgroundColor: color,
        margin: const EdgeInsets.all(AppSpacing.lg),
        shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(AppRadius.md)),
        content: Row(children: [
          Icon(icon, color: Colors.white, size: 20),
          const SizedBox(width: AppSpacing.sm),
          Expanded(
              child: Text(msg,
                  style: const TextStyle(
                      color: Colors.white, fontWeight: FontWeight.w600))),
        ]),
      ));
  }

  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    final user = ref.watch(currentUserProvider);
    final account = user != null ? Money.accountNumber(user.id) : '—';

    return PopScope(
      canPop: true,
      onPopInvokedWithResult: (didPop, result) {
        if (!didPop && context.canPop()) {
          context.pop();
        } else if (!didPop) {
          context.go('/dashboard');
        }
      },
      child: AppScaffold(
        title: 'استلام الأموال',
        onBack: () =>
            context.canPop() ? context.pop() : context.go('/dashboard'),
        body: SingleChildScrollView(
          padding: const EdgeInsets.symmetric(
              horizontal: AppSpacing.xl, vertical: AppSpacing.xl),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Align(
                alignment: AlignmentDirectional.centerStart,
                child: Text('اختر طريقة الاستلام',
                    style: TextStyle(
                        fontSize: 15,
                        fontWeight: FontWeight.bold,
                        color: colors.textPrimary)),
              ),
              const SizedBox(height: AppSpacing.md),

              // ───────── Selectable accordion list ─────────
              _methodCard(
                index: 0,
                icon: Iconsax.hashtag,
                title: 'رقم الحساب',
                subtitle: 'انسخ أو شارك رقم حسابك',
                content: _accountContent(account),
              ),
              _methodCard(
                index: 1,
                icon: Iconsax.scan_barcode,
                title: 'رمز QR',
                subtitle: 'يمسحه المُرسِل ليحوّل لك فوراً',
                content: _qrContent(account),
              ),
              _methodCard(
                index: 2,
                icon: Iconsax.wifi,
                title: 'استلام عبر NFC',
                subtitle: 'حوّل هاتفك لنقطة دفع — بلمسة واحدة',
                onTap: () => context.push('/nfc-receive'),
              ),

              const SizedBox(height: AppSpacing.lg),
              Container(
                padding: const EdgeInsets.all(AppSpacing.md),
                decoration: BoxDecoration(
                  color: colors.primaryLight.withValues(alpha: 0.5),
                  borderRadius: BorderRadius.circular(AppRadius.md),
                ),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Icon(Iconsax.info_circle,
                        size: 18, color: colors.primary),
                    const SizedBox(width: AppSpacing.sm),
                    Expanded(
                      child: Text(
                        'استلم الأموال من مستخدمي صكّ فوراً وبدون رسوم.',
                        style: TextStyle(
                            fontSize: 12.5,
                            color: colors.primary.withValues(alpha: 0.85),
                            height: 1.4),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  // ───────── Accordion method card ─────────
  Widget _methodCard({
    required int index,
    required IconData icon,
    required String title,
    required String subtitle,
    Widget? content,
    VoidCallback? onTap,
  }) {
    final colors = context.appColors;
    final isNav = onTap != null;
    final open = !isNav && _method == index;
    final highlight = open || isNav;
    return AppCard(
      margin: const EdgeInsets.only(bottom: AppSpacing.md),
      padding: EdgeInsets.zero,
      border: highlight
          ? Border.all(
              color: colors.primary.withValues(alpha: isNav ? 0.30 : 0.45),
              width: 1.4)
          : Border.all(color: colors.inputBackground),
      child: Column(
        children: [
          InkWell(
            borderRadius: BorderRadius.circular(AppRadius.lg),
            onTap: isNav ? onTap : () => setState(() => _method = open ? -1 : index),
            child: Padding(
              padding: const EdgeInsets.all(AppSpacing.lg),
              child: Row(
                children: [
                  Container(
                    width: 46,
                    height: 46,
                    decoration: BoxDecoration(
                      gradient: highlight
                          ? LinearGradient(
                              colors: colors.cardGradientVisa)
                          : null,
                      color: highlight ? null : colors.primaryLight,
                      borderRadius: BorderRadius.circular(14),
                    ),
                    child: Icon(icon,
                        color: highlight ? Colors.white : colors.primary,
                        size: 22),
                  ),
                  const SizedBox(width: AppSpacing.md),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(title,
                            style: TextStyle(
                                fontSize: 15,
                                fontWeight: FontWeight.w700,
                                color: colors.textPrimary)),
                        const SizedBox(height: 2),
                        Text(subtitle,
                            style: TextStyle(
                                fontSize: 12,
                                color: colors.textSecondary)),
                      ],
                    ),
                  ),
                  if (isNav)
                    Icon(Iconsax.arrow_left_2,
                        size: 18, color: colors.primary)
                  else
                    AnimatedRotation(
                      turns: open ? 0.5 : 0,
                      duration: const Duration(milliseconds: 200),
                      child: Icon(Iconsax.arrow_down_1,
                          size: 18,
                          color: open
                              ? colors.primary
                              : colors.textSecondary),
                    ),
                ],
              ),
            ),
          ),
          if (!isNav && content != null)
            AnimatedCrossFade(
              firstChild: const SizedBox(width: double.infinity, height: 0),
              secondChild: Padding(
                padding: const EdgeInsets.fromLTRB(
                    AppSpacing.lg, 0, AppSpacing.lg, AppSpacing.lg),
                child: content,
              ),
              crossFadeState:
                  open ? CrossFadeState.showSecond : CrossFadeState.showFirst,
              duration: const Duration(milliseconds: 250),
            ),
        ],
      ),
    );
  }

  // ───────── Method contents ─────────
  Widget _accountContent(String account) {
    final colors = context.appColors;
    return Column(
      children: [
        Container(
          width: double.infinity,
          padding: const EdgeInsets.symmetric(vertical: AppSpacing.lg),
          decoration: BoxDecoration(
            color: colors.inputBackground,
            borderRadius: BorderRadius.circular(AppRadius.md),
          ),
          child: Column(
            children: [
              Text('رقم حسابك في صكّ',
                  style: TextStyle(fontSize: 12, color: colors.textSecondary)),
              const SizedBox(height: 6),
              Text(
                account,
                textDirection: TextDirection.ltr,
                style: TextStyle(
                  fontSize: 28,
                  fontWeight: FontWeight.w800,
                  fontFamily: 'monospace',
                  letterSpacing: 3,
                  color: colors.primary,
                ),
              ),
            ],
          ),
        ),
        const SizedBox(height: AppSpacing.md),
        Row(
          children: [
            Expanded(
              child: AppButton(
                label: 'نسخ',
                icon: Iconsax.copy,
                variant: AppButtonVariant.secondary,
                onPressed: () {
                  Clipboard.setData(ClipboardData(text: account));
                  _snack('تم نسخ رقم الحساب');
                },
              ),
            ),
            const SizedBox(width: AppSpacing.md),
            Expanded(
              child: AppButton(
                label: 'مشاركة',
                icon: Iconsax.share,
                onPressed: () => Share.share('رقم حسابي في صكّ: $account'),
              ),
            ),
          ],
        ),
      ],
    );
  }

  Widget _qrContent(String account) {
    final colors = context.appColors;
    return Column(
      children: [
        BrandedQr(data: 'SAKK:$account', size: 220, caption: account),
        const SizedBox(height: AppSpacing.md),
        Container(
          padding: const EdgeInsets.symmetric(
              vertical: AppSpacing.sm, horizontal: AppSpacing.md),
          decoration: BoxDecoration(
            color: colors.primaryLight,
            borderRadius: BorderRadius.circular(AppRadius.md),
          ),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(Iconsax.scan_barcode, size: 16, color: colors.primary),
              const SizedBox(width: AppSpacing.sm),
              Text('اطلب من المُرسِل مسح هذا الرمز',
                  style: TextStyle(
                      fontSize: 12.5,
                      color: colors.primary,
                      fontWeight: FontWeight.w600)),
            ],
          ),
        ),
      ],
    );
  }

}
