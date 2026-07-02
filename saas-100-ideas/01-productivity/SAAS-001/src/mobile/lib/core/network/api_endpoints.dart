class ApiEndpoints {
  ApiEndpoints._();

  static const String baseUrl = 'https://api.tasksyncpro.com/api/v1';
  static const String baseUrlDev = 'http://localhost:8000/api/v1';

  // Auth
  static const String register = '/auth/register';
  static const String login = '/auth/login';
  static const String logout = '/auth/logout';
  static const String forgotPassword = '/auth/forgot-password';
  static const String resetPassword = '/auth/reset-password';
  static const String me = '/auth/me';

  // Workspaces
  static const String workspaces = '/workspaces';
  static String workspace(String id) => '/workspaces/$id';
  static String workspaceMembers(String id) => '/workspaces/$id/members';
  static String workspaceInvite(String id) => '/workspaces/$id/invite';

  // Projects
  static const String projects = '/projects';
  static String project(String id) => '/projects/$id';
  static String projectTasks(String id) => '/projects/$id/tasks';

  // Tasks
  static const String tasks = '/tasks';
  static String task(String id) => '/tasks/$id';
  static String taskStatus(String id) => '/tasks/$id/status';
  static const String reorderTasks = '/tasks/reorder';
  static String taskComments(String id) => '/tasks/$id/comments';
  static String taskAttachments(String id) => '/tasks/$id/attachments';

  // Time Entries
  static const String timeEntries = '/time-entries';
  static const String timeEntryStart = '/time-entries/start';
  static const String timeEntryStop = '/time-entries/stop';
  static String timeEntry(String id) => '/time-entries/$id';
  static const String timeEntryReport = '/time-entries/report';
  static const String timeEntryExport = '/time-entries/report/export';

  // Tags
  static const String tags = '/tags';
  static String tag(String id) => '/tags/$id';

  // Notifications
  static const String notifications = '/notifications';
  static String notificationRead(String id) => '/notifications/$id/read';
  static const String notificationsReadAll = '/notifications/read-all';

  // Dashboard
  static const String dashboardStats = '/dashboard/stats';
  static const String dashboardActivity = '/dashboard/activity';

  // Attachments
  static String attachmentDownload(String id) => '/attachments/$id/download';

  // Comments
  static String comment(String id) => '/comments/$id';
}
