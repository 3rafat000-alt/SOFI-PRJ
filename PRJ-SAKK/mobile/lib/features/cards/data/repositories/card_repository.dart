import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../../../../core/network/api_client.dart';
import '../../../../core/constants/api_constants.dart';
import '../../../transactions/data/models/transaction_model.dart';
import '../models/card_model.dart';

final cardRepositoryProvider = Provider<CardRepository>((ref) {
  return CardRepository(ref.read(dioProvider));
});

/// The card the user chose to highlight as "featured" on the cards page.
/// Persisted locally per device via shared_preferences (null = auto-select).
final featuredCardIdProvider =
    StateNotifierProvider<FeaturedCardNotifier, int?>(
        (ref) => FeaturedCardNotifier());

class FeaturedCardNotifier extends StateNotifier<int?> {
  FeaturedCardNotifier() : super(null) {
    _load();
  }

  static const _key = 'featured_card_id';

  Future<void> _load() async {
    final prefs = await SharedPreferences.getInstance();
    state = prefs.getInt(_key);
  }

  Future<void> setFeatured(int id) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setInt(_key, id);
    state = id;
  }
}

final cardsProvider = FutureProvider<List<CardModel>>((ref) async {
  return ref.read(cardRepositoryProvider).getCards();
});

/// Whether the virtual-cards feature is live (gated server-side on Stripe
/// Issuing). Fails closed: if we can't confirm, treat as disabled so the tab
/// shows "coming soon" rather than a broken cards screen.
final cardsEnabledProvider = FutureProvider<bool>((ref) async {
  return ref.read(cardRepositoryProvider).cardsEnabled();
});

final cardProvider = FutureProvider.family<CardModel, int>((ref, id) async {
  return ref.read(cardRepositoryProvider).getCard(id);
});

final cardTransactionsProvider =
    FutureProvider.family<List<TransactionModel>, int>((ref, id) async {
  return ref.read(cardRepositoryProvider).getCardTransactions(id);
});

class CardRepository {
  final Dio _dio;
  
  CardRepository(this._dio);
  
  Future<List<CardModel>> getCards() async {
    try {
      final response = await _dio.get(ApiConstants.cards);
      final List<dynamic> data = response.data['data'];
      return data.map((json) => CardModel.fromJson(json)).toList();
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  /// Reads the public feature flag. Returns false on any error (fail closed).
  Future<bool> cardsEnabled() async {
    try {
      final response = await _dio.get('/features');
      return response.data['data']?['cards_enabled'] == true;
    } catch (_) {
      return false;
    }
  }
  
  Future<CardModel> getCard(int id) async {
    try {
      final response = await _dio.get(ApiConstants.cardById(id));
      return CardModel.fromJson(response.data['data']);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  Future<List<TransactionModel>> getCardTransactions(int id) async {
    try {
      final response = await _dio.get(ApiConstants.cardTransactions(id));
      final List<dynamic> data = response.data['data'] ?? [];
      return data.map((j) => TransactionModel.fromJson(j)).toList();
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }
  
  Future<CardModel> createCard({
    required int walletId,
    required String brand,
    String type = 'virtual',
    String? nickname,
    String? color,
    double? spendingLimit,
  }) async {
    try {
      final response = await _dio.post(ApiConstants.cardCreate, data: {
        'wallet_id': walletId,
        'brand': brand,
        if (nickname != null && nickname.isNotEmpty) 'nickname': nickname,
        if (color != null) 'color': color,
        if (spendingLimit != null) 'spending_limit': spendingLimit,
      });
      return CardModel.fromJson(response.data['data']);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }
  
  Future<CardDetails> getCardDetails(int id) async {
    try {
      final response = await _dio.post(ApiConstants.cardDetails(id));
      return CardDetails.fromJson(response.data['data']);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }
  
  Future<CardModel> freezeCard(int id) async {
    try {
      final response = await _dio.post(ApiConstants.cardFreeze(id));
      return CardModel.fromJson(response.data['data']);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }
  
  Future<CardModel> unfreezeCard(int id) async {
    try {
      final response = await _dio.post(ApiConstants.cardUnfreeze(id));
      return CardModel.fromJson(response.data['data']);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }
  
  Future<void> loadCard({
    required int cardId,
    required int walletId,
    required double amount,
    required bool isLoad,
  }) async {
    try {
      final endpoint = isLoad ? ApiConstants.cardLoad(cardId) : ApiConstants.cardUnload(cardId);
      await _dio.post(endpoint, data: {
        'wallet_id': walletId,
        'amount': amount,
      });
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }
  
  Future<void> cancelCard(int id) async {
    try {
      await _dio.post(ApiConstants.cardCancel(id));
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }
}
