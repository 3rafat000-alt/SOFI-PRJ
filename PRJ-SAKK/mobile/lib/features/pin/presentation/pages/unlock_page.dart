import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/services/biometric_service.dart';
import '../../../../core/theme/app_colors.dart';
import '../../../../core/widgets/damascene_pattern.dart';
import '../../../auth/data/repositories/auth_repository.dart';
import '../../data/pin_service.dart';
import '../widgets/pin_pad.dart';

/// Quick-unlock gate shown on relaunch when a PIN and/or biometric is enabled.
/// Verifies via [PinService] (hash + lockout) or biometric, then → /dashboard.
class UnlockPage extends ConsumerStatefulWidget {
  const UnlockPage({super.key});

  @override
  ConsumerState<UnlockPage> createState() => _UnlockPageState();
}

class _UnlockPageState extends ConsumerState<UnlockPage> {
  final _bio = BiometricService();
  String _pin = '';
  String? _error;
  bool _shake = false;
  bool _bioEnabled = false;
  DateTime? _lockedUntil;

  @override
  void initState() {
    super.initState();
    _init();
  }

  Future<void> _init() async {
    final svc = ref.read(pinServiceProvider);
    _bioEnabled = await svc.isBiometricEnabled();
    if (mounted) setState(() {});
    if (_bioEnabled) _tryBiometric();
  }

  Future<void> _tryBiometric() async {
    final res = await _bio.authenticate(reason: 'افتح محفظة صكّ');
    if (res.success && mounted) context.go('/dashboard');
  }

  void _onDigit(String d) {
    if (_pin.length >= 6) return;
    if (_lockedUntil != null && _lockedUntil!.isAfter(DateTime.now())) return;
    setState(() {
      _pin += d;
      _error = null;
    });
    if (_pin.length == 6) _submit();
  }

  void _onBackspace() {
    if (_pin.isEmpty) return;
    setState(() => _pin = _pin.substring(0, _pin.length - 1));
  }

  Future<void> _submit() async {
    final res = await ref.read(pinServiceProvider).verifyPin(_pin);
    if (!mounted) return;
    if (res.isSuccess) {
      context.go('/dashboard');
      return;
    }
    if (res.isSessionLocked) {
      await ref.read(pinServiceProvider).clearPin();
      await ref.read(authRepositoryProvider).logout();
      if (mounted) context.go('/welcome');
      return;
    }
    setState(() {
      _pin = '';
      _shake = true;
      if (res.isLocked) {
        _lockedUntil = res.lockedUntil;
        _error = 'محاولات كثيرة. حاول بعد قليل';
      } else {
        _error = 'رمز غير صحيح • محاولات متبقية: ${res.attemptsLeft}';
      }
    });
    Future.delayed(const Duration(milliseconds: 460), () {
      if (mounted) setState(() => _shake = false);
    });
  }

  Future<void> _switchAccount() async {
    await ref.read(authRepositoryProvider).logout();
    if (mounted) context.go('/welcome');
  }

  @override
  Widget build(BuildContext context) {
    Widget dots = PinDots(filled: _pin.length);
    if (_shake) dots = dots.animate().shakeX(amount: 8, duration: 420.ms);

    return Scaffold(
      backgroundColor: AppColors.primaryDark,
      body: Stack(
        fit: StackFit.expand,
        children: [
          CustomPaint(
            painter: DamasceneMedallionPainter(
              color: AppColors.accent,
              opacity: 0.06,
              alignment: const Alignment(1.3, -1.2),
            ),
          ),
          SafeArea(
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 28),
              child: Column(
                children: [
                  const SizedBox(height: 36),
                  ClipRRect(
                    borderRadius: BorderRadius.circular(20),
                    child: Image.asset(
                      'assets/images/logo_mark.png',
                      width: 64,
                      height: 64,
                      fit: BoxFit.cover,
                    ),
                  ),
                  const SizedBox(height: 24),
                  const Text(
                    'أدخل رمز الدخول',
                    style: TextStyle(
                      fontSize: 20,
                      fontWeight: FontWeight.w700,
                      color: Colors.white,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    'رمزك السرّي المكوّن من 6 أرقام',
                    style: TextStyle(
                      fontSize: 13,
                      color: Colors.white.withValues(alpha: 0.6),
                    ),
                  ),
                  const SizedBox(height: 36),
                  dots,
                  const SizedBox(height: 16),
                  SizedBox(
                    height: 22,
                    child: _error != null
                        ? Text(
                            _error!,
                            style: const TextStyle(
                              color: Color(0xFFE8A0A0),
                              fontSize: 12.5,
                            ),
                          )
                        : null,
                  ),
                  const Spacer(),
                  PinKeypad(
                    onDigit: _onDigit,
                    onBackspace: _onBackspace,
                    onBiometric: _bioEnabled ? _tryBiometric : null,
                  ),
                  const SizedBox(height: 4),
                  TextButton(
                    onPressed: _switchAccount,
                    child: Text(
                      'تسجيل الدخول بحساب آخر',
                      style: TextStyle(
                        color: AppColors.accent.withValues(alpha: 0.9),
                        fontSize: 13,
                      ),
                    ),
                  ),
                  const SizedBox(height: 12),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}
