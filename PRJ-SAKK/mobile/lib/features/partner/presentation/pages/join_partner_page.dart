import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:iconsax/iconsax.dart';
import 'package:image_picker/image_picker.dart';

import '../../../../core/theme/app_colors.dart';
import '../../../../core/widgets/app_skeleton.dart';
import '../../../../core/widgets/app_ui.dart';
import '../../../../core/widgets/damascene_pattern.dart';
import '../../data/models/partner_models.dart';
import '../../data/repositories/partner_repository.dart';

/// "Join as agent or merchant" — entry point from Settings.
/// Shows a choice when there's no application, otherwise the status + documents.
class JoinPartnerPage extends ConsumerWidget {
  const JoinPartnerPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final colors = context.appColors;
    final stateAsync = ref.watch(partnerStateProvider);

    return AppScaffold(
      title: 'انضم كشريك',
      onRefresh: () async => ref.invalidate(partnerStateProvider),
      body: stateAsync.when(
        data: (state) {
          final hasAny = state.agent != null || state.merchant != null;
          return ListView(
            padding: const EdgeInsets.fromLTRB(
                AppSpacing.lg, AppSpacing.md, AppSpacing.lg, AppSpacing.xxxl),
            children: [
              if (!hasAny) ...[
                const _JoinPartnerHero(),
                const SizedBox(height: AppSpacing.xl),
                _RoleCard(
                  gradient: AppColors.cardGradientVisa,
                  icon: Iconsax.shop,
                  roleLabel: 'وكيل',
                  title: 'انضم كوكيل',
                  benefits: const [
                    'عمولة على كل عملية إيداع وسحب',
                    'يجدك العملاء على الخريطة',
                    'إيداع وسحب نقدي مباشر',
                  ],
                  onTap: () => _openApplyForm(context, ref, isAgent: true),
                  animationDelay: 80,
                ),
                const SizedBox(height: AppSpacing.md),
                _RoleCard(
                  gradient: AppColors.cardGradientGold,
                  icon: Iconsax.shopping_cart,
                  roleLabel: 'تاجر',
                  title: 'انضم كتاجر',
                  benefits: const [
                    'اقبل مدفوعات صكّ في متجرك',
                    'مفاتيح API لتكامل التجارة',
                    'تسوية يومية لمبيعاتك',
                  ],
                  onTap: () => _openApplyForm(context, ref, isAgent: false),
                  animationDelay: 160,
                ),
              ],
              if (state.agent != null)
                _ApplicationStatusCard(
                  app: state.agent!,
                  docTypes: state.agentDocTypes,
                ),
              if (state.merchant != null) ...[
                const SizedBox(height: AppSpacing.md),
                _ApplicationStatusCard(
                  app: state.merchant!,
                  docTypes: state.merchantDocTypes,
                ),
              ],
              // Allow applying for the other role too.
              if (hasAny && (state.agent == null || state.merchant == null)) ...[
                const SizedBox(height: AppSpacing.xl),
                const SectionHeader(title: 'انضم بدور آخر'),
                if (state.agent == null)
                  _RoleCard(
                    gradient: AppColors.cardGradientVisa,
                    icon: Iconsax.shop,
                    roleLabel: 'وكيل',
                    title: 'انضم كوكيل',
                    benefits: const [
                      'عمولة على كل عملية إيداع وسحب',
                      'يجدك العملاء على الخريطة',
                      'إيداع وسحب نقدي مباشر',
                    ],
                    onTap: () => _openApplyForm(context, ref, isAgent: true),
                    animationDelay: 0,
                  ),
                if (state.merchant == null)
                  _RoleCard(
                    gradient: AppColors.cardGradientGold,
                    icon: Iconsax.shopping_cart,
                    roleLabel: 'تاجر',
                    title: 'انضم كتاجر',
                    benefits: const [
                      'اقبل مدفوعات صكّ في متجرك',
                      'مفاتيح API لتكامل التجارة',
                      'تسوية يومية لمبيعاتك',
                    ],
                    onTap: () => _openApplyForm(context, ref, isAgent: false),
                    animationDelay: 0,
                  ),
              ],
            ],
          );
        },
        loading: () => const SkeletonEmptyFriendly(),
        error: (e, __) => Center(
          child: Padding(
            padding: const EdgeInsets.all(AppSpacing.xl),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Icon(Iconsax.warning_2, color: colors.error, size: 40),
                const SizedBox(height: AppSpacing.md),
                Text('تعذّر تحميل الحالة',
                    style: TextStyle(color: colors.textSecondary)),
                TextButton(
                    onPressed: () => ref.invalidate(partnerStateProvider),
                    child: const Text('إعادة المحاولة')),
              ],
            ),
          ),
        ),
      ),
    );
  }

  void _openApplyForm(BuildContext context, WidgetRef ref,
      {required bool isAgent}) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      useSafeArea: true,
      backgroundColor: Colors.transparent,
      builder: (_) => _ApplyFormSheet(isAgent: isAgent),
    );
  }
}

