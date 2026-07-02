import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:sakk_wallet/features/app_update/data/models/app_update_info.dart';
import 'package:sakk_wallet/features/app_update/data/repositories/app_update_repository.dart';
import 'package:sakk_wallet/features/app_update/presentation/pages/force_update_page.dart';
import 'package:sakk_wallet/features/app_update/presentation/widgets/update_gate.dart';

AppUpdateInfo _info({required bool required}) => AppUpdateInfo(
      enabled: true,
      minVersion: '1.0.1',
      minBuild: 3,
      latestVersion: '1.0.1',
      latestBuild: 3,
      forceUpdate: required,
      updateRequired: required,
      downloadUrl: 'https://sakk.zanjour.com/download/sakk.apk',
      title: 'تحديث مطلوب',
      message: 'حدّث',
    );

void main() {
  testWidgets('blocks with ForceUpdatePage when update required', (tester) async {
    await tester.pumpWidget(ProviderScope(
      overrides: [
        forceUpdateCheckProvider
            .overrideWith((ref) async => _info(required: true)),
      ],
      child: const MaterialApp(
        home: UpdateGate(child: Scaffold(body: Text('APP_HOME'))),
      ),
    ));
    await tester.pumpAndSettle();

    expect(find.byType(ForceUpdatePage), findsOneWidget);
    expect(find.text('APP_HOME'), findsNothing);
    expect(find.text('تحديث الآن'), findsOneWidget);
  });

  testWidgets('shows app when no update required (null)', (tester) async {
    await tester.pumpWidget(ProviderScope(
      overrides: [
        forceUpdateCheckProvider.overrideWith((ref) async => null),
      ],
      child: const MaterialApp(
        home: UpdateGate(child: Scaffold(body: Text('APP_HOME'))),
      ),
    ));
    await tester.pumpAndSettle();

    expect(find.byType(ForceUpdatePage), findsNothing);
    expect(find.text('APP_HOME'), findsOneWidget);
  });

  test('requiresUpdate logic', () {
    // force flag blocks every build
    expect(_info(required: true).requiresUpdate(99), isTrue);
    // server says required → block
    expect(_info(required: true).requiresUpdate(2), isTrue);
    // not required + build above floor → allow
    final ok = AppUpdateInfo(
      enabled: true,
      minVersion: '1.0.0',
      minBuild: 2,
      latestVersion: '1.0.0',
      latestBuild: 2,
      forceUpdate: false,
      updateRequired: false,
      downloadUrl: '',
      title: '',
      message: '',
    );
    expect(ok.requiresUpdate(2), isFalse); // 2 == floor → allowed
    expect(ok.requiresUpdate(1), isTrue); // 1 < floor 2 → blocked
  });
}
