import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';
import '../../../../core/constants/app_colors.dart';
import '../../../../core/constants/app_dimensions.dart';
import '../../../../core/localization/app_localizations.dart';
import '../../../../shared/widgets/loading_indicator.dart';
import '../../../../shared/widgets/error_view.dart';
import '../../../../shared/widgets/empty_state_view.dart';
import '../bloc/project_bloc.dart';
import '../widgets/project_card.dart';

class ProjectListPage extends StatelessWidget {
  const ProjectListPage({super.key});

  @override
  Widget build(BuildContext context) {
    final localizations = AppLocalizations.of(context);

    return Scaffold(
      appBar: AppBar(
        title: Text(localizations.projects),
        actions: [
          IconButton(
            icon: const Icon(Icons.add),
            onPressed: () => context.push('/projects/create'),
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
              onRetry: () => _loadProjects(context),
            );
          }
          if (state is ProjectsLoaded) {
            if (state.projects.isEmpty) {
              return EmptyStateView(
                message: localizations.noProjects,
                actionLabel: localizations.createProject,
                onAction: () => context.push('/projects/create'),
              );
            }
            return RefreshIndicator(
              onRefresh: () async => _loadProjects(context),
              child: ListView.builder(
                padding: const EdgeInsets.all(AppDimensions.spacing16),
                itemCount: state.projects.length,
                itemBuilder: (context, index) {
                  final project = state.projects[index];
                  return ProjectCard(
                    project: project,
                    onTap: () => context.push('/projects/${project.id}'),
                    onBoardTap: () => context.push('/projects/${project.id}/board'),
                  );
                },
              ),
            );
          }
          // Initial - load
          WidgetsBinding.instance.addPostFrameCallback((_) => _loadProjects(context));
          return const LoadingIndicator();
        },
      ),
      floatingActionButton: FloatingActionButton(
        onPressed: () => context.push('/projects/create'),
        child: const Icon(Icons.add),
      ),
    );
  }

  void _loadProjects(BuildContext context) {
    // Use workspace from secure storage or bloc
    context.read<ProjectBloc>().add(const LoadProjectsEvent(
          workspaceId: 'default',
          status: 'active',
        ));
  }
}