// ════════════════════════════════════════════════════════════════════
// Hero Banner — wine gradient + damascene medallion + headline
// ════════════════════════════════════════════════════════════════════
class _JoinPartnerHero extends StatelessWidget {
  const _JoinPartnerHero();

  @override
  Widget build(BuildContext context) {
    return ClipRRect(
      borderRadius: BorderRadius.circular(AppRadius.xl),
      child: Stack(
        children: [
          // Wine gradient background
          Container(
            height: 160,
            decoration: const BoxDecoration(
              gradient: LinearGradient(
                colors: AppColors.cardGradientVisa,
                begin: Alignment.topRight,
                end: Alignment.bottomLeft,
              ),
            ),
          ),
          // Damascene medallion watermark — faint, corner-aligned
          const Positioned.fill(
            child: IgnorePointer(
              child: CustomPaint(
                painter: DamasceneMedallionPainter(
                  color: Color(0xFFD9B978),
                  opacity: 0.10,
                  alignment: Alignment(1.15, -0.05),
                  radius: 140,
                ),
              ),
            ),
          ),
          // Gold hairline bottom accent
          Positioned(
            left: 0,
            right: 0,
            bottom: 0,
            child: Container(
              height: 2,
              decoration: const BoxDecoration(
                gradient: LinearGradient(
                  colors: [
                    Color(0x00B58A3C),
                    Color(0xFFB58A3C),
                    Color(0x00B58A3C),
                  ],
                ),
              ),
            ),
          ),
          // Content
          Positioned.fill(
            child: Padding(
              padding: const EdgeInsets.symmetric(
                  horizontal: AppSpacing.xl, vertical: AppSpacing.xl),
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Gold chip label
                  Container(
                    padding: const EdgeInsets.symmetric(
                        horizontal: AppSpacing.sm, vertical: 3),
                    decoration: BoxDecoration(
                      color: const Color(0xFFB58A3C).withValues(alpha: 0.22),
                      borderRadius: BorderRadius.circular(AppRadius.pill),
                      border: Border.all(
                          color: const Color(0xFFB58A3C).withValues(alpha: 0.5),
                          width: 0.8),
                    ),
                    child: const Text(
                      'شراكة مع صكّ',
                      style: TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.w700,
                        color: Color(0xFFE8C97A),
                        letterSpacing: 0.4,
                      ),
                    ),
                  ),
                  const SizedBox(height: AppSpacing.sm),
                  const Text(
                    'انضم إلى شبكة صكّ\nوابدأ بالربح',
                    style: TextStyle(
                      fontSize: 22,
                      fontWeight: FontWeight.w800,
                      color: Colors.white,
                      height: 1.25,
                    ),
                  ),
                  const SizedBox(height: AppSpacing.xs),
                  Text(
                    'وكيل إيداع وسحب أو تاجر يقبل المدفوعات',
                    style: TextStyle(
                      fontSize: 13,
                      color: Colors.white.withValues(alpha: 0.75),
                      height: 1.4,
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    )
        .animate()
        .fadeIn(duration: 280.ms, curve: Curves.easeOut)
        .slideY(begin: 0.06, end: 0, duration: 280.ms, curve: Curves.easeOut);
  }
}

// ════════════════════════════════════════════════════════════════════
// Role Card — gradient icon tile + benefit bullets + CTA affordance
// ════════════════════════════════════════════════════════════════════
class _RoleCard extends StatelessWidget {
  final List<Color> gradient;
  final IconData icon;
  final String roleLabel;
  final String title;
  final List<String> benefits;
  final VoidCallback onTap;
  final int animationDelay;

  const _RoleCard({
    required this.gradient,
    required this.icon,
    required this.roleLabel,
    required this.title,
    required this.benefits,
    required this.onTap,
    required this.animationDelay,
  });

  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    final Widget card = Material(
      color: Colors.transparent,
      child: InkWell(
        borderRadius: BorderRadius.circular(AppRadius.xl),
        onTap: onTap,
        child: Container(
          decoration: BoxDecoration(
            color: colors.surface,
            borderRadius: BorderRadius.circular(AppRadius.xl),
            boxShadow: [
              BoxShadow(
                color: gradient.first.withValues(alpha: 0.10),
                blurRadius: 18,
                offset: const Offset(0, 6),
              ),
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.04),
                blurRadius: 8,
                offset: const Offset(0, 2),
              ),
            ],
            border: Border(
              right: BorderSide(
                color: gradient.first.withValues(alpha: 0.55),
                width: 3,
              ),
            ),
          ),
          padding: const EdgeInsets.all(AppSpacing.lg),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Gradient icon tile
              Container(
                width: 58,
                height: 58,
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    colors: gradient,
                    begin: Alignment.topRight,
                    end: Alignment.bottomLeft,
                  ),
                  borderRadius: BorderRadius.circular(AppRadius.lg),
                  boxShadow: [
                    BoxShadow(
                      color: gradient.first.withValues(alpha: 0.30),
                      blurRadius: 10,
                      offset: const Offset(0, 4),
                    ),
                  ],
                ),
                child: Icon(icon, color: Colors.white, size: 28),
              ),
              const SizedBox(width: AppSpacing.md),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Role chip + title row
                    Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.symmetric(
                              horizontal: 7, vertical: 2),
                          decoration: BoxDecoration(
                            color: gradient.first.withValues(alpha: 0.10),
                            borderRadius: BorderRadius.circular(AppRadius.pill),
                          ),
                          child: Text(
                            roleLabel,
                            style: TextStyle(
                              fontSize: 10.5,
                              fontWeight: FontWeight.w700,
                              color: gradient.first,
                              letterSpacing: 0.3,
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 5),
                    Text(
                      title,
                      style: TextStyle(
                        fontSize: 17,
                        fontWeight: FontWeight.w800,
                        color: colors.textPrimary,
                      ),
                    ),
                    const SizedBox(height: AppSpacing.sm),
                    // Benefit bullets
                    ...benefits.map((b) => Padding(
                          padding:
                              const EdgeInsets.only(bottom: 4),
                          child: Row(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Padding(
                                padding: const EdgeInsets.only(top: 4),
                                child: Container(
                                  width: 5,
                                  height: 5,
                                  decoration: BoxDecoration(
                                    color: const Color(0xFFB58A3C),
                                    borderRadius: BorderRadius.circular(3),
                                  ),
                                ),
                              ),
                              const SizedBox(width: 7),
                              Expanded(
                                child: Text(
                                  b,
                                  style: TextStyle(
                                    fontSize: 12.5,
                                    color: colors.textSecondary,
                                    height: 1.45,
                                  ),
                                ),
                              ),
                            ],
                          ),
                        )),
                    const SizedBox(height: AppSpacing.sm),
                    // CTA affordance
                    Row(
                      children: [
                        Text(
                          'تقديم الطلب',
                          style: TextStyle(
                            fontSize: 13,
                            fontWeight: FontWeight.w700,
                            color: gradient.first,
                          ),
                        ),
                        const SizedBox(width: 4),
                        Icon(Iconsax.arrow_left_2,
                            color: gradient.first, size: 15),
                      ],
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );

    if (animationDelay == 0) return card;
    return card
        .animate(delay: Duration(milliseconds: animationDelay))
        .fadeIn(duration: 260.ms, curve: Curves.easeOut)
        .slideY(begin: 0.05, end: 0, duration: 260.ms, curve: Curves.easeOut);
  }
}

// ════════════════════════════════════════════════════════════════════
// Application Status Card (+ documents)
// ════════════════════════════════════════════════════════════════════
class _ApplicationStatusCard extends ConsumerWidget {
  final PartnerApplication app;
  final List<PartnerDocType> docTypes;
  const _ApplicationStatusCard({required this.app, required this.docTypes});

  StatusKind get _kind => switch (app.kycStatusColor) {
        'success' => StatusKind.success,
        'danger' => StatusKind.error,
        'warning' => StatusKind.warning,
        _ => StatusKind.neutral,
      };

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final colors = context.appColors;
    final isAgent = app.type == 'agent';
    final gradient =
        isAgent ? AppColors.cardGradientVisa : AppColors.cardGradientGold;
    return AppCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              // Gradient icon tile matching role
              Container(
                width: 46,
                height: 46,
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    colors: gradient,
                    begin: Alignment.topRight,
                    end: Alignment.bottomLeft,
                  ),
                  borderRadius: BorderRadius.circular(AppRadius.md),
                ),
                child: Icon(
                    isAgent ? Iconsax.shop : Iconsax.shopping_cart,
                    color: Colors.white,
                    size: 22),
              ),
              const SizedBox(width: AppSpacing.md),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(isAgent ? 'طلب وكيل' : 'طلب تاجر',
                        style:
                            TextStyle(fontSize: 12, color: colors.textSecondary)),
                    Text(app.name,
                        style: TextStyle(
                            fontSize: 15.5,
                            fontWeight: FontWeight.w700,
                            color: colors.textPrimary)),
                  ],
                ),
              ),
              StatusBadge(label: app.kycStatusLabel, kind: _kind),
            ],
          ),

          // ── approved: email-link notice (design truth — no URL shown) ──
          if (app.isApproved) ...[
            const SizedBox(height: AppSpacing.md),
            Container(
              padding: const EdgeInsets.all(AppSpacing.md),
              decoration: BoxDecoration(
                color: colors.successLight.withValues(alpha: 0.5),
                borderRadius: BorderRadius.circular(AppRadius.md),
                border: Border.all(
                    color: colors.success.withValues(alpha: 0.2), width: 0.8),
              ),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Icon(Iconsax.tick_circle, color: colors.success, size: 20),
                  const SizedBox(width: AppSpacing.sm),
                  Expanded(
                    child: Text(
                      'تمت الموافقة على طلبك — أرسلنا رابط تسجيل الدخول إلى بوابة الويب على بريدك الإلكتروني.',
                      style: TextStyle(
                          fontSize: 12.5, color: colors.success, height: 1.45),
                    ),
                  ),
                ],
              ),
            ),
          ],

          // ── documents_required: prominent upload prompt ──
          if (app.needsDocuments && !app.isRejected) ...[
            const SizedBox(height: AppSpacing.md),
            Container(
              padding: const EdgeInsets.all(AppSpacing.md),
              decoration: BoxDecoration(
                color: colors.warning.withValues(alpha: 0.08),
                borderRadius: BorderRadius.circular(AppRadius.md),
                border:
                    Border.all(color: colors.warning.withValues(alpha: 0.35)),
              ),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Icon(Iconsax.document_upload, color: colors.warning, size: 18),
                  const SizedBox(width: AppSpacing.sm),
                  Expanded(
                    child: Text(
                      'يرجى رفع المستندات المطلوبة لاستكمال المراجعة.',
                      style: TextStyle(
                          fontSize: 12.5, color: colors.warning, height: 1.4),
                    ),
                  ),
                ],
              ),
            ),
          ],

          // ── rejected: reason + resubmit guidance ──
          if (app.isRejected) ...[
            const SizedBox(height: AppSpacing.md),
            Container(
              padding: const EdgeInsets.all(AppSpacing.md),
              decoration: BoxDecoration(
                color: colors.errorLight.withValues(alpha: 0.4),
                borderRadius: BorderRadius.circular(AppRadius.md),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Icon(Iconsax.close_circle, color: colors.error, size: 18),
                      const SizedBox(width: AppSpacing.sm),
                      Expanded(
                        child: Text(
                          'تم رفض طلبك.',
                          style: TextStyle(
                              fontSize: 13,
                              fontWeight: FontWeight.w700,
                              color: colors.error),
                        ),
                      ),
                    ],
                  ),
                  if (app.rejectionReason != null &&
                      app.rejectionReason!.isNotEmpty) ...[
                    const SizedBox(height: 4),
                    Padding(
                      padding: const EdgeInsetsDirectional.only(start: 26),
                      child: Text(
                        'السبب: ${app.rejectionReason}',
                        style: TextStyle(
                            fontSize: 12.5, color: colors.error, height: 1.4),
                      ),
                    ),
                  ],
                  const SizedBox(height: AppSpacing.sm),
                  Padding(
                    padding: const EdgeInsetsDirectional.only(start: 26),
                    child: Text(
                      'يمكنك رفع مستندات جديدة وسيُعاد تقييم طلبك.',
                      style: TextStyle(
                          fontSize: 12, color: colors.textSecondary, height: 1.4),
                    ),
                  ),
                ],
              ),
            ),
          ],

          // ── pending (no other banner): "under review" note ──
          if (!app.isApproved && !app.isRejected && !app.needsDocuments) ...[
            const SizedBox(height: AppSpacing.md),
            Container(
              padding: const EdgeInsets.all(AppSpacing.md),
              decoration: BoxDecoration(
                color: colors.primary.withValues(alpha: 0.06),
                borderRadius: BorderRadius.circular(AppRadius.md),
              ),
              child: Row(
                children: [
                  Icon(Iconsax.clock, color: colors.primary, size: 18),
                  const SizedBox(width: AppSpacing.sm),
                  Expanded(
                    child: Text(
                      'طلبك قيد المراجعة. سنُخطرك بالنتيجة.',
                      style: TextStyle(
                          fontSize: 12.5, color: colors.primary, height: 1.4),
                    ),
                  ),
                ],
              ),
            ),
          ],

          const SizedBox(height: AppSpacing.md),
          // Gold accent divider
          Container(
            height: 1,
            decoration: BoxDecoration(
              gradient: LinearGradient(
                colors: [
                  const Color(0xFFB58A3C).withValues(alpha: 0.0),
                  const Color(0xFFB58A3C).withValues(alpha: 0.35),
                  const Color(0xFFB58A3C).withValues(alpha: 0.0),
                ],
              ),
            ),
          ),
          const SizedBox(height: AppSpacing.md),
          Text('المستندات',
              style: TextStyle(
                  fontSize: 14,
                  fontWeight: FontWeight.w700,
                  color: colors.textPrimary)),
          const SizedBox(height: AppSpacing.sm),

          if (app.documents.isEmpty)
            Padding(
              padding: const EdgeInsets.symmetric(vertical: AppSpacing.sm),
              child: Text('لم يُرفع أي مستند بعد.',
                  style: TextStyle(fontSize: 13, color: colors.textHint)),
            ),

          // Uploaded documents
          ...app.documents.map((d) => _docRow(context, d)),

          if (!app.isApproved) ...[
            const SizedBox(height: AppSpacing.sm),
            _UploadButton(
              type: app.type,
              docTypes: docTypes,
              uploadedTypes: app.documents.map((d) => d.documentType).toSet(),
            ),
          ],
        ],
      ),
    );
  }

  Widget _docRow(BuildContext context, PartnerDocument d) {
    final colors = context.appColors;
    final kind = switch (d.statusColor) {
      'success' => StatusKind.success,
      'danger' => StatusKind.error,
      _ => StatusKind.warning,
    };
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 5),
      child: Row(
        children: [
          Icon(
            d.isApproved
                ? Iconsax.tick_circle
                : d.isRejected
                    ? Iconsax.close_circle
                    : Iconsax.clock,
            size: 18,
            color: d.isApproved
                ? colors.success
                : d.isRejected
                    ? colors.error
                    : colors.warning,
          ),
          const SizedBox(width: AppSpacing.sm),
          Expanded(
            child: Text(d.typeLabel,
                style: TextStyle(fontSize: 13.5, color: colors.textPrimary)),
          ),
          StatusBadge(
              label: d.isApproved
                  ? 'مقبول'
                  : d.isRejected
                      ? 'مرفوض'
                      : 'قيد المراجعة',
              kind: kind),
        ],
      ),
    );
  }
}

