/// Widget tests for CardsPage — mocked card provider.
library;

import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:sakk_wallet/core/widgets/app_skeleton.dart';
import 'package:sakk_wallet/core/theme/app_theme.dart';
import 'package:sakk_wallet/features/cards/data/models/card_model.dart';
import 'package:sakk_wallet/features/cards/data/repositories/card_repository.dart';
import 'package:sakk_wallet/features/cards/presentation/pages/cards_page.dart';

import '../../../../helpers/mocks.dart';

/// Helper to build CardsPage with overridden providers.
Widget _buildCardsPage(List<CardModel>? cards) {
  return ProviderScope(
    overrides: [
      cardsEnabledProvider.overrideWith((ref) => Future.value(true)),
      cardsProvider.overrideWith((ref) => Future.value(cards ?? [])),
      featuredCardIdProvider.overrideWith((ref) => FeaturedCardNotifier()),
    ],
    child: MaterialApp(
      theme: AppTheme.lightTheme,
      locale: const Locale('ar'),
      home: const CardsPage(),
    ),
  );
}

/// Pump enough to get past flutter_animate entrance delays.
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
  testWidgets('renders card list with total balance', (WidgetTester tester) async {
    await tester.pumpWidget(_buildCardsPage([testCard]));
    await pumpPastAnimations(tester);

    // Scroll down if needed to reveal the header
    expect(find.text('\$500.00'), findsWidgets);

    await cleanupTimers(tester);
  });

  testWidgets('shows empty state when no cards', (WidgetTester tester) async {
    await tester.pumpWidget(_buildCardsPage([]));
    await pumpPastAnimations(tester);

    expect(find.text('لا توجد بطاقات'), findsOneWidget);
    expect(find.text('أنشئ بطاقة افتراضية للتسوق الآمن'), findsOneWidget);
    expect(find.text('إنشاء بطاقة'), findsOneWidget);

    await cleanupTimers(tester);
  });

  testWidgets('shows skeleton loading initially', (WidgetTester tester) async {
    // Never-completing future keeps the loading state active.
    final neverEnding = Completer<List<CardModel>>().future;
    await tester.pumpWidget(ProviderScope(
      overrides: [
        cardsEnabledProvider.overrideWith((ref) => Future.value(true)),
        cardsProvider.overrideWith((ref) => neverEnding),
        featuredCardIdProvider.overrideWith((ref) => FeaturedCardNotifier()),
      ],
      child: MaterialApp(
        theme: AppTheme.lightTheme,
        locale: const Locale('ar'),
        home: const CardsPage(),
      ),
    ));
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 600));

    expect(find.byType(SkeletonCard, skipOffstage: false), findsWidgets);

    await cleanupTimers(tester);
  });

  testWidgets('renders featured card', (WidgetTester tester) async {
    await tester.pumpWidget(_buildCardsPage([testCard]));
    await pumpPastAnimations(tester);

    expect(find.text('البطاقة المميزة'), findsOneWidget);
    expect(find.text('جميع البطاقات'), findsOneWidget);

    await cleanupTimers(tester);
  });

  testWidgets('renders add card button', (WidgetTester tester) async {
    await tester.pumpWidget(_buildCardsPage([testCard]));
    await pumpPastAnimations(tester);

    expect(find.text('إضافة بطاقة جديدة'), findsOneWidget);

    await cleanupTimers(tester);
  });

  testWidgets('shows cancelled cards separately', (WidgetTester tester) async {
    final cancelledCard = CardModel(
      id: 2,
      brand: 'mastercard',
      type: 'virtual',
      lastFour: '5678',
      expiryDate: '06/27',
      balance: 0.0,
      spendingLimit: 500.0,
      dailyLimit: 500.0,
      monthlyLimit: 5000.0,
      status: 'cancelled',
      label: 'ملغية',
      createdAt: DateTime(2026, 1, 1),
    );

    await tester.pumpWidget(_buildCardsPage([testCard, cancelledCard]));
    await pumpPastAnimations(tester);

    expect(find.text('البطاقات الملغية'), findsOneWidget);

    await cleanupTimers(tester);
  });

  testWidgets('shows frozen card status', (WidgetTester tester) async {
    final frozenCard = CardModel(
      id: 3,
      brand: 'visa',
      type: 'virtual',
      lastFour: '9999',
      expiryDate: '12/28',
      balance: 200.0,
      spendingLimit: 500.0,
      dailyLimit: 500.0,
      monthlyLimit: 5000.0,
      status: 'frozen',
      label: 'مجمدة',
      createdAt: DateTime(2026, 1, 1),
    );

    await tester.pumpWidget(_buildCardsPage([testCard, frozenCard]));
    await pumpPastAnimations(tester);

    expect(find.text('مجمدة'), findsWidgets);

    await cleanupTimers(tester);
  });

  testWidgets('shows error state', (WidgetTester tester) async {
    await tester.pumpWidget(ProviderScope(
      overrides: [
        cardsEnabledProvider.overrideWith((ref) => Future.value(true)),
        cardsProvider.overrideWith((ref) => Future.error('Server error')),
        featuredCardIdProvider.overrideWith((ref) => FeaturedCardNotifier()),
      ],
      child: MaterialApp(
        theme: AppTheme.lightTheme,
        locale: const Locale('ar'),
        home: const CardsPage(),
      ),
    ));
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 500));

    expect(find.text('تعذّر تحميل البطاقات'), findsOneWidget);
    expect(find.text('إعادة المحاولة'), findsOneWidget);

    await cleanupTimers(tester);
  });
}
