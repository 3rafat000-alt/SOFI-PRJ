import 'package:flutter_test/flutter_test.dart';
import 'package:mocktail/mocktail.dart';
import 'package:dartz/dartz.dart' hide Task;
import 'package:tasksync_pro/features/task/domain/entities/task.dart';
import 'package:tasksync_pro/features/task/domain/repositories/task_repository.dart';
import 'package:tasksync_pro/features/task/domain/usecases/update_task_usecase.dart';

class MockTaskRepository extends Mock implements TaskRepository {}

void main() {
  late UpdateTaskUseCase useCase;
  late MockTaskRepository mockRepository;

  setUp(() {
    mockRepository = MockTaskRepository();
    useCase = UpdateTaskUseCase(mockRepository);
  });

  final testTask = Task(
    id: '1',
    projectId: 'p1',
    title: 'Updated Task',
    status: 'in_progress',
    priority: 'high',
    createdAt: DateTime(2026, 7, 1),
    updatedAt: DateTime(2026, 7, 1),
  );

  test('updates task status', () async {
    when(() => mockRepository.updateTask(
          id: any(named: 'id'),
          status: any(named: 'status'),
        )).thenAnswer((_) async => Right(testTask));

    final result = await useCase.execute(id: '1', status: 'in_progress');

    expect(result.isRight(), true);
    result.fold(
      (l) => fail('Expected Right'),
      (task) => expect(task.status, 'in_progress'),
    );
  });

  test('fails with exception', () async {
    when(() => mockRepository.updateTask(
          id: any(named: 'id'),
        )).thenAnswer((_) async => Left(Exception('Not found')));

    final result = await useCase.execute(id: '999');

    expect(result.isLeft(), true);
  });

  test('passes optional fields', () async {
    when(() => mockRepository.updateTask(
          id: any(named: 'id'),
          title: any(named: 'title'),
          description: any(named: 'description'),
          priority: any(named: 'priority'),
          assigneeId: any(named: 'assigneeId'),
          dueDate: any(named: 'dueDate'),
          estimatedMinutes: any(named: 'estimatedMinutes'),
        )).thenAnswer((_) async => Right(testTask));

    await useCase.execute(
      id: '1',
      title: 'Updated',
      description: 'New desc',
      priority: 'urgent',
      assigneeId: 'u2',
      dueDate: DateTime(2026, 8, 1),
      estimatedMinutes: 180,
    );

    verify(() => mockRepository.updateTask(
          id: '1',
          title: 'Updated',
          description: 'New desc',
          priority: 'urgent',
          assigneeId: 'u2',
          dueDate: DateTime(2026, 8, 1),
          estimatedMinutes: 180,
        )).called(1);
  });
}
