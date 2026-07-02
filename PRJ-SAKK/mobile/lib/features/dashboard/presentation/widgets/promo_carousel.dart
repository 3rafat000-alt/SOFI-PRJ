import 'dart:async';

import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:iconsax/iconsax.dart';

import '../../../../core/theme/app_colors.dart';
import '../../../../core/widgets/app_ui.dart';

class _Promo {
  final IconData icon;
  final String title;
  final String subtitle;
  final String? route;
  const _Promo({
    required this.icon,
    required this.title,
    required this.subtitle,
    this.route,
  });
}

/// A polished, auto-scrolling promotions/ads carousel for the dashboard
/// (cashback offers). Black-identity gradient banners with page indicators.
class PromoCarousel extends StatefulWidget {
  const PromoCarousel({super.key});

  @override
  State<PromoCarousel> createState() => _PromoCarouselState();
}

class _PromoCarouselState extends State<PromoCarousel> {
  static const _promos = <_Promo>[
    _Promo(
      icon: Iconsax.percentage_square,
      title: 'كاش باك ٥٪',
      subtitle: 'استرجِع ٥٪ على تحويلاتك هذا الشهر',
      route: '/cashback',
    ),
    _Promo(
      icon: Iconsax.gift,
      title: 'ادعُ صديقاً واربح',
      subtitle: 'احصل على \$1 لكل صديق ينضم عبر دعوتك',
      route: '/referral',
    ),
    _Promo(
      icon: Iconsax.card,
      title: 'مكافآت البطاقة',
      subtitle: 'كاش باك إضافي عند الدفع ببطاقتك',
      route: '/cashback',
    ),
    _Promo(
      icon: Iconsax.flash_1,
      title: 'تحويلات بلا رسوم',
      subtitle: 'حوّل فوراً بين مستخدمي صكّ مجاناً',
    ),
  ];

  final _controller = PageController(viewportFraction: 0.92);
  Timer? _timer;
  int _index = 0;

  @override
  void initState() {
    super.initState();
    _timer = Timer.periodic(const Duration(seconds: 4), (_) {
      if (!mounted || !_controller.hasClients) return;
      final next = (_index + 1) % _promos.length;
      _controller.animateToPage(
        next,
        duration: const Duration(milliseconds: 450),
        curve: Curves.easeInOut,
      );
    });
  }

  @override
  void dispose() {
    _timer?.cancel();
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    return Column(
      children: [
        SizedBox(
          height: 124,
          child: PageView.builder(
            controller: _controller,
            itemCount: _promos.length,
            onPageChanged: (i) => setState(() => _index = i),
            itemBuilder: (context, i) => _banner(context, _promos[i]),
          ),
        ),
        const SizedBox(height: AppSpacing.sm),
        Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: List.generate(_promos.length, (i) {
            final active = i == _index;
            return AnimatedContainer(
              duration: const Duration(milliseconds: 250),
              margin: const EdgeInsets.symmetric(horizontal: 3),
              width: active ? 18 : 6,
              height: 6,
              decoration: BoxDecoration(
                color: active ? colors.primary : colors.textHint.withValues(alpha: 0.35),
                borderRadius: BorderRadius.circular(3),
              ),
            );
          }),
        ),
      ],
    );
  }

  Widget _banner(BuildContext context, _Promo p) {
    final colors = context.appColors;
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 6),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          borderRadius: BorderRadius.circular(AppRadius.xl),
          onTap: p.route == null ? null : () => context.push(p.route!),
          child: Container(
            decoration: BoxDecoration(
              gradient: LinearGradient(
                colors: colors.cardGradientVisa,
                begin: Alignment.topRight,
                end: Alignment.bottomLeft,
              ),
              borderRadius: BorderRadius.circular(AppRadius.xl),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withValues(alpha: 0.25),
                  blurRadius: 16,
                  offset: const Offset(0, 8),
                ),
              ],
            ),
            child: ClipRRect(
              borderRadius: BorderRadius.circular(AppRadius.xl),
              child: Stack(
                children: [
                  Positioned(
                    left: -12,
                    bottom: -16,
                    child: Icon(p.icon,
                        size: 96, color: Colors.white.withValues(alpha: 0.08)),
                  ),
                  Padding(
                    padding: const EdgeInsets.all(AppSpacing.lg),
                    child: Row(
                      children: [
                        Container(
                          width: 48,
                          height: 48,
                          decoration: BoxDecoration(
                            color: Colors.white.withValues(alpha: 0.14),
                            borderRadius: BorderRadius.circular(14),
                          ),
                          child: Icon(p.icon, color: Colors.white, size: 24),
                        ),
                        const SizedBox(width: AppSpacing.md),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              Text(p.title,
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                  style: const TextStyle(
                                      color: Colors.white,
                                      fontSize: 16,
                                      fontWeight: FontWeight.w800)),
                              const SizedBox(height: 4),
                              Text(p.subtitle,
                                  maxLines: 2,
                                  overflow: TextOverflow.ellipsis,
                                  style: TextStyle(
                                      color: Colors.white.withValues(alpha: 0.85),
                                      fontSize: 12.5,
                                      height: 1.4)),
                            ],
                          ),
                        ),
                        if (p.route != null) ...[
                          const SizedBox(width: AppSpacing.sm),
                          Container(
                            width: 30,
                            height: 30,
                            decoration: BoxDecoration(
                              color: Colors.white.withValues(alpha: 0.16),
                              shape: BoxShape.circle,
                            ),
                            child: const Icon(Iconsax.arrow_left_2,
                                color: Colors.white, size: 16),
                          ),
                        ],
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
