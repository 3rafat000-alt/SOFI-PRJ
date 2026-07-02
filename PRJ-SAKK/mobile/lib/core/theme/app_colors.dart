import 'package:flutter/material.dart';

/// ════════════════════════════════════════════════════════════════════
/// SAKK — هوية "العنابي الدمشقي" 2026 (Damascene Burgundy)
/// Primary: العنابي الفاخر · Accent: الذهبي المعتق · BG: الأبيض الرخامي
/// Light-only identity (no dark mode).
/// ════════════════════════════════════════════════════════════════════
class AppColors {
  AppColors._();

  // Damascene Burgundy (primary)
  static const Color primary = Color(0xFF6E1B2D);
  static const Color primaryLight = Color(0xFFF7E9EC); // soft wine tint
  static const Color primaryDark = Color(0xFF4A1320);
  static const Color secondary = Color(0xFF8E2A3D);
  static const Color secondaryLight = Color(0xFFF0DDE1);

  // Antique / brushed gold (accent)
  static const Color accent = Color(0xFFB58A3C);

  // Marble white surfaces
  static const Color background = Color(0xFFF7F3EE);
  static const Color surface = Colors.white;
  static const Color inputBackground = Color(0xFFF2ECE5);

  // Warm near-black text
  static const Color textPrimary = Color(0xFF2A1A1F);
  static const Color textSecondary = Color(0xFF6E5F63);
  static const Color textHint = Color(0xFFA99FA2);

  // Semantic
  static const Color success = Color(0xFF1F9D55);
  static const Color successLight = Color(0xFFE4F6EC);
  static const Color warning = Color(0xFFB58A3C);
  static const Color warningLight = Color(0xFFF7EEDA);
  static const Color error = Color(0xFFC0392B);
  static const Color errorLight = Color(0xFFFBEAE8);
  static const Color info = Color(0xFF6E1B2D);
  static const Color infoLight = Color(0xFFF7E9EC);

  // Card gradients (velvet wine, gold, warm stone)
  static const List<Color> cardGradientVisa = [Color(0xFF7A2236), Color(0xFF4A1320)];
  static const List<Color> cardGradientMastercard = [Color(0xFF9B3A4D), Color(0xFF6E1B2D)];
  static const List<Color> cardGradientGold = [Color(0xFFC9A24B), Color(0xFF8F6B2A)];
  static const List<Color> cardGradientPlatinum = [Color(0xFF8A7E74), Color(0xFF5C534C)];

  static const Color walletUSD = Color(0xFF1F9D55);
  static const Color walletSYP = Color(0xFFB58A3C);
}

/// Theme-aware color set (injected via ThemeData extensions).
class AppColorsTheme extends ThemeExtension<AppColorsTheme> {
  final Color primary;
  final Color primaryLight;
  final Color primaryDark;
  final Color secondary;
  final Color secondaryLight;
  final Color accent;

  final Color background;
  final Color surface;
  final Color inputBackground;

  final Color textPrimary;
  final Color textSecondary;
  final Color textHint;

  final Color success;
  final Color successLight;
  final Color warning;
  final Color warningLight;
  final Color error;
  final Color errorLight;
  final Color info;
  final Color infoLight;

  final List<Color> cardGradientVisa;
  final List<Color> cardGradientMastercard;
  final List<Color> cardGradientGold;
  final List<Color> cardGradientPlatinum;

  final Color walletUSD;
  final Color walletSYP;

  const AppColorsTheme({
    required this.primary,
    required this.primaryLight,
    required this.primaryDark,
    required this.secondary,
    required this.secondaryLight,
    required this.accent,
    required this.background,
    required this.surface,
    required this.inputBackground,
    required this.textPrimary,
    required this.textSecondary,
    required this.textHint,
    required this.success,
    required this.successLight,
    required this.warning,
    required this.warningLight,
    required this.error,
    required this.errorLight,
    required this.info,
    required this.infoLight,
    required this.cardGradientVisa,
    required this.cardGradientMastercard,
    required this.cardGradientGold,
    required this.cardGradientPlatinum,
    required this.walletUSD,
    required this.walletSYP,
  });

  /// The single, light-only Damascene Burgundy scheme.
  static const light = AppColorsTheme(
    primary: Color(0xFF6E1B2D),
    primaryLight: Color(0xFFF7E9EC),
    primaryDark: Color(0xFF4A1320),
    secondary: Color(0xFF8E2A3D),
    secondaryLight: Color(0xFFF0DDE1),
    accent: Color(0xFFB58A3C),
    background: Color(0xFFF7F3EE),
    surface: Color(0xFFFFFFFF),
    inputBackground: Color(0xFFF2ECE5),
    textPrimary: Color(0xFF2A1A1F),
    textSecondary: Color(0xFF6E5F63),
    textHint: Color(0xFFA99FA2),
    success: Color(0xFF1F9D55),
    successLight: Color(0xFFE4F6EC),
    warning: Color(0xFFB58A3C),
    warningLight: Color(0xFFF7EEDA),
    error: Color(0xFFC0392B),
    errorLight: Color(0xFFFBEAE8),
    info: Color(0xFF6E1B2D),
    infoLight: Color(0xFFF7E9EC),
    cardGradientVisa: [Color(0xFF7A2236), Color(0xFF4A1320)],
    cardGradientMastercard: [Color(0xFF9B3A4D), Color(0xFF6E1B2D)],
    cardGradientGold: [Color(0xFFC9A24B), Color(0xFF8F6B2A)],
    cardGradientPlatinum: [Color(0xFF8A7E74), Color(0xFF5C534C)],
    walletUSD: Color(0xFF1F9D55),
    walletSYP: Color(0xFFB58A3C),
  );

