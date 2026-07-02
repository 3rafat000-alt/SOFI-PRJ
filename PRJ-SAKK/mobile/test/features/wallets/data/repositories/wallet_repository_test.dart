/// Tests for WalletRepository — mock Dio.
library;

import 'package:dio/dio.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mocktail/mocktail.dart';

import 'package:sakk_wallet/core/constants/api_constants.dart';
import 'package:sakk_wallet/core/network/api_client.dart';
import 'package:sakk_wallet/features/wallets/data/models/wallet_model.dart';
import 'package:sakk_wallet/features/wallets/data/repositories/wallet_repository.dart';

import '../../../../helpers/mocks.dart';

class _MockWalletDio extends MockDio {}

void main() {
  late Dio dio;
  late WalletRepository repository;

  setUp(() {
    dio = _MockWalletDio();
    repository = WalletRepository(dio);
  });

  group('getWallets', () {
    test('returns list of WalletModel on success', () async {
      when(() => dio.get(any())).thenAnswer((_) async => buildDioResponse({
            'data': [testWalletJson, {...testWalletJson, 'id': 2, 'currency': 'SYP'}],
          }, path: ApiConstants.wallets));

      final wallets = await repository.getWallets();

      expect(wallets, hasLength(2));
      expect(wallets.first, isA<WalletModel>());
      expect(wallets.first.currency, 'USD');
      expect(wallets.last.currency, 'SYP');
    });

    test('throws ApiException on DioException', () async {
      when(() => dio.get(any())).thenThrow(buildDioException(
        statusCode: 500,
        message: 'Server error',
        path: ApiConstants.wallets,
      ));

      expect(() => repository.getWallets(), throwsA(isA<ApiException>()));
    });
  });

  group('getWallet', () {
    test('returns WalletModel by id', () async {
      when(() => dio.get(any())).thenAnswer((_) async => buildDioResponse({
            'data': testWalletJson,
          }, path: ApiConstants.walletById(1)));

      final wallet = await repository.getWallet(1);

      expect(wallet.id, 1);
      expect(wallet.balance, 1500.00);
    });

    test('throws ApiException on error', () async {
      when(() => dio.get(any())).thenThrow(buildDioException(
        statusCode: 404,
        message: 'Wallet not found',
        path: ApiConstants.walletById(999),
      ));

      expect(() => repository.getWallet(999), throwsA(isA<ApiException>()));
    });
  });

  group('createWallet', () {
    test('returns created WalletModel', () async {
      when(() => dio.post(
            any(),
            data: any(named: 'data'),
          )).thenAnswer((_) async => buildDioResponse({
            'data': testWalletJson,
          }, path: ApiConstants.wallets));

      final wallet = await repository.createWallet('USD');

      expect(wallet.currency, 'USD');
    });

    test('throws ApiException on error', () async {
      when(() => dio.post(
            any(),
            data: any(named: 'data'),
          )).thenThrow(buildDioException(
        statusCode: 422,
        message: 'Currency not supported',
        path: ApiConstants.wallets,
      ));

      expect(() => repository.createWallet('XYZ'), throwsA(isA<ApiException>()));
    });
  });

  group('deposit', () {
    test('returns deposit data', () async {
      when(() => dio.post(
            any(),
            data: any(named: 'data'),
          )).thenAnswer((_) async => buildDioResponse({
            'data': {'transaction_id': 42, 'amount': 100.00},
          }, path: ApiConstants.walletDeposit(1)));

      final result = await repository.deposit(1, 100.00, null);

      expect(result['transaction_id'], 42);
    });

    test('throws ApiException on error', () async {
      when(() => dio.post(
            any(),
            data: any(named: 'data'),
          )).thenThrow(buildDioException(
        statusCode: 400,
        message: 'Deposit failed',
        path: ApiConstants.walletDeposit(1),
      ));

      expect(
        () => repository.deposit(1, -100, null),
        throwsA(isA<ApiException>()),
      );
    });
  });

  group('withdraw', () {
    test('returns withdraw data with bank account', () async {
      when(() => dio.post(
            any(),
            data: any(named: 'data'),
          )).thenAnswer((_) async => buildDioResponse({
            'data': {'transaction_id': 43, 'amount': 50.00},
          }, path: ApiConstants.walletWithdraw(1)));

      final result = await repository.withdraw(
        1,
        amount: 50.00,
        bankAccount: 'SY000123',
      );

      expect(result['transaction_id'], 43);
    });

    test('throws ApiException on error', () async {
      when(() => dio.post(
            any(),
            data: any(named: 'data'),
          )).thenThrow(buildDioException(
        statusCode: 400,
        message: 'Insufficient balance',
        path: ApiConstants.walletWithdraw(1),
      ));

      expect(
        () => repository.withdraw(1, amount: 999999),
        throwsA(isA<ApiException>()),
      );
    });
  });

  group('convert', () {
    test('returns conversion result', () async {
      when(() => dio.post(
            any(),
            data: any(named: 'data'),
          )).thenAnswer((_) async => buildDioResponse({
            'data': {
              'from_currency': 'USD',
              'to_currency': 'SYP',
              'amount': 100.00,
              'credited': 1250000.00,
            },
          }, path: ApiConstants.walletConvert));

      final result = await repository.convert(
        fromCurrency: 'USD',
        toCurrency: 'SYP',
        amount: 100.00,
      );

      expect(result['credited'], 1250000.00);
    });

    test('throws ApiException on error', () async {
      when(() => dio.post(
            any(),
            data: any(named: 'data'),
          )).thenThrow(buildDioException(
        statusCode: 422,
        message: 'Conversion rate not available',
        path: ApiConstants.walletConvert,
      ));

      expect(
        () => repository.convert(fromCurrency: 'USD', toCurrency: 'EUR', amount: 100),
        throwsA(isA<ApiException>()),
      );
    });
  });
}
