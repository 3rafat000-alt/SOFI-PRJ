import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import '../constants/app_strings.dart';
import 'api_endpoints.dart';
import 'api_exceptions.dart';

class DioClient {
  late final Dio dio;
  final FlutterSecureStorage _secureStorage;
  String? _locale;

  DioClient({
    required FlutterSecureStorage secureStorage,
    String? baseUrl,
    String? locale,
  }) : _secureStorage = secureStorage,
       _locale = locale {
    dio = Dio(
      BaseOptions(
        baseUrl: baseUrl ?? ApiEndpoints.baseUrlDev,
        connectTimeout: const Duration(seconds: 15),
        receiveTimeout: const Duration(seconds: 15),
        sendTimeout: const Duration(seconds: 15),
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'Accept-Language': _locale ?? 'ar',
          'X-Timezone': 'Asia/Riyadh',
        },
      ),
    );

    dio.interceptors.addAll([
      _AuthInterceptor(_secureStorage),
      _LocaleInterceptor(() => _locale),
      _LoggingInterceptor(),
    ]);
  }

  void setLocale(String locale) {
    _locale = locale;
    dio.options.headers['Accept-Language'] = locale;
  }

  // GET
  Future<Response> get(
    String path, {
    Map<String, dynamic>? queryParameters,
    Options? options,
    CancelToken? cancelToken,
  }) async {
    try {
      final response = await dio.get(
        path,
        queryParameters: queryParameters,
        options: options,
        cancelToken: cancelToken,
      );
      return response;
    } on DioException catch (e) {
      throw parseDioException(e);
    }
  }

  // POST
  Future<Response> post(
    String path, {
    dynamic data,
    Map<String, dynamic>? queryParameters,
    Options? options,
    CancelToken? cancelToken,
  }) async {
    try {
      final response = await dio.post(
        path,
        data: data,
        queryParameters: queryParameters,
        options: options,
        cancelToken: cancelToken,
      );
      return response;
    } on DioException catch (e) {
      throw parseDioException(e);
    }
  }

  // PUT
  Future<Response> put(
    String path, {
    dynamic data,
    Map<String, dynamic>? queryParameters,
    Options? options,
    CancelToken? cancelToken,
  }) async {
    try {
      final response = await dio.put(
        path,
        data: data,
        queryParameters: queryParameters,
        options: options,
        cancelToken: cancelToken,
      );
      return response;
    } on DioException catch (e) {
      throw parseDioException(e);
    }
  }

  // PATCH
  Future<Response> patch(
    String path, {
    dynamic data,
    Map<String, dynamic>? queryParameters,
    Options? options,
    CancelToken? cancelToken,
  }) async {
    try {
      final response = await dio.patch(
        path,
        data: data,
        queryParameters: queryParameters,
        options: options,
        cancelToken: cancelToken,
      );
      return response;
    } on DioException catch (e) {
      throw parseDioException(e);
    }
  }

  // DELETE
  Future<Response> delete(
    String path, {
    dynamic data,
    Map<String, dynamic>? queryParameters,
    Options? options,
    CancelToken? cancelToken,
  }) async {
    try {
      final response = await dio.delete(
        path,
        data: data,
        queryParameters: queryParameters,
        options: options,
        cancelToken: cancelToken,
      );
      return response;
    } on DioException catch (e) {
      throw parseDioException(e);
    }
  }

  // Multipart upload
  Future<Response> uploadFile(
    String path, {
    required String filePath,
    String? fieldName,
    Map<String, dynamic>? extraFields,
    CancelToken? cancelToken,
    void Function(int, int)? onSendProgress,
  }) async {
    try {
      final formData = FormData.fromMap({
        fieldName ?? 'file': await MultipartFile.fromFile(filePath),
        if (extraFields != null) ...extraFields,
      });
      final response = await dio.post(
        path,
        data: formData,
        cancelToken: cancelToken,
        onSendProgress: onSendProgress,
        options: Options(
          contentType: 'multipart/form-data',
        ),
      );
      return response;
    } on DioException catch (e) {
      throw parseDioException(e);
    }
  }
}

/// Injects Bearer token from secure storage
class _AuthInterceptor extends Interceptor {
  final FlutterSecureStorage _secureStorage;

  _AuthInterceptor(this._secureStorage);

  @override
  void onRequest(
    RequestOptions options,
    RequestInterceptorHandler handler,
  ) async {
    // Skip auth header for auth endpoints
    if (options.path.contains('/auth/') &&
        !options.path.contains('/auth/me')) {
      return handler.next(options);
    }

    try {
      final token = await _secureStorage.read(key: 'auth_token');
      if (token != null && token.isNotEmpty) {
        options.headers['Authorization'] = 'Bearer $token';
      }
    } catch (_) {
      // Storage error, proceed without token
    }

    return handler.next(options);
  }

  @override
  void onError(DioException err, ErrorInterceptorHandler handler) async {
    if (err.response?.statusCode == 401) {
      // Token expired - clear storage
      await _secureStorage.delete(key: 'auth_token');
      await _secureStorage.delete(key: 'user_data');
    }
    return handler.next(err);
  }
}

/// Sets locale header on each request
class _LocaleInterceptor extends Interceptor {
  final String? Function() _localeProvider;

  _LocaleInterceptor(this._localeProvider);

  @override
  void onRequest(
    RequestOptions options,
    RequestInterceptorHandler handler,
  ) {
    final locale = _localeProvider();
    if (locale != null) {
      options.headers['Accept-Language'] = locale;
    }
    return handler.next(options);
  }
}

/// Debug logging
class _LoggingInterceptor extends Interceptor {
  @override
  void onRequest(
    RequestOptions options,
    RequestInterceptorHandler handler,
  ) {
    if (kDebugMode) {
      debugPrint('🌐 [DIO] ${options.method} ${options.path}');
      if (options.data != null) {
        debugPrint('📦 Body: ${options.data}');
      }
    }
    return handler.next(options);
  }

  @override
  void onResponse(
    Response response,
    ResponseInterceptorHandler handler,
  ) {
    if (kDebugMode) {
      debugPrint('✅ [DIO] ${response.statusCode} ${response.requestOptions.path}');
    }
    return handler.next(response);
  }

  @override
  void onError(DioException err, ErrorInterceptorHandler handler) {
    if (kDebugMode) {
      debugPrint('❌ [DIO] ${err.response?.statusCode} ${err.requestOptions.path}: ${err.message}');
    }
    return handler.next(err);
  }
}
