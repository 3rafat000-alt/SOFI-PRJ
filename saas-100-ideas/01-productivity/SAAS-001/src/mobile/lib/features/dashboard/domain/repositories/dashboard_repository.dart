import 'package:dartz/dartz.dart';
import '../entities/dashboard.dart';

abstract class DashboardRepository {
  Future<Either<Exception, DashboardStats>> getStats({
    required String workspaceId,
  });

  Future<Either<Exception, List<ActivityItem>>> getActivity({
    required String workspaceId,
    int limit = 20,
  });
}
