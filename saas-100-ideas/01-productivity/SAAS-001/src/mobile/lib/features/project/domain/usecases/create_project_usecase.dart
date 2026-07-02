import 'package:dartz/dartz.dart';
import '../repositories/project_repository.dart';
import '../entities/project.dart';

class CreateProjectUseCase {
  final ProjectRepository _repository;

  CreateProjectUseCase(this._repository);

  Future<Either<Exception, Project>> execute({
    required String workspaceId,
    required String name,
    String? description,
    String color = '#4F46E5',
    DateTime? startDate,
    DateTime? endDate,
  }) {
    return _repository.createProject(
      workspaceId: workspaceId,
      name: name,
      description: description,
      color: color,
      startDate: startDate,
      endDate: endDate,
    );
  }
}
