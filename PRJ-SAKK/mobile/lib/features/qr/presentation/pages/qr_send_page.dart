import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:go_router/go_router.dart';
import 'package:iconsax/iconsax.dart';

import '../../../../core/network/api_client.dart';
import '../../../../core/theme/app_colors.dart';
import '../../../../core/utils/money_formatter.dart';
import '../../../../core/widgets/app_ui.dart';
import '../../../wallets/data/repositories/wallet_repository.dart';
import '../../../wallets/data/models/wallet_model.dart';
import '../../../transactions/data/repositories/transaction_repository.dart';
import '../../../transactions/data/models/transaction_model.dart';
import '../../../transactions/data/receipt_service.dart';
import '../../../transfer/data/repositories/transfer_repository.dart';
import '../../../transfer/data/nfc_reader.dart';
import '../../../../shared/widgets/pin_prompt.dart';

/// Send money — recipient (grid: QR / contacts) → amount (custom keypad) →
/// review → confirm. NFC is automatic: when the recipient is broadcasting
/// (POS-style), tapping the two phones instantly shows a "pay X to {name}"
/// confirm/cancel sheet. Smooth, keyboard-free amount entry.
class QRSendPage extends ConsumerStatefulWidget {
  final String? initialIdentifier;

  /// When launched from an NFC tap, the recipient's broadcast payment.
  final NfcPayment? nfcPayment;

  const QRSendPage({super.key, this.initialIdentifier, this.nfcPayment});

  @override
  ConsumerState<QRSendPage> createState() => _QRSendPageState();
}

class _QRSendPageState extends ConsumerState<QRSendPage> {
  final _identifierController = TextEditingController();
  final _amountController = TextEditingController();
  final _noteController = TextEditingController();

