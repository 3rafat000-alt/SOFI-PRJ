import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../storage/secure_storage.dart';
import '../../features/auth/presentation/pages/login_page.dart';
import '../../features/auth/presentation/pages/register_page.dart';
import '../../features/dashboard/presentation/pages/dashboard_page.dart';
import '../../features/workspace/presentation/pages/workspace_list_page.dart';
import '../../features/workspace/presentation/pages/workspace_create_page.dart';
import '../../features/workspace/presentation/pages/members_page.dart';
import '../../features/project/presentation/pages/project_list_page.dart';
import '../../features/project/presentation/pages/project_create_page.dart';
import '../../features/project/presentation/pages/project_detail_page.dart';
import '../../features/task/presentation/pages/task_list_page.dart';
import '../../features/task/presentation/pages/task_detail_page.dart';
import '../../features/task/presentation/pages/task_create_page.dart';
import '../../features/task/presentation/pages/kanban_board_page.dart';
import '../../features/time_tracking/presentation/pages/time_tracking_page.dart';
import '../../features/time_tracking/presentation/pages/time_reports_page.dart';
import '../../features/notification/presentation/pages/notifications_page.dart';
import '../../shared/widgets/app_scaffold.dart';

class AppRouter {
  final SecureStorageService _secureStorage;
  late final GoRouter router;

  AppRouter({required SecureStorageService secureStorage})
      : _secureStorage = secureStorage {
    router = GoRouter(
      initialLocation: '/login',
      debugLogDiagnostics: false,
      redirect: _authGuard,
      routes: [
        GoRoute(
          path: '/login',
          name: 'login',
          builder: (context, state) => const LoginPage(),
        ),
        GoRoute(
          path: '/register',
          name: 'register',
          builder: (context, state) => const RegisterPage(),
        ),
        ShellRoute(
          builder: (context, state, child) => AppScaffold(child: child),
          routes: [
            GoRoute(
              path: '/',
              name: 'dashboard',
              builder: (context, state) => const DashboardPage(),
            ),
            GoRoute(
              path: '/workspaces',
              name: 'workspaces',
              builder: (context, state) => const WorkspaceListPage(),
            ),
            GoRoute(
              path: '/workspaces/create',
              name: 'workspaceCreate',
              builder: (context, state) => const WorkspaceCreatePage(),
            ),
            GoRoute(
              path: '/workspaces/:id/members',
              name: 'workspaceMembers',
              builder: (context, state) => MembersPage(
                workspaceId: state.pathParameters['id']!,
              ),
            ),
            GoRoute(
              path: '/projects',
              name: 'projects',
              builder: (context, state) => const ProjectListPage(),
            ),
            GoRoute(
              path: '/projects/create',
              name: 'projectCreate',
              builder: (context, state) => const ProjectCreatePage(),
            ),
            GoRoute(
              path: '/projects/:id',
              name: 'projectDetail',
              builder: (context, state) => ProjectDetailPage(
                projectId: state.pathParameters['id']!,
              ),
            ),
            GoRoute(
              path: '/projects/:id/board',
              name: 'kanbanBoard',
              builder: (context, state) => KanbanBoardPage(
                projectId: state.pathParameters['id']!,
              ),
            ),
            GoRoute(
              path: '/tasks',
              name: 'tasks',
              builder: (context, state) => const TaskListPage(),
            ),
            GoRoute(
              path: '/tasks/create',
              name: 'taskCreate',
              builder: (context, state) => const TaskCreatePage(),
            ),
            GoRoute(
              path: '/tasks/:id',
              name: 'taskDetail',
              builder: (context, state) => TaskDetailPage(
                taskId: state.pathParameters['id']!,
              ),
            ),
            GoRoute(
              path: '/time-tracking',
              name: 'timeTracking',
              builder: (context, state) => const TimeTrackingPage(),
            ),
            GoRoute(
              path: '/time-reports',
              name: 'timeReports',
              builder: (context, state) => const TimeReportsPage(),
            ),
            GoRoute(
              path: '/notifications',
              name: 'notifications',
              builder: (context, state) => const NotificationsPage(),
            ),
          ],
        ),
      ],
    );
  }

  Future<String?> _authGuard(BuildContext context, GoRouterState state) async {
    final isLoggedIn = await _secureStorage.getToken() != null;
    final isAuthRoute = state.matchedLocation == '/login' ||
        state.matchedLocation == '/register';

    if (!isLoggedIn && !isAuthRoute) {
      return '/login';
    }

    if (isLoggedIn && isAuthRoute) {
      return '/';
    }

    return null;
  }
}
