import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../data/repositories/app_update_repository.dart';
import '../pages/force_update_page.dart';

/// Wraps the whole app. On boot it resolves [forceUpdateCheckProvider]; if the
/// installed build is below the admin-set floor it replaces the entire UI with
/// the blocking [ForceUpdatePage]. Otherwise it shows [child] unchanged.
///
/// Re-checks every time the app returns to the foreground, so a policy the
/// admin flips while the app is merely backgrounded (not cold-killed) still
/// bites on the next resume — a plain FutureProvider would only fire once per
/// process and miss that.
///
/// Fail-open by design: while the check is in flight or if it errors
/// (offline / backend down), [child] is shown — a flaky network must never
/// brick a paying user. The kill-switch only bites when the device is online.
class UpdateGate extends ConsumerStatefulWidget {
  final Widget child;
  const UpdateGate({super.key, required this.child});

  @override
  ConsumerState<UpdateGate> createState() => _UpdateGateState();
}

class _UpdateGateState extends ConsumerState<UpdateGate>
    with WidgetsBindingObserver {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      // Re-pull the policy on every foreground; ignored if nothing changed.
      ref.invalidate(forceUpdateCheckProvider);
    }
  }

  @override
  Widget build(BuildContext context) {
    final check = ref.watch(forceUpdateCheckProvider);

    return check.maybeWhen(
      data: (info) => info != null ? ForceUpdatePage(info: info) : widget.child,
      orElse: () => widget.child,
    );
  }
}
