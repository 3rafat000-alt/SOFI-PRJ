import { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { Loader2, Save } from 'lucide-react';
import { fetchSettings, updateSettings } from '../../api/admin';

export default function AdminSettings() {
  const { i18n } = useTranslation();
  const isAr = i18n.language === 'ar';
  const [settings, setSettings] = useState<Record<string, { key: string; value: string }[]>>({});
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [success, setSuccess] = useState('');

  useEffect(() => {
    fetchSettings().then(d => { setSettings(d); }).finally(() => setLoading(false));
  }, []);

  const handleSave = async () => {
    setSaving(true);
    const all = Object.values(settings).flat().map(s => ({ key: s.key, value: s.value }));
    try { await updateSettings(all); setSuccess(isAr ? 'تم الحفظ' : 'Saved'); setTimeout(() => setSuccess(''), 3000); } catch {}
    setSaving(false);
  };

  const updateValue = (group: string, key: string, value: string) => {
    setSettings(prev => ({
      ...prev,
      [group]: prev[group].map(s => s.key === key ? { ...s, value } : s),
    }));
  };

  if (loading) return <div className="flex items-center justify-center h-48"><Loader2 className="w-8 h-8 animate-spin text-primary" /></div>;

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-2xl font-bold text-stone-900">{isAr ? 'الإعدادات' : 'Settings'}</h1>
        {success && <span className="text-sm text-emerald-600 font-medium">{success}</span>}
        <button onClick={handleSave} disabled={saving} className="btn-primary flex items-center gap-2 !py-2 !px-4 text-sm">
          <Save className="w-4 h-4" />{isAr ? 'حفظ' : 'Save'}
        </button>
      </div>
      <div className="space-y-4">
        {Object.entries(settings).map(([group, items]) => (
          <div key={group} className="card-3d p-5">
            <h2 className="font-bold text-stone-900 mb-4 capitalize">{group}</h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              {items.map(s => (
                <div key={s.key}>
                  <label className="text-sm font-medium text-stone-700 mb-1 block capitalize">{s.key.replace(/_/g, ' ')}</label>
                  <input value={s.value} onChange={e => updateValue(group, s.key, e.target.value)} className="input-field text-sm" />
                </div>
              ))}
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}
