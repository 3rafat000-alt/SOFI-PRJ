import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:iconsax/iconsax.dart';

import '../../../../core/theme/app_colors.dart';
import '../../../../core/utils/money_formatter.dart';
import '../../../../core/widgets/app_ui.dart';
import '../../../auth/data/repositories/auth_repository.dart';
import '../../../transfer/data/nfc_hce.dart';

/// Dedicated "receive via NFC" experience — a POS-style payment terminal:
/// a gradient amount card, a clean keypad (no keyboard), and a premium
/// contactless "waiting" animation once activated.
class NfcReceivePage extends ConsumerStatefulWidget {
  const NfcReceivePage({super.key});

  @override
  ConsumerState<NfcReceivePage> createState() => _NfcReceivePageState();
}

class _NfcReceivePageState extends ConsumerState<NfcReceivePage> {
  String _amount = '';
  String _currency = 'USD';
  bool _active = false;
  bool _busy = false;

  double get _value => double.tryParse(_amount) ?? 0;
  bool get _hasAmount => _value > 0;

  @override
  void dispose() {
    if (_active) NfcHce.stopEmulation();
    super.dispose();
  }

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

  void _onKey(String k) {
    if (_active) return;
    var t = _amount;
    if (k == 'back') {
      if (t.isNotEmpty) t = t.substring(0, t.length - 1);
    } else if (k == '.') {
      if (_currency != 'USD' || t.contains('.')) return;
      t = t.isEmpty ? '0.' : '$t.';
    } else {
      var candidate = (t == '0') ? k : t + k;
      if (_currency == 'USD' && candidate.contains('.')) {
        final parts = candidate.split('.');
        if (parts.length == 2 && parts[1].length > 2) return; // max 2 decimals
      }
      if (candidate.replaceAll('.', '').length > 12) return;
      t = candidate;
    }
    setState(() => _amount = t);
  }

