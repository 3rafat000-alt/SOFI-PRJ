import 'dart:io';
import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/network/api_client.dart';
import '../../../../core/constants/api_constants.dart';
import '../models/company_models.dart';

final companyRepositoryProvider = Provider<CompanyRepository>((ref) {
  return CompanyRepository(ref.read(dioProvider));
});

/// The current user's company application state (application + doc types).
final companyStateProvider = FutureProvider<CompanyState>((ref) async {
  return ref.read(companyRepositoryProvider).getApplication();
});

class CompanyRepository {
  final Dio _dio;

  CompanyRepository(this._dio);

  Future<CompanyState> getApplication() async {
    try {
      final response = await _dio.get(ApiConstants.companyApplication);
      return CompanyState.fromJson(response.data['data'] as Map<String, dynamic>);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  Future<void> apply({
    required String name,
    String? legalName,
    String? ownerName,
    String? phone,
    String? email,
    String? taxId,
    String? commercialRegister,
    String? address,
    String? city,
    String? governorate,
  }) async {
    try {
      await _dio.post(ApiConstants.companyApply, data: {
        'name': name,
        if (legalName != null) 'legal_name': legalName,
        if (ownerName != null) 'owner_name': ownerName,
        if (phone != null) 'phone': phone,
        if (email != null) 'email': email,
        if (taxId != null) 'tax_id': taxId,
        if (commercialRegister != null) 'commercial_register': commercialRegister,
        if (address != null) 'address': address,
        if (city != null) 'city': city,
        if (governorate != null) 'governorate': governorate,
      });
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  Future<void> uploadDocument({
    required String documentType,
    required File file,
    String? documentNumber,
  }) async {
    try {
      final formData = FormData.fromMap({
        'document_type': documentType,
        if (documentNumber != null) 'document_number': documentNumber,
        'file': await MultipartFile.fromFile(file.path,
            filename: file.path.split('/').last),
      });
      await _dio.post(
        ApiConstants.companyDocuments,
        data: formData,
        options: Options(contentType: 'multipart/form-data'),
      );
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }
}
