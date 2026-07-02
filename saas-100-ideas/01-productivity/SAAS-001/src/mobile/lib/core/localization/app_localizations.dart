import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../constants/app_strings.dart';

class AppLocalizations {
  final Locale locale;

  AppLocalizations(this.locale);

  static AppLocalizations of(BuildContext context) {
    return Localizations.of<AppLocalizations>(context, AppLocalizations)!;
  }

  static const LocalizationsDelegate<AppLocalizations> delegate =
      _AppLocalizationsDelegate();

  static const List<Locale> supportedLocales = [
    Locale('ar', 'AE'),
    Locale('en', 'US'),
  ];

  /// Map of translation key -> Map<locale, value>
  static const Map<String, Map<String, String>> _translations = {
    // App
    'app_name': {'ar': 'تاسك سينك برو', 'en': 'TaskSync Pro'},

    // General
    'loading': {'ar': 'جاري التحميل...', 'en': 'Loading...'},
    'error': {'ar': 'خطأ', 'en': 'Error'},
    'retry': {'ar': 'إعادة المحاولة', 'en': 'Retry'},
    'cancel': {'ar': 'إلغاء', 'en': 'Cancel'},
    'confirm': {'ar': 'تأكيد', 'en': 'Confirm'},
    'save': {'ar': 'حفظ', 'en': 'Save'},
    'delete': {'ar': 'حذف', 'en': 'Delete'},
    'edit': {'ar': 'تعديل', 'en': 'Edit'},
    'create': {'ar': 'إنشاء', 'en': 'Create'},
    'search': {'ar': 'بحث', 'en': 'Search'},
    'no_data': {'ar': 'لا توجد بيانات', 'en': 'No data available'},
    'offline': {'ar': 'غير متصل', 'en': 'Offline'},
    'online': {'ar': 'متصل', 'en': 'Online'},
    'pull_to_refresh': {'ar': 'اسحب للتحديث', 'en': 'Pull to refresh'},

    // Auth
    'login': {'ar': 'تسجيل الدخول', 'en': 'Login'},
    'register': {'ar': 'إنشاء حساب', 'en': 'Register'},
    'logout': {'ar': 'تسجيل الخروج', 'en': 'Logout'},
    'email': {'ar': 'البريد الإلكتروني', 'en': 'Email'},
    'password': {'ar': 'كلمة المرور', 'en': 'Password'},
    'confirm_password': {'ar': 'تأكيد كلمة المرور', 'en': 'Confirm Password'},
    'name': {'ar': 'الاسم', 'en': 'Name'},
    'forgot_password': {'ar': 'نسيت كلمة المرور؟', 'en': 'Forgot Password?'},
    'reset_password': {'ar': 'إعادة تعيين كلمة المرور', 'en': 'Reset Password'},
    'login_title': {'ar': 'مرحباً بعودتك', 'en': 'Welcome Back'},
    'register_title': {'ar': 'إنشاء حساب جديد', 'en': 'Create Account'},
    'no_account': {'ar': 'ليس لديك حساب؟ ', 'en': "Don't have an account? "},
    'have_account': {'ar': 'لديك حساب بالفعل؟ ', 'en': 'Already have an account? '},
    'sign_up_free': {'ar': 'ابدأ مجاناً', 'en': 'Start Free'},
    'workspace_name': {'ar': 'اسم مساحة العمل', 'en': 'Workspace Name'},
    'locale': {'ar': 'اللغة', 'en': 'Language'},

    // Workspace
    'workspaces': {'ar': 'مساحات العمل', 'en': 'Workspaces'},
    'create_workspace': {'ar': 'إنشاء مساحة عمل', 'en': 'Create Workspace'},
    'workspace_name_hint': {'ar': 'أدخل اسم مساحة العمل', 'en': 'Enter workspace name'},
    'workspace_description': {'ar': 'الوصف', 'en': 'Description'},
    'members': {'ar': 'الأعضاء', 'en': 'Members'},
    'invite_member': {'ar': 'دعوة عضو', 'en': 'Invite Member'},
    'invite_via_email': {'ar': 'دعوة عبر البريد', 'en': 'Invite via Email'},
    'invite_via_whatsapp': {'ar': 'دعوة عبر واتساب', 'en': 'Invite via WhatsApp'},
    'copy_invite_link': {'ar': 'نسخ رابط الدعوة', 'en': 'Copy Invite Link'},
    'member_role': {'ar': 'صلاحية العضو', 'en': 'Member Role'},
    'pending_invites': {'ar': 'دعوات معلقة', 'en': 'Pending Invites'},
    'no_members': {'ar': 'لا يوجد أعضاء بعد', 'en': 'No members yet'},
    'max_members_reached': {'ar': 'تم الوصول للحد الأقصى للأعضاء', 'en': 'Maximum members reached'},
    'invite_sent': {'ar': 'تم إرسال الدعوة', 'en': 'Invite sent'},

    // Project
    'projects': {'ar': 'المشاريع', 'en': 'Projects'},
    'create_project': {'ar': 'مشروع جديد', 'en': 'New Project'},
    'project_name': {'ar': 'اسم المشروع', 'en': 'Project Name'},
    'project_description': {'ar': 'وصف المشروع', 'en': 'Project Description'},
    'project_color': {'ar': 'لون المشروع', 'en': 'Project Color'},
    'start_date': {'ar': 'تاريخ البداية', 'en': 'Start Date'},
    'end_date': {'ar': 'تاريخ النهاية', 'en': 'End Date'},
    'active_projects': {'ar': 'المشاريع النشطة', 'en': 'Active Projects'},
    'archived_projects': {'ar': 'المشاريع المؤرشفة', 'en': 'Archived Projects'},
    'no_projects': {'ar': 'لا توجد مشاريع بعد', 'en': 'No projects yet'},

    // Task
    'tasks': {'ar': 'المهام', 'en': 'Tasks'},
    'create_task': {'ar': 'مهمة جديدة', 'en': 'New Task'},
    'task_title': {'ar': 'عنوان المهمة', 'en': 'Task Title'},
    'task_description': {'ar': 'وصف المهمة', 'en': 'Task Description'},
    'assignee': {'ar': 'المسؤول', 'en': 'Assignee'},
    'due_date': {'ar': 'تاريخ الاستحقاق', 'en': 'Due Date'},
    'priority': {'ar': 'الأولوية', 'en': 'Priority'},
    'status': {'ar': 'الحالة', 'en': 'Status'},
    'estimated_hours': {'ar': 'الوقت المقدر (ساعات)', 'en': 'Estimated (hours)'},
    'logged_hours': {'ar': 'الوقت المسجل', 'en': 'Logged Hours'},
    'tags': {'ar': 'الوسوم', 'en': 'Tags'},
    'attachments': {'ar': 'المرفقات', 'en': 'Attachments'},
    'comments': {'ar': 'التعليقات', 'en': 'Comments'},
    'todo': {'ar': 'معلقة', 'en': 'To Do'},
    'in_progress': {'ar': 'قيد التنفيذ', 'en': 'In Progress'},
    'done': {'ar': 'مكتملة', 'en': 'Done'},
    'urgent': {'ar': 'عاجل', 'en': 'Urgent'},
    'high': {'ar': 'عالية', 'en': 'High'},
    'medium': {'ar': 'متوسطة', 'en': 'Medium'},
    'low': {'ar': 'منخفضة', 'en': 'Low'},
    'no_tasks': {'ar': 'لا توجد مهام بعد', 'en': 'No tasks yet'},
    'overdue': {'ar': 'متأخر', 'en': 'Overdue'},
    'quick_status_change': {'ar': 'تغيير سريع للحالة', 'en': 'Quick Status Change'},

    // Kanban
    'kanban': {'ar': 'كانبان', 'en': 'Kanban'},
    'todo_column': {'ar': 'معلق', 'en': 'To Do'},
    'in_progress_column': {'ar': 'قيد التنفيذ', 'en': 'In Progress'},
    'done_column': {'ar': 'مكتمل', 'en': 'Done'},
    'drag_hint': {'ar': 'اسحب المهام بين الأعمدة', 'en': 'Drag tasks between columns'},

    // Time Tracking
    'time_tracking': {'ar': 'تتبع الوقت', 'en': 'Time Tracking'},
    'start_timer': {'ar': 'بدء المؤقت', 'en': 'Start Timer'},
    'stop_timer': {'ar': 'إيقاف المؤقت', 'en': 'Stop Timer'},
    'pause_timer': {'ar': 'إيقاف مؤقت', 'en': 'Pause'},
    'resume_timer': {'ar': 'استئناف', 'en': 'Resume'},
    'manual_entry': {'ar': 'إدخال يدوي', 'en': 'Manual Entry'},
    'timer_note': {'ar': 'ملاحظة', 'en': 'Note'},
    'today': {'ar': 'اليوم', 'en': 'Today'},
    'this_week': {'ar': 'هذا الأسبوع', 'en': 'This Week'},
    'this_month': {'ar': 'هذا الشهر', 'en': 'This Month'},
    'total_hours': {'ar': 'إجمالي الساعات', 'en': 'Total Hours'},
    'no_time_entries': {'ar': 'لا توجد إدخالات وقت', 'en': 'No time entries'},
    'timer_running_on_other_task': {'ar': 'المؤقت يعمل على مهمة أخرى', 'en': 'Timer running on another task'},

    // Reports
    'reports': {'ar': 'التقارير', 'en': 'Reports'},
    'total_tasks': {'ar': 'إجمالي المهام', 'en': 'Total Tasks'},
    'active_projects_title': {'ar': 'المشاريع النشطة', 'en': 'Active Projects'},
    'member_workload': {'ar': 'عبء العمل', 'en': 'Member Workload'},
    'export_csv': {'ar': 'تصدير CSV', 'en': 'Export CSV'},
    'export_pdf': {'ar': 'تصدير PDF', 'en': 'Export PDF'},
    'no_report_data': {'ar': 'أكمل المهام لترى التقارير', 'en': 'Complete tasks to see reports'},

    // Notifications
    'notifications': {'ar': 'الإشعارات', 'en': 'Notifications'},
    'mark_all_read': {'ar': 'تحديد الكل كمقروء', 'en': 'Mark All Read'},
    'no_notifications': {'ar': 'لا توجد إشعارات', 'en': 'No notifications'},
    'task_assigned': {'ar': 'مهمة جديدة', 'en': 'New Task'},
    'task_due_soon': {'ar': 'مهمة تستحق قريباً', 'en': 'Task Due Soon'},
    'mention': {'ar': 'ذكر', 'en': 'Mention'},
    'invite': {'ar': 'دعوة', 'en': 'Invite'},

    // Dashboard
    'dashboard': {'ar': 'لوحة التحكم', 'en': 'Dashboard'},
    'quick_stats': {'ar': 'إحصائيات سريعة', 'en': 'Quick Stats'},
    'recent_activity': {'ar': 'آخر النشاطات', 'en': 'Recent Activity'},
    'upcoming_deadlines': {'ar': 'المواعيد القادمة', 'en': 'Upcoming Deadlines'},
    'see_all': {'ar': 'عرض الكل', 'en': 'See All'},

    // Errors
    'network_error': {'ar': 'خطأ في الاتصال بالشبكة', 'en': 'Network error'},
    'server_error': {'ar': 'خطأ في الخادم', 'en': 'Server error'},
    'unauthorized': {'ar': 'غير مصرح', 'en': 'Unauthorized'},
    'forbidden': {'ar': 'ليس لديك صلاحية', 'en': 'Forbidden'},
    'not_found': {'ar': 'غير موجود', 'en': 'Not found'},
    'validation_error': {'ar': 'خطأ في التحقق من البيانات', 'en': 'Validation error'},
    'unknown_error': {'ar': 'خطأ غير معروف', 'en': 'Unknown error'},
    'session_expired': {'ar': 'انتهت الجلسة', 'en': 'Session expired'},
  };

