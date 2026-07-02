import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:equatable/equatable.dart';
import '../../domain/entities/dashboard.dart';
import '../../domain/repositories/dashboard_repository.dart';

// Events
abstract class DashboardEvent extends Equatable {
  const DashboardEvent();
  @override
  List<Object?> get props => [];
}

class LoadDashboardEvent extends DashboardEvent {
  final String workspaceId;

  const LoadDashboardEvent({required this.workspaceId});
  @override
  List<Object?> get props => [workspaceId];
}

// States
abstract class DashboardState extends Equatable {
  const DashboardState();
  @override
  List<Object?> get props => [];
}

class DashboardInitial extends DashboardState {}

class DashboardLoading extends DashboardState {}

class DashboardLoaded extends DashboardState {
  final DashboardStats stats;
  final List<ActivityItem> activity;

  const DashboardLoaded({
    required this.stats,
    this.activity = const [],
  });
  @override
  List<Object?> get props => [stats, activity];
}

class DashboardError extends DashboardState {
  final String message;

  const DashboardError({required this.message});
  @override
  List<Object?> get props => [message];
}

// Bloc
class DashboardBloc extends Bloc<DashboardEvent, DashboardState> {
  final DashboardRepository _repository;

  DashboardBloc({required DashboardRepository repository})
      : _repository = repository,
        super(DashboardInitial()) {
    on<LoadDashboardEvent>(_onLoad);
  }

  Future<void> _onLoad(
      LoadDashboardEvent event, Emitter<DashboardState> emit) async {
    emit(DashboardLoading());
    final statsResult = await _repository.getStats(workspaceId: event.workspaceId);
    final activityResult =
        await _repository.getActivity(workspaceId: event.workspaceId);

    statsResult.fold(
      (failure) => emit(DashboardError(message: _mapError(failure))),
      (stats) {
        List<ActivityItem> activity = [];
        activityResult.fold(
          (_) => activity = [],
          (items) => activity = items,
        );
        emit(DashboardLoaded(stats: stats, activity: activity));
      },
    );
  }

  String _mapError(Object error) {
    if (error is Exception) {
      return error.toString().replaceFirst('Exception: ', '');
    }
    return 'An unexpected error occurred';
  }
}
