import 'dart:io';
import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/network/api_client.dart';
import '../../../../core/constants/api_constants.dart';
import '../models/partner_models.dart';

final partnerRepositoryProvider = Provider<PartnerRepository>((ref) {
  return PartnerRepository(ref.read(dioProvider));
});

/// The current user's partner application state (agent + merchant + doc types).
final partnerStateProvider = FutureProvider<PartnerState>((ref) async {
  return ref.read(partnerRepositoryProvider).getApplication();
});

class PartnerRepository {
  final Dio _dio;

  PartnerRepository(this._dio);

  Future<PartnerState> getApplication() async {
    try {
      final response = await _dio.get(ApiConstants.partnerApplication);
      return PartnerState.fromJson(response.data['data'] as Map<String, dynamic>);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  Future<void> applyAsAgent({
    required String name,
    required String phone,
    required String address,
    required String city,
    String? ownerName,
    String? governorate,
    List<String>? services,
    String? workingHours,
    double? latitude,
    double? longitude,
  }) async {
    try {
      await _dio.post(ApiConstants.partnerApply, data: {
        'type': 'agent',
        'name': name,
        'phone': phone,
        'address': address,
        'city': city,
        if (ownerName != null) 'owner_name': ownerName,
        if (governorate != null) 'governorate': governorate,
        if (services != null) 'services': services,
        if (workingHours != null) 'working_hours': workingHours,
        if (latitude != null) 'latitude': latitude,
        if (longitude != null) 'longitude': longitude,
      });
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  Future<void> applyAsMerchant({
    required String storeName,
    required String storeType, // physical | ecommerce | both
    required String phone,
    String? ownerName,
    String? email,
    String? description,
    String? address,
    String? city,
    String? governorate,
    String? websiteUrl,
  }) async {
    try {
      await _dio.post(ApiConstants.partnerApply, data: {
        'type': 'merchant',
        'store_name': storeName,
        'store_type': storeType,
        'phone': phone,
        if (ownerName != null) 'owner_name': ownerName,
        if (email != null) 'email': email,
        if (description != null) 'description': description,
        if (address != null) 'address': address,
        if (city != null) 'city': city,
        if (governorate != null) 'governorate': governorate,
        if (websiteUrl != null) 'website_url': websiteUrl,
      });
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  Future<void> uploadDocument({
    required String type, // agent | merchant
    required String documentType,
    required File file,
    String? documentNumber,
  }) async {
    try {
      final formData = FormData.fromMap({
        'type': type,
        'document_type': documentType,
        if (documentNumber != null) 'document_number': documentNumber,
        'file': await MultipartFile.fromFile(file.path,
            filename: file.path.split('/').last),
      });
      await _dio.post(
        ApiConstants.partnerDocuments,
        data: formData,
        options: Options(contentType: 'multipart/form-data'),
      );
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }
}
