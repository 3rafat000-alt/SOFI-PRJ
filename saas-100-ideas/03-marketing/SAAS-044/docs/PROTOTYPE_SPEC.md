# PROTOTYPE SPEC — ChatFlow (SAAS-044)
> Owner: UI/UX Designer · Gate 2

## Screen: Chat Widget (maps to Journey Stage: Open)
- **Layout:** Floating bubble (bottom-right), expands to chat panel, input at bottom, messages scroll
- **Components:** ChatBubble, MessageList, MessageInput, TypingIndicator, BotAvatar
- **States:**
  - Closed: Floating bubble with notification badge
  - Open: Chat panel with messages
  - Offline: "We're away — leave a message" form
  - Empty: Greeting message + quick reply chips
- **Key Interaction:** Click bubble → expand panel → type/select quick reply
- **Friction Resolved:** [#1] quick reply chips للأسئلة الشائعة

## Screen: Agent Workspace (maps to Journey Stage: Agent Responds)
- **Layout:** Left panel (conversation list with status badges), center (chat with customer), right (customer info + canned replies)
- **Components:** ConversationList, ChatPane, CustomerInfo, CannedReplyList, TransferButton
- **States:**
  - No conversations: "No active chats"
  - Waiting: "Customer is typing..."
  - Active: Messages flowing
  - Closed: "Conversation ended" with summary
- **Key Interaction:** Click conversation → see messages → type or select canned reply → send
- **Friction Resolved:** [#3] وصول لسياق العميل (الطلبات السابقة)

## Screen: Bot Training (maps to Journey Stage: Configure Bot)
- **Layout:** Q&A pairs list with search, add rule form, test bot panel
- **Components:** RuleList, RuleForm, TestPanel, IntentSelector, ImportButton
- **States:**
  - Empty: "No rules — the bot won't answer anything"
  - Loading: Rules loading skeleton
  - Error: "Failed to save rule"
  - Edge: 500+ rules — search + pagination
- **Key Interaction:** Add question → add answer → assign intent → save → test
- **Friction Resolved:** [#1] تدريب البوت على الأسئلة العربية

## Screen: Analytics Dashboard (maps to Journey Stage: Report)
- **Layout:** Metrics row (conversations, CSAT, avg response time, bot rate), trend charts
- **Components:** MetricCard, LineChart, BarChart, CSATGauge, AgentLeaderboard
- **States:**
  - Empty: "Start getting conversations to see analytics"
  - Loading: Chart skeletons
  - Error: "Analytics unavailable"
- **Key Interaction:** Hover chart for details, filter by date
- **Friction Resolved:** [#4] تقارير CSAT وأداء الفريق

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| ChatBubble | open/closed/notification | default, hover, badge count | Animated expand |
| MessageList | customer/agent/bot | scroll, new message auto-scroll | Animated message appear |
| MessageInput | text/attachment/emoji | default, disabled (offline), typing | Send on Enter |
| QuickReply | chip | default, hover, selected | Sends as message |
| CannedReply | short/long | default, click to copy | Category grouping |
| ConversationList | waiting/active/closed | selected, unread badge | Real-time update via WS |
| MetricCard | default, trend up/down | hover for detail | Animated counter |
