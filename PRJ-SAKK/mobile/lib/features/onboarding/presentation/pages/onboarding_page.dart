import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:go_router/go_router.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../../../../core/theme/app_colors.dart';
import '../../../../core/widgets/damascene_pattern.dart';

// ── Slide data ────────────────────────────────────────────────────────────────

class _Slide {
  final IconData icon;
  final String headline;
  final String sub;
  final List<Color> gradient;

  const _Slide({
    required this.icon,
    required this.headline,
    required this.sub,
    required this.gradient,
  });
}

const _slides = [
  _Slide(
    icon: Icons.swap_horiz_rounded,
    headline: 'أرسل واستقبل الأموال بلحظة',
    sub: 'تحويلات فورية بين المحافظ عبر رمز QR أو رقم الهاتف.',
    gradient: [Color(0xFF7A2236), Color(0xFF4A1320)],
  ),
  _Slide(
    icon: Icons.credit_card_rounded,
    headline: 'بطاقاتك الافتراضية في مكان واحد',
    sub: 'أصدر بطاقات دفع رقمية واستخدمها في أي وقت وأي مكان.',
    gradient: [Color(0xFFC9A24B), Color(0xFF8F6B2A)],
  ),
  _Slide(
    icon: Icons.location_on_rounded,
    headline: 'وكلاء وصرافة قربك',
    sub: 'شبكة وكلاء معتمدين لإيداع وسحب النقد في مناطقك.',
    gradient: [Color(0xFF6E1B2D), Color(0xFF3A0E1A)],
  ),
  _Slide(
    icon: Icons.savings_rounded,
    headline: 'ادّخار وذهب',
    sub: 'ادّخر بالذهب وابنِ أهدافك المالية خطوة بخطوة.',
    gradient: [Color(0xFF9B3A4D), Color(0xFF6E1B2D)],
  ),
];

// ── Page ─────────────────────────────────────────────────────────────────────

class OnboardingPage extends StatefulWidget {
  const OnboardingPage({super.key});

  @override
  State<OnboardingPage> createState() => _OnboardingPageState();
}

class _OnboardingPageState extends State<OnboardingPage> {
  final PageController _ctrl = PageController();
  int _current = 0;

  @override
  void dispose() {
    _ctrl.dispose();
    super.dispose();
  }

