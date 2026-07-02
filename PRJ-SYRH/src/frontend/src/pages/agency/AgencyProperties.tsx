import { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { Building2, Plus, Edit2, Loader2, Search, MapPin, AlertTriangle, ArrowUp } from 'lucide-react';
import { Link } from 'react-router-dom';
import { fetchAgencyProperties, fetchAgencySubscription } from '../../api/agency';
import type { PropertyCard } from '../../api/client';
import type { AgencyUsage } from '../../api/agency';
import SubscriptionHint from '../../components/SubscriptionHint';

export default function AgencyProperties() {
  const { t, i18n } = useTranslation();
  const isAr = i18n.language === 'ar';
  const L = (ar: string, en: string) => isAr ? ar : en;
  const [properties, setProperties] = useState<PropertyCard[]>([]);
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [search, setSearch] = useState('');
  const [usage, setUsage] = useState<AgencyUsage | null>(null);

  useEffect(() => {
    fetchAgencySubscription().then(s => setUsage(s.usage ?? null));
  }, []);

  const load = (p?: number, q?: string) => {
    setLoading(true);
    const params: Record<string, string> = { page: String(p || page) };
    if (q || search) params.search = q || search;
    fetchAgencyProperties(params).then(res => {
      setProperties(res.data);
      setLastPage(res.meta?.last_page ?? 1);
    }).finally(() => setLoading(false));
  };

  useEffect(() => { load(); }, [page]);

  return (
    <div>
      <div className="flex items-center justify-between mb-2">
        <h1 className="text-2xl font-bold text-stone-900">{t('nav.properties')}</h1>
        {usage ? (
          <Link to="/dashboard/properties/new"
            className={`text-sm flex items-center gap-2 !py-2 !px-4 rounded-xl font-medium transition-all ${
              usage.properties.max > 0 && usage.properties.current >= usage.properties.max
                ? 'bg-stone-200 text-stone-400 cursor-not-allowed pointer-events-none'
                : 'btn-primary'
            }`}>
            <Plus className="w-4 h-4" /> {isAr ? 'إضافة عقار' : 'Add Property'}
          </Link>
        ) : (
          <Link to="/dashboard/properties/new" className="btn-primary text-sm flex items-center gap-2 !py-2 !px-4">
            <Plus className="w-4 h-4" /> {isAr ? 'إضافة عقار' : 'Add Property'}
          </Link>
        )}
      </div>

      {/* Plan limit bar */}
      {usage && usage.properties.max > 0 && (
        <div className="mb-6">
          <div className="flex items-center justify-between text-xs text-stone-500 mb-1">
            <span>{L('خطة الاشتراك', 'Subscription plan')}</span>
            <span className="font-medium">{usage.properties.current}/{usage.properties.max} {L('عقار', 'properties')}</span>
          </div>
          <div className="w-full h-2 rounded-full bg-stone-200">
            <div className={`h-2 rounded-full transition-all ${
              usage.properties.current >= usage.properties.max ? 'bg-red-500' :
              usage.properties.current / usage.properties.max >= 0.85 ? 'bg-amber-500' : 'bg-primary'
            }`} style={{ width: `${Math.min((usage.properties.current / usage.properties.max) * 100, 100)}%` }} />
          </div>
          {usage.properties.current >= usage.properties.max && (
            <div className="flex items-center gap-1.5 mt-2 text-xs text-red-600">
              <AlertTriangle className="w-3.5 h-3.5 flex-shrink-0" />
              <span>{L('لقد وصلت إلى الحد الأقصى للعقارات. قم بترقية خطتك لإضافة المزيد.', 'You have reached the property limit. Upgrade your plan to add more.')}</span>
              <Link to="/dashboard/subscription" className="text-primary underline hover:no-underline font-medium flex items-center gap-0.5">
                {L('ترقية', 'Upgrade')} <ArrowUp className="w-3 h-3" />
              </Link>
            </div>
          )}
          {usage.properties.max > 0 && usage.properties.current < usage.properties.max && usage.properties.current / usage.properties.max >= 0.85 && (
            <div className="flex items-center gap-1.5 mt-2 text-xs text-amber-600">
              <AlertTriangle className="w-3.5 h-3.5 flex-shrink-0" />
              <span>{L('تقترب من الحد الأقصى للعقارات', 'You are nearing your property limit')}</span>
            </div>
          )}
        </div>
      )}

      {/* Onboarding hint */}
      <SubscriptionHint type="properties" usage={usage} />

      {/* Search */}
      <div className="relative mb-6">
        <Search className="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-stone-400" />
        <input value={search} onChange={e => setSearch(e.target.value)} onKeyDown={e => e.key === 'Enter' && load(1, search)}
          placeholder={isAr ? 'بحث عن عقار...' : 'Search properties...'} className="input-field pr-10 text-sm" />
      </div>

      {loading ? (
        <div className="flex items-center justify-center h-48">
          <Loader2 className="w-8 h-8 animate-spin text-primary" />
        </div>
      ) : properties.length === 0 ? (
        <div className="text-center py-16">
          <Building2 className="w-12 h-12 text-stone-300 mx-auto mb-3" />
          <p className="text-stone-500">{isAr ? 'لا توجد عقارات بعد' : 'No properties yet'}</p>
        </div>
      ) : (
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-beige-dark/50 text-stone-500">
                <th className="text-right py-3 px-4 font-medium">{t('property.details')}</th>
                <th className="text-right py-3 px-4 font-medium">{t('property.price')}</th>
                <th className="text-right py-3 px-4 font-medium">{t('property.purpose.sale')}</th>
                <th className="text-right py-3 px-4 font-medium">{t('property.status.available')}</th>
                <th className="py-3 px-4"></th>
              </tr>
            </thead>
            <tbody>
              {properties.map(p => (
                <tr key={p.id} className="border-b border-beige-dark/20 hover:bg-beige/50 transition-colors">
                  <td className="py-3 px-4">
                    <div className="font-medium text-stone-900">{isAr ? p.title_ar : p.title_en}</div>
                    <div className="flex items-center gap-1 text-xs text-stone-400 mt-0.5">
                      <MapPin className="w-3 h-3" />
                      {p.governorate?.name}, {p.area?.name}
                    </div>
                  </td>
                  <td className="py-3 px-4 font-medium">{Number(p.price).toLocaleString()} {t('property.currency')}</td>
                  <td className="py-3 px-4">
                    <span className="badge-primary">{t(`property.purpose.${p.purpose}`)}</span>
                  </td>
                  <td className="py-3 px-4">
                    <span className={`badge ${p.status === 'available' ? 'badge-primary' : p.status === 'sold' || p.status === 'rented' ? 'badge-red' : 'badge-gold'}`}>
                      {t(`property.status.${p.status}`)}
                    </span>
                  </td>
                  <td className="py-3 px-4">
                    <Link to={`/dashboard/properties/${p.id}/edit`}
                      className="text-primary hover:text-primary-dark transition-colors">
                      <Edit2 className="w-4 h-4" />
                    </Link>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>

          {lastPage > 1 && (
            <div className="flex items-center justify-center gap-2 mt-6" dir="ltr">
              {Array.from({ length: lastPage }).map((_, i) => (
                <button key={i} onClick={() => setPage(i + 1)}
                  className={`w-9 h-9 rounded-lg text-sm font-medium transition-all ${
                    page === i + 1 ? 'bg-primary text-white' : 'border border-beige-dark hover:bg-beige'
                  }`}>
                  {i + 1}
                </button>
              ))}
            </div>
          )}
        </div>
      )}
    </div>
  );
}
