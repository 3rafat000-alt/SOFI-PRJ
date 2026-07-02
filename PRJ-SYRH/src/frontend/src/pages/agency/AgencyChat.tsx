import { useState, useEffect, useRef, useCallback, useMemo } from 'react';
import { useTranslation } from 'react-i18next';
import {
  MessageSquare, Send, Loader2, Phone, Mail, ChevronLeft, Building2,
  CheckCheck, Search, Paperclip, FileText, X, ChevronDown, ExternalLink,
  Wallet, Clock, ShieldCheck, CreditCard, CheckCircle,
  ReplyAll, Plus, Pencil, Trash2, Eye, HandCoins,
  ThumbsUp, ThumbsDown, Scale,
} from 'lucide-react';
import client from '../../api/client';
import { sendPaymentRequest, sendAgencyOffer, acceptAgencyOffer, rejectAgencyOffer, counterAgencyOffer } from '../../api/agency';
import echo from '../../echo';

interface ChatProperty {
  id: number;
  slug: string;
  title_ar: string;
  title_en: string;
  price: number | null;
  currency: string | null;
  purpose: string | null;
  status: string | null;
  cover_image: { path: string; alt_ar?: string; alt_en?: string } | null;
}

interface Conversation {
  id: number;
  client_name: string;
  client_phone: string | null;
  client_email: string | null;
  user: { id: number; name: string; avatar: string | null } | null;
  property: ChatProperty | null;
  updated_at: string;
  latest_message: { message: string; created_at: string; sender_type: string } | null;
  unread_client_messages_count?: number;
}

interface ChatAttachment {
  path: string;
  type: string;
  name: string;
  size: number;
  url: string;
}

interface ChatMessage {
  id: number;
  sender_type: 'agency' | 'client';
  sender_id: number | null;
  message: string;
  message_type: 'text' | 'payment_request' | 'offer';
  metadata: Record<string, any> | null;
  attachment_path: string | null;
  attachment_type: string | null;
  attachment_name: string | null;
  attachment_size: number | null;
  attachment_url: string | null;
  attachments: ChatAttachment[] | null;
  attachments_url: ChatAttachment[] | null;
  read_at: string | null;
  created_at: string;
}

