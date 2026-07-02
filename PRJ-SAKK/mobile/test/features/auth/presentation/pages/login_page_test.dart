/// Widget tests for LoginPage — mocked auth repository, go_router.
library;

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:go_router/go_router.dart';
import 'package:mocktail/mocktail.dart';

import 'package:sakk_wallet/core/theme/app_colors.dart';
import 'package:sakk_wallet/core/theme/app_theme.dart';
import 'package:sakk_wallet/features/auth/data/models/user_model.dart';
import 'package:sakk_wallet/features/auth/data/repositories/auth_repository.dart';
import 'package:sakk_wallet/features/auth/providers/auth_provider.dart';
import 'package:sakk_wallet/features/auth/presentation/pages/login_page.dart';

import '../../../../helpers/mocks.dart';

class _MockLoginRepo extends Mock implements AuthRepository {}

/// Builds a test app with GoRouter + LoginPage + provider overrides.
Widget _buildApp({
  required AuthRepository authRepo,
  UserModel? currentUser,
}) {
  final router = GoRouter(
    initialLocation: '/login',
    routes: [
      GoRoute(path: '/login', builder: (_, __) => const LoginPage()),
      GoRoute(
        path: '/register',
        builder: (_, __) => const SizedBox(key: ValueKey('register')),
      ),
      GoRoute(
        path: '/forgot-password',
        builder: (_, __) => const SizedBox(key: ValueKey('forgot_password')),
      ),
      GoRoute(
        path: '/dashboard',
        builder: (_, __) => const SizedBox(key: ValueKey('dashboard')),
      ),
    ],
  );
  return ProviderScope(
    overrides: [
      authRepositoryProvider.overrideWithValue(authRepo),
      currentUserProvider.overrideWith((ref) => currentUser),
    ],
    child: MaterialApp.router(
      routerConfig: router,
      theme: AppTheme.lightTheme,
      locale: const Locale('ar'),
    ),
  );
}

/// Pump past loading spinner and flutter_animate entrance animations.
Future<void> pumpPastEntrance(WidgetTester tester) async {
  await tester.pump();
  await tester.pump(const Duration(milliseconds: 800));
}

