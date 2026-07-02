import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';
import '../../../../core/constants/app_colors.dart';
import '../../../../core/constants/app_dimensions.dart';
import '../../../../core/localization/app_localizations.dart';
import '../bloc/project_bloc.dart';

class ProjectCreatePage extends StatefulWidget {
  const ProjectCreatePage({super.key});

  @override
  State<ProjectCreatePage> createState() => _ProjectCreatePageState();
}

class _ProjectCreatePageState extends State<ProjectCreatePage> {
  final _formKey = GlobalKey<FormState>();
  final _nameController = TextEditingController();
  final _descriptionController = TextEditingController();
  DateTime? _startDate;
  DateTime? _endDate;
  String _selectedColor = '#4F46E5';
  bool _isLoading = false;

  final List<String> _colors = [
    '#4F46E5', '#2563EB', '#7C3AED', '#EF4444',
    '#F59E0B', '#10B981', '#3B82F6', '#EC4899',
  ];

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

    return BlocListener<ProjectBloc, ProjectState>(
      listener: (context, state) {
        setState(() => _isLoading = state is ProjectLoading);
        if (state is ProjectCreated) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Project created'),
              backgroundColor: AppColors.success,
            ),
          );
          context.pop();
        }
        if (state is ProjectError) {
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
          title: Text(localizations.createProject),
        ),
        body: SingleChildScrollView(
          padding: const EdgeInsets.all(AppDimensions.spacing24),
          child: Form(
            key: _formKey,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                TextFormField(
                  controller: _nameController,
                  decoration: InputDecoration(
                    labelText: localizations.projectName,
                    prefixIcon: const Icon(Icons.folder_outlined),
                  ),
                  textDirection: isArabic ? TextDirection.rtl : TextDirection.ltr,
                  validator: (v) {
                    if (v == null || v.trim().isEmpty) {
                      return isArabic ? 'اسم المشروع مطلوب' : 'Project name is required';
                    }
                    return null;
                  },
                ),
                const SizedBox(height: AppDimensions.spacing16),
                TextFormField(
                  controller: _descriptionController,
                  maxLines: 3,
                  decoration: InputDecoration(
                    labelText: localizations.projectDescription,
                    prefixIcon: const Padding(
                      padding: EdgeInsets.only(bottom: 48),
                      child: Icon(Icons.description_outlined),
                    ),
                  ),
                  textDirection: isArabic ? TextDirection.rtl : TextDirection.ltr,
                ),
                const SizedBox(height: AppDimensions.spacing16),

                // Color picker
                Text(localizations.projectColor,
                    style: Theme.of(context).textTheme.labelLarge),
                const SizedBox(height: AppDimensions.spacing8),
                Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: _colors.map((color) {
                    final isSelected = _selectedColor == color;
                    return GestureDetector(
                      onTap: () => setState(() => _selectedColor = color),
                      child: Container(
                        width: 36,
                        height: 36,
                        decoration: BoxDecoration(
                          color: _parseColor(color),
                          borderRadius: BorderRadius.circular(8),
                          border: isSelected
                              ? Border.all(color: AppColors.primary, width: 3)
                              : null,
                        ),
                        child: isSelected
                            ? const Icon(Icons.check, color: Colors.white, size: 18)
                            : null,
                      ),
                    );
                  }).toList(),
                ),
                const SizedBox(height: AppDimensions.spacing16),

                // Date fields
                _DateField(
                  label: localizations.startDate,
                  value: _startDate,
                  onPicked: (d) => setState(() => _startDate = d),
                  isArabic: isArabic,
                ),
                const SizedBox(height: AppDimensions.spacing12),
                _DateField(
                  label: localizations.endDate,
                  value: _endDate,
                  onPicked: (d) => setState(() => _endDate = d),
                  isArabic: isArabic,
                ),
                const SizedBox(height: AppDimensions.spacing32),

                SizedBox(
                  height: AppDimensions.buttonHeightLarge,
                  child: ElevatedButton(
                    onPressed: _isLoading ? null : _submit,
                    child: _isLoading
                        ? const SizedBox(
                            width: 20, height: 20,
                            child: CircularProgressIndicator(
                                strokeWidth: 2, color: Colors.white),
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
    context.read<ProjectBloc>().add(CreateProjectEvent(
          workspaceId: 'default',
          name: _nameController.text.trim(),
          description: _descriptionController.text.trim().isEmpty
              ? null
              : _descriptionController.text.trim(),
          color: _selectedColor,
          startDate: _startDate,
          endDate: _endDate,
        ));
  }

  Color _parseColor(String hex) {
    hex = hex.replaceAll('#', '');
    if (hex.length == 6) hex = 'FF$hex';
    return Color(int.parse(hex, radix: 16));
  }
}

class _DateField extends StatelessWidget {
  final String label;
  final DateTime? value;
  final ValueChanged<DateTime> onPicked;
  final bool isArabic;

  const _DateField({
    required this.label,
    required this.value,
    required this.onPicked,
    required this.isArabic,
  });

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: () async {
        final picked = await showDatePicker(
          context: context,
          initialDate: value ?? DateTime.now(),
          firstDate: DateTime.now().subtract(const Duration(days: 365)),
          lastDate: DateTime.now().add(const Duration(days: 365 * 2)),
        );
        if (picked != null) onPicked(picked);
      },
      child: InputDecorator(
        decoration: InputDecoration(
          labelText: label,
          prefixIcon: const Icon(Icons.calendar_today_outlined),
        ),
        child: Text(
          value != null
              ? '${value!.year}/${value!.month}/${value!.day}'
              : isArabic ? 'اختر تاريخ' : 'Pick date',
        ),
      ),
    );
  }
}
