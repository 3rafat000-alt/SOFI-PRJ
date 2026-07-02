import { useState, useEffect, useRef, useCallback } from 'react';
import { useTranslation } from 'react-i18next';
import { useSearchParams } from 'react-router-dom';
import {
  MessageSquare, Send, Loader2, ChevronLeft, Building2,
  CheckCheck, Search, Paperclip, FileText, X, Building,
  Archive, Trash2, RotateCcw, AlertTriangle, Inbox,
  ArchiveRestore, Clock, HandCoins, DollarSign,
  ThumbsUp, ThumbsDown, Scale, ReplyAll
} from 'lucide-react';
import {
  startConversation,
  fetchUserConversationsByTab,
  fetchConversationMessages,
  sendChatMessage,
  markConversationRead,
  fetchUserChatUnread,
  archiveConversation,
  unarchiveConversation,
  trashConversation,
  restoreConversation,
  forceDeleteConversation,
  sendOffer,
  acceptOffer,
  rejectOffer,
  counterOffer,
  type ChatConversation,
  type ChatMessage,
} from '../../api/user';
import echo from '../../echo';

type Tab = 'inbox' | 'archived' | 'trash';

const USER_QUICK_REPLIES = [
  { ar: 'أنا مهتم بهذا العقار، أرجو إرسال التفاصيل', en: 'I\'m interested in this property, please send details' },
  { ar: 'ممكن نحدد موعد للمعاينة؟', en: 'Can we schedule a viewing?' },
  { ar: 'هل السعر قابل للتفاوض؟', en: 'Is the price negotiable?' },
  { ar: 'أحتاج معلومات إضافية عن العقار', en: 'I need more information about this property' },
  { ar: 'شكراً لكم، سنكون على تواصل', en: 'Thank you, we\'ll stay in touch' },
] as const;

const TABS: { key: Tab; labelAr: string; labelEn: string; icon: typeof Inbox }[] = [
  { key: 'inbox',    labelAr: 'الوارد',    labelEn: 'Inbox',    icon: Inbox },
  { key: 'archived', labelAr: 'المؤرشفة',  labelEn: 'Archived', icon: Archive },
  { key: 'trash',    labelAr: 'المحذوفة',  labelEn: 'Trash',    icon: Trash2 },
];

