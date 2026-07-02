import { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { Link } from 'react-router-dom';
import { MessageSquare, Loader2, ChevronLeft, ChevronRight } from 'lucide-react';
import { fetchUserInquiries, type UserInquiry } from '../../api/user';

export default function UserInquiries() {
  const { i18n } = useTranslation();
  const isAr = i18n.language === 'ar';
  const [data, setData] = useState<UserInquiry[]>([]);
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1 });
  const [loading, setLoading] = useState(true);

  const load = async (page = 1) => {
    setLoading(true);
    try {
      const res = await fetchUserInquiries(page);
      setData(res.data);
      setMeta(res.meta);
    } catch {}
    setLoading(false);
  };

  useEffect(() => { load(); }, []);

  const statusBadge = (status: string) => {
    const styles: Record<string, string> = {
      new: 'bg-blue-50 text-blue-600',
      read: 'bg-amber-50 text-amber-600',
      contacted: 'bg-green-50 text-green-600',
      closed: 'bg-stone-50 text-stone-500',
    };
    const labels: Record<string, string> = {
      new: isAr ? 'جديد' : 'New',
      read: isAr ? 'مقروء' : 'Read',
      contacted: isAr ? 'تم التواصل' : 'Contacted',
      closed: isAr ? 'مغلق' : 'Closed',
    };
    return (
      <span className={`inline-block px-2.5 py-1 rounded-full text-xs font-medium ${styles[status] || 'bg-stone-50 text-stone-500'}`}>
        {labels[status] || status}
      </span>
    );
  };

  return (
    <div>
      <h1 className="text-2xl font-bold text-stone-900 mb-6">{isAr ? 'استفساراتي' : 'My Inquiries'}</h1>

      {loading ? (
        <div className="flex items-center justify-center h-48"><Loader2 className="w-8 h-8 animate-spin text-primary" /></div>
      ) : data.length === 0 ? (
        <div className="card-3d p-12 text-center">
          <MessageSquare className="w-12 h-12 text-stone-300 mx-auto mb-3" />
          <p className="text-stone-500 mb-2">{isAr ? 'لا توجد استفسارات بعد' : 'No inquiries yet'}</p>
          <Link to="/properties" className="btn-primary text-sm inline-flex items-center gap-2 !py-2 !px-4">
            {isAr ? 'تصفح العقارات' : 'Browse Properties'}
          </Link>
        </div>
      ) : (
        <div className="space-y-4">
          {data.map((inq) => (
            <div key={inq.id} className="card-3d p-5">
              <div className="flex items-start justify-between mb-3">
                <div className="flex-1">
                  <Link to={`/properties/${inq.property?.slug || ''}`} className="text-base font-bold text-stone-900 hover:text-primary transition-colors">
                    {inq.property ? (isAr ? inq.property.title_ar : inq.property.title_en) : `#${inq.property_id}`}
                  </Link>
                  <div className="text-xs text-stone-400 mt-0.5">
                    {new Date(inq.created_at).toLocaleDateString(isAr ? 'ar' : 'en', {
                      year: 'numeric', month: 'long', day: 'numeric',
                    })}
                  </div>
                </div>
                {statusBadge(inq.status)}
              </div>

              <p className="text-sm text-stone-600 mb-3 bg-beige/50 rounded-xl p-3">
                {inq.message}
              </p>

              <div className="flex items-center gap-4 text-xs text-stone-500">
                <div className="flex items-center gap-1.5">
                  <MessageSquare className="w-3 h-3" />
                  <span>{isAr ? 'نوع الاستفسار: ' : 'Type: '}{inq.type === 'call' ? (isAr ? 'اتصال' : 'Call') : inq.type === 'visit' ? (isAr ? 'زيارة' : 'Visit') : inq.type === 'offer' ? (isAr ? 'عرض' : 'Offer') : inq.type}</span>
                </div>
                {inq.offer_amount && (
                  <div className="flex items-center gap-1.5">
                    <span>{isAr ? 'قيمة العرض: ' : 'Offer: '}{Number(inq.offer_amount).toLocaleString()} $</span>
                  </div>
                )}
              </div>
            </div>
          ))}

          {meta.last_page > 1 && (
            <div className="flex items-center justify-between px-1 py-3 text-sm text-stone-500">
              <span>{isAr ? `صفحة ${meta.current_page} من ${meta.last_page}` : `Page ${meta.current_page} of ${meta.last_page}`}</span>
              <div className="flex gap-2">
                <button disabled={meta.current_page <= 1} onClick={() => load(meta.current_page - 1)}
                  className="p-1.5 rounded-lg hover:bg-beige disabled:opacity-30 transition-all">
                  <ChevronLeft className="w-4 h-4 lucide-rtl" />
                </button>
                <button disabled={meta.current_page >= meta.last_page} onClick={() => load(meta.current_page + 1)}
                  className="p-1.5 rounded-lg hover:bg-beige disabled:opacity-30 transition-all">
                  <ChevronRight className="w-4 h-4 lucide-rtl" />
                </button>
              </div>
            </div>
          )}
        </div>
      )}
    </div>
  );
}
