import 'package:flutter_test/flutter_test.dart';
import 'package:mocktail/mocktail.dart';
import 'package:dartz/dartz.dart';
import 'package:bloc_test/bloc_test.dart';
import 'package:tasksync_pro/features/time_tracking/domain/entities/time_entry.dart';
import 'package:tasksync_pro/features/time_tracking/domain/repositories/time_entry_repository.dart';
import 'package:tasksync_pro/features/time_tracking/domain/usecases/start_timer_usecase.dart';
import 'package:tasksync_pro/features/time_tracking/domain/usecases/stop_timer_usecase.dart';
import 'package:tasksync_pro/features/time_tracking/domain/usecases/get_time_entries_usecase.dart';
import 'package:tasksync_pro/features/time_tracking/presentation/bloc/time_entry_bloc.dart';

class MockStartTimerUseCase extends Mock implements StartTimerUseCase {}
class MockStopTimerUseCase extends Mock implements StopTimerUseCase {}
class MockGetTimeEntriesUseCase extends Mock implements GetTimeEntriesUseCase {}

void main() {
  late TimeEntryBloc bloc;
  late MockStartTimerUseCase mockStartTimer;
  late MockStopTimerUseCase mockStopTimer;
  late MockGetTimeEntriesUseCase mockGetEntries;

  setUp(() {
    mockStartTimer = MockStartTimerUseCase();
    mockStopTimer = MockStopTimerUseCase();
    mockGetEntries = MockGetTimeEntriesUseCase();
    bloc = TimeEntryBloc(
      startTimerUseCase: mockStartTimer,
      stopTimerUseCase: mockStopTimer,
      getTimeEntriesUseCase: mockGetEntries,
    );
  });

  tearDown(() {
    bloc.close();
  });

  final testEntry = TimeEntry(
    id: '1',
    taskId: 't1',
    userId: 'u1',
    startedAt: DateTime(2026, 7, 5, 9, 0),
    endedAt: DateTime(2026, 7, 5, 10, 30),
    durationMinutes: 90,
    isRunning: false,
    createdAt: DateTime(2026, 7, 5, 9, 0),
  );

  group('StartTimerEvent', () {
    blocTest<TimeEntryBloc, TimeEntryState>(
      'emits TimerRunning on success',
      build: () {
        when(() => mockStartTimer.execute(
              taskId: any(named: 'taskId'),
              note: any(named: 'note'),
            )).thenAnswer((_) async =>
                Right(testEntry.copyWith(isRunning: true, endedAt: null)));
        return bloc;
      },
      act: (bloc) => bloc.add(const StartTimerEvent(taskId: 't1')),
      expect: () => [isA<TimerRunning>()],
    );

    blocTest<TimeEntryBloc, TimeEntryState>(
      'emits Error on failure',
      build: () {
        when(() => mockStartTimer.execute(
              taskId: any(named: 'taskId'),
              note: any(named: 'note'),
            )).thenAnswer((_) async =>
                Left(Exception('Timer already running')));
        return bloc;
      },
      act: (bloc) => bloc.add(const StartTimerEvent(taskId: 't1')),
      expect: () => [isA<TimeEntryError>()],
    );
  });

  group('StopTimerEvent', () {
    blocTest<TimeEntryBloc, TimeEntryState>(
      'emits TimerStopped on success',
      build: () {
        when(() => mockStopTimer.execute(
              note: any(named: 'note'),
            )).thenAnswer((_) async => Right(testEntry));
        return bloc;
      },
      act: (bloc) => bloc.add(const StopTimerEvent(note: 'Done')),
      expect: () => [
        isA<TimerStopped>().having(
          (s) => s.entry.durationMinutes,
          'duration',
          90,
        ),
      ],
    );

    blocTest<TimeEntryBloc, TimeEntryState>(
      'emits Error on failure',
      build: () {
        when(() => mockStopTimer.execute(
              note: any(named: 'note'),
            )).thenAnswer((_) async =>
                Left(Exception('No active timer')));
        return bloc;
      },
      act: (bloc) => bloc.add(const StopTimerEvent()),
      expect: () => [isA<TimeEntryError>()],
    );
  });

  group('LoadTimeEntriesEvent', () {
    blocTest<TimeEntryBloc, TimeEntryState>(
      'emits EntriesLoaded on success',
      build: () {
        when(() => mockGetEntries.execute(
              taskId: any(named: 'taskId'),
              from: any(named: 'from'),
              to: any(named: 'to'),
            )).thenAnswer((_) async => Right([testEntry]));
        return bloc;
      },
      act: (bloc) => bloc.add(const LoadTimeEntriesEvent()),
      expect: () => [
        isA<TimeEntryLoading>(),
        isA<TimeEntriesLoaded>().having(
          (s) => s.entries.length,
          'length',
          1,
        ),
      ],
    );

    blocTest<TimeEntryBloc, TimeEntryState>(
      'emits Error on failure',
      build: () {
        when(() => mockGetEntries.execute()).thenAnswer(
          (_) async => Left(Exception('Failed to load')),
        );
        return bloc;
      },
      act: (bloc) => bloc.add(const LoadTimeEntriesEvent()),
      expect: () => [
        isA<TimeEntryLoading>(),
        isA<TimeEntryError>(),
      ],
    );
  });

  group('TickEvent', () {
    blocTest<TimeEntryBloc, TimeEntryState>(
      'updates elapsed time when TimerRunning',
      build: () => bloc,
      seed: () => TimerRunning(
        taskId: 't1',
        elapsed: const Duration(seconds: 5),
        startedAt: DateTime(2026, 7, 5, 9, 0),
      ),
      act: (bloc) => bloc.add(const TickEvent(Duration(seconds: 10))),
      expect: () => [
        isA<TimerRunning>().having(
          (s) => s.elapsed.inSeconds,
          'elapsed',
          10,
        ),
      ],
    );
  });

  group('close', () {
    test('cancels tick timer', () {
      // Should not throw
      bloc.close();
    });
  });

  test('initial state is TimeEntryInitial', () {
    expect(bloc.state, isA<TimeEntryInitial>());
  });
}
