import 'dart:async';

import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/network/api_client.dart';
import '../../data/models/chat_message_model.dart';
import '../../data/repositories/chat_repository.dart';

/// Immutable view-state for the live-chat screen.
class ChatState {
  final bool loading; // first load in flight
  final bool sending; // a send() in flight
  final String? error; // fatal load error (retryable)
  final ChatConversationModel? conversation;
  final List<ChatMessageModel> messages;

  const ChatState({
    this.loading = true,
    this.sending = false,
    this.error,
    this.conversation,
    this.messages = const [],
  });

  bool get isClosed => conversation?.isClosed ?? false;
  int get lastId => messages.isEmpty ? 0 : messages.last.id;

  ChatState copyWith({
    bool? loading,
    bool? sending,
    String? error,
    bool clearError = false,
    ChatConversationModel? conversation,
    List<ChatMessageModel>? messages,
  }) {
    return ChatState(
      loading: loading ?? this.loading,
      sending: sending ?? this.sending,
      error: clearError ? null : (error ?? this.error),
      conversation: conversation ?? this.conversation,
      messages: messages ?? this.messages,
    );
  }
}

/// Drives the live chat: first load, 3s polling for agent replies, and send.
/// Polling pauses while a send is in flight and resumes after, so we never
/// duplicate the just-sent message or race the server.
class ChatController extends StateNotifier<ChatState> {
  final ChatRepository _repo;
  Timer? _poll;

  static const _interval = Duration(seconds: 3);

  ChatController(this._repo) : super(const ChatState()) {
    load();
  }

  Future<void> load() async {
    state = state.copyWith(loading: true, clearError: true);
    try {
      final thread = await _repo.openConversation();
      state = state.copyWith(
        loading: false,
        conversation: thread.conversation,
        messages: thread.messages,
      );
      _startPolling();
    } on ApiException catch (e) {
      state = state.copyWith(loading: false, error: e.message);
    }
  }

  void _startPolling() {
    _poll?.cancel();
    _poll = Timer.periodic(_interval, (_) => _tick());
  }

  Future<void> _tick() async {
    if (state.sending || state.loading) return;
    try {
      final fresh = await _repo.pollMessages(afterId: state.lastId);
      if (fresh.isNotEmpty) {
        state = state.copyWith(messages: [...state.messages, ...fresh]);
      }
    } on ApiException {
      // Transient poll failure is non-fatal — keep the thread, retry next tick.
    }
  }

  /// Append the user's message optimistically? No — POST is fast and the
  /// server assigns the id we poll against, so we just append the persisted row.
  Future<void> send(String body) async {
    final text = body.trim();
    if (text.isEmpty || state.sending) return;

    state = state.copyWith(sending: true, clearError: true);
    try {
      final msg = await _repo.send(text);
      // Reopen locally if the thread had been closed (server already did).
      final conv = state.conversation != null && state.conversation!.isClosed
          ? ChatConversationModel(
              id: state.conversation!.id,
              status: 'open',
              subject: state.conversation!.subject,
              lastMessageAt: msg.createdAt,
            )
          : state.conversation;
      state = state.copyWith(
        sending: false,
        conversation: conv,
        messages: [...state.messages, msg],
      );
    } on ApiException catch (e) {
      state = state.copyWith(sending: false, error: e.message);
    }
  }

  @override
  void dispose() {
    _poll?.cancel();
    super.dispose();
  }
}

/// Auto-disposed so leaving the chat screen cancels the polling timer.
final chatControllerProvider =
    StateNotifierProvider.autoDispose<ChatController, ChatState>((ref) {
  return ChatController(ref.read(chatRepositoryProvider));
});
