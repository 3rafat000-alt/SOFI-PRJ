import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter/services.dart';
import 'package:iconsax/iconsax.dart';
import 'package:flutter_animate/flutter_animate.dart';

import '../../../../core/theme/app_colors.dart';
import '../../../../core/utils/money_formatter.dart';
import '../../../../core/network/api_client.dart';
import '../../../../core/widgets/app_skeleton.dart';
import '../../../../core/widgets/app_ui.dart';
import '../../data/repositories/wallet_repository.dart';

class WalletDetailsPage extends ConsumerWidget {
  final int walletId;

  const WalletDetailsPage({super.key, required this.walletId});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final colors = context.appColors;
    final walletAsync = ref.watch(walletProvider(walletId));

    return walletAsync.when(
      loading: () => Scaffold(
        backgroundColor: colors.background,
        body: const SafeArea(child: SkeletonWalletScene()),
      ),
      error: (error, _) => Scaffold(
        backgroundColor: colors.background,
        appBar: AppBar(),
        body: Center(child: Text(error.toString())),
      ),
      data: (wallet) => AppScaffold(
        title: wallet.currency == 'USD' ? 'محفظة الدولار' : 'محفظة الليرة',
        action: IconButton(
          onPressed: () => _showActionsMenu(context, ref),
          icon: Icon(Iconsax.element_plus, color: colors.primary),
          tooltip: 'العمليات',
        ),
        body: ListView(
          padding: const EdgeInsets.fromLTRB(20, 8, 20, 32),
          children: [
                      Column(
                        children: [
                          Container(
                            width: double.infinity,
                            padding: const EdgeInsets.all(24),
                            decoration: BoxDecoration(
                              gradient: LinearGradient(
                                colors: wallet.currency == 'USD'
                                    ? [colors.walletUSD, const Color(0xFF15803D)]
                                    : [colors.walletSYP, const Color(0xFF1D4ED8)],
                                begin: Alignment.topLeft,
                                end: Alignment.bottomRight,
                              ),
                              borderRadius: BorderRadius.circular(24),
                              boxShadow: [
                                BoxShadow(
                                  color: (wallet.currency == 'USD' ? colors.walletUSD : colors.walletSYP).withValues(alpha: 0.4),
                                  blurRadius: 24,
                                  offset: const Offset(0, 12),
                                ),
                              ],
                            ),
                            child: Stack(
                              children: [
                                Positioned(
                                  right: -20, top: -20,
                                  child: Container(
                                    width: 100, height: 100,
                                    decoration: BoxDecoration(
                                      shape: BoxShape.circle,
                                      color: Colors.white.withValues(alpha: 0.1),
                                    ),
                                  ),
                                ),
                                Column(
                                  children: [
                                    Text(
                                      'الرصيد المتاح',
                                      style: TextStyle(
                                        color: Colors.white.withValues(alpha: 0.9),
                                        fontSize: 14,
                                      ),
                                    ),
                                    const SizedBox(height: 8),
                                    Text(
                                      wallet.formattedBalance,
                                      style: const TextStyle(
                                        color: Colors.white,
                                        fontSize: 36,
                                        fontWeight: FontWeight.bold,
                                      ),
                                    ),
                                    if (wallet.pendingBalance > 0) ...[
                                      const SizedBox(height: 8),
                                      Row(
                                        mainAxisAlignment: MainAxisAlignment.center,
                                        children: [
                                          const Icon(Iconsax.timer_1, color: Colors.white70, size: 14),
                                          const SizedBox(width: 4),
                                          Text(
                                            'معلق: ${wallet.formattedPending}',
                                            style: TextStyle(
                                              color: Colors.white.withValues(alpha: 0.8),
                                              fontSize: 13,
                                            ),
                                          ),
                                        ],
                                      ),
                                    ],
                                  ],
                                ),
                              ],
                            ),
                          ).animate().fadeIn().slideY(begin: 0.1),
                        ],
                      ),
          ],
        ),
      ),
    );
  }

  void _showDepositSheet(BuildContext context, WidgetRef ref) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      useSafeArea: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (context) => _DepositMethodSheet(),
    );
  }

  void _showWithdrawSheet(BuildContext context, WidgetRef ref) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      useSafeArea: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (context) => _WithdrawMethodSheet(),
    );
  }

  void _showActionsMenu(BuildContext context, WidgetRef ref) {
    final colors = context.appColors;
    showModalBottomSheet(
      context: context,
      backgroundColor: colors.surface,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (sheetContext) => Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Center(
              child: Container(
                width: 40,
                height: 4,
                decoration: BoxDecoration(
                  color: colors.textHint.withValues(alpha: 0.4),
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
            ),
            const SizedBox(height: 20),
            _menuRow(sheetContext, Iconsax.money_recive, 'إيداع', () {
              Navigator.pop(sheetContext);
              _showDepositSheet(context, ref);
            }),
            const SizedBox(height: 10),
            _menuRow(sheetContext, Iconsax.arrow_swap_horizontal, 'صرف', () {
              Navigator.pop(sheetContext);
              _showExchangeSheet(context, ref);
            }),
            const SizedBox(height: 10),
            _menuRow(sheetContext, Iconsax.money_send, 'سحب', () {
              Navigator.pop(sheetContext);
              _showWithdrawSheet(context, ref);
            }),
            const SizedBox(height: 12),
          ],
        ),
      ),
    );
  }

  Widget _menuRow(
      BuildContext context, IconData icon, String label, VoidCallback onTap) {
    final colors = context.appColors;
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(16),
      child: Container(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          border: Border.all(color: colors.inputBackground),
          borderRadius: BorderRadius.circular(16),
        ),
        child: Row(
          children: [
            Container(
              width: 44,
              height: 44,
              decoration: BoxDecoration(
                color: colors.primary.withValues(alpha: 0.12),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Icon(icon, color: colors.primary, size: 22),
            ),
            const SizedBox(width: 14),
            Text(label,
                style: const TextStyle(
                    fontSize: 15, fontWeight: FontWeight.w600)),
            const Spacer(),
            Icon(Iconsax.arrow_left_2,
                color: colors.textHint, size: 18),
          ],
        ),
      ),
    );
  }

  void _showExchangeSheet(BuildContext context, WidgetRef ref) {
    final wallets = ref.read(walletsProvider);
    final usdBalance = wallets.valueOrNull?.firstWhere((w) => w.currency == 'USD', orElse: () => wallets.valueOrNull!.first).balance ?? 0.0;
    final sypBalance = wallets.valueOrNull?.firstWhere((w) => w.currency == 'SYP', orElse: () => wallets.valueOrNull!.first).balance ?? 0.0;
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      useSafeArea: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (context) => _ExchangeSheet(usdBalance: usdBalance, sypBalance: sypBalance),
    );
  }
}

