import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Link } from 'react-router-dom';
import { Mail, Loader2, CheckCircle2, ArrowRight, ShieldCheck, Lock } from 'lucide-react';
import Navbar from '../components/Navbar';
import Footer from '../components/Footer';
import { forgotPassword } from '../api/auth';
import { validateForgotPassword, extractServerError, type ValidationErrors } from '../auth/validation';

export default function ForgotPassword() {
  const { t, i18n } = useTranslation();
  const isAr = i18n.language === 'ar';
  const [email, setEmail] = useState('');
  const [errors, setErrors] = useState<ValidationErrors>({});
  const [serverError, setServerError] = useState('');
  const [sent, setSent] = useState(false);
  const [submitting, setSubmitting] = useState(false);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setServerError('');
    setErrors({});

    const fieldErrors = validateForgotPassword({ email });
    if (Object.keys(fieldErrors).length > 0) {
      setErrors(fieldErrors);
      return;
    }

    setSubmitting(true);
    try {
      await forgotPassword(email);
      setSent(true);
    } catch (err: any) {
      setServerError(t(extractServerError(err)));
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div dir={isAr ? 'rtl' : 'ltr'} className="min-h-screen flex flex-col">
      <Navbar />
      <div className="flex-1 pt-20 flex items-center justify-center relative overflow-hidden">
        <div className="absolute inset-0 bg-gradient-to-br from-primary/5 via-cream to-gold/5" />
        <div className="absolute inset-0 hero-mesh" />

        <div className="relative z-10 w-full max-w-md mx-auto px-4 py-8">
          <div className="text-center mb-6">
            <div className="w-14 h-14 rounded-2xl bg-gradient-to-br from-primary to-primary-dark flex items-center justify-center mx-auto mb-3 shadow-lg shadow-primary/20">
              <Lock className="w-7 h-7 text-white" />
            </div>
            <h1 className="text-2xl font-bold text-stone-900">{t('auth.forgotTitle')}</h1>
            <p className="text-stone-500 mt-1 text-sm">{t('auth.forgotSubtitle')}</p>
          </div>

          <div className="bg-white rounded-3xl shadow-[0_4px_40px_rgba(0,0,0,0.06)] border border-beige-dark/40 p-6 sm:p-8">
            {sent ? (
              <div className="text-center py-6 animate-[fadeIn_0.4s_ease-out]">
                <div className="w-16 h-16 rounded-full bg-gradient-to-br from-primary to-primary-dark flex items-center justify-center mx-auto mb-4 shadow-lg shadow-primary/20">
                  <CheckCircle2 className="w-8 h-8 text-white" />
                </div>
                <p className="font-bold text-stone-900 text-lg mb-2">{t('auth.forgotSentTitle')}</p>
                <p className="text-sm text-stone-500 mb-6 max-w-xs mx-auto">{t('auth.forgotSentDesc')}</p>
                <Link to="/login"
                  className="text-primary font-medium hover:text-primary-dark transition-colors inline-flex items-center gap-2">
                  <ArrowRight className="w-4 h-4 lucide-rtl" />
                  {t('auth.backToLogin')}
                </Link>
              </div>
            ) : (
              <>
                {serverError && (
                  <div className="bg-red-50 border border-red-100 text-red-600 text-sm rounded-xl px-4 py-3 mb-5 flex items-center gap-2 animate-[fadeIn_0.3s_ease-out]">
                    <span className="w-1.5 h-1.5 rounded-full bg-red-500 shrink-0" />
                    {serverError}
                  </div>
                )}

                <form onSubmit={handleSubmit} className="space-y-4" noValidate>
                  <div>
                    <label className="text-sm font-medium text-stone-700 mb-1.5 block">{t('auth.email')}</label>
                    <div className="relative">
                      <input type="email" autoComplete="email"
                        value={email}
                        onChange={e => { setEmail(e.target.value); if (errors.email) setErrors(p => ({...p, email: ''})); }}
                        placeholder={t('auth.emailPlaceholder')}
                        className={`input-field pl-10 transition-all duration-200 focus:shadow-[0_0_0_4px] focus:shadow-primary/6 ${
                          errors.email ? 'border-red-300 focus:border-red-400 focus:shadow-red-100' : ''
                        }`}
                        dir="ltr" />
                      <Mail className="absolute top-1/2 -translate-y-1/2 w-4 h-4 text-stone-400"
                        style={{ [isAr ? 'right' : 'left']: '14px' }} />
                    </div>
                    {errors.email && (
                      <p className="text-red-500 text-xs mt-1.5 me-1">{t(errors.email)}</p>
                    )}
                  </div>

                  <button type="submit" disabled={submitting}
                    className="btn-primary w-full flex items-center justify-center gap-2 !py-3.5 text-base mt-2">
                    {submitting ? (
                      <Loader2 className="w-5 h-5 animate-spin" />
                    ) : (
                      <>{t('auth.forgotBtn')}</>
                    )}
                  </button>
                </form>

                <div className="mt-6 text-center">
                  <Link to="/login"
                    className="text-primary font-medium hover:text-primary-dark transition-colors inline-flex items-center gap-2 text-sm">
                    <ArrowRight className="w-4 h-4 lucide-rtl" />
                    {t('auth.backToLogin')}
                  </Link>
                </div>
              </>
            )}
          </div>

          <div className="flex items-center justify-center gap-2 mt-6 text-stone-400">
            <ShieldCheck className="w-4 h-4" />
            <span className="text-xs">{t('auth.secureData')}</span>
          </div>
        </div>
      </div>
      <Footer />
    </div>
  );
}
