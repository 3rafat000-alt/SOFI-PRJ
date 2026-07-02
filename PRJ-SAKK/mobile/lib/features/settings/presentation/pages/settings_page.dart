import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:iconsax/iconsax.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../../../../core/theme/app_colors.dart';
import '../../../../core/utils/money_formatter.dart';
import '../../../../core/widgets/app_skeleton.dart';
import '../../../../core/widgets/app_ui.dart';
import '../../../auth/data/repositories/auth_repository.dart';
import '../../../auth/providers/auth_provider.dart';
import '../../../transfer/data/nfc_hce.dart';

class SettingsPage extends ConsumerWidget {
  const SettingsPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final user = ref.watch(currentUserProvider);

    return AppScaffold(
      title: 'الإعدادات',
      showBack: false,
      body: _buildBody(context, ref, user),
    );
  }

  Widget _buildBody(BuildContext context, WidgetRef ref, dynamic user) {
    if (user == null) {
      return const SakkShimmer(
        child: Padding(
          padding: EdgeInsets.fromLTRB(
              AppSpacing.lg, AppSpacing.sm, AppSpacing.lg, AppSpacing.xxxl),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              SkeletonSectionHeader(),
              SkeletonCard(height: 220),
              SizedBox(height: AppSpacing.lg),
              SkeletonSectionHeader(),
              SkeletonCard(height: 120),
              SizedBox(height: AppSpacing.lg),
              SkeletonSectionHeader(),
              SkeletonCard(height: 160),
            ],
          ),
        ),
      );
    }
    return ListView(
      padding: const EdgeInsets.fromLTRB(
          AppSpacing.lg, AppSpacing.sm, AppSpacing.lg, AppSpacing.xxxl),
      children: [
        // ───────── Account ─────────
        const SectionHeader(title: 'الحساب'),
        _group([
          _row(context, Iconsax.user_edit, 'الملف الشخصي',
              onTap: () => context.push('/settings/profile')),
          _divider(context),
          _row(context, Iconsax.security_safe, 'الأمان',
              subtitle: 'كلمة المرور، البصمة، المصادقة الثنائية',
              onTap: () => context.push('/settings/security')),
          _divider(context),
          _row(context, Iconsax.security_user, 'توثيق الهوية',
              subtitle: _kycSubtitle(user?.kycLevel ?? 0),
              trailing: _kycBadge(user?.kycLevel ?? 0),
              onTap: () => context.push('/kyc')),
          _divider(context),
          _row(context, Iconsax.gift, 'دعوة الأصدقاء',
              subtitle: 'اربح مكافأة عن كل صديق',
              onTap: () => context.push('/referral')),
        ]),
        const SizedBox(height: AppSpacing.xl),

        // ───────── Partner ─────────
        const SectionHeader(title: 'الأعمال'),
        _group([
          _row(context, Iconsax.shop, 'انضم كوكيل أو تاجر',
              subtitle: 'قدّم خدمات نقدية أو اقبل المدفوعات في متجرك',
              onTap: () => context.push('/join-partner')),
          _divider(context),
          _row(context, Iconsax.building_4, 'انضم كشركة',
              subtitle: 'سجّل شركتك ووزّع رواتب موظفيك دفعة واحدة',
              onTap: () => context.push('/join-company')),
        ]),
        const SizedBox(height: AppSpacing.xl),

        // ───────── Preferences ─────────
        const SectionHeader(title: 'التفضيلات'),
        _group([
          _row(context, Iconsax.wifi, 'الدفع عبر NFC',
              subtitle: 'اجعل هاتفك بطاقة دفع عبر NFC',
              trailing: const _NfcToggleSwitch()),
        ]),
        const SizedBox(height: AppSpacing.xl),

        // ───────── Support ─────────
        const SectionHeader(title: 'الدعم'),
        _group([
          _row(context, Iconsax.message_question, 'المساعدة والدعم',
              subtitle: 'دردشة وتواصل مع فريق الدعم',
              onTap: () => context.push('/support')),
          _divider(context),
          _row(context, Iconsax.info_circle, 'حول التطبيق',
              trailing: _valueText(context, 'v1.0.0'),
              onTap: () => context.push('/about')),
          _divider(context),
          _row(context, Iconsax.document_text, 'الشروط والأحكام',
              onTap: () => context.push('/terms')),
          _divider(context),
          _row(context, Iconsax.shield_tick, 'سياسة الخصوصية',
              onTap: () => context.push('/privacy')),
          _divider(context),
          _row(context, Iconsax.document_text, 'سياسة الاستخدام',
              onTap: () => context.push('/usage')),
          _divider(context),
          _row(context, Iconsax.document_text, 'الإفصاحات',
              onTap: () => context.push('/disclosures')),
        ]),
        const SizedBox(height: AppSpacing.xxl),

        // ───────── Logout ─────────
        _logoutButton(context, ref),
        const SizedBox(height: AppSpacing.lg),
        Center(
          child: Text('SAKK Wallet · v1.0.0',
              style: TextStyle(
                  fontSize: 12, color: context.appColors.textHint)),
        ),
      ],
    );
  }

  // ───────────── Building blocks ─────────────
  Widget _group(List<Widget> children) => AppCard(
        padding: EdgeInsets.zero,
        child: Column(children: children),
      );

  Widget _divider(BuildContext context) => Divider(
      height: 1,
      indent: 68,
      endIndent: 16,
      color: context.appColors.textHint.withValues(alpha: 0.15));

  Widget _row(BuildContext context, IconData icon, String title,
      {String? subtitle, Widget? trailing, VoidCallback? onTap}) {
    final colors = context.appColors;
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(AppRadius.lg),
        child: Padding(
          padding: const EdgeInsets.symmetric(
              horizontal: AppSpacing.lg, vertical: AppSpacing.md),
          child: Row(children: [
            IconTile(icon: icon, size: 42),
            const SizedBox(width: AppSpacing.md),
            Expanded(
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Text(title,
                    style: TextStyle(
                        fontSize: 14.5,
                        fontWeight: FontWeight.w600,
                        color: colors.textPrimary)),
                if (subtitle != null) ...[
                  const SizedBox(height: 2),
                  Text(subtitle,
                      style: TextStyle(
                          fontSize: 11.5, color: colors.textSecondary)),
                ],
              ]),
            ),
            trailing ??
                Icon(Iconsax.arrow_left_2,
                    color: colors.textHint, size: 18),
          ]),
        ),
      ),
    );
  }

  Widget _valueText(BuildContext context, String t) => Text(t,
      style: TextStyle(color: context.appColors.textSecondary, fontSize: 13));

  Widget _kycBadge(int level) {
    final kind = switch (level) {
      2 => StatusKind.success,
      1 => StatusKind.warning,
      _ => StatusKind.neutral,
    };
    final label = switch (level) {
      2 => 'موثّق كامل',
      1 => 'موثّق أساسي',
      _ => 'غير موثّق',
    };
    return StatusBadge(label: label, kind: kind);
  }

  Widget _logoutButton(BuildContext context, WidgetRef ref) {
    final colors = context.appColors;
    return GestureDetector(
      onTap: () => _logout(context, ref),
      child: Container(
        height: 54,
        decoration: BoxDecoration(
          color: colors.errorLight,
          borderRadius: BorderRadius.circular(AppRadius.lg),
        ),
        child: Row(mainAxisAlignment: MainAxisAlignment.center, children: [
          Icon(Iconsax.logout, color: colors.error, size: 20),
          const SizedBox(width: 10),
          Text('تسجيل الخروج',
              style: TextStyle(
                  color: colors.error,
                  fontSize: 15,
                  fontWeight: FontWeight.bold)),
        ]),
      ),
    );
  }

  Future<void> _logout(BuildContext context, WidgetRef ref) async {
    final colors = context.appColors;
    final ok = await showDialog<bool>(
      context: context,
      builder: (c) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: const Text('تسجيل الخروج'),
        content: const Text('هل أنت متأكد من تسجيل الخروج؟'),
        actions: [
          TextButton(
              onPressed: () => Navigator.pop(c, false),
              child: const Text('إلغاء')),
          ElevatedButton(
              onPressed: () => Navigator.pop(c, true),
              style: ElevatedButton.styleFrom(backgroundColor: colors.error),
              child: const Text('تسجيل الخروج')),
        ],
      ),
    );
    if (ok == true) {
      await ref.read(authRepositoryProvider).logout();
      ref.read(currentUserProvider.notifier).state = null;
      ref.invalidate(authStateProvider);
      if (context.mounted) context.go('/login');
    }
  }

}

