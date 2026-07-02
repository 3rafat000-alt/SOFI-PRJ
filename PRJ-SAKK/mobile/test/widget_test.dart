import 'package:flutter_test/flutter_test.dart';
import 'package:flutter/material.dart';
import 'package:iconsax/iconsax.dart';

import 'package:sakk_wallet/core/theme/app_theme.dart';
import 'package:sakk_wallet/core/theme/app_colors.dart';
import 'package:sakk_wallet/core/widgets/app_ui.dart';

void main() {
  testWidgets('AppTheme renders MaterialApp without crash',
      (WidgetTester tester) async {
    await tester.pumpWidget(
      MaterialApp(
        theme: AppTheme.lightTheme,
        home: const Scaffold(body: Text('SAKK Wallet')),
      ),
    );
    expect(find.text('SAKK Wallet'), findsOneWidget);
  });

  testWidgets('AppScaffold renders body content',
      (WidgetTester tester) async {
    await tester.pumpWidget(
      MaterialApp(
        theme: AppTheme.lightTheme,
        home: AppScaffold(
          title: 'test',
          body: const Text('Body Content'),
        ),
      ),
    );
    expect(find.text('Body Content'), findsOneWidget);
  });

  testWidgets('EmptyState renders icon, title and action',
      (WidgetTester tester) async {
    await tester.pumpWidget(
      MaterialApp(
        theme: AppTheme.lightTheme,
        home: Scaffold(
          body: EmptyState(
            icon: Iconsax.wallet_2,
            title: 'لا توجد محافظ',
            subtitle: 'أنشئ محفظة جديدة للبدء',
            actionLabel: 'إنشاء محفظة',
            onAction: () {},
          ),
        ),
      ),
    );
    expect(find.text('لا توجد محافظ'), findsOneWidget);
    expect(find.text('أنشئ محفظة جديدة للبدء'), findsOneWidget);
    expect(find.text('إنشاء محفظة'), findsOneWidget);
  });
}
