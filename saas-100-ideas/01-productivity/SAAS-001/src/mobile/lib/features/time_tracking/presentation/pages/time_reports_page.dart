import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../../../core/constants/app_colors.dart';
import '../../../../core/constants/app_dimensions.dart';
import '../../../../core/localization/app_localizations.dart';
import '../../../../shared/widgets/loading_indicator.dart';
import '../../../../shared/widgets/error_view.dart';
import '../../../../shared/widgets/empty_state_view.dart';
import '../bloc/time_entry_bloc.dart';

class TimeReportsPage extends StatefulWidget {
  const TimeReportsPage({super.key});

  @override
  State<TimeReportsPage> createState() => _TimeReportsPageState();
}

class _TimeReportsPageState extends State<TimeReportsPage> {
  DateTime _fromDate = DateTime.now().subtract(const Duration(days: 30));
  DateTime _toDate = DateTime.now();

  @override
  void initState() {
    super.initState();
    _loadReport();
  }

  void _loadReport() {
    context.read<TimeEntryBloc>().add(LoadTimeEntriesEvent(
          from: _fromDate,
          to: _toDate,
        ));
  }

  @override
  Widget build(BuildContext context) {
    final localizations = AppLocalizations.of(context);
    final isArabic = localizations.isArabic;

    return Scaffold(
      appBar: AppBar(
        title: Text(localizations.reports),
        actions: [
          IconButton(
            icon: const Icon(Icons.file_download_outlined),
            onPressed: () {
              // TODO: export
            },
            tooltip: localizations.exportCSV,
          ),
        ],
      ),
      body: Column(
        children: [
          // Date range selector
          Container(
            padding: const EdgeInsets.all(AppDimensions.spacing16),
            color: Colors.white,
            child: Row(
              children: [
                Expanded(
                  child: _DateButton(
                    label: isArabic ? 'من' : 'From',
                    date: _fromDate,
                    onTap: () => _pickDate(true),
                  ),
                ),
                const Padding(
                  padding: EdgeInsets.symmetric(horizontal: 8),
                  child: Icon(Icons.arrow_forward, size: 16),
                ),
                Expanded(
                  child: _DateButton(
                    label: isArabic ? 'إلى' : 'To',
                    date: _toDate,
                    onTap: () => _pickDate(false),
                  ),
                ),
              ],
            ),
          ),
          const Divider(height: 1),

          // Report content
          Expanded(
            child: BlocBuilder<TimeEntryBloc, TimeEntryState>(
              builder: (context, state) {
                if (state is TimeEntryLoading) {
                  return const LoadingIndicator();
                }
                if (state is TimeEntryError) {
                  return ErrorView(
                    message: state.message,
                    onRetry: _loadReport,
                  );
                }
                if (state is TimeEntriesLoaded) {
                  if (state.entries.isEmpty) {
                    return EmptyStateView(
                      message: localizations.noTimeEntries,
                      icon: Icons.bar_chart_outlined,
                    );
                  }

                  // Calculate totals
                  int totalMinutes = 0;
                  for (final entry in state.entries) {
                    totalMinutes += entry.durationMinutes ?? 0;
                  }
                  final totalHours = totalMinutes ~/ 60;

                  return SingleChildScrollView(
                    padding: const EdgeInsets.all(AppDimensions.spacing16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        // KPI cards
                        Row(
                          children: [
                            Expanded(
                              child: _KpiCard(
                                title: localizations.totalHours,
                                value: '$totalHours',
                                icon: Icons.timer_outlined,
                                color: AppColors.primary,
                              ),
                            ),
                            const SizedBox(width: 12),
                            Expanded(
                              child: _KpiCard(
                                title: localizations.totalTasks,
                                value: '${state.entries.length}',
                                icon: Icons.check_circle_outline,
                                color: AppColors.success,
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: AppDimensions.spacing20),

                        // Time entries (simplified report)
                        Text(
                          localizations.timeTracking,
                          style: Theme.of(context).textTheme.titleMedium,
                        ),
                        const SizedBox(height: AppDimensions.spacing12),
                        ...state.entries.take(20).map((entry) {
                          final hours = (entry.durationMinutes ?? 0) ~/ 60;
                          final mins = (entry.durationMinutes ?? 0) % 60;
                          return ListTile(
                            dense: true,
                            title: Text(entry.taskTitle),
                            subtitle: Text(entry.projectName),
                            trailing: Text(
                              '${hours}h ${mins}m',
                              style: const TextStyle(
                                fontWeight: FontWeight.w600,
                                color: AppColors.textPrimary,
                              ),
                            ),
                          );
                        }),
                      ],
                    ),
                  );
                }
                return const SizedBox.shrink();
              },
            ),
          ),
        ],
      ),
    );
  }

  Future<void> _pickDate(bool isFrom) async {
    final picked = await showDatePicker(
      context: context,
      initialDate: isFrom ? _fromDate : _toDate,
      firstDate: DateTime.now().subtract(const Duration(days: 365)),
      lastDate: DateTime.now(),
    );
    if (picked != null) {
      setState(() {
        if (isFrom) {
          _fromDate = picked;
        } else {
          _toDate = picked;
        }
      });
      _loadReport();
    }
  }
}

class _DateButton extends StatelessWidget {
  final String label;
  final DateTime date;
  final VoidCallback onTap;

  const _DateButton({
    required this.label,
    required this.date,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      child: InputDecorator(
        decoration: InputDecoration(
          labelText: label,
          isDense: true,
          contentPadding: const EdgeInsets.symmetric(
            horizontal: 12,
            vertical: 8,
          ),
        ),
        child: Text(
          '${date.year}/${date.month}/${date.day}',
          style: const TextStyle(fontSize: 13),
        ),
      ),
    );
  }
}

class _KpiCard extends StatelessWidget {
  final String title;
  final String value;
  final IconData icon;
  final Color color;

  const _KpiCard({
    required this.title,
    required this.value,
    required this.icon,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(AppDimensions.spacing16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(icon, size: 20, color: color),
                const SizedBox(width: 8),
                Text(
                  title,
                  style: Theme.of(context).textTheme.bodySmall,
                ),
              ],
            ),
            const SizedBox(height: AppDimensions.spacing8),
            Text(
              value,
              style: Theme.of(context).textTheme.headlineMedium?.copyWith(
                    color: color,
                    fontWeight: FontWeight.w700,
                  ),
            ),
          ],
        ),
      ),
    );
  }
}
