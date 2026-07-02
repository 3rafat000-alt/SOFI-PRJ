class ApiConstants {
  ApiConstants._();
  
  // Base URL - Change for production
  // 🔒 SECURITY: Use HTTPS in production. HTTP LAN IP removed — all API traffic
  // including auth tokens, PINs, KYC documents, and card details must be TLS-protected.
  // For local development, override via --dart-define=BASE_URL=http://localhost:8000/api/v1
  // Debug builds may use the emulator address automatically.
  static const String baseUrl = String.fromEnvironment(
    'BASE_URL',
    defaultValue: 'https://sakk.zanjour.com/api/v1',
  );

  // Host without the /api/v1 suffix — used to build storage/asset URLs (avatars, etc.)
  static String get host => baseUrl.replaceAll(RegExp(r'/api/v\d+/?$'), '');

  /// Resolve a possibly-relative storage path into a full URL.
  /// Returns null for null/empty input. Leaves absolute http(s) URLs untouched.
  static String? resolveStorageUrl(String? path) {
    if (path == null || path.isEmpty) return null;
    if (path.startsWith('http://') || path.startsWith('https://')) return path;
    final clean = path.startsWith('/') ? path.substring(1) : path;
    if (clean.startsWith('storage/')) return '$host/$clean';
    return '$host/storage/$clean';
  }
  
  // Auth
  static const String register = '/auth/register';
  static const String login = '/auth/login';
  static const String logout = '/auth/logout';
  static const String me = '/auth/me';
  static const String updateProfile = '/profile';
  static const String deleteAccount = '/profile';
  static const String changePassword = '/auth/password';
  static const String setPin = '/auth/pin';
  static const String verifyPin = '/auth/pin/verify';
  static const String changePin = '/auth/pin/change';
  static const String disablePin = '/auth/pin/disable';
  static const String forgotPassword = '/auth/forgot-password';
  static const String twoFactorSetup = '/auth/2fa/setup';
  static const String twoFactorConfirm = '/auth/2fa/confirm';
  static const String twoFactorDisable = '/auth/2fa/disable';
  static const String twoFactorStatus = '/auth/2fa/status';
  static const String twoFactorRecoveryCodes = '/auth/2fa/recovery-codes';
  static const String deleteAvatar = '/profile/avatar';
  // Connected devices (new-device approval + 48h transaction hold)
  static const String devices = '/devices';
  static const String deviceRegister = '/devices/register';
  static String deviceApprove(int id) => '/devices/$id/approve';
  static String deviceReject(int id) => '/devices/$id/reject';
  static String deviceById(int id) => '/devices/$id';

  // Biometric (device-bound passwordless auth)
  static const String biometricDevices = '/auth/biometric/devices';
  static String biometricDevice(int id) => '/auth/biometric/devices/$id';
  static const String biometricChallenge = '/auth/biometric/challenge';
  static const String biometricVerify = '/auth/biometric/verify';
  
  // Wallets
  static const String wallets = '/wallets';
  static const String walletConvert = '/wallets/convert';

  // P2P Transfer (send to another user)
  static const String transferLookup = '/transfer/lookup';
  static const String transfer = '/transfer';

  // Payment Requests (request money via link/QR)
  static const String paymentRequests = '/payment-requests';
  static const String paymentRequestsReceived = '/payment-requests/received';
  static String paymentRequestByUuid(String uuid) => '/payment-requests/$uuid';
  static String paymentRequestPay(String uuid) => '/payment-requests/$uuid/pay';
  static String paymentRequestAccept(String uuid) => '/payment-requests/$uuid/accept';
  static String paymentRequestReject(String uuid) => '/payment-requests/$uuid/reject';
  static String paymentRequestCancel(String uuid) => '/payment-requests/$uuid/cancel';

  // Contacts matching + referral
  static const String contactsMatch = '/contacts/match';
  static const String referralInfo = '/referral/info';
  static String walletById(int id) => '/wallets/$id';
  static String walletDeposit(int id) => '/wallets/$id/deposit';
  static String walletWithdraw(int id) => '/wallets/$id/withdraw';
  static String walletTransactions(int id) => '/wallets/$id/transactions';
  
  // Cards
  static const String cards = '/cards';
  static String cardById(int id) => '/cards/$id';
  static String cardDetails(int id) => '/cards/$id/details';
  static String cardLoad(int id) => '/cards/$id/load';
  static String cardUnload(int id) => '/cards/$id/unload';
  static String cardFreeze(int id) => '/cards/$id/freeze';
  static String cardUnfreeze(int id) => '/cards/$id/unfreeze';
  static String cardCancel(int id) => '/cards/$id/cancel';
  static String cardTransactions(int id) => '/cards/$id/transactions';
  static String cardCreate = '/cards';
  
  // Cashback (earned rewards)
  static const String cashback = '/cashback';

  // Cash Agents (nearby agent finder)
  static const String agents = '/agents';
  static String agentById(int id) => '/agents/$id';

  // Gold Savings (buy/sell grams at real gold prices)
  static const String goldPrices = '/gold/prices';
  static const String goldWallet = '/gold/wallet';
  static const String goldBuy = '/gold/buy';
  static const String goldSell = '/gold/sell';
  static const String goldTransactions = '/gold/transactions';
  static const String goldStats = '/gold/stats';

  // Savings (separate cash-savings goals product)
  static const String savings = '/savings';
  static const String savingsSummary = '/savings/summary';
  static String savingsById(int id) => '/savings/$id';
  static String savingsDeposit(int id) => '/savings/$id/deposit';
  static String savingsWithdraw(int id) => '/savings/$id/withdraw';
  static String savingsClose(int id) => '/savings/$id/close';

  // Partner application (join as agent or merchant)
  static const String partnerApplication = '/partner/application';
  static const String partnerApply = '/partner/apply';
  static const String partnerDocuments = '/partner/documents';

  // Company application (join as a company — انضم كشركة)
  static const String companyApplication = '/company/application';
  static const String companyApply = '/company/apply';
  static const String companyDocuments = '/company/documents';

  // Transactions
  static const String transactions = '/transactions';
  static String transactionById(int id) => '/transactions/$id';
  static const String transactionStats = '/transactions/stats';
  
  // KYC
  static const String kycStatus = '/kyc/status';
  static const String kycLevels = '/kyc/levels';
  static const String kycSubmissions = '/kyc/submissions';
  static const String kycEmailSend = '/kyc/email/send';
  static const String kycEmailVerify = '/kyc/email/verify';
  static const String kycPhoneUpdate = '/kyc/phone/update';
  static const String kycPhoneSend = '/kyc/phone/send';
  static const String kycPhoneVerify = '/kyc/phone/verify';
  static const String kycIdDocument = '/kyc/id-document';
  static const String kycSelfie = '/kyc/selfie';
  static const String kycAddressProof = '/kyc/address-proof';

  // Telegram OTP channel (account linking)
  static const String telegramLink = '/telegram/link';
  static const String telegramStatus = '/telegram/status';
  static const String telegramUnlink = '/telegram/unlink';

  // Exchange Rates
  static const String exchangeRates = '/exchange-rates';
  static const String exchangeRate = '/exchange-rates/rate';
  static const String exchangeConvert = '/exchange-rates/convert';
  static const String exchangeHistory = '/exchange-rates/history';
  
  // Notifications
  static const String notifications = '/notifications';
  static String notificationRead(int id) => '/notifications/$id/read';
  static const String notificationsReadAll = '/notifications/read-all';
  static const String notificationsUnreadCount = '/notifications/unread-count';
  static const String notificationFcmToken = '/notifications/fcm-token';
  
  // App metadata (force-update policy — read at boot)
  static const String appVersion = '/app/version';

  // Help & Support — admin-managed contact channels served to the app.
  static const String appSupport = '/app/support';

  // Live chat (customer support, polling transport — no websocket)
  static const String chatConversation = '/chat/conversation';
  static const String chatMessages = '/chat/messages';

  // Support tickets (customer support desk)
  static const String supportCategories = '/support/categories';
  static const String supportTickets = '/support/tickets';
  static String supportTicket(String uuid) => '/support/tickets/$uuid';
  static String supportTicketReply(String uuid) => '/support/tickets/$uuid/reply';

  // QR Auth
  static const String qrGenerate = '/auth/qr/generate';
  static const String qrPoll = '/auth/qr/poll';
  static String qrPollToken(String token) => '/auth/qr/poll/$token';
  static const String qrApprove = '/auth/qr/approve';
}
