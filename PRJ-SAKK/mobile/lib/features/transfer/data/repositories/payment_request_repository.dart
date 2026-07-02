import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/network/api_client.dart';
import '../../../../core/constants/api_constants.dart';

final paymentRequestRepositoryProvider = Provider<PaymentRequestRepository>((ref) {
  return PaymentRequestRepository(ref.read(dioProvider));
});

/// Create, view, and pay "request money" links.
class PaymentRequestRepository {
  final Dio _dio;
  PaymentRequestRepository(this._dio);

  /// Create a payment request. Returns the created request (incl. uuid).
  Future<Map<String, dynamic>> create({
    required double amount,
    required String currency,
    String? note,
  }) async {
    try {
      final response = await _dio.post(ApiConstants.paymentRequests, data: {
        'amount': amount,
        'currency': currency,
        if (note != null && note.trim().isNotEmpty) 'note': note.trim(),
      });
      return Map<String, dynamic>.from(response.data['data']);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  /// View a payment request by uuid.
  Future<Map<String, dynamic>> show(String uuid) async {
    try {
      final response = await _dio.get(ApiConstants.paymentRequestByUuid(uuid));
      return Map<String, dynamic>.from(response.data['data']);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  /// Pay a payment request. Returns { transaction, payment_request }.
  /// Requires the user's PIN — the server enforces a second factor (SEC H1).
  Future<Map<String, dynamic>> pay(String uuid, String pin) async {
    try {
      final response = await _dio.post(
        ApiConstants.paymentRequestPay(uuid),
        data: {'pin': pin},
      );
      return Map<String, dynamic>.from(response.data['data']);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  /// List my payment requests.
  Future<List<Map<String, dynamic>>> list() async {
    try {
      final response = await _dio.get(ApiConstants.paymentRequests);
      return (response.data['data'] as List).map((e) => Map<String, dynamic>.from(e)).toList();
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  Future<void> cancel(String uuid) async {
    try {
      await _dio.post(ApiConstants.paymentRequestCancel(uuid));
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  /// Request a payment directly from a specific user (by account number). They
  /// get a notification and can accept or reject it.
  Future<Map<String, dynamic>> requestFromUser({
    required String account,
    required double amount,
    required String currency,
    String? note,
  }) async {
    try {
      final response = await _dio.post(ApiConstants.paymentRequests, data: {
        'amount': amount,
        'currency': currency,
        'requestee_account': account,
        if (note != null && note.trim().isNotEmpty) 'note': note.trim(),
      });
      return Map<String, dynamic>.from(response.data['data']);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  /// Requests targeted at me (awaiting my accept/reject).
  Future<List<Map<String, dynamic>>> received() async {
    try {
      final response = await _dio.get(ApiConstants.paymentRequestsReceived);
      return (response.data['data'] as List)
          .map((e) => Map<String, dynamic>.from(e))
          .toList();
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  /// Accept a directed request — I pay the requester.
  /// Requires the user's PIN — the server enforces a second factor (SEC H1).
  Future<void> accept(String uuid, String pin) async {
    try {
      await _dio.post(
        ApiConstants.paymentRequestAccept(uuid),
        data: {'pin': pin},
      );
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  /// Reject a directed request (optional reason note).
  Future<void> reject(String uuid, {String? note}) async {
    try {
      await _dio.post(ApiConstants.paymentRequestReject(uuid), data: {
        if (note != null && note.trim().isNotEmpty) 'note': note.trim(),
      });
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }
}
