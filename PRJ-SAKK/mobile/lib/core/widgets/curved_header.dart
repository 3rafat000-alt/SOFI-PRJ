import 'package:flutter/material.dart';
import 'package:iconsax/iconsax.dart';

import '../theme/app_colors.dart';

class CurvedHeader extends StatelessWidget {
  final String title;
  final IconData? icon;
  final Widget? extraContent;
  final double height;
  final VoidCallback? onBack;

  const CurvedHeader({
    super.key,
    required this.title,
    this.icon,
    this.extraContent,
    this.height = 260,
    this.onBack,
  });

  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    return ClipRRect(
      borderRadius: const BorderRadius.vertical(bottom: Radius.circular(40)),
      child: Container(
        height: height,
        width: double.infinity,
        decoration: BoxDecoration(
          gradient: LinearGradient(
            colors: colors.cardGradientVisa,
            begin: Alignment.topRight,
            end: Alignment.bottomLeft,
          ),
        ),
        child: Stack(
          children: [
            Positioned(top: -28, right: -24, child: _blob(150, 0.10)),
            Positioned(top: 70, left: -40, child: _blob(120, 0.07)),
            Positioned(bottom: 40, right: 36, child: _blob(46, 0.12)),
            Positioned(bottom: 96, left: 30, child: _blob(22, 0.14)),
            SafeArea(
              bottom: false,
              child: Padding(
                padding: const EdgeInsets.fromLTRB(20, 6, 20, 0),
                child: Column(
                  children: [
                    Row(
                      children: [
                        if (onBack != null)
                          _circleBtn(Iconsax.arrow_right_3, onBack!)
                        else
                          const SizedBox(width: 42),
                        Expanded(
                          child: Text(
                            title,
                            textAlign: TextAlign.center,
                            style: const TextStyle(
                              color: Colors.white,
                              fontSize: 17,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                        ),
                        const SizedBox(width: 42),
                      ],
                    ),
                    if (icon != null || extraContent != null) ...[
                      const SizedBox(height: 16),
                      if (icon != null)
                        Padding(
                          padding: const EdgeInsets.only(bottom: 10),
                          child: Container(
                            width: 64,
                            height: 64,
                            decoration: BoxDecoration(
                              color: Colors.white.withValues(alpha: 0.18),
                              borderRadius: BorderRadius.circular(18),
                            ),
                            child: Icon(icon, color: Colors.white, size: 32),
                          ),
                        ),
                      if (extraContent != null) extraContent!,
                    ],
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  static Widget _blob(double size, double opacity) => Container(
    width: size,
    height: size,
    decoration: BoxDecoration(
      color: Colors.white.withValues(alpha: opacity),
      shape: BoxShape.circle,
    ),
  );

  static Widget _circleBtn(IconData icon, VoidCallback onTap) => GestureDetector(
    onTap: onTap,
    child: Container(
      width: 42,
      height: 42,
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.18),
        borderRadius: BorderRadius.circular(13),
      ),
      child: Icon(icon, color: Colors.white, size: 20),
    ),
  );
}
