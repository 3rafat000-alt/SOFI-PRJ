import { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { Save, Loader2 } from 'lucide-react';
import { useAuth } from '../../auth/AuthContext';
import { updateProfile } from '../../api/auth';

export default function UserProfile() {
  const { t, i18n } = useTranslation();
  const isAr = i18n.language === 'ar';
  const { user, refreshUser } = useAuth();
  const [form, setForm] = useState({ name: '', phone: '' });
  const [saving, setSaving] = useState(false);
  const [success, setSuccess] = useState('');

  useEffect(() => {
    if (user) setForm({ name: user.name, phone: user.phone || '' });
  }, [user]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSaving(true);
    setSuccess('');
    try {
      await updateProfile(form);
      await refreshUser();
      setSuccess(isAr ? 'تم حفظ التغييرات' : 'Saved successfully');
      setTimeout(() => setSuccess(''), 3000);
    } catch (err) {
      console.error(err);
    } finally {
      setSaving(false);
    }
  };

  if (!user) return null;

  return (
    <div>
      <h1 className="text-2xl font-bold text-stone-900 mb-6">{isAr ? 'الملف الشخصي' : 'My Profile'}</h1>

      {/* Profile header */}
      <div className="card-3d p-6 md:p-8 bg-primary/5 mb-6">
        <div className="flex items-center gap-5">
          <div className="w-16 h-16 rounded-2xl bg-primary flex items-center justify-center text-white font-bold text-2xl shadow-lg">
            {(user.name || 'U').charAt(0)}
          </div>
          <div>
            <h2 className="text-xl font-bold text-stone-900">{user.name}</h2>
            <p className="text-stone-500 text-sm">{user.email}</p>
            <div className="flex items-center gap-2 mt-1">
              <span className="inline-block px-2.5 py-0.5 rounded-full bg-primary/10 text-primary text-xs font-medium">
                {user.roles?.map((r: any) => r.name).join(', ') || (isAr ? 'مستخدم' : 'User')}
              </span>
            </div>
          </div>
        </div>
      </div>

      {/* Edit form */}
      <div className="card-3d p-6 md:p-8 max-w-lg">
        {success && (
          <div className="bg-primary/10 text-primary text-sm rounded-xl px-4 py-3 mb-5 text-center">{success}</div>
        )}

        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <label className="text-sm font-medium text-stone-700 mb-1.5 block">{t('auth.name')}</label>
            <input required value={form.name} onChange={e => setForm({...form, name: e.target.value})}
              className="input-field" />
          </div>
          <div>
            <label className="text-sm font-medium text-stone-700 mb-1.5 block">{t('auth.email')}</label>
            <input value={user.email} disabled className="input-field opacity-60 cursor-not-allowed" dir="ltr" />
            <p className="text-xs text-stone-400 mt-1">{isAr ? 'البريد الإلكتروني لا يمكن تغييره' : 'Email cannot be changed'}</p>
          </div>
          <div>
            <label className="text-sm font-medium text-stone-700 mb-1.5 block">{t('auth.phone')}</label>
            <input value={form.phone} onChange={e => setForm({...form, phone: e.target.value})}
              className="input-field" dir="ltr" />
          </div>
          <button type="submit" disabled={saving} className="btn-primary flex items-center gap-2 !py-3">
            {saving ? <Loader2 className="w-4 h-4 animate-spin" /> : <Save className="w-4 h-4" />}
            {isAr ? 'حفظ التغييرات' : 'Save Changes'}
          </button>
        </form>
      </div>
    </div>
  );
}
