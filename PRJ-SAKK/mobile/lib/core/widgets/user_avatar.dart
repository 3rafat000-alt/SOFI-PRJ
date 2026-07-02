import 'package:flutter/material.dart';

import '../theme/app_colors.dart';

/// A reusable avatar that renders the user's network photo when available,
/// otherwise falls back to a gradient tile with the user's initials.
///
/// Used across the dashboard header, settings hero, and profile page so the
/// avatar stays consistent everywhere.
class UserAvatar extends StatelessWidget {
  final String? imageUrl;
  final String initials;
  final double size;

  /// Corner radius. If null, the avatar is a perfect circle.
  final double? radius;

  /// Fallback gradient for the initials tile. When null, the theme-aware
  /// brand gradient (`cardGradientVisa`) is used so it stays dark in both
  /// light & dark mode (keeping the white initials readable).
  final List<Color>? gradient;
  final double? fontSize;
  final Color? borderColor;
  final double borderWidth;
  final List<BoxShadow>? shadow;

  const UserAvatar({
    super.key,
    this.imageUrl,
    required this.initials,
    this.size = 48,
    this.radius,
    this.gradient,
    this.fontSize,
    this.borderColor,
    this.borderWidth = 0,
    this.shadow,
  });

  BorderRadius get _br =>
      BorderRadius.circular(radius ?? size); // size => fully rounded (circle)

  bool get _hasImage => imageUrl != null && imageUrl!.trim().isNotEmpty;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
        borderRadius: _br,
        border: borderColor != null && borderWidth > 0
            ? Border.all(color: borderColor!, width: borderWidth)
            : null,
        boxShadow: shadow,
      ),
      child: ClipRRect(
        borderRadius: _br,
        child: _hasImage
            ? Image.network(
                imageUrl!,
                fit: BoxFit.cover,
                loadingBuilder: (context, child, progress) =>
                    progress == null ? child : _gradientInitials(context),
                errorBuilder: (context, error, stack) => _gradientInitials(context),
              )
            : _gradientInitials(context),
      ),
    );
  }

  Widget _gradientInitials(BuildContext context) {
    return DecoratedBox(
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: gradient ?? context.appColors.cardGradientVisa,
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
      ),
      child: Center(
        child: Text(
          initials,
          style: TextStyle(
            color: Colors.white,
            fontWeight: FontWeight.bold,
            fontSize: fontSize ?? size * 0.38,
            letterSpacing: 0.5,
          ),
        ),
      ),
    );
  }
}
