import { useState, useEffect, useRef } from 'react';
import { useTranslation } from 'react-i18next';
import { useParams, Link, useNavigate } from 'react-router-dom';
import {
  MapPin, Bed, Bath, Maximize2, Heart, Share2, Check,
  ArrowLeft, Sparkles, Building2, Calendar, Send,
  MessageCircle, ChevronLeft, ChevronRight, X,
  Star, Home, ExternalLink, Shield,
  Ruler, Hash, TrendingUp,
} from 'lucide-react';
import Navbar from '../components/Navbar';
import Footer from '../components/Footer';
import PropertyCard from '../components/PropertyCard';
import { fetchProperty, fetchProperties } from '../api/properties';
import { useAuth } from '../auth/AuthContext';
import type { PropertyDetail as PropertyDetailType, PropertyCard as PropertyCardType } from '../api/client';
import useSEOMeta from '../hooks/useSEOMeta';
import { startConversation } from '../api/user';

// ─── Scroll reveal ───
function useReveal() {
  const ref = useRef<HTMLDivElement | null>(null);
  useEffect(() => {
    const el = ref.current;
    if (!el) return;
    const obs = new IntersectionObserver(
      ([entry]) => {
        if (entry.isIntersecting) {
          el.querySelectorAll('.reveal').forEach(c => c.classList.add('visible'));
          if (el.classList.contains('reveal')) el.classList.add('visible');
          obs.unobserve(el);
        }
      },
      { threshold: 0.08 }
    );
    obs.observe(el);
    return () => obs.disconnect();
  }, []);
  return ref;
}

// ─── Quick Inquiry Modal ───
function QuickInquiryModal({ property, agencyId, onClose }: {
  property: PropertyDetailType;
  agencyId: number | null;
  onClose: () => void;
}) {
  const { i18n } = useTranslation();
  const isAr = i18n.language === 'ar';
  const L = (ar: string, en: string) => isAr ? ar : en;
  const { user } = useAuth();
  const modalPrice = Number(property.price).toLocaleString();
  const modalIsRent = property.purpose === 'rent';
  const navigate = useNavigate();
  const title = isAr ? property.title_ar : property.title_en;
  const [message, setMessage] = useState(
    isAr
      ? `مرحباً، أنا مهتم بالعقار "${title}". أود معرفة المزيد من المعلومات.`
      : `Hello, I'm interested in "${title}". I'd like to know more.`
  );
  const [sending, setSending] = useState(false);
  const [sent, setSent] = useState(false);

  const handleSubmit = async () => {
    if (!user) {
      const params = new URLSearchParams();
      params.set('redirect', window.location.pathname);
      navigate(`/login?${params.toString()}`);
      return;
    }
    if (!message.trim()) return;
    setSending(true);
    // If no agencyId, go straight to chat fallback
    if (!agencyId) {
      const params = new URLSearchParams();
      if (property?.id) params.set('propertyId', String(property.id));
      navigate(`/user/chat?${params.toString()}`);
      return;
    }
    try {
      await startConversation({
        agency_id: agencyId,
        property_id: property.id,
        message: message.trim(),
      });
      setSent(true);
      setTimeout(() => onClose(), 1800);
    } catch {
      // Fallback: navigate to chat page
      const params = new URLSearchParams();
      params.set('agencyId', String(agencyId));
      if (property?.id) params.set('propertyId', String(property.id));
      navigate(`/user/chat?${params.toString()}`);
    } finally {
      setSending(false);
    }
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4" onClick={onClose}>
      <div className="absolute inset-0 bg-black/40 backdrop-blur-sm animate-fadeIn" />
      <div className="relative bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden animate-slideUp" onClick={e => e.stopPropagation()}>
        {/* Gold gradient top bar */}
        <div className="h-1 bg-gradient-to-r from-gold/80 via-gold to-gold/80" />

        {sent ? (
          <div className="text-center py-12 px-8">
            <div className="w-18 h-18 rounded-full bg-green-50 flex items-center justify-center mx-auto mb-5 shadow-sm">
              <Check className="w-9 h-9 text-green-500" />
            </div>
            <p className="font-bold text-stone-900 text-xl">{L('تم إرسال استفسارك', 'Inquiry Sent')}</p>
            <p className="text-stone-400 text-sm mt-1.5 leading-relaxed">{L('سيتواصل معك الوكيل قريباً', 'The agent will respond shortly')}</p>
          </div>
        ) : (
          <>
            {/* Property preview */}
            <div className="px-6 md:px-8 pt-6 md:pt-7 pb-4 flex items-center gap-3.5 border-b border-stone-100">
              <div className="w-12 h-12 rounded-xl overflow-hidden bg-beige shrink-0 shadow-sm border border-stone-100">
                {property.cover_image?.path ? (
                  <img src={property.cover_image.path} alt="" className="w-full h-full object-cover" />
                ) : (
                  <div className="w-full h-full flex items-center justify-center text-stone-300"><Home className="w-5 h-5" /></div>
                )}
              </div>
              <div className="min-w-0 flex-1">
                <p className="text-xs font-semibold text-stone-900 truncate">{title}</p>
                <p className="text-[11px] text-stone-400">${modalPrice}{modalIsRent ? `/${L('شهر', 'mo')}` : ''}</p>
              </div>
              <button onClick={onClose} className="p-1.5 rounded-lg hover:bg-stone-100 transition-colors shrink-0">
                <X className="w-4 h-4 text-stone-400" />
              </button>
            </div>

            {/* Form */}
            <div className="p-6 md:p-8 pt-5">
              <p className="text-xs text-stone-500 mb-3.5 flex items-center gap-1.5">
                <MessageCircle className="w-3.5 h-3.5 text-primary/50 shrink-0" />
                {L('رسالتك ستصل مباشرة إلى', 'Your message goes directly to')} <span className="font-semibold text-stone-700">{property.agent?.name || ''}</span>
              </p>
              <textarea value={message} onChange={e => setMessage(e.target.value)}
                rows={3}
                className="w-full rounded-xl border border-stone-200 bg-stone-50/50 px-4 py-3.5 text-sm leading-relaxed text-stone-900 placeholder:text-stone-400 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all resize-none"
                placeholder={L('اكتب رسالتك هنا...', 'Write your message here...')} />
              <div className="flex gap-2.5 mt-4">
                <button onClick={onClose}
                  className="flex-1 px-4 py-2.5 border border-stone-200 text-stone-600 text-sm font-medium rounded-xl hover:bg-stone-50 transition-all">
                  {L('إلغاء', 'Cancel')}
                </button>
                <button onClick={handleSubmit} disabled={sending}
                  className="flex-1 px-4 py-2.5 bg-primary text-white text-sm font-bold rounded-xl hover:bg-primary-dark disabled:opacity-50 transition-all flex items-center justify-center gap-2 shadow-sm shadow-primary/20">
                  {sending ? <span className="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" /> : <Send className="w-4 h-4 lucide-rtl" />}
                  {sending ? L('جارٍ الإرسال...', 'Sending...') : L('إرسال', 'Send')}
                </button>
              </div>
            </div>
          </>
        )}
      </div>
    </div>
  );
}

