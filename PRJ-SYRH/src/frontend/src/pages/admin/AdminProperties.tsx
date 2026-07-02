import { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { Loader2, ChevronLeft, ChevronRight, Search } from 'lucide-react';
import { fetchAdminProperties, moderateProperty, type AdminProperty } from '../../api/admin';

export default function AdminProperties() {
  const { i18n } = useTranslation();
  const isAr = i18n.language === 'ar';
  const [data, setData] = useState<AdminProperty[]>([]);
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1 });
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');

  const load = async (page = 1) => {
    setLoading(true);
    try { const res = await fetchAdminProperties(page); setData(res.data); setMeta(res.meta); } catch {}
    setLoading(false);
  };
  useEffect(() => { load(); }, []);

  const handleStatus = async (id: number, status: string) => {
    await moderateProperty(id, { status });
    load(meta.current_page);
  };

  const statusBadge = (s: string) => {
    const colors: Record<string, string> = { available: 'badge-primary', reserved: 'badge-gold', sold: 'badge-red', rented: 'badge-red', draft: 'bg-stone-100 text-stone-500' };
    return colors[s] || 'badge-primary';
  };

  return (
    <div>
      <h1 className="text-2xl font-bold text-stone-900 mb-6">{isAr ? 'إدارة العقارات' : 'Properties'}</h1>
      <div className="card-3d overflow-hidden">
        <div className="p-4 border-b border-beige-dark">
          <div className="relative max-w-xs">
            <Search className={`absolute top-1/2 -translate-y-1/2 w-4 h-4 text-stone-400 ${isAr ? 'right-3' : 'left-3'}`} />
            <input placeholder={isAr ? 'بحث...' : 'Search...'} value={search} onChange={e => setSearch(e.target.value)}
              className={`input-field !py-2 text-sm ${isAr ? '!pr-10' : '!pl-10'}`} />
          </div>
        </div>
        {loading ? (
          <div className="flex items-center justify-center h-48"><Loader2 className="w-8 h-8 animate-spin text-primary" /></div>
        ) : (
          <>
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                  <thead><tr className="bg-beige text-stone-500 text-start">
                  <th className="px-4 py-3 font-medium">{isAr ? 'العنوان' : 'Title'}</th>
                  <th className="px-4 py-3 font-medium">{isAr ? 'الوكالة' : 'Agency'}</th>
                  <th className="px-4 py-3 font-medium">{isAr ? 'النوع' : 'Type'}</th>
                  <th className="px-4 py-3 font-medium">{isAr ? 'الحالة' : 'Status'}</th>
                  <th className="px-4 py-3 font-medium">{isAr ? 'تحكم' : 'Actions'}</th>
                </tr></thead>
                <tbody>
                  {data.filter(p => !search || p.title_ar.includes(search) || p.title_en.includes(search) || p.ref_code.includes(search)).map(p => (
                    <tr key={p.id} className="border-t border-beige-dark hover:bg-beige/50 transition-colors">
                      <td className="px-4 py-3">
                        <div className="font-medium text-stone-800">{isAr ? p.title_ar : p.title_en}</div>
                        <div className="text-xs text-stone-400">{p.ref_code}</div>
                      </td>
                      <td className="px-4 py-3 text-stone-500">{p.agency?.name || '—'}</td>
                      <td className="px-4 py-3 text-stone-500 text-xs">{isAr ? p.type?.name_ar : p.type?.name_en}</td>
                      <td className="px-4 py-3"><span className={`badge ${statusBadge(p.status)}`}>{p.status}</span></td>
                      <td className="px-4 py-3">
                        <div className="flex gap-1">
                          {p.status === 'available' && (
                            <button onClick={() => handleStatus(p.id, 'reserved')} className="px-2.5 py-1 rounded-lg bg-amber-50 text-amber-600 text-xs font-medium hover:bg-amber-100">
                              {isAr ? 'حجز' : 'Reserve'}
                            </button>
                          )}
                          {(p.status === 'available' || p.status === 'reserved') && (
                            <button onClick={() => handleStatus(p.id, 'sold')} className="px-2.5 py-1 rounded-lg bg-red-50 text-red-600 text-xs font-medium hover:bg-red-100">
                              {isAr ? 'بيع' : 'Sold'}
                            </button>
                          )}
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
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
