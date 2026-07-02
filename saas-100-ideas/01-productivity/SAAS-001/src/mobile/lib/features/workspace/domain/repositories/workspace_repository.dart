import 'package:dartz/dartz.dart';
import '../entities/workspace.dart';

abstract class WorkspaceRepository {
  Future<Either<Exception, List<Workspace>>> getWorkspaces();
  Future<Either<Exception, Workspace>> createWorkspace({
    required String name,
    String? description,
    String timezone = 'Asia/Riyadh',
  });
  Future<Either<Exception, Workspace>> updateWorkspace({
    required String id,
    String? name,
    String? description,
  });
  Future<Either<Exception, void>> deleteWorkspace(String id);
  Future<Either<Exception, List<WorkspaceMember>>> getMembers(String workspaceId);
  Future<Either<Exception, Invitation>> inviteMember({
    required String workspaceId,
    required String email,
    String role = 'member',
    String? message,
    String channel = 'email',
  });
}
