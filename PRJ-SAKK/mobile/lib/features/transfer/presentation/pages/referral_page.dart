import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:go_router/go_router.dart';
import 'package:iconsax/iconsax.dart';
import 'package:share_plus/share_plus.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../../core/theme/app_colors.dart';
import '../../../../core/utils/money_formatter.dart';
import '../../../../core/widgets/app_skeleton.dart';
import '../../../../core/widgets/app_ui.dart';
import '../../data/repositories/contacts_repository.dart';

class ReferralPage extends ConsumerStatefulWidget {
  const ReferralPage({super.key});

  @override
  ConsumerState<ReferralPage> createState() => _ReferralPageState();
}

class _ReferralPageState extends ConsumerState<ReferralPage> {
  Map<String, dynamic>? _info;
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    try {
      final info = await ref.read(contactsRepositoryProvider).referralInfo();
      if (mounted) setState(() { _info = info; _loading = false; });
    } catch (e) {
      if (mounted) setState(() { _error = e.toString(); _loading = false; });
    }
  }

  void _snack(String msg) {
    final colors = context.appColors;
    ScaffoldMessenger.of(context)
      ..hideCurrentSnackBar()
      ..showSnackBar(SnackBar(
        content: Text(msg),
        behavior: SnackBarBehavior.floating,
        backgroundColor: colors.success,
      ));
  }

  @override
  Widget build(BuildContext context) {
    return AppScaffold(
      title: 'دعوة الأصدقاء',
      onBack: () => context.canPop() ? context.pop() : context.go('/settings'),
      onRefresh: _load,
      body: _loading
          ? const SakkShimmer(
              child: SingleChildScrollView(
                physics: AlwaysScrollableScrollPhysics(),
                padding: EdgeInsets.fromLTRB(
                    AppSpacing.lg, AppSpacing.sm, AppSpacing.lg, 40),
                child: Column(
                  children: [
                    SkeletonCard(height: 260),
                    SizedBox(height: AppSpacing.md),
                    Row(
                      children: [
                        Expanded(
                            child: SkeletonCard(
                                height: 100, margin: EdgeInsets.zero)),
                        SizedBox(width: AppSpacing.md),
                        Expanded(
                            child: SkeletonCard(
                                height: 100, margin: EdgeInsets.zero)),
                      ],
                    ),
                    SizedBox(height: AppSpacing.md),
                    SkeletonCard(height: 160),
                    SkeletonListItem(),
                    SkeletonListItem(),
                    SkeletonListItem(),
                    SkeletonListItem(),
                  ],
                ),
              ),
            )
          : _error != null
              ? EmptyState(
                  icon: Iconsax.warning_2,
                  title: 'تعذّر التحميل',
                  subtitle: _error,
                  actionLabel: 'إعادة المحاولة',
                  onAction: _load,
                )
              : _buildContent(),
    );
  }

  Widget _buildContent() {
    final colors = context.appColors;
    final code = _info?['referral_code'] as String? ?? '';
    final inviteUrl = _info?['invite_url'] as String? ?? '';
    final reward = (_info?['reward_amount'] as num?)?.toDouble() ?? 5.0;
    final currency = _info?['reward_currency'] as String? ?? 'USD';
    final totalReferrals = (_info?['total_referrals'] as num?)?.toInt() ?? 0;
    final totalEarned = (_info?['total_earned'] as num?)?.toDouble() ?? 0;

    final shareText = 'انضم إليّ على محفظة صكّ 💚\n'
        'حوّل واستقبل الأموال فوراً.\n'
        'سجّل عبر الرابط (كود الإحالة $code):\n$inviteUrl';

    return SingleChildScrollView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.fromLTRB(
          AppSpacing.lg, AppSpacing.sm, AppSpacing.lg, 40),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          // ── Hero (single brand gradient) ──
          _hero(reward, currency),

          const SizedBox(height: AppSpacing.xl),

          // ── Stats ──
          Row(
            children: [
              Expanded(
                child: _statCard(Iconsax.people, '$totalReferrals', 'عدد الإحالات'),
              ),
              const SizedBox(width: AppSpacing.md),
              Expanded(
                child: _statCard(Iconsax.dollar_circle,
                    Money.format(totalEarned, currency), 'إجمالي الأرباح',
                    credit: true),
              ),
            ],
          ),

          const SizedBox(height: AppSpacing.lg),

          // ── Reward condition ──
          _conditionCard(reward, currency),
          const SizedBox(height: AppSpacing.lg),

          // ── Referral code (premium "invite ticket") ──
          Container(
            padding: const EdgeInsets.all(AppSpacing.lg),
            decoration: BoxDecoration(
              gradient: LinearGradient(colors: colors.cardGradientVisa),
              borderRadius: BorderRadius.circular(AppRadius.lg),
              boxShadow: [
                BoxShadow(
                    color: Colors.black.withValues(alpha: 0.2),
                    blurRadius: 16,
                    offset: const Offset(0, 6)),
              ],
            ),
            child: Column(
              children: [
                Row(
                  children: [
                    const Icon(Iconsax.ticket_star, color: Colors.white, size: 18),
                    const SizedBox(width: 8),
                    Text('كود الإحالة الخاص بك',
                        style: TextStyle(color: Colors.white.withValues(alpha: 0.85), fontSize: 13)),
                    const Spacer(),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                      decoration: BoxDecoration(
                        color: colors.success.withValues(alpha: 0.25),
                        borderRadius: BorderRadius.circular(6),
                      ),
                      child: const Text('نشط',
                          style: TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.w700)),
                    ),
                  ],
                ),
                const SizedBox(height: AppSpacing.md),
                Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Text(
                      code,
                      textDirection: TextDirection.ltr,
                      style: const TextStyle(
                        fontSize: 28,
                        fontWeight: FontWeight.w900,
                        letterSpacing: 4,
                        fontFamily: 'monospace',
                        color: Colors.white,
                      ),
                    ),
                    const SizedBox(width: AppSpacing.md),
                    GestureDetector(
                      onTap: () {
                        Clipboard.setData(ClipboardData(text: code));
                        _snack('تم نسخ كود الإحالة');
                      },
                      child: Container(
                        padding: const EdgeInsets.all(9),
                        decoration: BoxDecoration(
                          color: Colors.white.withValues(alpha: 0.18),
                          borderRadius: BorderRadius.circular(AppRadius.sm),
                        ),
                        child: const Icon(Iconsax.copy, size: 18, color: Colors.white),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),

          const SizedBox(height: AppSpacing.lg),

          // ── Share actions ──
          AppButton(
            label: 'مشاركة عبر واتساب',
            icon: Iconsax.direct,
            onPressed: () async {
              final whatsapp = Uri.parse(
                'https://wa.me/?text=${Uri.encodeComponent(shareText)}',
              );
              try {
                final launched =
                    await launchUrl(whatsapp, mode: LaunchMode.externalApplication);
                if (!launched) await Share.share(shareText);
              } catch (_) {
                await Share.share(shareText);
              }
            },
          ),
          const SizedBox(height: AppSpacing.md),
          AppButton(
            label: 'مشاركة عبر تطبيق آخر',
            icon: Iconsax.share,
            variant: AppButtonVariant.secondary,
            onPressed: () => Share.share(shareText),
          ),
          const SizedBox(height: AppSpacing.md),
          AppButton(
            label: 'نسخ رابط الدعوة',
            icon: Iconsax.copy,
            variant: AppButtonVariant.secondary,
            onPressed: () {
              Clipboard.setData(ClipboardData(text: inviteUrl));
              _snack('تم نسخ رابط الدعوة');
            },
          ),

          const SizedBox(height: AppSpacing.xl),

          // ── How it works ──
          const SectionHeader(title: 'كيف تعمل؟'),
          const SizedBox(height: AppSpacing.xs),
          AppCard(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                _howStep('1', 'شارك رابط الدعوة مع أصدقائك.'),
                _howStep('2',
                    'يسجّل صديقك في صكّ عبر رابطك (الكود مُضمَّن تلقائياً).'),
                _howStep('3', 'يوثّق صديقك هويته (KYC).'),
                _howStep('4',
                    'يودع أول \$100 — وتحصل أنت على ${Money.format(reward, currency)} فوراً!',
                    last: true),
              ],
            ),
          ),
        ],
      ),
    );
  }

  // ── Hero ──
  Widget _hero(double reward, String currency) {
    final colors = context.appColors;
    return Container(
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
              blurRadius: 24,
              offset: const Offset(0, 10))
        ],
      ),
      child: Stack(
        clipBehavior: Clip.hardEdge,
        children: [
          Positioned(top: -28, left: -24, child: _heroBlob(110, 0.08)),
          Positioned(bottom: -30, right: -20, child: _heroBlob(130, 0.06)),
          Column(
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Container(
                    width: 54,
                    height: 54,
                    decoration: BoxDecoration(
                      color: Colors.white.withValues(alpha: 0.18),
                      borderRadius: BorderRadius.circular(16),
                    ),
                    child: const Icon(Iconsax.gift, size: 28, color: Colors.white),
                  ),
                ],
              ),
              const SizedBox(height: AppSpacing.lg),
              Text('ادعُ أصدقاءك واربح',
                  style: TextStyle(color: Colors.white.withValues(alpha: 0.9), fontSize: 15)),
              const SizedBox(height: 6),
              // Prominent reward amount
              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                crossAxisAlignment: CrossAxisAlignment.baseline,
                textBaseline: TextBaseline.alphabetic,
                children: [
                  Text(
                    Money.format(reward, currency),
                    textDirection: TextDirection.ltr,
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 44,
                      fontWeight: FontWeight.w900,
                      height: 1,
                    ),
                  ),
                  const SizedBox(width: 8),
                  Text('عن كل صديق',
                      style: TextStyle(color: Colors.white.withValues(alpha: 0.9), fontSize: 14)),
                ],
              ),
              const SizedBox(height: AppSpacing.md),
              Text(
                'عند توثيق صديقك لحسابه وإيداع أول 100 دولار، تحصل على مكافأتك فوراً.',
                textAlign: TextAlign.center,
                style: TextStyle(color: Colors.white.withValues(alpha: 0.85), fontSize: 12.5, height: 1.5),
              ),
            ],
          ),
        ],
      ),
    ).animate().fadeIn().scale(begin: const Offset(0.95, 0.95));
  }

  Widget _heroBlob(double size, double opacity) => Container(
        width: size,
        height: size,
        decoration: BoxDecoration(
          color: Colors.white.withValues(alpha: opacity),
          shape: BoxShape.circle,
        ),
      );

  Widget _conditionCard(double reward, String currency) {
    final colors = context.appColors;
    Widget cond(IconData icon, String title, String sub) => Padding(
          padding: const EdgeInsets.only(bottom: AppSpacing.sm),
          child: Row(children: [
            Container(
              width: 38,
              height: 38,
              decoration: BoxDecoration(
                  color: colors.primaryLight,
                  borderRadius: BorderRadius.circular(11)),
              child: Icon(icon, size: 18, color: colors.primary),
            ),
            const SizedBox(width: AppSpacing.md),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(title,
                      style: TextStyle(
                          fontSize: 13.5,
                          fontWeight: FontWeight.w700,
                          color: colors.textPrimary)),
                  Text(sub,
                      style: TextStyle(
                          fontSize: 11.5, color: colors.textSecondary)),
                ],
              ),
            ),
            Icon(Iconsax.tick_circle, size: 18, color: colors.success),
          ]),
        );

    return AppCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(children: [
            Icon(Iconsax.medal_star, color: colors.primary, size: 20),
            const SizedBox(width: AppSpacing.sm),
            Text('كيف تربح ${Money.format(reward, currency)}؟',
                style: TextStyle(
                    fontSize: 15,
                    fontWeight: FontWeight.w700,
                    color: colors.textPrimary)),
          ]),
          const SizedBox(height: AppSpacing.md),
          cond(Iconsax.security_user, 'يوثّق صديقك حسابه',
              'إكمال توثيق الهوية (KYC)'),
          cond(Iconsax.wallet_add, 'يودع أول \$100',
              'أول إيداع لا يقلّ عن 100 دولار'),
          Container(
            padding: const EdgeInsets.all(AppSpacing.sm),
            decoration: BoxDecoration(
                color: colors.successLight.withValues(alpha: 0.6),
                borderRadius: BorderRadius.circular(AppRadius.sm)),
            child: Row(children: [
              Icon(Iconsax.gift, size: 14, color: colors.success),
              const SizedBox(width: 6),
              Expanded(
                child: Text(
                    'عند تحقّق الشرطين تُضاف ${Money.format(reward, currency)} إلى محفظتك فوراً.',
                    style: TextStyle(
                        fontSize: 11.5,
                        color: colors.success,
                        fontWeight: FontWeight.w600)),
              ),
            ]),
          ),
        ],
      ),
    );
  }

  Widget _statCard(IconData icon, String value, String label, {bool credit = false}) {
    final colors = context.appColors;
    return AppCard(
      child: Column(
        children: [
          IconTile(icon: icon),
          const SizedBox(height: AppSpacing.sm),
          Text(value,
              textDirection: TextDirection.ltr,
              style: TextStyle(
                  fontSize: 20,
                  fontWeight: FontWeight.w700,
                  color: credit ? colors.success : colors.textPrimary)),
          const SizedBox(height: 4),
          Text(label,
              textAlign: TextAlign.center,
              style: TextStyle(fontSize: 12, color: colors.textSecondary)),
        ],
      ),
    );
  }

  Widget _howStep(String num, String text, {bool last = false}) {
    final colors = context.appColors;
    final isDark = Theme.of(context).brightness == Brightness.dark;
    return Padding(
      padding: EdgeInsets.only(bottom: last ? 0 : AppSpacing.md),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: 26,
            height: 26,
            decoration: BoxDecoration(
                color: isDark ? colors.surface : colors.primary,
                shape: BoxShape.circle),
            child: Center(
              child: Text(num,
                  textDirection: TextDirection.ltr,
                  style: TextStyle(
                      color: isDark ? colors.textPrimary : Colors.white,
                      fontWeight: FontWeight.bold,
                      fontSize: 13)),
            ),
          ),
          const SizedBox(width: AppSpacing.md),
          Expanded(
            child: Text(text,
                style: TextStyle(
                    fontSize: 13, color: colors.textPrimary, height: 1.5)),
          ),
        ],
      ),
    );
  }
}
