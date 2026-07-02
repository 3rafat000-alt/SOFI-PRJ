import { useTranslation } from 'react-i18next';
import { Link } from 'react-router-dom';
import { Building2, Mail, Phone, MapPin, Globe, Camera, Send, MessageCircle } from 'lucide-react';
import { useState, useEffect } from 'react';
import client from '../api/client';
import type { SettingsPublic } from '../api/properties';

export default function Footer() {
  const { t } = useTranslation();
  const [settings, setSettings] = useState<SettingsPublic | null>(null);
  const [email, setEmail] = useState('');
  const [subscribed, setSubscribed] = useState(false);

  useEffect(() => {
    client.get('/settings/public').then(({ data }) => setSettings(data.data)).catch(() => {});
  }, []);

  const handleSubscribe = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!email) return;
    try {
      await client.post('/newsletter', { email });
      setSubscribed(true);
      setEmail('');
      setTimeout(() => setSubscribed(false), 3000);
    } catch {}
  };

  const s = settings;
  const socialLinks = [
    { icon: Globe, href: s?.facebook_url, label: 'Facebook' },
    { icon: Camera, href: s?.instagram_url, label: 'Instagram' },
    { icon: MessageCircle, href: s?.twitter_url, label: 'Twitter' },
    { icon: Send, href: s?.telegram_url, label: 'Telegram' },
  ].filter(l => l.href);

  return (
    <footer className="bg-stone-900 text-stone-300">
      {/* Newsletter */}
      <div className="border-b border-stone-800">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
          <div className="flex flex-col md:flex-row items-center justify-between gap-6">
            <div>
              <h3 className="text-white font-bold text-lg">{t('home.newsletterTitle')}</h3>
              <p className="text-stone-400 text-sm mt-1">{t('home.newsletterSub')}</p>
            </div>
            <form onSubmit={handleSubscribe} className="flex gap-2 w-full md:w-auto">
              <input
                type="email"
                value={email}
                onChange={e => setEmail(e.target.value)}
                placeholder="your@email.com"
                className="bg-stone-800 border border-stone-700 rounded-xl px-4 py-2.5 text-white text-sm w-full md:w-64 focus:outline-none focus:ring-2 focus:ring-gold/30 focus:border-gold/50 placeholder:text-stone-500"
                required
              />
              <button type="submit" className="btn-gold text-sm !py-2 !px-5 whitespace-nowrap">
                {subscribed ? t('home.newsletterSuccess') : t('home.newsletterBtn')}
              </button>
            </form>
          </div>
        </div>
      </div>

      {/* Main */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-10">
          {/* Brand */}
          <div>
            <div className="flex items-center gap-2.5 mb-4">
              <div className="w-9 h-9 rounded-xl bg-primary flex items-center justify-center">
                <Building2 className="w-5 h-5 text-white" />
              </div>
              <span className="text-white font-bold text-xl">سوريا هومز</span>
            </div>
            <p className="text-stone-400 text-sm leading-relaxed">{t('footer.tagline')}</p>
            <div className="flex gap-3 mt-5">
              {socialLinks.map((l) => (
                <a
                  key={l.label}
                  href={l.href!}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="w-9 h-9 rounded-lg bg-stone-800 hover:bg-gold/20 flex items-center justify-center text-stone-400 hover:text-gold transition-all"
                >
                  <l.icon className="w-4 h-4" />
                </a>
              ))}
            </div>
          </div>

          {/* Quick links */}
          <div>
            <h4 className="text-white font-semibold mb-4">{t('footer.quickLinks')}</h4>
            <ul className="space-y-3">
              {[
                { to: '/', label: t('footer.home') },
                { to: '/properties', label: t('footer.properties') },
              ].map((lnk) => (
                <li key={lnk.to}>
                  <Link to={lnk.to} className="text-stone-400 hover:text-gold transition-colors text-sm">
                    {lnk.label}
                  </Link>
                </li>
              ))}
            </ul>
          </div>

          {/* Contact */}
          <div>
            <h4 className="text-white font-semibold mb-4">{t('footer.contactUs')}</h4>
            <ul className="space-y-3 text-sm">
              <li className="flex items-center gap-2">
                <Mail className="w-4 h-4 text-gold shrink-0" />
                <span className="text-stone-400">{s?.contact_email || 'info@syriahomes.com'}</span>
              </li>
              <li className="flex items-center gap-2">
                <Phone className="w-4 h-4 text-gold shrink-0" />
                <span className="text-stone-400">{s?.contact_phone || '+963 11 234 5678'}</span>
              </li>
              <li className="flex items-center gap-2">
                <MapPin className="w-4 h-4 text-gold shrink-0" />
                <span className="text-stone-400">{'دمشق، سوريا'}</span>
              </li>
            </ul>
          </div>

          {/* Working hours */}
          <div>
            <h4 className="text-white font-semibold mb-4">{t('contact.workingHours')}</h4>
            <p className="text-stone-400 text-sm">{t('contact.workingHoursDetail')}</p>
            <div className="mt-4 p-4 rounded-xl bg-stone-800/50 border border-stone-700/30">
              <p className="text-gold text-sm font-medium">⭐ {t('home.ctaTitle')}</p>
              <p className="text-stone-400 text-xs mt-1">{t('home.ctaSubtitle')}</p>
            </div>
          </div>
        </div>
      </div>

      {/* Bottom */}
      <div className="border-t border-stone-800">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 flex flex-col md:flex-row items-center justify-between gap-4 text-sm text-stone-500">
          <p>&copy; {new Date().getFullYear()} سوريا هومز. {t('footer.rights')}.</p>
          <div className="flex gap-6">
            <span>{t('footer.privacy')}</span>
            <span>{t('footer.terms')}</span>
          </div>
        </div>
      </div>
    </footer>
  );
}
