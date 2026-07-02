import 'package:flutter_test/flutter_test.dart';
import 'package:mocktail/mocktail.dart';
import 'package:dartz/dartz.dart';
import 'package:tasksync_pro/features/time_tracking/domain/entities/time_entry.dart';
import 'package:tasksync_pro/features/time_tracking/domain/repositories/time_entry_repository.dart';
import 'package:tasksync_pro/features/time_tracking/domain/usecases/start_timer_usecase.dart';

class MockTimeEntryRepository extends Mock implements TimeEntryRepository {}

void main() {
  late StartTimerUseCase useCase;
  late MockTimeEntryRepository mockRepository;

  setUp(() {
    mockRepository = MockTimeEntryRepository();
    useCase = StartTimerUseCase(mockRepository);
  });

  final testEntry = TimeEntry(
    id: '1',
    taskId: 't1',
    userId: 'u1',
    startedAt: DateTime(2026, 7, 5, 9, 0),
    isRunning: true,
    createdAt: DateTime(2026, 7, 5, 9, 0),
  );

  test('starts timer successfully', () async {
    when(() => mockRepository.startTimer(
          taskId: any(named: 'taskId'),
          note: any(named: 'note'),
        )).thenAnswer((_) async => Right(testEntry));

    final result = await useCase.execute(taskId: 't1', note: 'Starting');

    expect(result.isRight(), true);
    result.fold(
      (l) => fail('Expected Right'),
      (entry) {
        expect(entry.isRunning, true);
        expect(entry.taskId, 't1');
      },
    );
  });

  test('fails when timer already running', () async {
    when(() => mockRepository.startTimer(
          taskId: any(named: 'taskId'),
          note: any(named: 'note'),
        )).thenAnswer((_) async => Left(Exception('Timer already running')));

    final result = await useCase.execute(taskId: 't1');

    expect(result.isLeft(), true);
  });

  test('starts timer without note', () async {
    when(() => mockRepository.startTimer(
          taskId: any(named: 'taskId'),
          note: any(named: 'note'),
        )).thenAnswer((_) async => Right(testEntry));

    final result = await useCase.execute(taskId: 't1');

    expect(result.isRight(), true);
    verify(() => mockRepository.startTimer(
          taskId: 't1',
          note: null,
        )).called(1);
  });
}
