import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../services/deep_link_parser.dart';

import '../../features/auth/presentation/pages/login_page.dart';
import '../../features/auth/presentation/pages/register_page.dart';
import '../../features/auth/presentation/pages/forgot_password_page.dart';
import '../../features/dashboard/presentation/pages/dashboard_page.dart';
import '../../features/wallets/presentation/pages/wallets_page.dart';
import '../../features/wallets/presentation/pages/wallet_details_page.dart';
import '../../features/wallets/presentation/pages/withdraw_page.dart';
import '../../features/wallets/presentation/pages/crypto_deposit_page.dart';
import '../../features/wallets/presentation/pages/crypto_withdraw_page.dart';
import '../../features/cards/presentation/pages/cards_page.dart';
import '../../features/cards/presentation/pages/card_details_page.dart';
import '../../features/cards/presentation/pages/create_card_page.dart';
import '../../features/cards/presentation/pages/fund_card_page.dart';
import '../../features/cards/presentation/pages/card_transactions_page.dart';
import '../../features/transactions/presentation/pages/transactions_page.dart';
import '../../features/settings/presentation/pages/settings_page.dart';
import '../../features/settings/presentation/pages/profile_edit_page.dart';
import '../../features/settings/presentation/pages/security_page.dart';
import '../../features/settings/presentation/pages/connected_devices_page.dart';
import '../../features/kyc/presentation/pages/kyc_page.dart';
import '../../features/qr/presentation/pages/qr_scanner_page.dart';
import '../../features/qr/presentation/pages/qr_receive_page.dart';
import '../../features/qr/presentation/pages/nfc_receive_page.dart';
import '../../features/qr/presentation/pages/qr_send_page.dart';
import '../../features/transfer/presentation/pages/request_money_page.dart';
import '../../features/transfer/presentation/pages/pay_request_page.dart';
import '../../features/transfer/presentation/pages/contacts_transfer_page.dart';
import '../../features/transfer/presentation/pages/my_payment_requests_page.dart';
import '../../features/transfer/presentation/pages/received_requests_page.dart';
import '../../features/cashback/presentation/pages/cashback_page.dart';
import '../../features/notifications/presentation/pages/notifications_page.dart';
import '../../features/transfer/presentation/pages/referral_page.dart';
import '../../features/gold/presentation/pages/gold_page.dart';
import '../../features/savings/presentation/pages/savings_page.dart';
import '../../features/partner/presentation/pages/join_partner_page.dart';
import '../../features/company/presentation/pages/join_company_page.dart';
import '../../features/agents/presentation/pages/agents_page.dart';
import '../../features/agents/presentation/pages/agent_details_page.dart';
import '../../features/agents/data/models/agent_model.dart';
import '../../features/bills/presentation/pages/bills_page.dart';
import '../../features/settings/presentation/pages/info_pages.dart';
import '../../features/settings/presentation/pages/support_page.dart';
import '../../features/chat/presentation/pages/chat_page.dart';
import '../../features/support/presentation/pages/tickets_page.dart';
import '../../features/support/presentation/pages/new_ticket_page.dart';
import '../../features/support/presentation/pages/ticket_thread_page.dart';
import '../../shared/widgets/main_shell.dart';
import '../../features/auth/data/repositories/auth_repository.dart';
import '../../features/transfer/data/nfc_reader.dart';
import '../../features/splash/presentation/pages/splash_page.dart';
import '../../features/onboarding/presentation/pages/onboarding_page.dart';
import '../../features/welcome/presentation/pages/welcome_page.dart';
import '../../features/pin/presentation/pages/unlock_page.dart';
import '../../features/pin/presentation/pages/pin_setup_page.dart';

