import { useState, useEffect, useCallback, useRef } from 'react';
import { useTranslation } from 'react-i18next';
import { useSearchParams, Link } from 'react-router-dom';
import {
  X, SlidersHorizontal, ChevronLeft, ChevronRight,
  Search, Grid3X3, List, Building2, MapPin,
  Bed, Bath, Maximize2, DollarSign, RotateCcw, Filter,
  Loader2, Home,
} from 'lucide-react';
import Navbar from '../components/Navbar';
import Footer from '../components/Footer';
import SelectField from '../components/SelectField';
import { fetchProperties, fetchPropertyTypes } from '../api/properties';
import { fetchLocations, fetchAreas } from '../api/locations';
import type { PropertyCard as PropertyCardType, Governorate, PropertyType, Area } from '../api/client';

const SORT_OPTIONS = [
  { value: 'newest', key: 'property.sort.newest' },
  { value: 'price_asc', key: 'property.sort.priceAsc' },
  { value: 'price_desc', key: 'property.sort.priceDesc' },
];

const BEDROOM_OPTIONS = [
  { value: '', label: '\u0627\u0644\u0643\u0644' },
  { value: '1', label: '1' },
  { value: '2', label: '2' },
  { value: '3', label: '3' },
  { value: '4', label: '4' },
  { value: '5', label: '5+' },
];

const BATHROOM_OPTIONS = [
  { value: '', label: '\u0627\u0644\u0643\u0644' },
  { value: '1', label: '1' },
  { value: '2', label: '2' },
  { value: '3', label: '3' },
  { value: '4', label: '4+' },
];

