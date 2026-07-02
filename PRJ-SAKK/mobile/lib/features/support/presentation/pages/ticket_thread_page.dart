import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:iconsax/iconsax.dart';
import 'package:intl/intl.dart';

import '../../../../core/network/api_client.dart';
import '../../../../core/theme/app_colors.dart';
import '../../../../core/widgets/app_ui.dart';
import '../../data/models/support_models.dart';
import '../../data/repositories/support_repository.dart';

/// One ticket and its public thread. The customer can reply, which reopens a
/// resolved/closed ticket server-side.
class TicketThreadPage extends ConsumerStatefulWidget {
  final String uuid;

  const TicketThreadPage({super.key, required this.uuid});

  @override
  ConsumerState<TicketThreadPage> createState() => _TicketThreadPageState();
}

class _TicketThreadPageState extends ConsumerState<TicketThreadPage> {
  final _reply = TextEditingController();
  final _scroll = ScrollController();
  Timer? _poll;
  bool _sending = false;

  // Poll for support replies so a resolved answer appears without reopening.
  static const _pollInterval = Duration(seconds: 8);

  @override
  void initState() {
    super.initState();
    _poll = Timer.periodic(_pollInterval, (_) {
      if (mounted && !_sending) ref.invalidate(ticketDetailProvider(widget.uuid));
    });
  }

  @override
  void dispose() {
    _poll?.cancel();
    _reply.dispose();
    _scroll.dispose();
    super.dispose();
  }

