import client from './client';

export interface UserDashboard {
  favorites_count: number;
  inquiries_count: number;
  searches_count: number;
  recent_inquiries: UserInquiry[];
  recent_favorites: any[];
}

export interface UserInquiry {
  id: number;
  property_id: number;
  name: string;
  phone: string;
  email: string | null;
  message: string;
  type: string;
  status: string;
  preferred_at: string | null;
  offer_amount: string | null;
  created_at: string;
  property: {
    id: number;
    title_ar: string;
    title_en: string;
    slug: string;
    price: number;
    currency: string;
    cover_image: string | null;
  } | null;
}

// User dashboard
export function fetchUserDashboard(): Promise<{ data: UserDashboard }> {
  return client.get('/user/dashboard').then(r => r.data);
}

// User inquiries
export function fetchUserInquiries(page = 1): Promise<{ data: UserInquiry[]; meta: any }> {
  return client.get('/user/inquiries', { params: { page } }).then(r => r.data);
}

// Chat
export interface ChatConversation {
  id: number;
  client_name: string;
  client_phone: string | null;
  client_email: string | null;
  user_id: number | null;
  user: { id: number; name: string; avatar: string | null } | null;
  agency: { id: number; name: string; slug: string; logo: string | null } | null;
  property: { id: number; slug: string; title_ar: string; title_en: string } | null;
  updated_at: string;
  latest_message: { message: string; created_at: string; sender_type: string } | null;
  unread_client_messages_count?: number;
  unread_agency_messages_count?: number;
}

export interface ChatAttachment {
  path: string;
  type: string;
  name: string;
  size: number;
  url: string;
}

export interface ChatMessage {
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

export function fetchUserConversations(page = 1): Promise<{ data: ChatConversation[]; meta: any }> {
  return client.get('/user/chat/conversations', { params: { page } }).then(r => r.data);
}

export function startConversation(data: { agency_id: number; property_id?: number; message: string }): Promise<any> {
  return client.post('/user/chat/conversations', data).then(r => r.data);
}

export function fetchConversationMessages(convId: number): Promise<{ data: ChatMessage[]; meta: any }> {
  return client.get(`/user/chat/conversations/${convId}/messages`).then(r => r.data);
}

export function sendChatMessage(convId: number, message: string, attachments?: File | File[]): Promise<any> {
  const files = Array.isArray(attachments) ? attachments : (attachments ? [attachments] : []);
  if (files.length > 0) {
    const fd = new FormData();
    fd.append('message', message);
    if (files.length === 1) {
      fd.append('attachment', files[0]);
    } else {
      files.forEach(f => fd.append('attachments[]', f));
    }
    return client.post(`/user/chat/conversations/${convId}/messages`, fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    }).then(r => r.data);
  }
  return client.post(`/user/chat/conversations/${convId}/messages`, { message }).then(r => r.data);
}

export function markConversationRead(convId: number): Promise<any> {
  return client.put(`/user/chat/conversations/${convId}/read`).then(r => r.data);
}

export function fetchUserChatUnread(): Promise<{ data: { unread_count: number } }> {
  return client.get('/user/chat/unread-count').then(r => r.data);
}

// ── Archive / Trash / Restore ──

export function archiveConversation(convId: number): Promise<any> {
  return client.put(`/user/chat/conversations/${convId}/archive`).then(r => r.data);
}

export function unarchiveConversation(convId: number): Promise<any> {
  return client.post(`/user/chat/conversations/${convId}/unarchive`).then(r => r.data);
}

export function trashConversation(convId: number): Promise<any> {
  return client.delete(`/user/chat/conversations/${convId}/trash`).then(r => r.data);
}

export function restoreConversation(convId: number): Promise<any> {
  return client.post(`/user/chat/conversations/${convId}/restore`).then(r => r.data);
}

export function forceDeleteConversation(convId: number): Promise<any> {
  return client.delete(`/user/chat/conversations/${convId}/force`).then(r => r.data);
}

// Fetch conversations with tab
export function fetchUserConversationsByTab(tab: 'inbox' | 'archived' | 'trash', page = 1): Promise<{ data: ChatConversation[]; meta: any }> {
  return client.get('/user/chat/conversations', { params: { tab, page } }).then(r => r.data);
}

// ── Offer / Negotiation ──

export function sendOffer(convId: number, data: { amount: number; currency: string; note?: string }): Promise<any> {
  return client.post(`/user/chat/conversations/${convId}/offer`, data).then(r => r.data);
}

export function acceptOffer(convId: number, messageId: number): Promise<any> {
  return client.post(`/user/chat/conversations/${convId}/offer/accept`, { message_id: messageId }).then(r => r.data);
}

export function rejectOffer(convId: number, messageId: number, reason?: string): Promise<any> {
  return client.post(`/user/chat/conversations/${convId}/offer/reject`, { message_id: messageId, reason }).then(r => r.data);
}

export function counterOffer(convId: number, messageId: number, data: { amount: number; currency: string; note?: string }): Promise<any> {
  return client.post(`/user/chat/conversations/${convId}/offer/counter`, { message_id: messageId, ...data }).then(r => r.data);
}
