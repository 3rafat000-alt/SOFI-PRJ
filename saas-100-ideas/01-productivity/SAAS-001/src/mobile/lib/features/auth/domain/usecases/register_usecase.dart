import 'package:dartz/dartz.dart';
import '../repositories/auth_repository.dart';
import '../entities/auth_response.dart';

class RegisterUseCase {
  final AuthRepository _repository;

  RegisterUseCase(this._repository);

  Future<Either<Exception, AuthResponse>> execute({
    required String name,
    required String email,
    required String password,
    required String passwordConfirmation,
    required String workspaceName,
    String locale = 'ar',
    String timezone = 'Asia/Riyadh',
  }) {
    return _repository.register(
      name: name,
      email: email,
      password: password,
      passwordConfirmation: passwordConfirmation,
      workspaceName: workspaceName,
      locale: locale,
      timezone: timezone,
    );
  }
}