class _SheetHandleBar extends StatelessWidget {
  const _SheetHandleBar();

  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    return Center(
      child: Container(
        width: 40, height: 4,
        decoration: BoxDecoration(
          color: colors.textHint.withValues(alpha: 0.4),
          borderRadius: BorderRadius.circular(2),
        ),
      ),
    );
  }
}

// ──────────────────────────────────────────────
// Deposit Method Bottom Sheet (USDT + Agent)
// ──────────────────────────────────────────────
class _DepositMethodSheet extends ConsumerStatefulWidget {
  @override
  ConsumerState<_DepositMethodSheet> createState() => _DepositMethodSheetState();
}

class _DepositMethodSheetState extends ConsumerState<_DepositMethodSheet> {
  String? _selectedMethod;

  @override
  Widget build(BuildContext context) {
    if (_selectedMethod == 'usdt') return const _InlineUsdtDepositSheet();
    if (_selectedMethod == 'agent') return const _AgentDepositSheet();

    final colors = context.appColors;
    return Container(
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(color: colors.surface, borderRadius: const BorderRadius.vertical(top: Radius.circular(24))),
      child: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.stretch, children: [
        const _SheetHandleBar(), const SizedBox(height: 16),
        Row(children: [Icon(Iconsax.direct_down, color: colors.primary), const SizedBox(width: 12), const Text('إيداع', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold))]),
        const SizedBox(height: 24),
        _MethodOptionCard(icon: Iconsax.coin, title: 'USDT', subtitle: 'إيداع عبر USDT (TRC20/ERC20/BEP20)', color: const Color(0xFF26A17B), onTap: () => setState(() => _selectedMethod = 'usdt')),
        const SizedBox(height: 12),
        _MethodOptionCard(icon: Iconsax.people, title: 'وكيل', subtitle: 'إيداع نقدي عبر وكيل معتمد', color: colors.secondary, onTap: () => setState(() => _selectedMethod = 'agent')),
        const SizedBox(height: 16),
      ]),
    );
  }
}

class _WithdrawMethodSheet extends ConsumerStatefulWidget {
  @override
  ConsumerState<_WithdrawMethodSheet> createState() => _WithdrawMethodSheetState();
}

class _WithdrawMethodSheetState extends ConsumerState<_WithdrawMethodSheet> {
  String? _selectedMethod;

  @override
  Widget build(BuildContext context) {
    if (_selectedMethod == 'usdt') return const _InlineUsdtWithdrawSheet();
    if (_selectedMethod == 'agent') return const _AgentWithdrawSheet();

    final colors = context.appColors;
    return Container(
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(color: colors.surface, borderRadius: const BorderRadius.vertical(top: Radius.circular(24))),
      child: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.stretch, children: [
        const _SheetHandleBar(), const SizedBox(height: 16),
        Row(children: [Icon(Iconsax.direct_up, color: colors.primary), const SizedBox(width: 12), const Text('سحب', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold))]),
        const SizedBox(height: 24),
        _MethodOptionCard(icon: Iconsax.coin, title: 'USDT', subtitle: 'سحب عبر USDT (TRC20/ERC20/BEP20)', color: const Color(0xFF26A17B), onTap: () => setState(() => _selectedMethod = 'usdt')),
        const SizedBox(height: 12),
        _MethodOptionCard(icon: Iconsax.people, title: 'وكيل', subtitle: 'سحب نقدي عبر وكيل معتمد', color: colors.secondary, onTap: () => setState(() => _selectedMethod = 'agent')),
        const SizedBox(height: 16),
      ]),
    );
  }
}

