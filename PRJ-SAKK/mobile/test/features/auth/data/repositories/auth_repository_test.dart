/// Tests for AuthRepository — mock Dio + mock FlutterSecureStorage.
library;

import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mocktail/mocktail.dart';

import 'package:sakk_wallet/core/constants/api_constants.dart';
import 'package:sakk_wallet/core/network/api_client.dart';
import 'package:sakk_wallet/features/auth/data/models/user_model.dart';
import 'package:sakk_wallet/features/auth/data/repositories/auth_repository.dart';

import '../../../../helpers/mocks.dart';

class _MockAuthDio extends MockDio {}
class _MockAuthStorage extends MockFlutterSecureStorage {}

void main() {
  late Dio dio;
  late FlutterSecureStorage storage;
  late AuthRepository repository;

  setUp(() {
    dio = _MockAuthDio();
    storage = _MockAuthStorage();
    // Stub write/delete to return proper Future<void> (mock default is null).
    when(() => storage.write(
          key: any(named: 'key'),
          value: any(named: 'value'),
        )).thenAnswer((_) async {});
    when(() => storage.delete(key: any(named: 'key')))
        .thenAnswer((_) async {});
    repository = AuthRepository(dio, storage);
  });

  group('register', () {
    test('returns UserModel on success', () async {
      when(() => dio.post(
            any(),
            data: any(named: 'data'),
          )).thenAnswer((_) async => buildDioResponse({
            'data': {
              'token': 'token-abc',
              'user': testUserJson,
            },
          }, path: ApiConstants.register));

      final user = await repository.register(
        firstName: 'أحمد',
        lastName: 'السوري',
        email: 'ahmad@example.com',
        phone: '+963900000001',
        password: 'Pass123!',
        passwordConfirmation: 'Pass123!',
      );

      expect(user, isA<UserModel>());
      expect(user.id, 1);
      expect(user.fullName, 'أحمد السوري');
      verify(() => storage.write(key: 'auth_token', value: 'token-abc')).called(1);
    });

    test('throws ApiException on DioException', () async {
      when(() => dio.post(
            any(),
            data: any(named: 'data'),
          )).thenThrow(buildDioException(
        statusCode: 422,
        message: 'البريد الإلكتروني مستخدم بالفعل',
        path: ApiConstants.register,
      ));

      expect(
        () => repository.register(
          firstName: 'أحمد',
          lastName: 'السوري',
          email: 'used@example.com',
          phone: '+963900000001',
          password: 'Pass123!',
          passwordConfirmation: 'Pass123!',
        ),
        throwsA(isA<ApiException>()),
      );
    });
  });

  group('login', () {
    test('returns UserModel on success', () async {
      when(() => dio.post(
            any(),
            data: any(named: 'data'),
          )).thenAnswer((_) async => buildDioResponse({
            'data': {
              'token': 'token-xyz',
              'user': testUserJson,
            },
          }, path: ApiConstants.login));

      final user = await repository.login(
        email: 'ahmad@example.com',
        password: 'Pass123!',
      );

      expect(user.id, 1);
      verify(() => storage.write(key: 'auth_token', value: 'token-xyz')).called(1);
    });

    test('throws TwoFactorRequiredException when 2fa required', () async {
      when(() => dio.post(
            any(),
            data: any(named: 'data'),
          )).thenAnswer((_) async => buildDioResponse({
            'data': {
              'requires_2fa': true,
              'user_id': 1,
              'email': 'ahmad@example.com',
            },
          }, path: ApiConstants.login));

      expect(
        () => repository.login(
          email: 'ahmad@example.com',
          password: 'Pass123!',
        ),
        throwsA(isA<TwoFactorRequiredException>()),
      );
    });

    test('saves remember_me credentials when rememberMe=true', () async {
      when(() => dio.post(
            any(),
            data: any(named: 'data'),
          )).thenAnswer((_) async => buildDioResponse({
            'data': {
              'token': 'token-xyz',
              'user': testUserJson,
            },
          }, path: ApiConstants.login));

      await repository.login(
        email: 'ahmad@example.com',
        password: 'Pass123!',
        rememberMe: true,
      );

      verify(() => storage.write(key: 'remember_email', value: 'ahmad@example.com'))
          .called(1);
      verify(() => storage.write(key: 'remember_me', value: 'true')).called(1);
    });

    test('throws ApiException on DioException', () async {
      when(() => dio.post(
            any(),
            data: any(named: 'data'),
          )).thenThrow(buildDioException(
        statusCode: 401,
        message: 'Invalid credentials',
        path: ApiConstants.login,
      ));

      expect(
        () => repository.login(
          email: 'ahmad@example.com',
          password: 'wrong',
        ),
        throwsA(isA<ApiException>()),
      );
    });

    test('with 2FA code passes code in request body', () async {
      when(() => dio.post(
            any(),
            data: any(named: 'data'),
          )).thenAnswer((_) async => buildDioResponse({
            'data': {
              'token': 'token-xyz',
              'user': testUserJson,
            },
          }, path: ApiConstants.login));

      await repository.login(
        email: 'ahmad@example.com',
        password: 'Pass123!',
        twoFactorCode: '123456',
      );

      final captured = verify(() => dio.post(
            any(),
            data: captureAny(named: 'data'),
          )).captured.first as Map<String, dynamic>;
      expect(captured['two_factor_code'], '123456');
    });
  });

  group('forgotPassword', () {
    test('succeeds on 200', () async {
      when(() => dio.post(
            any(),
            data: any(named: 'data'),
          )).thenAnswer((_) async => buildDioResponse(
            {'message': 'Email sent'},
            path: ApiConstants.forgotPassword,
          ));

      await expectLater(
        repository.forgotPassword('ahmad@example.com'),
        completes,
      );
    });

    test('throws ApiException on error', () async {
      when(() => dio.post(
            any(),
            data: any(named: 'data'),
          )).thenThrow(buildDioException(
        statusCode: 404,
        message: 'Email not found',
        path: ApiConstants.forgotPassword,
      ));

      expect(
        () => repository.forgotPassword('notfound@example.com'),
        throwsA(isA<ApiException>()),
      );
    });
  });

  group('logout', () {
    test('clears auth_token and swallows API error', () async {
      when(() => dio.post(any())).thenThrow(Exception('Network error'));

      await repository.logout();

      verify(() => storage.delete(key: 'auth_token')).called(1);
    });

    test('clears auth_token on success', () async {
      when(() => dio.post(any())).thenAnswer((_) async =>
          buildDioResponse({'message': 'Logged out'}, path: ApiConstants.logout));

      await repository.logout();

      verify(() => storage.delete(key: 'auth_token')).called(1);
    });
  });

  group('getCurrentUser', () {
    test('returns UserModel', () async {
      when(() => dio.get(
            any(),
            options: any(named: 'options'),
          )).thenAnswer((_) async => buildDioResponse({
            'data': testUserJson,
          }, path: ApiConstants.me));

      final user = await repository.getCurrentUser();
      expect(user.id, 1);
    });

    test('passes skipAuthRedirect when silent=true', () async {
      when(() => dio.get(
            any(),
            options: any(named: 'options'),
          )).thenAnswer((_) async => buildDioResponse({
            'data': testUserJson,
          }, path: ApiConstants.me));

      await repository.getCurrentUser(silent: true);

      final captured = verify(() => dio.get(
            any(),
            options: captureAny(named: 'options'),
          )).captured.first as Options?;
      expect(captured?.extra?['skipAuthRedirect'], true);
    });

    test('throws ApiException on error', () async {
      when(() => dio.get(
            any(),
            options: any(named: 'options'),
          )).thenThrow(buildDioException(
        statusCode: 500,
        message: 'Server error',
        path: ApiConstants.me,
      ));

      expect(
        () => repository.getCurrentUser(),
        throwsA(isA<ApiException>()),
      );
    });
  });

  group('updateProfile', () {
    test('returns updated UserModel', () async {
      when(() => dio.put(
            any(),
            data: any(named: 'data'),
          )).thenAnswer((_) async => buildDioResponse({
            'data': testUserJson,
          }, path: ApiConstants.updateProfile));

      final user = await repository.updateProfile(firstName: 'محمد');
      expect(user, isA<UserModel>());
    });

    test('throws ApiException on error', () async {
      when(() => dio.put(
            any(),
            data: any(named: 'data'),
          )).thenThrow(buildDioException(
        statusCode: 422,
        message: 'Validation error',
        path: ApiConstants.updateProfile,
      ));

      expect(
        () => repository.updateProfile(firstName: ''),
        throwsA(isA<ApiException>()),
      );
    });
  });

  group('deleteAccount', () {
    test('wipes credentials on success', () async {
      when(() => dio.delete(
            any(),
            data: any(named: 'data'),
          )).thenAnswer((_) async => buildDioResponse(
            {'message': 'Deleted'},
            path: ApiConstants.deleteAccount,
          ));

      await repository.deleteAccount(password: 'Pass123!');

      verify(() => storage.delete(key: 'auth_token')).called(1);
      verify(() => storage.delete(key: 'remember_email')).called(1);
      // 🔒 Security fix TKT-011: remember_password no longer stored
      verify(() => storage.write(key: 'remember_me', value: 'false')).called(1);
    });

    test('throws ApiException on wrong password', () async {
      when(() => dio.delete(
            any(),
            data: any(named: 'data'),
          )).thenThrow(buildDioException(
        statusCode: 422,
        message: 'Wrong password',
        path: ApiConstants.deleteAccount,
      ));

      expect(
        () => repository.deleteAccount(password: 'wrong'),
        throwsA(isA<ApiException>()),
      );
    });
  });

  group('isAuthenticated / biometric / remember-me', () {
    test('isAuthenticated returns true when token exists', () async {
      when(() => storage.read(key: 'auth_token')).thenAnswer((_) async => 'token');

      final result = await repository.isAuthenticated();
      expect(result, true);
    });

    test('isAuthenticated returns false when no token', () async {
      when(() => storage.read(key: 'auth_token')).thenAnswer((_) async => null);

      final result = await repository.isAuthenticated();
      expect(result, false);
    });

    test('isBiometricEnabled returns true when enabled', () async {
      when(() => storage.read(key: 'biometric_enabled')).thenAnswer((_) async => 'true');

      final result = await repository.isBiometricEnabled();
      expect(result, true);
    });

    test('setBiometricEnabled writes to storage', () async {
      await repository.setBiometricEnabled(true);
      verify(() => storage.write(key: 'biometric_enabled', value: 'true')).called(1);
    });

    test('isRememberMeEnabled returns true', () async {
      when(() => storage.read(key: 'remember_me')).thenAnswer((_) async => 'true');

      expect(await repository.isRememberMeEnabled(), true);
    });

    test('getRememberedEmail returns null when remember_me off', () async {
      when(() => storage.read(key: 'remember_me')).thenAnswer((_) async => 'false');

      final email = await repository.getRememberedEmail();
      expect(email, isNull);
    });

    test('clearRememberMe deletes keys', () async {
      await repository.clearRememberMe();

      verify(() => storage.delete(key: 'remember_email')).called(1);
      verify(() => storage.write(key: 'remember_me', value: 'false')).called(1);
    });
  });

  group('two-factor methods', () {
    test('twoFactorSetup returns data', () async {
      when(() => dio.post(any())).thenAnswer((_) async => buildDioResponse({
            'data': {'secret': 'ABC123', 'qr': 'data:image/png;base64,...'},
          }, path: ApiConstants.twoFactorSetup));

      final data = await repository.twoFactorSetup();
      expect(data['secret'], 'ABC123');
    });

    test('twoFactorConfirm succeeds', () async {
      when(() => dio.post(
            any(),
            data: any(named: 'data'),
          )).thenAnswer((_) async =>
          buildDioResponse({'message': '2FA enabled'}, path: ApiConstants.twoFactorConfirm));

      await expectLater(repository.twoFactorConfirm('123456'), completes);
    });

    test('twoFactorStatus returns data', () async {
      when(() => dio.get(any())).thenAnswer((_) async => buildDioResponse({
            'data': {'enabled': true},
          }, path: ApiConstants.twoFactorStatus));

      final status = await repository.twoFactorStatus();
      expect(status['enabled'], true);
    });

    test('changePassword succeeds', () async {
      when(() => dio.put(
            any(),
            data: any(named: 'data'),
          )).thenAnswer((_) async =>
          buildDioResponse({'message': 'Changed'}, path: ApiConstants.changePassword));

      await expectLater(
        repository.changePassword(
          currentPassword: 'old',
          newPassword: 'New123!',
          newPasswordConfirmation: 'New123!',
        ),
        completes,
      );
    });

    test('twoFactorRecoveryCodes returns list', () async {
      when(() => dio.post(
            any(),
            data: any(named: 'data'),
          )).thenAnswer((_) async => buildDioResponse({
            'data': {
              'recovery_codes': ['code1', 'code2', 'code3'],
            },
          }, path: ApiConstants.twoFactorRecoveryCodes));

      final codes = await repository.twoFactorRecoveryCodes('Pass123!');
      expect(codes, hasLength(3));
      expect(codes.first, 'code1');
    });
  });

  group('deleteAvatar', () {
    test('succeeds', () async {
      when(() => dio.delete(any())).thenAnswer((_) async =>
          buildDioResponse({'message': 'Deleted'}, path: ApiConstants.deleteAvatar));

      await expectLater(repository.deleteAvatar(), completes);
    });
  });
}
