import { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { Loader2, Plus, X, TrendingUp } from 'lucide-react';
import { fetchAgencyDeals, storeAgencyDeal, fetchAgencyProperties, type AgencyDeal } from '../../api/agency';
import type { PropertyCard } from '../../api/client';
import axios from 'axios';

export default function AgencyDeals() {
  const { i18n } = useTranslation();
  const isAr = i18n.language === 'ar';
  const L = (ar: string, en: string) => isAr ? ar : en;
  const [deals, setDeals] = useState<AgencyDeal[]>([]);
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [showForm, setShowForm] = useState(false);
  const [properties, setProperties] = useState<PropertyCard[]>([]);
  const [form, setForm] = useState({ property_id: '', type: 'sale', price: '', currency: 'USD', deal_date: new Date().toISOString().split('T')[0], client_name: '', client_phone: '', notes: '' });
  const [error, setError] = useState('');
  const [saving, setSaving] = useState(false);

  const statusMap: Record<string, string> = {
    pending: L('قيد الانتظار', 'Pending'),
    confirmed: L('مؤكد', 'Confirmed'),
    cancelled: L('ملغي', 'Cancelled'),
  };

  const statusColor: Record<string, string> = {
    pending: 'badge-gold',
    confirmed: 'badge-primary',
    cancelled: 'badge-red',
  };

  const load = () => {
    setLoading(true);
    fetchAgencyDeals({ page: String(page) }).then(res => {
      setDeals(res.data);
      setLastPage(res.meta?.last_page ?? 1);
    }).finally(() => setLoading(false));
  };

  useEffect(() => { load(); }, [page]);

  const openAdd = () => {
    fetchAgencyProperties().then(r => setProperties(r.data));
    setShowForm(true);
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    setSaving(true);
    try {
      await storeAgencyDeal({
        ...form,
        price: Number(form.price),
        deal_date: form.deal_date,
      });
      setForm({ property_id: '', type: 'sale', price: '', currency: 'USD', deal_date: new Date().toISOString().split('T')[0], client_name: '', client_phone: '', notes: '' });
      setShowForm(false);
      load();
    } catch (err: any) {
      if (axios.isAxiosError(err) && err.response?.data?.errors) {
        setError(Object.values(err.response.data.errors).flat()[0] as string);
      } else {
        setError(L('خطأ في حفظ الصفقة', 'Error saving deal'));
      }
    } finally {
      setSaving(false);
    }
  };

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-2xl font-bold text-stone-900">{L('الصفقات', 'Deals')}</h1>
        <button onClick={openAdd} className="btn-primary text-sm flex items-center gap-2 !py-2 !px-4">
          <Plus className="w-4 h-4" /> {L('إضافة صفقة', 'Add Deal')}
        </button>
      </div>

      {loading ? (
        <div className="flex items-center justify-center h-48"><Loader2 className="w-8 h-8 animate-spin text-primary" /></div>
      ) : deals.length === 0 ? (
        <div className="text-center py-16">
          <TrendingUp className="w-12 h-12 text-stone-300 mx-auto mb-3" />
          <p className="text-stone-500">{L('لا توجد صفقات بعد', 'No deals yet')}</p>
        </div>
      ) : (
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-beige-dark/50 text-stone-500">
                <th className="text-right py-3 px-4 font-medium">{L('العميل', 'Client')}</th>
                <th className="text-right py-3 px-4 font-medium">{L('العقار', 'Property')}</th>
                <th className="text-right py-3 px-4 font-medium">{L('المبلغ', 'Amount')}</th>
                <th className="text-right py-3 px-4 font-medium">{L('العمولة', 'Commission')}</th>
                <th className="text-right py-3 px-4 font-medium">{L('الحالة', 'Status')}</th>
                <th className="text-right py-3 px-4 font-medium">{L('التاريخ', 'Date')}</th>
              </tr>
            </thead>
            <tbody>
              {deals.map(d => (
                <tr key={d.id} className="border-b border-beige-dark/20 hover:bg-beige/50">
                  <td className="py-3 px-4 font-medium text-stone-900">{d.client_name}</td>
                  <td className="py-3 px-4 text-stone-600 truncate max-w-[200px]">{isAr ? d.property?.title_ar : d.property?.title_en}</td>
                  <td className="py-3 px-4">{Number(d.price).toLocaleString()} ${d.currency}</td>
                  <td className="py-3 px-4 text-emerald-600 font-medium">{Number(d.commission_amount).toLocaleString()} $</td>
                  <td className="py-3 px-4">
                    <span className={statusColor[d.status]}>{statusMap[d.status]}</span>
                  </td>
                  <td className="py-3 px-4 text-stone-500">{new Date(d.deal_date).toLocaleDateString()}</td>
                </tr>
              ))}
            </tbody>
          </table>

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

      {/* Add deal modal */}
      {showForm && (
        <div className="fixed inset-0 bg-black/30 z-50 flex items-center justify-center p-4" onClick={() => setShowForm(false)}>
          <div className="bg-white rounded-2xl p-6 w-full max-w-md shadow-xl" onClick={e => e.stopPropagation()}>
            <div className="flex items-center justify-between mb-5">
              <h3 className="font-bold text-stone-900">{L('إضافة صفقة', 'Add Deal')}</h3>
              <button onClick={() => setShowForm(false)} className="p-1 hover:bg-beige rounded-lg">
                <X className="w-5 h-5 text-stone-400" />
              </button>
            </div>
            {error && <div className="bg-red-50 text-red-600 text-sm rounded-xl px-4 py-3 mb-4">{error}</div>}
            <form onSubmit={handleSubmit} className="space-y-3.5">
              <input required value={form.client_name} onChange={e => setForm({...form, client_name: e.target.value})}
                placeholder={L('اسم العميل', 'Client Name')} className="input-field text-sm" />
              <input value={form.client_phone} onChange={e => setForm({...form, client_phone: e.target.value})}
                placeholder={L('هاتف العميل', 'Client Phone')} className="input-field text-sm" />
              <select value={form.type} onChange={e => setForm({...form, type: e.target.value})} className="input-field text-sm">
                <option value="sale">{L('بيع', 'Sale')}</option>
                <option value="rent">{L('إيجار', 'Rent')}</option>
              </select>
              <select value={form.property_id} onChange={e => setForm({...form, property_id: e.target.value})} required className="input-field text-sm">
                <option value="">{L('اختر العقار', 'Select property')}</option>
                {properties.map(p => <option key={p.id} value={p.id}>{isAr ? p.title_ar : p.title_en}</option>)}
              </select>
              <div className="flex gap-2">
                <input type="number" required value={form.price} onChange={e => setForm({...form, price: e.target.value})}
                  placeholder={L('السعر', 'Price')} className="input-field text-sm flex-1" />
                <select value={form.currency} onChange={e => setForm({...form, currency: e.target.value})} className="input-field text-sm w-20">
                  <option>USD</option>
                  <option>SYP</option>
                </select>
              </div>
              <input type="date" required value={form.deal_date} onChange={e => setForm({...form, deal_date: e.target.value})}
                className="input-field text-sm" />
              <textarea value={form.notes} onChange={e => setForm({...form, notes: e.target.value})}
                placeholder={L('ملاحظات', 'Notes')} className="input-field text-sm" rows={2} />
              <button type="submit" disabled={saving} className="btn-primary w-full !py-3">
                {saving ? <Loader2 className="w-4 h-4 animate-spin mx-auto" /> : L('إضافة', 'Add Deal')}
              </button>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}