class _MethodOptionCard extends StatelessWidget {
  final IconData icon; final String title; final String subtitle; final Color color; final VoidCallback onTap;
  const _MethodOptionCard({required this.icon, required this.title, required this.subtitle, required this.color, required this.onTap});

  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    return InkWell(
      onTap: onTap, borderRadius: BorderRadius.circular(16),
      child: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(border: Border.all(color: colors.inputBackground), borderRadius: BorderRadius.circular(16)),
        child: Row(children: [
          Container(width: 52, height: 52, decoration: BoxDecoration(color: color.withValues(alpha: 0.1), borderRadius: BorderRadius.circular(14)), child: Icon(icon, color: color, size: 24)),
          const SizedBox(width: 14),
          Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text(title, style: const TextStyle(fontSize: 15, fontWeight: FontWeight.bold)),
            const SizedBox(height: 3), Text(subtitle, style: TextStyle(fontSize: 12, color: colors.textSecondary)),
          ])),
          Icon(Iconsax.arrow_left_2, color: colors.textHint, size: 18),
        ]),
      ),
    );
  }
}

// ──────────────────────────────────────────────
// USDT Deposit Bottom Sheet (Inline)
// ──────────────────────────────────────────────
class _InlineUsdtDepositSheet extends ConsumerStatefulWidget {
  const _InlineUsdtDepositSheet();
  @override
  ConsumerState<_InlineUsdtDepositSheet> createState() => _InlineUsdtDepositSheetState();
}

class _InlineUsdtDepositSheetState extends ConsumerState<_InlineUsdtDepositSheet> {
  final _chains = ['TRC20', 'ERC20', 'BEP20'];
  String _selectedChain = 'TRC20';
  bool _isLoading = false;
  Map<String, dynamic>? _depositData;
  String? _error;

  @override void initState() { super.initState(); _createDepositAddress(); }

