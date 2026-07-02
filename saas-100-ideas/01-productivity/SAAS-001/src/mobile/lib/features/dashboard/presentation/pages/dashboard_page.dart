import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';
import '../../../../core/constants/app_colors.dart';
import '../../../../core/constants/app_dimensions.dart';
import '../../../../core/localization/app_localizations.dart';
import '../../../../shared/widgets/loading_indicator.dart';
import '../../../../shared/widgets/error_view.dart';
import '../bloc/dashboard_bloc.dart';
import '../widgets/stats_card.dart';
import '../widgets/activity_feed.dart';

class DashboardPage extends StatelessWidget {
  const DashboardPage({super.key});

  @override
  Widget build(BuildContext context) {
    final localizations = AppLocalizations.of(context);

    return Scaffold(
      appBar: AppBar(
        title: Text(localizations.dashboard),
        actions: [
          IconButton(
            icon: const Icon(Icons.notifications_outlined),
            onPressed: () => context.push('/notifications'),
          ),
          IconButton(
            icon: const Icon(Icons.access_time),
            onPressed: () => context.push('/time-tracking'),
          ),
        ],
      ),
      body: BlocBuilder<DashboardBloc, DashboardState>(
        builder: (context, state) {
          if (state is DashboardLoading) {
            return const LoadingIndicator();
          }
          if (state is DashboardError) {
            return ErrorView(
              message: state.message,
              onRetry: () => context
                  .read<DashboardBloc>()
                  .add(const LoadDashboardEvent(workspaceId: 'default')),
            );
          }
          if (state is DashboardLoaded) {
            final stats = state.stats;
            return RefreshIndicator(
              onRefresh: () async {
                context.read<DashboardBloc>().add(
                      const LoadDashboardEvent(workspaceId: 'default'),
                    );
              },
              child: SingleChildScrollView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.all(AppDimensions.spacing16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Stats row
                    Text(
                      localizations.quickStats,
                      style: Theme.of(context).textTheme.titleMedium,
                    ),
                    const SizedBox(height: AppDimensions.spacing12),
                    Row(
                      children: [
                        Expanded(
                          child: StatsCard(
                            title: localizations.tasks,
                            value: '${stats.tasks.total}',
                            subtitle:
                                '${stats.tasks.done} ${localizations.done}',
                            icon: Icons.checklist,
                            color: AppColors.primary,
                          ),
                        ),
                        const SizedBox(width: AppDimensions.spacing12),
                        Expanded(
                          child: StatsCard(
                            title: localizations.inProgress,
                            value: '${stats.tasks.inProgress}',
                            subtitle: '${stats.tasks.overdue} ${localizations.overdue}',
                            icon: Icons.sync,
                            color: AppColors.warning,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: AppDimensions.spacing12),
                    Row(
                      children: [
                        Expanded(
                          child: StatsCard(
                            title: localizations.projects,
                            value: '${stats.projects.active}',
                            subtitle: localizations.activeProjects,
                            icon: Icons.folder_outlined,
                            color: AppColors.secondary,
                          ),
                        ),
                        const SizedBox(width: AppDimensions.spacing12),
                        Expanded(
                          child: StatsCard(
                            title: localizations.members,
                            value: '${stats.members.total}',
                            subtitle:
                                '${stats.members.activeToday} ${localizations.online}',
                            icon: Icons.people_outline,
                            color: AppColors.success,
                          ),
                        ),
                      ],
                    ),

                    const SizedBox(height: AppDimensions.spacing24),

                    // Activity feed
                    ActivityFeed(
                      activities: state.activity,
                      onSeeAll: () {},
                    ),

                    const SizedBox(height: AppDimensions.spacing24),

                    // Quick links
                    Text(
                      localizations.seeAll,
                      style: Theme.of(context).textTheme.titleMedium,
                    ),
                    const SizedBox(height: AppDimensions.spacing12),
                    _QuickLink(
                      icon: Icons.list_alt,
                      title: localizations.tasks,
                      onTap: () => context.push('/tasks'),
                    ),
                    _QuickLink(
                      icon: Icons.dashboard_outlined,
                      title: localizations.projects,
                      onTap: () => context.push('/projects'),
                    ),
                    _QuickLink(
                      icon: Icons.access_time,
                      title: localizations.timeTracking,
                      onTap: () => context.push('/time-tracking'),
                    ),
                    _QuickLink(
                      icon: Icons.bar_chart_outlined,
                      title: localizations.reports,
                      onTap: () => context.push('/time-reports'),
                    ),
                  ],
                ),
              ),
            );
          }
          // Initial load
          WidgetsBinding.instance.addPostFrameCallback((_) {
            context
                .read<DashboardBloc>()
                .add(const LoadDashboardEvent(workspaceId: 'default'));
          });
          return const LoadingIndicator();
        },
      ),
    );
  }
}

class _QuickLink extends StatelessWidget {
  final IconData icon;
  final String title;
  final VoidCallback onTap;

  const _QuickLink({
    required this.icon,
    required this.title,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: AppDimensions.spacing8),
      child: ListTile(
        leading: Icon(icon, color: AppColors.primary),
        title: Text(title),
        trailing: const Icon(Icons.chevron_right, color: AppColors.neutral400),
        onTap: onTap,
      ),
    );
  }
}
