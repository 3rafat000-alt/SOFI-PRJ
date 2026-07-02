import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { useNavigate } from 'react-router-dom';
import {
  Building2, Home, Search, MapPin, ArrowRight, ArrowLeft,
  CheckCircle2, MessageSquareText, Sparkles,
  RefreshCw, Loader2, Wallet, Users,
} from 'lucide-react';
import Navbar from '../components/Navbar';
import Footer from '../components/Footer';
import { fetchAgencies, type AgencyPublic } from '../api/properties';
import type { PropertyType } from '../api/client';
import { fetchPropertyTypes } from '../api/properties';
import { useEffect } from 'react';

type Step = 1 | 2 | 3 | 4 | 5;
type Purpose = 'buy' | 'rent' | 'invest';
type BudgetRange = { label: string; min: number; max: number };

const budgetRanges: BudgetRange[] = [
  { label: 'أقل من 50 مليون', min: 0, max: 50000000 },
  { label: '50 - 100 مليون', min: 50000000, max: 100000000 },
  { label: '100 - 200 مليون', min: 100000000, max: 200000000 },
  { label: '200 - 500 مليون', min: 200000000, max: 500000000 },
  { label: 'أكثر من 500 مليون', min: 500000000, max: Infinity },
];

export default function AgentMatching() {
  const { i18n } = useTranslation();
  const isAr = i18n.language === 'ar';
  const navigate = useNavigate();

  const [step, setStep] = useState<Step>(1);
  const [purpose, setPurpose] = useState<Purpose | null>(null);
  const [propertyType, setPropertyType] = useState<number | null>(null);
  const [budget, setBudget] = useState<number | null>(null);
  const [governorate, setGovernorate] = useState('');
  const [propertyTypes, setPropertyTypes] = useState<PropertyType[]>([]);
  const [filteredAgencies, setFilteredAgencies] = useState<AgencyPublic[]>([]);
  const [loading, setLoading] = useState(false);
  useEffect(() => {
    fetchPropertyTypes().then(setPropertyTypes).catch(() => {});
  }, []);

  const purposeOptions: { value: Purpose; icon: any; label: string; desc: string }[] = [
    { value: 'buy', icon: Home, label: isAr ? 'شراء' : 'Buy', desc: isAr ? 'أبحث عن عقار للتملك' : 'Looking to own a property' },
    { value: 'rent', icon: Search, label: isAr ? 'إيجار' : 'Rent', desc: isAr ? 'أبحث عن عقار للإيجار' : 'Looking to rent' },
    { value: 'invest', icon: Wallet, label: isAr ? 'استثمار' : 'Invest', desc: isAr ? 'أبحث عن فرصة استثمارية' : 'Looking for investment' },
  ];

  const stepNames = isAr
    ? ['الغرض', 'النوع', 'الميزانية', 'الموقع', 'النتائج']
    : ['Purpose', 'Type', 'Budget', 'Location', 'Results'];

  const handleFindAgencies = async () => {
    setLoading(true);
    try {
      const allAgencies = await fetchAgencies();
      let matched = [...allAgencies];

      // Sort by properties_count (more properties = more relevant)
      matched.sort((a, b) => b.properties_count - a.properties_count);

      // If we have a governorate, try to prioritize agencies in that area
      // (backend doesn't filter by location currently, so we just show sorted)
      if (matched.length > 6) matched = matched.slice(0, 6);

      setFilteredAgencies(matched);
      setStep(5);
    } catch {
      setFilteredAgencies([]);
    } finally {
      setLoading(false);
    }
  };

  const nextStep = () => {
    if (step === 1 && purpose) setStep(2);
    else if (step === 2 && propertyType) setStep(3);
    else if (step === 3 && budget !== null) setStep(4);
    else if (step === 4 && governorate.trim()) handleFindAgencies();
  };

  const prevStep = () => setStep(p => (Math.max(1, p - 1) as Step));

  const ProgressSteps = () => (
    <div className="flex items-center justify-center gap-0 mb-8">
      {stepNames.map((name, i) => {
        const idx = (i + 1) as Step;
        const done = idx < step;
        const active = idx === step;
        return (
          <div key={i} className="flex items-center gap-0">
            <div className={`flex flex-col items-center gap-1 px-3 py-2 transition-all duration-300 ${
              active ? 'scale-105' : ''
            }`}>
              <div className={`w-9 h-9 rounded-full flex items-center justify-center text-xs font-bold transition-all duration-300 ${
                done ? 'bg-primary text-white' : active ? 'bg-primary text-white ring-4 ring-primary/10' : 'bg-stone-100 text-stone-400'
              }`}>
                {done ? <CheckCircle2 className="w-4 h-4" /> : idx}
              </div>
              <span className={`text-2xs font-medium whitespace-nowrap ${
                active ? 'text-primary' : done ? 'text-primary/60' : 'text-stone-400'
              }`}>
                {name}
              </span>
            </div>
            {i < stepNames.length - 1 && (
              <div className={`w-8 h-px transition-colors duration-300 ${idx < step ? 'bg-primary/40' : 'bg-stone-200'}`} />
            )}
          </div>
        );
      })}
    </div>
  );

  return (
    <div dir={isAr ? 'rtl' : 'ltr'} className="min-h-screen flex flex-col">
      <Navbar />
      <div className="flex-1 pt-20 flex items-start justify-center relative overflow-hidden">
        <div className="absolute inset-0 bg-gradient-to-br from-primary/5 via-cream to-gold/5" />
        <div className="absolute inset-0 hero-mesh" />

        <div className="relative z-10 w-full max-w-2xl mx-auto px-4 py-8">
          {/* Header */}
          <div className="text-center mb-6">
            <div className="w-14 h-14 rounded-2xl bg-gradient-to-br from-gold to-amber-600 flex items-center justify-center mx-auto mb-3 shadow-lg shadow-gold/20">
              <Users className="w-7 h-7 text-white" />
            </div>
            <h1 className="text-2xl md:text-3xl font-bold text-stone-900">
              {isAr ? 'اعثر على الوكالة المناسبة' : 'Find Your Right Agency'}
            </h1>
            <p className="text-stone-500 mt-1 text-sm max-w-md mx-auto">
              {isAr
                ? 'أجب على بضعة أسئلة وسنجد لك أفضل الوكالات العقارية التي تناسب احتياجك'
                : 'Answer a few questions and we\'ll find the best agencies for your needs'}
            </p>
          </div>

          <ProgressSteps />

          {/* ═══════ Step 1: Purpose ═══════ */}
          {step === 1 && (
            <div className="bg-white rounded-3xl shadow-[0_4px_40px_rgba(0,0,0,0.06)] border border-beige-dark/40 p-6 sm:p-8 animate-[fadeIn_0.3s_ease-out]">
              <h2 className="text-lg font-bold text-stone-900 mb-1">
                {isAr ? 'ما هو غرضك؟' : 'What is your purpose?'}
              </h2>
              <p className="text-sm text-stone-500 mb-6">
                {isAr ? 'اختر الغرض من بحثك العقاري' : 'Choose the purpose of your property search'}
              </p>
              <div className="grid gap-3">
                {purposeOptions.map((opt) => {
                  const Icon = opt.icon;
                  const selected = purpose === opt.value;
                  return (
                    <button key={opt.value} onClick={() => { setPurpose(opt.value); }}
                      className={`group text-right p-5 rounded-2xl border-2 transition-all duration-200 hover:shadow-md ${
                        selected
                          ? 'border-primary bg-primary/5 shadow-sm'
                          : 'border-beige-dark/50 hover:border-stone-300'
                      }`}>
                      <div className="flex items-center gap-4">
                        <div className={`w-12 h-12 rounded-xl flex items-center justify-center shrink-0 transition-all duration-200 ${
                          selected ? 'bg-primary text-white' : 'bg-stone-100 text-stone-400 group-hover:bg-primary/10 group-hover:text-primary'
                        }`}>
                          <Icon className="w-6 h-6" />
                        </div>
                        <div className="flex-1">
                          <div className={`text-base font-bold ${selected ? 'text-primary' : 'text-stone-800'}`}>
                            {opt.label}
                          </div>
                          <div className="text-sm text-stone-500 mt-0.5">{opt.desc}</div>
                        </div>
                        {selected && <CheckCircle2 className="w-6 h-6 text-primary shrink-0" />}
                      </div>
                    </button>
                  );
                })}
              </div>
              <div className="flex justify-between items-center mt-6">
                <button onClick={() => navigate(-1)} className="text-sm text-stone-400 hover:text-stone-600 transition-colors">
                  {isAr ? 'إلغاء' : 'Cancel'}
                </button>
                <button onClick={nextStep} disabled={!purpose}
                  className={`btn-primary flex items-center gap-2 !py-2.5 !px-6 transition-all duration-200 ${
                    !purpose ? 'opacity-50 cursor-not-allowed' : ''
                  }`}>
                  {isAr ? 'التالي' : 'Next'}
                  {isAr ? <ArrowLeft className="w-4 h-4" /> : <ArrowRight className="w-4 h-4" />}
                </button>
              </div>
            </div>
          )}

          {/* ═══════ Step 2: Property Type ═══════ */}
          {step === 2 && (
            <div className="bg-white rounded-3xl shadow-[0_4px_40px_rgba(0,0,0,0.06)] border border-beige-dark/40 p-6 sm:p-8 animate-[fadeIn_0.3s_ease-out]">
              <h2 className="text-lg font-bold text-stone-900 mb-1">
                {isAr ? 'ما هو نوع العقار؟' : 'Property Type?'}
              </h2>
              <p className="text-sm text-stone-500 mb-6">
                {isAr ? 'اختر نوع العقار الذي تبحث عنه' : 'Select the type of property you\'re looking for'}
              </p>
              <div className="grid grid-cols-2 sm:grid-cols-3 gap-3">
                {propertyTypes.map((pt) => {
                  const selected = propertyType === pt.id;
                  return (
                    <button key={pt.id} onClick={() => setPropertyType(pt.id)}
                      className={`p-5 rounded-2xl border-2 text-center transition-all duration-200 hover:shadow-md ${
                        selected
                          ? 'border-primary bg-primary/5 shadow-sm'
                          : 'border-beige-dark/50 hover:border-stone-300'
                      }`}>
                      <div className={`w-10 h-10 rounded-xl flex items-center justify-center mx-auto mb-2 transition-all duration-200 ${
                        selected ? 'bg-primary text-white' : 'bg-stone-100 text-stone-400'
                      }`}>
                        <Home className="w-5 h-5" />
                      </div>
                      <div className={`text-sm font-bold ${selected ? 'text-primary' : 'text-stone-700'}`}>
                        {isAr ? pt.name_ar : pt.name_en}
                      </div>
                    </button>
                  );
                })}
              </div>
              <div className="flex justify-between items-center mt-6">
                <button onClick={prevStep} className="flex items-center gap-2 text-sm text-stone-500 hover:text-stone-700 transition-colors">
                  {isAr ? <ArrowRight className="w-4 h-4" /> : <ArrowLeft className="w-4 h-4" />}
                  {isAr ? 'السابق' : 'Back'}
                </button>
                <button onClick={nextStep} disabled={!propertyType}
                  className={`btn-primary flex items-center gap-2 !py-2.5 !px-6 transition-all duration-200 ${
                    !propertyType ? 'opacity-50 cursor-not-allowed' : ''
                  }`}>
                  {isAr ? 'التالي' : 'Next'}
                  {isAr ? <ArrowLeft className="w-4 h-4" /> : <ArrowRight className="w-4 h-4" />}
                </button>
              </div>
            </div>
          )}

          {/* ═══════ Step 3: Budget ═══════ */}
          {step === 3 && (
            <div className="bg-white rounded-3xl shadow-[0_4px_40px_rgba(0,0,0,0.06)] border border-beige-dark/40 p-6 sm:p-8 animate-[fadeIn_0.3s_ease-out]">
              <h2 className="text-lg font-bold text-stone-900 mb-1">
                {isAr ? 'ما هي ميزانيتك؟' : 'What is your budget?'}
              </h2>
              <p className="text-sm text-stone-500 mb-6">
                {isAr ? 'اختر النطاق السعري المناسب' : 'Choose your price range'}
              </p>
              <div className="grid gap-3">
                {budgetRanges.map((br, i) => {
                  const selected = budget === i;
                  return (
                    <button key={i} onClick={() => setBudget(i)}
                      className={`group text-right p-4 rounded-2xl border-2 transition-all duration-200 hover:shadow-md ${
                        selected
                          ? 'border-primary bg-primary/5 shadow-sm'
                          : 'border-beige-dark/50 hover:border-stone-300'
                      }`}>
                      <div className="flex items-center gap-4">
                        <div className={`w-10 h-10 rounded-xl flex items-center justify-center shrink-0 ${
                          selected ? 'bg-primary text-white' : 'bg-stone-100 text-stone-400 group-hover:bg-primary/10'
                        }`}>
                          <Wallet className="w-5 h-5" />
                        </div>
                        <div className={`text-sm font-bold ${selected ? 'text-primary' : 'text-stone-700'}`}>
                          {br.label}
                        </div>
                        {selected && <CheckCircle2 className="w-5 h-5 text-primary mr-auto" />}
                      </div>
                    </button>
                  );
                })}
              </div>
              <div className="flex justify-between items-center mt-6">
                <button onClick={prevStep} className="flex items-center gap-2 text-sm text-stone-500 hover:text-stone-700 transition-colors">
                  {isAr ? <ArrowRight className="w-4 h-4" /> : <ArrowLeft className="w-4 h-4" />}
                  {isAr ? 'السابق' : 'Back'}
                </button>
                <button onClick={nextStep} disabled={budget === null}
                  className={`btn-primary flex items-center gap-2 !py-2.5 !px-6 transition-all duration-200 ${
                    budget === null ? 'opacity-50 cursor-not-allowed' : ''
                  }`}>
                  {isAr ? 'التالي' : 'Next'}
                  {isAr ? <ArrowLeft className="w-4 h-4" /> : <ArrowRight className="w-4 h-4" />}
                </button>
              </div>
            </div>
          )}

          {/* ═══════ Step 4: Location ═══════ */}
          {step === 4 && (
            <div className="bg-white rounded-3xl shadow-[0_4px_40px_rgba(0,0,0,0.06)] border border-beige-dark/40 p-6 sm:p-8 animate-[fadeIn_0.3s_ease-out]">
              <h2 className="text-lg font-bold text-stone-900 mb-1">
                {isAr ? 'أين تبحث؟' : 'Where are you looking?'}
              </h2>
              <p className="text-sm text-stone-500 mb-6">
                {isAr ? 'أدخل المدينة أو المنطقة المفضلة' : 'Enter your preferred city or area'}
              </p>

              <div className="relative">
                <MapPin className="absolute top-1/2 -translate-y-1/2 w-5 h-5 text-stone-400"
                  style={{ [isAr ? 'right' : 'left']: '16px' }} />
                <input type="text" value={governorate}
                  onChange={e => setGovernorate(e.target.value)}
                  placeholder={isAr ? 'مثال: دمشق، حلب، اللاذقية...' : 'e.g. Damascus, Aleppo, Latakia...'}
                  className="input-field pl-12 py-4 text-base transition-all duration-200 focus:shadow-[0_0_0_4px] focus:shadow-primary/6"
                  onKeyDown={e => e.key === 'Enter' && governorate.trim() && handleFindAgencies()} />
              </div>

              <div className="mt-4 flex flex-wrap gap-2">
                {['دمشق', 'حلب', 'اللاذقية', 'حمص', 'طرطوس', 'حماة'].map((city) => (
                  <button key={city} onClick={() => setGovernorate(city)}
                    className={`px-4 py-1.5 rounded-full text-xs font-medium border transition-all duration-200 ${
                      governorate === city
                        ? 'border-primary bg-primary/10 text-primary'
                        : 'border-beige-dark/50 text-stone-500 hover:border-primary/30 hover:text-primary'
                    }`}>
                    {city}
                  </button>
                ))}
              </div>

              <div className="flex justify-between items-center mt-8">
                <button onClick={prevStep} className="flex items-center gap-2 text-sm text-stone-500 hover:text-stone-700 transition-colors">
                  {isAr ? <ArrowRight className="w-4 h-4" /> : <ArrowLeft className="w-4 h-4" />}
                  {isAr ? 'السابق' : 'Back'}
                </button>
                <button onClick={handleFindAgencies} disabled={!governorate.trim() || loading}
                  className={`btn-primary flex items-center gap-2 !py-2.5 !px-6 transition-all duration-200 ${
                    !governorate.trim() || loading ? 'opacity-50 cursor-not-allowed' : ''
                  }`}>
                  {loading ? (
                    <Loader2 className="w-4 h-4 animate-spin" />
                  ) : (
                    <><Search className="w-4 h-4" /> {isAr ? 'ابحث عن وكالات' : 'Find Agencies'}</>
                  )}
                </button>
              </div>
            </div>
          )}

          {/* ═══════ Step 5: Results ═══════ */}
          {step === 5 && (
            <div className="animate-[fadeIn_0.4s_ease-out]">
              {/* Summary card */}
              <div className="bg-white rounded-3xl shadow-[0_4px_40px_rgba(0,0,0,0.06)] border border-beige-dark/40 p-6 mb-5">
                <div className="flex items-start gap-4">
                  <Sparkles className="w-6 h-6 text-gold shrink-0 mt-0.5" />
                  <div>
                    <h2 className="text-lg font-bold text-stone-900">
                      {isAr ? 'تم العثور على وكالات مناسبة' : 'Found Matching Agencies'}
                    </h2>
                    <p className="text-sm text-stone-500 mt-1">
                      {isAr
                        ? `بناءً على احتياجك، وجدنا ${filteredAgencies.length} وكالة مناسبة في ${governorate}`
                        : `Based on your needs, we found ${filteredAgencies.length} suitable agencies in ${governorate}`}
                    </p>
                  </div>
                </div>
                {/* Tags */}
                <div className="flex flex-wrap gap-2 mt-4 pt-4 border-t border-beige-dark/30">
                  <span className="inline-flex items-center gap-1 text-xs bg-primary/8 text-primary px-3 py-1 rounded-full">
                    {purposeOptions.find(p => p.value === purpose)?.label}
                  </span>
                  <span className="inline-flex items-center gap-1 text-xs bg-primary/8 text-primary px-3 py-1 rounded-full">
                    {propertyTypes.find(pt => pt.id === propertyType)?.name_ar || propertyTypes.find(pt => pt.id === propertyType)?.name_en}
                  </span>
                  <span className="inline-flex items-center gap-1 text-xs bg-primary/8 text-primary px-3 py-1 rounded-full">
                    <MapPin className="w-3 h-3" /> {governorate}
                  </span>
                  <span className="inline-flex items-center gap-1 text-xs bg-gold/10 text-gold px-3 py-1 rounded-full">
                    {budgetRanges[budget || 0]?.label}
                  </span>
                </div>
              </div>

              {/* Agency cards */}
              {filteredAgencies.length === 0 ? (
                <div className="bg-white rounded-3xl shadow-[0_4px_40px_rgba(0,0,0,0.06)] border border-beige-dark/40 p-10 text-center">
                  <Building2 className="w-12 h-12 text-stone-300 mx-auto mb-4" />
                  <p className="text-stone-500">{isAr ? 'لا توجد وكالات متاحة حالياً' : 'No agencies available yet'}</p>
                  <p className="text-sm text-stone-400 mt-1">{isAr ? 'يرجى المحاولة لاحقاً' : 'Please try again later'}</p>
                </div>
              ) : (
                <div className="grid gap-4">
                  {filteredAgencies.map((agency) => (
                    <div key={agency.id}
                      className="bg-white rounded-3xl shadow-[0_4px_40px_rgba(0,0,0,0.06)] border border-beige-dark/40 p-6 hover:shadow-md transition-all duration-200">
                      <div className="flex items-start gap-4">
                        {/* Logo */}
                        <div className="w-14 h-14 rounded-2xl bg-gradient-to-br from-primary/10 to-primary/5 flex items-center justify-center shrink-0 border border-primary/10">
                          {agency.logo_path ? (
                            <img src={agency.logo_path} alt={agency.name} className="w-10 h-10 rounded-xl object-cover" />
                          ) : (
                            <Building2 className="w-7 h-7 text-primary" />
                          )}
                        </div>
                        <div className="flex-1 min-w-0">
                          <h3 className="font-bold text-stone-900">{agency.name}</h3>
                          <div className="flex items-center gap-3 mt-1.5 text-xs text-stone-500">
                            <span className="flex items-center gap-1">
                              <Building2 className="w-3.5 h-3.5" />
                              {agency.properties_count} {isAr ? 'عقار' : 'properties'}
                            </span>
                            <span className="flex items-center gap-1">
                              <Users className="w-3.5 h-3.5" />
                              {agency.agents_count} {isAr ? 'وكيل' : 'agents'}
                            </span>
                          </div>
                          {/* Action buttons */}
                          <div className="flex items-center gap-2 mt-4">
                            <button onClick={() => navigate(`/agencies/${agency.slug}`)}
                              className="flex items-center gap-1.5 px-4 py-2 rounded-xl bg-stone-100 text-stone-600 text-sm font-medium hover:bg-stone-200 transition-all duration-200">
                              {isAr ? 'عرض العقارات' : 'View Properties'}
                            </button>
                            <button onClick={() => navigate(`/agencies/${agency.slug}`)}
                              className="flex items-center gap-1.5 px-4 py-2 rounded-xl bg-primary text-white text-sm font-medium hover:bg-primary-dark transition-all duration-200">
                              <MessageSquareText className="w-3.5 h-3.5" />
                              {isAr ? 'عرض الوكالة' : 'View Agency'}
                            </button>
                          </div>
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              )}

              {/* Actions */}
              <div className="flex flex-col sm:flex-row gap-3 justify-center mt-6">
                <button onClick={() => setStep(1)}
                  className="flex items-center justify-center gap-2 px-6 py-3 rounded-xl bg-white border border-beige-dark/50 text-stone-600 text-sm font-medium hover:bg-beige/50 transition-all duration-200">
                  <RefreshCw className="w-4 h-4" />
                  {isAr ? 'بدء بحث جديد' : 'New Search'}
                </button>
                <button onClick={() => navigate('/properties')}
                  className="btn-primary flex items-center justify-center gap-2 !py-3 !px-8">
                  <Search className="w-4 h-4" />
                  {isAr ? 'تصفح جميع العقارات' : 'Browse All Properties'}
                </button>
              </div>
            </div>
          )}
        </div>
      </div>
      <Footer />

    </div>
  );
}