  int _step = 0;
  String _currency = 'USD';
  Map<String, dynamic>? _recipient;
  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    final initial = widget.initialIdentifier;
    if (initial != null && initial.trim().isNotEmpty) {
      _identifierController.text = initial.trim();
      WidgetsBinding.instance.addPostFrameCallback((_) => _lookup());
    }
    final nfc = widget.nfcPayment;
    if (nfc != null) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        ref.read(pendingNfcPaymentProvider.notifier).state = null;
        _onNfcPayment(nfc);
      });
    }
  }

  @override
  void dispose() {
    _identifierController.dispose();
    _amountController.dispose();
    _noteController.dispose();
    super.dispose();
  }

  double get _amount =>
      Money.parseAmount(_amountController.text, currency: _currency) ?? 0;

  WalletModel? _walletFor(List<WalletModel> wallets, String currency) {
    for (final w in wallets) {
      if (w.currency == currency) return w;
    }
    return null;
  }

  void _snack(String msg, {Color color = AppColors.error}) {
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

  String _parseSakk(String raw) {
    var v = raw.trim();
    if (v.toUpperCase().startsWith('SAKK:')) v = v.substring(5);
    return v.trim();
  }

  Future<void> _lookup([String? value]) async {
    final id = _parseSakk(value ?? _identifierController.text);
    if (id.isEmpty) {
      _snack('أدخل رقم الحساب (يبدأ بـ SK)', color: AppColors.warning);
      return;
    }
    setState(() => _isLoading = true);
    try {
      final recipient =
          await ref.read(transferRepositoryProvider).lookupRecipient(id);
      setState(() {
        _recipient = recipient;
        _step = 1;
      });
    } catch (e) {
      _snack(e.toString());
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  static String? paymentUuidFrom(String raw) {
    final v = raw.trim();
    final lower = v.toLowerCase();
    if (lower.startsWith('sakkpay:')) return v.substring(8).trim();
    // Any deep link / URL containing /pay/{uuid}
    //   sakk://pay/{uuid}  •  https://host/pay/{uuid}
    final pathMatch =
        RegExp(r'/pay/([0-9a-fA-F-]{8,})').firstMatch(v);
    if (pathMatch != null) return pathMatch.group(1);
    // A bare uuid (e.g. scanned plain).
    if (RegExp(r'^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$')
        .hasMatch(v)) {
      return v;
    }
    return null;
  }

  Future<void> _scanQr() async {
    final result = await context.push<String>('/scan');
    if (!mounted || result == null) return;
    final raw = result.trim();
    final payUuid = paymentUuidFrom(raw);
    if (payUuid != null) {
      context.push('/pay-request/$payUuid');
      return;
    }
    await _lookup(raw);
  }

  String _friendly(Object e) =>
      e is ApiException ? e.message : 'تعذّر تنفيذ العملية، حاول مجدداً';

  // ── NFC payment (launched by a tap) ─────────────────────────────────────
  // The recipient broadcasts an NDEF tag; the OS auto-launches us here with
  // {account, amount, name}. We look the recipient up and show a confirm sheet.
  Future<void> _onNfcPayment(NfcPayment payment) async {
    // Don't interrupt an in-flight transfer or an open review.
    if (_isLoading || _step == 2) return;

    setState(() => _isLoading = true);
    Map<String, dynamic>? recipient;
    try {
      recipient = await ref
          .read(transferRepositoryProvider)
          .lookupRecipient(payment.account);
    } catch (e) {
      if (mounted) {
        setState(() => _isLoading = false);
        _snack(_friendly(e));
      }
      return;
    }
    if (!mounted) return;

    setState(() {
      _recipient = recipient;
      if (payment.currency != null) _currency = payment.currency!;
      if (payment.hasAmount) {
        final a = payment.amount!;
        _amountController.text = a == a.roundToDouble()
            ? a.toStringAsFixed(0)
            : a.toStringAsFixed(2);
      }
      _step = 1;
      _isLoading = false;
    });

    if (payment.hasAmount) {
      await _showNfcConfirm();
    } else {
      _snack('أدخل المبلغ الذي تريد دفعه', color: AppColors.warning);
    }
  }

  Future<void> _showNfcConfirm() async {
    final colors = context.appColors;
    final name = (_recipient?['name'] ?? '').toString();
    final account = (_recipient?['account_number'] ?? '').toString();
    final wallets = ref.read(walletsProvider).valueOrNull ?? const <WalletModel>[];
    final source = _walletFor(wallets, _currency);
    final insufficient = source == null || _amount > source.availableBalance;

    await showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      backgroundColor: colors.surface,
      shape: const RoundedRectangleBorder(
          borderRadius:
              BorderRadius.vertical(top: Radius.circular(AppRadius.xl))),
      builder: (ctx) => SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(AppSpacing.xl),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: 40,
                height: 4,
                decoration: BoxDecoration(
                    color: colors.inputBackground,
                    borderRadius: BorderRadius.circular(2)),
              ),
              const SizedBox(height: AppSpacing.lg),
              Container(
                width: 64,
                height: 64,
                decoration: BoxDecoration(
                    color: colors.primaryLight, shape: BoxShape.circle),
                child: Icon(Iconsax.wifi,
                    color: colors.primary, size: 32),
              ),
              const SizedBox(height: AppSpacing.md),
              Text('تأكيد الدفع عبر NFC',
                  style: TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.w700,
                      color: colors.textPrimary)),
              const SizedBox(height: AppSpacing.lg),
              Directionality(
                textDirection: TextDirection.ltr,
                child: Text(Money.format(_amount, _currency),
                    style: TextStyle(
                        fontSize: 32,
                        fontWeight: FontWeight.w800,
                        color: colors.primary)),
              ),
              const SizedBox(height: AppSpacing.xs),
              Text('إلى ${name.isEmpty ? account : name}',
                  textAlign: TextAlign.center,
                  style: TextStyle(
                      fontSize: 14, color: colors.textSecondary)),
              if (name.isNotEmpty && account.isNotEmpty) ...[
                const SizedBox(height: 2),
                Directionality(
                  textDirection: TextDirection.ltr,
                  child: Text(account,
                      style: TextStyle(
                          fontSize: 12, color: colors.textHint)),
                ),
              ],
              const SizedBox(height: AppSpacing.lg),
              if (insufficient) ...[
                Container(
                  padding: const EdgeInsets.all(AppSpacing.md),
                  decoration: BoxDecoration(
                      color: colors.warning.withValues(alpha: 0.12),
                      borderRadius: BorderRadius.circular(AppRadius.md)),
                  child: Row(children: [
                    Icon(Iconsax.warning_2, size: 16, color: colors.warning),
                    const SizedBox(width: AppSpacing.sm),
                    Expanded(
                        child: Text('الرصيد غير كافٍ لإتمام هذا الدفع',
                            style: TextStyle(
                                fontSize: 12.5,
                                color: colors.warning,
                                fontWeight: FontWeight.w600))),
                  ]),
                ),
                const SizedBox(height: AppSpacing.md),
              ],
              Row(children: [
                Expanded(
                  child: AppButton(
                    label: 'إلغاء',
                    variant: AppButtonVariant.secondary,
                    onPressed: () => Navigator.pop(ctx),
                  ),
                ),
                const SizedBox(width: AppSpacing.md),
                Expanded(
                  child: AppButton(
                    label: 'تأكيد الدفع',
                    icon: Iconsax.tick_circle,
                    onPressed: insufficient
                        ? null
                        : () {
                            Navigator.pop(ctx);
                            _confirm();
                          },
                  ),
                ),
              ]),
            ],
          ),
        ),
      ),
    );
  }

  // ── Custom keypad input (keyboard-free amount entry) ──────────────────
  void _onKey(String k) {
    var t = _amountController.text;
    if (k == 'back') {
      if (t.isNotEmpty) t = t.substring(0, t.length - 1);
    } else if (k == '.') {
      if (_currency != 'USD' || t.contains('.')) return;
      t = t.isEmpty ? '0.' : '$t.';
    } else {
      // digit
      var candidate = (t == '0') ? k : t + k;
      if (_currency == 'USD' && candidate.contains('.')) {
        final parts = candidate.split('.');
        if (parts.length == 2 && parts[1].length > 2) return; // max 2 decimals
      }
      if (candidate.replaceAll('.', '').length > 12) return;
      t = candidate;
    }
    _amountController.text = t;
    setState(() {});
  }

  void _goReview(List<WalletModel> wallets) {
    final source = _walletFor(wallets, _currency);
    if (_amount <= 0) {
      _snack('أدخل مبلغاً صالحاً للتحويل', color: AppColors.warning);
      return;
    }
    if (source == null || _amount > source.availableBalance) {
      _snack('الرصيد غير كافٍ لإتمام هذا التحويل', color: AppColors.warning);
      return;
    }
    setState(() => _step = 2);
  }

  Future<void> _confirm() async {
    // Second factor: the server requires a PIN for every transfer (SEC H1).
    final pin = await askTransactionPin(context, title: 'تأكيد التحويل برمز PIN');
    if (pin == null || pin.isEmpty) return;

    setState(() => _isLoading = true);
    try {
      final result = await ref.read(transferRepositoryProvider).sendTransfer(
            identifier: _recipient?['account_number']?.toString() ??
                _parseSakk(_identifierController.text),
            amount: _amount,
            currency: _currency,
            pin: pin,
            note: _noteController.text,
          );

      ref.invalidate(walletsProvider);
      ref.invalidate(recentTransactionsProvider);

      if (mounted) _showSuccess(result);
    } catch (e) {
      _snack(e.toString());
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  void _showSuccess(Map<String, dynamic> result) {
    final amount = (result['amount'] as num?)?.toDouble() ?? _amount;
    final currency = (result['currency'] ?? _currency).toString();
    final name = (_recipient?['name'] ?? '').toString();
    final account = _recipient?['account_number']?.toString();

    TransactionModel? tx;
    if (result['transaction'] is Map) {
      try {
        tx = TransactionModel.fromJson(
            Map<String, dynamic>.from(result['transaction']));
      } catch (_) {}
    }

    final colors = context.appColors;
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (ctx) => AlertDialog(
        backgroundColor: colors.surface,
        shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(AppRadius.xl)),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 72,
              height: 72,
              decoration: BoxDecoration(
                  color: colors.successLight, shape: BoxShape.circle),
              child: Icon(Iconsax.tick_circle,
                  color: colors.success, size: 38),
            ).animate().scale(
                begin: const Offset(0.5, 0.5), curve: Curves.easeOutBack),
            const SizedBox(height: AppSpacing.lg),
            Text('تم الإرسال بنجاح',
                style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.w700,
                    color: colors.textPrimary)),
            const SizedBox(height: AppSpacing.sm),
            Directionality(
              textDirection: TextDirection.ltr,
              child: Text(Money.format(amount, currency),
                  style: TextStyle(
                      fontSize: 24,
                      fontWeight: FontWeight.w800,
                      color: colors.success)),
            ),
            if (name.isNotEmpty) ...[
              const SizedBox(height: 2),
              Text('إلى $name',
                  textAlign: TextAlign.center,
                  style: TextStyle(
                      fontSize: 13, color: colors.textSecondary)),
            ],
            const SizedBox(height: AppSpacing.xl),
            if (tx != null) ...[
              AppButton(
                label: 'تحميل الإيصال (PDF)',
                icon: Iconsax.document_download,
                variant: AppButtonVariant.secondary,
                onPressed: () => ReceiptService.share(
                  tx!,
                  counterpartyName: name,
                  counterpartyAccount: account,
                ),
              ),
              const SizedBox(height: AppSpacing.sm),
            ],
            AppButton(
              label: 'تم',
              onPressed: () {
                Navigator.pop(ctx);
                if (context.canPop()) {
                  context.pop();
                } else {
                  context.go('/dashboard');
                }
              },
            ),
          ],
        ),
      ),
    );
  }

  void _back() {
    if (_step > 0) {
      setState(() => _step -= 1);
    } else if (context.canPop()) {
      context.pop();
    } else {
      context.go('/dashboard');
    }
  }

  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    final walletsAsync = ref.watch(walletsProvider);

    return PopScope(
      canPop: _step == 0,
      onPopInvokedWithResult: (didPop, result) {
        if (didPop) return;
        _back();
      },
      child: AppScaffold(
        title: _step == 0
            ? 'إرسال أموال'
            : (_step == 1 ? 'المبلغ' : 'مراجعة التحويل'),
        onBack: _back,
        body: walletsAsync.when(
          loading: () => Center(
              child: CircularProgressIndicator(color: colors.primary)),
          error: (e, _) => EmptyState(
            icon: Iconsax.warning_2,
            title: 'تعذّر تحميل المحفظة',
            subtitle: e.toString(),
            actionLabel: 'إعادة المحاولة',
            onAction: () => ref.invalidate(walletsProvider),
          ),
          data: (wallets) {
            switch (_step) {
              case 0:
                return _buildRecipientStep();
              case 1:
                return _buildAmountStep(wallets);
              default:
                return _buildReviewStep();
            }
          },
        ),
      ),
    );
  }

  // ════════════════════ Step 0 — recipient (grid) ════════════════════
  Widget _buildRecipientStep() {
    final colors = context.appColors;
    return SingleChildScrollView(
      padding: const EdgeInsets.symmetric(
          horizontal: AppSpacing.xl, vertical: AppSpacing.xl),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Text('إلى مَن تريد الإرسال؟',
              style: TextStyle(
                  fontSize: 22,
                  fontWeight: FontWeight.w800,
                  color: colors.textPrimary)),
          const SizedBox(height: AppSpacing.xs),
          Text('أدخل رقم الحساب أو اختر طريقة',
              style: TextStyle(fontSize: 13.5, color: colors.textSecondary)),
          const SizedBox(height: AppSpacing.xl),

          // Primary path: account number field + action.
          Container(
            padding: const EdgeInsets.all(AppSpacing.lg),
            decoration: BoxDecoration(
              color: colors.surface,
              borderRadius: BorderRadius.circular(AppRadius.xl),
              border: Border.all(color: colors.textHint.withValues(alpha: 0.18)),
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
                TextField(
                  controller: _identifierController,
                  textDirection: TextDirection.ltr,
                  textCapitalization: TextCapitalization.characters,
                  textInputAction: TextInputAction.search,
                  onSubmitted: (_) => _lookup(),
                  style: const TextStyle(
                      fontSize: 20,
                      fontWeight: FontWeight.w700,
                      letterSpacing: 2),
                  decoration: _input(
                      hint: 'SK00000000',
                      icon: Iconsax.hashtag,
                      hintLetterSpacing: 2),
                ),
                const SizedBox(height: AppSpacing.md),
                AppButton(
                  label: 'بحث ومتابعة',
                  icon: Iconsax.arrow_left_2,
                  loading: _isLoading,
                  onPressed: () => _lookup(),
                ),
              ],
            ),
          ),
          const SizedBox(height: AppSpacing.xl),

          Row(children: [
            Expanded(child: Divider(color: colors.textHint.withValues(alpha: 0.25))),
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: AppSpacing.md),
              child: Text('أو',
                  style: TextStyle(
                      fontSize: 12,
                      color: colors.textHint,
                      fontWeight: FontWeight.w600)),
            ),
            Expanded(child: Divider(color: colors.textHint.withValues(alpha: 0.25))),
          ]),
          const SizedBox(height: AppSpacing.xl),

          // Grid of alternative methods (flex tiles).
          GridView.count(
            crossAxisCount: 2,
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            mainAxisSpacing: AppSpacing.md,
            crossAxisSpacing: AppSpacing.md,
            childAspectRatio: 1.15,
            children: [
              _gridMethod(
                icon: Iconsax.scan_barcode,
                title: 'مسح رمز QR',
                subtitle: 'امسح رمز المستلم',
                onTap: _isLoading ? null : _scanQr,
              ),
              _gridMethod(
                icon: Iconsax.profile_2user,
                title: 'جهات الاتصال',
                subtitle: 'حوّل لأصدقائك',
                onTap: _isLoading
                    ? null
                    : () => context.push('/contacts-transfer'),
              ),
            ],
          ),
          const SizedBox(height: AppSpacing.lg),
          _nfcHint(),
        ],
      ),
    );
  }

  Widget _nfcHint() {
    final colors = context.appColors;
    return Container(
      padding: const EdgeInsets.all(AppSpacing.md),
      decoration: BoxDecoration(
        color: colors.primaryLight.withValues(alpha: 0.5),
        borderRadius: BorderRadius.circular(AppRadius.md),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(Iconsax.wifi, size: 18, color: colors.primary),
          const SizedBox(width: AppSpacing.sm),
          Expanded(
            child: Text(
              'الدفع بالـ NFC تلقائي: إذا فعّل المستلم "استلام NFC"، قرّب هاتفك من هاتفه ليظهر تأكيد الدفع فوراً.',
              style:
                  TextStyle(fontSize: 12.5, color: colors.primary, height: 1.5),
            ),
          ),
        ],
      ),
    );
  }

  Widget _gridMethod({
    required IconData icon,
    required String title,
    required String subtitle,
    VoidCallback? onTap,
  }) {
    final colors = context.appColors;
    return AppCard(
      onTap: onTap,
      padding: const EdgeInsets.all(AppSpacing.lg),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Container(
            width: 48,
            height: 48,
            decoration: BoxDecoration(
              gradient: LinearGradient(
                  colors: colors.cardGradientVisa),
              borderRadius: BorderRadius.circular(AppRadius.lg),
            ),
            child: Icon(icon, color: Colors.white, size: 24),
          ),
          Column(
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
                      fontSize: 11.5, color: colors.textSecondary)),
            ],
          ),
        ],
      ),
    ).animate().fadeIn(duration: 350.ms).slideY(begin: 0.1, end: 0);
  }

  // ════════════════════ Step 1 — amount (keypad) ════════════════════
  Widget _buildAmountStep(List<WalletModel> wallets) {
    final colors = context.appColors;
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final source = _walletFor(wallets, _currency);
    final balance = source?.availableBalance ?? 0;
    final exceeds = _amount > balance;
    final display = _amountController.text.isEmpty ? '0' : _amountController.text;

    return Column(
      children: [
        Expanded(
          child: SingleChildScrollView(
            padding: const EdgeInsets.fromLTRB(
                AppSpacing.xl, AppSpacing.lg, AppSpacing.xl, AppSpacing.md),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                _recipientCard(),
                const SizedBox(height: AppSpacing.lg),
                _currencyToggle(),
                const SizedBox(height: AppSpacing.xxl),

                // Big amount display (driven by the keypad — no system keyboard).
                Directionality(
                  textDirection: TextDirection.ltr,
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      if (_currency == 'USD')
                        Padding(
                          padding: const EdgeInsets.only(top: 10, right: 4),
                          child: Text('\$',
                              style: TextStyle(
                                  fontSize: 28,
                                  fontWeight: FontWeight.w800,
                                  color: exceeds
                                      ? colors.error
                                      : colors.primary)),
                        ),
                      Flexible(
                        child: FittedBox(
                          fit: BoxFit.scaleDown,
                          child: Text(
                            display,
                            maxLines: 1,
                            style: TextStyle(
                              fontSize: 56,
                              fontWeight: FontWeight.w800,
                              color: exceeds
                                  ? colors.error
                                  : (_amountController.text.isEmpty
                                      ? colors.textHint
                                      : colors.textPrimary),
                            ),
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: AppSpacing.xs),
                Text(Money.currencyLabel(_currency),
                    textAlign: TextAlign.center,
                    style: TextStyle(
                        fontSize: 12.5, color: colors.textSecondary)),
                const SizedBox(height: AppSpacing.md),

                // Balance + max
                Center(
                  child: Container(
                    padding: const EdgeInsets.symmetric(
                        vertical: AppSpacing.xs, horizontal: AppSpacing.md),
                    decoration: BoxDecoration(
                      color: exceeds
                          ? colors.errorLight
                          : colors.inputBackground,
                      borderRadius: BorderRadius.circular(AppRadius.pill),
                    ),
                    child: Row(mainAxisSize: MainAxisSize.min, children: [
                      Icon(Iconsax.wallet,
                          size: 14,
                          color: exceeds
                              ? colors.error
                              : colors.textSecondary),
                      const SizedBox(width: 6),
                      Text('الرصيد ',
                          style: TextStyle(
                              fontSize: 12,
                              color: exceeds
                                  ? colors.error
                                  : colors.textSecondary)),
                      Directionality(
                        textDirection: TextDirection.ltr,
                        child: Text(Money.format(balance, _currency),
                            style: TextStyle(
                                fontSize: 12,
                                fontWeight: FontWeight.w700,
                                color: exceeds
                                    ? colors.error
                                    : colors.primary)),
                      ),
                      const SizedBox(width: 8),
                      GestureDetector(
                        onTap: () {
                          _amountController.text = _currency == 'USD'
                              ? balance.toStringAsFixed(2)
                              : balance.toStringAsFixed(0);
                          setState(() {});
                        },
                        child: Container(
                          padding: const EdgeInsets.symmetric(
                              horizontal: AppSpacing.sm, vertical: 2),
                          decoration: BoxDecoration(
                            color: isDark ? colors.surface : colors.primary,
                            borderRadius: BorderRadius.circular(AppRadius.pill),
                          ),
                          child: Text('الكل',
                              style: TextStyle(
                                  fontSize: 11,
                                  fontWeight: FontWeight.w700,
                                  color: isDark
                                      ? colors.textPrimary
                                      : Colors.white)),
                        ),
                      ),
                    ]),
                  ),
                ),
                const SizedBox(height: AppSpacing.lg),

                // Quick amounts (flex wrap)
                Wrap(
                  alignment: WrapAlignment.center,
                  spacing: AppSpacing.sm,
                  runSpacing: AppSpacing.sm,
                  children: (_currency == 'USD'
                          ? <double>[5, 10, 25, 50, 100]
                          : <double>[250, 500, 1000, 5000])
                      .map((v) => _quickAmountChip(v))
                      .toList(),
                ),
                const SizedBox(height: AppSpacing.lg),

                // Optional note
                TextField(
                  controller: _noteController,
                  maxLength: 140,
                  textAlign: TextAlign.center,
                  style: TextStyle(
                      fontSize: 14, color: colors.textPrimary),
                  decoration: _input(
                      hint: 'أضف ملاحظة (اختياري)', icon: Iconsax.note_1),
                ),
              ],
            ),
          ),
        ),

        // Custom numeric keypad + continue (pinned at bottom).
        Container(
          padding: const EdgeInsets.fromLTRB(
              AppSpacing.xl, AppSpacing.md, AppSpacing.xl, AppSpacing.lg),
          decoration: BoxDecoration(
            color: colors.surface,
            borderRadius: const BorderRadius.vertical(
                top: Radius.circular(AppRadius.xl)),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.05),
                blurRadius: 20,
                offset: const Offset(0, -4),
              ),
            ],
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              _keypad(),
              const SizedBox(height: AppSpacing.md),
              AppButton(
                  label: 'مراجعة التحويل',
                  icon: Iconsax.arrow_left_2,
                  onPressed: () => _goReview(wallets)),
            ],
          ),
        ),
      ],
    );
  }

  Widget _keypad() {
    final keys = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '.', '0', 'back'];
    return GridView.count(
      crossAxisCount: 3,
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      childAspectRatio: 2.1,
      mainAxisSpacing: AppSpacing.xs,
      crossAxisSpacing: AppSpacing.md,
      children: keys.map(_keypadKey).toList(),
    );
  }

  Widget _keypadKey(String k) {
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

  // ════════════════════ Step 2 — review ════════════════════
  Widget _buildReviewStep() {
    final colors = context.appColors;
    final note = _noteController.text.trim();
    return SingleChildScrollView(
      padding: const EdgeInsets.symmetric(
          horizontal: AppSpacing.xl, vertical: AppSpacing.xl),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Container(
            padding: const EdgeInsets.all(AppSpacing.xxl),
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
            child: Column(
              children: [
                const Text('أنت ترسل',
                    style: TextStyle(color: Colors.white70, fontSize: 13)),
                const SizedBox(height: AppSpacing.sm),
                Directionality(
                  textDirection: TextDirection.ltr,
                  child: Text(Money.format(_amount, _currency),
                      style: const TextStyle(
                          color: Colors.white,
                          fontSize: 38,
                          fontWeight: FontWeight.w800)),
                ),
                const SizedBox(height: AppSpacing.sm),
                Container(
                  padding: const EdgeInsets.symmetric(
                      horizontal: AppSpacing.md, vertical: AppSpacing.xs),
                  decoration: BoxDecoration(
                      color: Colors.white.withValues(alpha: 0.2),
                      borderRadius: BorderRadius.circular(AppRadius.pill)),
                  child: Text(Money.currencyLabel(_currency),
                      style: const TextStyle(
                          color: Colors.white,
                          fontSize: 12,
                          fontWeight: FontWeight.w600)),
                ),
              ],
            ),
          ).animate().fadeIn(duration: 400.ms).scale(
              begin: const Offset(0.96, 0.96)),
          const SizedBox(height: AppSpacing.xl),
          _recipientCard(),
          const SizedBox(height: AppSpacing.lg),

          AppCard(
            child: Column(
              children: [
                _reviewRow('العملة', Money.currencyLabel(_currency)),
                const Divider(height: AppSpacing.xl),
                _reviewRow('المبلغ', Money.format(_amount, _currency),
                    ltr: true),
                if (note.isNotEmpty) ...[
                  const Divider(height: AppSpacing.xl),
                  _reviewRow('ملاحظة', note),
                ],
                const Divider(height: AppSpacing.xl),
                _reviewRow('الرسوم', 'مجاني', valueColor: colors.success),
              ],
            ),
          ),
          const SizedBox(height: AppSpacing.xl),
          AppButton(
            label: 'تأكيد الإرسال',
            icon: Iconsax.send_1,
            loading: _isLoading,
            onPressed: _confirm,
          ),
          const SizedBox(height: AppSpacing.sm),
          Center(
            child: TextButton(
              onPressed: _isLoading ? null : () => setState(() => _step = 1),
              style:
                  TextButton.styleFrom(foregroundColor: colors.textSecondary),
              child: const Text('تعديل المعلومات'),
            ),
          ),
        ],
      ),
    );
  }

  // ── Shared widgets ────────────────────────────────────────────────────
  Widget _recipientCard() {
    final colors = context.appColors;
    final name = (_recipient?['name'] ?? '').toString();
    final account = (_recipient?['account_number'] ?? '').toString();
    final initials = (_recipient?['initials'] ?? '').toString();
    return AppCard(
      padding: const EdgeInsets.all(AppSpacing.md),
      child: Row(children: [
        Container(
          width: 50,
          height: 50,
          decoration: BoxDecoration(
              color: colors.primaryLight,
              borderRadius: BorderRadius.circular(AppRadius.lg)),
          alignment: Alignment.center,
          child: Text(initials,
              style: TextStyle(
                  color: colors.primary,
                  fontWeight: FontWeight.w800,
                  fontSize: 17)),
        ),
        const SizedBox(width: AppSpacing.md),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(name,
                  style: TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.w700,
                      color: colors.textPrimary)),
              const SizedBox(height: 4),
              Row(children: [
                Container(
                  width: 6,
                  height: 6,
                  decoration: BoxDecoration(
                      color: colors.success, shape: BoxShape.circle),
                ),
                const SizedBox(width: 6),
                Text(account,
                    textDirection: TextDirection.ltr,
                    style: TextStyle(
                        fontSize: 12,
                        color: colors.textSecondary,
                        fontFamily: 'monospace',
                        letterSpacing: 1)),
              ]),
            ],
          ),
        ),
        Container(
          width: 28,
          height: 28,
          decoration: BoxDecoration(
              color: colors.successLight,
              borderRadius: BorderRadius.circular(AppRadius.sm)),
          child: Icon(Iconsax.verify5, color: colors.success, size: 16),
        ),
      ]),
    );
  }

  Widget _currencyToggle() {
    final colors = context.appColors;
    return Container(
      padding: const EdgeInsets.all(AppSpacing.xs),
      decoration: BoxDecoration(
          color: colors.inputBackground,
          borderRadius: BorderRadius.circular(AppRadius.md)),
      child: Row(children: [
        Expanded(child: _currencyTab('USD', 'دولار أمريكي')),
        const SizedBox(width: AppSpacing.xs),
        Expanded(child: _currencyTab('SYP', 'ليرة سورية')),
      ]),
    );
  }

  Widget _currencyTab(String code, String label) {
    final colors = context.appColors;
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final selected = _currency == code;
    final onPrimary = isDark ? colors.background : Colors.white;
    return GestureDetector(
      onTap: () => setState(() {
        _currency = code;
        _amountController.clear();
      }),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 180),
        padding: const EdgeInsets.symmetric(vertical: AppSpacing.md),
        decoration: BoxDecoration(
          color: selected ? colors.primary : Colors.transparent,
          borderRadius: BorderRadius.circular(AppRadius.sm + 2),
        ),
        child: Column(children: [
          Text(code,
              style: TextStyle(
                  fontSize: 15,
                  fontWeight: FontWeight.w700,
                  color: selected ? onPrimary : colors.textPrimary)),
          Text(label,
              style: TextStyle(
                  fontSize: 11,
                  color: selected
                      ? onPrimary.withValues(alpha: 0.9)
                      : colors.textSecondary)),
        ]),
      ),
    );
  }

  Widget _quickAmountChip(double value) {
    final colors = context.appColors;
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final selected = _amount == value;
    final onPrimary = isDark ? colors.background : Colors.white;
    return GestureDetector(
      onTap: () {
        _amountController.text = _currency == 'USD'
            ? value.toStringAsFixed(0)
            : value.toStringAsFixed(0);
        setState(() {});
      },
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 150),
        padding: const EdgeInsets.symmetric(
            horizontal: AppSpacing.lg, vertical: AppSpacing.sm),
        decoration: BoxDecoration(
          color: selected ? colors.primary : colors.surface,
          borderRadius: BorderRadius.circular(AppRadius.pill),
          border: Border.all(
              color: selected
                  ? colors.primary
                  : colors.textHint.withValues(alpha: 0.25)),
        ),
        child: Directionality(
          textDirection: TextDirection.ltr,
          child: Text(
            _currency == 'USD'
                ? '\$${value.toStringAsFixed(0)}'
                : Money.number(value, _currency),
            style: TextStyle(
              fontSize: 13,
              fontWeight: FontWeight.w600,
              color: selected ? onPrimary : colors.textPrimary,
            ),
          ),
        ),
      ),
    );
  }

  Widget _reviewRow(String label, String value,
      {Color? valueColor, bool ltr = false}) {
    final colors = context.appColors;
    final valueWidget = Text(value,
        textAlign: TextAlign.end,
        style: TextStyle(
            fontSize: 14,
            fontWeight: FontWeight.w600,
            color: valueColor ?? colors.textPrimary));
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: AppSpacing.sm),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label,
              style: TextStyle(
                  fontSize: 14, color: colors.textSecondary)),
          Flexible(
            child: ltr
                ? Directionality(
                    textDirection: TextDirection.ltr, child: valueWidget)
                : valueWidget,
          ),
        ],
      ),
    );
  }

  InputDecoration _input({
    String? label,
    String? hint,
    IconData? icon,
    String? suffix,
    double? hintLetterSpacing,
  }) {
    final colors = context.appColors;
    OutlineInputBorder border(Color c, [double w = 1]) => OutlineInputBorder(
          borderRadius: BorderRadius.circular(AppRadius.md),
          borderSide: BorderSide(color: c, width: w),
        );
    return InputDecoration(
      labelText: label,
      hintText: hint,
      counterText: '',
      hintStyle: hintLetterSpacing != null
          ? TextStyle(
              color: colors.textHint.withValues(alpha: 0.5),
              letterSpacing: hintLetterSpacing,
              fontSize: 20)
          : null,
      prefixIcon: icon != null ? Icon(icon, color: colors.textHint) : null,
      suffixText: suffix,
      filled: true,
      fillColor: colors.surface,
      border: border(colors.textHint.withValues(alpha: 0.25)),
      enabledBorder: border(colors.textHint.withValues(alpha: 0.25)),
      focusedBorder: border(colors.primary, 1.4),
      contentPadding: const EdgeInsets.symmetric(
          horizontal: AppSpacing.lg, vertical: AppSpacing.lg),
    );
  }
}