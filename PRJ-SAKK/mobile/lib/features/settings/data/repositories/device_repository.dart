import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/network/api_client.dart';
import '../../../../core/constants/api_constants.dart';
import '../../../../core/services/device_service.dart';
import '../models/device_model.dart';

final deviceRepositoryProvider = Provider<DeviceRepository>((ref) {
  return DeviceRepository(ref.read(dioProvider));
});

final devicesProvider = FutureProvider.autoDispose<List<DeviceModel>>((ref) async {
  return ref.read(deviceRepositoryProvider).getDevices();
});

/// Fires once per app session (watched on the dashboard) so the current device
/// is registered/heartbeated regardless of how the user reached the app.
final deviceRegistrationProvider = FutureProvider<DeviceModel?>((ref) async {
  return ref.read(deviceRepositoryProvider).registerDevice();
});

class DeviceRepository {
  final Dio _dio;

  DeviceRepository(this._dio);

  /// Register / heartbeat the current device. Idempotent — safe to call on
  /// every login & app start. New (non-first) devices land in "pending".
  Future<DeviceModel?> registerDevice() async {
    try {
      final id = await DeviceService.getDeviceId();
      final response = await _dio.post(ApiConstants.deviceRegister, data: {
        'device_id': id,
        'device_name': DeviceService.deviceName(),
        'device_type': DeviceService.deviceType(),
      });
      final data = response.data['data'];
      return data == null ? null : DeviceModel.fromJson(data as Map<String, dynamic>);
    } catch (_) {
      return null; // never block app flow on registration
    }
  }

  Future<List<DeviceModel>> getDevices() async {
    try {
      final response = await _dio.get(ApiConstants.devices);
      final List<dynamic> data = response.data['data'] ?? [];
      return data
          .map((e) => DeviceModel.fromJson(e as Map<String, dynamic>))
          .toList();
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  Future<void> approveDevice(int id) async {
    try {
      await _dio.post(ApiConstants.deviceApprove(id));
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  Future<void> rejectDevice(int id) async {
    try {
      await _dio.post(ApiConstants.deviceReject(id));
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  Future<void> removeDevice(int id) async {
    try {
      await _dio.delete(ApiConstants.deviceById(id));
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }
}