// ════════════════════════════════════════════════════════════════════
// Upload Button (pick doc type → pick image → upload)
// ════════════════════════════════════════════════════════════════════
class _UploadButton extends ConsumerStatefulWidget {
  final String type;
  final List<PartnerDocType> docTypes;
  final Set<String> uploadedTypes;
  const _UploadButton({
    required this.type,
    required this.docTypes,
    required this.uploadedTypes,
  });

  @override
  ConsumerState<_UploadButton> createState() => _UploadButtonState();
}

class _UploadButtonState extends ConsumerState<_UploadButton> {
  bool _busy = false;

  Future<void> _upload() async {
    final docType = await showModalBottomSheet<String>(
      context: context,
      backgroundColor: Colors.transparent,
      builder: (_) => _DocTypePicker(
        docTypes: widget.docTypes,
        uploadedTypes: widget.uploadedTypes,
      ),
    );
    if (docType == null) return;

    final picker = ImagePicker();
    final picked = await picker.pickImage(
      source: ImageSource.gallery,
      imageQuality: 80,
      maxWidth: 1600,
    );
    if (picked == null) return;

    setState(() => _busy = true);
    try {
      await ref.read(partnerRepositoryProvider).uploadDocument(
            type: widget.type,
            documentType: docType,
            file: File(picked.path),
          );
      ref.invalidate(partnerStateProvider);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(
        content: const Text('تم رفع المستند بنجاح'),
        backgroundColor: context.appColors.success,
        behavior: SnackBarBehavior.floating,
      ));
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(
        content: Text(e.toString()),
        backgroundColor: context.appColors.error,
        behavior: SnackBarBehavior.floating,
      ));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return AppButton(
      label: 'رفع مستند',
      icon: Iconsax.document_upload,
      loading: _busy,
      variant: AppButtonVariant.secondary,
      onPressed: _upload,
    );
  }
}

