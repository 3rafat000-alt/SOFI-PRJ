import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:equatable/equatable.dart';
import '../../domain/entities/workspace.dart';
import '../../domain/usecases/get_workspaces_usecase.dart';
import '../../domain/usecases/create_workspace_usecase.dart';
import '../../domain/usecases/invite_member_usecase.dart';

// Events
abstract class WorkspaceEvent extends Equatable {
  const WorkspaceEvent();
  @override
  List<Object?> get props => [];
}

class LoadWorkspacesEvent extends WorkspaceEvent {}

class CreateWorkspaceEvent extends WorkspaceEvent {
  final String name;
  final String? description;

  const CreateWorkspaceEvent({required this.name, this.description});
  @override
  List<Object?> get props => [name, description];
}

class InviteMemberEvent extends WorkspaceEvent {
  final String workspaceId;
  final String email;
  final String role;
  final String? message;
  final String channel;

  const InviteMemberEvent({
    required this.workspaceId,
    required this.email,
    this.role = 'member',
    this.message,
    this.channel = 'email',
  });
  @override
  List<Object?> get props => [workspaceId, email, role, message, channel];
}

class LoadMembersEvent extends WorkspaceEvent {
  final String workspaceId;

  const LoadMembersEvent({required this.workspaceId});
  @override
  List<Object?> get props => [workspaceId];
}

// States
abstract class WorkspaceState extends Equatable {
  const WorkspaceState();
  @override
  List<Object?> get props => [];
}

class WorkspaceInitial extends WorkspaceState {}

class WorkspaceLoading extends WorkspaceState {}

class WorkspacesLoaded extends WorkspaceState {
  final List<Workspace> workspaces;

  const WorkspacesLoaded({required this.workspaces});
  @override
  List<Object?> get props => [workspaces];
}

class WorkspaceCreated extends WorkspaceState {
  final Workspace workspace;

  const WorkspaceCreated({required this.workspace});
  @override
  List<Object?> get props => [workspace];
}

class WorkspaceError extends WorkspaceState {
  final String message;

  const WorkspaceError({required this.message});
  @override
  List<Object?> get props => [message];
}

// Bloc
class WorkspaceBloc extends Bloc<WorkspaceEvent, WorkspaceState> {
  final GetWorkspacesUseCase _getWorkspacesUseCase;
  final CreateWorkspaceUseCase _createWorkspaceUseCase;
  final InviteMemberUseCase _inviteMemberUseCase;

  WorkspaceBloc({
    required GetWorkspacesUseCase getWorkspacesUseCase,
    required CreateWorkspaceUseCase createWorkspaceUseCase,
    required InviteMemberUseCase inviteMemberUseCase,
  })  : _getWorkspacesUseCase = getWorkspacesUseCase,
        _createWorkspaceUseCase = createWorkspaceUseCase,
        _inviteMemberUseCase = inviteMemberUseCase,
        super(WorkspaceInitial()) {
    on<LoadWorkspacesEvent>(_onLoadWorkspaces);
    on<CreateWorkspaceEvent>(_onCreateWorkspace);
    on<InviteMemberEvent>(_onInviteMember);
  }

  Future<void> _onLoadWorkspaces(
      LoadWorkspacesEvent event, Emitter<WorkspaceState> emit) async {
    emit(WorkspaceLoading());
    final result = await _getWorkspacesUseCase.execute();
    result.fold(
      (failure) => emit(WorkspaceError(message: _mapError(failure))),
      (workspaces) => emit(WorkspacesLoaded(workspaces: workspaces)),
    );
  }

  Future<void> _onCreateWorkspace(
      CreateWorkspaceEvent event, Emitter<WorkspaceState> emit) async {
    emit(WorkspaceLoading());
    final result = await _createWorkspaceUseCase.execute(
      name: event.name,
      description: event.description,
    );
    result.fold(
      (failure) => emit(WorkspaceError(message: _mapError(failure))),
      (workspace) => emit(WorkspaceCreated(workspace: workspace)),
    );
  }

  Future<void> _onInviteMember(
      InviteMemberEvent event, Emitter<WorkspaceState> emit) async {
    emit(WorkspaceLoading());
    final result = await _inviteMemberUseCase.execute(
      workspaceId: event.workspaceId,
      email: event.email,
      role: event.role,
      message: event.message,
      channel: event.channel,
    );
    result.fold(
      (failure) => emit(WorkspaceError(message: _mapError(failure))),
      (_) => emit(WorkspaceInitial()),
    );
  }

  String _mapError(Object error) {
    if (error is Exception) {
      return error.toString().replaceFirst('Exception: ', '');
    }
    return 'An unexpected error occurred';
  }
}
