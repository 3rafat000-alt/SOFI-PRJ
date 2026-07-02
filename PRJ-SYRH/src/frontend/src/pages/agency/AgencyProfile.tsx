import { useState, useEffect, useRef, useCallback } from 'react';
import { useTranslation } from 'react-i18next';
import { Loader2, CheckCircle2, Building2, Shield, Unlink, ExternalLink, Phone, Mail, MapPin, MessageCircle, Save, Upload, Trash2, Navigation, ChevronDown, Image } from 'lucide-react';
import { MapContainer, TileLayer, Marker, useMapEvents, useMap } from 'react-leaflet';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { fetchAgencyProfile, updateAgencyProfile, uploadAgencyLogo, uploadAgencyCover, fetchSakkAccount, updateSakkAccount, removeSakkAccount, type AgencyProfile } from '../../api/agency';
import { fetchLocations, fetchAreas } from '../../api/locations';
import type { Governorate, Area } from '../../api/client';

// Fix Leaflet default icon issue
import iconUrl from 'leaflet/dist/images/marker-icon.png';
import iconRetinaUrl from 'leaflet/dist/images/marker-icon-2x.png';
import shadowUrl from 'leaflet/dist/images/marker-shadow.png';

L.Icon.Default.mergeOptions({ iconUrl, iconRetinaUrl, shadowUrl });

// Syria default center
const SYRIA_CENTER: [number, number] = [34.8021, 39.0822];

function DraggableMarker({ position, onMove }: { position: [number, number] | null; onMove: (lat: number, lng: number) => void }) {
  const markerRef = useRef<L.Marker>(null);

  useMapEvents({
    click(e) {
      onMove(e.latlng.lat, e.latlng.lng);
    },
  });

  return position ? (
    <Marker
      ref={markerRef}
      position={position}
      draggable={true}
      eventHandlers={{
        dragend: () => {
          const m = markerRef.current;
          if (m) {
            const p = m.getLatLng();
            onMove(p.lat, p.lng);
          }
        },
      }}
    />
  ) : null;
}

function FlyTo({ center }: { center: [number, number] }) {
  const map = useMap();
  useEffect(() => { map.flyTo(center, 13, { duration: 1 }); }, [center, map]);
  return null;
}

