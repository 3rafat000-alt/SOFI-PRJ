import 'package:flutter/material.dart';
import '../../../../core/constants/app_colors.dart';
import '../../../../core/constants/app_dimensions.dart';
import '../../domain/entities/task.dart';
import 'priority_badge.dart';
import 'status_badge.dart';

class TaskCard extends StatelessWidget {
  final Task task;
  final VoidCallback onTap;
  final bool isDragging;

  const TaskCard({
    super.key,
    required this.task,
    required this.onTap,
    this.isDragging = false,
  });

  @override
  Widget build(BuildContext context) {
    return Opacity(
      opacity: isDragging ? 0.5 : 1.0,
      child: Card(
        margin: const EdgeInsets.only(bottom: AppDimensions.spacing8),
        child: InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(AppDimensions.radiusCard),
          child: Container(
            decoration: task.isOverdue
                ? const BoxDecoration(
                    border: Border(
                      right: BorderSide(color: AppColors.error, width: 3),
                    ),
                  )
                : null,
            padding: const EdgeInsets.all(AppDimensions.spacing16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Title
                Text(
                  task.title,
                  style: Theme.of(context).textTheme.titleSmall?.copyWith(
                        fontWeight: FontWeight.w600,
                      ),
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                ),
                const SizedBox(height: AppDimensions.spacing8),

                // Badges row
                Row(
                  children: [
                    PriorityBadge(priority: task.priority),
                    const SizedBox(width: AppDimensions.spacing8),
                    StatusBadge(status: task.status),
                    if (task.isOverdue) ...[
                      const SizedBox(width: AppDimensions.spacing8),
                      const Icon(Icons.warning_amber_rounded,
                          size: 14, color: AppColors.error),
                    ],
                  ],
                ),
                const SizedBox(height: AppDimensions.spacing10),

                // Footer row
                Row(
                  children: [
                    // Assignee avatar
                    if (task.assignee != null)
                      CircleAvatar(
                        radius: 12,
                        backgroundColor: AppColors.primary.withValues(alpha: 0.1),
                        child: Text(
                          task.assignee!.name.isNotEmpty
                              ? task.assignee!.name[0].toUpperCase()
                              : '?',
                          style: const TextStyle(
                            fontSize: 10,
                            fontWeight: FontWeight.w600,
                            color: AppColors.primary,
                          ),
                        ),
                      ),
                    const Spacer(),
                    // Due date
                    if (task.dueDate != null)
                      Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(
                            Icons.calendar_today_outlined,
                            size: 12,
                            color: task.isOverdue
                                ? AppColors.error
                                : AppColors.neutral400,
                          ),
                          const SizedBox(width: 4),
                          Text(
                            '${task.dueDate!.month}/${task.dueDate!.day}',
                            style: TextStyle(
                              fontSize: 11,
                              color: task.isOverdue
                                  ? AppColors.error
                                  : AppColors.neutral400,
                            ),
                          ),
                        ],
                      ),
                    const SizedBox(width: AppDimensions.spacing12),
                    // Time estimate
                    if (task.estimatedMinutes > 0)
                      Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          const Icon(Icons.timer_outlined,
                              size: 12, color: AppColors.neutral400),
                          const SizedBox(width: 4),
                          Text(
                            '${task.estimatedMinutes ~/ 60}h',
                            style: const TextStyle(
                              fontSize: 11,
                              color: AppColors.neutral400,
                            ),
                          ),
                        ],
                      ),
                    // Comments count
                    if (task.commentsCount > 0) ...[
                      const SizedBox(width: AppDimensions.spacing12),
                      Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          const Icon(Icons.chat_bubble_outline,
                              size: 12, color: AppColors.neutral400),
                          const SizedBox(width: 4),
                          Text(
                            '${task.commentsCount}',
                            style: const TextStyle(
                              fontSize: 11,
                              color: AppColors.neutral400,
                            ),
                          ),
                        ],
                      ),
                    ],
                  ],
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