export default function AgencyChat() {
  const { i18n } = useTranslation();
  const isAr = i18n.language === 'ar';
  const L = (ar: string, en: string) => isAr ? ar : en;

  const [conversations, setConversations] = useState<Conversation[]>([]);
  const [selected, setSelected] = useState<Conversation | null>(null);
  const [messages, setMessages] = useState<ChatMessage[]>([]);
  const [newMsg, setNewMsg] = useState('');
  const [loading, setLoading] = useState(true);
  const [sending, setSending] = useState(false);
  const [loadingMsgs, setLoadingMsgs] = useState(false);
  const [convPage, setConvPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [unreadCount, setUnreadCount] = useState(0);
  const [chatError, setChatError] = useState<string | null>(null);
  const [searchQuery, setSearchQuery] = useState('');
  const [attachments, setAttachments] = useState<File[]>([]);
  const [attachmentPreviews, setAttachmentPreviews] = useState<string[]>([]);
  const [propertyCard, setPropertyCard] = useState<ChatProperty | null>(null);
  const [showPaymentReq, setShowPaymentReq] = useState(false);
  const [prAmount, setPrAmount] = useState('');
  const [prCurrency, setPrCurrency] = useState('SYP');
  const [prEscrowType, setPrEscrowType] = useState<'sale' | 'rent' | 'rental_operation'>('sale');
  const [prNote, setPrNote] = useState('');
  const [sendingPr, setSendingPr] = useState(false);
  const [showOfferModal, setShowOfferModal] = useState(false);
  const [offerAmount, setOfferAmount] = useState('');
  const [offerCurrency, setOfferCurrency] = useState('USD');
  const [offerNote, setOfferNote] = useState('');
  const [offerLoading, setOfferLoading] = useState(false);
  const [actionMsgId, setActionMsgId] = useState<number | null>(null);
  const [showPropertyCard, setShowPropertyCard] = useState(false);
  const [loadingPropertyCard, setLoadingPropertyCard] = useState(false);
  interface QuickReply { id: number; title: string; content: string; placeholders: string[]; property_id: number | null; is_active: boolean; }
  const [quickReplies, setQuickReplies] = useState<QuickReply[]>([]);
  const [showQRPanel, setShowQRPanel] = useState(false);
  const [loadingQR, setLoadingQR] = useState(false);
  const [qrPreview, setQrPreview] = useState<{ qr: QuickReply; rendered: string } | null>(null);
  const [showQRForm, setShowQRForm] = useState(false);
  const [editingQR, setEditingQR] = useState<QuickReply | null>(null);
  const [qrForm, setQrForm] = useState({ title: '', content: '' });
  const messagesEnd = useRef<HTMLDivElement>(null);
  const fileInputRef = useRef<HTMLInputElement>(null);
  const msgInputRef = useRef<HTMLInputElement>(null);
  const [propertyFilter, setPropertyFilter] = useState<number | null>(null);

  // Derive unique properties from conversations for filter dropdown
  const conversationProperties = useMemo(() => {
    const props = new Map<number, { id: number; title_ar: string; title_en: string }>();
    conversations.forEach(c => {
      if (c.property && !props.has(c.property.id)) {
        props.set(c.property.id, { id: c.property.id, title_ar: c.property.title_ar, title_en: c.property.title_en });
      }
    });
    return Array.from(props.values()).sort((a, b) => isAr ? a.title_ar.localeCompare(b.title_ar) : a.title_en.localeCompare(b.title_en));
  }, [conversations]);

  // Filter conversations by search + property
  const filteredConversations = conversations.filter(c => {
    if (propertyFilter !== null && (!c.property || c.property.id !== propertyFilter)) return false;
    if (searchQuery) {
      const q = searchQuery.toLowerCase();
      return c.client_name.toLowerCase().includes(q) ||
        c.client_phone?.includes(q) ||
        (c.property && (c.property.title_ar.includes(q) || c.property.title_en.toLowerCase().includes(q)));
    }
    return true;
  });

  const loadConversations = (p = 1) => {
    setLoading(true);
    client.get('/agency/conversations', { params: { page: p } }).then(r => {
      const data = r.data.data ?? r.data;
      setConversations(prev => p === 1 ? data : [...prev, ...data]);
      setLastPage(r.data.meta?.last_page ?? 1);
    }).catch(err => { console.error('Failed to load conversations', err); }).finally(() => setLoading(false));
  };

  const loadMessages = (conv: Conversation) => {
    setSelected(conv);
    setLoadingMsgs(true);
    setMessages([]);
    client.get(`/agency/conversations/${conv.id}/messages`).then(r => {
      setMessages(r.data.data ?? r.data);
    }).catch(err => { console.error('Failed to load messages', err); }).finally(() => setLoadingMsgs(false));
    markRead(conv.id);
  };

  const markRead = useCallback((convId: number) => {
    client.put(`/agency/conversations/${convId}/read`).catch(() => {});
    setConversations(prev => prev.map(c =>
      c.id === convId ? { ...c, unread_client_messages_count: 0 } : c
    ));
    setUnreadCount(prev => Math.max(0, prev - 1));
  }, []);

  const sendMessage = async () => {
    if ((!newMsg.trim() && attachments.length === 0) || !selected || sending) return;
    setSending(true);
    try {
      const fd = new FormData();
      fd.append('message', newMsg.trim());
      // If single file, use backward-compat field; if multiple, send as array
      if (attachments.length === 1) {
        fd.append('attachment', attachments[0]);
      } else if (attachments.length > 1) {
        attachments.forEach(f => fd.append('attachments[]', f));
      }
      const res = await client.post(`/agency/conversations/${selected.id}/messages`, fd, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
      setMessages(prev => [...prev, res.data.data]);
      setNewMsg('');
      setAttachments([]);
      setAttachmentPreviews([]);
    } catch (err) {
      console.error('Failed to send message', err);
      setChatError(L('فشل الإرسال', 'Send failed'));
    } finally {
      setSending(false);
    }
  };

  const handleAttachmentSelect = (e: React.ChangeEvent<HTMLInputElement>) => {
    const files = e.target.files;
    if (!files || files.length === 0) return;
    const newFiles = Array.from(files);
    setAttachments(prev => [...prev, ...newFiles]);
    const newPreviews = newFiles
      .filter(f => f.type.startsWith('image/'))
      .map(f => URL.createObjectURL(f));
    setAttachmentPreviews(prev => [...prev, ...newPreviews]);
    if (fileInputRef.current) fileInputRef.current.value = '';
  };

  const handlePaymentRequest = async () => {
    if (!selected || sendingPr || !prAmount || Number(prAmount) <= 0) return;
    setSendingPr(true);
    try {
      const msg = await sendPaymentRequest(selected.id, {
        amount: Number(prAmount),
        currency: prCurrency,
        escrow_type: prEscrowType,
        note: prNote || undefined,
      });
      setMessages(prev => [...prev, msg]);
      setShowPaymentReq(false);
      setPrAmount('');
      setPrNote('');
    } catch (err) {
      console.error('Failed to send payment request', err);
      setChatError(L('فشل الإرسال', 'Send failed'));
    } finally {
      setSendingPr(false);
    }
  };

  // ─── Offer action handlers ───
  const handleOfferAction = async (action: 'accept' | 'reject' | 'counter', msg: ChatMessage) => {
    if (!selected) return;
    setActionMsgId(msg.id);
    try {
      if (action === 'accept') {
        await acceptAgencyOffer(selected.id, msg.id);
        const res = await client.get(`/agency/conversations/${selected.id}/messages`);
        setMessages(res.data.data ?? res.data);
      } else if (action === 'counter') {
        setOfferAmount('');
        setOfferCurrency('USD');
        setOfferNote('');
        setShowOfferModal(true);
        setActionMsgId(msg.id);
        return;
      } else {
        await rejectAgencyOffer(selected.id, msg.id);
        const res = await client.get(`/agency/conversations/${selected.id}/messages`);
        setMessages(res.data.data ?? res.data);
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
        await counterAgencyOffer(selected.id, actionMsgId, {
          amount: parseFloat(offerAmount),
          currency: offerCurrency,
          note: offerNote || undefined,
        });
      } else {
        await sendAgencyOffer(selected.id, {
          amount: parseFloat(offerAmount),
          currency: offerCurrency,
          note: offerNote || undefined,
        });
      }
      setShowOfferModal(false);
      setOfferAmount('');
      setOfferNote('');
      setActionMsgId(null);
      const res = await client.get(`/agency/conversations/${selected.id}/messages`);
      setMessages(res.data.data ?? res.data);
    } catch (err) {
      console.error('Failed to send offer', err);
      setChatError(L('فشل الإرسال', 'Send failed'));
    } finally {
      setOfferLoading(false);
    }
  };

  const togglePropertyCard = useCallback(async (prop: ChatProperty) => {
    if (showPropertyCard && propertyCard?.id === prop.id) {
      setShowPropertyCard(false);
      return;
    }
    setLoadingPropertyCard(true);
    try {
      const res = await client.get(`/properties/chat-card/${prop.id}`);
      setPropertyCard(res.data.data);
      setShowPropertyCard(true);
    } catch {
      // Fallback: use conversation property data
      setPropertyCard(prop);
      setShowPropertyCard(true);
    } finally {
      setLoadingPropertyCard(false);
    }
  }, [showPropertyCard, propertyCard]);

  const removeAttachment = (idx: number) => {
    setAttachments(prev => prev.filter((_, i) => i !== idx));
    setAttachmentPreviews(prev => {
      // Revoke object URL to avoid memory leak
      if (prev[idx]) URL.revokeObjectURL(prev[idx]);
      return prev.filter((_, i) => i !== idx);
    });
  };

  const sendQuickReply = async (qr: QuickReply) => {
    if (!selected) return;
    setQrPreview(null);
    setShowQRPanel(false);
    const values: Record<string, string> = {};
    if (selected.client_name) values.client_name = selected.client_name;
    if (selected.property) {
      values.property_title = selected.property.title_ar;
      values.price = selected.property.price ? Number(selected.property.price).toLocaleString() + ' ' + (selected.property.currency || '') : '';
      values.location = selected.property.title_ar;
    }
    try {
      const res = await client.post(`/agency/quick-replies/${qr.id}/send`, {
        conversation_id: selected.id,
        values,
      });
      setMessages(prev => [...prev, res.data.data]);
    } catch (err) {
      console.error('Failed to send quick reply', err);
    }
  };

  const renderQRPreview = (qr: QuickReply) => {
    if (!selected) return;
    let text = qr.content;
    if (selected.client_name) text = text.replace(/\{client_name\}/g, selected.client_name);
    if (selected.property) {
      text = text.replace(/\{property_title\}/g, selected.property.title_ar || '');
      const p = selected.property;
      const currency = (p as any).currency || '';
      text = text.replace(/\{price\}/g, p.price ? Number(p.price).toLocaleString() + ' ' + currency : '');
      text = text.replace(/\{location\}/g, (p as any).address_ar || (p as any).title_ar || '');
      text = text.replace(/\{area\}/g, (p as any).area_sqm ? String((p as any).area_sqm) : '');
      text = text.replace(/\{bedrooms\}/g, (p as any).bedrooms ? String((p as any).bedrooms) : '');
    }
    // Strip any remaining unreplaced placeholders
    text = text.replace(/\{(\w+)\}/g, '');
    setQrPreview({ qr, rendered: text });
  };

  const openQRForm = (qr?: QuickReply) => {
    setEditingQR(qr || null);
    setQrForm({ title: qr?.title || '', content: qr?.content || '' });
    setShowQRForm(true);
  };

  const saveQR = async () => {
    if (!qrForm.title.trim() || !qrForm.content.trim()) return;
    try {
      const payload = { title: qrForm.title, content: qrForm.content };
      if (editingQR) {
        await client.put(`/agency/quick-replies/${editingQR.id}`, payload);
      } else {
        await client.post('/agency/quick-replies', payload);
      }
      setShowQRForm(false);
      // Reload
      const propId = selected?.property?.id;
      const res = await client.get('/agency/quick-replies', { params: propId ? { property_id: propId } : {} });
      setQuickReplies(res.data.data || []);
    } catch { }
  };

  const deleteQR = async (id: number) => {
    if (!confirm(L('حذف هذا الرد؟', 'Delete this reply?'))) return;
    try {
      await client.delete(`/agency/quick-replies/${id}`);
      setQuickReplies(prev => prev.filter(r => r.id !== id));
    } catch { }
  };

  // Load conversations on mount
  useEffect(() => { loadConversations(); }, []);

  // Reload QR when conversation changes
  useEffect(() => { if (showQRPanel && selected) loadQR(); }, [selected?.id, showQRPanel]);

  // Listen for new messages via WebSocket
  useEffect(() => {
    if (!selected) return;
    const convId = selected.id;

    loadMessages(selected);
    const channel = echo.channel(`conversation.${convId}`);
    channel.listen('.new-message', (e: any) => {
      if (e.senderType !== 'agency') {
        loadMessages(selected);
        markRead(convId);
      }
    });

    return () => {
      channel.stopListening('.new-message');
      echo.leaveChannel(`conversation.${convId}`);
    };
  }, [selected?.id, markRead]);

  // Fallback: poll unread count every 30s
  useEffect(() => {
    const fetchUnread = () => {
      client.get('/agency/chat/unread-count').then(r => {
        setUnreadCount(r.data.data?.unread_count ?? 0);
      }).catch(() => {});
    };
    fetchUnread();
    const iv = setInterval(fetchUnread, 30000);
    return () => clearInterval(iv);
  }, []);

  // Refresh conversation list when messages change (update latest message + time)
  useEffect(() => {
    if (messages.length === 0) return;
    const last = messages[messages.length - 1];
    setConversations(prev => prev.map(c =>
      c.id === selected?.id
        ? { ...c, latest_message: { message: last.message, created_at: last.created_at, sender_type: last.sender_type }, updated_at: last.created_at }
        : c
    ));
  }, [messages.length]);

  const loadQR = useCallback(() => {
    if (!selected) { setQuickReplies([]); return; }
    setLoadingQR(true);
    const propId = selected.property?.id;
    const params: any = {};
    if (propId) params.property_id = propId;
    client.get('/agency/quick-replies', { params }).then(r => {
      setQuickReplies(r.data.data || []);
    }).catch(err => { console.error('Failed to load quick replies', err); }).finally(() => setLoadingQR(false));
  }, [selected?.id]);
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

  return (
    <div className="flex flex-1 h-full bg-white">
      {/* ─── Conversations Sidebar ─── */}
      <div className={`w-72 lg:w-80 border-s border-beige-dark/20 bg-white flex flex-col ${selected ? 'hidden md:flex' : 'flex'}`}>
        {/* Header */}
        <div className="p-4 border-b border-beige-dark/20">
          <div className="flex items-center justify-between mb-3">
            <h2 className="text-sm font-bold text-stone-800 flex items-center gap-2">
              <MessageSquare className="w-4 h-4 text-primary" />
              {L('المحادثات', 'Chats')}
            </h2>
            {unreadCount > 0 && (
              <span className="bg-red-500 text-white text-xs font-bold rounded-full min-w-[22px] h-[22px] flex items-center justify-center px-1.5 shadow-sm">
                {unreadCount > 99 ? '99+' : unreadCount}
              </span>
            )}
          </div>
          {/* Search */}
          <div className="relative">
            <Search className={`absolute end-3 top-1/2 -translate-y-1/2 w-4 h-4 text-stone-300`} />
            <input value={searchQuery} onChange={e => setSearchQuery(e.target.value)}
              placeholder={L('بحث في المحادثات...', 'Search conversations...')}
              dir={isAr ? 'rtl' : 'ltr'}
              className="w-full bg-beige/50 rounded-xl py-2.5 pe-10 ps-4 text-sm outline-none focus:ring-2 focus:ring-primary/20 focus:bg-white transition-all placeholder:text-stone-400" />
          </div>
          {/* Property filter */}
          {conversationProperties.length > 0 && (
            <div className="px-4 pb-3 pt-2">
              <select value={propertyFilter ?? ''} onChange={e => setPropertyFilter(e.target.value ? Number(e.target.value) : null)}
                className="w-full bg-beige/30 rounded-xl py-2 px-3 text-xs text-stone-600 outline-none focus:ring-2 focus:ring-primary/20 transition-all border-none">
                <option value="">{L('كل العقارات', 'All properties')}</option>
                {conversationProperties.map(p => (
                  <option key={p.id} value={p.id}>{isAr ? p.title_ar : p.title_en}</option>
                ))}
              </select>
            </div>
          )}
        </div>

        {/* Conversation List */}
        <div className="flex-1 overflow-y-auto">
          {loading && conversations.length === 0 ? (
            <div className="flex items-center justify-center h-32"><Loader2 className="w-6 h-6 animate-spin text-primary" /></div>
          ) : filteredConversations.length === 0 ? (
            <div className="text-center py-16 px-4">
              <MessageSquare className="w-10 h-10 text-stone-300 mx-auto mb-2" />
              <p className="text-sm text-stone-500">
                {searchQuery ? L('لا توجد نتائج', 'No results') : L('لا توجد محادثات بعد', 'No conversations yet')}
              </p>
            </div>
          ) : (
            filteredConversations.map(c => {
              const unread = c.unread_client_messages_count ?? 0;
              return (
                <button key={c.id} onClick={() => loadMessages(c)}
                  className={`w-full text-right p-3.5 border-b border-beige-dark/10 hover:bg-beige/40 transition-all
                    ${selected?.id === c.id ? 'bg-primary/[0.04] border-s-2 border-s-primary shadow-sm' : ''}`}>
                  <div className="flex items-start gap-3">
                    {/* Avatar */}
                    <div className={`w-10 h-10 rounded-xl shrink-0 flex items-center justify-center text-sm font-bold shadow-sm
                      ${unread > 0 ? 'bg-primary text-white' : 'bg-beige/70 text-stone-500'}`}>
                      {c.client_name.charAt(0).toUpperCase()}
                    </div>
                    <div className="flex-1 min-w-0 text-right">
                      <div className="flex items-center justify-between gap-2">
                        <span className={`text-sm truncate ${unread > 0 ? 'font-bold text-stone-900' : 'font-medium text-stone-700'}`}>
                          {c.client_name}
                        </span>
                        <span className="text-2xs text-stone-400 shrink-0 font-medium">{formatTime(c.updated_at)}</span>
                      </div>
                      {c.property && (
                        <div className="text-xs text-stone-400 truncate mt-0.5">
                          {isAr ? c.property.title_ar : c.property.title_en}
                        </div>
                      )}
                      <div className="flex items-center justify-between gap-2 mt-1">
                        <p className={`text-xs truncate text-right leading-relaxed ${unread > 0 ? 'text-stone-700 font-medium' : 'text-stone-400'}`}>
                          {c.latest_message?.message || L('...', '...')}
                        </p>
                        {unread > 0 && (
                          <span className="bg-primary text-white text-2xs font-bold rounded-full min-w-[18px] h-[18px] flex items-center justify-center px-1 shrink-0">
                            {unread > 9 ? '9+' : unread}
                          </span>
                        )}
                      </div>
                    </div>
                  </div>
                </button>
              );
            })
          )}
          {lastPage > convPage && (
            <button onClick={() => { setConvPage(p => p + 1); loadConversations(convPage + 1); }}
              className="w-full py-3 text-sm text-primary hover:bg-beige/60 transition-colors font-medium">
              {L('تحميل المزيد', 'Load more')}
            </button>
          )}
        </div>
      </div>

      {/* ─── Chat Area ─── */}
      <div className={`flex-1 flex flex-col bg-white ${selected ? 'flex' : 'hidden md:flex'}`}>
        {!selected ? (
          <div className="flex-1 flex items-center justify-center bg-beige/20">
            <div className="text-center max-w-sm">
              <div className="w-16 h-16 rounded-2xl bg-primary/5 flex items-center justify-center mx-auto mb-4">
                <MessageSquare className="w-8 h-8 text-primary/30" />
              </div>
              <h3 className="text-base font-bold text-stone-700 mb-1">{L('رسائل المحادثات', 'Conversation Messages')}</h3>
              <p className="text-sm text-stone-400 leading-relaxed">
                {L('اختر محادثة من القائمة لعرض الرسائل والرد على العملاء', 'Select a conversation to view messages and reply to clients')}
              </p>
            </div>
          </div>
        ) : (
          <>
            {/* ── Chat Header ── */}
            <div className="px-4 py-3 border-b border-beige-dark/20 bg-white flex items-center gap-3">
              <button onClick={() => setSelected(null)} className="md:hidden p-1.5 rounded-lg hover:bg-beige/60 transition-colors">
                <ChevronLeft className="w-5 h-5 text-stone-600 lucide-rtl" />
              </button>
              <div className="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center text-primary text-sm font-bold shrink-0">
                {selected.client_name.charAt(0).toUpperCase()}
              </div>
              <div className="flex-1 min-w-0">
                <div className="font-semibold text-sm text-stone-900 truncate">{selected.client_name}</div>
                <div className="flex items-center gap-3 text-xs text-stone-400">
                  {selected.client_phone && (
                    <a href={`tel:${selected.client_phone}`} className="flex items-center gap-1 hover:text-primary transition-colors">
                      <Phone className="w-3 h-3" />
                      <span dir="ltr">{selected.client_phone}</span>
                    </a>
                  )}
                  {selected.client_email && (
                    <a href={`mailto:${selected.client_email}`} className="flex items-center gap-1 hover:text-primary transition-colors">
                      <Mail className="w-3 h-3" />
                      <span className="truncate max-w-[120px]">{selected.client_email}</span>
                    </a>
                  )}
                </div>
              </div>
              {/* Payment request button */}
              <button onClick={() => setShowPaymentReq(true)}
                className="hidden sm:flex items-center gap-1.5 text-xs font-medium text-green-700 bg-green-50 hover:bg-green-100 border border-green-200 rounded-xl px-3 py-1.5 transition-colors">
                <Wallet className="w-3 h-3" />
                {L('طلب دفع', 'Payment')}
              </button>
              {selected.property && (
                <div className="relative">
                  <button onClick={() => togglePropertyCard(selected.property!)}
                    className="hidden sm:flex items-center gap-1.5 text-xs text-stone-600 bg-beige/50 rounded-xl px-3 py-1.5 border border-beige-dark/10 hover:bg-beige/80 transition-colors">
                    <Building2 className="w-3 h-3" />
                    <span className="truncate max-w-[120px]">{isAr ? selected.property.title_ar : selected.property.title_en}</span>
                    <ChevronDown className={`w-3 h-3 transition-transform ${showPropertyCard ? 'rotate-180' : ''}`} />
                  </button>

                  {/* Property Card Dropdown */}
                  {showPropertyCard && propertyCard && (
                    <div className="absolute top-full left-0 mt-2 w-72 bg-white rounded-xl shadow-lg border border-beige-dark/20 z-30 overflow-hidden">
                      {loadingPropertyCard ? (
                        <div className="p-4 text-center"><Loader2 className="w-5 h-5 animate-spin text-primary mx-auto" /></div>
                      ) : (
                        <>
                          {propertyCard.cover_image && (
                            <div className="h-32 bg-stone-100 overflow-hidden">
                              <img src={'/storage/' + propertyCard.cover_image.path}
                                alt={isAr ? propertyCard.cover_image.alt_ar : propertyCard.cover_image.alt_en || propertyCard.title_ar}
                                className="w-full h-full object-cover"
                                onError={e => { (e.target as HTMLImageElement).style.display = 'none'; }} />
                            </div>
                          )}
                          <div className="p-3">
                            <h4 className="font-semibold text-sm text-stone-900 mb-1">
                              {isAr ? propertyCard.title_ar : propertyCard.title_en}
                            </h4>
                            {propertyCard.price && (
                              <p className="text-primary font-bold text-sm">
                                {Number(propertyCard.price).toLocaleString()} {propertyCard.currency || 'SYP'}
                              </p>
                            )}
                            <div className="flex items-center gap-2 mt-2 text-xs text-stone-500">
                              <span className="bg-beige/70 px-2 py-0.5 rounded">{propertyCard.purpose === 'sale' ? L('بيع', 'Sale') : L('إيجار', 'Rent')}</span>
                              <span className={`px-2 py-0.5 rounded ${propertyCard.status === 'available' ? 'bg-green-50 text-green-700' : 'bg-stone-100 text-stone-500'}`}>
                                {propertyCard.status === 'available' ? L('متاح', 'Available') : propertyCard.status}
                              </span>
                            </div>
                            <a href={`/properties/${propertyCard.slug}`} target="_blank" rel="noopener noreferrer"
                              className="mt-3 flex items-center gap-1 text-xs text-primary hover:underline">
                              {L('عرض التفاصيل', 'View details')}
                              <ExternalLink className="w-3 h-3" />
                            </a>
                          </div>
                        </>
                      )}
                    </div>
                  )}
                </div>
              )}
            </div>

            {/* ── Error Banner ── */}
            {chatError && (
              <div className="flex items-center gap-2 px-4 py-2 bg-red-50 border-b border-red-100 text-red-600 text-xs">
                <span className="flex-1">{chatError}</span>
                <button onClick={() => setChatError(null)}><X className="w-3 h-3" /></button>
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
                    <p className="text-xs text-stone-300 mt-1">{L('أرسل رسالة لبدء المحادثة', 'Send a message to start the conversation')}</p>
                  </div>
                </div>
              ) : (
                messages.map((m, idx) => {
                  const isAgency = m.sender_type === 'agency';
                  const isConsecutive = idx > 0 && messages[idx - 1]?.sender_type === m.sender_type;
                  const isImage = isImageFile(m.attachment_type);
                  const multiAttachments = m.attachments_url || m.attachments || [];
                  const hasMultiAttachments = multiAttachments.length > 0;
                  const isOffer = m.message_type === 'offer';
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
                    const isMyOffer = (isAgency && meta.sender_role === 'agency') || (!isAgency && meta.sender_role === 'client');

                    return (
                      <div key={m.id} className="flex justify-center my-2">
                        <div className={`w-full max-w-sm rounded-2xl border-2 overflow-hidden shadow-md ${statusColors[offerStatus!] || 'bg-white border-stone-200'}`}>
                          {/* Header */}
                          <div className="px-4 py-3 flex items-center gap-2 border-b border-inherit/20">
                            <HandCoins className="w-5 h-5" />
                            <span className="font-bold text-sm flex-1">
                              {isMyOffer ? L('عرضك', 'Your Offer') : L('عرض من', 'Offer from') + ' ' + L('العميل', 'Client')}
                            </span>
                            <span className="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full bg-white/80">
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
                    );
                  }

                  return (
                    <div key={m.id} className={`flex ${isAgency ? 'justify-start' : 'justify-end'}`}>
                      <div className={`max-w-[75%] md:max-w-[65%] ${isConsecutive ? 'mt-0.5' : 'mt-0'}`}>
                        {/* Single attachment (backward compat) */}
                        {m.attachment_url && (
                          <div className={`rounded-2xl overflow-hidden mb-1 ${isAgency ? 'rounded-bl-md' : 'rounded-br-md'}`}>
                            {isImage ? (
                              <a href={m.attachment_url} target="_blank" rel="noopener noreferrer">
                                <img src={m.attachment_url} alt={m.attachment_name || 'attachment'}
                                  className="max-w-full max-h-64 object-cover cursor-pointer hover:opacity-95 transition-opacity" />
                              </a>
                            ) : (
                              <a href={m.attachment_url} target="_blank" rel="noopener noreferrer"
                                className={`flex items-center gap-3 px-4 py-3 ${isAgency ? 'bg-primary/90 hover:bg-primary' : 'bg-beige hover:bg-beige-dark/10'} transition-colors`}>
                                <FileText className={`w-6 h-6 ${isAgency ? 'text-white/80' : 'text-stone-400'}`} />
                                <div className="min-w-0">
                                  <p className={`text-sm font-medium truncate ${isAgency ? 'text-white' : 'text-stone-700'}`}>
                                    {m.attachment_name || L('ملف', 'File')}
                                  </p>
                                  <p className={`text-xs ${isAgency ? 'text-white/70' : 'text-stone-400'}`}>
                                    {formatFileSize(m.attachment_size)}
                                  </p>
                                </div>
                              </a>
                            )}
                          </div>
                        )}
                        {/* Multiple attachments */}
                        {hasMultiAttachments && (
                          <div className={`rounded-2xl overflow-hidden mb-1 ${isAgency ? 'rounded-bl-md' : 'rounded-br-md'}
                            ${multiAttachments.length > 1 ? 'grid grid-cols-2 gap-0.5' : ''}`}>
                            {multiAttachments.map((att, attIdx) => (
                              att.type?.startsWith('image/') ? (
                                <a key={attIdx} href={att.url} target="_blank" rel="noopener noreferrer">
                                  <img src={att.url} alt={att.name || 'attachment'}
                                    className="w-full max-h-48 object-cover cursor-pointer hover:opacity-95 transition-opacity" />
                                </a>
                              ) : (
                                <a key={attIdx} href={att.url} target="_blank" rel="noopener noreferrer"
                                  className={`flex items-center gap-3 px-4 py-3 ${isAgency ? 'bg-primary/90 hover:bg-primary' : 'bg-beige hover:bg-beige-dark/10'} transition-colors`}>
                                  <FileText className={`w-6 h-6 ${isAgency ? 'text-white/80' : 'text-stone-400'}`} />
                                  <div className="min-w-0">
                                    <p className={`text-sm font-medium truncate ${isAgency ? 'text-white' : 'text-stone-700'}`}>
                                      {att.name || L('ملف', 'File')}
                                    </p>
                                    <p className={`text-xs ${isAgency ? 'text-white/70' : 'text-stone-400'}`}>
                                      {formatFileSize(att.size)}
                                    </p>
                                  </div>
                                </a>
                              )
                            ))}
                          </div>
                        )}
                        {/* Payment request card */}
                        {m.message_type === 'payment_request' && m.metadata && (
                          <div className={`rounded-2xl overflow-hidden mb-1 border ${isAgency ? 'rounded-br-md border-primary/30' : 'rounded-bl-md border-green-200'}
                            ${isAgency ? 'bg-primary/5' : 'bg-white'}`}>
                            <div className={`px-4 py-3 ${isAgency ? 'bg-primary/10' : 'bg-green-50'} border-b ${isAgency ? 'border-primary/20' : 'border-green-200'}`}>
                              <div className="flex items-center gap-2">
                                <Wallet className={`w-4 h-4 ${isAgency ? 'text-primary' : 'text-green-600'}`} />
                                <span className={`text-xs font-bold ${isAgency ? 'text-primary' : 'text-green-700'}`}>
                                  {L('طلب دفع', 'Payment Request')}
                                </span>
                                <span className={`text-2xs px-2 py-0.5 rounded-full ms-auto
                                  ${m.metadata.status === 'paid' ? 'bg-green-100 text-green-700' :
                                    m.metadata.status === 'pending' ? 'bg-amber-100 text-amber-700' :
                                    m.metadata.status === 'released' ? 'bg-blue-100 text-blue-700' :
                                    'bg-stone-100 text-stone-600'}`}>
                                  {m.metadata.status === 'paid' ? L('تم الدفع', 'Paid') :
                                   m.metadata.status === 'pending' ? L('قيد الانتظار', 'Pending') :
                                   m.metadata.status === 'released' ? L('تم الصرف', 'Released') :
                                   m.metadata.status}
                                </span>
                              </div>
                            </div>
                            <div className="px-4 py-3 space-y-2">
                              <div className="text-lg font-bold text-stone-900">
                                {Number(m.metadata.amount).toLocaleString()} {m.metadata.currency}
                              </div>
                              {m.message && (
                                <p className="text-xs text-stone-500">{m.message}</p>
                              )}
                              <div className="flex items-center gap-3 text-xs text-stone-500">
                                <span className="flex items-center gap-1">
                                  <Clock className="w-3 h-3" />
                                  {m.metadata.escrow_type === 'sale' ? L('ضمان 3 أيام', '3-day escrow') :
                                   m.metadata.escrow_type === 'rent' ? L('ضمان 14 يوم', '14-day escrow') :
                                   L('ضمان 3 ساعات', '3-hour escrow')}
                                </span>
                                <span className="flex items-center gap-1">
                                  <ShieldCheck className="w-3 h-3" />
                                  {L('عبر صك', 'via SAKK')}
                                </span>
                              </div>
                              {m.metadata.pay_url && m.metadata.status === 'pending' && (
                                <a href={m.metadata.pay_url} target="_blank" rel="noopener noreferrer"
                                  className="block w-full text-center py-2.5 bg-green-600 text-white text-sm font-medium rounded-xl hover:bg-green-700 transition-colors">
                                  <CreditCard className="w-4 h-4 inline-block ml-1.5" />
                                  {L('اضغط للدفع', 'Click to Pay')}
                                </a>
                              )}
                              {m.metadata.status === 'paid' && (
                                <div className="flex items-center gap-1.5 text-xs text-green-600">
                                  <ShieldCheck className="w-4 h-4" />
                                  {L('تم الدفع - المبلغ في الضمان', 'Paid — funds in escrow')}
                                </div>
                              )}
                              {m.metadata.status === 'released' && (
                                <div className="flex items-center gap-1.5 text-xs text-blue-600">
                                  <CheckCircle className="w-4 h-4" />
                                  {L('تم الصرف - المبلغ محول للوكالة', 'Released — funds transferred to agency')}
                                </div>
                              )}
                            </div>
                          </div>
                        )}
                        {/* Text message */}
                        {m.message && m.message_type !== 'payment_request' && (
                          <div className={`px-3.5 py-2 text-sm leading-relaxed shadow-sm
                          ${isAgency
                            ? 'bg-primary text-white rounded-2xl rounded-bl-md'
                            : 'bg-white text-stone-800 rounded-2xl rounded-br-md'}
                            ${(m.attachment_url && isImage) || hasMultiAttachments ? 'rounded-tr-none rounded-tl-none mt-0' : ''}`}>
                            <p className="whitespace-pre-wrap break-words">{m.message}</p>
                          </div>
                        )}
                        {/* Time + Status */}
                        <div className={`flex items-center gap-1 px-1 mt-0.5 ${isAgency ? 'justify-start' : 'justify-end'}`}>
                          <span className="text-2xs text-stone-400">{formatTime(m.created_at)}</span>
                          {isAgency && (
                            m.read_at
                              ? <CheckCheck className="w-3 h-3 text-blue-500" />
                              : <CheckCheck className="w-3 h-3 text-stone-300" />
                          )}
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
              {/* Attachments preview */}
              {attachments.length > 0 && (
                <div className="flex flex-wrap gap-2 mb-2">
                  {attachments.map((file, idx) => (
                    <div key={idx} className="flex items-center gap-2 bg-beige/40 rounded-xl px-3 py-2 border border-beige-dark/20">
                      {file.type.startsWith('image/') && attachmentPreviews[idx] ? (
                        <img src={attachmentPreviews[idx]} alt="preview" className="w-8 h-8 rounded-lg object-cover" />
                      ) : (
                        <FileText className="w-5 h-5 text-primary" />
                      )}
                      <div className="min-w-0 max-w-[120px]">
                        <p className="text-xs font-medium text-stone-700 truncate">{file.name}</p>
                        <p className="text-2xs text-stone-400">{formatFileSize(file.size)}</p>
                      </div>
                      <button onClick={() => removeAttachment(idx)}
                        className="p-0.5 rounded hover:bg-stone-200 transition-colors shrink-0">
                        <X className="w-3.5 h-3.5 text-stone-400" />
                      </button>
                    </div>
                  ))}
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
                <input ref={fileInputRef} type="file" multiple accept="image/*,.pdf,.doc,.docx" className="hidden" onChange={handleAttachmentSelect} />
                {/* Quick Reply Button */}
                {/* Quick Reply Panel Toggle */}
                {selected && (
                  <button type="button" onClick={() => setShowQRPanel(o => !o)}
                    className={`p-2.5 rounded-xl transition-all ${showQRPanel ? 'bg-primary/10 text-primary' : 'hover:bg-beige/70 text-stone-400 hover:text-primary'}`}
                    title={L('الردود الجاهزة', 'Quick Replies')}>
                    <ReplyAll className="w-5 h-5" />
                  </button>
                )}
                <div className="flex-1 relative">
                  <input ref={msgInputRef} value={newMsg} onChange={e => setNewMsg(e.target.value)}
                    onKeyDown={e => e.key === 'Enter' && !e.shiftKey && (e.preventDefault(), sendMessage())}
                    placeholder={L('اكتب رسالتك...', 'Type a message...')}
                    className="w-full bg-beige/40 rounded-full py-2.5 px-4 text-sm outline-none focus:ring-2 focus:ring-primary/20 transition-all resize-none"
                    dir={isAr ? 'rtl' : 'ltr'} />
                </div>
                <button onClick={sendMessage} disabled={(!newMsg.trim() && attachments.length === 0) || sending}
                  className="p-2.5 rounded-full bg-primary text-white hover:bg-primary-dark disabled:opacity-40 disabled:cursor-not-allowed transition-all">
                  {sending ? <Loader2 className="w-5 h-5 animate-spin" /> : <Send className="w-5 h-5" />}
                </button>
              </div>
            </div>
          </>
        )}
      </div>

      {/* ── Quick Reply Side Panel ── */}
      {showQRPanel && selected && (
        <div className="w-80 border-e border-beige-dark/20 bg-white flex flex-col">
          {/* Header */}
          <div className="p-4 border-b border-beige-dark/20">
            <div className="flex items-center justify-between">
              <h3 className="font-bold text-stone-900 text-sm flex items-center gap-2">
                <ReplyAll className="w-4 h-4 text-primary" />
                {L('الردود الجاهزة', 'Quick Replies')}
              </h3>
              <button onClick={() => openQRForm()}
                className="p-1.5 rounded-lg hover:bg-primary/10 text-primary transition-colors" title={L('إضافة رد', 'Add reply')}>
                <Plus className="w-4 h-4" />
              </button>
            </div>
            {selected.property && (
              <div className="flex items-center gap-1.5 mt-2 text-xs text-primary bg-primary/5 rounded-lg px-2.5 py-1.5">
                <Building2 className="w-3 h-3 shrink-0" />
                <span className="truncate">{isAr ? selected.property.title_ar : selected.property.title_en}</span>
              </div>
            )}
          </div>

          {/* List */}
          <div className="flex-1 overflow-y-auto p-2 space-y-1.5">
            {loadingQR ? (
              <div className="flex items-center justify-center h-32"><Loader2 className="w-5 h-5 animate-spin text-primary" /></div>
            ) : quickReplies.length === 0 ? (
              <div className="text-center py-16 px-4">
                <ReplyAll className="w-8 h-8 text-stone-300 mx-auto mb-2" />
                <p className="text-xs text-stone-400">{L('لا توجد ردود جاهزة', 'No quick replies')}</p>
                <button onClick={() => openQRForm()}
                  className="mt-3 text-xs text-primary hover:underline font-medium">
                  {L('أضف أول رد الآن', 'Add your first reply')}
                </button>
              </div>
            ) : (
              quickReplies.map(qr => (
                <div key={qr.id}
                  className="group relative bg-white border border-beige-dark/20 rounded-xl p-3 hover:shadow-sm hover:border-primary/20 transition-all cursor-pointer"
                  onClick={() => { if (qrPreview?.qr.id === qr.id) { setQrPreview(null); } else { renderQRPreview(qr); } }}>
                  <div className="flex items-start justify-between gap-2">
                    <div className="flex-1 min-w-0">
                      <div className="flex items-center gap-1.5">
                        <p className="text-sm font-semibold text-stone-900">{qr.title}</p>
                        {!qr.property_id && (
                          <span className="text-2xs text-stone-400 bg-beige/50 px-1.5 py-0.5 rounded">{L('عام', 'General')}</span>
                        )}
                      </div>
                      <p className="text-xs text-stone-400 mt-1 line-clamp-2 whitespace-pre-line leading-relaxed">{qr.content}</p>
                    </div>
                    {/* Actions on hover */}
                    <div className="hidden group-hover:flex items-center gap-0.5 shrink-0">
                      <button onClick={(e) => { e.stopPropagation(); deleteQR(qr.id); }}
                        className="p-1 rounded hover:bg-red-50 text-stone-300 hover:text-red-500 transition-colors">
                        <Trash2 className="w-3.5 h-3.5" />
                      </button>
                      <button onClick={(e) => { e.stopPropagation(); openQRForm(qr); }}
                        className="p-1 rounded hover:bg-beige text-stone-300 hover:text-stone-600 transition-colors">
                        <Pencil className="w-3.5 h-3.5" />
                      </button>
                    </div>
                  </div>
                  {/* Send button */}
                  <button onClick={(e) => { e.stopPropagation(); sendQuickReply(qr); }}
                    className="mt-2 w-full py-1.5 text-xs text-primary bg-primary/5 hover:bg-primary/10 rounded-lg transition-colors font-medium">
                    {L('إرسال الرد', 'Send reply')}
                  </button>
                </div>
              ))
            )}
          </div>
        </div>
      )}

      {/* ── Quick Reply Preview & Send ── */}
      {qrPreview && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/30" onClick={() => setQrPreview(null)}>
          <div className="bg-white rounded-2xl shadow-xl w-full max-w-lg mx-4 p-6" onClick={e => e.stopPropagation()}>
            <div className="flex items-center justify-between mb-4">
              <h3 className="font-bold text-stone-900 flex items-center gap-2">
                <Eye className="w-4 h-4 text-primary" />
                {L('معاينة الرد', 'Reply Preview')}
              </h3>
              <button onClick={() => setQrPreview(null)} className="p-1 rounded-lg hover:bg-beige text-stone-400">
                <X className="w-5 h-5" />
              </button>
            </div>
            <div className="text-xs text-stone-500 mb-3 font-medium">{qrPreview.qr.title}</div>
            <div className="p-4 bg-beige/30 rounded-2xl">
              <div className="max-w-[85%] ms-auto">
                <div className="px-3.5 py-2.5 text-sm leading-relaxed bg-primary text-white rounded-2xl rounded-bl-md shadow-sm whitespace-pre-wrap break-words">
                  {qrPreview.rendered}
                </div>
                <div className="flex items-center gap-1 px-1 mt-0.5 justify-start">
                  <span className="text-2xs text-stone-400">{L('الآن', 'Now')}</span>
                  <CheckCheck className="w-3 h-3 text-stone-300" />
                </div>
              </div>
            </div>
            <div className="flex items-center gap-3 mt-5">
              <button onClick={() => setQrPreview(null)}
                className="flex-1 py-2.5 rounded-xl border border-stone-300 text-sm text-stone-600 hover:bg-stone-50 transition-colors">
                {L('إلغاء', 'Cancel')}
              </button>
              <button onClick={() => sendQuickReply(qrPreview.qr)}
                className="flex-1 py-2.5 rounded-xl bg-primary text-white text-sm font-medium hover:bg-primary-dark transition-colors flex items-center justify-center gap-1.5">
                <Send className="w-4 h-4" />
                {L('إرسال الرد', 'Send Reply')}
              </button>
            </div>
          </div>
        </div>
      )}

      {/* ── Quick Reply Form Modal ── */}
      {showQRForm && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/30" onClick={() => setShowQRForm(false)}>
          <div className="bg-white rounded-2xl shadow-xl w-full max-w-md mx-4 p-6" onClick={e => e.stopPropagation()}>
            <div className="flex items-center justify-between mb-4">
              <h3 className="font-bold text-stone-900 text-sm">
                {editingQR ? L('تعديل الرد الجاهز', 'Edit Quick Reply') : L('إضافة رد جاهز', 'Add Quick Reply')}
              </h3>
              <button onClick={() => setShowQRForm(false)} className="p-1 rounded-lg hover:bg-beige text-stone-400">
                <X className="w-5 h-5" />
              </button>
            </div>
            <div className="space-y-3">
              <div>
                <label className="text-xs font-medium text-stone-600 mb-1 block">{L('العنوان', 'Title')}</label>
                <input value={qrForm.title} onChange={e => setQrForm(f => ({ ...f, title: e.target.value }))}
                  className="w-full px-3 py-2 border border-stone-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary/20"
                  placeholder={L('مثال: ترحيب أولي', 'e.g. Welcome greeting')} />
              </div>
              <div>
                <label className="text-xs font-medium text-stone-600 mb-1 block">{L('نص الرد', 'Reply Text')}</label>
                <textarea value={qrForm.content} onChange={e => setQrForm(f => ({ ...f, content: e.target.value }))}
                  rows={5}
                  className="w-full px-3 py-2 border border-stone-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 resize-none"
                  placeholder={L('استخدم {client_name} للمتغيرات', 'Use {client_name} for variables')} />
                <p className="text-xs text-stone-400 mt-1">
                  {L('المتغيرات: {client_name}, {property_title}, {price}, {location}', 'Variables: {client_name}, {property_title}, {price}, {location}')}
                </p>
              </div>
            </div>
            <div className="flex items-center gap-3 mt-5">
              <button onClick={() => setShowQRForm(false)}
                className="flex-1 py-2.5 rounded-xl border border-stone-300 text-sm text-stone-600 hover:bg-stone-50 transition-colors">
                {L('إلغاء', 'Cancel')}
              </button>
              <button onClick={saveQR}
                className="flex-1 py-2.5 rounded-xl bg-primary text-white text-sm font-medium hover:bg-primary-dark transition-colors">
                {editingQR ? L('حفظ', 'Save') : L('إضافة', 'Create')}
              </button>
            </div>
          </div>
        </div>
      )}

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

      {/* ── Payment Request Dialog ── */}
      {showPaymentReq && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/30" onClick={() => setShowPaymentReq(false)}>
          <div className="bg-white rounded-2xl shadow-xl w-full max-w-md p-6 mx-4" onClick={e => e.stopPropagation()}>
            <h3 className="font-bold text-stone-900 text-lg mb-4">{L('طلب دفع عبر صك', 'SAKK Payment Request')}</h3>

            <div className="space-y-3">
              {/* Amount */}
              <div>
                <label className="text-xs font-medium text-stone-600 mb-1 block">{L('المبلغ', 'Amount')}</label>
                <input type="number" min="1" step="0.01" value={prAmount} onChange={e => setPrAmount(e.target.value)}
                  className="input-field w-full text-sm" placeholder="1000" />
              </div>

              {/* Currency */}
              <div>
                <label className="text-xs font-medium text-stone-600 mb-1 block">{L('العملة', 'Currency')}</label>
                <select value={prCurrency} onChange={e => setPrCurrency(e.target.value)}
                  className="input-field w-full text-sm">
                  <option value="SYP">SYP</option>
                  <option value="USD">USD</option>
                  <option value="EUR">EUR</option>
                </select>
              </div>

              {/* Escrow type */}
              <div>
                <label className="text-xs font-medium text-stone-600 mb-1 block">{L('نوع الضمان', 'Escrow Type')}</label>
                <select value={prEscrowType} onChange={e => setPrEscrowType(e.target.value as any)}
                  className="input-field w-full text-sm">
                  <option value="sale">{L('بيع (3 أيام)', 'Sale (3 days)')}</option>
                  <option value="rent">{L('إيجار (14 يوم)', 'Rent (14 days)')}</option>
                  <option value="rental_operation">{L('تشغيل (3 ساعات)', 'Rental Ops (3 hours)')}</option>
                </select>
              </div>

              {/* Note */}
              <div>
                <label className="text-xs font-medium text-stone-600 mb-1 block">{L('ملاحظة', 'Note')}</label>
                <textarea value={prNote} onChange={e => setPrNote(e.target.value)}
                  className="input-field w-full text-sm resize-none" rows={2}
                  placeholder={L('وصف طلب الدفع...', 'Payment request description...')} />
              </div>

              {/* Escrow info */}
              <div className="bg-amber-50 border border-amber-200 rounded-xl p-3 text-xs text-amber-800">
                <p className="flex items-center gap-1.5 font-medium mb-1">
                  <ShieldCheck className="w-4 h-4" />
                  {L('ضمان الدفع عبر صك', 'SAKK Escrow Protection')}
                </p>
                <p>{L('سيتم تعليق المبلغ في محفظة صك لحين تأكيد الطرفين. يتم التحويل بعد:', 'Funds held in SAKK wallet until confirmed by both parties. Released after:')}</p>
                <ul className="list-disc list-inside mt-1 space-y-0.5">
                  <li>{prEscrowType === 'sale' ? L('3 أيام من الدفع', '3 days from payment') : prEscrowType === 'rent' ? L('14 يوم من الدفع', '14 days from payment') : L('3 ساعات من الدفع', '3 hours from payment')}</li>
                </ul>
              </div>
            </div>

            {/* Buttons */}
            <div className="flex items-center gap-3 mt-5">
              <button onClick={() => setShowPaymentReq(false)}
                className="flex-1 py-2.5 rounded-xl border border-stone-300 text-sm text-stone-600 hover:bg-stone-50 transition-colors">
                {L('إلغاء', 'Cancel')}
              </button>
              <button onClick={handlePaymentRequest} disabled={!prAmount || Number(prAmount) <= 0 || sendingPr}
                className="flex-1 py-2.5 rounded-xl bg-green-600 text-white text-sm font-medium hover:bg-green-700 disabled:opacity-40 transition-colors">
                {sendingPr ? <Loader2 className="w-4 h-4 animate-spin mx-auto" /> : L('إرسال طلب الدفع', 'Send Payment Request')}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
