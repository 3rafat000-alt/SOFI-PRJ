import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Link, useNavigate } from 'react-router-dom';
import {
  Building2, Eye, EyeOff, Loader2, CheckCircle2, User, Mail, Phone,
  ArrowRight, ArrowLeft, Hash, MapPin, ShieldCheck, FileText,
  Landmark, Award, Clock,
} from 'lucide-react';
import Navbar from '../components/Navbar';
import Footer from '../components/Footer';
import { useAuth } from '../auth/AuthContext';
import { validateAgencyRegister } from '../auth/validation';
import axios from 'axios';

type Step = 1 | 2 | 3 | 4;

export default function AgencyRegister() {
  const { t, i18n } = useTranslation();
  const isAr = i18n.language === 'ar';
  const navigate = useNavigate();
  const { agencyRegister } = useAuth();
  const [step, setStep] = useState<Step>(1);
  const [showPw, setShowPw] = useState(false);
  const [error, setError] = useState('');
  const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});
  const [submitting, setSubmitting] = useState(false);

  const [form, setForm] = useState({
    owner_name: '',
    owner_email: '',
    owner_phone: '',
    password: '',
    password_confirmation: '',
    agency_name: '',
    license_no: '',
    agency_email: '',
    agency_phone: '',
    whatsapp: '',
    address: '',
    city: '',
    description: '',
    specializations: [] as string[],
    team_size: '',
  });

  const [errors, setErrors] = useState<Record<string, string>>({});

  const handleChange = (field: string, value: string) => {
    setForm(prev => ({ ...prev, [field]: value }));
    if (errors[field]) setErrors(prev => ({ ...prev, [field]: '' }));
  };

  const toggleSpecialization = (spec: string) => {
    setForm(prev => ({
      ...prev,
      specializations: prev.specializations.includes(spec)
        ? prev.specializations.filter(s => s !== spec)
        : [...prev.specializations, spec],
    }));
  };

  const specKeys = [
    'auth.agencyRegister.specResidential',
    'auth.agencyRegister.specCommercial',
    'auth.agencyRegister.specLand',
    'auth.agencyRegister.specAgricultural',
    'auth.agencyRegister.specIndustrial',
    'auth.agencyRegister.specHotels',
    'auth.agencyRegister.specOffices',
    'auth.agencyRegister.specShops',
  ];

  const stepNames = [
    t('auth.agencyRegister.stepOwner'),
    t('auth.agencyRegister.stepAgency'),
    t('auth.agencyRegister.stepSpecialization'),
    t('auth.agencyRegister.stepReview'),
  ];

  const stepIcons = [User, Building2, Award, ShieldCheck];

  // Validation per step
  const validateStep = (s: Step): boolean => {
    const errs: Record<string, string> = {};
    if (s === 1) {
      const all = validateAgencyRegister({
        owner_name: form.owner_name,
        owner_email: form.owner_email,
        owner_phone: form.owner_phone,
        password: form.password,
        password_confirmation: form.password_confirmation,
        agency_name: 'x', // dummy — not checked in step 1
      });
      if (all.owner_name) errs.owner_name = all.owner_name;
      if (all.owner_email) errs.owner_email = all.owner_email;
      if (all.owner_phone) errs.owner_phone = all.owner_phone;
      if (all.password) errs.password = all.password;
      if (all.password_confirmation) errs.password_confirmation = all.password_confirmation;
    } else if (s === 2) {
      if (!form.agency_name.trim()) errs.agency_name = 'auth.nameRequired';
      if (!form.city.trim()) errs.city = 'auth.nameRequired';
    }
    setErrors(errs);
    return Object.keys(errs).length === 0;
  };

  const nextStep = () => {
    if (validateStep(step)) setStep(p => (Math.min(4, p + 1) as Step));
  };

  const prevStep = () => setStep(p => (Math.max(1, p - 1) as Step));

  const handleSubmit = async () => {
    setError('');
    setFieldErrors({});

    // Full validation before submission
    const full = validateAgencyRegister({
      owner_name: form.owner_name,
      owner_email: form.owner_email,
      owner_phone: form.owner_phone,
      password: form.password,
      password_confirmation: form.password_confirmation,
      agency_name: form.agency_name,
      agency_email: form.agency_email,
    });
    if (Object.keys(full).length > 0) {
      setErrors(full);
      return;
    }

    setSubmitting(true);
    try {
      await agencyRegister({
        owner_name: form.owner_name,
        owner_email: form.owner_email,
        owner_phone: form.owner_phone,
        password: form.password,
        password_confirmation: form.password_confirmation,
        agency_name: form.agency_name,
        license_no: form.license_no,
        agency_email: form.agency_email,
        agency_phone: form.agency_phone,
        whatsapp: form.whatsapp,
        address: form.address ? `${form.city} - ${form.address}` : form.city,
        locale: i18n.language,
      });
      setStep(4);
    } catch (err: any) {
      if (axios.isAxiosError(err) && err.response?.data?.errors) {
        const errs = err.response.data.errors;
        const firstKey = Object.keys(errs)[0];
        if (firstKey) setError(errs[firstKey][0]);
        setFieldErrors(Object.fromEntries(
          Object.entries(errs).map(([k, v]) => [k, (v as string[])[0]])
        ));
      } else {
        setError(err.message || t('auth.connectionError'));
      }
    } finally {
      setSubmitting(false);
    }
  };

  const inputClass = (field: string) =>
    `input-field pl-10 transition-all duration-200 focus:shadow-[0_0_0_4px] focus:shadow-primary/6 ${
      errors[field] ? 'border-red-300 focus:ring-red-200' : ''
    } ${fieldErrors[field] ? 'border-red-300' : ''}`;

  const renderFieldError = (field: string) => {
    const key = errors[field] || fieldErrors[field];
    return key ? <p className="text-red-500 text-xs mt-1">{t(key)}</p> : null;
  };

  const isComplete = step === 4;

  return (
    <div dir={isAr ? 'rtl' : 'ltr'} className="min-h-screen flex flex-col">
      <Navbar />
      <div className="flex-1 pt-20 flex items-start justify-center relative overflow-hidden">
        <div className="absolute inset-0 bg-gradient-to-br from-primary/5 via-transparent to-gold/5" />
        <div className="absolute inset-0 hero-mesh" />

        <div className="relative z-10 w-full max-w-2xl mx-auto px-4 py-8">
          {/* ─── Header ─── */}
          <div className="text-center mb-6">
            <div className="w-14 h-14 rounded-2xl bg-gradient-to-br from-primary to-primary-dark flex items-center justify-center mx-auto mb-3 shadow-lg shadow-primary/20">
              <Landmark className="w-7 h-7 text-white" />
            </div>
            <h1 className="text-2xl md:text-3xl font-bold text-stone-900">
              {t('auth.agencyRegister.title')}
            </h1>
            <p className="text-stone-500 mt-1 text-sm">
              {t('auth.agencyRegister.subtitle')}
            </p>
          </div>

          {/* ─── Progress Stepper ─── */}
          {!isComplete && (
            <div className="flex items-center justify-center gap-0 mb-8">
              {stepNames.map((name, i) => {
                const idx = (i + 1) as Step;
                const StepIcon = stepIcons[i];
                const done = idx < step;
                const active = idx === step;
                return (
                  <div key={i} className="flex items-center gap-0">
                    <div className={`flex flex-col items-center gap-1.5 px-4 py-2 transition-all duration-300 ${
                      active ? 'scale-105' : ''
                    }`}>
                      <div className={`w-10 h-10 rounded-xl flex items-center justify-center transition-all duration-300 ${
                        done
                          ? 'bg-primary text-white shadow-sm shadow-primary/20'
                          : active
                            ? 'bg-primary text-white shadow-md shadow-primary/25 ring-4 ring-primary/10'
                            : 'bg-stone-100 text-stone-400'
                      }`}>
                        {done ? <CheckCircle2 className="w-5 h-5" /> : <StepIcon className="w-5 h-5" />}
                      </div>
                      <span className={`text-xs font-medium whitespace-nowrap ${
                        active ? 'text-primary' : done ? 'text-primary/60' : 'text-stone-400'
                      }`}>
                        {name}
                      </span>
                    </div>
                    {i < stepNames.length - 1 && (
                      <div className={`w-12 h-px transition-colors duration-300 ${
                        idx < step ? 'bg-primary/40' : 'bg-stone-200'
                      }`} />
                    )}
                  </div>
                );
              })}
            </div>
          )}

          {/* ─── Error ─── */}
          {error && (
            <div className="bg-red-50 border border-red-100 text-red-600 text-sm rounded-xl px-4 py-3 mb-5 flex items-center gap-2">
              <span className="w-1.5 h-1.5 rounded-full bg-red-500 shrink-0" />
              {error}
            </div>
          )}

          {/* ═══════════════ STEP 1: Owner Info ═══════════════ */}
          {step === 1 && !isComplete && (
            <div className="bg-white rounded-3xl shadow-[0_4px_40px_rgba(0,0,0,0.06)] border border-beige-dark/40 p-6 sm:p-8 animate-[fadeIn_0.3s_ease-out]">
              <div className="flex items-center gap-3 mb-5">
                <div className="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center">
                  <User className="w-5 h-5 text-primary" />
                </div>
                <div>
                  <h2 className="text-lg font-bold text-stone-900">{t('auth.agencyRegister.ownerInfoTitle')}</h2>
                  <p className="text-sm text-stone-500">{t('auth.agencyRegister.ownerInfoDesc')}</p>
                </div>
              </div>

              <div className="space-y-4">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label className="text-sm font-medium text-stone-700 mb-1.5 block">{t('auth.agencyRegister.fullName')}</label>
                    <div className="relative">
                      <input type="text" required value={form.owner_name}
                        onChange={e => handleChange('owner_name', e.target.value)}
                        placeholder={t('auth.agencyRegister.fullNamePlaceholder')}
                        className={inputClass('owner_name')} />
                      <User className="absolute top-1/2 -translate-y-1/2 w-4 h-4 text-stone-400"
                        style={{ [isAr ? 'right' : 'left']: '14px' }} />
                    </div>
                    {renderFieldError('owner_name')}
                  </div>
                  <div>
                    <label className="text-sm font-medium text-stone-700 mb-1.5 block">{t('auth.email')}</label>
                    <div className="relative">
                      <input type="email" required value={form.owner_email}
                        onChange={e => handleChange('owner_email', e.target.value)}
                        placeholder={t('auth.emailPlaceholder')}
                        className={inputClass('owner_email')}
                        dir="ltr" />
                      <Mail className="absolute top-1/2 -translate-y-1/2 w-4 h-4 text-stone-400"
                        style={{ [isAr ? 'right' : 'left']: '14px' }} />
                    </div>
                    {renderFieldError('owner_email')}
                  </div>
                  <div>
                    <label className="text-sm font-medium text-stone-700 mb-1.5 block">{t('auth.agencyRegister.phoneNumber')}</label>
                    <div className="relative">
                      <input type="tel" required value={form.owner_phone}
                        onChange={e => handleChange('owner_phone', e.target.value)}
                        placeholder={t('auth.agencyRegister.phonePlaceholder')}
                        className={inputClass('owner_phone')} />
                      <Phone className="absolute top-1/2 -translate-y-1/2 w-4 h-4 text-stone-400"
                        style={{ [isAr ? 'right' : 'left']: '14px' }} />
                    </div>
                    {renderFieldError('owner_phone')}
                  </div>
                  <div>
                    <label className="text-sm font-medium text-stone-700 mb-1.5 block">{t('auth.password')}</label>
                    <div className="relative">
                      <input type={showPw ? 'text' : 'password'} required value={form.password}
                        onChange={e => handleChange('password', e.target.value)}
                        placeholder={t('auth.agencyRegister.passwordPlaceholder')}
                        className={`${inputClass('password')} pr-10`} />
                      <button type="button" onClick={() => setShowPw(!showPw)}
                        className="absolute inset-y-0 right-0 px-3 flex items-center text-stone-400 hover:text-stone-600 transition-colors">
                        {showPw ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                      </button>
                    </div>
                    {renderFieldError('password')}
                  </div>
                  <div>
                    <label className="text-sm font-medium text-stone-700 mb-1.5 block">{t('auth.passwordConfirm')}</label>
                    <input type={showPw ? 'text' : 'password'} required value={form.password_confirmation}
                      onChange={e => handleChange('password_confirmation', e.target.value)}
                      placeholder={t('auth.agencyRegister.confirmPasswordPlaceholder')}
                      className={inputClass('password_confirmation')} />
                    {renderFieldError('password_confirmation')}
                  </div>
                </div>
              </div>

              <div className="flex justify-between items-center mt-8">
                <Link to="/register" className="text-sm text-stone-400 hover:text-primary transition-colors flex items-center gap-1">
                  {isAr ? <ArrowRight className="w-4 h-4" /> : <ArrowLeft className="w-4 h-4" />}
                  {t('auth.agencyRegister.userAccountLink')}
                </Link>
                <button onClick={nextStep} className="btn-primary flex items-center gap-2 !py-2.5 !px-6">
                  {t('auth.agencyRegister.next')}
                  {isAr ? <ArrowLeft className="w-4 h-4" /> : <ArrowRight className="w-4 h-4" />}
                </button>
              </div>
            </div>
          )}

          {/* ═══════════════ STEP 2: Agency Info ═══════════════ */}
          {step === 2 && !isComplete && (
            <div className="bg-white rounded-3xl shadow-[0_4px_40px_rgba(0,0,0,0.06)] border border-beige-dark/40 p-6 sm:p-8 animate-[fadeIn_0.3s_ease-out]">
              <div className="flex items-center gap-3 mb-5">
                <div className="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center">
                  <Building2 className="w-5 h-5 text-primary" />
                </div>
                <div>
                  <h2 className="text-lg font-bold text-stone-900">{t('auth.agencyRegister.agencyInfoTitle')}</h2>
                  <p className="text-sm text-stone-500">{t('auth.agencyRegister.agencyInfoDesc')}</p>
                </div>
              </div>

              <div className="space-y-4">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div className="md:col-span-2">
                    <label className="text-sm font-medium text-stone-700 mb-1.5 block">{t('auth.agencyRegister.agencyName')}</label>
                    <div className="relative">
                      <input type="text" required value={form.agency_name}
                        onChange={e => handleChange('agency_name', e.target.value)}
                        placeholder={t('auth.agencyRegister.agencyNamePlaceholder')}
                        className={inputClass('agency_name')} />
                      <Building2 className="absolute top-1/2 -translate-y-1/2 w-4 h-4 text-stone-400"
                        style={{ [isAr ? 'right' : 'left']: '14px' }} />
                    </div>
                    {renderFieldError('agency_name')}
                  </div>
                  <div>
                    <label className="text-sm font-medium text-stone-700 mb-1.5 block">
                      <Hash className="w-3.5 h-3.5 inline ml-1" />
                      {t('auth.agencyRegister.licenseNo')}
                    </label>
                    <input type="text" value={form.license_no}
                      onChange={e => handleChange('license_no', e.target.value)}
                      placeholder={t('auth.agencyRegister.licenseNoPlaceholder')}
                      className="input-field" />
                  </div>
                  <div>
                    <label className="text-sm font-medium text-stone-700 mb-1.5 block">{t('auth.agencyRegister.teamSize')}</label>
                    <input type="text" value={form.team_size}
                      onChange={e => handleChange('team_size', e.target.value)}
                      placeholder={t('auth.agencyRegister.teamSizePlaceholder')}
                      className="input-field" />
                  </div>
                  <div>
                    <label className="text-sm font-medium text-stone-700 mb-1.5 block">{t('auth.agencyRegister.city')}</label>
                    <div className="relative">
                      <input type="text" required value={form.city}
                        onChange={e => handleChange('city', e.target.value)}
                        placeholder={t('auth.agencyRegister.cityPlaceholder')}
                        className={inputClass('city')} />
                      <MapPin className="absolute top-1/2 -translate-y-1/2 w-4 h-4 text-stone-400"
                        style={{ [isAr ? 'right' : 'left']: '14px' }} />
                    </div>
                    {renderFieldError('city')}
                  </div>
                  <div>
                    <label className="text-sm font-medium text-stone-700 mb-1.5 block">{t('auth.agencyRegister.agencyEmail')}</label>
                    <div className="relative">
                      <input type="email" value={form.agency_email}
                        onChange={e => handleChange('agency_email', e.target.value)}
                        placeholder={t('auth.agencyRegister.agencyEmailPlaceholder')}
                        className="input-field pl-10" dir="ltr" />
                      <Mail className="absolute top-1/2 -translate-y-1/2 w-4 h-4 text-stone-400"
                        style={{ [isAr ? 'right' : 'left']: '14px' }} />
                    </div>
                  </div>
                  <div>
                    <label className="text-sm font-medium text-stone-700 mb-1.5 block">{t('auth.agencyRegister.agencyPhone')}</label>
                    <div className="relative">
                      <input type="tel" value={form.agency_phone}
                        onChange={e => handleChange('agency_phone', e.target.value)}
                        placeholder={t('auth.agencyRegister.agencyPhonePlaceholder')}
                        className="input-field pl-10" />
                      <Phone className="absolute top-1/2 -translate-y-1/2 w-4 h-4 text-stone-400"
                        style={{ [isAr ? 'right' : 'left']: '14px' }} />
                    </div>
                  </div>
                  <div>
                    <label className="text-sm font-medium text-stone-700 mb-1.5 block">{t('auth.agencyRegister.whatsapp')}</label>
                    <input type="tel" value={form.whatsapp}
                      onChange={e => handleChange('whatsapp', e.target.value)}
                      placeholder={t('auth.agencyRegister.whatsappPlaceholder')}
                      className="input-field" />
                  </div>
                  <div className="md:col-span-2">
                    <label className="text-sm font-medium text-stone-700 mb-1.5 block">{t('auth.agencyRegister.address')}</label>
                    <div className="relative">
                      <input type="text" value={form.address}
                        onChange={e => handleChange('address', e.target.value)}
                        placeholder={t('auth.agencyRegister.addressPlaceholder')}
                        className="input-field pl-10" />
                      <MapPin className="absolute top-1/2 -translate-y-1/2 w-4 h-4 text-stone-400"
                        style={{ [isAr ? 'right' : 'left']: '14px' }} />
                    </div>
                  </div>
                </div>
              </div>

              <div className="flex justify-between items-center mt-8">
                <button onClick={prevStep} className="flex items-center gap-2 text-sm text-stone-500 hover:text-stone-700 transition-colors !py-2.5 !px-5">
                  {isAr ? <ArrowRight className="w-4 h-4" /> : <ArrowLeft className="w-4 h-4" />}
                  {t('auth.agencyRegister.back')}
                </button>
                <button onClick={nextStep} className="btn-primary flex items-center gap-2 !py-2.5 !px-6">
                  {t('auth.agencyRegister.next')}
                  {isAr ? <ArrowLeft className="w-4 h-4" /> : <ArrowRight className="w-4 h-4" />}
                </button>
              </div>
            </div>
          )}

          {/* ═══════════════ STEP 3: Specialization ═══════════════ */}
          {step === 3 && !isComplete && (
            <div className="bg-white rounded-3xl shadow-[0_4px_40px_rgba(0,0,0,0.06)] border border-beige-dark/40 p-6 sm:p-8 animate-[fadeIn_0.3s_ease-out]">
              <div className="flex items-center gap-3 mb-5">
                <div className="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center">
                  <Award className="w-5 h-5 text-primary" />
                </div>
                <div>
                  <h2 className="text-lg font-bold text-stone-900">{t('auth.agencyRegister.specializationTitle')}</h2>
                  <p className="text-sm text-stone-500">{t('auth.agencyRegister.specializationDesc')}</p>
                </div>
              </div>

              <div className="space-y-5">
                {/* Specializations */}
                <div>
                  <label className="text-sm font-medium text-stone-700 mb-3 block">
                    {t('auth.agencyRegister.specializations')} <span className="text-stone-400">({t('auth.agencyRegister.specializationsHint')})</span>
                  </label>
                  <div className="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                    {specKeys.map((key, i) => {
                      const spec = t(key);
                      const selected = form.specializations.includes(spec);
                      return (
                        <button key={i} type="button" onClick={() => toggleSpecialization(spec)}
                          className={`p-3 rounded-xl text-sm font-medium border-2 transition-all duration-200 ${
                            selected
                              ? 'border-primary bg-primary/5 text-primary'
                              : 'border-beige-dark/50 text-stone-500 hover:border-stone-300 hover:bg-stone-50'
                          }`}>
                          {selected && <CheckCircle2 className="w-3.5 h-3.5 inline ml-1" />}
                          {spec}
                        </button>
                      );
                    })}
                  </div>
                </div>

                {/* Description */}
                <div>
                  <label className="text-sm font-medium text-stone-700 mb-1.5 block">
                    <FileText className="w-3.5 h-3.5 inline ml-1" />
                    {t('auth.agencyRegister.description')}
                  </label>
                  <textarea value={form.description}
                    onChange={e => handleChange('description', e.target.value)}
                    placeholder={t('auth.agencyRegister.descriptionPlaceholder')}
                    rows={4}
                    className="input-field resize-none" />
                </div>

                {/* Verification note */}
                <div className="flex items-start gap-3 bg-amber-50 border border-amber-100 rounded-xl px-5 py-4">
                  <ShieldCheck className="w-5 h-5 text-amber-600 shrink-0 mt-0.5" />
                  <div className="text-sm text-amber-700">
                    <p className="font-medium mb-1">{t('auth.agencyRegister.verificationTitle')}</p>
                    <p className="text-amber-600/80">
                      {t('auth.agencyRegister.verificationDesc')}
                    </p>
                  </div>
                </div>
              </div>

              <div className="flex justify-between items-center mt-8">
                <button onClick={prevStep} className="flex items-center gap-2 text-sm text-stone-500 hover:text-stone-700 transition-colors !py-2.5 !px-5">
                  {isAr ? <ArrowRight className="w-4 h-4" /> : <ArrowLeft className="w-4 h-4" />}
                  {t('auth.agencyRegister.back')}
                </button>
                <button onClick={handleSubmit} disabled={submitting}
                  className="btn-primary flex items-center gap-2 !py-2.5 !px-8">
                  {submitting ? (
                    <Loader2 className="w-4 h-4 animate-spin" />
                  ) : (
                    <><ShieldCheck className="w-4 h-4" /> {t('auth.agencyRegister.submitBtn')}</>
                  )}
                </button>
              </div>
            </div>
          )}

          {/* ═══════════════ STEP 4: Done ═══════════════ */}
          {isComplete && (
            <div className="bg-white rounded-3xl shadow-[0_4px_40px_rgba(0,0,0,0.06)] border border-beige-dark/40 p-8 sm:p-10 text-center animate-[fadeIn_0.5s_ease-out]">
              <div className="w-20 h-20 rounded-full bg-gradient-to-br from-primary to-primary-dark flex items-center justify-center mx-auto mb-5 shadow-lg shadow-primary/20">
                <Clock className="w-10 h-10 text-white" />
              </div>
              <h2 className="text-2xl font-bold text-stone-900 mb-2">
                {t('auth.agencyRegister.doneTitle')}
              </h2>
              <p className="text-stone-500 mb-2">
                {t('auth.agencyRegister.doneWelcome')}
              </p>
              <div className="flex items-start gap-3 bg-amber-50 border border-amber-100 rounded-xl px-5 py-4 text-right max-w-md mx-auto mt-4 mb-8">
                <Clock className="w-5 h-5 text-amber-600 shrink-0 mt-0.5" />
                <div className="text-sm text-amber-700">
                  <p className="font-medium mb-1">{t('auth.agencyRegister.doneReview')}</p>
                  <p className="text-amber-600/80">
                    {t('auth.agencyRegister.doneDesc')}
                  </p>
                </div>
              </div>
              <div className="flex flex-col sm:flex-row gap-3 justify-center">
                <button onClick={() => navigate('/dashboard')}
                  className="btn-primary flex items-center justify-center gap-2 !py-3 !px-6">
                  <Building2 className="w-4 h-4" />
                  {t('auth.agencyRegister.dashboard')}
                </button>
                <button onClick={() => navigate('/')}
                  className="btn-outline flex items-center justify-center gap-2 !py-3 !px-6">
                  {t('auth.agencyRegister.backHome')}
                </button>
              </div>
            </div>
          )}
        </div>
      </div>
      <Footer />
    </div>
  );
}
