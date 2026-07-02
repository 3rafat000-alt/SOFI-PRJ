import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:iconsax/iconsax.dart';
import 'package:flutter_animate/flutter_animate.dart';

import '../../../../core/theme/app_colors.dart';
import '../../../../core/widgets/app_skeleton.dart';
import '../../../../core/widgets/app_ui.dart';
import '../../data/repositories/wallet_repository.dart';
import '../widgets/wallet_card.dart';

class WalletsPage extends ConsumerWidget {
  const WalletsPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final colors = context.appColors;
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final walletsAsync = ref.watch(walletsProvider);

    return AppScaffold(
      title: 'المحافظ',
      showBack: false,
      onRefresh: () async => ref.invalidate(walletsProvider),
      floatingActionButton: FloatingActionButton(
        onPressed: () => _showCreateWalletDialog(context, ref),
        backgroundColor: isDark ? colors.surface : colors.primary,
        elevation: 2,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(AppRadius.lg),
        ),
        child: Icon(Iconsax.add,
            color: isDark ? colors.textPrimary : Colors.white, size: 28),
      ),
      body: walletsAsync.when(
        data: (wallets) {
          if (wallets.isEmpty) {
            return ListView(
              children: [
                const SizedBox(height: 60),
                EmptyState(
                  icon: Iconsax.wallet_2,
                  title: 'لا توجد محافظ',
                  subtitle: 'أنشئ محفظة جديدة للبدء',
                  actionLabel: 'إنشاء محفظة',
                  onAction: () => _showCreateWalletDialog(context, ref),
                ),
              ],
            );
          }
          return ListView.builder(
            padding: const EdgeInsets.fromLTRB(
                AppSpacing.lg, AppSpacing.sm, AppSpacing.lg, 100),
            itemCount: wallets.length,
            itemBuilder: (context, index) {
              final wallet = wallets[index];
              return Padding(
                padding: const EdgeInsets.only(bottom: AppSpacing.lg),
                child: WalletCard(
                  wallet: wallet,
                  onTap: () => context.push('/wallets/${wallet.id}'),
                )
                    .animate(delay: Duration(milliseconds: index * 100))
                    .fadeIn()
                    .slideX(begin: 0.08),
              );
            },
          );
        },
        loading: () => SakkShimmer(
          child: ListView(
            padding: const EdgeInsets.fromLTRB(
                AppSpacing.lg, AppSpacing.sm, AppSpacing.lg, 100),
            children: const [
              SkeletonCard(height: 180),
              SkeletonCard(height: 140),
              SkeletonCard(height: 140),
            ],
          ),
        ),
        error: (error, _) => ListView(
          children: [
            const SizedBox(height: 60),
            EmptyState(
              icon: Iconsax.warning_2,
              title: 'تعذّر تحميل المحافظ',
              subtitle: 'تحقّق من اتصالك وحاول مجدداً',
              actionLabel: 'إعادة المحاولة',
              onAction: () => ref.invalidate(walletsProvider),
            ),
          ],
        ),
      ),
    );
  }

  void _showCreateWalletDialog(BuildContext context, WidgetRef ref) {
    final colors = context.appColors;
    showModalBottomSheet(
      context: context,
      backgroundColor: colors.surface,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (context) => Padding(
        padding: const EdgeInsets.all(AppSpacing.xxl),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
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
            const SizedBox(height: AppSpacing.lg),
            Row(
              children: const [
                IconTile(icon: Iconsax.wallet_add),
                SizedBox(width: AppSpacing.md),
                Text(
                  'إنشاء محفظة جديدة',
                  style: TextStyle(fontSize: 18, fontWeight: FontWeight.w700),
                ),
              ],
            ),
            const SizedBox(height: AppSpacing.xxl),
            _CurrencyOption(
              currency: 'USD',
              name: 'دولار أمريكي',
              icon: Iconsax.dollar_circle,
              color: colors.walletUSD,
              onTap: () async {
                Navigator.pop(context);
                await ref.read(walletRepositoryProvider).createWallet('USD');
                ref.invalidate(walletsProvider);
              },
            ),
            const SizedBox(height: AppSpacing.md),
            _CurrencyOption(
              currency: 'SYP',
              name: 'ليرة سورية',
              icon: Iconsax.money,
              color: colors.walletSYP,
              onTap: () async {
                Navigator.pop(context);
                await ref.read(walletRepositoryProvider).createWallet('SYP');
                ref.invalidate(walletsProvider);
              },
            ),
            const SizedBox(height: AppSpacing.xxl),
          ],
        ),
      ),
    );
  }
}

class _CurrencyOption extends StatelessWidget {
  final String currency;
  final String name;
  final IconData icon;
  final Color color;
  final VoidCallback onTap;

  const _CurrencyOption({
    required this.currency,
    required this.name,
    required this.icon,
    required this.color,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(AppRadius.lg),
      child: Container(
        padding: const EdgeInsets.all(AppSpacing.lg),
        decoration: BoxDecoration(
          border: Border.all(color: colors.inputBackground),
          borderRadius: BorderRadius.circular(AppRadius.lg),
        ),
        child: Row(
          children: [
            Container(
              width: 52,
              height: 52,
              decoration: BoxDecoration(
                color: color.withValues(alpha: 0.12),
                borderRadius: BorderRadius.circular(14),
              ),
              child: Icon(icon, color: color, size: 26),
            ),
            const SizedBox(width: AppSpacing.lg),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    currency,
                    style: const TextStyle(
                        fontSize: 16, fontWeight: FontWeight.bold),
                  ),
                  Text(
                    name,
                    style: TextStyle(
                        fontSize: 13, color: colors.textSecondary),
                  ),
                ],
              ),
            ),
            Icon(Iconsax.arrow_left_2,
                color: colors.textHint, size: 18),
          ],
        ),
      ),
    );
  }
}
