import { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { Loader2, ChevronLeft, ChevronRight, Mail, MailOpen } from 'lucide-react';
import { fetchMessages, markMessageRead, type ContactMessage } from '../../api/admin';

export default function AdminMessages() {
  const { i18n } = useTranslation();
  const isAr = i18n.language === 'ar';
  const [data, setData] = useState<ContactMessage[]>([]);
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1 });
  const [loading, setLoading] = useState(true);
  const [selected, setSelected] = useState<ContactMessage | null>(null);

  const load = async (page = 1) => {
    setLoading(true);
    try { const res = await fetchMessages(page); setData(res.data); setMeta(res.meta); } catch {}
    setLoading(false);
  };
  useEffect(() => { load(); }, []);

  const openMsg = async (msg: ContactMessage) => {
    setSelected(msg);
    if (!msg.is_read) { await markMessageRead(msg.id); load(meta.current_page); }
  };

  return (
    <div>
      <h1 className="text-2xl font-bold text-stone-900 mb-6">{isAr ? 'الرسائل' : 'Messages'}</h1>
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div className="card-3d overflow-hidden">
          {loading ? (
            <div className="flex items-center justify-center h-48"><Loader2 className="w-8 h-8 animate-spin text-primary" /></div>
          ) : (
            <>
              <div className="divide-y divide-beige-dark max-h-[600px] overflow-y-auto">
                {data.map(msg => (
                  <button key={msg.id} onClick={() => openMsg(msg)}
                    className={`w-full text-start p-4 hover:bg-beige/50 transition-colors ${!msg.is_read ? 'bg-primary/[0.02]' : ''} ${selected?.id === msg.id ? 'bg-beige' : ''}`}>
                    <div className="flex items-center gap-2 mb-1">
                      {!msg.is_read ? <Mail className="w-3.5 h-3.5 text-primary" /> : <MailOpen className="w-3.5 h-3.5 text-stone-300" />}
                      <span className="font-medium text-sm text-stone-800">{msg.name}</span>
                      <span className="text-xs text-stone-400 ms-auto">{new Date(msg.created_at).toLocaleDateString()}</span>
                    </div>
                    <div className="text-xs text-stone-500 truncate ps-5.5">{msg.subject}</div>
                  </button>
                ))}
              </div>
              <div className="flex items-center justify-between px-4 py-3 border-t border-beige-dark text-sm text-stone-500">
                <span>{isAr ? `صفحة ${meta.current_page} من ${meta.last_page}` : `Page ${meta.current_page} of ${meta.last_page}`}</span>
                <div className="flex gap-2">
                  <button disabled={meta.current_page <= 1} onClick={() => load(meta.current_page - 1)} className="p-1.5 rounded-lg hover:bg-beige disabled:opacity-30"><ChevronLeft className="w-4 h-4 lucide-rtl" /></button>
                  <button disabled={meta.current_page >= meta.last_page} onClick={() => load(meta.current_page + 1)} className="p-1.5 rounded-lg hover:bg-beige disabled:opacity-30"><ChevronRight className="w-4 h-4 lucide-rtl" /></button>
                </div>
              </div>
            </>
          )}
        </div>

        {selected && (
          <div className="card-3d p-5">
            <div className="mb-4">
              <h2 className="font-bold text-stone-900 mb-1">{selected.subject}</h2>
              <div className="flex items-center gap-3 text-sm text-stone-500">
                <span>{selected.name}</span>
                <span>{selected.email}</span>
                {selected.phone && <span>{selected.phone}</span>}
              </div>
              <div className="text-xs text-stone-400 mt-1">{new Date(selected.created_at).toLocaleString()}</div>
            </div>
            <div className="text-sm text-stone-700 leading-relaxed whitespace-pre-wrap">{selected.message}</div>
          </div>
        )}
      </div>
    </div>
  );
}
