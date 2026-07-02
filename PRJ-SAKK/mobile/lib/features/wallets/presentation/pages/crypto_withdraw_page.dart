import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:iconsax/iconsax.dart';
import 'package:flutter_animate/flutter_animate.dart';

import '../../../../core/theme/app_colors.dart';
import '../../../../core/network/api_client.dart';
import '../../../../core/widgets/app_ui.dart';
import '../../data/repositories/wallet_repository.dart';

class CryptoWithdrawPage extends ConsumerStatefulWidget {
  const CryptoWithdrawPage({super.key});

  @override
  ConsumerState<CryptoWithdrawPage> createState() => _CryptoWithdrawPageState();
}

class _CryptoWithdrawPageState extends ConsumerState<CryptoWithdrawPage> {
  final _formKey = GlobalKey<FormState>();
  final _addressController = TextEditingController();
  final _amountController = TextEditingController();
  final _memoController = TextEditingController();

  String _selectedCurrency = 'USDT';
  String _selectedChain = 'TRC20';
  bool _isLoading = false;
  bool _isLoadingFee = false;
  Map<String, dynamic>? _feeData;
  String? _error;

  // USDT-only: the CCPayment backend integration services USDT withdrawals
  // exclusively. Offering USDC/BTC/ETH here routed any coin through the
  // USDT payout path, which is a fund-loss bug for non-USDT sends. Do not
  // re-add other coins without a matching backend change (SEV-1).
  final List<Map<String, dynamic>> _coins = [
    {'symbol': 'USDT', 'name': 'Tether', 'color': const Color(0xFF26A17B)},
  ];

  Map<String, List<String>> get _chains => {
    'USDT': ['TRC20', 'ERC20', 'BEP20'],
  };

  @override
  void initState() {
    super.initState();
    _fetchFee();
  }

  @override
  void dispose() {
    _addressController.dispose();
    _amountController.dispose();
    _memoController.dispose();
    super.dispose();
  }

  Future<void> _fetchFee() async {
    setState(() => _isLoadingFee = true);

    try {
      final dio = ref.read(dioProvider);

      // Backend resolves the CCPayment coinId + network from currency + chain.
      // Do NOT send a client-computed coin_id — it was wrong for non-TRC20.
      final response = await dio.get('/ccpayment/withdraw/fee', queryParameters: {
        'currency': _selectedCurrency,
        'chain': _selectedChain,
      });

      if (response.data['success'] == true) {
        setState(() {
          _feeData = response.data['data']['fee'];
        });
      }
    } catch (e) {
      // Fee is optional for the user, but log the failure reason for
      // debugging instead of swallowing it silently.
      debugPrint('crypto_withdraw_page: _fetchFee failed for '
          '$_selectedCurrency/$_selectedChain: $e');
    } finally {
      setState(() => _isLoadingFee = false);
    }
  }

