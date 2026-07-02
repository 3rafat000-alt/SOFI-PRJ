import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart' show kDebugMode;
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

import '../constants/api_constants.dart';
import '../router/app_router.dart';
import '../services/device_service.dart';

final dioProvider = Provider<Dio>((ref) {
  final dio = Dio(BaseOptions(
    baseUrl: ApiConstants.baseUrl,
    connectTimeout: const Duration(seconds: 30),
    receiveTimeout: const Duration(seconds: 30),
    headers: {
      'Accept': 'application/json',
    },
  ));
  
  dio.interceptors.add(AuthInterceptor(ref));
  // 🔒 SEC-007: لا تُسجّل الأجسام (توكِنات/PIN/بيانات دفع) إلا في وضع التطوير
  if (kDebugMode) {
    dio.interceptors.add(LogInterceptor(
      requestBody: true,
      responseBody: true,
      error: true,
    ));
  }
  
  return dio;
});

final secureStorageProvider = Provider<FlutterSecureStorage>((ref) {
  return const FlutterSecureStorage(
    aOptions: AndroidOptions(encryptedSharedPreferences: true),
    iOptions: IOSOptions(accessibility: KeychainAccessibility.first_unlock),
  );
});

class AuthInterceptor extends Interceptor {
  final Ref _ref;
  
  AuthInterceptor(this._ref);
  
  @override
  void onRequest(RequestOptions options, RequestInterceptorHandler handler) async {
    final storage = _ref.read(secureStorageProvider);
    final token = await storage.read(key: 'auth_token');
    
    if (token != null) {
      options.headers['Authorization'] = 'Bearer $token';
    }

    // Identify the device for the connected-devices security feature.
    // NOTE: only the ASCII UUID goes in a header — HTTP header values must be
    // Latin-1, so the (Arabic) device name is sent only in the register body.
    try {
      options.headers['X-Device-Id'] = await DeviceService.getDeviceId();
    } catch (_) {/* non-fatal */}

    handler.next(options);
  }
  
  @override
  void onError(DioException err, ErrorInterceptorHandler handler) async {
    // Background/refresh calls (e.g. silent /me sync) opt out of the forced
    // logout so a single transient 401 never kicks the user out mid-session.
    final skipRedirect = err.requestOptions.extra['skipAuthRedirect'] == true;

    if (err.response?.statusCode == 401 && !skipRedirect) {
      final storage = _ref.read(secureStorageProvider);
      await storage.delete(key: 'auth_token');
      try {
        _ref.read(appRouterProvider).go('/login');
      } catch (_) {}
    }

    handler.next(err);
  }
}

class ApiResponse<T> {
  final bool success;
  final T? data;
  final String? message;
  final Map<String, List<String>>? errors;
  
  ApiResponse({
    required this.success,
    this.data,
    this.message,
    this.errors,
  });
  
  factory ApiResponse.fromJson(
    Map<String, dynamic> json,
    T Function(dynamic)? fromJson,
  ) {
    return ApiResponse(
      success: json['success'] ?? true,
      data: json['data'] != null && fromJson != null 
          ? fromJson(json['data']) 
          : json['data'],
      message: json['message'],
      errors: json['errors'] != null 
          ? Map<String, List<String>>.from(
              json['errors'].map((k, v) => MapEntry(k, List<String>.from(v))),
            )
          : null,
    );
  }
}

/// Thrown when login response indicates the user must provide a 2FA code.
class TwoFactorRequiredException implements Exception {
  final int? userId;
  final String email;

  TwoFactorRequiredException({this.userId, required this.email});

  @override
  String toString() => 'مطلوب رمز التحقق الثنائي (2FA)';
}

class ApiException implements Exception {
  final String message;
  final int? statusCode;
  final Map<String, List<String>>? errors;
  
  ApiException({
    required this.message,
    this.statusCode,
    this.errors,
  });
  
  factory ApiException.fromDioError(DioException error) {
    String message = 'حدث خطأ غير متوقع';
    int? statusCode = error.response?.statusCode;
    Map<String, List<String>>? errors;
    
    if (error.response?.data != null && error.response!.data is Map) {
      final data = error.response!.data as Map<String, dynamic>;
      message = data['message'] ?? message;
      if (data['errors'] != null) {
        errors = Map<String, List<String>>.from(
          data['errors'].map((k, v) => MapEntry(k, List<String>.from(v))),
        );
      }
    } else {
      switch (error.type) {
        case DioExceptionType.connectionTimeout:
        case DioExceptionType.sendTimeout:
        case DioExceptionType.receiveTimeout:
          message = 'انتهت مهلة الاتصال';
          break;
        case DioExceptionType.connectionError:
          message = 'لا يوجد اتصال بالإنترنت';
          break;
        case DioExceptionType.cancel:
          message = 'تم إلغاء الطلب';
          break;
        default:
          message = error.message ?? message;
      }
    }
    
    return ApiException(
      message: message,
      statusCode: statusCode,
      errors: errors,
    );
  }
  
  @override
  String toString() => message;
}
