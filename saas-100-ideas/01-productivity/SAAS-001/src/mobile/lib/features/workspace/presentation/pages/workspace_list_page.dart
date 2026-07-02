import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';
import '../../../../core/constants/app_colors.dart';
import '../../../../core/constants/app_dimensions.dart';
import '../../../../core/localization/app_localizations.dart';
import '../../../../shared/widgets/loading_indicator.dart';
import '../../../../shared/widgets/error_view.dart';
import '../../../../shared/widgets/empty_state_view.dart';
import '../bloc/workspace_bloc.dart';

class WorkspaceListPage extends StatelessWidget {
  const WorkspaceListPage({super.key});

  @override
  Widget build(BuildContext context) {
    final localizations = AppLocalizations.of(context);
    final isArabic = localizations.isArabic;

    return Scaffold(
      appBar: AppBar(
        title: Text(localizations.workspaces),
        actions: [
          IconButton(
            icon: const Icon(Icons.add),
            onPressed: () => context.push('/workspaces/create'),
          ),
        ],
      ),
      body: BlocBuilder<WorkspaceBloc, WorkspaceState>(
        builder: (context, state) {
          if (state is WorkspaceLoading) {
            return const LoadingIndicator();
          }
          if (state is WorkspaceError) {
            return ErrorView(
              message: state.message,
              onRetry: () => context.read<WorkspaceBloc>().add(LoadWorkspacesEvent()),
            );
          }
          if (state is WorkspacesLoaded) {
            if (state.workspaces.isEmpty) {
              return EmptyStateView(
                message: localizations.noData,
                actionLabel: localizations.createWorkspace,
                onAction: () => context.push('/workspaces/create'),
              );
            }
            return RefreshIndicator(
              onRefresh: () async {
                context.read<WorkspaceBloc>().add(LoadWorkspacesEvent());
              },
              child: ListView.builder(
                padding: const EdgeInsets.all(AppDimensions.spacing16),
                itemCount: state.workspaces.length,
                itemBuilder: (context, index) {
                  final workspace = state.workspaces[index];
                  return _WorkspaceCard(
                    workspace: workspace,
                    isArabic: isArabic,
                    onTap: () => context.push('/workspaces/${workspace.id}/members'),
                  );
                },
              ),
            );
          }
          return const SizedBox.shrink();
        },
      ),
      floatingActionButton: FloatingActionButton(
        onPressed: () => context.push('/workspaces/create'),
        child: const Icon(Icons.add),
      ),
    );
  }
}

class _WorkspaceCard extends StatelessWidget {
  final dynamic workspace;
  final bool isArabic;
  final VoidCallback onTap;

  const _WorkspaceCard({
    required this.workspace,
    required this.isArabic,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: AppDimensions.spacing12),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(AppDimensions.radiusCard),
        child: Padding(
          padding: const EdgeInsets.all(AppDimensions.spacing16),
          child: Row(
            children: [
              // Avatar
              Container(
                width: 48,
                height: 48,
                decoration: BoxDecoration(
                  gradient: AppColors.primaryGradient,
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Center(
                  child: Text(
                    workspace.name.isNotEmpty
                        ? workspace.name[0].toUpperCase()
                        : 'W',
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 20,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
              ),
              const SizedBox(width: AppDimensions.spacing16),
              // Details
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      workspace.name,
                      style: Theme.of(context).textTheme.titleMedium?.copyWith(
                            fontWeight: FontWeight.w600,
                          ),
                    ),
                    const SizedBox(height: AppDimensions.spacing4),
                    Text(
                      '${workspace.memberCount} members · ${workspace.projectCount} projects',
                      style: Theme.of(context).textTheme.bodySmall,
                    ),
                  ],
                ),
              ),
              // Plan badge
              Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 8,
                  vertical: 4,
                ),
                decoration: BoxDecoration(
                  color: workspace.plan == 'pro'
                      ? AppColors.primary.withValues(alpha: 0.1)
                      : AppColors.neutral100,
                  borderRadius: BorderRadius.circular(AppDimensions.radiusBadge),
                ),
                child: Text(
                  workspace.plan.toUpperCase(),
                  style: TextStyle(
                    fontSize: 10,
                    fontWeight: FontWeight.w600,
                    color: workspace.plan == 'pro'
                        ? AppColors.primary
                        : AppColors.neutral500,
                  ),
                ),
              ),
              const SizedBox(width: AppDimensions.spacing8),
              const Icon(Icons.chevron_right, color: AppColors.neutral400),
            ],
          ),
        ),
      ),
    );
  }
}
