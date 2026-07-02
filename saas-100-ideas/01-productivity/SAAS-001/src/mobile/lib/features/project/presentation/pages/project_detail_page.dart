import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';
import '../../../../core/constants/app_colors.dart';
import '../../../../core/constants/app_dimensions.dart';
import '../../../../core/localization/app_localizations.dart';
import '../../../../shared/widgets/loading_indicator.dart';
import '../../../../shared/widgets/error_view.dart';
import '../bloc/project_bloc.dart';

class ProjectDetailPage extends StatelessWidget {
  final String projectId;

  const ProjectDetailPage({super.key, required this.projectId});

  @override
  Widget build(BuildContext context) {
    final localizations = AppLocalizations.of(context);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Project Details'),
        actions: [
          IconButton(
            icon: const Icon(Icons.dashboard_outlined),
            onPressed: () => context.push('/projects/$projectId/board'),
            tooltip: localizations.kanban,
          ),
        ],
      ),
      body: BlocBuilder<ProjectBloc, ProjectState>(
        builder: (context, state) {
          if (state is ProjectLoading) {
            return const LoadingIndicator();
          }
          if (state is ProjectError) {
            return ErrorView(
              message: state.message,
              onRetry: () {},
            );
          }
          return SingleChildScrollView(
            padding: const EdgeInsets.all(AppDimensions.spacing24),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Project header card
                Card(
                  child: Padding(
                    padding: const EdgeInsets.all(AppDimensions.spacing20),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Container(
                              width: 12,
                              height: 12,
                              decoration: const BoxDecoration(
                                color: Color(0xFF4F46E5),
                                shape: BoxShape.circle,
                              ),
                            ),
                            const SizedBox(width: 12),
                            Expanded(
                              child: Text(
                                'Project Name',
                                style: Theme.of(context).textTheme.headlineSmall,
                              ),
                            ),
                            _StatusChip(status: 'active'),
                          ],
                        ),
                        const SizedBox(height: AppDimensions.spacing12),
                        Text(
                          'Project description will appear here.',
                          style: Theme.of(context).textTheme.bodyMedium,
                        ),
                        const SizedBox(height: AppDimensions.spacing16),
                        Row(
                          children: [
                            _InfoChip(
                              icon: Icons.calendar_today,
                              label: 'Start: --',
                            ),
                            const SizedBox(width: 16),
                            _InfoChip(
                              icon: Icons.event,
                              label: 'End: --',
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: AppDimensions.spacing24),

                // Task progress
                Text(
                  localizations.tasks,
                  style: Theme.of(context).textTheme.titleMedium,
                ),
                const SizedBox(height: AppDimensions.spacing12),

                // Quick actions
                Row(
                  children: [
                    _ActionChip(
                      icon: Icons.add_task,
                      label: localizations.createTask,
                      onTap: () => context.push('/tasks/create'),
                    ),
                    const SizedBox(width: 12),
                    _ActionChip(
                      icon: Icons.dashboard_outlined,
                      label: localizations.kanban,
                      onTap: () => context.push('/projects/$projectId/board'),
                    ),
                  ],
                ),
              ],
            ),
          );
        },
      ),
    );
  }
}

class _StatusChip extends StatelessWidget {
  final String status;

  const _StatusChip({required this.status});

  @override
  Widget build(BuildContext context) {
    final isActive = status == 'active';
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: isActive ? AppColors.successLight : AppColors.neutral100,
        borderRadius: BorderRadius.circular(AppDimensions.radiusBadge),
      ),
      child: Text(
        isActive ? 'Active' : 'Archived',
        style: TextStyle(
          fontSize: 12,
          fontWeight: FontWeight.w600,
          color: isActive ? AppColors.success : AppColors.neutral500,
        ),
      ),
    );
  }
}

class _InfoChip extends StatelessWidget {
  final IconData icon;
  final String label;

  const _InfoChip({required this.icon, required this.label});

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Icon(icon, size: 16, color: AppColors.neutral500),
        const SizedBox(width: 6),
        Text(label, style: Theme.of(context).textTheme.bodySmall),
      ],
    );
  }
}

class _ActionChip extends StatelessWidget {
  final IconData icon;
  final String label;
  final VoidCallback onTap;

  const _ActionChip({
    required this.icon,
    required this.label,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return OutlinedButton.icon(
      onPressed: onTap,
      icon: Icon(icon, size: 18),
      label: Text(label),
    );
  }
}
