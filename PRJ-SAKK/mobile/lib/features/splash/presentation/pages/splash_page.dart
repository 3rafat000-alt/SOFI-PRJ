import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../../../../core/theme/app_colors.dart';
import '../../../../core/widgets/damascene_pattern.dart';
import '../../../auth/data/repositories/auth_repository.dart';
import '../../../pin/data/pin_service.dart';

/// Flutter splash screen — continues seamlessly after the native
/// android/launch_background.xml splash.
///
/// Routing logic (single decision tree, no redundant redirects):
///   1. onboarding not seen          → /onboarding
///   2. not authenticated             → /welcome
///   3. authenticated + PIN/bio set  → /unlock
///   4. authenticated, no gate       → /dashboard
class SplashPage extends ConsumerStatefulWidget {
  const SplashPage({super.key});

  @override
  ConsumerState<SplashPage> createState() => _SplashPageState();
}

class _SplashPageState extends ConsumerState<SplashPage> {
  @override
  void initState() {
    super.initState();
    // Allow the logo animation (~1.5 s) before routing.
    Future.delayed(const Duration(milliseconds: 1600), _route);
  }

  Future<void> _route() async {
    if (!mounted) return;

    // 1. First-launch check.
    final prefs = await SharedPreferences.getInstance();
    final onboardingDone = prefs.getBool('onboarding_completed') ?? false;
    if (!onboardingDone) {
      if (mounted) context.go('/onboarding');
      return;
    }

    // 2. Auth check.
    final authRepo = ref.read(authRepositoryProvider);
    final authed = await authRepo.isAuthenticated();
    if (!authed) {
      if (mounted) context.go('/welcome');
      return;
    }

    // 3. Unlock gate.
    final pinService = ref.read(pinServiceProvider);
    final pinEnabled = await pinService.isPinEnabled();
    final pinSet = await pinService.isPinSet();
    final bioEnabled = await pinService.isBiometricEnabled();

    if ((pinEnabled && pinSet) || bioEnabled) {
      if (mounted) context.go('/unlock');
      return;
    }

    // 4. Straight to dashboard.
    if (mounted) context.go('/dashboard');
  }

  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;

    return Scaffold(
      backgroundColor: AppColors.primaryDark,
      body: Stack(
        fit: StackFit.expand,
        children: [
          // Faint Damascene medallion watermark.
          CustomPaint(
            painter: DamasceneMedallionPainter(
              color: AppColors.accent,
              opacity: 0.07,
              alignment: const Alignment(1.3, 1.3),
            ),
          ),

          // Radial glow behind logo.
          Center(
            child: Container(
              width: 260,
              height: 260,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                gradient: RadialGradient(
                  colors: [
                    colors.primary.withValues(alpha: 0.35),
                    Colors.transparent,
                  ],
                ),
              ),
            ),
          ),

          // Logo + wordmark.
          Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              // Logo mark — round icon.
              ClipRRect(
                borderRadius: BorderRadius.circular(32),
                child: Image.asset(
                  'assets/images/logo_mark.png',
                  width: 108,
                  height: 108,
                  fit: BoxFit.cover,
                ),
              )
                  .animate()
                  .scale(
                    begin: const Offset(0.6, 0.6),
                    end: const Offset(1.0, 1.0),
                    duration: 700.ms,
                    curve: Curves.easeOutBack,
                  )
                  .fadeIn(duration: 600.ms),

              const SizedBox(height: 28),

              // Arabic brand name.
              Text(
                'صكّ',
                style: TextStyle(
                  fontSize: 46,
                  fontWeight: FontWeight.w700,
                  color: Colors.white,
                  letterSpacing: 2,
                  shadows: [
                    Shadow(
                      color: Colors.black.withValues(alpha: 0.25),
                      blurRadius: 12,
                      offset: const Offset(0, 4),
                    ),
                  ],
                ),
              )
                  .animate(delay: 350.ms)
                  .fadeIn(duration: 500.ms)
                  .slideY(begin: 0.25, end: 0, duration: 500.ms, curve: Curves.easeOut),

              const SizedBox(height: 8),

              // Gold tagline.
              Text(
                'محفظتك الرقمية الآمنة',
                style: TextStyle(
                  fontSize: 14,
                  fontWeight: FontWeight.w400,
                  color: AppColors.accent.withValues(alpha: 0.9),
                  letterSpacing: 0.5,
                ),
              )
                  .animate(delay: 600.ms)
                  .fadeIn(duration: 500.ms),
            ],
          ),

          // Bottom gold hairline + subtle brand footer.
          Positioned(
            bottom: 48,
            left: 0,
            right: 0,
            child: Column(
              children: [
                SizedBox(
                  width: 40,
                  child: LinearProgressIndicator(
                    backgroundColor: Colors.white.withValues(alpha: 0.12),
                    valueColor: AlwaysStoppedAnimation<Color>(
                      AppColors.accent.withValues(alpha: 0.7),
                    ),
                    minHeight: 2,
                    borderRadius: BorderRadius.circular(1),
                  ),
                )
                    .animate(delay: 800.ms)
                    .fadeIn(duration: 400.ms),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
