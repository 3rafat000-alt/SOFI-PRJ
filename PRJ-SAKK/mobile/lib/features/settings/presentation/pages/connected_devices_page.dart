import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:iconsax/iconsax.dart';

import '../../../../core/theme/app_colors.dart';
import '../../../../core/widgets/app_skeleton.dart';
import '../../../../core/widgets/app_ui.dart';
import '../../data/models/device_model.dart';
import '../../data/repositories/device_repository.dart';

class ConnectedDevicesPage extends ConsumerStatefulWidget {
  const ConnectedDevicesPage({super.key});

  @override
  ConsumerState<ConnectedDevicesPage> createState() => _ConnectedDevicesPageState();
}

class _ConnectedDevicesPageState extends ConsumerState<ConnectedDevicesPage> {
  int? _busyId;

  void _snack(String msg, {bool ok = true}) {
    final colors = context.appColors;
    ScaffoldMessenger.of(context)
      ..hideCurrentSnackBar()
      ..showSnackBar(SnackBar(
        content: Text(msg),
        behavior: SnackBarBehavior.floating,
        backgroundColor: ok ? colors.success : colors.error,
      ));
  }

  Future<void> _approve(DeviceModel d) async {
    setState(() => _busyId = d.id);
    try {
      await ref.read(deviceRepositoryProvider).approveDevice(d.id);
      ref.invalidate(devicesProvider);
      _snack('تمت الموافقة. لن يتمكّن الجهاز من إجراء معاملات قبل 48 ساعة.');
    } catch (e) {
      _snack(e.toString(), ok: false);
    } finally {
      if (mounted) setState(() => _busyId = null);
    }
  }

  Future<void> _reject(DeviceModel d) async {
    setState(() => _busyId = d.id);
    try {
      await ref.read(deviceRepositoryProvider).rejectDevice(d.id);
      ref.invalidate(devicesProvider);
      _snack('تم رفض الجهاز.');
    } catch (e) {
      _snack(e.toString(), ok: false);
    } finally {
      if (mounted) setState(() => _busyId = null);
    }
  }

