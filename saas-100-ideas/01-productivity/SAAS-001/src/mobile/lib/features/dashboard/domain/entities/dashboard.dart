import 'package:equatable/equatable.dart';

class DashboardStats extends Equatable {
  final TaskStats tasks;
  final ProjectStats projects;
  final TimeStats time;
  final MemberStats members;

  const DashboardStats({
    required this.tasks,
    required this.projects,
    required this.time,
    required this.members,
  });

  @override
  List<Object?> get props => [tasks, projects, time, members];
}

class TaskStats extends Equatable {
  final int total;
  final int todo;
  final int inProgress;
  final int done;
  final int overdue;
  final int upcomingWeek;

  const TaskStats({
    this.total = 0,
    this.todo = 0,
    this.inProgress = 0,
    this.done = 0,
    this.overdue = 0,
    this.upcomingWeek = 0,
  });

  @override
  List<Object?> get props => [total, todo, inProgress, done, overdue, upcomingWeek];
}

class ProjectStats extends Equatable {
  final int active;
  final int archived;

  const ProjectStats({this.active = 0, this.archived = 0});

  @override
  List<Object?> get props => [active, archived];
}

class TimeStats extends Equatable {
  final int todayMinutes;
  final int weekMinutes;
  final int monthMinutes;

  const TimeStats({
    this.todayMinutes = 0,
    this.weekMinutes = 0,
    this.monthMinutes = 0,
  });

  @override
  List<Object?> get props => [todayMinutes, weekMinutes, monthMinutes];
}

class MemberStats extends Equatable {
  final int total;
  final int activeToday;

  const MemberStats({this.total = 0, this.activeToday = 0});

  @override
  List<Object?> get props => [total, activeToday];
}

class ActivityItem extends Equatable {
  final String id;
  final String type;
  final String userName;
  final String description;
  final String projectName;
  final DateTime createdAt;

  const ActivityItem({
    required this.id,
    required this.type,
    required this.userName,
    required this.description,
    required this.projectName,
    required this.createdAt,
  });

  @override
  List<Object?> get props => [id, type, userName, description, projectName, createdAt];
}
