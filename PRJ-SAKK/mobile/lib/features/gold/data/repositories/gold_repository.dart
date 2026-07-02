import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/network/api_client.dart';
import '../../../../core/constants/api_constants.dart';
import '../models/gold_models.dart';

final goldRepositoryProvider = Provider<GoldRepository>((ref) {
  return GoldRepository(ref.read(dioProvider));
});

/// Live karat prices (24/22/21/18).
final goldPricesProvider = FutureProvider<List<GoldPriceModel>>((ref) async {
  return ref.read(goldRepositoryProvider).getPrices();
});

/// The user's gold wallet (balance, valuation, USD balance, prices).
final goldWalletProvider = FutureProvider<GoldWalletModel>((ref) async {
  return ref.read(goldRepositoryProvider).getWallet();
});

/// Gold transaction history (most recent first).
final goldTransactionsProvider =
    FutureProvider<List<GoldTransactionModel>>((ref) async {
  return ref.read(goldRepositoryProvider).getTransactions();
});

class GoldRepository {
  final Dio _dio;

  GoldRepository(this._dio);

  Future<List<GoldPriceModel>> getPrices() async {
    try {
      final response = await _dio.get(ApiConstants.goldPrices);
      final List<dynamic> data = response.data['data'] ?? [];
      return data
          .map((e) => GoldPriceModel.fromJson(e as Map<String, dynamic>))
          .toList();
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  Future<GoldWalletModel> getWallet() async {
    try {
      final response = await _dio.get(ApiConstants.goldWallet);
      return GoldWalletModel.fromJson(
          response.data['data'] as Map<String, dynamic>);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  Future<List<GoldTransactionModel>> getTransactions({String? type}) async {
    try {
      final response = await _dio.get(ApiConstants.goldTransactions,
          queryParameters: {if (type != null) 'type': type});
      final List<dynamic> data = response.data['data'] ?? [];
      return data
          .map((e) => GoldTransactionModel.fromJson(e as Map<String, dynamic>))
          .toList();
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  /// Buy [grams] of gold at the given [karat]. Debits the USD wallet (+1% fee).
  /// Authorized by PIN (entered after on-device biometric).
  Future<Map<String, dynamic>> buy({
    required String karat,
    required double grams,
    required String pin,
  }) async {
    try {
      final response = await _dio.post(ApiConstants.goldBuy, data: {
        'karat': karat,
        'grams': grams,
        'pin': pin,
      });
      return Map<String, dynamic>.from(response.data['data'] as Map);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  /// Sell [grams] of gold at the given [karat]. Credits the USD wallet (−0.5% fee).
  /// Authorized by PIN (entered after on-device biometric).
  Future<Map<String, dynamic>> sell({
    required String karat,
    required double grams,
    required String pin,
  }) async {
    try {
      final response = await _dio.post(ApiConstants.goldSell, data: {
        'karat': karat,
        'grams': grams,
        'pin': pin,
      });
      return Map<String, dynamic>.from(response.data['data'] as Map);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }
}
