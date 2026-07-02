import 'package:flutter_test/flutter_test.dart';
import 'package:mocktail/mocktail.dart';
import 'package:dartz/dartz.dart';
import 'package:tasksync_pro/features/auth/domain/entities/user.dart';
import 'package:tasksync_pro/features/auth/domain/entities/auth_response.dart';
import 'package:tasksync_pro/features/auth/domain/repositories/auth_repository.dart';
import 'package:tasksync_pro/features/auth/domain/usecases/register_usecase.dart';

class MockAuthRepository extends Mock implements AuthRepository {}

void main() {
  late RegisterUseCase useCase;
  late MockAuthRepository mockRepository;

  setUp(() {
    mockRepository = MockAuthRepository();
    useCase = RegisterUseCase(mockRepository);
  });

  final testResponse = AuthResponse(
    user: User(
      id: '1',
      name: 'New User',
      email: 'new@test.com',
      createdAt: DateTime(2026, 7, 1),
    ),
    token: 'token-xyz',
    workspace: null,
  );

  test('registers successfully', () async {
    when(() => mockRepository.register(
          name: any(named: 'name'),
          email: any(named: 'email'),
          password: any(named: 'password'),
          passwordConfirmation: any(named: 'passwordConfirmation'),
          workspaceName: any(named: 'workspaceName'),
          locale: any(named: 'locale'),
          timezone: any(named: 'timezone'),
        )).thenAnswer((_) async => Right(testResponse));

    final result = await useCase.execute(
      name: 'New User',
      email: 'new@test.com',
      password: 'SecureP@ss123',
      passwordConfirmation: 'SecureP@ss123',
      workspaceName: 'My Workspace',
    );

    expect(result.isRight(), true);
    result.fold(
      (l) => fail('Expected Right'),
      (response) {
        expect(response.user.name, 'New User');
        expect(response.token, 'token-xyz');
      },
    );
  });

  test('fails with duplicate email', () async {
    when(() => mockRepository.register(
          name: any(named: 'name'),
          email: any(named: 'email'),
          password: any(named: 'password'),
          passwordConfirmation: any(named: 'passwordConfirmation'),
          workspaceName: any(named: 'workspaceName'),
          locale: any(named: 'locale'),
          timezone: any(named: 'timezone'),
        )).thenAnswer((_) async => Left(Exception('Email already exists')));

    final result = await useCase.execute(
      name: 'New User',
      email: 'existing@test.com',
      password: 'SecureP@ss123',
      passwordConfirmation: 'SecureP@ss123',
      workspaceName: 'My Workspace',
    );

    expect(result.isLeft(), true);
  });
}
