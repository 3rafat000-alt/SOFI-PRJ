import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/theme/app_colors.dart';
import '../../features/transfer/data/nfc_reader.dart';

class MainShell extends ConsumerStatefulWidget {
  final Widget child;

  const MainShell({super.key, required this.child});

  @override
  ConsumerState<MainShell> createState() => _MainShellState();
}

class _MainShellState extends ConsumerState<MainShell> {
  DateTime? _lastBackPress;
  bool _nfcHandled = false;

  @override
  void initState() {
    super.initState();
    // A payment tapped via NFC before unlock — show it now that we're in.
    WidgetsBinding.instance
        .addPostFrameCallback((_) => _maybeHandlePendingNfc());
  }

  void _maybeHandlePendingNfc() {
    if (_nfcHandled || !mounted) return;
    final pending = ref.read(pendingNfcPaymentProvider);
    if (pending == null) return;
    _nfcHandled = true;
    ref.read(pendingNfcPaymentProvider.notifier).state = null;
    context.push('/nfc-pay', extra: pending);
  }

  int _calculateSelectedIndex(BuildContext context) {
    final location = GoRouterState.of(context).uri.path;
    if (location.startsWith('/dashboard')) return 0;
    if (location.startsWith('/cards')) return 1;
    // QR button is index 2 (center)
    if (location.startsWith('/transactions')) return 3;
    if (location.startsWith('/settings')) return 4;
    return 0;
  }

  void _onItemTapped(BuildContext context, int index) {
    switch (index) {
      case 0: context.go('/dashboard'); break;
      case 1: context.go('/cards'); break;
      case 2: _showQRActionSheet(context); break; // QR center button
      case 3: context.go('/transactions'); break;
      case 4: context.go('/settings'); break;
    }
  }

