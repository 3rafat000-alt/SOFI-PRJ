import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter/services.dart';
import 'package:iconsax/iconsax.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:qr_flutter/qr_flutter.dart';

import '../../../../core/theme/app_colors.dart';
import '../../../../core/network/api_client.dart';
import '../../../../core/widgets/app_ui.dart';
import '../../data/repositories/wallet_repository.dart';

class CryptoDepositPage extends ConsumerStatefulWidget {
  const CryptoDepositPage({super.key});

  @override
  ConsumerState<CryptoDepositPage> createState() => _CryptoDepositPageState();
}

class _CryptoDepositPageState extends ConsumerState<CryptoDepositPage> {
  String _selectedCurrency = 'USDT';
  String _selectedChain = 'TRC20';
  bool _isLoading = false;
  Map<String, dynamic>? _depositData;
  String? _error;

  // USDT-only: the CCPayment backend integration services USDT deposits
  // exclusively. Offering USDC/BTC/ETH here credited any coin 1:1 as USD,
  // which is a fund-loss bug for non-USDT sends. Do not re-add other coins
  // without a matching backend change (SEV-1).
  final List<Map<String, dynamic>> _coins = [
    {'symbol': 'USDT', 'name': 'Tether', 'color': const Color(0xFF26A17B)},
  ];

  Map<String, List<String>> get _chains => {
    'USDT': ['TRC20', 'ERC20', 'BEP20'],
  };

  @override
  void initState() {
    super.initState();
    _createDepositAddress();
  }

  Future<void> _createDepositAddress() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final wallets = await ref.read(walletsProvider.future);
      final usdWallet = wallets.firstWhere(
        (w) => w.currency == 'USD',
        orElse: () => wallets.first,
      );

      final repo = ref.read(walletRepositoryProvider);
      final data = await repo.createCryptoDepositAddress(
        walletId: usdWallet.id,
        chain: _selectedChain,
        currency: _selectedCurrency,
      );

