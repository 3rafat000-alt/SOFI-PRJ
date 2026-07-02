import 'package:dartz/dartz.dart';
import '../entities/notification.dart';

abstract class NotificationRepository {
  Future<Either<Exception, List<AppNotification>>> getNotifications({
    String? type,
    bool? read,
    int page = 1,
    int perPage = 20,
  });

  Future<Either<Exception, void>> markAsRead(String id);

  Future<Either<Exception, int>> markAllAsRead();
}
