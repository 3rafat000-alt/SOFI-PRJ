/// Shared mock classes for carda-wallet mobile tests.
library;

import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:mocktail/mocktail.dart';

import 'package:sakk_wallet/features/auth/data/models/user_model.dart';
import 'package:sakk_wallet/features/wallets/data/models/wallet_model.dart';
import 'package:sakk_wallet/features/cards/data/models/card_model.dart';
import 'package:sakk_wallet/features/transactions/data/models/transaction_model.dart';
import 'package:sakk_wallet/features/gold/data/models/gold_models.dart';

// ──────────────────────────────────────────────
// Dio
// ──────────────────────────────────────────────
class MockDio extends Mock implements Dio {}

class MockRequestOptions extends Mock implements RequestOptions {
  @override
  // ignore: overridden_fields
  String path;
  MockRequestOptions({this.path = ''});
}

// ──────────────────────────────────────────────
// FlutterSecureStorage
// ──────────────────────────────────────────────
class MockFlutterSecureStorage extends Mock
    implements FlutterSecureStorage {}

// ──────────────────────────────────────────────
// Shared test data
// ──────────────────────────────────────────────

final testUserJson = <String, dynamic>{
  'id': 1,
  'uuid': 'abc-123',
  'first_name': 'أحمد',
  'last_name': 'السوري',
  'full_name': 'أحمد السوري',
  'email': 'ahmad@example.com',
  'phone': '+963900000001',
  'status': 'active',
  'kyc_status': 'verified',
  'is_kyc_verified': true,
  'is_active': true,
  'has_pin': true,
  'two_factor_enabled': false,
  'email_verified': true,
  'phone_verified': true,
  'kyc_level': 2,
  'referral_code': 'REF001',
  'created_at': '2026-01-01T00:00:00.000Z',
};

final testUser = UserModel(
  id: 1,
  uuid: 'abc-123',
  firstName: 'أحمد',
  lastName: 'السوري',
  fullName: 'أحمد السوري',
  email: 'ahmad@example.com',
  phone: '+963900000001',
  statusValue: 'active',
  statusLabel: 'Active',
  kycStatusValue: 'verified',
  kycStatusLabel: 'Verified',
  isKycVerified: true,
  isActive: true,
  hasPin: true,
  twoFactorEnabled: false,
  emailVerified: true,
  phoneVerified: true,
  kycLevel: 2,
  referralCode: 'REF001',
  createdAt: DateTime(2026, 1, 1),
);

final testWalletJson = <String, dynamic>{
  'id': 1,
  'currency': 'USD',
  'balance': 1500.00,
  'available_balance': 1400.00,
  'pending_balance': 100.00,
  'is_active': true,
  'created_at': '2026-01-01T00:00:00.000Z',
};

final testWallet = WalletModel(
  id: 1,
  currency: 'USD',
  balance: 1500.00,
  availableBalance: 1400.00,
  pendingBalance: 100.00,
  isActive: true,
  createdAt: DateTime(2026, 1, 1),
);

final testCardJson = <String, dynamic>{
  'id': 1,
  'brand': 'visa',
  'card_type': 'virtual',
  'last_four': '1234',
  'expiry': '12/28',
  'balance': 500.00,
  'spending_limit': 500.00,
  'daily_limit': 500.00,
  'monthly_limit': 5000.00,
  'status': 'active',
  'label': 'بطاقتي',
  'cardholder_name': 'Ahmad Al Suri',
  'created_at': '2026-01-01T00:00:00.000Z',
};

final testCard = CardModel(
  id: 1,
  brand: 'visa',
  type: 'virtual',
  lastFour: '1234',
  expiryDate: '12/28',
  balance: 500.00,
  spendingLimit: 500.00,
  dailyLimit: 500.00,
  monthlyLimit: 5000.00,
  status: 'active',
  label: 'بطاقتي',
  cardholderName: 'Ahmad Al Suri',
  createdAt: DateTime(2026, 1, 1),
);