class _DocTypePicker extends StatelessWidget {
  final List<PartnerDocType> docTypes;
  final Set<String> uploadedTypes;
  const _DocTypePicker({required this.docTypes, required this.uploadedTypes});

  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    return Container(
      padding: const EdgeInsets.all(AppSpacing.xl),
      decoration: BoxDecoration(
        color: colors.surface,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          _handle(context),
          const SizedBox(height: AppSpacing.lg),
          Row(
            children: [
              Container(
                width: 4,
                height: 18,
                decoration: BoxDecoration(
                  color: AppColors.accent,
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
              const SizedBox(width: AppSpacing.sm),
              const Text('اختر نوع المستند',
                  style: TextStyle(fontSize: 17, fontWeight: FontWeight.bold)),
            ],
          ),
          const SizedBox(height: AppSpacing.md),
          ...docTypes.map((t) {
            final done = uploadedTypes.contains(t.key);
            return ListTile(
              contentPadding: EdgeInsets.zero,
              leading: Icon(
                  done ? Iconsax.tick_circle : Iconsax.document,
                  color: done ? colors.success : colors.primary),
              title: Text(t.label),
              trailing: const Icon(Iconsax.arrow_left_2, size: 18),
              onTap: () => Navigator.pop(context, t.key),
            );
          }),
          const SizedBox(height: AppSpacing.sm),
        ],
      ),
    );
  }
}