  Future<void> _withdraw() async {
    if (!_formKey.currentState!.validate()) return;

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

      final dio = ref.read(dioProvider);
      final response = await dio.post('/ccpayment/withdraw', data: {
        'wallet_id': usdWallet.id,
        'address': _addressController.text.trim(),
        'amount': _amountController.text,
        'chain': _selectedChain,
        'currency': _selectedCurrency,
        'memo': _memoController.text.isEmpty ? null : _memoController.text,
      });

      if (response.data['success'] == true) {
        if (mounted) {
          final colors = context.appColors;
          final isDark = Theme.of(context).brightness == Brightness.dark;
          showDialog(
            context: context,
            barrierDismissible: false,
            builder: (context) => AlertDialog(
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
              content: Padding(
                padding: const EdgeInsets.symmetric(vertical: 16),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Container(
                      width: 64, height: 64,
                      decoration: BoxDecoration(
                        gradient: LinearGradient(colors: [colors.success, const Color(0xFF16A34A)]),
                        shape: BoxShape.circle,
                        boxShadow: [BoxShadow(color: colors.success.withValues(alpha: 0.3), blurRadius: 16, offset: const Offset(0, 4))],
                      ),
                      child: const Icon(Iconsax.tick_circle, color: Colors.white, size: 32),
                    ),
                    const SizedBox(height: 16),
                    const Text('تم الإرسال', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                    const SizedBox(height: 8),
                    Text('تم إرسال طلب السحب بنجاح', style: TextStyle(color: colors.textSecondary, fontSize: 14)),
                    const SizedBox(height: 16),
                    Container(
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(color: colors.inputBackground, borderRadius: BorderRadius.circular(12)),
                      child: Column(
                        children: [
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Text('المبلغ', style: TextStyle(color: colors.textSecondary, fontSize: 13)),
                              Text('${response.data['data']['amount']} $_selectedCurrency',
                                style: const TextStyle(fontWeight: FontWeight.bold)),
                            ],
                          ),
                          const SizedBox(height: 8),
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Text('الرسوم', style: TextStyle(color: colors.textSecondary, fontSize: 13)),
                              Text('${response.data['data']['fee']['amount'] ?? 'غير معروف'}',
                                style: TextStyle(fontSize: 12, color: colors.textSecondary)),
                            ],
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
              actions: [
                Padding(
                  padding: const EdgeInsets.only(bottom: 8, left: 16, right: 16),
                  child: SizedBox(
                    width: double.infinity, height: 48,
                    child: ElevatedButton(
                      onPressed: () {
                        Navigator.pop(context);
                        context.go('/transactions');
                      },
                      style: ElevatedButton.styleFrom(
                        backgroundColor: isDark ? colors.surface : colors.primary,
                        foregroundColor: isDark ? colors.textPrimary : Colors.white,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                      child: Text('الذهاب للمعاملات', style: TextStyle(color: isDark ? colors.textPrimary : Colors.white, fontWeight: FontWeight.bold)),
                    ),
                  ),
                ),
              ],
            ),
          );
        }
      } else {
        setState(() {
          _error = response.data['message'] ?? 'فشل السحب';
        });
      }
    } on DioException catch (e) {
      // السيرفر يردّ سبباً واضحاً بالعربية (KYC/الجهاز/الرصيد) في جسم الرد؛
      // اقرأه بدل إظهار رمز الحالة الخام مثل «خطأ تقني 403».
      setState(() {
        _error = ApiException.fromDioError(e).message;
      });
    } catch (e) {
      setState(() {
        _error = 'عذراً، حدث خطأ غير متوقع. يرجى المحاولة لاحقاً.';
      });
    } finally {
      setState(() => _isLoading = false);
    }
  }

  void _onCurrencyChanged(String currency) {
    setState(() {
      _selectedCurrency = currency;
      _selectedChain = _chains[currency]!.first;
      _feeData = null;
    });
    _fetchFee();
  }

  void _onChainChanged(String chain) {
    setState(() {
      _selectedChain = chain;
      _feeData = null;
    });
    _fetchFee();
  }

  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    final isDark = Theme.of(context).brightness == Brightness.dark;
    return AppScaffold(
      title: 'سحب عملات رقمية',
      body: SingleChildScrollView(
        padding: const EdgeInsets.fromLTRB(20, 8, 20, 32),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
                        _buildCurrencySelector(),
                        const SizedBox(height: 20),
                        _buildChainSelector(),
                        const SizedBox(height: 24),
                        _buildTextField(
                          controller: _addressController,
                          label: 'عنوان المحفظة',
                          hint: 'أدخل عنوان المحفظة',
                          prefixIcon: Iconsax.wallet,
                          validator: (value) {
                            if (value == null || value.isEmpty) return 'عنوان المحفظة مطلوب';
                            if (value.length < 10) return 'عنوان المحفظة قصير جداً';
                            return null;
                          },
                        ),
                        const SizedBox(height: 16),
                        TextFormField(
                          controller: _amountController,
                          keyboardType: TextInputType.number,
                          textAlign: TextAlign.center,
                          style: const TextStyle(fontSize: 28, fontWeight: FontWeight.bold),
                          decoration: InputDecoration(
                            labelText: 'المبلغ',
                            hintText: '0.00',
                            prefixIcon: const Icon(Iconsax.coin, size: 20),
                            border: OutlineInputBorder(borderRadius: BorderRadius.circular(16)),
                            enabledBorder: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(16),
                              borderSide: BorderSide(color: colors.inputBackground),
                            ),
                            focusedBorder: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(16),
                              borderSide: BorderSide(color: colors.primary),
                            ),
                            filled: true,
                            fillColor: colors.surface,
                          ),
                          validator: (value) {
                            if (value == null || value.isEmpty) return 'المبلغ مطلوب';
                            if (double.tryParse(value) == null || double.parse(value) <= 0) return 'المبلغ غير صحيح';
                            return null;
                          },
                        ),
                        const SizedBox(height: 16),
                        _buildTextField(
                          controller: _memoController,
                          label: 'Memo (اختياري)',
                          hint: 'ميمو للمحفظة المستلمة',
                          prefixIcon: Iconsax.note,
                        ),
                        const SizedBox(height: 24),
                        if (_isLoadingFee)
                          const Center(
                            child: SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2)),
                          )
                        else if (_feeData != null)
                          Container(
                            padding: const EdgeInsets.all(14),
                            decoration: BoxDecoration(
                              color: colors.infoLight,
                              borderRadius: BorderRadius.circular(14),
                              border: Border.all(color: colors.primary.withValues(alpha: 0.1)),
                            ),
                            child: Row(
                              children: [
                                Container(
                                  width: 36, height: 36,
                                  decoration: BoxDecoration(
                                    color: colors.primary.withValues(alpha: 0.1),
                                    borderRadius: BorderRadius.circular(10),
                                  ),
                                  child: Icon(Iconsax.money, color: colors.primary, size: 18),
                                ),
                                const SizedBox(width: 12),
                                Expanded(
                                  child: Text(
                                    'رسوم السحب: ${_feeData!['amount'] ?? 'غير معروف'} ${_feeData!['coinSymbol'] ?? _selectedCurrency}',
                                    style: TextStyle(fontSize: 14, color: colors.primary, fontWeight: FontWeight.w600),
                                  ),
                                ),
                              ],
                            ),
                          ),
                        if (_error != null)
                          Container(
                            padding: const EdgeInsets.all(12),
                            margin: const EdgeInsets.only(bottom: 16, top: 8),
                            decoration: BoxDecoration(
                              color: colors.errorLight.withValues(alpha: 0.3),
                              borderRadius: BorderRadius.circular(14),
                            ),
                            child: Row(
                              children: [
                                Icon(Iconsax.close_circle, color: colors.error, size: 20),
                                const SizedBox(width: 8),
                                Expanded(child: Text(_error!, style: TextStyle(color: colors.error, fontSize: 13))),
                              ],
                            ),
                          ),
                        const SizedBox(height: 8),
                        SizedBox(
                          height: 54,
                          child: ElevatedButton(
                            onPressed: _isLoading ? null : _withdraw,
                            style: ElevatedButton.styleFrom(
                              backgroundColor: isDark ? colors.surface : colors.primary,
                              foregroundColor: isDark ? colors.textPrimary : Colors.white,
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                              elevation: 0,
                            ),
                            child: _isLoading
                                ? SizedBox(width: 24, height: 24, child: CircularProgressIndicator(color: isDark ? colors.textPrimary : Colors.white, strokeWidth: 2))
                                : Row(
                                    mainAxisAlignment: MainAxisAlignment.center,
                                    children: [
                                      Icon(Iconsax.direct_up, color: isDark ? colors.textPrimary : Colors.white, size: 20),
                                      const SizedBox(width: 8),
                                      Text('تأكيد السحب', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: isDark ? colors.textPrimary : Colors.white)),
                                    ],
                                  ),
                          ),
                        ),
                        const SizedBox(height: 16),
                        Container(
                          padding: const EdgeInsets.all(14),
                          decoration: BoxDecoration(
                            color: colors.warningLight.withValues(alpha: 0.3),
                            borderRadius: BorderRadius.circular(14),
                            border: Border.all(color: colors.warning.withValues(alpha: 0.15)),
                          ),
                          child: Row(
                            children: [
                              Icon(Iconsax.warning_2, color: colors.warning, size: 20),
                              const SizedBox(width: 10),
                              Expanded(
                                child: Text(
                                  'تأكد من صحة العنوان والشبكة. إرسال لعنوان خاطئ قد يؤدي لفقدان الأموال بشكل دائم.',
                                  style: TextStyle(fontSize: 12, color: colors.warning),
                                ),
                              ),
                            ],
                          ),
                        ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildCurrencySelector() {
    final colors = context.appColors;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text('اختر العملة', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: colors.textPrimary)),
        const SizedBox(height: 12),
        SizedBox(
          height: 80,
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
                  width: 90,
                  margin: const EdgeInsets.only(right: 10),
                  decoration: BoxDecoration(
                    gradient: isSelected
                        ? LinearGradient(colors: [color, color.withValues(alpha: 0.8)])
                        : null,
                    color: isSelected ? null : colors.surface,
                    borderRadius: BorderRadius.circular(14),
                    border: Border.all(
                      color: isSelected ? color : colors.inputBackground,
                      width: isSelected ? 0 : 1.5,
                    ),
                    boxShadow: isSelected
                        ? [BoxShadow(color: color.withValues(alpha: 0.3), blurRadius: 10, offset: const Offset(0, 4))]
                        : null,
                  ),
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Text(symbol,
                        style: TextStyle(
                          fontSize: 13, fontWeight: FontWeight.bold,
                          color: isSelected ? Colors.white : colors.textPrimary,
                        )),
                      Text(name,
                        style: TextStyle(
                          fontSize: 9,
                          color: isSelected ? Colors.white70 : colors.textSecondary,
                        )),
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
        Text('اختر الشبكة', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: colors.textPrimary)),
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
                  child: Row(children: [
                    Container(width: 10, height: 10, decoration: BoxDecoration(shape: BoxShape.circle, color: dotColor)),
                    const SizedBox(width: 10),
                    Text('$_selectedCurrency-$chain', style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 15)),
                  ]),
                );
              }).toList(),
              onChanged: (v) { if (v == null) return; _onChainChanged(v); },
            ),
          ),
        ),
      ],
    ).animate(delay: 100.ms).fadeIn().slideX(begin: -0.1);
  }

  Widget _buildTextField({
    required TextEditingController controller,
    required String label,
    required String hint,
    required IconData prefixIcon,
    TextInputType? keyboardType,
    String? Function(String?)? validator,
  }) {
    final colors = context.appColors;
    return TextFormField(
      controller: controller,
      keyboardType: keyboardType,
      validator: validator,
      decoration: InputDecoration(
        labelText: label,
        hintText: hint,
        prefixIcon: Icon(prefixIcon, color: colors.primary),
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(14)),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(14),
          borderSide: BorderSide(color: colors.inputBackground),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(14),
          borderSide: BorderSide(color: colors.primary),
        ),
        filled: true,
        fillColor: colors.surface,
      ),
    );
  }
}