final appRouterProvider = Provider<GoRouter>((ref) {
  final authRepo = ref.watch(authRepositoryProvider);

  return GoRouter(
    initialLocation: '/splash',
    debugLogDiagnostics: true,
    redirect: (context, state) async {
      // Cold-start deep links (sakk://invite/{code} · sakk://pay/{uuid} and their
      // https App-Link twins) land here as the platform's *initial route* before
      // the app_links listener can re-route them. GoRouter matches on the path
      // only — the custom-scheme host (invite/pay) is stripped — so the bare code
      // matches no route and throws "no routes for location". state.uri still
      // carries the full scheme+host, so translate any recognised deep link to
      // its real in-app destination here (one source of truth with main.dart).
      final deepLinkRoute = routeForDeepLink(state.uri);
      if (deepLinkRoute != null) {
        return deepLinkRoute;
      }

      // Defensive fallback for payment links: a bare UUID path (host fully
      // stripped) still opens the pay page even if the scheme is lost.
      final uuidMatch = RegExp(
              r'^/([0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12})$')
          .firstMatch(state.matchedLocation);
      if (uuidMatch != null) {
        return '/pay/${uuidMatch.group(1)}';
      }

      final isAuthenticated = await authRepo.isAuthenticated();
      const publicPrefixes = [
        '/splash',
        '/onboarding',
        '/welcome',
        '/login',
        '/register',
        '/forgot-password',
        '/unlock',
        '/pin-setup',
        '/terms',
        '/privacy',
        '/about',
      ];
      final isPublic =
          publicPrefixes.any((p) => state.matchedLocation.startsWith(p));

      // Unauthenticated users may only reach public routes; everything else
      // sends them to the welcome gateway. The splash screen owns the
      // first-run (onboarding) and unlock (PIN/biometric) decision tree.
      if (!isAuthenticated && !isPublic) {
        return '/welcome';
      }

      return null;
    },
    routes: [
      // First-run flow + auth gateway + unlock (outside the bottom-nav shell)
      GoRoute(
        path: '/splash',
        name: 'splash',
        builder: (context, state) => const SplashPage(),
      ),
      GoRoute(
        path: '/onboarding',
        name: 'onboarding',
        builder: (context, state) => const OnboardingPage(),
      ),
      GoRoute(
        path: '/welcome',
        name: 'welcome',
        builder: (context, state) => const WelcomePage(),
      ),
      GoRoute(
        path: '/unlock',
        name: 'unlock',
        builder: (context, state) => const UnlockPage(),
      ),
      GoRoute(
        path: '/pin-setup',
        name: 'pin-setup',
        builder: (context, state) => const PinSetupPage(),
      ),

      // Auth Routes
      GoRoute(
        path: '/login',
        name: 'login',
        builder: (context, state) => const LoginPage(),
      ),
      GoRoute(
        path: '/register',
        name: 'register',
        // ?ref=CODE arrives from an invite deep link → prefill the referral field.
        builder: (context, state) => RegisterPage(
          referralCode: state.uri.queryParameters['ref'],
        ),
      ),
      GoRoute(
        path: '/forgot-password',
        name: 'forgot-password',
        builder: (context, state) => const ForgotPasswordPage(),
      ),
      
      // Main Shell with Bottom Navigation
      ShellRoute(
        builder: (context, state, child) => MainShell(child: child),
        routes: [
          // Dashboard
          GoRoute(
            path: '/dashboard',
            name: 'dashboard',
            pageBuilder: (context, state) => const NoTransitionPage(
              child: DashboardPage(),
            ),
          ),
          
          // Wallets
          GoRoute(
            path: '/wallets',
            name: 'wallets',
            pageBuilder: (context, state) => const NoTransitionPage(
              child: WalletsPage(),
            ),
            routes: [
              GoRoute(
                path: ':id',
                name: 'wallet-details',
                builder: (context, state) => WalletDetailsPage(
                  walletId: int.parse(state.pathParameters['id']!),
                ),
              ),
              GoRoute(
                path: 'withdraw/:walletId',
                name: 'wallet-withdraw',
                builder: (context, state) => WithdrawPage(
                  walletId: int.parse(state.pathParameters['walletId']!),
                ),
              ),
            ],
          ),

          // Crypto Deposit/Withdraw
          GoRoute(
            path: '/crypto-deposit',
            name: 'crypto-deposit',
            builder: (context, state) => const CryptoDepositPage(),
          ),
          GoRoute(
            path: '/crypto-withdraw',
            name: 'crypto-withdraw',
            builder: (context, state) => const CryptoWithdrawPage(),
          ),
          
          // Cards
          GoRoute(
            path: '/cards',
            name: 'cards',
            pageBuilder: (context, state) => const NoTransitionPage(
              child: CardsPage(),
            ),
            routes: [
              GoRoute(
                path: 'create',
                name: 'create-card',
                builder: (context, state) => const CreateCardPage(),
              ),
              GoRoute(
                path: ':id',
                name: 'card-details',
                builder: (context, state) => CardDetailsPage(
                  cardId: int.parse(state.pathParameters['id']!),
                ),
              ),
              GoRoute(
                path: ':id/transactions',
                name: 'card-transactions',
                builder: (context, state) => CardTransactionsPage(
                  cardId: int.parse(state.pathParameters['id']!),
                ),
              ),
              GoRoute(
                path: ':id/fund',
                name: 'card-fund',
                builder: (context, state) => FundCardPage(
                  cardId: int.parse(state.pathParameters['id']!),
                ),
              ),
            ],
          ),
          
          // Transactions
          GoRoute(
            path: '/transactions',
            name: 'transactions',
            pageBuilder: (context, state) => const NoTransitionPage(
              child: TransactionsPage(),
            ),
          ),
          
          // Settings
          GoRoute(
            path: '/settings',
            name: 'settings',
            pageBuilder: (context, state) => const NoTransitionPage(
              child: SettingsPage(),
            ),
            routes: [
              GoRoute(
                path: 'profile',
                name: 'settings-profile',
                builder: (context, state) => const ProfileEditPage(),
              ),
              GoRoute(
                path: 'security',
                name: 'settings-security',
                builder: (context, state) => const SecurityPage(),
                routes: [
                  GoRoute(
                    path: 'devices',
                    name: 'connected-devices',
                    builder: (context, state) => const ConnectedDevicesPage(),
                  ),
                ],
              ),
            ],
          ),
        ],
      ),
      
      // KYC Verification
      GoRoute(
        path: '/kyc',
        name: 'kyc',
        builder: (context, state) => const KycPage(),
      ),

      // Bills
      GoRoute(
        path: '/bills',
        name: 'bills',
        builder: (context, state) => const BillsPage(),
      ),

      // Info & Support
      GoRoute(
        path: '/about',
        name: 'about',
        builder: (context, state) => const AboutPage(),
      ),
      GoRoute(
        path: '/terms',
        name: 'terms',
        builder: (context, state) => const TermsPage(),
      ),
      GoRoute(
        path: '/privacy',
        name: 'privacy',
        builder: (context, state) => const PrivacyPage(),
      ),
      GoRoute(
        path: '/usage',
        name: 'usage',
        builder: (context, state) => const UsagePage(),
      ),
      GoRoute(
        path: '/disclosures',
        name: 'disclosures',
        builder: (context, state) => const DisclosuresPage(),
      ),
      GoRoute(
        path: '/support',
        name: 'support',
        builder: (context, state) => const SupportPage(),
      ),

      // Live chat with support (in-app, polling transport)
      GoRoute(
        path: '/chat',
        name: 'chat',
        builder: (context, state) => const ChatPage(),
      ),

      // Support tickets (desk): list → new / thread
      GoRoute(
        path: '/support-tickets',
        name: 'support-tickets',
        builder: (context, state) => const TicketsPage(),
        routes: [
          GoRoute(
            path: 'new',
            name: 'support-ticket-new',
            builder: (context, state) => const NewTicketPage(),
          ),
          GoRoute(
            path: ':uuid',
            name: 'support-ticket-thread',
            builder: (context, state) =>
                TicketThreadPage(uuid: state.pathParameters['uuid']!),
          ),
        ],
      ),

      // Scan / QR (returns the scanned raw string on pop)
      GoRoute(
        path: '/scan',
        name: 'scan',
        builder: (context, state) => const QRScannerPage(),
      ),
      
      // QR Receive / Send
      GoRoute(
        path: '/qr-receive',
        name: 'qr-receive',
        builder: (context, state) => const QRReceivePage(),
      ),
      GoRoute(
        path: '/nfc-receive',
        name: 'nfc-receive',
        builder: (context, state) => const NfcReceivePage(),
      ),
      GoRoute(
        path: '/qr-send',
        name: 'qr-send',
        builder: (context, state) => QRSendPage(initialIdentifier: state.extra as String?),
      ),
      // NFC tap target: pay a recipient who was broadcasting via NFC.
      GoRoute(
        path: '/nfc-pay',
        name: 'nfc-pay',
        builder: (context, state) {
          final payment = state.extra is NfcPayment
              ? state.extra as NfcPayment
              : ref.read(pendingNfcPaymentProvider);
          return QRSendPage(nfcPayment: payment);
        },
      ),

      // Payment requests (request money / pay a request)
      GoRoute(
        path: '/request-money',
        name: 'request-money',
        builder: (context, state) => const RequestMoneyPage(),
      ),
      GoRoute(
        path: '/pay-request/:uuid',
        name: 'pay-request',
        builder: (context, state) => PayRequestPage(uuid: state.pathParameters['uuid']!),
      ),
      // Deep link target: https://sakk.app/pay/{uuid} and sakk://pay/{uuid}
      GoRoute(
        path: '/pay/:uuid',
        name: 'pay-link',
        builder: (context, state) => PayRequestPage(uuid: state.pathParameters['uuid']!),
      ),
      GoRoute(
        path: '/contacts-transfer',
        name: 'contacts-transfer',
        builder: (context, state) => const ContactsTransferPage(),
      ),
      GoRoute(
        path: '/received-requests',
        name: 'received-requests',
        builder: (context, state) => const ReceivedRequestsPage(),
      ),
      GoRoute(
        path: '/cashback',
        name: 'cashback',
        builder: (context, state) => const CashbackPage(),
      ),

      // Notifications
      GoRoute(
        path: '/notifications',
        name: 'notifications',
        builder: (context, state) => const NotificationsPage(),
      ),

      // Gold savings (buy/sell grams)
      GoRoute(
        path: '/gold',
        name: 'gold',
        builder: (context, state) => const GoldPage(),
      ),

      // Cash savings goals
      GoRoute(
        path: '/savings',
        name: 'savings',
        builder: (context, state) => const SavingsPage(),
      ),

      // Join as agent / merchant
      GoRoute(
        path: '/join-partner',
        name: 'join-partner',
        builder: (context, state) => const JoinPartnerPage(),
      ),
      GoRoute(
        path: '/join-company',
        name: 'join-company',
        builder: (context, state) => const JoinCompanyPage(),
      ),
      GoRoute(
        path: '/my-requests',
        name: 'my-requests',
        builder: (context, state) => const MyPaymentRequestsPage(),
      ),
      GoRoute(
        path: '/referral',
        name: 'referral',
        builder: (context, state) => const ReferralPage(),
      ),

      // Nearby cash agents (map + finder)
      GoRoute(
        path: '/agents',
        name: 'agents',
        builder: (context, state) => AgentsPage(
          initialService: state.uri.queryParameters['service'],
        ),
        routes: [
          GoRoute(
            path: ':id',
            name: 'agent-details',
            builder: (context, state) => AgentDetailsPage(
              agentId: int.parse(state.pathParameters['id']!),
              agent: state.extra is AgentModel ? state.extra as AgentModel : null,
            ),
          ),
        ],
      ),
    ],
  );
});

class AppRoutes {
  static const String login = '/login';
  static const String register = '/register';
  static const String dashboard = '/dashboard';
  static const String wallets = '/wallets';
  static const String cards = '/cards';
  static const String transactions = '/transactions';
  static const String settings = '/settings';
  static const String kyc = '/kyc';
  static const String scan = '/scan';
  static const String qrReceive = '/qr-receive';
  static const String qrSend = '/qr-send';
  static const String cryptoDeposit = '/crypto-deposit';
  static const String cryptoWithdraw = '/crypto-withdraw';
  static const String referral = '/referral';
}