  Future<void> _createDepositAddress() async {
    setState(() { _isLoading = true; _error = null; });
    try {
      final wallets = await ref.read(walletsProvider.future);
      final usdWallet = wallets.firstWhere((w) => w.currency == 'USD', orElse: () => wallets.first);
      final response = await ref.read(dioProvider).post('/ccpayment/deposit/address', data: {'wallet_id': usdWallet.id, 'chain': _selectedChain, 'currency': 'USDT'});
      if (response.data['success'] == true) {
        setState(() => _depositData = response.data['data']);
      } else {
        setState(() => _error = response.data['message'] ?? 'فشل إنشاء عنوان الإيداع');
      }
    } on DioException catch (e) {
      setState(() => _error = ApiException.fromDioError(e).message);
    } catch (e) {
      setState(() => _error = 'حدث خطأ: ${e.toString()}');
    } finally { setState(() => _isLoading = false); }
  }

  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    return Container(
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(color: colors.surface, borderRadius: const BorderRadius.vertical(top: Radius.circular(24))),
      child: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.stretch, children: [
        const _SheetHandleBar(), const SizedBox(height: 16),
        Row(children: [
          Container(width: 44, height: 44, decoration: BoxDecoration(color: const Color(0xFF26A17B).withValues(alpha: 0.1), borderRadius: BorderRadius.circular(12)),
            child: const Icon(Iconsax.direct_down, color: Color(0xFF26A17B), size: 22)),
          const SizedBox(width: 12),
          Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            const Text('إيداع USDT', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
            Text('اختر الشبكة ثم أرسل المبلغ إلى العنوان أدناه', style: TextStyle(fontSize: 12, color: colors.textSecondary)),
          ]),
        ]),
        const SizedBox(height: 20),
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 16),
          decoration: BoxDecoration(color: colors.inputBackground, borderRadius: BorderRadius.circular(12), border: Border.all(color: colors.inputBackground)),
          child: DropdownButtonHideUnderline(child: DropdownButton<String>(
            value: _selectedChain, isExpanded: true, icon: const Icon(Iconsax.arrow_down_1),
            items: _chains.map((c) => DropdownMenuItem(value: c, child: Row(children: [
              Container(width: 8, height: 8, decoration: BoxDecoration(shape: BoxShape.circle, color: c == 'TRC20' ? const Color(0xFF26A17B) : c == 'ERC20' ? const Color(0xFF627EEA) : const Color(0xFFF3BA2F))),
              const SizedBox(width: 10), Text('USDT-$c', style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 15)),
            ]))).toList(),
            onChanged: (v) { if (v == null) return; setState(() { _selectedChain = v; _depositData = null; }); _createDepositAddress(); },
          )),
        ),
        const SizedBox(height: 24),
        if (_isLoading) const Padding(padding: EdgeInsets.symmetric(vertical: 32), child: Center(child: CircularProgressIndicator()))
        else if (_error != null) _buildError() else if (_depositData != null) _buildAddress() else const SizedBox.shrink(),
      ]),
    );
  }

  Widget _buildAddress() {
    final colors = context.appColors;
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final address = _depositData!['address'] as String? ?? '';
    final memo = _depositData!['memo'] as String?;
    return Column(children: [
      Container(padding: const EdgeInsets.all(16), decoration: BoxDecoration(color: colors.surface, borderRadius: BorderRadius.circular(16), border: Border.all(color: colors.inputBackground)),
        child: Icon(Iconsax.wallet, size: 80, color: colors.textSecondary)),
      const SizedBox(height: 16),
      Container(padding: const EdgeInsets.all(14), decoration: BoxDecoration(color: colors.inputBackground, borderRadius: BorderRadius.circular(12)),
        child: Row(children: [
          Expanded(child: Text(address, style: const TextStyle(fontSize: 12, fontFamily: 'monospace'), textAlign: TextAlign.center)),
          GestureDetector(onTap: () { Clipboard.setData(ClipboardData(text: address)); ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('تم نسخ العنوان'), duration: Duration(seconds: 2))); },
            child: Container(padding: const EdgeInsets.all(8), decoration: BoxDecoration(color: isDark ? colors.surface : colors.primary, borderRadius: BorderRadius.circular(8)), child: Icon(Iconsax.copy, color: isDark ? colors.textPrimary : Colors.white, size: 18))),
        ]),
      ),
      if (memo != null) ...[const SizedBox(height: 8), Container(padding: const EdgeInsets.all(14), decoration: BoxDecoration(color: colors.inputBackground, borderRadius: BorderRadius.circular(12)), child: Row(children: [
        Text('Memo: ', style: TextStyle(fontSize: 12, color: colors.textSecondary)),
        Expanded(child: Text(memo, style: const TextStyle(fontSize: 12, fontFamily: 'monospace'))),
        GestureDetector(onTap: () { Clipboard.setData(ClipboardData(text: memo)); ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('تم نسخ الميمو'), duration: Duration(seconds: 2))); },
          child: Container(padding: const EdgeInsets.all(8), decoration: BoxDecoration(color: isDark ? colors.surface : colors.primary, borderRadius: BorderRadius.circular(8)), child: Icon(Iconsax.copy, color: isDark ? colors.textPrimary : Colors.white, size: 18))),
      ]))],
      const SizedBox(height: 16),
      Container(padding: const EdgeInsets.all(12), decoration: BoxDecoration(color: colors.warningLight.withValues(alpha: 0.3), borderRadius: BorderRadius.circular(12)),
        child: Row(children: [Icon(Iconsax.warning_2, color: colors.warning, size: 18), const SizedBox(width: 8),
          Expanded(child: Text('أرسل USDT فقط على شبكة $_selectedChain. إرسال عملة أخرى قد يؤدي لفقدان الأموال.', style: TextStyle(fontSize: 12, color: colors.warning))),
        ]),
      ),
      const SizedBox(height: 8),
    ]);
  }

  Widget _buildError() {
    final colors = context.appColors;
    return Container(padding: const EdgeInsets.all(20), decoration: BoxDecoration(color: colors.errorLight.withValues(alpha: 0.3), borderRadius: BorderRadius.circular(16)),
      child: Column(children: [Icon(Iconsax.close_circle, color: colors.error, size: 40), const SizedBox(height: 8),
        Text(_error!, style: TextStyle(color: colors.error, fontSize: 13), textAlign: TextAlign.center), const SizedBox(height: 12),
        TextButton.icon(onPressed: _createDepositAddress, icon: const Icon(Iconsax.refresh, size: 18), label: const Text('إعادة المحاولة'))]));
  }
}

class _AgentDepositSheet extends StatelessWidget {
  const _AgentDepositSheet();
  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    return Container(padding: const EdgeInsets.all(24), decoration: BoxDecoration(color: colors.surface, borderRadius: const BorderRadius.vertical(top: Radius.circular(24))),
      child: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.stretch, children: [
        const _SheetHandleBar(), const SizedBox(height: 16),
        Row(children: [Icon(Iconsax.people, color: colors.secondary), const SizedBox(width: 12), const Text('إيداع عبر وكيل', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold))]),
        const SizedBox(height: 8),
        Text('تواصل مع أحد الوكلاء المعتمدين لإتمام عملية الإيداع النقدي.', style: TextStyle(fontSize: 13, color: colors.textSecondary)),
        const SizedBox(height: 24),
        Container(padding: const EdgeInsets.all(16), decoration: BoxDecoration(color: colors.secondary.withValues(alpha: 0.1), borderRadius: BorderRadius.circular(16)),
          child: Row(children: [Icon(Iconsax.info_circle, color: colors.secondary, size: 20), const SizedBox(width: 12),
            Expanded(child: Text('سيتم تفعيل الإيداع عبر الوكيل قريباً', style: TextStyle(fontSize: 13, color: colors.secondary)))]),
        ),
        const SizedBox(height: 24),
      ]),
    );
  }
}