export default function Properties() {
  const { t, i18n } = useTranslation();
  const isAr = i18n.language === 'ar';
  const L = (ar: string, en: string) => isAr ? ar : en;
  const [searchParams, setSearchParams] = useSearchParams();

  const [properties, setProperties] = useState<PropertyCardType[]>([]);
  const [total, setTotal] = useState(0);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [propertyTypes, setPropertyTypes] = useState<PropertyType[]>([]);
  const [governorates, setGovernorates] = useState<Governorate[]>([]);
  const [areas, setAreas] = useState<Area[]>([]);
  const [filteredAreas, setFilteredAreas] = useState<Area[]>([]);
  const [filtersOpen, setFiltersOpen] = useState(false);
  const [loading, setLoading] = useState(false);
  const [viewMode, setViewMode] = useState<'grid' | 'list'>('grid');
  const [searchQuery, setSearchQuery] = useState(searchParams.get('q') || '');
  const searchTimer = useRef<ReturnType<typeof setTimeout> | null>(null);

  // Filter values from URL
  const governorate = searchParams.get('governorate') || '';
  const area = searchParams.get('area') || '';
  const type = searchParams.get('type') || '';
  const purpose = searchParams.get('purpose') || '';
  const sort = searchParams.get('sort') || 'newest';
  const bedrooms = searchParams.get('bedrooms') || '';
  const bathrooms = searchParams.get('bathrooms') || '';
  const priceMin = searchParams.get('price_min') || '';
  const priceMax = searchParams.get('price_max') || '';
  const q = searchParams.get('q') || '';

  // Load reference data
  useEffect(() => {
    fetchPropertyTypes().then(setPropertyTypes);
    fetchLocations().then((res) => {
      setGovernorates(res.governorates);
    });
  }, []);

  // Load areas from popular_areas
  useEffect(() => {
    fetchLocations().then((res) => {
      if (res.popular_areas) setAreas(res.popular_areas);
    });
  }, []);

  // Filter areas by selected governorate
  useEffect(() => {
    if (governorate) {
      fetchAreas(governorate).then(setFilteredAreas);
    } else {
      setFilteredAreas([]);
    }
  }, [governorate]);

  // Reset page on filter change
  useEffect(() => { setPage(1); }, [governorate, area, type, purpose, sort, bedrooms, bathrooms, priceMin, priceMax, q]);

  // Fetch properties
  useEffect(() => {
    setLoading(true);
    const params: Record<string, string> = {};
    if (governorate) params.governorate = governorate;
    if (area) params.area = area;
    if (type) params.type = type;
    if (purpose) params.purpose = purpose;
    if (sort) params.sort = sort;
    if (bedrooms) params.bedrooms = bedrooms;
    if (bathrooms) params.bathrooms = bathrooms;
    if (priceMin) params.price_min = priceMin;
    if (priceMax) params.price_max = priceMax;
    if (q) params.q = q;
    params.page = String(page);
    fetchProperties(params).then(res => {
      setProperties(res.data);
      setTotal(res.meta?.total ?? 0);
      setLastPage(res.meta?.last_page ?? 1);
    }).finally(() => setLoading(false));
  }, [governorate, area, type, purpose, sort, bedrooms, bathrooms, priceMin, priceMax, q, page]);

  const updateFilter = useCallback((key: string, value: string) => {
    const params = new URLSearchParams(searchParams);
    if (value) params.set(key, value);
    else params.delete(key);
    if (key !== 'sort' && key !== 'page') params.delete('page');
    setSearchParams(params);
  }, [searchParams, setSearchParams]);

  const clearFilters = () => {
    setSearchParams({});
    setSearchQuery('');
  };

  const handleSearch = (val: string) => {
    setSearchQuery(val);
    if (searchTimer.current) clearTimeout(searchTimer.current);
    searchTimer.current = setTimeout(() => {
      updateFilter('q', val);
    }, 400);
  };

  const hasActiveFilters = !!(governorate || area || type || purpose || bedrooms || bathrooms || priceMin || priceMax || q);

  // Set governorate + clear area in single update (avoid stale closure race)
  const setGovernorate = useCallback((slug: string) => {
    const params = new URLSearchParams(searchParams);
    if (slug) params.set('governorate', slug);
    else params.delete('governorate');
    params.delete('area');
    params.delete('page');
    setSearchParams(params);
  }, [searchParams, setSearchParams]);

  // Build chips
  const activeFilters: { key: string; label: string; onRemove: () => void }[] = [];
  if (purpose) activeFilters.push({ key: 'purpose', label: L(purpose === 'sale' ? '\u0644\u0644\u0628\u064a\u0639' : '\u0644\u0644\u0627\u064a\u062c\u0627\u0631', purpose === 'sale' ? 'For Sale' : 'For Rent'), onRemove: () => updateFilter('purpose', '') });
  if (governorate) {
    const g = governorates.find(gv => gv.slug === governorate);
    activeFilters.push({ key: 'governorate', label: g?.name || governorate, onRemove: () => setGovernorate('') });
  }
  if (area) activeFilters.push({ key: 'area', label: area, onRemove: () => updateFilter('area', '') });
  if (type) {
    const pt = Array.isArray(propertyTypes) ? propertyTypes.find((pt2: PropertyType) => pt2.slug === type) : undefined;
    activeFilters.push({ key: 'type', label: pt?.name || type, onRemove: () => updateFilter('type', '') });
  }
  if (bedrooms) activeFilters.push({ key: 'bedrooms', label: L(`${bedrooms} ${bedrooms === '1' ? '\u063a\u0631\u0641\u0629' : '\u063a\u0631\u0641'}`, `${bedrooms} Bed`), onRemove: () => updateFilter('bedrooms', '') });
  if (bathrooms) activeFilters.push({ key: 'bathrooms', label: L(`${bathrooms} \u062d\u0645\u0627\u0645${bathrooms === '1' ? '' : '\u0627\u062a'}`, `${bathrooms} Bath`), onRemove: () => updateFilter('bathrooms', '') });
  if (priceMin) activeFilters.push({ key: 'price_min', label: L(`\u0645\u0646 ${Number(priceMin).toLocaleString()} $`, `From $${Number(priceMin).toLocaleString()}`), onRemove: () => updateFilter('price_min', '') });
  if (priceMax) activeFilters.push({ key: 'price_max', label: L(`\u062d\u062a\u0649 ${Number(priceMax).toLocaleString()} $`, `Up to $${Number(priceMax).toLocaleString()}`), onRemove: () => updateFilter('price_max', '') });
  if (q) activeFilters.push({ key: 'q', label: `\u201c${q}\u201d`, onRemove: () => { updateFilter('q', ''); setSearchQuery(''); } });

  // ─── Filter panel (shared between sidebar + mobile drawer) ───
  const FilterContent = ({ mobile = false }: { mobile?: boolean }) => (
    <div className={`${mobile ? 'p-5' : ''} space-y-5`}>
      {/* Search (sidebar only) */}
      {!mobile && (
        <div>
          <label className="block text-xs font-bold text-stone-500 tracking-wider mb-2">
            {L('\u0628\u062d\u062b', 'Search')}
          </label>
          <div className="relative">
            <Search className={`absolute top-1/2 -translate-y-1/2 w-4 h-4 text-stone-400 ${isAr ? 'right-3' : 'left-3'}`} />
            <input value={searchQuery} onChange={e => handleSearch(e.target.value)}
              placeholder={L('\u0627\u0628\u062d\u062b \u0639\u0646 \u0639\u0642\u0627\u0631...', 'Search properties...')}
              className="w-full rounded-xl border border-beige-dark/30 bg-white/90 px-9 py-2.5 text-sm text-stone-900 placeholder:text-stone-400 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all" />
            {searchQuery && (
              <button onClick={() => { setSearchQuery(''); updateFilter('q', ''); }}
                className={`absolute top-1/2 -translate-y-1/2 ${isAr ? 'left-3' : 'right-3'} text-stone-400 hover:text-stone-600`}>
                <X className="w-3.5 h-3.5" />
              </button>
            )}
          </div>
        </div>
      )}

      {/* Purpose */}
      <div>
        <label className="block text-xs font-bold text-stone-500 tracking-wider mb-2.5">
          {L('\u0646\u0648\u0639 \u0627\u0644\u0635\u0641\u0642\u0629', 'Purpose')}
        </label>
        <div className="flex gap-2 bg-beige/30 rounded-xl p-1">
          <button onClick={() => updateFilter('purpose', purpose === 'sale' ? '' : 'sale')}
            className={`flex-1 px-4 py-2.5 rounded-lg text-sm font-medium transition-all ${
              purpose === 'sale'
                ? 'bg-white text-primary shadow-sm border border-primary/10'
                : 'text-stone-500 hover:text-stone-700'
            }`}>
            {L('\u0644\u0644\u0628\u064a\u0639', 'For Sale')}
          </button>
          <button onClick={() => updateFilter('purpose', purpose === 'rent' ? '' : 'rent')}
            className={`flex-1 px-4 py-2.5 rounded-lg text-sm font-medium transition-all ${
              purpose === 'rent'
                ? 'bg-white text-primary shadow-sm border border-primary/10'
                : 'text-stone-500 hover:text-stone-700'
            }`}>
            {L('\u0644\u0644\u0627\u064a\u062c\u0627\u0631', 'For Rent')}
          </button>
        </div>
      </div>

      {/* Governorate + Area */}
      <div className="grid grid-cols-2 gap-3">
        <div>
          <label className="block text-xs font-bold text-stone-500 tracking-wider mb-2">
            {L('\u0627\u0644\u0645\u062d\u0627\u0641\u0638\u0629', 'Governorate')}
          </label>
          <SelectField value={governorate} onChange={(v) => setGovernorate(v)}
            placeholder={L('\u0627\u0644\u0643\u0644', 'All')}
            options={governorates.map((g) => ({ value: g.slug, label: g.name }))} />
        </div>
        <div>
          <label className="block text-xs font-bold text-stone-500 tracking-wider mb-2">
            {L('\u0627\u0644\u0645\u0646\u0637\u0642\u0629', 'Area')}
          </label>
          <SelectField value={area} onChange={(v) => updateFilter('area', v)}
            placeholder={L('\u0627\u0644\u0643\u0644', 'All')}
            options={(filteredAreas.length > 0 ? filteredAreas : areas).map((a) => ({ value: a.slug, label: a.name }))} />
        </div>
      </div>

      {/* Type */}
      <div>
        <label className="block text-xs font-bold text-stone-500 tracking-wider mb-2">
          {L('\u0646\u0648\u0639 \u0627\u0644\u0639\u0642\u0627\u0631', 'Property Type')}
        </label>
        <SelectField value={type} onChange={(v) => updateFilter('type', v)}
          placeholder={L('\u062c\u0645\u064a\u0639 \u0627\u0644\u0623\u0646\u0648\u0627\u0639', 'All Types')}
          options={Array.isArray(propertyTypes) ? propertyTypes.map((pt: PropertyType) => ({ value: pt.slug, label: pt.name })) : []} />
      </div>

      {/* Bedrooms + Bathrooms */}
      <div className="grid grid-cols-2 gap-3">
        <div>
          <label className="block text-xs font-bold text-stone-500 tracking-wider mb-2 flex items-center gap-1">
            <Bed className="w-3 h-3" /> {L('\u063a\u0631\u0641 \u0627\u0644\u0646\u0648\u0645', 'Bedrooms')}
          </label>
          <SelectField value={bedrooms} onChange={(v) => updateFilter('bedrooms', v)}
            options={BEDROOM_OPTIONS} />
        </div>
        <div>
          <label className="block text-xs font-bold text-stone-500 tracking-wider mb-2 flex items-center gap-1">
            <Bath className="w-3 h-3" /> {L('\u0627\u0644\u062d\u0645\u0627\u0645\u0627\u062a', 'Bathrooms')}
          </label>
          <SelectField value={bathrooms} onChange={(v) => updateFilter('bathrooms', v)}
            options={BATHROOM_OPTIONS} />
        </div>
      </div>

      {/* Price range */}
      <div>
        <label className="block text-xs font-bold text-stone-500 tracking-wider mb-2 flex items-center gap-1">
          <DollarSign className="w-3 h-3" /> {L('\u0646\u0637\u0627\u0642 \u0627\u0644\u0633\u0639\u0631', 'Price Range')}
        </label>
        <div className="grid grid-cols-2 gap-2">
          <input value={priceMin} onChange={e => updateFilter('price_min', e.target.value)}
            type="number" min="0" placeholder={L('\u0645\u0646', 'Min')}
            className="w-full rounded-xl border border-beige-dark/30 bg-white/90 px-3 py-2.5 text-sm text-stone-900 placeholder:text-stone-400 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all" />
          <input value={priceMax} onChange={e => updateFilter('price_max', e.target.value)}
            type="number" min="0" placeholder={L('\u0625\u0644\u0649', 'Max')}
            className="w-full rounded-xl border border-beige-dark/30 bg-white/90 px-3 py-2.5 text-sm text-stone-900 placeholder:text-stone-400 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all" />
        </div>
      </div>
    </div>
  );

  return (
    <div dir={isAr ? 'rtl' : 'ltr'} className="bg-warm min-h-screen">
      <Navbar />

      {/* ═══════ MOBILE FILTER DRAWER ═══════ */}
      {filtersOpen && (
        <>
          <div className="fixed inset-0 bg-black/40 z-50" onClick={() => setFiltersOpen(false)} />
          <div className={`fixed top-0 ${isAr ? 'right-0' : 'left-0'} h-full w-[85vw] max-w-sm bg-white z-50 shadow-2xl overflow-y-auto`}>
            <div className="sticky top-0 bg-white border-b border-beige-dark/20 p-4 flex items-center justify-between z-10">
              <h2 className="font-bold text-stone-900 flex items-center gap-2">
                <Filter className="w-4 h-4" /> {L('\u062a\u0635\u0641\u064a\u0629', 'Filters')}
              </h2>
              <div className="flex items-center gap-2">
                {hasActiveFilters && (
                  <button onClick={clearFilters} className="text-xs text-red-500 hover:text-red-600 flex items-center gap-1">
                    <RotateCcw className="w-3 h-3" /> {L('\u0625\u0639\u0627\u062f\u0629 \u062a\u0639\u064a\u064a\u0646', 'Reset')}
                  </button>
                )}
                <button onClick={() => setFiltersOpen(false)} className="p-1.5 rounded-lg hover:bg-beige">
                  <X className="w-5 h-5" />
                </button>
              </div>
            </div>
            <FilterContent mobile />
            <div className="p-4 border-t border-beige-dark/20">
              <button onClick={() => setFiltersOpen(false)}
                className="w-full bg-primary text-white rounded-xl py-3 text-sm font-bold hover:bg-primary-dark transition-all shadow-sm">
                {L('\u0639\u0631\u0636 \u0627\u0644\u0646\u062a\u0627\u0626\u062c', 'Show Results')} ({total})
              </button>
            </div>
          </div>
        </>
      )}

      {/* ═══════ PAGE HEADER ═══════ */}
      <div className="pt-24 pb-0">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="mb-6">
            <h1 className="text-3xl md:text-4xl font-bold text-primary">
              {L('\u0627\u0644\u0639\u0642\u0627\u0631\u0627\u062a', 'Properties')}
            </h1>
            <p className="text-stone-500 text-sm mt-1">
              {total > 0
                ? `${total.toLocaleString()} ${L('\u0639\u0642\u0627\u0631', 'property')}`
                : ''}
            </p>
          </div>
        </div>
      </div>

      {/* ═══════ MAIN CONTENT ═══════ */}
      <div className="pb-16">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          {/* Toolbar: chips + sort + view toggle */}
          <div className="flex flex-wrap items-center gap-3 mb-6">
            {/* Active filter chips */}
            <div className="flex flex-wrap items-center gap-2 flex-1 min-w-0">
              {activeFilters.map((f) => (
                <span key={f.key}
                  className="inline-flex items-center gap-1.5 bg-white text-primary text-xs font-medium px-3 py-1.5 rounded-full border border-primary/15 shadow-sm">
                  {f.label}
                  <button onClick={f.onRemove} className="hover:bg-primary/10 rounded-full p-0.5 transition-colors">
                    <X className="w-3 h-3" />
                  </button>
                </span>
              ))}
              {activeFilters.length > 0 && (
                <button onClick={clearFilters}
                  className="text-xs text-stone-400 hover:text-red-500 transition-colors px-2 py-1 flex items-center gap-1">
                  <RotateCcw className="w-3 h-3" /> {L('\u0645\u0633\u062d \u0627\u0644\u0643\u0644', 'Clear')}
                </button>
              )}
              {activeFilters.length === 0 && !loading && total > 0 && (
                <span className="text-xs text-stone-400">
                  {L('\u062c\u0645\u064a\u0639 \u0627\u0644\u0639\u0642\u0627\u0631\u0627\u062a \u0627\u0644\u0645\u062a\u0627\u062d\u0629', 'All available properties')}
                </span>
              )}
            </div>

            {/* Sort + view */}
            <div className="flex items-center gap-2 shrink-0">
              <div className="hidden sm:flex items-center gap-1.5 bg-white rounded-xl border border-beige-dark/30 px-3 py-1.5 shadow-sm">
                <span className="text-xs text-stone-400">{L('\u062a\u0631\u062a\u064a\u0628:', 'Sort:')}</span>
                <SelectField value={sort} onChange={(v) => updateFilter('sort', v)}
                  options={SORT_OPTIONS.map((o) => ({ value: o.value, label: t(o.key) }))}
                  className="w-32" size="sm" />
              </div>

              <div className="bg-white border border-beige-dark/30 rounded-xl p-1 flex gap-1 shadow-sm">
                <button onClick={() => setViewMode('grid')}
                  className={`p-2 rounded-lg transition-all ${viewMode === 'grid' ? 'bg-primary text-white shadow-sm' : 'text-stone-400 hover:text-stone-600'}`}
                  title={L('\u0639\u0631\u0636 \u0634\u0628\u0643\u064a', 'Grid view')}>
                  <Grid3X3 className="w-4 h-4" />
                </button>
                <button onClick={() => setViewMode('list')}
                  className={`p-2 rounded-lg transition-all ${viewMode === 'list' ? 'bg-primary text-white shadow-sm' : 'text-stone-400 hover:text-stone-600'}`}
                  title={L('\u0639\u0631\u0636 \u0642\u0627\u0626\u0645\u0629', 'List view')}>
                  <List className="w-4 h-4" />
                </button>
              </div>

              {/* Mobile filter toggle */}
              <button onClick={() => setFiltersOpen(true)}
                className="lg:hidden px-3 py-2.5 rounded-xl bg-white border border-beige-dark/30 hover:bg-beige/50 transition-all text-stone-600 shadow-sm">
                <SlidersHorizontal className="w-4 h-4" />
              </button>
            </div>
          </div>

          {/* Loading indicator */}
          {loading && (
            <div className="flex items-center justify-center py-8">
              <div className="flex items-center gap-3 bg-white rounded-2xl px-6 py-3 shadow-sm border border-beige-dark/20">
                <Loader2 className="w-5 h-5 animate-spin text-primary" />
                <span className="text-sm text-stone-500">{L('\u062c\u0627\u0631\u064a \u0627\u0644\u062a\u062d\u0645\u064a\u0644...', 'Loading...')}</span>
              </div>
            </div>
          )}

          {/* ═══ BODY LAYOUT ═══ */}
          <div className="flex gap-8">
            {/* Sidebar filters — desktop */}
            <aside className="hidden lg:block w-72 shrink-0">
              <div className="sticky top-24 bg-white rounded-2xl border border-beige-dark/30 p-5 shadow-sm">
                <div className="flex items-center justify-between mb-5 pb-4 border-b border-beige-dark/20">
                  <h3 className="font-bold text-stone-900 flex items-center gap-2">
                    <Filter className="w-4 h-4 text-primary" /> {L('\u062a\u0635\u0641\u064a\u0629', 'Filters')}
                  </h3>
                  {hasActiveFilters && (
                    <button onClick={clearFilters}
                      className="text-xs text-red-500 hover:text-red-600 flex items-center gap-1 font-medium">
                      <RotateCcw className="w-3 h-3" /> {L('\u0625\u0639\u0627\u062f\u0629', 'Reset')}
                    </button>
                  )}
                </div>
                <FilterContent />
              </div>
            </aside>

            {/* Results */}
            <div className="flex-1 min-w-0">
              {!loading && properties.length === 0 ? (
                /* ─── Empty state ─── */
                <div className="text-center py-16 bg-white rounded-2xl border border-beige-dark/30 shadow-sm">
                  <div className="w-20 h-20 rounded-full bg-beige flex items-center justify-center mx-auto mb-5">
                    <Building2 className="w-8 h-8 text-stone-300" />
                  </div>
                  <p className="text-stone-500 text-lg font-medium mb-2">
                    {L('\u0644\u0627 \u062a\u0648\u062c\u062f \u0639\u0642\u0627\u0631\u0627\u062a', 'No properties found')}
                  </p>
                  <p className="text-stone-400 text-sm mb-6 max-w-md mx-auto">
                    {L('\u062d\u0627\u0648\u0644 \u062a\u063a\u064a\u064a\u0631 \u0645\u0639\u0627\u064a\u064a\u0631 \u0627\u0644\u0628\u062d\u062b \u0623\u0648 \u062a\u0642\u0644\u064a\u0644 \u0639\u062f\u062f \u0627\u0644\u0641\u0644\u0627\u062a\u0631', 'Try adjusting your search criteria or remove some filters')}
                  </p>
                  {hasActiveFilters && (
                    <button onClick={clearFilters}
                      className="inline-flex items-center gap-2 bg-primary text-white rounded-xl px-6 py-2.5 text-sm font-bold hover:bg-primary-dark transition-all shadow-sm">
                      <RotateCcw className="w-4 h-4" /> {L('\u0625\u0639\u0627\u062f\u0629 \u062a\u0639\u064a\u064a\u0646 \u0627\u0644\u062a\u0635\u0641\u064a\u0629', 'Reset Filters')}
                    </button>
                  )}
                </div>
              ) : !loading && properties.length > 0 && (
                <>
                  {/* Grid / List */}
                  <div className={
                    viewMode === 'grid'
                      ? 'grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6'
                      : 'space-y-4'
                  }>
                    {properties.map((p, idx) => (
                      <div key={p.id} className="animate-fadeIn" style={{ animationDelay: `${(idx % 6) * 60}ms` }}>
                        {viewMode === 'list' ? (
                          <ListCard property={p} isAr={isAr} L={L} />
                        ) : (
                          <GridCard property={p} isAr={isAr} L={L} t={t} />
                        )}
                      </div>
                    ))}
                  </div>

                  {/* ─── Pagination ─── */}
                  {lastPage > 1 && (
                    <div className="flex flex-col sm:flex-row items-center justify-between gap-4 mt-12 pt-6 border-t border-beige-dark/20">
                      <p className="text-xs text-stone-400">
                        {L(`\u0635\u0641\u062d\u0629 ${page} \u0645\u0646 ${lastPage}`, `Page ${page} of ${lastPage}`)}
                        {' \u00b7 '}
                        <span className="font-medium text-stone-500">{total.toLocaleString()}</span> {L('\u0639\u0642\u0627\u0631', 'properties')}
                      </p>
                      <div className="flex items-center gap-1.5">
                        <button onClick={() => setPage(p => Math.max(1, p - 1))}
                          disabled={page === 1}
                          className="w-9 h-9 rounded-xl border border-beige-dark/30 hover:bg-beige disabled:opacity-30 disabled:cursor-not-allowed transition-all flex items-center justify-center text-stone-500 bg-white shadow-sm">
                          <ChevronLeft className="w-4 h-4 lucide-rtl" />
                        </button>
                        {(() => {
                          const pages: (number | 'ellipsis')[] = [];
                          if (lastPage <= 7) {
                            for (let i = 1; i <= lastPage; i++) pages.push(i);
                          } else {
                            pages.push(1);
                            if (page > 3) pages.push('ellipsis');
                            for (let i = Math.max(2, page - 1); i <= Math.min(lastPage - 1, page + 1); i++) pages.push(i);
                            if (page < lastPage - 2) pages.push('ellipsis');
                            pages.push(lastPage);
                          }
                          return pages.map((pNum, i) =>
                            pNum === 'ellipsis' ? (
                              <span key={`e${i}`} className="px-1 text-stone-300 text-sm">...</span>
                            ) : (
                              <button key={pNum} onClick={() => setPage(pNum)}
                                className={`w-9 h-9 rounded-xl text-xs font-bold transition-all ${
                                  page === pNum
                                    ? 'bg-primary text-white shadow-sm'
                                    : 'border border-beige-dark/30 hover:bg-beige text-stone-500 bg-white shadow-sm'
                                }`}>
                                {pNum}
                              </button>
                            )
                          );
                        })()}
                        <button onClick={() => setPage(p => Math.min(lastPage, p + 1))}
                          disabled={page === lastPage}
                          className="w-9 h-9 rounded-xl border border-beige-dark/30 hover:bg-beige disabled:opacity-30 disabled:cursor-not-allowed transition-all flex items-center justify-center text-stone-500 bg-white shadow-sm">
                          <ChevronRight className="w-4 h-4 lucide-rtl" />
                        </button>
                      </div>
                    </div>
                  )}
                </>
              )}

              {/* Skeleton loading */}
              {loading && (
                <div className={viewMode === 'grid'
                  ? 'grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6'
                  : 'space-y-4'
                }>
                  {Array.from({ length: 9 }).map((_, i) => (
                    <div key={i} className={`rounded-2xl bg-white border border-beige-dark/20 overflow-hidden animate-pulse ${
                      viewMode === 'list' ? 'flex h-40' : ''
                    }`}>
                      <div className={viewMode === 'list' ? 'w-56 h-full shrink-0 shimmer' : 'aspect-[4/3] shimmer'} />
                      {viewMode === 'list' && (
                        <div className="flex-1 p-5 space-y-3">
                          <div className="h-5 shimmer rounded w-3/4" />
                          <div className="h-4 shimmer rounded w-1/2" />
                          <div className="h-3 shimmer rounded w-2/3" />
                        </div>
                      )}
                      {viewMode !== 'list' && (
                        <div className="p-4 space-y-3">
                          <div className="h-4 shimmer rounded w-3/4" />
                          <div className="h-3 shimmer rounded w-1/2" />
                          <div className="h-3 shimmer rounded w-2/3" />
                        </div>
                      )}
                    </div>
                  ))}
                </div>
              )}
            </div>
          </div>
        </div>
      </div>

      <Footer />
    </div>
  );
}