  String translate(String key) {
    final translations = _translations[key];
    if (translations == null) return key;
    return translations[locale.languageCode] ?? translations['en'] ?? key;
  }

  String get appName => translate('app_name');
  String get loading => translate('loading');
  String get error => translate('error');
  String get retry => translate('retry');
  String get cancel => translate('cancel');
  String get confirm => translate('confirm');
  String get save => translate('save');
  String get delete => translate('delete');
  String get edit => translate('edit');
  String get create => translate('create');
  String get search => translate('search');
  String get noData => translate('no_data');
  String get offline => translate('offline');
  String get online => translate('online');
  String get login => translate('login');
  String get register => translate('register');
  String get logout => translate('logout');
  String get email => translate('email');
  String get password => translate('password');
  String get confirmPassword => translate('confirm_password');
  String get name => translate('name');
  String get forgotPassword => translate('forgot_password');
  String get loginTitle => translate('login_title');
  String get registerTitle => translate('register_title');
  String get noAccount => translate('no_account');
  String get haveAccount => translate('have_account');
  String get signUpFree => translate('sign_up_free');
  String get workspaces => translate('workspaces');
  String get projects => translate('projects');
  String get tasks => translate('tasks');
  String get timeTracking => translate('time_tracking');
  String get reports => translate('reports');
  String get notifications => translate('notifications');
  String get dashboard => translate('dashboard');
  String get todo => translate('todo');
  String get inProgress => translate('in_progress');
  String get done => translate('done');
  String get high => translate('high');
  String get medium => translate('medium');
  String get low => translate('low');
  String get urgent => translate('urgent');
  String get today => translate('today');
  String get thisWeek => translate('this_week');
  String get thisMonth => translate('this_month');
  String get noTasks => translate('no_tasks');
  String get noProjects => translate('no_projects');
  String get noNotifications => translate('no_notifications');
  String get noTimeEntries => translate('no_time_entries');
  String get createWorkspace => translate('create_workspace');
  String get workspaceName => translate('workspace_name');
  String get workspaceNameHint => translate('workspace_name_hint');
  String get workspaceDescription => translate('workspace_description');
  String get createProject => translate('create_project');
  String get projectName => translate('project_name');
  String get projectDescription => translate('project_description');
  String get projectColor => translate('project_color');
  String get startDate => translate('start_date');
  String get endDate => translate('end_date');
  String get createTask => translate('create_task');
  String get taskTitle => translate('task_title');
  String get taskDescription => translate('task_description');
  String get assignee => translate('assignee');
  String get dueDate => translate('due_date');
  String get estimatedHours => translate('estimated_hours');
  String get loggedHours => translate('logged_hours');
  String get tags => translate('tags');
  String get quickStatusChange => translate('quick_status_change');
  String get comments => translate('comments');
  String get priority => translate('priority');
  String get kanban => translate('kanban');
  String get todoColumn => translate('todo_column');
  String get inProgressColumn => translate('in_progress_column');
  String get doneColumn => translate('done_column');
  String get quickStats => translate('quick_stats');
  String get recentActivity => translate('recent_activity');
  String get seeAll => translate('see_all');
  String get overdue => translate('overdue');
  String get members => translate('members');
  String get activeProjects => translate('active_projects');
  String get markAllRead => translate('mark_all_read');
  String get exportCSV => translate('export_csv');
  String get totalHours => translate('total_hours');
  String get totalTasks => translate('total_tasks');
  String get manualEntry => translate('manual_entry');
  String get inviteMember => translate('invite_member');
  String get noMembers => translate('no_members');
  String get networkError => translate('network_error');
  String get serverError => translate('server_error');
  String get unknownError => translate('unknown_error');
  String get sessionExpired => translate('session_expired');

  /// Check if current locale is Arabic
  bool get isArabic => locale.languageCode == 'ar';
}

class _AppLocalizationsDelegate extends LocalizationsDelegate<AppLocalizations> {
  const _AppLocalizationsDelegate();

  @override
  bool isSupported(Locale locale) {
    return ['ar', 'en'].contains(locale.languageCode);
  }

  @override
  Future<AppLocalizations> load(Locale locale) async {
    return AppLocalizations(locale);
  }

  @override
  bool shouldReload(_AppLocalizationsDelegate old) => false;
}
