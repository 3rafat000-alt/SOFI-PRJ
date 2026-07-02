import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:iconsax/iconsax.dart';
import 'package:qr_flutter/qr_flutter.dart';
import 'package:share_plus/share_plus.dart';

import '../../../../core/network/api_client.dart';
import '../../../../core/theme/app_colors.dart';
import '../../../../core/utils/money_formatter.dart';
import '../../../../core/widgets/app_ui.dart';
import '../../data/repositories/payment_request_repository.dart';

/// My payment requests — a clean list with per-status badges and quick
/// actions (view QR / share link / cancel) for pending requests.
class MyPaymentRequestsPage extends ConsumerStatefulWidget {
  const MyPaymentRequestsPage({super.key});

  @override
  ConsumerState<MyPaymentRequestsPage> createState() => _MyPaymentRequestsPageState();
}

class _MyPaymentRequestsPageState extends ConsumerState<MyPaymentRequestsPage> {
  List<Map<String, dynamic>>? _items;
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    if (mounted) setState(() => _loading = true);
    try {
      final items = await ref.read(paymentRequestRepositoryProvider).list();
      if (!mounted) return;
      setState(() {
        _items = items;
        _error = null;
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e is ApiException ? e.message : 'تعذّر تحميل الطلبات';
        _loading = false;
      });
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
          Expanded(
              child: Text(msg,
                  maxLines: 4,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                      color: Colors.white, fontWeight: FontWeight.w600))),
        ]),
      ));
  }

  Future<void> _confirmCancel(String uuid) async {
    final colors = context.appColors;
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor: colors.surface,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.lg)),
        title: const Text('إلغاء الطلب؟', style: TextStyle(fontWeight: FontWeight.w700)),
        content: const Text('لن يتمكّن أحد من دفع هذا الطلب بعد إلغائه.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('تراجع')),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: Text('إلغاء الطلب', style: TextStyle(color: colors.error, fontWeight: FontWeight.w700)),
          ),
        ],
      ),
    );
    if (ok == true) _cancel(uuid);
  }

  Future<void> _cancel(String uuid) async {
    final items = _items;
    if (items == null) return;
    final idx = items.indexWhere((e) => e['uuid'].toString() == uuid);
    if (idx == -1) return;

    // Optimistically reflect the cancellation right away so the UI updates
    // instantly; revert if the server rejects it.
    final previous = Map<String, dynamic>.from(items[idx]);
    setState(() => items[idx] = {...previous, 'status': 'cancelled'});

    try {
      await ref.read(paymentRequestRepositoryProvider).cancel(uuid);
      if (mounted) _snack('تم إلغاء الطلب', success: true);
    } on ApiException catch (e) {
      if (!mounted) return;
      setState(() => items[idx] = previous);
      _snack(e.message);
    } catch (_) {
      if (!mounted) return;
      setState(() => items[idx] = previous);
      _snack('تعذّر إلغاء الطلب، تحقّق من اتصالك وحاول مجدداً');
    }
  }

  static (String, StatusKind, IconData) _statusMeta(String status) {
    return switch (status) {
      'pending' => ('قيد الانتظار', StatusKind.warning, Iconsax.clock),
      'paid' => ('مدفوع', StatusKind.success, Iconsax.tick_circle),
      'cancelled' => ('ملغى', StatusKind.error, Iconsax.close_circle),
      'expired' => ('منتهي الصلاحية', StatusKind.neutral, Iconsax.timer_pause),
      _ => (status, StatusKind.neutral, Iconsax.info_circle),
    };
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

  @override
  Widget build(BuildContext context) {
    return AppScaffold(
      title: 'طلبات الدفع',
      subtitle: 'طلبات الدفع التي أنشأتها',
      onRefresh: () async => _load(),
      body: _buildBody(),
    );
  }

  Widget _buildBody() {
    final colors = context.appColors;
    if (_loading && _items == null) {
      return Center(child: CircularProgressIndicator(color: colors.primary));
    }
    if (_error != null) {
      return _fill(EmptyState(
        icon: Iconsax.warning_2,
        title: 'تعذّر تحميل الطلبات',
        subtitle: _error!,
        actionLabel: 'إعادة المحاولة',
        onAction: _load,
      ));
    }
    final items = _items ?? [];
    if (items.isEmpty) {
      return _fill(const EmptyState(
        icon: Iconsax.receipt_item,
        title: 'لا توجد طلبات دفع',
        subtitle: 'أنشئ طلب دفعة وشاركه ليُدفع لك فوراً.',
      ));
    }
    return ListView.builder(
      padding: const EdgeInsets.fromLTRB(AppSpacing.lg, AppSpacing.sm, AppSpacing.lg, AppSpacing.xxl),
      itemCount: items.length,
      itemBuilder: (context, i) =>
          _tile(items[i]).animate(delay: (i * 40).ms).fadeIn().slideX(begin: 0.05),
    );
  }

  /// Make non-list states fill the viewport so pull-to-refresh still works.
  Widget _fill(Widget child) => LayoutBuilder(
        builder: (ctx, c) => SingleChildScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          child: ConstrainedBox(
            constraints: BoxConstraints(minHeight: c.maxHeight),
            child: child,
          ),
        ),
      );

  Widget _tile(Map<String, dynamic> r) {
    final colors = context.appColors;
    final status = r['status'].toString();
    final (label, kind, icon) = _statusMeta(status);
    final color = _kindColor(kind);
    final amount = (r['amount'] as num).toDouble();
    final currency = r['currency'].toString();
    final note = (r['note'] ?? '').toString();

    return AppCard(
      margin: const EdgeInsets.only(bottom: AppSpacing.md),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              IconTile(icon: icon, color: color),
              const SizedBox(width: AppSpacing.md),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Directionality(
                      textDirection: TextDirection.ltr,
                      child: Align(
                        alignment: Alignment.centerRight,
                        child: Text(Money.format(amount, currency),
                            style: TextStyle(
                                fontSize: 16, fontWeight: FontWeight.w700, color: colors.textPrimary)),
                      ),
                    ),
                    if (note.isNotEmpty) ...[
                      const SizedBox(height: 2),
                      Text(note,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: TextStyle(fontSize: 12.5, color: colors.textSecondary)),
                    ],
                  ],
                ),
              ),
              const SizedBox(width: AppSpacing.sm),
              StatusBadge(label: label, kind: kind),
            ],
          ),
          if (status == 'pending') ...[
            const SizedBox(height: AppSpacing.md),
            const Divider(height: 1),
            const SizedBox(height: AppSpacing.xs),
            Row(
              children: [
                Expanded(
                  child: TextButton.icon(
                    onPressed: () => _showQr(r),
                    icon: Icon(Iconsax.scan_barcode, size: 16, color: colors.primary),
                    label: Text('عرض الرابط/QR', style: TextStyle(color: colors.primary)),
                  ),
                ),
                Container(width: 1, height: 20, color: colors.inputBackground),
                Expanded(
                  child: TextButton.icon(
                    onPressed: () => _confirmCancel(r['uuid'].toString()),
                    icon: Icon(Iconsax.close_circle, size: 16, color: colors.error),
                    label: Text('إلغاء', style: TextStyle(color: colors.error)),
                  ),
                ),
              ],
            ),
          ],
        ],
      ),
    );
  }

  void _showQr(Map<String, dynamic> r) {
    final payUrl = (r['pay_url'] ?? 'https://sakk.app/pay/${r['uuid']}').toString();
    final amount = (r['amount'] as num).toDouble();
    final currency = r['currency'].toString();
    final colors = context.appColors;
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: colors.surface,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(AppRadius.xl))),
      builder: (sheetCtx) => SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(AppSpacing.xl),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
            Container(
              width: 40,
              height: 4,
              decoration: BoxDecoration(color: colors.inputBackground, borderRadius: BorderRadius.circular(2)),
            ),
            const SizedBox(height: AppSpacing.lg),
            Directionality(
              textDirection: TextDirection.ltr,
              child: Text(Money.format(amount, currency),
                  style: TextStyle(fontSize: 22, fontWeight: FontWeight.w800, color: colors.textPrimary)),
            ),
            const SizedBox(height: AppSpacing.lg),
            Container(
              padding: const EdgeInsets.all(AppSpacing.lg),
              decoration: BoxDecoration(
                color: colors.surface,
                borderRadius: BorderRadius.circular(AppRadius.xl),
                border: Border.all(color: colors.primary.withValues(alpha: 0.12)),
                boxShadow: AppShadows.card,
              ),
              child: QrImageView(
                data: payUrl,
                size: 200,
                backgroundColor: Colors.white,
                eyeStyle: const QrEyeStyle(eyeShape: QrEyeShape.circle, color: Color(0xFF6E1B2D)),
                dataModuleStyle: const QrDataModuleStyle(
                    dataModuleShape: QrDataModuleShape.circle, color: Color(0xFF1E1B4B)),
              ),
            ),
            const SizedBox(height: AppSpacing.lg),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: AppSpacing.md, vertical: AppSpacing.sm),
              decoration: BoxDecoration(
                color: colors.primaryLight,
                borderRadius: BorderRadius.circular(AppRadius.md),
              ),
              child: Text(
                payUrl,
                textDirection: TextDirection.ltr,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: TextStyle(fontSize: 12.5, color: colors.primary, fontWeight: FontWeight.w600),
              ),
            ),
            const SizedBox(height: AppSpacing.lg),
            Row(children: [
              Expanded(
                child: AppButton(
                  label: 'نسخ',
                  icon: Iconsax.copy,
                  variant: AppButtonVariant.secondary,
                  onPressed: () {
                    Clipboard.setData(ClipboardData(text: payUrl));
                    Navigator.pop(sheetCtx);
                    _snack('تم نسخ رابط الدفع', success: true);
                  },
                ),
              ),
              const SizedBox(width: AppSpacing.md),
              Expanded(
                child: AppButton(
                  label: 'مشاركة',
                  icon: Iconsax.share,
                  onPressed: () => Share.share('ادفع لي عبر صكّ: ${Money.format(amount, currency)}\n$payUrl'),
                ),
              ),
            ]),
          ],
        ),
        ),
      ),
    );
  }
}
