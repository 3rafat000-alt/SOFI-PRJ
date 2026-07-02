import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/network/api_client.dart';
import '../../../../core/constants/api_constants.dart';
import '../models/wallet_model.dart';

final walletRepositoryProvider = Provider<WalletRepository>((ref) {
  return WalletRepository(ref.read(dioProvider));
});

final walletsProvider = FutureProvider<List<WalletModel>>((ref) async {
  return ref.read(walletRepositoryProvider).getWallets();
});

final walletProvider = FutureProvider.family<WalletModel, int>((ref, id) async {
  return ref.read(walletRepositoryProvider).getWallet(id);
});

class WalletRepository {
  final Dio _dio;
  
  WalletRepository(this._dio);
  
  Future<List<WalletModel>> getWallets() async {
    try {
      final response = await _dio.get(ApiConstants.wallets);
      final List<dynamic> data = response.data['data'];
      return data.map((json) => WalletModel.fromJson(json)).toList();
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }
  
  Future<WalletModel> getWallet(int id) async {
    try {
      final response = await _dio.get(ApiConstants.walletById(id));
      return WalletModel.fromJson(response.data['data']);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }
  
  Future<WalletModel> createWallet(String currency) async {
    try {
      final response = await _dio.post(ApiConstants.wallets, data: {
        'currency': currency,
      });
      return WalletModel.fromJson(response.data['data']);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }
  
  Future<Map<String, dynamic>> deposit(int walletId, double amount, String? reference) async {
    try {
      final response = await _dio.post(ApiConstants.walletDeposit(walletId), data: {
        'amount': amount,
        if (reference != null) 'reference': reference,
      });
      return response.data['data'];
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }
  
  Future<Map<String, dynamic>> withdraw(int walletId, {
    required double amount,
    String? bankAccount,
    String? address,
    String? network,
  }) async {
    try {
      final response = await _dio.post(ApiConstants.walletWithdraw(walletId), data: {
        'amount': amount,
        if (bankAccount != null) 'bank_account': bankAccount,
        if (address != null) 'address': address,
        if (network != null) 'network': network,
      });
      return response.data['data'];
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  /// Create (or fetch) a CCPayment crypto deposit address. USDT-only —
  /// the backend integration currently services USDT deposits exclusively;
  /// do not widen `currency` beyond 'USDT' without a matching backend change.
  Future<Map<String, dynamic>> createCryptoDepositAddress({
    required int walletId,
    required String chain,
    required String currency,
  }) async {
    try {
      final response = await _dio.post('/ccpayment/deposit/address', data: {
        'wallet_id': walletId,
        'chain': chain,
        'currency': currency,
      });
      return response.data['data'];
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  /// Exchange (convert) money between the user's own USD and SYP wallets.
  /// Revenue is spread-only (server-authoritative). No PIN required for
  /// transferring between the user's own wallets. Returns the conversion
  /// result including the credited amount and updated wallet balances.
  Future<Map<String, dynamic>> convert({
    required String fromCurrency,
    required String toCurrency,
    required double amount,
  }) async {
    try {
      final response = await _dio.post(ApiConstants.walletConvert, data: {
        'from_currency': fromCurrency,
        'to_currency': toCurrency,
        'amount': amount,
      });
      return response.data['data'];
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

}
