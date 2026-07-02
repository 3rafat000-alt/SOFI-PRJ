import 'package:dartz/dartz.dart';
import '../repositories/project_repository.dart';
import '../entities/project.dart';

class GetProjectsUseCase {
  final ProjectRepository _repository;

  GetProjectsUseCase(this._repository);

  Future<Either<Exception, List<Project>>> execute({
    required String workspaceId,
    String status = 'active',
    String? search,
    int page = 1,
    int perPage = 20,
  }) {
    return _repository.getProjects(
      workspaceId: workspaceId,
      status: status,
      search: search,
      page: page,
      perPage: perPage,
    );
  }
}