void main() {
  late AuthRepository mockRepo;

  setUp(() {
    mockRepo = _MockLoginRepo();
    // Default: not authenticated, biometric disabled — shows login form.
    when(() => mockRepo.isAuthenticated()).thenAnswer((_) async => false);
    when(() => mockRepo.isBiometricEnabled()).thenAnswer((_) async => false);
    when(() => mockRepo.getRememberedEmail()).thenAnswer((_) async => null);
  });

  testWidgets('shows loading indicator initially, then login form',
      (WidgetTester tester) async {
    await tester.pumpWidget(_buildApp(authRepo: mockRepo));

    // Initially _locked is null → CircularProgressIndicator
    expect(find.byType(CircularProgressIndicator), findsOneWidget);

    // After async init + entrance animations resolve
    await pumpPastEntrance(tester);

    // Login form should now be visible
    expect(find.text('مرحباً بعودتك'), findsOneWidget);
    expect(find.byType(TextFormField), findsNWidgets(2)); // email + password

    // Clear animation timers
    await tester.pumpWidget(const SizedBox());
    await tester.pump(const Duration(milliseconds: 200));
  });

  testWidgets('renders email and password fields', (WidgetTester tester) async {
    await tester.pumpWidget(_buildApp(authRepo: mockRepo));
    await pumpPastEntrance(tester);

    expect(find.text('البريد الإلكتروني'), findsOneWidget);
    expect(find.text('كلمة المرور'), findsOneWidget);
    expect(find.text('تسجيل الدخول'), findsOneWidget);

    await tester.pumpWidget(const SizedBox());
    await tester.pump(const Duration(milliseconds: 200));
  });

  testWidgets('shows validation errors on empty submit',
      (WidgetTester tester) async {
    await tester.pumpWidget(_buildApp(authRepo: mockRepo));
    await pumpPastEntrance(tester);

    // Tap login button without filling anything
    await tester.tap(find.text('تسجيل الدخول'));
    await tester.pump(const Duration(milliseconds: 100));

    // Validation messages in Arabic
    expect(find.text('البريد الإلكتروني مطلوب'), findsOneWidget);
    expect(find.text('كلمة المرور مطلوبة'), findsOneWidget);

    await tester.pumpWidget(const SizedBox());
    await tester.pump(const Duration(milliseconds: 200));
  });

  testWidgets('shows forgot password link', (WidgetTester tester) async {
    await tester.pumpWidget(_buildApp(authRepo: mockRepo));
    await pumpPastEntrance(tester);

    expect(find.text('نسيت كلمة المرور؟'), findsOneWidget);

    await tester.pumpWidget(const SizedBox());
    await tester.pump(const Duration(milliseconds: 200));
  });

  testWidgets('shows register link', (WidgetTester tester) async {
    await tester.pumpWidget(_buildApp(authRepo: mockRepo));
    await pumpPastEntrance(tester);

    expect(find.text('ليس لديك حساب؟'), findsOneWidget);
    expect(find.text('سجل الآن'), findsOneWidget);

    await tester.pumpWidget(const SizedBox());
    await tester.pump(const Duration(milliseconds: 200));
  });

  testWidgets('shows remember me checkbox', (WidgetTester tester) async {
    await tester.pumpWidget(_buildApp(authRepo: mockRepo));
    await pumpPastEntrance(tester);

    expect(find.text('تذكرني'), findsOneWidget);
    expect(find.byType(Checkbox), findsOneWidget);

    await tester.pumpWidget(const SizedBox());
    await tester.pump(const Duration(milliseconds: 200));
  });

  testWidgets('login button shows loading state on submit',
      (WidgetTester tester) async {
    // Stub login to never complete during test
    when(() => mockRepo.login(
          email: any(named: 'email'),
          password: any(named: 'password'),
          rememberMe: any(named: 'rememberMe'),
          twoFactorCode: any(named: 'twoFactorCode'),
        )).thenAnswer((_) async {
      // Never completes — keeps loading
      await Future.delayed(const Duration(seconds: 30));
      return testUser;
    });

    await tester.pumpWidget(_buildApp(authRepo: mockRepo));
    await pumpPastEntrance(tester);

    // Fill fields
    await tester.enterText(find.byType(TextFormField).first, 'ahmad@example.com');
    await tester.enterText(find.byType(TextFormField).last, 'Pass123!');
    await tester.pump();

    // Tap login
    await tester.tap(find.text('تسجيل الدخول'));
    await tester.pump();

    // Loading indicator should appear
    expect(find.byType(CircularProgressIndicator), findsWidgets);

    await tester.pumpWidget(const SizedBox());
    await tester.pump(const Duration(seconds: 31));
  });

  testWidgets('navigates to register page on "سجل الآن" tap',
      (WidgetTester tester) async {
    await tester.pumpWidget(_buildApp(authRepo: mockRepo));
    await pumpPastEntrance(tester);

    // Tap register link — registered at /register in GoRouter.
    // The button uses context.push('/register') via GoRouter.of(context).
    await tester.ensureVisible(find.text('سجل الآن'));
    await tester.pump();
    await tester.tap(find.text('سجل الآن'));
    await tester.pump(const Duration(milliseconds: 100));
    await tester.pump(const Duration(milliseconds: 100));

    expect(find.byKey(const ValueKey('register')), findsOneWidget);

    await tester.pumpWidget(const SizedBox());
    await tester.pump(const Duration(milliseconds: 200));
  });

  testWidgets('navigates to forgot password on link tap',
      (WidgetTester tester) async {
    await tester.pumpWidget(_buildApp(authRepo: mockRepo));
    await pumpPastEntrance(tester);

    await tester.ensureVisible(find.text('نسيت كلمة المرور؟'));
    await tester.pump();
    await tester.tap(find.text('نسيت كلمة المرور؟'));
    await tester.pump(const Duration(milliseconds: 100));
    await tester.pump(const Duration(milliseconds: 100));

    expect(find.byKey(const ValueKey('forgot_password')), findsOneWidget);

    await tester.pumpWidget(const SizedBox());
    await tester.pump(const Duration(milliseconds: 200));
  });
}
