/// Tests for AuthProvider — Riverpod ProviderContainer with overrides.
library;

import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mocktail/mocktail.dart';

import 'package:sakk_wallet/core/network/api_client.dart';
import 'package:sakk_wallet/features/auth/providers/auth_provider.dart';
import 'package:sakk_wallet/features/auth/data/repositories/auth_repository.dart';
import 'package:sakk_wallet/features/auth/data/models/user_model.dart';

import '../../../helpers/mocks.dart';

class _MockAuthProvRepo extends Mock implements AuthRepository {}
class _MockAuthProvStorage extends Mock implements FlutterSecureStorage {}

void main() {
  late AuthRepository mockRepo;
  late FlutterSecureStorage mockStorage;
  late ProviderContainer container;

  setUp(() {
    mockRepo = _MockAuthProvRepo();
    mockStorage = _MockAuthProvStorage();
    // Stub write/delete to return proper Future<void>.
    when(() => mockStorage.write(
          key: any(named: 'key'),
          value: any(named: 'value'),
        )).thenAnswer((_) async {});
    when(() => mockStorage.delete(key: any(named: 'key')))
        .thenAnswer((_) async {});
    container = ProviderContainer(
      overrides: [
        authRepositoryProvider.overrideWithValue(mockRepo),
        secureStorageProvider.overrideWithValue(mockStorage),
      ],
    );
  });

  tearDown(() {
    container.dispose();
  });

  group('authStateProvider', () {
    test('returns UserModel when token exists and getCurrentUser succeeds',
        () async {
      when(() => mockStorage.read(key: 'auth_token')).thenAnswer((_) async => 'valid-token');
      when(() => mockRepo.getCurrentUser()).thenAnswer((_) async => testUser);

      final user = await container.read(authStateProvider.future);

      expect(user, isA<UserModel>());
      expect(user!.id, 1);
      expect(user.email, 'ahmad@example.com');
    });

    test('returns null when no token', () async {
      when(() => mockStorage.read(key: 'auth_token')).thenAnswer((_) async => null);

      final user = await container.read(authStateProvider.future);

      expect(user, isNull);
      verifyNever(() => mockRepo.getCurrentUser());
    });

    test('returns null when getCurrentUser throws', () async {
      when(() => mockStorage.read(key: 'auth_token')).thenAnswer((_) async => 'valid-token');
      when(() => mockRepo.getCurrentUser()).thenThrow(Exception('Network error'));

      final user = await container.read(authStateProvider.future);

      expect(user, isNull);
    });
  });

  group('currentUserProvider', () {
    test('initial state is null', () {
      expect(container.read(currentUserProvider), isNull);
    });

    test('can be updated with user', () {
      container.read(currentUserProvider.notifier).state = testUser;

      expect(container.read(currentUserProvider), testUser);
    });

    test('can be set back to null', () {
      container.read(currentUserProvider.notifier).state = testUser;
      container.read(currentUserProvider.notifier).state = null;

      expect(container.read(currentUserProvider), isNull);
    });
  });
}
