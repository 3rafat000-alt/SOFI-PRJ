import { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { useSearchParams, Link } from 'react-router-dom';
import { Search, SlidersHorizontal, X, ChevronLeft } from 'lucide-react';
import Navbar from '../components/Navbar';
import Footer from '../components/Footer';
import PropertyCard from '../components/PropertyCard';
import SelectField from '../components/SelectField';
import { fetchProperties } from '../api/properties';
import type { PropertyCard as PropertyCardType } from '../api/client';
import { fetchLocations } from '../api/locations';
import { fetchPropertyTypes } from '../api/properties';
import type { Governorate, PropertyType } from '../api/client';

export default function SearchResults() {
  const { t, i18n } = useTranslation();
  const isAr = i18n.language === 'ar';
  const [searchParams, setSearchParams] = useSearchParams();
  const [properties, setProperties] = useState<PropertyCardType[]>([]);
  const [loading, setLoading] = useState(true);
  const [governorates, setGovernorates] = useState<Governorate[]>([]);
  const [types, setTypes] = useState<PropertyType[]>([]);
  const [showFilters, setShowFilters] = useState(false);

  const q = searchParams.get('q') || '';
  const governorate = searchParams.get('governorate') || '';
  const type = searchParams.get('type') || '';
  const purpose = searchParams.get('purpose') || '';
  const minPrice = searchParams.get('min_price') || '';
  const maxPrice = searchParams.get('max_price') || '';

  useEffect(() => {
    fetchLocations().then(loc => setGovernorates(loc.governorates)).catch(() => {});
    fetchPropertyTypes().then(setTypes).catch(() => {});
  }, []);

  useEffect(() => {
    setLoading(true);
    const params: Record<string, string> = {};
    if (q) params.search = q;
    if (governorate) params.governorate = governorate;
    if (type) params.type = type;
    if (purpose) params.purpose = purpose;
    if (minPrice) params.min_price = minPrice;
    if (maxPrice) params.max_price = maxPrice;
    fetchProperties(params).then(res => {
      setProperties(res.data || []);
    }).finally(() => setLoading(false));
  }, [searchParams]);

  const updateFilter = (key: string, value: string) => {
    const params = new URLSearchParams(searchParams);
    if (value) params.set(key, value);
    else params.delete(key);
    setSearchParams(params);
  };

  const clearFilters = () => setSearchParams(new URLSearchParams());

  const hasFilters = governorate || type || purpose || minPrice || maxPrice;

  return (
    <div dir={isAr ? 'rtl' : 'ltr'} className="min-h-screen flex flex-col">
      <Navbar />
      <div className="flex-1 pt-24 pb-16">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          {/* Header */}
          <div className="mb-6">
            <div className="flex items-center gap-2 text-sm text-stone-400 mb-3">
              <Link to="/" className="hover:text-primary">{t('nav.home')}</Link>
              <ChevronLeft className="w-3 h-3 lucide-rtl" />
              <span className="text-stone-800 font-medium">{isAr ? 'بحث' : 'Search'}</span>
            </div>
            <h1 className="text-2xl md:text-3xl font-bold text-stone-900">
              {isAr ? 'نتائج البحث' : 'Search Results'}
              {q && <span className="text-stone-400">: "{q}"</span>}
            </h1>
          </div>

          {/* Search bar */}
          <div className="flex gap-2 mb-6">
            <div className="relative flex-1">
              <Search className={`absolute top-1/2 -translate-y-1/2 w-5 h-5 text-stone-400 ${isAr ? 'right-3' : 'left-3'}`} />
              <input value={q} onChange={e => updateFilter('q', e.target.value)}
                placeholder={isAr ? 'ابحث عن عقار...' : 'Search properties...'}
                className={`input-field text-base !rounded-full ${isAr ? '!pr-11' : '!pl-11'}`} />
            </div>
            <button onClick={() => setShowFilters(!showFilters)}
              className={`px-4 rounded-full border transition-all ${showFilters ? 'bg-primary text-white border-primary' : 'border-beige-dark text-stone-500 hover:bg-beige'}`}>
              <SlidersHorizontal className="w-5 h-5" />
            </button>
          </div>

          {/* Filters */}
          {showFilters && (
            <div className="card-3d p-4 mb-6">
              <div className="grid grid-cols-2 md:grid-cols-5 gap-3">
                <SelectField
                  value={governorate}
                  onChange={(v) => updateFilter('governorate', v)}
                  placeholder={isAr ? 'كل المحافظات' : 'All Cities'}
                  options={governorates.map(g => ({ value: g.slug, label: g.name }))}
                />
                <SelectField
                  value={type}
                  onChange={(v) => updateFilter('type', v)}
                  placeholder={isAr ? 'كل الأنواع' : 'All Types'}
                  options={types.map(t => ({ value: t.slug, label: isAr ? t.name_ar : t.name_en }))}
                />
                <SelectField
                  value={purpose}
                  onChange={(v) => updateFilter('purpose', v)}
                  placeholder={isAr ? 'الكل' : 'All'}
                  options={[
                    { value: 'sale', label: isAr ? 'بيع' : 'Sale' },
                    { value: 'rent', label: isAr ? 'إيجار' : 'Rent' },
                  ]}
                />
                <input placeholder={isAr ? 'أقل سعر' : 'Min Price'} value={minPrice} onChange={e => updateFilter('min_price', e.target.value)} className="input-field text-sm !rounded-full" />
                <input placeholder={isAr ? 'أعلى سعر' : 'Max Price'} value={maxPrice} onChange={e => updateFilter('max_price', e.target.value)} className="input-field text-sm !rounded-full" />
              </div>
              {hasFilters && (
                <button onClick={clearFilters} className="flex items-center gap-1 text-sm text-stone-500 hover:text-red-500 mt-3 transition-colors">
                  <X className="w-3.5 h-3.5" />{isAr ? 'مسح الفلترة' : 'Clear filters'}
                </button>
              )}
            </div>
          )}

          {/* Results */}
          {loading ? (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 md:gap-6">
              {Array.from({ length: 6 }).map((_, i) => (
                <div key={i} className="card-3d"><div className="aspect-[4/3] shimmer" /><div className="p-4 space-y-2"><div className="h-4 shimmer rounded w-3/4" /><div className="h-3 shimmer rounded w-1/2" /></div></div>
              ))}
            </div>
          ) : properties.length === 0 ? (
            <div className="card-3d p-12 text-center">
              <Search className="w-12 h-12 text-stone-300 mx-auto mb-3" />
              <h3 className="text-lg font-bold text-stone-800 mb-1">{isAr ? 'لا توجد نتائج' : 'No results found'}</h3>
              <p className="text-stone-500 mb-4">{isAr ? 'حاول تعديل معايير البحث' : 'Try adjusting your search criteria'}</p>
              <button onClick={clearFilters} className="btn-primary inline-flex !py-2 !px-4 text-sm">{isAr ? 'مسح الفلترة' : 'Clear filters'}</button>
            </div>
          ) : (
            <>
              <p className="text-sm text-stone-500 mb-4">{properties.length} {isAr ? 'نتيجة' : 'results found'}</p>
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 md:gap-6">
                {properties.map(p => <PropertyCard key={p.id} property={p} />)}
              </div>
            </>
          )}
        </div>
      </div>
      <Footer />
    </div>
  );
}
