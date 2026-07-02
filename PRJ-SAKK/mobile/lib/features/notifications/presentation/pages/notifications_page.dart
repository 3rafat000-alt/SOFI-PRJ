import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:iconsax/iconsax.dart';
import 'package:flutter_animate/flutter_animate.dart';

import '../../../../core/theme/app_colors.dart';
import '../../../../core/widgets/app_skeleton.dart';
import '../../../../core/widgets/app_ui.dart';
import '../../data/models/notification_model.dart';
import '../../data/repositories/notification_repository.dart';

/// Notifications — a refined, date-grouped feed with per-type visuals, a soft
/// unread summary, and a clean unread accent. Tap a card to mark it read.
class NotificationsPage extends ConsumerWidget {
  const NotificationsPage({super.key});

  ({IconData icon, Color color}) _visual(BuildContext context, String? code) {
    final colors = context.appColors;
    switch (code) {
      case 'p2p_received':
      case 'transfer_in':
        return (icon: Iconsax.arrow_down_2, color: colors.success);
      case 'p2p_sent':
      case 'transfer_out':
        return (icon: Iconsax.arrow_up_3, color: colors.error);
      case 'salary_received':
      case 'salary_in':
        return (icon: Iconsax.briefcase, color: colors.success);
      case 'payment_request':
        return (icon: Iconsax.money_recive, color: colors.primary);
      case 'payment_request_accepted':
        return (icon: Iconsax.tick_circle, color: colors.success);
      case 'payment_request_rejected':
        return (icon: Iconsax.close_circle, color: colors.error);
      case 'kyc_level_upgrade':
        return (icon: Iconsax.shield_tick, color: colors.primary);
      case 'document_verified':
        return (icon: Iconsax.verify, color: colors.success);
      case 'kyc_rejected':
        return (icon: Iconsax.close_circle, color: colors.error);
      default:
        return (icon: Iconsax.notification, color: colors.primary);
    }
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final notificationsAsync = ref.watch(notificationsProvider);

    return AppScaffold(
      title: 'الإشعارات',
      subtitle: 'آخر التحديثات على حسابك',
      action: notificationsAsync.maybeWhen(
        data: (items) => items.any((n) => !n.isRead)
            ? _MarkAllButton(
                onTap: () async {
                  try {
                    await ref
                        .read(notificationRepositoryProvider)
                        .markAllAsRead();
                    ref.invalidate(notificationsProvider);
                    ref.invalidate(unreadNotificationsProvider);
                  } catch (_) {}
                },
              )
            : null,
        orElse: () => null,
      ),
      onRefresh: () async {
        ref.invalidate(notificationsProvider);
        ref.invalidate(unreadNotificationsProvider);
      },
      body: notificationsAsync.when(
        loading: () => const SkeletonListScene(items: 6, trailing: false),
        error: (e, _) => _fill(EmptyState(
          icon: Iconsax.warning_2,
          title: 'تعذّر تحميل الإشعارات',
          subtitle: 'تحقّق من اتصالك وحاول مجدداً',
          actionLabel: 'إعادة المحاولة',
          onAction: () => ref.invalidate(notificationsProvider),
        )),
        data: (items) {
          if (items.isEmpty) {
            return _fill(const EmptyState(
              icon: Iconsax.notification,
              title: 'لا توجد إشعارات',
              subtitle: 'ستظهر هنا تنبيهاتك وتحديثات حسابك أولاً بأول.',
            ));
          }

          final unread = items.where((n) => !n.isRead).length;
          final groups = _group(items);

          final children = <Widget>[];
          if (unread > 0) children.add(_unreadBanner(context, unread));

          var i = 0;
          groups.forEach((label, list) {
            children.add(_dateHeader(context, label, top: children.isNotEmpty));
            for (final n in list) {
              children.add(
                _NotificationTile(
                  notification: n,
                  visual: _visual(context, n.templateCode),
                  onTap: () async {
                    if (!n.isRead) {
                      try {
                        await ref
                            .read(notificationRepositoryProvider)
                            .markAsRead(n.id);
                        ref.invalidate(notificationsProvider);
                        ref.invalidate(unreadNotificationsProvider);
                      } catch (_) {}
                    }
                  },
                )
                    .animate(delay: Duration(milliseconds: i * 35))
                    .fadeIn(duration: 280.ms)
                    .slideY(begin: 0.06),
              );
              i++;
            }
          });

          return ListView(
            padding: const EdgeInsets.fromLTRB(
                AppSpacing.lg, AppSpacing.md, AppSpacing.lg, AppSpacing.xxl),
            children: children,
          );
        },
      ),
    );
  }

