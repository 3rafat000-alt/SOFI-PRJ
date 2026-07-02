import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:iconsax/iconsax.dart';

import '../../../../core/theme/app_colors.dart';
import '../../../../core/widgets/app_skeleton.dart';
import '../../../../core/widgets/app_ui.dart';
import '../../../wallets/data/repositories/wallet_repository.dart';
import '../../data/repositories/card_repository.dart';

class CreateCardPage extends ConsumerStatefulWidget {
  const CreateCardPage({super.key});

  @override
  ConsumerState<CreateCardPage> createState() => _CreateCardPageState();
}

class _CreateCardPageState extends ConsumerState<CreateCardPage> {
  int? _selectedWalletId;
  String _selectedBrand = 'visa';
  final _nicknameController = TextEditingController();
  bool _isLoading = false;

  @override
  void dispose() {
    _nicknameController.dispose();
    super.dispose();
  }

  Future<void> _createCard() async {
    if (_selectedWalletId == null) {
      _snack('اختر المحفظة أولاً');
      return;
    }

    setState(() => _isLoading = true);

    try {
      await ref.read(cardRepositoryProvider).createCard(
            walletId: _selectedWalletId!,
            brand: _selectedBrand,
            nickname: _nicknameController.text.trim().isEmpty
                ? null
                : _nicknameController.text.trim(),
          );

      ref.invalidate(cardsProvider);

      if (mounted) {
        _snack('تم إنشاء البطاقة بنجاح!', color: AppColors.success);
        context.pop();
      }
    } catch (e) {
      if (mounted) _snack(e.toString());
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  void _snack(String msg, {Color color = AppColors.error}) {
    ScaffoldMessenger.of(context)
      ..hideCurrentSnackBar()
      ..showSnackBar(SnackBar(
        content: Text(msg),
        backgroundColor: color,
        behavior: SnackBarBehavior.floating,
      ));
  }

  @override
  Widget build(BuildContext context) {
    final walletsAsync = ref.watch(walletsProvider);
    final colors = context.appColors;

    return AppScaffold(
      title: 'إنشاء بطاقة جديدة',
      body: SingleChildScrollView(
        padding: const EdgeInsets.fromLTRB(20, 8, 20, 32),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // ───── Card preview ─────
            _cardPreview(),
            const SizedBox(height: 20),

            // ───── Issuance fee notice ─────
            Container(
              padding: const EdgeInsets.all(AppSpacing.md),
              decoration: BoxDecoration(
                color: colors.infoLight,
                borderRadius: BorderRadius.circular(AppRadius.md),
              ),
              child: Row(
                children: [
                  Icon(Iconsax.info_circle,
                      color: colors.info, size: 20),
                  const SizedBox(width: AppSpacing.sm),
                  Expanded(
                    child: Text(
                      'البطاقات تُصدر وتعمل بالدولار (USD) فقط. تُخصم رسوم الإصدار من محفظة الدولار.',
                      style: TextStyle(fontSize: 12.5, color: colors.info.withValues(alpha: 0.9)),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: AppSpacing.xxl),

            // ───── Wallet ─────
            const _Label('المحفظة'),
            const SizedBox(height: AppSpacing.md),
            walletsAsync.when(
              data: (wallets) {
                // Cards are issued & operate on the USD wallet only.
                final usd = wallets
                    .where((w) => w.currency == 'USD' && w.isActive)
                    .toList();
                if (usd.isEmpty) {
                  return Container(
                    padding: const EdgeInsets.all(AppSpacing.md),
                    decoration: BoxDecoration(
                      color: colors.errorLight,
                      borderRadius: BorderRadius.circular(AppRadius.md),
                    ),
                    child: Row(children: [
                      Icon(Iconsax.warning_2, color: colors.error, size: 18),
                      const SizedBox(width: AppSpacing.sm),
                      Expanded(
                        child: Text('تحتاج محفظة دولار (USD) نشطة لإنشاء بطاقة.',
                            style: TextStyle(
                                fontSize: 12.5, color: colors.error)),
                      ),
                    ]),
                  );
                }
                final w = usd.first;
                if (_selectedWalletId != w.id) {
                  WidgetsBinding.instance.addPostFrameCallback((_) {
                    if (mounted && _selectedWalletId != w.id) {
                      setState(() => _selectedWalletId = w.id);
                    }
                  });
                }
                return Container(
                  padding: const EdgeInsets.all(AppSpacing.md),
                  decoration: BoxDecoration(
                    color: colors.inputBackground,
                    borderRadius: BorderRadius.circular(AppRadius.md),
                    border: Border.all(color: colors.inputBackground),
                  ),
                  child: Row(children: [
                    Container(
                      width: 42,
                      height: 42,
                      decoration: BoxDecoration(
                        gradient: LinearGradient(
                            colors: colors.cardGradientVisa),
                        borderRadius: BorderRadius.circular(AppRadius.md),
                      ),
                      child: const Icon(Iconsax.wallet_3,
                          color: Colors.white, size: 20),
                    ),
                    const SizedBox(width: AppSpacing.md),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text('محفظة الدولار (USD)',
                              style: TextStyle(
                                  fontSize: 14,
                                  fontWeight: FontWeight.w700,
                                  color: colors.textPrimary)),
                          const SizedBox(height: 2),
                          Text(w.formattedBalance,
                              textDirection: TextDirection.ltr,
                              style: TextStyle(
                                  fontSize: 12.5,
                                  color: colors.textSecondary)),
                        ],
                      ),
                    ),
                    Container(
                      padding: const EdgeInsets.symmetric(
                          horizontal: 10, vertical: 4),
                      decoration: BoxDecoration(
                          color: colors.primaryLight,
                          borderRadius: BorderRadius.circular(AppRadius.pill)),
                      child: Icon(Iconsax.tick_circle,
                          color: colors.primary, size: 16),
                    ),
                  ]),
                );
              },
              loading: () => const SkeletonEmptyFriendly(),
              error: (_, __) => const Text('خطأ في تحميل المحافظ'),
            ),
            const SizedBox(height: AppSpacing.xxl),

            // ───── Brand ─────
            const _Label('نوع البطاقة'),
            const SizedBox(height: AppSpacing.md),
            Row(
              children: [
                Expanded(
                  child: _BrandOption(
                    brand: 'visa',
                    isSelected: _selectedBrand == 'visa',
                    onTap: () => setState(() => _selectedBrand = 'visa'),
                  ),
                ),
                const SizedBox(width: AppSpacing.md),
                Expanded(
                  child: _BrandOption(
                    brand: 'mastercard',
                    isSelected: _selectedBrand == 'mastercard',
                    onTap: () => setState(() => _selectedBrand = 'mastercard'),
                  ),
                ),
              ],
            ),
            const SizedBox(height: AppSpacing.xxl),

            // ───── Nickname ─────
            const _Label('اسم البطاقة (اختياري)'),
            const SizedBox(height: AppSpacing.md),
            TextField(
              controller: _nicknameController,
              maxLength: 50,
              decoration: const InputDecoration(
                hintText: 'مثال: بطاقة التسوق',
                prefixIcon: Icon(Iconsax.card),
                counterText: '',
              ),
            ),
            const SizedBox(height: AppSpacing.xxl),

            // ───── Create ─────
            AppButton(
              label: 'إنشاء البطاقة',
              icon: Iconsax.add_circle,
              loading: _isLoading,
              onPressed: _createCard,
            ),
          ],
        ),
      ),
    );
  }

  Widget _cardPreview() {
    final colors = context.appColors;
    return Container(
      width: double.infinity,
      height: 200,
      decoration: BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          // Unified card gradient (no brand-color scatter) — matches VirtualCardWidget.
          colors: colors.cardGradientVisa,
        ),
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.3),
            blurRadius: 20,
            offset: const Offset(0, 10),
          ),
        ],
      ),
      padding: const EdgeInsets.all(24),
      child: MediaQuery.withClampedTextScaling(
        maxScaleFactor: 1.0,
        child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Text('صكك',
                  style: TextStyle(
                      color: Colors.white,
                      fontSize: 22,
                      fontWeight: FontWeight.bold)),
              Text(
                _selectedBrand.toUpperCase(),
                style: TextStyle(
                    color: Colors.white.withValues(alpha: 0.9),
                    fontSize: 16,
                    fontWeight: FontWeight.w600),
              ),
            ],
          ),
          const Spacer(),
          const Text(
            '**** **** **** ****',
            textDirection: TextDirection.ltr,
            style: TextStyle(
                color: Colors.white,
                fontSize: 24,
                fontWeight: FontWeight.w600,
                letterSpacing: 2),
          ),
          const SizedBox(height: 16),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              _previewField('اسم حامل البطاقة', 'YOUR NAME'),
              _previewField('صالحة حتى', 'MM/YY'),
            ],
          ),
        ],
      ),
      ),
    );
  }

  Widget _previewField(String label, String value) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label,
            style: TextStyle(color: Colors.white.withValues(alpha: 0.7), fontSize: 10)),
        Text(value,
            style: const TextStyle(
                color: Colors.white, fontSize: 14, fontWeight: FontWeight.w600)),
      ],
    );
  }
}

