import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:iconsax/iconsax.dart';
import 'package:local_auth/local_auth.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../../core/network/api_client.dart';
import '../../../../core/theme/app_colors.dart';
import '../../../../core/widgets/app_ui.dart';
import '../../../auth/data/repositories/auth_repository.dart';
import '../../data/repositories/telegram_repository.dart';

class SecurityPage extends ConsumerStatefulWidget {
  const SecurityPage({super.key});
  @override
  ConsumerState<SecurityPage> createState() => _SecurityPageState();
}

class _SecurityPageState extends ConsumerState<SecurityPage> {
  final _curPw = TextEditingController();
  final _newPw = TextEditingController();
  final _confPw = TextEditingController();
  bool _pwBusy = false;
  bool _bioAvailable = true, _bioLoaded = false, _bioEnabled = false;
  int _expanded = -1;

  bool _showCur = false, _showNew = false, _showConf = false;

  @override
  void initState() {
    super.initState();
    Future.delayed(const Duration(milliseconds: 300), _checkBio);
    WidgetsBinding.instance.addPostFrameCallback((_) => _syncFromServer());
  }

  @override
  void dispose() {
    _curPw.dispose();
    _newPw.dispose();
    _confPw.dispose();
    super.dispose();
  }

  Future<void> _checkBio() async {
    try {
      final la = LocalAuthentication();
      final types = await la.getAvailableBiometrics();
      final hasHW = await la.isDeviceSupported();
      final ok = types.isNotEmpty || (hasHW && await la.canCheckBiometrics);
      final enabled = await ref.read(authRepositoryProvider).isBiometricEnabled();
      if (mounted) {
        setState(() {
          _bioAvailable = ok;
          _bioEnabled = enabled;
          _bioLoaded = true;
        });
      }
    } catch (_) {
      if (mounted) setState(() => _bioLoaded = true);
    }
  }

