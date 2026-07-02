import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:hive_flutter/hive_flutter.dart';
import 'package:app_links/app_links.dart';

import 'core/services/deep_link_parser.dart';
import 'core/services/fcm_service.dart';
import 'core/theme/app_theme.dart';
import 'core/router/app_router.dart';
import 'features/auth/data/repositories/auth_repository.dart';
import 'features/notifications/data/repositories/notification_repository.dart';
import 'features/transfer/data/nfc_hce.dart';
import 'features/transfer/data/nfc_reader.dart';
import 'features/app_update/presentation/widgets/update_gate.dart';
import 'features/partner/data/repositories/partner_repository.dart';
import 'features/company/data/repositories/company_repository.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();

  await Hive.initFlutter();
  await SystemChrome.setPreferredOrientations([
    DeviceOrientation.portraitUp,
    DeviceOrientation.portraitDown,
  ]);

  // Initialize FCM (safe: no crash if Firebase config is missing).
  await FCMService.instance.init();

  runApp(const ProviderScope(child: SAKKWalletApp()));
}

class SAKKWalletApp extends ConsumerStatefulWidget {
  const SAKKWalletApp({super.key});

  @override
  ConsumerState<SAKKWalletApp> createState() => _SAKKWalletAppState();
}

class _SAKKWalletAppState extends ConsumerState<SAKKWalletApp>
    with WidgetsBindingObserver {
  bool _isReady = false;

  // Deep links: invite (sakk://invite/{code} or https .../invite/{code}) and
  // pay links. A cold-start link is stashed until the app is ready, then routed.
  final AppLinks _appLinks = AppLinks();
  StreamSubscription<Uri>? _linkSub;
  String? _pendingDeepLinkRoute;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    // Push the FCM token to the backend whenever it rotates mid-session,
    // so server-side pushes never target a stale token.
    FCMService.instance.tokenNotifier.addListener(_syncFcmToken);
    _initDeepLinks();
    _initApp();
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    _linkSub?.cancel();
    FCMService.instance.tokenNotifier.removeListener(_syncFcmToken);
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      // Force-refresh join-application state every time the app comes to the
      // foreground so approved / linked accounts show immediately without
      // manual pull-to-refresh or restart.
      ref.invalidate(partnerStateProvider);
      ref.invalidate(companyStateProvider);
    }
  }

  Future<void> _initApp() async {
    try {
      final authRepo = ref.read(authRepositoryProvider);
      final isAuthenticated = await authRepo.isAuthenticated();

      if (isAuthenticated) {
        final user = await authRepo.getCurrentUser();
        ref.read(currentUserProvider.notifier).state = user;
        // Refresh the server's copy of the token on every authenticated launch.
        _syncFcmToken();
      }
    } catch (_) {
      // Auth check failed, will redirect to login
    }

    if (mounted) {
      setState(() => _isReady = true);
      // Flush any link that arrived before the UI was ready (cold start).
      final pending = _pendingDeepLinkRoute;
      _pendingDeepLinkRoute = null;
      if (pending != null) {
        WidgetsBinding.instance.addPostFrameCallback(
            (_) => ref.read(appRouterProvider).go(pending));
      }
    }
  }

  /// Listen for invite/pay deep links — both at cold start and while running.
  Future<void> _initDeepLinks() async {
    _linkSub = _appLinks.uriLinkStream.listen(_handleDeepLink, onError: (_) {});
    try {
      final initial = await _appLinks.getInitialLink();
      if (initial != null) _handleDeepLink(initial);
    } catch (_) {
      // No initial link / platform unsupported — ignore.
    }
  }

  void _handleDeepLink(Uri uri) {
    final route = routeForDeepLink(uri);
    if (route == null) return;
    if (_isReady) {
      ref.read(appRouterProvider).go(route);
    } else {
      _pendingDeepLinkRoute = route; // navigate once _initApp finishes
    }
  }

  /// Send the current FCM token to the backend — but only while authenticated.
  /// Fire-and-forget; the repository swallows transient failures.
  void _syncFcmToken() {
    final token = FCMService.instance.token;
    if (token == null || token.isEmpty) return;
    if (ref.read(currentUserProvider) == null) return;
    ref.read(notificationRepositoryProvider).updateFcmToken(token);
  }

  @override
  Widget build(BuildContext context) {
    if (!_isReady) {
      return MaterialApp(
        debugShowCheckedModeBanner: false,
        theme: AppTheme.lightTheme,
        locale: const Locale('ar'),
        home: const Scaffold(
          body: Center(
            child: CircularProgressIndicator(),
          ),
        ),
      );
    }

    final router = ref.watch(appRouterProvider);

    // Light-only identity (Damascene Burgundy) — no dark mode.
    SystemChrome.setSystemUIOverlayStyle(const SystemUiOverlayStyle(
      statusBarColor: Colors.transparent,
      statusBarIconBrightness: Brightness.dark,
      systemNavigationBarColor: Color(0xFFF7F3EE),
      systemNavigationBarIconBrightness: Brightness.dark,
    ));

    return MaterialApp.router(
      title: 'صكك | SAKK Wallet',
      debugShowCheckedModeBanner: false,

      theme: AppTheme.lightTheme,
      themeMode: ThemeMode.light,

      locale: const Locale('ar'),
      supportedLocales: const [
        Locale('ar'),
        Locale('en'),
      ],
      localizationsDelegates: const [
        GlobalMaterialLocalizations.delegate,
        GlobalWidgetsLocalizations.delegate,
        GlobalCupertinoLocalizations.delegate,
      ],

      routerConfig: router,

      builder: (context, child) => UpdateGate(
        child: _NfcLaunchGate(child: child ?? const SizedBox.shrink()),
      ),
    );
  }
}

/// Bridges NFC payment taps into navigation. A cold-start tap is stashed and
/// shown by [MainShell] once the user is past the lock; a tap while running is
/// shown immediately.
class _NfcLaunchGate extends ConsumerStatefulWidget {
  final Widget child;
  const _NfcLaunchGate({required this.child});

  @override
  ConsumerState<_NfcLaunchGate> createState() => _NfcLaunchGateState();
}

class _NfcLaunchGateState extends ConsumerState<_NfcLaunchGate> {
  @override
  void initState() {
    super.initState();
    NfcLaunch.setHandler(_onWarmUri);
    WidgetsBinding.instance.addPostFrameCallback((_) => _consumeInitial());
  }

  Future<void> _consumeInitial() async {
    final payment = NfcPayment.fromUri(await NfcLaunch.consumeInitialUri());
    if (payment != null) {
      ref.read(pendingNfcPaymentProvider.notifier).state = payment;
    }
  }

  void _onWarmUri(String uri) {
    final payment = NfcPayment.fromUri(uri);
    if (payment == null) return;
    ref.read(appRouterProvider).push('/nfc-pay', extra: payment);
  }

  @override
  Widget build(BuildContext context) => widget.child;
}
