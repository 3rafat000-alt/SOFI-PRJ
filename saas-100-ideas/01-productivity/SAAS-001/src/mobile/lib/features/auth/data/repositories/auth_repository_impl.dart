import 'package:dartz/dartz.dart';
import 'package:flutter/foundation.dart';
import '../../../../core/network/api_exceptions.dart';
import '../../../../core/storage/secure_storage.dart';
import '../../domain/entities/user.dart';
import '../../domain/entities/auth_response.dart';
import '../../domain/repositories/auth_repository.dart';
import '../datasources/auth_remote_source.dart';
import '../models/auth_models.dart';

class AuthRepositoryImpl implements AuthRepository {
  final AuthRemoteSource _remoteSource;
  final SecureStorageService _storage;

  AuthRepositoryImpl(this._remoteSource, this._storage);

  @override
  Future<Either<Exception, AuthResponse>> login({
    required String email,
    required String password,
  }) async {
    try {
      final request = LoginRequest(email: email, password: password);
      final dto = await _remoteSource.login(request);

      // Store token and user data
      await _storage.saveToken(dto.token);
      await _storage.saveUserData(dto.user.toJson());
      if (dto.user.currentWorkspaceId != null) {
        await _storage.saveCurrentWorkspaceId(dto.user.currentWorkspaceId!);
      }

      final response = _mapToDomain(dto);
      return Right(response);
    } on ApiException catch (e) {
      return Left(e);
    } catch (e) {
      if (kDebugMode) debugPrint('Login error: $e');
      return Left(Exception('Login failed: $e'));
    }
  }

  @override
  Future<Either<Exception, AuthResponse>> register({
    required String name,
    required String email,
    required String password,
    required String passwordConfirmation,
    required String workspaceName,
    String locale = 'ar',
    String timezone = 'Asia/Riyadh',
  }) async {
    try {
      final request = RegisterRequest(
        name: name,
        email: email,
        password: password,
        passwordConfirmation: passwordConfirmation,
        workspaceName: workspaceName,
        locale: locale,
        timezone: timezone,
      );
      final dto = await _remoteSource.register(request);

      await _storage.saveToken(dto.token);
      await _storage.saveUserData(dto.user.toJson());
      if (dto.user.currentWorkspaceId != null) {
        await _storage.saveCurrentWorkspaceId(dto.user.currentWorkspaceId!);
      }

      final response = _mapToDomain(dto);
      return Right(response);
    } on ApiException catch (e) {
      return Left(e);
    } catch (e) {
      if (kDebugMode) debugPrint('Register error: $e');
      return Left(Exception('Registration failed: $e'));
    }
  }

  @override
  Future<Either<Exception, void>> logout() async {
    try {
      await _remoteSource.logout();
    } catch (_) {
      // Even if remote logout fails, clear local state
    }
    await _storage.deleteToken();
    await _storage.deleteUserData();
    await _storage.delete('current_workspace_id');
    return const Right(null);
  }

  @override
  Future<Either<Exception, User>> getCurrentUser() async {
    try {
      final dto = await _remoteSource.getCurrentUser();
      final user = _mapUser(dto);
      await _storage.saveUserData(dto.toJson());
      return Right(user);
    } on ApiException catch (e) {
      // Try local cache
      final cached = await _storage.getUserData();
      if (cached != null) {
        final userDto = UserDTO.fromJson(cached);
        return Right(_mapUser(userDto));
      }
      return Left(e);
    } catch (e) {
      final cached = await _storage.getUserData();
      if (cached != null) {
        final userDto = UserDTO.fromJson(cached);
        return Right(_mapUser(userDto));
      }
      return Left(Exception('Failed to get user: $e'));
    }
  }

  @override
  Future<Either<Exception, String>> forgotPassword(String email) async {
    try {
      final request = ForgotPasswordRequest(email: email);
      final dto = await _remoteSource.forgotPassword(request);
      return Right(dto.message);
    } on ApiException catch (e) {
      return Left(e);
    } catch (e) {
      return Left(Exception('Failed to send reset email: $e'));
    }
  }

  @override
  Future<Either<Exception, void>> resetPassword({
    required String token,
    required String email,
    required String password,
    required String passwordConfirmation,
  }) async {
    try {
      final request = ResetPasswordRequest(
        token: token,
        email: email,
        password: password,
        passwordConfirmation: passwordConfirmation,
      );
      await _remoteSource.resetPassword(request);
      return const Right(null);
    } on ApiException catch (e) {
      return Left(e);
    } catch (e) {
      return Left(Exception('Failed to reset password: $e'));
    }
  }

  AuthResponse _mapToDomain(AuthResponseDTO dto) {
    return AuthResponse(
      user: _mapUser(dto.user),
      token: dto.token,
      workspace: dto.workspace != null
          ? WorkspaceSummary(
              id: dto.workspace!.id,
              name: dto.workspace!.name,
              slug: dto.workspace!.slug,
              role: dto.workspace!.role,
              memberCount: dto.workspace!.memberCount,
              plan: dto.workspace!.plan,
            )
          : null,
      workspaces: dto.workspaces
          ?.map((w) => WorkspaceSummary(
                id: w.id,
                name: w.name,
                slug: w.slug,
                role: w.role,
                memberCount: w.memberCount,
                plan: w.plan,
              ))
          .toList(),
    );
  }

  User _mapUser(UserDTO dto) {
    return User(
      id: dto.id,
      name: dto.name,
      email: dto.email,
      avatarUrl: dto.avatarUrl,
      locale: dto.locale,
      timezone: dto.timezone,
      currentWorkspaceId: dto.currentWorkspaceId,
      createdAt: dto.createdAt != null ? DateTime.parse(dto.createdAt!) : DateTime.now(),
    );
  }
}
