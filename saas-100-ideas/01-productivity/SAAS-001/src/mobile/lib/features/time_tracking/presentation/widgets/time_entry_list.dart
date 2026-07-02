import 'package:flutter/material.dart';
import '../../../../core/constants/app_colors.dart';
import '../../../../core/constants/app_dimensions.dart';
import '../../../../core/localization/app_localizations.dart';
import '../../domain/entities/time_entry.dart';

class TimeEntryList extends StatelessWidget {
  final List<TimeEntry> entries;

  const TimeEntryList({super.key, required this.entries});

  @override
  Widget build(BuildContext context) {
    final localizations = AppLocalizations.of(context);

    if (entries.isEmpty) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(AppDimensions.spacing24),
          child: Column(
            children: [
              Icon(
                Icons.timer_off_outlined,
                size: 48,
                color: AppColors.neutral300,
              ),
              const SizedBox(height: AppDimensions.spacing12),
              Text(
                localizations.noTimeEntries,
                style: Theme.of(context).textTheme.bodyMedium,
              ),
            ],
          ),
        ),
      );
    }

    return ListView.separated(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      itemCount: entries.length,
      separatorBuilder: (_, __) => const Divider(height: 1),
      itemBuilder: (context, index) {
        final entry = entries[index];
        final hours = (entry.durationMinutes ?? 0) ~/ 60;
        final mins = (entry.durationMinutes ?? 0) % 60;

        return ListTile(
          leading: CircleAvatar(
            radius: 18,
            backgroundColor: entry.isRunning
                ? AppColors.successLight
                : AppColors.neutral100,
            child: Icon(
              entry.isRunning ? Icons.play_circle_filled : Icons.check_circle_outline,
              color: entry.isRunning ? AppColors.success : AppColors.neutral400,
              size: 20,
            ),
          ),
          title: Text(
            entry.taskTitle.isNotEmpty ? entry.taskTitle : 'Task',
            style: const TextStyle(
              fontWeight: FontWeight.w500,
              fontSize: 14,
            ),
          ),
          subtitle: Text(
            entry.projectName.isNotEmpty ? entry.projectName : 'No project',
            style: const TextStyle(fontSize: 12),
          ),
          trailing: Text(
            entry.isRunning
                ? localizations.inProgress
                : '${hours}h ${mins}m',
            style: TextStyle(
              fontSize: 13,
              fontWeight: FontWeight.w600,
              color: entry.isRunning ? AppColors.success : AppColors.textPrimary,
            ),
          ),
          dense: true,
        );
      },
    );
  }
}
