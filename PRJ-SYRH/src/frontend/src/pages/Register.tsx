import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Link, useNavigate } from 'react-router-dom';
import {
  UserPlus, Building2, Eye, EyeOff, Loader2, CheckCircle2, Home, Search,
  ArrowRight, ArrowLeft, Mail, Phone, User, ShieldCheck,
} from 'lucide-react';
import Navbar from '../components/Navbar';
import Footer from '../components/Footer';
import { useAuth } from '../auth/AuthContext';
import { validateRegister, extractServerError, type ValidationErrors } from '../auth/validation';

type Step = 'role' | 'info' | 'done';

export default function Register() {
  const { t, i18n } = useTranslation();
  const isAr = i18n.language === 'ar';
  const navigate = useNavigate();
  const { register: apiRegister } = useAuth();
  const [step, setStep] = useState<Step>('role');
  const [role, setRole] = useState<'user' | 'agency' | null>(null);
  const [form, setForm] = useState({
    name: '', email: '', phone: '',
    password: '', password_confirmation: '',
  });
  const [showPw, setShowPw] = useState(false);
  const [errors, setErrors] = useState<ValidationErrors>({});
  const [serverError, setServerError] = useState('');
  const [submitting, setSubmitting] = useState(false);

  const stepNames = [t('auth.stepType'), t('auth.stepInfo'), t('auth.stepDone')];

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (role === 'agency') {
      navigate('/register/agency');
      return;
    }
    setServerError('');
    setErrors({});

    // Client validation
    const fieldErrors = validateRegister(form);
    if (Object.keys(fieldErrors).length > 0) {
      setErrors(fieldErrors);
      return;
    }

    setSubmitting(true);
    try {
      await apiRegister({ ...form, locale: i18n.language });
      setStep('done');
    } catch (err: any) {
      setServerError(t(extractServerError(err)));
    } finally {
      setSubmitting(false);
    }
  };

  const handleChange = (f: string, v: string) => {
    setForm(p => ({ ...p, [f]: v }));
    if (errors[f]) setErrors(p => ({...p, [f]: ''}));
  };

  const steps: Step[] = ['role', 'info', 'done'];
  const currentIdx = steps.indexOf(step);

  return (
    <div dir={isAr ? 'rtl' : 'ltr'} className="min-h-screen flex flex-col">
      <Navbar />
      <div className="flex-1 pt-20 flex items-center justify-center relative overflow-hidden">
        <div className="absolute inset-0 bg-gradient-to-br from-primary/5 via-transparent to-gold/5" />
        <div className="absolute inset-0 hero-mesh" />

        <div className="relative z-10 w-full max-w-lg mx-auto px-4 py-8">
          {/* ─── Header ─── */}
          <div className="text-center mb-6">
            <div className="w-14 h-14 rounded-2xl bg-gradient-to-br from-primary to-primary-dark flex items-center justify-center mx-auto mb-3 shadow-lg shadow-primary/20">
              <UserPlus className="w-7 h-7 text-white" />
            </div>
            <h1 className="text-2xl md:text-3xl font-bold text-stone-900">{t('auth.registerTitle')}</h1>
            <p className="text-stone-500 mt-1 text-sm">{t('auth.registerSubtitle')}</p>
          </div>

          {/* ─── Progress steps ─── */}
          <div className="flex items-center justify-center gap-2 mb-8">
            {stepNames.map((name, i) => (
              <div key={i} className="flex items-center gap-2">
                <div className={`flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-medium transition-all duration-300 ${
                  i <= currentIdx
                    ? 'bg-primary/10 text-primary'
                    : 'bg-stone-100 text-stone-400'
                }`}>
                  <span className={`w-5 h-5 rounded-full flex items-center justify-center text-2xs font-bold ${
                    i < currentIdx
                      ? 'bg-primary text-white'
                      : i === currentIdx
                        ? 'bg-primary text-white'
                        : 'bg-stone-200 text-stone-500'
                  }`}>
                    {i < currentIdx ? <CheckCircle2 className="w-3 h-3" /> : i + 1}
                  </span>
                  <span className="hidden sm:inline">{name}</span>
                </div>
                {i < stepNames.length - 1 && (
                  <div className={`w-6 h-px transition-colors duration-300 ${
                    i < currentIdx ? 'bg-primary/40' : 'bg-stone-200'
                  }`} />
                )}
              </div>
            ))}
          </div>

          {/* ─── Step: Role Selection ─── */}
          {step === 'role' && (
            <div className="bg-white rounded-3xl shadow-[0_4px_40px_rgba(0,0,0,0.06)] border border-beige-dark/40 p-6 sm:p-8">
              <h2 className="text-lg font-bold text-stone-900 mb-2">{t('auth.chooseRole')}</h2>
              <p className="text-sm text-stone-500 mb-6">{t('auth.chooseRoleDesc')}</p>

              <div className="grid gap-4">
                {/* User card */}
                <button type="button" onClick={() => { setRole('user'); setStep('info'); }}
                  className={`group text-right p-5 rounded-2xl border-2 transition-all duration-200 hover:shadow-md ${
                    role === 'user'
                      ? 'border-primary bg-primary/5 shadow-sm'
                      : 'border-beige-dark/50 hover:border-stone-300'
                  }`}>
                  <div className="flex items-start gap-4">
                    <div className={`w-14 h-14 rounded-2xl flex items-center justify-center shrink-0 transition-all duration-200 ${
                      role === 'user' ? 'bg-primary text-white' : 'bg-stone-100 text-stone-400 group-hover:bg-primary/10 group-hover:text-primary'
                    }`}>
                      <Home className="w-7 h-7" />
                    </div>
                    <div className="flex-1 min-w-0">
                      <div className={`text-base font-bold ${role === 'user' ? 'text-primary' : 'text-stone-800'}`}>
                        {t('auth.userRole')}
                      </div>
                      <div className="text-sm text-stone-500 mt-1 leading-relaxed">
                        {t('auth.userDesc')}
                      </div>
                      <div className="flex flex-wrap gap-2 mt-3">
                        <span className="inline-flex items-center gap-1 text-xs text-primary bg-primary/5 px-2 py-0.5 rounded-full">
                          <Search className="w-3 h-3" /> {t('auth.userFeatureSearch')}
                        </span>
                        <span className="inline-flex items-center gap-1 text-xs text-primary bg-primary/5 px-2 py-0.5 rounded-full">
                          <Building2 className="w-3 h-3" /> {t('auth.userFeatureContact')}
                        </span>
                      </div>
                    </div>
                    {role === 'user' && (
                      <CheckCircle2 className="w-6 h-6 text-primary shrink-0" />
                    )}
                  </div>
                </button>

                {/* Agency card */}
                <button type="button" onClick={() => navigate('/register/agency')}
                  className={`group text-right p-5 rounded-2xl border-2 transition-all duration-200 hover:shadow-md ${
                    role === 'agency'
                      ? 'border-gold bg-gold/5 shadow-sm'
                      : 'border-beige-dark/50 hover:border-stone-300'
                  }`}>
                  <div className="flex items-start gap-4">
                    <div className={`w-14 h-14 rounded-2xl flex items-center justify-center shrink-0 transition-all duration-200 ${
                      role === 'agency' ? 'bg-gold text-white' : 'bg-stone-100 text-stone-400 group-hover:bg-gold/10 group-hover:text-gold'
                    }`}>
                      <Building2 className="w-7 h-7" />
                    </div>
                    <div className="flex-1 min-w-0">
                      <div className={`text-base font-bold ${role === 'agency' ? 'text-gold' : 'text-stone-800'}`}>
                        {t('auth.agencyRole')}
                      </div>
                      <div className="text-sm text-stone-500 mt-1 leading-relaxed">
                        {t('auth.agencyDesc')}
                      </div>
                      <div className="flex flex-wrap gap-2 mt-3">
                        <span className="inline-flex items-center gap-1 text-xs text-gold bg-gold/5 px-2 py-0.5 rounded-full">
                          <Building2 className="w-3 h-3" /> {t('auth.agencyFeatureMgmt')}
                        </span>
                        <span className="inline-flex items-center gap-1 text-xs text-gold bg-gold/5 px-2 py-0.5 rounded-full">
                          <User className="w-3 h-3" /> {t('auth.agencyFeatureClient')}
                        </span>
                      </div>
                    </div>
                  </div>
                </button>
              </div>

              <div className="mt-6 text-center text-sm text-stone-500">
                {t('auth.haveAccount')}{' '}
                <Link to="/login" className="text-primary font-bold hover:text-primary-dark transition-colors">
                  {t('auth.loginBtn')}
                </Link>
              </div>
            </div>
          )}

          {/* ─── Step: Info ─── */}
          {step === 'info' && role === 'user' && (
            <div className="bg-white rounded-3xl shadow-[0_4px_40px_rgba(0,0,0,0.06)] border border-beige-dark/40 p-6 sm:p-8 animate-[fadeIn_0.3s_ease-out]">
              <button type="button" onClick={() => setStep('role')}
                className="flex items-center gap-1.5 text-sm text-stone-400 hover:text-stone-600 transition-colors mb-5">
                {isAr ? <ArrowRight className="w-4 h-4" /> : <ArrowLeft className="w-4 h-4" />}
                {t('auth.back')}
              </button>

              <h2 className="text-lg font-bold text-stone-900 mb-1">{t('auth.personalInfo')}</h2>
              <p className="text-sm text-stone-500 mb-5">{t('auth.stepPersonalInfoDesc')}</p>

              {serverError && (
                <div className="bg-red-50 border border-red-100 text-red-600 text-sm rounded-xl px-4 py-3 mb-5 flex items-center gap-2">
                  <span className="w-1.5 h-1.5 rounded-full bg-red-500 shrink-0" />
                  {serverError}
                </div>
              )}

              <form onSubmit={handleSubmit} className="space-y-4" noValidate>
                {/* Name */}
                <div>
                  <label className="text-sm font-medium text-stone-700 mb-1.5 block">{t('auth.name')}</label>
                  <div className="relative">
                    <input type="text" autoComplete="name"
                      value={form.name}
                      onChange={e => handleChange('name', e.target.value)}
                      placeholder={t('auth.namePlaceholder')}
                      className={`input-field ${isAr ? 'pr-10' : 'pl-10'} ${errors.name ? 'border-red-300 focus:border-red-400 focus:shadow-red-100' : ''}`} />
                    <User className="absolute top-1/2 -translate-y-1/2 w-4 h-4 text-stone-400"
                      style={{ [isAr ? 'right' : 'left']: '14px' }} />
                  </div>
                  {errors.name && <p className="text-red-500 text-xs mt-1.5 me-1">{t(errors.name)}</p>}
                </div>

                {/* Email */}
                <div>
                  <label className="text-sm font-medium text-stone-700 mb-1.5 block">{t('auth.email')}</label>
                  <div className="relative">
                    <input type="email" autoComplete="email"
                      value={form.email}
                      onChange={e => handleChange('email', e.target.value)}
                      placeholder={t('auth.emailPlaceholder')}
                      className={`input-field ${isAr ? 'pr-10' : 'pl-10'} ${errors.email ? 'border-red-300 focus:border-red-400 focus:shadow-red-100' : ''}`}
                      dir="ltr" />
                    <Mail className="absolute top-1/2 -translate-y-1/2 w-4 h-4 text-stone-400"
                      style={{ [isAr ? 'right' : 'left']: '14px' }} />
                  </div>
                  {errors.email && <p className="text-red-500 text-xs mt-1.5 me-1">{t(errors.email)}</p>}
                </div>

                {/* Phone */}
                <div>
                  <label className="text-sm font-medium text-stone-700 mb-1.5 block">{t('auth.phone')}</label>
                  <div className="relative">
                    <input type="tel"
                      value={form.phone}
                      onChange={e => handleChange('phone', e.target.value)}
                      placeholder={t('auth.phonePlaceholder')}
                      className={`input-field ${isAr ? 'pr-10' : 'pl-10'} ${errors.phone ? 'border-red-300 focus:border-red-400 focus:shadow-red-100' : ''}`} />
                    <Phone className="absolute top-1/2 -translate-y-1/2 w-4 h-4 text-stone-400"
                      style={{ [isAr ? 'right' : 'left']: '14px' }} />
                  </div>
                  {errors.phone && <p className="text-red-500 text-xs mt-1.5 me-1">{t(errors.phone)}</p>}
                </div>

                {/* Password */}
                <div>
                  <label className="text-sm font-medium text-stone-700 mb-1.5 block">{t('auth.password')}</label>
                  <div className="relative">
                    <input type={showPw ? 'text' : 'password'} autoComplete="new-password"
                      value={form.password}
                      onChange={e => handleChange('password', e.target.value)}
                      placeholder={t('auth.passwordPlaceholder')}
                      className={`input-field pr-10 ${errors.password ? 'border-red-300 focus:border-red-400 focus:shadow-red-100' : ''}`} />
                    <button type="button" onClick={() => setShowPw(!showPw)}
                      className="absolute inset-y-0 right-0 px-3 flex items-center text-stone-400 hover:text-stone-600 transition-colors">
                      {showPw ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                    </button>
                  </div>
                  {errors.password && <p className="text-red-500 text-xs mt-1.5 me-1">{t(errors.password)}</p>}
                </div>

                {/* Password confirm */}
                <div>
                  <label className="text-sm font-medium text-stone-700 mb-1.5 block">{t('auth.passwordConfirm')}</label>
                  <input type={showPw ? 'text' : 'password'} autoComplete="new-password"
                    value={form.password_confirmation}
                    onChange={e => handleChange('password_confirmation', e.target.value)}
                    placeholder={t('auth.passwordConfirmPlaceholder')}
                    className={`input-field ${errors.password_confirmation ? 'border-red-300 focus:border-red-400 focus:shadow-red-100' : ''}`} />
                  {errors.password_confirmation && <p className="text-red-500 text-xs mt-1.5 me-1">{t(errors.password_confirmation)}</p>}
                </div>

                {/* Password hint */}
                <div className="flex items-start gap-2 text-xs text-stone-400 bg-stone-50 rounded-xl px-4 py-3">
                  <ShieldCheck className="w-4 h-4 text-primary shrink-0 mt-0.5" />
                  <span>{t('auth.passwordHint')}</span>
                </div>

                {/* Submit */}
                <button type="submit" disabled={submitting}
                  className="btn-primary w-full flex items-center justify-center gap-2 !py-3.5 text-base mt-2">
                  {submitting ? (
                    <Loader2 className="w-5 h-5 animate-spin" />
                  ) : (
                    <><UserPlus className="w-4 h-4" /> {t('auth.registerBtn')}</>
                  )}
                </button>
              </form>
            </div>
          )}

          {/* ─── Step: Done ─── */}
          {step === 'done' && (
            <div className="bg-white rounded-3xl shadow-[0_4px_40px_rgba(0,0,0,0.06)] border border-beige-dark/40 p-8 sm:p-10 text-center animate-[fadeIn_0.5s_ease-out]">
              <div className="w-20 h-20 rounded-full bg-gradient-to-br from-primary to-primary-dark flex items-center justify-center mx-auto mb-5 shadow-lg shadow-primary/20 animate-[scaleUp_0.5s_ease-out]">
                <CheckCircle2 className="w-10 h-10 text-white" />
              </div>
              <h2 className="text-2xl font-bold text-stone-900 mb-2">{t('auth.registerDoneTitle')}</h2>
              <p className="text-stone-500 mb-2">{t('auth.registerDoneWelcome')}</p>
              <p className="text-sm text-stone-400 mb-8 max-w-sm mx-auto">{t('auth.registerDoneDesc')}</p>
              <div className="flex flex-col sm:flex-row gap-3 justify-center">
                <button onClick={() => navigate('/user/dashboard')}
                  className="btn-primary flex items-center justify-center gap-2 !py-3 !px-6">
                  <Home className="w-4 h-4" />
                  {t('auth.goToDashboard')}
                </button>
                <button onClick={() => navigate('/properties')}
                  className="btn-outline flex items-center justify-center gap-2 !py-3 !px-6">
                  <Search className="w-4 h-4" />
                  {t('auth.browseProperties')}
                </button>
              </div>
              <div className="mt-6 pt-6 border-t border-beige-dark/30">
                <Link to="/login"
                  className="text-sm text-stone-400 hover:text-primary transition-colors">
                  {t('auth.signInLink')}
                </Link>
              </div>
            </div>
          )}
        </div>
      </div>
      <Footer />
    </div>
  );
}