class _AgentWithdrawSheet extends StatelessWidget {
  const _AgentWithdrawSheet();
  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    return Container(padding: const EdgeInsets.all(24), decoration: BoxDecoration(color: colors.surface, borderRadius: const BorderRadius.vertical(top: Radius.circular(24))),
      child: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.stretch, children: [
        const _SheetHandleBar(), const SizedBox(height: 16),
        Row(children: [Icon(Iconsax.people, color: colors.secondary), const SizedBox(width: 12), const Text('سحب عبر وكيل', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold))]),
        const SizedBox(height: 8),
        Text('تواصل مع أحد الوكلاء المعتمدين لإتمام عملية السحب النقدي.', style: TextStyle(fontSize: 13, color: colors.textSecondary)),
        const SizedBox(height: 24),
        Container(padding: const EdgeInsets.all(16), decoration: BoxDecoration(color: colors.secondary.withValues(alpha: 0.1), borderRadius: BorderRadius.circular(16)),
          child: Row(children: [Icon(Iconsax.info_circle, color: colors.secondary, size: 20), const SizedBox(width: 12),
            Expanded(child: Text('سيتم تفعيل السحب عبر الوكيل قريباً', style: TextStyle(fontSize: 13, color: colors.secondary)))]),
        ),
        const SizedBox(height: 24),
      ]),
    );
  }
}

// ──────────────────────────────────────────────
// USDT Withdraw Bottom Sheet (Inline)
// ──────────────────────────────────────────────
class _InlineUsdtWithdrawSheet extends ConsumerStatefulWidget {
  const _InlineUsdtWithdrawSheet();
  @override
  ConsumerState<_InlineUsdtWithdrawSheet> createState() => _InlineUsdtWithdrawSheetState();
}

class _InlineUsdtWithdrawSheetState extends ConsumerState<_InlineUsdtWithdrawSheet> {
  final _formKey = GlobalKey<FormState>();
  final _addressController = TextEditingController();
  final _amountController = TextEditingController();
  final _memoController = TextEditingController();
  final _chains = ['TRC20', 'ERC20', 'BEP20'];
  String _selectedChain = 'TRC20';
  bool _isLoading = false, _isLoadingFee = false;
  Map<String, dynamic>? _feeData;
  String? _error;

  @override void initState() { super.initState(); _fetchFee(); }
  @override void dispose() { _addressController.dispose(); _amountController.dispose(); _memoController.dispose(); super.dispose(); }

  Future<void> _fetchFee() async {
    setState(() => _isLoadingFee = true);
    try {
      // Backend resolves coinId + network; do not send a client-computed coin_id.
      final response = await ref.read(dioProvider).get('/ccpayment/withdraw/fee', queryParameters: {'currency': 'USDT', 'chain': _selectedChain});
      if (response.data['success'] == true) setState(() => _feeData = response.data['data']['fee']);
    } catch (_) {} finally { setState(() => _isLoadingFee = false); }
  }

