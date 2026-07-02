import 'package:dartz/dartz.dart';
import '../repositories/auth_repository.dart';
import '../entities/auth_response.dart';

class LoginUseCase {
  final AuthRepository _repository;

  LoginUseCase(this._repository);

  Future<Either<Exception, AuthResponse>> execute({
    required String email,
    required String password,
  }) {
    return _repository.login(email: email, password: password);
  }
}
