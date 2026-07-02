import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/services/biometric_service.dart';
import '../../../../core/theme/app_colors.dart';
import '../../../../core/widgets/damascene_pattern.dart';
import '../../data/pin_service.dart';
import '../widgets/pin_pad.dart';

/// First-time quick-access setup: choose a 6-digit PIN (enter + confirm),
/// then optionally enable biometric. Skippable. Shown after first auth.
class PinSetupPage extends ConsumerStatefulWidget {
  const PinSetupPage({super.key});

  @override
  ConsumerState<PinSetupPage> createState() => _PinSetupPageState();
}

class _PinSetupPageState extends ConsumerState<PinSetupPage> {
  String _pin = '';
  String _confirm = '';
  bool _confirming = false;
  String? _error;
  bool _shake = false;

  void _onDigit(String d) {
    final cur = _confirming ? _confirm : _pin;
    if (cur.length >= 6) return;
    setState(() {
      if (_confirming) {
        _confirm += d;
      } else {
        _pin += d;
      }
      _error = null;
    });
    final now = _confirming ? _confirm : _pin;
    if (now.length == 6) {
      if (!_confirming) {
        setState(() => _confirming = true);
      } else {
        _finish();
      }
    }
  }

  void _onBackspace() {
    setState(() {
      if (_confirming) {
        if (_confirm.isNotEmpty) {
          _confirm = _confirm.substring(0, _confirm.length - 1);
        }
      } else {
        if (_pin.isNotEmpty) {
          _pin = _pin.substring(0, _pin.length - 1);
        }
      }
    });
  }

  Future<void> _finish() async {
    if (_pin != _confirm) {
      setState(() {
        _shake = true;
        _error = 'الرمزان غير متطابقين، حاول مجدداً';
        _pin = '';
        _confirm = '';
        _confirming = false;
      });
      Future.delayed(const Duration(milliseconds: 460), () {
        if (mounted) setState(() => _shake = false);
      });
      return;
    }
    await ref.read(pinServiceProvider).setPin(_pin);
    if (!mounted) return;
    await _offerBiometric();
    if (mounted) context.go('/dashboard');
  }

  Future<void> _offerBiometric() async {
    final svc = ref.read(pinServiceProvider);
    final bio = BiometricService();
    if (!await bio.isSupported() || !await bio.hasEnrolledBiometrics()) return;
    if (!mounted) return;
    final enable = await showModalBottomSheet<bool>(
      context: context,
      backgroundColor: AppColors.surface,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (ctx) => Padding(
        padding: const EdgeInsets.fromLTRB(24, 28, 24, 28),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.fingerprint, size: 56, color: AppColors.primary),
            const SizedBox(height: 16),
            const Text(
              'تفعيل البصمة؟',
              style: TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.w700,
                color: AppColors.textPrimary,
              ),
            ),
            const SizedBox(height: 8),
            const Text(
              'ادخل لمحفظتك بلمسة واحدة بدل كتابة الرمز في كل مرة.',
              textAlign: TextAlign.center,
              style: TextStyle(
                fontSize: 13.5,
                color: AppColors.textSecondary,
                height: 1.5,
              ),
            ),
            const SizedBox(height: 24),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                onPressed: () => Navigator.pop(ctx, true),
                style: ElevatedButton.styleFrom(
                  backgroundColor: AppColors.primary,
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(vertical: 14),
                ),
                child: const Text('تفعيل البصمة'),
              ),
            ),
            const SizedBox(height: 8),
            TextButton(
              onPressed: () => Navigator.pop(ctx, false),
              child: const Text(
                'لاحقاً',
                style: TextStyle(color: AppColors.textSecondary),
              ),
            ),
          ],
        ),
      ),
    );
    if (enable == true) {
      final res = await bio.authenticate(
        reason: 'فعّل البصمة لمحفظة صكّ',
        biometricOnly: true,
      );
      if (res.success) await svc.setBiometricEnabled(true);
    }
  }

  void _skip() => context.go('/dashboard');

  @override
  Widget build(BuildContext context) {
    final filled = _confirming ? _confirm.length : _pin.length;
    Widget dots = PinDots(filled: filled);
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
              alignment: const Alignment(-1.3, -1.2),
            ),
          ),
          SafeArea(
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 28),
              child: Column(
                children: [
                  Align(
                    alignment: AlignmentDirectional.centerEnd,
                    child: TextButton(
                      onPressed: _skip,
                      child: Text(
                        'تخطّي',
                        style: TextStyle(
                          color: Colors.white.withValues(alpha: 0.7),
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(height: 4),
                  const Icon(Icons.lock_outline, size: 44, color: Colors.white),
                  const SizedBox(height: 20),
                  Text(
                    _confirming ? 'أكّد رمز الدخول' : 'أنشئ رمز دخول',
                    style: const TextStyle(
                      fontSize: 21,
                      fontWeight: FontWeight.w700,
                      color: Colors.white,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    _confirming
                        ? 'أعد إدخال نفس الرمز للتأكيد'
                        : 'رمز من 6 أرقام لتأمين محفظتك وفتحها بسرعة',
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      fontSize: 13,
                      color: Colors.white.withValues(alpha: 0.6),
                      height: 1.5,
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
                  PinKeypad(onDigit: _onDigit, onBackspace: _onBackspace),
                  const SizedBox(height: 20),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}
