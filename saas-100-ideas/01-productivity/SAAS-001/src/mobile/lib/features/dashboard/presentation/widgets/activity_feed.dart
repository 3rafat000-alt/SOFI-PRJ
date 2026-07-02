import 'package:flutter/material.dart';
import '../../../../core/constants/app_colors.dart';
import '../../../../core/constants/app_dimensions.dart';
import '../../../../core/localization/app_localizations.dart';
import '../../domain/entities/dashboard.dart';

class ActivityFeed extends StatelessWidget {
  final List<ActivityItem> activities;
  final VoidCallback? onSeeAll;

  const ActivityFeed({
    super.key,
    required this.activities,
    this.onSeeAll,
  });

  @override
  Widget build(BuildContext context) {
    final localizations = AppLocalizations.of(context);

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Text(
              localizations.recentActivity,
              style: Theme.of(context).textTheme.titleMedium,
            ),
            if (onSeeAll != null)
              TextButton(
                onPressed: onSeeAll,
                child: Text(localizations.seeAll),
              ),
          ],
        ),
        const SizedBox(height: AppDimensions.spacing12),
        if (activities.isEmpty)
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(AppDimensions.spacing24),
            decoration: BoxDecoration(
              color: AppColors.neutral50,
              borderRadius: BorderRadius.circular(AppDimensions.radiusCard),
            ),
            child: Column(
              children: [
                Icon(
                  Icons.history,
                  size: 40,
                  color: AppColors.neutral300,
                ),
                const SizedBox(height: AppDimensions.spacing8),
                Text(
                  localizations.noData,
                  style: Theme.of(context).textTheme.bodyMedium,
                ),
              ],
            ),
          )
        else
          ...activities.take(5).map((activity) {
            return _ActivityItemWidget(activity: activity);
          }),
      ],
    );
  }
}

class _ActivityItemWidget extends StatelessWidget {
  final ActivityItem activity;

  const _ActivityItemWidget({required this.activity});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: AppDimensions.spacing8),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: 32,
            height: 32,
            decoration: BoxDecoration(
              color: _iconBgColor,
              borderRadius: BorderRadius.circular(8),
            ),
            child: Icon(
              _icon,
              color: _iconColor,
              size: 16,
            ),
          ),
          const SizedBox(width: AppDimensions.spacing12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                RichText(
                  text: TextSpan(
                    style: Theme.of(context).textTheme.bodyMedium,
                    children: [
                      TextSpan(
                        text: activity.userName,
                        style: const TextStyle(fontWeight: FontWeight.w600),
                      ),
                      TextSpan(text: ' ${activity.description}'),
                    ],
                  ),
                ),
                const SizedBox(height: 2),
                Row(
                  children: [
                    if (activity.projectName.isNotEmpty) ...[
                      Text(
                        activity.projectName,
                        style: Theme.of(context).textTheme.labelSmall?.copyWith(
                              color: AppColors.primary,
                            ),
                      ),
                      const SizedBox(width: 8),
                    ],
                    Text(
                      _timeAgo(activity.createdAt),
                      style: Theme.of(context).textTheme.labelSmall,
                    ),
                  ],
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  IconData get _icon {
    switch (activity.type) {
      case 'task_created':
        return Icons.add_task;
      case 'task_updated':
        return Icons.edit_note;
      case 'task_completed':
        return Icons.check_circle;
      default:
        return Icons.circle_outlined;
    }
  }

  Color get _iconColor {
    switch (activity.type) {
      case 'task_created':
        return AppColors.primary;
      case 'task_completed':
        return AppColors.success;
      default:
        return AppColors.neutral500;
    }
  }

  Color get _iconBgColor {
    switch (activity.type) {
      case 'task_created':
        return AppColors.primary.withValues(alpha: 0.1);
      case 'task_completed':
        return AppColors.successLight;
      default:
        return AppColors.neutral100;
    }
  }

  String _timeAgo(DateTime date) {
    final diff = DateTime.now().difference(date);
    if (diff.inMinutes < 1) return 'Just now';
    if (diff.inMinutes < 60) return '${diff.inMinutes}m ago';
    if (diff.inHours < 24) return '${diff.inHours}h ago';
    return '${diff.inDays}d ago';
  }
}
