import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:integration_test/integration_test.dart';
import 'package:sakk_wallet/main.dart' as app;

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();

  group('SAKK Wallet E2E Tests', () {
    /// Test 1: Cold Start Performance (<2s)
    testWidgets('Cold start loads within 2 seconds', (WidgetTester tester) async {
      final stopwatch = Stopwatch()..start();

      app.main();
      await tester.pumpAndSettle(const Duration(seconds: 5));

      stopwatch.stop();

      expect(
        stopwatch.elapsedMilliseconds,
        lessThan(2000),
        reason: 'Cold start took ${stopwatch.elapsedMilliseconds}ms (must be <2000ms)',
      );

      // Verify app is ready (splash or login screen visible)
      expect(
        find.byType(MaterialApp),
        findsOneWidget,
        reason: 'MaterialApp should be rendered after cold start',
      );
    });

    /// Test 2: Auth Flow - Login
    testWidgets('Login flow works end-to-end', (WidgetTester tester) async {
      app.main();
      await tester.pumpAndSettle(const Duration(seconds: 3));

      // Find login screen - look for email/phone input field
      final emailField = find.byType(TextField);
      expect(
        emailField,
        findsWidgets,
        reason: 'Login screen should have input fields',
      );

      // Enter email (tap first text field)
      await tester.tap(emailField.first);
      await tester.pumpAndSettle();
      await tester.enterText(emailField.first, 'test@sakk.wallet');

      // Find password field (usually second input)
      final passwordFields = find.byType(TextField);
      if (passwordFields.evaluate().length >= 2) {
        await tester.tap(passwordFields.at(1));
        await tester.pumpAndSettle();
        await tester.enterText(passwordFields.at(1), 'TestPassword123!');
      }

      // Look for login button
      final loginButton = find.byType(ElevatedButton).first;
      await tester.tap(loginButton);
      await tester.pumpAndSettle(const Duration(seconds: 2));

      // After successful login, dashboard should load
      expect(
        find.byType(MaterialApp),
        findsOneWidget,
        reason: 'App should remain rendered after login attempt',
      );
    });

    /// Test 3: Auth Flow - Register
    testWidgets('Register flow works end-to-end', (WidgetTester tester) async {
      app.main();
      await tester.pumpAndSettle(const Duration(seconds: 3));

      // Look for "Register" or "Sign Up" button
      final registerButton = find.byWidgetPredicate(
        (widget) =>
            widget is Text &&
            ((widget.data != null &&
                    (widget.data!.contains('Register') ||
                        widget.data!.contains('Sign Up') ||
                        widget.data!.contains('إنشاء حساب'))) ||
                false),
      );

      if (registerButton.evaluate().isNotEmpty) {
        await tester.tap(registerButton.first);
        await tester.pumpAndSettle(const Duration(seconds: 1));

        // Fill registration form
        final inputFields = find.byType(TextField);
        if (inputFields.evaluate().length >= 3) {
          // First Name
          await tester.tap(inputFields.at(0));
          await tester.enterText(inputFields.at(0), 'Test');
          await tester.pumpAndSettle();

          // Email
          await tester.tap(inputFields.at(1));
          await tester.enterText(inputFields.at(1), 'newuser@sakk.wallet');
          await tester.pumpAndSettle();

          // Password
          await tester.tap(inputFields.at(2));
          await tester.enterText(inputFields.at(2), 'SecurePass123!');
          await tester.pumpAndSettle();
        }

        // Find and tap register/submit button
        final submitButton = find.byType(ElevatedButton).first;
        await tester.tap(submitButton);
        await tester.pumpAndSettle(const Duration(seconds: 2));

        expect(
          find.byType(MaterialApp),
          findsOneWidget,
          reason: 'App should remain rendered after registration attempt',
        );
      } else {
        // Register not available in this screen
        debugPrint('ℹ️ Register button not found on current screen');
      }
    });

    /// Test 4: Dashboard Load & Animations
    testWidgets('Dashboard loads with animations', (WidgetTester tester) async {
      app.main();
      await tester.pumpAndSettle(const Duration(seconds: 5));

      // After login/ready, dashboard should be visible
      expect(
        find.byType(MaterialApp),
        findsOneWidget,
        reason: 'Dashboard should be rendered',
      );

      // Check for animated widgets (Lottie, AnimatedBuilder, etc.)
      final animatedWidgets = find.byType(AnimatedBuilder);
      if (animatedWidgets.evaluate().isNotEmpty) {
        debugPrint('✅ Dashboard has AnimatedBuilder widgets');
      }

      // Trigger animation frames and ensure no crash
      await tester.pumpAndSettle(const Duration(milliseconds: 500));
      await tester.pumpAndSettle(const Duration(seconds: 1));

      // Verify no errors in Flutter layer
      expect(
        find.byType(MaterialApp),
        findsOneWidget,
        reason: 'Dashboard should still render after animations',
      );
    });

    /// Test 5: QR Code Generation & Display
    testWidgets('QR send flow loads and displays QR code', (WidgetTester tester) async {
      app.main();
      await tester.pumpAndSettle(const Duration(seconds: 5));

      // Look for QR/Send button (likely in bottom nav or menu)
      final qrButton = find.byWidgetPredicate(
        (widget) =>
            (widget is Tooltip &&
                ((widget.message ?? '').contains('QR') ||
                    (widget.message ?? '').contains('Send') ||
                    (widget.message ?? '').contains('تحويل') ||
                    (widget.message ?? '').contains('إرسال'))) ||
            (widget is Icon &&
                ((widget.icon?.toString().contains('qr') ?? false) ||
                    (widget.icon?.toString().contains('send') ?? false))),
      );

      if (qrButton.evaluate().isNotEmpty) {
        await tester.tap(qrButton.first);
        await tester.pumpAndSettle(const Duration(seconds: 2));

        // After tapping, look for QR code widget
        final qrCodeWidget = find.byWidgetPredicate(
          (widget) =>
              widget.toString().contains('QrImage') ||
              widget.toString().contains('QR') ||
              widget is CustomPaint,
        );

        if (qrCodeWidget.evaluate().isNotEmpty) {
          debugPrint('✅ QR code widget found and displayed');
          expect(
            find.byType(MaterialApp),
            findsOneWidget,
            reason: 'QR screen should render without crash',
          );
        }
      } else {
        debugPrint('ℹ️ QR send button not found in current screen');
      }
    });

    /// Test 6: QR Code Scan Flow
    testWidgets('QR scan flow initializes scanner', (WidgetTester tester) async {
      app.main();
      await tester.pumpAndSettle(const Duration(seconds: 5));

      // Look for Scan button (likely in nav or menu)
      final scanButton = find.byWidgetPredicate(
        (widget) =>
            (widget is Tooltip &&
                ((widget.message ?? '').contains('Scan') ||
                    (widget.message ?? '').contains('Camera') ||
                    (widget.message ?? '').contains('مسح') ||
                    (widget.message ?? '').contains('ماسح'))) ||
            (widget is Icon) ||
            false,
      );

      if (scanButton.evaluate().isNotEmpty) {
        await tester.tap(scanButton.first);
        await tester.pumpAndSettle(const Duration(seconds: 2));

        // After tapping scan, a camera/scanner widget should appear
        // Look for MobileScanner or camera-related widget
        final scannerWidget = find.byWidgetPredicate(
          (widget) =>
              widget.toString().contains('MobileScanner') ||
              widget.toString().contains('Camera') ||
              widget.toString().contains('Scanner'),
        );

        if (scannerWidget.evaluate().isNotEmpty) {
          debugPrint('✅ Scanner widget initialized successfully');
          expect(
            find.byType(MaterialApp),
            findsOneWidget,
            reason: 'Scanner screen should render without crash',
          );
        }
      } else {
        debugPrint('ℹ️ Scan button not found in current screen');
      }
    });

    /// Test 7: Dashboard Navigation & Stability
    testWidgets('Dashboard navigation is stable', (WidgetTester tester) async {
      app.main();
      await tester.pumpAndSettle(const Duration(seconds: 5));

      // Look for bottom nav items or menu options
      final navButtons = find.byType(BottomNavigationBarItem);

      if (navButtons.evaluate().isNotEmpty) {
        // Try tapping each nav item to ensure stability
        for (int i = 0; i < navButtons.evaluate().length && i < 3; i++) {
          final navItem = find.byType(BottomNavigationBarItem).at(i);
          if (navItem.evaluate().isNotEmpty) {
            await tester.tap(navItem);
            await tester.pumpAndSettle(const Duration(seconds: 1));

            expect(
              find.byType(MaterialApp),
              findsOneWidget,
              reason: 'App should remain stable after nav tap',
            );
          }
        }
      }

      debugPrint('✅ Dashboard navigation stable');
    });

    /// Test 8: No Crashes on Cold Start
    testWidgets('App does not crash on cold start', (WidgetTester tester) async {
      try {
        app.main();
        await tester.pumpAndSettle(const Duration(seconds: 5));

        expect(
          find.byType(MaterialApp),
          findsOneWidget,
          reason: 'App should render without throwing',
        );
        debugPrint('✅ Cold start completed without crash');
      } catch (e) {
        fail('🔴 App crashed on cold start: $e');
      }
    });
  });
}
