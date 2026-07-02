import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';
import '../../../../core/constants/app_colors.dart';
import '../../../../core/constants/app_dimensions.dart';
import '../../../../core/localization/app_localizations.dart';
import '../bloc/workspace_bloc.dart';

class WorkspaceCreatePage extends StatefulWidget {
  const WorkspaceCreatePage({super.key});

  @override
  State<WorkspaceCreatePage> createState() => _WorkspaceCreatePageState();
}

class _WorkspaceCreatePageState extends State<WorkspaceCreatePage> {
  final _formKey = GlobalKey<FormState>();
  final _nameController = TextEditingController();
  final _descriptionController = TextEditingController();
  bool _isLoading = false;

  @override
  void dispose() {
    _nameController.dispose();
    _descriptionController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final localizations = AppLocalizations.of(context);
    final isArabic = localizations.isArabic;

    return BlocListener<WorkspaceBloc, WorkspaceState>(
      listener: (context, state) {
        setState(() => _isLoading = state is WorkspaceLoading);
        if (state is WorkspaceCreated) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(isArabic ? 'تم إنشاء مساحة العمل' : 'Workspace created'),
              backgroundColor: AppColors.success,
            ),
          );
          context.pop();
        }
        if (state is WorkspaceError) {
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
          title: Text(localizations.createWorkspace),
        ),
        body: SingleChildScrollView(
          padding: const EdgeInsets.all(AppDimensions.spacing24),
          child: Form(
            key: _formKey,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Text(
                  localizations.createWorkspace,
                  style: Theme.of(context).textTheme.headlineLarge,
                ),
                const SizedBox(height: AppDimensions.spacing32),
                TextFormField(
                  controller: _nameController,
                  decoration: InputDecoration(
                    labelText: localizations.workspaceName,
                    hintText: localizations.workspaceNameHint,
                    prefixIcon: const Icon(Icons.workspaces_outlined),
                  ),
                  textDirection: isArabic ? TextDirection.rtl : TextDirection.ltr,
                  validator: (v) {
                    if (v == null || v.trim().isEmpty) {
                      return isArabic ? 'اسم مساحة العمل مطلوب' : 'Workspace name is required';
                    }
                    return null;
                  },
                ),
                const SizedBox(height: AppDimensions.spacing16),
                TextFormField(
                  controller: _descriptionController,
                  maxLines: 3,
                  decoration: InputDecoration(
                    labelText: localizations.workspaceDescription,
                    prefixIcon: const Padding(
                      padding: EdgeInsets.only(bottom: 48),
                      child: Icon(Icons.description_outlined),
                    ),
                  ),
                  textDirection: isArabic ? TextDirection.rtl : TextDirection.ltr,
                ),
                const SizedBox(height: AppDimensions.spacing32),
                SizedBox(
                  height: AppDimensions.buttonHeightLarge,
                  child: ElevatedButton(
                    onPressed: _isLoading ? null : _submit,
                    child: _isLoading
                        ? const SizedBox(
                            width: 20,
                            height: 20,
                            child: CircularProgressIndicator(
                              strokeWidth: 2,
                              color: Colors.white,
                            ),
                          )
                        : Text(localizations.create),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  void _submit() {
    if (!_formKey.currentState!.validate()) return;
    context.read<WorkspaceBloc>().add(CreateWorkspaceEvent(
          name: _nameController.text.trim(),
          description: _descriptionController.text.trim().isEmpty
              ? null
              : _descriptionController.text.trim(),
        ));
  }
}
