import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/network/api_client.dart';
import '../../../../core/constants/api_constants.dart';

final contactsRepositoryProvider = Provider<ContactsRepository>((ref) {
  return ContactsRepository(ref.read(dioProvider));
});

/// Matches device phone numbers against registered SAKK users, and fetches
/// referral info (code + admin-configured reward) for inviting others.
class ContactsRepository {
  final Dio _dio;
  ContactsRepository(this._dio);

  /// Returns matched users: [{ phone, name, initials, account_number }].
  Future<List<Map<String, dynamic>>> match(List<String> phones) async {
    try {
      final response = await _dio.post(ApiConstants.contactsMatch, data: {'phones': phones});
      return (response.data['data'] as List).map((e) => Map<String, dynamic>.from(e)).toList();
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  /// Referral code + reward amount + stats.
  Future<Map<String, dynamic>> referralInfo() async {
    try {
      final response = await _dio.get(ApiConstants.referralInfo);
      return Map<String, dynamic>.from(response.data['data']);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }
}
