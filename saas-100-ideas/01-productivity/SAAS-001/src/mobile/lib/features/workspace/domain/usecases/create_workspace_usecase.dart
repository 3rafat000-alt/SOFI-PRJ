import 'package:dartz/dartz.dart';
import '../repositories/workspace_repository.dart';
import '../entities/workspace.dart';

class CreateWorkspaceUseCase {
  final WorkspaceRepository _repository;

  CreateWorkspaceUseCase(this._repository);

  Future<Either<Exception, Workspace>> execute({
    required String name,
    String? description,
  }) {
    return _repository.createWorkspace(name: name, description: description);
  }
}