      setState(() {
        _depositData = data;
      });
    } on ApiException catch (e) {
      setState(() {
        _error = _cleanMessage(e.message);
      });
    } catch (e) {
      setState(() {
        _error = 'تعذّر إنشاء عنوان الإيداع حالياً. يرجى المحاولة لاحقاً.';
      });
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  /// Hide raw/technical server text behind a clean fallback — ApiException
  /// already localizes network/timeout errors to Arabic, but a raw 5xx
  /// `message` payload can still leak technical text, so keep this filter.
  String _cleanMessage(String message) {
    final m = message.trim();
    if (m.isEmpty) return 'تعذّر إنشاء عنوان الإيداع حالياً. يرجى المحاولة لاحقاً.';
    final looksTechnical = RegExp(
            r'(Exception|Error:|SQLSTATE|stack|trace|\bDio\b|http|Null|<[a-z!])',
            caseSensitive: false)
        .hasMatch(m);
    return looksTechnical
        ? 'تعذّر إنشاء عنوان الإيداع حالياً. يرجى المحاولة لاحقاً.'
        : m;
  }

  void _onCurrencyChanged(String currency) {
    setState(() {
      _selectedCurrency = currency;
      _selectedChain = _chains[currency]!.first;
      _depositData = null;
    });
    _createDepositAddress();
  }

  void _onChainChanged(String chain) {
    setState(() {
      _selectedChain = chain;
      _depositData = null;
    });
    _createDepositAddress();
  }

  @override
  Widget build(BuildContext context) {
    return AppScaffold(
      title: 'إيداع عملات رقمية',
      body: SingleChildScrollView(
        padding: const EdgeInsets.fromLTRB(20, 8, 20, 32),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
                      _buildCurrencySelector(),
                      const SizedBox(height: 20),
                      _buildChainSelector(),
                      const SizedBox(height: 24),
                      if (_isLoading)
                        const Padding(
                          padding: EdgeInsets.symmetric(vertical: 48),
                          child: Center(
                            child: Column(
                              children: [
                                CircularProgressIndicator(),
                                SizedBox(height: 16),
                                Text('جاري إنشاء عنوان الإيداع...'),
                              ],
                            ),
                          ),
                        )
                      else if (_error != null)
                        _buildErrorWidget()
                      else if (_depositData != null)
                        _buildDepositAddressWidget()
                      else
                        const SizedBox.shrink(),
          ],
        ),
      ),
    );
  }

  Widget _buildCurrencySelector() {
    final colors = context.appColors;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'اختر العملة',
          style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: colors.textPrimary),
        ),
        const SizedBox(height: 12),
        SizedBox(
          height: 90,
          child: ListView.builder(
            scrollDirection: Axis.horizontal,
            itemCount: _coins.length,
            itemBuilder: (context, index) {
              final coin = _coins[index];
              final symbol = coin['symbol'] as String;
              final name = coin['name'] as String;
              final color = coin['color'] as Color;
              final isSelected = symbol == _selectedCurrency;

              return GestureDetector(
                onTap: () => _onCurrencyChanged(symbol),
                child: Container(
                  width: 100,
                  margin: const EdgeInsets.only(right: 12),
                  decoration: BoxDecoration(
                    gradient: isSelected
                        ? LinearGradient(
                            colors: [color, color.withValues(alpha: 0.8)],
                          )
                        : null,
                    color: isSelected ? null : colors.surface,
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(
                      color: isSelected ? color : colors.inputBackground,
                      width: isSelected ? 0 : 1.5,
                    ),
                    boxShadow: isSelected
                        ? [BoxShadow(color: color.withValues(alpha: 0.3), blurRadius: 12, offset: const Offset(0, 4))]
                        : null,
                  ),
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Container(
                        width: 8, height: 8,
                        decoration: BoxDecoration(shape: BoxShape.circle, color: isSelected ? Colors.white : color),
                      ),
                      const SizedBox(height: 8),
                      Text(
                        symbol,
                        style: TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.bold,
                          color: isSelected ? Colors.white : colors.textPrimary,
                        ),
                      ),
                      Text(
                        name,
                        style: TextStyle(
                          fontSize: 10,
                          color: isSelected ? Colors.white70 : colors.textSecondary,
                        ),
                      ),
                    ],
                  ),
                ),
              );
            },
          ),
        ),
      ],
    ).animate().fadeIn().slideX(begin: -0.1);
  }

  Widget _buildChainSelector() {
    final colors = context.appColors;
    final chains = _chains[_selectedCurrency] ?? [];

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'اختر الشبكة',
          style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: colors.textPrimary),
        ),
        const SizedBox(height: 12),
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 16),
          decoration: BoxDecoration(
            color: colors.inputBackground,
            borderRadius: BorderRadius.circular(12),
            border: Border.all(color: colors.inputBackground),
          ),
          child: DropdownButtonHideUnderline(
            child: DropdownButton<String>(
              value: _selectedChain,
              isExpanded: true,
              icon: const Icon(Iconsax.arrow_down_1),
              items: chains.map((chain) {
                final dotColor = chain == 'TRC20' ? const Color(0xFF26A17B) :
                                chain == 'ERC20' ? const Color(0xFF627EEA) :
                                chain == 'BEP20' ? const Color(0xFFF3BA2F) :
                                const Color(0xFFF7931A);
                return DropdownMenuItem(
                  value: chain,
                  child: Row(
                    children: [
                      Container(width: 10, height: 10, decoration: BoxDecoration(shape: BoxShape.circle, color: dotColor)),
                      const SizedBox(width: 10),
                      Text('$_selectedCurrency-$chain', style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 15)),
                    ],
                  ),
                );
              }).toList(),
              onChanged: (v) {
                if (v == null) return;
                _onChainChanged(v);
              },
            ),
          ),
        ),
      ],
    ).animate(delay: 100.ms).fadeIn().slideX(begin: -0.1);
  }

  Widget _buildDepositAddressWidget() {
    final address = _depositData!['address'] as String?;
    final memo = _depositData!['memo'] as String?;
    final qrCode = _depositData!['qr_code'] as String?;

    final colors = context.appColors;
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: colors.surface,
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: colors.inputBackground),
        boxShadow: [
          BoxShadow(color: Colors.black.withValues(alpha: 0.04), blurRadius: 20, offset: const Offset(0, 8)),
        ],
      ),
      child: Column(
        children: [
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: colors.surface,
              borderRadius: BorderRadius.circular(20),
              border: Border.all(color: colors.primary.withValues(alpha: 0.15)),
            ),
            child: qrCode != null
                ? QrImageView(
                    data: address ?? '',
                    version: QrVersions.auto,
                    size: 200,
                    backgroundColor: Colors.white,
                  )
                : Container(
                    width: 200, height: 200,
                    decoration: BoxDecoration(color: colors.inputBackground, borderRadius: BorderRadius.circular(16)),
                    child: Icon(Iconsax.wallet, size: 64, color: colors.textSecondary),
                  ),
          ),

          const SizedBox(height: 20),

          Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: colors.inputBackground,
              borderRadius: BorderRadius.circular(14),
            ),
            child: Row(
              children: [
                Expanded(
                  child: Text(
                    address ?? 'غير متوفر',
                    style: TextStyle(fontSize: 13, fontFamily: 'monospace', color: colors.textPrimary),
                    textAlign: TextAlign.center,
                  ),
                ),
                const SizedBox(width: 8),
                GestureDetector(
                  onTap: () {
                    if (address != null) {
                      Clipboard.setData(ClipboardData(text: address));
                      ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(content: Text('تم نسخ العنوان'), duration: Duration(seconds: 2)),
                      );
                    }
                  },
                  child: Container(
                    padding: const EdgeInsets.all(10),
                    decoration: BoxDecoration(
                      gradient: LinearGradient(colors: colors.cardGradientVisa),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: const Icon(Iconsax.copy, color: Colors.white, size: 18),
                  ),
                ),
              ],
            ),
          ),

          if (memo != null && memo.isNotEmpty) ...[
            const SizedBox(height: 8),
            Container(
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(color: colors.inputBackground, borderRadius: BorderRadius.circular(14)),
              child: Row(
                children: [
                  Text('Memo: ', style: TextStyle(fontSize: 12, color: colors.textSecondary)),
                  Expanded(child: Text(memo, style: const TextStyle(fontSize: 12, fontFamily: 'monospace'))),
                  GestureDetector(
                    onTap: () {
                      Clipboard.setData(ClipboardData(text: memo));
                      ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(content: Text('تم نسخ الميمو'), duration: Duration(seconds: 2)),
                      );
                    },
                    child: Container(
                      padding: const EdgeInsets.all(8),
                      decoration: BoxDecoration(
                        gradient: LinearGradient(colors: colors.cardGradientVisa),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: const Icon(Iconsax.copy, color: Colors.white, size: 16),
                    ),
                  ),
                ],
              ),
            ),
          ],

          const SizedBox(height: 16),

          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: colors.warningLight.withValues(alpha: 0.4),
              borderRadius: BorderRadius.circular(14),
              border: Border.all(color: colors.warning.withValues(alpha: 0.2)),
            ),
            child: Row(
              children: [
                Icon(Iconsax.warning_2, color: colors.warning, size: 20),
                const SizedBox(width: 10),
                Expanded(
                  child: Text(
                    'أرسل $_selectedCurrency فقط على شبكة $_selectedChain. إرسال عملة أخرى قد يؤدي لفقدان الأموال.',
                    style: TextStyle(fontSize: 12, color: colors.warning),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    ).animate().fadeIn(duration: 400.ms).slideY(begin: 0.1);
  }

  Widget _buildErrorWidget() {
    final colors = context.appColors;
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(28),
      decoration: BoxDecoration(
        color: colors.surface,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: colors.warning.withValues(alpha: 0.25)),
      ),
      child: Column(
        children: [
          Container(
            width: 76,
            height: 76,
            decoration: BoxDecoration(
              color: colors.warning.withValues(alpha: 0.12),
              shape: BoxShape.circle,
            ),
            child: Icon(Iconsax.cloud_cross,
                color: colors.warning, size: 38),
          ),
          const SizedBox(height: 16),
          Text(
            'تعذّر إنشاء عنوان الإيداع',
            style: TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.w700,
                color: colors.textPrimary),
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 8),
          Text(
            _error!,
            style: TextStyle(
                fontSize: 13, color: colors.textSecondary, height: 1.5),
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 20),
          SizedBox(
            width: double.infinity,
            child: AppButton(
              label: 'إعادة المحاولة',
              icon: Iconsax.refresh,
              onPressed: _createDepositAddress,
            ),
          ),
        ],
      ),
    ).animate().fadeIn(duration: 300.ms);
  }
}