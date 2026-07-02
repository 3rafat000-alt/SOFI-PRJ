import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../../../core/constants/app_colors.dart';
import '../../../../core/constants/app_dimensions.dart';
import '../../../../core/localization/app_localizations.dart';
import '../../../../shared/widgets/loading_indicator.dart';
import '../../../../shared/widgets/error_view.dart';
import '../../../../shared/widgets/empty_state_view.dart';
import '../bloc/workspace_bloc.dart';
import '../widgets/member_tile.dart';

class MembersPage extends StatefulWidget {
  final String workspaceId;

  const MembersPage({super.key, required this.workspaceId});

  @override
  State<MembersPage> createState() => _MembersPageState();
}

class _MembersPageState extends State<MembersPage> {
  final _inviteEmailController = TextEditingController();
  bool _showInviteForm = false;

  @override
  void initState() {
    super.initState();
    context.read<WorkspaceBloc>().add(LoadMembersEvent(
          workspaceId: widget.workspaceId,
        ));
  }

  @override
  void dispose() {
    _inviteEmailController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final localizations = AppLocalizations.of(context);
    final isArabic = localizations.isArabic;

    return Scaffold(
      appBar: AppBar(
        title: Text(localizations.members),
        actions: [
          IconButton(
            icon: const Icon(Icons.person_add),
            onPressed: () => setState(() => _showInviteForm = !_showInviteForm),
          ),
        ],
      ),
      body: Column(
        children: [
          // Invite form
          if (_showInviteForm)
            Container(
              padding: const EdgeInsets.all(AppDimensions.spacing16),
              color: AppColors.neutral50,
              child: Row(
                children: [
                  Expanded(
                    child: TextField(
                      controller: _inviteEmailController,
                      decoration: InputDecoration(
                        hintText: localizations.inviteMember,
                        isDense: true,
                      ),
                      keyboardType: TextInputType.emailAddress,
                      textDirection: isArabic ? TextDirection.rtl : TextDirection.ltr,
                    ),
                  ),
                  const SizedBox(width: AppDimensions.spacing8),
                  IconButton(
                    icon: const Icon(Icons.send),
                    color: AppColors.primary,
                    onPressed: _sendInvite,
                  ),
                ],
              ),
            ),

          // Members list
          Expanded(
            child: BlocBuilder<WorkspaceBloc, WorkspaceState>(
              builder: (context, state) {
                if (state is WorkspaceLoading) {
                  return const LoadingIndicator();
                }
                if (state is WorkspaceError) {
                  return ErrorView(
                    message: state.message,
                    onRetry: () => context
                        .read<WorkspaceBloc>()
                        .add(LoadMembersEvent(workspaceId: widget.workspaceId)),
                  );
                }
                // We use the bloc to track invited state
                return RefreshIndicator(
                  onRefresh: () async {
                    context
                        .read<WorkspaceBloc>()
                        .add(LoadMembersEvent(workspaceId: widget.workspaceId));
                  },
                  child: ListView(
                    padding: const EdgeInsets.all(AppDimensions.spacing16),
                    children: [
                      Text(
                        localizations.members,
                        style: Theme.of(context).textTheme.titleMedium,
                      ),
                      const SizedBox(height: AppDimensions.spacing12),
                      // Placeholder until we fetch members
                      EmptyStateView(
                        message: localizations.noMembers,
                        icon: Icons.people_outline,
                      ),
                    ],
                  ),
                );
              },
            ),
          ),
        ],
      ),
    );
  }

  void _sendInvite() {
    final email = _inviteEmailController.text.trim();
    if (email.isEmpty) return;
    context.read<WorkspaceBloc>().add(InviteMemberEvent(
          workspaceId: widget.workspaceId,
          email: email,
        ));
    _inviteEmailController.clear();
    setState(() => _showInviteForm = false);
  }
}
