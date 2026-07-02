import { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { useNavigate } from 'react-router-dom';
import { Building2, Loader2, ArrowRight, MapPin, DollarSign, Ruler, Bed, Bath, Car, Building } from 'lucide-react';
import { storeAgencyProperty, fetchAgencyAgents } from '../../api/agency';
import type { AgencyAgent } from '../../api/agency';
import client from '../../api/client';

interface PropertyType { id: number; name_ar: string; name_en: string; slug: string; }
interface Governorate { id: number; name_ar: string; name_en: string; }
interface Area { id: number; name_ar: string; name_en: string; }

export default function PropertyCreate() {
  const { i18n } = useTranslation();
  const isAr = i18n.language === 'ar';
  const navigate = useNavigate();
  const L = (ar: string, en: string) => isAr ? ar : en;

  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState('');
  const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});

  const [propertyTypes, setPropertyTypes] = useState<PropertyType[]>([]);
  const [governorates, setGovernorates] = useState<Governorate[]>([]);
  const [areas, setAreas] = useState<Area[]>([]);
  const [agents, setAgents] = useState<AgencyAgent[]>([]);

  const [form, setForm] = useState({
    property_type_id: '',
    purpose: 'sale',
    title_ar: '',
    title_en: '',
    price: '',
    currency: 'USD',
    area_sqm: '',
    bedrooms: '',
    bathrooms: '',
    parking: '',
    floor: '',
    year_built: '',
    furnished: false,
    governorate_id: '',
    area_id: '',
    address_ar: '',
    address_en: '',
    lat: '',
    lng: '',
    description_ar: '',
    description_en: '',
    agent_id: '',
    status: 'draft',
  });

  useEffect(() => {
    client.get('/property-types').then(r => setPropertyTypes(r.data.data));
    client.get('/locations').then(r => {
      const d = r.data.data ?? r.data;
      setGovernorates(Array.isArray(d) ? d : (d.governorates ?? []));
    });
    fetchAgencyAgents().then(setAgents).catch(() => {});
  }, []);

  const loadAreas = (govId: string) => {
    if (!govId) { setAreas([]); return; }
    client.get(`/locations/${govId}/areas`).then(r => {
      const d = r.data.data ?? r.data;
      setAreas(Array.isArray(d) ? d : (d.areas ?? []));
    });
  };

  const handleChange = (field: string, value: any) => {
    setForm(prev => ({ ...prev, [field]: value }));
    if (field === 'governorate_id') { setForm(prev => ({ ...prev, area_id: '' })); loadAreas(value); }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    setFieldErrors({});
    setSubmitting(true);
    try {
      const payload: Record<string, any> = { ...form, price: Number(form.price), area_sqm: Number(form.area_sqm) };
      if (form.bedrooms) payload.bedrooms = Number(form.bedrooms);
      if (form.bathrooms) payload.bathrooms = Number(form.bathrooms);
      if (form.parking) payload.parking = Number(form.parking);
      if (form.floor) payload.floor = Number(form.floor);
      if (form.year_built) payload.year_built = Number(form.year_built);
      if (form.lat) payload.lat = form.lat;
      if (form.lng) payload.lng = form.lng;
      if (!form.agent_id) payload.agent_id = undefined;

      await storeAgencyProperty(payload);
      navigate('/dashboard/properties');
    } catch (err: any) {
      setError(err?.response?.data?.message || err.message || 'Error');
      if (err?.response?.data?.errors) {
        setFieldErrors(Object.fromEntries(
          Object.entries(err.response.data.errors).map(([k, v]) => [k, (v as string[])[0]])
        ));
      }
    } finally {
      setSubmitting(false);
    }
  };

  const inputCls = (field: string) =>
    `input-field ${fieldErrors[field] ? 'border-red-300 focus:ring-red-200' : ''}`;

  return (
    <div>
      <div className="flex items-center gap-3 mb-6">
        <button onClick={() => navigate('/dashboard/properties')} className="p-2 rounded-lg hover:bg-beige text-stone-500">
          <ArrowRight className="w-5 h-5 lucide-rtl" />
        </button>
        <h1 className="text-2xl font-bold text-stone-900">{L('إضافة عقار جديد', 'Add New Property')}</h1>
      </div>

      {error && (
        <div className="bg-red-50 border border-red-100 text-red-600 text-sm rounded-xl px-4 py-3 mb-5">{error}</div>
      )}

      <form onSubmit={handleSubmit} className="space-y-8">
        {/* Basic Info */}
        <section className="card-3d p-6">
          <h2 className="text-lg font-bold text-primary mb-4 flex items-center gap-2">
            <Building2 className="w-5 h-5" /> {L('معلومات أساسية', 'Basic Info')}
          </h2>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label className="text-sm font-medium text-stone-700 mb-1.5 block">{L('نوع العقار', 'Property Type')}</label>
              <select value={form.property_type_id} onChange={e => handleChange('property_type_id', e.target.value)} required className={inputCls('property_type_id')}>
                <option value="">{L('اختر النوع', 'Select type')}</option>
                {propertyTypes.map(pt => <option key={pt.id} value={pt.id}>{isAr ? pt.name_ar : pt.name_en}</option>)}
              </select>
            </div>
            <div>
              <label className="text-sm font-medium text-stone-700 mb-1.5 block">{L('الغرض', 'Purpose')}</label>
              <select value={form.purpose} onChange={e => handleChange('purpose', e.target.value)} className={inputCls('purpose')}>
                <option value="sale">{L('للبيع', 'For Sale')}</option>
                <option value="rent">{L('للإيجار', 'For Rent')}</option>
              </select>
            </div>
            <div>
              <label className="text-sm font-medium text-stone-700 mb-1.5 block">{L('عنوان العقار (عربي)', 'Title (Arabic)')}</label>
              <input type="text" required value={form.title_ar} onChange={e => handleChange('title_ar', e.target.value)} placeholder="مثال: شقة فاخرة في المزة" className={inputCls('title_ar')} />
            </div>
            <div>
              <label className="text-sm font-medium text-stone-700 mb-1.5 block">{L('عنوان العقار (إنجليزي)', 'Title (English)')}</label>
              <input type="text" required value={form.title_en} onChange={e => handleChange('title_en', e.target.value)} placeholder="e.g. Luxury apartment in Mezzeh" className={inputCls('title_en')} />
            </div>
            <div>
              <label className="text-sm font-medium text-stone-700 mb-1.5 block">
                <DollarSign className="w-3.5 h-3.5 inline mr-1" /> {L('السعر', 'Price')}
              </label>
              <div className="flex gap-2">
                <input type="number" required value={form.price} onChange={e => handleChange('price', e.target.value)} placeholder="0" className={`${inputCls('price')} flex-1`} />
                <select value={form.currency} onChange={e => handleChange('currency', e.target.value)} className="w-24 input-field">
                  <option value="USD">USD</option>
                  <option value="SYP">SYP</option>
                </select>
              </div>
            </div>
            <div>
              <label className="text-sm font-medium text-stone-700 mb-1.5 block">{L('الوكيل المسؤول', 'Agent')}</label>
              <select value={form.agent_id} onChange={e => handleChange('agent_id', e.target.value)} className={inputCls('agent_id')}>
                <option value="">{L('بدون وكيل', 'No agent')}</option>
                {agents.map(a => <option key={a.id} value={a.id}>{a.display_name}</option>)}
              </select>
            </div>
          </div>
        </section>

        {/* Location */}
        <section className="card-3d p-6">
          <h2 className="text-lg font-bold text-primary mb-4 flex items-center gap-2">
            <MapPin className="w-5 h-5" /> {L('الموقع', 'Location')}
          </h2>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label className="text-sm font-medium text-stone-700 mb-1.5 block">{L('المحافظة', 'Governorate')}</label>
              <select value={form.governorate_id} onChange={e => handleChange('governorate_id', e.target.value)} required className={inputCls('governorate_id')}>
                <option value="">{L('اختر المحافظة', 'Select governorate')}</option>
                {governorates.map(g => <option key={g.id} value={g.id}>{isAr ? g.name_ar : g.name_en}</option>)}
              </select>
            </div>
            <div>
              <label className="text-sm font-medium text-stone-700 mb-1.5 block">{L('المنطقة', 'Area')}</label>
              <select value={form.area_id} onChange={e => handleChange('area_id', e.target.value)} className={inputCls('area_id')}>
                <option value="">{L('اختر المنطقة', 'Select area')}</option>
                {areas.map(a => <option key={a.id} value={a.id}>{isAr ? a.name_ar : a.name_en}</option>)}
              </select>
            </div>
            <div>
              <label className="text-sm font-medium text-stone-700 mb-1.5 block">{L('العنوان (عربي)', 'Address (Arabic)')}</label>
              <input type="text" value={form.address_ar} onChange={e => handleChange('address_ar', e.target.value)} placeholder="المزة، شارع الثورة" className={inputCls('address_ar')} />
            </div>
            <div>
              <label className="text-sm font-medium text-stone-700 mb-1.5 block">{L('العنوان (إنجليزي)', 'Address (English)')}</label>
              <input type="text" value={form.address_en} onChange={e => handleChange('address_en', e.target.value)} placeholder="Mezzeh, Al-Thawra St." className={inputCls('address_en')} />
            </div>
            <div>
              <label className="text-sm font-medium text-stone-700 mb-1.5 block">Latitude</label>
              <input type="text" value={form.lat} onChange={e => handleChange('lat', e.target.value)} placeholder="33.5138" className={inputCls('lat')} dir="ltr" />
            </div>
            <div>
              <label className="text-sm font-medium text-stone-700 mb-1.5 block">Longitude</label>
              <input type="text" value={form.lng} onChange={e => handleChange('lng', e.target.value)} placeholder="36.2765" className={inputCls('lng')} dir="ltr" />
            </div>
          </div>
        </section>

        {/* Details */}
        <section className="card-3d p-6">
          <h2 className="text-lg font-bold text-primary mb-4 flex items-center gap-2">
            <Building className="w-5 h-5" /> {L('تفاصيل العقار', 'Property Details')}
          </h2>
          <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div>
              <label className="text-sm font-medium text-stone-700 mb-1.5 block">
                <Ruler className="w-3.5 h-3.5 inline mr-1" /> {L('المساحة (م²)', 'Area (m²)')}
              </label>
              <input type="number" required value={form.area_sqm} onChange={e => handleChange('area_sqm', e.target.value)} className={inputCls('area_sqm')} />
            </div>
            <div>
              <label className="text-sm font-medium text-stone-700 mb-1.5 block">
                <Bed className="w-3.5 h-3.5 inline mr-1" /> {L('غرف النوم', 'Bedrooms')}
              </label>
              <input type="number" value={form.bedrooms} onChange={e => handleChange('bedrooms', e.target.value)} className={inputCls('bedrooms')} />
            </div>
            <div>
              <label className="text-sm font-medium text-stone-700 mb-1.5 block">
                <Bath className="w-3.5 h-3.5 inline mr-1" /> {L('الحمامات', 'Bathrooms')}
              </label>
              <input type="number" value={form.bathrooms} onChange={e => handleChange('bathrooms', e.target.value)} className={inputCls('bathrooms')} />
            </div>
            <div>
              <label className="text-sm font-medium text-stone-700 mb-1.5 block">
                <Car className="w-3.5 h-3.5 inline mr-1" /> {L('مواقف سيارات', 'Parking')}
              </label>
              <input type="number" value={form.parking} onChange={e => handleChange('parking', e.target.value)} className={inputCls('parking')} />
            </div>
            <div>
              <label className="text-sm font-medium text-stone-700 mb-1.5 block">{L('الطابق', 'Floor')}</label>
              <input type="number" value={form.floor} onChange={e => handleChange('floor', e.target.value)} className={inputCls('floor')} />
            </div>
            <div>
              <label className="text-sm font-medium text-stone-700 mb-1.5 block">{L('سنة البناء', 'Year Built')}</label>
              <input type="number" value={form.year_built} onChange={e => handleChange('year_built', e.target.value)} className={inputCls('year_built')} />
            </div>
            <div className="flex items-center gap-3 pt-6">
              <input type="checkbox" id="furnished" checked={form.furnished} onChange={e => handleChange('furnished', e.target.checked)}
                className="w-4 h-4 rounded border-stone-300 text-primary focus:ring-primary" />
              <label htmlFor="furnished" className="text-sm font-medium text-stone-700">{L('مفروش', 'Furnished')}</label>
            </div>
          </div>
        </section>

        {/* Description */}
        <section className="card-3d p-6">
          <h2 className="text-lg font-bold text-primary mb-4">{L('الوصف', 'Description')}</h2>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label className="text-sm font-medium text-stone-700 mb-1.5 block">{L('الوصف (عربي)', 'Description (Arabic)')}</label>
              <textarea rows={4} required value={form.description_ar} onChange={e => handleChange('description_ar', e.target.value)}
                placeholder={L('اكتب وصفاً مفصلاً للعقار بالعربية', 'Write a detailed description in Arabic')}
                className={inputCls('description_ar')} />
            </div>
            <div>
              <label className="text-sm font-medium text-stone-700 mb-1.5 block">{L('الوصف (إنجليزي)', 'Description (English)')}</label>
              <textarea rows={4} required value={form.description_en} onChange={e => handleChange('description_en', e.target.value)}
                placeholder="Write a detailed description in English"
                className={inputCls('description_en')} />
            </div>
          </div>
        </section>

        {/* Status & Submit */}
        <section className="card-3d p-6">
          <div className="flex flex-wrap items-center justify-between gap-4">
            <div className="flex items-center gap-3">
              <label className="text-sm font-medium text-stone-700">{L('الحالة', 'Status')}</label>
              <select value={form.status} onChange={e => handleChange('status', e.target.value)} className="input-field w-40">
                <option value="draft">{L('مسودة', 'Draft')}</option>
                <option value="available">{L('متاح', 'Available')}</option>
              </select>
            </div>
            <div className="flex gap-3">
              <button type="button" onClick={() => navigate('/dashboard/properties')}
                className="px-6 py-2.5 rounded-xl text-sm font-medium text-stone-500 hover:bg-beige transition-colors">
                {L('إلغاء', 'Cancel')}
              </button>
              <button type="submit" disabled={submitting}
                className="btn-primary flex items-center gap-2 !py-2.5">
                {submitting ? <Loader2 className="w-4 h-4 animate-spin" /> : <Building2 className="w-4 h-4" />}
                {L('إضافة العقار', 'Add Property')}
              </button>
            </div>
          </div>
        </section>
      </form>
    </div>
  );
}