// ════════════════════════════════════════════════════════════════════
// Apply Form Sheet (agent / merchant) — all required + optional fields
// ════════════════════════════════════════════════════════════════════
class _ApplyFormSheet extends ConsumerStatefulWidget {
  final bool isAgent;
  const _ApplyFormSheet({required this.isAgent});

  @override
  ConsumerState<_ApplyFormSheet> createState() => _ApplyFormSheetState();
}

class _ApplyFormSheetState extends ConsumerState<_ApplyFormSheet> {
  final _formKey = GlobalKey<FormState>();

  // shared
  final _name = TextEditingController();
  final _ownerName = TextEditingController();
  final _phone = TextEditingController();

  // agent-specific
  final _address = TextEditingController();
  final _city = TextEditingController();
  final _governorate = TextEditingController();
  final _workingHours = TextEditingController();
  final _services = <String>{'cash_in', 'cash_out'};

  // merchant-specific
  final _email = TextEditingController();
  final _description = TextEditingController();
  final _websiteUrl = TextEditingController();
  final _merchantAddress = TextEditingController();
  final _merchantCity = TextEditingController();
  final _merchantGovernorate = TextEditingController();
  String _storeType = 'physical';

  bool _loading = false;
  String? _error;

  @override
  void dispose() {
    _name.dispose();
    _ownerName.dispose();
    _phone.dispose();
    _address.dispose();
    _city.dispose();
    _governorate.dispose();
    _workingHours.dispose();
    _email.dispose();
    _description.dispose();
    _websiteUrl.dispose();
    _merchantAddress.dispose();
    _merchantCity.dispose();
    _merchantGovernorate.dispose();
    super.dispose();
  }

