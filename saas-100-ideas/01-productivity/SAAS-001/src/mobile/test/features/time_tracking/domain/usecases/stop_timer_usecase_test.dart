import 'package:flutter_test/flutter_test.dart';
import 'package:mocktail/mocktail.dart';
import 'package:dartz/dartz.dart';
import 'package:tasksync_pro/features/time_tracking/domain/entities/time_entry.dart';
import 'package:tasksync_pro/features/time_tracking/domain/repositories/time_entry_repository.dart';
import 'package:tasksync_pro/features/time_tracking/domain/usecases/stop_timer_usecase.dart';

class MockTimeEntryRepository extends Mock implements TimeEntryRepository {}

void main() {
  late StopTimerUseCase useCase;
  late MockTimeEntryRepository mockRepository;

  setUp(() {
    mockRepository = MockTimeEntryRepository();
    useCase = StopTimerUseCase(mockRepository);
  });

  final testEntry = TimeEntry(
    id: '1',
    taskId: 't1',
    userId: 'u1',
    startedAt: DateTime(2026, 7, 5, 9, 0),
    endedAt: DateTime(2026, 7, 5, 10, 30),
    durationMinutes: 90,
    isRunning: false,
    note: 'Done',
    createdAt: DateTime(2026, 7, 5, 9, 0),
  );

  test('stops timer successfully', () async {
    when(() => mockRepository.stopTimer(
          note: any(named: 'note'),
        )).thenAnswer((_) async => Right(testEntry));

    final result = await useCase.execute(note: 'Done');

    expect(result.isRight(), true);
    result.fold(
      (l) => fail('Expected Right'),
      (entry) {
        expect(entry.isRunning, false);
        expect(entry.durationMinutes, 90);
        expect(entry.note, 'Done');
      },
    );
  });

  test('fails when no active timer', () async {
    when(() => mockRepository.stopTimer(
          note: any(named: 'note'),
        )).thenAnswer((_) async => Left(Exception('No active timer')));

    final result = await useCase.execute();

    expect(result.isLeft(), true);
  });

  test('stops without note', () async {
    when(() => mockRepository.stopTimer(
          note: any(named: 'note'),
        )).thenAnswer((_) async => Right(testEntry));

    final result = await useCase.execute();

    expect(result.isRight(), true);
    verify(() => mockRepository.stopTimer(note: null)).called(1);
  });
}
