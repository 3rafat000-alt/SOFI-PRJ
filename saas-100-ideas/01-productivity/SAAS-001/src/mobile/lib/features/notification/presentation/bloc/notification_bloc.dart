import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:equatable/equatable.dart';
import '../../domain/entities/notification.dart';
import '../../domain/repositories/notification_repository.dart';

// Events
abstract class NotificationEvent extends Equatable {
  const NotificationEvent();
  @override
  List<Object?> get props => [];
}

class LoadNotificationsEvent extends NotificationEvent {}

class MarkAsReadEvent extends NotificationEvent {
  final String id;

  const MarkAsReadEvent({required this.id});
  @override
  List<Object?> get props => [id];
}

class MarkAllAsReadEvent extends NotificationEvent {}

// States
abstract class NotificationState extends Equatable {
  const NotificationState();
  @override
  List<Object?> get props => [];
}

class NotificationInitial extends NotificationState {}

class NotificationsLoading extends NotificationState {}

class NotificationsLoaded extends NotificationState {
  final List<AppNotification> notifications;
  final int unreadCount;

  const NotificationsLoaded({
    required this.notifications,
    this.unreadCount = 0,
  });
  @override
  List<Object?> get props => [notifications, unreadCount];
}

class NotificationError extends NotificationState {
  final String message;

  const NotificationError({required this.message});
  @override
  List<Object?> get props => [message];
}

// Bloc
class NotificationBloc extends Bloc<NotificationEvent, NotificationState> {
  final NotificationRepository _repository;

  NotificationBloc({required NotificationRepository repository})
      : _repository = repository,
        super(NotificationInitial()) {
    on<LoadNotificationsEvent>(_onLoad);
    on<MarkAsReadEvent>(_onMarkAsRead);
    on<MarkAllAsReadEvent>(_onMarkAllAsRead);
  }

  Future<void> _onLoad(
      LoadNotificationsEvent event, Emitter<NotificationState> emit) async {
    emit(NotificationsLoading());
    final result = await _repository.getNotifications();
    result.fold(
      (failure) => emit(NotificationError(message: _mapError(failure))),
      (notifications) => emit(NotificationsLoaded(
        notifications: notifications,
        unreadCount: notifications.where((n) => !n.isRead).length,
      )),
    );
  }

  Future<void> _onMarkAsRead(
      MarkAsReadEvent event, Emitter<NotificationState> emit) async {
    await _repository.markAsRead(event.id);
    // Reload
    add(LoadNotificationsEvent());
  }

  Future<void> _onMarkAllAsRead(
      MarkAllAsReadEvent event, Emitter<NotificationState> emit) async {
    await _repository.markAllAsRead();
    add(LoadNotificationsEvent());
  }

  String _mapError(Object error) {
    if (error is Exception) {
      return error.toString().replaceFirst('Exception: ', '');
    }
    return 'An unexpected error occurred';
  }
}
