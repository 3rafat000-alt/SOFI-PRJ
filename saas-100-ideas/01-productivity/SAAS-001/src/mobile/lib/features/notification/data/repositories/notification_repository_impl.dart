import 'package:dartz/dartz.dart';
import 'package:flutter/foundation.dart';
import '../../../../core/network/api_exceptions.dart';
import '../../domain/entities/notification.dart';
import '../../domain/repositories/notification_repository.dart';
import '../datasources/notification_remote_source.dart';
import '../models/notification_models.dart';

class NotificationRepositoryImpl implements NotificationRepository {
  final NotificationRemoteSource _remoteSource;

  NotificationRepositoryImpl(this._remoteSource);

  @override
  Future<Either<Exception, List<AppNotification>>> getNotifications({
    String? type,
    bool? read,
    int page = 1,
    int perPage = 20,
  }) async {
    try {
      final dto = await _remoteSource.getNotifications(
        type: type,
        read: read,
        page: page,
        perPage: perPage,
      );
      final notifications = dto.notifications.map((n) => AppNotification(
            id: n.id,
            type: n.type,
            title: n.title,
            body: n.body,
            data: n.data,
            readAt: n.readAt != null ? DateTime.parse(n.readAt!) : null,
            createdAt: n.createdAt != null
                ? DateTime.parse(n.createdAt!)
                : DateTime.now(),
          )).toList();
      return Right(notifications);
    } on ApiException catch (e) {
      return Left(e);
    } catch (e) {
      return Left(Exception('Failed to load notifications'));
    }
  }

  @override
  Future<Either<Exception, void>> markAsRead(String id) async {
    try {
      await _remoteSource.markAsRead(id);
      return const Right(null);
    } on ApiException catch (e) {
      return Left(e);
    } catch (e) {
      return Left(Exception('Failed to mark as read'));
    }
  }

  @override
  Future<Either<Exception, int>> markAllAsRead() async {
    try {
      final dto = await _remoteSource.markAllAsRead();
      return Right(dto.count);
    } on ApiException catch (e) {
      return Left(e);
    } catch (e) {
      return Left(Exception('Failed to mark all as read'));
    }
  }
}
