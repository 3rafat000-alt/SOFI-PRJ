import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';
import '../../../../core/constants/app_colors.dart';
import '../../../../core/constants/app_dimensions.dart';
import '../../../../core/localization/app_localizations.dart';
import '../../../../shared/widgets/loading_indicator.dart';
import '../../../../shared/widgets/error_view.dart';
import '../../../../shared/widgets/empty_state_view.dart';
import '../bloc/task_bloc.dart';
import '../widgets/kanban_column.dart';
import '../widgets/task_card.dart';

class KanbanBoardPage extends StatefulWidget {
  final String projectId;

  const KanbanBoardPage({super.key, required this.projectId});

  @override
  State<KanbanBoardPage> createState() => _KanbanBoardPageState();
}

class _KanbanBoardPageState extends State<KanbanBoardPage> {
  @override
  void initState() {
    super.initState();
    context.read<TaskBloc>().add(LoadTasksEvent(
          workspaceId: 'default',
          projectId: widget.projectId,
        ));
  }

  @override
  Widget build(BuildContext context) {
    final localizations = AppLocalizations.of(context);

    return Scaffold(
      appBar: AppBar(
        title: Text(localizations.kanban),
        actions: [
          IconButton(
            icon: const Icon(Icons.add),
            onPressed: () => context.push('/tasks/create'),
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
              onRetry: () => context.read<TaskBloc>().add(LoadTasksEvent(
                    workspaceId: 'default',
                    projectId: widget.projectId,
                  )),
            );
          }
          if (state is TasksLoaded) {
            final tasks = state.tasks;
            if (tasks.isEmpty) {
              return EmptyStateView(
                message: localizations.noTasks,
                actionLabel: localizations.createTask,
                onAction: () => context.push('/tasks/create'),
              );
            }

            final todoTasks = tasks.where((t) => t.status == 'todo').toList();
            final inProgressTasks = tasks.where((t) => t.status == 'in_progress').toList();
            final doneTasks = tasks.where((t) => t.status == 'done').toList();

            return SingleChildScrollView(
              scrollDirection: Axis.horizontal,
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  KanbanColumn(
                    title: localizations.todoColumn,
                    color: AppColors.statusTodo,
                    tasks: todoTasks,
                    onTaskTap: (task) => context.push('/tasks/${task.id}'),
                    onTaskDropped: (taskId) {
                      context.read<TaskBloc>().add(
                            QuickStatusChangeEvent(
                              taskId: taskId,
                              status: 'todo',
                            ),
                          );
                    },
                  ),
                  KanbanColumn(
                    title: localizations.inProgressColumn,
                    color: AppColors.statusInProgress,
                    tasks: inProgressTasks,
                    onTaskTap: (task) => context.push('/tasks/${task.id}'),
                    onTaskDropped: (taskId) {
                      context.read<TaskBloc>().add(
                            QuickStatusChangeEvent(
                              taskId: taskId,
                              status: 'in_progress',
                            ),
                          );
                    },
                  ),
                  KanbanColumn(
                    title: localizations.doneColumn,
                    color: AppColors.statusDone,
                    tasks: doneTasks,
                    onTaskTap: (task) => context.push('/tasks/${task.id}'),
                    onTaskDropped: (taskId) {
                      context.read<TaskBloc>().add(
                            QuickStatusChangeEvent(
                              taskId: taskId,
                              status: 'done',
                            ),
                          );
                    },
                  ),
                ],
              ),
            );
          }
          return const LoadingIndicator();
        },
      ),
      floatingActionButton: FloatingActionButton(
        onPressed: () => context.push('/tasks/create'),
        child: const Icon(Icons.add),
      ),
    );
  }
}
