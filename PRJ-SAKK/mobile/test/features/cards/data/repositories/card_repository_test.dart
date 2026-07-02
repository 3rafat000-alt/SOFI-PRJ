/// Tests for CardRepository — mock Dio.
library;

import 'package:dio/dio.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mocktail/mocktail.dart';

import 'package:sakk_wallet/core/constants/api_constants.dart';
import 'package:sakk_wallet/core/network/api_client.dart';
import 'package:sakk_wallet/features/cards/data/models/card_model.dart';
import 'package:sakk_wallet/features/cards/data/repositories/card_repository.dart';
import 'package:sakk_wallet/features/transactions/data/models/transaction_model.dart';

import '../../../../helpers/mocks.dart';

class _MockCardDio extends MockDio {}

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();
  late Dio dio;
  late CardRepository repository;

  setUp(() {
    dio = _MockCardDio();
    repository = CardRepository(dio);
  });

  group('getCards', () {
    test('returns list of CardModel on success', () async {
      when(() => dio.get(any())).thenAnswer((_) async => buildDioResponse({
            'data': [testCardJson],
          }, path: ApiConstants.cards));

      final cards = await repository.getCards();

      expect(cards, hasLength(1));
      expect(cards.first, isA<CardModel>());
      expect(cards.first.lastFour, '1234');
    });

    test('returns empty list when data empty', () async {
      when(() => dio.get(any())).thenAnswer((_) async => buildDioResponse({
            'data': [],
          }, path: ApiConstants.cards));

      final cards = await repository.getCards();
      expect(cards, isEmpty);
    });

    test('throws ApiException on DioException', () async {
      when(() => dio.get(any())).thenThrow(buildDioException(
        statusCode: 500,
        message: 'Server error',
        path: ApiConstants.cards,
      ));

      expect(() => repository.getCards(), throwsA(isA<ApiException>()));
    });
  });

  group('getCard', () {
    test('returns CardModel by id', () async {
      when(() => dio.get(any())).thenAnswer((_) async => buildDioResponse({
            'data': testCardJson,
          }, path: ApiConstants.cardById(1)));

      final card = await repository.getCard(1);

      expect(card.id, 1);
      expect(card.brand, 'visa');
    });

    test('throws ApiException on 404', () async {
      when(() => dio.get(any())).thenThrow(buildDioException(
        statusCode: 404,
        message: 'Card not found',
        path: ApiConstants.cardById(999),
      ));

      expect(() => repository.getCard(999), throwsA(isA<ApiException>()));
    });
  });

  group('getCardTransactions', () {
    test('returns transaction list', () async {
      when(() => dio.get(any())).thenAnswer((_) async => buildDioResponse({
            'data': [testTransactionJson],
          }, path: ApiConstants.cardTransactions(1)));

      final txs = await repository.getCardTransactions(1);

      expect(txs, hasLength(1));
      expect(txs.first, isA<TransactionModel>());
    });

    test('returns empty list when data is null', () async {
      when(() => dio.get(any())).thenAnswer((_) async => buildDioResponse({
            'data': null,
          }, path: ApiConstants.cardTransactions(1)));

      final txs = await repository.getCardTransactions(1);
      expect(txs, isEmpty);
    });
  });

  group('createCard', () {
    test('returns created CardModel', () async {
      when(() => dio.post(
            any(),
            data: any(named: 'data'),
          )).thenAnswer((_) async => buildDioResponse({
            'data': testCardJson,
          }, path: ApiConstants.cardCreate));

      final card = await repository.createCard(
        walletId: 1,
        brand: 'visa',
        nickname: 'My Card',
      );

      expect(card.brand, 'visa');
    });

    test('throws ApiException on error', () async {
      when(() => dio.post(
            any(),
            data: any(named: 'data'),
          )).thenThrow(buildDioException(
        statusCode: 422,
        message: 'Insufficient balance',
        path: ApiConstants.cardCreate,
      ));

      expect(
        () => repository.createCard(walletId: 1, brand: 'visa'),
        throwsA(isA<ApiException>()),
      );
    });
  });

  group('getCardDetails', () {
    test('returns CardDetails', () async {
      when(() => dio.post(any())).thenAnswer((_) async => buildDioResponse({
            'data': testCardDetailsJson,
          }, path: ApiConstants.cardDetails(1)));

      final details = await repository.getCardDetails(1);

      expect(details.cardNumber, '4111111111111111');
      expect(details.cvv, '123');
    });

    test('throws ApiException on error', () async {
      when(() => dio.post(any())).thenThrow(buildDioException(
        statusCode: 403,
        message: 'PIN required',
        path: ApiConstants.cardDetails(1),
      ));

      expect(() => repository.getCardDetails(1), throwsA(isA<ApiException>()));
    });
  });

  group('freezeCard', () {
    test('returns frozen CardModel', () async {
      final frozenJson = {...testCardJson, 'status': 'frozen'};
      when(() => dio.post(any())).thenAnswer((_) async => buildDioResponse({
            'data': frozenJson,
          }, path: ApiConstants.cardFreeze(1)));

      final card = await repository.freezeCard(1);

      expect(card.isFrozen, true);
    });
  });

  group('unfreezeCard', () {
    test('returns active CardModel', () async {
      when(() => dio.post(any())).thenAnswer((_) async => buildDioResponse({
            'data': testCardJson,
          }, path: ApiConstants.cardUnfreeze(1)));

      final card = await repository.unfreezeCard(1);

      expect(card.isActive, true);
    });
  });

  group('loadCard', () {
    test('calls load endpoint when isLoad=true', () async {
      when(() => dio.post(
            any(),
            data: any(named: 'data'),
          )).thenAnswer((_) async =>
          buildDioResponse({}, path: ApiConstants.cardLoad(1)));

      await repository.loadCard(cardId: 1, walletId: 1, amount: 100.00, isLoad: true);

      verify(() => dio.post(ApiConstants.cardLoad(1), data: any(named: 'data')))
          .called(1);
    });

    test('calls unload endpoint when isLoad=false', () async {
      when(() => dio.post(
            any(),
            data: any(named: 'data'),
          )).thenAnswer((_) async =>
          buildDioResponse({}, path: ApiConstants.cardUnload(1)));

      await repository.loadCard(cardId: 1, walletId: 1, amount: 50.00, isLoad: false);

      verify(() => dio.post(ApiConstants.cardUnload(1), data: any(named: 'data')))
          .called(1);
    });
  });

  group('cancelCard', () {
    test('succeeds', () async {
      when(() => dio.post(any())).thenAnswer((_) async =>
          buildDioResponse({}, path: ApiConstants.cardCancel(1)));

      await expectLater(repository.cancelCard(1), completes);
    });

    test('throws ApiException on error', () async {
      when(() => dio.post(any())).thenThrow(buildDioException(
        statusCode: 400,
        message: 'Card has balance',
        path: ApiConstants.cardCancel(1),
      ));

      expect(() => repository.cancelCard(1), throwsA(isA<ApiException>()));
    });
  });

  group('FeaturedCardNotifier', () {
    test('initial state is null', () {
      final notifier = FeaturedCardNotifier();
      expect(notifier.state, isNull);
    });
  });
}