  /// Dark mode was removed — kept as an alias to [light] for compatibility.
  static AppColorsTheme get dark => light;

  @override
  AppColorsTheme copyWith({
    Color? primary,
    Color? primaryLight,
    Color? primaryDark,
    Color? secondary,
    Color? secondaryLight,
    Color? accent,
    Color? background,
    Color? surface,
    Color? inputBackground,
    Color? textPrimary,
    Color? textSecondary,
    Color? textHint,
    Color? success,
    Color? successLight,
    Color? warning,
    Color? warningLight,
    Color? error,
    Color? errorLight,
    Color? info,
    Color? infoLight,
    List<Color>? cardGradientVisa,
    List<Color>? cardGradientMastercard,
    List<Color>? cardGradientGold,
    List<Color>? cardGradientPlatinum,
    Color? walletUSD,
    Color? walletSYP,
  }) {
    return AppColorsTheme(
      primary: primary ?? this.primary,
      primaryLight: primaryLight ?? this.primaryLight,
      primaryDark: primaryDark ?? this.primaryDark,
      secondary: secondary ?? this.secondary,
      secondaryLight: secondaryLight ?? this.secondaryLight,
      accent: accent ?? this.accent,
      background: background ?? this.background,
      surface: surface ?? this.surface,
      inputBackground: inputBackground ?? this.inputBackground,
      textPrimary: textPrimary ?? this.textPrimary,
      textSecondary: textSecondary ?? this.textSecondary,
      textHint: textHint ?? this.textHint,
      success: success ?? this.success,
      successLight: successLight ?? this.successLight,
      warning: warning ?? this.warning,
      warningLight: warningLight ?? this.warningLight,
      error: error ?? this.error,
      errorLight: errorLight ?? this.errorLight,
      info: info ?? this.info,
      infoLight: infoLight ?? this.infoLight,
      cardGradientVisa: cardGradientVisa ?? this.cardGradientVisa,
      cardGradientMastercard: cardGradientMastercard ?? this.cardGradientMastercard,
      cardGradientGold: cardGradientGold ?? this.cardGradientGold,
      cardGradientPlatinum: cardGradientPlatinum ?? this.cardGradientPlatinum,
      walletUSD: walletUSD ?? this.walletUSD,
      walletSYP: walletSYP ?? this.walletSYP,
    );
  }

  @override
  AppColorsTheme lerp(AppColorsTheme? other, double t) {
    if (other == null) return this;
    return AppColorsTheme(
      primary: Color.lerp(primary, other.primary, t)!,
      primaryLight: Color.lerp(primaryLight, other.primaryLight, t)!,
      primaryDark: Color.lerp(primaryDark, other.primaryDark, t)!,
      secondary: Color.lerp(secondary, other.secondary, t)!,
      secondaryLight: Color.lerp(secondaryLight, other.secondaryLight, t)!,
      accent: Color.lerp(accent, other.accent, t)!,
      background: Color.lerp(background, other.background, t)!,
      surface: Color.lerp(surface, other.surface, t)!,
      inputBackground: Color.lerp(inputBackground, other.inputBackground, t)!,
      textPrimary: Color.lerp(textPrimary, other.textPrimary, t)!,
      textSecondary: Color.lerp(textSecondary, other.textSecondary, t)!,
      textHint: Color.lerp(textHint, other.textHint, t)!,
      success: Color.lerp(success, other.success, t)!,
      successLight: Color.lerp(successLight, other.successLight, t)!,
      warning: Color.lerp(warning, other.warning, t)!,
      warningLight: Color.lerp(warningLight, other.warningLight, t)!,
      error: Color.lerp(error, other.error, t)!,
      errorLight: Color.lerp(errorLight, other.errorLight, t)!,
      info: Color.lerp(info, other.info, t)!,
      infoLight: Color.lerp(infoLight, other.infoLight, t)!,
      cardGradientVisa: t < 0.5 ? cardGradientVisa : other.cardGradientVisa,
      cardGradientMastercard: t < 0.5 ? cardGradientMastercard : other.cardGradientMastercard,
      cardGradientGold: t < 0.5 ? cardGradientGold : other.cardGradientGold,
      cardGradientPlatinum: t < 0.5 ? cardGradientPlatinum : other.cardGradientPlatinum,
      walletUSD: Color.lerp(walletUSD, other.walletUSD, t)!,
      walletSYP: Color.lerp(walletSYP, other.walletSYP, t)!,
    );
  }
}

extension AppColorsBuildContext on BuildContext {
  AppColorsTheme get appColors => Theme.of(this).extension<AppColorsTheme>()!;
}
