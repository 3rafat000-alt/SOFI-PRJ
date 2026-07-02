import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/constants/api_constants.dart';
import '../../../../core/network/api_client.dart';
import '../models/support_contact_model.dart';
import '../models/support_models.dart';

final supportRepositoryProvider = Provider<SupportRepository>((ref) {
  return SupportRepository(ref.read(dioProvider));
});

/// Live admin-managed contact channels for the "تواصل معنا" screen.
final supportContactProvider =
    FutureProvider<SupportContactModel>((ref) async {
  return ref.read(supportRepositoryProvider).getContact();
});

/// The signed-in user's tickets (newest activity first).
final ticketsProvider = FutureProvider<List<SupportTicketModel>>((ref) async {
  return ref.read(supportRepositoryProvider).getTickets();
});

/// Selectable categories for the "new ticket" form.
final ticketCategoriesProvider =
    FutureProvider<List<TicketCategory>>((ref) async {
  return ref.read(supportRepositoryProvider).getCategories();
});

/// One ticket with its public thread.
final ticketDetailProvider =
    FutureProvider.family<SupportTicketModel, String>((ref, uuid) async {
  return ref.read(supportRepositoryProvider).getTicket(uuid);
});

class SupportRepository {
  final Dio _dio;

  SupportRepository(this._dio);

  Future<SupportContactModel> getContact() async {
    try {
      final response = await _dio.get(ApiConstants.appSupport);
      return SupportContactModel.fromJson(
          response.data['data'] as Map<String, dynamic>);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  Future<List<TicketCategory>> getCategories() async {
    try {
      final response = await _dio.get(ApiConstants.supportCategories);
      return (response.data['data'] as List<dynamic>? ?? [])
          .map((e) => TicketCategory.fromJson(e as Map<String, dynamic>))
          .toList();
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  Future<List<SupportTicketModel>> getTickets() async {
    try {
      final response = await _dio.get(ApiConstants.supportTickets);
      return (response.data['data'] as List<dynamic>? ?? [])
          .map((e) => SupportTicketModel.fromJson(e as Map<String, dynamic>))
          .toList();
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  Future<SupportTicketModel> getTicket(String uuid) async {
    try {
      final response = await _dio.get(ApiConstants.supportTicket(uuid));
      return SupportTicketModel.fromJson(
          response.data['data'] as Map<String, dynamic>);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  /// Open a new ticket — the description becomes the first message.
  Future<SupportTicketModel> createTicket({
    required String subject,
    required String description,
    String? category,
    String? priority,
  }) async {
    try {
      final response = await _dio.post(ApiConstants.supportTickets, data: {
        'subject': subject,
        'description': description,
        if (category != null) 'category': category,
        if (priority != null) 'priority': priority,
      });
      return SupportTicketModel.fromJson(
          response.data['data'] as Map<String, dynamic>);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  /// Customer reply — reopens a resolved/closed ticket server-side.
  Future<SupportTicketModel> reply(String uuid, String message) async {
    try {
      final response = await _dio.post(
        ApiConstants.supportTicketReply(uuid),
        data: {'message': message},
      );
      return SupportTicketModel.fromJson(
          response.data['data'] as Map<String, dynamic>);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }
}
