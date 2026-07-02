import { useState, useEffect, useRef } from 'react';
import { useTranslation } from 'react-i18next';
import {
  Search, MapPin, Building2, Users, Home as HomeIcon, Star,
  TrendingUp, ShieldCheck, Smartphone, HeadphonesIcon, CheckCircle,
  ArrowLeft, Award, Sparkles, Bed, Bath, Maximize2,
} from 'lucide-react';
import { Link, useNavigate } from 'react-router-dom';
import Navbar from '../components/Navbar';
import Footer from '../components/Footer';
import PropertyCard from '../components/PropertyCard';
import SelectField from '../components/SelectField';
import {
  fetchFeatured, fetchHotDeals, fetchStats, fetchTestimonials,
  fetchPropertyTypes, fetchAgencies, fetchProperties,
} from '../api/properties';
import type { AgencyPublic, Testimonial } from '../api/properties';
import { fetchLocations } from '../api/locations';
import type { PropertyCard as PropertyCardType, Governorate, PropertyType } from '../api/client';
import useSEOMeta from '../hooks/useSEOMeta';

// ─── Scroll reveal hook (callback ref — works with conditional rendering) ───
function useReveal() {
  const obsRef = useRef<IntersectionObserver | null>(null);
  const moRef = useRef<MutationObserver | null>(null);
  const intersectedRef = useRef(false);

  const markVisible = (el: HTMLDivElement) => {
    el.querySelectorAll('.reveal').forEach(c => c.classList.add('visible'));
    if (el.classList.contains('reveal')) el.classList.add('visible');
  };

  const ref = (el: HTMLDivElement | null) => {
    // Cleanup previous observers
    if (obsRef.current) { obsRef.current.disconnect(); obsRef.current = null; }
    if (moRef.current) { moRef.current.disconnect(); moRef.current = null; }
    intersectedRef.current = false;
    if (!el) return;

    const obs = new IntersectionObserver(
      ([entry]) => { if (entry.isIntersecting) { intersectedRef.current = true; markVisible(el); } },
      { threshold: 0.1 }
    );
    obs.observe(el);
    obsRef.current = obs;

    // Watch for DOM mutation (data-loaded sections add .reveal elements after observer fires)
    const mo = new MutationObserver(() => { if (intersectedRef.current) markVisible(el); });
    mo.observe(el, { childList: true, subtree: true });
    moRef.current = mo;
  };
  return ref;
}

// ─── Animated Counter ───
function Counter({ value, suffix = '' }: { value: number; suffix?: string }) {
  const [display, setDisplay] = useState(0);
  const counted = useRef(false);
  useEffect(() => {
    if (counted.current || value === 0) {
      setDisplay(value);
      return;
    }
    // Auto-animate after 200ms delay — no IntersectionObserver needed
    const tmr = setTimeout(() => {
      counted.current = true;
      const steps = 25; const stepVal = value / steps;
      let cur = 0;
      const interval = setInterval(() => {
        cur += stepVal;
        if (cur >= value) { setDisplay(value); clearInterval(interval); }
        else setDisplay(Math.floor(cur));
      }, 40);
    }, 200);
    return () => clearTimeout(tmr);
  }, [value]);
  return <span>{display}{suffix}</span>;
}

// ─── Swiper ───
function SwiperDots({ total, active, onChange }: { total: number; active: number; onChange: (i: number) => void }) {
  return (
    <div className="flex justify-center gap-2 mt-8">
      {Array.from({ length: total }).map((_, i) => (
        <button key={i} onClick={() => onChange(i)}
          className={`w-2.5 h-2.5 rounded-full transition-all duration-500 cursor-pointer ${
            i === active ? 'w-8 bg-gold' : 'bg-beige-dark hover:bg-gold/40'
          }`} />
      ))}
    </div>
  );
}

// ─── Particles ───
function Particles({ count = 6 }: { count?: number }) {
  return (
    <div className="particles" aria-hidden="true">
      {Array.from({ length: count }).map((_, i) => (
        <div key={i} className="particle" />
      ))}
    </div>
  );
}

// ─── 3D Blobs ───
function Blobs({ colors }: { colors: string[] }) {
  return (
    <div className="absolute inset-0 overflow-hidden pointer-events-none" aria-hidden="true">
      {colors.map((c, i) => (
        <div key={i}
          className="blob"
          style={{
            background: c,
            width: `${300 + i * 150}px`,
            height: `${300 + i * 150}px`,
            top: `${10 + i * 20}%`,
            left: `${5 + i * 25}%`,
          }} />
      ))}
    </div>
  );
}

// ─── 3D floating shapes ───
function FloatingShapes() {
  const shapes = [
    { size: 60, top: '15%', left: '5%', color: 'rgba(201,168,76,0.06)', rotate: 45, dur: 14 },
    { size: 40, top: '70%', left: '90%', color: 'rgba(26,107,60,0.05)', rotate: 30, dur: 18 },
    { size: 80, top: '80%', left: '10%', color: 'rgba(201,168,76,0.04)', rotate: 60, dur: 16 },
    { size: 30, top: '20%', left: '85%', color: 'rgba(26,107,60,0.06)', rotate: 20, dur: 12 },
  ];
  return (
    <div className="absolute inset-0 overflow-hidden pointer-events-none" aria-hidden="true">
      {shapes.map((s, i) => (
        <div key={i} className="shape-3d"
          style={{
            top: s.top, left: s.left,
            width: s.size, height: s.size,
            borderRadius: `${30 + (i * 10)}% ${60 - (i * 10)}% ${40 + (i * 5)}% ${50 - (i * 5)}%`,
            background: s.color,
            border: '1px solid rgba(201,168,76,0.08)',
            animationDuration: `${s.dur}s`,
            animationDelay: `${-i * 3}s`,
          }} />
      ))}
    </div>
  );
}

// ═══════════════════════ MAIN ═══════════════════════

