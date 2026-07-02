import { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { useParams, Link, useNavigate } from 'react-router-dom';
import { Building2, MessageSquareText, ChevronLeft, Loader2 } from 'lucide-react';
import Navbar from '../components/Navbar';
import Footer from '../components/Footer';
import PropertyCard from '../components/PropertyCard';
import { useAuth } from '../auth/AuthContext';
import { fetchAgencyDetail, type AgencyDetailData } from '../api/properties';

export default function AgencyDetail() {
  const { t, i18n } = useTranslation();
  const isAr = i18n.language === 'ar';
  const { slug } = useParams();
  const navigate = useNavigate();
  const { user } = useAuth();
  const [agency, setAgency] = useState<AgencyDetailData | null>(null);
  const [loading, setLoading] = useState(true);
  const [heroLoaded, setHeroLoaded] = useState(false);
  const [logoError, setLogoError] = useState(false);

  useEffect(() => {
    if (!slug) return;
    setLoading(true);
    fetchAgencyDetail(slug).then(setAgency).finally(() => setLoading(false));
  }, [slug]);

  // Hero background: agency cover first, fallback to first property cover
  const heroImage = agency?.cover_path || agency?.properties?.[0]?.cover_image?.path || null;

  const handleChatClick = () => {
    if (!user) { navigate('/login'); return; }
    if (agency?.id) navigate(`/user/chat?agencyId=${agency.id}`);
  };

  if (loading) return (
    <div dir={isAr ? 'rtl' : 'ltr'} className="min-h-screen flex flex-col">
      <Navbar /><div className="flex-1 flex items-center justify-center"><Loader2 className="w-8 h-8 animate-spin text-primary" /></div><Footer />
    </div>
  );

  if (!agency) return (
    <div dir={isAr ? 'rtl' : 'ltr'} className="min-h-screen flex flex-col">
      <Navbar />
      <div className="flex-1 flex items-center justify-center">
        <div className="text-center">
          <h2 className="text-xl font-bold text-stone-900 mb-2">{isAr ? 'الوكالة غير موجودة' : 'Agency not found'}</h2>
          <Link to="/agencies" className="text-primary hover:underline">{isAr ? 'العودة للوكالات' : 'Back to agencies'}</Link>
        </div>
      </div>
      <Footer />
    </div>
  );

  const desc = isAr ? agency.description_ar : agency.description_en;

  return (
    <div dir={isAr ? 'rtl' : 'ltr'} className="min-h-screen flex flex-col">
      <Navbar />
      <div className="flex-1 pt-16">
        {/* ═══ HERO — cover image + overlay ═══ */}
        <section className="relative h-[45vh] md:h-[55vh] overflow-hidden">
          {heroImage ? (
            <>
              <img src={heroImage} alt=""
                className={`w-full h-full object-cover transition-opacity duration-700 ${heroLoaded ? 'opacity-100' : 'opacity-0'}`}
                onLoad={() => setHeroLoaded(true)}
                onError={() => setHeroLoaded(true)} />
              <div className="absolute inset-0 bg-gradient-to-t from-black/80 via-black/40 to-black/30" />
            </>
          ) : (
            <div className="w-full h-full bg-gradient-to-br from-hero via-primary-dark to-primary" />
          )}

          <div className="absolute -top-20 -right-20 w-72 h-72 bg-gold/5 rounded-full blur-3xl" />
          <div className="absolute -bottom-20 -left-20 w-96 h-96 bg-primary/10 rounded-full blur-3xl" />

          <div className="absolute inset-0 flex items-end pb-8 md:pb-12">
            <div className="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
              <div className="flex items-center gap-2 text-sm text-white/40 mb-4">
                <Link to="/" className="hover:text-white/80 transition-colors">{t('nav.home')}</Link>
                <ChevronLeft className="w-3 h-3 lucide-rtl" />
                <Link to="/agencies" className="hover:text-white/80 transition-colors">{isAr ? 'الوكالات' : 'Agencies'}</Link>
                <ChevronLeft className="w-3 h-3 lucide-rtl" />
                <span className="text-white/60 font-medium truncate">{agency.name}</span>
              </div>

              <div className="flex items-end gap-5 md:gap-7">
                <div className="w-20 h-20 md:w-28 md:h-28 rounded-2xl bg-white/10 backdrop-blur-md flex items-center justify-center text-white text-2xl md:text-3xl font-bold shrink-0 ring-2 ring-white/20 overflow-hidden shadow-xl">
                  {agency.logo_path && !logoError
                    ? <img src={agency.logo_path} alt={agency.name} className="w-full h-full object-cover" onError={() => setLogoError(true)} />
                    : (agency.name || 'A').charAt(0)
                  }
                </div>

                <div className="flex-1 min-w-0 pb-1">
                  <h1 className="text-2xl md:text-4xl font-bold text-white drop-shadow-sm">{agency.name}</h1>
                  <div className="flex flex-wrap items-center gap-x-5 gap-y-2 mt-2 text-sm text-white/60">
                    <span className="flex items-center gap-1.5">
                      <Building2 className="w-4 h-4" />
                      {agency.properties_count} {isAr ? 'عقار' : 'properties'}
                    </span>
                    <span className="w-1 h-1 rounded-full bg-white/20" />
                    <span className="flex items-center gap-1.5">
                      {agency.agents_count} {isAr ? 'وكيل' : 'agents'}
                    </span>
                    {agency.address && (
                      <>
                        <span className="w-1 h-1 rounded-full bg-white/20" />
                        <span>{agency.address}</span>
                      </>
                    )}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>

        {/* ═══ CONTENT ═══ */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 md:py-14">
          {/* Description */}
          {desc && (
            <div className="max-w-3xl mb-10">
              <span className="text-xs font-semibold uppercase tracking-[0.2em] text-gold">{isAr ? 'عن الوكالة' : 'About'}</span>
              <span className="mx-3 inline-block w-8 h-px bg-gold/30 align-middle" />
              <p className="text-stone-600 leading-relaxed mt-3 text-base md:text-lg font-light">{desc}</p>
            </div>
          )}

          {/* CTA + Chat area */}
          <div className="mb-10">
            <div className="hidden md:flex items-center justify-between mb-4">
              <div>
                <h2 className="text-xl font-bold text-stone-900">{isAr ? 'العقارات المتاحة' : 'Available Properties'}</h2>
                <p className="text-sm text-stone-400 mt-1">{isAr ? `عقارات معروضة من ${agency.name}` : `Properties listed by ${agency.name}`}</p>
              </div>
              <button onClick={handleChatClick}
                className="flex items-center gap-2 px-6 py-3 rounded-xl bg-primary text-white font-medium text-sm hover:bg-primary-dark transition-all shadow-lg shadow-primary/20">
                <MessageSquareText className="w-4 h-4" />
                {isAr ? 'تواصل مع الوكالة' : 'Chat'}
              </button>
            </div>

            <div className="md:hidden mb-4">
              <h2 className="text-lg font-bold text-stone-900 mb-1">{isAr ? 'العقارات المتاحة' : 'Available Properties'}</h2>
              <button onClick={handleChatClick}
                className="w-full flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-primary text-white font-medium text-sm hover:bg-primary-dark transition-all mt-3 shadow-lg shadow-primary/20">
                <MessageSquareText className="w-4 h-4" />
                {isAr ? 'تواصل مع الوكالة' : 'Chat'}
              </button>
            </div>


          </div>

          {/* Properties grid */}
          {agency.properties && agency.properties.length > 0 ? (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              {agency.properties.map((p: any) => <PropertyCard key={p.id} property={p} />)}
            </div>
          ) : (
            <div className="card-3d p-10 text-center">
              <div className="w-16 h-16 rounded-full bg-stone-50 flex items-center justify-center mx-auto mb-4">
                <Building2 className="w-7 h-7 text-stone-300" />
              </div>
              <p className="text-stone-500">{isAr ? 'لا توجد عقارات متاحة حالياً' : 'No properties available at the moment'}</p>
            </div>
          )}
        </div>
      </div>
      <Footer />
    </div>
  );
}
