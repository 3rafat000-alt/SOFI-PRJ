/// Tests for GoldRepository — mock Dio.
library;

import 'package:dio/dio.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mocktail/mocktail.dart';

import 'package:sakk_wallet/core/constants/api_constants.dart';
import 'package:sakk_wallet/core/network/api_client.dart';
import 'package:sakk_wallet/features/gold/data/models/gold_models.dart';
import 'package:sakk_wallet/features/gold/data/repositories/gold_repository.dart';

import '../../../../helpers/mocks.dart';

class _MockGoldDio extends MockDio {}

void main() {
  late Dio dio;
  late GoldRepository repository;

  setUp(() {
    dio = _MockGoldDio();
    repository = GoldRepository(dio);
  });

  group('getPrices', () {
    test('returns list of GoldPriceModel', () async {
      when(() => dio.get(any())).thenAnswer((_) async => buildDioResponse({
            'data': [
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
          }, path: ApiConstants.goldPrices));

      final prices = await repository.getPrices();

      expect(prices, hasLength(2));
      expect(prices.first, isA<GoldPriceModel>());
      expect(prices.first.karat, '24');
      expect(prices.first.buyPrice, 75.50);
    });

    test('returns empty list when data is null', () async {
      when(() => dio.get(any())).thenAnswer((_) async => buildDioResponse({
            'data': null,
          }, path: ApiConstants.goldPrices));

      final prices = await repository.getPrices();
      expect(prices, isEmpty);
    });

    test('throws ApiException on error', () async {
      when(() => dio.get(any())).thenThrow(buildDioException(
        statusCode: 500,
        message: 'Server error',
        path: ApiConstants.goldPrices,
      ));

      expect(() => repository.getPrices(), throwsA(isA<ApiException>()));
    });
  });

  group('getWallet', () {
    test('returns GoldWalletModel', () async {
      when(() => dio.get(any())).thenAnswer((_) async => buildDioResponse({
            'data': testGoldWalletJson,
          }, path: ApiConstants.goldWallet));

      final wallet = await repository.getWallet();

      expect(wallet.balanceGrams, 10.5);
      expect(wallet.currentValueUsd, 792.75);
      expect(wallet.prices, hasLength(2));
      expect(wallet.hasGold, true);
      expect(wallet.isProfit, true);
      expect(wallet.profitLossPercent, closeTo(5.7, 0.1));
    });

    test('throws ApiException on error', () async {
      when(() => dio.get(any())).thenThrow(buildDioException(
        statusCode: 500,
        message: 'Server error',
        path: ApiConstants.goldWallet,
      ));

      expect(() => repository.getWallet(), throwsA(isA<ApiException>()));
    });
  });

  group('getTransactions', () {
    test('returns list of GoldTransactionModel', () async {
      when(() => dio.get(
            any(),
            queryParameters: any(named: 'queryParameters'),
          )).thenAnswer((_) async => buildDioResponse({
            'data': [
              testGoldTxJson,
              {
                ...testGoldTxJson,
                'reference': 'GOLD-002',
                'type': 'sell',
                'grams': 1.0,
              },
            ],
          }, path: ApiConstants.goldTransactions));

      final txs = await repository.getTransactions();

      expect(txs, hasLength(2));
      expect(txs.first, isA<GoldTransactionModel>());
      expect(txs.first.isBuy, true);
      expect(txs.last.isBuy, false);
    });

    test('filters by type', () async {
      when(() => dio.get(
            any(),
            queryParameters: any(named: 'queryParameters'),
          )).thenAnswer((_) async => buildDioResponse({
            'data': [testGoldTxJson],
          }, path: ApiConstants.goldTransactions));

      await repository.getTransactions(type: 'buy');

      final captured = verify(() => dio.get(
            any(),
            queryParameters: captureAny(named: 'queryParameters'),
          )).captured.first as Map<String, dynamic>;
      expect(captured['type'], 'buy');
    });

    test('returns empty list on null data', () async {
      when(() => dio.get(
            any(),
            queryParameters: any(named: 'queryParameters'),
          )).thenAnswer((_) async => buildDioResponse({
            'data': null,
          }, path: ApiConstants.goldTransactions));

      final txs = await repository.getTransactions();
      expect(txs, isEmpty);
    });
  });

  group('buy', () {
    test('returns buy result map', () async {
      when(() => dio.post(
            any(),
            data: any(named: 'data'),
          )).thenAnswer((_) async => buildDioResponse({
            'data': {
              'reference': 'GOLD-BUY-001',
              'grams': 5.0,
              'total': 381.28,
              'fee': 3.78,
              'balance_grams': 15.5,
            },
          }, path: ApiConstants.goldBuy));

      final result = await repository.buy(
        karat: '24',
        grams: 5.0,
        pin: '123456',
      );

      expect(result['reference'], 'GOLD-BUY-001');
      expect(result['grams'], 5.0);
    });

    test('throws ApiException on error', () async {
      when(() => dio.post(
            any(),
            data: any(named: 'data'),
          )).thenThrow(buildDioException(
        statusCode: 400,
        message: 'Insufficient USD balance',
        path: ApiConstants.goldBuy,
      ));

      expect(
        () => repository.buy(karat: '24', grams: 999, pin: '123456'),
        throwsA(isA<ApiException>()),
      );
    });
  });

  group('sell', () {
    test('returns sell result map', () async {
      when(() => dio.post(
            any(),
            data: any(named: 'data'),
          )).thenAnswer((_) async => buildDioResponse({
            'data': {
              'reference': 'GOLD-SELL-001',
              'grams': 2.0,
              'total': 146.40,
              'fee': 0.73,
              'balance_grams': 8.5,
            },
          }, path: ApiConstants.goldSell));

      final result = await repository.sell(
        karat: '24',
        grams: 2.0,
        pin: '123456',
      );

      expect(result['reference'], 'GOLD-SELL-001');
    });

    test('throws ApiException on error', () async {
      when(() => dio.post(
            any(),
            data: any(named: 'data'),
          )).thenThrow(buildDioException(
        statusCode: 400,
        message: 'Insufficient gold balance',
        path: ApiConstants.goldSell,
      ));

      expect(
        () => repository.sell(karat: '24', grams: 999, pin: '123456'),
        throwsA(isA<ApiException>()),
      );
    });
  });
}
