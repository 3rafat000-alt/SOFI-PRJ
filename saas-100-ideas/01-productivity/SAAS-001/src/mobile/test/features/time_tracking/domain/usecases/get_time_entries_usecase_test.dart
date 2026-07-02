import 'package:flutter_test/flutter_test.dart';
import 'package:mocktail/mocktail.dart';
import 'package:dartz/dartz.dart';
import 'package:tasksync_pro/features/time_tracking/domain/entities/time_entry.dart';
import 'package:tasksync_pro/features/time_tracking/domain/repositories/time_entry_repository.dart';
import 'package:tasksync_pro/features/time_tracking/domain/usecases/get_time_entries_usecase.dart';

class MockTimeEntryRepository extends Mock implements TimeEntryRepository {}

void main() {
  late GetTimeEntriesUseCase useCase;
  late MockTimeEntryRepository mockRepository;

  setUp(() {
    mockRepository = MockTimeEntryRepository();
    useCase = GetTimeEntriesUseCase(mockRepository);
  });

  final testEntry = TimeEntry(
    id: '1',
    taskId: 't1',
    userId: 'u1',
    startedAt: DateTime(2026, 7, 5, 9, 0),
    endedAt: DateTime(2026, 7, 5, 10, 0),
    durationMinutes: 60,
    isRunning: false,
    createdAt: DateTime(2026, 7, 5, 9, 0),
  );

  test('returns entries', () async {
    when(() => mockRepository.getTimeEntries(
          userId: any(named: 'userId'),
          page: any(named: 'page'),
        )).thenAnswer((_) async => Right([testEntry]));

    final result = await useCase.execute();

    expect(result.isRight(), true);
    result.fold(
      (l) => fail('Expected Right'),
      (entries) => expect(entries, hasLength(1)),
    );
  });

  test('filters by date range', () async {
    when(() => mockRepository.getTimeEntries(
          from: any(named: 'from'),
          to: any(named: 'to'),
          page: any(named: 'page'),
        )).thenAnswer((_) async => Right([testEntry]));

    await useCase.execute(
      from: DateTime(2026, 7, 1),
      to: DateTime(2026, 7, 31),
    );

    verify(() => mockRepository.getTimeEntries(
          from: DateTime(2026, 7, 1),
          to: DateTime(2026, 7, 31),
          page: 1,
        )).called(1);
  });

  test('returns empty list', () async {
    when(() => mockRepository.getTimeEntries(
          page: any(named: 'page'),
        )).thenAnswer((_) async => const Right([]));

    final result = await useCase.execute();

    result.fold(
      (l) => fail('Expected Right'),
      (entries) => expect(entries, isEmpty),
    );
  });
}
