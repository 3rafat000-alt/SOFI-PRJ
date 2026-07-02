import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/network/api_client.dart';
import '../../../../core/constants/api_constants.dart';
import '../../../../core/services/fcm_service.dart';
import '../models/user_model.dart';

final authRepositoryProvider = Provider<AuthRepository>((ref) {
  return AuthRepository(ref.read(dioProvider), ref.read(secureStorageProvider));
});

final currentUserProvider = StateProvider<UserModel?>((ref) => null);

class AuthRepository {
  final Dio _dio;
  final dynamic _storage;
  
  AuthRepository(this._dio, this._storage);
  
  Future<UserModel> register({
    required String firstName,
    required String lastName,
    required String email,
    required String phone,
    required String password,
    required String passwordConfirmation,
    String? dateOfBirth,
    String? gender,
    String? occupation,
    String? referralCode,
  }) async {
    try {
      final fcmToken = FCMService.instance.token;
      final response = await _dio.post(ApiConstants.register, data: {
        'first_name': firstName,
        'last_name': lastName,
        'email': email,
        'phone': phone,
        'password': password,
        'password_confirmation': passwordConfirmation,
        if (dateOfBirth != null) 'date_of_birth': dateOfBirth,
        if (gender != null) 'gender': gender,
        if (occupation != null) 'occupation': occupation,
        if (referralCode != null) 'referral_code': referralCode,
        if (fcmToken != null) 'fcm_token': fcmToken,
      });
      
      final data = response.data['data'];
      final token = data['token'];
      final user = UserModel.fromJson(data['user']);
      
      await _storage.write(key: 'auth_token', value: token);
      
      return user;
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }
  
  /// Returns [UserModel] on success, or throws [TwoFactorRequiredException]
  /// when the server responds with `requires_2fa: true`.
  Future<UserModel> login({
    required String email,
    required String password,
    bool rememberMe = false,
    String? twoFactorCode,
  }) async {
    try {
      final fcmToken = FCMService.instance.token;
      final response = await _dio.post(ApiConstants.login, data: {
        'email': email,
        'password': password,
        if (twoFactorCode != null) 'two_factor_code': twoFactorCode,
        if (fcmToken != null) 'fcm_token': fcmToken,
      });
      
      final data = response.data['data'];

      // Server asks for 2FA code — throw a dedicated exception so the UI can catch it.
      if (data is Map && data['requires_2fa'] == true) {
        throw TwoFactorRequiredException(
          userId: data['user_id'] as int?,
          email: data['email'] as String? ?? email,
        );
      }

      final token = data['token'];
      final user = UserModel.fromJson(data['user']);
      
      await _storage.write(key: 'auth_token', value: token);
      
      if (rememberMe) {
        await _storage.write(key: 'remember_email', value: email);
        // 🔒 FIXED: Never persist passwords in storage (even secure storage).
        // Use a server-issued refresh token for long-lived sessions instead.
        // The 'remember_me' flow should exchange a refresh token, not the password.
        await _storage.write(key: 'remember_me', value: 'true');
      } else {
        await _storage.delete(key: 'remember_email');
        await _storage.delete(key: 'remember_me', value: 'false');
      }
      
      return user;
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }
  
  Future<void> forgotPassword(String email) async {
    try {
      await _dio.post(ApiConstants.forgotPassword, data: {
        'email': email,
      });
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }
  
  Future<void> logout() async {
    try {
      await _dio.post(ApiConstants.logout);
    } catch (_) {
      // Ignore errors on logout
    } finally {
      await _storage.delete(key: 'auth_token');
      // Note: We don't clear remember me credentials on logout
      // so the user can auto-login next time. They can clear it manually in settings.
    }
  }
  
  /// Fetch the current user. Pass [silent] for background refreshes so a
  /// transient 401 does not force a logout via the auth interceptor.
  Future<UserModel> getCurrentUser({bool silent = false}) async {
    try {
      final response = await _dio.get(
        ApiConstants.me,
        options: silent ? Options(extra: {'skipAuthRedirect': true}) : null,
      );
      return UserModel.fromJson(response.data['data']);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }
  
  Future<UserModel> updateProfile({
    String? firstName,
    String? lastName,
    String? phone,
    String? dateOfBirth,
    String? gender,
  }) async {
    try {
      final response = await _dio.put(ApiConstants.updateProfile, data: {
        if (firstName != null) 'first_name': firstName,
        if (lastName != null) 'last_name': lastName,
        if (phone != null) 'phone': phone,
        if (dateOfBirth != null) 'date_of_birth': dateOfBirth,
        if (gender != null) 'gender': gender,
      });
      return UserModel.fromJson(response.data['data']);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }
  
  Future<void> deleteAvatar() async {
    try {
      await _dio.delete(ApiConstants.deleteAvatar);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  /// Permanently disable & delete the current account. Requires the account
  /// password for confirmation. Only on a successful deletion are all local
  /// credentials wiped, so a failed attempt (e.g. wrong password) keeps the
  /// user logged in.
  Future<void> deleteAccount({
    required String password,
    String? reason,
  }) async {
    try {
      await _dio.delete(ApiConstants.deleteAccount, data: {
        'password': password,
        if (reason != null && reason.trim().isNotEmpty) 'reason': reason.trim(),
      });
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }

    // Success — wipe every local credential so the deleted account can never
    // auto-login again.
    await _storage.delete(key: 'auth_token');
    await clearRememberMe();
  }

  Future<List<String>> twoFactorRecoveryCodes(String password) async {
    try {
      final response = await _dio.post(ApiConstants.twoFactorRecoveryCodes, data: {
        'password': password,
      });
      final codes = response.data['data']?['recovery_codes'];
      return codes is List ? codes.map((e) => e.toString()).toList() : <String>[];
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  Future<void> changePassword({
    required String currentPassword,
    required String newPassword,
    required String newPasswordConfirmation,
  }) async {
    try {
      await _dio.put(ApiConstants.changePassword, data: {
        'current_password': currentPassword,
        'password': newPassword,
        'password_confirmation': newPasswordConfirmation,
      });
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }
  
  Future<bool> isAuthenticated() async {
    final token = await _storage.read(key: 'auth_token');
    return token != null;
  }
  
  Future<Map<String, dynamic>> twoFactorSetup() async {
    try {
      final response = await _dio.post(ApiConstants.twoFactorSetup);
      return response.data['data'];
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }
  
  Future<void> twoFactorConfirm(String code) async {
    try {
      await _dio.post(ApiConstants.twoFactorConfirm, data: {
        'code': code,
      });
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }
  
  Future<void> twoFactorDisable({required String password, required String code}) async {
    try {
      await _dio.post(ApiConstants.twoFactorDisable, data: {
        'password': password,
        'code': code,
      });
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }
  
  Future<Map<String, dynamic>> twoFactorStatus() async {
    try {
      final response = await _dio.get(ApiConstants.twoFactorStatus);
      return response.data['data'];
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }
  
  Future<bool> isBiometricEnabled() async {
    final value = await _storage.read(key: 'biometric_enabled');
    return value == 'true';
  }

  Future<void> setBiometricEnabled(bool enabled) async {
    await _storage.write(key: 'biometric_enabled', value: enabled.toString());
  }

  // Remember Me — stores email only. Password is NEVER persisted.
  // Auto-login uses a server-issued refresh token (future work).
  Future<bool> isRememberMeEnabled() async {
    final value = await _storage.read(key: 'remember_me');
    return value == 'true';
  }

  Future<String?> getRememberedEmail() async {
    final rememberMe = await isRememberMeEnabled();
    if (!rememberMe) return null;
    return await _storage.read(key: 'remember_email');
  }

  Future<void> clearRememberMe() async {
    await _storage.delete(key: 'remember_email');
    await _storage.write(key: 'remember_me', value: 'false');
  }
}
