import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:iconsax/iconsax.dart';
import 'package:flutter_animate/flutter_animate.dart';

import '../../../../core/theme/app_colors.dart';
import '../../../../core/network/api_client.dart';
import '../../../../core/services/biometric_service.dart';
import '../../../../core/widgets/user_avatar.dart';
import '../../data/repositories/auth_repository.dart';
import '../../../pin/data/pin_service.dart';
import '../widgets/auth_text_field.dart';

class LoginPage extends ConsumerStatefulWidget {
  const LoginPage({super.key});

  @override
  ConsumerState<LoginPage> createState() => _LoginPageState();
}

class _LoginPageState extends ConsumerState<LoginPage> {
  final _formKey = GlobalKey<FormState>();
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  final BiometricService _bio = BiometricService();
  bool _obscurePassword = true;
  bool _isLoading = false;
  bool _rememberMe = false;

  /// Null while we determine whether to show the biometric unlock view.
  /// True  => an existing session is present and biometric lock is enabled.
  /// False => show the normal email/password form.
  bool? _locked;
  bool _authenticating = false;
  String? _bioError;

  @override
  void initState() {
    super.initState();
    _loadRememberedEmail();
    _resolveLockState();
  }

  Future<void> _loadRememberedEmail() async {
    final email = await ref.read(authRepositoryProvider).getRememberedEmail();
    if (email != null && mounted) {
      setState(() {
        _emailController.text = email;
        _rememberMe = true;
      });
    }
  }

  /// Decide whether to greet a returning user with the in-page fingerprint
  /// unlock (replaces the old standalone /lock screen).
  Future<void> _resolveLockState() async {
    final repo = ref.read(authRepositoryProvider);
    final authed = await repo.isAuthenticated();
    final bioOn = await repo.isBiometricEnabled();
    final supported = authed && bioOn ? await _bio.isSupported() : false;
    final locked = authed && bioOn && supported;

    if (locked && ref.read(currentUserProvider) == null) {
      // Populate the greeting/avatar without forcing a logout on a transient 401.
      try {
        final user = await repo.getCurrentUser(silent: true);
        ref.read(currentUserProvider.notifier).state = user;
      } catch (_) {/* keep generic greeting */}
    }

    if (!mounted) return;
    setState(() => _locked = locked);
    if (locked) {
      WidgetsBinding.instance.addPostFrameCallback((_) => _authenticate());
    }
  }

