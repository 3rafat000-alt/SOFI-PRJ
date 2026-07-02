// SAKK Wallet — Unified Design System
import 'package:flutter/material.dart';
import 'package:iconsax/iconsax.dart';

import '../theme/app_colors.dart';

// ════════════════════════════════════════════════════════════════════
// 1) المسافات ونصف الأقطار والظلال — رموز التصميم
// ════════════════════════════════════════════════════════════════════
class AppSpacing {
  AppSpacing._();
  static const double xs = 4;
  static const double sm = 8;
  static const double md = 12;
  static const double lg = 16;
  static const double xl = 20;
  static const double xxl = 24;
  static const double xxxl = 32;
}

class AppRadius {
  AppRadius._();
  static const double sm = 8;
  static const double md = 12;
  static const double lg = 16;
  static const double xl = 20;
  static const double pill = 999;
}

class AppShadows {
  AppShadows._();

  static List<BoxShadow> get card => [
        BoxShadow(
          color: Colors.black.withValues(alpha: 0.04),
          blurRadius: 12,
          offset: const Offset(0, 4),
        ),
      ];

  static List<BoxShadow> get soft => [
        BoxShadow(
          color: Colors.black.withValues(alpha: 0.03),
          blurRadius: 8,
          offset: const Offset(0, 2),
        ),
      ];
}

// ════════════════════════════════════════════════════════════════════
// 2) AppHeader
// ════════════════════════════════════════════════════════════════════
/// Minimal top bar. Page titles were intentionally removed app-wide for a
/// cleaner look — this now renders only a back button (when applicable) and an
/// optional trailing [action]. [title]/[subtitle] are kept for compatibility
/// (and accessibility) but are no longer displayed.
class AppHeader extends StatelessWidget implements PreferredSizeWidget {
  final String title;
  final String? subtitle;
  final VoidCallback? onBack;
  final bool showBack;
  final Widget? action;

  const AppHeader({
    super.key,
    required this.title,
    this.subtitle,
    this.onBack,
    this.showBack = true,
    this.action,
  });

  @override
  Size get preferredSize => const Size.fromHeight(56);

  @override
  Widget build(BuildContext context) {
    final hasAction = action != null;

    // Main tab pages (no back, no action): just clear the status bar so the
    // content starts cleanly without an empty title bar.
    if (!showBack && !hasAction) {
      return const SafeArea(bottom: false, child: SizedBox(height: AppSpacing.sm));
    }

    return SafeArea(
      bottom: false,
      child: Padding(
        padding: const EdgeInsets.fromLTRB(
            AppSpacing.md, AppSpacing.sm, AppSpacing.md, AppSpacing.xs),
        child: Row(
          children: [
            if (showBack)
              _HeaderBackButton(onBack: onBack)
            else
              const SizedBox(width: 44),
            const Spacer(),
            if (hasAction) action!,
          ],
        ),
      ),
    );
  }
}

/// Circular back button used by [AppHeader] (RTL — points to the right).
class _HeaderBackButton extends StatelessWidget {
  final VoidCallback? onBack;
  const _HeaderBackButton({this.onBack});

  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    return GestureDetector(
      onTap: onBack ?? () => Navigator.maybePop(context),
      child: Container(
        width: 44,
        height: 44,
        decoration: BoxDecoration(
          color: colors.surface,
          shape: BoxShape.circle,
          border: Border.all(color: colors.inputBackground),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.04),
              blurRadius: 8,
              offset: const Offset(0, 2),
            ),
          ],
        ),
        child: Icon(Iconsax.arrow_right_3, color: colors.textPrimary, size: 20),
      ),
    );
  }
}

// ════════════════════════════════════════════════════════════════════
// 3) AppScaffold
// ════════════════════════════════════════════════════════════════════
class AppScaffold extends StatelessWidget {
  final String title;
  final String? subtitle;
  final VoidCallback? onBack;
  final bool showBack;
  final Widget? action;
  final Widget body;
  final Widget? floatingActionButton;
  final Widget? bottomBar;
  final Future<void> Function()? onRefresh;
  final bool safeBottom;

