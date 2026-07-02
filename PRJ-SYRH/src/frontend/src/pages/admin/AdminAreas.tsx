import { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { Loader2, Plus, Edit3, X, Search, MapPin } from 'lucide-react';
import { fetchAdminAreas, createAdminArea, updateAdminArea, deleteAdminArea, fetchAdminGovernorates, type AdminArea, type AdminGovernorate } from '../../api/admin';

interface AreaForm {
  governorate_id: string;
  name_ar: string;
  name_en: string;
  lat: string;
  lng: string;
}

const emptyForm = (): AreaForm => ({ governorate_id: '', name_ar: '', name_en: '', lat: '', lng: '' });

export default function AdminAreas() {
  const { i18n } = useTranslation();
  const isAr = i18n.language === 'ar';
  const L = (ar: string, en: string) => isAr ? ar : en;

  const [areas, setAreas] = useState<AdminArea[]>([]);
  const [governorates, setGovernorates] = useState<AdminGovernorate[]>([]);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [page, setPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [search, setSearch] = useState('');
  const [showForm, setShowForm] = useState(false);
  const [editId, setEditId] = useState<number | null>(null);
  const [form, setForm] = useState<AreaForm>(emptyForm());

  const loadAreas = async () => {
    setLoading(true);
    try {
      const res = await fetchAdminAreas(page);
      setAreas(res.data);
      setTotalPages(res.meta.last_page);
    } catch (err) {
      console.error('Failed to load areas', err);
    } finally {
      setLoading(false);
    }
  };

  const loadGovernorates = async () => {
    try {
      setGovernorates(await fetchAdminGovernorates());
    } catch (err) {
      console.error('Failed to load governorates', err);
    }
  };

  useEffect(() => { loadAreas(); }, [page]);
  useEffect(() => { loadGovernorates(); }, []);

  const filtered = search
    ? areas.filter(a => a.name_ar.includes(search) || a.name_en.toLowerCase().includes(search.toLowerCase()))
    : areas;

  const openCreate = () => {
    setEditId(null);
    setForm(emptyForm());
    setShowForm(true);
  };

  const openEdit = (area: AdminArea) => {
    setEditId(area.id);
    setForm({
      governorate_id: area.governorate_id.toString(),
      name_ar: area.name_ar,
      name_en: area.name_en,
      lat: area.lat || '',
      lng: area.lng || '',
    });
    setShowForm(true);
  };

  const handleSave = async () => {
    if (!form.name_ar || !form.name_en || !form.governorate_id) return;
    setSaving(true);
    try {
      const data = {
        governorate_id: parseInt(form.governorate_id),
        name_ar: form.name_ar,
        name_en: form.name_en,
        lat: form.lat || undefined,
        lng: form.lng || undefined,
      };
      if (editId) {
        await updateAdminArea(editId, data);
      } else {
        await createAdminArea(data);
      }
      setShowForm(false);
      loadAreas();
    } catch (err) {
      console.error('Failed to save area', err);
    } finally {
      setSaving(false);
    }
  };

  const handleDelete = async (id: number) => {
    if (!confirm(L('حذف هذه المنطقة؟', 'Delete this area?'))) return;
    try {
      await deleteAdminArea(id);
      loadAreas();
    } catch (err) {
      console.error('Failed to delete area', err);
    }
  };

  const govName = (govId: number) => {
    const g = governorates.find(g => g.id === govId);
    return g ? (isAr ? g.name_ar : g.name_en) : `#${govId}`;
  };

  return (
    <div>
      {/* Header */}
      <div className="flex items-center justify-between mb-6">
        <div>
          <h1 className="text-2xl font-bold text-stone-900">{L('إدارة المناطق', 'Areas Management')}</h1>
          <p className="text-sm text-stone-400">{L('إضافة وتعديل وحذف المناطق', 'Add, edit and delete areas')}</p>
        </div>
        <button onClick={openCreate}
          className="btn-primary !py-2 !px-4 text-sm flex items-center gap-1.5">
          <Plus className="w-4 h-4" /> {L('إضافة منطقة', 'Add Area')}
        </button>
      </div>

      {/* Search */}
      <div className="relative mb-4">
        <Search className="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-stone-300" />
        <input value={search} onChange={e => setSearch(e.target.value)}
          placeholder={L('بحث...', 'Search...')}
          className="input-field pr-10 max-w-xs" />
      </div>

      {/* Form modal */}
      {showForm && (
        <div className="fixed inset-0 bg-black/30 z-50 flex items-center justify-center p-4" onClick={() => setShowForm(false)}>
          <div className="bg-white rounded-2xl p-6 w-full max-w-lg shadow-xl" onClick={e => e.stopPropagation()}>
            <div className="flex items-center justify-between mb-5">
              <h2 className="text-lg font-bold text-stone-800">
                {editId ? L('تعديل المنطقة', 'Edit Area') : L('إضافة منطقة جديدة', 'Add New Area')}
              </h2>
              <button onClick={() => setShowForm(false)} className="p-1 hover:bg-stone-100 rounded-lg">
                <X className="w-5 h-5 text-stone-400" />
              </button>
            </div>
            <div className="space-y-4">
              <div>
                <label className="text-sm font-semibold text-stone-600 mb-1 block">{L('المحافظة', 'Governorate')}</label>
                <select value={form.governorate_id} onChange={e => setForm({...form, governorate_id: e.target.value})}
                  className="input-field appearance-none cursor-pointer">
                  <option value="">{L('اختر المحافظة', 'Select governorate')}</option>
                  {governorates.map(g => (
                    <option key={g.id} value={g.id}>{isAr ? g.name_ar : g.name_en}</option>
                  ))}
                </select>
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="text-sm font-semibold text-stone-600 mb-1 block">{L('الاسم (عربي)', 'Name (Arabic)')}</label>
                  <input value={form.name_ar} onChange={e => setForm({...form, name_ar: e.target.value})}
                    className="input-field" placeholder={L('اسم المنطقة بالعربية', 'Area name in Arabic')} />
                </div>
                <div>
                  <label className="text-sm font-semibold text-stone-600 mb-1 block">{L('الاسم (إنجليزي)', 'Name (English)')}</label>
                  <input value={form.name_en} onChange={e => setForm({...form, name_en: e.target.value})}
                    className="input-field" dir="ltr" placeholder="Area name in English" />
                </div>
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="text-sm font-semibold text-stone-600 mb-1 block">Lat</label>
                  <input value={form.lat} onChange={e => setForm({...form, lat: e.target.value})}
                    className="input-field" dir="ltr" placeholder="33.5138" />
                </div>
                <div>
                  <label className="text-sm font-semibold text-stone-600 mb-1 block">Lng</label>
                  <input value={form.lng} onChange={e => setForm({...form, lng: e.target.value})}
                    className="input-field" dir="ltr" placeholder="36.2765" />
                </div>
              </div>
              <div className="flex gap-3 pt-2">
                <button onClick={handleSave} disabled={saving || !form.name_ar || !form.name_en || !form.governorate_id}
                  className="btn-primary !py-2.5 !px-6 text-sm flex items-center gap-1.5">
                  {saving ? <Loader2 className="w-4 h-4 animate-spin" /> : null}
                  {editId ? L('حفظ التعديلات', 'Save Changes') : L('إضافة', 'Add')}
                </button>
                <button onClick={() => setShowForm(false)}
                  className="text-sm text-stone-400 hover:text-stone-600 transition-colors px-3">
                  {L('إلغاء', 'Cancel')}
                </button>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Table */}
      {loading ? (
        <div className="flex items-center justify-center h-48">
          <Loader2 className="w-8 h-8 animate-spin text-primary" />
        </div>
      ) : (
        <div className="bg-white rounded-2xl border border-beige-dark/20 overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="bg-stone-50 text-stone-500 text-xs uppercase tracking-wider">
                  <th className="text-start px-4 py-3 font-semibold">{L('المحافظة', 'Governorate')}</th>
                  <th className="text-start px-4 py-3 font-semibold">{L('الاسم (عربي)', 'Name (Ar)')}</th>
                  <th className="text-start px-4 py-3 font-semibold">{L('الاسم (إنجليزي)', 'Name (En)')}</th>
                  <th className="text-start px-4 py-3 font-semibold">Slug</th>
                  <th className="text-start px-4 py-3 font-semibold">Lat/Lng</th>
                  <th className="text-center px-4 py-3 font-semibold">{L('إجراءات', 'Actions')}</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-beige-dark/10">
                {filtered.length === 0 ? (
                  <tr><td colSpan={6} className="text-center py-8 text-stone-400">{L('لا توجد مناطق', 'No areas found')}</td></tr>
                ) : filtered.map(area => (
                  <tr key={area.id} className="hover:bg-stone-50/50 transition-colors">
                    <td className="px-4 py-3">
                      <span className="inline-flex items-center gap-1.5 text-xs bg-primary/5 text-primary px-2.5 py-1 rounded-lg font-medium">
                        <MapPin className="w-3 h-3" />
                        {govName(area.governorate_id)}
                      </span>
                    </td>
                    <td className="px-4 py-3 font-medium text-stone-800">{area.name_ar}</td>
                    <td className="px-4 py-3 text-stone-500">{area.name_en}</td>
                    <td className="px-4 py-3 text-stone-400 font-mono text-xs">{area.slug}</td>
                    <td className="px-4 py-3 text-stone-400 text-xs font-mono">
                      {area.lat && area.lng ? `${parseFloat(area.lat).toFixed(4)}, ${parseFloat(area.lng).toFixed(4)}` : '-'}
                    </td>
                    <td className="px-4 py-3 text-center">
                      <div className="flex items-center justify-center gap-1">
                        <button onClick={() => openEdit(area)}
                          className="p-1.5 hover:bg-stone-100 rounded-lg text-stone-400 hover:text-primary transition-colors">
                          <Edit3 className="w-3.5 h-3.5" />
                        </button>
                        <button onClick={() => handleDelete(area.id)}
                          className="p-1.5 hover:bg-red-50 rounded-lg text-stone-400 hover:text-red-500 transition-colors">
                          <X className="w-3.5 h-3.5" />
                        </button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>

          {/* Pagination */}
          {totalPages > 1 && (
            <div className="flex items-center justify-center gap-2 p-4 border-t border-beige-dark/10">
              {Array.from({ length: totalPages }, (_, i) => i + 1).map(p => (
                <button key={p} onClick={() => setPage(p)}
                  className={`w-8 h-8 rounded-lg text-xs font-medium transition-colors ${
                    page === p ? 'bg-primary text-white' : 'text-stone-500 hover:bg-stone-100'
                  }`}>
                  {p}
                </button>
              ))}
            </div>
          )}
        </div>
      )}
    </div>
  );
}