  Future<void> _toggle(String account, String name) async {
    if (_busy) return;
    setState(() => _busy = true);
    try {
      if (!_active) {
        if (!await NfcHce.isSupported()) {
          if (mounted) {
            _snack('NFC غير متاح على هذا الجهاز', color: AppColors.warning);
          }
          return;
        }
        final ok = await NfcHce.startPaymentBroadcast(
          account: account,
          amount: _hasAmount ? _value : null,
          currency: _currency,
          name: name,
        );
        if (!mounted) return;
        if (!ok) {
          _snack('تعذّر تفعيل الاستلام عبر NFC', color: AppColors.warning);
          return;
        }
        setState(() => _active = true);
      } else {
        await NfcHce.stopEmulation();
        if (mounted) setState(() => _active = false);
      }
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final user = ref.watch(currentUserProvider);
    final account = user != null ? Money.accountNumber(user.id) : '—';
    final name = user?.fullName ?? '';

    return AppScaffold(
      title: 'استلام عبر NFC',
      subtitle: 'حوّل هاتفك إلى نقطة دفع لاسلكية',
      body: SafeArea(
        top: false,
        child: Column(
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(
                  AppSpacing.xl, AppSpacing.lg, AppSpacing.xl, 0),
              child: _terminalCard(context),
            ),
            Expanded(
                child: _active ? _waitingView(context) : _setupView(context)),
            Padding(
              padding: const EdgeInsets.fromLTRB(
                  AppSpacing.xl, AppSpacing.sm, AppSpacing.xl, AppSpacing.lg),
              child: AppButton(
                label: _active ? 'إيقاف الاستلام' : 'تفعيل الاستلام',
                icon: _active ? Iconsax.close_circle : Iconsax.wifi,
                loading: _busy,
                variant:
                    _active ? AppButtonVariant.danger : AppButtonVariant.primary,
                onPressed: () => _toggle(account, name),
              ),
            ),
          ],
        ),
      ),
    );
  }

  // ───────── Gradient payment-terminal card ─────────
  Widget _terminalCard(BuildContext context) {
    final colors = context.appColors;
    return Container(
      width: double.infinity,
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
            blurRadius: 28,
            offset: const Offset(0, 14),
          ),
        ],
      ),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(AppRadius.xl),
        child: Stack(
          children: [
            Positioned(
              right: -18,
              top: -18,
              child: Transform.rotate(
                angle: -0.5,
                child: Icon(Iconsax.wifi,
                    size: 120, color: Colors.white.withValues(alpha: 0.10)),
              ),
            ),
            Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Row(
                  children: [
                    const Icon(Iconsax.wifi, color: Colors.white70, size: 16),
                    const SizedBox(width: 6),
                    Text(_active ? 'بانتظار الدفع' : 'المبلغ المطلوب',
                        style: const TextStyle(
                            color: Colors.white70,
                            fontSize: 13,
                            fontWeight: FontWeight.w600)),
                  ],
                ),
                const SizedBox(height: AppSpacing.lg),
                Directionality(
                  textDirection: TextDirection.ltr,
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    crossAxisAlignment: CrossAxisAlignment.baseline,
                    textBaseline: TextBaseline.alphabetic,
                    children: [
                      Text('${Money.currencyLabel(_currency)} ',
                          style: const TextStyle(
                              color: Colors.white70,
                              fontSize: 18,
                              fontWeight: FontWeight.w700)),
                      Flexible(
                        child: FittedBox(
                          fit: BoxFit.scaleDown,
                          child: Text(
                            _amount.isEmpty
                                ? (_currency == 'USD' ? '0.00' : '0')
                                : _amount,
                            style: const TextStyle(
                                color: Colors.white,
                                fontSize: 42,
                                fontWeight: FontWeight.w800,
                                height: 1.1),
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: AppSpacing.lg),
                _currencyToggle(),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _currencyToggle() {
    return Container(
      padding: const EdgeInsets.all(4),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.18),
        borderRadius: BorderRadius.circular(AppRadius.md),
      ),
      child: Row(
        children: [_segment('USD'), _segment('SYP')],
      ),
    );
  }

  Widget _segment(String currency) {
    final selected = _currency == currency;
    return Expanded(
      child: GestureDetector(
        onTap: _active
            ? null
            : () => setState(() {
                  _currency = currency;
                  _amount = '';
                }),
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 200),
          padding: const EdgeInsets.symmetric(vertical: AppSpacing.sm),
          decoration: BoxDecoration(
            color: selected ? Colors.white : Colors.transparent,
            borderRadius: BorderRadius.circular(AppRadius.sm),
          ),
          child: Center(
            child: Text(
              Money.currencyLabel(currency),
              style: TextStyle(
                fontSize: 13,
                fontWeight: FontWeight.w700,
                color: selected ? AppColors.primary : Colors.white,
              ),
            ),
          ),
        ),
      ),
    );
  }

  // ───────── Setup: hint + keypad ─────────
  Widget _setupView(BuildContext context) {
    final colors = context.appColors;
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: AppSpacing.xl),
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Container(
            padding: const EdgeInsets.symmetric(
                horizontal: AppSpacing.md, vertical: 6),
            decoration: BoxDecoration(
              color: colors.successLight.withValues(alpha: 0.7),
              borderRadius: BorderRadius.circular(AppRadius.pill),
            ),
            child: Row(mainAxisSize: MainAxisSize.min, children: [
              Icon(Iconsax.shield_tick, size: 14, color: colors.success),
              const SizedBox(width: 6),
              Text('الدافع يرى المبلغ واسمك ثم يؤكّد بنفسه',
                  style: TextStyle(
                      fontSize: 11.5,
                      color: colors.success,
                      fontWeight: FontWeight.w600)),
            ]),
          ),
          const SizedBox(height: AppSpacing.lg),
          _keypad(context),
          const SizedBox(height: AppSpacing.xs),
          Text(
            _hasAmount
                ? 'سيدفع لك الطرف الآخر هذا المبلغ بالضبط'
                : 'اتركه فارغاً لاستلام أي مبلغ',
            style: TextStyle(fontSize: 11.5, color: colors.textHint),
          ),
        ],
      ),
    );
  }

  Widget _keypad(BuildContext context) {
    const keys = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '.', '0', 'back'];
    return GridView.count(
      crossAxisCount: 3,
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      childAspectRatio: 2.0,
      mainAxisSpacing: AppSpacing.xs,
      crossAxisSpacing: AppSpacing.lg,
      children: keys.map((k) => _key(context, k)).toList(),
    );
  }

  Widget _key(BuildContext context, String k) {
    final colors = context.appColors;
    final isDot = k == '.';
    final isBack = k == 'back';
    final disabled = isDot && _currency != 'USD';
    return Material(
      color: Colors.transparent,
      child: InkWell(
        borderRadius: BorderRadius.circular(AppRadius.md),
        onTap: disabled ? null : () => _onKey(k),
        child: Center(
          child: isBack
              ? Icon(Icons.backspace_outlined,
                  size: 22, color: colors.textPrimary)
              : Text(
                  k,
                  style: TextStyle(
                    fontSize: 26,
                    fontWeight: FontWeight.w700,
                    color: disabled
                        ? colors.textHint.withValues(alpha: 0.4)
                        : colors.textPrimary,
                  ),
                ),
        ),
      ),
    );
  }

  // ───────── Active: contactless waiting animation ─────────
  Widget _waitingView(BuildContext context) {
    final colors = context.appColors;
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: AppSpacing.xl),
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          SizedBox(
            width: 200,
            height: 200,
            child: Stack(
              alignment: Alignment.center,
              children: [
                _wave(context, 200, 0),
                _wave(context, 200, 800),
                _wave(context, 200, 1600),
                Container(
                  width: 108,
                  height: 108,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    gradient: LinearGradient(
                      colors: colors.cardGradientVisa,
                      begin: Alignment.topLeft,
                      end: Alignment.bottomRight,
                    ),
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withValues(alpha: 0.3),
                        blurRadius: 28,
                        offset: const Offset(0, 10),
                      ),
                    ],
                  ),
                  child: Transform.rotate(
                    angle: -0.785398,
                    child: const Icon(Iconsax.wifi, color: Colors.white, size: 48),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: AppSpacing.xl),
          Container(
            padding:
                const EdgeInsets.symmetric(horizontal: AppSpacing.md, vertical: 6),
            decoration: BoxDecoration(
              color: colors.successLight,
              borderRadius: BorderRadius.circular(AppRadius.pill),
            ),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Container(
                  width: 8,
                  height: 8,
                  decoration: BoxDecoration(
                      color: colors.success, shape: BoxShape.circle),
                )
                    .animate(onPlay: (c) => c.repeat(reverse: true))
                    .fadeIn(duration: 700.ms)
                    .then()
                    .fadeOut(duration: 700.ms),
                const SizedBox(width: 6),
                Text('جاهز للاستلام',
                    style: TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w700,
                        color: colors.success)),
              ],
            ),
          ),
          const SizedBox(height: AppSpacing.md),
          Text(
            'قرّب هاتف الدافع من هاتفك\nليتمّ الدفع فوراً',
            textAlign: TextAlign.center,
            style: TextStyle(
                fontSize: 13.5, color: colors.textSecondary, height: 1.6),
          ),
        ],
      ),
    );
  }

  Widget _wave(BuildContext context, double size, int delayMs) {
    final colors = context.appColors;
    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        color: colors.primary.withValues(alpha: 0.10),
      ),
    )
        .animate(onPlay: (c) => c.repeat())
        .scaleXY(
            begin: 0.5,
            end: 1.0,
            duration: 2400.ms,
            curve: Curves.easeOut,
            delay: delayMs.ms)
        .fadeOut(duration: 2400.ms, curve: Curves.easeOut, delay: delayMs.ms);
  }
}
