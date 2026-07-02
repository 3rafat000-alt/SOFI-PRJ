import { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { Loader2, Plus, Edit3, X } from 'lucide-react';
import { fetchPlans, createPlan, updatePlan, type SubscriptionPlan } from '../../api/admin';
import SelectField from '../../components/SelectField';

export default function AdminPlans() {
  const { i18n } = useTranslation();
  const isAr = i18n.language === 'ar';
  const [plans, setPlans] = useState<SubscriptionPlan[]>([]);
  const [loading, setLoading] = useState(true);
  const [showForm, setShowForm] = useState(false);
  const [editId, setEditId] = useState<number | null>(null);
  const [form, setForm] = useState({ name_ar: '', name_en: '', slug: '', price: '', currency: 'USD', duration_days: '30', max_properties: '10', max_agents: '3', is_active: true } as any);

  const load = async () => {
    setLoading(true);
    try { setPlans(await fetchPlans()); } catch {}
    setLoading(false);
  };
  useEffect(() => { load(); }, []);

  const resetForm = () => { setForm({ name_ar: '', name_en: '', slug: '', price: '', currency: 'USD', duration_days: '30', max_properties: '10', max_agents: '3', is_active: true }); setEditId(null); setShowForm(false); };

  const openEdit = (p: SubscriptionPlan) => {
    setForm({ name_ar: p.name_ar, name_en: p.name_en, slug: p.slug, price: p.price, currency: p.currency, duration_days: String(p.duration_days), max_properties: String(p.max_properties), max_agents: String(p.max_agents), is_active: p.is_active });
    setEditId(p.id); setShowForm(true);
  };

  const handleSave = async () => {
    const payload = { ...form, price: Number(form.price), duration_days: Number(form.duration_days), max_properties: Number(form.max_properties), max_agents: Number(form.max_agents) };
    if (editId) await updatePlan(editId, payload);
    else await createPlan(payload);
    resetForm(); load();
  };

  if (loading) return <div className="flex items-center justify-center h-48"><Loader2 className="w-8 h-8 animate-spin text-primary" /></div>;

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-2xl font-bold text-stone-900">{isAr ? 'الباقات' : 'Plans'}</h1>
        <button onClick={() => { resetForm(); setShowForm(true); }} className="btn-primary flex items-center gap-2 !py-2 !px-4 text-sm">
          <Plus className="w-4 h-4" />{isAr ? 'إضافة' : 'Add Plan'}
        </button>
      </div>

      {showForm && (
        <div className="card-3d p-5 mb-6">
          <div className="flex items-center justify-between mb-4">
            <h2 className="font-bold text-stone-900">{editId ? (isAr ? 'تعديل' : 'Edit') : (isAr ? 'إضافة' : 'New')} Plan</h2>
            <button onClick={resetForm} className="p-1 rounded-lg hover:bg-beige text-stone-400"><X className="w-4 h-4" /></button>
          </div>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <input placeholder="Name AR" value={form.name_ar} onChange={e => setForm({...form, name_ar: e.target.value})} className="input-field text-sm" />
            <input placeholder="Name EN" value={form.name_en} onChange={e => setForm({...form, name_en: e.target.value})} className="input-field text-sm" />
            <input placeholder="Slug" value={form.slug} onChange={e => setForm({...form, slug: e.target.value})} className="input-field text-sm" />
            <input placeholder="Price" type="number" value={form.price} onChange={e => setForm({...form, price: e.target.value})} className="input-field text-sm" />
            <SelectField
              value={form.currency}
              onChange={(v) => setForm({...form, currency: v})}
              options={[
                { value: 'USD', label: 'USD' },
                { value: 'SYP', label: 'SYP' },
              ]}
            />
            <input placeholder="Duration (days)" type="number" value={form.duration_days} onChange={e => setForm({...form, duration_days: e.target.value})} className="input-field text-sm" />
            <input placeholder="Max properties" type="number" value={form.max_properties} onChange={e => setForm({...form, max_properties: e.target.value})} className="input-field text-sm" />
            <input placeholder="Max agents" type="number" value={form.max_agents} onChange={e => setForm({...form, max_agents: e.target.value})} className="input-field text-sm" />
          </div>
          <button onClick={handleSave} className="btn-primary !py-2 text-sm">{isAr ? 'حفظ' : 'Save'}</button>
        </div>
      )}

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        {plans.map(p => (
          <div key={p.id} className={`card-3d p-5 ${p.is_active ? '' : 'opacity-60'}`}>
            <div className="flex items-center justify-between mb-3">
              <h3 className="font-bold text-stone-900">{isAr ? p.name_ar : p.name_en}</h3>
              <button onClick={() => openEdit(p)} className="p-1.5 rounded-lg hover:bg-beige text-stone-400"><Edit3 className="w-4 h-4" /></button>
            </div>
            <div className="text-2xl font-bold text-primary mb-3">{Number(p.price).toLocaleString()} {p.currency}</div>
            <div className="space-y-1.5 text-sm text-stone-500">
              <div>{isAr ? 'المدة' : 'Duration'}: {p.duration_days} {isAr ? 'يوم' : 'days'}</div>
              <div>{isAr ? 'أقصى عقارات' : 'Max properties'}: {p.max_properties}</div>
              <div>{isAr ? 'أقصى وكلاء' : 'Max agents'}: {p.max_agents}</div>
            </div>
            {p.is_featured && <span className="badge-gold mt-3 text-xs">{isAr ? 'مميز' : 'Featured'}</span>}
          </div>
        ))}
      </div>
    </div>
  );
}
