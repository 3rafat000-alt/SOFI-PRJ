import 'package:equatable/equatable.dart';

class TimeEntry extends Equatable {
  final String id;
  final String taskId;
  final String taskTitle;
  final String projectName;
  final String userId;
  final String userName;
  final DateTime? startedAt;
  final DateTime? endedAt;
  final int? durationMinutes;
  final String? note;
  final bool isRunning;
  final bool isManual;
  final DateTime createdAt;

  const TimeEntry({
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
    this.isRunning = false,
    this.isManual = false,
    required this.createdAt,
  });

  @override
  List<Object?> get props => [
        id, taskId, taskTitle, projectName, userId, userName,
        startedAt, endedAt, durationMinutes, note, isRunning, isManual, createdAt,
      ];

  TimeEntry copyWith({
    String? id,
    String? taskId,
    String? taskTitle,
    String? projectName,
    String? userId,
    String? userName,
    DateTime? startedAt,
    DateTime? endedAt,
    int? durationMinutes,
    String? note,
    bool? isRunning,
    bool? isManual,
    DateTime? createdAt,
  }) {
    return TimeEntry(
      id: id ?? this.id,
      taskId: taskId ?? this.taskId,
      taskTitle: taskTitle ?? this.taskTitle,
      projectName: projectName ?? this.projectName,
      userId: userId ?? this.userId,
      userName: userName ?? this.userName,
      startedAt: startedAt ?? this.startedAt,
      endedAt: endedAt ?? this.endedAt,
      durationMinutes: durationMinutes ?? this.durationMinutes,
      note: note ?? this.note,
      isRunning: isRunning ?? this.isRunning,
      isManual: isManual ?? this.isManual,
      createdAt: createdAt ?? this.createdAt,
    );
  }
}

class TimeReport extends Equatable {
  final int totalMinutes;
  final int totalHours;
  final int billableMinutes;
  final double avgDailyHours;
  final DateTime periodFrom;
  final DateTime periodTo;
  final List<ReportEntry> entries;

  const TimeReport({
    required this.totalMinutes,
    required this.totalHours,
    required this.billableMinutes,
    required this.avgDailyHours,
    required this.periodFrom,
    required this.periodTo,
    this.entries = const [],
  });

  @override
  List<Object?> get props => [
        totalMinutes, totalHours, billableMinutes, avgDailyHours,
        periodFrom, periodTo, entries,
      ];
}

class ReportEntry extends Equatable {
  final DateTime date;
  final int minutes;
  final List<ReportProject> projects;

  const ReportEntry({
    required this.date,
    required this.minutes,
    this.projects = const [],
  });

  @override
  List<Object?> get props => [date, minutes, projects];
}

class ReportProject extends Equatable {
  final String projectId;
  final String projectName;
  final int minutes;

  const ReportProject({
    required this.projectId,
    required this.projectName,
    required this.minutes,
  });

  @override
  List<Object?> get props => [projectId, projectName, minutes];
}