// ─── Premium Grid Card ───
function GridCard({ property, isAr, L, t }: {
  property: PropertyCardType;
  isAr: boolean;
  L: (ar: string, en: string) => string;
  t: (key: string) => string;
}) {
  const [imgError, setImgError] = useState(false);
  const title = isAr ? property.title_ar : property.title_en;
  const price = Number(property.price).toLocaleString();
  const isRent = property.purpose === 'rent';

  return (
    <Link to={`/properties/${property.slug}`}
      className="group/card bg-white rounded-2xl overflow-hidden border border-beige-dark/20 shadow-sm hover:border-primary/20 transition-all duration-300 hover:shadow-xl hover:shadow-primary/5 hover:-translate-y-1 block">
      {/* Image */}
      <div className="relative aspect-[4/3] overflow-hidden bg-beige">
        {property.cover_image?.path && !imgError ? (
          <img src={property.cover_image.path} alt={title}
            className="w-full h-full object-cover group-hover/card:scale-105 transition-transform duration-500"
            loading="lazy"
            onError={() => setImgError(true)} />
        ) : (
          <div className="w-full h-full flex items-center justify-center text-stone-300">
            <Home className="w-12 h-12" />
          </div>
        )}

        {/* Featured badge */}
        {property.is_featured && (
          <div className="absolute top-3 ltr:left-3 rtl:right-3">
            <span className="bg-gold text-white text-[10px] font-bold px-2 py-1 rounded-md shadow-md">
              {L('\u0645\u0645\u064a\u0632', 'Featured')}
            </span>
          </div>
        )}

        {/* Purpose badge */}
        <div className="absolute top-3 ltr:right-3 rtl:left-3">
          <span className={`text-white text-[10px] font-bold px-2 py-1 rounded-md shadow-md ${
            isRent ? 'bg-blue-500' : 'bg-emerald-500'
          }`}>
            {isRent ? t('property.purpose.rent') : t('property.purpose.sale')}
          </span>
        </div>

        {/* Price overlay */}
        <div className="absolute bottom-3 ltr:left-3 rtl:right-3">
          <span className="inline-flex items-center bg-white/90 backdrop-blur-sm text-primary font-bold text-sm px-2.5 py-1 rounded-lg shadow-sm gap-0.5">
            ${price}
            {isRent && <span className="text-stone-400 text-[10px] font-normal">/{t('property.perMonth')}</span>}
          </span>
        </div>
      </div>

      {/* Content */}
      <div className="p-3.5 md:p-4">
        <h3 className="text-stone-800 font-bold text-sm leading-snug line-clamp-1 group-hover/card:text-primary transition-colors">
          {title}
        </h3>

        <div className="flex items-center gap-1 text-stone-400 text-[11px] mt-1 mb-2.5">
          <MapPin className="w-3 h-3 shrink-0" />
          <span className="truncate">{property.governorate?.name ?? ''}</span>
        </div>

        {/* Stats row */}
        <div className="flex items-center gap-3 pt-2.5 border-t border-beige-dark/20 text-stone-500 text-[11px]">
          <span className="flex items-center gap-1">
            <Maximize2 className="w-3 h-3" /> {property.area_sqm}
          </span>
          <span className="flex items-center gap-1">
            <Bed className="w-3 h-3" /> {property.bedrooms}
          </span>
          <span className="flex items-center gap-1">
            <Bath className="w-3 h-3" /> {property.bathrooms}
          </span>
        </div>
      </div>
    </Link>
  );
}

