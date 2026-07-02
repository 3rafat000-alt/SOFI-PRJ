import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/theme/app_colors.dart';
import '../../../../core/widgets/damascene_pattern.dart';

/// Auth gateway shown to unauthenticated users after onboarding.
/// Clean, secure fintech feel — Sign In vs Create Account + legal links.
class WelcomePage extends StatelessWidget {
  const WelcomePage({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Container(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
            colors: [AppColors.primaryDark, Color(0xFF2E0B14)],
          ),
        ),
        child: Stack(
          fit: StackFit.expand,
          children: [
            CustomPaint(
              painter: DamasceneMedallionPainter(
                color: AppColors.accent,
                opacity: 0.06,
                alignment: const Alignment(1.2, -0.9),
              ),
            ),
            SafeArea(
              child: Padding(
                padding: const EdgeInsets.symmetric(horizontal: 28),
                child: Column(
                  children: [
                    const Spacer(flex: 3),
                    ClipRRect(
                      borderRadius: BorderRadius.circular(28),
                      child: Image.asset(
                        'assets/images/logo_mark.png',
                        width: 96,
                        height: 96,
                        fit: BoxFit.cover,
                      ),
                    )
                        .animate()
                        .scale(
                          begin: const Offset(0.7, 0.7),
                          end: const Offset(1, 1),
                          duration: 600.ms,
                          curve: Curves.easeOutBack,
                        )
                        .fadeIn(),
                    const SizedBox(height: 24),
                    const Text(
                      'أهلاً بك في صكّ',
                      style: TextStyle(
                        fontSize: 28,
                        fontWeight: FontWeight.w800,
                        color: Colors.white,
                      ),
                    ).animate(delay: 200.ms).fadeIn().slideY(begin: 0.2, end: 0),
                    const SizedBox(height: 12),
                    Text(
                      'محفظتك الرقمية الآمنة — أرسل، استقبل، وادّخر بثقة.',
                      textAlign: TextAlign.center,
                      style: TextStyle(
                        fontSize: 14.5,
                        color: Colors.white.withValues(alpha: 0.7),
                        height: 1.6,
                      ),
                    ).animate(delay: 350.ms).fadeIn(),
                    const Spacer(flex: 4),
                    SizedBox(
                      width: double.infinity,
                      child: ElevatedButton(
                        onPressed: () => context.go('/login'),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: Colors.white,
                          foregroundColor: AppColors.primaryDark,
                          padding: const EdgeInsets.symmetric(vertical: 16),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(16),
                          ),
                          textStyle: const TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                        child: const Text('تسجيل الدخول'),
                      ),
                    ).animate(delay: 500.ms).fadeIn().slideY(begin: 0.3, end: 0),
                    const SizedBox(height: 14),
                    SizedBox(
                      width: double.infinity,
                      child: OutlinedButton(
                        onPressed: () => context.go('/register'),
                        style: OutlinedButton.styleFrom(
                          foregroundColor: Colors.white,
                          side: BorderSide(
                            color: AppColors.accent.withValues(alpha: 0.7),
                            width: 1.4,
                          ),
                          padding: const EdgeInsets.symmetric(vertical: 16),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(16),
                          ),
                          textStyle: const TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                        child: const Text('إنشاء حساب جديد'),
                      ),
                    ).animate(delay: 620.ms).fadeIn().slideY(begin: 0.3, end: 0),
                    const SizedBox(height: 22),
                    Wrap(
                      alignment: WrapAlignment.center,
                      crossAxisAlignment: WrapCrossAlignment.center,
                      children: [
                        Text(
                          'بالمتابعة فأنت توافق على ',
                          style: TextStyle(
                            fontSize: 11.5,
                            color: Colors.white.withValues(alpha: 0.5),
                          ),
                        ),
                        GestureDetector(
                          onTap: () => context.push('/terms'),
                          child: const Text(
                            'شروط الاستخدام',
                            style: TextStyle(
                              fontSize: 11.5,
                              color: AppColors.accent,
                              decoration: TextDecoration.underline,
                            ),
                          ),
                        ),
                        Text(
                          ' و ',
                          style: TextStyle(
                            fontSize: 11.5,
                            color: Colors.white.withValues(alpha: 0.5),
                          ),
                        ),
                        GestureDetector(
                          onTap: () => context.push('/privacy'),
                          child: const Text(
                            'سياسة الخصوصية',
                            style: TextStyle(
                              fontSize: 11.5,
                              color: AppColors.accent,
                              decoration: TextDecoration.underline,
                            ),
                          ),
                        ),
                      ],
                    ).animate(delay: 750.ms).fadeIn(),
                    const SizedBox(height: 40),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