  Future<void> _withdraw() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() { _isLoading = true; _error = null; });
    try {
      final wallets = await ref.read(walletsProvider.future);
      final usdWallet = wallets.firstWhere((w) => w.currency == 'USD', orElse: () => wallets.first);
      final response = await ref.read(dioProvider).post('/ccpayment/withdraw', data: {
        'wallet_id': usdWallet.id, 'address': _addressController.text.trim(), 'amount': _amountController.text,
        'chain': _selectedChain, 'currency': 'USDT', 'memo': _memoController.text.isEmpty ? null : _memoController.text,
      });
      if (response.data['success'] == true) {
        if (mounted) { final colors = context.appColors; Navigator.pop(context); ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: const Text('✅ تم إرسال طلب السحب'), backgroundColor: colors.success)); }
      } else {
        setState(() => _error = response.data['message'] ?? 'فشل السحب');
      }
    } on DioException catch (e) {
      // اقرأ سبب السيرفر الواضح (KYC/الجهاز/الرصيد) بدل «خطأ تقني 403».
      setState(() => _error = ApiException.fromDioError(e).message);
    } catch (e) { setState(() => _error = 'حدث خطأ: ${e.toString()}'); } finally { setState(() => _isLoading = false); }
  }

  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    final isDark = Theme.of(context).brightness == Brightness.dark;
    return Container(
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(color: colors.surface, borderRadius: const BorderRadius.vertical(top: Radius.circular(24))),
      child: SingleChildScrollView(child: Form(key: _formKey, child: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.stretch, children: [
        const _SheetHandleBar(), const SizedBox(height: 16),
        Row(children: [
          Container(width: 44, height: 44, decoration: BoxDecoration(color: const Color(0xFF26A17B).withValues(alpha: 0.1), borderRadius: BorderRadius.circular(12)), child: const Icon(Iconsax.direct_up, color: Color(0xFF26A17B), size: 22)),
          const SizedBox(width: 12), Column(crossAxisAlignment: CrossAxisAlignment.start, children: [const Text('سحب USDT', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)), Text('أدخل عنوان المحفظة والمبلغ', style: TextStyle(fontSize: 12, color: colors.textSecondary))]),
        ]),
        const SizedBox(height: 20),
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 16),
          decoration: BoxDecoration(color: colors.inputBackground, borderRadius: BorderRadius.circular(12), border: Border.all(color: colors.inputBackground)),
          child: DropdownButtonHideUnderline(child: DropdownButton<String>(
            value: _selectedChain, isExpanded: true, icon: const Icon(Iconsax.arrow_down_1),
            items: _chains.map((c) => DropdownMenuItem(value: c, child: Row(children: [
              Container(width: 8, height: 8, decoration: BoxDecoration(shape: BoxShape.circle, color: c == 'TRC20' ? const Color(0xFF26A17B) : c == 'ERC20' ? const Color(0xFF627EEA) : const Color(0xFFF3BA2F))),
              const SizedBox(width: 10), Text('USDT-$c', style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 15)),
            ]))).toList(),
            onChanged: (v) { if (v == null) return; setState(() { _selectedChain = v; _feeData = null; }); _fetchFee(); },
          )),
        ),
        const SizedBox(height: 20),
        TextFormField(
          controller: _addressController,
          decoration: InputDecoration(labelText: 'عنوان المحفظة', hintText: 'أدخل عنوان التحويل', prefixIcon: const Icon(Iconsax.wallet, size: 20),
            border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)), enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide(color: colors.inputBackground)),
            focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide(color: colors.primary)), filled: true, fillColor: colors.surface),
          validator: (v) => (v == null || v.isEmpty) ? 'عنوان المحفظة مطلوب' : null,
        ),
        const SizedBox(height: 12),
        TextFormField(
          controller: _amountController, keyboardType: TextInputType.number,
          decoration: InputDecoration(labelText: 'المبلغ (USDT)', hintText: '0.00', prefixIcon: const Icon(Iconsax.coin, size: 20),
            border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)), enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide(color: colors.inputBackground)),
            focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide(color: colors.primary)), filled: true, fillColor: colors.surface),
          validator: (v) { if (v == null || v.isEmpty) return 'المبلغ مطلوب'; if (double.tryParse(v) == null || double.parse(v) <= 0) return 'المبلغ غير صحيح'; return null; },
        ),
        const SizedBox(height: 12),
        TextFormField(
          controller: _memoController,
          decoration: InputDecoration(labelText: 'Memo (اختياري)', hintText: 'ميمو للمحفظة المستلمة', prefixIcon: const Icon(Iconsax.note, size: 20),
            border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)), enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide(color: colors.inputBackground)),
            focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide(color: colors.primary)), filled: true, fillColor: colors.surface),
        ),
        const SizedBox(height: 16),
        if (_isLoadingFee) const Center(child: SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2)))
        else if (_feeData != null) Container(
          padding: const EdgeInsets.all(12), decoration: BoxDecoration(color: colors.infoLight, borderRadius: BorderRadius.circular(12)),
          child: Row(children: [Icon(Iconsax.money, color: colors.primary, size: 18), const SizedBox(width: 8),
            Text('رسوم السحب: ${_feeData!['amount'] ?? 'غير معروف'} USDT', style: TextStyle(fontSize: 13, color: colors.primary, fontWeight: FontWeight.w600))])),
        if (_error != null) Container(
          margin: const EdgeInsets.only(top: 12), padding: const EdgeInsets.all(12), decoration: BoxDecoration(color: colors.errorLight.withValues(alpha: 0.3), borderRadius: BorderRadius.circular(12)),
          child: Row(children: [Icon(Iconsax.close_circle, color: colors.error, size: 18), const SizedBox(width: 8), Expanded(child: Text(_error!, style: TextStyle(color: colors.error, fontSize: 12)))])),
        const SizedBox(height: 20),
        SizedBox(height: 52, child: ElevatedButton(onPressed: _isLoading ? null : _withdraw,
          style: ElevatedButton.styleFrom(backgroundColor: isDark ? colors.surface : colors.primary, foregroundColor: isDark ? colors.textPrimary : Colors.white, shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12))),
          child: _isLoading ? SizedBox(width: 22, height: 22, child: CircularProgressIndicator(color: isDark ? colors.textPrimary : Colors.white, strokeWidth: 2)) : Text('تأكيد السحب', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w600, color: isDark ? colors.textPrimary : Colors.white)))),
        const SizedBox(height: 8),
        Container(
          padding: const EdgeInsets.all(12), decoration: BoxDecoration(color: colors.warningLight.withValues(alpha: 0.3), borderRadius: BorderRadius.circular(12)),
          child: Row(children: [Icon(Iconsax.warning_2, color: colors.warning, size: 18), const SizedBox(width: 8),
            Expanded(child: Text('تأكد من صحة العنوان والشبكة. إرسال لعنوان خاطئ قد يؤدي لفقدان الأموال.', style: TextStyle(fontSize: 11, color: colors.warning)))]),
        ),
        const SizedBox(height: 8),
      ]))),
    );
  }
}

// ──────────────────────────────────────────────
// Currency Exchange Bottom Sheet (USD ↔ SYP)
// ──────────────────────────────────────────────
class _ExchangeSheet extends ConsumerStatefulWidget {
  final double usdBalance;
  final double sypBalance;
  const _ExchangeSheet({required this.usdBalance, required this.sypBalance});

  @override
  ConsumerState<_ExchangeSheet> createState() => _ExchangeSheetState();
}

