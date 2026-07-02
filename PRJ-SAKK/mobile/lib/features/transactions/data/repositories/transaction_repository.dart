import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/network/api_client.dart';
import '../../../../core/constants/api_constants.dart';
import '../models/transaction_model.dart';

final transactionRepositoryProvider = Provider<TransactionRepository>((ref) {
  return TransactionRepository(ref.read(dioProvider));
});

final transactionsProvider = FutureProvider<List<TransactionModel>>((ref) async {
  return ref.read(transactionRepositoryProvider).getTransactions();
});

final recentTransactionsProvider = FutureProvider<List<TransactionModel>>((ref) async {
  return ref.read(transactionRepositoryProvider).getTransactions(limit: 10);
});

class TransactionRepository {
  final Dio _dio;
  
  TransactionRepository(this._dio);
  
  Future<List<TransactionModel>> getTransactions({
    int? limit,
    int? offset,
    String? type,
    String? status,
    DateTime? startDate,
    DateTime? endDate,
  }) async {
    try {
      final response = await _dio.get(ApiConstants.transactions, queryParameters: {
        if (limit != null) 'limit': limit,
        if (offset != null) 'offset': offset,
        if (type != null) 'type': type,
        if (status != null) 'status': status,
        if (startDate != null) 'start_date': startDate.toIso8601String(),
        if (endDate != null) 'end_date': endDate.toIso8601String(),
      });
      
      final List<dynamic> data = response.data['data'];
      return data.map((json) => TransactionModel.fromJson(json)).toList();
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }
  
  Future<TransactionModel> getTransaction(int id) async {
    try {
      final response = await _dio.get(ApiConstants.transactionById(id));
      return TransactionModel.fromJson(response.data['data']);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }
  
  Future<Map<String, dynamic>> getStats({
    DateTime? startDate,
    DateTime? endDate,
  }) async {
    try {
      final response = await _dio.get(ApiConstants.transactionStats, queryParameters: {
        if (startDate != null) 'start_date': startDate.toIso8601String(),
        if (endDate != null) 'end_date': endDate.toIso8601String(),
      });
      return response.data['data'];
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }
}
