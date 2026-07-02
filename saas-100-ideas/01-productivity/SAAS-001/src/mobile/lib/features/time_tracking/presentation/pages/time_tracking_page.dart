import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../../../core/constants/app_colors.dart';
import '../../../../core/constants/app_dimensions.dart';
import '../../../../core/localization/app_localizations.dart';
import '../bloc/time_entry_bloc.dart';
import '../widgets/timer_widget.dart';
import '../widgets/time_entry_list.dart';

class TimeTrackingPage extends StatefulWidget {
  const TimeTrackingPage({super.key});

  @override
  State<TimeTrackingPage> createState() => _TimeTrackingPageState();
}

class _TimeTrackingPageState extends State<TimeTrackingPage> {
  @override
  void initState() {
    super.initState();
    context.read<TimeEntryBloc>().add(const LoadTimeEntriesEvent());
  }

  @override
  Widget build(BuildContext context) {
    final localizations = AppLocalizations.of(context);

    return Scaffold(
      appBar: AppBar(
        title: Text(localizations.timeTracking),
      ),
      body: BlocConsumer<TimeEntryBloc, TimeEntryState>(
        listener: (context, state) {
          if (state is TimeEntryError) {
            ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(
                content: Text(state.message),
                backgroundColor: AppColors.error,
              ),
            );
          }
        },
        builder: (context, state) {
          return SingleChildScrollView(
            padding: const EdgeInsets.all(AppDimensions.spacing16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Timer widget
                Card(
                  child: Padding(
                    padding: const EdgeInsets.all(AppDimensions.spacing20),
                    child: Column(
                      children: [
                        Text(
                          localizations.timeTracking,
                          style: Theme.of(context).textTheme.titleMedium,
                        ),
                        const SizedBox(height: AppDimensions.spacing20),
                        // Timer display
                        TimerWidget(
                          isRunning: state is TimerRunning,
                          elapsed: state is TimerRunning ? state.elapsed : Duration.zero,
                          onStart: () {
                            // TODO: show task picker
                          },
                          onStop: () {
                            context
                                .read<TimeEntryBloc>()
                                .add(const StopTimerEvent());
                          },
                        ),
                        const SizedBox(height: AppDimensions.spacing16),
                        // Manual entry button
                        OutlinedButton.icon(
                          onPressed: () {
                            // TODO: show manual entry dialog
                          },
                          icon: const Icon(Icons.edit_outlined, size: 18),
                          label: Text(localizations.manualEntry),
                        ),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: AppDimensions.spacing24),

                // Time entries
                Text(
                  localizations.timeTracking,
                  style: Theme.of(context).textTheme.titleMedium,
                ),
                const SizedBox(height: AppDimensions.spacing12),
                if (state is TimeEntriesLoaded)
                  TimeEntryList(entries: state.entries)
                else
                  const TimeEntryList(entries: []),
              ],
            ),
          );
        },
      ),
    );
  }
}
