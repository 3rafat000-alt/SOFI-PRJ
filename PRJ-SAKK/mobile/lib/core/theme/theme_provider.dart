import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

/// Dark mode was removed — the app is light-only (Damascene Burgundy identity).
/// This provider is kept for backwards-compatibility and always reports light.
final themeModeProvider = StateNotifierProvider<ThemeModeNotifier, ThemeMode>((ref) {
  return ThemeModeNotifier();
});

class ThemeModeNotifier extends StateNotifier<ThemeMode> {
  ThemeModeNotifier() : super(ThemeMode.light);

  // No-ops: the project is light-only.
  Future<void> set(ThemeMode mode) async {}
  void toggle() {}
}
