import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/network/api_client.dart';
import '../../../../core/constants/api_constants.dart';
import '../models/savings_models.dart';

final savingsRepositoryProvider = Provider<SavingsRepository>((ref) {
  return SavingsRepository(ref.read(dioProvider));
});

final savingsSummaryProvider = FutureProvider<SavingsSummary>((ref) async {
  return ref.read(savingsRepositoryProvider).getSummary();
});

final savingsGoalsProvider = FutureProvider<List<SavingsGoalModel>>((ref) async {
  return ref.read(savingsRepositoryProvider).getGoals();
});

class SavingsRepository {
  final Dio _dio;

  SavingsRepository(this._dio);

  Future<SavingsSummary> getSummary() async {
    try {
      final response = await _dio.get(ApiConstants.savingsSummary);
      return SavingsSummary.fromJson(
          response.data['data'] as Map<String, dynamic>);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  Future<List<SavingsGoalModel>> getGoals() async {
    try {
      final response = await _dio.get(ApiConstants.savings);
      final List<dynamic> data = response.data['data'] ?? [];
      return data
          .map((e) => SavingsGoalModel.fromJson(e as Map<String, dynamic>))
          .toList();
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  Future<SavingsGoalModel> createGoal({
    required String name,
    double? targetAmount,
    double? initialAmount,
    String? icon,
    String? color,
    DateTime? targetDate,
  }) async {
    try {
      final response = await _dio.post(ApiConstants.savings, data: {
        'name': name,
        if (targetAmount != null) 'target_amount': targetAmount,
        if (initialAmount != null && initialAmount > 0) 'initial_amount': initialAmount,
        if (icon != null) 'icon': icon,
        if (color != null) 'color': color,
        if (targetDate != null) 'target_date': targetDate.toIso8601String().split('T').first,
      });
      return SavingsGoalModel.fromJson(
          response.data['data'] as Map<String, dynamic>);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  Future<SavingsGoalModel> deposit(int id, double amount, String pin) async {
    try {
      final response = await _dio.post(ApiConstants.savingsDeposit(id), data: {
        'amount': amount,
        'pin': pin,
      });
      return SavingsGoalModel.fromJson(
          response.data['data'] as Map<String, dynamic>);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  Future<SavingsGoalModel> withdraw(int id, double amount, String pin) async {
    try {
      final response = await _dio.post(ApiConstants.savingsWithdraw(id), data: {
        'amount': amount,
        'pin': pin,
      });
      return SavingsGoalModel.fromJson(
          response.data['data'] as Map<String, dynamic>);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  Future<void> close(int id) async {
    try {
      await _dio.post(ApiConstants.savingsClose(id));
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }
}
