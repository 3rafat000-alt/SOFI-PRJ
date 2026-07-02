import 'dart:io';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:uuid/uuid.dart';

/// Provides a stable per-install device identifier (persisted in secure
/// storage) plus a human-friendly name/type, used for the connected-devices
/// security feature. The id is sent as the `X-Device-Id` header on every
/// request so the backend can gate transactions per device.
class DeviceService {
  static const _storage = FlutterSecureStorage(
    aOptions: AndroidOptions(encryptedSharedPreferences: true),
    iOptions: IOSOptions(accessibility: KeychainAccessibility.first_unlock),
  );
  static const _key = 'sakk_device_id';

  static String? _cachedId;

  /// Stable device id — generated once per install and persisted.
  static Future<String> getDeviceId() async {
    if (_cachedId != null) return _cachedId!;
    var id = await _storage.read(key: _key);
    if (id == null || id.isEmpty) {
      id = const Uuid().v4();
      await _storage.write(key: _key, value: id);
    }
    _cachedId = id;
    return id;
  }

  static String deviceName() {
    if (Platform.isAndroid) return 'هاتف Android';
    if (Platform.isIOS) return 'iPhone';
    return 'جهاز';
  }

  static String deviceType() => Platform.isIOS ? 'ios' : 'android';
}