String _kycSubtitle(int level) {
  const subtitles = {
    0: 'وثّق حسابك لرفع الحدود',
    1: 'أكمل التوثيق للوصول للمستوى الكامل',
    2: 'حسابك موثّق بالكامل',
  };
  return subtitles[level] ?? 'وثّق حسابك لرفع الحدود';
}

/// Toggle that turns the phone into an NFC payment card (Host Card Emulation).
/// Persists its state; the native HCE service keeps responding while enabled.
class _NfcToggleSwitch extends ConsumerStatefulWidget {
  const _NfcToggleSwitch();

  @override
  ConsumerState<_NfcToggleSwitch> createState() => _NfcToggleSwitchState();
}

class _NfcToggleSwitchState extends ConsumerState<_NfcToggleSwitch> {
  static const _key = 'nfc_enabled';
  bool _enabled = false;
  bool _busy = false;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    final prefs = await SharedPreferences.getInstance();
    if (mounted) setState(() => _enabled = prefs.getBool(_key) ?? false);
  }

  Future<void> _toggle(bool value) async {
    if (_busy) return;
    setState(() => _busy = true);
    final messenger = ScaffoldMessenger.of(context);
    try {
      if (value) {
        if (!await NfcHce.isSupported()) {
          messenger.showSnackBar(const SnackBar(
            content: Text('NFC غير متاح على هذا الجهاز'),
            backgroundColor: AppColors.warning,
            behavior: SnackBarBehavior.floating,
          ));
          return;
        }
        final user = ref.read(currentUserProvider);
        if (user == null) return;
        await NfcHce.startEmulation(Money.accountNumber(user.id));
      } else {
        await NfcHce.stopEmulation();
      }
      final prefs = await SharedPreferences.getInstance();
      await prefs.setBool(_key, value);
      if (!mounted) return;
      setState(() => _enabled = value);
      messenger.showSnackBar(SnackBar(
        content:
            Text(value ? 'تم تفعيل الدفع عبر NFC' : 'تم إيقاف الدفع عبر NFC'),
        backgroundColor: value ? AppColors.success : AppColors.textSecondary,
        behavior: SnackBarBehavior.floating,
        duration: const Duration(seconds: 1),
      ));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_busy) {
      return const SizedBox(
        width: 40,
        height: 24,
        child: Center(
          child: SizedBox(
              width: 18,
              height: 18,
              child: CircularProgressIndicator(strokeWidth: 2)),
        ),
      );
    }
    return Switch.adaptive(
      value: _enabled,
      onChanged: _toggle,
      activeColor: context.appColors.primary,
    );
  }
}
