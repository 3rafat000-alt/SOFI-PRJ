import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:equatable/equatable.dart';
import '../../domain/entities/project.dart';
import '../../domain/usecases/get_projects_usecase.dart';
import '../../domain/usecases/create_project_usecase.dart';

// Events
abstract class ProjectEvent extends Equatable {
  const ProjectEvent();
  @override
  List<Object?> get props => [];
}

class LoadProjectsEvent extends ProjectEvent {
  final String workspaceId;
  final String status;

  const LoadProjectsEvent({
    required this.workspaceId,
    this.status = 'active',
  });
  @override
  List<Object?> get props => [workspaceId, status];
}

class CreateProjectEvent extends ProjectEvent {
  final String workspaceId;
  final String name;
  final String? description;
  final String color;
  final DateTime? startDate;
  final DateTime? endDate;

  const CreateProjectEvent({
    required this.workspaceId,
    required this.name,
    this.description,
    this.color = '#4F46E5',
    this.startDate,
    this.endDate,
  });
  @override
  List<Object?> get props => [workspaceId, name, description, color, startDate, endDate];
}

class DeleteProjectEvent extends ProjectEvent {
  final String projectId;

  const DeleteProjectEvent({required this.projectId});
  @override
  List<Object?> get props => [projectId];
}

// States
abstract class ProjectState extends Equatable {
  const ProjectState();
  @override
  List<Object?> get props => [];
}

class ProjectInitial extends ProjectState {}

class ProjectLoading extends ProjectState {}

class ProjectsLoaded extends ProjectState {
  final List<Project> projects;

  const ProjectsLoaded({required this.projects});
  @override
  List<Object?> get props => [projects];
}

class ProjectCreated extends ProjectState {
  final Project project;

  const ProjectCreated({required this.project});
  @override
  List<Object?> get props => [project];
}

class ProjectOperationSuccess extends ProjectState {
  final String message;

  const ProjectOperationSuccess({required this.message});
  @override
  List<Object?> get props => [message];
}

class ProjectError extends ProjectState {
  final String message;

  const ProjectError({required this.message});
  @override
  List<Object?> get props => [message];
}

// Bloc
class ProjectBloc extends Bloc<ProjectEvent, ProjectState> {
  final GetProjectsUseCase _getProjectsUseCase;
  final CreateProjectUseCase _createProjectUseCase;

  ProjectBloc({
    required GetProjectsUseCase getProjectsUseCase,
    required CreateProjectUseCase createProjectUseCase,
  })  : _getProjectsUseCase = getProjectsUseCase,
        _createProjectUseCase = createProjectUseCase,
        super(ProjectInitial()) {
    on<LoadProjectsEvent>(_onLoadProjects);
    on<CreateProjectEvent>(_onCreateProject);
    on<DeleteProjectEvent>(_onDeleteProject);
  }

  Future<void> _onLoadProjects(
      LoadProjectsEvent event, Emitter<ProjectState> emit) async {
    emit(ProjectLoading());
    final result = await _getProjectsUseCase.execute(
      workspaceId: event.workspaceId,
      status: event.status,
    );
    result.fold(
      (failure) => emit(ProjectError(message: _mapError(failure))),
      (projects) => emit(ProjectsLoaded(projects: projects)),
    );
  }

  Future<void> _onCreateProject(
      CreateProjectEvent event, Emitter<ProjectState> emit) async {
    emit(ProjectLoading());
    final result = await _createProjectUseCase.execute(
      workspaceId: event.workspaceId,
      name: event.name,
      description: event.description,
      color: event.color,
      startDate: event.startDate,
      endDate: event.endDate,
    );
    result.fold(
      (failure) => emit(ProjectError(message: _mapError(failure))),
      (project) => emit(ProjectCreated(project: project)),
    );
  }

  Future<void> _onDeleteProject(
      DeleteProjectEvent event, Emitter<ProjectState> emit) async {
    emit(ProjectLoading());
    // Would call delete usecase
    emit(ProjectOperationSuccess(message: 'Project deleted'));
  }

  String _mapError(Object error) {
    if (error is Exception) {
      return error.toString().replaceFirst('Exception: ', '');
    }
    return 'An unexpected error occurred';
  }
}
