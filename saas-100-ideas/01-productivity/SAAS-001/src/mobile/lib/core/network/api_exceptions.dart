import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';

/// Base API exception
class ApiException implements Exception {
  final String message;
  final String? code;
  final int? statusCode;
  final Map<String, dynamic>? details;
  final String? requestId;

  ApiException({
    required this.message,
    this.code,
    this.statusCode,
    this.details,
    this.requestId,
  });

  @override
  String toString() {
    return 'ApiException($statusCode): $message [code: $code]';
  }
}

class UnauthorizedException extends ApiException {
  UnauthorizedException({
    String? message,
    String? requestId,
  }) : super(
          message: message ?? 'Unauthenticated. Please login again.',
          code: 'UNAUTHENTICATED',
          statusCode: 401,
          requestId: requestId,
        );
}

class ForbiddenException extends ApiException {
  ForbiddenException({
    String? message,
    String? requestId,
    Map<String, dynamic>? details,
  }) : super(
          message: message ?? 'You do not have permission.',
          code: 'FORBIDDEN',
          statusCode: 403,
          details: details,
          requestId: requestId,
        );
}

class NotFoundException extends ApiException {
  NotFoundException({
    String? message,
    String? requestId,
  }) : super(
          message: message ?? 'Resource not found.',
          code: 'NOT_FOUND',
          statusCode: 404,
          requestId: requestId,
        );
}

class ValidationException extends ApiException {
  ValidationException({
    String? message,
    Map<String, dynamic>? details,
    String? requestId,
  }) : super(
          message: message ?? 'Validation failed.',
          code: 'VALIDATION_ERROR',
          statusCode: 422,
          details: details,
          requestId: requestId,
        );
}

class ConflictException extends ApiException {
  ConflictException({
    String? message,
    String? requestId,
  }) : super(
          message: message ?? 'Resource conflict.',
          code: 'CONFLICT',
          statusCode: 409,
          requestId: requestId,
        );
}

class RateLimitException extends ApiException {
  final int retryAfterSeconds;

  RateLimitException({
    String? message,
    String? requestId,
    this.retryAfterSeconds = 60,
  }) : super(
          message: message ?? 'Too many requests. Please slow down.',
          code: 'RATE_LIMIT_EXCEEDED',
          statusCode: 429,
          requestId: requestId,
        );
}

class ServerException extends ApiException {
  ServerException({
    String? message,
    String? requestId,
  }) : super(
          message: message ?? 'An unexpected server error occurred.',
          code: 'SERVER_ERROR',
          statusCode: 500,
          requestId: requestId,
        );
}

class NetworkException extends ApiException {
  NetworkException({
    String? message,
  }) : super(
          message: message ?? 'Network error. Check your connection.',
          code: 'NETWORK_ERROR',
          statusCode: null,
        );
}

/// Parse DioException into typed ApiException
ApiException parseDioException(DioException error) {
  if (error.type == DioExceptionType.connectionTimeout ||
      error.type == DioExceptionType.receiveTimeout ||
      error.type == DioExceptionType.sendTimeout) {
    return NetworkException(message: 'Connection timed out.');
  }

  if (error.type == DioExceptionType.connectionError) {
    return NetworkException(message: 'No internet connection.');
  }

  if (error.response == null) {
    return NetworkException();
  }

  final response = error.response!;
  final statusCode = response.statusCode;
  final data = response.data is Map ? response.data as Map<String, dynamic> : null;
  final errorData = data?['error'] as Map<String, dynamic>?;

  final message = errorData?['message'] as String? ?? 'Unknown error';
  final code = errorData?['code'] as String?;
  final details = errorData?['details'] as Map<String, dynamic>?;
  final meta = errorData?['meta'] as Map<String, dynamic>?;
  final requestId = meta?['request_id'] as String?;

  if (kDebugMode) {
    debugPrint('Api Error [$statusCode]: $message (code: $code)');
  }

  final sc = statusCode ?? 0;
  switch (sc) {
    case 401:
      return UnauthorizedException(message: message, requestId: requestId);
    case 403:
      return ForbiddenException(
        message: message,
        requestId: requestId,
        details: details,
      );
    case 404:
      return NotFoundException(message: message, requestId: requestId);
    case 409:
      return ConflictException(message: message, requestId: requestId);
    case 422:
      return ValidationException(
        message: message,
        details: details,
        requestId: requestId,
      );
    case 429:
      final retryAfter = response.headers.value('Retry-After');
      return RateLimitException(
        message: message,
        requestId: requestId,
        retryAfterSeconds: retryAfter != null ? int.tryParse(retryAfter) ?? 60 : 60,
      );
    case >= 500:
      return ServerException(message: message, requestId: requestId);
    default:
      return ApiException(
        message: message,
        code: code,
        statusCode: sc,
        details: details,
        requestId: requestId,
      );
  }
}