export default function Home() {
  const { t, i18n } = useTranslation();
  const isAr = i18n.language === 'ar';
  const navigate = useNavigate();
  const [featured, setFeatured] = useState<PropertyCardType[]>([]);
  const [latest, setLatest] = useState<PropertyCardType[]>([]);
  const [hotDeals, setHotDeals] = useState<PropertyCardType[]>([]);
  const [stats, setStats] = useState<any>({});
  const [testimonials, setTestimonials] = useState<Testimonial[]>([]);
  const [propertyTypes, setPropertyTypes] = useState<PropertyType[]>([]);
  const [governorates, setGovernorates] = useState<Governorate[]>([]);
  const [agencies, setAgencies] = useState<AgencyPublic[]>([]);
  const [searchQuery, setSearchQuery] = useState('');
  const [searchGov, setSearchGov] = useState('');
  const [testiIdx, setTestiIdx] = useState(0);
  const [mousePos, setMousePos] = useState({ x: 0, y: 0 });

  // ── SEO: Organization + WebSite schema ──
  const siteUrl = window.location.origin;
  const brand = isAr ? 'سورية هومز' : 'Syria Homes';
  const tagline = isAr ? 'سوق العقارات الرائد في سورية' : 'Leading Real Estate Marketplace in Syria';

  useSEOMeta({
    title: `${brand} | ${tagline}`,
    description: isAr
      ? 'سورية هومز هو سوق العقارات الرائد في سورية. ابحث عن عقارات للبيع والإيجار، وتواصل مع الوكلاء المعتمدين.'
      : 'Syria Homes is the leading real estate marketplace in Syria. Find properties for sale and rent, connect with trusted agents.',
    url: siteUrl,
    type: 'website',
    schema: {
      '@context': 'https://schema.org',
      '@graph': [
        {
          '@type': 'Organization',
          '@id': `${siteUrl}/#organization`,
          name: brand,
          url: siteUrl,
          logo: `${siteUrl}/logo.png`,
          description: tagline,
          sameAs: [
            'https://syriahomes.zanjour.com',
          ],
          address: {
            '@type': 'PostalAddress',
            addressCountry: 'SY',
          },
        },
        {
          '@type': 'WebSite',
          '@id': `${siteUrl}/#website`,
          url: siteUrl,
          name: brand,
          description: tagline,
          publisher: { '@id': `${siteUrl}/#organization` },
          potentialAction: {
            '@type': 'SearchAction',
            target: {
              '@type': 'EntryPoint',
              urlTemplate: `${siteUrl}/properties?search={search_term_string}`,
            },
            'query-input': 'required name=search_term_string',
          },
        },
      ],
    },
  });

  // Parallax mouse tracker
  useEffect(() => {
    const onMove = (e: MouseEvent) => {
      setMousePos({ x: e.clientX / window.innerWidth - 0.5, y: e.clientY / window.innerHeight - 0.5 });
    };
    window.addEventListener('mousemove', onMove);
    return () => window.removeEventListener('mousemove', onMove);
  }, []);

  useEffect(() => {
    Promise.all([
      fetchFeatured().then(setFeatured),
      fetchProperties({ sort: 'newest', per_page: '12' }).then(r => setLatest(r.data)),
      fetchHotDeals().then(setHotDeals),
      fetchStats().then(setStats),
      fetchTestimonials().then(setTestimonials),
      fetchPropertyTypes().then(setPropertyTypes),
      fetchLocations().then((r) => setGovernorates(r.governorates)),
      fetchAgencies().then(setAgencies),
    ]).catch(() => {});
  }, []);

  // Auto testimonial
  useEffect(() => {
    if (testimonials.length <= 1) return;
    const tmr = setInterval(() => setTestiIdx((i) => (i + 1) % testimonials.length), 4000);
    return () => clearInterval(tmr);
  }, [testimonials.length]);

  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    const params = new URLSearchParams();
    if (searchQuery) params.set('q', searchQuery);
    if (searchGov) params.set('governorate', searchGov);
    navigate(`/properties?${params.toString()}`);
  };

  const iconMap: Record<string, any> = {
    'building-apartment': Building2, 'building-villa': HomeIcon,
    'house': HomeIcon, 'store': Building2, 'land-plot': MapPin,
  };

  const features = t('home.features', { returnObjects: true }) as unknown as { title: string; desc: string }[];
  const r = mousePos; // parallax offset

  // ─── Refs for reveal ───
  const typesRef = useReveal();
  const featuresRef = useReveal();
  const featuredSecRef = useReveal();
  const latestRef = useReveal();
  const agenciesRef = useReveal();
  const hotRef = useReveal();
  const aboutRef = useReveal();
  const testiRef = useReveal();
  const citiesRef = useReveal();
  const typesTitleRef = useReveal();
  const ctaRef = useReveal();

  // Scroll progress
  const [scrollPct, setScrollPct] = useState(0);
  useEffect(() => {
    const onScroll = () => {
      const h = document.documentElement.scrollHeight - window.innerHeight;
      setScrollPct(h > 0 ? window.scrollY / h : 0);
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    return () => window.removeEventListener('scroll', onScroll);
  }, []);

  const cities: { slug: string; name: string; img: string; bg: string; count: number }[] = [
    { slug: 'دمشق', name: t('home.exploreDamascus'), img: 'https://images.unsplash.com/photo-1580674684081-7617fbf3d745?w=600&q=80', bg: 'bg-stone-900/70', count: 8 },
    { slug: 'حلب', name: t('home.exploreAleppo'), img: 'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?w=600&q=80', bg: 'bg-stone-900/70', count: 5 },
    { slug: 'اللاذقية', name: t('home.exploreLatakia'), img: 'https://images.unsplash.com/photo-1590523277543-a94d2e4eb00b?w=600&q=80', bg: 'bg-stone-900/70', count: 4 },
    { slug: 'حمص', name: t('home.exploreHoms'), img: 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=600&q=80', bg: 'bg-stone-900/70', count: 3 },
  ];

  return (
    <div dir={isAr ? 'rtl' : 'ltr'} className="overflow-x-hidden">
      <Navbar />

      {/* Scroll progress bar */}
      <div className="scroll-progress" style={{ transform: `scaleX(${scrollPct})` }} />

      {/* ═══════════════ HERO + STATS ═══════════════ */}
      <section className="relative min-h-screen flex flex-col bg-hero">
        {/* Animated background */}
        <Blobs colors={['rgba(201,168,76,0.12)', 'rgba(26,107,60,0.08)', 'rgba(255,255,255,0.05)']} />
        <FloatingShapes />
        <Particles count={8} />
        <div className="hero-mesh" aria-hidden="true" />
        <div className="hero-vignette" aria-hidden="true" />

        {/* 3D parallax orbs */}
        <div className="absolute inset-0 pointer-events-none" aria-hidden="true"
          style={{
            transform: `translate(${r.x * 15}px, ${r.y * 15}px)`,
            transition: 'transform 0.1s ease-out',
          }}>
          <div className="orb" style={{ width: 400, height: 400, background: 'var(--color-gold)', top: '20%', left: '10%' }} />
          <div className="orb" style={{ width: 300, height: 300, background: 'var(--color-primary)', bottom: '10%', right: '15%' }} />
          <div className="orb" style={{ width: 250, height: 250, background: 'var(--color-primary-light)', top: '50%', left: '60%' }} />
        </div>

        {/* ── النصف العلوي: محتوى الهيرو ── */}
        <div className="flex-1 flex items-center relative" style={{ zIndex: 10 }}>
          <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 lg:py-28 w-full">
            <div className="grid lg:grid-cols-2 gap-10 lg:gap-16 items-center">
              {/* Left — Hero text & search */}
              <div className="animate-fade-in">
                <div className="glass-3d inline-flex items-center gap-2 rounded-full px-4 py-1.5 mb-6 animate-float"
                  style={{ animationDelay: '0.5s' }}>
                  <Sparkles className="w-4 h-4 text-gold" />
                  <span className="text-white/90 text-sm font-medium">{t('home.stats.clients')} +15,000</span>
                </div>
                <h1 className="text-4xl sm:text-5xl lg:text-6xl xl:text-7xl font-bold text-white leading-tight mb-5"
                  style={{ textShadow: '0 2px 30px rgba(0,0,0,0.15)' }}>
                  {t('hero.title')}
                </h1>
                <p className="text-lg md:text-xl text-white/70 mb-8 max-w-xl leading-relaxed">
                  {t('hero.subtitle')}
                </p>

                {/* Search */}
                <form onSubmit={handleSearch}
                  className="glass-3d p-2 rounded-2xl flex flex-col md:flex-row gap-2 max-w-2xl transition-all duration-300 hover:shadow-[0_8px_40px_rgba(0,0,0,0.12)]"
                  style={{ transform: `perspective(1000px) rotateX(${r.y * -2}deg) rotateY(${r.x * 2}deg)` }}>
                  <div className="flex-1 relative">
                    <Search className="absolute top-1/2 -translate-y-1/2 text-white/40 w-5 h-5" style={{ [isAr ? 'right' : 'left']: '14px' }} />
                    <input type="text" value={searchQuery}
                      onChange={e => setSearchQuery(e.target.value)}
                      placeholder={t('hero.search')}
                      className="input-glass w-full ltr:pl-10 rtl:pr-10" />
                  </div>
                  <div className="md:w-44 relative">
                    <MapPin className="absolute top-1/2 -translate-y-1/2 text-white/40 w-5 h-5 z-10" style={{ [isAr ? 'right' : 'left']: '14px' }} />
                    <SelectField
                      value={searchGov} onChange={setSearchGov}
                      placeholder={t('hero.allGovernorates')}
                      options={governorates.map((g) => ({ value: g.slug, label: g.name }))}
                      variant="glass" selectClassName="ltr:!pl-10 rtl:!pr-10 !py-3" />
                  </div>
                  <button type="submit" className="btn-gold !rounded-xl whitespace-nowrap shadow-lg shadow-gold/20">
                    {t('hero.searchBtn')}
                  </button>
                </form>

                <div className="flex flex-wrap gap-3 mt-6">
                  <Link to="/properties" className="btn-white text-sm !py-2 !px-4 shadow-xl hover:shadow-2xl transition-shadow">
                    {t('hero.browseBtn')}
                  </Link>
                  <Link to="/register"
                    className="text-sm text-white/70 hover:text-white flex items-center gap-2 px-4 py-2 transition-all hover:bg-white/5 rounded-lg">
                    <Building2 className="w-4 h-4" /> {t('hero.agencyBtn')}
                  </Link>
                </div>
              </div>

              {/* Right — 3D image collage */}
              <div className="hidden lg:grid grid-cols-2 gap-4 animate-slide-in-right perspective-1500">
                <div className="space-y-4" style={{ transform: `translateY(${r.y * -20}px)` }}>
                  <div className="rounded-2xl overflow-hidden shadow-2xl tilt-card"
                    style={{ transform: `rotateY(${r.x * 4}deg) rotateX(${r.y * -3}deg)` }}>
                    <img src="https://images.unsplash.com/photo-1564013799919-ab600027ffc6?w=500&q=80" alt="Luxury home"
                      className="w-full h-48 object-cover hover:scale-110 transition-transform duration-700" loading="lazy" />
                  </div>
                  <div className="rounded-2xl overflow-hidden shadow-2xl tilt-card"
                    style={{ transform: `rotateY(${r.x * 3}deg) rotateX(${r.y * -2}deg)` }}>
                    <img src="https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=500&q=80" alt="Villa"
                      className="w-full h-56 object-cover hover:scale-110 transition-transform duration-700" loading="lazy" />
                  </div>
                </div>
                <div className="space-y-4" style={{ transform: `translateY(${r.y * 20}px)` }}>
                  <div className="rounded-2xl overflow-hidden shadow-2xl tilt-card"
                    style={{ transform: `rotateY(${r.x * -3}deg) rotateX(${r.y * 2}deg)` }}>
                    <img src="https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=500&q=80" alt="House"
                      className="w-full h-56 object-cover hover:scale-110 transition-transform duration-700" loading="lazy" />
                  </div>
                  <div className="rounded-2xl overflow-hidden shadow-2xl tilt-card"
                    style={{ transform: `rotateY(${r.x * -4}deg) rotateX(${r.y * 3}deg)` }}>
                    <img src="https://images.unsplash.com/photo-1600566752355-35792bedcfea?w=500&q=80" alt="Apartment"
                      className="w-full h-48 object-cover hover:scale-110 transition-transform duration-700" loading="lazy" />
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* ── النصف السفلي: إحصائيات احترافية ── */}
        <div className="relative pb-12 lg:pb-16" style={{ zIndex: 20 }}>
          <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div className="bg-white/10 backdrop-blur-xl border border-white/15 rounded-3xl p-6 md:p-8 lg:p-10 shadow-2xl shadow-black/20">
              <div className="grid grid-cols-3 md:grid-cols-6 gap-5 md:gap-6">
                {[
                  { icon: Building2, value: stats.total_properties ?? 20, label: isAr ? 'عقار' : 'Properties', color: 'text-gold' },
                  { icon: Users, value: stats.total_agents ?? 6, label: isAr ? 'وكيل عقاري' : 'Agents', color: 'text-emerald-400' },
                  { icon: Building2, value: stats.total_agencies ?? 3, label: isAr ? 'شركة عقارية' : 'Agencies', color: 'text-blue-400' },
                  { icon: MapPin, value: stats.total_governorates ?? 14, label: isAr ? 'مدينة ومحافظة' : 'Cities', color: 'text-violet-400' },
                  { icon: Award, value: stats.happy_clients ?? 1500, label: isAr ? 'عميل سعيد' : 'Happy Clients', color: 'text-amber-400' },
                  { icon: Star, value: stats.satisfaction_pct ?? 98, suffix: '%', label: isAr ? 'نسبة رضا' : 'Satisfaction', color: 'text-rose-400' },
                ].map((s, i) => (
                  <div key={i} className="text-center group">
                    <div className={`w-12 h-12 rounded-2xl bg-white/10 backdrop-blur-sm flex items-center justify-center mx-auto mb-3 shadow-lg group-hover:scale-110 group-hover:-translate-y-1 transition-all duration-300 ${s.color}`}>
                      <s.icon className="w-6 h-6" />
                    </div>
                    <div className="text-2xl md:text-3xl lg:text-4xl font-bold text-white drop-shadow-lg">
                      <Counter value={typeof s.value === 'number' ? s.value : 0} />{s.suffix || '+'}
                    </div>
                    <div className="text-xs md:text-sm text-white/60 mt-1 font-medium">{s.label}</div>
                  </div>
                ))}
              </div>
              {/* Decorative progress bar */}
              <div className="mt-6 pt-5 border-t border-white/10 flex items-center gap-3 justify-center">
                <span className="w-2 h-2 rounded-full bg-gold animate-pulse" />
                <span className="text-xs text-white/40 font-medium">{isAr ? 'سوق عقاري متكامل' : 'Complete Real Estate Marketplace'}</span>
                <span className="text-white/20">·</span>
                <span className="text-xs text-gold/60 font-semibold">{isAr ? 'ثقة واحترافية' : 'Trust & Professionalism'}</span>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* ═══════════════ PROPERTY TYPES ═══════════════ */}
      {propertyTypes.length > 0 && (
        <section className="py-24" ref={typesRef}>
          <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div className="text-center mb-14 reveal" ref={typesTitleRef}>
              <span className="badge-primary mb-4 inline-block">{t('home.propertyTypes')}</span>
              <h2 className="section-title">{t('home.propertyTypes')}</h2>
              <p className="section-subtitle mx-auto">{t('home.propertyTypesSub')}</p>
            </div>
            <div className="grid grid-cols-2 md:grid-cols-5 gap-4">
              {propertyTypes.map((pt, i) => {
                const Icon = iconMap[pt.icon] || Building2;
                return (
                  <Link key={pt.id} to={`/properties?type=${pt.slug}`}
                    className="card-3d p-6 md:p-8 text-center group reveal"
                    style={{ animationDelay: `${i * 0.1}s`, transitionDelay: `${i * 0.1}s` }}>
                    <div className="w-16 h-16 rounded-2xl bg-primary/5 flex items-center justify-center mx-auto mb-4
                      group-hover:scale-110 group-hover:-translate-y-1 transition-all duration-300">
                      <Icon className="w-8 h-8 text-primary group-hover:text-gold transition-colors duration-300" />
                    </div>
                    <div className="font-bold text-stone-800 group-hover:text-primary transition-colors">{pt.name}</div>
                    <div className="text-xs text-stone-400 mt-2">{pt.listings_count || 0} {t('home.stats.properties')}</div>
                  </Link>
                );
              })}
            </div>
          </div>
        </section>
      )}

      {/* ═══════════════ FEATURES ═══════════════ */}
      <section className="py-24 bg-white relative section-divider-top section-divider-bottom" ref={featuresRef}>
        <Particles count={6} />
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
          <div className="text-center mb-14 reveal">
            <span className="badge-primary mb-4 inline-block">{t('home.featuresTitle')}</span>
            <h2 className="section-title">{t('home.featuresTitle')}</h2>
            <p className="section-subtitle mx-auto">{t('home.featuresSub')}</p>
          </div>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 perspective-1000">
            {features.map((f, i) => {
              const icons = [ShieldCheck, Smartphone, Search, HeadphonesIcon];
              const gradients = [
                'bg-primary/10 text-primary',
                'bg-gold/10 text-gold-dark',
                'bg-emerald-50 text-emerald-600',
                'bg-stone-50 text-stone',
              ];
              const Icon = icons[i] || ShieldCheck;
              return (
                <div key={i}
                  className="card-3d p-6 md:p-8 text-center group tilt-card reveal reveal-delay-2"
                  style={{ perspective: '800px', transitionDelay: `${i * 0.1}s` }}
                  onClick={() => {}}>
                  <div className={`w-16 h-16 rounded-2xl ${gradients[i]} flex items-center justify-center mx-auto mb-5
                    group-hover:scale-125 group-hover:-translate-y-2 transition-all duration-300 shadow-lg group-hover:shadow-xl`}>
                    <Icon className="w-8 h-8" />
                  </div>
                  <h3 className="text-lg font-bold text-stone-900 mb-2 group-hover:text-primary transition-colors">{f.title}</h3>
                  <p className="text-stone-500 text-sm leading-relaxed">{f.desc}</p>
                </div>
              );
            })}
          </div>
        </div>
      </section>

      {/* ═══════════════ FEATURED — Premium Plan marketing ═══════════════ */}
      {featured.length > 0 && (
        <section className="relative py-24 md:py-32 overflow-hidden" ref={featuredSecRef}
          style={{ background: 'linear-gradient(165deg, #0a2b18 0%, #051a0e 40%, #0d3320 100%)' }}>
          {/* Premium decorative elements */}
          <div className="absolute top-0 inset-x-0 h-px bg-gradient-to-r from-transparent via-gold/30 to-transparent" />
          <div className="absolute top-20 left-1/4 w-72 h-72 bg-gold/[0.04] rounded-full blur-3xl" />
          <div className="absolute bottom-20 right-1/4 w-96 h-96 bg-primary/[0.06] rounded-full blur-3xl" />
          <div className="absolute top-40 right-10 w-32 h-32 border border-gold/10 rounded-full" />
          <div className="absolute bottom-40 left-10 w-24 h-24 border border-gold/5 rounded-full" />
          <div className="absolute top-8 left-8 w-16 h-0.5 bg-gradient-to-r from-gold/40 to-transparent" />
          <div className="absolute top-8 left-8 w-0.5 h-16 bg-gradient-to-b from-gold/40 to-transparent" />

          <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
            {/* Section header */}
            <div className="text-center mb-14 reveal">
              <span className="badge-gold mb-3 inline-flex items-center gap-1.5 shadow-lg shadow-gold/20">
                <Sparkles className="w-3.5 h-3.5" /> {t('property.featured')}
              </span>
              <h2 className="text-3xl md:text-4xl lg:text-5xl font-bold text-white mb-4">
                {t('home.featuredTitle')}
              </h2>
              <p className="text-base md:text-lg text-white/60 max-w-2xl mx-auto font-medium">
                {t('home.featuredSubtitle')}
              </p>
              <div className="inline-flex items-center gap-2 mt-5 px-4 py-1.5 rounded-full bg-gold/10 border border-gold/20">
                <Award className="w-4 h-4 text-gold" />
                <span className="text-xs font-bold text-gold tracking-wider uppercase">{t('home.featuredBadge')}</span>
              </div>
            </div>

            {/* 3 featured cards — horizontal row, rich details */}
            <div className="flex flex-col lg:flex-row gap-6 lg:gap-8">
              {featured.slice(0, 3).map((p, i) => {
                const title = isAr ? p.title_ar : p.title_en;
                const loc = [p.governorate?.name, p.area?.name].filter(Boolean).join('، ');
                const price = Number(p.price).toLocaleString();
                const isRent = p.purpose === 'rent';
                const purposeLabel = isRent ? t('property.purpose.rent') : t('property.purpose.sale');
                return (
                  <Link key={p.id} to={`/properties/${p.slug}`}
                    className="group/card relative flex-1 bg-white/5 backdrop-blur-sm rounded-2xl overflow-hidden border border-white/10 hover:border-gold/30 transition-all duration-500 hover:shadow-2xl hover:shadow-gold/5 hover:-translate-y-1 reveal"
                    style={{ transitionDelay: `${i * 0.15}s` }}>
                    <div className="absolute top-0 inset-x-0 h-0.5 bg-gradient-to-r from-transparent via-gold/60 to-transparent scale-x-0 group-hover/card:scale-x-100 transition-transform duration-500 z-10" />
                    {/* Image */}
                    <div className="relative aspect-[16/9] overflow-hidden">
                      {p.cover_image?.path ? (
                        <img src={p.cover_image.path} alt={title}
                          className="w-full h-full object-cover group-hover/card:scale-105 transition-transform duration-700" />
                      ) : (
                        <div className="w-full h-full flex items-center justify-center bg-primary/20">
                          <HomeIcon className="w-12 h-12 text-white/30" />
                        </div>
                      )}
                      <div className="absolute inset-0 bg-gradient-to-t from-[#051a0e] via-[#051a0e]/30 to-transparent" />
                      {/* Gold featured badge */}
                      <div className="absolute top-4 ltr:left-4 rtl:right-4">
                        <span className="bg-gold text-white text-xs font-bold px-3 py-1.5 rounded-lg shadow-lg shadow-gold/20 flex items-center gap-1.5">
                          <Sparkles className="w-3.5 h-3.5" /> {t('property.featured')}
                        </span>
                      </div>
                      {/* Purpose badge */}
                      <div className="absolute top-4 ltr:right-4 rtl:left-4">
                        <span className="bg-white/15 backdrop-blur-sm text-white text-[11px] font-bold px-2.5 py-1 rounded-lg border border-white/20">
                          {purposeLabel}
                        </span>
                      </div>
                      {/* Price */}
                      <div className="absolute bottom-4 ltr:left-4 rtl:right-4">
                        <div className="flex flex-col">
                          <span className="text-2xl md:text-3xl font-bold text-gold drop-shadow-lg">
                            ${price}
                          </span>
                          {isRent && <span className="text-white/60 text-xs font-medium">/{t('property.perMonth')}</span>}
                        </div>
                      </div>
                    </div>
                    {/* Content — richer details */}
                    <div className="p-5 space-y-3">
                      <div>
                        <h3 className="text-white font-bold text-base leading-snug line-clamp-1 group-hover/card:text-gold transition-colors">
                          {title}
                        </h3>
                        <div className="flex items-center gap-1.5 text-white/40 text-xs mt-1">
                          <MapPin className="w-3 h-3 shrink-0" />
                          <span className="truncate">{loc}</span>
                        </div>
                      </div>
                      {/* Details row */}
                      <div className="grid grid-cols-3 gap-2 py-2.5 border-t border-b border-white/10">
                        <div className="text-center">
                          <div className="text-gold/80 text-xs font-bold">{p.area_sqm}</div>
                          <div className="text-white/35 text-[10px]">{isAr ? 'م²' : 'm²'}</div>
                        </div>
                        <div className="text-center border-x border-white/10">
                          <div className="text-gold/80 text-xs font-bold">{p.bedrooms}</div>
                          <div className="text-white/35 text-[10px]">{isAr ? 'غرف' : 'Beds'}</div>
                        </div>
                        <div className="text-center">
                          <div className="text-gold/80 text-xs font-bold">{p.bathrooms}</div>
                          <div className="text-white/35 text-[10px]">{isAr ? 'حمامات' : 'Baths'}</div>
                        </div>
                      </div>
                      {/* Status + ref */}
                      <div className="flex items-center justify-between">
                        <div className="flex items-center gap-2">
                          {p.agency?.logo_path ? (
                            <img src={p.agency.logo_path} alt={p.agency.name}
                              className="w-5 h-5 rounded-full object-cover ring-1 ring-white/10" />
                          ) : (
                            <Building2 className="w-4 h-4 text-white/30" />
                          )}
                          <span className="text-white/40 text-[11px] font-medium truncate max-w-[120px]">
                            {p.agency?.name ?? ''}
                          </span>
                        </div>
                        <div className="text-white/25 text-[10px] font-mono">
                          #{p.ref_code}
                        </div>
                      </div>
                    </div>
                  </Link>
                );
              })}
            </div>
          </div>
        </section>
      )}
      {/* ═══════════════ AGENCIES ═══════════════ */}
      {agencies.length > 0 && (
        <section className="py-24 bg-white relative section-divider-top section-divider-bottom" ref={agenciesRef}>
          <Blobs colors={['rgba(26,107,60,0.03)', 'rgba(201,168,76,0.03)']} />
          <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
            <div className="text-center mb-14 reveal">
              <span className="badge-primary mb-4 inline-block">{t('home.agenciesTitle')}</span>
              <h2 className="section-title">{t('home.agenciesTitle')}</h2>
              <p className="section-subtitle mx-auto">{t('home.agenciesSub')}</p>
            </div>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
              {agencies.slice(0, 6).map((a, i) => (
                <Link key={a.id} to={`/agencies/${a.slug}`} className="card-3d p-6 flex items-center gap-4 group reveal"
                  style={{ transitionDelay: `${i * 0.1}s` }}>
                  <div className="w-16 h-16 rounded-2xl bg-primary/5 flex items-center justify-center shrink-0 overflow-hidden
                    group-hover:scale-110 group-hover:-rotate-3 transition-all duration-300">
                    {a.logo_path
                      ? <img src={a.logo_path} alt={a.name} className="w-full h-full object-cover"
                          onError={(e) => { (e.target as HTMLImageElement).style.display = 'none'; }} />
                      : <Building2 className="w-8 h-8 text-primary" />
                    }
                  </div>
                  <div className="min-w-0">
                    <div className="font-bold text-stone-900 truncate group-hover:text-primary transition-colors">{a.name}</div>
                    <div className="text-xs text-stone-500 mt-1 font-medium">
                      {a.properties_count} {t('home.stats.properties')} · {a.agents_count} {t('home.stats.agents')}
                    </div>
                    <span className="flex items-center gap-1 mt-1 text-xs text-primary font-medium">
                      {isAr ? 'عرض الملف' : 'View Profile'}
                    </span>
                  </div>
                </Link>
              ))}
            </div>
          </div>
        </section>
      )}

      {/* ═══════════════ LATEST PROPERTIES — 3×4 grid ═══════════════ */}
      {latest.length > 0 && (
        <section ref={latestRef} className="py-24 md:py-28 bg-white relative overflow-hidden">
          <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
            {/* Section header */}
            <div className="text-center mb-14 reveal">
              <span className="badge-primary mb-4 inline-block">{t('home.latestBadge')}</span>
              <h2 className="section-title">{t('home.latestTitle')}</h2>
              <p className="section-subtitle mx-auto">{t('home.latestSubtitle')}</p>
              <div className="flex items-center justify-center gap-2 mt-5">
                <span className="w-12 h-0.5 rounded-full bg-gradient-to-r from-transparent via-primary/20 to-transparent" />
                <span className="w-2 h-2 rounded-full bg-primary/30" />
                <span className="w-12 h-0.5 rounded-full bg-gradient-to-r from-transparent via-primary/20 to-transparent" />
              </div>
            </div>
            {/* 3×4 grid — spacious premium cards */}
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 md:gap-8">
              {latest.slice(0, 12).map((p, i) => (
                <Link key={p.id} to={`/properties/${p.slug}`}
                  className="group/card bg-white rounded-xl overflow-hidden border border-beige-dark/20 shadow-md hover:border-primary/20 transition-all duration-300 hover:shadow-xl hover:shadow-primary/5 hover:-translate-y-1 reveal"
                  style={{ transitionDelay: `${i * 0.04}s` }}>
                  {/* Image — bigger aspect */}
                  <div className="relative aspect-[16/11] overflow-hidden bg-beige">
                    {p.cover_image?.path ? (
                      <img src={p.cover_image.path} alt={isAr ? p.title_ar : p.title_en}
                        className="w-full h-full object-cover group-hover/card:scale-105 transition-transform duration-500"
                        loading="lazy" />
                    ) : (
                      <div className="w-full h-full flex items-center justify-center text-stone-300">
                        <HomeIcon className="w-12 h-12" />
                      </div>
                    )}
                    {/* Overlay badges */}
                    <div className="absolute top-3 ltr:left-3 rtl:right-3 flex flex-col gap-1.5">
                      <span className={`text-white text-xs font-bold px-2.5 py-1 rounded-md shadow-md ${
                        p.purpose === 'rent' ? 'bg-blue-500' : 'bg-emerald-500'
                      }`}>
                        {p.purpose === 'rent' ? t('property.purpose.rent') : t('property.purpose.sale')}
                      </span>
                    </div>
                    {/* Price overlay on image */}
                    <div className="absolute bottom-3 ltr:right-3 rtl:left-3">
                      <span className="inline-block bg-white/90 backdrop-blur-sm text-primary font-bold text-sm px-3 py-1 rounded-lg shadow-sm">
                        ${Number(p.price).toLocaleString()}
                        {p.purpose === 'rent' && <span className="text-stone-400 text-[10px]">/{t('property.perMonth')}</span>}
                      </span>
                    </div>
                  </div>
                  {/* Content — bigger padding */}
                  <div className="p-4 md:p-5">
                    <h3 className="text-stone-800 font-bold text-base leading-snug line-clamp-1 group-hover/card:text-primary transition-colors">
                      {isAr ? p.title_ar : p.title_en}
                    </h3>
                    <div className="flex items-center gap-1.5 text-stone-400 text-xs mt-1.5 mb-3">
                      <MapPin className="w-3.5 h-3.5 shrink-0" />
                      <span className="truncate">{p.governorate?.name ?? ''}</span>
                    </div>
                    {/* Stats row */}
                    <div className="flex items-center gap-4 pt-3 border-t border-beige-dark/20 text-stone-500 text-xs">
                      <span className="flex items-center gap-1.5">
                        <Maximize2 className="w-3.5 h-3.5" />
                        <span dir="ltr">{p.area_sqm} م²</span>
                      </span>
                      {p.bedrooms > 0 && (
                        <span className="flex items-center gap-1.5">
                          <Bed className="w-3.5 h-3.5" /> {p.bedrooms}
                        </span>
                      )}
                      {p.bathrooms > 0 && (
                        <span className="flex items-center gap-1.5">
                          <Bath className="w-3.5 h-3.5" /> {p.bathrooms}
                        </span>
                      )}
                    </div>
                    {/* Agency */}
                    {p.agency && (
                      <div className="flex items-center gap-2 mt-3 pt-3 border-t border-beige-dark/10">
                        {p.agency.logo_path ? (
                          <img src={p.agency.logo_path} alt={p.agency.name}
                            className="w-5 h-5 rounded-full object-cover ring-2 ring-white" />
                        ) : (
                          <Building2 className="w-4 h-4 text-stone-300" />
                        )}
                        <span className="text-stone-400 text-xs truncate">{p.agency.name}</span>
                      </div>
                    )}
                  </div>
                </Link>
              ))}
            </div>
          </div>
        </section>
      )}

      {/* ═══════════════ HOT DEALS ═══════════════ */}
      {hotDeals.length > 0 && (
        <section className="py-24 md:py-32 bg-white relative section-divider-top section-divider-bottom" ref={hotRef}>
          {/* Pattern overlay */}
          <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_top,rgba(26,107,60,0.03)_0%,transparent_70%)]" />
          <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
            <div className="flex items-end justify-between mb-14 reveal">
              <div>
                <span className="badge-red mb-3 inline-flex items-center gap-1.5">
                  <TrendingUp className="w-3.5 h-3.5" /> {t('property.hotDeal')}
                </span>
                <h2 className="section-title">{t('home.hotDealsTitle')}</h2>
                <p className="section-subtitle">{t('home.hotDealsSubtitle')}</p>
              </div>
              <Link to="/properties?sort=price_asc"
                className="hidden md:flex items-center gap-2 px-5 py-2.5 rounded-xl bg-primary/5 text-primary font-medium text-sm hover:bg-primary hover:text-white transition-all duration-300 group">
                {isAr ? 'عرض الكل' : 'View All'}
                <ArrowLeft className="w-4 h-4 lucide-rtl group-hover:-translate-x-1 transition-transform" />
              </Link>
            </div>
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              {hotDeals.slice(0, 6).map((p, i) => (
                <div key={p.id} className="reveal group/card"
                  style={{ transitionDelay: `${i * 0.1}s` }}>
                  <div className="relative">
                    {/* Green dot indicator */}
                    <div className="absolute -top-1 -right-1 w-3 h-3 z-10">
                      <span className="absolute inset-0 rounded-full bg-emerald-400 animate-ping opacity-75" />
                      <span className="absolute inset-0 rounded-full bg-emerald-400" />
                    </div>
                    <PropertyCard property={p} />
                  </div>
                </div>
              ))}
            </div>
            {/* Mobile view all */}
            <div className="text-center mt-10 md:hidden reveal">
              <Link to="/properties?sort=price_asc"
                className="inline-flex items-center gap-2 px-6 py-3 rounded-xl border-2 border-primary/15 text-primary font-semibold text-sm hover:bg-primary hover:text-white transition-all">
                {isAr ? 'عرض الكل' : 'View All'}
                <ArrowLeft className="w-4 h-4 lucide-rtl" />
              </Link>
            </div>
          </div>
        </section>
      )}

      {/* ═══════════════ ABOUT ═══════════════ */}
      <section className="py-24 bg-white relative section-divider-top" ref={aboutRef}>
        <FloatingShapes />
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
          <div className="grid lg:grid-cols-2 gap-16 items-center perspective-1500">
            {/* Left — 3D image grid */}
            <div className="reveal">
              <div className="grid grid-cols-2 gap-4" style={{
                transform: `perspective(1000px) rotateY(${r.x * -3}deg)`,
              }}>
                <div className="space-y-4">
                  <div className="rounded-2xl overflow-hidden shadow-xl tilt-card">
                    <img src="https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=400&q=80" alt="Modern"
                      className="w-full h-44 object-cover hover:scale-110 transition-transform duration-500" loading="lazy" />
                  </div>
                  <div className="rounded-2xl overflow-hidden shadow-xl -translate-y-4 tilt-card">
                    <img src="https://images.unsplash.com/photo-1600573472550-8090b5e0745e?w=400&q=80" alt="Interior"
                      className="w-full h-44 object-cover hover:scale-110 transition-transform duration-500" loading="lazy" />
                  </div>
                </div>
                <div className="space-y-4 translate-y-6">
                  <div className="rounded-2xl overflow-hidden shadow-xl tilt-card">
                    <img src="https://images.unsplash.com/photo-1600047509807-ba8f99d2cdde?w=400&q=80" alt="Exterior"
                      className="w-full h-44 object-cover hover:scale-110 transition-transform duration-500" loading="lazy" />
                  </div>
                  <div className="rounded-2xl overflow-hidden shadow-xl tilt-card">
                    <img src="https://images.unsplash.com/photo-1600585154526-990dced4db0d?w=400&q=80" alt="Luxury"
                      className="w-full h-44 object-cover hover:scale-110 transition-transform duration-500" loading="lazy" />
                  </div>
                </div>
              </div>
              {/* Floating badge */}
              <div className="absolute -bottom-4 left-4 bg-primary text-white p-5 rounded-2xl shadow-2xl animate-float">
                <Star className="w-6 h-6 text-gold mb-1" />
                <div className="font-bold text-xl">{stats.total_properties || 0}+</div>
                <div className="text-xs text-primary-light">{t('home.stats.properties')}</div>
              </div>
            </div>

            {/* Right */}
            <div className="reveal reveal-delay-2">
              <span className="badge-gold mb-4 inline-block">{t('home.aboutTitle')}</span>
              <h2 className="section-title text-primary font-bold mb-6">{t('home.aboutTitle')}</h2>
              <p className="text-stone-600 leading-relaxed mb-6 text-lg">{t('home.aboutDesc')}</p>
              <ul className="space-y-4 mb-8">
                {[1, 2, 3].map((i) => (
                  <li key={i} className="flex items-start gap-3 group">
                    <div className="w-7 h-7 rounded-lg bg-primary/5 flex items-center justify-center shrink-0 group-hover:bg-primary/10 group-hover:scale-110 transition-all">
                      <CheckCircle className="w-4 h-4 text-primary" />
                    </div>
                    <span className="text-stone-700">{t(`home.aboutFeature${i}`)}</span>
                  </li>
                ))}
              </ul>
              <Link to="/properties" className="btn-primary inline-flex items-center gap-2 shadow-lg shadow-primary/20">
                {t('home.ctaUserBtn')}
                <ArrowLeft className="w-4 h-4 lucide-rtl" />
              </Link>
            </div>
          </div>
        </div>
      </section>

      {/* ═══════════════ TESTIMONIALS ═══════════════ */}
      {testimonials.length > 0 && (
        <section className="py-24 relative overflow-hidden" ref={testiRef}>
          {/* Animated background */}
          <div className="absolute inset-0 bg-beige/50 pattern-grid" />
          <Blobs colors={['rgba(201,168,76,0.04)', 'rgba(26,107,60,0.03)']} />

          <div className="max-w-4xl mx-auto px-4 text-center relative">
            <div className="reveal">
              <span className="badge-gold mb-4 inline-block">{t('home.testimonialsTitle')}</span>
              <h2 className="section-title">{t('home.testimonialsTitle')}</h2>
              <p className="section-subtitle mx-auto mb-16">{t('home.testimonialsSubtitle')}</p>
            </div>

            <div className="relative min-h-[280px] perspective-1000">
              {testimonials.map((tm, i) => (
                <div key={tm.id}
                  className={`absolute inset-0 transition-all duration-700 tilt-card ${
                    i === testiIdx ? 'opacity-100 [transform:translateZ(0)_rotateX(0deg)]' : 'opacity-0 [transform:translateZ(-50px)_rotateX(5deg)] pointer-events-none'
                  }`}>
                  <div className="flex justify-center gap-1 mb-6">
                    {[1,2,3,4,5].map((s) => (
                      <Star key={s} className={`w-6 h-6 ${s <= tm.rating ? 'text-gold fill-gold' : 'text-stone-300'} transition-all duration-300 ${i === testiIdx ? 'scale-100' : 'scale-50'}`} />
                    ))}
                  </div>
                  <blockquote className="text-xl md:text-2xl text-stone-700 leading-relaxed max-w-2xl mx-auto mb-8 font-medium"
                    style={{ fontStyle: 'italic' }}>
                    "{isAr ? tm.quote_ar : tm.quote_en}"
                  </blockquote>
                  <div className="flex items-center justify-center gap-4">
                    <div className="w-14 h-14 rounded-full overflow-hidden ring-2 ring-gold/20 ring-offset-2">
                      {tm.avatar_path
                        ? <img src={tm.avatar_path} alt={tm.name} className="w-full h-full object-cover" />
                        : <div className="w-full h-full bg-primary/10 flex items-center justify-center text-primary font-bold text-xl">{(tm.name || '?').charAt(0)}</div>
                      }
                    </div>
                    <div className="text-right">
                      <div className="font-bold text-stone-900 text-lg">{tm.name}</div>
                      <div className="text-sm text-stone-500">{isAr ? tm.role_ar : tm.role_en}</div>
                    </div>
                  </div>
                </div>
              ))}
            </div>
            <SwiperDots total={testimonials.length} active={testiIdx} onChange={setTestiIdx} />
          </div>
        </section>
      )}

      {/* ═══════════════ CITIES ═══════════════ */}
      <section className="py-24 bg-white section-divider-top" ref={citiesRef}>
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-14 reveal">
            <h2 className="section-title">استكشف المدن</h2>
            <p className="section-subtitle mx-auto">اكتشف العقارات المتاحة في أهم المدن السورية</p>
          </div>
          <div className="grid grid-cols-2 md:grid-cols-4 gap-4 perspective-1500">
            {cities.map((city, i) => (
              <Link key={city.slug} to={`/properties?q=${encodeURIComponent(city.slug)}`}
                className="group relative rounded-2xl overflow-hidden h-64 shadow-lg reveal tilt-card"
                style={{ transitionDelay: `${i * 0.1}s` }}>
                <img src={city.img} alt={city.name}
                  className="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700 ease-out" loading="lazy" />
                <div className={`absolute inset-0 ${city.bg} group-hover:opacity-90 transition-opacity duration-300`} />
                <div className="absolute inset-0 bg-black/10" />
                <div className="absolute bottom-0 left-0 right-0 p-6">
                  <h3 className="text-white font-bold text-xl group-hover:text-gold transition-colors drop-shadow-lg">{city.name}</h3>
                  <p className="text-white/70 text-sm mt-1">{city.count} {t('home.stats.properties')}</p>
                </div>
                {/* 3D overlay on hover */}
                <div className="absolute inset-0 border-2 border-transparent group-hover:border-gold/30 rounded-2xl transition-all duration-300" />
              </Link>
            ))}
          </div>
        </div>
      </section>

      {/* ═══════════════ CTA ═══════════════ */}
      <section className="py-28 relative overflow-hidden bg-hero" ref={ctaRef}>
        <Blobs colors={['rgba(201,168,76,0.08)', 'rgba(255,255,255,0.03)']} />
        <Particles count={6} />
        <div className="absolute inset-0" style={{
          backgroundImage: `url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23c9a84c' fill-opacity='0.04'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E")`,
        }} />

        <div className="max-w-4xl mx-auto px-4 text-center relative" style={{ zIndex: 10 }}>
          <div className="glass-3d rounded-3xl p-10 md:p-14 border border-white/10
            transition-all duration-300 hover:shadow-[0_20px_80px_rgba(0,0,0,0.08)]"
            style={{ transform: `perspective(1000px) rotateX(${r.y * -1}deg)` }}>
            <Building2 className="w-14 h-14 text-gold mx-auto mb-5 animate-float" />
            <h2 className="text-3xl md:text-5xl font-bold text-white mb-3" style={{ textShadow: '0 2px 20px rgba(0,0,0,0.1)' }}>
              {t('home.ctaTitle')}
            </h2>
            <p className="text-white/70 text-lg mb-10 max-w-xl mx-auto">{t('home.ctaSubtitle')}</p>
            <div className="flex flex-wrap justify-center gap-4">
              <Link to="/register" className="btn-gold text-lg !px-10 !py-4 shadow-xl shadow-gold/20
                hover:shadow-2xl hover:shadow-gold/30 hover:-translate-y-0.5 transition-all duration-300">
                {t('home.ctaBtn')}
              </Link>
              <Link to="/properties"
                className="text-white/80 hover:text-white border-2 border-white/20 hover:border-white/40 rounded-xl px-8 py-4 text-lg font-medium transition-all hover:-translate-y-0.5 backdrop-blur-sm">
                {t('home.ctaUserBtn')}
              </Link>
            </div>
            <div className="flex justify-center gap-8 mt-10 text-white/50 text-sm flex-wrap">
              <span className="flex items-center gap-1.5"><CheckCircle className="w-4 h-4 text-gold" /> {t('home.stats.agencies')}</span>
              <span className="flex items-center gap-1.5"><CheckCircle className="w-4 h-4 text-gold" /> 30 {t('home.stats.properties')}</span>
              <span className="flex items-center gap-1.5"><CheckCircle className="w-4 h-4 text-gold" /> {features[2]?.title}</span>
            </div>
          </div>
        </div>
      </section>

      <Footer />
    </div>
  );
}