  String? _opt(TextEditingController c) =>
      c.text.trim().isEmpty ? null : c.text.trim();

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final repo = ref.read(partnerRepositoryProvider);
      if (widget.isAgent) {
        await repo.applyAsAgent(
          name: _name.text.trim(),
          phone: _phone.text.trim(),
          address: _address.text.trim(),
          city: _city.text.trim(),
          ownerName: _opt(_ownerName),
          governorate: _opt(_governorate),
          services: _services.isNotEmpty ? _services.toList() : null,
          workingHours: _opt(_workingHours),
        );
      } else {
        await repo.applyAsMerchant(
          storeName: _name.text.trim(),
          storeType: _storeType,
          phone: _phone.text.trim(),
          ownerName: _opt(_ownerName),
          email: _opt(_email),
          description: _opt(_description),
          address: _opt(_merchantAddress),
          city: _opt(_merchantCity),
          governorate: _opt(_merchantGovernorate),
          websiteUrl: _opt(_websiteUrl),
        );
      }
      ref.invalidate(partnerStateProvider);
      if (!mounted) return;
      Navigator.pop(context);
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(
        content: const Text('تم تقديم الطلب. ارفع المستندات الآن.'),
        backgroundColor: context.appColors.success,
        behavior: SnackBarBehavior.floating,
      ));
    } catch (e) {
      setState(() => _error = e.toString());
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    final isAgent = widget.isAgent;
    final gradient =
        isAgent ? AppColors.cardGradientVisa : AppColors.cardGradientGold;
    return Padding(
      padding:
          EdgeInsets.only(bottom: MediaQuery.of(context).viewInsets.bottom),
      child: Container(
        padding: const EdgeInsets.all(AppSpacing.xl),
        decoration: BoxDecoration(
          color: colors.surface,
          borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
        ),
        child: SingleChildScrollView(
          child: Form(
            key: _formKey,
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                _handle(context),
                const SizedBox(height: AppSpacing.lg),
                // Sheet header with gold accent bar
                Row(
                  children: [
                    Container(
                      width: 36,
                      height: 36,
                      decoration: BoxDecoration(
                        gradient: LinearGradient(
                          colors: gradient,
                          begin: Alignment.topRight,
                          end: Alignment.bottomLeft,
                        ),
                        borderRadius: BorderRadius.circular(AppRadius.md),
                      ),
                      child: Icon(
                          isAgent ? Iconsax.shop : Iconsax.shopping_cart,
                          color: Colors.white,
                          size: 18),
                    ),
                    const SizedBox(width: AppSpacing.md),
                    Text(
                      isAgent ? 'طلب الانضمام كوكيل' : 'طلب الانضمام كتاجر',
                      style: const TextStyle(
                          fontSize: 18, fontWeight: FontWeight.bold),
                    ),
                  ],
                ),
                const SizedBox(height: 10),
                Container(
                  height: 1.5,
                  decoration: BoxDecoration(
                    gradient: LinearGradient(
                      colors: [
                        const Color(0xFFB58A3C).withValues(alpha: 0.0),
                        const Color(0xFFB58A3C).withValues(alpha: 0.5),
                        const Color(0xFFB58A3C).withValues(alpha: 0.0),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: AppSpacing.xl),

                // ── name (store_name for merchant, name for agent) ──
                _input(
                  controller: _name,
                  label: isAgent ? 'اسم المكتب / الصرافة' : 'اسم المتجر',
                  icon: isAgent ? Iconsax.shop : Iconsax.shopping_cart,
                  validator: (v) =>
                      (v == null || v.trim().isEmpty) ? 'مطلوب' : null,
                ),
                const SizedBox(height: AppSpacing.md),

                // ── owner_name (optional for both) ──
                _input(
                  controller: _ownerName,
                  label: 'اسم المالك (اختياري)',
                  icon: Iconsax.user,
                ),
                const SizedBox(height: AppSpacing.md),

                // ── merchant: store_type chips ──
                if (!isAgent) ...[
                  Text('نوع المتجر',
                      style: TextStyle(
                          fontSize: 13,
                          fontWeight: FontWeight.w600,
                          color: colors.textSecondary)),
                  const SizedBox(height: AppSpacing.sm),
                  Wrap(
                    spacing: AppSpacing.sm,
                    children: [
                      ('physical', 'متجر فعلي'),
                      ('ecommerce', 'إلكتروني'),
                      ('both', 'كلاهما'),
                    ].map((e) {
                      final selected = _storeType == e.$1;
                      return ChoiceChip(
                        label: Text(e.$2),
                        selected: selected,
                        onSelected: (_) => setState(() => _storeType = e.$1),
                        selectedColor: colors.primary,
                        labelStyle: TextStyle(
                            color:
                                selected ? Colors.white : colors.textPrimary,
                            fontWeight: FontWeight.w600),
                        backgroundColor: colors.inputBackground,
                      );
                    }).toList(),
                  ),
                  const SizedBox(height: AppSpacing.md),
                ],

                // ── phone (required for both) ──
                _input(
                  controller: _phone,
                  label: 'رقم الهاتف',
                  icon: Iconsax.call,
                  keyboard: TextInputType.phone,
                  validator: (v) =>
                      (v == null || v.trim().isEmpty) ? 'مطلوب' : null,
                ),
                const SizedBox(height: AppSpacing.md),

                // ── agent: address + city (required) ──
                if (isAgent) ...[
                  _input(
                    controller: _address,
                    label: 'العنوان',
                    icon: Iconsax.location,
                    validator: (v) =>
                        (v == null || v.trim().isEmpty) ? 'مطلوب' : null,
                  ),
                  const SizedBox(height: AppSpacing.md),
                  _input(
                    controller: _city,
                    label: 'المدينة',
                    icon: Iconsax.buildings,
                    validator: (v) =>
                        (v == null || v.trim().isEmpty) ? 'مطلوب' : null,
                  ),
                  const SizedBox(height: AppSpacing.md),
                  _input(
                    controller: _governorate,
                    label: 'المحافظة (اختياري)',
                    icon: Iconsax.map,
                  ),
                  const SizedBox(height: AppSpacing.md),

                  // ── services checkboxes ──
                  Text('الخدمات المقدّمة',
                      style: TextStyle(
                          fontSize: 13,
                          fontWeight: FontWeight.w600,
                          color: colors.textSecondary)),
                  const SizedBox(height: AppSpacing.sm),
                  _ServiceCheckboxRow(
                    value: 'cash_in',
                    label: 'إيداع نقدي',
                    icon: Iconsax.money_recive,
                    selected: _services.contains('cash_in'),
                    onToggle: (v) => setState(() {
                      if (v) {
                        _services.add('cash_in');
                      } else {
                        _services.remove('cash_in');
                      }
                    }),
                  ),
                  _ServiceCheckboxRow(
                    value: 'cash_out',
                    label: 'سحب نقدي',
                    icon: Iconsax.money_send,
                    selected: _services.contains('cash_out'),
                    onToggle: (v) => setState(() {
                      if (v) {
                        _services.add('cash_out');
                      } else {
                        _services.remove('cash_out');
                      }
                    }),
                  ),
                  const SizedBox(height: AppSpacing.md),

                  _input(
                    controller: _workingHours,
                    label: 'ساعات العمل (اختياري)',
                    icon: Iconsax.clock,
                    hint: 'مثال: 9ص – 5م',
                  ),
                  const SizedBox(height: AppSpacing.md),
                ],

                // ── merchant: optional extra fields ──
                if (!isAgent) ...[
                  _input(
                    controller: _email,
                    label: 'البريد الإلكتروني (اختياري)',
                    icon: Iconsax.sms,
                    keyboard: TextInputType.emailAddress,
                    validator: (v) {
                      if (v == null || v.trim().isEmpty) return null;
                      final emailRegex = RegExp(r'^[^@]+@[^@]+\.[^@]+');
                      return emailRegex.hasMatch(v.trim())
                          ? null
                          : 'بريد إلكتروني غير صحيح';
                    },
                  ),
                  const SizedBox(height: AppSpacing.md),
                  _input(
                    controller: _description,
                    label: 'وصف المتجر (اختياري)',
                    icon: Iconsax.text,
                    maxLines: 3,
                  ),
                  const SizedBox(height: AppSpacing.md),
                  _input(
                    controller: _merchantAddress,
                    label: 'العنوان (اختياري)',
                    icon: Iconsax.location,
                  ),
                  const SizedBox(height: AppSpacing.md),
                  _input(
                    controller: _merchantCity,
                    label: 'المدينة (اختياري)',
                    icon: Iconsax.buildings,
                  ),
                  const SizedBox(height: AppSpacing.md),
                  _input(
                    controller: _merchantGovernorate,
                    label: 'المحافظة (اختياري)',
                    icon: Iconsax.map,
                  ),
                  const SizedBox(height: AppSpacing.md),
                  _input(
                    controller: _websiteUrl,
                    label: 'رابط الموقع الإلكتروني (اختياري)',
                    icon: Iconsax.global,
                    keyboard: TextInputType.url,
                    validator: (v) {
                      if (v == null || v.trim().isEmpty) return null;
                      final urlRegex = RegExp(r'^https?://');
                      return urlRegex.hasMatch(v.trim())
                          ? null
                          : 'يجب أن يبدأ الرابط بـ http:// أو https://';
                    },
                  ),
                  const SizedBox(height: AppSpacing.md),
                ],

                if (_error != null) ...[
                  const SizedBox(height: AppSpacing.md),
                  Text(_error!,
                      style:
                          TextStyle(color: colors.error, fontSize: 12.5)),
                ],
                const SizedBox(height: AppSpacing.xl),
                AppButton(
                    label: 'تقديم الطلب',
                    loading: _loading,
                    onPressed: _submit),
                const SizedBox(height: AppSpacing.sm),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _input({
    required TextEditingController controller,
    required String label,
    required IconData icon,
    TextInputType? keyboard,
    String? Function(String?)? validator,
    String? hint,
    int maxLines = 1,
  }) {
    final colors = context.appColors;
    return TextFormField(
      controller: controller,
      keyboardType: keyboard,
      validator: validator,
      maxLines: maxLines,
      decoration: InputDecoration(
        labelText: label,
        hintText: hint,
        prefixIcon: Icon(icon, size: 20),
        filled: true,
        fillColor: colors.inputBackground,
        border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(AppRadius.md),
            borderSide: BorderSide.none),
      ),
    );
  }
}

// ════════════════════════════════════════════════════════════════════
// Service checkbox row (cash_in / cash_out)
// ════════════════════════════════════════════════════════════════════
class _ServiceCheckboxRow extends StatelessWidget {
  final String value;
  final String label;
  final IconData icon;
  final bool selected;
  final ValueChanged<bool> onToggle;

  const _ServiceCheckboxRow({
    required this.value,
    required this.label,
    required this.icon,
    required this.selected,
    required this.onToggle,
  });

  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    return InkWell(
      onTap: () => onToggle(!selected),
      borderRadius: BorderRadius.circular(AppRadius.sm),
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 6),
        child: Row(
          children: [
            Checkbox(
              value: selected,
              onChanged: (v) => onToggle(v ?? false),
              activeColor: colors.primary,
              visualDensity: VisualDensity.compact,
            ),
            Icon(icon, size: 18, color: colors.textSecondary),
            const SizedBox(width: AppSpacing.sm),
            Text(label,
                style:
                    TextStyle(fontSize: 14, color: colors.textPrimary)),
          ],
        ),
      ),
    );
  }
}

Widget _handle(BuildContext context) => Center(
      child: Container(
        width: 40,
        height: 4,
        decoration: BoxDecoration(
          color: context.appColors.textHint.withValues(alpha: 0.4),
          borderRadius: BorderRadius.circular(2),
        ),
      ),
    );
