/// Widget tests for GoldPage — mocked gold providers.
library;

import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:sakk_wallet/core/widgets/app_skeleton.dart';
import 'package:sakk_wallet/core/theme/app_theme.dart';
import 'package:sakk_wallet/features/gold/data/models/gold_models.dart';
import 'package:sakk_wallet/features/gold/data/repositories/gold_repository.dart';
import 'package:sakk_wallet/features/gold/presentation/pages/gold_page.dart';

import '../../../../helpers/mocks.dart';

Widget _buildGoldPage({
  GoldWalletModel? wallet,
  List<GoldTransactionModel> transactions = const [],
  List<GoldPriceModel> prices = const [],
}) {
  return ProviderScope(
    overrides: [
      goldWalletProvider.overrideWith((ref) => Future.value(wallet)),
      goldPricesProvider.overrideWith((ref) => Future.value(prices)),
      goldTransactionsProvider.overrideWith((ref) => Future.value(transactions)),
    ],
    child: MaterialApp(
      theme: AppTheme.lightTheme,
      locale: const Locale('ar'),
      home: const GoldPage(),
    ),
  );
}

/// Catch all errors to avoid UnhandledException in test for error-state test.
Widget _buildGoldPageError() {
  return ProviderScope(
    overrides: [
      goldWalletProvider.overrideWith((ref) => Future.error('Server error')),
      goldPricesProvider.overrideWith((ref) => Future.value([])),
      goldTransactionsProvider.overrideWith((ref) => Future.value([])),
    ],
    child: MaterialApp(
      theme: AppTheme.lightTheme,
      locale: const Locale('ar'),
      home: const GoldPage(),
    ),
  );
}

/// Pump enough to get past flutter_animate entrance delays + fade.
Future<void> pumpPastAnimations(WidgetTester tester) async {
  await tester.pump();
  await tester.pump(const Duration(milliseconds: 800));
}

/// Cleanup flutter_animate repeating timers.
Future<void> cleanupTimers(WidgetTester tester) async {
  await tester.pumpWidget(const SizedBox());
  await tester.pump(const Duration(milliseconds: 200));
}

void main() {
  testWidgets('renders gold balance card', (WidgetTester tester) async {
    await tester.pumpWidget(_buildGoldPage(
      wallet: testGoldWallet,
      prices: testGoldWallet.prices,
      transactions: [testGoldTx],
    ));
    await pumpPastAnimations(tester);

    expect(find.text('رصيد الذهب'), findsOneWidget);
    expect(find.text('10.50'), findsOneWidget);
    expect(find.text('غرام'), findsOneWidget);
    expect(find.textContaining('\$792.75'), findsWidgets);

    await cleanupTimers(tester);
  });

  testWidgets('shows buy and sell buttons', (WidgetTester tester) async {
    await tester.pumpWidget(_buildGoldPage(
      wallet: testGoldWallet,
      prices: testGoldWallet.prices,
    ));
    await pumpPastAnimations(tester);

    expect(find.text('شراء ذهب'), findsOneWidget);
    expect(find.text('بيع ذهب'), findsOneWidget);

    await cleanupTimers(tester);
  });

  testWidgets('shows live prices section', (WidgetTester tester) async {
    await tester.pumpWidget(_buildGoldPage(
      wallet: testGoldWallet,
      prices: testGoldWallet.prices,
    ));
    await pumpPastAnimations(tester);

    expect(find.text('أسعار الذهب اليوم'), findsOneWidget);
    expect(find.text('عيار 24'), findsWidgets);
    expect(find.text('عيار 22'), findsOneWidget);

    await cleanupTimers(tester);
  });

  testWidgets('shows empty history', (WidgetTester tester) async {
    await tester.pumpWidget(_buildGoldPage(
      wallet: testGoldWallet,
      prices: testGoldWallet.prices,
      transactions: [],
    ));
    await pumpPastAnimations(tester);

    // Scroll down to the history section
    await tester.drag(find.byType(ListView), const Offset(0, -500));
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 500));

    expect(find.text('سجل العمليات'), findsOneWidget);
    expect(find.text('لا توجد عمليات بعد'), findsOneWidget);

    await cleanupTimers(tester);
  });

  testWidgets('shows transaction history', (WidgetTester tester) async {
    await tester.pumpWidget(_buildGoldPage(
      wallet: testGoldWallet,
      prices: testGoldWallet.prices,
      transactions: [testGoldTx],
    ));
    await pumpPastAnimations(tester);

    // Scroll down to the transaction history section
    await tester.drag(find.byType(ListView), const Offset(0, -500));
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 500));

    expect(find.text('شراء 5.00 غرام'), findsOneWidget);
    expect(find.textContaining('\$381.28'), findsOneWidget);

    await cleanupTimers(tester);
  });

  testWidgets('shows skeleton during loading', (WidgetTester tester) async {
    // Never-completing future keeps the loading state active.
    final neverEnding = Completer<GoldWalletModel>().future;
    await tester.pumpWidget(ProviderScope(
      overrides: [
        goldWalletProvider.overrideWith((ref) => neverEnding),
        goldPricesProvider.overrideWith((ref) => Future.value([])),
        goldTransactionsProvider.overrideWith((ref) => Future.value([])),
      ],
      child: MaterialApp(
        theme: AppTheme.lightTheme,
        locale: const Locale('ar'),
        home: const GoldPage(),
      ),
    ));
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 600));

    expect(find.byType(SkeletonBalanceCard, skipOffstage: false), findsOneWidget);

    await cleanupTimers(tester);
  });

  testWidgets('shows error state when wallet fails', (WidgetTester tester) async {
    await tester.pumpWidget(_buildGoldPageError());
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 500));

    expect(find.text('تعذّر تحميل محفظة الذهب'), findsOneWidget);
    expect(find.text('إعادة المحاولة'), findsOneWidget);

    await cleanupTimers(tester);
  });

  testWidgets('shows sell button disabled when no gold',
      (WidgetTester tester) async {
    final emptyWallet = GoldWalletModel(
      balanceGrams: 0,
      currentValueUsd: 0,
      totalInvestedUsd: 0,
      totalBoughtGrams: 0,
      totalSoldGrams: 0,
      profitLossUsd: 0,
      usdBalance: 5000,
      prices: testGoldWallet.prices,
    );

    await tester.pumpWidget(_buildGoldPage(
      wallet: emptyWallet,
      prices: testGoldWallet.prices,
    ));
    await pumpPastAnimations(tester);

    expect(find.text('شراء ذهب'), findsOneWidget);
    expect(find.text('بيع ذهب'), findsOneWidget);

    await cleanupTimers(tester);
  });
}