final testCardDetailsJson = <String, dynamic>{
  'card_number': '4111111111111111',
  'cvv': '123',
  'expiry_month': '12',
  'expiry_year': '2028',
  'cardholder_name': 'Ahmad Al Suri',
};

final testTransactionJson = <String, dynamic>{
  'id': 1,
  'type': 'deposit',
  'status': 'completed',
  'amount': 500.00,
  'fee': 0.00,
  'currency': 'USD',
  'title': 'إيداع',
  'created_at': '2026-06-01T12:00:00.000Z',
};

final testTransaction = TransactionModel(
  id: 1,
  type: 'deposit',
  status: 'completed',
  amount: 500.00,
  fee: 0.00,
  currency: 'USD',
  title: 'إيداع',
  createdAt: DateTime(2026, 6, 1, 12, 0, 0),
);

final testGoldPrice = GoldPriceModel(
  karat: '24',
  karatLabel: 'عيار 24',
  purity: '99.9%',
  buyPrice: 75.50,
  sellPrice: 73.20,
  spread: 2.30,
);

final testGoldWalletJson = <String, dynamic>{
  'balance_grams': 10.5,
  'current_value_usd': 792.75,
  'total_invested_usd': 750.00,
  'total_bought_grams': 12.0,
  'total_sold_grams': 1.5,
  'profit_loss_usd': 42.75,
  'usd_balance': 5000.00,
  'prices': [
    {
      'karat': '24',
      'karat_label': 'عيار 24',
      'purity': '99.9%',
      'buy_price': 75.50,
      'sell_price': 73.20,
      'spread': 2.30,
    },
    {
      'karat': '22',
      'karat_label': 'عيار 22',
      'buy_price': 69.20,
      'sell_price': 67.10,
      'spread': 2.10,
    },
  ],
};

final testGoldWallet = GoldWalletModel(
  balanceGrams: 10.5,
  currentValueUsd: 792.75,
  totalInvestedUsd: 750.00,
  totalBoughtGrams: 12.0,
  totalSoldGrams: 1.5,
  profitLossUsd: 42.75,
  usdBalance: 5000.00,
  prices: [
    GoldPriceModel(
      karat: '24',
      karatLabel: 'عيار 24',
      purity: '99.9%',
      buyPrice: 75.50,
      sellPrice: 73.20,
      spread: 2.30,
    ),
    GoldPriceModel(
      karat: '22',
      karatLabel: 'عيار 22',
      buyPrice: 69.20,
      sellPrice: 67.10,
      spread: 2.10,
    ),
  ],
);

final testGoldTxJson = <String, dynamic>{
  'reference': 'GOLD-001',
  'type': 'buy',
  'karat': '24',
  'grams': 5.0,
  'price_per_gram_usd': 75.50,
  'total_usd': 381.28,
  'fee_usd': 3.78,
  'status': 'completed',
  'created_at': '2026-06-01T10:00:00.000Z',
};

final testGoldTx = GoldTransactionModel(
  reference: 'GOLD-001',
  type: 'buy',
  karat: '24',
  grams: 5.0,
  pricePerGramUsd: 75.50,
  totalUsd: 381.28,
  feeUsd: 3.78,
  status: 'completed',
  createdAt: DateTime(2026, 6, 1, 10, 0, 0),
);

/// Helper to build a Dio Response for a given status/data.
Response buildDioResponse(
  dynamic data, {
  int statusCode = 200,
  String path = '/test',
}) {
  return Response(
    requestOptions: RequestOptions(path: path),
    data: data,
    statusCode: statusCode,
  );
}

/// Helper to build a DioException for error-path testing.
DioException buildDioException({
  int statusCode = 422,
  String message = 'Validation error',
  String path = '/test',
}) {
  return DioException(
    requestOptions: RequestOptions(path: path),
    response: Response(
      requestOptions: RequestOptions(path: path),
      data: {'message': message},
      statusCode: statusCode,
    ),
    type: DioExceptionType.badResponse,
  );
}