  Future<void> _finish() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool('onboarding_completed', true);
    if (mounted) context.go('/welcome');
  }

  void _next() {
    if (_current < _slides.length - 1) {
      _ctrl.nextPage(
          duration: const Duration(milliseconds: 380), curve: Curves.easeInOut);
    } else {
      _finish();
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      body: Stack(
        children: [
          // Slides.
          PageView.builder(
            controller: _ctrl,
            itemCount: _slides.length,
            onPageChanged: (i) => setState(() => _current = i),
            itemBuilder: (ctx, i) => _SlideView(slide: _slides[i], active: i == _current),
          ),

          // Skip button — top trailing.
          SafeArea(
            child: Align(
              alignment: AlignmentDirectional.topEnd,
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: TextButton(
                  onPressed: _finish,
                  child: Text(
                    'تخطّي',
                    style: TextStyle(
                      color: AppColors.textSecondary,
                      fontWeight: FontWeight.w500,
                      fontSize: 14,
                    ),
                  ),
                ),
              ),
            ),
          ),

          // Bottom controls.
          SafeArea(
            child: Align(
              alignment: Alignment.bottomCenter,
              child: Padding(
                padding: const EdgeInsets.fromLTRB(28, 0, 28, 36),
                child: Row(
                  children: [
                    // Dot indicator.
                    Row(
                      children: List.generate(_slides.length, (i) {
                        final active = i == _current;
                        return AnimatedContainer(
                          duration: const Duration(milliseconds: 280),
                          margin: const EdgeInsetsDirectional.only(end: 6),
                          width: active ? 22 : 7,
                          height: 7,
                          decoration: BoxDecoration(
                            borderRadius: BorderRadius.circular(4),
                            color: active
                                ? AppColors.primary
                                : AppColors.primary.withValues(alpha: 0.22),
                          ),
                        );
                      }),
                    ),

                    const Spacer(),

                    // Next / Start button.
                    GestureDetector(
                      onTap: _next,
                      child: AnimatedContainer(
                        duration: const Duration(milliseconds: 280),
                        height: 52,
                        padding: const EdgeInsets.symmetric(horizontal: 24),
                        decoration: BoxDecoration(
                          borderRadius: BorderRadius.circular(16),
                          gradient: LinearGradient(
                            colors: AppColors.cardGradientVisa,
                          ),
                          boxShadow: [
                            BoxShadow(
                              color: AppColors.primary.withValues(alpha: 0.35),
                              blurRadius: 14,
                              offset: const Offset(0, 6),
                            ),
                          ],
                        ),
                        child: Center(
                          child: Text(
                            _current == _slides.length - 1 ? 'ابدأ الآن' : 'التالي',
                            style: const TextStyle(
                              color: Colors.white,
                              fontWeight: FontWeight.w700,
                              fontSize: 15,
                            ),
                          ),
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

// ── Single slide ──────────────────────────────────────────────────────────────

class _SlideView extends StatelessWidget {
  final _Slide slide;
  final bool active;

  const _SlideView({required this.slide, required this.active});

  @override
  Widget build(BuildContext context) {
    final size = MediaQuery.sizeOf(context);

    return Stack(
      fit: StackFit.expand,
      children: [
        // Upper hero zone — gradient card with medallion.
        Positioned(
          top: 0,
          left: 0,
          right: 0,
          height: size.height * 0.52,
          child: Container(
            decoration: BoxDecoration(
              gradient: LinearGradient(
                begin: Alignment.topCenter,
                end: Alignment.bottomCenter,
                colors: slide.gradient,
              ),
            ),
            child: Stack(
              children: [
                // Damascene watermark.
                CustomPaint(
                  painter: DamasceneMedallionPainter(
                    color: AppColors.accent,
                    opacity: 0.09,
                    alignment: const Alignment(0.9, 0.1),
                  ),
                  size: Size.infinite,
                ),

                // Icon.
                Center(
                  child: Container(
                    width: 120,
                    height: 120,
                    decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      color: Colors.white.withValues(alpha: 0.12),
                      border: Border.all(
                        color: Colors.white.withValues(alpha: 0.25),
                        width: 1.5,
                      ),
                    ),
                    child: Icon(slide.icon, size: 58, color: Colors.white),
                  )
                      .animate(target: active ? 1 : 0)
                      .scale(
                        begin: const Offset(0.75, 0.75),
                        end: const Offset(1.0, 1.0),
                        duration: 500.ms,
                        curve: Curves.easeOutBack,
                      )
                      .fadeIn(duration: 400.ms),
                ),

                // Gold hairline bottom accent.
                Positioned(
                  bottom: 0,
                  left: 0,
                  right: 0,
                  child: Container(
                    height: 2,
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                        colors: [
                          Colors.transparent,
                          AppColors.accent.withValues(alpha: 0.6),
                          Colors.transparent,
                        ],
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),

        // Bottom text zone.
        Positioned(
          top: size.height * 0.52,
          left: 0,
          right: 0,
          bottom: 0,
          child: Padding(
            padding: const EdgeInsets.fromLTRB(28, 40, 28, 100),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  slide.headline,
                  style: TextStyle(
                    fontSize: 24,
                    fontWeight: FontWeight.w700,
                    color: AppColors.textPrimary,
                    height: 1.35,
                  ),
                )
                    .animate(target: active ? 1 : 0)
                    .fadeIn(duration: 380.ms, delay: 80.ms)
                    .slideY(begin: 0.18, end: 0, duration: 380.ms, curve: Curves.easeOut),

                const SizedBox(height: 14),

                Text(
                  slide.sub,
                  style: TextStyle(
                    fontSize: 15,
                    color: AppColors.textSecondary,
                    height: 1.6,
                  ),
                )
                    .animate(target: active ? 1 : 0)
                    .fadeIn(duration: 380.ms, delay: 160.ms)
                    .slideY(begin: 0.18, end: 0, duration: 380.ms, curve: Curves.easeOut),
              ],
            ),
          ),
        ),
      ],
    );
  }
}
