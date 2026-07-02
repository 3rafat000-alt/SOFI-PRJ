import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_client.dart';
import '../data/models/user_model.dart';
import '../data/repositories/auth_repository.dart';

final authStateProvider = FutureProvider<UserModel?>((ref) async {
  final storage = ref.read(secureStorageProvider);
  final token = await storage.read(key: 'auth_token');
  if (token == null) return null;
  try {
    final user = await ref.read(authRepositoryProvider).getCurrentUser();
    ref.read(currentUserProvider.notifier).state = user;
    return user;
  } catch (_) {
    return null;
  }
});
