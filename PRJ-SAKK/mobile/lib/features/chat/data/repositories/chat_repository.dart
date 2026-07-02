import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/constants/api_constants.dart';
import '../../../../core/network/api_client.dart';
import '../models/chat_message_model.dart';

final chatRepositoryProvider = Provider<ChatRepository>((ref) {
  return ChatRepository(ref.read(dioProvider));
});

/// REST client for the customer live-chat (polling transport). All calls map
/// DioException → ApiException so the UI shows the backend's Arabic reason.
class ChatRepository {
  final Dio _dio;

  ChatRepository(this._dio);

  /// Open (or lazily create) the caller's conversation + its full history.
  Future<ChatThread> openConversation() async {
    try {
      final response = await _dio.get(ApiConstants.chatConversation);
      final data = response.data['data'] as Map<String, dynamic>;
      final messages = (data['messages'] as List<dynamic>? ?? [])
          .map((e) => ChatMessageModel.fromJson(e as Map<String, dynamic>))
          .toList();
      return ChatThread(
        conversation: ChatConversationModel.fromJson(
            data['conversation'] as Map<String, dynamic>),
        messages: messages,
      );
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  /// Poll messages after [afterId]. Empty list when nothing new.
  Future<List<ChatMessageModel>> pollMessages({int afterId = 0}) async {
    try {
      final response = await _dio.get(
        ApiConstants.chatMessages,
        queryParameters: {if (afterId > 0) 'after': afterId},
      );
      final data = response.data['data'] as Map<String, dynamic>;
      return (data['messages'] as List<dynamic>? ?? [])
          .map((e) => ChatMessageModel.fromJson(e as Map<String, dynamic>))
          .toList();
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  /// Send a customer message. Returns the persisted message.
  Future<ChatMessageModel> send(String body) async {
    try {
      final response =
          await _dio.post(ApiConstants.chatMessages, data: {'body': body});
      return ChatMessageModel.fromJson(
          response.data['data'] as Map<String, dynamic>);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }
}
