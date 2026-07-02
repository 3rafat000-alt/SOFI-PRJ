import 'package:flutter/material.dart';
import '../../core/constants/app_colors.dart';
import '../../core/localization/app_localizations.dart';

class AppScaffold extends StatelessWidget {
  final Widget child;
  final int _currentIndex;

  AppScaffold({super.key, required this.child})
      : _currentIndex = _computeIndex(child);

  static int _computeIndex(Widget child) {
    // In a real app this would use child's route info.
    // Default to dashboard (index 0).
    return 0;
  }

  @override
  Widget build(BuildContext context) {
    final localizations = AppLocalizations.of(context);

    return Scaffold(
      appBar: AppBar(
        title: Text(localizations.appName),
        actions: [
          IconButton(
            icon: const Icon(Icons.notifications_outlined),
            onPressed: () => Navigator.of(context).pushNamed('/notifications'),
          ),
        ],
      ),
      body: child,
      bottomNavigationBar: BottomNavigationBar(
        currentIndex: _currentIndex,
        onTap: (index) {
          final routes = ['/', '/tasks', '/time-tracking', '/reports', '/workspaces'];
          if (index < routes.length) {
            Navigator.of(context).pushReplacementNamed(routes[index]);
          }
        },
        type: BottomNavigationBarType.fixed,
        selectedItemColor: AppColors.primary,
        unselectedItemColor: AppColors.neutral400,
        items: [
          BottomNavigationBarItem(
            icon: const Icon(Icons.dashboard_outlined),
            activeIcon: const Icon(Icons.dashboard),
            label: localizations.dashboard,
          ),
          BottomNavigationBarItem(
            icon: const Icon(Icons.task_outlined),
            activeIcon: const Icon(Icons.task),
            label: localizations.tasks,
          ),
          BottomNavigationBarItem(
            icon: const Icon(Icons.timer_outlined),
            activeIcon: const Icon(Icons.timer),
            label: localizations.timeTracking,
          ),
          BottomNavigationBarItem(
            icon: const Icon(Icons.bar_chart_outlined),
            activeIcon: const Icon(Icons.bar_chart),
            label: localizations.reports,
          ),
          BottomNavigationBarItem(
            icon: const Icon(Icons.workspaces_outlined),
            activeIcon: const Icon(Icons.workspaces),
            label: localizations.workspaces,
          ),
        ],
      ),
    );
  }
}
