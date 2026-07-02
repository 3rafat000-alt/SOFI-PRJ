import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:iconsax/iconsax.dart';
import 'package:image_picker/image_picker.dart';
import 'package:dio/dio.dart';

import '../../../../core/theme/app_colors.dart';
import '../../../../core/network/api_client.dart';
import '../../../../core/constants/api_constants.dart';
import '../../../../core/widgets/app_ui.dart';
import '../../../../core/widgets/user_avatar.dart';
import '../../../auth/data/repositories/auth_repository.dart';
import '../../../auth/providers/auth_provider.dart';

class ProfileEditPage extends ConsumerStatefulWidget {
  const ProfileEditPage({super.key});

  @override
  ConsumerState<ProfileEditPage> createState() => _ProfileEditPageState();
}

class _ProfileEditPageState extends ConsumerState<ProfileEditPage> {
  final _firstName = TextEditingController();
  final _lastName = TextEditingController();
  final _phone = TextEditingController();
  final _dateOfBirth = TextEditingController();
  String _gender = '';
  File? _avatarFile;
  bool _saving = false;
  bool _uploading = false;

  static const _genders = [
    {'value': 'male', 'label': 'ذكر', 'icon': Iconsax.man},
    {'value': 'female', 'label': 'أنثى', 'icon': Iconsax.woman},
  ];

  @override
  void initState() {
    super.initState();
    final u = ref.read(currentUserProvider);
    if (u != null) {
      _firstName.text = u.firstName;
      _lastName.text = u.lastName;
      _phone.text = u.phone ?? '';
      _dateOfBirth.text = u.dateOfBirth ?? '';
      _gender = u.gender ?? '';
    }
  }

  @override
  void dispose() {
    _firstName.dispose();
    _lastName.dispose();
    _phone.dispose();
    _dateOfBirth.dispose();
    super.dispose();
  }

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

  Future<void> _pickAvatar() async {
    if (_uploading) return;
    final img = await ImagePicker().pickImage(
        source: ImageSource.gallery, imageQuality: 80, maxWidth: 1024, maxHeight: 1024);
    if (img == null) return;

    final bytes = await img.length();
    if (bytes > 2 * 1024 * 1024) {
      _snack('حجم الصورة يجب أن لا يتجاوز 2 ميجابايت', ok: false);
      return;
    }

    setState(() {
      _avatarFile = File(img.path);
      _uploading = true;
    });
    try {
      final dio = ref.read(dioProvider);
      final form = FormData.fromMap(
          {'avatar': await MultipartFile.fromFile(img.path, filename: img.name)});
      final res = await dio.post('${ApiConstants.updateProfile}/avatar', data: form);
      final avatar = res.data['data']?['avatar'];
      if (avatar != null) {
        final u = ref.read(currentUserProvider);
        if (u != null) {
          ref.read(currentUserProvider.notifier).state =
              u.copyWith(avatar: avatar.toString());
        }
        if (mounted) _snack('تم تحديث الصورة الشخصية');
      } else {
        if (mounted) _snack('تعذّر تأكيد رفع الصورة، حاول مجدداً', ok: false);
      }
    } on DioException catch (e) {
      _snack(ApiException.fromDioError(e).message, ok: false);
    } catch (_) {
      _snack('فشل رفع الصورة', ok: false);
    } finally {
      if (mounted) setState(() => _uploading = false);
    }
  }

  Future<void> _showAvatarSheet(String initials, String? avatarUrl, bool hasAvatar,
      AppColorsTheme colors) async {
    final action = await showModalBottomSheet<String>(
      context: context,
      backgroundColor: Colors.transparent,
      builder: (_) => Container(
        decoration: BoxDecoration(
          color: colors.surface,
          borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
        ),
        padding: const EdgeInsets.fromLTRB(24, 16, 24, 28),
        child: Column(mainAxisSize: MainAxisSize.min, children: [
          Container(
              width: 40,
              height: 4,
              decoration: BoxDecoration(
                  color: colors.textHint.withValues(alpha: 0.4),
                  borderRadius: BorderRadius.circular(2))),
          const SizedBox(height: 22),
          _sheetOption(
              Iconsax.gallery_edit, hasAvatar ? 'تحديث الصورة' : 'رفع صورة', 'upload', colors),
          if (hasAvatar) ...[
            const SizedBox(height: 12),
            _sheetOption(Iconsax.trash, 'حذف الصورة', 'delete', colors),
          ],
          const SizedBox(height: 12),
          _sheetOption(Iconsax.close_circle, 'إلغاء', 'cancel', colors, isCancel: true),
        ]),
      ),
    );
    if (action == 'upload') _pickAvatar();
    if (action == 'delete') _deleteAvatar();
  }