  const AppScaffold({
    super.key,
    required this.title,
    this.subtitle,
    this.onBack,
    this.showBack = true,
    this.action,
    required this.body,
    this.floatingActionButton,
    this.bottomBar,
    this.onRefresh,
    this.safeBottom = true,
  });

  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    Widget content = body;
    if (onRefresh != null) {
      content = RefreshIndicator(
        color: colors.primary,
        onRefresh: onRefresh!,
        child: content,
      );
    }
    return Scaffold(
      backgroundColor: colors.background,
      body: Column(
        children: [
          AppHeader(
            title: title,
            subtitle: subtitle,
            onBack: onBack,
            showBack: showBack,
            action: action,
          ),
          Expanded(
            // Edge-to-edge is on (Flutter default) → protect body content from
            // the Android system nav bar. When a bottomBar exists it owns the
            // bottom inset (below), so the body must not double-pad.
            child: SafeArea(
              top: false,
              bottom: safeBottom && bottomBar == null,
              child: content,
            ),
          ),
          if (bottomBar != null)
            SafeArea(top: false, child: bottomBar!),
        ],
      ),
      floatingActionButton: floatingActionButton,
    );
  }
}

// ════════════════════════════════════════════════════════════════════
// 4) AppCard
// ════════════════════════════════════════════════════════════════════
class AppCard extends StatelessWidget {
  final Widget child;
  final EdgeInsetsGeometry padding;
  final EdgeInsetsGeometry? margin;
  final VoidCallback? onTap;
  final Color? color;
  final double radius;
  final Border? border;

  const AppCard({
    super.key,
    required this.child,
    this.padding = const EdgeInsets.all(AppSpacing.lg),
    this.margin,
    this.onTap,
    this.color,
    this.radius = AppRadius.lg,
    this.border,
  });

  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    final card = Container(
      width: double.infinity,
      padding: padding,
      decoration: BoxDecoration(
        color: color ?? colors.surface,
        borderRadius: BorderRadius.circular(radius),
        boxShadow: AppShadows.card,
        border: border,
      ),
      child: child,
    );

    final wrapped = onTap != null
        ? Material(
            color: Colors.transparent,
            child: InkWell(
              borderRadius: BorderRadius.circular(radius),
              onTap: onTap,
              child: card,
            ),
          )
        : card;

    if (margin != null) {
      return Padding(padding: margin!, child: wrapped);
    }
    return wrapped;
  }
}

// ════════════════════════════════════════════════════════════════════
// 5) SectionHeader
// ════════════════════════════════════════════════════════════════════
class SectionHeader extends StatelessWidget {
  final String title;
  final String? actionLabel;
  final VoidCallback? onAction;

  const SectionHeader({
    super.key,
    required this.title,
    this.actionLabel,
    this.onAction,
  });

  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: AppSpacing.sm),
      child: Row(
        children: [
          Text(
            title,
            style: TextStyle(
              fontSize: 17,
              fontWeight: FontWeight.w700,
              color: colors.textPrimary,
            ),
          ),
          const Spacer(),
          if (actionLabel != null)
            TextButton(
              onPressed: onAction,
              style: TextButton.styleFrom(
                padding: const EdgeInsets.symmetric(horizontal: AppSpacing.sm),
                minimumSize: Size.zero,
                tapTargetSize: MaterialTapTargetSize.shrinkWrap,
              ),
              child: Text(
                actionLabel!,
                style: TextStyle(
                  fontSize: 13,
                  fontWeight: FontWeight.w600,
                  color: colors.primary,
                ),
              ),
            ),
        ],
      ),
    );
  }
}

// ════════════════════════════════════════════════════════════════════
// 6) IconTile
// ════════════════════════════════════════════════════════════════════
class IconTile extends StatelessWidget {
  final IconData icon;
  final double size;
  final Color? color;
  final double radius;

  const IconTile({
    super.key,
    required this.icon,
    this.size = 44,
    this.color,
    this.radius = AppRadius.md,
  });

  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    final c = color ?? colors.primary;
    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
        color: c.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(radius),
      ),
      child: Icon(icon, color: c, size: size * 0.5),
    );
  }
}