class _ExchangeSheetState extends ConsumerState<_ExchangeSheet> {
  final _amountController = TextEditingController();
  String _direction = 'usd_to_syp';
  double? _rate;      // mid rate — for the "1 USD = X ل.س" display
  double? _buyRate;   // platform buys USD (applied to usd→syp)
  double? _sellRate;  // platform sells USD (applied to syp→usd)
  bool _isLoading = false, _isLoadingRate = false;
  String? _error;
  double? _convertedAmount;

  @override
  void initState() {
    super.initState();
    _fetchRate();
  }

  @override
  void dispose() {
    _amountController.dispose();
    super.dispose();
  }

  Future<void> _fetchRate() async {
    setState(() => _isLoadingRate = true);
    try {
      final response = await ref.read(dioProvider).get('/wallets/exchange-rates');
      if (response.data['success'] == true && response.data['data'] is List) {
        final rates = response.data['data'] as List;
        for (final r in rates) {
          if (r['from_currency'] == 'USD' && r['to_currency'] == 'SYP') {
            setState(() {
              _rate = double.tryParse(r['rate'].toString());
              _buyRate = double.tryParse(r['buy_rate'].toString());
              _sellRate = double.tryParse(r['sell_rate'].toString());
            });
            break;
          }
        }
      }
    } catch (_) {
    } finally {
      setState(() => _isLoadingRate = false);
    }
  }

  void _onAmountChanged(String value) {
    final amount = double.tryParse(value);
    // usd→syp applies the buy rate, syp→usd the sell rate — matches the backend.
    final usdToSyp = _direction == 'usd_to_syp';
    final appliedRate = usdToSyp ? _buyRate : _sellRate;
    if (amount != null && amount > 0 && appliedRate != null && appliedRate > 0) {
      setState(() => _convertedAmount =
          usdToSyp ? amount * appliedRate : amount / appliedRate);
    } else {
      setState(() => _convertedAmount = null);
    }
  }

