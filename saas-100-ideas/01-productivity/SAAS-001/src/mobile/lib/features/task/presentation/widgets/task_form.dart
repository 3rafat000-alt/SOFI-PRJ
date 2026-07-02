import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../../../core/constants/app_colors.dart';
import '../../../../core/constants/app_dimensions.dart';
import '../../../../core/localization/app_localizations.dart';
import '../bloc/task_bloc.dart';

class TaskForm extends StatefulWidget {
  const TaskForm({super.key});

  @override
  State<TaskForm> createState() => _TaskFormState();
}

class _TaskFormState extends State<TaskForm> {
  final _formKey = GlobalKey<FormState>();
  final _titleController = TextEditingController();
  final _descriptionController = TextEditingController();
  String _selectedPriority = 'medium';
  DateTime? _dueDate;
  int _estimatedMinutes = 0;
  bool _isLoading = false;

  @override
  void dispose() {
    _titleController.dispose();
    _descriptionController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final localizations = AppLocalizations.of(context);
    final isArabic = localizations.isArabic;

    return Form(
      key: _formKey,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          // Title
          TextFormField(
            controller: _titleController,
            decoration: InputDecoration(
              labelText: localizations.taskTitle,
              prefixIcon: const Icon(Icons.task_alt),
            ),
            textDirection: isArabic ? TextDirection.rtl : TextDirection.ltr,
            validator: (v) {
              if (v == null || v.trim().isEmpty) {
                return isArabic ? 'عنوان المهمة مطلوب' : 'Title is required';
              }
              return null;
            },
          ),
          const SizedBox(height: AppDimensions.spacing16),

          // Description
          TextFormField(
            controller: _descriptionController,
            maxLines: 4,
            decoration: InputDecoration(
              labelText: localizations.taskDescription,
              prefixIcon: const Padding(
                padding: EdgeInsets.only(bottom: 64),
                child: Icon(Icons.description_outlined),
              ),
            ),
            textDirection: isArabic ? TextDirection.rtl : TextDirection.ltr,
          ),
          const SizedBox(height: AppDimensions.spacing16),

          // Priority selector
          Text(localizations.priority,
              style: Theme.of(context).textTheme.labelLarge),
          const SizedBox(height: AppDimensions.spacing8),
          Row(
            children: ['urgent', 'high', 'medium', 'low'].map((p) {
              final isSelected = _selectedPriority == p;
              return Padding(
                padding: const EdgeInsets.only(right: 8),
                child: ChoiceChip(
                  label: Text(_priorityLabel(p, localizations)),
                  selected: isSelected,
                  onSelected: (_) => setState(() => _selectedPriority = p),
                  selectedColor: _priorityColor(p).withValues(alpha: 0.2),
                  labelStyle: TextStyle(
                    color: isSelected ? _priorityColor(p) : null,
                  ),
                ),
              );
            }).toList(),
          ),
          const SizedBox(height: AppDimensions.spacing16),

          // Due date
          InkWell(
            onTap: _pickDate,
            child: InputDecorator(
              decoration: InputDecoration(
                labelText: localizations.dueDate,
                prefixIcon: const Icon(Icons.calendar_today_outlined),
              ),
              child: Text(
                _dueDate != null
                    ? '${_dueDate!.year}/${_dueDate!.month}/${_dueDate!.day}'
                    : (isArabic ? 'اختر تاريخ' : 'Pick date'),
              ),
            ),
          ),
          const SizedBox(height: AppDimensions.spacing16),

          // Estimated hours
          TextFormField(
            decoration: InputDecoration(
              labelText: localizations.estimatedHours,
              prefixIcon: const Icon(Icons.timer_outlined),
            ),
            keyboardType: TextInputType.number,
            onChanged: (v) {
              _estimatedMinutes = (int.tryParse(v) ?? 0) * 60;
            },
          ),
          const SizedBox(height: AppDimensions.spacing32),

          // Submit
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
    );
  }

  void _submit() {
    if (!_formKey.currentState!.validate()) return;
    context.read<TaskBloc>().add(CreateTaskEvent(
          projectId: 'default',
          title: _titleController.text.trim(),
          description: _descriptionController.text.trim().isEmpty
              ? null
              : _descriptionController.text.trim(),
          priority: _selectedPriority,
          dueDate: _dueDate,
          estimatedMinutes: _estimatedMinutes,
        ));
  }

  Future<void> _pickDate() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: _dueDate ?? DateTime.now().add(const Duration(days: 7)),
      firstDate: DateTime.now(),
      lastDate: DateTime.now().add(const Duration(days: 365)),
    );
    if (picked != null) setState(() => _dueDate = picked);
  }

  String _priorityLabel(String p, AppLocalizations l) {
    switch (p) {
      case 'urgent':
        return l.urgent;
      case 'high':
        return l.high;
      case 'medium':
        return l.medium;
      case 'low':
        return l.low;
      default:
        return p;
    }
  }

  Color _priorityColor(String p) {
    switch (p) {
      case 'urgent':
        return AppColors.priorityUrgent;
      case 'high':
        return AppColors.priorityHigh;
      case 'medium':
        return AppColors.priorityMedium;
      case 'low':
        return AppColors.priorityLow;
      default:
        return AppColors.neutral500;
    }
  }
}
