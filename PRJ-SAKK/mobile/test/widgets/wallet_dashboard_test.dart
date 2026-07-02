import 'package:flutter_test/flutter_test.dart';
import 'package:flutter/material.dart';
import 'package:iconsax/iconsax.dart';

import 'package:sakk_wallet/core/theme/app_theme.dart';
import 'package:sakk_wallet/core/theme/app_colors.dart';
import 'package:sakk_wallet/features/wallets/data/models/wallet_model.dart';
import 'package:sakk_wallet/features/wallets/presentation/widgets/wallet_card.dart';

Widget _buildApp(Widget child) {
  return MaterialApp(
    theme: AppTheme.lightTheme,
    locale: const Locale('ar'),
    home: Scaffold(body: child),
  );
}

final _usdWallet = WalletModel(
  id: 1,
  currency: 'USD',
  balance: 1500.00,
  availableBalance: 1400.00,
  pendingBalance: 0,
  isActive: true,
  createdAt: DateTime(2026, 1, 1),
);

final _sypWallet = WalletModel(
  id: 2,
  currency: 'SYP',
  balance: 500000,
  availableBalance: 490000,
  pendingBalance: 10000,
  isActive: true,
  createdAt: DateTime(2026, 1, 1),
);

void main() {
  testWidgets('WalletCard renders USD currency and formatted balance',
      (WidgetTester tester) async {
    await tester.pumpWidget(_buildApp(WalletCard(wallet: _usdWallet)));

    expect(find.text('USD'), findsOneWidget);
    expect(find.text('دولار أمريكي'), findsOneWidget);
    expect(find.text('⁦\$1,500.00⁩'), findsOneWidget);
  });

  testWidgets('WalletCard renders SYP currency and formatted balance',
      (WidgetTester tester) async {
    await tester.pumpWidget(_buildApp(WalletCard(wallet: _sypWallet)));

    expect(find.text('SYP'), findsOneWidget);
    expect(find.text('ليرة سورية'), findsOneWidget);
    expect(find.text('⁦ل.س 500,000⁩'), findsOneWidget);
  });

  testWidgets('WalletCard shows pending balance badge when > 0',
      (WidgetTester tester) async {
    await tester.pumpWidget(_buildApp(WalletCard(wallet: _sypWallet)));

    expect(find.text('⁦ل.س 10,000⁩'), findsOneWidget);
  });

  testWidgets('WalletCard calls onTap when tapped',
      (WidgetTester tester) async {
    bool tapped = false;
    await tester.pumpWidget(_buildApp(WalletCard(
      wallet: _usdWallet,
      onTap: () => tapped = true,
    )));

    await tester.tap(find.text('⁦\$1,500.00⁩'));
    await tester.pump();

    expect(tapped, isTrue);
  });

  testWidgets('WalletCard renders card gradient and arrow icon',
      (WidgetTester tester) async {
    await tester.pumpWidget(_buildApp(WalletCard(wallet: _usdWallet)));

    expect(find.byIcon(Iconsax.arrow_left_2), findsOneWidget);
  });
}