export default function PropertyDetail() {
  const { t, i18n } = useTranslation();
  const isAr = i18n.language === 'ar';
  const L = (ar: string, en: string) => isAr ? ar : en;
  const { slug } = useParams<{ slug: string }>();
  const { user } = useAuth();
  const navigate = useNavigate();

  const handleChatRedirect = () => {
    if (!user) { navigate('/login'); return; }
    const params = new URLSearchParams();
    if (property?.agent?.agency?.id) params.set('agencyId', String(property.agent.agency.id));
    if (property?.id) params.set('propertyId', String(property.id));
    navigate(`/user/chat?${params.toString()}`);
  };

  const [property, setProperty] = useState<PropertyDetailType | null>(null);
  const [loading, setLoading] = useState(true);
  const [selectedImage, setSelectedImage] = useState(0);
  const [faved, setFaved] = useState(false);
  const [imgError, setImgError] = useState(false);
  const [related, setRelated] = useState<PropertyCardType[]>([]);
  const [lightboxOpen, setLightboxOpen] = useState(false);
  const [lightboxIdx, setLightboxIdx] = useState(0);
  const [copied, setCopied] = useState(false);
  const [inquiryOpen, setInquiryOpen] = useState(false);

  const descRef = useReveal();
  const amenitiesRef = useReveal();
  const detailsRef = useReveal();
  const locationRef = useReveal();
  const relatedRef = useReveal();

  const siteUrl = window.location.origin;
  const seoTitle = property
    ? `${isAr ? property.title_ar : property.title_en} | Syria Homes`
    : 'Syria Homes | ' + (isAr ? 'سوق العقارات' : 'Real Estate Marketplace');
  const seoDesc = property
    ? (isAr ? (property.description_ar || '').slice(0, 160) : (property.description_en || '').slice(0, 160))
    : (isAr ? 'سوق العقارات في سورية' : 'Syria Homes - Real Estate Marketplace in Syria');
  const seoImage = property?.cover_image?.path
    ? (property.cover_image.path.startsWith('http') ? property.cover_image.path : siteUrl + '/' + property.cover_image.path.replace(/^\/+/, ''))
    : null;
  const seoUrl = property ? `${siteUrl}/${property.slug}` : siteUrl;

  const seoSchema = property ? {
    '@context': 'https://schema.org',
    '@type': 'Product',
    name: isAr ? property.title_ar : property.title_en,
    description: isAr ? (property.description_ar || '').slice(0, 200) : (property.description_en || '').slice(0, 200),
    image: seoImage,
    url: seoUrl,
    offers: {
      '@type': 'Offer',
      price: property.price,
      priceCurrency: property.currency || 'USD',
      availability: property.status === 'available' ? 'https://schema.org/InStock' : 'https://schema.org/SoldOut',
    },
    ...(property.latitude && property.longitude ? {
      location: {
        '@type': 'Place',
        name: `${property.governorate?.name}, ${property.area?.name}`,
        latitude: parseFloat(property.latitude),
        longitude: parseFloat(property.longitude),
      },
    } : {}),
  } : null;

  useSEOMeta(property ? {
    title: seoTitle, description: seoDesc, image: seoImage, url: seoUrl,
    type: 'product', schema: seoSchema,
  } : { title: seoTitle, description: seoDesc });

  useEffect(() => {
    if (!slug) return;
    setLoading(true); setImgError(false); setRelated([]); setSelectedImage(0);
    fetchProperty(slug).then(p => {
      setProperty(p);
      const params: Record<string, string> = { sort: 'newest', per_page: '6' };
      if (p.governorate?.slug) params.governorate = p.governorate.slug;
      fetchProperties(params).then(res => setRelated(res.data.filter(r => r.slug !== slug).slice(0, 3))).catch(() => {});
    }).finally(() => setLoading(false));
  }, [slug]);

  useEffect(() => { setImgError(false); }, [selectedImage]);

  const imgCount = property
    ? new Set([...(property.images?.map((i: any) => i.path) || []), ...(property.cover_image?.path ? [property.cover_image.path] : [])]).size
    : 0;

  useEffect(() => {
    if (!lightboxOpen) return;
    const handleKey = (e: KeyboardEvent) => {
      if (e.key === 'Escape') setLightboxOpen(false);
      if (e.key === 'ArrowLeft') setLightboxIdx(i => Math.max(0, i - 1));
      if (e.key === 'ArrowRight') setLightboxIdx(i => Math.min(imgCount - 1, i + 1));
    };
    window.addEventListener('keydown', handleKey);
    document.body.style.overflow = 'hidden';
    return () => { window.removeEventListener('keydown', handleKey); document.body.style.overflow = ''; };
  }, [lightboxOpen, imgCount]);

  const openLightbox = (idx: number) => { setLightboxIdx(idx); setLightboxOpen(true); };
  const shareWhatsApp = () => {
    const url = window.location.href;
    window.open(`https://wa.me/?text=${encodeURIComponent(isAr ? `*${title}*\n${url}` : `*${title}*\n${url}`)}`, '_blank');
  };
  const copyLink = () => {
    navigator.clipboard?.writeText(window.location.href).then(() => { setCopied(true); setTimeout(() => setCopied(false), 2000); }).catch(() => {});
  };
  // ── Loading ──
  if (loading) {
    return (
      <div dir={isAr ? 'rtl' : 'ltr'}>
        <Navbar />
        <div className="pt-16 min-h-screen bg-cream">
          <div className="animate-pulse">
            <div className="h-[60vh] md:h-[75vh] bg-stone-200" />
            <div className="max-w-6xl mx-auto px-6 py-12 space-y-8">
              <div className="h-10 bg-stone-200 rounded w-2/3" />
              <div className="h-5 bg-stone-200 rounded w-1/4" />
              <div className="grid grid-cols-4 gap-6">
                {[1,2,3,4].map(i => <div key={i} className="h-16 bg-stone-200 rounded" />)}
              </div>
              <div className="h-40 bg-stone-200 rounded" />
            </div>
          </div>
        </div>
        <Footer />
      </div>
    );
  }

  if (!property) {
    return (
      <div dir={isAr ? 'rtl' : 'ltr'}>
        <Navbar />
        <div className="pt-16 min-h-screen flex items-center justify-center bg-cream">
          <div className="text-center max-w-md mx-auto p-8">
            <div className="w-20 h-20 rounded-full bg-stone-100 flex items-center justify-center mx-auto mb-6">
              <Home className="w-10 h-10 text-stone-300" />
            </div>
            <p className="text-stone-500 text-lg mb-2">{t('property.noResults')}</p>
            <p className="text-stone-400 text-sm mb-6">{L('قد يكون العقار محذوفاً أو غير متاح', 'Property may be deleted or unavailable')}</p>
            <Link to="/properties" className="btn-primary inline-flex items-center gap-2 shadow-lg shadow-primary/20">
              <ArrowLeft className="w-4 h-4 lucide-rtl" /> {t('nav.properties')}
            </Link>
          </div>
        </div>
        <Footer />
      </div>
    );
  }

  const title = isAr ? property.title_ar : property.title_en;
  const desc = isAr ? property.description_ar : property.description_en;
  const price = Number(property.price).toLocaleString();
  const p = property as any;
  const isRent = property.purpose === 'rent';
  const allImages = [...(property.images?.map((i: any) => i.path) || []), ...(property.cover_image?.path ? [property.cover_image.path] : [])];
  const uniqueImages = [...new Set(allImages)];
  const imgSrc = uniqueImages[selectedImage] || '';
  const daysAgo = p.published_at
    ? Math.floor((Date.now() - new Date(p.published_at).getTime()) / 86400000)
    : Math.floor(Math.random() * 14) + 1;

  return (
    <div dir={isAr ? 'rtl' : 'ltr'} className="min-h-screen bg-cream">
      <Navbar />

      {/* ═══════ QUICK INQUIRY MODAL ═══════ */}
      {inquiryOpen && property?.agent && (
        <QuickInquiryModal
          property={property}
          agencyId={property.agent.agency?.id || null}
          onClose={() => setInquiryOpen(false)} />
      )}

      {/* ═══════ LIGHTBOX ═══════ */}
      {lightboxOpen && (
        <div className="fixed inset-0 z-[60] bg-black/95 flex flex-col" dir="ltr">
          <div className="flex items-center justify-between p-4 z-10">
            <button onClick={() => setLightboxOpen(false)} className="text-white/70 hover:text-white"><X className="w-6 h-6" /></button>
            <span className="text-white/60 text-xs font-medium">{lightboxIdx + 1} / {uniqueImages.length}</span>
            <button onClick={shareWhatsApp} className="text-white/70 hover:text-white p-1"><Share2 className="w-5 h-5" /></button>
          </div>
          <div className="flex-1 flex items-center justify-center relative px-4">
            {lightboxIdx > 0 && (
              <button onClick={() => setLightboxIdx(i => i - 1)}
                className="absolute left-4 top-1/2 -translate-y-1/2 w-11 h-11 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white backdrop-blur-sm">
                <ChevronLeft className="w-5 h-5 lucide-rtl" />
              </button>
            )}
            <img src={uniqueImages[lightboxIdx]} alt="" className="max-h-[85vh] max-w-full object-contain rounded-xl" key={lightboxIdx} />
            {lightboxIdx < uniqueImages.length - 1 && (
              <button onClick={() => setLightboxIdx(i => i + 1)}
                className="absolute right-4 top-1/2 -translate-y-1/2 w-11 h-11 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white backdrop-blur-sm">
                <ChevronRight className="w-5 h-5 lucide-rtl" />
              </button>
            )}
          </div>
          <div className="flex justify-center gap-2 p-4 overflow-x-auto">
            {uniqueImages.map((src, i) => (
              <button key={i} onClick={() => setLightboxIdx(i)}
                className={`w-16 h-12 rounded-lg overflow-hidden border-2 shrink-0 ${i === lightboxIdx ? 'border-white' : 'border-transparent opacity-50 hover:opacity-80'}`}>
                <img src={src} alt="" className="w-full h-full object-cover" />
              </button>
            ))}
          </div>
        </div>
      )}

      {/* ══════════════════════════════════════
          HERO — editorial minimal
          ══════════════════════════════════════ */}
      <section className="relative pt-16">
        <div className="group/hero relative h-[60vh] md:h-[85vh] lg:h-[90vh] overflow-hidden cursor-pointer"
          onClick={() => uniqueImages.length > 0 && openLightbox(selectedImage)}>

          {imgSrc && !imgError ? (
            <img src={imgSrc} alt={title}
              className="w-full h-full object-cover transition-transform duration-[4s] group-hover/hero:scale-105"
              onError={() => setImgError(true)} />
          ) : (
            <div className="w-full h-full flex items-center justify-center bg-stone-800"><Home className="w-36 h-36 text-stone-600" /></div>
          )}

          {/* Prev/Next arrows — desktop only, show on hover */}
          {uniqueImages.length > 1 && (
            <>
              <button onClick={(e) => { e.stopPropagation(); setSelectedImage(i => (i - 1 + uniqueImages.length) % uniqueImages.length); setImgError(false); }}
                className="absolute left-4 md:left-8 top-1/2 -translate-y-1/2 w-10 h-10 md:w-12 md:h-12 rounded-full bg-white/10 backdrop-blur-sm hover:bg-white/25 flex items-center justify-center text-white opacity-0 group-hover/hero:opacity-100 transition-all duration-300 z-20 shadow-lg border border-white/15">
                <ChevronLeft className="w-5 h-5 lucide-rtl" />
              </button>
              <button onClick={(e) => { e.stopPropagation(); setSelectedImage(i => (i + 1) % uniqueImages.length); setImgError(false); }}
                className="absolute right-4 md:right-8 top-1/2 -translate-y-1/2 w-10 h-10 md:w-12 md:h-12 rounded-full bg-white/10 backdrop-blur-sm hover:bg-white/25 flex items-center justify-center text-white opacity-0 group-hover/hero:opacity-100 transition-all duration-300 z-20 shadow-lg border border-white/15">
                <ChevronRight className="w-5 h-5 lucide-rtl" />
              </button>
            </>
          )}

          {/* Subtle bottom gradient — just enough for text */}
          <div className="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent" />
          <div className="absolute inset-0 bg-gradient-to-r from-black/10 to-transparent" />

          {/* Top bar — purpose badge + photo count */}
          <div className="absolute top-4 md:top-6 left-0 right-0 px-4 md:px-8 lg:px-12 flex items-start justify-between z-10">
            <span className={`inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold tracking-wider uppercase shadow-lg ${
              property.purpose === 'sale'
                ? 'bg-white/90 backdrop-blur-sm text-stone-800'
                : 'bg-gold text-white'
            }`}>
              {L(property.purpose === 'sale' ? 'للبيع' : 'للإيجار', property.purpose === 'sale' ? 'For Sale' : 'For Rent')}
              {property.is_featured && <Sparkles className="w-3 h-3" />}
            </span>

            <button onClick={(e) => { e.stopPropagation(); openLightbox(selectedImage); }}
              className="bg-white/15 backdrop-blur-md border border-white/25 text-white text-xs px-3 py-1.5 rounded-lg hover:bg-white/25 transition-all flex items-center gap-1.5 shadow-lg">
              <Maximize2 className="w-3 h-3" />
              {uniqueImages.length} {L('صورة', 'photos')}
            </button>
          </div>

          {/* Bottom overlay — title + meta + price + gallery dots */}
          <div className="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent pt-24 pb-0">
            <div className="px-6 md:px-12 lg:px-16 pb-5 md:pb-7">
              <div className="max-w-4xl">
                {/* Breadcrumb — desktop only */}
                <div className="hidden md:flex items-center gap-2 text-white/40 text-xs mb-4 tracking-wider uppercase">
                  <Link to="/" className="hover:text-white/80 transition-colors">{L('الرئيسية', 'Home')}</Link>
                  <span className="w-4 h-px bg-white/20" />
                  <Link to="/properties" className="hover:text-white/80 transition-colors">{L('العقارات', 'Properties')}</Link>
                  <span className="w-4 h-px bg-white/20" />
                  <span className="text-white/60 truncate max-w-[200px]">{title}</span>
                </div>

                <h1 className="text-2xl md:text-5xl lg:text-7xl font-bold text-white leading-tight tracking-tight drop-shadow-sm">
                  {title}
                </h1>

                <div className="flex flex-wrap items-center gap-x-5 gap-y-1.5 mt-3 md:mt-4 text-white/60 text-sm md:text-base">
                  <span className="flex items-center gap-1.5">
                    <MapPin className="w-4 h-4 text-gold" />
                    {property.governorate?.name}{property.area?.name ? `، ${property.area.name}` : ''}
                  </span>
                  <span className="w-1 h-1 rounded-full bg-white/20" />
                  <span className="flex items-center gap-1.5">
                    <Calendar className="w-3.5 h-3.5" />
                    {L(`منذ ${daysAgo} يوم`, `${daysAgo} day${daysAgo !== 1 ? 's' : ''} ago`)}
                  </span>
                  {p.views_count > 0 && (
                    <>
                      <span className="w-1 h-1 rounded-full bg-white/20" />
                      <span className="flex items-center gap-1.5">
                        <Star className="w-3.5 h-3.5" />
                        {p.views_count} {L('مشاهدة', 'views')}
                      </span>
                    </>
                  )}
                </div>
              </div>

              {/* Price — clean, no box */}
              <div className="absolute bottom-5 md:bottom-7 ltr:right-6 rtl:left-6 md:ltr:right-12 md:rtl:left-12 text-right">
                <div className="text-2xs md:text-xs font-semibold uppercase tracking-widest text-white/40">{L('السعر', 'Price')}</div>
                <div className="text-2xl md:text-5xl lg:text-6xl font-bold text-white drop-shadow-lg leading-none mt-1">${price}</div>
                <div className="text-xs md:text-sm font-medium text-white/50 mt-1">
                  {isRent ? `/${L('شهر', 'month')}` : L('نهائي', 'Final')}
                </div>
              </div>
            </div>

            {/* Gallery — dots + all photos button */}
            {uniqueImages.length > 1 && (
              <div className="flex items-center justify-between px-6 md:px-12 lg:px-16 pb-4 md:pb-5">
                <div className="flex items-center gap-1.5">
                  {uniqueImages.slice(0, 7).map((_, i) => (
                    <button key={i} onClick={(e) => { e.stopPropagation(); setSelectedImage(i); setImgError(false); }}
                      className={`w-1.5 h-1.5 md:w-2 md:h-2 rounded-full transition-all duration-300 ${
                        i === selectedImage
                          ? 'bg-gold w-4 md:w-5'
                          : 'bg-white/40 hover:bg-white/60'
                      }`} />
                  ))}
                  {uniqueImages.length > 7 && (
                    <span className="text-white/30 text-2xs ml-1">+{uniqueImages.length - 7}</span>
                  )}
                </div>
                <button onClick={(e) => { e.stopPropagation(); openLightbox(selectedImage); }}
                  className="flex items-center gap-1.5 text-xs md:text-xs font-medium text-white/60 hover:text-white transition-colors">
                  <Maximize2 className="w-3 h-3" />
                  {L('عرض الكل', 'View all')} ({uniqueImages.length})
                </button>
              </div>
            )}
          </div>
        </div>
      </section>

      {/* ═══════ GALLERY THUMBNAILS — professional strip ═══════ */}
      {uniqueImages.length > 1 && (
        <section className="bg-gradient-to-b from-white to-stone-50/60 border-b border-stone-100 shadow-sm shadow-stone-100/50">
          <div className="max-w-6xl mx-auto px-5 sm:px-8 lg:px-12 py-4 md:py-5">
            {/* Label + count */}
            <div className="flex items-center justify-between mb-3 md:mb-3.5">
              <div className="flex items-center gap-2">
                <span className="text-[10px] font-semibold uppercase tracking-[0.2em] text-stone-400">
                  {L('معرض الصور', 'Gallery')}
                </span>
                <span className="px-1.5 py-0.5 text-[10px] font-medium text-stone-400 bg-stone-100 rounded-md">
                  {selectedImage + 1}/{uniqueImages.length}
                </span>
              </div>
              <button onClick={() => openLightbox(selectedImage)}
                className="text-[11px] font-medium text-primary/60 hover:text-primary transition-colors">
                {L('عرض الكل', 'View All')}
              </button>
            </div>

            {/* Scrollable row with gradient fade edges */}
            <div className="relative">
              {/* Left gradient fade */}
              <div className="absolute left-0 top-0 bottom-2 w-8 bg-gradient-to-r from-white to-transparent z-10 pointer-events-none" />
              {/* Right gradient fade */}
              <div className="absolute right-0 top-0 bottom-2 w-8 bg-gradient-to-l from-white to-transparent z-10 pointer-events-none" />

              <div className="flex items-center gap-2 md:gap-3 overflow-x-auto pb-2 scrollbar-none">
                {uniqueImages.map((src, i) => (
                  <button key={i} onClick={() => { setSelectedImage(i); setImgError(false); }}
                    className={`group/thumb relative w-28 h-20 md:w-36 md:h-24 rounded-xl overflow-hidden shrink-0 transition-all duration-300 ${
                      i === selectedImage
                        ? 'ring-2 ring-gold ring-offset-2 ring-offset-white shadow-lg shadow-gold/15 scale-[1.02]'
                        : 'ring-1 ring-stone-200/70 hover:ring-stone-300 hover:shadow-md'
                    }`}>
                    <img src={src} alt=""
                      className="w-full h-full object-cover transition-all duration-300 group-hover/thumb:scale-110" />

                    {/* Hover overlay */}
                    <div className="absolute inset-0 bg-black/0 group-hover/thumb:bg-black/30 transition-all duration-300 flex items-center justify-center">
                      <span className="text-white text-[10px] font-semibold opacity-0 group-hover/thumb:opacity-100 transition-all duration-300 tracking-wider uppercase bg-white/20 backdrop-blur-sm px-2.5 py-1 rounded-lg">
                        {L('عرض', 'View')}
                      </span>
                    </div>

                    {/* Selected indicator */}
                    {i === selectedImage && (
                      <div className="absolute bottom-1.5 left-1/2 -translate-x-1/2 w-5 h-0.5 rounded-full bg-gold shadow-sm" />
                    )}
                  </button>
                ))}
              </div>
            </div>
          </div>
        </section>
      )}

      {/* ══════════════════════════════════════
          CONTENT AREA — smooth, airy, comfortable
          ══════════════════════════════════════ */}
      <div className="max-w-6xl mx-auto px-5 sm:px-8 lg:px-12 py-10 md:py-14 lg:py-16">

        <div className="flex flex-col lg:flex-row gap-10 lg:gap-12">

          {/* ─── LEFT: Main content ─── */}
          <div className="flex-1 min-w-0 space-y-14 md:space-y-20">

            {/* ✅ Description — elegant typography */}
            <section ref={descRef}>
              <div className="mb-5 md:mb-6">
                <span className="text-[10px] font-semibold uppercase tracking-[0.25em] text-stone-400">{L('الوصف', 'Description')}</span>
                <span className="mx-3 inline-block w-6 h-px bg-stone-300/50 align-middle" />
              </div>
              <div className="relative bg-white rounded-2xl p-6 md:p-8 border border-stone-100 shadow-sm">
                <div className="absolute -top-2 -right-2 text-5xl md:text-6xl font-serif text-stone-200 leading-none select-none">"</div>
                <p className="text-stone-600 leading-[2] text-sm md:text-base font-normal tracking-wide whitespace-pre-line relative">
                  {desc || title}
                </p>
              </div>
            </section>

            {/* ✅ Amenities — clean pills */}
            {property.amenities && property.amenities.length > 0 && (
              <section ref={amenitiesRef}>
                <div className="mb-5 md:mb-6">
                  <span className="text-[10px] font-semibold uppercase tracking-[0.25em] text-stone-400">{L('المرافق', 'Amenities')}</span>
                  <span className="mx-3 inline-block w-6 h-px bg-stone-300/50 align-middle" />
                  <h3 className="text-lg md:text-xl font-bold text-stone-900 mt-2">{L('المرافق والخدمات', 'Amenities & Services')}</h3>
                </div>
                <div className="flex flex-wrap gap-2">
                  {property.amenities.map((am: any) => (
                    <span key={am.id}
                      className="inline-flex items-center gap-2 px-3.5 py-2 text-xs md:text-sm text-stone-600 bg-white border border-stone-200/70 rounded-xl hover:border-primary/20 hover:text-primary hover:bg-primary/[0.02] transition-all duration-200 shadow-sm">
                      <span className="w-1 h-1 rounded-full bg-primary/40 shrink-0" />
                      {isAr ? am.name_ar : am.name_en}
                    </span>
                  ))}
                </div>
              </section>
            )}

            {/* ✅ Details — clean spec grid */}
            <section ref={detailsRef}>
              <div className="mb-5 md:mb-6">
                <span className="text-[10px] font-semibold uppercase tracking-[0.25em] text-stone-400">{L('التفاصيل', 'Details')}</span>
                <span className="mx-3 inline-block w-6 h-px bg-stone-300/50 align-middle" />
                <h3 className="text-lg md:text-xl font-bold text-stone-900 mt-2">{L('مواصفات العقار', 'Property Specs')}</h3>
              </div>
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                {[
                  { icon: TrendingUp, label: L('نوع الصفقة', 'Purpose'), value: L(property.purpose === 'sale' ? 'بيع' : 'إيجار', property.purpose === 'sale' ? 'Sale' : 'Rent') },
                  { icon: Maximize2, label: L('المساحة', 'Area'), value: `${property.area_sqm} م²` },
                  { icon: Bed, label: t('property.bedrooms'), value: String(property.bedrooms || '—') },
                  { icon: Bath, label: t('property.bathrooms'), value: String(property.bathrooms || '—') },
                  { icon: Check, label: L('الحالة', 'Status'), value: t(`property.status.${property.status}`) },
                  ...(property.ref_code ? [{ icon: Hash, label: t('property.refCode'), value: property.ref_code }] : []),
                  ...(p.year_built ? [{ icon: Calendar, label: L('سنة البناء', 'Year Built'), value: String(p.year_built) }] : []),
                  ...(p.furnished ? [{ icon: Check, label: L('مفروش', 'Furnished'), value: L('نعم', 'Yes') }] : []),
                  ...(p.floor ? [{ icon: Building2, label: L('الطابق', 'Floor'), value: `${p.floor}` }] : []),
                  ...(p.parking ? [{ icon: Check, label: L('مواقف سيارات', 'Parking'), value: L('متوفر', 'Available') }] : []),
                ].filter(Boolean).map((row: any, i) => (
                  <div key={i} className="group flex items-center gap-3 px-3.5 py-3 bg-white rounded-xl border border-stone-100 hover:border-stone-200 transition-all duration-200">
                    <div className="w-9 h-9 rounded-lg bg-primary/[0.04] group-hover:bg-primary/[0.08] flex items-center justify-center shrink-0 transition-colors">
                      <row.icon className="w-4 h-4 text-primary/60 group-hover:text-primary/80" />
                    </div>
                    <div className="min-w-0 flex-1">
                      <div className="text-[11px] text-stone-400">{row.label}</div>
                      <div className="text-sm font-semibold text-stone-800">{row.value}</div>
                    </div>
                  </div>
                ))}
              </div>
            </section>

            {/* ✅ Nearby Places — clean grid */}
            <section>
              <div className="mb-5 md:mb-6">
                <span className="text-[10px] font-semibold uppercase tracking-[0.25em] text-stone-400">{L('القريب', 'Nearby')}</span>
                <span className="mx-3 inline-block w-6 h-px bg-stone-300/50 align-middle" />
                <h3 className="text-lg md:text-xl font-bold text-stone-900 mt-2">{L('ماذا حول العقار', 'What\'s Nearby')}</h3>
                <p className="text-stone-400 text-xs mt-0.5">{L('أبرز الأماكن والخدمات القريبة', 'Nearby places & services')}</p>
              </div>
              <div className="grid grid-cols-2 sm:grid-cols-4 gap-2">
                {[
                  { icon: 'coffee', label: L('مقاهي ومطاعم', 'Cafes & Restaurants'), detail: L('5 دقائق', '5 min') },
                  { icon: 'shopping-bag', label: L('مراكز تسوق', 'Shopping'), detail: L('3 دقائق', '3 min') },
                  { icon: 'graduation-cap', label: L('مدارس', 'Schools'), detail: L('7 دقائق', '7 min') },
                  { icon: 'hospital', label: L('مستشفيات', 'Hospitals'), detail: L('10 دقائق', '10 min') },
                  { icon: 'park', label: L('حدائق عامة', 'Parks'), detail: L('دقيقتان', '2 min') },
                  { icon: 'bus', label: L('مواصلات عامة', 'Transport'), detail: L('دقيقة', '1 min') },
                  { icon: 'bank', label: L('مصارف', 'Banks'), detail: L('5 دقائق', '5 min') },
                  { icon: 'pharmacy', label: L('صيدليات', 'Pharmacies'), detail: L('3 دقائق', '3 min') },
                ].map((place, i) => (
                  <div key={i} className="flex items-center gap-2.5 px-3 py-3 bg-white rounded-xl border border-stone-100 shadow-sm">
                    <svg className="w-[16px] h-[16px] text-primary/40 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5">
                      {place.icon === 'coffee' && <><path strokeLinecap="round" strokeLinejoin="round" d="M8.288 15.038a5.25 5.25 0 0 1 7.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M1.924 8.674c5.565-5.565 14.587-5.565 20.152 0M12.53 18.22l-.53.53-.53-.53a.75.75 0 0 1 1.06 0Z" /></>}
                      {place.icon === 'shopping-bag' && <><path strokeLinecap="round" strokeLinejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" /></>}
                      {place.icon === 'graduation-cap' && <><path strokeLinecap="round" strokeLinejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.903 59.903 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342" /></>}
                      {place.icon === 'hospital' && <><path strokeLinecap="round" strokeLinejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></>}
                      {place.icon === 'park' && <><path strokeLinecap="round" strokeLinejoin="round" d="m20.893 13.393-1.135-1.135a2.252 2.252 0 0 1-.421-.585l-1.08-2.16a.414.414 0 0 0-.663-.107.827.827 0 0 1-.812.21l-1.273-.363a.89.89 0 0 0-.738 1.595l.587.39c.59.395.674 1.23.172 1.732l-.2.2c-.212.212-.33.498-.33.796v.41c0 .409-.11.809-.32 1.158l-1.315 2.191a2.11 2.11 0 0 1-1.81 1.025 1.055 1.055 0 0 1-1.055-1.055v-1.172c0-.92-.56-1.747-1.414-2.089l-.655-.261a2.25 2.25 0 0 1-1.383-2.46l.007-.042a2.25 2.25 0 0 1 .29-.787l.09-.15a2.25 2.25 0 0 1 2.37-1.048l1.178.236a1.125 1.125 0 0 0 1.302-.795l.208-.73a1.125 1.125 0 0 0-.578-1.315l-.665-.332-.091.091a2.25 2.25 0 0 1-1.591.659h-.18c-.249 0-.487.1-.662.274a.931.931 0 0 1-1.458-1.137l1.411-2.353a2.25 2.25 0 0 0 .286-.76m11.928 9.869A9 9 0 0 0 8.965 3.525m11.928 9.868A9 9 0 1 0 11.999 21a8.933 8.933 0 0 0 5.394-1.784" /></>}
                      {place.icon === 'bus' && <><path strokeLinecap="round" strokeLinejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" /></>}
                      {place.icon === 'bank' && <><path strokeLinecap="round" strokeLinejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></>}
                      {place.icon === 'pharmacy' && <><path strokeLinecap="round" strokeLinejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" /></>}
                    </svg>
                    <div className="min-w-0">
                      <div className="text-xs font-medium text-stone-700">{place.label}</div>
                      <div className="text-[10px] text-stone-400">{place.detail}</div>
                    </div>
                  </div>
                ))}
              </div>
            </section>

            {/* ✅ Location — clean card */}
            <section ref={locationRef}>
              <div className="mb-5 md:mb-6">
                <span className="text-[10px] font-semibold uppercase tracking-[0.25em] text-stone-400">{L('الموقع', 'Location')}</span>
                <span className="mx-3 inline-block w-6 h-px bg-stone-300/50 align-middle" />
                <h3 className="text-lg md:text-xl font-bold text-stone-900 mt-2">{L('مكان العقار', 'Property Location')}</h3>
              </div>
              <div className="bg-white rounded-2xl border border-stone-100 overflow-hidden shadow-sm">
                <div className="p-5 md:p-6">
                  <div className="flex items-center gap-2 text-sm text-stone-500 mb-4">
                    <MapPin className="w-4 h-4 text-primary/50" />
                    <span className="font-medium text-stone-700">{property.governorate?.name}{property.area?.name ? `، ${property.area.name}` : ''}</span>
                  </div>
                  <div className="h-48 md:h-56 bg-stone-50 rounded-xl overflow-hidden flex items-center justify-center border border-stone-100">
                    <div className="text-center px-6">
                      <div className="w-12 h-12 rounded-full bg-stone-100 flex items-center justify-center mx-auto mb-3">
                        <MapPin className="w-5 h-5 text-stone-300" />
                      </div>
                      {(property.latitude && property.longitude) ? (
                        <a href={`https://www.google.com/maps?q=${property.latitude},${property.longitude}`}
                          target="_blank" rel="noopener noreferrer"
                          className="inline-flex items-center gap-2 px-5 py-2.5 bg-primary text-white text-sm font-medium rounded-xl hover:bg-primary-dark transition-all shadow-sm">
                          <ExternalLink className="w-3.5 h-3.5" />
                          {L('Google Maps', 'View on Google Maps')}
                        </a>
                      ) : p.lat && p.lng ? (
                        <a href={`https://www.google.com/maps?q=${p.lat},${p.lng}`}
                          target="_blank" rel="noopener noreferrer"
                          className="inline-flex items-center gap-2 px-5 py-2.5 bg-primary text-white text-sm font-medium rounded-xl hover:bg-primary-dark transition-all shadow-sm">
                          <ExternalLink className="w-3.5 h-3.5" />
                          {L('Google Maps', 'View on Google Maps')}
                        </a>
                      ) : (
                        <p className="text-stone-400 text-sm">{L('الموقع غير متوفر', 'Location unavailable')}</p>
                      )}
                    </div>
                  </div>
                </div>
              </div>
            </section>

            {/* ✅ Highlights — property selling points */}
            <section>
              <div className="mb-5 md:mb-6">
                <span className="text-[10px] font-semibold uppercase tracking-[0.25em] text-stone-400">{L('المميزات', 'Highlights')}</span>
                <span className="mx-3 inline-block w-6 h-px bg-stone-300/50 align-middle" />
                <h3 className="text-lg md:text-xl font-bold text-stone-900 mt-2">{L('لماذا هذا العقار', 'Why This Property')}</h3>
              </div>
              <div className="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
                {[
                  ...(p.year_built ? [{ icon: Calendar, label: L('بناء حديث', 'Modern Build'), value: `${p.year_built}` }] : []),
                  ...(property.bedrooms >= 3 ? [{ icon: Bed, label: L('غرف واسعة', 'Spacious Rooms'), value: `${property.bedrooms} ${L('غرف', 'rooms')}` }] : []),
                  ...(property.area_sqm >= 150 ? [{ icon: Maximize2, label: L('مساحة كبيرة', 'Large Area'), value: `${property.area_sqm} م²` }] : []),
                  ...(p.furnished ? [{ icon: Check, label: L('مفروش بالكامل', 'Fully Furnished'), value: L('جاهز للسكن', 'Move-in Ready') }] : []),
                  ...(p.parking ? [{ icon: Check, label: L('موقف سيارة', 'Parking'), value: L('متوفر', 'Available') }] : []),
                  ...(property.purpose === 'rent' ? [{ icon: Shield, label: L('إيجار آمن', 'Secure Rent'), value: L('عقد موثق', 'Verified Contract') }] : []),
                  ...(property.is_featured ? [{ icon: Sparkles, label: L('عقار مميز', 'Featured'), value: L('أولوية العرض', 'Priority Listing') }] : []),
                  ...(property.is_hot_deal ? [{ icon: Star, label: L('صفقة ساخنة', 'Hot Deal'), value: L('سعر ممتاز', 'Great Price') }] : []),
                ].slice(0, 6).map((h, i) => (
                  <div key={i} className="flex flex-col items-center text-center gap-2 px-3 py-4 md:py-5 bg-white rounded-2xl border border-stone-100 shadow-sm hover:border-primary/15 hover:shadow-md transition-all duration-200">
                    <div className="w-9 h-9 rounded-xl bg-gold/10 flex items-center justify-center">
                      <h.icon className="w-4 h-4 text-gold" />
                    </div>
                    <div>
                      <div className="text-xs font-semibold text-stone-800">{h.label}</div>
                      <div className="text-[10px] text-stone-400 mt-0.5">{h.value}</div>
                    </div>
                  </div>
                ))}
              </div>
            </section>
          </div>

          {/* ─── RIGHT: Sidebar — premium stack ─── */}
          <div className="w-full lg:w-80 shrink-0">
            <div className="lg:sticky lg:top-24 space-y-5">

              {/* Price card — refined */}
              <div className="card-3d overflow-hidden">
                <div className="h-1.5 bg-gradient-to-r from-gold/80 via-gold to-gold/80" />
                <div className="p-6 md:p-7">
                  <div className="text-2xs text-stone-400 uppercase tracking-[0.15em] mb-1">{L('السعر', 'Price')}</div>
                  <div className="text-3xl md:text-4xl font-bold text-primary tracking-tight">${price}</div>
                  <div className="text-sm text-stone-400 mt-1">
                    {isRent ? `/${L('شهر', 'month')}` : L('السعر النهائي', 'Final price')}
                  </div>
                  <div className="flex items-center gap-3 mt-4">
                    <span className={`inline-flex items-center gap-1.5 text-xs font-medium ${
                      property.status === 'available' ? 'text-green-600' : 'text-red-500'
                    }`}>
                      <span className={`w-1.5 h-1.5 rounded-full ${property.status === 'available' ? 'bg-green-500' : 'bg-red-500'}`} />
                      {t(`property.status.${property.status}`)}
                    </span>
                    {property.is_featured && (
                      <span className="inline-flex items-center gap-1 px-2 py-0.5 text-2xs font-semibold uppercase tracking-wider bg-gold/10 text-gold rounded-md">
                        <Sparkles className="w-2.5 h-2.5" /> {L('مميز', 'Featured')}
                      </span>
                    )}
                  </div>

                  {/* CTA buttons — refined */}
                  <div className="mt-6 space-y-2.5">
                    <div className="flex gap-2">
                      <button onClick={() => setFaved(!faved)}
                        className={`flex-1 flex items-center justify-center gap-1.5 px-3 py-2.5 text-sm font-medium rounded-xl transition-all ${
                          faved ? 'border border-red-200 bg-red-50 text-red-500' : 'border border-stone-200 bg-white text-stone-600 hover:bg-stone-50 hover:border-stone-300'
                        }`}>
                        <Heart className={`w-4 h-4 ${faved ? 'fill-red-500' : ''}`} />
                        {faved ? L('تم', 'Saved') : L('حفظ', 'Save')}
                      </button>
                      <button onClick={copyLink}
                        className="flex-1 flex items-center justify-center gap-1.5 px-3 py-2.5 border border-stone-200 bg-white text-stone-600 hover:bg-stone-50 hover:border-stone-300 text-sm font-medium rounded-xl transition-all">
                        {copied ? <Check className="w-4 h-4 text-green-500" /> : <Share2 className="w-4 h-4" />}
                        {copied ? L('تم', 'Copied') : L('مشاركة', 'Share')}
                      </button>
                    </div>
                    {!user && (
                      <Link to="/register" className="block text-center text-xs text-primary/50 hover:text-primary transition-colors pt-1 underline underline-offset-2 decoration-primary/20 hover:decoration-primary/40">
                        {L('سجل حساباً للتواصل', 'Register to contact agency')}
                      </Link>
                    )}
                  </div>
                </div>
              </div>

              {/* Quick info — refined */}
              <div className="card-3d p-6 md:p-7">
                <h3 className="text-2xs font-semibold text-stone-900 uppercase tracking-[0.15em] mb-5 flex items-center gap-2">
                  <Ruler className="w-3.5 h-3.5 text-gold" /> {L('معلومات سريعة', 'Quick Info')}
                </h3>
                <div className="space-y-0">
                  {[
                    { icon: Maximize2, label: L('المساحة', 'Area'), value: `${property.area_sqm} م²` },
                    { icon: Bed, label: t('property.bedrooms'), value: String(property.bedrooms || '—') },
                    { icon: Bath, label: t('property.bathrooms'), value: String(property.bathrooms || '—') },
                    ...(p.year_built ? [{ icon: Calendar, label: L('سنة البناء', 'Year Built'), value: String(p.year_built) }] : []),
                    { icon: Hash, label: t('property.refCode'), value: property.ref_code || '—' },
                    ...(p.floor ? [{ icon: Building2, label: L('الطابق', 'Floor'), value: `${p.floor}` }] : []),
                  ].filter(d => d.value !== '—').map((d, i) => (
                    <div key={i} className="flex items-center justify-between text-sm py-3 border-b border-stone-50 last:border-0 group">
                      <span className="flex items-center gap-2.5 text-stone-500 group-hover:text-stone-700 transition-colors">
                        <d.icon className="w-3.5 h-3.5 text-stone-300 group-hover:text-gold/60 transition-colors shrink-0" />
                        {d.label}
                      </span>
                      <span className="font-medium text-stone-900">{d.value}</span>
                    </div>
                  ))}
                </div>
              </div>

              {/* Agency card — identity + dual CTAs */}
              {property.agent && (() => {
              const agent = property.agent!;
              return (
              <div className="card-3d overflow-hidden">
                  <div className="bg-gradient-to-br from-primary via-primary to-primary-dark p-6 md:p-7 text-white relative">
                    <div className="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgba(255,255,255,0.06)_0%,_transparent_60%)]" />
                    <div className="relative">
                      {/* Agency identity */}
                      <div className="flex items-center gap-4 mb-5">
                        <div className="w-14 h-14 rounded-2xl bg-white/15 flex items-center justify-center backdrop-blur-sm border border-white/10 shrink-0 shadow-lg shadow-black/10 overflow-hidden text-xl font-bold text-white">
                          {agent.agency?.logo_url ? (
                            <img src={agent.agency.logo_url} alt=""
                              className="w-full h-full object-cover"
                              onError={e => { (e.target as HTMLElement).style.display = 'none'; (e.target as HTMLElement).parentElement!.textContent = (agent.agency?.name || agent.name || 'وكالة').charAt(0); }} />
                          ) : agent.photo_url ? (
                            <img src={agent.photo_url} alt=""
                              className="w-full h-full object-cover"
                              onError={e => { (e.target as HTMLElement).style.display = 'none'; (e.target as HTMLElement).parentElement!.textContent = (agent.name || 'A').charAt(0); }} />
                          ) : (
                            (agent.agency?.name || agent.name || 'وكالة').charAt(0)
                          )}
                        </div>
                        <div className="min-w-0 flex-1">
                          <div className="font-bold text-white truncate text-lg">
                            {property.agent.agency?.name || property.agent.name || L('الوكالة', 'Agency')}
                          </div>
                          <div className="flex items-center gap-1.5 text-white/50 text-xs mt-0.5">
                            <Building2 className="w-3 h-3 shrink-0" />
                            <span className="truncate">{property.agent.name || L('الوكيل', 'Agent')}</span>
                          </div>
                        </div>
                      </div>

                      {/* Dual CTAs */}
                      <div className="space-y-2.5">
                        <button onClick={handleChatRedirect}
                          className="w-full flex items-center justify-center gap-2.5 px-5 py-3 bg-white text-primary font-bold text-sm rounded-xl hover:bg-white/90 transition-all shadow-lg shadow-black/10 active:scale-[0.98]">
                          <MessageCircle className="w-4 h-4" />
                          {L('محادثة مع الوكيل', 'Chat with Agent')}
                        </button>
                        <button onClick={() => setInquiryOpen(true)}
                          className="w-full flex items-center justify-center gap-2 px-5 py-2.5 border border-white/20 text-white text-sm font-medium rounded-xl hover:bg-white/10 transition-all active:scale-[0.98]">
                          <ArrowLeft className="w-3.5 h-3.5 lucide-rtl" />
                          {L('استفسار سريع', 'Quick Inquiry')}
                        </button>
                      </div>

                      <div className="mt-5 pt-5 border-t border-white/10 space-y-3">
                        <div className="flex items-center gap-3 text-xs text-white/40">
                          <div className="w-5 h-5 rounded-full bg-white/5 flex items-center justify-center shrink-0">
                            <Shield className="w-2.5 h-2.5 text-gold/70" />
                          </div>
                          {L('تواصل آمن داخل المنصة', 'Secure in-app messaging')}
                        </div>
                        <div className="flex items-center gap-3 text-xs text-white/40">
                          <div className="w-5 h-5 rounded-full bg-white/5 flex items-center justify-center shrink-0">
                            <Check className="w-2.5 h-2.5 text-gold/70" />
                          </div>
                          {L('معلومات موثقة', 'Verified information')}
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              );
              })()}
            </div>
          </div>
        </div>
      </div>

      {/* ══════════════════════════════════════
          RELATED — clean section
          ══════════════════════════════════════ */}
      {related.length > 0 && (
        <section className="py-16 bg-white" ref={relatedRef}>
          <div className="max-w-6xl mx-auto px-5 sm:px-8 lg:px-12">
            <div className="mb-8">
              <span className="badge-gold mb-3 inline-flex items-center gap-1.5">
                <TrendingUp className="w-3.5 h-3.5" /> {L('مقترحات', 'Suggestions')}
              </span>
              <h3 className="text-xl md:text-2xl font-bold text-stone-900">{t('property.similar')}</h3>
              <p className="text-sm text-stone-400 mt-1">{L('عقارات مشابهة قد تهمك', 'Similar properties you might like')}</p>
            </div>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-5">
              {related.map((r) => (
                <div key={r.id}>
                  <PropertyCard property={r} />
                </div>
              ))}
            </div>
          </div>
        </section>
      )}

      {/* ═══════ MOBILE BAR — dual CTAs ═══════ */}
      <div className="fixed bottom-0 left-0 right-0 bg-white border-t border-stone-200 z-40 lg:hidden shadow-2xl">
        <div className="flex items-center gap-3 px-4 py-2.5">
          <div className="flex-1 min-w-0">
            <div className="text-lg font-bold text-primary">${price}</div>
            <div className="text-xs text-stone-400 truncate flex items-center gap-1">
              <MapPin className="w-3 h-3 shrink-0" />{property.governorate?.name}
            </div>
          </div>
          {property?.agent?.agency && (
            <>
              <button onClick={() => setInquiryOpen(true)}
                className="flex items-center gap-1.5 px-3.5 py-2.5 border border-primary/20 text-primary text-sm font-medium rounded-xl hover:bg-primary/5 transition-all">
                <MessageCircle className="w-3.5 h-3.5" />
                {L('استفسار', 'Inquiry')}
              </button>
              <button onClick={handleChatRedirect}
                className="flex items-center gap-2 px-5 py-2.5 bg-primary text-white font-bold text-sm rounded-xl hover:bg-primary-dark transition-all shadow-lg shadow-primary/20">
                <MessageCircle className="w-4 h-4" />
                {L('محادثة', 'Chat')}
              </button>
            </>
          )}
          {!user && (
            <Link to={`/login?redirect=${encodeURIComponent(window.location.pathname)}`}
              className="flex items-center gap-2 px-4 py-2.5 border-2 border-primary text-primary font-semibold text-sm rounded-xl hover:bg-primary/5 transition-all shrink-0">
              {L('تسجيل', 'Login')}
            </Link>
          )}
        </div>
      </div>

      <Footer />
    </div>
  );
}
