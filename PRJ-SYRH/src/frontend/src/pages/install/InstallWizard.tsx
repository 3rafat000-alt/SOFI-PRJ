import { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { useNavigate } from 'react-router-dom';
import {
  CheckCircle2, XCircle, Database, UserPlus, Settings, Check, Loader2,
  Building2, Server, Shield, Globe, ArrowRight,
} from 'lucide-react';
import {
  fetchRequirements, saveDatabaseConfig,
  saveAdmin, fetchSettings, saveSettings, fetchComplete,
  type RequirementCheck,
} from '../../api/install';

const STEPS = [
  { key: 'requirements', icon: Server, labelAr: 'المتطلبات', labelEn: 'Requirements' },
  { key: 'database', icon: Database, labelAr: 'قاعدة البيانات', labelEn: 'Database' },
  { key: 'admin', icon: Shield, labelAr: 'المدير', labelEn: 'Admin' },
  { key: 'settings', icon: Settings, labelAr: 'الإعدادات', labelEn: 'Settings' },
  { key: 'complete', icon: Check, labelAr: 'اكتمال', labelEn: 'Complete' },
];

export default function InstallWizard() {
  const { i18n } = useTranslation();
  const isAr = i18n.language === 'ar';
  const navigate = useNavigate();

  const [step, setStep] = useState(0);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');

  // Step 1 state
  const [checks, setChecks] = useState<RequirementCheck[]>([]);
  const [allPassed, setAllPassed] = useState(false);

  // Step 2 state
  const [dbDriver, setDbDriver] = useState('sqlite');
  const [dbForm, setDbForm] = useState({ host: '127.0.0.1', port: '3306', name: '', user: 'root', password: '' });

  // Step 3 state
  const [adminForm, setAdminForm] = useState({ name: '', email: '', phone: '', password: '', password_confirmation: '' });

  // Step 4 state
  const [settingsForm, setSettingsForm] = useState({ app_name: 'سوريا هومز', app_url: '', currency: 'USD' });

  // Step 5 state
  const [completeData, setCompleteData] = useState<any>(null);

  useEffect(() => {
    if (step === 0) loadRequirements();
    else if (step === 2) { /* nothing to preload */ }
    else if (step === 3) loadSettings();
  }, [step]);

  const loadRequirements = async () => {
    setLoading(true);
    try {
      const res = await fetchRequirements();
      setChecks(res.data.checks);
      setAllPassed(res.data.allPassed);
    } catch (e: any) {
      setError(e?.response?.data?.message || 'فشل تحميل المتطلبات');
    }
    setLoading(false);
  };

  const loadSettings = async () => {
    try {
      const res = await fetchSettings();
      const d = res.data.defaults;
      setSettingsForm({
        app_name: d.app_name || 'سوريا هومز',
        app_url: d.app_url || window.location.origin,
        currency: d.currency || 'USD',
      });
    } catch {}
  };

  const nextStep = () => {
    setError('');
    setSuccess('');
    setStep(s => Math.min(s + 1, STEPS.length - 1));
  };

  const prevStep = () => {
    setError('');
    setSuccess('');
    setStep(s => Math.max(s - 1, 0));
  };

  const handleDatabase = async () => {
    setLoading(true); setError('');
    try {
      const data: any = { driver: dbDriver };
      if (dbDriver !== 'sqlite') {
        data.host = dbForm.host;
        data.port = dbForm.port;
        data.name = dbForm.name;
        data.user = dbForm.user;
        data.password = dbForm.password;
      }
      const res = await saveDatabaseConfig(data);
      if (res.success) {
        setSuccess(res.message);
        setTimeout(() => nextStep(), 1000);
      } else {
        setError(res.message);
      }
    } catch (e: any) {
      setError(e?.response?.data?.message || 'فشل إعداد قاعدة البيانات');
    }
    setLoading(false);
  };

  const handleAdmin = async () => {
    if (adminForm.password !== adminForm.password_confirmation) {
      setError(isAr ? 'كلمة المرور غير متطابقة' : 'Passwords do not match');
      return;
    }
    setLoading(true); setError('');
    try {
      const res = await saveAdmin(adminForm);
      if (res.success) {
        setSuccess(res.message);
        setTimeout(() => nextStep(), 1000);
      } else {
        setError(res.message);
      }
    } catch (e: any) {
      const err = e?.response?.data?.errors;
      if (err) {
        const first = Object.values(err).flat()[0];
        setError(first as string);
      } else {
        setError(e?.response?.data?.message || 'فشل إنشاء الحساب');
      }
    }
    setLoading(false);
  };

  const handleSettings = async () => {
    setLoading(true); setError('');
    try {
      const res = await saveSettings(settingsForm);
      if (res.success) {
        setSuccess(res.message);
        setTimeout(() => {
          fetchComplete().then(r => setCompleteData(r.data));
          nextStep();
        }, 500);
      } else {
        setError(res.message);
      }
    } catch (e: any) {
      setError(e?.response?.data?.message || 'فشل حفظ الإعدادات');
    }
    setLoading(false);
  };

  const renderStep = () => {
    switch (step) {
      case 0: return renderRequirements();
      case 1: return renderDatabase();
      case 2: return renderAdmin();
      case 3: return renderSettings();
      case 4: return renderComplete();
      default: return null;
    }
  };

  // ── Step 1 ──
  const renderRequirements = () => (
    <div>
      <Server className="w-12 h-12 text-primary mx-auto mb-4" />
      <h2 className="text-xl font-bold text-center mb-2">{isAr ? 'التحقق من المتطلبات' : 'Requirements Check'}</h2>
      <p className="text-sm text-stone-500 text-center mb-6">
        {isAr ? 'نتحقق من أن الخادم يلبي جميع المتطلبات' : 'Checking if your server meets all requirements'}
      </p>
      {loading ? (
        <div className="flex justify-center py-8"><Loader2 className="w-8 h-8 animate-spin text-primary" /></div>
      ) : (
        <div className="space-y-2 mb-6">
          {checks.map((c, i) => (
            <div key={i} className={`flex items-center justify-between p-3 rounded-xl text-sm ${
              c.passed ? 'bg-green-50' : 'bg-red-50'
            }`}>
              <span className={c.passed ? 'text-green-700' : 'text-red-700'}>{c.name}</span>
              <div className="flex items-center gap-2">
                {c.value && <span className="text-xs text-stone-400">{c.value}</span>}
                {c.passed ? <CheckCircle2 className="w-4 h-4 text-green-500" /> : <XCircle className="w-4 h-4 text-red-500" />}
              </div>
            </div>
          ))}
        </div>
      )}
      {!allPassed && !loading && (
        <div className="bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 text-sm text-amber-700 mb-4">
          {isAr ? 'بعض المتطلبات غير مستوفاة. يرجى تصحيحها قبل المتابعة.' : 'Some requirements are not met. Please fix them before proceeding.'}
        </div>
      )}
      <button onClick={nextStep} disabled={!allPassed || loading}
        className="btn-primary w-full flex items-center justify-center gap-2 !py-3 disabled:opacity-50">
        {isAr ? 'متابعة' : 'Continue'} <ArrowRight className="w-4 h-4 lucide-rtl" />
      </button>
    </div>
  );

  // ── Step 2 ──
  const renderDatabase = () => (
    <div>
      <Database className="w-12 h-12 text-primary mx-auto mb-4" />
      <h2 className="text-xl font-bold text-center mb-2">{isAr ? 'إعداد قاعدة البيانات' : 'Database Setup'}</h2>
      <p className="text-sm text-stone-500 text-center mb-6">
        {isAr ? 'اختر نوع قاعدة البيانات وأدخل معلومات الاتصال' : 'Choose database type and enter connection details'}
      </p>

      <div className="grid grid-cols-3 gap-3 mb-6">
        {['sqlite', 'mysql', 'pgsql'].map(d => (
          <button key={d} onClick={() => setDbDriver(d)}
            className={`p-3 rounded-2xl border-2 text-center transition-all ${
              dbDriver === d ? 'border-primary bg-primary/5' : 'border-beige-dark/50 hover:border-stone-300'
            }`}>
            <div className="text-lg font-bold text-stone-800 uppercase text-xs">{d}</div>
            <div className="text-2xs text-stone-400 mt-1">
              {d === 'sqlite' ? (isAr ? 'بسيط' : 'Simple') :
               d === 'mysql' ? (isAr ? 'شائع' : 'Popular') :
               (isAr ? 'متقدم' : 'Advanced')}
            </div>
          </button>
        ))}
      </div>

      {dbDriver !== 'sqlite' && (
        <div className="space-y-4 mb-6">
          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="text-sm font-medium text-stone-700 mb-1.5 block">{isAr ? 'المضيف' : 'Host'}</label>
              <input value={dbForm.host} onChange={e => setDbForm({...dbForm, host: e.target.value})} className="input-field" dir="ltr" />
            </div>
            <div>
              <label className="text-sm font-medium text-stone-700 mb-1.5 block">{isAr ? 'المنفذ' : 'Port'}</label>
              <input value={dbForm.port} onChange={e => setDbForm({...dbForm, port: e.target.value})} className="input-field" dir="ltr" />
            </div>
          </div>
          <div>
            <label className="text-sm font-medium text-stone-700 mb-1.5 block">{isAr ? 'اسم قاعدة البيانات' : 'Database Name'}</label>
            <input value={dbForm.name} onChange={e => setDbForm({...dbForm, name: e.target.value})} className="input-field" dir="ltr" />
          </div>
          <div>
            <label className="text-sm font-medium text-stone-700 mb-1.5 block">{isAr ? 'اسم المستخدم' : 'Username'}</label>
            <input value={dbForm.user} onChange={e => setDbForm({...dbForm, user: e.target.value})} className="input-field" dir="ltr" />
          </div>
          <div>
            <label className="text-sm font-medium text-stone-700 mb-1.5 block">{isAr ? 'كلمة المرور' : 'Password'}</label>
            <input type="password" value={dbForm.password} onChange={e => setDbForm({...dbForm, password: e.target.value})} className="input-field" dir="ltr" />
          </div>
        </div>
      )}

      {dbDriver === 'sqlite' && (
        <div className="bg-blue-50 rounded-xl px-4 py-3 text-sm text-blue-700 mb-6">
          {isAr ? 'سيتم استخدام SQLite — مناسب للتجربة والتطوير. للإنتاج، استخدم MySQL.' : 'SQLite will be used — good for testing and development. For production, use MySQL.'}
        </div>
      )}

      <div className="flex gap-3">
        <button onClick={prevStep} className="flex-1 px-4 py-2.5 rounded-xl border border-beige-dark text-stone-600 hover:bg-beige transition-all">
          {isAr ? 'رجوع' : 'Back'}
        </button>
        <button onClick={handleDatabase} disabled={loading} className="flex-[2] btn-primary flex items-center justify-center gap-2 !py-2.5 disabled:opacity-50">
          {loading ? <Loader2 className="w-4 h-4 animate-spin" /> : null}
          {isAr ? 'إعداد وتشغيل الترحيلات' : 'Setup & Run Migrations'}
        </button>
      </div>
    </div>
  );

  // ── Step 3 ──
  const renderAdmin = () => (
    <div>
      <Shield className="w-12 h-12 text-primary mx-auto mb-4" />
      <h2 className="text-xl font-bold text-center mb-2">{isAr ? 'إنشاء حساب المدير' : 'Create Admin Account'}</h2>
      <p className="text-sm text-stone-500 text-center mb-6">
        {isAr ? 'أنشئ حساب المسؤول الرئيسي للمنصة' : 'Create the main administrator account'}
      </p>

      <div className="space-y-4 mb-6">
        <div>
          <label className="text-sm font-medium text-stone-700 mb-1.5 block">{isAr ? 'الاسم' : 'Name'}</label>
          <input value={adminForm.name} onChange={e => setAdminForm({...adminForm, name: e.target.value})}
            className="input-field" placeholder={isAr ? 'الاسم الكامل' : 'Full name'} />
        </div>
        <div>
          <label className="text-sm font-medium text-stone-700 mb-1.5 block">{isAr ? 'البريد الإلكتروني' : 'Email'}</label>
          <input type="email" value={adminForm.email} onChange={e => setAdminForm({...adminForm, email: e.target.value})}
            className="input-field" dir="ltr" placeholder="admin@example.com" />
        </div>
        <div>
          <label className="text-sm font-medium text-stone-700 mb-1.5 block">{isAr ? 'رقم الهاتف' : 'Phone'}</label>
          <input value={adminForm.phone} onChange={e => setAdminForm({...adminForm, phone: e.target.value})}
            className="input-field" dir="ltr" />
        </div>
        <div>
          <label className="text-sm font-medium text-stone-700 mb-1.5 block">{isAr ? 'كلمة المرور' : 'Password'}</label>
          <input type="password" value={adminForm.password} onChange={e => setAdminForm({...adminForm, password: e.target.value})}
            className="input-field" placeholder={isAr ? '8 أحرف على الأقل' : 'At least 8 characters'} />
        </div>
        <div>
          <label className="text-sm font-medium text-stone-700 mb-1.5 block">{isAr ? 'تأكيد كلمة المرور' : 'Confirm Password'}</label>
          <input type="password" value={adminForm.password_confirmation} onChange={e => setAdminForm({...adminForm, password_confirmation: e.target.value})}
            className="input-field" />
        </div>
      </div>

      <div className="flex gap-3">
        <button onClick={prevStep} className="flex-1 px-4 py-2.5 rounded-xl border border-beige-dark text-stone-600 hover:bg-beige transition-all">
          {isAr ? 'رجوع' : 'Back'}
        </button>
        <button onClick={handleAdmin} disabled={loading || !adminForm.name || !adminForm.email || !adminForm.password}
          className="flex-[2] btn-primary flex items-center justify-center gap-2 !py-2.5 disabled:opacity-50">
          {loading ? <Loader2 className="w-4 h-4 animate-spin" /> : <UserPlus className="w-4 h-4" />}
          {isAr ? 'إنشاء الحساب' : 'Create Account'}
        </button>
      </div>
    </div>
  );

  // ── Step 4 ──
  const renderSettings = () => (
    <div>
      <Settings className="w-12 h-12 text-primary mx-auto mb-4" />
      <h2 className="text-xl font-bold text-center mb-2">{isAr ? 'إعدادات المنصة' : 'Platform Settings'}</h2>
      <p className="text-sm text-stone-500 text-center mb-6">
        {isAr ? 'الإعدادات النهائية قبل إطلاق المنصة' : 'Final settings before launching the platform'}
      </p>

      <div className="space-y-4 mb-6">
        <div>
          <label className="text-sm font-medium text-stone-700 mb-1.5 block">{isAr ? 'اسم المنصة' : 'Site Name'}</label>
          <input value={settingsForm.app_name} onChange={e => setSettingsForm({...settingsForm, app_name: e.target.value})}
            className="input-field" />
        </div>
        <div>
          <label className="text-sm font-medium text-stone-700 mb-1.5 block">{isAr ? 'رابط المنصة' : 'Site URL'}</label>
          <input value={settingsForm.app_url} onChange={e => setSettingsForm({...settingsForm, app_url: e.target.value})}
            className="input-field" dir="ltr" placeholder="https://example.com" />
        </div>
        <div>
          <label className="text-sm font-medium text-stone-700 mb-1.5 block">{isAr ? 'العملة الافتراضية' : 'Default Currency'}</label>
          <select value={settingsForm.currency} onChange={e => setSettingsForm({...settingsForm, currency: e.target.value})}
            className="input-field">
            <option value="USD">USD - دولار أمريكي</option>
            <option value="SYP">SYP - ليرة سورية</option>
            <option value="EUR">EUR - يورو</option>
            <option value="TRY">TRY - ليرة تركية</option>
          </select>
        </div>
      </div>

      <div className="flex gap-3">
        <button onClick={prevStep} className="flex-1 px-4 py-2.5 rounded-xl border border-beige-dark text-stone-600 hover:bg-beige transition-all">
          {isAr ? 'رجوع' : 'Back'}
        </button>
        <button onClick={handleSettings} disabled={loading}
          className="flex-[2] btn-primary flex items-center justify-center gap-2 !py-2.5 disabled:opacity-50">
          {loading ? <Loader2 className="w-4 h-4 animate-spin" /> : <Globe className="w-4 h-4" />}
          {isAr ? 'تثبيت المنصة' : 'Install Platform'}
        </button>
      </div>
    </div>
  );

  // ── Step 5 ──
  const renderComplete = () => (
    <div className="text-center">
      <div className="w-16 h-16 rounded-2xl bg-green-100 flex items-center justify-center mx-auto mb-4">
        <CheckCircle2 className="w-8 h-8 text-green-600" />
      </div>
      <h2 className="text-xl font-bold text-stone-900 mb-2">{isAr ? 'تم التثبيت بنجاح!' : 'Installation Complete!'}</h2>
      <p className="text-sm text-stone-500 mb-6">
        {isAr ? 'تم إعداد المنصة وتشغيلها. يمكنك الآن تسجيل الدخول.' : 'The platform has been set up and is ready. You can now log in.'}
      </p>

      {completeData && (
        <div className="card-3d p-4 mb-6 text-right">
          <div className="space-y-2 text-sm">
            <div className="flex justify-between"><span className="text-stone-500">{isAr ? 'اسم المنصة' : 'Site Name'}</span><span className="font-medium">{completeData.app_name}</span></div>
            <div className="flex justify-between"><span className="text-stone-500">{isAr ? 'رابط المنصة' : 'Site URL'}</span><span className="font-medium">{completeData.app_url}</span></div>
            <div className="flex justify-between"><span className="text-stone-500">{isAr ? 'بريد المدير' : 'Admin Email'}</span><span className="font-medium">{completeData.admin_email}</span></div>
            <div className="flex justify-between"><span className="text-stone-500">{isAr ? 'تاريخ التثبيت' : 'Installed At'}</span><span className="font-medium">{completeData.installed_at}</span></div>
          </div>
        </div>
      )}

      <button onClick={() => navigate('/login')}
        className="btn-primary w-full flex items-center justify-center gap-2 !py-3">
        <UserPlus className="w-4 h-4" />
        {isAr ? 'تسجيل الدخول' : 'Log In'}
      </button>
    </div>
  );

  // ── Main render ──
  return (
    <div dir={isAr ? 'rtl' : 'ltr'} className="min-h-screen bg-gradient-to-br from-beige/80 via-white to-beige/40 flex items-center justify-center p-4">
      <div className="w-full max-w-lg">
        {/* Logo */}
        <div className="text-center mb-8">
          <div className="w-16 h-16 rounded-2xl bg-primary flex items-center justify-center mx-auto mb-3 shadow-lg shadow-primary/20">
            <Building2 className="w-8 h-8 text-white" />
          </div>
          <h1 className="text-2xl font-bold text-primary">{isAr ? 'تثبيت سوريا هومز' : 'Syria Homes Setup'}</h1>
          <p className="text-sm text-stone-500 mt-1">{isAr ? 'معالج التثبيت خطوة بخطوة' : 'Step-by-step installation wizard'}</p>
        </div>

        {/* Steps bar */}
        <div className="flex items-center justify-between mb-8 px-2">
          {STEPS.map((s, i) => (
            <div key={s.key} className="flex items-center">
              <div className={`flex items-center justify-center w-9 h-9 rounded-xl text-xs font-bold transition-all ${
                i < step ? 'bg-primary text-white' :
                i === step ? 'bg-primary/10 text-primary border-2 border-primary' :
                'bg-beige text-stone-400'
              }`}>
                {i < step ? <Check className="w-4 h-4" /> : <s.icon className="w-4 h-4" />}
              </div>
              {i < STEPS.length - 1 && (
                <div className={`w-8 h-0.5 mx-1 ${i < step ? 'bg-primary' : 'bg-beige-dark'}`} />
              )}
            </div>
          ))}
        </div>

        {/* Step labels */}
        <div className="flex justify-between text-2xs text-stone-400 mb-6 px-1">
          {STEPS.map((s, i) => (
            <span key={s.key} className={i === step ? 'text-primary font-medium' : ''}>
              {isAr ? s.labelAr : s.labelEn}
            </span>
          ))}
        </div>

        {/* Card */}
        <div className="card-3d p-6 md:p-8">
          {error && (
            <div className="bg-red-50 border border-red-100 text-red-600 text-sm rounded-xl px-4 py-3 mb-5 text-center">{error}</div>
          )}
          {success && (
            <div className="bg-green-50 border border-green-100 text-green-600 text-sm rounded-xl px-4 py-3 mb-5 text-center">{success}</div>
          )}

          {renderStep()}
        </div>
      </div>
    </div>
  );
}