  // Pull the authoritative state (hasPin, twoFactorEnabled, ...) from /me so the
  // toggles always reflect the backend truth, not a stale login snapshot.
  // NOTE: silent:true is REQUIRED — it prevents an auto-logout on transient errors.
  Future<void> _syncFromServer() async {
    try {
      final fresh =
          await ref.read(authRepositoryProvider).getCurrentUser(silent: true);
      if (!mounted) return;
      // Defer the global-provider update to after the current frame. Setting it
      // synchronously here can hit a still-registered but deactivated consumer
      // (this page mid-navigation) and crash with a defunct-element assertion.
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (mounted) ref.read(currentUserProvider.notifier).state = fresh;
      });
    } catch (_) {/* keep last known state */}
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

  bool get _has2FA => ref.watch(currentUserProvider)?.twoFactorEnabled ?? false;

  int get _score =>
      (_has2FA ? 1 : 0) + (_bioEnabled && _bioAvailable ? 1 : 0);

  // ═══════════ Password ═══════════
  Future<void> _changePassword() async {
    if (_newPw.text.length < 8) {
      _snack('8 أحرف على الأقل', ok: false);
      return;
    }
    if (_newPw.text != _confPw.text) {
      _snack('كلمتا المرور غير متطابقتين', ok: false);
      return;
    }
    setState(() => _pwBusy = true);
    try {
      await ref.read(authRepositoryProvider).changePassword(
          currentPassword: _curPw.text,
          newPassword: _newPw.text,
          newPasswordConfirmation: _confPw.text);
      _curPw.clear();
      _newPw.clear();
      _confPw.clear();
      _snack('تم تغيير كلمة المرور');
      setState(() => _expanded = -1);
    } catch (e) {
      _snack(e.toString(), ok: false);
    } finally {
      setState(() => _pwBusy = false);
    }
  }

  // ═══════════ 2FA ═══════════
  Future<void> _enable2FA() async {
    final colors = context.appColors;
    final isDark = Theme.of(context).brightness == Brightness.dark;
    try {
      final data = await ref.read(authRepositoryProvider).twoFactorSetup();
      if (!mounted) return;
      final code = TextEditingController();
      showDialog(
        context: context,
        builder: (ctx) => AlertDialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
          contentPadding: EdgeInsets.zero,
          content: Column(mainAxisSize: MainAxisSize.min, children: [
            _dialogHeader(
                'تفعيل المصادقة الثنائية',
                'امسح رمز QR باستخدام Google Authenticator',
                Iconsax.security_safe),
            Padding(
              padding: const EdgeInsets.fromLTRB(24, 20, 24, 0),
              child: Column(mainAxisSize: MainAxisSize.min, children: [
                if (data['qr_code'] != null)
                  ClipRRect(
                    borderRadius: BorderRadius.circular(12),
                    child: Image.network(data['qr_code'], height: 160, fit: BoxFit.contain),
                  ),
                if (data['secret'] != null) ...[
                  const SizedBox(height: 12),
                  Container(
                    padding: const EdgeInsets.all(10),
                    decoration: BoxDecoration(
                        color: colors.inputBackground,
                        borderRadius: BorderRadius.circular(8)),
                  child: SelectableText(data['secret'],
                        textDirection: TextDirection.ltr,
                        style: const TextStyle(
                            fontSize: 12, fontFamily: 'monospace')),
                  ),
                ],
                const SizedBox(height: 16),
                TextField(
                    controller: code,
                    decoration: _deco('رمز التحقق', Iconsax.security_safe),
                    keyboardType: TextInputType.number,
                    textDirection: TextDirection.ltr),
                const SizedBox(height: 20),
                _dialogActions([
                  TextButton(
                      onPressed: () => Navigator.pop(ctx),
                      child: const Text('إلغاء')),
                  ElevatedButton(
                      onPressed: () async {
                        if (code.text.isEmpty) return;
                        Navigator.pop(ctx);
                        try {
                          await ref
                              .read(authRepositoryProvider)
                              .twoFactorConfirm(code.text);
                          final u = ref.read(currentUserProvider);
                          if (u != null) {
                            ref.read(currentUserProvider.notifier).state =
                                u.copyWith(twoFA: true);
                          }
                          _snack('تم تفعيل المصادقة الثنائية');
                          setState(() {});
                        } catch (e) {
                          _snack(e.toString(), ok: false);
                        }
                      },
                      style: ElevatedButton.styleFrom(
                        backgroundColor: isDark ? colors.surface : colors.primary,
                        foregroundColor: isDark ? colors.textPrimary : Colors.white,
                        shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(12)),
                        padding: const EdgeInsets.symmetric(vertical: 14),
                      ),
                      child: const Text('تفعيل',
                          style: TextStyle(fontWeight: FontWeight.w600))),
                ]),
              ]),
            ),
          ]),
        ),
      );
    } catch (e) {
      _snack(e.toString(), ok: false);
    }
  }

  void _disable2FA() {
    final colors = context.appColors;
    final code = TextEditingController();
    final pw = TextEditingController();
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        contentPadding: EdgeInsets.zero,
        content: Column(mainAxisSize: MainAxisSize.min, children: [
          _dialogHeader(
              'تعطيل المصادقة الثنائية',
              'أدخل كلمة المرور ورمز التحقق',
              Iconsax.security_safe),
          Padding(
            padding: const EdgeInsets.fromLTRB(24, 20, 24, 0),
            child: Column(mainAxisSize: MainAxisSize.min, children: [
              TextField(
                  controller: pw,
                  obscureText: true,
                  decoration: _deco('كلمة المرور', Iconsax.key)),
              const SizedBox(height: 12),
              TextField(
                  controller: code,
                  decoration: _deco('رمز التحقق', Iconsax.security_safe),
                  keyboardType: TextInputType.number,
                  maxLength: 6,
                  textDirection: TextDirection.ltr),
              const SizedBox(height: 20),
              _dialogActions([
                TextButton(
                    onPressed: () {
                      code.dispose();
                      pw.dispose();
                      Navigator.pop(ctx);
                    },
                    child: const Text('إلغاء')),
                ElevatedButton(
                    onPressed: () async {
                      if (pw.text.isEmpty || code.text.isEmpty) return;
                      Navigator.pop(ctx);
                      final p = pw.text, c = code.text;
                      code.dispose();
                      pw.dispose();
                      try {
                        await ref
                            .read(authRepositoryProvider)
                            .twoFactorDisable(password: p, code: c);
                        final u = ref.read(currentUserProvider);
                        if (u != null) {
                          ref.read(currentUserProvider.notifier).state =
                              u.copyWith(twoFA: false);
                        }
                        _snack('تم تعطيل المصادقة الثنائية');
                        setState(() {});
                      } catch (e) {
                        _snack(e.toString(), ok: false);
                      }
                    },
                    style: ElevatedButton.styleFrom(
                      backgroundColor: colors.error,
                      foregroundColor: Colors.white,
                      shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12)),
                      padding: const EdgeInsets.symmetric(vertical: 14),
                    ),
                    child: const Text('تعطيل',
                        style: TextStyle(fontWeight: FontWeight.w600))),
              ]),
            ]),
          ),
        ]),
      ),
    );
  }

  Future<void> _showRecoveryCodes() async {
    final colors = context.appColors;
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final pw = TextEditingController();
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        contentPadding: EdgeInsets.zero,
        content: Column(mainAxisSize: MainAxisSize.min, children: [
          _dialogHeader('أكواد الاسترداد',
              'أدخل كلمة المرور لإنشاء أكواد جديدة', Iconsax.key_square),
          Padding(
            padding: const EdgeInsets.fromLTRB(24, 20, 24, 0),
            child: Column(mainAxisSize: MainAxisSize.min, children: [
              Text(
                  'أدخل كلمة المرور لإنشاء أكواد استرداد جديدة. ستُلغى الأكواد القديمة.',
                  style:
                      TextStyle(fontSize: 12, color: colors.textSecondary)),
              const SizedBox(height: 12),
              TextField(
                  controller: pw,
                  obscureText: true,
                  decoration: _deco('كلمة المرور', Iconsax.key)),
              const SizedBox(height: 20),
              _dialogActions([
                TextButton(
                    onPressed: () => Navigator.pop(ctx, false),
                    child: const Text('إلغاء')),
                ElevatedButton(
                    onPressed: () => Navigator.pop(ctx, true),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: isDark ? colors.surface : colors.primary,
                      foregroundColor: isDark ? colors.textPrimary : Colors.white,
                      shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12)),
                      padding: const EdgeInsets.symmetric(vertical: 14),
                    ),
                    child: const Text('إنشاء',
                        style: TextStyle(fontWeight: FontWeight.w600))),
              ]),
            ]),
          ),
        ]),
      ),
    );
    if (ok != true || pw.text.isEmpty) {
      pw.dispose();
      return;
    }
    try {
      final codes = await ref.read(authRepositoryProvider).twoFactorRecoveryCodes(pw.text);
      pw.dispose();
      if (!mounted) return;
      showDialog(
        context: context,
        builder: (ctx) => AlertDialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
          contentPadding: EdgeInsets.zero,
          content: Column(mainAxisSize: MainAxisSize.min, children: [
            _dialogHeader('احفظ هذه الأكواد',
                'استخدم كود واحد عند فقدان جهازك', Iconsax.key_square),
            Padding(
              padding: const EdgeInsets.fromLTRB(24, 16, 24, 20),
              child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    ...codes.map((c) => Container(
                          margin: const EdgeInsets.only(bottom: 8),
                          padding: const EdgeInsets.symmetric(
                              vertical: 12, horizontal: 16),
                          decoration: BoxDecoration(
                              color: colors.inputBackground,
                              borderRadius: BorderRadius.circular(10)),
                          child: SelectableText(c,
                              textAlign: TextAlign.center,
                              style: const TextStyle(
                                  fontFamily: 'monospace',
                                  fontSize: 14,
                                  letterSpacing: 1.5,
                                  fontWeight: FontWeight.w600)),
                        )),
                  ]),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 0, 20, 16),
              child: SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: () => Navigator.pop(ctx),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: isDark ? colors.surface : colors.primary,
                    foregroundColor: isDark ? colors.textPrimary : Colors.white,
                    shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12)),
                    padding: const EdgeInsets.symmetric(vertical: 14),
                  ),
                  child: const Text('تم',
                      style: TextStyle(fontWeight: FontWeight.w600)),
                ),
              ),
            ),
          ]),
        ),
      );
    } catch (e) {
      pw.dispose();
      _snack(e.toString(), ok: false);
    }
  }

  // ═══════════ Biometric ═══════════
  Future<void> _toggleBio() async {
    final la = LocalAuthentication();
    if (_bioEnabled) {
      await ref.read(authRepositoryProvider).setBiometricEnabled(false);
      setState(() => _bioEnabled = false);
      _snack('تم تعطيل الدخول بالبصمة');
      return;
    }
    try {
      final ok = await la.authenticate(
          localizedReason: 'تأكيد هويتك لتفعيل الدخول بالبصمة',
          options: const AuthenticationOptions(stickyAuth: true));
      if (ok) {
        await ref.read(authRepositoryProvider).setBiometricEnabled(true);
        setState(() => _bioEnabled = true);
        _snack('تم تفعيل الدخول بالبصمة');
      }
    } on PlatformException catch (e) {
      if (e.code == 'NotAvailable') {
        _snack('الجهاز لا يدعم البصمة', ok: false);
      } else if (e.code == 'NotEnrolled') {
        _snack('لم يتم تسجيل بصمة. اذهب لإعدادات الجهاز', ok: false);
      } else if (e.code == 'LockedOut' || e.code == 'PermanentlyLockedOut') {
        _snack('تم قفل البصمة. حاول لاحقاً', ok: false);
      } else {
        _snack('فشل التحقق — ${e.message ?? "حاول مجدداً"}', ok: false);
      }
    } catch (_) {
      _snack('تعذر التحقق. حاول مجدداً', ok: false);
    }
  }

  // ═══════════════════════ UI ═══════════════════════
  @override
  Widget build(BuildContext context) {
    return AppScaffold(
      title: 'الأمان',
      onBack: () => context.canPop() ? context.pop() : context.go('/settings'),
      body: SingleChildScrollView(
        padding: const EdgeInsets.fromLTRB(
            AppSpacing.xl, AppSpacing.sm, AppSpacing.xl, 40),
        child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
          _scoreHeader(),
          const SizedBox(height: AppSpacing.xxl),
          _sectionTitle('تسجيل الدخول'),
          const SizedBox(height: AppSpacing.md),
          _passwordCard(),
          const SizedBox(height: AppSpacing.xl),
          _sectionTitle('حماية إضافية'),
          const SizedBox(height: AppSpacing.md),
          _twoFACard(),
          const SizedBox(height: AppSpacing.md),
          _bioCard(),
          const SizedBox(height: AppSpacing.md),
          _telegramCard(),
          const SizedBox(height: AppSpacing.xl),
          _sectionTitle('الأجهزة'),
          const SizedBox(height: AppSpacing.md),
          _devicesCard(),
        ]),
      ),
    );
  }

  // ───────── Connected devices entry ─────────
  Widget _devicesCard() {
    final colors = context.appColors;
    return ListTileCard(
      icon: Iconsax.mobile,
      title: 'الأجهزة المتصلة',
      subtitle: 'راجع أجهزتك ووافق على ربط الأجهزة الجديدة',
      trailing: Icon(Iconsax.arrow_left_2, size: 18, color: colors.textHint),
      onTap: () => context.push('/settings/security/devices'),
    );
  }

  // ───────── Telegram OTP channel ─────────
  Widget _telegramCard() {
    final colors = context.appColors;
    final linked = ref.watch(telegramStatusProvider).maybeWhen(
          data: (s) => s.linked,
          orElse: () => false,
        );
    return ListTileCard(
      icon: Iconsax.send_2,
      title: 'ربط تلجرام',
      subtitle: linked
          ? 'مربوط ✓ — تصلك رموز التحقق عبر تلجرام'
          : 'استقبل رموز التحقق عبر تلجرام بدل الرسائل',
      trailing: linked
          ? Icon(Iconsax.tick_circle, size: 20, color: colors.success)
          : Icon(Iconsax.arrow_left_2, size: 18, color: colors.textHint),
      onTap: linked ? _confirmUnlinkTelegram : _linkTelegram,
    );
  }

  Future<void> _linkTelegram() async {
    try {
      final url = await ref.read(telegramRepositoryProvider).getDeepLink();
      final opened = await launchUrl(
        Uri.parse(url),
        mode: LaunchMode.externalApplication,
      );
      if (!opened) {
        _snack('تعذّر فتح تلجرام — تأكد من تثبيت التطبيق', ok: false);
        return;
      }
      // The bind happens on the bot side; refresh status when the user returns.
      ref.invalidate(telegramStatusProvider);
      _snack('اضغط Start داخل تلجرام، ثم عُد إلى التطبيق');
    } catch (e) {
      _snack(e is ApiException ? e.message : 'تعذّر إنشاء رابط الربط', ok: false);
    }
  }

  Future<void> _confirmUnlinkTelegram() async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('إلغاء ربط تلجرام'),
        content: const Text('لن تصلك رموز التحقق عبر تلجرام بعد الإلغاء.'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('تراجع'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: Text('إلغاء الربط',
                style: TextStyle(color: context.appColors.error)),
          ),
        ],
      ),
    );
    if (ok != true) return;
    try {
      await ref.read(telegramRepositoryProvider).unlink();
      ref.invalidate(telegramStatusProvider);
      _snack('تم إلغاء ربط تلجرام');
    } catch (e) {
      _snack(e is ApiException ? e.message : 'تعذّر إلغاء الربط', ok: false);
    }
  }

  // ───────── Security-score hero (the only gradient on the page) ─────────
  Widget _scoreHeader() {
    final colors = context.appColors;
    return Container(
        width: double.infinity,
        padding: const EdgeInsets.all(AppSpacing.xxl),
        decoration: BoxDecoration(
          gradient: LinearGradient(
            colors: colors.cardGradientVisa,
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
          ),
          borderRadius: BorderRadius.circular(AppRadius.xl),
        ),
        child: Column(
          children: [
            _scoreRing(),
            const SizedBox(height: 14),
            Text(_scoreLabel,
                style: const TextStyle(
                    color: Colors.white, fontSize: 19, fontWeight: FontWeight.bold)),
            const SizedBox(height: 4),
            Text('$_score من 2 طبقات حماية مفعّلة',
                style: TextStyle(color: Colors.white.withValues(alpha: 0.85), fontSize: 13)),
          ],
        ),
      );
  }

  String get _scoreLabel {
    switch (_score) {
      case 2:
        return 'حسابك محمي بالكامل';
      case 1:
        return 'حماية أساسية';
      default:
        return 'فعّل المزيد من الحماية';
    }
  }

  Widget _scoreRing() {
    return SizedBox(
      width: 96,
      height: 96,
      child: Stack(alignment: Alignment.center, children: [
        SizedBox(
          width: 96,
          height: 96,
          child: CircularProgressIndicator(
            value: _score / 2,
            strokeWidth: 6,
            backgroundColor: Colors.white.withValues(alpha: 0.22),
            valueColor: const AlwaysStoppedAnimation(Colors.white),
          ),
        ),
        Container(
          width: 70,
          height: 70,
          decoration:
              BoxDecoration(color: Colors.white.withValues(alpha: 0.16), shape: BoxShape.circle),
          child: const Icon(Iconsax.shield_tick, color: Colors.white, size: 34),
        ),
      ]),
    );
  }

  Widget _sectionTitle(String t) {
    final colors = context.appColors;
    return Padding(
        padding: const EdgeInsets.only(right: AppSpacing.xs),
        child: Text(t,
            style: TextStyle(
                fontSize: 15, fontWeight: FontWeight.bold, color: colors.textPrimary)),
      );
  }

  // ───────── Password card (expandable) ─────────
  Widget _passwordCard() {
    final open = _expanded == 0;
    return AppCard(
      padding: EdgeInsets.zero,
      child: Column(children: [
        _rowHeader(
          icon: Iconsax.key,
          title: 'كلمة المرور',
          subtitle: 'غيّر كلمة مرور حسابك',
          trailing: _chevron(open),
          onTap: () => setState(() => _expanded = open ? -1 : 0),
        ),
        AnimatedCrossFade(
          firstChild: const SizedBox(width: double.infinity),
          secondChild: _pwForm(),
          crossFadeState: open ? CrossFadeState.showSecond : CrossFadeState.showFirst,
          duration: const Duration(milliseconds: 240),
        ),
      ]),
    );
  }

  Widget _pwForm() => Padding(
        padding: const EdgeInsets.fromLTRB(
            AppSpacing.lg, 0, AppSpacing.lg, AppSpacing.lg),
        child: Column(children: [
          const Divider(height: 8),
          const SizedBox(height: AppSpacing.md),
          _pwField(_curPw, 'كلمة المرور الحالية',
              obscure: !_showCur, onToggle: () => setState(() => _showCur = !_showCur)),
          const SizedBox(height: AppSpacing.md),
          _pwField(_newPw, 'كلمة المرور الجديدة',
              obscure: !_showNew,
              onToggle: () => setState(() => _showNew = !_showNew),
              helper: '٨ أحرف على الأقل'),
          const SizedBox(height: AppSpacing.md),
          _pwField(_confPw, 'تأكيد كلمة المرور',
              obscure: !_showConf, onToggle: () => setState(() => _showConf = !_showConf)),
          const SizedBox(height: AppSpacing.lg),
          AppButton(label: 'حفظ كلمة المرور', onPressed: _changePassword, loading: _pwBusy),
        ]),
      );

  // ───────── 2FA card ─────────
  Widget _twoFACard() {
    final colors = context.appColors;
    return AppCard(
      padding: EdgeInsets.zero,
      child: Column(children: [
        _rowHeader(
          icon: Iconsax.security_safe,
          title: 'المصادقة الثنائية',
          subtitle: _has2FA ? 'Google Authenticator' : 'طبقة حماية إضافية عند الدخول',
          statusOn: _has2FA,
          trailing: Switch.adaptive(
              value: _has2FA,
              onChanged: (v) => v ? _enable2FA() : _disable2FA(),
              activeColor: colors.primary),
        ),
        if (_has2FA) ...[
          const Divider(height: 1, indent: AppSpacing.lg, endIndent: AppSpacing.lg),
          InkWell(
            onTap: _showRecoveryCodes,
            child: Padding(
              padding: const EdgeInsets.fromLTRB(AppSpacing.lg, AppSpacing.md, AppSpacing.lg, 14),
              child: Row(children: [
                Icon(Iconsax.key_square, size: 18, color: colors.primary),
                const SizedBox(width: AppSpacing.sm),
                const Expanded(
                    child: Text('أكواد الاسترداد',
                        style: TextStyle(fontSize: 13.5, fontWeight: FontWeight.w500))),
                Icon(Iconsax.arrow_left_2, size: 16, color: colors.textHint),
              ]),
            ),
          ),
        ],
      ]),
    );
  }

  // ───────── Biometric card ─────────
  Widget _bioCard() {
    final colors = context.appColors;
    final active = _bioEnabled && _bioAvailable;
    return AppCard(
      padding: EdgeInsets.zero,
      child: _rowHeader(
        icon: Iconsax.finger_scan,
        title: 'الدخول بالبصمة',
        subtitle: !_bioLoaded
            ? 'جاري الفحص...'
            : (!_bioAvailable ? 'الجهاز لا يدعم البصمة' : 'افتح التطبيق ببصمتك'),
        statusOn: (!_bioLoaded || !_bioAvailable) ? null : active,
        trailing: !_bioLoaded
            ? const SizedBox(
                width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2))
            : (_bioAvailable
                ? Switch.adaptive(
                    value: _bioEnabled,
                    onChanged: (_) => _toggleBio(),
                    activeColor: colors.primary)
                : Icon(Iconsax.info_circle, color: colors.textHint, size: 20)),
      ),
    );
  }

  // ───────── Shared building blocks ─────────
  Widget _rowHeader({
    required IconData icon,
    required String title,
    String? subtitle,
    bool? statusOn,
    Widget? trailing,
    VoidCallback? onTap,
  }) {
    final colors = context.appColors;
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(AppRadius.lg),
        child: Padding(
          padding: const EdgeInsets.all(AppSpacing.lg),
          child: Row(children: [
            IconTile(icon: icon),
            const SizedBox(width: 14),
            Expanded(
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Row(children: [
                  Flexible(
                    child: Text(title,
                        style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w600),
                        overflow: TextOverflow.ellipsis),
                  ),
                  if (statusOn != null) ...[
                    const SizedBox(width: AppSpacing.sm),
                    StatusBadge(
                      label: statusOn ? 'مفعّل' : 'غير مفعّل',
                      kind: statusOn ? StatusKind.success : StatusKind.neutral,
                    ),
                  ],
                ]),
                if (subtitle != null) ...[
                  const SizedBox(height: 3),
                  Text(subtitle,
                      style: TextStyle(fontSize: 12, color: colors.textSecondary)),
                ],
              ]),
            ),
            if (trailing != null) trailing,
          ]),
        ),
      ),
    );
  }

  Widget _chevron(bool open) {
    final colors = context.appColors;
    return AnimatedRotation(
        turns: open ? 0.25 : 0,
        duration: const Duration(milliseconds: 200),
        child: Icon(Iconsax.arrow_left_2, size: 18, color: colors.textHint),
      );
  }

  Widget _dialogHeader(String title, String subtitle, IconData icon) {
    final colors = context.appColors;
    return Container(
        width: double.infinity,
        padding: const EdgeInsets.symmetric(vertical: 28, horizontal: 24),
        decoration: BoxDecoration(
          gradient: LinearGradient(colors: colors.cardGradientVisa),
          borderRadius: const BorderRadius.vertical(top: Radius.circular(20)),
        ),
        child: Column(children: [
          Icon(icon, color: Colors.white, size: 36),
          const SizedBox(height: 12),
          Text(title,
              style: const TextStyle(
                  color: Colors.white,
                  fontSize: 18,
                  fontWeight: FontWeight.bold)),
          const SizedBox(height: 4),
          Text(subtitle,
              style:
                  TextStyle(color: Colors.white.withValues(alpha: 0.85), fontSize: 12)),
        ]),
      );
  }

  Widget _dialogActions(List<Widget> children) => Padding(
        padding: const EdgeInsets.fromLTRB(20, 0, 20, 16),
        child: Row(children: children
            .map((w) => Padding(
                  padding: const EdgeInsetsDirectional.only(end: 12),
                  child: Expanded(child: w),
                ))
            .toList()),
      );

  Widget _pwField(TextEditingController c, String label,
      {required bool obscure, required VoidCallback onToggle, String? helper}) {
    final colors = context.appColors;
    return TextFormField(
      controller: c,
      obscureText: obscure,
      textDirection: TextDirection.ltr,
      style: const TextStyle(fontSize: 14),
      decoration: InputDecoration(
        labelText: label,
        labelStyle: const TextStyle(fontSize: 13),
        helperText: helper,
        helperStyle: TextStyle(fontSize: 11, color: colors.textHint),
        prefixIcon: const Icon(Iconsax.lock, size: 18),
        suffixIcon: IconButton(
          onPressed: onToggle,
          icon: Icon(obscure ? Iconsax.eye_slash : Iconsax.eye,
              size: 18, color: colors.textSecondary),
        ),
        filled: true,
        fillColor: colors.inputBackground,
        border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(12), borderSide: BorderSide.none),
        enabledBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(12), borderSide: BorderSide.none),
        focusedBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(12),
            borderSide: BorderSide(color: colors.primary, width: 1.5)),
      ),
    );
  }

  InputDecoration _deco(String label, IconData icon) => InputDecoration(
        labelText: label,
        prefixIcon: Icon(icon, size: 18),
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
      );
}