  @override
  void dispose() {
    _emailController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  // ───────────────────────── Biometric unlock ─────────────────────────
  Future<void> _authenticate() async {
    if (_authenticating) return;
    setState(() {
      _authenticating = true;
      _bioError = null;
    });
    try {
      final result = await _bio.authenticate(
        reason: 'الرجاء التحقق من هويتك للدخول إلى المحفظة',
      );
      if (!mounted) return;
      if (result.success) {
        context.go('/dashboard');
      } else {
        setState(() => _bioError = result.message);
      }
    } finally {
      if (mounted) setState(() => _authenticating = false);
    }
  }

  /// Abandon the saved session and reveal the normal login form.
  Future<void> _switchAccount() async {
    await ref.read(authRepositoryProvider).logout();
    ref.read(currentUserProvider.notifier).state = null;
    if (mounted) setState(() => _locked = false);
  }

  Future<void> _login({String? twoFactorCode}) async {
    if (twoFactorCode == null && !_formKey.currentState!.validate()) return;

    setState(() => _isLoading = true);

    try {
      final user = await ref.read(authRepositoryProvider).login(
        email: _emailController.text.trim(),
        password: _passwordController.text,
        rememberMe: _rememberMe,
        twoFactorCode: twoFactorCode,
      );

      ref.read(currentUserProvider.notifier).state = user;

      if (mounted) {
        final pinSet = await ref.read(pinServiceProvider).isPinSet();
        if (mounted) context.go(pinSet ? '/dashboard' : '/pin-setup');
      }
    } on TwoFactorRequiredException catch (e) {
      if (mounted) _show2FADialog(e.email);
    } on ApiException catch (e) {
      if (mounted) {
        final colors = context.appColors;
        // Surface the backend's own reason (e.g. KYC/403) instead of a generic string;
        // fall back to a generic Arabic message only when the backend sent none.
        String? firstFieldError;
        final errorLists = e.errors?.values;
        if (errorLists != null && errorLists.isNotEmpty) {
          final firstList = errorLists.first;
          if (firstList.isNotEmpty) firstFieldError = firstList.first;
        }
        final text = firstFieldError ?? e.message.trim();
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(text.isNotEmpty ? text : 'تعذر تسجيل الدخول، حاول مرة أخرى'),
            backgroundColor: colors.error,
          ),
        );
      }
    } catch (e) {
      if (mounted) {
        final colors = context.appColors;
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: const Text('تعذر تسجيل الدخول، حاول مرة أخرى'),
            backgroundColor: colors.error,
          ),
        );
      }
    } finally {
      if (mounted) {
        setState(() => _isLoading = false);
      }
    }
  }

  void _show2FADialog(String email) {
    final colors = context.appColors;
    final controllers = List.generate(6, (_) => TextEditingController());
    final focusNodes = List.generate(6, (_) => FocusNode());
    bool isLoading2fa = false;

    showDialog(
      context: context,
      barrierDismissible: false,
      barrierColor: Colors.black54,
      builder: (ctx) {
        return StatefulBuilder(
          builder: (ctx, setSheet) {
            String getCode() => controllers.map((c) => c.text).join();

            void onDigitChanged(int index, String value) {
              if (value.isNotEmpty && index < 5) {
                focusNodes[index + 1].requestFocus();
              }
            }

            final bool canConfirm = !isLoading2fa && getCode().length == 6;

            return Dialog(
              backgroundColor: colors.surface,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
              child: Padding(
                padding: const EdgeInsets.all(28),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Container(
                      width: 56,
                      height: 56,
                      decoration: BoxDecoration(
                        color: colors.info.withValues(alpha: 0.12),
                        borderRadius: BorderRadius.circular(16),
                      ),
                      child: Icon(Iconsax.security_safe, color: colors.info, size: 28),
                    ),
                    const SizedBox(height: 14),
                    Text(
                      'المصادقة الثنائية',
                      style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: colors.textPrimary),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      'أدخل رمز التحقق من تطبيق المصادقة',
                      textAlign: TextAlign.center,
                      style: TextStyle(fontSize: 13, color: colors.textSecondary),
                    ),
                    const SizedBox(height: 24),
                    Directionality(
                      textDirection: TextDirection.ltr,
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                        children: List.generate(6, (index) {
                          return SizedBox(
                            width: 46,
                            height: 56,
                            child: TextField(
                              controller: controllers[index],
                              focusNode: focusNodes[index],
                              textAlign: TextAlign.center,
                              keyboardType: TextInputType.number,
                              maxLength: 1,
                              style: TextStyle(
                                fontSize: 22,
                                fontWeight: FontWeight.bold,
                                color: colors.textPrimary,
                              ),
                              decoration: InputDecoration(
                                counterText: '',
                                filled: true,
                                fillColor: colors.inputBackground,
                                border: OutlineInputBorder(
                                  borderRadius: BorderRadius.circular(12),
                                  borderSide: BorderSide.none,
                                ),
                                enabledBorder: OutlineInputBorder(
                                  borderRadius: BorderRadius.circular(12),
                                  borderSide: BorderSide(color: colors.inputBackground),
                                ),
                                focusedBorder: OutlineInputBorder(
                                  borderRadius: BorderRadius.circular(12),
                                  borderSide: BorderSide(color: colors.primary, width: 2),
                                ),
                              ),
                              onChanged: (value) {
                                setSheet(() {});
                                onDigitChanged(index, value);
                              },
                            ),
                          );
                        }),
                      ),
                    ),
                    const SizedBox(height: 24),
                    SizedBox(
                      width: double.infinity,
                      height: 52,
                      child: Container(
                        decoration: BoxDecoration(
                          borderRadius: BorderRadius.circular(14),
                          gradient: canConfirm
                              ? LinearGradient(
                                  colors: colors.cardGradientVisa,
                                )
                              : null,
                          color: canConfirm
                              ? null
                              : colors.cardGradientVisa.first.withValues(alpha: 0.35),
                          boxShadow: canConfirm
                              ? [
                                  BoxShadow(
                                    color: Colors.black.withValues(alpha: 0.3),
                                    blurRadius: 10,
                                    offset: const Offset(0, 4),
                                  ),
                                ]
                              : null,
                        ),
                        child: ElevatedButton(
                          onPressed: canConfirm
                              ? () {
                                  setSheet(() => isLoading2fa = true);
                                  for (var c in controllers) { c.dispose(); }
                                  for (var f in focusNodes) { f.dispose(); }
                                  Navigator.pop(ctx);
                                  _login(twoFactorCode: getCode());
                                }
                              : null,
                          style: ElevatedButton.styleFrom(
                            backgroundColor: Colors.transparent,
                            shadowColor: Colors.transparent,
                            disabledBackgroundColor: Colors.transparent,
                            foregroundColor: Colors.white,
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(14),
                            ),
                          ),
                          child: isLoading2fa
                              ? const SizedBox(
                                  width: 22,
                                  height: 22,
                                  child: CircularProgressIndicator(
                                    color: Colors.white,
                                    strokeWidth: 2,
                                  ),
                                )
                              : const Text(
                                  'تأكيد',
                                  style: TextStyle(fontWeight: FontWeight.w700),
                                ),
                        ),
                      ),
                    ),
                    const SizedBox(height: 8),
                    TextButton(
                      onPressed: () {
                        for (var c in controllers) { c.dispose(); }
                        for (var f in focusNodes) { f.dispose(); }
                        Navigator.pop(ctx);
                      },
                      child: Text(
                        'إلغاء',
                        style: TextStyle(color: colors.textSecondary),
                      ),
                    ),
                  ],
                ),
              ),
            );
          },
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    return Scaffold(
      backgroundColor: colors.background,
      body: SafeArea(
        child: _locked == null
            ? Center(child: CircularProgressIndicator(color: colors.primary))
            : (_locked! ? _buildBiometricUnlock(colors) : _buildLoginForm(colors)),
      ),
    );
  }

  // ───────────────────────── Biometric unlock view ─────────────────────────
  Widget _buildBiometricUnlock(AppColorsTheme colors) {
    final user = ref.watch(currentUserProvider);
    return Padding(
      padding: const EdgeInsets.fromLTRB(24, 18, 24, 24),
      child: Column(
        children: [
          Row(
            textDirection: TextDirection.ltr,
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Text('مرحباً 👋',
                        textAlign: TextAlign.left,
                        style: TextStyle(fontSize: 14, color: colors.textSecondary)),
                    const SizedBox(height: 4),
                    Text(
                      user?.fullName ?? 'مستخدم',
                      textAlign: TextAlign.left,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: TextStyle(
                          fontSize: 20,
                          fontWeight: FontWeight.bold,
                          color: colors.textPrimary),
                    ),
                    if (user != null) ...[
                      const SizedBox(height: 8),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                        decoration: BoxDecoration(
                          color: colors.primaryLight,
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Text(
                          user.sakkTag,
                          style: TextStyle(
                            color: colors.primary,
                            fontSize: 11.5,
                            fontFamily: 'monospace',
                            letterSpacing: 1,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ),
                    ],
                  ],
                ),
              ),
              const SizedBox(width: 14),
              UserAvatar(
                imageUrl: user?.avatarUrl,
                initials: user?.initials ?? '؟',
                size: 60,
                radius: 20,
                fontSize: 24,
                borderColor: Colors.white,
                borderWidth: 3,
                shadow: [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: 0.18),
                    blurRadius: 18,
                    offset: const Offset(0, 6),
                  ),
                ],
              ),
            ],
          ).animate().fadeIn(duration: 400.ms).slideY(begin: -0.15, end: 0),
          Expanded(
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                GestureDetector(
                  onTap: _authenticating ? null : _authenticate,
                  child: Container(
                    width: 124,
                    height: 124,
                    decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      gradient: LinearGradient(
                        colors: [
                          colors.primary.withValues(alpha: 0.12),
                          colors.secondary.withValues(alpha: 0.10),
                        ],
                      ),
                      border: Border.all(
                        color: colors.primary.withValues(alpha: 0.25),
                        width: 1.5,
                      ),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withValues(alpha: 0.15),
                          blurRadius: 28,
                          offset: const Offset(0, 12),
                        ),
                      ],
                    ),
                    child: Icon(Iconsax.finger_scan, color: colors.primary, size: 58),
                  ),
                )
                    .animate(onPlay: (c) => c.repeat(reverse: true))
                    .scale(
                      begin: const Offset(1, 1),
                      end: const Offset(1.04, 1.04),
                      duration: 1400.ms,
                      curve: Curves.easeInOut,
                    ),
                const SizedBox(height: 30),
                Text('افتح محفظتك',
                    style: TextStyle(
                        fontSize: 21,
                        fontWeight: FontWeight.bold,
                        color: colors.textPrimary)),
                const SizedBox(height: 8),
                SizedBox(
                  height: 24,
                  child: _authenticating
                      ? SizedBox(
                          width: 22,
                          height: 22,
                          child: CircularProgressIndicator(
                              color: colors.primary, strokeWidth: 2),
                        )
                      : Text(
                          _bioError ?? 'استخدم بصمتك للمتابعة بأمان',
                          textAlign: TextAlign.center,
                          style: TextStyle(
                            fontSize: 13.5,
                            color: _bioError != null ? colors.error : colors.textSecondary,
                          ),
                        ),
                ),
                const SizedBox(height: 22),
                if (!_authenticating)
                  OutlinedButton.icon(
                    onPressed: _authenticate,
                    icon: const Icon(Iconsax.finger_scan, size: 18),
                    label: const Text('المصادقة بالبصمة'),
                    style: OutlinedButton.styleFrom(
                      foregroundColor: colors.primary,
                      side: BorderSide(color: colors.primary.withValues(alpha: 0.4)),
                      padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(14),
                      ),
                    ),
                  ),
              ],
            ),
          ),
          TextButton(
            onPressed: _switchAccount,
            child: Text(
              'تسجيل الدخول بحساب مختلف',
              style: TextStyle(
                color: colors.primary,
                fontSize: 14,
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
        ],
      ),
    );
  }

  // ───────────────────────── Email / password form ─────────────────────────
  Widget _buildLoginForm(AppColorsTheme colors) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    return SingleChildScrollView(
      padding: const EdgeInsets.all(24),
      child: Form(
        key: _formKey,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            const SizedBox(height: 40),

            // Brand logo — صكّ mark (real app icon)
            Center(
              child: Container(
                width: 80,
                height: 80,
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(24),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withValues(alpha: 0.3),
                      blurRadius: 20,
                      offset: const Offset(0, 8),
                    ),
                  ],
                ),
                child: ClipRRect(
                  borderRadius: BorderRadius.circular(24),
                  child: Image.asset(
                    'assets/images/logo.png',
                    width: 80,
                    height: 80,
                    fit: BoxFit.cover,
                  ),
                ),
              ),
            ).animate().scale(begin: const Offset(0.8, 0.8)).fadeIn(duration: 500.ms),

            const SizedBox(height: 32),

            Text(
              'مرحباً بعودتك',
              textAlign: TextAlign.center,
              style: TextStyle(
                fontSize: 28,
                fontWeight: FontWeight.bold,
                color: colors.textPrimary,
              ),
            ).animate(delay: 200.ms).fadeIn().slideY(begin: 0.3, duration: 300.ms),

            const SizedBox(height: 8),

            Text(
              'سجل دخولك للوصول إلى محفظتك',
              textAlign: TextAlign.center,
              style: TextStyle(
                fontSize: 14,
                color: colors.textSecondary,
              ),
            ).animate(delay: 300.ms).fadeIn(duration: 400.ms),

            const SizedBox(height: 40),

            AuthTextField(
              controller: _emailController,
              label: 'البريد الإلكتروني',
              hint: 'example@email.com',
              keyboardType: TextInputType.emailAddress,
              textDirection: TextDirection.ltr,
              prefixIcon: Iconsax.sms,
              validator: (value) {
                if (value == null || value.isEmpty) {
                  return 'البريد الإلكتروني مطلوب';
                }
                if (!RegExp(r'^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$').hasMatch(value)) {
                  return 'البريد الإلكتروني غير صالح';
                }
                return null;
              },
            ).animate(delay: 400.ms).fadeIn().slideX(begin: -0.1, duration: 400.ms),

            const SizedBox(height: 16),

            AuthTextField(
              controller: _passwordController,
              label: 'كلمة المرور',
              hint: '••••••••',
              obscureText: _obscurePassword,
              textDirection: TextDirection.ltr,
              prefixIcon: Iconsax.lock,
              suffixIcon: IconButton(
                icon: Icon(
                  _obscurePassword ? Iconsax.eye_slash : Iconsax.eye,
                  color: colors.textHint,
                ),
                onPressed: () {
                  setState(() => _obscurePassword = !_obscurePassword);
                },
              ),
              validator: (value) {
                if (value == null || value.isEmpty) {
                  return 'كلمة المرور مطلوبة';
                }
                return null;
              },
            ).animate(delay: 500.ms).fadeIn().slideX(begin: -0.1, duration: 500.ms),

            const SizedBox(height: 8),

            Row(
              children: [
                TextButton(
                  onPressed: () => context.push('/forgot-password'),
                  child: Text(
                    'نسيت كلمة المرور؟',
                    style: TextStyle(
                      color: colors.primary,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ),
                const Spacer(),
                Row(
                  children: [
                    Text(
                      'تذكرني',
                      style: TextStyle(
                        fontSize: 13,
                        color: colors.textSecondary,
                      ),
                    ),
                    Checkbox(
                      value: _rememberMe,
                      onChanged: (value) {
                        setState(() => _rememberMe = value ?? false);
                      },
                      activeColor: isDark ? colors.surface : colors.primary,
                      checkColor: Colors.white,
                      materialTapTargetSize: MaterialTapTargetSize.shrinkWrap,
                    ),
                  ],
                ),
              ],
            ).animate(delay: 600.ms).fadeIn(duration: 600.ms),

            const SizedBox(height: 24),

            // Login Button — gradient filled
            SizedBox(
              height: 54,
              child: Container(
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(12),
                  gradient: LinearGradient(
                    colors: colors.cardGradientVisa,
                  ),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withValues(alpha: 0.3),
                      blurRadius: 12,
                      offset: const Offset(0, 4),
                    ),
                  ],
                ),
                child: ElevatedButton(
                  onPressed: _isLoading ? null : _login,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.transparent,
                    shadowColor: Colors.transparent,
                    disabledBackgroundColor: Colors.transparent,
                    foregroundColor: Colors.white,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                  ),
                  child: _isLoading
                      ? const SizedBox(
                          width: 24,
                          height: 24,
                          child: CircularProgressIndicator(
                            color: Colors.white,
                            strokeWidth: 2,
                          ),
                        )
                      : const Text(
                          'تسجيل الدخول',
                          style: TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.w600,
                            color: Colors.white,
                          ),
                        ),
                ),
              ),
            ).animate(delay: 700.ms).fadeIn().slideY(begin: 0.2, duration: 700.ms),

            const SizedBox(height: 32),

            Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Text(
                  'ليس لديك حساب؟',
                  style: TextStyle(color: colors.textSecondary),
                ),
                TextButton(
                  onPressed: () => context.push('/register'),
                  child: Text(
                    'سجل الآن',
                    style: TextStyle(
                      fontWeight: FontWeight.w600,
                      color: colors.primary,
                    ),
                  ),
                ),
              ],
            ).animate(delay: 1000.ms).fadeIn(duration: 1000.ms),
          ],
        ),
      ),
    );
  }
}