class _Label extends StatelessWidget {
  final String text;
  const _Label(this.text);

  @override
  Widget build(BuildContext context) {
    return Text(
      text,
      style: TextStyle(
        fontSize: 15,
        fontWeight: FontWeight.w700,
        color: context.appColors.textPrimary,
      ),
    );
  }
}

class _BrandOption extends StatelessWidget {
  final String brand;
  final bool isSelected;
  final VoidCallback onTap;

  const _BrandOption({
    required this.brand,
    required this.isSelected,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(AppRadius.md),
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 16),
        decoration: BoxDecoration(
          color: isSelected ? colors.primaryLight : colors.surface,
          borderRadius: BorderRadius.circular(AppRadius.md),
          border: Border.all(
            color: isSelected ? colors.primary : colors.inputBackground,
            width: isSelected ? 2 : 1,
          ),
        ),
        child: Column(
          children: [
            Text(
              brand.toUpperCase(),
              style: TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.bold,
                color: isSelected ? colors.primary : colors.textPrimary,
              ),
            ),
            const SizedBox(height: 4),
            Text(
              brand == 'visa' ? 'فيزا' : 'ماستركارد',
              style: TextStyle(fontSize: 12, color: colors.textSecondary),
            ),
          ],
        ),
      ),
    );
  }
}