// ════════════════════════════════════════════════════════════════════
// 7) AppActionButton
// ════════════════════════════════════════════════════════════════════
class AppActionButton extends StatelessWidget {
  final IconData icon;
  final String label;
  final VoidCallback onTap;

  const AppActionButton({
    super.key,
    required this.icon,
    required this.label,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    return Expanded(
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          borderRadius: BorderRadius.circular(AppRadius.lg),
          onTap: onTap,
          child: Padding(
            padding: const EdgeInsets.symmetric(vertical: AppSpacing.md),
            child: Column(
              children: [
                Container(
                  width: 52,
                  height: 52,
                  decoration: BoxDecoration(
                    color: colors.surface,
                    borderRadius: BorderRadius.circular(AppRadius.lg),
                    boxShadow: AppShadows.soft,
                  ),
                  child: Icon(icon, color: colors.primary, size: 24),
                ),
                const SizedBox(height: AppSpacing.sm),
                Text(
                  label,
                  style: TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.w600,
                    color: colors.textPrimary,
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

// ════════════════════════════════════════════════════════════════════
// 8) ListTileCard
// ════════════════════════════════════════════════════════════════════
class ListTileCard extends StatelessWidget {
  final IconData icon;
  final Color? iconColor;
  final String title;
  final String? subtitle;
  final Widget? trailing;
  final VoidCallback? onTap;

  const ListTileCard({
    super.key,
    required this.icon,
    this.iconColor,
    required this.title,
    this.subtitle,
    this.trailing,
    this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    return AppCard(
      onTap: onTap,
      padding: const EdgeInsets.symmetric(
          horizontal: AppSpacing.lg, vertical: AppSpacing.md),
      child: Row(
        children: [
          IconTile(icon: icon, color: iconColor),
          const SizedBox(width: AppSpacing.md),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: TextStyle(
                    fontSize: 15,
                    fontWeight: FontWeight.w600,
                    color: colors.textPrimary,
                  ),
                ),
                if (subtitle != null) ...[
                  const SizedBox(height: 2),
                  Text(
                    subtitle!,
                    style: TextStyle(
                      fontSize: 12.5,
                      color: colors.textSecondary,
                    ),
                  ),
                ],
              ],
            ),
          ),
          if (trailing != null) ...[
            const SizedBox(width: AppSpacing.sm),
            trailing!,
          ],
        ],
      ),
    );
  }
}

// ════════════════════════════════════════════════════════════════════
// 9) AmountText
// ════════════════════════════════════════════════════════════════════
class AmountText extends StatelessWidget {
  final String amount;
  final bool? isCredit;
  final double fontSize;
  final FontWeight fontWeight;

  const AmountText({
    super.key,
    required this.amount,
    this.isCredit,
    this.fontSize = 16,
    this.fontWeight = FontWeight.w700,
  });

  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    Color color = colors.textPrimary;
    if (isCredit == true) color = colors.success;
    if (isCredit == false) color = colors.error;
    return Text(
      amount,
      style: TextStyle(
        fontSize: fontSize,
        fontWeight: fontWeight,
        color: color,
      ),
    );
  }
}

// ════════════════════════════════════════════════════════════════════
// 10) StatusBadge
// ════════════════════════════════════════════════════════════════════
enum StatusKind { success, warning, error, info, neutral }

class StatusBadge extends StatelessWidget {
  final String label;
  final StatusKind kind;

  const StatusBadge({
    super.key,
    required this.label,
    this.kind = StatusKind.neutral,
  });

  factory StatusBadge.fromColor(String label, String? color) {
    StatusKind k;
    switch (color) {
      case 'green':
        k = StatusKind.success;
        break;
      case 'red':
        k = StatusKind.error;
        break;
      case 'amber':
      case 'orange':
      case 'yellow':
        k = StatusKind.warning;
        break;
      case 'blue':
        k = StatusKind.info;
        break;
      default:
        k = StatusKind.neutral;
    }
    return StatusBadge(label: label, kind: k);
  }

  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    late Color fg;
    late Color bg;
    switch (kind) {
      case StatusKind.success:
        fg = colors.success;
        bg = colors.successLight;
        break;
      case StatusKind.warning:
        fg = colors.warning;
        bg = colors.warningLight;
        break;
      case StatusKind.error:
        fg = colors.error;
        bg = colors.errorLight;
        break;
      case StatusKind.info:
        fg = colors.info;
        bg = colors.infoLight;
        break;
      case StatusKind.neutral:
        fg = colors.textSecondary;
        bg = colors.inputBackground;
        break;
    }
    return Container(
      padding: const EdgeInsets.symmetric(
          horizontal: AppSpacing.sm, vertical: 3),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(AppRadius.sm),
      ),
      child: Text(
        label,
        style: TextStyle(
          fontSize: 11.5,
          fontWeight: FontWeight.w600,
          color: fg,
        ),
      ),
    );
  }
}

// ════════════════════════════════════════════════════════════════════
// 11) AppButton
// ════════════════════════════════════════════════════════════════════
enum AppButtonVariant { primary, secondary, danger }

class AppButton extends StatelessWidget {
  final String label;
  final VoidCallback? onPressed;
  final IconData? icon;
  final bool loading;
  final bool fullWidth;
  final AppButtonVariant variant;

  const AppButton({
    super.key,
    required this.label,
    required this.onPressed,
    this.icon,
    this.loading = false,
    this.fullWidth = true,
    this.variant = AppButtonVariant.primary,
  });

  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final Color bg = switch (variant) {
      AppButtonVariant.primary => isDark ? colors.surface : colors.primary,
      AppButtonVariant.danger => colors.error,
      AppButtonVariant.secondary => colors.primaryLight,
    };
    final Color fg = isDark && variant == AppButtonVariant.primary
        ? colors.textPrimary
        : variant == AppButtonVariant.secondary
            ? colors.primary
            : Colors.white;

    final child = loading
        ? SizedBox(
            width: 22,
            height: 22,
            child: CircularProgressIndicator(
              strokeWidth: 2.4,
              valueColor: AlwaysStoppedAnimation(fg),
            ),
          )
        : Row(
            mainAxisSize: MainAxisSize.min,
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              if (icon != null) ...[
                Icon(icon, size: 20, color: fg),
                const SizedBox(width: AppSpacing.sm),
              ],
              Text(
                label,
                style: TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.w700,
                  color: fg,
                ),
              ),
            ],
          );

