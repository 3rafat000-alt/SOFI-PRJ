import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:iconsax/iconsax.dart';
import 'package:flutter_animate/flutter_animate.dart';

import '../../../../core/theme/app_colors.dart';
import '../../../../core/widgets/app_ui.dart';
import '../../../../core/widgets/biometric_gate.dart';
import '../../data/repositories/wallet_repository.dart';

class WithdrawPage extends ConsumerStatefulWidget {
  final int walletId;

  const WithdrawPage({super.key, required this.walletId});

  @override
  ConsumerState<WithdrawPage> createState() => _WithdrawPageState();
}

class _WithdrawPageState extends ConsumerState<WithdrawPage> {
  final _amountController = TextEditingController();
  final _formKey = GlobalKey<FormState>();
  bool _isLoading = false;
  double _amount = 0;

  @override
  void dispose() {
    _amountController.dispose();
    super.dispose();
  }

  Future<void> _withdraw() async {
    if (!_formKey.currentState!.validate()) return;

    final amount = double.tryParse(_amountController.text.trim());
    if (amount == null || amount <= 0) return;

    _amount = amount;

    final confirmed = await confirmWithBiometrics(
      context,
      reason: 'التحقق بالبصمة لتأكيد عملية السحب',
    );
    if (!confirmed) return;

    setState(() => _isLoading = true);

    try {
      final result = await ref.read(walletRepositoryProvider).withdraw(
        widget.walletId,
        amount: _amount,
      );

      if (mounted) {
        _showSuccessDialog(result['reference'] ?? 'N/A');
      }
    } catch (e) {
      if (mounted) {
        final colors = context.appColors;
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(e.toString()),
            backgroundColor: colors.error,
          ),
        );
      }
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  void _showSuccessDialog(String reference) {
    final colors = context.appColors;
    final isDark = Theme.of(context).brightness == Brightness.dark;
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
        content: Padding(
          padding: const EdgeInsets.symmetric(vertical: 16),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: 72,
                height: 72,
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    colors: [colors.success, const Color(0xFF16A34A)],
                  ),
                  shape: BoxShape.circle,
                  boxShadow: [
                    BoxShadow(
                      color: colors.success.withValues(alpha: 0.3),
                      blurRadius: 16,
                      offset: const Offset(0, 4),
                    ),
                  ],
                ),
                child: const Icon(Iconsax.tick_circle, color: Colors.white, size: 36),
              ),
              const SizedBox(height: 20),
              const Text(
                'تم إرسال طلب السحب',
                style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
              ),
              const SizedBox(height: 12),
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: colors.inputBackground,
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Column(
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text('رقم المرجع', style: TextStyle(color: colors.textSecondary, fontSize: 13)),
                        Text(reference, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                      ],
                    ),
                    const SizedBox(height: 8),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text('المبلغ', style: TextStyle(color: colors.textSecondary, fontSize: 13)),
                        Text(
                          '\$${_amount.toStringAsFixed(2)}',
                          style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: colors.success),
                        ),
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
              width: double.infinity,
              height: 48,
              child: ElevatedButton(
                onPressed: () {
                  Navigator.pop(ctx);
                  context.pop();
                },
                style: ElevatedButton.styleFrom(
                  backgroundColor: isDark ? colors.surface : colors.primary,
                  foregroundColor: isDark ? colors.textPrimary : Colors.white,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                ),
                child: Text('تم', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: isDark ? colors.textPrimary : Colors.white)),
              ),
            ),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    final isDark = Theme.of(context).brightness == Brightness.dark;
    return AppScaffold(
      title: 'سحب الأموال',
      body: SingleChildScrollView(
        padding: const EdgeInsets.fromLTRB(20, 8, 20, 32),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
                      Column(
                        children: [
                            Container(
                              padding: const EdgeInsets.all(24),
                              decoration: BoxDecoration(
                                gradient: LinearGradient(
                                  colors: [colors.primary.withValues(alpha: 0.05), colors.secondary.withValues(alpha: 0.02)],
                                ),
                                borderRadius: BorderRadius.circular(24),
                                border: Border.all(color: colors.primary.withValues(alpha: 0.1)),
                              ),
                              child: Column(
                                children: [
                                  Icon(Iconsax.dollar_circle, size: 32, color: colors.primary),
                                  const SizedBox(height: 16),
                                  TextFormField(
                                    controller: _amountController,
                                    keyboardType: TextInputType.number,
                                    textAlign: TextAlign.center,
                                    style: const TextStyle(fontSize: 36, fontWeight: FontWeight.bold),
                                    decoration: InputDecoration(
                                      labelText: 'المبلغ',
                                      hintText: '0.00',
                                      labelStyle: TextStyle(color: colors.textSecondary, fontSize: 14),
                                      border: OutlineInputBorder(
                                        borderRadius: BorderRadius.circular(16),
                                        borderSide: BorderSide.none,
                                      ),
                                      filled: true,
                                      fillColor: colors.surface,
                                    ),
                                    validator: (v) {
                                      if (v == null || v.isEmpty) return 'المبلغ مطلوب';
                                      final amount = double.tryParse(v);
                                      if (amount == null || amount <= 0) return 'المبلغ غير صالح';
                                      return null;
                                    },
                                  ),
                                ],
                              ),
                            ).animate().fadeIn().slideY(begin: 0.1),
                          ],
                        ),

                      const SizedBox(height: 32),

                      SizedBox(
                        height: 54,
                        child: ElevatedButton(
                          onPressed: _isLoading ? null : _withdraw,
                          style: ElevatedButton.styleFrom(
                            backgroundColor: isDark ? colors.surface : colors.primary,
                            foregroundColor: isDark ? colors.textPrimary : Colors.white,
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(16),
                            ),
                            elevation: 0,
                          ),
                          child: _isLoading
                              ? SizedBox(
                                  height: 22,
                                  width: 22,
                                  child: CircularProgressIndicator(
                                    strokeWidth: 2,
                                    color: isDark ? colors.textPrimary : Colors.white,
                                  ),
                                )
                              : Text(
                                  'تأكيد السحب',
                                  style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: isDark ? colors.textPrimary : Colors.white),
                                ),
                        ),
                      ).animate(delay: 100.ms).fadeIn().slideY(begin: 0.1),
            ],
          ),
        ),
      ),
    );
  }
}