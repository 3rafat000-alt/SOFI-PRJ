import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../../../core/constants/app_colors.dart';
import '../../../../core/constants/app_dimensions.dart';
import '../../../../core/localization/app_localizations.dart';
import '../../../../shared/widgets/loading_indicator.dart';
import '../../../../shared/widgets/error_view.dart';
import '../../../../shared/widgets/empty_state_view.dart';
import '../bloc/notification_bloc.dart';
import '../widgets/notification_tile.dart';

class NotificationsPage extends StatelessWidget {
  const NotificationsPage({super.key});

  @override
  Widget build(BuildContext context) {
    final localizations = AppLocalizations.of(context);

    return Scaffold(
      appBar: AppBar(
        title: Text(localizations.notifications),
        actions: [
          IconButton(
            icon: const Icon(Icons.done_all),
            onPressed: () {
              context.read<NotificationBloc>().add(MarkAllAsReadEvent());
            },
            tooltip: localizations.markAllRead,
          ),
        ],
      ),
      body: BlocBuilder<NotificationBloc, NotificationState>(
        builder: (context, state) {
          if (state is NotificationsLoading) {
            return const LoadingIndicator();
          }
          if (state is NotificationError) {
            return ErrorView(
              message: state.message,
              onRetry: () =>
                  context.read<NotificationBloc>().add(LoadNotificationsEvent()),
            );
          }
          if (state is NotificationsLoaded) {
            if (state.notifications.isEmpty) {
              return EmptyStateView(
                message: localizations.noNotifications,
                icon: Icons.notifications_none,
              );
            }
            return RefreshIndicator(
              onRefresh: () async {
                context.read<NotificationBloc>().add(LoadNotificationsEvent());
              },
              child: ListView.builder(
                padding: const EdgeInsets.all(AppDimensions.spacing16),
                itemCount: state.notifications.length,
                itemBuilder: (context, index) {
                  final notification = state.notifications[index];
                  return NotificationTile(
                    notification: notification,
                    onTap: () {
                      if (!notification.isRead) {
                        context
                            .read<NotificationBloc>()
                            .add(MarkAsReadEvent(id: notification.id));
                      }
                    },
                  );
                },
              ),
            );
          }
          // Initial load
          WidgetsBinding.instance.addPostFrameCallback((_) {
            context.read<NotificationBloc>().add(LoadNotificationsEvent());
          });
          return const LoadingIndicator();
        },
      ),
    );
  }
}
