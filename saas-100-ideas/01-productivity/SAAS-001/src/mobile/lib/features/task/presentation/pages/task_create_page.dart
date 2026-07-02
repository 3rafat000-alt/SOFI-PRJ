import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';
import '../../../../core/constants/app_colors.dart';
import '../../../../core/constants/app_dimensions.dart';
import '../../../../core/localization/app_localizations.dart';
import '../bloc/task_bloc.dart';
import '../widgets/task_form.dart';

class TaskCreatePage extends StatelessWidget {
  const TaskCreatePage({super.key});

  @override
  Widget build(BuildContext context) {
    final localizations = AppLocalizations.of(context);

    return BlocListener<TaskBloc, TaskState>(
      listener: (context, state) {
        if (state is TaskCreated) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Task created'),
              backgroundColor: AppColors.success,
            ),
          );
          context.pop();
        }
        if (state is TaskError) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(state.message),
              backgroundColor: AppColors.error,
            ),
          );
        }
      },
      child: Scaffold(
        appBar: AppBar(
          title: Text(localizations.createTask),
        ),
        body: const SingleChildScrollView(
          padding: EdgeInsets.all(AppDimensions.spacing24),
          child: TaskForm(),
        ),
      ),
    );
  }
}
