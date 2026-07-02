// Live-chat models (polling transport). Mirror the customer-facing
// `API\ChatController` JSON shape exactly.

/// One message in a conversation. `senderType` is one of: user | agent | system.
class ChatMessageModel {
  final int id;
  final String senderType;
  final String body;
  final DateTime createdAt;

  const ChatMessageModel({
    required this.id,
    required this.senderType,
    required this.body,
    required this.createdAt,
  });

  bool get isMine => senderType == 'user';
  bool get isSystem => senderType == 'system';

  factory ChatMessageModel.fromJson(Map<String, dynamic> json) {
    return ChatMessageModel(
      id: json['id'] as int,
      senderType: (json['sender_type'] ?? 'agent') as String,
      body: (json['body'] ?? '') as String,
      createdAt: DateTime.tryParse((json['created_at'] ?? '') as String)?.toLocal() ??
          DateTime.now(),
    );
  }
}

/// The caller's single open conversation.
class ChatConversationModel {
  final int id;
  final String status; // open | closed
  final String? subject;
  final DateTime? lastMessageAt;

  const ChatConversationModel({
    required this.id,
    required this.status,
    this.subject,
    this.lastMessageAt,
  });

  bool get isClosed => status == 'closed';

  factory ChatConversationModel.fromJson(Map<String, dynamic> json) {
    return ChatConversationModel(
      id: json['id'] as int,
      status: (json['status'] ?? 'open') as String,
      subject: json['subject'] as String?,
      lastMessageAt: json['last_message_at'] != null
          ? DateTime.tryParse(json['last_message_at'] as String)?.toLocal()
          : null,
    );
  }
}

/// Bundle returned by GET /chat/conversation.
class ChatThread {
  final ChatConversationModel conversation;
  final List<ChatMessageModel> messages;

  const ChatThread({required this.conversation, required this.messages});
}
