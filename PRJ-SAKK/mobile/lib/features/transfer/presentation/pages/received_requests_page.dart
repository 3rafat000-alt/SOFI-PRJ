import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:iconsax/iconsax.dart';

import '../../../../core/network/api_client.dart';
import '../../../../core/theme/app_colors.dart';
import '../../../../core/utils/money_formatter.dart';
import '../../../../core/widgets/app_ui.dart';
import '../../data/repositories/payment_request_repository.dart';
import '../../../../shared/widgets/pin_prompt.dart';

/// Payment requests other people sent TO me — accept (pay) or reject, with an
/// optional reason. Used by the "الطلبات الواردة" entry.
class ReceivedRequestsPage extends ConsumerStatefulWidget {
  const ReceivedRequestsPage({super.key});

  @override
  ConsumerState<ReceivedRequestsPage> createState() =>
      _ReceivedRequestsPageState();
}

class _ReceivedRequestsPageState extends ConsumerState<ReceivedRequestsPage> {
  List<Map<String, dynamic>>? _items;
  bool _loading = true;
  String? _error;
  String? _busyUuid;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    if (mounted) setState(() => _loading = true);
    try {
      final items = await ref.read(paymentRequestRepositoryProvider).received();
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
        shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(AppRadius.md)),
        content: Row(children: [
          Icon(success ? Iconsax.tick_circle : Iconsax.warning_2,
              color: Colors.white, size: 20),
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

  Future<void> _accept(Map<String, dynamic> r) async {
    final uuid = r['uuid'].toString();
    // Second factor: the server requires a PIN to accept/pay a request (SEC H1).
    final pin = await askTransactionPin(context, title: 'تأكيد الدفع برمز PIN');
    if (pin == null || pin.isEmpty) return;

    setState(() => _busyUuid = uuid);
    try {
      await ref.read(paymentRequestRepositoryProvider).accept(uuid, pin);
      if (!mounted) return;
      setState(() => _items?.removeWhere((e) => e['uuid'] == uuid));
      _snack('تم الدفع بنجاح', success: true);
    } on ApiException catch (e) {
      if (mounted) _snack(e.message);
    } catch (_) {
      if (mounted) _snack('تعذّر إتمام الدفع، حاول مجدداً');
    } finally {
      if (mounted) setState(() => _busyUuid = null);
    }
  }

  Future<void> _reject(Map<String, dynamic> r) async {
    final uuid = r['uuid'].toString();
    final note = await _askReason();
    if (note == null) return; // cancelled
    setState(() => _busyUuid = uuid);
    try {
      await ref
          .read(paymentRequestRepositoryProvider)
          .reject(uuid, note: note.isEmpty ? null : note);
      if (!mounted) return;
      setState(() => _items?.removeWhere((e) => e['uuid'] == uuid));
      _snack('تم رفض الطلب', success: true);
    } on ApiException catch (e) {
      if (mounted) _snack(e.message);
    } catch (_) {
      if (mounted) _snack('تعذّر رفض الطلب، حاول مجدداً');
    } finally {
      if (mounted) setState(() => _busyUuid = null);
    }
  }

  Future<String?> _askReason() {
    final colors = context.appColors;
    final ctrl = TextEditingController();
    return showDialog<String>(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor: colors.surface,
        shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(AppRadius.lg)),
        title: const Text('رفض الطلب؟',
            style: TextStyle(fontWeight: FontWeight.w700)),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Align(
              alignment: AlignmentDirectional.centerStart,
              child: Text('يمكنك إضافة سبب (اختياري).'),
            ),
            const SizedBox(height: AppSpacing.md),
            TextField(
              controller: ctrl,
              maxLength: 140,
              decoration: InputDecoration(
                hintText: 'سبب الرفض',
                counterText: '',
                filled: true,
                fillColor: colors.inputBackground,
                border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(AppRadius.md),
                    borderSide: BorderSide.none),
              ),
            ),
          ],
        ),
        actions: [
          TextButton(
              onPressed: () => Navigator.pop(ctx),
              child: const Text('تراجع')),
          TextButton(
            onPressed: () => Navigator.pop(ctx, ctrl.text.trim()),
            child: Text('رفض',
                style: TextStyle(
                    color: colors.error, fontWeight: FontWeight.w700)),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return AppScaffold(
      title: 'الطلبات الواردة',
      subtitle: 'طلبات دفع موجّهة إليك',
      onRefresh: () async => _load(),
      body: _buildBody(),
    );
  }

  Widget _buildBody() {
    final colors = context.appColors;
    if (_loading && _items == null) {
      return Center(
          child: CircularProgressIndicator(color: colors.primary));
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
        title: 'لا توجد طلبات واردة',
        subtitle: 'عندما يطلب منك صديق دفعة، ستظهر هنا لتقبلها أو ترفضها.',
      ));
    }
    return ListView(
      padding: const EdgeInsets.fromLTRB(
          AppSpacing.lg, AppSpacing.md, AppSpacing.lg, AppSpacing.xxl),
      children: [
        _summary(items.length),
        const SizedBox(height: AppSpacing.md),
        ...items.asMap().entries.map((e) => _tile(e.value)
            .animate(delay: (e.key * 50).ms)
            .fadeIn(duration: 280.ms)
            .slideY(begin: 0.06)),
      ],
    );
  }

  Widget _summary(int count) {
    final colors = context.appColors;
    return Container(
      padding: const EdgeInsets.symmetric(
          horizontal: AppSpacing.lg, vertical: AppSpacing.md),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: colors.cardGradientVisa,
          begin: Alignment.topRight,
          end: Alignment.bottomLeft,
        ),
        borderRadius: BorderRadius.circular(AppRadius.lg),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.3),
            blurRadius: 16,
            offset: const Offset(0, 6),
          ),
        ],
      ),
      child: Row(
        children: [
          Container(
            width: 40,
            height: 40,
            decoration: BoxDecoration(
                color: Colors.white.withValues(alpha: 0.2), shape: BoxShape.circle),
            child: const Icon(Iconsax.money_recive, color: Colors.white, size: 20),
          ),
          const SizedBox(width: AppSpacing.md),
          Expanded(
            child: Text(
              count == 1
                  ? 'لديك طلب واحد بانتظار ردّك'
                  : 'لديك $count طلبات بانتظار ردّك',
              style: const TextStyle(
                  color: Colors.white,
                  fontSize: 14,
                  fontWeight: FontWeight.w700),
            ),
          ),
        ],
      ),
    ).animate().fadeIn(duration: 300.ms).slideY(begin: -0.1);
  }

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
    final amount = (r['amount'] as num).toDouble();
    final currency = r['currency'].toString();
    final note = (r['note'] ?? '').toString();
    final merchantName = (r['merchant_name'] ?? '').toString();
    final isMerchant = merchantName.isNotEmpty;
    final requester = r['requester'] as Map?;
    final name = isMerchant ? merchantName : (requester?['name'] ?? 'مستخدم').toString();
    final initials = (requester?['initials'] ?? '').toString();
    final busy = _busyUuid == r['uuid'].toString();

    return AppCard(
      margin: const EdgeInsets.only(bottom: AppSpacing.md),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(children: [
            isMerchant
                ? Container(
                    width: 48,
                    height: 48,
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                        colors: colors.cardGradientVisa,
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight,
                      ),
                      borderRadius: BorderRadius.circular(AppRadius.lg),
                    ),
                    child: const Icon(Iconsax.shop,
                        color: Colors.white, size: 22),
                  )
                : Container(
                    width: 48,
                    height: 48,
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                        colors: colors.cardGradientVisa,
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight,
                      ),
                      shape: BoxShape.circle,
                    ),
                    alignment: Alignment.center,
                    child: Text(initials,
                        style: const TextStyle(
                            color: Colors.white,
                            fontWeight: FontWeight.w800,
                            fontSize: 16)),
                  ),
            const SizedBox(width: AppSpacing.md),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(children: [
                    Flexible(
                      child: Text(name,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: TextStyle(
                              fontSize: 15,
                              fontWeight: FontWeight.w700,
                              color: colors.textPrimary)),
                    ),
                    if (isMerchant) ...[
                      const SizedBox(width: 5),
                      Icon(Iconsax.shield_tick,
                          size: 14, color: colors.primary),
                    ],
                  ]),
                  const SizedBox(height: 2),
                  Text(isMerchant ? 'طلب دفعة عبر منصة معتمدة' : 'يطلب منك دفعة',
                      style: TextStyle(
                          fontSize: 12, color: colors.textSecondary)),
                ],
              ),
            ),
            const SizedBox(width: AppSpacing.sm),
            Container(
              padding: const EdgeInsets.symmetric(
                  horizontal: AppSpacing.md, vertical: 6),
              decoration: BoxDecoration(
                color: colors.primaryLight,
                borderRadius: BorderRadius.circular(AppRadius.pill),
              ),
              child: Directionality(
                textDirection: TextDirection.ltr,
                child: Text(Money.format(amount, currency),
                    style: TextStyle(
                        fontSize: 15,
                        fontWeight: FontWeight.w800,
                        color: colors.primary)),
              ),
            ),
          ]),
          if (note.isNotEmpty) ...[
            const SizedBox(height: AppSpacing.md),
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(AppSpacing.sm),
              decoration: BoxDecoration(
                  color: colors.inputBackground,
                  borderRadius: BorderRadius.circular(AppRadius.sm)),
              child: Row(children: [
                Icon(Iconsax.note_1, size: 14, color: colors.textHint),
                const SizedBox(width: 6),
                Expanded(
                    child: Text(note,
                        style: TextStyle(
                            fontSize: 12.5, color: colors.textSecondary))),
              ]),
            ),
          ],
          const SizedBox(height: AppSpacing.md),
          Row(children: [
            Expanded(
              child: AppButton(
                label: 'رفض',
                variant: AppButtonVariant.secondary,
                onPressed: busy ? null : () => _reject(r),
              ),
            ),
            const SizedBox(width: AppSpacing.md),
            Expanded(
              child: AppButton(
                label: 'قبول ودفع',
                icon: Iconsax.tick_circle,
                loading: busy,
                onPressed: busy ? null : () => _accept(r),
              ),
            ),
          ]),
        ],
      ),
    );
  }
}