// ─── Premium List Card ───
function ListCard({ property, isAr, L }: { property: PropertyCardType; isAr: boolean; L: (ar: string, en: string) => string }) {
  const [imgError, setImgError] = useState(false);
  const imgSrc = property.cover_image?.path || '';
  const title = isAr ? property.title_ar : property.title_en;
  const price = Number(property.price).toLocaleString();
  const isRent = property.purpose === 'rent';

  return (
    <Link to={`/properties/${property.slug}`}
      className="flex bg-white rounded-2xl border border-beige-dark/20 overflow-hidden hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300 group shadow-sm">
      <div className="w-48 md:w-56 shrink-0 bg-stone-100 relative overflow-hidden">
        {imgSrc && !imgError ? (
          <img src={imgSrc} alt={title}
            className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
            onError={() => setImgError(true)} />
        ) : (
          <div className="w-full h-full flex items-center justify-center text-stone-300">
            <Building2 className="w-8 h-8" />
          </div>
        )}
        {property.is_featured && (
          <span className="absolute top-2 ltr:left-2 rtl:right-2 bg-gold text-white text-[10px] font-bold px-2 py-0.5 rounded-md shadow-sm">
            {L('\u0645\u0645\u064a\u0632', 'Featured')}
          </span>
        )}
      </div>
      <div className="flex-1 p-4 md:p-5 flex flex-col justify-between min-w-0">
        <div>
          <h3 className="font-bold text-stone-900 text-sm md:text-base truncate group-hover:text-primary transition-colors">{title}</h3>
          <div className="flex items-center gap-1.5 text-xs text-stone-400 mt-1">
            <MapPin className="w-3 h-3" />
            <span className="truncate">{property.governorate?.name}{property.area?.name ? `, ${property.area.name}` : ''}</span>
          </div>
          <div className="flex items-center gap-4 mt-2.5 text-xs text-stone-500">
            <span className="flex items-center gap-1"><Maximize2 className="w-3 h-3" />{property.area_sqm} \u0645\u00b2</span>
            {property.bedrooms > 0 && (
              <span className="flex items-center gap-1"><Bed className="w-3 h-3" />{property.bedrooms}</span>
            )}
            {property.bathrooms > 0 && (
              <span className="flex items-center gap-1"><Bath className="w-3 h-3" />{property.bathrooms}</span>
            )}
          </div>
        </div>
        <div className="flex items-center justify-between mt-3 pt-3 border-t border-beige-dark/20">
          <div className="text-lg font-bold text-primary">
            ${price}
            {isRent && <span className="text-xs text-stone-400 font-normal">/{L('\u0634\u0647\u0631', 'mo')}</span>}
          </div>
          <span className={`text-[11px] font-bold px-2.5 py-1 rounded-lg ${
            isRent ? 'bg-blue-50 text-blue-600' : 'bg-emerald-50 text-emerald-600'
          }`}>
            {isRent ? L('\u0644\u0644\u0627\u064a\u062c\u0627\u0631', 'For Rent') : L('\u0644\u0644\u0628\u064a\u0639', 'For Sale')}
          </span>
        </div>
      </div>
    </Link>
  );
}
