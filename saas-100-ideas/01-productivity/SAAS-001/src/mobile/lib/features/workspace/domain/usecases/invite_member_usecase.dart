import 'package:dartz/dartz.dart';
import '../repositories/workspace_repository.dart';
import '../entities/workspace.dart';

class InviteMemberUseCase {
  final WorkspaceRepository _repository;

  InviteMemberUseCase(this._repository);

  Future<Either<Exception, Invitation>> execute({
    required String workspaceId,
    required String email,
    String role = 'member',
    String? message,
    String channel = 'email',
  }) {
    return _repository.inviteMember(
      workspaceId: workspaceId,
      email: email,
      role: role,
      message: message,
      channel: channel,
    );
  }
}
