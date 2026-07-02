import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:iconsax/iconsax.dart';
import 'package:intl/intl.dart';

import '../../../../core/theme/app_colors.dart';
import '../../../../core/widgets/app_ui.dart';
import '../../data/models/chat_message_model.dart';
import '../controllers/chat_controller.dart';

/// In-app live chat with the support team. Polling transport (3s) — wired to
/// the real backend (`/chat/*`), replacing the old WhatsApp deep-link shim.
class ChatPage extends ConsumerStatefulWidget {
  const ChatPage({super.key});

  @override
  ConsumerState<ChatPage> createState() => _ChatPageState();
}

class _ChatPageState extends ConsumerState<ChatPage> {
  final _scroll = ScrollController();
  final _input = TextEditingController();

  @override
  void dispose() {
    _scroll.dispose();
    _input.dispose();
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

  Future<void> _send() async {
    final text = _input.text.trim();
    if (text.isEmpty) return;
    _input.clear();
    await ref.read(chatControllerProvider.notifier).send(text);
  }

  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    final state = ref.watch(chatControllerProvider);

    // Auto-scroll whenever the message count grows (new send or polled reply).
    ref.listen(chatControllerProvider, (prev, next) {
      if ((prev?.messages.length ?? 0) != next.messages.length) {
        _scrollToBottom();
      }
    });

    return AppScaffold(
      title: 'الدعم المباشر',
      subtitle: 'محادثة فورية مع فريق صكّ',
      body: _body(state, colors),
      bottomBar: _composer(state, colors),
    );
  }

  Widget _body(ChatState state, AppColorsTheme colors) {
    if (state.loading) {
      return const Center(child: CircularProgressIndicator());
    }
    if (state.error != null && state.messages.isEmpty) {
      return _ErrorView(
        message: state.error!,
        onRetry: () => ref.read(chatControllerProvider.notifier).load(),
      );
    }
    if (state.messages.isEmpty) {
      return _emptyState(colors);
    }

    return ListView.builder(
      controller: _scroll,
      padding: const EdgeInsets.fromLTRB(
          AppSpacing.lg, AppSpacing.lg, AppSpacing.lg, AppSpacing.md),
      itemCount: state.messages.length,
      itemBuilder: (_, i) => _bubble(state.messages[i], colors),
    );
  }

  Widget _emptyState(AppColorsTheme colors) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(AppSpacing.xxl),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 72,
              height: 72,
              decoration: BoxDecoration(
                  color: colors.primaryLight, shape: BoxShape.circle),
              child: Icon(Iconsax.messages_2, color: colors.primary, size: 34),
            ),
            const SizedBox(height: AppSpacing.lg),
            Text('ابدأ المحادثة مع فريق الدعم',
                style: TextStyle(
                    fontSize: 15,
                    fontWeight: FontWeight.w700,
                    color: colors.textPrimary)),
            const SizedBox(height: AppSpacing.xs),
            Text('اكتب رسالتك بالأسفل وسيردّ عليك أحد موظفينا',
                textAlign: TextAlign.center,
                style: TextStyle(fontSize: 12.5, color: colors.textSecondary)),
          ],
        ),
      ),
    );
  }

  Widget _bubble(ChatMessageModel m, AppColorsTheme colors) {
    if (m.isSystem) {
      return Padding(
        padding: const EdgeInsets.symmetric(vertical: AppSpacing.sm),
        child: Center(
          child: Container(
            padding: const EdgeInsets.symmetric(
                horizontal: AppSpacing.md, vertical: 6),
            decoration: BoxDecoration(
                color: colors.inputBackground,
                borderRadius: BorderRadius.circular(AppRadius.pill)),
            child: Text(m.body,
                style: TextStyle(fontSize: 11.5, color: colors.textSecondary)),
          ),
        ),
      );
    }

    final mine = m.isMine;
    final bg = mine ? colors.primary : colors.surface;
    final fg = mine ? Colors.white : colors.textPrimary;

    return Align(
      alignment:
          mine ? AlignmentDirectional.centerEnd : AlignmentDirectional.centerStart,
      child: Container(
        constraints: BoxConstraints(
            maxWidth: MediaQuery.of(context).size.width * 0.78),
        margin: const EdgeInsets.only(bottom: AppSpacing.sm),
        padding: const EdgeInsets.symmetric(
            horizontal: AppSpacing.md, vertical: AppSpacing.sm),
        decoration: BoxDecoration(
          color: bg,
          borderRadius: BorderRadius.only(
            topLeft: const Radius.circular(AppRadius.lg),
            topRight: const Radius.circular(AppRadius.lg),
            bottomLeft: Radius.circular(mine ? AppRadius.lg : 4),
            bottomRight: Radius.circular(mine ? 4 : AppRadius.lg),
          ),
          border: mine ? null : Border.all(color: colors.inputBackground),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(m.body,
                style: TextStyle(fontSize: 13.5, color: fg, height: 1.45)),
            const SizedBox(height: 3),
            Text(DateFormat('HH:mm').format(m.createdAt),
                style: TextStyle(
                    fontSize: 9.5,
                    color: mine
                        ? Colors.white.withValues(alpha: 0.75)
                        : colors.textHint)),
          ],
        ),
      ),
    );
  }

  Widget _composer(ChatState state, AppColorsTheme colors) {
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
              controller: _input,
              minLines: 1,
              maxLines: 4,
              textInputAction: TextInputAction.newline,
              decoration: InputDecoration(
                hintText: 'اكتب رسالتك…',
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
            onTap: state.sending ? null : _send,
            child: Container(
              width: 44,
              height: 44,
              decoration:
                  BoxDecoration(color: colors.primary, shape: BoxShape.circle),
              child: state.sending
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

class _ErrorView extends StatelessWidget {
  final String message;
  final VoidCallback onRetry;

  const _ErrorView({required this.message, required this.onRetry});

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
