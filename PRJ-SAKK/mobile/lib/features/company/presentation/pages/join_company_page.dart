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
import '../../data/models/company_models.dart';
import '../../data/repositories/company_repository.dart';

/// "Join as a company" (انضم كشركة) — entry point from Settings › الأعمال.
/// No application → registration form. Has one → status + KYC documents.
class JoinCompanyPage extends ConsumerWidget {
  const JoinCompanyPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final colors = context.appColors;
    final stateAsync = ref.watch(companyStateProvider);

    return AppScaffold(
      title: 'انضم كشركة',
      onRefresh: () async => ref.invalidate(companyStateProvider),
      body: stateAsync.when(
        data: (state) {
          final company = state.company;
          return ListView(
            padding: const EdgeInsets.fromLTRB(
                AppSpacing.lg, AppSpacing.md, AppSpacing.lg, AppSpacing.xxxl),
            children: [
              if (company == null) ...[
                const _JoinCompanyHero(),
                const SizedBox(height: AppSpacing.xl),
                _CompanyRegisterCard(
                  onTap: () => _openApplyForm(context, ref),
                ).animate(delay: 100.ms)
                    .fadeIn(duration: 260.ms, curve: Curves.easeOut)
                    .slideY(begin: 0.05, end: 0, duration: 260.ms, curve: Curves.easeOut),
              ] else
                _ApplicationStatusCard(app: company, docTypes: state.docTypes),
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
                    onPressed: () => ref.invalidate(companyStateProvider),
                    child: const Text('إعادة المحاولة')),
              ],
            ),
          ),
        ),
      ),
    );
  }

  void _openApplyForm(BuildContext context, WidgetRef ref) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      useSafeArea: true,
      backgroundColor: Colors.transparent,
      builder: (_) => const _ApplyFormSheet(),
    );
  }
}

// ════════════════════════════════════════════════════════════════════
// Hero Banner — warm stone gradient + damascene medallion + headline
// ════════════════════════════════════════════════════════════════════
class _JoinCompanyHero extends StatelessWidget {
  const _JoinCompanyHero();

  // Warm stone / corporate tone (cardGradientPlatinum family)
  static const List<Color> _heroGradient = [
    Color(0xFF7A3A20), // warm terracotta-burgundy
    Color(0xFF4A1F10),
  ];

