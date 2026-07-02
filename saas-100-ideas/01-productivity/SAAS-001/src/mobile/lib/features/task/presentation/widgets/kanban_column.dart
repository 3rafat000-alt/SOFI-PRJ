import 'package:flutter/material.dart';
import '../../../../core/constants/app_colors.dart';
import '../../../../core/constants/app_dimensions.dart';
import '../../domain/entities/task.dart';
import 'task_card.dart';

class KanbanColumn extends StatelessWidget {
  final String title;
  final Color color;
  final List<Task> tasks;
  final void Function(Task task) onTaskTap;
  final void Function(String taskId) onTaskDropped;

  const KanbanColumn({
    super.key,
    required this.title,
    required this.color,
    required this.tasks,
    required this.onTaskTap,
    required this.onTaskDropped,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      width: 300,
      margin: const EdgeInsets.all(AppDimensions.spacing8),
      child: Column(
        children: [
          // Column header
          Container(
            padding: const EdgeInsets.symmetric(
              horizontal: AppDimensions.spacing16,
              vertical: AppDimensions.spacing12,
            ),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: const BorderRadius.vertical(
                top: Radius.circular(AppDimensions.radiusCard),
              ),
              border: Border(
                top: BorderSide(color: color, width: 3),
              ),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withValues(alpha: 0.04),
                  blurRadius: 4,
                  offset: const Offset(0, 1),
                ),
              ],
            ),
            child: Row(
              children: [
                Container(
                  width: 8,
                  height: 8,
                  decoration: BoxDecoration(
                    color: color,
                    shape: BoxShape.circle,
                  ),
                ),
                const SizedBox(width: AppDimensions.spacing8),
                Expanded(
                  child: Text(
                    title,
                    style: const TextStyle(
                      fontWeight: FontWeight.w600,
                      fontSize: 14,
                    ),
                  ),
                ),
                // Task count
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 8,
                    vertical: 2,
                  ),
                  decoration: BoxDecoration(
                    color: AppColors.neutral100,
                    borderRadius: BorderRadius.circular(AppDimensions.radiusPill),
                  ),
                  child: Text(
                    '${tasks.length}',
                    style: TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w600,
                      color: AppColors.neutral600,
                    ),
                  ),
                ),
              ],
            ),
          ),

          // Task list (scrollable)
          Expanded(
            child: Container(
              decoration: BoxDecoration(
                color: AppColors.neutral100,
                borderRadius: const BorderRadius.vertical(
                  bottom: Radius.circular(AppDimensions.radiusCard),
                ),
              ),
              child: tasks.isEmpty
                  ? Center(
                      child: Padding(
                        padding: const EdgeInsets.all(24),
                        child: Icon(
                          Icons.inbox_outlined,
                          size: 40,
                          color: AppColors.neutral300,
                        ),
                      ),
                    )
                  : ListView.builder(
                      padding: const EdgeInsets.all(AppDimensions.spacing8),
                      itemCount: tasks.length,
                      itemBuilder: (context, index) {
                        final task = tasks[index];
                        return DragTarget<String>(
                          onAcceptWithDetails: (details) =>
                              onTaskDropped(task.id),
                          builder: (context, candidateData, rejectedData) {
                            return LongPressDraggable<String>(
                              data: task.id,
                              feedback: Material(
                                elevation: 4,
                                borderRadius: BorderRadius.circular(
                                    AppDimensions.radiusCard),
                                child: SizedBox(
                                  width: 280,
                                  child: Opacity(
                                    opacity: 0.8,
                                    child: TaskCard(
                                      task: task,
                                      onTap: () => onTaskTap(task),
                                      isDragging: true,
                                    ),
                                  ),
                                ),
                              ),
                              childWhenDragging: Opacity(
                                opacity: 0.3,
                                child: TaskCard(
                                  task: task,
                                  onTap: () => onTaskTap(task),
                                ),
                              ),
                              child: TaskCard(
                                task: task,
                                onTap: () => onTaskTap(task),
                              ),
                            );
                          },
                        );
                      },
                    ),
            ),
          ),
        ],
      ),
    );
  }
}
