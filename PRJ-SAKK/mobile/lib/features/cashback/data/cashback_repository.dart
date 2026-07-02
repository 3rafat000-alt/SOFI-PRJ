import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/constants/api_constants.dart';
import '../../../core/network/api_client.dart';
import '../../transactions/data/models/transaction_model.dart';

/// A user's cashback summary: total earned + recent cashback operations.
class CashbackSummary {
  final double total;
  final String currency;
  final int count;
  final List<TransactionModel> transactions;

  const CashbackSummary({
    required this.total,
    required this.currency,
    required this.count,
    required this.transactions,
  });

  static const empty = CashbackSummary(
      total: 0, currency: 'USD', count: 0, transactions: []);
}

final cashbackRepositoryProvider = Provider<CashbackRepository>((ref) {
  return CashbackRepository(ref.read(dioProvider));
});

final cashbackProvider = FutureProvider<CashbackSummary>((ref) async {
  return ref.read(cashbackRepositoryProvider).get();
});

class CashbackRepository {
  final Dio _dio;
  CashbackRepository(this._dio);

  Future<CashbackSummary> get() async {
    try {
      final response = await _dio.get(ApiConstants.cashback);
      final d = Map<String, dynamic>.from(response.data['data']);
      return CashbackSummary(
        total: (d['total'] as num?)?.toDouble() ?? 0,
        currency: (d['currency'] ?? 'USD').toString(),
        count: (d['count'] as num?)?.toInt() ?? 0,
        transactions: (d['transactions'] as List? ?? [])
            .map((e) => TransactionModel.fromJson(Map<String, dynamic>.from(e)))
            .toList(),
      );
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }
}
