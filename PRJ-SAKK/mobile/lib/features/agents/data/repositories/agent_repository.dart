import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/network/api_client.dart';
import '../../../../core/constants/api_constants.dart';
import '../models/agent_model.dart';

final agentRepositoryProvider = Provider<AgentRepository>((ref) {
  return AgentRepository(ref.read(dioProvider));
});

/// Query key for the nearby-agents provider (records give value-equality so the
/// FutureProvider caches/refetches correctly as the filter or location change).
typedef AgentQuery = ({double? lat, double? lng, String? service, String? q});

final agentsProvider =
    FutureProvider.family<List<AgentModel>, AgentQuery>((ref, query) async {
  return ref.read(agentRepositoryProvider).getAgents(
        lat: query.lat,
        lng: query.lng,
        service: query.service,
        query: query.q,
      );
});

final agentDetailProvider =
    FutureProvider.family<AgentModel, int>((ref, id) async {
  return ref.read(agentRepositoryProvider).getAgent(id);
});

class AgentRepository {
  final Dio _dio;

  AgentRepository(this._dio);

  Future<List<AgentModel>> getAgents({
    double? lat,
    double? lng,
    String? service,
    String? city,
    String? query,
    int? limit,
  }) async {
    try {
      final response = await _dio.get(ApiConstants.agents, queryParameters: {
        if (lat != null) 'lat': lat,
        if (lng != null) 'lng': lng,
        if (service != null) 'service': service,
        if (city != null && city.isNotEmpty) 'city': city,
        if (query != null && query.isNotEmpty) 'q': query,
        if (limit != null) 'limit': limit,
      });
      final List<dynamic> data = response.data['data'] ?? [];
      return data
          .map((e) => AgentModel.fromJson(e as Map<String, dynamic>))
          .toList();
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  Future<AgentModel> getAgent(int id) async {
    try {
      final response = await _dio.get(ApiConstants.agentById(id));
      return AgentModel.fromJson(response.data['data'] as Map<String, dynamic>);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }
}
