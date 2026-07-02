import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/constants/api_constants.dart';
import '../../../../core/network/api_client.dart';

/// Telegram OTP channel — account linking from the app.
///
/// The user taps "ربط تلجرام", we fetch a one-time deep link
/// (`t.me/<bot>?start=<token>`) and open it; once they press Start the
/// backend webhook binds their chat. Status reflects the bound state.
final telegramRepositoryProvider = Provider<TelegramRepository>((ref) {
  return TelegramRepository(ref.read(dioProvider));
});

/// Current link state for the signed-in user (auto-refreshes on invalidate).
final telegramStatusProvider =
    FutureProvider.autoDispose<TelegramLinkStatus>((ref) async {
  return ref.read(telegramRepositoryProvider).status();
});

class TelegramLinkStatus {
  final bool linked;
  final String? username;

  const TelegramLinkStatus({required this.linked, this.username});

  factory TelegramLinkStatus.fromJson(Map<String, dynamic> json) {
    return TelegramLinkStatus(
      linked: json['linked'] == true,
      username: json['username'] as String?,
    );
  }
}

class TelegramRepository {
  final Dio _dio;

  TelegramRepository(this._dio);

  /// One-time deep link to open in Telegram to bind this account.
  Future<String> getDeepLink() async {
    try {
      final res = await _dio.get(ApiConstants.telegramLink);
      final link = res.data['deep_link'] as String?;
      if (link == null || link.isEmpty) {
        throw ApiException(message: 'تعذّر إنشاء رابط الربط');
      }
      return link;
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  Future<TelegramLinkStatus> status() async {
    try {
      final res = await _dio.get(ApiConstants.telegramStatus);
      return TelegramLinkStatus.fromJson(Map<String, dynamic>.from(res.data));
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  Future<void> unlink() async {
    try {
      await _dio.post(ApiConstants.telegramUnlink);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }
}
