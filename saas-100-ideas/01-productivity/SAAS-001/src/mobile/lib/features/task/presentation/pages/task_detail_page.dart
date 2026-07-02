import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../../../core/constants/app_colors.dart';
import '../../../../core/constants/app_dimensions.dart';
import '../../../../core/localization/app_localizations.dart';
import '../../../../shared/widgets/loading_indicator.dart';
import '../../../../shared/widgets/error_view.dart';
import '../bloc/task_bloc.dart';
import '../widgets/priority_badge.dart';
import '../widgets/status_badge.dart';

class TaskDetailPage extends StatelessWidget {
  final String taskId;

  const TaskDetailPage({super.key, required this.taskId});

  @override
  Widget build(BuildContext context) {
    final localizations = AppLocalizations.of(context);
    final isArabic = localizations.isArabic;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Task Details'),
        actions: [
          IconButton(
            icon: const Icon(Icons.edit_outlined),
            onPressed: () {
              // TODO: edit task
            },
          ),
        ],
      ),
      body: BlocBuilder<TaskBloc, TaskState>(
        builder: (context, state) {
          if (state is TaskLoading) {
            return const LoadingIndicator();
          }
          if (state is TaskError) {
            return ErrorView(
              message: state.message,
              onRetry: () {},
            );
          }
          if (state is TasksLoaded) {
            final task = state.tasks.where((t) => t.id == taskId).firstOrNull;
            if (task == null) {
              return const ErrorView(message: 'Task not found');
            }

            return SingleChildScrollView(
              padding: const EdgeInsets.all(AppDimensions.spacing20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Title + status
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Expanded(
                        child: Text(
                          task.title,
                          style: Theme.of(context).textTheme.headlineMedium,
                        ),
                      ),
                      const SizedBox(width: 12),
                      StatusBadge(status: task.status),
                    ],
                  ),
                  const SizedBox(height: AppDimensions.spacing16),

                  // Priority + Due date row
                  Row(
                    children: [
                      PriorityBadge(priority: task.priority),
                      const SizedBox(width: 16),
                      if (task.dueDate != null)
                        Row(
                          children: [
                            Icon(Icons.calendar_today,
                                size: 16, color: AppColors.neutral500),
                            const SizedBox(width: 6),
                            Text(
                              '${task.dueDate!.month}/${task.dueDate!.day}/${task.dueDate!.year}',
                              style: Theme.of(context).textTheme.bodyMedium,
                            ),
                          ],
                        ),
                    ],
                  ),
                  const SizedBox(height: AppDimensions.spacing20),

                  // Assignee
                  if (task.assignee != null) ...[
                    _SectionHeader(
                      title: localizations.assignee,
                      isArabic: isArabic,
                    ),
                    const SizedBox(height: AppDimensions.spacing8),
                    Row(
                      children: [
                        CircleAvatar(
                          radius: 18,
                          backgroundColor: AppColors.primary.withValues(alpha: 0.1),
                          child: Text(
                            task.assignee!.name.isNotEmpty
                                ? task.assignee!.name[0].toUpperCase()
                                : '?',
                            style: TextStyle(
                              color: AppColors.primary,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ),
                        const SizedBox(width: 12),
                        Text(task.assignee!.name,
                            style: Theme.of(context).textTheme.bodyLarge),
                      ],
                    ),
                    const SizedBox(height: AppDimensions.spacing20),
                  ],

                  // Description
                  if (task.description != null &&
                      task.description!.isNotEmpty) ...[
                    _SectionHeader(
                      title: localizations.taskDescription,
                      isArabic: isArabic,
                    ),
                    const SizedBox(height: AppDimensions.spacing8),
                    Text(
                      task.description!,
                      style: Theme.of(context).textTheme.bodyLarge,
                    ),
                    const SizedBox(height: AppDimensions.spacing20),
                  ],

                  // Time info
                  _SectionHeader(
                    title: localizations.timeTracking,
                    isArabic: isArabic,
                  ),
                  const SizedBox(height: AppDimensions.spacing8),
                  Row(
                    children: [
                      _InfoItem(
                        icon: Icons.timer_outlined,
                        label: '${localizations.estimatedHours}: ${task.estimatedMinutes ~/ 60}h',
                      ),
                      const SizedBox(width: 24),
                      _InfoItem(
                        icon: Icons.access_time,
                        label: '${localizations.loggedHours}: ${task.loggedMinutes ~/ 60}h',
                      ),
                    ],
                  ),
                  const SizedBox(height: AppDimensions.spacing20),

                  // Tags
                  if (task.tags.isNotEmpty) ...[
                    _SectionHeader(
                      title: localizations.tags,
                      isArabic: isArabic,
                    ),
                    const SizedBox(height: AppDimensions.spacing8),
                    Wrap(
                      spacing: 8,
                      runSpacing: 4,
                      children: task.tags.map((tag) {
                        return Chip(
                          label: Text(tag.name, style: const TextStyle(fontSize: 12)),
                          backgroundColor: _parseColor(tag.color).withValues(alpha: 0.1),
                          side: BorderSide.none,
                        );
                      }).toList(),
                    ),
                    const SizedBox(height: AppDimensions.spacing20),
                  ],

                  // Quick status change
                  _SectionHeader(
                    title: localizations.quickStatusChange,
                    isArabic: isArabic,
                  ),
                  const SizedBox(height: AppDimensions.spacing8),
                  Row(
                    children: ['todo', 'in_progress', 'done'].map((status) {
                      return Padding(
                        padding: const EdgeInsets.only(right: 8),
                        child: OutlinedButton(
                          onPressed: task.status == status
                              ? null
                              : () {
                                  context.read<TaskBloc>().add(
                                        QuickStatusChangeEvent(
                                          taskId: task.id,
                                          status: status,
                                        ),
                                      );
                                },
                          style: OutlinedButton.styleFrom(
                            side: BorderSide(
                              color: task.status == status
                                  ? AppColors.primary
                                  : AppColors.neutral300,
                            ),
                          ),
                          child: Text(_statusLabel(status, localizations)),
                        ),
                      );
                    }).toList(),
                  ),

                  const SizedBox(height: AppDimensions.spacing24),

                  // Comments section placeholder
                  _SectionHeader(
                    title: '${localizations.comments} (${task.commentsCount})',
                    isArabic: isArabic,
                  ),
                  const SizedBox(height: AppDimensions.spacing8),
                  Container(
                    padding: const EdgeInsets.all(AppDimensions.spacing16),
                    decoration: BoxDecoration(
                      color: AppColors.neutral50,
                      borderRadius: BorderRadius.circular(AppDimensions.radiusCard),
                    ),
                    child: Center(
                      child: Text(
                        isArabic ? 'لا توجد تعليقات بعد' : 'No comments yet',
                        style: Theme.of(context).textTheme.bodyMedium,
                      ),
                    ),
                  ),
                ],
              ),
            );
          }
          return const LoadingIndicator();
        },
      ),
    );
  }

  String _statusLabel(String status, AppLocalizations l) {
    switch (status) {
      case 'todo':
        return l.todo;
      case 'in_progress':
        return l.inProgress;
      case 'done':
        return l.done;
      default:
        return status;
    }
  }

  Color _parseColor(String hex) {
    hex = hex.replaceAll('#', '');
    if (hex.length == 6) hex = 'FF$hex';
    return Color(int.parse(hex, radix: 16));
  }
}

class _SectionHeader extends StatelessWidget {
  final String title;
  final bool isArabic;

  const _SectionHeader({required this.title, required this.isArabic});

  @override
  Widget build(BuildContext context) {
    return Text(
      title,
      style: Theme.of(context).textTheme.titleSmall?.copyWith(
            fontWeight: FontWeight.w600,
            color: AppColors.textPrimary,
          ),
    );
  }
}

class _InfoItem extends StatelessWidget {
  final IconData icon;
  final String label;

  const _InfoItem({required this.icon, required this.label});

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Icon(icon, size: 18, color: AppColors.neutral500),
        const SizedBox(width: 6),
        Text(label, style: Theme.of(context).textTheme.bodyMedium),
      ],
    );
  }
}
