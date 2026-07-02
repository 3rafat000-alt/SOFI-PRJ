import 'package:flutter_test/flutter_test.dart';
import 'package:mocktail/mocktail.dart';
import 'package:dartz/dartz.dart';
import 'package:bloc_test/bloc_test.dart';
import 'package:tasksync_pro/features/auth/domain/entities/user.dart';
import 'package:tasksync_pro/features/auth/domain/entities/auth_response.dart';
import 'package:tasksync_pro/features/auth/domain/usecases/login_usecase.dart';
import 'package:tasksync_pro/features/auth/domain/usecases/register_usecase.dart';
import 'package:tasksync_pro/features/auth/presentation/bloc/auth_bloc.dart';

class MockLoginUseCase extends Mock implements LoginUseCase {}

class MockRegisterUseCase extends Mock implements RegisterUseCase {}

void main() {
  late AuthBloc bloc;
  late MockLoginUseCase mockLogin;
  late MockRegisterUseCase mockRegister;

  setUp(() {
    mockLogin = MockLoginUseCase();
    mockRegister = MockRegisterUseCase();
    bloc = AuthBloc(loginUseCase: mockLogin, registerUseCase: mockRegister);
  });

  tearDown(() {
    bloc.close();
  });

  final testUser = User(
    id: '1',
    name: 'Sara',
    email: 'sara@test.com',
    createdAt: DateTime(2026, 7, 1),
  );

  final testResponse = AuthResponse(
    user: testUser,
    token: 'token-123',
    workspace: null,
  );

  group('LoginEvent', () {
    blocTest<AuthBloc, AuthState>(
      'emits [Loading, Authenticated] on success',
      build: () {
        when(() => mockLogin.execute(
              email: any(named: 'email'),
              password: any(named: 'password'),
            )).thenAnswer((_) async => Right(testResponse));
        return bloc;
      },
      act: (bloc) => bloc.add(const LoginEvent(
        email: 'sara@test.com',
        password: 'password',
      )),
      expect: () => [
        isA<AuthLoading>(),
        isA<AuthAuthenticated>().having(
          (s) => s.user.email,
          'email',
          'sara@test.com',
        ),
      ],
    );

    blocTest<AuthBloc, AuthState>(
      'emits [Loading, Error] on failure',
      build: () {
        when(() => mockLogin.execute(
              email: any(named: 'email'),
              password: any(named: 'password'),
            )).thenAnswer((_) async => Left(Exception('Invalid credentials')));
        return bloc;
      },
      act: (bloc) => bloc.add(const LoginEvent(
        email: 'bad@test.com',
        password: 'wrong',
      )),
      expect: () => [
        isA<AuthLoading>(),
        isA<AuthError>().having(
          (s) => s.message,
          'message',
          'Invalid credentials',
        ),
      ],
    );
  });

  group('RegisterEvent', () {
    blocTest<AuthBloc, AuthState>(
      'emits [Loading, Authenticated] on success',
      build: () {
        when(() => mockRegister.execute(
              name: any(named: 'name'),
              email: any(named: 'email'),
              password: any(named: 'password'),
              passwordConfirmation: any(named: 'passwordConfirmation'),
              workspaceName: any(named: 'workspaceName'),
              locale: any(named: 'locale'),
              timezone: any(named: 'timezone'),
            )).thenAnswer((_) async => Right(testResponse));
        return bloc;
      },
      act: (bloc) => bloc.add(const RegisterEvent(
        name: 'Sara',
        email: 'sara@test.com',
        password: 'SecureP@ss123',
        passwordConfirmation: 'SecureP@ss123',
        workspaceName: 'My Workspace',
      )),
      expect: () => [
        isA<AuthLoading>(),
        isA<AuthAuthenticated>(),
      ],
    );

    blocTest<AuthBloc, AuthState>(
      'emits [Loading, Error] on failure',
      build: () {
        when(() => mockRegister.execute(
              name: any(named: 'name'),
              email: any(named: 'email'),
              password: any(named: 'password'),
              passwordConfirmation: any(named: 'passwordConfirmation'),
              workspaceName: any(named: 'workspaceName'),
              locale: any(named: 'locale'),
              timezone: any(named: 'timezone'),
            )).thenAnswer((_) async => Left(Exception('Email taken')));
        return bloc;
      },
      act: (bloc) => bloc.add(const RegisterEvent(
        name: 'Sara',
        email: 'taken@test.com',
        password: 'SecureP@ss123',
        passwordConfirmation: 'SecureP@ss123',
        workspaceName: 'My Workspace',
      )),
      expect: () => [
        isA<AuthLoading>(),
        isA<AuthError>().having(
          (s) => s.message,
          'message',
          'Email taken',
        ),
      ],
    );
  });

  group('LogoutEvent', () {
    blocTest<AuthBloc, AuthState>(
      'emits [Loading, Unauthenticated]',
      build: () => bloc,
      act: (bloc) => bloc.add(LogoutEvent()),
      expect: () => [
        isA<AuthLoading>(),
        isA<AuthUnauthenticated>(),
      ],
    );
  });

  group('CheckAuthEvent', () {
    blocTest<AuthBloc, AuthState>(
      'emits [Loading, Unauthenticated]',
      build: () => bloc,
      act: (bloc) => bloc.add(CheckAuthEvent()),
      expect: () => [
        isA<AuthLoading>(),
        isA<AuthUnauthenticated>(),
      ],
    );
  });

  group('AuthErrorClearedEvent', () {
    blocTest<AuthBloc, AuthState>(
      'emits Initial state',
      build: () => bloc,
      seed: () => const AuthError(message: 'Some error'),
      act: (bloc) => bloc.add(AuthErrorClearedEvent()),
      expect: () => [isA<AuthInitial>()],
    );
  });

  test('initial state is AuthInitial', () {
    expect(bloc.state, isA<AuthInitial>());
  });
}
