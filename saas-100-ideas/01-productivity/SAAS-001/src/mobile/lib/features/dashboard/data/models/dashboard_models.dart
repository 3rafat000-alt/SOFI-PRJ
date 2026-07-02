import 'package:equatable/equatable.dart';

class DashboardStatsDTO extends Equatable {
  final TaskStatsDTO tasks;
  final ProjectStatsDTO projects;
  final TimeStatsDTO time;
  final MemberStatsDTO members;

  const DashboardStatsDTO({
    required this.tasks,
    required this.projects,
    required this.time,
    required this.members,
  });

  factory DashboardStatsDTO.fromJson(Map<String, dynamic> json) {
    final data = json['data'] as Map<String, dynamic>;
    return DashboardStatsDTO(
      tasks: TaskStatsDTO.fromJson(data['tasks'] as Map<String, dynamic>),
      projects: ProjectStatsDTO.fromJson(data['projects'] as Map<String, dynamic>),
      time: TimeStatsDTO.fromJson(data['time'] as Map<String, dynamic>),
      members: MemberStatsDTO.fromJson(data['members'] as Map<String, dynamic>),
    );
  }

  @override
  List<Object?> get props => [tasks, projects, time, members];
}

class TaskStatsDTO extends Equatable {
  final int total;
  final int todo;
  final int inProgress;
  final int done;
  final int overdue;
  final int upcomingWeek;

  const TaskStatsDTO({
    this.total = 0,
    this.todo = 0,
    this.inProgress = 0,
    this.done = 0,
    this.overdue = 0,
    this.upcomingWeek = 0,
  });

  factory TaskStatsDTO.fromJson(Map<String, dynamic> json) {
    return TaskStatsDTO(
      total: json['total'] as int? ?? 0,
      todo: json['todo'] as int? ?? 0,
      inProgress: json['in_progress'] as int? ?? 0,
      done: json['done'] as int? ?? 0,
      overdue: json['overdue'] as int? ?? 0,
      upcomingWeek: json['upcoming_week'] as int? ?? 0,
    );
  }

  @override
  List<Object?> get props => [total, todo, inProgress, done, overdue, upcomingWeek];
}

class ProjectStatsDTO extends Equatable {
  final int active;
  final int archived;

  const ProjectStatsDTO({this.active = 0, this.archived = 0});

  factory ProjectStatsDTO.fromJson(Map<String, dynamic> json) {
    return ProjectStatsDTO(
      active: json['active'] as int? ?? 0,
      archived: json['archived'] as int? ?? 0,
    );
  }

  @override
  List<Object?> get props => [active, archived];
}

class TimeStatsDTO extends Equatable {
  final int todayMinutes;
  final int weekMinutes;
  final int monthMinutes;

  const TimeStatsDTO({
    this.todayMinutes = 0,
    this.weekMinutes = 0,
    this.monthMinutes = 0,
  });

  factory TimeStatsDTO.fromJson(Map<String, dynamic> json) {
    return TimeStatsDTO(
      todayMinutes: json['today_minutes'] as int? ?? 0,
      weekMinutes: json['week_minutes'] as int? ?? 0,
      monthMinutes: json['month_minutes'] as int? ?? 0,
    );
  }

  @override
  List<Object?> get props => [todayMinutes, weekMinutes, monthMinutes];
}

class MemberStatsDTO extends Equatable {
  final int total;
  final int activeToday;

  const MemberStatsDTO({this.total = 0, this.activeToday = 0});

  factory MemberStatsDTO.fromJson(Map<String, dynamic> json) {
    return MemberStatsDTO(
      total: json['total'] as int? ?? 0,
      activeToday: json['active_today'] as int? ?? 0,
    );
  }

  @override
  List<Object?> get props => [total, activeToday];
}

class ActivityItemDTO extends Equatable {
  final String id;
  final String type;
  final ActivityUserDTO user;
  final String description;
  final String projectName;
  final String? createdAt;

  const ActivityItemDTO({
    required this.id,
    required this.type,
    required this.user,
    required this.description,
    required this.projectName,
    this.createdAt,
  });

  factory ActivityItemDTO.fromJson(Map<String, dynamic> json) {
    return ActivityItemDTO(
      id: json['id'] as String,
      type: json['type'] as String,
      user: ActivityUserDTO.fromJson(json['user'] as Map<String, dynamic>),
      description: json['description'] as String,
      projectName: json['project_name'] as String? ?? '',
      createdAt: json['created_at'] as String?,
    );
  }

  @override
  List<Object?> get props => [id, type, user, description, projectName, createdAt];
}

class ActivityUserDTO extends Equatable {
  final String id;
  final String name;

  const ActivityUserDTO({required this.id, required this.name});

  factory ActivityUserDTO.fromJson(Map<String, dynamic> json) {
    return ActivityUserDTO(
      id: json['id'] as String,
      name: json['name'] as String,
    );
  }

  @override
  List<Object?> get props => [id, name];
}