  @override
  Widget build(BuildContext context) {
    return ClipRRect(
      borderRadius: BorderRadius.circular(AppRadius.xl),
      child: Stack(
        children: [
          // Gradient background
          Container(
            height: 168,
            decoration: const BoxDecoration(
              gradient: LinearGradient(
                colors: _heroGradient,
                begin: Alignment.topRight,
                end: Alignment.bottomLeft,
              ),
            ),
          ),
          // Damascene medallion watermark
          const Positioned.fill(
            child: IgnorePointer(
              child: CustomPaint(
                painter: DamasceneMedallionPainter(
                  color: Color(0xFFD9B978),
                  opacity: 0.09,
                  alignment: Alignment(1.15, -0.05),
                  radius: 145,
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
                      color:
                          const Color(0xFFB58A3C).withValues(alpha: 0.22),
                      borderRadius:
                          BorderRadius.circular(AppRadius.pill),
                      border: Border.all(
                          color: const Color(0xFFB58A3C)
                              .withValues(alpha: 0.5),
                          width: 0.8),
                    ),
                    child: const Text(
                      'حلول الأعمال',
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
                    'سجّل شركتك ووزّع\nالرواتب بضغطة',
                    style: TextStyle(
                      fontSize: 22,
                      fontWeight: FontWeight.w800,
                      color: Colors.white,
                      height: 1.25,
                    ),
                  ),
                  const SizedBox(height: AppSpacing.xs),
                  Text(
                    'محفظة مستقلة للشركة · رواتب دفعة واحدة',
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
// Company Register Card — rich benefits + CTA affordance
// ════════════════════════════════════════════════════════════════════
class _CompanyRegisterCard extends StatelessWidget {
  final VoidCallback onTap;
  const _CompanyRegisterCard({required this.onTap});

  static const List<Color> _gradient = [
    Color(0xFF7A3A20),
    Color(0xFF4A1F10),
  ];

  static const List<String> _benefits = [
    'رواتب دفعة واحدة لجميع الموظفين',
    'رفع قائمة الموظفين عبر ملف CSV',
    'محفظة شركة مستقلة + تسوية فورية',
  ];

  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    return Material(
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
                color: _gradient.first.withValues(alpha: 0.10),
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
                color: _gradient.first.withValues(alpha: 0.55),
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
                  gradient: const LinearGradient(
                    colors: _gradient,
                    begin: Alignment.topRight,
                    end: Alignment.bottomLeft,
                  ),
                  borderRadius: BorderRadius.circular(AppRadius.lg),
                  boxShadow: [
                    BoxShadow(
                      color: _gradient.first.withValues(alpha: 0.30),
                      blurRadius: 10,
                      offset: const Offset(0, 4),
                    ),
                  ],
                ),
                child: const Icon(Iconsax.building_4,
                    color: Colors.white, size: 28),
              ),
              const SizedBox(width: AppSpacing.md),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.symmetric(
                              horizontal: 7, vertical: 2),
                          decoration: BoxDecoration(
                            color: _gradient.first.withValues(alpha: 0.10),
                            borderRadius:
                                BorderRadius.circular(AppRadius.pill),
                          ),
                          child: Text(
                            'شركة',
                            style: TextStyle(
                              fontSize: 10.5,
                              fontWeight: FontWeight.w700,
                              color: _gradient.first,
                              letterSpacing: 0.3,
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 5),
                    Text(
                      'سجّل شركتك',
                      style: TextStyle(
                        fontSize: 17,
                        fontWeight: FontWeight.w800,
                        color: colors.textPrimary,
                      ),
                    ),
                    const SizedBox(height: AppSpacing.sm),
                    ..._benefits.map((b) => Padding(
                          padding: const EdgeInsets.only(bottom: 4),
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
                    Row(
                      children: [
                        Text(
                          'تسجيل الشركة',
                          style: TextStyle(
                            fontSize: 13,
                            fontWeight: FontWeight.w700,
                            color: _gradient.first,
                          ),
                        ),
                        const SizedBox(width: 4),
                        Icon(Iconsax.arrow_left_2,
                            color: _gradient.first, size: 15),
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
  }
}

// ════════════════════════════════════════════════════════════════════
// Application Status Card (+ documents)
// ════════════════════════════════════════════════════════════════════
class _ApplicationStatusCard extends ConsumerWidget {
  final CompanyApplication app;
  final List<CompanyDocType> docTypes;
  const _ApplicationStatusCard({required this.app, required this.docTypes});

  static const List<Color> _gradient = [
    Color(0xFF7A3A20),
    Color(0xFF4A1F10),
  ];

  StatusKind get _kind => switch (app.kycStatusColor) {
        'success' => StatusKind.success,
        'danger' => StatusKind.error,
        'warning' => StatusKind.warning,
        _ => StatusKind.neutral,
      };

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final colors = context.appColors;
    return AppCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              // Gradient icon tile
              Container(
                width: 46,
                height: 46,
                decoration: BoxDecoration(
                  gradient: const LinearGradient(
                    colors: _gradient,
                    begin: Alignment.topRight,
                    end: Alignment.bottomLeft,
                  ),
                  borderRadius: BorderRadius.circular(AppRadius.md),
                ),
                child: const Icon(Iconsax.building_4,
                    color: Colors.white, size: 22),
              ),
              const SizedBox(width: AppSpacing.md),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('شركة · ${app.code}',
                        style: TextStyle(
                            fontSize: 12, color: colors.textSecondary)),
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

          // ── documents_required: upload prompt ──
          if (app.kycStatus == 'documents_required') ...[
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
                  Icon(Iconsax.document_upload,
                      color: colors.warning, size: 18),
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
          if (app.kycStatus == 'rejected') ...[
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
                      Icon(Iconsax.close_circle,
                          color: colors.error, size: 18),
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
                      padding:
                          const EdgeInsetsDirectional.only(start: 26),
                      child: Text(
                        'السبب: ${app.rejectionReason}',
                        style: TextStyle(
                            fontSize: 12.5,
                            color: colors.error,
                            height: 1.4),
                      ),
                    ),
                  ],
                  const SizedBox(height: AppSpacing.sm),
                  Padding(
                    padding:
                        const EdgeInsetsDirectional.only(start: 26),
                    child: Text(
                      'يمكنك رفع مستندات جديدة وسيُعاد تقييم طلبك.',
                      style: TextStyle(
                          fontSize: 12,
                          color: colors.textSecondary,
                          height: 1.4),
                    ),
                  ),
                ],
              ),
            ),
          ],

          // ── pending: "under review" note ──
          if (!app.isApproved &&
              app.kycStatus != 'rejected' &&
              app.kycStatus != 'documents_required') ...[
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
                          fontSize: 12.5,
                          color: colors.primary,
                          height: 1.4),
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
              padding:
                  const EdgeInsets.symmetric(vertical: AppSpacing.sm),
              child: Text('لم يُرفع أي مستند بعد.',
                  style:
                      TextStyle(fontSize: 13, color: colors.textHint)),
            ),

          ...app.documents.map((d) => _docRow(context, d)),

          if (!app.isApproved) ...[
            const SizedBox(height: AppSpacing.sm),
            _UploadButton(
              docTypes: docTypes,
              uploadedTypes:
                  app.documents.map((d) => d.documentType).toSet(),
            ),
          ],
        ],
      ),
    );
  }

  Widget _docRow(BuildContext context, CompanyDocument d) {
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
                style: TextStyle(
                    fontSize: 13.5, color: colors.textPrimary)),
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
  final List<CompanyDocType> docTypes;
  final Set<String> uploadedTypes;
  const _UploadButton({required this.docTypes, required this.uploadedTypes});

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
      await ref.read(companyRepositoryProvider).uploadDocument(
            documentType: docType,
            file: File(picked.path),
          );
      ref.invalidate(companyStateProvider);
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
  final List<CompanyDocType> docTypes;
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
                  style:
                      TextStyle(fontSize: 17, fontWeight: FontWeight.bold)),
            ],
          ),
          const SizedBox(height: AppSpacing.md),
          ...docTypes.map((t) {
            final done = uploadedTypes.contains(t.key);
            return ListTile(
              contentPadding: EdgeInsets.zero,
              leading: Icon(done ? Iconsax.tick_circle : Iconsax.document,
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
// Apply Form Sheet — all fields per API contract
// ════════════════════════════════════════════════════════════════════
class _ApplyFormSheet extends ConsumerStatefulWidget {
  const _ApplyFormSheet();

  @override
  ConsumerState<_ApplyFormSheet> createState() => _ApplyFormSheetState();
}

class _ApplyFormSheetState extends ConsumerState<_ApplyFormSheet> {
  final _formKey = GlobalKey<FormState>();
  final _name = TextEditingController();
  final _legalName = TextEditingController();
  final _ownerName = TextEditingController();
  final _phone = TextEditingController();
  final _email = TextEditingController();
  final _taxId = TextEditingController();
  final _commercialRegister = TextEditingController();
  final _address = TextEditingController();
  final _city = TextEditingController();
  final _governorate = TextEditingController();
  bool _loading = false;
  String? _error;

  static const List<Color> _gradient = [
    Color(0xFF7A3A20),
    Color(0xFF4A1F10),
  ];

  @override
  void dispose() {
    _name.dispose();
    _legalName.dispose();
    _ownerName.dispose();
    _phone.dispose();
    _email.dispose();
    _taxId.dispose();
    _commercialRegister.dispose();
    _address.dispose();
    _city.dispose();
    _governorate.dispose();
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
      await ref.read(companyRepositoryProvider).apply(
            name: _name.text.trim(),
            legalName: _opt(_legalName),
            ownerName: _opt(_ownerName),
            phone: _opt(_phone),
            email: _opt(_email),
            taxId: _opt(_taxId),
            commercialRegister: _opt(_commercialRegister),
            address: _opt(_address),
            city: _opt(_city),
            governorate: _opt(_governorate),
          );
      ref.invalidate(companyStateProvider);
      if (!mounted) return;
      Navigator.pop(context);
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(
        content: const Text('تم تسجيل الشركة. ارفع المستندات الآن.'),
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
                // Sheet header with gradient icon + gold accent bar
                Row(
                  children: [
                    Container(
                      width: 36,
                      height: 36,
                      decoration: BoxDecoration(
                        gradient: const LinearGradient(
                          colors: _gradient,
                          begin: Alignment.topRight,
                          end: Alignment.bottomLeft,
                        ),
                        borderRadius: BorderRadius.circular(AppRadius.md),
                      ),
                      child: const Icon(Iconsax.building_4,
                          color: Colors.white, size: 18),
                    ),
                    const SizedBox(width: AppSpacing.md),
                    const Text(
                      'تسجيل شركة',
                      style: TextStyle(
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
                _input(
                  controller: _name,
                  label: 'اسم الشركة',
                  icon: Iconsax.building_4,
                  validator: (v) =>
                      (v == null || v.trim().isEmpty) ? 'مطلوب' : null,
                ),
                const SizedBox(height: AppSpacing.md),
                _input(
                  controller: _legalName,
                  label: 'الاسم القانوني (اختياري)',
                  icon: Iconsax.document_text,
                ),
                const SizedBox(height: AppSpacing.md),
                _input(
                  controller: _ownerName,
                  label: 'اسم المالك / المفوّض (اختياري)',
                  icon: Iconsax.user,
                ),
                const SizedBox(height: AppSpacing.md),
                _input(
                  controller: _phone,
                  label: 'رقم الهاتف (اختياري)',
                  icon: Iconsax.call,
                  keyboard: TextInputType.phone,
                ),
                const SizedBox(height: AppSpacing.md),
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
                  controller: _commercialRegister,
                  label: 'رقم السجل التجاري (اختياري)',
                  icon: Iconsax.receipt_text,
                ),
                const SizedBox(height: AppSpacing.md),
                _input(
                  controller: _taxId,
                  label: 'الرقم الضريبي (اختياري)',
                  icon: Iconsax.percentage_square,
                ),
                const SizedBox(height: AppSpacing.md),
                _input(
                  controller: _address,
                  label: 'العنوان (اختياري)',
                  icon: Iconsax.location,
                ),
                const SizedBox(height: AppSpacing.md),
                _input(
                  controller: _city,
                  label: 'المدينة (اختياري)',
                  icon: Iconsax.buildings,
                ),
                const SizedBox(height: AppSpacing.md),
                _input(
                  controller: _governorate,
                  label: 'المحافظة (اختياري)',
                  icon: Iconsax.map,
                ),
                if (_error != null) ...[
                  const SizedBox(height: AppSpacing.md),
                  Text(_error!,
                      style: TextStyle(
                          color: colors.error, fontSize: 12.5)),
                ],
                const SizedBox(height: AppSpacing.xl),
                AppButton(
                    label: 'تسجيل الشركة',
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
  }) {
    final colors = context.appColors;
    return TextFormField(
      controller: controller,
      keyboardType: keyboard,
      validator: validator,
      decoration: InputDecoration(
        labelText: label,
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
