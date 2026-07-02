import 'package:dio/dio.dart';
import '../../../../core/network/dio_client.dart';
import '../../../../core/network/api_endpoints.dart';
import '../models/auth_models.dart';

class AuthRemoteSource {
  final DioClient _client;

  AuthRemoteSource(this._client);

  Future<AuthResponseDTO> login(LoginRequest request) async {
    final response = await _client.post(
      ApiEndpoints.login,
      data: request.toJson(),
    );
    return AuthResponseDTO.fromJson(response.data as Map<String, dynamic>);
  }

  Future<AuthResponseDTO> register(RegisterRequest request) async {
    final response = await _client.post(
      ApiEndpoints.register,
      data: request.toJson(),
    );
    return AuthResponseDTO.fromJson(response.data as Map<String, dynamic>);
  }

  Future<void> logout() async {
    await _client.post(ApiEndpoints.logout);
  }

  Future<UserDTO> getCurrentUser() async {
    final response = await _client.get(ApiEndpoints.me);
    final data = response.data as Map<String, dynamic>;
    final userData = data['data'] as Map<String, dynamic>;
    return UserDTO.fromJson(userData);
  }

  Future<MessageResponseDTO> forgotPassword(ForgotPasswordRequest request) async {
    final response = await _client.post(
      ApiEndpoints.forgotPassword,
      data: request.toJson(),
    );
    return MessageResponseDTO.fromJson(response.data as Map<String, dynamic>);
  }

  Future<MessageResponseDTO> resetPassword(ResetPasswordRequest request) async {
    final response = await _client.post(
      ApiEndpoints.resetPassword,
      data: request.toJson(),
    );
    return MessageResponseDTO.fromJson(response.data as Map<String, dynamic>);
  }
}