  Future<void> _convert() async {
    final rawAmount = double.tryParse(_amountController.text) ?? 0;
    if (rawAmount <= 0) {
      setState(() => _error = 'الرجاء إدخال مبلغ صالح للصرف');
      return;
    }
    final fromCurrency = _direction == 'usd_to_syp' ? 'USD' : 'SYP';
    final toCurrency = _direction == 'usd_to_syp' ? 'SYP' : 'USD';
    // True scale — amount sent as-is for both currencies (no ×100).
    final backendAmount = rawAmount;

    // Friendly client-side balance check — never surface a raw backend error.
    final available =
        fromCurrency == 'USD' ? widget.usdBalance : widget.sypBalance;
    if (backendAmount > available) {
      setState(() => _error =
          'رصيدك غير كافٍ لإتمام عملية الصرف. تحقّق من رصيدك وجرّب مبلغاً أقل.');
      return;
    }

    setState(() {
      _isLoading = true;
      _error = null;
    });
    try {
      final response =
          await ref.read(dioProvider).post('/wallets/convert', data: {
        'from_currency': fromCurrency,
        'to_currency': toCurrency,
        'amount': backendAmount.toStringAsFixed(2),
      });
      if (response.data['success'] == true) {
        if (mounted) {
          final colors = context.appColors;
          Navigator.pop(context);
          ref.invalidate(walletsProvider);
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
                content: const Text('✅ تم الصرف بنجاح'),
                backgroundColor: colors.success),
          );
        }
      } else {
        setState(() => _error = _friendlyExchangeError(response.data));
      }
    } catch (_) {
      setState(() =>
          _error = 'تعذّر إتمام عملية الصرف، حاول مرة أخرى لاحقاً.');
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  String _friendlyExchangeError(dynamic data) {
    final msg = (data is Map && data['message'] is String)
        ? (data['message'] as String).trim()
        : null;
    if (msg != null &&
        (msg.contains('رصيد') ||
            msg.contains('كاف') ||
            msg.toLowerCase().contains('insufficient'))) {
      return 'رصيدك غير كافٍ لإتمام عملية الصرف. تحقّق من رصيدك وجرّب مبلغاً أقل.';
    }
    return 'تعذّر إتمام عملية الصرف، حاول مرة أخرى لاحقاً.';
  }

  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final isUsdToSyp = _direction == 'usd_to_syp';
    return Container(
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(
        color: colors.surface,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          const _SheetHandleBar(),
          Flexible(
            child: SingleChildScrollView(
              padding: const EdgeInsets.only(bottom: 12),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
          const SizedBox(height: 16),
          Row(
            children: [
              Container(
                width: 44, height: 44,
                decoration: BoxDecoration(
                  color: colors.primary.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Icon(Iconsax.arrow_swap_horizontal, color: colors.primary, size: 22),
              ),
              const SizedBox(width: 12),
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text('صرف', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                  Text('تحويل بين الدولار والليرة السورية',
                      style: TextStyle(fontSize: 12, color: colors.textSecondary)),
                ],
              ),
            ],
          ),
          const SizedBox(height: 24),
          Container(
            padding: const EdgeInsets.all(4),
            decoration: BoxDecoration(
              color: colors.inputBackground,
              borderRadius: BorderRadius.circular(12),
            ),
            child: Row(
              children: [
                Expanded(
                  child: GestureDetector(
                    onTap: () => setState(() {
                      _direction = 'usd_to_syp';
                      _convertedAmount = null;
                    }),
                    child: Container(
                      padding: const EdgeInsets.symmetric(vertical: 10),
                      decoration: BoxDecoration(
                        color: isUsdToSyp ? colors.surface : Colors.transparent,
                        borderRadius: BorderRadius.circular(10),
                        boxShadow: isUsdToSyp
                            ? [BoxShadow(color: Colors.black.withValues(alpha: 0.05), blurRadius: 8, offset: const Offset(0, 2))]
                            : null,
                      ),
                      child: const Center(
                        child: Text('USD → SYP', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                      ),
                    ),
                  ),
                ),
                Expanded(
                  child: GestureDetector(
                    onTap: () => setState(() {
                      _direction = 'syp_to_usd';
                      _convertedAmount = null;
                    }),
                    child: Container(
                      padding: const EdgeInsets.symmetric(vertical: 10),
                      decoration: BoxDecoration(
                        color: !isUsdToSyp ? colors.surface : Colors.transparent,
                        borderRadius: BorderRadius.circular(10),
                        boxShadow: !isUsdToSyp
                            ? [BoxShadow(color: Colors.black.withValues(alpha: 0.05), blurRadius: 8, offset: const Offset(0, 2))]
                            : null,
                      ),
                      child: const Center(
                        child: Text('SYP → USD', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            decoration: BoxDecoration(color: colors.inputBackground, borderRadius: BorderRadius.circular(12), border: Border.all(color: colors.inputBackground)),
            child: Row(children: [
              Icon(Iconsax.wallet, size: 18, color: colors.textSecondary),
              const SizedBox(width: 8),
              Text('الرصيد المتوفر: ', style: TextStyle(color: colors.textSecondary, fontSize: 13)),
              Text(
                isUsdToSyp
                    ? Money.format(widget.usdBalance, 'USD')
                    : Money.format(widget.sypBalance, 'SYP'),
                style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: colors.primary),
              ),
            ]),
          ),
          const SizedBox(height: 16),
          TextFormField(
            controller: _amountController,
            keyboardType: TextInputType.number,
            decoration: InputDecoration(
              labelText: isUsdToSyp ? 'المبلغ (USD)' : 'المبلغ (SYP)',
              hintText: '0.00',
              prefixIcon: Icon(isUsdToSyp ? Iconsax.dollar_circle : Iconsax.money, size: 20),
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
              enabledBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12), borderSide: BorderSide(color: colors.inputBackground)),
              focusedBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12), borderSide: BorderSide(color: colors.primary)),
              filled: true,
              fillColor: colors.surface,
            ),
            onChanged: _onAmountChanged,
          ),
          const SizedBox(height: 16),
          if (_isLoadingRate)
            const Center(
              child: SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2)),
            )
          else if (_rate != null)
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(color: colors.infoLight, borderRadius: BorderRadius.circular(12)),
              child: Row(
                children: [
                  Icon(Iconsax.info_circle, color: colors.primary, size: 18),
                  const SizedBox(width: 8),
                  Text('سعر الصرف: 1 USD = ${Money.format(_rate!, 'SYP')}',
                      style: TextStyle(fontSize: 13, color: colors.primary, fontWeight: FontWeight.w600)),
                ],
              ),
            ),
          if (_convertedAmount != null)
            Padding(
              padding: const EdgeInsets.only(top: 12),
              child: Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(color: colors.successLight, borderRadius: BorderRadius.circular(12)),
                child: Row(
                  children: [
                    Icon(Iconsax.tick_circle, color: colors.success, size: 18),
                    const SizedBox(width: 8),
                    Text(
                      isUsdToSyp
                          ? '≈ ${Money.format(_convertedAmount!, 'SYP')}'
                          : '≈ ${Money.format(_convertedAmount!, 'USD')}',
                      style: TextStyle(fontSize: 14, color: colors.success, fontWeight: FontWeight.bold),
                    ),
                  ],
                ),
              ),
            ),
          if (_error != null)
            Container(
              margin: const EdgeInsets.only(top: 12),
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(color: colors.errorLight.withValues(alpha: 0.3), borderRadius: BorderRadius.circular(12)),
              child: Row(
                children: [
                  Icon(Iconsax.close_circle, color: colors.error, size: 18),
                  const SizedBox(width: 8),
                  Expanded(child: Text(_error!, style: TextStyle(color: colors.error, fontSize: 12))),
                ],
              ),
            ),
          const SizedBox(height: 20),
          SizedBox(
            height: 52,
            child: ElevatedButton(
              onPressed: _isLoading ? null : _convert,
              style: ElevatedButton.styleFrom(
                backgroundColor: isDark ? colors.surface : colors.primary,
                foregroundColor: isDark ? colors.textPrimary : Colors.white,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
              ),
              child: _isLoading
                  ? SizedBox(width: 22, height: 22, child: CircularProgressIndicator(color: isDark ? colors.textPrimary : Colors.white, strokeWidth: 2))
                  : Text('تأكيد الصرف', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w600, color: isDark ? colors.textPrimary : Colors.white)),
            ),
          ),
          const SizedBox(height: 8),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}