  void _scrollToBottom() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (_scroll.hasClients) {
        _scroll.animateTo(
          _scroll.position.maxScrollExtent,
          duration: const Duration(milliseconds: 220),
          curve: Curves.easeOut,
        );
      }
    });
  }

  Future<void> _refresh() async {
    ref.invalidate(ticketDetailProvider(widget.uuid));
    await ref.read(ticketDetailProvider(widget.uuid).future);
  }

  Future<void> _send() async {
    final text = _reply.text.trim();
    if (text.isEmpty || _sending) return;
    setState(() => _sending = true);
    try {
      await ref.read(supportRepositoryProvider).reply(widget.uuid, text);
      _reply.clear();
      ref.invalidate(ticketDetailProvider(widget.uuid));
      ref.invalidate(ticketsProvider);
    } on ApiException catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(
          content: Text(e.message),
          behavior: SnackBarBehavior.floating,
          backgroundColor: context.appColors.error,
        ));
      }
    } finally {
      if (mounted) setState(() => _sending = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    final detail = ref.watch(ticketDetailProvider(widget.uuid));

    // Auto-scroll to the newest bubble whenever a reply lands (poll or send).
    ref.listen(ticketDetailProvider(widget.uuid), (prev, next) {
      final before = prev?.valueOrNull?.messages.length ?? 0;
      final after = next.valueOrNull?.messages.length ?? 0;
      if (after > before) _scrollToBottom();
    });

    return AppScaffold(
      title: 'تذكرة دعم',
      subtitle: detail.valueOrNull?.ticketNumber,
      onRefresh: _refresh,
      body: detail.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => Center(
          child: Padding(
            padding: const EdgeInsets.all(AppSpacing.xxl),
            child: Text(e.toString(),
                textAlign: TextAlign.center,
                style: TextStyle(color: colors.textSecondary)),
          ),
        ),
        data: (t) => _thread(t, colors),
      ),
      bottomBar: detail.maybeWhen(
        data: (t) => _composer(colors, t.isOpenForReply),
        orElse: () => null,
      ),
    );
  }

  Widget _thread(SupportTicketModel t, AppColorsTheme colors) {
    final style = TicketStatusStyle.of(t.status);
    final statusColor = style.color(context);

    return ListView(
      controller: _scroll,
      padding: const EdgeInsets.fromLTRB(
          AppSpacing.lg, AppSpacing.lg, AppSpacing.lg, AppSpacing.md),
      children: [
        // Header card: subject, status, category, opened date.
        AppCard(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Expanded(
                    child: Text(t.subject,
                        style: TextStyle(
                            fontSize: 15,
                            fontWeight: FontWeight.w800,
                            color: colors.textPrimary)),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(
                        horizontal: 10, vertical: 5),
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
              const SizedBox(height: AppSpacing.sm),
              Row(children: [
                Icon(ticketCategoryIcon(t.category),
                    size: 15, color: colors.textHint),
                const SizedBox(width: 6),
                Text(
                    t.createdAt != null
                        ? DateFormat('y/MM/dd · HH:mm').format(t.createdAt!)
                        : '',
                    style:
                        TextStyle(fontSize: 11.5, color: colors.textSecondary)),
              ]),
            ],
          ),
        ),
        const SizedBox(height: AppSpacing.md),
        ...t.messages.map((m) => _bubble(m, colors)),
        if (!t.isOpenForReply)
          Padding(
            padding: const EdgeInsets.only(top: AppSpacing.md),
            child: Center(
              child: Text('هذه التذكرة مغلقة',
                  style: TextStyle(fontSize: 12, color: colors.textHint)),
            ),
          ),
      ],
    );
  }

  Widget _bubble(TicketMessageModel m, AppColorsTheme colors) {
    final mine = m.isMine;
    final bg = mine ? colors.primary : colors.surface;
    final fg = mine ? Colors.white : colors.textPrimary;

    return Align(
      alignment:
          mine ? AlignmentDirectional.centerEnd : AlignmentDirectional.centerStart,
      child: Container(
        constraints:
            BoxConstraints(maxWidth: MediaQuery.of(context).size.width * 0.8),
        margin: const EdgeInsets.only(bottom: AppSpacing.sm),
        padding: const EdgeInsets.symmetric(
            horizontal: AppSpacing.md, vertical: AppSpacing.sm),
        decoration: BoxDecoration(
          color: bg,
          borderRadius: BorderRadius.circular(AppRadius.md),
          border: mine ? null : Border.all(color: colors.inputBackground),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(mine ? 'أنت' : 'الدعم الفني',
                style: TextStyle(
                    fontSize: 10.5,
                    fontWeight: FontWeight.w700,
                    color: mine
                        ? Colors.white.withValues(alpha: 0.85)
                        : colors.primary)),
            const SizedBox(height: 3),
            Text(m.message,
                style: TextStyle(fontSize: 13.5, color: fg, height: 1.45)),
            const SizedBox(height: 3),
            Text(DateFormat('y/MM/dd HH:mm').format(m.createdAt),
                style: TextStyle(
                    fontSize: 9.5,
                    color: mine
                        ? Colors.white.withValues(alpha: 0.7)
                        : colors.textHint)),
          ],
        ),
      ),
    );
  }

  Widget _composer(AppColorsTheme colors, bool open) {
    return Container(
      padding: const EdgeInsets.fromLTRB(
          AppSpacing.md, AppSpacing.sm, AppSpacing.md, AppSpacing.sm),
      decoration: BoxDecoration(
        color: colors.surface,
        border: Border(top: BorderSide(color: colors.inputBackground)),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.end,
        children: [
          Expanded(
            child: TextField(
              controller: _reply,
              minLines: 1,
              maxLines: 4,
              decoration: InputDecoration(
                hintText: open ? 'اكتب ردّك…' : 'أعد فتح التذكرة بالرد…',
                filled: true,
                fillColor: colors.inputBackground,
                contentPadding: const EdgeInsets.symmetric(
                    horizontal: AppSpacing.md, vertical: 10),
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(AppRadius.lg),
                  borderSide: BorderSide.none,
                ),
              ),
            ),
          ),
          const SizedBox(width: AppSpacing.sm),
          GestureDetector(
            onTap: _sending ? null : _send,
            child: Container(
              width: 44,
              height: 44,
              decoration:
                  BoxDecoration(color: colors.primary, shape: BoxShape.circle),
              child: _sending
                  ? const Padding(
                      padding: EdgeInsets.all(12),
                      child: CircularProgressIndicator(
                          strokeWidth: 2, color: Colors.white),
                    )
                  : const Icon(Iconsax.send_1, color: Colors.white, size: 20),
            ),
          ),
        ],
      ),
    );
  }
}