  Future<void> _remove(DeviceModel d) async {
    final colors = context.appColors;
    final ok = await showDialog<bool>(
      context: context,
      builder: (c) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: const Text('إزالة الجهاز'),
        content: Text('سيتم إلغاء وصول "${d.deviceName}" إلى حسابك.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(c, false), child: const Text('إلغاء')),
          ElevatedButton(
            onPressed: () => Navigator.pop(c, true),
            style: ElevatedButton.styleFrom(backgroundColor: colors.error),
            child: const Text('إزالة', style: TextStyle(color: Colors.white)),
          ),
        ],
      ),
    );
    if (ok != true) return;
    setState(() => _busyId = d.id);
    try {
      await ref.read(deviceRepositoryProvider).removeDevice(d.id);
      ref.invalidate(devicesProvider);
      _snack('تم حذف الجهاز.');
    } catch (e) {
      _snack(e.toString(), ok: false);
    } finally {
      if (mounted) setState(() => _busyId = null);
    }
  }

  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    final devicesAsync = ref.watch(devicesProvider);

    return AppScaffold(
      title: 'الأجهزة المتصلة',
      onRefresh: () async => ref.invalidate(devicesProvider),
      body: devicesAsync.when(
        loading: () => const SkeletonListScene(items: 4, trailing: false),
        error: (e, _) => EmptyState(
          icon: Iconsax.warning_2,
          title: 'تعذّر تحميل الأجهزة',
          subtitle: 'تحقّق من اتصالك وحاول مجدداً',
          actionLabel: 'إعادة المحاولة',
          onAction: () => ref.invalidate(devicesProvider),
        ),
        data: (devices) {
          final pending = devices.where((d) => d.isPending).toList();
          final others = devices.where((d) => !d.isPending).toList();
          return ListView(
            padding: const EdgeInsets.fromLTRB(AppSpacing.lg, 4, AppSpacing.lg, 32),
            children: [
              _securityBanner(colors),
              if (pending.isNotEmpty) ...[
                const SizedBox(height: AppSpacing.lg),
                _sectionTitle('طلبات ربط جديدة', colors, accent: colors.warning),
                const SizedBox(height: AppSpacing.sm),
                ...pending.map((d) => _pendingCard(d, colors)),
              ],
              const SizedBox(height: AppSpacing.lg),
              _sectionTitle('أجهزتك', colors),
              const SizedBox(height: AppSpacing.sm),
              if (others.isEmpty)
                Text('لا توجد أجهزة', style: TextStyle(color: colors.textSecondary))
              else
                ...others.map((d) => _deviceCard(d, colors)),
            ],
          );
        },
      ),
    );
  }

  Widget _securityBanner(AppColorsTheme colors) {
    return Container(
      padding: const EdgeInsets.all(AppSpacing.lg),
      decoration: BoxDecoration(
        gradient: LinearGradient(colors: colors.cardGradientVisa),
        borderRadius: BorderRadius.circular(AppRadius.lg),
        boxShadow: [
          BoxShadow(color: Colors.black.withValues(alpha: 0.2), blurRadius: 16, offset: const Offset(0, 6)),
        ],
      ),
      child: Row(
        children: [
          Container(
            width: 46,
            height: 46,
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.18),
              borderRadius: BorderRadius.circular(14),
            ),
            child: const Icon(Iconsax.shield_tick, color: Colors.white, size: 24),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text('حماية عالية للأجهزة',
                    style: TextStyle(color: Colors.white, fontSize: 15, fontWeight: FontWeight.w800)),
                const SizedBox(height: 4),
                Text(
                  'أي جهاز جديد يحتاج موافقتك، ولا يمكنه إجراء أي معاملة قبل مرور 48 ساعة على الموافقة.',
                  style: TextStyle(color: Colors.white.withValues(alpha: 0.85), fontSize: 12.5, height: 1.5),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _sectionTitle(String text, AppColorsTheme colors, {Color? accent}) {
    return Row(
      children: [
        Container(width: 4, height: 16, decoration: BoxDecoration(
          color: accent ?? colors.primary, borderRadius: BorderRadius.circular(2))),
        const SizedBox(width: 8),
        Text(text, style: TextStyle(fontSize: 15, fontWeight: FontWeight.w800, color: colors.textPrimary)),
      ],
    );
  }

  Widget _pendingCard(DeviceModel d, AppColorsTheme colors) {
    final busy = _busyId == d.id;
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: colors.warningLight,
        borderRadius: BorderRadius.circular(AppRadius.lg),
        border: Border.all(color: colors.warning.withValues(alpha: 0.4)),
      ),
      child: Column(
        children: [
          Row(
            children: [
              _deviceIcon(d, colors, bg: colors.warning),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(d.deviceName,
                        style: TextStyle(fontSize: 15, fontWeight: FontWeight.w700, color: colors.textPrimary)),
                    const SizedBox(height: 2),
                    Text('يطلب الوصول إلى حسابك',
                        style: TextStyle(fontSize: 12.5, color: colors.warning, fontWeight: FontWeight.w600)),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 14),
          Row(
            children: [
              Expanded(
                child: OutlinedButton(
                  onPressed: busy ? null : () => _reject(d),
                  style: OutlinedButton.styleFrom(
                    foregroundColor: colors.error,
                    side: BorderSide(color: colors.error.withValues(alpha: 0.5)),
                    padding: const EdgeInsets.symmetric(vertical: 12),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                  child: const Text('رفض', style: TextStyle(fontWeight: FontWeight.w700)),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: ElevatedButton(
                  onPressed: busy ? null : () => _approve(d),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: colors.success,
                    foregroundColor: Colors.white,
                    elevation: 0,
                    padding: const EdgeInsets.symmetric(vertical: 12),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                  child: busy
                      ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                      : const Text('موافقة', style: TextStyle(fontWeight: FontWeight.w700)),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _deviceCard(DeviceModel d, AppColorsTheme colors) {
    final busy = _busyId == d.id;
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: colors.surface,
        borderRadius: BorderRadius.circular(AppRadius.lg),
        border: Border.all(color: d.isCurrent ? colors.primary : colors.inputBackground, width: d.isCurrent ? 1.5 : 1),
        boxShadow: [
          BoxShadow(color: Colors.black.withValues(alpha: 0.04), blurRadius: 10, offset: const Offset(0, 4)),
        ],
      ),
      child: Column(
        children: [
          Row(
            children: [
              _deviceIcon(d, colors),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Flexible(
                          child: Text(d.deviceName,
                              maxLines: 1, overflow: TextOverflow.ellipsis,
                              style: TextStyle(fontSize: 15, fontWeight: FontWeight.w700, color: colors.textPrimary)),
                        ),
                        if (d.isCurrent) ...[
                          const SizedBox(width: 6),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2),
                            decoration: BoxDecoration(color: colors.primaryLight, borderRadius: BorderRadius.circular(6)),
                            child: Text('هذا الجهاز',
                                style: TextStyle(fontSize: 10.5, fontWeight: FontWeight.w700, color: colors.primary)),
                          ),
                        ],
                      ],
                    ),
                    const SizedBox(height: 3),
                    Text(
                      d.lastActiveAt != null ? 'آخر نشاط: ${_ago(d.lastActiveAt!)}' : (d.isIos ? 'iOS' : 'Android'),
                      style: TextStyle(fontSize: 12, color: colors.textSecondary),
                    ),
                  ],
                ),
              ),
              StatusBadge(
                label: d.statusLabel,
                kind: d.isApproved ? StatusKind.success : (d.isRejected ? StatusKind.error : StatusKind.warning),
              ),
              if (!d.isCurrent) ...[
                const SizedBox(width: 4),
                busy
                    ? const Padding(
                        padding: EdgeInsets.all(8),
                        child: SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2)))
                    : IconButton(
                        icon: Icon(Iconsax.trash, size: 18, color: colors.textHint),
                        onPressed: () => _remove(d),
                      ),
              ],
            ],
          ),
          if (d.transactionLocked && d.lockRemainingLabel != null) ...[
            const SizedBox(height: 10),
            Container(
              width: double.infinity,
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 9),
              decoration: BoxDecoration(
                color: colors.warningLight,
                borderRadius: BorderRadius.circular(10),
              ),
              child: Row(
                children: [
                  Icon(Iconsax.lock_1, size: 15, color: colors.warning),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      'المعاملات مقفلة لأسباب أمنية — ${d.lockRemainingLabel}',
                      style: TextStyle(fontSize: 11.5, color: colors.warning, fontWeight: FontWeight.w600),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ],
      ),
    );
  }

  Widget _deviceIcon(DeviceModel d, AppColorsTheme colors, {Color? bg}) {
    return Container(
      width: 46,
      height: 46,
      decoration: BoxDecoration(
        color: (bg ?? colors.primary).withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(13),
      ),
      child: Icon(
        d.isIos ? Iconsax.mobile : Iconsax.mobile,
        color: bg ?? colors.primary,
        size: 22,
      ),
    );
  }

  String _ago(DateTime t) {
    final diff = DateTime.now().difference(t);
    if (diff.inMinutes < 1) return 'الآن';
    if (diff.inMinutes < 60) return 'قبل ${diff.inMinutes} دقيقة';
    if (diff.inHours < 24) return 'قبل ${diff.inHours} ساعة';
    return 'قبل ${diff.inDays} يوم';
  }
}
