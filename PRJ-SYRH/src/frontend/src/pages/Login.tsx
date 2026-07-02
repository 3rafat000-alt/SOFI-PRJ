import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Link, useNavigate } from 'react-router-dom';
import {
  Building2, Eye, EyeOff, Loader2, Home, ShieldCheck, Star, Users,
  CheckCircle,
} from 'lucide-react';
import Navbar from '../components/Navbar';
import Footer from '../components/Footer';
import { useAuth } from '../auth/AuthContext';
import { validateLogin, extractServerError, type ValidationErrors } from '../auth/validation';

export default function Login() {
  const { t, i18n } = useTranslation();
  const isAr = i18n.language === 'ar';
  const navigate = useNavigate();
  const { login } = useAuth();
  const [tab, setTab] = useState<'user' | 'agency'>('user');
  const [form, setForm] = useState({ email: '', password: '' });
  const [showPw, setShowPw] = useState(false);
  const [errors, setErrors] = useState<ValidationErrors>({});
  const [serverError, setServerError] = useState('');
  const [submitting, setSubmitting] = useState(false);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setServerError('');
    setErrors({});

    // Client-side validation first
    const fieldErrors = validateLogin(form);
    if (Object.keys(fieldErrors).length > 0) {
      setErrors(fieldErrors);
      return;
    }

    setSubmitting(true);
    try {
      const userData = await login(form.email, form.password);
      const userRoles = userData?.roles?.map((r: any) => r.name) || [];
      if (userRoles.includes('admin')) navigate('/admin');
      else if (userRoles.includes('agency')) navigate('/dashboard');
      else navigate('/user/dashboard');
    } catch (err: any) {
      setServerError(t(extractServerError(err)));
    } finally {
      setSubmitting(false);
    }
  };

  const trustStats = [
    { icon: Users, value: '+15,000', label: t('login.registeredUsers') },
    { icon: Building2, value: '+250', label: t('login.agencies') },
    { icon: Star, value: '94%', label: t('login.satisfaction') },
  ];

  const brandFeatures = [
    t('login.featureVerified'),
    t('login.featureSupport'),
    t('login.featurePrices'),
    t('login.featureMatch'),
  ];

  return (
    <div dir={isAr ? 'rtl' : 'ltr'} className="min-h-screen flex flex-col">
      <Navbar />
      <div className="flex-1 flex pt-16 md:pt-20">
        {/* ═══════ Left — Brand Side ═══════ */}
        <div className="hidden lg:flex lg:w-1/2 relative bg-gradient-to-br from-primary-dark via-primary to-primary-light overflow-hidden">
          <div className="absolute inset-0 overflow-hidden pointer-events-none">
            <div className="absolute w-[500px] h-[500px] rounded-full bg-white/5 -top-20 -right-20 blur-3xl" />
            <div className="absolute w-[400px] h-[400px] rounded-full bg-gold/8 -bottom-20 -left-20 blur-3xl" />
            <div className="absolute w-[300px] h-[300px] rounded-full bg-white/4 top-1/2 left-1/3 blur-2xl" />
          </div>
          <div className="relative z-10 flex flex-col justify-between p-12 xl:p-16 w-full">
            <div>
              <div className="flex items-center gap-3 mb-4">
                <div className="w-12 h-12 rounded-2xl bg-white/15 backdrop-blur-sm border border-white/20 flex items-center justify-center">
                  <Building2 className="w-7 h-7 text-white" />
                </div>
                <div>
                  <span className="text-2xl font-bold text-white">سوريا هومز</span>
                  <span className="block text-xs text-white/50 tracking-wider">SYRIA HOMES</span>
                </div>
              </div>
              <h2 className="text-3xl xl:text-4xl font-bold text-white leading-tight mt-8 max-w-md">
                {t('login.brandTitle')}
              </h2>
              <div className="mt-6 space-y-3">
                {brandFeatures.map((f, i) => (
                  <div key={i} className="flex items-center gap-3 text-white/80">
                    <CheckCircle className="w-5 h-5 text-gold shrink-0" />
                    <span className="text-sm">{f}</span>
                  </div>
                ))}
              </div>
            </div>
            <div>
              <div className="grid grid-cols-3 gap-6 p-6 rounded-2xl bg-white/8 backdrop-blur-sm border border-white/10">
                {trustStats.map((s, i) => {
                  const Icon = s.icon;
                  return (
                    <div key={i} className="text-center">
                      <Icon className="w-5 h-5 text-gold mx-auto mb-2" />
                      <div className="text-2xl font-bold text-white">{s.value}</div>
                      <div className="text-xs text-white/60 mt-0.5">{s.label}</div>
                    </div>
                  );
                })}
              </div>
            </div>
          </div>
        </div>

        {/* ═══════ Right — Form Side ═══════ */}
        <div className="w-full lg:w-1/2 flex items-center justify-center p-4 sm:p-8 bg-gradient-to-br from-warm to-cream">
          <div className="w-full max-w-md">
            <div className="lg:hidden text-center mb-6">
              <div className="w-14 h-14 rounded-2xl bg-primary flex items-center justify-center mx-auto mb-3 shadow-lg shadow-primary/20">
                <Building2 className="w-7 h-7 text-white" />
              </div>
              <h1 className="text-xl font-bold text-stone-900">سوريا هومز</h1>
              <p className="text-sm text-stone-500 mt-1">{t('auth.loginSubtitle')}</p>
            </div>

            <div className="bg-white rounded-3xl shadow-[0_4px_40px_rgba(0,0,0,0.06)] border border-beige-dark/40 p-6 sm:p-8">
              {/* Role tabs */}
              <div className="flex bg-beige/60 rounded-xl p-1 mb-6">
                <button type="button"
                  onClick={() => setTab('user')}
                  className={`flex-1 flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 ${
                    tab === 'user'
                      ? 'bg-white text-primary shadow-sm'
                      : 'text-stone-500 hover:text-stone-700'
                  }`}
                >
                  <Home className="w-4 h-4" />
                  {t('auth.userRole')}
                </button>
                <button type="button"
                  onClick={() => setTab('agency')}
                  className={`flex-1 flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 ${
                    tab === 'agency'
                      ? 'bg-white text-primary shadow-sm'
                      : 'text-stone-500 hover:text-stone-700'
                  }`}
                >
                  <Building2 className="w-4 h-4" />
                  {t('auth.agencyRole')}
                </button>
              </div>

              <div className="mb-6">
                <h2 className="text-xl font-bold text-stone-900">
                  {tab === 'user' ? t('auth.loginTitle') : t('auth.agencyLoginTitle')}
                </h2>
                <p className="text-sm text-stone-500 mt-1">
                  {tab === 'user' ? t('auth.loginSubtitle') : t('auth.agencyLoginSubtitle')}
                </p>
              </div>

              {/* Server error */}
              {serverError && (
                <div className="bg-red-50 border border-red-100 text-red-600 text-sm rounded-xl px-4 py-3 mb-5 flex items-center gap-2 animate-[fadeIn_0.3s_ease-out]">
                  <span className="w-1.5 h-1.5 rounded-full bg-red-500 shrink-0" />
                  {serverError}
                </div>
              )}

              <form onSubmit={handleSubmit} className="space-y-4" noValidate>
                {/* Email */}
                <div>
                  <label className="text-sm font-medium text-stone-700 mb-1.5 block">
                    {t('auth.email')}
                  </label>
                  <div className="relative">
                    <input
                      type="email" autoComplete="email"
                      value={form.email}
                      onChange={e => { setForm({...form, email: e.target.value}); if (errors.email) setErrors(p => ({...p, email: ''})); }}
                      placeholder={tab === 'user' ? t('auth.emailPlaceholder') : t('auth.agencyEmailPlaceholder')}
                      className={`input-field ${isAr ? 'pr-10' : 'pl-10'} transition-all duration-200 focus:shadow-[0_0_0_4px] focus:shadow-primary/6 ${
                        errors.email ? 'border-red-300 focus:border-red-400 focus:shadow-red-100' : ''
                      }`}
                      dir="ltr"
                    />
                    <Building2 className="absolute top-1/2 -translate-y-1/2 w-4 h-4 text-stone-400" style={{ [isAr ? 'right' : 'left']: '14px' }} />
                  </div>
                  {errors.email && (
                    <p className="text-red-500 text-xs mt-1.5 me-1">{t(errors.email)}</p>
                  )}
                </div>

                {/* Password */}
                <div>
                  <label className="text-sm font-medium text-stone-700 mb-1.5 block">
                    {t('auth.password')}
                  </label>
                  <div className="relative">
                    <input
                      type={showPw ? 'text' : 'password'} autoComplete="current-password"
                      value={form.password}
                      onChange={e => { setForm({...form, password: e.target.value}); if (errors.password) setErrors(p => ({...p, password: ''})); }}
                      placeholder={t('auth.passwordPlaceholder')}
                      className={`input-field pr-10 transition-all duration-200 focus:shadow-[0_0_0_4px] focus:shadow-primary/6 ${
                        errors.password ? 'border-red-300 focus:border-red-400 focus:shadow-red-100' : ''
                      }`}
                    />
                    <button type="button" onClick={() => setShowPw(!showPw)}
                      className="absolute inset-y-0 right-0 px-3 flex items-center text-stone-400 hover:text-stone-600 transition-colors">
                      {showPw ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                    </button>
                  </div>
                  {errors.password && (
                    <p className="text-red-500 text-xs mt-1.5 me-1">{t(errors.password)}</p>
                  )}
                </div>

                {/* Remember + Forgot */}
                <div className="flex items-center justify-between text-sm">
                  <label className="flex items-center gap-2 text-stone-500 cursor-pointer select-none">
                    <input type="checkbox" defaultChecked
                      className="w-4 h-4 rounded border-beige-dark text-primary focus:ring-primary/20 transition-all" />
                    {t('auth.rememberMe')}
                  </label>
                  <Link to="/forgot-password"
                    className="text-primary hover:text-primary-dark transition-colors font-medium text-sm">
                    {t('auth.forgotPassword')}
                  </Link>
                </div>

                {/* Submit */}
                <button type="submit" disabled={submitting}
                  className="btn-primary w-full flex items-center justify-center gap-2 !py-3.5 text-base mt-2">
                  {submitting ? (
                    <Loader2 className="w-5 h-5 animate-spin" />
                  ) : (
                    <>{t('auth.loginBtn')}</>
                  )}
                </button>
              </form>

              {/* Divider */}
              <div className="relative my-6">
                <div className="absolute inset-0 flex items-center">
                  <div className="w-full border-t border-beige-dark/50" />
                </div>
                <div className="relative flex justify-center">
                  <span className="bg-white px-4 text-xs text-stone-400">{t('auth.orContinue')}</span>
                </div>
              </div>

              {/* Register links */}
              <div className="text-center text-sm text-stone-500 space-y-2">
                <div className="flex items-center justify-center gap-1">
                  <span>{t('auth.noAccount')}</span>
                  <Link to="/register"
                    className="text-primary font-bold hover:text-primary-dark transition-colors">
                    {t('auth.registerBtn')}
                  </Link>
                </div>
                <div className="flex items-center justify-center gap-1 pt-1">
                  <Building2 className="w-3.5 h-3.5 text-stone-400" />
                  <span className="text-stone-400">{t('auth.agencyPrompt')}</span>
                  <Link to="/register/agency"
                    className="text-gold font-bold hover:text-gold-dark transition-colors">
                    {t('auth.agencyRegisterLink')}
                  </Link>
                </div>
              </div>
            </div>

            <div className="lg:hidden flex items-center justify-center gap-4 mt-6 text-stone-400">
              <ShieldCheck className="w-4 h-4" />
              <span className="text-xs">{t('auth.secureData')}</span>
              <ShieldCheck className="w-4 h-4" />
            </div>
          </div>
        </div>
      </div>
      <Footer />
    </div>
  );
}