  Widget _sheetOption(IconData icon, String label, String value, AppColorsTheme colors,
      {bool isCancel = false}) {
    return GestureDetector(
      onTap: () => Navigator.pop(context, value),
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 14, horizontal: 16),
        decoration: BoxDecoration(
          color: isCancel ? colors.inputBackground : Colors.transparent,
          borderRadius: BorderRadius.circular(14),
        ),
        child: Row(children: [
          Icon(icon, size: 22, color: isCancel ? colors.textSecondary : colors.textPrimary),
          const SizedBox(width: 14),
          Text(label,
              style: TextStyle(
                  fontSize: 15,
                  fontWeight: FontWeight.w600,
                  color: isCancel ? colors.textSecondary : colors.textPrimary)),
        ]),
      ),
    );
  }

  Future<void> _deleteAvatar() async {
    final confirm = await showModalBottomSheet<bool>(
      context: context,
      backgroundColor: Colors.transparent,
      builder: (c) => _confirmSheet(
        icon: Iconsax.trash,
        color: context.appColors.error,
        title: 'حذف الصورة الشخصية',
        message: 'سيتم استبدال صورتك بالحروف الأولى من اسمك.',
        confirmLabel: 'حذف',
      ),
    );
    if (confirm != true) return;
    setState(() => _uploading = true);
    try {
      await ref.read(authRepositoryProvider).deleteAvatar();
      final u = ref.read(currentUserProvider);
      if (u != null) {
        ref.read(currentUserProvider.notifier).state = u.copyWith(clearAvatar: true);
      }
      if (mounted) {
        setState(() => _avatarFile = null);
        _snack('تم حذف الصورة');
      }
    } catch (e) {
      _snack(e.toString(), ok: false);
    } finally {
      if (mounted) setState(() => _uploading = false);
    }
  }

  Future<void> _save({required bool verified}) async {
    if (!verified && (_firstName.text.trim().isEmpty || _lastName.text.trim().isEmpty)) {
      _snack('الاسم الأول واسم العائلة مطلوبان', ok: false);
      return;
    }
    setState(() => _saving = true);
    try {
      // A verified account may only edit its phone — identity fields are locked.
      final user = await ref.read(authRepositoryProvider).updateProfile(
            firstName: verified ? null : _firstName.text.trim(),
            lastName: verified ? null : _lastName.text.trim(),
            phone: _phone.text.trim().isEmpty ? null : _phone.text.trim(),
            dateOfBirth: verified
                ? null
                : (_dateOfBirth.text.trim().isEmpty ? null : _dateOfBirth.text.trim()),
            gender: verified ? null : (_gender.isEmpty ? null : _gender),
          );
      ref.read(currentUserProvider.notifier).state = user;
      if (mounted) {
        _snack('تم حفظ التغييرات بنجاح');
        context.canPop() ? context.pop() : context.go('/dashboard');
      }
    } catch (e) {
      if (mounted) _snack(e.toString(), ok: false);
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  Future<void> _pickDate() async {
    final colors = context.appColors;
    final isDark = Theme.of(context).brightness == Brightness.dark;
    DateTime initial = DateTime.now().subtract(const Duration(days: 365 * 18));
    if (_dateOfBirth.text.isNotEmpty) {
      initial = DateTime.tryParse(_dateOfBirth.text) ?? initial;
    }
    final date = await showDatePicker(
      context: context,
      initialDate: initial,
      firstDate: DateTime(1950),
      lastDate: DateTime.now(),
      builder: (ctx, child) => Theme(
        data: Theme.of(ctx).copyWith(
          colorScheme: (isDark ? const ColorScheme.dark() : const ColorScheme.light())
              .copyWith(
            primary: colors.primary,
            onPrimary: isDark ? colors.background : Colors.white,
            surface: colors.surface,
            onSurface: colors.textPrimary,
          ),
        ),
        child: child!,
      ),
    );
    if (date != null) {
      setState(() => _dateOfBirth.text =
          '${date.year}-${date.month.toString().padLeft(2, '0')}-${date.day.toString().padLeft(2, '0')}');
    }
  }

  String get _genderLabel {
    switch (_gender) {
      case 'male':
        return 'ذكر';
      case 'female':
        return 'أنثى';
      default:
        return '—';
    }
  }

  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    final user = ref.watch(currentUserProvider);
    final initials = user?.initials ?? '؟';
    final avatarUrl = user?.avatarUrl;
    final hasAvatar = (avatarUrl != null && avatarUrl.isNotEmpty) || _avatarFile != null;
    final verified = user?.isKycVerified ?? false;

    return Scaffold(
      backgroundColor: colors.background,
      // top:false keeps the cover hero full-bleed; bottom protects content
      // from the Android system nav bar (edge-to-edge is on).
      body: SafeArea(
        top: false,
        child: SingleChildScrollView(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            _coverHero(user?.fullName ?? 'مستخدم', user?.sakkTag ?? '', initials,
                avatarUrl, hasAvatar, verified, colors),
            Padding(
              padding: const EdgeInsets.fromLTRB(AppSpacing.lg, AppSpacing.lg, AppSpacing.lg, 40),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  _personalInfoSection(verified, colors),
                  const SizedBox(height: AppSpacing.lg),
                  _loginInfoSection(user?.email ?? '-', user?.emailVerified ?? false,
                      verified, colors),
                  const SizedBox(height: AppSpacing.xxl),
                  AppButton(
                    label: 'حفظ التغييرات',
                    icon: Iconsax.tick_circle,
                    loading: _saving,
                    onPressed: () => _save(verified: verified),
                  ),
                  const SizedBox(height: AppSpacing.xxxl),
                  _dangerZone(),
                ],
              ),
            ),
          ],
        ),
      ),
      ),
    );
  }

  // ───────────────────────── Cover hero (banner + overlapping avatar) ─────
  Widget _coverHero(String name, String tag, String initials, String? avatarUrl,
      bool hasAvatar, bool verified, AppColorsTheme colors) {
    return Column(
      children: [
        Stack(
          clipBehavior: Clip.none,
          alignment: Alignment.bottomCenter,
          children: [
            // Gradient cover banner
            Container(
              height: 168,
              width: double.infinity,
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  colors: colors.cardGradientVisa,
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
                borderRadius: const BorderRadius.vertical(bottom: Radius.circular(32)),
              ),
              child: Stack(
                clipBehavior: Clip.hardEdge,
                children: [
                  Positioned(top: -30, left: -20, child: _blob(120, 0.08)),
                  Positioned(bottom: 10, right: -24, child: _blob(96, 0.07)),
                ],
              ),
            ),
            // Back button
            Positioned(
              top: MediaQuery.of(context).padding.top + 8,
              right: 14,
              child: GestureDetector(
                onTap: () => context.canPop() ? context.pop() : context.go('/settings'),
                child: Container(
                  width: 42,
                  height: 42,
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: 0.22),
                    shape: BoxShape.circle,
                  ),
                  child: const Icon(Iconsax.arrow_right_3, color: Colors.white, size: 20),
                ),
              ),
            ),
            // Overlapping avatar
            Positioned(
              bottom: -50,
              child: Stack(
                clipBehavior: Clip.none,
                children: [
                    GestureDetector(
                      onTap: _uploading
                          ? null
                          : () => _showAvatarSheet(initials, avatarUrl, hasAvatar, colors),
                      child: _avatarPreview(initials, avatarUrl),
                    ),
                    Positioned(
                      bottom: 2,
                      right: 2,
                      child: Material(
                        color: Colors.transparent,
                        child: InkWell(
                          onTap: _uploading ? null : _pickAvatar,
                          customBorder: const CircleBorder(),
                          child: Container(
                            width: 36,
                            height: 36,
                            decoration: BoxDecoration(
                              color: colors.primary,
                              shape: BoxShape.circle,
                              border: Border.all(color: colors.surface, width: 3),
                            ),
                            child: _uploading
                                ? const Padding(
                                    padding: EdgeInsets.all(9),
                                    child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                                : Icon(Iconsax.camera,
                                    size: 16,
                                    color: Theme.of(context).brightness == Brightness.dark
                                        ? colors.background
                                        : Colors.white),
                          ),
                        ),
                      ),
                    ),
                ],
              ),
            ),
          ],
        ),
        const SizedBox(height: 58),
        Text(name,
            textAlign: TextAlign.center,
            style: TextStyle(fontSize: 21, fontWeight: FontWeight.w800, color: colors.textPrimary)),
        const SizedBox(height: 8),
        Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            if (tag.isNotEmpty)
              Container(
                padding: const EdgeInsets.symmetric(horizontal: AppSpacing.md, vertical: 5),
                decoration: BoxDecoration(
                  color: colors.inputBackground,
                  borderRadius: BorderRadius.circular(AppRadius.sm),
                ),
                child: Text(
                  tag,
                  textDirection: TextDirection.ltr,
                  style: TextStyle(
                    color: colors.textSecondary,
                    fontSize: 12.5,
                    fontWeight: FontWeight.w700,
                    fontFamily: 'monospace',
                    letterSpacing: 1,
                  ),
                ),
              ),
            const SizedBox(width: AppSpacing.sm),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
              decoration: BoxDecoration(
                color: (verified ? colors.success : colors.warning).withValues(alpha: 0.14),
                borderRadius: BorderRadius.circular(AppRadius.sm),
              ),
              child: Row(mainAxisSize: MainAxisSize.min, children: [
                Icon(verified ? Iconsax.verify5 : Iconsax.info_circle,
                    size: 14, color: verified ? colors.success : colors.warning),
                const SizedBox(width: 4),
                Text(verified ? 'موثّق' : 'غير موثّق',
                    style: TextStyle(
                        color: verified ? colors.success : colors.warning,
                        fontSize: 11.5,
                        fontWeight: FontWeight.w700)),
              ]),
            ),
          ],
        ),
        if (hasAvatar) ...[
          const SizedBox(height: AppSpacing.md),
          Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              _miniAction(Iconsax.gallery_edit, 'تغيير', _uploading ? null : _pickAvatar, colors),
              const SizedBox(width: AppSpacing.md),
              _miniAction(Iconsax.trash, 'حذف', _uploading ? null : _deleteAvatar, colors),
            ],
          ),
        ],
      ],
    );
  }

  Widget _blob(double size, double opacity) => Container(
        width: size,
        height: size,
        decoration: BoxDecoration(
          color: Colors.white.withValues(alpha: opacity),
          shape: BoxShape.circle,
        ),
      );

  Widget _avatarPreview(String initials, String? avatarUrl) {
    final colors = context.appColors;
    final shadow = [
      BoxShadow(color: Colors.black.withValues(alpha: 0.28), blurRadius: 18, offset: const Offset(0, 8))
    ];
    if (_avatarFile != null) {
      return Container(
        width: 104,
        height: 104,
        decoration: BoxDecoration(
            shape: BoxShape.circle,
            border: Border.all(color: colors.surface, width: 4),
            boxShadow: shadow),
        child: ClipOval(child: Image.file(_avatarFile!, fit: BoxFit.cover)),
      );
    }
    return UserAvatar(
      imageUrl: avatarUrl,
      initials: initials,
      size: 104,
      fontSize: 38,
      borderColor: colors.surface,
      borderWidth: 4,
      shadow: shadow,
    );
  }

  Widget _miniAction(IconData icon, String label, VoidCallback? onTap, AppColorsTheme colors) =>
      GestureDetector(
        onTap: onTap,
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
          decoration: BoxDecoration(
              color: colors.inputBackground, borderRadius: BorderRadius.circular(AppRadius.md)),
          child: Row(mainAxisSize: MainAxisSize.min, children: [
            Icon(icon, size: 15, color: colors.primary),
            const SizedBox(width: 6),
            Text(label,
                style: TextStyle(
                    color: colors.textPrimary, fontSize: 12.5, fontWeight: FontWeight.w600)),
          ]),
        ),
      );

  // ───────────────────────── Personal info ─────────────────────────
  Widget _personalInfoSection(bool verified, AppColorsTheme colors) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Row(
          children: [
            Text('المعلومات الشخصية',
                style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800, color: colors.textPrimary)),
            const Spacer(),
            if (verified)
              Row(children: [
                Icon(Iconsax.lock_1, size: 13, color: colors.success),
                const SizedBox(width: 3),
                Text('محمية',
                    style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: colors.success)),
              ]),
          ],
        ),
        const SizedBox(height: AppSpacing.sm),
        if (verified)
          Container(
            margin: const EdgeInsets.only(bottom: AppSpacing.sm),
            padding: const EdgeInsets.all(AppSpacing.md),
            decoration: BoxDecoration(
              color: colors.successLight,
              borderRadius: BorderRadius.circular(AppRadius.md),
            ),
            child: Row(children: [
              Icon(Iconsax.shield_tick, size: 18, color: colors.success),
              const SizedBox(width: 10),
              Expanded(
                child: Text(
                  'لا يمكن تعديل الاسم وتاريخ الميلاد والجنس بعد توثيق الحساب لحماية هويتك.',
                  style: TextStyle(fontSize: 12, height: 1.5, color: colors.textSecondary),
                ),
              ),
            ]),
          ),
        AppCard(
          child: verified
              ? Column(children: [
                  _lockedRow('الاسم الأول', _firstName.text, Iconsax.user, colors),
                  Divider(height: 22, color: colors.inputBackground),
                  _lockedRow('اسم العائلة', _lastName.text, Iconsax.user, colors),
                  Divider(height: 22, color: colors.inputBackground),
                  _lockedRow('تاريخ الميلاد',
                      _dateOfBirth.text.isEmpty ? '—' : _dateOfBirth.text, Iconsax.calendar, colors,
                      ltr: true),
                  Divider(height: 22, color: colors.inputBackground),
                  _lockedRow('الجنس', _genderLabel, Iconsax.profile_2user, colors),
                ])
              : Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
                  _field(_firstName, 'الاسم الأول', Iconsax.user, hint: 'أحمد'),
                  const SizedBox(height: AppSpacing.lg),
                  _field(_lastName, 'اسم العائلة', Iconsax.user, hint: 'محمد'),
                  const SizedBox(height: AppSpacing.lg),
                  _dateField(),
                  const SizedBox(height: AppSpacing.lg),
                  _genderField(),
                ]),
        ),
      ],
    );
  }

  Widget _lockedRow(String label, String value, IconData icon, AppColorsTheme colors,
      {bool ltr = false}) {
    // Dim the whole row so a locked/verified field reads as non-interactive
    // at a glance, distinct from the full-opacity editable fields above it.
    return Opacity(
      opacity: 0.55,
      child: Row(children: [
        Container(
          width: 38,
          height: 38,
          decoration: BoxDecoration(
              color: colors.inputBackground, borderRadius: BorderRadius.circular(11)),
          child: Icon(icon, size: 18, color: colors.textSecondary),
        ),
        const SizedBox(width: AppSpacing.md),
        Expanded(
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text(label, style: TextStyle(fontSize: 12, color: colors.textSecondary)),
            const SizedBox(height: 2),
            Text(value.isEmpty ? '—' : value,
                textDirection: ltr ? TextDirection.ltr : null,
                style: TextStyle(
                    fontSize: 14.5, fontWeight: FontWeight.w600, color: colors.textSecondary)),
          ]),
        ),
        Icon(Iconsax.lock_1, size: 16, color: colors.textHint),
      ]),
    );
  }

  // ───────────────────────── Login & verification ─────────────────────────
  Widget _loginInfoSection(String email, bool emailVerified, bool verified, AppColorsTheme colors) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Text('معلومات الدخول والتواصل',
            style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800, color: colors.textPrimary)),
        const SizedBox(height: AppSpacing.sm),
        AppCard(
          child: Column(children: [
            _emailRow(email, emailVerified, colors),
            Divider(height: 24, color: colors.inputBackground),
            _field(_phone, 'رقم الهاتف', Iconsax.call,
                hint: '+9639XXXXXXXX', keyboard: TextInputType.phone, ltr: true),
            Divider(height: 24, color: colors.inputBackground),
            _passwordRow(colors),
          ]),
        ),
        const SizedBox(height: AppSpacing.md),
        _verifyCta(verified, colors),
      ],
    );
  }

  Widget _emailRow(String email, bool emailVerified, AppColorsTheme colors) {
    return Row(children: [
      const IconTile(icon: Iconsax.sms),
      const SizedBox(width: AppSpacing.md),
      Expanded(
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text('البريد الإلكتروني', style: TextStyle(fontSize: 12, color: colors.textSecondary)),
          const SizedBox(height: 2),
          Text(email,
              textDirection: TextDirection.ltr,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: TextStyle(
                  fontSize: 14, fontWeight: FontWeight.w600, color: colors.textPrimary)),
        ]),
      ),
      const SizedBox(width: AppSpacing.sm),
      if (emailVerified)
        const StatusBadge(label: 'موثّق', kind: StatusKind.success)
      else
        GestureDetector(
          onTap: () => context.push('/kyc'),
          child: Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
            decoration: BoxDecoration(
              color: colors.primary,
              borderRadius: BorderRadius.circular(AppRadius.pill),
            ),
            child: Text('توثيق',
                style: TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.w700,
                    color: Theme.of(context).brightness == Brightness.dark
                        ? colors.background
                        : Colors.white)),
          ),
        ),
    ]);
  }

  Widget _passwordRow(AppColorsTheme colors) {
    return GestureDetector(
      onTap: () => context.push('/settings/security'),
      behavior: HitTestBehavior.opaque,
      child: Row(children: [
        const IconTile(icon: Iconsax.lock),
        const SizedBox(width: AppSpacing.md),
        Expanded(
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text('كلمة المرور', style: TextStyle(fontSize: 12, color: colors.textSecondary)),
            const SizedBox(height: 2),
            Text('••••••••',
                style: TextStyle(
                    fontSize: 14, fontWeight: FontWeight.w600, color: colors.textPrimary)),
          ]),
        ),
        Text('تغيير', style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.w700, color: colors.primary)),
        const SizedBox(width: 4),
        Icon(Iconsax.arrow_left_2, size: 15, color: colors.textHint),
      ]),
    );
  }

  Widget _verifyCta(bool verified, AppColorsTheme colors) {
    if (verified) {
      return Container(
        padding: const EdgeInsets.all(AppSpacing.lg),
        decoration: BoxDecoration(
          color: colors.successLight,
          borderRadius: BorderRadius.circular(AppRadius.lg),
        ),
        child: Row(children: [
          Icon(Iconsax.verify5, color: colors.success, size: 24),
          const SizedBox(width: 12),
          Expanded(
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text('حساب موثّق بالكامل',
                  style: TextStyle(fontSize: 14.5, fontWeight: FontWeight.w800, color: colors.success)),
              const SizedBox(height: 2),
              Text('تم تأكيد هويتك — لديك وصول كامل لجميع الميزات.',
                  style: TextStyle(fontSize: 12, color: colors.textSecondary)),
            ]),
          ),
        ]),
      );
    }
    return GestureDetector(
      onTap: () => context.push('/kyc'),
      child: Container(
        padding: const EdgeInsets.all(AppSpacing.lg),
        decoration: BoxDecoration(
          gradient: LinearGradient(colors: colors.cardGradientVisa),
          borderRadius: BorderRadius.circular(AppRadius.lg),
          boxShadow: [
            BoxShadow(color: Colors.black.withValues(alpha: 0.2), blurRadius: 14, offset: const Offset(0, 6)),
          ],
        ),
        child: Row(children: [
          Container(
            width: 46,
            height: 46,
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.18),
              borderRadius: BorderRadius.circular(13),
            ),
            child: const Icon(Iconsax.shield_tick, color: Colors.white, size: 24),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              const Text('وثّق حسابك الآن',
                  style: TextStyle(fontSize: 15, fontWeight: FontWeight.w800, color: Colors.white)),
              const SizedBox(height: 3),
              Text('ارفع حدودك واحمِ بياناتك بتأكيد هويتك.',
                  style: TextStyle(fontSize: 12, color: Colors.white.withValues(alpha: 0.85))),
            ]),
          ),
          const Icon(Iconsax.arrow_left_2, color: Colors.white, size: 18),
        ]),
      ),
    );
  }

  // ───────────────────────── Editable fields ─────────────────────────
  Widget _field(TextEditingController c, String label, IconData icon,
      {String? hint, TextInputType keyboard = TextInputType.text, bool ltr = false}) {
    final colors = context.appColors;
    return Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      Text(label,
          style: TextStyle(
              fontSize: 13, fontWeight: FontWeight.w600, color: colors.textSecondary)),
      const SizedBox(height: AppSpacing.sm),
      TextFormField(
        controller: c,
        keyboardType: keyboard,
        textDirection: ltr ? TextDirection.ltr : null,
        style: TextStyle(
            fontSize: 15, color: colors.textPrimary, fontWeight: FontWeight.w500),
        decoration: InputDecoration(
          hintText: hint,
          hintStyle: TextStyle(color: colors.textHint, fontWeight: FontWeight.normal),
          prefixIcon: Icon(icon, color: colors.primary, size: 20),
          filled: true,
          fillColor: colors.inputBackground,
          contentPadding: const EdgeInsets.symmetric(vertical: 16),
          border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(AppRadius.md), borderSide: BorderSide.none),
          enabledBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(AppRadius.md), borderSide: BorderSide.none),
          focusedBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(AppRadius.md),
              borderSide: BorderSide(color: colors.primary, width: 1.6)),
        ),
      ),
    ]);
  }

  Widget _dateField() {
    final colors = context.appColors;
    return Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      Text('تاريخ الميلاد',
          style: TextStyle(
              fontSize: 13, fontWeight: FontWeight.w600, color: colors.textSecondary)),
      const SizedBox(height: AppSpacing.sm),
      GestureDetector(
        onTap: _pickDate,
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 16),
          decoration: BoxDecoration(
              color: colors.inputBackground, borderRadius: BorderRadius.circular(AppRadius.md)),
          child: Row(children: [
            Icon(Iconsax.calendar, color: colors.primary, size: 20),
            const SizedBox(width: AppSpacing.md),
            Expanded(
              child: Text(
                _dateOfBirth.text.isEmpty ? 'اختر التاريخ' : _dateOfBirth.text,
                textDirection: TextDirection.ltr,
                textAlign: TextAlign.right,
                style: TextStyle(
                    fontSize: 15,
                    fontWeight: FontWeight.w500,
                    color: _dateOfBirth.text.isEmpty ? colors.textHint : colors.textPrimary),
              ),
            ),
            Icon(Iconsax.arrow_down_1, size: 16, color: colors.textHint),
          ]),
        ),
      ),
    ]);
  }

  Widget _genderField() {
    final colors = context.appColors;
    return Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      Text('الجنس',
          style: TextStyle(
              fontSize: 13, fontWeight: FontWeight.w600, color: colors.textSecondary)),
      const SizedBox(height: AppSpacing.sm),
      Row(
          children: _genders.map((g) {
        final selected = _gender == g['value'];
        return Expanded(
          child: Padding(
            padding: EdgeInsets.only(
                left: g['value'] == 'male' ? 6 : 0, right: g['value'] == 'female' ? 6 : 0),
            child: GestureDetector(
              onTap: () => setState(() => _gender = g['value']! as String),
              child: AnimatedContainer(
                duration: const Duration(milliseconds: 180),
                padding: const EdgeInsets.symmetric(vertical: 14),
                decoration: BoxDecoration(
                  color: selected ? colors.primary : colors.inputBackground,
                  borderRadius: BorderRadius.circular(AppRadius.md),
                ),
                child: Row(mainAxisAlignment: MainAxisAlignment.center, children: [
                  Icon(g['icon'] as IconData,
                      size: 18,
                      color: selected
                          ? (Theme.of(context).brightness == Brightness.dark
                              ? colors.background
                              : Colors.white)
                          : colors.textSecondary),
                  const SizedBox(width: AppSpacing.sm),
                  Text(g['label']! as String,
                      style: TextStyle(
                          fontSize: 15,
                          fontWeight: FontWeight.w600,
                          color: selected
                              ? (Theme.of(context).brightness == Brightness.dark
                                  ? colors.background
                                  : Colors.white)
                              : colors.textSecondary)),
                ]),
              ),
            ),
          ),
        );
      }).toList()),
    ]);
  }

  // ───────────────────────── Danger zone ─────────────────────────
  Widget _dangerZone() {
    final colors = context.appColors;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Row(children: [
          Icon(Iconsax.danger, size: 18, color: colors.error),
          const SizedBox(width: 6),
          Text('منطقة الخطر',
              style: TextStyle(fontSize: 15, fontWeight: FontWeight.w700, color: colors.error)),
        ]),
        const SizedBox(height: AppSpacing.sm),
        Container(
          padding: const EdgeInsets.all(AppSpacing.lg),
          decoration: BoxDecoration(
            color: colors.error.withValues(alpha: 0.06),
            borderRadius: BorderRadius.circular(AppRadius.lg),
            border: Border.all(color: colors.error.withValues(alpha: 0.25)),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Text('حذف الحساب',
                  style: TextStyle(
                      fontSize: 15, fontWeight: FontWeight.w700, color: colors.textPrimary)),
              const SizedBox(height: 6),
              Text(
                'سيتم تعطيل حسابك نهائياً وإلغاء جميع بطاقاتك وحذف بياناتك. لا يمكن التراجع عن هذا الإجراء. يجب سحب رصيدك أولاً.',
                style: TextStyle(fontSize: 12.5, height: 1.6, color: colors.textSecondary),
              ),
              const SizedBox(height: AppSpacing.lg),
              SizedBox(
                height: 50,
                child: OutlinedButton.icon(
                  onPressed: _deleteAccount,
                  icon: Icon(Iconsax.trash, size: 19, color: colors.error),
                  label: Text('حذف حسابي',
                      style: TextStyle(
                          fontSize: 15, fontWeight: FontWeight.w700, color: colors.error)),
                  style: OutlinedButton.styleFrom(
                    side: BorderSide(color: colors.error, width: 1.4),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.md)),
                  ),
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }

  // ───────────────────────── Delete account flow ─────────────────────────
  Future<void> _deleteAccount() async {
    final result = await showModalBottomSheet<_DeleteConfirmResult>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => const _DeleteAccountSheet(),
    );
    if (result == null) return;

    if (mounted) {
      showDialog(
        context: context,
        barrierDismissible: false,
        builder: (_) => const Center(child: CircularProgressIndicator()),
      );
    }

    try {
      await ref.read(authRepositoryProvider).deleteAccount(
            password: result.password,
            reason: result.reason,
          );
      if (!mounted) return;
      Navigator.of(context, rootNavigator: true).pop();
      ref.read(currentUserProvider.notifier).state = null;
      ref.invalidate(authStateProvider);
      _snack('تم حذف حسابك بنجاح');
      context.go('/login');
    } catch (e) {
      if (!mounted) return;
      Navigator.of(context, rootNavigator: true).pop();
      _snack(e is ApiException ? e.message : e.toString(), ok: false);
    }
  }

  // ───────────────────────── Generic confirm sheet ─────────────────────────
  Widget _confirmSheet({
    required IconData icon,
    required Color color,
    required String title,
    required String message,
    required String confirmLabel,
  }) {
    final colors = context.appColors;
    return Container(
      decoration: BoxDecoration(
        color: colors.surface,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
      ),
      padding: const EdgeInsets.fromLTRB(24, 16, 24, 28),
      child: Column(mainAxisSize: MainAxisSize.min, children: [
        Container(
            width: 40,
            height: 4,
            decoration: BoxDecoration(
                color: colors.textHint.withValues(alpha: 0.4),
                borderRadius: BorderRadius.circular(2))),
        const SizedBox(height: 22),
        Container(
          width: 64,
          height: 64,
          decoration: BoxDecoration(color: color.withValues(alpha: 0.12), shape: BoxShape.circle),
          child: Icon(icon, color: color, size: 30),
        ),
        const SizedBox(height: 16),
        Text(title,
            style: TextStyle(fontSize: 17, fontWeight: FontWeight.bold, color: colors.textPrimary)),
        const SizedBox(height: 8),
        Text(message,
            textAlign: TextAlign.center,
            style: TextStyle(fontSize: 13, color: colors.textSecondary)),
        const SizedBox(height: 24),
        Row(children: [
          Expanded(
            child: OutlinedButton(
              onPressed: () => Navigator.pop(context, false),
              style: OutlinedButton.styleFrom(
                  padding: const EdgeInsets.symmetric(vertical: 14),
                  side: BorderSide(color: colors.textHint.withValues(alpha: 0.4)),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14))),
              child: Text('إلغاء', style: TextStyle(color: colors.textSecondary)),
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: ElevatedButton(
              onPressed: () => Navigator.pop(context, true),
              style: ElevatedButton.styleFrom(
                  backgroundColor: color,
                  padding: const EdgeInsets.symmetric(vertical: 14),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14))),
              child: Text(confirmLabel, style: const TextStyle(color: Colors.white)),
            ),
          ),
        ]),
      ]),
    );
  }
}

