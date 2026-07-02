import { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { Mail, Phone, MapPin, Clock, Send, Check, Sparkles } from 'lucide-react';
import Navbar from '../components/Navbar';
import Footer from '../components/Footer';
import { fetchSettings } from '../api/properties';
import type { SettingsPublic } from '../api/properties';

export default function Contact() {
  const { t, i18n } = useTranslation();
  const isAr = i18n.language === 'ar';
  const [settings, setSettings] = useState<SettingsPublic | null>(null);
  const [form, setForm] = useState({ name: '', email: '', phone: '', subject: '', message: '' });
  const [sent, setSent] = useState(false);
  const [submitting, setSubmitting] = useState(false);

  useEffect(() => {
    fetchSettings().then(setSettings).catch(() => {});
  }, []);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSubmitting(true);
    // Simulate send — connect to API later
    await new Promise(r => setTimeout(r, 800));
    setSent(true);
    setSubmitting(false);
  };

  const contactInfo = [
    { icon: MapPin, label: t('contact.address'), value: settings?.address_ar || 'دمشق، سوريا' },
    { icon: Phone, label: t('contact.phoneLabel'), value: settings?.contact_phone || '+963 11 234 5678' },
    { icon: Mail, label: t('contact.emailLabel'), value: settings?.contact_email || 'info@syriahomes.sy' },
    { icon: Clock, label: t('contact.workingHours'), value: t('contact.workingHoursDetail') },
  ];

  return (
    <div dir={isAr ? 'rtl' : 'ltr'} className="overflow-x-hidden">
      <Navbar />

      {/* ═══════════ HERO ═══════════ */}
      <section className="relative pt-32 pb-20 overflow-hidden bg-hero">
        <div className="hero-mesh" aria-hidden="true" />
        <div className="hero-vignette" aria-hidden="true" />
        <div className="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center" style={{ zIndex: 10 }}>
          <div className="inline-flex items-center gap-2 bg-white/10 backdrop-blur-sm rounded-full px-5 py-2 mb-6 border border-white/10">
            <Sparkles className="w-4 h-4 text-gold" />
            <span className="text-white/80 text-sm font-medium">{t('contact.subtitle')}</span>
          </div>
          <h1 className="text-4xl md:text-6xl font-bold text-white leading-tight mb-4"
            style={{ textShadow: '0 2px 30px rgba(0,0,0,0.15)' }}>
            {t('contact.title')}
          </h1>
          <p className="text-lg text-white/70 max-w-xl mx-auto">{t('contact.subtitle')}</p>
        </div>
      </section>

      {/* ═══════════ CONTENT ═══════════ */}
      <section className="py-24">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="grid lg:grid-cols-3 gap-10">
            {/* Contact Info */}
            <div className="space-y-6">
              {contactInfo.map((info, i) => (
                <div key={i} className="card-3d p-5 flex items-start gap-4 group">
                  <div className="w-12 h-12 rounded-2xl bg-primary/5 flex items-center justify-center shrink-0 group-hover:scale-110 transition-all">
                    <info.icon className="w-5 h-5 text-primary" />
                  </div>
                  <div>
                    <div className="text-sm font-semibold text-stone-500 mb-0.5">{info.label}</div>
                    <div className="text-stone-900 font-medium">{info.value}</div>
                  </div>
                </div>
              ))}

              {/* Social */}
              <div className="card-3d p-5">
                <h3 className="font-bold text-stone-900 mb-3">{t('footer.followUs')}</h3>
                <div className="flex gap-3">
                  {settings?.facebook_url && (
                    <a href={settings.facebook_url} target="_blank" rel="noopener noreferrer"
                      className="w-10 h-10 rounded-xl bg-primary/5 flex items-center justify-center hover:bg-primary/10 hover:scale-110 transition-all text-primary">
                      <span className="text-sm font-bold">ف</span>
                    </a>
                  )}
                  {settings?.instagram_url && (
                    <a href={settings.instagram_url} target="_blank" rel="noopener noreferrer"
                      className="w-10 h-10 rounded-xl bg-gold/5 flex items-center justify-center hover:bg-gold/10 hover:scale-110 transition-all text-gold-dark">
                      <span className="text-sm font-bold">إن</span>
                    </a>
                  )}
                  {settings?.twitter_url && (
                    <a href={settings.twitter_url} target="_blank" rel="noopener noreferrer"
                      className="w-10 h-10 rounded-xl bg-stone-100 flex items-center justify-center hover:bg-stone-200 hover:scale-110 transition-all text-stone">
                      <span className="text-sm font-bold">X</span>
                    </a>
                  )}
                </div>
              </div>
            </div>

            {/* Form */}
            <div className="lg:col-span-2">
              <div className="card-3d p-8 md:p-10">
                <h2 className="text-2xl font-bold text-stone-900 mb-6">{t('contact.title')}</h2>
                {sent ? (
                  <div className="text-center py-12">
                    <div className="w-16 h-16 rounded-2xl bg-primary/10 flex items-center justify-center mx-auto mb-4">
                      <Check className="w-8 h-8 text-primary" />
                    </div>
                    <p className="text-xl font-bold text-stone-900 mb-2">{t('contact.success')}</p>
                    <button onClick={() => { setSent(false); setForm({ name: '', email: '', phone: '', subject: '', message: '' }); }}
                      className="text-primary hover:text-primary-dark font-medium underline underline-offset-4 cursor-pointer">
                      {t('contact.send')} {t('contact.message')}
                    </button>
                  </div>
                ) : (
                  <form onSubmit={handleSubmit} className="space-y-5">
                    <div className="grid md:grid-cols-2 gap-5">
                      <input type="text" required value={form.name}
                        onChange={e => setForm({...form, name: e.target.value})}
                        placeholder={t('contact.name')} className="input-field" />
                      <input type="email" value={form.email}
                        onChange={e => setForm({...form, email: e.target.value})}
                        placeholder={t('contact.email')} className="input-field" />
                    </div>
                    <div className="grid md:grid-cols-2 gap-5">
                      <input type="tel" value={form.phone}
                        onChange={e => setForm({...form, phone: e.target.value})}
                        placeholder={t('contact.phone')} className="input-field" />
                      <input type="text" value={form.subject}
                        onChange={e => setForm({...form, subject: e.target.value})}
                        placeholder={t('contact.subject')} className="input-field" />
                    </div>
                    <textarea rows={5} required value={form.message}
                      onChange={e => setForm({...form, message: e.target.value})}
                      placeholder={t('contact.message')} className="input-field" />
                    <button type="submit" disabled={submitting}
                      className="btn-primary w-full flex items-center justify-center gap-2 text-lg !py-4">
                      {submitting ? '...' : <><Send className="w-5 h-5" /> {t('contact.send')}</>}
                    </button>
                  </form>
                )}
              </div>
            </div>
          </div>

          {/* Map placeholder */}
          <div className="mt-10 card-3d h-64 md:h-80 flex items-center justify-center bg-beige">
            <div className="text-center">
              <MapPin className="w-12 h-12 text-primary mx-auto mb-3 opacity-40" />
              <p className="text-stone-400 font-medium">دمشق، سوريا</p>
              <p className="text-stone-300 text-sm">{settings?.address_ar || ''}</p>
            </div>
          </div>
        </div>
      </section>

      <Footer />
    </div>
  );
}
