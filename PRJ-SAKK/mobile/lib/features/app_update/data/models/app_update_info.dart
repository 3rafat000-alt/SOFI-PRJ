import 'package:equatable/equatable.dart';

/// Force-update policy served by `GET /api/v1/app/version`.
///
/// The backend decides [updateRequired] for the build we send it, so the gate
/// logic lives server-side; the app just honours the flag. We still keep the
/// raw [minBuild]/[forceUpdate] values for a local fallback decision when the
/// caller could not pass its build.
class AppUpdateInfo extends Equatable {
  final bool enabled;
  final String minVersion;
  final int minBuild;
  final String latestVersion;
  final int latestBuild;
  final bool forceUpdate;
  final bool updateRequired;
  final String downloadUrl;
  final String title;
  final String message;

  const AppUpdateInfo({
    required this.enabled,
    required this.minVersion,
    required this.minBuild,
    required this.latestVersion,
    required this.latestBuild,
    required this.forceUpdate,
    required this.updateRequired,
    required this.downloadUrl,
    required this.title,
    required this.message,
  });

  factory AppUpdateInfo.fromJson(Map<String, dynamic> json) {
    int asInt(dynamic v) => v is int ? v : int.tryParse('${v ?? ''}') ?? 0;
    bool asBool(dynamic v) => v == true || v == 1 || v == '1';

    return AppUpdateInfo(
      enabled: asBool(json['enabled']),
      minVersion: (json['min_version'] ?? '').toString(),
      minBuild: asInt(json['min_build']),
      latestVersion: (json['latest_version'] ?? '').toString(),
      latestBuild: asInt(json['latest_build']),
      forceUpdate: asBool(json['force_update']),
      updateRequired: asBool(json['update_required']),
      downloadUrl: (json['download_url'] ?? '').toString(),
      title: (json['title'] ?? 'تحديث مطلوب').toString(),
      message: (json['message'] ?? '').toString(),
    );
  }

  /// Whether [installedBuild] must update. Trusts the server-computed
  /// [updateRequired] (it already knows our build) but also re-derives locally
  /// as a defensive fallback.
  bool requiresUpdate(int installedBuild) {
    if (!enabled) return false;
    if (updateRequired) return true;
    if (installedBuild <= 0) return false; // unknown build → never brick
    return forceUpdate || installedBuild < minBuild;
  }

  @override
  List<Object?> get props => [
        enabled,
        minVersion,
        minBuild,
        latestVersion,
        latestBuild,
        forceUpdate,
        updateRequired,
        downloadUrl,
        title,
        message,
      ];
}
