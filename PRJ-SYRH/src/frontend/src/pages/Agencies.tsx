import { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { Link } from 'react-router-dom';
import { Building2, ChevronLeft, MapPin, ArrowLeft, Users } from 'lucide-react';
import Navbar from '../components/Navbar';
import Footer from '../components/Footer';
import SelectField from '../components/SelectField';
import { fetchAgencies, type AgencyPublic } from '../api/properties';
import { fetchLocations, fetchAreas, type LocationsResponse } from '../api/locations';

const agencyColors = [
  'from-primary/20 to-primary/5',
  'from-gold/20 to-amber-50',
  'from-emerald-500/10 to-emerald-500/5',
  'from-beige to-beige-dark',
  'from-primary/10 to-primary/5',
  'from-gold/10 to-gold/5',
];

export default function Agencies() {
  const { t, i18n } = useTranslation();
  const isAr = i18n.language === 'ar';
  const L = (ar: string, en: string) => isAr ? ar : en;

  const [agencies, setAgencies] = useState<AgencyPublic[]>([]);
  const [loading, setLoading] = useState(true);
  const [locations, setLocations] = useState<LocationsResponse | null>(null);
  const [govSlug, setGovSlug] = useState('');
  const [areaSlug, setAreaSlug] = useState('');
  const [areas, setAreas] = useState<{ slug: string; name: string; name_ar: string; name_en: string }[]>([]);
  const [areasLoading, setAreasLoading] = useState(false);

  useEffect(() => {
    fetchLocations().then(setLocations).catch(() => {});
  }, []);

  useEffect(() => {
    if (!govSlug) { setAreas([]); setAreaSlug(''); return; }
    setAreasLoading(true);
    setAreaSlug('');
    fetchAreas(govSlug).then(setAreas).catch(() => setAreas([])).finally(() => setAreasLoading(false));
  }, [govSlug]);

  useEffect(() => {
    setLoading(true);
    const params: Record<string, string> = {};
    if (govSlug) params.governorate = govSlug;
    if (areaSlug) params.area = areaSlug;
    fetchAgencies(params).then(setAgencies).finally(() => setLoading(false));
  }, [govSlug, areaSlug]);

  const areaOptions = areas.map(a => ({ value: a.slug, label: isAr ? a.name_ar : a.name_en }));

  return (
    <div dir={isAr ? 'rtl' : 'ltr'} className="min-h-screen flex flex-col bg-cream">
      <Navbar />
      <div className="flex-1 pt-24 pb-16">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          {/* Breadcrumb */}
          <div className="flex items-center gap-2 text-sm text-stone-400 mb-6">
            <Link to="/" className="hover:text-primary transition-colors">{t('nav.home')}</Link>
            <ChevronLeft className="w-3 h-3 lucide-rtl" />
            <span className="text-stone-800 font-medium">{L('الوكالات العقارية', 'Agencies')}</span>
          </div>

          {/* Header */}
          <div className="mb-8">
            <h1 className="text-3xl md:text-4xl font-bold text-stone-900">
              {L('الوكالات العقارية', 'Real Estate Agencies')}
            </h1>
            <p className="text-stone-500 mt-2 text-lg">
              {L('تصفح الوكالات العقارية في سوريا', 'Browse real estate agencies in Syria')}
            </p>
          </div>

          {/* Filters — clean glass style */}
          <div className="flex flex-wrap items-end gap-4 mb-8 p-5 bg-white rounded-2xl shadow-sm border border-beige">
            <div className="w-full sm:w-56">
              <label className="block text-xs font-medium text-stone-500 mb-1.5">
                {L('المحافظة', 'Governorate')}
              </label>
              <SelectField
                placeholder={L('كل المحافظات', 'All governorates')}
                options={(locations?.governorates ?? []).map(g => ({ value: g.slug, label: isAr ? g.name_ar : g.name_en }))}
                value={govSlug}
                onChange={v => { setGovSlug(v); setAreaSlug(''); }}
                selectClassName="!py-2.5 text-sm"
              />
            </div>
            <div className="w-full sm:w-56">
              <label className="block text-xs font-medium text-stone-500 mb-1.5">
                {L('المنطقة', 'Area')}
              </label>
              <SelectField
                placeholder={L('كل المناطق', 'All areas')}
                options={areaOptions}
                value={areaSlug}
                onChange={setAreaSlug}
                disabled={!govSlug || areasLoading}
                selectClassName="!py-2.5 text-sm"
              />
            </div>
            {(govSlug || areaSlug) && (
              <button
                onClick={() => { setGovSlug(''); setAreaSlug(''); }}
                className="px-5 py-2.5 text-sm font-medium text-primary hover:text-primary-dark transition-colors rounded-lg hover:bg-primary/5"
              >
                {L('إعادة تعيين', 'Reset')}
              </button>
            )}
          </div>

          {/* Loading skeleton */}
          {loading ? (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              {Array.from({ length: 6 }).map((_, i) => (
                <div key={i} className="bg-white rounded-2xl overflow-hidden shadow-sm">
                  <div className="h-32 shimmer" />
                  <div className="p-5 space-y-3">
                    <div className="h-5 shimmer rounded w-3/4" />
                    <div className="h-3 shimmer rounded w-1/2" />
                    <div className="h-3 shimmer rounded w-full" />
                  </div>
                </div>
              ))}
            </div>
          ) : agencies.length === 0 ? (
            <div className="bg-white rounded-2xl p-16 text-center shadow-sm">
              <Building2 className="w-16 h-16 text-stone-200 mx-auto mb-4" />
              <p className="text-stone-500 text-lg">
                {govSlug || areaSlug
                  ? L('لا توجد وكالات في هذا الموقع', 'No agencies found in this location')
                  : L('لا توجد وكالات مسجلة بعد', 'No agencies registered yet')}
              </p>
              {(govSlug || areaSlug) && (
                <button
                  onClick={() => { setGovSlug(''); setAreaSlug(''); }}
                  className="mt-5 text-primary font-medium hover:underline text-sm"
                >
                  {L('إعادة تعيين الفلترة', 'Reset filters')}
                </button>
              )}
            </div>
          ) : (
            <>
              <div className="flex items-center justify-between mb-5">
                <p className="text-sm text-stone-500">
                  <span className="font-semibold text-stone-800">{agencies.length}</span>
                  {' '}{L('وكالة', 'agencies')}
                  {govSlug && (
                    <span className="hidden sm:inline">
                      {' — '}{locations?.governorates.find(g => g.slug === govSlug)?.name}
                      {areaSlug && ` / ${areas.find(a => a.slug === areaSlug)?.name}`}
                    </span>
                  )}
                </p>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                {agencies.map((a, idx) => (
                  <Link
                    key={a.id}
                    to={`/agencies/${a.slug}`}
                    className="card-3d group"
                  >
                    {/* Logo area */}
                    <div className={`relative h-32 bg-gradient-to-br ${agencyColors[idx % agencyColors.length]} flex items-center justify-center`}>
                      {a.logo_path ? (
                        <img
                          src={a.logo_path}
                          alt={a.name}
                          className="w-20 h-20 rounded-2xl object-cover shadow-lg ring-4 ring-white/80"
                          onError={(e) => { (e.target as HTMLImageElement).style.display = 'none'; }}
                        />
                      ) : (
                        <div className="w-20 h-20 rounded-2xl bg-white shadow-lg ring-4 ring-white/80 flex items-center justify-center">
                          <span className="text-3xl font-bold text-primary">{a.name.charAt(0)}</span>
                        </div>
                      )}
                    </div>

                    {/* Content */}
                    <div className="p-5">
                      <h3 className="font-bold text-stone-900 text-lg mb-2 group-hover:text-primary transition-colors">
                        {a.name}
                      </h3>

                      {a.description_ar && (
                        <p className="text-sm text-stone-500 line-clamp-2 mb-4 leading-relaxed">
                          {isAr ? a.description_ar : a.description_en}
                        </p>
                      )}

                      {/* Stats pills */}
                      <div className="flex items-center gap-2 mb-4">
                        <span className="inline-flex items-center gap-1.5 text-xs font-medium text-stone-600 bg-stone-50 px-3 py-1.5 rounded-full border border-stone-100">
                          <Building2 className="w-3.5 h-3.5 text-primary/60" />
                          {a.properties_count} {L('عقار', 'prop.')}
                        </span>
                        <span className="inline-flex items-center gap-1.5 text-xs font-medium text-stone-600 bg-stone-50 px-3 py-1.5 rounded-full border border-stone-100">
                          <Users className="w-3.5 h-3.5 text-gold/60" />
                          {a.agents_count} {L('وكيل', 'ag.')}
                        </span>
                      </div>

                      {/* CTA */}
                      <div className="flex items-center justify-between pt-3 border-t border-beige">
                        <span className="text-xs font-medium text-primary group-hover:gap-2 transition-all flex items-center gap-1">
                          {L('زيارة الوكالة', 'Visit agency')}
                          <ArrowLeft className="w-3.5 h-3.5 lucide-rtl group-hover:translate-x-0.5 transition-transform" />
                        </span>
                        <MapPin className="w-3.5 h-3.5 text-stone-300" />
                      </div>
                    </div>
                  </Link>
                ))}
              </div>
            </>
          )}
        </div>
      </div>
      <Footer />
    </div>
  );
}
