import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:package_info_plus/package_info_plus.dart';

import '../../../../core/constants/api_constants.dart';
import '../../../../core/network/api_client.dart';
import '../models/app_update_info.dart';

final appUpdateRepositoryProvider = Provider<AppUpdateRepository>((ref) {
  return AppUpdateRepository(ref.read(dioProvider));
});

class AppUpdateRepository {
  final Dio _dio;
  AppUpdateRepository(this._dio);

  /// Fetch the force-update policy, telling the server our installed build so
  /// it can decide [AppUpdateInfo.updateRequired] for us.
  Future<AppUpdateInfo> getPolicy({required int installedBuild}) async {
    final response = await _dio.get(
      ApiConstants.appVersion,
      queryParameters: {'build': installedBuild, 'platform': 'android'},
    );
    final data = (response.data['data'] ?? {}) as Map<String, dynamic>;
    return AppUpdateInfo.fromJson(data);
  }
}

/// The installed build number (versionCode) — `0` if it can't be read.
final installedBuildProvider = FutureProvider<int>((ref) async {
  final info = await PackageInfo.fromPlatform();
  return int.tryParse(info.buildNumber) ?? 0;
});

/// Resolved gate state for app boot. Returns `null` (fail-open) on any error —
/// a flaky network or unreachable backend must never block a paying user.
final forceUpdateCheckProvider = FutureProvider<AppUpdateInfo?>((ref) async {
  try {
    final build = await ref.watch(installedBuildProvider.future);
    final policy =
        await ref.read(appUpdateRepositoryProvider).getPolicy(installedBuild: build);
    return policy.requiresUpdate(build) ? policy : null;
  } catch (_) {
    return null; // fail-open
  }
});
