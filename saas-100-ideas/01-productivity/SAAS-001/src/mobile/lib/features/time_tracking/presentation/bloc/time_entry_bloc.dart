import 'dart:async';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:equatable/equatable.dart';
import '../../domain/entities/time_entry.dart';
import '../../domain/usecases/start_timer_usecase.dart';
import '../../domain/usecases/stop_timer_usecase.dart';
import '../../domain/usecases/get_time_entries_usecase.dart';

// Events
abstract class TimeEntryEvent extends Equatable {
  const TimeEntryEvent();
  @override
  List<Object?> get props => [];
}

class StartTimerEvent extends TimeEntryEvent {
  final String taskId;
  final String? note;

  const StartTimerEvent({required this.taskId, this.note});
  @override
  List<Object?> get props => [taskId, note];
}

class StopTimerEvent extends TimeEntryEvent {
  final String? note;

  const StopTimerEvent({this.note});
  @override
  List<Object?> get props => [note];
}

class LoadTimeEntriesEvent extends TimeEntryEvent {
  final String? taskId;
  final DateTime? from;
  final DateTime? to;

  const LoadTimeEntriesEvent({this.taskId, this.from, this.to});
  @override
  List<Object?> get props => [taskId, from, to];
}

class TickEvent extends TimeEntryEvent {
  final Duration elapsed;

  const TickEvent(this.elapsed);
  @override
  List<Object?> get props => [elapsed];
}

// States
abstract class TimeEntryState extends Equatable {
  const TimeEntryState();
  @override
  List<Object?> get props => [];
}

class TimeEntryInitial extends TimeEntryState {}

class TimeEntryLoading extends TimeEntryState {}

class TimerRunning extends TimeEntryState {
  final String taskId;
  final Duration elapsed;
  final DateTime startedAt;

  const TimerRunning({
    required this.taskId,
    required this.elapsed,
    required this.startedAt,
  });
  @override
  List<Object?> get props => [taskId, elapsed, startedAt];
}

class TimerStopped extends TimeEntryState {
  final TimeEntry entry;

  const TimerStopped({required this.entry});
  @override
  List<Object?> get props => [entry];
}

class TimeEntriesLoaded extends TimeEntryState {
  final List<TimeEntry> entries;

  const TimeEntriesLoaded({required this.entries});
  @override
  List<Object?> get props => [entries];
}

class TimeEntryError extends TimeEntryState {
  final String message;

  const TimeEntryError({required this.message});
  @override
  List<Object?> get props => [message];
}

// Bloc
class TimeEntryBloc extends Bloc<TimeEntryEvent, TimeEntryState> {
  final StartTimerUseCase _startTimerUseCase;
  final StopTimerUseCase _stopTimerUseCase;
  final GetTimeEntriesUseCase _getTimeEntriesUseCase;

  Timer? _tickTimer;
  DateTime? _timerStart;

  TimeEntryBloc({
    required StartTimerUseCase startTimerUseCase,
    required StopTimerUseCase stopTimerUseCase,
    required GetTimeEntriesUseCase getTimeEntriesUseCase,
  })  : _startTimerUseCase = startTimerUseCase,
        _stopTimerUseCase = stopTimerUseCase,
        _getTimeEntriesUseCase = getTimeEntriesUseCase,
        super(TimeEntryInitial()) {
    on<StartTimerEvent>(_onStartTimer);
    on<StopTimerEvent>(_onStopTimer);
    on<LoadTimeEntriesEvent>(_onLoadEntries);
    on<TickEvent>(_onTick);
  }

  @override
  Future<void> close() {
    _tickTimer?.cancel();
    return super.close();
  }

  Future<void> _onStartTimer(
      StartTimerEvent event, Emitter<TimeEntryState> emit) async {
    final result = await _startTimerUseCase.execute(
      taskId: event.taskId,
      note: event.note,
    );
    result.fold(
      (failure) => emit(TimeEntryError(message: _mapError(failure))),
      (entry) {
        _timerStart = entry.startedAt ?? DateTime.now();
        _startTicking();
        emit(TimerRunning(
          taskId: event.taskId,
          elapsed: Duration.zero,
          startedAt: _timerStart!,
        ));
      },
    );
  }

  Future<void> _onStopTimer(
      StopTimerEvent event, Emitter<TimeEntryState> emit) async {
    _tickTimer?.cancel();
    final result = await _stopTimerUseCase.execute(note: event.note);
    result.fold(
      (failure) => emit(TimeEntryError(message: _mapError(failure))),
      (entry) => emit(TimerStopped(entry: entry)),
    );
  }

  Future<void> _onLoadEntries(
      LoadTimeEntriesEvent event, Emitter<TimeEntryState> emit) async {
    emit(TimeEntryLoading());
    final result = await _getTimeEntriesUseCase.execute(
      taskId: event.taskId,
      from: event.from,
      to: event.to,
    );
    result.fold(
      (failure) => emit(TimeEntryError(message: _mapError(failure))),
      (entries) => emit(TimeEntriesLoaded(entries: entries)),
    );
  }

  void _onTick(TickEvent event, Emitter<TimeEntryState> emit) {
    final current = state;
    if (current is TimerRunning) {
      emit(TimerRunning(
        taskId: current.taskId,
        elapsed: event.elapsed,
        startedAt: current.startedAt,
      ));
    }
  }

  void _startTicking() {
    _tickTimer?.cancel();
    final start = DateTime.now();
    _tickTimer = Timer.periodic(const Duration(seconds: 1), (_) {
      add(TickEvent(DateTime.now().difference(start)));
    });
  }

  String _mapError(Object error) {
    if (error is Exception) {
      return error.toString().replaceFirst('Exception: ', '');
    }
    return 'An unexpected error occurred';
  }
}
