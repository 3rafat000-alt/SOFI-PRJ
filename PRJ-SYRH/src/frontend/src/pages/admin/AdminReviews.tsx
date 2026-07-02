import { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { Loader2, ChevronLeft, ChevronRight, Star, CheckCircle2 } from 'lucide-react';
import { fetchReviews, approveReview, type ReviewItem } from '../../api/admin';

export default function AdminReviews() {
  const { i18n } = useTranslation();
  const isAr = i18n.language === 'ar';
  const [data, setData] = useState<ReviewItem[]>([]);
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1 });
  const [loading, setLoading] = useState(true);

  const load = async (page = 1) => {
    setLoading(true);
    try { const res = await fetchReviews(page); setData(res.data); setMeta(res.meta); } catch {}
    setLoading(false);
  };
  useEffect(() => { load(); }, []);

  const handleApprove = async (id: number) => {
    await approveReview(id);
    load(meta.current_page);
  };

  return (
    <div>
      <h1 className="text-2xl font-bold text-stone-900 mb-6">{isAr ? 'التقييمات' : 'Reviews'}</h1>
      <div className="card-3d overflow-hidden">
        {loading ? (
          <div className="flex items-center justify-center h-48"><Loader2 className="w-8 h-8 animate-spin text-primary" /></div>
        ) : (
          <>
            <div className="divide-y divide-beige-dark">
              {data.map(r => (
                <div key={r.id} className="p-4">
                  <div className="flex items-start justify-between">
                    <div className="flex-1">
                      <div className="flex items-center gap-2 mb-1">
                        <span className="font-medium text-sm text-stone-800">{r.user?.name}</span>
                        <div className="flex items-center gap-0.5">
                          {Array.from({ length: 5 }).map((_, i) => (
                            <Star key={i} className={`w-3.5 h-3.5 ${i < r.rating ? 'text-gold fill-gold' : 'text-stone-200'}`} />
                          ))}
                        </div>
                        <span className="text-xs text-stone-400">{new Date(r.created_at).toLocaleDateString()}</span>
                      </div>
                      <div className="text-xs text-stone-500 mb-2">
                        {isAr ? 'على' : 'on'} {isAr ? r.property?.title_ar : r.property?.title_en}
                      </div>
                      <p className="text-sm text-stone-700">{r.comment}</p>
                    </div>
                    <div className="flex gap-1 shrink-0 ms-4">
                      {!r.is_approved ? (
                        <button onClick={() => handleApprove(r.id)} className="p-2 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-100 transition-all" title={isAr ? 'موافقة' : 'Approve'}>
                          <CheckCircle2 className="w-4 h-4" />
                        </button>
                      ) : (
                        <span className="badge-primary text-xs">{isAr ? 'معتمد' : 'Approved'}</span>
                      )}
                    </div>
                  </div>
                </div>
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
    </div>
  );
}
