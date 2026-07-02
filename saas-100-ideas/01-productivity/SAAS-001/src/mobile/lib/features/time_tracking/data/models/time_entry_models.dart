import 'package:equatable/equatable.dart';

class TimeEntryDTO extends Equatable {
  final String id;
  final String taskId;
  final String taskTitle;
  final String projectName;
  final String userId;
  final String userName;
  final String? startedAt;
  final String? endedAt;
  final int? durationMinutes;
  final String? note;
  final bool isManual;
  final String? createdAt;

  const TimeEntryDTO({
    required this.id,
    required this.taskId,
    this.taskTitle = '',
    this.projectName = '',
    required this.userId,
    this.userName = '',
    this.startedAt,
    this.endedAt,
    this.durationMinutes,
    this.note,
    this.isManual = false,
    this.createdAt,
  });

  factory TimeEntryDTO.fromJson(Map<String, dynamic> json) {
    return TimeEntryDTO(
      id: json['id'] as String,
      taskId: json['task_id'] as String,
      taskTitle: json['task_title'] as String? ?? '',
      projectName: json['project_name'] as String? ?? '',
      userId: json['user_id'] as String,
      userName: json['user_name'] as String? ?? '',
      startedAt: json['started_at'] as String?,
      endedAt: json['ended_at'] as String?,
      durationMinutes: json['duration_minutes'] as int?,
      note: json['note'] as String?,
      isManual: json['is_manual'] as bool? ?? false,
      createdAt: json['created_at'] as String?,
    );
  }

  bool get isRunning => endedAt == null && startedAt != null;

  @override
  List<Object?> get props => [
        id, taskId, taskTitle, projectName, userId, userName,
        startedAt, endedAt, durationMinutes, note, isManual, createdAt,
      ];
}

class StartTimerRequest extends Equatable {
  final String taskId;
  final String? note;

  const StartTimerRequest({required this.taskId, this.note});

  Map<String, dynamic> toJson() => {
        'task_id': taskId,
        if (note != null) 'note': note,
      };

  @override
  List<Object?> get props => [taskId, note];
}

class StopTimerRequest extends Equatable {
  final String? note;

  const StopTimerRequest({this.note});

  Map<String, dynamic> toJson() => {if (note != null) 'note': note};

  @override
  List<Object?> get props => [note];
}

class ManualTimeEntryRequest extends Equatable {
  final String taskId;
  final String startedAt;
  final String endedAt;
  final String? note;

  const ManualTimeEntryRequest({
    required this.taskId,
    required this.startedAt,
    required this.endedAt,
    this.note,
  });

  Map<String, dynamic> toJson() => {
        'task_id': taskId,
        'started_at': startedAt,
        'ended_at': endedAt,
        'is_manual': true,
        if (note != null) 'note': note,
      };

  @override
  List<Object?> get props => [taskId, startedAt, endedAt, note];
}

class TimeReportDTO extends Equatable {
  final int totalMinutes;
  final int totalHours;
  final int billableMinutes;
  final double avgDailyHours;
  final String? periodFrom;
  final String? periodTo;
  final List<ReportEntryDTO> entries;
  final String? exportUrl;

  const TimeReportDTO({
    required this.totalMinutes,
    required this.totalHours,
    required this.billableMinutes,
    required this.avgDailyHours,
    this.periodFrom,
    this.periodTo,
    this.entries = const [],
    this.exportUrl,
  });

  factory TimeReportDTO.fromJson(Map<String, dynamic> json) {
    final summary = json['summary'] as Map<String, dynamic>;
    final entriesList = json['entries'] as List<dynamic>? ?? [];
    return TimeReportDTO(
      totalMinutes: summary['total_minutes'] as int? ?? 0,
      totalHours: summary['total_hours'] as int? ?? 0,
      billableMinutes: summary['billable_minutes'] as int? ?? 0,
      avgDailyHours: (summary['avg_daily_hours'] as num?)?.toDouble() ?? 0.0,
      periodFrom: (summary['period'] as Map<String, dynamic>?)?['from'] as String?,
      periodTo: (summary['period'] as Map<String, dynamic>?)?['to'] as String?,
      entries: entriesList
          .map((e) => ReportEntryDTO.fromJson(e as Map<String, dynamic>))
          .toList(),
      exportUrl: json['export_url'] as String?,
    );
  }

  @override
  List<Object?> get props => [
        totalMinutes, totalHours, billableMinutes, avgDailyHours,
        periodFrom, periodTo, entries, exportUrl,
      ];
}

class ReportEntryDTO extends Equatable {
  final String? date;
  final int minutes;
  final List<ReportProjectDTO> projects;

  const ReportEntryDTO({
    this.date,
    this.minutes = 0,
    this.projects = const [],
  });

  factory ReportEntryDTO.fromJson(Map<String, dynamic> json) {
    return ReportEntryDTO(
      date: json['date'] as String?,
      minutes: json['minutes'] as int? ?? 0,
      projects: (json['projects'] as List<dynamic>?)
              ?.map((e) => ReportProjectDTO.fromJson(e as Map<String, dynamic>))
              .toList() ??
          [],
    );
  }

  @override
  List<Object?> get props => [date, minutes, projects];
}

class ReportProjectDTO extends Equatable {
  final String projectId;
  final String projectName;
  final int minutes;

  const ReportProjectDTO({
    required this.projectId,
    required this.projectName,
    required this.minutes,
  });

  factory ReportProjectDTO.fromJson(Map<String, dynamic> json) {
    return ReportProjectDTO(
      projectId: json['project_id'] as String,
      projectName: json['project_name'] as String,
      minutes: json['minutes'] as int,
    );
  }

  @override
  List<Object?> get props => [projectId, projectName, minutes];
}
