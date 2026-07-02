import 'package:dartz/dartz.dart';
import '../entities/user.dart';
import '../entities/auth_response.dart';

abstract class AuthRepository {
  Future<Either<Exception, AuthResponse>> login({
    required String email,
    required String password,
  });

  Future<Either<Exception, AuthResponse>> register({
    required String name,
    required String email,
    required String password,
    required String passwordConfirmation,
    required String workspaceName,
    String locale = 'ar',
    String timezone = 'Asia/Riyadh',
  });

  Future<Either<Exception, void>> logout();

  Future<Either<Exception, User>> getCurrentUser();

  Future<Either<Exception, String>> forgotPassword(String email);

  Future<Either<Exception, void>> resetPassword({
    required String token,
    required String email,
    required String password,
    required String passwordConfirmation,
  });
}
