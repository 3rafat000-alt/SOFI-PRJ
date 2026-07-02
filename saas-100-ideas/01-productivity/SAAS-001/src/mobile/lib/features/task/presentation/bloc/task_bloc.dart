import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:equatable/equatable.dart';
import '../../domain/entities/task.dart';
import '../../domain/repositories/task_repository.dart';
import '../../domain/usecases/get_tasks_usecase.dart';
import '../../domain/usecases/create_task_usecase.dart';
import '../../domain/usecases/update_task_usecase.dart';
import '../../domain/usecases/reorder_task_usecase.dart';

// Events
abstract class TaskEvent extends Equatable {
  const TaskEvent();
  @override
  List<Object?> get props => [];
}

class LoadTasksEvent extends TaskEvent {
  final String workspaceId;
  final String? projectId;
  final String? status;

  const LoadTasksEvent({
    required this.workspaceId,
    this.projectId,
    this.status,
  });
  @override
  List<Object?> get props => [workspaceId, projectId, status];
}

class CreateTaskEvent extends TaskEvent {
  final String projectId;
  final String title;
  final String? description;
  final String priority;
  final String? assigneeId;
  final DateTime? dueDate;
  final int estimatedMinutes;

  const CreateTaskEvent({
    required this.projectId,
    required this.title,
    this.description,
    this.priority = 'medium',
    this.assigneeId,
    this.dueDate,
    this.estimatedMinutes = 0,
  });
  @override
  List<Object?> get props => [
        projectId, title, description, priority, assigneeId,
        dueDate, estimatedMinutes,
      ];
}

class UpdateTaskEvent extends TaskEvent {
  final String id;
  final String? title;
  final String? description;
  final String? status;
  final String? priority;
  final String? assigneeId;
  final DateTime? dueDate;
  final int? estimatedMinutes;

  const UpdateTaskEvent({
    required this.id,
    this.title,
    this.description,
    this.status,
    this.priority,
    this.assigneeId,
    this.dueDate,
    this.estimatedMinutes,
  });
  @override
  List<Object?> get props => [
        id, title, description, status, priority, assigneeId,
        dueDate, estimatedMinutes,
      ];
}

class QuickStatusChangeEvent extends TaskEvent {
  final String taskId;
  final String status;

  const QuickStatusChangeEvent({
    required this.taskId,
    required this.status,
  });
  @override
  List<Object?> get props => [taskId, status];
}

class ReorderTasksEvent extends TaskEvent {
  final String projectId;
  final List<OrderEntry> orders;

  const ReorderTasksEvent({
    required this.projectId,
    required this.orders,
  });
  @override
  List<Object?> get props => [projectId, orders];
}

// States
abstract class TaskState extends Equatable {
  const TaskState();
  @override
  List<Object?> get props => [];
}

class TaskInitial extends TaskState {}

class TaskLoading extends TaskState {}

class TasksLoaded extends TaskState {
  final List<Task> tasks;

  const TasksLoaded({required this.tasks});
  @override
  List<Object?> get props => [tasks];
}

class TaskCreated extends TaskState {
  final Task task;

  const TaskCreated({required this.task});
  @override
  List<Object?> get props => [task];
}

class TaskUpdated extends TaskState {
  final Task task;

  const TaskUpdated({required this.task});
  @override
  List<Object?> get props => [task];
}

class TaskOperationSuccess extends TaskState {
  final String message;

  const TaskOperationSuccess({required this.message});
  @override
  List<Object?> get props => [message];
}

class TaskError extends TaskState {
  final String message;

  const TaskError({required this.message});
  @override
  List<Object?> get props => [message];
}

// Bloc
class TaskBloc extends Bloc<TaskEvent, TaskState> {
  final GetTasksUseCase _getTasksUseCase;
  final CreateTaskUseCase _createTaskUseCase;
  final UpdateTaskUseCase _updateTaskUseCase;
  final ReorderTaskUseCase _reorderTaskUseCase;

  TaskBloc({
    required GetTasksUseCase getTasksUseCase,
    required CreateTaskUseCase createTaskUseCase,
    required UpdateTaskUseCase updateTaskUseCase,
    required ReorderTaskUseCase reorderTaskUseCase,
  })  : _getTasksUseCase = getTasksUseCase,
        _createTaskUseCase = createTaskUseCase,
        _updateTaskUseCase = updateTaskUseCase,
        _reorderTaskUseCase = reorderTaskUseCase,
        super(TaskInitial()) {
    on<LoadTasksEvent>(_onLoadTasks);
    on<CreateTaskEvent>(_onCreateTask);
    on<UpdateTaskEvent>(_onUpdateTask);
    on<QuickStatusChangeEvent>(_onQuickStatusChange);
    on<ReorderTasksEvent>(_onReorderTasks);
  }

  Future<void> _onLoadTasks(
      LoadTasksEvent event, Emitter<TaskState> emit) async {
    emit(TaskLoading());
    final result = await _getTasksUseCase.execute(
      workspaceId: event.workspaceId,
      projectId: event.projectId,
      status: event.status,
    );
    result.fold(
      (failure) => emit(TaskError(message: _mapError(failure))),
      (tasks) => emit(TasksLoaded(tasks: tasks)),
    );
  }

  Future<void> _onCreateTask(
      CreateTaskEvent event, Emitter<TaskState> emit) async {
    emit(TaskLoading());
    final result = await _createTaskUseCase.execute(
      projectId: event.projectId,
      title: event.title,
      description: event.description,
      priority: event.priority,
      assigneeId: event.assigneeId,
      dueDate: event.dueDate,
      estimatedMinutes: event.estimatedMinutes,
    );
    result.fold(
      (failure) => emit(TaskError(message: _mapError(failure))),
      (task) => emit(TaskCreated(task: task)),
    );
  }

  Future<void> _onUpdateTask(
      UpdateTaskEvent event, Emitter<TaskState> emit) async {
    emit(TaskLoading());
    final result = await _updateTaskUseCase.execute(
      id: event.id,
      title: event.title,
      description: event.description,
      status: event.status,
      priority: event.priority,
      assigneeId: event.assigneeId,
      dueDate: event.dueDate,
      estimatedMinutes: event.estimatedMinutes,
    );
    result.fold(
      (failure) => emit(TaskError(message: _mapError(failure))),
      (task) => emit(TaskUpdated(task: task)),
    );
  }

  Future<void> _onQuickStatusChange(
      QuickStatusChangeEvent event, Emitter<TaskState> emit) async {
    final currentState = state;
    // Optimistic update
    if (currentState is TasksLoaded) {
      final updated = currentState.tasks.map((t) {
        if (t.id == event.taskId) {
          return t.copyWith(status: event.status);
        }
        return t;
      }).toList();
      emit(TasksLoaded(tasks: updated));
    }
    // Actual API call
    final result = await _updateTaskUseCase.execute(
      id: event.taskId,
      status: event.status,
    );
    result.fold(
      (failure) {
        // Rollback on failure, re-emit original
        if (currentState is TasksLoaded) {
          emit(currentState);
        }
        emit(TaskError(message: _mapError(failure)));
      },
      (_) {},
    );
  }

  Future<void> _onReorderTasks(
      ReorderTasksEvent event, Emitter<TaskState> emit) async {
    final result = await _reorderTaskUseCase.execute(
      projectId: event.projectId,
      orders: event.orders,
    );
    result.fold(
      (failure) => emit(TaskError(message: _mapError(failure))),
      (_) {},
    );
  }

  String _mapError(Object error) {
    if (error is Exception) {
      return error.toString().replaceFirst('Exception: ', '');
    }
    return 'An unexpected error occurred';
  }
}
