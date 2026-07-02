import 'dart:io';
import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/network/api_client.dart';
import '../../../../core/constants/api_constants.dart';

final kycRepositoryProvider = Provider<KycRepository>((ref) {
  return KycRepository(ref.read(dioProvider));
});

final kycStatusProvider = FutureProvider<Map<String, dynamic>>((ref) async {
  return ref.read(kycRepositoryProvider).getKycStatus();
});

final kycLevelsProvider = FutureProvider<List<Map<String, dynamic>>>((ref) async {
  return ref.read(kycRepositoryProvider).getKycLevels();
});

/// KYC repository — 2-level system (unverified → verified).
/// Verification requires: email + phone + id_document + selfie.
class KycRepository {
  final Dio _dio;

  KycRepository(this._dio);

  Map<String, dynamic> _map(Response res) =>
      (res.data is Map) ? Map<String, dynamic>.from(res.data as Map) : <String, dynamic>{};

  /// User's current KYC status (level, limits, verifications, missing reqs).
  Future<Map<String, dynamic>> getKycStatus() async {
    try {
      final response = await _dio.get(ApiConstants.kycStatus);
      return Map<String, dynamic>.from(response.data['data'] as Map);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  /// All KYC levels with dual-currency limits.
  Future<List<Map<String, dynamic>>> getKycLevels() async {
    try {
      final response = await _dio.get(ApiConstants.kycLevels);
      return List<Map<String, dynamic>>.from(response.data['data']);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  // ──────────── Email ────────────

  Future<void> sendEmailCode() async {
    try {
      await _dio.post(ApiConstants.kycEmailSend);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  Future<Map<String, dynamic>> verifyEmailCode(String code) async {
    try {
      final response = await _dio.post(ApiConstants.kycEmailVerify, data: {'code': code});
      return _map(response);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  // ──────────── Phone ────────────

  Future<void> updatePhone(String phone) async {
    try {
      await _dio.post(ApiConstants.kycPhoneUpdate, data: {'phone': phone});
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  Future<void> sendPhoneCode() async {
    try {
      await _dio.post(ApiConstants.kycPhoneSend);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  Future<Map<String, dynamic>> verifyPhoneCode(String code) async {
    try {
      final response = await _dio.post(ApiConstants.kycPhoneVerify, data: {'code': code});
      return _map(response);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  // ──────────── Documents ────────────

  Future<Map<String, dynamic>> submitIdDocument({
    required File frontImage,
    required String documentType,
    String? documentNumber,
    File? backImage,
  }) async {
    try {
      final formData = FormData.fromMap({
        'document_type': documentType,
        if (documentNumber != null) 'document_number': documentNumber,
        'front_image': await MultipartFile.fromFile(frontImage.path, filename: 'id_front.jpg'),
        if (backImage != null)
          'back_image': await MultipartFile.fromFile(backImage.path, filename: 'id_back.jpg'),
      });

      final response = await _dio.post(
        ApiConstants.kycIdDocument,
        data: formData,
        options: Options(contentType: 'multipart/form-data'),
      );
      return _map(response);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  Future<Map<String, dynamic>> submitSelfie({required File selfieImage}) async {
    try {
      final formData = FormData.fromMap({
        'selfie': await MultipartFile.fromFile(selfieImage.path, filename: 'selfie.jpg'),
      });

      final response = await _dio.post(
        ApiConstants.kycSelfie,
        data: formData,
        options: Options(contentType: 'multipart/form-data'),
      );
      return _map(response);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  /// Verification submission history.
  Future<List<Map<String, dynamic>>> getVerificationHistory() async {
    try {
      final response = await _dio.get(ApiConstants.kycSubmissions);
      return List<Map<String, dynamic>>.from(response.data['data']);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }
}
