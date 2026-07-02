import { useTranslation } from 'react-i18next';
import { Building2, Target, Eye, ShieldCheck, Award, Users, Star, TrendingUp, Sparkles } from 'lucide-react';
import { Link } from 'react-router-dom';
import Navbar from '../components/Navbar';
import Footer from '../components/Footer';

export default function About() {
  const { t, i18n } = useTranslation();
  const isAr = i18n.language === 'ar';

  const values = t('about.valuesList', { returnObjects: true }) as string[];
  const stats = [
    { icon: Building2, value: '500+', label: t('home.stats.properties') },
    { icon: Users, value: '50+', label: t('home.stats.agents') },
    { icon: Award, value: '15K+', label: t('home.stats.clients') },
    { icon: Star, value: '98%', label: t('home.stats.satisfaction') },
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
            <span className="text-white/80 text-sm font-medium">{t('about.subtitle')}</span>
          </div>
          <h1 className="text-4xl md:text-6xl lg:text-7xl font-bold text-white leading-tight mb-5"
            style={{ textShadow: '0 2px 30px rgba(0,0,0,0.15)' }}>
            {t('about.title')}
          </h1>
          <p className="text-lg md:text-xl text-white/70 max-w-2xl mx-auto leading-relaxed">
            {t('about.subtitle')}
          </p>
        </div>
      </section>

      {/* ═══════════ MISSION & VISION ═══════════ */}
      <section className="py-24">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="grid md:grid-cols-2 gap-8">
            <div className="card-3d p-8 md:p-10 relative overflow-hidden group">
              <div className="absolute -top-10 -right-10 w-40 h-40 bg-primary/5 rounded-full blur-3xl group-hover:bg-primary/10 transition-all duration-500" />
              <Target className="w-12 h-12 text-primary mb-5" />
              <h2 className="text-2xl font-bold text-stone-900 mb-4">{t('about.mission')}</h2>
              <p className="text-stone-600 leading-relaxed text-lg">{t('about.missionDesc')}</p>
            </div>
            <div className="card-3d p-8 md:p-10 relative overflow-hidden group">
              <div className="absolute -bottom-10 -left-10 w-40 h-40 bg-gold/5 rounded-full blur-3xl group-hover:bg-gold/10 transition-all duration-500" />
              <Eye className="w-12 h-12 text-gold-dark mb-5" />
              <h2 className="text-2xl font-bold text-stone-900 mb-4">{t('about.vision')}</h2>
              <p className="text-stone-600 leading-relaxed text-lg">{t('about.visionDesc')}</p>
            </div>
          </div>
        </div>
      </section>

      {/* ═══════════ VALUES ═══════════ */}
      <section className="py-24 bg-white section-divider-top section-divider-bottom">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-14">
            <h2 className="section-title">{t('about.values')}</h2>
          </div>
          <div className="grid grid-cols-2 md:grid-cols-4 gap-6">
            {values.map((v, i) => {
              const icons = [ShieldCheck, Award, TrendingUp, Star];
              const gradients = [
                'bg-primary/10 text-primary',
                'bg-gold/10 text-gold-dark',
                'bg-emerald-50 text-emerald-600',
                'bg-stone-50 text-stone',
              ];
              const Icon = icons[i] || ShieldCheck;
              return (
                <div key={i} className="card-3d p-6 md:p-8 text-center group">
                  <div className={`w-16 h-16 rounded-2xl ${gradients[i]} flex items-center justify-center mx-auto mb-5 group-hover:scale-110 group-hover:-translate-y-1 transition-all duration-300`}>
                    <Icon className="w-8 h-8" />
                  </div>
                  <h3 className="font-bold text-stone-900 text-lg">{v}</h3>
                </div>
              );
            })}
          </div>
        </div>
      </section>

      {/* ═══════════ STATS ═══════════ */}
      <section className="py-24">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-12">
            <h2 className="section-title">{t('about.stats')}</h2>
          </div>
          <div className="grid grid-cols-2 md:grid-cols-4 gap-6">
            {stats.map((s, i) => (
              <div key={i} className="card-3d p-6 text-center group hover:-translate-y-2">
                <div className="w-14 h-14 rounded-2xl bg-primary/5 flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-all">
                  <s.icon className="w-7 h-7 text-primary" />
                </div>
                <div className="text-3xl font-bold text-stone-900 mb-1">{s.value}</div>
                <div className="text-sm text-stone-500">{s.label}</div>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* ═══════════ CTA ═══════════ */}
      <section className="py-24">
        <div className="max-w-4xl mx-auto px-4 text-center">
          <div className="card-3d p-10 md:p-14 bg-primary text-white">
            <Building2 className="w-14 h-14 text-gold mx-auto mb-5" />
            <h2 className="text-3xl md:text-4xl font-bold mb-3">{t('home.ctaTitle')}</h2>
            <p className="text-white/70 text-lg mb-8 max-w-lg mx-auto">{t('home.ctaSubtitle')}</p>
            <div className="flex flex-wrap justify-center gap-4">
              <Link to="/register" className="btn-gold text-lg !px-10 !py-4 shadow-xl">
                {t('home.ctaBtn')}
              </Link>
              <Link to="/properties" className="text-white/80 hover:text-white border-2 border-white/20 hover:border-white/40 rounded-xl px-8 py-4 text-lg font-medium transition-all backdrop-blur-sm">
                {t('home.ctaUserBtn')}
              </Link>
            </div>
          </div>
        </div>
      </section>

      <Footer />
    </div>
  );
}