  Widget _fill(Widget child) => LayoutBuilder(
        builder: (ctx, c) => SingleChildScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          child: ConstrainedBox(
            constraints: BoxConstraints(minHeight: c.maxHeight),
            child: Center(child: child),
          ),
        ),
      );

  Widget _unreadBanner(BuildContext context, int count) {
    final colors = context.appColors;
    return Container(
      margin: const EdgeInsets.only(bottom: AppSpacing.md),
      padding: const EdgeInsets.symmetric(
          horizontal: AppSpacing.lg, vertical: AppSpacing.md),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: colors.cardGradientVisa,
          begin: Alignment.topRight,
          end: Alignment.bottomLeft,
        ),
        borderRadius: BorderRadius.circular(AppRadius.lg),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.3),
            blurRadius: 16,
            offset: const Offset(0, 6),
          ),
        ],
      ),
      child: Row(
        children: [
          Container(
            width: 40,
            height: 40,
            decoration: BoxDecoration(
                color: Colors.white.withValues(alpha: 0.2), shape: BoxShape.circle),
            child: const Icon(Iconsax.notification_bing,
                color: Colors.white, size: 20),
          ),
          const SizedBox(width: AppSpacing.md),
          Expanded(
            child: Text(
              count == 1
                  ? 'لديك إشعار واحد غير مقروء'
                  : 'لديك $count إشعارات غير مقروءة',
              style: const TextStyle(
                  color: Colors.white,
                  fontSize: 14,
                  fontWeight: FontWeight.w700),
            ),
          ),
        ],
      ),
    ).animate().fadeIn(duration: 300.ms).slideY(begin: -0.1);
  }

  Widget _dateHeader(BuildContext context, String label, {bool top = false}) {
    final colors = context.appColors;
    return Padding(
      padding: EdgeInsets.only(
          top: top ? AppSpacing.lg : 0, bottom: AppSpacing.sm, right: 2),
      child: Text(
        label,
        style: TextStyle(
          fontSize: 13,
          fontWeight: FontWeight.w700,
          color: colors.textSecondary,
        ),
      ),
    );
  }

  /// Bucket notifications into اليوم / أمس / هذا الأسبوع / الأقدم.
  Map<String, List<NotificationModel>> _group(List<NotificationModel> items) {
    final now = DateTime.now();
    final out = <String, List<NotificationModel>>{};
    for (final n in items) {
      final d = n.createdAt;
      String key;
      if (d.year == now.year && d.month == now.month && d.day == now.day) {
        key = 'اليوم';
      } else if (d.year == now.year &&
          d.month == now.month &&
          d.day == now.day - 1) {
        key = 'أمس';
      } else if (now.difference(d).inDays < 7) {
        key = 'هذا الأسبوع';
      } else {
        key = 'الأقدم';
      }
      out.putIfAbsent(key, () => []).add(n);
    }
    return out;
  }
}

class _MarkAllButton extends StatelessWidget {
  final VoidCallback onTap;
  const _MarkAllButton({required this.onTap});

  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    return TextButton.icon(
      onPressed: onTap,
      style: TextButton.styleFrom(
        foregroundColor: colors.primary,
        padding: const EdgeInsets.symmetric(horizontal: AppSpacing.sm),
      ),
      icon: const Icon(Iconsax.tick_circle, size: 18),
      label: const Text('قراءة الكل',
          style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.w700)),
    );
  }
}

class _NotificationTile extends StatelessWidget {
  final NotificationModel notification;
  final ({IconData icon, Color color}) visual;
  final VoidCallback onTap;

  const _NotificationTile({
    required this.notification,
    required this.visual,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    final unread = !notification.isRead;
    return Container(
      margin: const EdgeInsets.only(bottom: AppSpacing.sm),
      decoration: BoxDecoration(
        color: unread ? colors.primaryLight.withValues(alpha: 0.4) : colors.surface,
        borderRadius: BorderRadius.circular(AppRadius.lg),
        border: Border.all(
          color: unread
              ? colors.primary.withValues(alpha: 0.18)
              : colors.inputBackground,
        ),
      ),
      clipBehavior: Clip.antiAlias,
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: onTap,
          child: IntrinsicHeight(
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                // Unread accent bar (leading edge).
                Container(
                  width: 4,
                  color: unread ? visual.color : Colors.transparent,
                ),
                Expanded(
                  child: Padding(
                    padding: const EdgeInsets.all(AppSpacing.md),
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Container(
                          width: 44,
                          height: 44,
                          decoration: BoxDecoration(
                            color: visual.color.withValues(alpha: 0.12),
                            borderRadius: BorderRadius.circular(13),
                          ),
                          child: Icon(visual.icon, color: visual.color, size: 21),
                        ),
                        const SizedBox(width: AppSpacing.md),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Expanded(
                                    child: Text(
                                      notification.title,
                                      maxLines: 1,
                                      overflow: TextOverflow.ellipsis,
                                      style: TextStyle(
                                        fontSize: 14.5,
                                        fontWeight: unread
                                            ? FontWeight.w700
                                            : FontWeight.w600,
                                        color: colors.textPrimary,
                                      ),
                                    ),
                                  ),
                                  if (unread)
                                    Container(
                                      width: 8,
                                      height: 8,
                                      margin: const EdgeInsets.only(
                                          top: 5, right: 6),
                                      decoration: BoxDecoration(
                                        color: visual.color,
                                        shape: BoxShape.circle,
                                      ),
                                    ),
                                ],
                              ),
                              const SizedBox(height: 4),
                              Text(
                                notification.body,
                                maxLines: 2,
                                overflow: TextOverflow.ellipsis,
                                style: TextStyle(
                                  fontSize: 13,
                                  color: colors.textSecondary,
                                  height: 1.5,
                                ),
                              ),
                              const SizedBox(height: 8),
                              Row(
                                children: [
                                  Icon(Iconsax.clock,
                                      size: 12, color: colors.textHint),
                                  const SizedBox(width: 4),
                                  Text(
                                    notification.formattedDate,
                                    style: TextStyle(
                                        fontSize: 11,
                                        color: colors.textHint),
                                  ),
                                ],
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
