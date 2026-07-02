import 'package:flutter/material.dart';
import '../../../../core/constants/app_colors.dart';
import '../../../../core/constants/app_dimensions.dart';

class MemberTile extends StatelessWidget {
  final String name;
  final String email;
  final String? avatarUrl;
  final String role;
  final int taskCount;
  final VoidCallback? onRemove;

  const MemberTile({
    super.key,
    required this.name,
    required this.email,
    this.avatarUrl,
    required this.role,
    this.taskCount = 0,
    this.onRemove,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: AppDimensions.spacing8),
      child: ListTile(
        leading: CircleAvatar(
          radius: 20,
          backgroundColor: AppColors.primary.withValues(alpha: 0.1),
          child: Text(
            name.isNotEmpty ? name[0].toUpperCase() : '?',
            style: TextStyle(
              color: AppColors.primary,
              fontWeight: FontWeight.w600,
            ),
          ),
        ),
        title: Text(
          name,
          style: const TextStyle(fontWeight: FontWeight.w500),
        ),
        subtitle: Text(email),
        trailing: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            // Role badge
            Container(
              padding: const EdgeInsets.symmetric(
                horizontal: 8,
                vertical: 2,
              ),
              decoration: BoxDecoration(
                color: _roleColor.withValues(alpha: 0.1),
                borderRadius: BorderRadius.circular(AppDimensions.radiusBadge),
              ),
              child: Text(
                role.toUpperCase(),
                style: TextStyle(
                  fontSize: 10,
                  fontWeight: FontWeight.w600,
                  color: _roleColor,
                ),
              ),
            ),
            if (onRemove != null) ...[
              const SizedBox(width: AppDimensions.spacing8),
              IconButton(
                icon: const Icon(Icons.remove_circle_outline,
                    color: AppColors.error, size: 20),
                onPressed: onRemove,
              ),
            ],
          ],
        ),
      ),
    );
  }

  Color get _roleColor {
    switch (role) {
      case 'owner':
        return AppColors.primary;
      case 'admin':
        return AppColors.secondary;
      default:
        return AppColors.neutral500;
    }
  }
}
