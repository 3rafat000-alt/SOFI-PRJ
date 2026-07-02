import 'package:dartz/dartz.dart';
import '../entities/project.dart';

abstract class ProjectRepository {
  Future<Either<Exception, List<Project>>> getProjects({
    required String workspaceId,
    String status = 'active',
    String? search,
    int page = 1,
    int perPage = 20,
  });
  Future<Either<Exception, Project>> getProject(String id);
  Future<Either<Exception, Project>> createProject({
    required String workspaceId,
    required String name,
    String? description,
    String color = '#4F46E5',
    DateTime? startDate,
    DateTime? endDate,
  });
  Future<Either<Exception, Project>> updateProject({
    required String id,
    String? name,
    String? description,
    String? color,
    DateTime? endDate,
  });
  Future<Either<Exception, void>> deleteProject(String id);
}
