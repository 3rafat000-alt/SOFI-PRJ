import 'package:flutter/material.dart';
import '../../../../core/constants/app_colors.dart';
import '../../../../core/constants/app_dimensions.dart';
import '../../domain/entities/project.dart';

class ProjectCard extends StatelessWidget {
  final Project project;
  final VoidCallback onTap;
  final VoidCallback? onBoardTap;

  const ProjectCard({
    super.key,
    required this.project,
    required this.onTap,
    this.onBoardTap,
  });

  @override
  Widget build(BuildContext context) {
    final progress = project.taskCount.total > 0
        ? project.taskCount.done / project.taskCount.total
        : 0.0;

    return Card(
      margin: const EdgeInsets.only(bottom: AppDimensions.spacing12),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(AppDimensions.radiusCard),
        child: Padding(
          padding: const EdgeInsets.all(AppDimensions.spacing16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Header
              Row(
                children: [
                  Container(
                    width: 40,
                    height: 40,
                    decoration: BoxDecoration(
                      color: _parseColor(project.color).withValues(alpha: 0.1),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: Icon(
                      Icons.folder_outlined,
                      color: _parseColor(project.color),
                      size: 20,
                    ),
                  ),
                  const SizedBox(width: AppDimensions.spacing12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          project.name,
                          style: Theme.of(context).textTheme.titleSmall?.copyWith(
                                fontWeight: FontWeight.w600,
                              ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                        if (project.description != null &&
                            project.description!.isNotEmpty) ...[
                          const SizedBox(height: 2),
                          Text(
                            project.description!,
                            style: Theme.of(context).textTheme.bodySmall,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ],
                      ],
                    ),
                  ),
                  if (onBoardTap != null)
                    IconButton(
                      icon: const Icon(Icons.dashboard_outlined, size: 20),
                      onPressed: onBoardTap,
                      color: AppColors.neutral400,
                    ),
                  const Icon(Icons.chevron_right, color: AppColors.neutral400),
                ],
              ),
              const SizedBox(height: AppDimensions.spacing12),

              // Progress bar
              ClipRRect(
                borderRadius: BorderRadius.circular(4),
                child: LinearProgressIndicator(
                  value: progress,
                  backgroundColor: AppColors.neutral200,
                  valueColor: AlwaysStoppedAnimation<Color>(
                    progress == 1.0 ? AppColors.success : AppColors.primary,
                  ),
                  minHeight: 4,
                ),
              ),
              const SizedBox(height: AppDimensions.spacing8),

              // Stats
              Row(
                children: [
                  _StatBadge(
                    icon: Icons.check_circle_outline,
                    label: '${project.taskCount.done}/${project.taskCount.total}',
                    color: AppColors.success,
                  ),
                  const SizedBox(width: AppDimensions.spacing16),
                  _StatBadge(
                    icon: Icons.people_outline,
                    label: '${project.memberCount}',
                    color: AppColors.neutral500,
                  ),
                  const Spacer(),
                  if (project.endDate != null)
                    Text(
                      'Due: ${project.endDate!.month}/${project.endDate!.day}',
                      style: Theme.of(context).textTheme.bodySmall?.copyWith(
                            color: project.endDate!.isBefore(DateTime.now())
                                ? AppColors.error
                                : AppColors.neutral500,
                          ),
                    ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  Color _parseColor(String hex) {
    hex = hex.replaceAll('#', '');
    if (hex.length == 6) hex = 'FF$hex';
    return Color(int.parse(hex, radix: 16));
  }
}

class _StatBadge extends StatelessWidget {
  final IconData icon;
  final String label;
  final Color color;

  const _StatBadge({
    required this.icon,
    required this.label,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Icon(icon, size: 14, color: color),
        const SizedBox(width: 4),
        Text(label, style: TextStyle(fontSize: 12, color: color)),
      ],
    );
  }
}
