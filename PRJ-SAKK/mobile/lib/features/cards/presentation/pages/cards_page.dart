import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:iconsax/iconsax.dart';
import 'package:flutter_animate/flutter_animate.dart';

import '../../../../core/theme/app_colors.dart';
import '../../../../core/widgets/app_skeleton.dart';
import '../../../../core/widgets/app_ui.dart';
import '../../data/repositories/card_repository.dart';
import '../../data/models/card_model.dart';
import '../widgets/virtual_card_widget.dart';

class CardsPage extends ConsumerWidget {
  const CardsPage({super.key});

  /// Platform "reduce motion" signal (Settings → Accessibility). When true,
  /// entrance animations must be skipped so we don't override the user's
  /// preference (WCAG 2.2 SC 2.3.3).
  static bool _reduceMotion(BuildContext context) =>
      MediaQuery.maybeOf(context)?.disableAnimations ?? false;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final enabledAsync = ref.watch(cardsEnabledProvider);

    return enabledAsync.when(
      // Feature gated off (Stripe Issuing not configured) → "coming soon".
      data:
          (enabled) =>
              enabled ? _enabledBody(context, ref) : _comingSoon(context, ref),
      loading:
          () => const AppScaffold(
            title: 'البطاقات',
            showBack: false,
            body: SakkShimmer(
              child: Padding(
                padding: EdgeInsets.all(20),
                child: Column(
                  children: [
                    SkeletonCard(height: 200),
                    SkeletonCard(height: 200),
                  ],
                ),
              ),
            ),
          ),
      // Can't confirm the flag → fail closed to coming-soon, never a card UI.
      error: (_, __) => _comingSoon(context, ref),
    );
  }

  Widget _comingSoon(BuildContext context, WidgetRef ref) => AppScaffold(
    title: 'البطاقات',
    showBack: false,
    onRefresh: () async => ref.invalidate(cardsEnabledProvider),
    body: const EmptyState(
      icon: Iconsax.card,
      title: 'البطاقات الافتراضية — قريباً',
      subtitle: 'ميزة إصدار البطاقات قيد التفعيل وستتوفّر قريباً.',
    ),
  );

  Widget _enabledBody(BuildContext context, WidgetRef ref) {
    final cardsAsync = ref.watch(cardsProvider);

    final totalBalance = cardsAsync.whenOrNull(
      data: (cards) => cards.fold<double>(0, (sum, c) => sum + c.balance),
    );

    return AppScaffold(
      title: 'البطاقات',
      showBack: false,
      onRefresh: () async => ref.invalidate(cardsProvider),
      body: cardsAsync.when(
        data:
            (cards) =>
                cards.isEmpty
                    ? _emptyState(context, ref)
                    : _buildContent(context, ref, cards, totalBalance ?? 0),
        loading:
            () => const SakkShimmer(
              child: Padding(
                padding: EdgeInsets.all(20),
                child: Column(
                  children: [
                    SkeletonCard(height: 200),
                    SkeletonCard(height: 200),
                  ],
                ),
              ),
            ),
        error:
            (error, _) => EmptyState(
              icon: Iconsax.warning_2,
              title: 'تعذّر تحميل البطاقات',
              subtitle: 'تحقّق من اتصالك وحاول مجدداً',
              actionLabel: 'إعادة المحاولة',
              onAction: () => ref.invalidate(cardsProvider),
            ),
      ),
    );
  }

  Widget _buildContent(
    BuildContext context,
    WidgetRef ref,
    List<CardModel> cards,
    double totalBalance,
  ) {
    final colors = context.appColors;
    final sorted = List<CardModel>.from(cards)..sort((a, b) {
      if (a.isActive != b.isActive) return a.isActive ? -1 : 1;
      if (a.isFrozen != b.isFrozen) return a.isFrozen ? -1 : 1;
      return 0;
    });
    // User-chosen featured card (persisted) — fall back to auto-selection.
    final eligible = sorted.where((c) => c.isActive || c.isFrozen).toList();
    final featuredId = ref.watch(featuredCardIdProvider);
    final featured = eligible.firstWhere(
      (c) => c.id == featuredId,
      orElse: () => eligible.isNotEmpty ? eligible.first : sorted.first,
    );
    final activeCards = sorted.where((c) => !c.isCancelled).toList();
    final cancelledCards = sorted.where((c) => c.isCancelled).toList();

    return SingleChildScrollView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.fromLTRB(20, 8, 20, 100),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _totalBalanceCard(context, totalBalance),
          const SizedBox(height: 24),
          if (cards.isNotEmpty) ...[
            Padding(
              padding: const EdgeInsets.only(right: 4),
              child: Text(
                'البطاقة المميزة',
                style: TextStyle(
                  fontSize: 15,
                  fontWeight: FontWeight.bold,
                  color: colors.textPrimary,
                ),
              ),
            ),
            const SizedBox(height: 12),
            _featuredCard(context, featured),
            const SizedBox(height: 24),
            if (activeCards.isNotEmpty) ...[
              Padding(
                padding: const EdgeInsets.only(right: 4),
                child: Text(
                  'جميع البطاقات',
                  style: TextStyle(
                    fontSize: 15,
                    fontWeight: FontWeight.bold,
                    color: colors.textPrimary,
                  ),
                ),
              ),
              const SizedBox(height: 12),
              ...activeCards.map((card) => _compactCard(context, card)),
            ],
            if (cancelledCards.isNotEmpty) ...[
              const SizedBox(height: 8),
              Padding(
                padding: const EdgeInsets.only(right: 4),
                child: Text(
                  'البطاقات الملغية',
                  style: TextStyle(fontSize: 13, color: colors.textHint),
                ),
              ),
              const SizedBox(height: 8),
              ...cancelledCards.map((card) => _compactCard(context, card)),
            ],
            const SizedBox(height: 8),
            _addCardButton(context),
          ],
        ],
      ),
    );
  }

  Widget _addCardButton(BuildContext context) {
    final colors = context.appColors;
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: () => context.push('/cards/create'),
        borderRadius: BorderRadius.circular(AppRadius.lg),
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 18),
          decoration: BoxDecoration(
            color: colors.primaryLight.withValues(alpha: 0.4),
            borderRadius: BorderRadius.circular(AppRadius.lg),
            border: Border.all(
              color: colors.primary.withValues(alpha: 0.3),
              width: 1.5,
            ),
          ),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(Iconsax.add_circle, color: colors.primary, size: 22),
              const SizedBox(width: 8),
              Text(
                'إضافة بطاقة جديدة',
                style: TextStyle(
                  color: colors.primary,
                  fontSize: 15,
                  fontWeight: FontWeight.w700,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _totalBalanceCard(BuildContext context, double balance) {
    final colors = context.appColors;
    final content = Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: colors.cardGradientVisa,
          begin: Alignment.centerLeft,
          end: Alignment.centerRight,
        ),
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.3),
            blurRadius: 20,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: Row(
        children: [
          Container(
            width: 52,
            height: 52,
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.2),
              borderRadius: BorderRadius.circular(16),
            ),
            child: const Icon(Iconsax.wallet_2, color: Colors.white, size: 28),
          ),
          const SizedBox(width: 16),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'إجمالي الرصيد',
                  style: TextStyle(
                    color: Colors.white.withValues(alpha: 0.85),
                    fontSize: 13,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  '\$${balance.toStringAsFixed(2)}',
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 28,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ],
            ),
          ),
          Container(
            width: 42,
            height: 42,
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.18),
              borderRadius: BorderRadius.circular(12),
            ),
            child: const Icon(
              Iconsax.arrow_left_2,
              color: Colors.white,
              size: 20,
            ),
          ),
        ],
      ),
    );
    return _reduceMotion(context)
        ? content
        : content.animate().fadeIn(duration: 400.ms).slideY(begin: 0.1, end: 0);
  }

  Widget _featuredCard(BuildContext context, CardModel card) {
    final colors = context.appColors;
    final statusKind =
        card.isFrozen
            ? StatusKind.warning
            : (card.isCancelled ? StatusKind.error : StatusKind.success);

    final tappable = !card.isCancelled;
    final content = Semantics(
      button: tappable,
      label:
          tappable
              ? 'بطاقة ${card.label}، اضغط للإدارة'
              : 'بطاقة ${card.label}، ملغاة',
      child: GestureDetector(
        onTap:
            card.isCancelled ? null : () => context.push('/cards/${card.id}'),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            // Self-contained card visual (own gradient, radius and shadow).
            VirtualCardWidget(card: card),
            const SizedBox(height: 14),
            // Clean caption row — status on the start, manage hint on the end.
            // (No more overlays painted on top of the card.)
            Row(
              children: [
                StatusBadge(label: card.statusLabel, kind: statusKind),
                const Spacer(),
                if (!card.isCancelled)
                  Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Text(
                        'إدارة البطاقة',
                        style: TextStyle(
                          color: colors.primary,
                          fontSize: 13,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                      const SizedBox(width: 4),
                      Icon(
                        Iconsax.arrow_left_2,
                        color: colors.primary,
                        size: 16,
                      ),
                    ],
                  ),
              ],
            ),
          ],
        ),
      ),
    );
    return _reduceMotion(context)
        ? content
        : content
            .animate()
            .fadeIn(duration: 500.ms)
            .slideY(begin: 0.08, end: 0);
  }

  Widget _compactCard(BuildContext context, CardModel card) {
    final colors = context.appColors;
    final statusKind =
        card.isFrozen
            ? StatusKind.warning
            : (card.isCancelled ? StatusKind.error : StatusKind.success);

    final compactTappable = !card.isCancelled;
    final content = Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Semantics(
        button: compactTappable,
        label:
            compactTappable
                ? 'بطاقة ${card.label}، اضغط للإدارة'
                : 'بطاقة ${card.label}، ملغاة',
        child: AppCard(
          onTap:
              card.isCancelled ? null : () => context.push('/cards/${card.id}'),
          padding: const EdgeInsets.all(14),
          child: Opacity(
            opacity: card.isCancelled ? 0.55 : 1.0,
            child: Row(
              children: [
                // Unified primary avatar with brand initial (no colour scatter)
                Container(
                  width: 48,
                  height: 48,
                  decoration: BoxDecoration(
                    gradient: LinearGradient(
                      colors: colors.cardGradientVisa,
                      begin: Alignment.topLeft,
                      end: Alignment.bottomRight,
                    ),
                    borderRadius: BorderRadius.circular(14),
                  ),
                  child: Center(
                    child: Text(
                      card.brand[0].toUpperCase(),
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 20,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ),
                ),
                const SizedBox(width: 14),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Expanded(
                            child: Text(
                              card.label,
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: TextStyle(
                                fontSize: 14.5,
                                fontWeight: FontWeight.w600,
                                color: colors.textPrimary,
                              ),
                            ),
                          ),
                          StatusBadge(
                            label: card.statusLabel,
                            kind: statusKind,
                          ),
                        ],
                      ),
                      const SizedBox(height: 6),
                      Row(
                        children: [
                          Text(
                            card.maskedNumber,
                            textDirection: TextDirection.ltr,
                            style: TextStyle(
                              fontSize: 12,
                              color: colors.textSecondary,
                              fontFamily: 'monospace',
                            ),
                          ),
                          const Spacer(),
                          Text(
                            card.formattedBalance,
                            style: TextStyle(
                              fontSize: 16,
                              fontWeight: FontWeight.bold,
                              color: colors.textPrimary,
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 4),
                      Text(
                        'صالحة حتى ${card.expiryDate}',
                        style: TextStyle(fontSize: 11, color: colors.textHint),
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: 4),
                Icon(
                  card.isCancelled
                      ? Iconsax.close_circle
                      : Iconsax.arrow_left_2,
                  color: card.isCancelled ? colors.error : colors.textHint,
                  size: 18,
                ),
              ],
            ),
          ),
        ),
      ),
    );
    return _reduceMotion(context)
        ? content
        : content
            .animate()
            .fadeIn(duration: 300.ms)
            .slideX(begin: 0.03, end: 0);
  }

  Widget _emptyState(BuildContext context, WidgetRef ref) {
    final colors = context.appColors;
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Container(
            width: 80,
            height: 80,
            decoration: BoxDecoration(
              color: colors.primaryLight,
              borderRadius: BorderRadius.circular(20),
            ),
            child: Icon(Iconsax.card, size: 40, color: colors.primary),
          ),
          const SizedBox(height: 20),
          Text(
            'لا توجد بطاقات',
            style: TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.w600,
              color: colors.textPrimary,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            'أنشئ بطاقة افتراضية للتسوق الآمن',
            style: TextStyle(fontSize: 14, color: colors.textSecondary),
          ),
          const SizedBox(height: 24),
          ElevatedButton.icon(
            onPressed: () => context.push('/cards/create'),
            icon: const Icon(Iconsax.add),
            label: const Text('إنشاء بطاقة'),
          ),
        ],
      ),
    );
  }
}
