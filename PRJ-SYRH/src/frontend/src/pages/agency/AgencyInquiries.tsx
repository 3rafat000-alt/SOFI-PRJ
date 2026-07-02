import { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { MessageSquare, Loader2, Phone, Mail } from 'lucide-react';
import { fetchAgencyInquiries, updateAgencyInquiry, type AgencyInquiry } from '../../api/agency';

export default function AgencyInquiries() {
  const { i18n } = useTranslation();
  const isAr = i18n.language === 'ar';
  const L = (ar: string, en: string) => isAr ? ar : en;
  const [inquiries, setInquiries] = useState<AgencyInquiry[]>([]);
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [expandedId, setExpandedId] = useState<number | null>(null);

  const statusMap: Record<string, string> = {
    new: L('جديد', 'New'),
    contacted: L('تم التواصل', 'Contacted'),
    closed: L('مغلق', 'Closed'),
  };

  const statusColor: Record<string, string> = {
    new: 'badge-primary',
    contacted: 'badge-gold',
    closed: 'badge bg-stone-100 text-stone-500',
  };

  const load = () => {
    setLoading(true);
    fetchAgencyInquiries({ page: String(page) }).then(res => {
      setInquiries(res.data);
      setLastPage(res.meta?.last_page ?? 1);
    }).finally(() => setLoading(false));
  };

  useEffect(() => { load(); }, [page]);

  const handleStatus = async (id: number, status: string) => {
    await updateAgencyInquiry(id, status);
    load();
  };

  return (
    <div>
      <h1 className="text-2xl font-bold text-stone-900 mb-6">{L('الاستفسارات', 'Inquiries')}</h1>

      {loading ? (
        <div className="flex items-center justify-center h-48"><Loader2 className="w-8 h-8 animate-spin text-primary" /></div>
      ) : inquiries.length === 0 ? (
        <div className="text-center py-16">
          <MessageSquare className="w-12 h-12 text-stone-300 mx-auto mb-3" />
          <p className="text-stone-500">{L('لا توجد استفسارات بعد', 'No inquiries yet')}</p>
        </div>
      ) : (
        <div className="space-y-3">
          {inquiries.map(inq => (
            <div key={inq.id} className="card-3d p-4">
              <div className="flex items-start justify-between cursor-pointer" onClick={() => setExpandedId(expandedId === inq.id ? null : inq.id)}>
                <div className="flex-1 min-w-0">
                  <div className="font-medium text-stone-900">{inq.name}</div>
                  <div className="text-xs text-stone-400 mt-0.5">
                    {isAr ? inq.property?.title_ar : inq.property?.title_en}
                  </div>
                </div>
                <div className="flex items-center gap-3">
                  <span className={statusColor[inq.status] || 'badge'}>{statusMap[inq.status] || inq.status}</span>
                  <span className="text-xs text-stone-400">{new Date(inq.created_at).toLocaleDateString()}</span>
                </div>
              </div>

              {expandedId === inq.id && (
                <div className="mt-4 pt-4 border-t border-beige-dark/30 space-y-3">
                  <div className="flex flex-wrap gap-4 text-sm text-stone-600">
                    <span className="flex items-center gap-1.5"><Phone className="w-3.5 h-3.5 text-stone-400" />{inq.phone}</span>
                    {inq.email && <span className="flex items-center gap-1.5"><Mail className="w-3.5 h-3.5 text-stone-400" />{inq.email}</span>}
                  </div>
                  <p className="text-sm text-stone-600 bg-beige rounded-xl p-3">{inq.message}</p>
                  <div className="flex gap-2">
                    {inq.status === 'new' && (
                      <button onClick={() => handleStatus(inq.id, 'contacted')}
                        className="btn-primary text-xs !py-1.5 !px-3">{L('تم التواصل', 'Contacted')}</button>
                    )}
                    {inq.status !== 'closed' && (
                      <button onClick={() => handleStatus(inq.id, 'closed')}
                        className="px-3 py-1.5 rounded-lg text-xs font-medium text-stone-500 hover:bg-beige border border-beige-dark/30">{L('إغلاق', 'Close')}</button>
                    )}
                  </div>
                </div>
              )}
            </div>
          ))}

          {lastPage > 1 && (
            <div className="flex items-center justify-center gap-2 mt-6" dir="ltr">
              {Array.from({ length: lastPage }).map((_, i) => (
                <button key={i} onClick={() => setPage(i + 1)}
                  className={`w-9 h-9 rounded-lg text-sm font-medium transition-all ${
                    page === i + 1 ? 'bg-primary text-white' : 'border border-beige-dark hover:bg-beige'
                  }`}>{i + 1}</button>
              ))}
            </div>
          )}
        </div>
      )}
    </div>
  );
}