    return SizedBox(
      width: fullWidth ? double.infinity : null,
      height: 54,
      child: ElevatedButton(
        onPressed: loading ? null : onPressed,
        style: ElevatedButton.styleFrom(
          backgroundColor: bg,
          disabledBackgroundColor: bg.withValues(alpha: 0.6),
          foregroundColor: fg,
          elevation: 0,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(AppRadius.md),
          ),
        ),
        child: child,
      ),
    );
  }
}

// ════════════════════════════════════════════════════════════════════
// 12) EmptyState
// ════════════════════════════════════════════════════════════════════
class EmptyState extends StatelessWidget {
  final IconData icon;
  final String title;
  final String? subtitle;
  final String? actionLabel;
  final VoidCallback? onAction;

  const EmptyState({
    super.key,
    required this.icon,
    required this.title,
    this.subtitle,
    this.actionLabel,
    this.onAction,
  });

  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(AppSpacing.xxl),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 88,
              height: 88,
              decoration: BoxDecoration(
                color: colors.primaryLight,
                shape: BoxShape.circle,
              ),
              child: Icon(icon, size: 40, color: colors.primary),
            ),
            const SizedBox(height: AppSpacing.lg),
            Text(
              title,
              textAlign: TextAlign.center,
              style: TextStyle(
                fontSize: 17,
                fontWeight: FontWeight.w700,
                color: colors.textPrimary,
              ),
            ),
            if (subtitle != null) ...[
              const SizedBox(height: AppSpacing.sm),
              Text(
                subtitle!,
                textAlign: TextAlign.center,
                style: TextStyle(
                  fontSize: 14,
                  color: colors.textSecondary,
                ),
              ),
            ],
            if (actionLabel != null) ...[
              const SizedBox(height: AppSpacing.xl),
              AppButton(
                label: actionLabel!,
                onPressed: onAction,
                fullWidth: false,
              ),
            ],
          ],
        ),
      ),
    );
  }
}
