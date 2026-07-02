import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:go_router/go_router.dart';
import 'package:iconsax/iconsax.dart';

import '../../../../core/theme/app_colors.dart';
import '../../../../core/utils/money_formatter.dart';
import '../../../../core/widgets/app_ui.dart';
import '../../../wallets/data/repositories/wallet_repository.dart';
import '../../../transactions/data/repositories/transaction_repository.dart';
import '../../../../shared/widgets/pin_prompt.dart';
import '../../data/repositories/payment_request_repository.dart';

/// Pay a payment request: requester + amount hero + note, with a single
/// clear "ادفع الآن" action and explicit status banners for non-payable states.
class PayRequestPage extends ConsumerStatefulWidget {
  final String uuid;
  const PayRequestPage({super.key, required this.uuid});

  @override
  ConsumerState<PayRequestPage> createState() => _PayRequestPageState();
}

class _PayRequestPageState extends ConsumerState<PayRequestPage> {
  Map<String, dynamic>? _req;
  bool _loading = true;
  bool _paying = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final req = await ref.read(paymentRequestRepositoryProvider).show(widget.uuid);
      if (mounted) setState(() => _req = req);
    } catch (e) {
      // Real backend message (e.g. "الطلب غير موجود").
      if (mounted) setState(() => _error = e.toString());
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _pay() async {
    // Second factor: the server requires a PIN to pay a request (SEC H1).
    final pin = await askTransactionPin(context, title: 'تأكيد الدفع برمز PIN');
    if (pin == null || pin.isEmpty) return;

    setState(() => _paying = true);
    try {
      await ref.read(paymentRequestRepositoryProvider).pay(widget.uuid, pin);
      ref.invalidate(walletsProvider);
      ref.invalidate(recentTransactionsProvider);
      if (mounted) _showSuccess();
    } catch (e) {
      // Surface the REAL backend message (insufficient balance, expired…).
      if (mounted) _snack(e.toString());
    } finally {
      if (mounted) setState(() => _paying = false);
    }
  }

  void _snack(String msg, {bool success = false}) {
    final colors = context.appColors;
    final color = success ? colors.success : colors.error;
    ScaffoldMessenger.of(context)
      ..hideCurrentSnackBar()
      ..showSnackBar(SnackBar(
        behavior: SnackBarBehavior.floating,
        backgroundColor: color,
        margin: const EdgeInsets.all(AppSpacing.lg),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.md)),
        content: Row(children: [
          Icon(success ? Iconsax.tick_circle : Iconsax.warning_2, color: Colors.white, size: 20),
          const SizedBox(width: AppSpacing.sm),
          Expanded(child: Text(msg, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w600))),
        ]),
      ));
  }

  void _showSuccess() {
    final amount = (_req!['amount'] as num).toDouble();
    final currency = _req!['currency'].toString();
    final merchantName = (_req!['merchant_name'] ?? '').toString();
    final name = merchantName.isNotEmpty
        ? merchantName
        : (_req!['requester']?['name'] ?? '').toString();
    final colors = context.appColors;
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (ctx) => AlertDialog(
        backgroundColor: colors.surface,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.xl)),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 64,
              height: 64,
              decoration: BoxDecoration(color: colors.successLight, borderRadius: BorderRadius.circular(AppRadius.lg)),
              child: Icon(Iconsax.tick_circle, color: colors.success, size: 32),
            ),
            const SizedBox(height: AppSpacing.lg),
            Text('تم الدفع بنجاح',
                style: TextStyle(fontSize: 18, fontWeight: FontWeight.w700, color: colors.textPrimary)),
            const SizedBox(height: AppSpacing.sm),
            Directionality(
              textDirection: TextDirection.ltr,
              child: Text(Money.format(amount, currency),
                  style: TextStyle(fontSize: 22, fontWeight: FontWeight.w800, color: colors.success)),
            ),
            if (name.isNotEmpty) ...[
              const SizedBox(height: 2),
              Text('إلى $name',
                  textAlign: TextAlign.center,
                  style: TextStyle(fontSize: 13, color: colors.textSecondary)),
            ],
            const SizedBox(height: AppSpacing.xl),
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

  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    return AppScaffold(
      title: 'دفع الطلب',
      body: Container(
        color: colors.surface,
        child: _loading
            ? Center(
                child: CircularProgressIndicator(color: colors.primary))
            : _error != null
                ? EmptyState(
                    icon: Iconsax.warning_2,
                    title: 'تعذّر تحميل الطلب',
                    subtitle: _error,
                    actionLabel: 'إعادة المحاولة',
                    onAction: _load,
                  )
                : _buildContent(_req!),
      ),
    );
  }

  Widget _buildContent(Map<String, dynamic> req) {
    final colors = context.appColors;
    final amount = (req['amount'] as num).toDouble();
    final currency = req['currency'].toString();
    final note = (req['note'] ?? '').toString();
    final status = req['status'].toString();
    final payable = req['is_payable'] == true;
    final isMine = req['is_mine'] == true;
    final merchantName = (req['merchant_name'] ?? '').toString();
    final isMerchant = merchantName.isNotEmpty;
    final requester = req['requester'] as Map<String, dynamic>?;
    final name = isMerchant ? merchantName : (requester?['name'] ?? '').toString();
    final account = (requester?['account_number'] ?? '').toString();
    final initials = (requester?['initials'] ?? '').toString();

    return SingleChildScrollView(
      padding: const EdgeInsets.fromLTRB(
          AppSpacing.xl, AppSpacing.lg, AppSpacing.xl, AppSpacing.xxxl),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          // Header
          Center(
            child: Column(
              children: [
                Container(
                  width: 72,
                  height: 72,
                  decoration: BoxDecoration(
                    gradient: LinearGradient(
                        colors: colors.cardGradientVisa),
                    shape: BoxShape.circle,
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withValues(alpha: 0.3),
                        blurRadius: 18,
                        offset: const Offset(0, 8),
                      ),
                    ],
                  ),
                  child: const Icon(Iconsax.money_recive,
                      color: Colors.white, size: 34),
                ),
                const SizedBox(height: AppSpacing.md),
                Text('طلب دفع',
                    style: TextStyle(
                        fontSize: 20,
                        fontWeight: FontWeight.w800,
                        color: colors.textPrimary)),
                const SizedBox(height: 2),
                Text('راجِع التفاصيل ثم أكمِل الدفع بأمان',
                    style:
                        TextStyle(fontSize: 13, color: colors.textSecondary)),
              ],
            ),
          ).animate().fadeIn(duration: 400.ms).scale(begin: const Offset(0.9, 0.9)),
          const SizedBox(height: AppSpacing.xxl),

          // Amount hero — big & clear.
          Container(
            padding: const EdgeInsets.symmetric(
                vertical: AppSpacing.xxl, horizontal: AppSpacing.lg),
            decoration: BoxDecoration(
              color: colors.surface,
              borderRadius: BorderRadius.circular(AppRadius.xl),
              border: Border.all(color: colors.primary.withValues(alpha: 0.15)),
              boxShadow: [
                BoxShadow(
                  color: colors.primary.withValues(alpha: 0.10),
                  blurRadius: 24,
                  offset: const Offset(0, 10),
                ),
              ],
            ),
            child: Column(
              children: [
                Text('المبلغ المطلوب',
                    style: TextStyle(fontSize: 13, color: colors.textSecondary)),
                const SizedBox(height: AppSpacing.sm),
                Directionality(
                  textDirection: TextDirection.ltr,
                  child: FittedBox(
                    fit: BoxFit.scaleDown,
                    child: Text(Money.format(amount, currency),
                        maxLines: 1,
                        style: TextStyle(
                            fontSize: 42,
                            fontWeight: FontWeight.w800,
                            color: colors.primary)),
                  ),
                ),
                const SizedBox(height: AppSpacing.sm),
                Container(
                  padding: const EdgeInsets.symmetric(
                      horizontal: AppSpacing.md, vertical: AppSpacing.xs),
                  decoration: BoxDecoration(
                      color: colors.primaryLight,
                      borderRadius: BorderRadius.circular(AppRadius.pill)),
                  child: Text(Money.currencyLabel(currency),
                      style: TextStyle(
                          fontSize: 12,
                          fontWeight: FontWeight.w600,
                          color: colors.primary)),
                ),
              ],
            ),
          ).animate().fadeIn(delay: 80.ms).scale(begin: const Offset(0.96, 0.96)),
          const SizedBox(height: AppSpacing.xl),

          // To whom — clear recipient.
          Align(
            alignment: AlignmentDirectional.centerStart,
            child: Text('تدفع إلى',
                style: TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w700,
                    color: colors.textPrimary)),
          ),
          const SizedBox(height: AppSpacing.sm),
          Container(
            padding: const EdgeInsets.all(AppSpacing.md),
            decoration: BoxDecoration(
              color: colors.surface,
              borderRadius: BorderRadius.circular(AppRadius.lg),
              border: Border.all(color: colors.inputBackground),
            ),
            child: Row(children: [
              isMerchant
                  ? Container(
                      width: 50,
                      height: 50,
                      decoration: BoxDecoration(
                        gradient: LinearGradient(
                            colors: colors.cardGradientVisa),
                        borderRadius: BorderRadius.circular(AppRadius.lg),
                      ),
                      child: const Icon(Iconsax.shop,
                          color: Colors.white, size: 24),
                    )
                  : _InitialsAvatar(initials: initials, size: 50),
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
                      Icon(
                          isMerchant ? Iconsax.building : Iconsax.card,
                          size: 13, color: colors.textSecondary),
                      const SizedBox(width: 5),
                      Text(
                          isMerchant ? 'منصة مدفوعة معتمدة' : account,
                          textDirection: TextDirection.ltr,
                          style: TextStyle(
                              fontSize: 12.5,
                              color: colors.textSecondary,
                              fontFamily:
                                  isMerchant ? null : 'monospace',
                              letterSpacing: isMerchant ? 0 : 1)),
                    ]),
                  ],
                ),
              ),
              Container(
                width: 30,
                height: 30,
                decoration: BoxDecoration(
                    color: isMerchant
                        ? colors.infoLight ?? colors.primaryLight
                        : colors.successLight,
                    borderRadius: BorderRadius.circular(AppRadius.sm)),
                child: Icon(
                    isMerchant ? Iconsax.shield_tick : Iconsax.verify5,
                    color: isMerchant ? colors.info ?? colors.primary : colors.success,
                    size: 17),
              ),
            ]),
          ),

          if (note.isNotEmpty) ...[
            const SizedBox(height: AppSpacing.md),
            Container(
              padding: const EdgeInsets.all(AppSpacing.md),
              decoration: BoxDecoration(
                color: colors.inputBackground,
                borderRadius: BorderRadius.circular(AppRadius.md),
              ),
              child: Row(children: [
                Icon(Iconsax.note_1, size: 18, color: colors.textSecondary),
                const SizedBox(width: AppSpacing.sm),
                Expanded(
                    child: Text(note,
                        style: TextStyle(
                            fontSize: 13, color: colors.textPrimary))),
              ]),
            ),
          ],
          const SizedBox(height: AppSpacing.xl),

          // Instructions / reassurance.
          Container(
            padding: const EdgeInsets.all(AppSpacing.lg),
            decoration: BoxDecoration(
              color: colors.primaryLight.withValues(alpha: 0.4),
              borderRadius: BorderRadius.circular(AppRadius.lg),
            ),
            child: Column(
              children: [
                _instructionRow(Iconsax.flash_1, 'تحويل فوري — يصل في الحال'),
                const SizedBox(height: AppSpacing.md),
                _instructionRow(
                    Iconsax.wallet_3, 'يُخصم المبلغ من محفظتك مباشرةً'),
                const SizedBox(height: AppSpacing.md),
                _instructionRow(
                    Iconsax.shield_tick, 'عملية آمنة ومجانية عبر صكّ'),
              ],
            ),
          ),
          const SizedBox(height: AppSpacing.xl),

          if (payable)
            AppButton(
              label: 'ادفع الآن · ${Money.format(amount, currency)}',
              icon: Iconsax.send_1,
              loading: _paying,
              onPressed: _pay,
            ).animate().fadeIn(delay: 140.ms)
          else
            _statusBanner(status, isMine).animate().fadeIn(delay: 140.ms),
        ],
      ),
    );
  }

  Widget _instructionRow(IconData icon, String text) {
    final colors = context.appColors;
    return Row(children: [
      Container(
        width: 32,
        height: 32,
        decoration: BoxDecoration(color: colors.surface, shape: BoxShape.circle),
        child: Icon(icon, color: colors.primary, size: 16),
      ),
      const SizedBox(width: AppSpacing.sm),
      Expanded(
        child: Text(text,
            style: TextStyle(
                fontSize: 13, color: colors.textPrimary, height: 1.3)),
      ),
    ]);
  }

  Widget _statusBanner(String status, bool isMine) {
    final (String label, StatusKind kind, IconData icon, String desc) = switch (status) {
      'paid' => ('مدفوع', StatusKind.success, Iconsax.tick_circle, 'تم دفع هذا الطلب مسبقاً.'),
      'cancelled' => ('ملغى', StatusKind.error, Iconsax.close_circle, 'تم إلغاء هذا الطلب من قِبل صاحبه.'),
      'expired' => ('منتهي الصلاحية', StatusKind.warning, Iconsax.clock, 'انتهت صلاحية هذا الطلب ولم يعد قابلاً للدفع.'),
      _ when isMine => ('طلبك أنت', StatusKind.info, Iconsax.info_circle, 'هذا طلبك أنت — شاركه ليُدفع لك.'),
      _ => ('غير متاح', StatusKind.neutral, Iconsax.info_circle, 'هذا الطلب غير قابل للدفع حالياً.'),
    };
    final colors = context.appColors;
    final color = _kindColor(kind);
    return AppCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(children: [
            Icon(icon, color: color, size: 22),
            const SizedBox(width: AppSpacing.sm),
            StatusBadge(label: label, kind: kind),
          ]),
          const SizedBox(height: AppSpacing.md),
          Text(desc, style: TextStyle(fontSize: 14, color: colors.textPrimary, height: 1.4)),
        ],
      ),
    );
  }

  Color _kindColor(StatusKind kind) {
    final colors = context.appColors;
    return switch (kind) {
      StatusKind.success => colors.success,
      StatusKind.error => colors.error,
      StatusKind.warning => colors.warning,
      StatusKind.info => colors.info,
      StatusKind.neutral => colors.textSecondary,
    };
  }
}

/// Monochrome initials avatar (primaryLight tile + primary text) — no gradient
/// scatter; gradients stay reserved for the balance card per the design system.
class _InitialsAvatar extends StatelessWidget {
  final String initials;
  final double size;
  const _InitialsAvatar({required this.initials, this.size = 48});

  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
        color: colors.primaryLight,
        borderRadius: BorderRadius.circular(AppRadius.lg),
      ),
      alignment: Alignment.center,
      child: Text(
        initials,
        style: TextStyle(color: colors.primary, fontWeight: FontWeight.w800, fontSize: size * 0.34),
      ),
    );
  }
}
