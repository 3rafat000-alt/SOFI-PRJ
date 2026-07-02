import 'package:flutter_test/flutter_test.dart';
import 'package:mocktail/mocktail.dart';
import 'package:dartz/dartz.dart' hide Task;
import 'package:bloc_test/bloc_test.dart';
import 'package:tasksync_pro/features/task/domain/entities/task.dart';
import 'package:tasksync_pro/features/task/domain/repositories/task_repository.dart';
import 'package:tasksync_pro/features/task/domain/usecases/get_tasks_usecase.dart';
import 'package:tasksync_pro/features/task/domain/usecases/create_task_usecase.dart';
import 'package:tasksync_pro/features/task/domain/usecases/update_task_usecase.dart';
import 'package:tasksync_pro/features/task/domain/usecases/reorder_task_usecase.dart';
import 'package:tasksync_pro/features/task/presentation/bloc/task_bloc.dart';

class MockGetTasksUseCase extends Mock implements GetTasksUseCase {}
class MockCreateTaskUseCase extends Mock implements CreateTaskUseCase {}
class MockUpdateTaskUseCase extends Mock implements UpdateTaskUseCase {}
class MockReorderTaskUseCase extends Mock implements ReorderTaskUseCase {}

void main() {
  late TaskBloc bloc;
  late MockGetTasksUseCase mockGetTasks;
  late MockCreateTaskUseCase mockCreateTask;
  late MockUpdateTaskUseCase mockUpdateTask;
  late MockReorderTaskUseCase mockReorderTask;

  setUp(() {
    mockGetTasks = MockGetTasksUseCase();
    mockCreateTask = MockCreateTaskUseCase();
    mockUpdateTask = MockUpdateTaskUseCase();
    mockReorderTask = MockReorderTaskUseCase();
    bloc = TaskBloc(
      getTasksUseCase: mockGetTasks,
      createTaskUseCase: mockCreateTask,
      updateTaskUseCase: mockUpdateTask,
      reorderTaskUseCase: mockReorderTask,
    );
  });

  tearDown(() {
    bloc.close();
  });

  final testTask = Task(
    id: '1',
    projectId: 'p1',
    title: 'Test Task',
    status: 'todo',
    priority: 'medium',
    createdAt: DateTime(2026, 7, 1),
    updatedAt: DateTime(2026, 7, 1),
  );

  final testTask2 = Task(
    id: '2',
    projectId: 'p1',
    title: 'Task 2',
    status: 'done',
    priority: 'high',
    createdAt: DateTime(2026, 7, 1),
    updatedAt: DateTime(2026, 7, 1),
  );

  group('LoadTasksEvent', () {
    blocTest<TaskBloc, TaskState>(
      'emits [Loading, Loaded] on success',
      build: () {
        when(() => mockGetTasks.execute(
              workspaceId: any(named: 'workspaceId'),
              projectId: any(named: 'projectId'),
              status: any(named: 'status'),
            )).thenAnswer((_) async => Right([testTask, testTask2]));
        return bloc;
      },
      act: (bloc) => bloc.add(const LoadTasksEvent(
        workspaceId: 'w1',
        projectId: 'p1',
      )),
      expect: () => [
        isA<TaskLoading>(),
        isA<TasksLoaded>().having(
          (s) => s.tasks.length,
          'length',
          2,
        ),
      ],
    );

    blocTest<TaskBloc, TaskState>(
      'emits [Loading, Error] on failure',
      build: () {
        when(() => mockGetTasks.execute(
              workspaceId: any(named: 'workspaceId'),
            )).thenAnswer((_) async => Left(Exception('Failed to load')));
        return bloc;
      },
      act: (bloc) => bloc.add(const LoadTasksEvent(workspaceId: 'w1')),
      expect: () => [
        isA<TaskLoading>(),
        isA<TaskError>().having(
          (s) => s.message,
          'message',
          'Failed to load',
        ),
      ],
    );
  });

  group('CreateTaskEvent', () {
    blocTest<TaskBloc, TaskState>(
      'emits [Loading, Created] on success',
      build: () {
        when(() => mockCreateTask.execute(
              projectId: any(named: 'projectId'),
              title: any(named: 'title'),
              priority: any(named: 'priority'),
            )).thenAnswer((_) async => Right(testTask));
        return bloc;
      },
      act: (bloc) => bloc.add(const CreateTaskEvent(
        projectId: 'p1',
        title: 'Test Task',
      )),
      expect: () => [
        isA<TaskLoading>(),
        isA<TaskCreated>().having(
          (s) => s.task.title,
          'title',
          'Test Task',
        ),
      ],
    );

    blocTest<TaskBloc, TaskState>(
      'emits [Loading, Error] on failure',
      build: () {
        when(() => mockCreateTask.execute(
              projectId: any(named: 'projectId'),
              title: any(named: 'title'),
            )).thenAnswer((_) async => Left(Exception('Validation failed')));
        return bloc;
      },
      act: (bloc) => bloc.add(const CreateTaskEvent(
        projectId: 'p1',
        title: '',
      )),
      expect: () => [
        isA<TaskLoading>(),
        isA<TaskError>(),
      ],
    );
  });

  group('UpdateTaskEvent', () {
    blocTest<TaskBloc, TaskState>(
      'emits [Loading, Updated] on success',
      build: () {
        when(() => mockUpdateTask.execute(
              id: any(named: 'id'),
              status: any(named: 'status'),
            )).thenAnswer((_) async => Right(testTask.copyWith(status: 'in_progress')));
        return bloc;
      },
      act: (bloc) => bloc.add(const UpdateTaskEvent(id: '1', status: 'in_progress')),
      expect: () => [
        isA<TaskLoading>(),
        isA<TaskUpdated>().having(
          (s) => s.task.status,
          'status',
          'in_progress',
        ),
      ],
    );
  });

  group('QuickStatusChangeEvent', () {
    blocTest<TaskBloc, TaskState>(
      'optimistically updates then calls API',
      build: () {
        when(() => mockUpdateTask.execute(
              id: any(named: 'id'),
              status: any(named: 'status'),
            )).thenAnswer((_) async => Right(testTask.copyWith(status: 'in_progress')));
        return bloc;
      },
      seed: () => TasksLoaded(tasks: [testTask, testTask2]),
      act: (bloc) => bloc.add(const QuickStatusChangeEvent(
        taskId: '1',
        status: 'in_progress',
      )),
      expect: () => [
        // Optimistic update
        isA<TasksLoaded>().having(
          (s) => s.tasks.first.status,
          'first task status',
          'in_progress',
        ),
      ],
    );

    blocTest<TaskBloc, TaskState>(
      'rolls back on API failure',
      build: () {
        when(() => mockUpdateTask.execute(
              id: any(named: 'id'),
              status: any(named: 'status'),
            )).thenAnswer((_) async => Left(Exception('API Error')));
        return bloc;
      },
      seed: () => TasksLoaded(tasks: [testTask]),
      act: (bloc) => bloc.add(const QuickStatusChangeEvent(
        taskId: '1',
        status: 'in_progress',
      )),
      expect: () => [
        // Optimistic update
        isA<TasksLoaded>().having(
          (s) => s.tasks.first.status,
          'first task status',
          'in_progress',
        ),
        // Rollback + error
        isA<TasksLoaded>().having(
          (s) => s.tasks.first.status,
          'rolled back status',
          'todo',
        ),
        isA<TaskError>(),
      ],
    );
  });

  group('ReorderTasksEvent', () {
    blocTest<TaskBloc, TaskState>(
      'emits success on reorder',
      build: () {
        when(() => mockReorderTask.execute(
              projectId: any(named: 'projectId'),
              orders: any(named: 'orders'),
            )).thenAnswer((_) async => const Right(null));
        return bloc;
      },
      act: (bloc) => bloc.add(const ReorderTasksEvent(
        projectId: 'p1',
        orders: [],
      )),
      expect: () => [],
    );

    blocTest<TaskBloc, TaskState>(
      'emits Error on failure',
      build: () {
        when(() => mockReorderTask.execute(
              projectId: any(named: 'projectId'),
              orders: any(named: 'orders'),
            )).thenAnswer((_) async => Left(Exception('Reorder failed')));
        return bloc;
      },
      act: (bloc) => bloc.add(const ReorderTasksEvent(
        projectId: 'p1',
        orders: [],
      )),
      expect: () => [isA<TaskError>()],
    );
  });

  test('initial state is TaskInitial', () {
    expect(bloc.state, isA<TaskInitial>());
  });
}
