import 'package:flutter_test/flutter_test.dart';
import 'package:mocktail/mocktail.dart';
import 'package:dartz/dartz.dart';
import 'package:tasksync_pro/features/auth/domain/entities/user.dart';
import 'package:tasksync_pro/features/auth/domain/entities/auth_response.dart';
import 'package:tasksync_pro/features/auth/domain/repositories/auth_repository.dart';
import 'package:tasksync_pro/features/auth/domain/usecases/login_usecase.dart';

class MockAuthRepository extends Mock implements AuthRepository {}

void main() {
  late LoginUseCase useCase;
  late MockAuthRepository mockRepository;

  setUp(() {
    mockRepository = MockAuthRepository();
    useCase = LoginUseCase(mockRepository);
  });

  final testResponse = AuthResponse(
    user: User(
      id: '1',
      name: 'Sara',
      email: 'sara@test.com',
      createdAt: DateTime(2026, 7, 1),
    ),
    token: 'token-abc',
    workspace: null,
  );

  test('logs in successfully', () async {
    when(() => mockRepository.login(
          email: any(named: 'email'),
          password: any(named: 'password'),
        )).thenAnswer((_) async => Right(testResponse));

    final result = await useCase.execute(
      email: 'sara@test.com',
      password: 'password',
    );

    expect(result.isRight(), true);
    result.fold(
      (l) => fail('Expected Right'),
      (response) {
        expect(response.user.email, 'sara@test.com');
        expect(response.token, 'token-abc');
      },
    );
  });

  test('fails with wrong credentials', () async {
    when(() => mockRepository.login(
          email: any(named: 'email'),
          password: any(named: 'password'),
        )).thenAnswer((_) async => Left(Exception('Invalid credentials')));

    final result = await useCase.execute(
      email: 'bad@test.com',
      password: 'wrong',
    );

    expect(result.isLeft(), true);
  });
}
