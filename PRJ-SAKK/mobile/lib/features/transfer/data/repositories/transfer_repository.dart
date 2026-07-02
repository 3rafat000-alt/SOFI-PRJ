import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/network/api_client.dart';
import '../../../../core/constants/api_constants.dart';

final transferRepositoryProvider = Provider<TransferRepository>((ref) {
  return TransferRepository(ref.read(dioProvider));
});

/// Peer-to-peer transfer: resolve a recipient and send money to another SAKK user.
class TransferRepository {
  final Dio _dio;

  TransferRepository(this._dio);

  /// Resolve a recipient by SAKK tag / email / phone.
  /// Returns a recipient card: { id, name, initials, tag, avatar }.
  /// Throws [ApiException] (e.g. 404 not found, 422 self-transfer).
  Future<Map<String, dynamic>> lookupRecipient(String identifier) async {
    try {
      final response = await _dio.get(
        ApiConstants.transferLookup,
        queryParameters: {'identifier': identifier},
      );
      return Map<String, dynamic>.from(response.data['data']);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  /// Send money to another SAKK user (same-currency, instant, no fee).
  Future<Map<String, dynamic>> sendTransfer({
    required String identifier,
    required double amount,
    required String currency,
    required String pin,
    String? note,
  }) async {
    try {
      final response = await _dio.post(ApiConstants.transfer, data: {
        'identifier': identifier,
        'amount': amount,
        'currency': currency,
        'pin': pin,
        if (note != null && note.trim().isNotEmpty) 'note': note.trim(),
      });
      return Map<String, dynamic>.from(response.data['data']);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }
}