/// Result returned by [_DeleteAccountSheet] on confirmation.
class _DeleteConfirmResult {
  final String password;
  final String? reason;
  const _DeleteConfirmResult(this.password, this.reason);
}

/// Bottom sheet that confirms permanent account deletion. Requires the account
/// password and accepts an optional reason.
class _DeleteAccountSheet extends StatefulWidget {
  const _DeleteAccountSheet();

  @override
  State<_DeleteAccountSheet> createState() => _DeleteAccountSheetState();
}

class _DeleteAccountSheetState extends State<_DeleteAccountSheet> {
  final _password = TextEditingController();
  final _reason = TextEditingController();
  bool _obscure = true;
  bool _ack = false;

  @override
  void dispose() {
    _password.dispose();
    _reason.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    final bottomInset = MediaQuery.of(context).viewInsets.bottom;
    final canConfirm = _ack && _password.text.isNotEmpty;
    return Padding(
      padding: EdgeInsets.only(bottom: bottomInset),
      child: Container(
        decoration: BoxDecoration(
          color: colors.surface,
          borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
        ),
        padding: const EdgeInsets.fromLTRB(24, 16, 24, 28),
        child: Column(mainAxisSize: MainAxisSize.min, children: [
          Container(
              width: 40,
              height: 4,
              decoration: BoxDecoration(
                  color: colors.textHint.withValues(alpha: 0.4),
                  borderRadius: BorderRadius.circular(2))),
          const SizedBox(height: 22),
          Container(
            width: 66,
            height: 66,
            decoration:
                BoxDecoration(color: colors.error.withValues(alpha: 0.12), shape: BoxShape.circle),
            child: Icon(Iconsax.trash, color: colors.error, size: 32),
          ),
          const SizedBox(height: 16),
          Text('حذف الحساب نهائياً',
              style: TextStyle(
                  fontSize: 18, fontWeight: FontWeight.bold, color: colors.textPrimary)),
          const SizedBox(height: 8),
          Text(
            'لتأكيد حذف حسابك، أدخل كلمة المرور. سيتم إلغاء بطاقاتك وحذف بياناتك ولا يمكن التراجع.',
            textAlign: TextAlign.center,
            style: TextStyle(fontSize: 13, height: 1.6, color: colors.textSecondary),
          ),
          const SizedBox(height: 20),
          TextField(
            controller: _password,
            obscureText: _obscure,
            onChanged: (_) => setState(() {}),
            style: TextStyle(color: colors.textPrimary, fontWeight: FontWeight.w500),
            decoration: InputDecoration(
              hintText: 'كلمة المرور',
              hintStyle: TextStyle(color: colors.textHint),
              prefixIcon: Icon(Iconsax.lock, color: colors.primary, size: 20),
              suffixIcon: IconButton(
                icon: Icon(_obscure ? Iconsax.eye_slash : Iconsax.eye,
                    color: colors.textHint, size: 20),
                onPressed: () => setState(() => _obscure = !_obscure),
              ),
              filled: true,
              fillColor: colors.inputBackground,
              border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(AppRadius.md),
                  borderSide: BorderSide.none),
              enabledBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(AppRadius.md),
                  borderSide: BorderSide.none),
              focusedBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(AppRadius.md),
                  borderSide: BorderSide(color: colors.error, width: 1.4)),
            ),
          ),
          const SizedBox(height: 12),
          TextField(
            controller: _reason,
            maxLines: 2,
            maxLength: 500,
            style: TextStyle(color: colors.textPrimary, fontWeight: FontWeight.w500),
            decoration: InputDecoration(
              hintText: 'سبب الحذف (اختياري)',
              hintStyle: TextStyle(color: colors.textHint),
              counterText: '',
              filled: true,
              fillColor: colors.inputBackground,
              contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
              border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(AppRadius.md),
                  borderSide: BorderSide.none),
              enabledBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(AppRadius.md),
                  borderSide: BorderSide.none),
              focusedBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(AppRadius.md),
                  borderSide: BorderSide(color: colors.primary, width: 1.4)),
            ),
          ),
          const SizedBox(height: 4),
          InkWell(
            onTap: () => setState(() => _ack = !_ack),
            borderRadius: BorderRadius.circular(AppRadius.sm),
            child: Padding(
              padding: const EdgeInsets.symmetric(vertical: 6),
              child: Row(children: [
                Icon(_ack ? Iconsax.tick_square5 : Iconsax.square,
                    color: _ack ? colors.error : colors.textHint, size: 22),
                const SizedBox(width: 10),
                Expanded(
                  child: Text('أفهم أن هذا الإجراء نهائي ولا يمكن التراجع عنه.',
                      style: TextStyle(fontSize: 12.5, color: colors.textSecondary)),
                ),
              ]),
            ),
          ),
          const SizedBox(height: 18),
          Row(children: [
            Expanded(
              child: OutlinedButton(
                onPressed: () => Navigator.pop(context),
                style: OutlinedButton.styleFrom(
                    padding: const EdgeInsets.symmetric(vertical: 15),
                    side: BorderSide(color: colors.textHint.withValues(alpha: 0.4)),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14))),
                child: Text('إلغاء', style: TextStyle(color: colors.textSecondary)),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: ElevatedButton(
                onPressed: canConfirm
                    ? () => Navigator.pop(
                        context,
                        _DeleteConfirmResult(
                            _password.text, _reason.text.trim().isEmpty ? null : _reason.text))
                    : null,
                style: ElevatedButton.styleFrom(
                    backgroundColor: colors.error,
                    disabledBackgroundColor: colors.error.withValues(alpha: 0.4),
                    padding: const EdgeInsets.symmetric(vertical: 15),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14))),
                child: const Text('حذف نهائي',
                    style: TextStyle(color: Colors.white, fontWeight: FontWeight.w700)),
              ),
            ),
          ]),
        ]),
      ),
    );
  }
}
