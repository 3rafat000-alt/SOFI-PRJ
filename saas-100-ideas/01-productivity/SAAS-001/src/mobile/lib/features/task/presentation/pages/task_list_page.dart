import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';
import '../../../../core/constants/app_colors.dart';
import '../../../../core/constants/app_dimensions.dart';
import '../../../../core/localization/app_localizations.dart';
import '../../../../shared/widgets/loading_indicator.dart';
import '../../../../shared/widgets/error_view.dart';
import '../../../../shared/widgets/empty_state_view.dart';
import '../../../../shared/widgets/search_bar.dart' as app;
import '../bloc/task_bloc.dart';
import '../widgets/task_card.dart';

class TaskListPage extends StatefulWidget {
  const TaskListPage({super.key});

  @override
  State<TaskListPage> createState() => _TaskListPageState();
}

class _TaskListPageState extends State<TaskListPage> {
  String _searchQuery = '';
  String _statusFilter = 'all';

  @override
  void initState() {
    super.initState();
    _loadTasks();
  }

  void _loadTasks() {
    context.read<TaskBloc>().add(const LoadTasksEvent(
          workspaceId: 'default',
          status: 'all',
        ));
  }

  @override
  Widget build(BuildContext context) {
    final localizations = AppLocalizations.of(context);
    final isArabic = localizations.isArabic;

    return Scaffold(
      appBar: AppBar(
        title: Text(localizations.tasks),
        actions: [
          IconButton(
            icon: const Icon(Icons.add),
            onPressed: () => context.push('/tasks/create'),
          ),
        ],
      ),
      body: Column(
        children: [
          // Search bar
          Padding(
            padding: const EdgeInsets.fromLTRB(
              AppDimensions.spacing16,
              AppDimensions.spacing8,
              AppDimensions.spacing16,
              AppDimensions.spacing4,
            ),
            child: app.TaskSyncSearchBar(
              hintText: localizations.search,
              onChanged: (q) => setState(() => _searchQuery = q),
            ),
          ),

          // Status filter chips
          SizedBox(
            height: 40,
            child: ListView(
              scrollDirection: Axis.horizontal,
              padding: const EdgeInsets.symmetric(horizontal: AppDimensions.spacing16),
              children: ['all', 'todo', 'in_progress', 'done'].map((status) {
                final isSelected = _statusFilter == status;
                return Padding(
                  padding: const EdgeInsets.only(right: 8),
                  child: FilterChip(
                    label: Text(_statusLabel(status, localizations)),
                    selected: isSelected,
                    onSelected: (_) => setState(() => _statusFilter = status),
                    selectedColor: AppColors.primary.withValues(alpha: 0.1),
                    checkmarkColor: AppColors.primary,
                  ),
                );
              }).toList(),
            ),
          ),
          const SizedBox(height: AppDimensions.spacing4),

          // Task list
          Expanded(
            child: BlocBuilder<TaskBloc, TaskState>(
              builder: (context, state) {
                if (state is TaskLoading) {
                  return const LoadingIndicator();
                }
                if (state is TaskError) {
                  return ErrorView(
                    message: state.message,
                    onRetry: _loadTasks,
                  );
                }
                if (state is TasksLoaded) {
                  var tasks = state.tasks;
                  // Apply filters
                  if (_statusFilter != 'all') {
                    tasks = tasks.where((t) => t.status == _statusFilter).toList();
                  }
                  if (_searchQuery.isNotEmpty) {
                    tasks = tasks
                        .where((t) =>
                            t.title.toLowerCase().contains(_searchQuery.toLowerCase()))
                        .toList();
                  }
                  if (tasks.isEmpty) {
                    return EmptyStateView(
                      message: localizations.noTasks,
                      actionLabel: localizations.createTask,
                      onAction: () => context.push('/tasks/create'),
                    );
                  }
                  return RefreshIndicator(
                    onRefresh: () async => _loadTasks(),
                    child: ListView.builder(
                      padding: const EdgeInsets.all(AppDimensions.spacing16),
                      itemCount: tasks.length,
                      itemBuilder: (context, index) {
                        final task = tasks[index];
                        return TaskCard(
                          task: task,
                          onTap: () => context.push('/tasks/${task.id}'),
                        );
                      },
                    ),
                  );
                }
                return const SizedBox.shrink();
              },
            ),
          ),
        ],
      ),
      floatingActionButton: FloatingActionButton(
        onPressed: () => context.push('/tasks/create'),
        child: const Icon(Icons.add),
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
        return 'All';
    }
  }
}
