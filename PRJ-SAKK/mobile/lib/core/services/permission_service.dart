import 'package:permission_handler/permission_handler.dart';

/// Central runtime-permission helper.
///
/// Each feature plugin (mobile_scanner, image_picker, flutter_contacts,
/// geolocator, firebase_messaging) requests its own permission, but Android
/// throws a SecurityException for camera once CAMERA is declared in the
/// manifest unless it is granted first — and on permanent denial the user is
/// left with no way out. This wraps `permission_handler` to give every call
/// site a uniform "request, then offer settings on permanent denial" path.
class PermissionService {
  /// Request camera. Returns true when usable (granted or limited).
  static Future<bool> ensureCamera() async {
    final status = await Permission.camera.request();
    return status.isGranted || status.isLimited;
  }

  static Future<bool> isCameraPermanentlyDenied() =>
      Permission.camera.isPermanentlyDenied;

  static Future<bool> isContactsPermanentlyDenied() =>
      Permission.contacts.isPermanentlyDenied;

  /// Open the OS app-settings page so the user can grant a permission they
  /// previously denied permanently. Returns false if it could not be opened.
  static Future<bool> openSettings() => openAppSettings();
}
