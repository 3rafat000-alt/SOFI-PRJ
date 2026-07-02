import 'package:dartz/dartz.dart';
import '../repositories/workspace_repository.dart';
import '../entities/workspace.dart';

class GetWorkspacesUseCase {
  final WorkspaceRepository _repository;

  GetWorkspacesUseCase(this._repository);

  Future<Either<Exception, List<Workspace>>> execute() {
    return _repository.getWorkspaces();
  }
}