  Future<bool> _onWillPop() async {
    final now = DateTime.now();
    final location = GoRouterState.of(context).uri.path;
    
    // If we're on the main tabs, show exit confirmation
    final mainTabs = ['/dashboard', '/cards', '/transactions', '/settings'];
    final isOnMainTab = mainTabs.any((tab) => location == tab || location.startsWith('$tab/'));
    
    if (isOnMainTab && (location == '/dashboard' || location == '/cards' || 
        location == '/transactions' || location == '/settings')) {
      if (_lastBackPress != null && now.difference(_lastBackPress!) < const Duration(seconds: 2)) {
        SystemNavigator.pop();
        return true;
      }
      _lastBackPress = now;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('اضغط مرة أخرى للخروج'),
          duration: Duration(seconds: 2),
          behavior: SnackBarBehavior.floating,
        ),
      );
      return false;
    }
    
    // If we're on a sub-page, go back normally
    return true;
  }

  @override
  Widget build(BuildContext context) {
    // Cold-start NFC payment that arrived after this shell mounted.
    ref.listen<NfcPayment?>(pendingNfcPaymentProvider, (_, next) {
      if (next != null) {
        WidgetsBinding.instance
            .addPostFrameCallback((_) => _maybeHandlePendingNfc());
      }
    });

    final selectedIndex = _calculateSelectedIndex(context);
    final colors = context.appColors;
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, result) async {
        if (!didPop) {
          final shouldPop = await _onWillPop();
          if (shouldPop && context.mounted && context.canPop()) {
            context.pop();
          }
        }
      },
      child: Scaffold(
        body: widget.child,
        bottomNavigationBar: SafeArea(
          child: Container(
            height: 80,
            decoration: BoxDecoration(
              color: colors.surface,
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withValues(alpha: isDark ? 0.4 : 0.08),
                  blurRadius: 25,
                  offset: const Offset(0, -5),
                ),
              ],
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceEvenly,
              children: [
                _NavItem(
                  icon: Icons.home_rounded,
                  label: 'الرئيسية',
                  isSelected: selectedIndex == 0,
                  onTap: () => _onItemTapped(context, 0),
                ),
                _NavItem(
                  icon: Icons.credit_card_rounded,
                  label: 'البطاقات',
                  isSelected: selectedIndex == 1,
                  onTap: () => _onItemTapped(context, 1),
                ),
                // QR Center Button
                GestureDetector(
                  onTap: () => _showQRActionSheet(context),
                  child: Container(
                    width: 64,
                    height: 64,
                    margin: const EdgeInsets.only(bottom: 8),
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                        colors: colors.cardGradientVisa,
                      ),
                      shape: BoxShape.circle,
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withValues(alpha: 0.35),
                          blurRadius: 12,
                          offset: const Offset(0, 4),
                        ),
                      ],
                    ),
                    child: const Icon(
                      Icons.qr_code_scanner_rounded,
                      color: Colors.white,
                      size: 30,
                    ),
                  ),
                ),
                _NavItem(
                  icon: Icons.receipt_long_rounded,
                  label: 'المعاملات',
                  isSelected: selectedIndex == 3,
                  onTap: () => _onItemTapped(context, 3),
                ),
                _NavItem(
                  icon: Icons.settings_rounded,
                  label: 'الإعدادات',
                  isSelected: selectedIndex == 4,
                  onTap: () => _onItemTapped(context, 4),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  void _showQRActionSheet(BuildContext context) {
    final colors = context.appColors;
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: colors.surface,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (context) => SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.fromLTRB(24, 12, 24, 24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Center(
                child: Container(
                  width: 40,
                  height: 4,
                  decoration: BoxDecoration(
                    color: colors.inputBackground,
                    borderRadius: BorderRadius.circular(2),
                  ),
                ),
              ),
              const SizedBox(height: 16),
              Text(
                'رمز QR',
                style: TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.bold,
                    color: colors.textPrimary),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 20),
              _QRActionCard(
                icon: Icons.qr_code_rounded,
                title: 'استلام',
                subtitle: 'عرض رقم الحساب ورمز QR',
                color: colors.success,
                onTap: () {
                  Navigator.pop(context);
                  context.push('/qr-receive');
                },
              ),
              const SizedBox(height: 12),
              _QRActionCard(
                icon: Icons.qr_code_scanner_rounded,
                title: 'إرسال',
                subtitle: 'مسح رمز QR أو إدخال رقم الحساب',
                color: colors.primary,
                onTap: () {
                  Navigator.pop(context);
                  context.push('/qr-send');
                },
              ),
              const SizedBox(height: 12),
              _QRActionCard(
                icon: Icons.request_page_rounded,
                title: 'طلب دفعة',
                subtitle: 'عبر رابط/QR أو من صديق',
                color: colors.secondary,
                onTap: () {
                  Navigator.pop(context);
                  context.push('/request-money');
                },
              ),
              const SizedBox(height: 12),
              _QRActionCard(
                icon: Icons.move_to_inbox_rounded,
                title: 'الطلبات الواردة',
                subtitle: 'طلبات دفع موجّهة إليك',
                color: colors.success,
                onTap: () {
                  Navigator.pop(context);
                  context.push('/received-requests');
                },
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _QRActionCard extends StatelessWidget {
  final IconData icon;
  final String title;
  final String subtitle;
  final Color color;
  final VoidCallback onTap;

  const _QRActionCard({
    required this.icon,
    required this.title,
    required this.subtitle,
    required this.color,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(16),
      child: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          border: Border.all(color: colors.inputBackground),
          borderRadius: BorderRadius.circular(16),
          color: colors.surface,
        ),
        child: Row(
          children: [
            Container(
              width: 56,
              height: 56,
              decoration: BoxDecoration(
                color: color.withValues(alpha: 0.1),
                borderRadius: BorderRadius.circular(16),
              ),
              child: Icon(icon, color: color, size: 28),
            ),
            const SizedBox(width: 16),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    title,
                    style: TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.bold,
                      color: colors.textPrimary,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    subtitle,
                    style: TextStyle(
                      fontSize: 13,
                      color: colors.textSecondary,
                    ),
                  ),
                ],
              ),
            ),
            Icon(
              Icons.arrow_forward_ios_rounded,
              color: colors.textHint,
              size: 20,
            ),
          ],
        ),
      ),
    );
  }
}

class _NavItem extends StatelessWidget {
  final IconData icon;
  final String label;
  final bool isSelected;
  final VoidCallback onTap;
  
  const _NavItem({
    required this.icon,
    required this.label,
    required this.isSelected,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    return GestureDetector(
      onTap: onTap,
      behavior: HitTestBehavior.opaque,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
        decoration: BoxDecoration(
          color: isSelected ? colors.primaryLight : Colors.transparent,
          borderRadius: BorderRadius.circular(12),
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(
              icon,
              color: isSelected ? colors.primary : colors.textHint,
              size: 24,
            ),
            const SizedBox(height: 4),
            Text(
              label,
              style: TextStyle(
                fontSize: 10,
                fontWeight: isSelected ? FontWeight.w600 : FontWeight.normal,
                color: isSelected ? colors.primary : colors.textHint,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
