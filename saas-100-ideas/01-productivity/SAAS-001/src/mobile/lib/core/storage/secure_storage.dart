import 'dart:convert';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

class SecureStorageService {
  final FlutterSecureStorage _storage;

  SecureStorageService()
      : _storage = const FlutterSecureStorage(
          aOptions: AndroidOptions(encryptedSharedPreferences: true),
        );

  // Auth token
  Future<void> saveToken(String token) async {
    await _storage.write(key: 'auth_token', value: token);
  }

  Future<String?> getToken() async {
    return await _storage.read(key: 'auth_token');
  }

  Future<void> deleteToken() async {
    await _storage.delete(key: 'auth_token');
  }

  // User data
  Future<void> saveUserData(Map<String, dynamic> userData) async {
    final json = jsonEncode(userData);
    await _storage.write(key: 'user_data', value: json);
  }

  Future<Map<String, dynamic>?> getUserData() async {
    final json = await _storage.read(key: 'user_data');
    if (json == null) return null;
    try {
      return jsonDecode(json) as Map<String, dynamic>;
    } catch (_) {
      return null;
    }
  }

  Future<void> deleteUserData() async {
    await _storage.delete(key: 'user_data');
  }

  // Locale preference
  Future<void> saveLocale(String locale) async {
    await _storage.write(key: 'locale', value: locale);
  }

  Future<String?> getLocale() async {
    return await _storage.read(key: 'locale');
  }

  // Current workspace
  Future<void> saveCurrentWorkspaceId(String id) async {
    await _storage.write(key: 'current_workspace_id', value: id);
  }

  Future<String?> getCurrentWorkspaceId() async {
    return await _storage.read(key: 'current_workspace_id');
  }

  // Generic key-value
  Future<void> save(String key, String value) async {
    await _storage.write(key: key, value: value);
  }

  Future<String?> read(String key) async {
    return await _storage.read(key: key);
  }

  Future<void> delete(String key) async {
    await _storage.delete(key: key);
  }

  // Clear all
  Future<void> clearAll() async {
    await _storage.deleteAll();
  }
}
