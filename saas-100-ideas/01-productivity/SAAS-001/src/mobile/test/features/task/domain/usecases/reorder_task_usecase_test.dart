import 'package:flutter_test/flutter_test.dart';
import 'package:mocktail/mocktail.dart';
import 'package:dartz/dartz.dart';
import 'package:tasksync_pro/features/task/domain/repositories/task_repository.dart';
import 'package:tasksync_pro/features/task/domain/usecases/reorder_task_usecase.dart';

class MockTaskRepository extends Mock implements TaskRepository {}

void main() {
  late ReorderTaskUseCase useCase;
  late MockTaskRepository mockRepository;

  setUp(() {
    mockRepository = MockTaskRepository();
    useCase = ReorderTaskUseCase(mockRepository);
  });

  test('reorders tasks successfully', () async {
    when(() => mockRepository.reorderTasks(
          projectId: any(named: 'projectId'),
          orders: any(named: 'orders'),
        )).thenAnswer((_) async => const Right(null));

    final orders = [
      const OrderEntry(id: '1', status: 'todo', position: 2),
      const OrderEntry(id: '2', status: 'in_progress', position: 1),
    ];

    final result = await useCase.execute(projectId: 'p1', orders: orders);

    expect(result.isRight(), true);
  });

  test('fails with exception', () async {
    when(() => mockRepository.reorderTasks(
          projectId: any(named: 'projectId'),
          orders: any(named: 'orders'),
        )).thenAnswer((_) async => Left(Exception('Reorder failed')));

    final result = await useCase.execute(
      projectId: 'p1',
      orders: [const OrderEntry(id: '1', status: 'todo', position: 1)],
    );

    expect(result.isLeft(), true);
  });
}
