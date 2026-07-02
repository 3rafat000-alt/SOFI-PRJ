import 'package:flutter_test/flutter_test.dart';
import 'package:mocktail/mocktail.dart';
import 'package:dartz/dartz.dart' hide Task;
import 'package:tasksync_pro/features/task/domain/entities/task.dart';
import 'package:tasksync_pro/features/task/domain/repositories/task_repository.dart';
import 'package:tasksync_pro/features/task/domain/usecases/get_tasks_usecase.dart';

class MockTaskRepository extends Mock implements TaskRepository {}

void main() {
  late GetTasksUseCase useCase;
  late MockTaskRepository mockRepository;

  setUp(() {
    mockRepository = MockTaskRepository();
    useCase = GetTasksUseCase(mockRepository);
  });

  final testTask = Task(
    id: '1',
    projectId: 'p1',
    title: 'Test Task',
    status: 'todo',
    priority: 'high',
    createdAt: DateTime(2026, 7, 1),
    updatedAt: DateTime(2026, 7, 1),
  );

  group('GetTasksUseCase', () {
    test('returns tasks on success', () async {
      when(() => mockRepository.getTasks(
            workspaceId: any(named: 'workspaceId'),
            limit: any(named: 'limit'),
          )).thenAnswer((_) async => Right([testTask]));

      final result = await useCase.execute(workspaceId: 'w1');

      expect(result.isRight(), true);
      result.fold(
        (l) => fail('Expected Right'),
        (tasks) {
          expect(tasks, hasLength(1));
          expect(tasks.first.title, 'Test Task');
        },
      );
    });

    test('returns exception on failure', () async {
      when(() => mockRepository.getTasks(
            workspaceId: any(named: 'workspaceId'),
            limit: any(named: 'limit'),
          )).thenAnswer((_) async => Left(Exception('API Error')));

      final result = await useCase.execute(workspaceId: 'w1');

      expect(result.isLeft(), true);
    });

    test('passes status filter to repository', () async {
      when(() => mockRepository.getTasks(
            workspaceId: any(named: 'workspaceId'),
            status: any(named: 'status'),
            limit: any(named: 'limit'),
          )).thenAnswer((_) async => Right([testTask]));

      await useCase.execute(workspaceId: 'w1', status: 'todo');

      verify(() => mockRepository.getTasks(
            workspaceId: 'w1',
            status: 'todo',
            limit: 50,
          )).called(1);
    });

    test('returns empty list when no tasks', () async {
      when(() => mockRepository.getTasks(
            workspaceId: any(named: 'workspaceId'),
            limit: any(named: 'limit'),
          )).thenAnswer((_) async => const Right([]));

      final result = await useCase.execute(workspaceId: 'w1');

      result.fold(
        (l) => fail('Expected Right'),
        (tasks) => expect(tasks, isEmpty),
      );
    });
  });
}