export default function AgencyProfile() {
  const { i18n } = useTranslation();
  const isAr = i18n.language === 'ar';
  const L = (ar: string, en: string) => isAr ? ar : en;

  const [profile, setProfile] = useState<AgencyProfile | null>(null);
  const [form, setForm] = useState({
    name: '', email: '', phone: '', whatsapp: '',
    address: '', description_ar: '', description_en: '', logo_path: '', cover_path: '',
    governorate_id: '', area_id: '', lat: '', lng: '',
  });
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [saved, setSaved] = useState(false);
  const [logoPreview, setLogoPreview] = useState<string | null>(null);
  const [logoError, setLogoError] = useState(false);
  const [coverPreview, setCoverPreview] = useState<string | null>(null);
  const [coverError, setCoverError] = useState(false);

  const [governorates, setGovernorates] = useState<Governorate[]>([]);
  const [areas, setAreas] = useState<Area[]>([]);
  const [areasLoading, setAreasLoading] = useState(false);

  const [sakk, setSakk] = useState<{ sakk_merchant_id: string | null; sakk_verified: boolean } | null>(null);
  const [showSakkForm, setShowSakkForm] = useState(false);
  const [sakkMerchant, setSakkMerchant] = useState('');
  const [sakkKey, setSakkKey] = useState('');
  const [sakkLoading, setSakkLoading] = useState(false);
  const [uploading, setUploading] = useState(false);
  const fileInputRef = useRef<HTMLInputElement>(null);
  const coverInputRef = useRef<HTMLInputElement>(null);

  const mapPos: [number, number] | null = form.lat && form.lng
    ? [parseFloat(form.lat), parseFloat(form.lng)]
    : null;

  const setFormField = useCallback((field: string, value: any) => {
    setForm(prev => ({ ...prev, [field]: value }));
  }, []);

  // Load profile + governorates on mount
  useEffect(() => {
    Promise.all([
      fetchAgencyProfile(),
      fetchLocations(),
    ]).then(([p, locs]) => {
      setProfile(p);
      setForm({
        name: p.name, email: p.email, phone: p.phone || '',
        whatsapp: p.whatsapp || '', address: p.address || '',
        description_ar: p.description_ar || '', description_en: p.description_en || '',
        logo_path: p.logo_url || '',
        cover_path: p.cover_url || '',
        governorate_id: p.governorate_id?.toString() || '',
        area_id: p.area_id?.toString() || '',
        lat: p.lat?.toString() || '',
        lng: p.lng?.toString() || '',
      });
      setLogoPreview(p.logo_url);
      setCoverPreview(p.cover_url);
      setGovernorates(locs.governorates);
    }).finally(() => setLoading(false));
    fetchSakkAccount().then(setSakk).catch(() => {});
  }, []);

  // Load areas when governorate changes
  useEffect(() => {
    if (!form.governorate_id) { setAreas([]); return; }
    const gov = governorates.find(g => g.id.toString() === form.governorate_id);
    if (!gov) return;
    setAreasLoading(true);
    fetchAreas(gov.slug).then(setAreas).catch(() => setAreas([])).finally(() => setAreasLoading(false));
  }, [form.governorate_id, governorates]);

  const handleMapMove = useCallback((lat: number, lng: number) => {
    setForm(prev => ({ ...prev, lat: lat.toString(), lng: lng.toString() }));
  }, []);

  // Pick coords from area
  const handleAreaChange = (areaId: string) => {
    setFormField('area_id', areaId);
    const area = areas.find(a => a.id.toString() === areaId);
    if (area?.lat && area?.lng) {
      setForm(prev => ({ ...prev, area_id: areaId, lat: area.lat, lng: area.lng }));
    }
  };

  // Pick coords from governorate
  const handleGovernorateChange = (govId: string) => {
    setForm(prev => ({ ...prev, governorate_id: govId, area_id: '', lat: '', lng: '' }));
    const gov = governorates.find(g => g.id.toString() === govId);
    if (gov?.lat && gov?.lng) {
      setForm(prev => ({ ...prev, lat: gov.lat, lng: gov.lng }));
    }
  };

  const handleLinkSakk = async () => {
    setSakkLoading(true);
    try {
      await updateSakkAccount(sakkMerchant, sakkKey);
      const updated = await fetchSakkAccount();
      setSakk(updated);
      setShowSakkForm(false);
      setSakkMerchant('');
      setSakkKey('');
    } catch (err) {
      console.error('SAKK link failed', err);
      alert(L('فشل ربط حساب صك', 'Failed to link SAKK account'));
    } finally {
      setSakkLoading(false);
    }
  };

  const handleUnlinkSakk = async () => {
    if (!confirm(L('إلغاء ربط حساب صك؟', 'Remove SAKK account?'))) return;
    setSakkLoading(true);
    try {
      await removeSakkAccount();
      setSakk({ sakk_merchant_id: null, sakk_verified: false });
    } catch (err) {
      console.error('SAKK unlink failed', err);
    } finally {
      setSakkLoading(false);
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSaving(true);
    setSaved(false);
    try {
      const payload: Record<string, any> = { ...form };
      if (form.logo_path) payload.logo_path = form.logo_path;
      else payload.logo_path = null;
      if (form.cover_path) payload.cover_path = form.cover_path;
      else payload.cover_path = null;
      payload.governorate_id = form.governorate_id ? parseInt(form.governorate_id) : null;
      payload.area_id = form.area_id ? parseInt(form.area_id) : null;
      payload.lat = form.lat ? parseFloat(form.lat) : null;
      payload.lng = form.lng ? parseFloat(form.lng) : null;
      await updateAgencyProfile(payload);
      setSaved(true);
      setTimeout(() => setSaved(false), 3000);
    } catch (err) {
      console.error('Failed to save profile', err);
    } finally {
      setSaving(false);
    }
  };

  const handleLogoUpload = async (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;
    setUploading(true);
    try {
      const url = await uploadAgencyLogo(file);
      setLogoPreview(url);
      setForm(prev => ({ ...prev, logo_path: url }));
      setLogoError(false);
    } catch (err) {
      console.error('Logo upload failed', err);
    } finally {
      setUploading(false);
      if (fileInputRef.current) fileInputRef.current.value = '';
    }
  };

  const handleRemoveLogo = async () => {
    if (!confirm(L('إزالة الشعار؟', 'Remove logo?'))) return;
    setLogoPreview(null);
    setLogoError(false);
    setForm(prev => ({ ...prev, logo_path: '' }));
    try { await updateAgencyProfile({ logo_path: null }); } catch (err) { console.error('Failed to remove logo', err); }
  };

  const handleCoverUpload = async (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;
    setUploading(true);
    try {
      const url = await uploadAgencyCover(file);
      setCoverPreview(url);
      setForm(prev => ({ ...prev, cover_path: url }));
      setCoverError(false);
    } catch (err) {
      console.error('Cover upload failed', err);
    } finally {
      setUploading(false);
      if (coverInputRef.current) coverInputRef.current.value = '';
    }
  };

  const handleRemoveCover = async () => {
    if (!confirm(L('إزالة صورة الخلفية؟', 'Remove cover image?'))) return;
    setCoverPreview(null);
    setCoverError(false);
    setForm(prev => ({ ...prev, cover_path: '' }));
    try { await updateAgencyProfile({ cover_path: null }); } catch (err) { console.error('Failed to remove cover', err); }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <Loader2 className="w-8 h-8 animate-spin text-primary" />
      </div>
    );
  }

  return (
    <div className="max-w-5xl mx-auto">
      {/* Header */}
      <div className="flex items-center justify-between mb-6">
        <div className="flex items-center gap-3">
          <div className="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center">
            <Building2 className="w-5 h-5 text-primary" />
          </div>
          <div>
            <h1 className="text-2xl font-bold text-stone-900">{L('الملف الشخصي للوكالة', 'Agency Profile')}</h1>
            <p className="text-sm text-stone-400">{L('إدارة معلومات وكالتك وإعدادات الدفع', 'Manage your agency info and payment settings')}</p>
          </div>
        </div>
        {saved && (
          <div className="flex items-center gap-1.5 text-sm text-emerald-600 bg-emerald-50 border border-emerald-100 px-4 py-2 rounded-xl">
            <CheckCircle2 className="w-4 h-4" /> {L('تم الحفظ بنجاح!', 'Saved successfully!')}
          </div>
        )}
      </div>

      <form onSubmit={handleSubmit} className="space-y-6">
        {/* Row: Logo Card + Quick Stats */}
        <div className="card-3d p-5">
          <div className="flex flex-col sm:flex-row items-start sm:items-center gap-4">
            {/* Logo + Name */}
            <div className="flex items-center gap-4 flex-1">
              <div className="w-16 h-16 rounded-2xl border-2 border-dashed border-beige-dark/40 flex items-center justify-center overflow-hidden bg-beige/30 shrink-0">
                {logoPreview && !logoError ? (
                  <img src={logoPreview} alt={form.name || 'logo'} className="w-full h-full object-contain p-1.5" onError={() => setLogoError(true)} />
                ) : (
                  <div className="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center">
                    <span className="text-primary font-bold text-lg">{form.name ? form.name.charAt(0) : '?'}</span>
                  </div>
                )}
              </div>
              <div>
                <h2 className="text-lg font-bold text-stone-900">{form.name || L('اسم الوكالة', 'Agency Name')}</h2>
                <p className="text-xs text-stone-400 line-clamp-1">
                  {isAr
                    ? (form.description_ar || L('لا يوجد وصف', 'No description'))
                    : (form.description_en || L('لا يوجد وصف', 'No description'))}
                </p>
              </div>
            </div>

            {/* Upload */}
            <div className="flex items-center gap-2">
              <input ref={fileInputRef} type="file" accept="image/jpeg,image/png,image/webp,image/svg+xml" className="hidden" onChange={handleLogoUpload} />
              <button type="button" onClick={() => fileInputRef.current?.click()} disabled={uploading}
                className="btn-primary !py-1.5 !px-3.5 text-xs flex items-center gap-1.5">
                {uploading ? <Loader2 className="w-3 h-3 animate-spin" /> : <Upload className="w-3.5 h-3.5" />}
                {uploading ? L('...', '...') : L('رفع شعار', 'Upload Logo')}
              </button>
              {logoPreview && (
                <button type="button" onClick={handleRemoveLogo}
                  className="p-1.5 rounded-xl border border-beige-dark/30 text-stone-400 hover:text-red-500 transition-colors">
                  <Trash2 className="w-3.5 h-3.5" />
                </button>
              )}
            </div>

            {/* Quick Stats */}
            {profile && (
              <div className="flex items-center gap-3 text-xs sm:border-e sm:border-beige-dark/20 sm:pe-4">
                <div className="text-center">
                  <div className="font-bold text-primary">{profile.commission_rate}%</div>
                  <div className="text-stone-400">{L('عمولة', 'Comm.')}</div>
                </div>
                <div className="text-center">
                  <div className="font-bold text-stone-700">#{profile.id}</div>
                  <div className="text-stone-400">ID</div>
                </div>
              </div>
            )}
          </div>
        </div>

        {/* Agency Info Card — Professional */}
        <div className="card-3d p-6">
          <div className="flex items-center gap-2 mb-6 pb-4 border-b border-beige-dark/10">
            <div className="w-9 h-9 rounded-xl bg-primary/10 flex items-center justify-center">
              <Building2 className="w-5 h-5 text-primary" />
            </div>
            <div>
              <h2 className="text-lg font-bold text-stone-800">{L('معلومات الوكالة', 'Agency Information')}</h2>
              <p className="text-xs text-stone-400">{L('بيانات الاتصال والموقع', 'Contact details and location')}</p>
            </div>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5">
            {/* Name */}
            <div className="space-y-1">
              <label className="text-xs font-semibold text-stone-500 uppercase tracking-wider">{L('اسم الوكالة', 'Agency Name')}</label>
              <div className="relative">
                <Building2 className="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-stone-300" />
                <input required value={form.name} onChange={e => setFormField('name', e.target.value)}
                  className="input-field pr-10" placeholder={L('اسم الوكالة', 'Agency name')} />
              </div>
            </div>

            {/* Email */}
            <div className="space-y-1">
              <label className="text-xs font-semibold text-stone-500 uppercase tracking-wider">EMAIL</label>
              <div className="relative">
                <Mail className="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-stone-300" />
                <input type="email" value={form.email} onChange={e => setFormField('email', e.target.value)}
                  className="input-field pr-10" dir="ltr" placeholder="email@agency.com" />
              </div>
            </div>

            {/* Phone */}
            <div className="space-y-1">
              <label className="text-xs font-semibold text-stone-500 uppercase tracking-wider">{L('رقم الهاتف', 'Phone')}</label>
              <div className="relative">
                <Phone className="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-stone-300" />
                <input value={form.phone} onChange={e => setFormField('phone', e.target.value)}
                  className="input-field pr-10" dir="ltr" placeholder="+963 XX XXX XXXX" />
              </div>
            </div>

            {/* WhatsApp */}
            <div className="space-y-1">
              <label className="text-xs font-semibold text-stone-500 uppercase tracking-wider">WHATSAPP</label>
              <div className="relative">
                <MessageCircle className="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-stone-300" />
                <input value={form.whatsapp} onChange={e => setFormField('whatsapp', e.target.value)}
                  className="input-field pr-10" dir="ltr" placeholder="+963 XX XXX XXXX" />
              </div>
            </div>
          </div>

          {/* Location Section */}
          <div className="mt-6 pt-5 border-t border-beige-dark/10">
            <div className="flex items-center gap-2 mb-4">
              <MapPin className="w-4 h-4 text-primary" />
              <h3 className="font-bold text-stone-700 text-sm">{L('الموقع', 'Location')}</h3>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
              {/* Governorate */}
              <div className="space-y-1">
                <label className="text-xs font-semibold text-stone-500 uppercase tracking-wider">{L('المحافظة', 'Governorate')}</label>
                <div className="relative">
                  <MapPin className="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-stone-300 pointer-events-none" />
                  <select value={form.governorate_id} onChange={e => handleGovernorateChange(e.target.value)}
                    className="input-field pr-10 appearance-none cursor-pointer">
                    <option value="">{L('اختر المحافظة', 'Select governorate')}</option>
                    {governorates.map(g => (
                      <option key={g.id} value={g.id}>{isAr ? g.name_ar : g.name_en}</option>
                    ))}
                  </select>
                  <ChevronDown className="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-stone-300 pointer-events-none" />
                </div>
              </div>

              {/* Area */}
              <div className="space-y-1">
                <label className="text-xs font-semibold text-stone-500 uppercase tracking-wider">{L('المنطقة', 'Area')}</label>
                <div className="relative">
                  <Navigation className="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-stone-300 pointer-events-none" />
                  <select value={form.area_id} onChange={e => handleAreaChange(e.target.value)}
                    className="input-field pr-10 appearance-none cursor-pointer"
                    disabled={!form.governorate_id || areasLoading}>
                    <option value="">{areasLoading ? L('جاري التحميل...', 'Loading...') : L('اختر المنطقة', 'Select area')}</option>
                    {areas.map(a => (
                      <option key={a.id} value={a.id}>{isAr ? a.name_ar : a.name_en}</option>
                    ))}
                  </select>
                  <ChevronDown className="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-stone-300 pointer-events-none" />
                </div>
              </div>

              {/* Address */}
              <div className="space-y-1">
                <label className="text-xs font-semibold text-stone-500 uppercase tracking-wider">{L('العنوان', 'Address')}</label>
                <div className="relative">
                  <MapPin className="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-stone-300" />
                  <input value={form.address} onChange={e => setFormField('address', e.target.value)}
                    className="input-field pr-10" placeholder={L('العنوان بالتفصيل', 'Detailed address')} />
                </div>
              </div>
            </div>

            {/* Map */}
            <div className="rounded-2xl overflow-hidden border border-beige-dark/20 h-64 md:h-72">
              <MapContainer
                center={mapPos || SYRIA_CENTER}
                zoom={mapPos ? 13 : 7}
                className="w-full h-full"
                zoomControl={true}
              >
                <TileLayer
                  attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
                  url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
                />
                <DraggableMarker position={mapPos} onMove={handleMapMove} />
                {mapPos && <FlyTo center={mapPos} />}
              </MapContainer>
            </div>
            <p className="text-xs text-stone-400 mt-2 flex items-center gap-1">
              <Navigation className="w-3 h-3" />
              {mapPos
                ? `${parseFloat(form.lat).toFixed(5)}, ${parseFloat(form.lng).toFixed(5)}`
                : L('انقر على الخريطة أو اسحب المؤشر لتحديد الموقع', 'Click on the map or drag the marker to set location')}
            </p>
          </div>
        </div>

        {/* Cover Image Card */}
        <div className="card-3d p-6">
          <div className="flex items-center gap-2 mb-5 pb-3 border-b border-beige-dark/10">
            <div className="w-8 h-8 rounded-lg bg-stone-100 flex items-center justify-center">
              <Image className="w-4 h-4 text-stone-500" />
            </div>
            <div>
              <h2 className="text-lg font-bold text-stone-800">{L('صورة الخلفية', 'Cover Image')}</h2>
              <p className="text-xs text-stone-400">{L('تظهر في أعلى صفحة الوكالة العامة', 'Displayed at the top of your public agency page')}</p>
            </div>
          </div>

          {/* Preview */}
          {coverPreview && !coverError ? (
            <div className="relative rounded-2xl overflow-hidden border border-beige-dark/20 mb-4 bg-beige/20">
              <img src={coverPreview} alt="cover"
                className="w-full h-48 object-cover"
                onError={() => setCoverError(true)} />
              <div className="absolute inset-0 bg-gradient-to-t from-black/30 to-transparent pointer-events-none" />
            </div>
          ) : coverError ? (
            <div className="rounded-2xl border border-red-200 bg-red-50 p-6 text-center mb-4">
              <p className="text-sm text-red-500">{L('فشل تحميل الصورة', 'Failed to load image')}</p>
            </div>
          ) : (
            <div className="rounded-2xl border-2 border-dashed border-beige-dark/30 bg-beige/20 p-10 text-center mb-4">
              <Image className="w-10 h-10 mx-auto text-stone-300 mb-3" />
              <p className="text-sm text-stone-400">{L('لم يتم تعيين صورة خلفية بعد', 'No cover image set yet')}</p>
              <p className="text-xs text-stone-300 mt-1">{L('يُفضل 1200×400 بكسل أو أكبر', 'Recommended 1200×400px or larger')}</p>
            </div>
          )}

          {/* Actions */}
          <div className="flex items-center gap-2">
            <input ref={coverInputRef} type="file" accept="image/jpeg,image/png,image/webp" className="hidden" onChange={handleCoverUpload} />
            <button type="button" onClick={() => coverInputRef.current?.click()} disabled={uploading}
              className="btn-primary !py-2 !px-4 text-sm flex items-center gap-1.5">
              {uploading ? <Loader2 className="w-3.5 h-3.5 animate-spin" /> : <Upload className="w-4 h-4" />}
              {uploading ? L('...', '...') : L('رفع صورة', 'Upload Cover')}
            </button>
            {coverPreview && (
              <button type="button" onClick={handleRemoveCover}
                className="p-2 rounded-xl border border-beige-dark/30 text-stone-400 hover:text-red-500 transition-colors">
                <Trash2 className="w-4 h-4" />
              </button>
            )}
          </div>
        </div>

        {/* Description Card */}
        <div className="card-3d p-6">
          <div className="flex items-center gap-2 mb-5 pb-3 border-b border-beige-dark/10">
            <div className="w-8 h-8 rounded-lg bg-stone-100 flex items-center justify-center">
              <MessageCircle className="w-4 h-4 text-stone-500" />
            </div>
            <h2 className="text-lg font-bold text-stone-800">{L('الوصف', 'Description')}</h2>
          </div>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div className="space-y-1.5">
              <label className="text-xs font-semibold text-stone-500 uppercase tracking-wider">{L('وصف عربي', 'Arabic Description')}</label>
              <textarea rows={4} value={form.description_ar}
                onChange={e => setFormField('description_ar', e.target.value)}
                className="input-field resize-none"
                placeholder={L('اكتب وصفاً للوكالة بالعربية', 'Write agency description in Arabic')} />
            </div>
            <div className="space-y-1.5">
              <label className="text-xs font-semibold text-stone-500 uppercase tracking-wider">{L('وصف إنجليزي', 'English Description')}</label>
              <textarea rows={4} value={form.description_en}
                onChange={e => setFormField('description_en', e.target.value)}
                className="input-field resize-none"
                placeholder="Write agency description in English" />
            </div>
          </div>
        </div>

        {/* SAKK Account Card */}
        <div className="card-3d p-6">
          <div className="flex items-center gap-2 mb-5 pb-3 border-b border-beige-dark/10">
            <div className="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center">
              <Shield className="w-4 h-4 text-emerald-600" />
            </div>
            <div>
              <h2 className="text-lg font-bold text-stone-800">{L('حساب صك للدفع', 'SAKK Payment Account')}</h2>
              <p className="text-xs text-stone-400">{L('لاستقبال مدفوعات الاشتراكات والعمولات', 'Receive subscription payments and commissions')}</p>
            </div>
          </div>

          {sakk?.sakk_merchant_id ? (
            <div className="bg-emerald-50/50 border border-emerald-100 rounded-2xl p-5">
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-4">
                  <div className="w-12 h-12 rounded-xl bg-emerald-100 flex items-center justify-center">
                    <Shield className="w-6 h-6 text-emerald-600" />
                  </div>
                  <div>
                    <div className="font-bold text-stone-900 text-sm font-mono">{sakk.sakk_merchant_id}</div>
                    <div className="flex items-center gap-1.5 mt-0.5">
                      <span className={`inline-block w-1.5 h-1.5 rounded-full ${sakk.sakk_verified ? 'bg-emerald-500' : 'bg-amber-400'}`} />
                      <span className="text-xs text-stone-500">
                        {sakk.sakk_verified
                          ? L('موثق ✓', 'Verified ✓')
                          : L('بانتظار التوثيق', 'Pending verification')}
                      </span>
                    </div>
                  </div>
                </div>
                <button type="button" onClick={handleUnlinkSakk} disabled={sakkLoading}
                  className="text-sm text-red-400 hover:text-red-600 flex items-center gap-1.5 transition-colors p-2 hover:bg-red-50 rounded-xl">
                  <Unlink className="w-3.5 h-3.5" />
                  <span className="hidden sm:inline">{L('إلغاء الربط', 'Unlink')}</span>
                </button>
              </div>
            </div>
          ) : showSakkForm ? (
            <div className="space-y-4 bg-stone-50 rounded-2xl p-5">
              <div>
                <label className="text-sm font-medium text-stone-700 mb-1.5 block">
                  {L('معرف التاجر في صك', 'SAKK Merchant ID')}
                </label>
                <input value={sakkMerchant} onChange={e => setSakkMerchant(e.target.value)}
                  className="input-field" dir="ltr" placeholder="sakk_merchant_..." />
              </div>
              <div>
                <label className="text-sm font-medium text-stone-700 mb-1.5 block">
                  {L('مفتاح API', 'API Key')}
                </label>
                <input value={sakkKey} onChange={e => setSakkKey(e.target.value)}
                  className="input-field" dir="ltr" type="password" placeholder="••••••••" />
              </div>
              <div className="flex gap-3">
                <button type="button" onClick={handleLinkSakk} disabled={sakkLoading || !sakkMerchant || !sakkKey}
                  className="btn-primary !py-2.5 !px-5 text-sm flex items-center gap-1.5">
                  {sakkLoading ? <Loader2 className="w-3.5 h-3.5 animate-spin" /> : <ExternalLink className="w-3.5 h-3.5" />}
                  {L('ربط', 'Link')}
                </button>
                <button type="button" onClick={() => setShowSakkForm(false)}
                  className="text-sm text-stone-400 hover:text-stone-600 transition-colors px-3">
                  {L('إلغاء', 'Cancel')}
                </button>
              </div>
            </div>
          ) : (
            <div className="text-center py-6">
              <div className="w-16 h-16 rounded-full bg-stone-100 flex items-center justify-center mx-auto mb-4">
                <Shield className="w-7 h-7 text-stone-300" />
              </div>
              <p className="text-sm text-stone-500 mb-4 max-w-md mx-auto">
                {L('اربط حساب صك لاستقبال مدفوعات الاشتراكات والعمولات بشكل آمن', 'Link your SAKK account to securely receive subscription payments and commissions')}
              </p>
              <button type="button" onClick={() => setShowSakkForm(true)}
                className="btn-primary !py-2.5 !px-5 text-sm flex items-center gap-1.5 mx-auto w-fit">
                <ExternalLink className="w-3.5 h-3.5" />
                {L('ربط حساب صك', 'Link SAKK Account')}
              </button>
            </div>
          )}
        </div>

        {/* Save Button */}
        <div className="flex items-center justify-between bg-white border border-beige-dark/30 rounded-2xl p-5">
          <div className="hidden sm:block">
            <p className="text-sm font-medium text-stone-700">{L('جميع التغييرات محفوظة محلياً', 'All changes are saved locally')}</p>
            <p className="text-xs text-stone-400">{L('اضغط حفظ لتطبيق التغييرات', 'Click save to apply changes')}</p>
          </div>
          <button type="submit" disabled={saving}
            className="btn-primary !py-3 !px-8 flex items-center gap-2 text-sm shadow-lg shadow-primary/20">
            {saving ? <Loader2 className="w-4 h-4 animate-spin" /> : <Save className="w-4 h-4" />}
            {L('حفظ التغييرات', 'Save Changes')}
          </button>
        </div>
      </form>
    </div>
  );
}
