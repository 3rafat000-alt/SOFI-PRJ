import 'package:flutter_test/flutter_test.dart';
import 'package:mocktail/mocktail.dart';
import 'package:dartz/dartz.dart' hide Task;
import 'package:tasksync_pro/features/task/domain/entities/task.dart';
import 'package:tasksync_pro/features/task/domain/repositories/task_repository.dart';
import 'package:tasksync_pro/features/task/domain/usecases/create_task_usecase.dart';

class MockTaskRepository extends Mock implements TaskRepository {}

void main() {
  late CreateTaskUseCase useCase;
  late MockTaskRepository mockRepository;

  setUp(() {
    mockRepository = MockTaskRepository();
    useCase = CreateTaskUseCase(mockRepository);
  });

  final testTask = Task(
    id: '1',
    projectId: 'p1',
    title: 'New Task',
    status: 'todo',
    priority: 'medium',
    createdAt: DateTime(2026, 7, 1),
    updatedAt: DateTime(2026, 7, 1),
  );

  test('creates task successfully', () async {
    when(() => mockRepository.createTask(
          projectId: any(named: 'projectId'),
          title: any(named: 'title'),
          priority: any(named: 'priority'),
        )).thenAnswer((_) async => Right(testTask));

    final result = await useCase.execute(
      projectId: 'p1',
      title: 'New Task',
    );

    expect(result.isRight(), true);
    result.fold(
      (l) => fail('Expected Right'),
      (task) => expect(task.title, 'New Task'),
    );
  });

  test('fails with exception', () async {
    when(() => mockRepository.createTask(
          projectId: any(named: 'projectId'),
          title: any(named: 'title'),
        )).thenAnswer((_) async => Left(Exception('Validation failed')));

    final result = await useCase.execute(
      projectId: 'p1',
      title: '',
    );

    expect(result.isLeft(), true);
  });

  test('passes all parameters', () async {
    when(() => mockRepository.createTask(
          projectId: any(named: 'projectId'),
          title: any(named: 'title'),
          description: any(named: 'description'),
          priority: any(named: 'priority'),
          assigneeId: any(named: 'assigneeId'),
          dueDate: any(named: 'dueDate'),
          estimatedMinutes: any(named: 'estimatedMinutes'),
          tags: any(named: 'tags'),
        )).thenAnswer((_) async => Right(testTask));

    await useCase.execute(
      projectId: 'p1',
      title: 'Task',
      description: 'Desc',
      priority: 'high',
      assigneeId: 'u1',
      dueDate: DateTime(2026, 7, 10),
      estimatedMinutes: 120,
      tags: ['tag1'],
    );

    verify(() => mockRepository.createTask(
          projectId: 'p1',
          title: 'Task',
          description: 'Desc',
          priority: 'high',
          assigneeId: 'u1',
          dueDate: DateTime(2026, 7, 10),
          estimatedMinutes: 120,
          tags: ['tag1'],
        )).called(1);
  });
}
