import client from './client';

export interface GuestConversation {
  id: number;
  token: string;
  conversation: {
    id: number;
    agency: { id: number; name: string; slug: string; logo: string | null } | null;
  };
  message: any;
}

export function startGuestChat(data: {
  agency_id: number;
  property_id?: number;
  client_name: string;
  client_phone?: string;
  client_email?: string;
  message: string;
}): Promise<{ data: GuestConversation }> {
  return client.post('/guest/chat/start', data).then(r => r.data);
}

export function fetchGuestMessages(token: string): Promise<{ data: any[]; meta: any }> {
  return client.get(`/guest/chat/${token}/messages`).then(r => r.data);
}

export function sendGuestMessage(token: string, message: string, attachments?: File | File[]): Promise<any> {
  const files = Array.isArray(attachments) ? attachments : (attachments ? [attachments] : []);
  if (files.length > 0) {
    const fd = new FormData();
    fd.append('message', message);
    if (files.length === 1) {
      fd.append('attachment', files[0]);
    } else {
      files.forEach(f => fd.append('attachments[]', f));
    }
    return client.post(`/guest/chat/${token}/messages`, fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    }).then(r => r.data);
  }
  return client.post(`/guest/chat/${token}/messages`, { message }).then(r => r.data);
}

export function markGuestRead(token: string): Promise<any> {
  return client.put(`/guest/chat/${token}/read`).then(r => r.data);
}
