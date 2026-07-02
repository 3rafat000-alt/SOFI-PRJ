/// Widget tests for DashboardPage — mocked providers.
library;

import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mocktail/mocktail.dart';

import 'package:sakk_wallet/core/widgets/app_skeleton.dart';
import 'package:sakk_wallet/core/theme/app_theme.dart';
import 'package:sakk_wallet/core/network/api_client.dart';
import 'package:sakk_wallet/features/auth/data/models/user_model.dart';
import 'package:sakk_wallet/features/auth/data/repositories/auth_repository.dart';
import 'package:sakk_wallet/features/auth/providers/auth_provider.dart';
import 'package:sakk_wallet/features/notifications/data/repositories/notification_repository.dart';
import 'package:sakk_wallet/features/settings/data/repositories/device_repository.dart';
import 'package:sakk_wallet/features/wallets/data/models/wallet_model.dart';
import 'package:sakk_wallet/features/wallets/data/repositories/wallet_repository.dart';
import 'package:sakk_wallet/features/transactions/data/models/transaction_model.dart';
import 'package:sakk_wallet/features/transactions/data/repositories/transaction_repository.dart';
import 'package:sakk_wallet/features/dashboard/presentation/pages/dashboard_page.dart';

import '../../../../helpers/mocks.dart';

/// Build dashboard with mocked async providers delivering data.
Widget _buildDashboard({
  List<WalletModel> wallets = const [],
  List<TransactionModel> transactions = const [],
  UserModel? user,
  int unreadCount = 0,
}) {
  return ProviderScope(
    overrides: [
      currentUserProvider.overrideWith((ref) => user),
      walletsProvider.overrideWith((ref) => Future.value(wallets)),
      recentTransactionsProvider.overrideWith((ref) => Future.value(transactions)),
      unreadNotificationsProvider.overrideWith((ref) => Future.value(unreadCount)),
      deviceRegistrationProvider.overrideWith((ref) => Future.value(null)),
      secureStorageProvider.overrideWith(
        (ref) => throw UnimplementedError('not needed'),
      ),
      dioProvider.overrideWith(
        (ref) => throw UnimplementedError('not needed'),
      ),
    ],
    child: MaterialApp(
      theme: AppTheme.lightTheme,
      locale: const Locale('ar'),
      home: const DashboardPage(),
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
  testWidgets('renders greeting with user name', (WidgetTester tester) async {
    await tester.pumpWidget(_buildDashboard(
      wallets: [testWallet],
      user: testUser,
    ));
    await pumpPastAnimations(tester);

    expect(find.text('مرحباً'), findsOneWidget);
    expect(find.text('أحمد السوري'), findsOneWidget);

    await cleanupTimers(tester);
  });

  testWidgets('renders "مستخدم" when no user', (WidgetTester tester) async {
    await tester.pumpWidget(_buildDashboard(wallets: [testWallet]));
    await pumpPastAnimations(tester);

    expect(find.text('مستخدم'), findsOneWidget);

    await cleanupTimers(tester);
  });

  testWidgets('renders wallet balance card', (WidgetTester tester) async {
    await tester.pumpWidget(_buildDashboard(
      wallets: [testWallet],
      user: testUser,
    ));
    await pumpPastAnimations(tester);

    expect(find.text('⁦\$1,500.00⁩'), findsOneWidget);

    await cleanupTimers(tester);
  });

  testWidgets('shows skeleton when wallets loading', (WidgetTester tester) async {
    // Use a never-completing future to keep the loading state active.
    final neverEnding = Completer<List<WalletModel>>().future;
    await tester.pumpWidget(ProviderScope(
      overrides: [
        currentUserProvider.overrideWith((ref) => testUser),
        walletsProvider.overrideWith((ref) => neverEnding),
        recentTransactionsProvider.overrideWith((ref) => Future.value(<TransactionModel>[])),
        unreadNotificationsProvider.overrideWith((ref) => Future.value(0)),
        deviceRegistrationProvider.overrideWith((ref) => Future.value(null)),
        secureStorageProvider.overrideWith(
          (ref) => throw UnimplementedError('not needed'),
        ),
        dioProvider.overrideWith(
          (ref) => throw UnimplementedError('not needed'),
        ),
      ],
      child: MaterialApp(
        theme: AppTheme.lightTheme,
        locale: const Locale('ar'),
        home: const DashboardPage(),
      ),
    ));
    // Pump past flutter_animate delays (balance card: 100ms, shortcuts: 300ms)
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 600));

    // The loading skeleton is rendered by walletsAsync.when(loading: ...)
    // which shows SkeletonBalanceCard.
    expect(find.byType(SkeletonBalanceCard, skipOffstage: false), findsOneWidget);

    await cleanupTimers(tester);
  });

  testWidgets('shows action buttons', (WidgetTester tester) async {
    await tester.pumpWidget(_buildDashboard(
      wallets: [testWallet],
      user: testUser,
    ));
    await pumpPastAnimations(tester);

    expect(find.text('إيداع'), findsOneWidget);
    expect(find.text('سحب'), findsOneWidget);

    await cleanupTimers(tester);
  });

  testWidgets('shows service shortcuts', (WidgetTester tester) async {
    await tester.pumpWidget(_buildDashboard(
      wallets: [testWallet],
      user: testUser,
    ));
    await pumpPastAnimations(tester);

    expect(find.text('الادخار'), findsOneWidget);
    expect(find.text('الفواتير'), findsOneWidget);
    expect(find.text('الذهب'), findsOneWidget);

    await cleanupTimers(tester);
  });

  testWidgets('shows "لا توجد معاملات" when no transactions',
      (WidgetTester tester) async {
    await tester.pumpWidget(_buildDashboard(
      wallets: [testWallet],
      user: testUser,
      transactions: [],
    ));
    await pumpPastAnimations(tester);

    // Scroll down to the transactions section
    await tester.drag(find.byType(CustomScrollView), const Offset(0, -500));
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 800));

    expect(find.text('لا توجد معاملات'), findsOneWidget);

    await cleanupTimers(tester);
  });

  testWidgets('shows transaction list', (WidgetTester tester) async {
    await tester.pumpWidget(_buildDashboard(
      wallets: [testWallet],
      user: testUser,
      transactions: [testTransaction],
    ));
    await pumpPastAnimations(tester);

    // Scroll down to the transactions section
    await tester.drag(find.byType(CustomScrollView), const Offset(0, -500));
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 800));

    expect(find.text('آخر المعاملات'), findsOneWidget);
    expect(find.text('عرض الكل'), findsOneWidget);

    await cleanupTimers(tester);
  });

  testWidgets('shows notification bell with unread count',
      (WidgetTester tester) async {
    await tester.pumpWidget(_buildDashboard(
      wallets: [testWallet],
      user: testUser,
      unreadCount: 3,
    ));
    await pumpPastAnimations(tester);

    expect(find.text('3'), findsOneWidget);

    await cleanupTimers(tester);
  });

  testWidgets('shows exchange button', (WidgetTester tester) async {
    await tester.pumpWidget(_buildDashboard(
      wallets: [testWallet],
      user: testUser,
    ));
    await pumpPastAnimations(tester);

    expect(find.text('صرف'), findsOneWidget);

    await cleanupTimers(tester);
  });
}
