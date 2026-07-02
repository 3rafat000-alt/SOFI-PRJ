import 'package:flutter/material.dart';
import '../../../../core/constants/app_colors.dart';
import '../../../../core/constants/app_dimensions.dart';

class StatusBadge extends StatelessWidget {
  final String status;

  const StatusBadge({super.key, required this.status});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(
        horizontal: AppDimensions.spacing8,
        vertical: 2,
      ),
      decoration: BoxDecoration(
        color: _bgColor,
        borderRadius: BorderRadius.circular(AppDimensions.radiusBadge),
      ),
      child: Text(
        _label,
        style: TextStyle(
          fontSize: 11,
          fontWeight: FontWeight.w600,
          color: _textColor,
        ),
      ),
    );
  }

  Color get _bgColor {
    switch (status) {
      case 'todo':
        return AppColors.neutral100;
      case 'in_progress':
        return AppColors.infoLight;
      case 'done':
        return AppColors.successLight;
      default:
        return AppColors.neutral100;
    }
  }

  Color get _textColor {
    switch (status) {
      case 'todo':
        return AppColors.neutral600;
      case 'in_progress':
        return AppColors.info;
      case 'done':
        return AppColors.success;
      default:
        return AppColors.neutral500;
    }
  }

  String get _label {
    switch (status) {
      case 'todo':
        return 'To Do';
      case 'in_progress':
        return 'In Progress';
      case 'done':
        return 'Done';
      default:
        return status;
    }
  }
}
