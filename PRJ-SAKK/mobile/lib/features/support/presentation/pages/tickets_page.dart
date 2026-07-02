import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:iconsax/iconsax.dart';
import 'package:intl/intl.dart';

import '../../../../core/theme/app_colors.dart';
import '../../../../core/widgets/app_ui.dart';
import '../../data/models/support_models.dart';
import '../../data/repositories/support_repository.dart';

/// The customer's support tickets — list, refresh, open one, or create a new one.
class TicketsPage extends ConsumerWidget {
  const TicketsPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final colors = context.appColors;
    final tickets = ref.watch(ticketsProvider);

    return AppScaffold(
      title: 'تذاكر الدعم',
      subtitle: 'متابعة طلبات الدعم الخاصة بك',
      onRefresh: () => ref.refresh(ticketsProvider.future),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => context.push('/support-tickets/new'),
        backgroundColor: colors.primary,
        foregroundColor: Colors.white,
        icon: const Icon(Iconsax.add, size: 20),
        label: const Text('تذكرة جديدة',
            style: TextStyle(fontWeight: FontWeight.w700)),
      ),
      body: tickets.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => _Error(
          message: e.toString(),
          onRetry: () => ref.invalidate(ticketsProvider),
        ),
        data: (items) => items.isEmpty
            ? _empty(colors)
            : ListView.builder(
                padding: const EdgeInsets.fromLTRB(AppSpacing.lg, AppSpacing.lg,
                    AppSpacing.lg, AppSpacing.xxxl * 2),
                itemCount: items.length,
                itemBuilder: (_, i) => _TicketRow(ticket: items[i]),
              ),
      ),
    );
  }

  Widget _empty(AppColorsTheme colors) {
    return ListView(
      // ListView keeps pull-to-refresh working on an empty list.
      children: [
        const SizedBox(height: 120),
        Icon(Iconsax.ticket, size: 56, color: colors.textHint),
        const SizedBox(height: AppSpacing.lg),
        Center(
          child: Text('لا توجد تذاكر بعد',
              style: TextStyle(
                  fontSize: 15,
                  fontWeight: FontWeight.w700,
                  color: colors.textPrimary)),
        ),
        const SizedBox(height: AppSpacing.xs),
        Center(
          child: Text('افتح تذكرة جديدة وسيردّ عليك فريق الدعم',
              style: TextStyle(fontSize: 12.5, color: colors.textSecondary)),
        ),
      ],
    );
  }
}

class _TicketRow extends StatelessWidget {
  final SupportTicketModel ticket;

  const _TicketRow({required this.ticket});

  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    final style = TicketStatusStyle.of(ticket.status);
    final statusColor = style.color(context);

    return AppCard(
      margin: const EdgeInsets.only(bottom: AppSpacing.md),
      onTap: () => context.push('/support-tickets/${ticket.uuid}'),
      child: Row(
        children: [
          Container(
            width: 44,
            height: 44,
            decoration: BoxDecoration(
                color: colors.primaryLight,
                borderRadius: BorderRadius.circular(AppRadius.md)),
            child: Icon(ticketCategoryIcon(ticket.category),
                color: colors.primary, size: 22),
          ),
          const SizedBox(width: AppSpacing.md),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(ticket.subject,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.w700,
                        color: colors.textPrimary)),
                const SizedBox(height: 3),
                Text(
                    '${ticket.ticketNumber} · ${ticket.updatedAt != null ? DateFormat('y/MM/dd').format(ticket.updatedAt!) : ''}',
                    style:
                        TextStyle(fontSize: 11.5, color: colors.textSecondary)),
              ],
            ),
          ),
          const SizedBox(width: AppSpacing.sm),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
            decoration: BoxDecoration(
                color: statusColor.withValues(alpha: 0.12),
                borderRadius: BorderRadius.circular(AppRadius.pill)),
            child: Text(style.label,
                style: TextStyle(
                    fontSize: 11,
                    fontWeight: FontWeight.w700,
                    color: statusColor)),
          ),
        ],
      ),
    );
  }
}

class _Error extends StatelessWidget {
  final String message;
  final VoidCallback onRetry;

  const _Error({required this.message, required this.onRetry});

  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(AppSpacing.xxl),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(Iconsax.warning_2, color: colors.error, size: 40),
            const SizedBox(height: AppSpacing.md),
            Text(message,
                textAlign: TextAlign.center,
                style: TextStyle(fontSize: 13, color: colors.textSecondary)),
            const SizedBox(height: AppSpacing.lg),
            AppButton(label: 'إعادة المحاولة', onPressed: onRetry),
          ],
        ),
      ),
    );
  }
}