export default function UserChat() {
  const { i18n } = useTranslation();
  const isAr = i18n.language === 'ar';
  const L = (ar: string, en: string) => isAr ? ar : en;
  const [searchParams] = useSearchParams();

  const [tab, setTab] = useState<Tab>('inbox');
  const [conversations, setConversations] = useState<ChatConversation[]>([]);
  const [selected, setSelected] = useState<ChatConversation | null>(null);
  const [messages, setMessages] = useState<ChatMessage[]>([]);
  const [newMsg, setNewMsg] = useState('');
  const [loading, setLoading] = useState(true);
  const [sending, setSending] = useState(false);
  const [loadingMsgs, setLoadingMsgs] = useState(false);
  const [convPage, setConvPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [unreadCount, setUnreadCount] = useState(0);
  const [searchQuery, setSearchQuery] = useState('');
  const [attachment, setAttachment] = useState<File | null>(null);
  const [attachmentPreview, setAttachmentPreview] = useState<string | null>(null);
  const [actionLoading, setActionLoading] = useState<number | null>(null);
  const [confirmDelete, setConfirmDelete] = useState<number | null>(null);
  const [_startingConv, _setStartingConv] = useState(false);
  const startedRef = useRef(false);
  // Offer / negotiation
  const [showOfferModal, setShowOfferModal] = useState(false);
  const [offerAmount, setOfferAmount] = useState('');
  const [offerCurrency, setOfferCurrency] = useState('USD');
  const [offerNote, setOfferNote] = useState('');
  const [offerLoading, setOfferLoading] = useState(false);
  const [actionMsgId, setActionMsgId] = useState<number | null>(null);
  // Quick replies
  const [showQuickReplies, setShowQuickReplies] = useState(false);
  const [sendError, setSendError] = useState<string | null>(null);
  const messagesEnd = useRef<HTMLDivElement>(null);
  const fileInputRef = useRef<HTMLInputElement>(null);
  const selectedRef = useRef<ChatConversation | null>(null);

  const filteredConversations = searchQuery
    ? conversations.filter(c => {
        const nameMatch = (c.agency?.name || '').toLowerCase().includes(searchQuery.toLowerCase());
        const titleMatch = c.property && (
          (c.property.title_ar || '').includes(searchQuery) ||
          (c.property.title_en || '').toLowerCase().includes(searchQuery.toLowerCase())
        );
        return nameMatch || titleMatch;
      })
    : conversations;

  const loadConversations = (p = 1) => {
    setLoading(true);
    fetchUserConversationsByTab(tab, p).then(r => {
      setConversations(prev => p === 1 ? r.data : [...prev, ...r.data]);
      setLastPage(r.meta?.last_page ?? 1);
    }).catch(err => { console.error('Failed to load conversations', err); }).finally(() => setLoading(false));
  };

  // Auto-start conversation from query params (e.g. ?agencyId=5&propertyId=3)
  useEffect(() => {
    if (startedRef.current) return;
    startedRef.current = true;
    const agencyId = searchParams.get('agencyId');
    if (!agencyId) return;
    _setStartingConv(true);
    const propertyId = searchParams.get('propertyId') || undefined;
    startConversation({
      agency_id: Number(agencyId),
      property_id: propertyId ? Number(propertyId) : undefined,
      message: L('مرحباً، أنا مهتم بعقاركم', 'Hello, I am interested in your property'),
    }).then((res) => {
      const conv = res.data.conversation ?? res.data;
      loadConversations(1);
      // Select the new conversation after a short delay to let list populate
      setTimeout(() => {
        setSelected(conv);
        loadMessages(conv);
        _setStartingConv(false);
      }, 300);
    }).catch((err) => {
      console.error('Failed to start conversation', err);
      _setStartingConv(false);
    });
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  // Reload when tab changes
  useEffect(() => {
    setConvPage(1);
    if (!_startingConv) setSelected(null);
    loadConversations(1);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [tab]);

  const loadMessages = (conv: ChatConversation) => {
    setSelected(conv);
    selectedRef.current = conv;
    setLoadingMsgs(true);
    setMessages([]);
    fetchConversationMessages(conv.id).then(r => {
      setMessages(r.data ?? []);
    }).catch(err => { console.error('Failed to load messages', err); }).finally(() => setLoadingMsgs(false));
    if (tab === 'inbox') {
      markConversationRead(conv.id).catch(() => {});
    }
  };

  const handleAction = async (action: () => Promise<any>, convId: number) => {
    setActionLoading(convId);
    try {
      await action();
      // Remove from current list
      setConversations(prev => prev.filter(c => c.id !== convId));
      if (selected?.id === convId) setSelected(null);
      setConfirmDelete(null);
    } catch (err) {
      console.error('Action failed', err);
    } finally {
      setActionLoading(null);
    }
  };

  const markRead = useCallback((convId: number) => {
    markConversationRead(convId).catch(() => {});
    setConversations(prev => {
      let totalDelta = 0;
      const updated = prev.map(c => {
        if (c.id === convId) {
          totalDelta = c.unread_agency_messages_count ?? 0;
          return { ...c, unread_agency_messages_count: 0 };
        }
        return c;
      });
      setUnreadCount(u => Math.max(0, u - totalDelta));
      return updated;
    });
  }, []);

  const handleSend = async () => {
    if ((!newMsg.trim() && !attachment) || !selected || sending) return;
    setSending(true);
    setSendError(null);
    try {
      const res = await sendChatMessage(selected.id, newMsg.trim(), attachment || undefined);
      setMessages(prev => [...prev, res.data]);
      setNewMsg('');
      setAttachment(null);
      setAttachmentPreview(null);
    } catch (err) {
      console.error('Failed to send message', err);
      setSendError(L('فشل إرسال الرسالة', 'Failed to send message'));
    } finally {
      setSending(false);
    }
  };

  // Revoke object URL on cleanup to avoid memory leak
  useEffect(() => {
    return () => {
      if (attachmentPreview) URL.revokeObjectURL(attachmentPreview);
    };
  }, [attachmentPreview]);

  const handleAttachmentSelect = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;
    // Revoke previous preview
    if (attachmentPreview) URL.revokeObjectURL(attachmentPreview);
    setAttachment(file);
    if (file.type.startsWith('image/')) {
      setAttachmentPreview(URL.createObjectURL(file));
    } else {
      setAttachmentPreview(null);
    }
    if (fileInputRef.current) fileInputRef.current.value = '';
  };

  // Listen for new messages via WebSocket
  useEffect(() => {
    if (!selected || tab !== 'inbox') return;
    const convId = selected.id;

    fetchConversationMessages(convId).then(r => setMessages(r.data ?? [])).catch(() => {});
    const channel = echo.channel(`conversation.${convId}`);
    channel.listen('.new-message', (e: any) => {
      if (e.senderType !== 'user') {
        fetchConversationMessages(convId).then(r => {
          setMessages(r.data ?? []);
          markRead(convId);
        }).catch(() => {});
      }
    });

    return () => {
      channel.stopListening('.new-message');
      echo.leaveChannel(`conversation.${convId}`);
    };
  }, [selected?.id, markRead, tab]);

  // Fallback: poll unread count every 30s
  useEffect(() => {
    const fetchUnread = () => {
      fetchUserChatUnread().then(r => {
        setUnreadCount(r.data?.unread_count ?? 0);
      }).catch(() => {});
    };
    fetchUnread();
    const iv = setInterval(fetchUnread, 30000);
    return () => clearInterval(iv);
  }, []);

  useEffect(() => { messagesEnd.current?.scrollIntoView({ behavior: 'smooth' }); }, [messages]);

  const formatTime = (dateStr: string) => {
    const d = new Date(dateStr);
    const now = new Date();
    const diff = now.getTime() - d.getTime();
    if (diff < 86400000) return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    if (diff < 604800000) return d.toLocaleDateString([], { weekday: 'short' });
    return d.toLocaleDateString([], { day: 'numeric', month: 'short' });
  };

  const isImageFile = (type: string | null) => type?.startsWith('image/');
  const formatFileSize = (bytes: number | null) => {
    if (!bytes) return '';
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1048576) return `${(bytes / 1024).toFixed(0)} KB`;
    return `${(bytes / 1048576).toFixed(1)} MB`;
  };

  // ─── Offer action handlers ───
  const handleOfferAction = async (action: 'accept' | 'reject' | 'counter', msg: ChatMessage) => {
    if (!selected) return;
    setActionMsgId(msg.id);
    try {
      if (action === 'accept') {
        await acceptOffer(selected.id, msg.id);
        // Refresh messages to show updated status
        const res = await fetchConversationMessages(selected.id);
        setMessages(res.data ?? []);
      } else if (action === 'counter') {
        setOfferAmount('');
        setOfferCurrency('USD');
        setOfferNote('');
        setShowOfferModal(true);
        setActionMsgId(msg.id);
        return; // Don't clear actionMsgId yet
      } else {
        await rejectOffer(selected.id, msg.id);
        const res = await fetchConversationMessages(selected.id);
        setMessages(res.data ?? []);
      }
    } catch (err) {
      console.error('Offer action failed', err);
    } finally {
      if (action !== 'counter') setActionMsgId(null);
    }
  };

  const handleSendOffer = async () => {
    if (!selected || !offerAmount || offerLoading) return;
    setOfferLoading(true);
    try {
      if (actionMsgId) {
        // This is a counter offer
        await counterOffer(selected.id, actionMsgId, {
          amount: parseFloat(offerAmount),
          currency: offerCurrency,
          note: offerNote || undefined,
        });
      } else {
        // New offer
        await sendOffer(selected.id, {
          amount: parseFloat(offerAmount),
          currency: offerCurrency,
          note: offerNote || undefined,
        });
      }
      setShowOfferModal(false);
      setOfferAmount('');
      setOfferNote('');
      setActionMsgId(null);
      // Refresh messages
      const res = await fetchConversationMessages(selected.id);
      setMessages(res.data ?? []);
    } catch (err) {
      console.error('Failed to send offer', err);
    } finally {
      setOfferLoading(false);
    }
  };

  // ─── Quick reply handler ───
  const sendQuickReply = async (text: string) => {
    if (!selected) return;
    setNewMsg(text);
    setShowQuickReplies(false);
    // Auto-send after brief delay so user sees text appear
    setTimeout(() => {
      if (text.trim()) {
        setSending(true);
        sendChatMessage(selected.id, text, undefined).then(() => {
          setNewMsg('');
          return fetchConversationMessages(selected.id);
        }).then(res => {
          setMessages(res.data ?? []);
        }).catch(console.error).finally(() => setSending(false));
      }
    }, 100);
  };

  // ─── Action buttons per tab ───
  const renderActions = (conv: ChatConversation) => {
    const loading = actionLoading === conv.id;
    if (loading) return <Loader2 className="w-4 h-4 animate-spin text-primary" />;

    if (tab === 'inbox') {
      return (
        <div className="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
          <button onClick={e => { e.stopPropagation(); handleAction(() => archiveConversation(conv.id), conv.id); }}
            title={L('أرشفة', 'Archive')}
            className="p-1.5 rounded-lg hover:bg-amber-100 text-stone-400 hover:text-amber-600 transition-all">
            <Archive className="w-4 h-4" />
          </button>
          <button onClick={e => { e.stopPropagation(); handleAction(() => trashConversation(conv.id), conv.id); }}
            title={L('حذف', 'Delete')}
            className="p-1.5 rounded-lg hover:bg-red-100 text-stone-400 hover:text-red-500 transition-all">
            <Trash2 className="w-4 h-4" />
          </button>
        </div>
      );
    }

    if (tab === 'archived') {
      return (
        <div className="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
          <button onClick={e => { e.stopPropagation(); handleAction(() => unarchiveConversation(conv.id), conv.id); }}
            title={L('إعادة للوارد', 'Move to inbox')}
            className="p-1.5 rounded-lg hover:bg-blue-100 text-stone-400 hover:text-blue-600 transition-all">
            <ArchiveRestore className="w-4 h-4" />
          </button>
          <button onClick={e => { e.stopPropagation(); handleAction(() => trashConversation(conv.id), conv.id); }}
            title={L('حذف', 'Delete')}
            className="p-1.5 rounded-lg hover:bg-red-100 text-stone-400 hover:text-red-500 transition-all">
            <Trash2 className="w-4 h-4" />
          </button>
        </div>
      );
    }

    // Trash
    return (
      <div className="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
        <button onClick={e => { e.stopPropagation(); handleAction(() => restoreConversation(conv.id), conv.id); }}
          title={L('استعادة', 'Restore')}
          className="p-1.5 rounded-lg hover:bg-green-100 text-stone-400 hover:text-green-600 transition-all">
          <RotateCcw className="w-4 h-4" />
        </button>
        {confirmDelete === conv.id ? (
          <div className="flex items-center gap-1" onClick={e => e.stopPropagation()}>
            <button onClick={() => handleAction(() => forceDeleteConversation(conv.id), conv.id)}
              className="p-1.5 rounded-lg bg-red-500 text-white hover:bg-red-600 transition-all text-xs font-bold px-2">
              {L('تأكيد', 'Confirm')}
            </button>
            <button onClick={() => setConfirmDelete(null)}
              className="p-1.5 rounded-lg hover:bg-stone-200 text-stone-500 transition-all">
              <X className="w-3.5 h-3.5" />
            </button>
          </div>
        ) : (
          <button onClick={e => { e.stopPropagation(); setConfirmDelete(conv.id); }}
            title={L('حذف نهائي', 'Delete permanently')}
            className="p-1.5 rounded-lg hover:bg-red-100 text-stone-400 hover:text-red-600 transition-all">
            <AlertTriangle className="w-4 h-4" />
          </button>
        )}
      </div>
    );
  };

  return (
    <div className="flex flex-1 h-full bg-white">
      {/* ─── Conversations Sidebar ─── */}
      <div className={`w-80 lg:w-96 border-s border-beige-dark/20 bg-white flex flex-col ${selected ? 'hidden md:flex' : 'flex'}`}>
        {/* ── Header with tabs ── */}
        <div className="border-b border-beige-dark/20 bg-white">
          <div className="p-4 pb-3">
            <div className="flex items-center justify-between mb-3">
              <h2 className="font-bold text-stone-900 flex items-center gap-2">
                <MessageSquare className="w-5 h-5 text-primary" />
                {L('المحادثات', 'Messages')}
              </h2>
              {tab === 'inbox' && unreadCount > 0 && (
                <span className="bg-red-500 text-white text-xs font-bold rounded-full min-w-[20px] h-5 flex items-center justify-center px-1 shadow-sm">
                  {unreadCount > 99 ? '99+' : unreadCount}
                </span>
              )}
            </div>
            <div className="relative">
              <Search className="absolute end-3 top-1/2 -translate-y-1/2 w-4 h-4 text-stone-300" />
              <input value={searchQuery} onChange={e => setSearchQuery(e.target.value)}
                placeholder={L('بحث في المحادثات...', 'Search conversations...')}
                className="w-full bg-beige/50 rounded-xl py-2 pe-10 ps-3 text-sm outline-none focus:ring-2 focus:ring-primary/20 transition-all"
                dir={isAr ? 'rtl' : 'ltr'} />
            </div>
          </div>

          {/* ── Tab bar ── */}
          <div className="flex px-4 gap-1 pb-0">
            {TABS.map(t => {
              const Icon = t.icon;
              const isActive = tab === t.key;
              return (
                <button key={t.key} onClick={() => setTab(t.key)}
                  className={`flex items-center gap-1.5 px-3 py-2 text-xs font-medium rounded-t-lg transition-all border-b-2
                    ${isActive
                      ? 'text-primary border-primary bg-primary/5'
                      : 'text-stone-400 border-transparent hover:text-stone-600 hover:bg-stone-50'}`}>
                  <Icon className="w-3.5 h-3.5" />
                  <span>{L(t.labelAr, t.labelEn)}</span>
                </button>
              );
            })}
          </div>
        </div>

        {/* ── Conversation list ── */}
        <div className="flex-1 overflow-y-auto">
          {loading && conversations.length === 0 ? (
            <div className="flex items-center justify-center h-32"><Loader2 className="w-6 h-6 animate-spin text-primary" /></div>
          ) : filteredConversations.length === 0 ? (
            <div className="text-center py-16 px-4">
              {tab === 'inbox' && <Inbox className="w-12 h-12 text-stone-200 mx-auto mb-3" />}
              {tab === 'archived' && <Archive className="w-12 h-12 text-stone-200 mx-auto mb-3" />}
              {tab === 'trash' && <Trash2 className="w-12 h-12 text-stone-200 mx-auto mb-3" />}
              <p className="text-sm text-stone-500 font-medium">
                {searchQuery ? L('لا توجد نتائج', 'No results') :
                  tab === 'inbox' ? L('صندوق الوارد فارغ', 'Inbox is empty') :
                  tab === 'archived' ? L('لا توجد محادثات مؤرشفة', 'No archived conversations') :
                  L('سلة المحذوفات فارغة', 'Trash is empty')}
              </p>
              <p className="text-xs text-stone-400 mt-1">
                {!searchQuery && tab === 'inbox' && L('تواصل مع وكالة عقارية من صفحة العقار', 'Contact an agency from a property page')}
                {!searchQuery && tab === 'archived' && L('المحادثات المؤرشفة تظهر هنا', 'Archived conversations appear here')}
                {!searchQuery && tab === 'trash' && L('المحادثات المحذوفة تظهر هنا', 'Deleted conversations appear here')}
              </p>
            </div>
          ) : (
            <>
              {filteredConversations.map(c => {
                const unread = tab === 'inbox' ? (c.unread_agency_messages_count ?? 0) : 0;
                const isArchived = tab === 'archived';
                const isTrash = tab === 'trash';
                return (
                  <div key={c.id} className="group relative">
                    <button onClick={() => { if (!isTrash) loadMessages(c); }}
                      className={`w-full text-right p-3.5 border-b border-beige-dark/10 hover:bg-beige/30 transition-all
                        ${selected?.id === c.id ? 'bg-primary/[0.04] border-s-2 border-s-primary' : ''}
                        ${isTrash ? 'opacity-70 hover:opacity-100' : ''}`}>
                      <div className="flex items-start gap-3">
                        {/* Avatar */}
                        <div className={`w-11 h-11 rounded-full shrink-0 flex items-center justify-center text-sm font-bold shadow-sm
                          ${unread > 0 ? 'bg-primary text-white shadow-primary/20' :
                            isArchived ? 'bg-amber-100 text-amber-600' :
                            isTrash ? 'bg-red-100 text-red-400' :
                            'bg-gold/15 text-gold'}`}>
                          {c.agency?.logo ? (
                            <img src={c.agency.logo} alt="" className="w-full h-full rounded-full object-cover" onError={e => { (e.target as HTMLElement).style.display = 'none'; }} />
                          ) : (
                            <Building className="w-5 h-5" />
                          )}
                        </div>

                        <div className="flex-1 min-w-0 text-right">
                          <div className="flex items-center justify-between gap-2">
                            <span className={`text-sm truncate ${unread > 0 ? 'font-bold text-stone-900' : 'font-medium text-stone-700'}`}>
                              {c.agency?.name || L('وكالة عقارية', 'Agency')}
                            </span>
                            <span className="text-xs text-stone-400 shrink-0 whitespace-nowrap">{formatTime(c.updated_at)}</span>
                          </div>

                          {c.property && (
                            <div className="text-xs text-stone-400 truncate mt-0.5 flex items-center gap-1">
                              <Building2 className="w-3 h-3 inline shrink-0" />
                              <span>{isAr ? c.property.title_ar : c.property.title_en}</span>
                            </div>
                          )}

                          <div className="flex items-center justify-between gap-2 mt-1">
                            <p className={`text-xs truncate text-right flex-1 ${unread > 0 ? 'text-stone-700 font-medium' : 'text-stone-400'}`}>
                              {c.latest_message?.message || L('...', '...')}
                            </p>
                            <div className="flex items-center gap-1.5 shrink-0">
                              {isArchived && (
                                <Archive className="w-3 h-3 text-amber-400" />
                              )}
                              {unread > 0 && (
                                <span className="bg-primary text-white text-2xs font-bold rounded-full min-w-[18px] h-[18px] flex items-center justify-center px-1 shadow-sm">
                                  {unread > 9 ? '9+' : unread}
                                </span>
                              )}
                            </div>
                          </div>

                          {/* Inline actions — part of card */}
                          <div className="opacity-0 group-hover:opacity-100 transition-opacity mt-2 pt-2 border-t border-stone-100">
                            {renderActions(c)}
                          </div>
                        </div>
                      </div>
                    </button>
                  </div>
                );
              })}
              {lastPage > convPage && (
                <button onClick={() => { setConvPage(p => p + 1); loadConversations(convPage + 1); }}
                  className="w-full py-3 text-sm text-primary hover:bg-beige/60 transition-colors font-medium">
                  {L('تحميل المزيد', 'Load more')}
                </button>
              )}
            </>
          )}
        </div>
      </div>

      {/* ─── Chat Area ─── */}
      <div className={`flex-1 flex flex-col bg-white ${selected ? 'flex' : 'hidden md:flex'}`}>
        {!selected ? (
          <div className="flex-1 flex items-center justify-center bg-beige/20">
            {tab === 'inbox' && conversations.length > 0 ? (
              <div className="text-center max-w-md px-6">
                <div className="w-20 h-20 rounded-3xl bg-gradient-to-br from-primary/5 to-gold/5 flex items-center justify-center mx-auto mb-5 ring-1 ring-primary/10">
                  <MessageSquare className="w-10 h-10 text-primary/30" />
                </div>
                <h3 className="text-lg font-bold text-stone-800 mb-1">{L('رسائلك', 'Your Messages')}</h3>
                <p className="text-sm text-stone-400 mb-6">{L('اختر محادثة أو استخدم الردود السريعة', 'Select a conversation or use quick replies')}</p>
                {/* Quick reply cards */}
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-2.5 max-w-sm mx-auto">
                  {USER_QUICK_REPLIES.slice(0, 4).map((qr, idx) => (
                    <button key={idx} onClick={() => {
                      // Select first conversation and send quick reply
                      if (conversations.length > 0) {
                        const conv = conversations[0];
                        loadMessages(conv);
                        setTimeout(() => {
                          setNewMsg(isAr ? qr.ar : qr.en);
                          setShowQuickReplies(false);
                        }, 300);
                      }
                    }}
                      className="text-right px-4 py-3 bg-white rounded-xl border border-stone-100 hover:border-primary/20 hover:shadow-sm hover:bg-primary/[0.02] transition-all">
                      <span className="text-xs text-stone-600 leading-relaxed line-clamp-2">{isAr ? qr.ar : qr.en}</span>
                    </button>
                  ))}
                </div>
                <p className="text-xs text-stone-400 mt-4">{L('انقر على أي رد لفتح المحادثة وإرساله', 'Click a reply to open the conversation and send it')}</p>
              </div>
            ) : (
              <div className="text-center max-w-sm">
                <div className="w-24 h-24 rounded-3xl bg-gradient-to-br from-primary/5 to-gold/5 flex items-center justify-center mx-auto mb-6 ring-1 ring-primary/10">
                  <MessageSquare className="w-12 h-12 text-primary/30" />
                </div>
                <h3 className="text-xl font-bold text-stone-800 mb-2">{L('رسائلك', 'Your Messages')}</h3>
                <p className="text-sm text-stone-400 leading-relaxed max-w-xs mx-auto">
                  {tab === 'inbox' ? L('اختر محادثة من القائمة لعرض الرسائل', 'Select a conversation to view messages') :
                   tab === 'archived' ? L('اختر محادثة مؤرشفة لعرضها', 'Select an archived conversation') :
                   L('المحادثات المحذوفة لا يمكن فتحها', 'Deleted conversations cannot be opened')}
                </p>
              </div>
            )}
          </div>
        ) : (
          <>
            {/* ── Chat Header ── */}
            <div className="px-4 py-3 border-b border-beige-dark/20 bg-white flex items-center gap-3 shadow-sm">
              <button onClick={() => setSelected(null)} className="md:hidden p-1.5 rounded-lg hover:bg-beige/60 transition-colors">
                <ChevronLeft className="w-5 h-5 text-stone-600 lucide-rtl" />
              </button>
              <div className={`w-11 h-11 rounded-full shrink-0 flex items-center justify-center shadow-sm
                ${tab === 'archived' ? 'bg-amber-100 text-amber-600' : 'bg-gold/10 text-gold'}`}>
                {selected.agency?.logo ? (
                  <img src={selected.agency.logo} alt="" className="w-full h-full rounded-full object-cover" onError={e => { (e.target as HTMLElement).style.display = 'none'; }} />
                ) : (
                  <Building className="w-5 h-5" />
                )}
              </div>
              <div className="flex-1 min-w-0">
                <div className="font-semibold text-sm text-stone-900 truncate flex items-center gap-2">
                  {selected.agency?.name || L('وكالة عقارية', 'Agency')}
                  {tab === 'archived' && (
                    <span className="text-2xs bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded-full font-medium">
                      {L('مؤرشفة', 'Archived')}
                    </span>
                  )}
                </div>
                <div className="text-xs text-stone-400 flex items-center gap-2">
                  <Clock className="w-3 h-3" />
                  {L('آخر نشاط', 'Last activity')}: {formatTime(selected.updated_at)}
                </div>
              </div>
              {selected.property && (
                <div className="hidden sm:flex items-center gap-1.5 text-xs text-stone-500 bg-beige/70 rounded-xl px-3 py-1.5 border border-beige-dark/10">
                  <Building2 className="w-3 h-3" />
                  <span className="truncate max-w-[160px]">{isAr ? selected.property.title_ar : selected.property.title_en}</span>
                </div>
              )}
              {/* Quick actions in header */}
              {tab === 'inbox' && (
                <div className="flex items-center gap-1">
                  <button onClick={() => handleAction(() => archiveConversation(selected.id), selected.id)}
                    title={L('أرشفة', 'Archive')}
                    className="p-2 rounded-xl hover:bg-amber-50 text-stone-400 hover:text-amber-600 transition-all">
                    <Archive className="w-4 h-4" />
                  </button>
                  <button onClick={() => handleAction(() => trashConversation(selected.id), selected.id)}
                    title={L('حذف', 'Delete')}
                    className="p-2 rounded-xl hover:bg-red-50 text-stone-400 hover:text-red-500 transition-all">
                    <Trash2 className="w-4 h-4" />
                  </button>
                </div>
              )}
              {tab === 'archived' && (
                <button onClick={() => handleAction(() => unarchiveConversation(selected.id), selected.id)}
                  title={L('إعادة للوارد', 'Move to inbox')}
                  className="p-2 rounded-xl hover:bg-blue-50 text-stone-400 hover:text-blue-600 transition-all">
                  <ArchiveRestore className="w-4 h-4" />
                </button>
              )}
            </div>

            {/* ── Error Banner ── */}
            {sendError && (
              <div className="flex items-center gap-2 px-4 py-2 bg-red-50 border-b border-red-100 text-red-600 text-xs">
                <span className="flex-1">{sendError}</span>
                <button onClick={() => setSendError(null)}><X className="w-3 h-3" /></button>
              </div>
            )}

            {/* ── Messages ── */}
            <div className="flex-1 overflow-y-auto p-4 md:p-6 space-y-2.5 bg-beige/30">
              {loadingMsgs ? (
                <div className="flex items-center justify-center h-32"><Loader2 className="w-6 h-6 animate-spin text-primary" /></div>
              ) : messages.length === 0 ? (
                <div className="flex items-center justify-center h-full">
                  <div className="text-center">
                    <MessageSquare className="w-10 h-10 text-stone-300 mx-auto mb-2" />
                    <p className="text-sm text-stone-400">{L('لا توجد رسائل بعد', 'No messages yet')}</p>
                    <p className="text-xs text-stone-300 mt-1">{L('أرسل رسالة لبدء المحادثة', 'Send a message to start')}</p>
                  </div>
                </div>
              ) : (
                messages.map((m, i) => {
                  const isClient = m.sender_type === 'client';
                  const isImage = isImageFile(m.attachment_type);
                  const isOffer = m.message_type === 'offer';
                  const isPayment = m.message_type === 'payment_request';
                  const showDate = i === 0 || new Date(m.created_at).toDateString() !== new Date(messages[i - 1].created_at).toDateString();
                  const meta = m.metadata || {};
                  const offerStatus = isOffer ? (meta.status as string) : null;

                  // ─── Offer Card ───
                  if (isOffer) {
                    const statusColors: Record<string, string> = {
                      pending: 'bg-amber-50 border-amber-200 text-amber-800',
                      accepted: 'bg-emerald-50 border-emerald-200 text-emerald-800',
                      rejected: 'bg-red-50 border-red-200 text-red-800',
                      countered: 'bg-blue-50 border-blue-200 text-blue-800',
                    };
                    const statusIcons: Record<string, any> = {
                      pending: Clock,
                      accepted: ThumbsUp,
                      rejected: ThumbsDown,
                      countered: Scale,
                    };
                    const StatusIcon = statusIcons[offerStatus!] || Clock;
                    const arLabels: Record<string, string> = { pending: 'قيد الانتظار', accepted: 'تم القبول', rejected: 'مرفوض', countered: 'عرض مضاد' };
                    const enLabels: Record<string, string> = { pending: 'Pending', accepted: 'Accepted', rejected: 'Rejected', countered: 'Countered' };
                    const statusLabel = L(arLabels[offerStatus!] || '', enLabels[offerStatus!] || '');
                    const isPending = offerStatus === 'pending';
                    const isMyOffer = (isClient && meta.sender_role === 'client') || (!isClient && meta.sender_role === 'agency');

                    return (
                      <div key={m.id}>
                        {showDate && (
                          <div className="flex justify-center my-3">
                            <span className="text-xs bg-white/80 backdrop-blur-sm text-stone-400 px-3 py-1 rounded-full shadow-sm border border-stone-200/50">
                              {new Date(m.created_at).toLocaleDateString(isAr ? 'ar' : 'en', { weekday: 'long', day: 'numeric', month: 'long' })}
                            </span>
                          </div>
                        )}
                        <div className="flex justify-center my-2">
                          <div className={`w-full max-w-sm rounded-2xl border-2 overflow-hidden shadow-md ${statusColors[offerStatus!] || 'bg-white border-stone-200'}`}>
                            {/* Header */}
                            <div className="px-4 py-3 flex items-center gap-2 border-b border-inherit/20">
                              <HandCoins className="w-5 h-5" />
                              <span className="font-bold text-sm flex-1">
                                {isMyOffer ? L('عرضك', 'Your Offer') : L('عرض من', 'Offer from') + ' ' + (meta.sender_role === 'agency'
                                  ? (selected?.agency?.name || '')
                                  : L('العميل', 'Client'))}
                              </span>
                              <span className={`inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full bg-white/80`}>
                                <StatusIcon className="w-3 h-3" />
                                {statusLabel}
                              </span>
                            </div>
                            {/* Amount */}
                            <div className="px-4 py-4 text-center">
                              <div className="text-3xl font-black tracking-tight">
                                {Number(meta.amount).toLocaleString()}
                              </div>
                              <div className="text-sm font-medium mt-0.5 opacity-70">{meta.currency || 'USD'}</div>
                            </div>
                            {/* Note */}
                            {m.message && (
                              <div className="px-4 pb-2">
                                <p className="text-xs opacity-70 leading-relaxed">{m.message}</p>
                              </div>
                            )}
                            {/* Actions */}
                            {isPending && !isMyOffer && (
                              <div className="px-4 pb-4 flex items-center gap-2">
                                <button onClick={() => handleOfferAction('accept', m)}
                                  className="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold py-2 rounded-xl transition-all flex items-center justify-center gap-1.5">
                                  <ThumbsUp className="w-3.5 h-3.5" />
                                  {L('قبول', 'Accept')}
                                </button>
                                <button onClick={() => handleOfferAction('counter', m)}
                                  className="flex-1 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold py-2 rounded-xl transition-all flex items-center justify-center gap-1.5">
                                  <Scale className="w-3.5 h-3.5" />
                                  {L('عرض مضاد', 'Counter')}
                                </button>
                                <button onClick={() => handleOfferAction('reject', m)}
                                  className="px-3 bg-red-100 hover:bg-red-200 text-red-700 text-xs font-bold py-2 rounded-xl transition-all">
                                  <ThumbsDown className="w-3.5 h-3.5" />
                                </button>
                              </div>
                            )}
                            {/* Counter info */}
                            {meta.counter_to && (
                              <div className="px-4 pb-3 text-center">
                                <span className="text-2xs opacity-50">{L('رد على عرض سابق', 'Response to previous offer')}</span>
                              </div>
                            )}
                            {/* Timestamp */}
                            <div className="px-4 py-1.5 border-t border-inherit/10 flex justify-between items-center bg-black/5">
                              <span className="text-2xs opacity-50">{formatTime(m.created_at)}</span>
                              {meta.accepted_at && (
                                <span className="text-2xs text-emerald-600 font-medium">{L('تم القبول', 'Accepted')}</span>
                              )}
                            </div>
                          </div>
                        </div>
                      </div>
                    );
                  }

                  // ─── Payment Card ───
                  if (isPayment) {
                    return (
                      <div key={m.id}>
                        {showDate && (
                          <div className="flex justify-center my-3">
                            <span className="text-xs bg-white/80 backdrop-blur-sm text-stone-400 px-3 py-1 rounded-full shadow-sm border border-stone-200/50">
                              {new Date(m.created_at).toLocaleDateString(isAr ? 'ar' : 'en', { weekday: 'long', day: 'numeric', month: 'long' })}
                            </span>
                          </div>
                        )}
                        <div className="flex justify-center my-2">
                          <div className="w-full max-w-sm rounded-2xl border-2 border-primary/20 bg-primary/[0.03] overflow-hidden shadow-md">
                            <div className="px-4 py-3 flex items-center gap-2 border-b border-primary/10">
                              <DollarSign className="w-5 h-5 text-primary" />
                              <span className="font-bold text-sm flex-1 text-stone-800">{L('طلب دفع', 'Payment Request')}</span>
                              <span className={`text-xs font-semibold px-2 py-0.5 rounded-full ${
                                meta.status === 'paid' ? 'bg-emerald-100 text-emerald-700' :
                                meta.status === 'released' ? 'bg-blue-100 text-blue-700' :
                                'bg-amber-100 text-amber-700'
                              }`}>
                                {meta.status === 'paid' ? L('تم الدفع', 'Paid') :
                                 meta.status === 'released' ? L('تم الإفراج', 'Released') :
                                 L('قيد الانتظار', 'Pending')}
                              </span>
                            </div>
                            <div className="px-4 py-4 text-center">
                              <div className="text-3xl font-black tracking-tight text-primary">
                                {Number(meta.amount).toLocaleString()}
                              </div>
                              <div className="text-sm font-medium mt-0.5 text-stone-500">{meta.currency || 'USD'}</div>
                            </div>
                            {meta.pay_url && meta.status === 'pending' && (
                              <div className="px-4 pb-4">
                                <a href={meta.pay_url} target="_blank" rel="noopener noreferrer"
                                  className="block w-full bg-primary hover:bg-primary-dark text-white text-sm font-bold py-2.5 rounded-xl text-center transition-all">
                                  {L('ادفع الآن', 'Pay Now')}
                                </a>
                              </div>
                            )}
                            <div className="px-4 py-1.5 border-t border-primary/10 flex justify-between bg-black/5">
                              <span className="text-2xs text-stone-400">{formatTime(m.created_at)}</span>
                            </div>
                          </div>
                        </div>
                      </div>
                    );
                  }

                  // ─── Regular Message ───
                  return (
                    <div key={m.id}>
                      {showDate && (
                        <div className="flex justify-center my-3">
                          <span className="text-xs bg-white/80 backdrop-blur-sm text-stone-400 px-3 py-1 rounded-full shadow-sm border border-stone-200/50">
                            {new Date(m.created_at).toLocaleDateString(isAr ? 'ar' : 'en', { weekday: 'long', day: 'numeric', month: 'long' })}
                          </span>
                        </div>
                      )}
                      <div className={`flex ${isClient ? 'justify-end' : 'justify-start'}`}>
                        <div className={`max-w-[75%] md:max-w-[65%] ${isClient ? 'items-end' : 'items-start'}`}>
                          {m.attachment_url && (
                            <div className={`rounded-2xl overflow-hidden mb-0.5 shadow-sm ${isClient ? 'rounded-br-md' : 'rounded-bl-md'}`}>
                              {isImage ? (
                                <a href={m.attachment_url} target="_blank" rel="noopener noreferrer">
                                  <img src={m.attachment_url} alt={m.attachment_name || 'attachment'}
                                    className="max-w-full max-h-72 object-cover cursor-pointer hover:opacity-95 transition-opacity" />
                                </a>
                              ) : (
                                <a href={m.attachment_url} target="_blank" rel="noopener noreferrer"
                                  className={`flex items-center gap-3 px-4 py-3 ${isClient ? 'bg-primary/90 hover:bg-primary text-white' : 'bg-white hover:bg-beige/50 text-stone-700'} transition-all`}>
                                  <FileText className={`w-6 h-6 shrink-0 ${isClient ? 'text-white/80' : 'text-primary'}`} />
                                  <div className="min-w-0">
                                    <p className={`text-sm font-medium truncate ${isClient ? 'text-white' : 'text-stone-700'}`}>
                                      {m.attachment_name || L('ملف', 'File')}
                                    </p>
                                    <p className={`text-xs ${isClient ? 'text-white/70' : 'text-stone-400'}`}>
                                      {formatFileSize(m.attachment_size)}
                                    </p>
                                  </div>
                                </a>
                              )}
                            </div>
                          )}
                          {m.message && (
                            <div className={`px-3.5 py-2 text-sm leading-relaxed shadow-sm
                              ${isClient
                                ? 'bg-primary text-white rounded-2xl rounded-br-md'
                                : 'bg-white text-stone-800 rounded-2xl rounded-bl-md border border-stone-100'}`}>
                              <p className="whitespace-pre-wrap break-words">{m.message}</p>
                            </div>
                          )}
                          <div className={`flex items-center gap-1 px-1 mt-0.5 ${isClient ? 'justify-end' : 'justify-start'}`}>
                            <span className="text-2xs text-stone-400">{formatTime(m.created_at)}</span>
                            {isClient && (
                              m.read_at
                                ? <CheckCheck className="w-3 h-3 text-blue-500" />
                                : <CheckCheck className="w-3 h-3 text-stone-300" />
                            )}
                          </div>
                        </div>
                      </div>
                    </div>
                  );
                })
              )}
              <div ref={messagesEnd} />
            </div>

            {/* ── Input ── */}
            <div className="px-4 py-3 border-t border-beige-dark/20 bg-white">
              {attachment && (
                <div className="flex items-center gap-3 mb-2 bg-beige/50 rounded-xl px-3 py-2 border border-beige-dark/20">
                  {attachmentPreview ? (
                    <img src={attachmentPreview} alt="preview" className="w-10 h-10 rounded-lg object-cover" />
                  ) : (
                    <FileText className="w-6 h-6 text-primary" />
                  )}
                  <div className="flex-1 min-w-0">
                    <p className="text-xs font-medium text-stone-700 truncate">{attachment.name}</p>
                    <p className="text-xs text-stone-400">{formatFileSize(attachment.size)}</p>
                  </div>
                  <button onClick={() => { setAttachment(null); setAttachmentPreview(null); }}
                    className="p-1 rounded hover:bg-stone-200 transition-colors">
                    <X className="w-4 h-4 text-stone-400" />
                  </button>
                </div>
              )}
              <div className="flex items-end gap-2">
                <button type="button" onClick={() => fileInputRef.current?.click()}
                  className="p-2.5 rounded-xl hover:bg-beige/70 text-stone-400 hover:text-primary transition-all">
                  <Paperclip className="w-5 h-5" />
                </button>
                <button type="button" onClick={() => { setOfferAmount(''); setOfferCurrency('USD'); setOfferNote(''); setActionMsgId(null); setShowOfferModal(true); }}
                  className="p-2.5 rounded-xl bg-amber-50 hover:bg-amber-100 text-amber-600 hover:text-amber-700 transition-all border border-amber-200"
                  title={L('إرسال عرض', 'Send Offer')}>
                  <HandCoins className="w-5 h-5" />
                </button>
                <div className="relative">
                  <button type="button" onClick={() => setShowQuickReplies(o => !o)}
                    className={`p-2.5 rounded-xl transition-all ${showQuickReplies ? 'bg-primary/10 text-primary' : 'hover:bg-beige/70 text-stone-400 hover:text-primary'}`}
                    title={L('الردود الجاهزة', 'Quick Replies')}>
                    <ReplyAll className="w-5 h-5" />
                  </button>
                  {showQuickReplies && (
                    <div className={`absolute bottom-full mb-2 ${isAr ? 'left-auto right-0' : 'left-0 right-auto'} w-72 bg-white rounded-2xl shadow-xl border border-stone-100 overflow-hidden z-20`}>
                      <div className="px-3 py-2.5 border-b border-beige-dark/10">
                        <span className="text-xs font-bold text-stone-600">{L('الردود الجاهزة', 'Quick Replies')}</span>
                      </div>
                      <div className="max-h-64 overflow-y-auto p-1.5 space-y-0.5">
                        {USER_QUICK_REPLIES.map((qr, idx) => (
                          <button key={idx} onClick={() => sendQuickReply(isAr ? qr.ar : qr.en)}
                            className="w-full text-right px-3 py-2.5 rounded-xl hover:bg-beige/60 transition-all group">
                            <span className="text-sm text-stone-700 group-hover:text-stone-900 leading-relaxed">
                              {isAr ? qr.ar : qr.en}
                            </span>
                          </button>
                        ))}
                      </div>
                    </div>
                  )}
                </div>
                <input ref={fileInputRef} type="file" accept="image/*,.pdf,.doc,.docx" className="hidden" onChange={handleAttachmentSelect} />
                <div className="flex-1 relative">
                  <input value={newMsg} onChange={e => setNewMsg(e.target.value)}
                    onKeyDown={e => e.key === 'Enter' && !e.shiftKey && (e.preventDefault(), handleSend())}
                    placeholder={L('اكتب رسالتك...', 'Type a message...')}
                    className="w-full bg-beige/50 rounded-full py-2.5 px-4 text-sm outline-none focus:ring-2 focus:ring-primary/20 transition-all resize-none"
                    dir={isAr ? 'rtl' : 'ltr'} />
                </div>
                <button onClick={handleSend} disabled={(!newMsg.trim() && !attachment) || sending}
                  className="p-2.5 rounded-full bg-primary text-white hover:bg-primary-dark disabled:opacity-40 disabled:cursor-not-allowed transition-all shadow-sm">
                  {sending ? <Loader2 className="w-5 h-5 animate-spin" /> : <Send className="w-5 h-5" />}
                </button>
              </div>
            </div>

            {/* ── Offer Modal ── */}
            {showOfferModal && (
              <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm" onClick={() => !offerLoading && setShowOfferModal(false)}>
                <div className="bg-white rounded-2xl shadow-xl w-full max-w-sm mx-4 overflow-hidden" onClick={e => e.stopPropagation()}>
                  {/* Header */}
                  <div className="px-5 py-4 border-b border-stone-100 flex items-center gap-2">
                    <HandCoins className="w-5 h-5 text-amber-600" />
                    <h3 className="font-bold text-stone-800 text-base">{L('إرسال عرض', 'Send Offer')}</h3>
                  </div>
                  {/* Body */}
                  <div className="px-5 py-4 space-y-4">
                    {/* Amount */}
                    <div>
                      <label className="block text-xs font-semibold text-stone-500 mb-1.5">{L('المبلغ', 'Amount')}</label>
                      <input type="number" dir="ltr" inputMode="decimal"
                        value={offerAmount} onChange={e => setOfferAmount(e.target.value)}
                        placeholder="0.00"
                        className="w-full border border-stone-200 rounded-xl px-4 py-2.5 text-lg font-bold text-stone-800 text-center focus:outline-none focus:ring-2 focus:ring-primary/20 transition-all" />
                    </div>
                    {/* Currency */}
                    <div>
                      <label className="block text-xs font-semibold text-stone-500 mb-1.5">{L('العملة', 'Currency')}</label>
                      <select value={offerCurrency} onChange={e => setOfferCurrency(e.target.value)}
                        className="w-full border border-stone-200 rounded-xl px-4 py-2.5 text-sm text-stone-700 focus:outline-none focus:ring-2 focus:ring-primary/20 transition-all"
                        dir={isAr ? 'rtl' : 'ltr'}>
                        <option value="USD">USD ($)</option>
                        <option value="EUR">EUR (€)</option>
                        <option value="SAR">SAR (﷼)</option>
                        <option value="AED">AED (د.إ)</option>
                        <option value="QAR">QAR (ر.ق)</option>
                        <option value="KWD">KWD (د.ك)</option>
                      </select>
                    </div>
                    {/* Note */}
                    <div>
                      <label className="block text-xs font-semibold text-stone-500 mb-1.5">{L('ملاحظة (اختياري)', 'Note (optional)')}</label>
                      <textarea value={offerNote} onChange={e => setOfferNote(e.target.value)}
                        placeholder={L('وصف العرض...', 'Describe your offer...')}
                        rows={3}
                        className="w-full border border-stone-200 rounded-xl px-4 py-2.5 text-sm text-stone-700 focus:outline-none focus:ring-2 focus:ring-primary/20 transition-all resize-none" />
                    </div>
                    {actionMsgId && (
                      <div className="bg-blue-50 text-blue-700 text-xs font-medium px-3 py-2 rounded-xl flex items-center gap-2">
                        <Scale className="w-4 h-4 shrink-0" />
                        {L('سيتم إرسال هذا كعرض مضاد', 'This will be sent as a counter offer')}
                      </div>
                    )}
                  </div>
                  {/* Footer */}
                  <div className="px-5 py-3 border-t border-stone-100 flex items-center gap-2">
                    <button onClick={() => { setShowOfferModal(false); setActionMsgId(null); }}
                      disabled={offerLoading}
                      className="flex-1 py-2.5 rounded-xl border border-stone-200 text-stone-600 text-sm font-semibold hover:bg-stone-50 disabled:opacity-50 transition-all">
                      {L('إلغاء', 'Cancel')}
                    </button>
                    <button onClick={handleSendOffer}
                      disabled={!offerAmount || parseFloat(offerAmount) <= 0 || offerLoading}
                      className="flex-1 py-2.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-sm font-bold disabled:opacity-50 disabled:cursor-not-allowed transition-all flex items-center justify-center gap-2">
                      {offerLoading ? <Loader2 className="w-4 h-4 animate-spin" /> : <HandCoins className="w-4 h-4" />}
                      {actionMsgId ? L('إرسال عرض مضاد', 'Send Counter') : L('إرسال العرض', 'Send Offer')}
                    </button>
                  </div>
                </div>
              </div>
            )}
          </>
        )}
      </div>
    </div>
  );
}
