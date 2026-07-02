import { useState, useEffect, useRef } from 'react';
import { useTranslation } from 'react-i18next';
import { Link, useNavigate } from 'react-router-dom';
import {
  Save, Loader2, LogOut, ChevronLeft, Camera, User,
  Lock, KeyRound, Eye, EyeOff, Heart, Search, Trash2,
  ShieldCheck, PanelLeftClose, PanelLeft,
} from 'lucide-react';
import Navbar from '../components/Navbar';
import Footer from '../components/Footer';
import PropertyCard from '../components/PropertyCard';
import { useAuth } from '../auth/AuthContext';
import { validateProfile, validateChangePassword, type ValidationErrors } from '../auth/validation';
import { updateProfile, uploadAvatar, changePassword, fetchFavorites, toggleFavorite, fetchSavedSearches, deleteSavedSearch, type FavoriteItem, type SavedSearchItem } from '../api/auth';

type Tab = 'info' | 'security' | 'favorites' | 'searches';

const TAB_KEYS: Record<Tab, string> = {
  info:     'auth.tabInfo',
  security: 'auth.tabSecurity',
  favorites:'auth.tabFavorites',
  searches: 'auth.tabSearches',
};

const TABS: { key: Tab; icon: any }[] = [
  { key: 'info',     icon: User },
  { key: 'security', icon: Lock },
  { key: 'favorites',icon: Heart },
  { key: 'searches', icon: Search },
];

export default function Profile() {
  const { t, i18n } = useTranslation();
  const isAr = i18n.language === 'ar';
  const navigate = useNavigate();
  const { user, logout, refreshUser } = useAuth();

  const [tab, setTab] = useState<Tab>('info');
  const [sidebarCollapsed, setSidebarCollapsed] = useState(false);

  // ── Profile form ──
  const [name, setName] = useState('');
  const [phone, setPhone] = useState('');
  const [saving, setSaving] = useState(false);
  const [success, setSuccess] = useState('');
  const [formErrors, setFormErrors] = useState<ValidationErrors>({});

  // ── Avatar ──
  const [avatarPreview, setAvatarPreview] = useState<string | null>(null);
  const [avatarError, setAvatarError] = useState(false);
  const [uploading, setUploading] = useState(false);
  const fileRef = useRef<HTMLInputElement>(null);

  // ── Password ──
  const [showPassword, setShowPassword] = useState(false);
  const [currentPassword, setCurrentPassword] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [newPasswordConfirm, setNewPasswordConfirm] = useState('');
  const [changingPassword, setChangingPassword] = useState(false);
  const [passwordErrors, setPasswordErrors] = useState<ValidationErrors>({});
  const [passwordError, setPasswordError] = useState('');
  const [passwordSuccess, setPasswordSuccess] = useState('');

  // ── Favorites ──
  const [favorites, setFavorites] = useState<FavoriteItem[]>([]);
  const [favLoading, setFavLoading] = useState(false);

  // ── Saved searches ──
  const [searches, setSearches] = useState<SavedSearchItem[]>([]);
  const [searchLoading, setSearchLoading] = useState(false);

  useEffect(() => {
    if (!user) { navigate('/login'); return; }
    setName(user.name);
    setPhone(user.phone || '');
    setAvatarPreview(user.avatar_url);
  }, [user]);

  useEffect(() => {
    if (tab === 'favorites') { setFavLoading(true); fetchFavorites().then(setFavorites).catch(() => {}).finally(() => setFavLoading(false)); }
    if (tab === 'searches') { setSearchLoading(true); fetchSavedSearches().then(setSearches).catch(() => {}).finally(() => setSearchLoading(false)); }
  }, [tab]);

  // ── Handlers ──
  const handleSave = async (e: React.FormEvent) => {
    e.preventDefault();
    setFormErrors({}); setSuccess('');

    const fieldErrors = validateProfile({ name, phone });
    if (Object.keys(fieldErrors).length > 0) { setFormErrors(fieldErrors); return; }

    setSaving(true);
    try { await updateProfile({ name, phone }); await refreshUser(); setSuccess(t('auth.profileSaved')); setTimeout(() => setSuccess(''), 3000); }
    catch (err) { console.error(err); }
    finally { setSaving(false); }
  };

  const handleAvatar = async (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0]; if (!file) return;
    setUploading(true);
    try { const updated = await uploadAvatar(file); setAvatarPreview(updated.avatar_url); setAvatarError(false); await refreshUser(); }
    catch (err) { console.error('Avatar upload failed', err); }
    finally { setUploading(false); if (fileRef.current) fileRef.current.value = ''; }
  };

  const handleChangePassword = async (e: React.FormEvent) => {
    e.preventDefault();
    setPasswordErrors({}); setPasswordError(''); setPasswordSuccess('');

    const fieldErrors = validateChangePassword({
      current_password: currentPassword,
      password: newPassword,
      password_confirmation: newPasswordConfirm,
    });
    if (Object.keys(fieldErrors).length > 0) { setPasswordErrors(fieldErrors); return; }

    setChangingPassword(true);
    try {
      const msg = await changePassword(currentPassword, newPassword);
      setPasswordSuccess(msg); setCurrentPassword(''); setNewPassword(''); setNewPasswordConfirm('');
      setTimeout(() => setPasswordSuccess(''), 4000);
    } catch (err: any) { setPasswordError(err?.response?.data?.message || t('auth.changePasswordFailed')); }
    finally { setChangingPassword(false); }
  };

  const handleRemoveFav = async (propertyId: number) => { await toggleFavorite(propertyId); fetchFavorites().then(setFavorites).catch(() => {}); };
  const handleDeleteSearch = async (id: number) => { await deleteSavedSearch(id); fetchSavedSearches().then(setSearches).catch(() => {}); };

  if (!user) return null;

  return (
    <div dir={isAr ? 'rtl' : 'ltr'} className="min-h-screen flex flex-col bg-beige">
      <Navbar />
      <div className="flex-1 pt-24 pb-16">
        <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">

          {/* Breadcrumb */}
          <div className="flex items-center gap-2 text-sm text-stone-400 mb-6">
            <Link to="/" className="hover:text-primary transition-colors">{t('nav.home')}</Link>
            <ChevronLeft className="w-3 h-3 lucide-rtl" />
            <span className="text-stone-800 font-medium">{t('auth.profileTitle')}</span>
          </div>

          <div className="flex flex-col lg:flex-row gap-6">

            {/* ═══ SIDEBAR ═══ */}
            <aside className={`${sidebarCollapsed ? 'w-[72px]' : 'w-64'} shrink-0 transition-all duration-300`}>
              {/* Toggle button */}
              <div className={`flex ${isAr ? 'justify-start' : 'justify-end'} mb-2`}>
                <button onClick={() => setSidebarCollapsed(!sidebarCollapsed)}
                  className="w-7 h-7 rounded-lg bg-white border border-stone-200 flex items-center justify-center text-stone-400 hover:text-stone-700 hover:border-stone-300 transition-all">
                  {sidebarCollapsed
                    ? <PanelLeft className="w-3.5 h-3.5" />
                    : <PanelLeftClose className="w-3.5 h-3.5" />}
                </button>
              </div>

              {/* Avatar card */}
              <div className={`card-3d ${sidebarCollapsed ? 'p-3' : 'p-5 text-center'} mb-4 transition-all duration-300`}>
                {sidebarCollapsed ? (
                  <div className="flex flex-col items-center gap-1">
                    <div className="relative w-10 h-10">
                      <div className="w-10 h-10 rounded-full border-2 border-stone-100 overflow-hidden bg-primary/5 flex items-center justify-center">
                        {avatarPreview && !avatarError ? (
                          <img src={avatarPreview} alt={name} className="w-full h-full object-cover" onError={() => setAvatarError(true)} />
                        ) : (
                          <span className="text-primary font-bold text-sm">{name.charAt(0).toUpperCase()}</span>
                        )}
                      </div>
                      <input ref={fileRef} type="file" accept="image/jpeg,image/png,image/webp" className="hidden" onChange={handleAvatar} />
                      <button type="button" onClick={() => fileRef.current?.click()} disabled={uploading}
                        className="absolute -bottom-0.5 -right-0.5 w-4 h-4 rounded-full bg-primary text-white flex items-center justify-center shadow border border-white">
                        {uploading ? <Loader2 className="w-2 h-2 animate-spin" /> : <Camera className="w-2 h-2" />}
                      </button>
                    </div>
                  </div>
                ) : (
                  <>
                    <div className="relative mx-auto w-20 h-20 mb-3">
                      <div className="w-20 h-20 rounded-full border-4 border-stone-100 overflow-hidden bg-primary/5 flex items-center justify-center">
                        {avatarPreview && !avatarError ? (
                          <img src={avatarPreview} alt={name}
                            className="w-full h-full object-cover"
                            onError={() => setAvatarError(true)} />
                        ) : (
                          <User className="w-9 h-9 text-primary/40" />
                        )}
                      </div>
                      <input ref={fileRef} type="file" accept="image/jpeg,image/png,image/webp"
                        className="hidden" onChange={handleAvatar} />
                      <button type="button" onClick={() => fileRef.current?.click()} disabled={uploading}
                        className="absolute -bottom-1 -right-1 w-7 h-7 rounded-full bg-primary text-white flex items-center justify-center shadow-lg hover:bg-primary-dark transition-all border-2 border-white">
                        {uploading ? <Loader2 className="w-3 h-3 animate-spin" /> : <Camera className="w-3.5 h-3.5" />}
                      </button>
                    </div>
                    <h2 className="text-base font-bold text-stone-900 truncate">{name}</h2>
                    <p className="text-xs text-stone-400 truncate">{user.email}</p>
                    <span className="inline-block mt-2 px-2.5 py-0.5 rounded-full bg-primary/10 text-primary text-2xs font-medium">
                      {user.roles?.map(r => r.name).join(', ') || t('auth.roleUser')}
                    </span>
                  </>
                )}
              </div>

              {/* Sidebar nav */}
              <nav className="card-3d p-2 space-y-0.5">
                {TABS.map(tabItem => (
                  <button key={tabItem.key} onClick={() => setTab(tabItem.key)}
                    className={`w-full flex items-center ${sidebarCollapsed ? 'justify-center' : 'gap-3 px-4'} py-2.5 rounded-xl text-sm font-medium transition-all ${
                      tab === tabItem.key
                        ? 'bg-primary/10 text-primary'
                        : 'text-stone-600 hover:bg-stone-50 hover:text-stone-800'
                    }`}>
                    <tabItem.icon className={`w-4 h-4 shrink-0 ${tab === tabItem.key ? 'text-primary' : 'text-stone-400'}`} />
                    {!sidebarCollapsed && t(TAB_KEYS[tabItem.key])}
                  </button>
                ))}
                <hr className="my-2 border-stone-100" />
                <button onClick={() => { logout(); navigate('/'); }}
                  className={`w-full flex items-center ${sidebarCollapsed ? 'justify-center' : 'gap-3 px-4'} py-2.5 rounded-xl text-sm font-medium text-stone-500 hover:bg-red-50 hover:text-red-500 transition-all`}>
                  <LogOut className="w-4 h-4 shrink-0" />
                  {!sidebarCollapsed && t('auth.signOut')}
                </button>
              </nav>

              {/* Mini user card (collapsed only) */}
              {sidebarCollapsed && (
                <div className="mt-3 p-2 card-3d">
                  <div className="flex items-center justify-center">
                    <div className="w-7 h-7 rounded-lg bg-primary/10 flex items-center justify-center text-primary font-bold text-xs">
                      {name.charAt(0).toUpperCase()}
                    </div>
                  </div>
                </div>
              )}
            </aside>

            {/* ═══ CONTENT ═══ */}
            <main className="flex-1 min-w-0">

              {/* ── Tab: Personal Info ── */}
              {tab === 'info' && (
                <div className="card-3d p-6">
                  <div className="flex items-center gap-2 mb-6 pb-4 border-b border-beige-dark/20">
                    <div className="w-9 h-9 rounded-xl bg-primary/10 flex items-center justify-center">
                      <User className="w-5 h-5 text-primary" />
                    </div>
                    <div>
                      <h2 className="text-lg font-bold text-stone-800">{t('auth.tabInfo')}</h2>
                      <p className="text-xs text-stone-400">{t('auth.personalInfoDesc')}</p>
                    </div>
                  </div>
                  {success && (
                    <div className="bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm rounded-xl px-4 py-3 mb-5 flex items-center gap-2">
                      <svg className="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5"><path d="M20 6L9 17l-5-5"/></svg>
                      {success}
                    </div>
                  )}
                  <form onSubmit={handleSave} className="space-y-4 max-w-lg" noValidate>
                    <div>
                      <label className="text-sm font-medium text-stone-700 mb-1.5 block">{t('auth.name')}</label>
                      <input value={name} onChange={e => { setName(e.target.value); if (formErrors.name) setFormErrors(p => ({...p, name: ''})); }}
                        className={`input-field ${formErrors.name ? 'border-red-300' : ''}`} />
                      {formErrors.name && <p className="text-red-500 text-xs mt-1.5 me-1">{t(formErrors.name)}</p>}
                    </div>
                    <div>
                      <label className="text-sm font-medium text-stone-700 mb-1.5 block">{t('auth.email')}</label>
                      <input value={user.email} disabled className="input-field opacity-60 cursor-not-allowed" dir="ltr" />
                      <p className="text-xs text-stone-400 mt-1">{t('auth.emailLocked')}</p>
                    </div>
                    <div>
                      <label className="text-sm font-medium text-stone-700 mb-1.5 block">{t('auth.phone')}</label>
                      <input value={phone} onChange={e => { setPhone(e.target.value); if (formErrors.phone) setFormErrors(p => ({...p, phone: ''})); }}
                        className={`input-field ${formErrors.phone ? 'border-red-300' : ''}`} dir="ltr" placeholder="+963 XX XXX XXXX" />
                      {formErrors.phone && <p className="text-red-500 text-xs mt-1.5 me-1">{t(formErrors.phone)}</p>}
                    </div>
                    <button type="submit" disabled={saving} className="btn-primary flex items-center gap-2 !py-3 !px-6">
                      {saving ? <Loader2 className="w-4 h-4 animate-spin" /> : <Save className="w-4 h-4" />}
                      {t('auth.saveChanges')}
                    </button>
                  </form>
                </div>
              )}

              {/* ── Tab: Security ── */}
              {tab === 'security' && (
                <div className="card-3d p-6">
                  <div className="flex items-center gap-2 mb-6 pb-4 border-b border-beige-dark/20">
                    <div className="w-9 h-9 rounded-xl bg-primary/10 flex items-center justify-center">
                      <ShieldCheck className="w-5 h-5 text-primary" />
                    </div>
                    <div>
                      <h2 className="text-lg font-bold text-stone-800">{t('auth.tabSecurity')}</h2>
                      <p className="text-xs text-stone-400">{t('auth.securityDesc')}</p>
                    </div>
                  </div>
                  {passwordError && (
                    <div className="bg-red-50 border border-red-200 text-red-600 text-sm rounded-xl px-4 py-3 mb-5 flex items-center gap-2">
                      <svg className="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
                      {passwordError}
                    </div>
                  )}
                  {passwordSuccess && (
                    <div className="bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm rounded-xl px-4 py-3 mb-5 flex items-center gap-2">
                      <svg className="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5"><path d="M20 6L9 17l-5-5"/></svg>
                      {passwordSuccess}
                    </div>
                  )}
                  <form onSubmit={handleChangePassword} className="space-y-4 max-w-lg" noValidate>
                    <div>
                      <label className="text-sm font-medium text-stone-700 mb-1.5 block">{t('auth.currentPasswordLabel')}</label>
                      <div className="relative">
                        <Lock className="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-stone-300" />
                        <input type={showPassword ? 'text' : 'password'} value={currentPassword}
                          onChange={e => { setCurrentPassword(e.target.value); if (passwordErrors.current_password) setPasswordErrors(p => ({...p, current_password: ''})); }}
                          className={`input-field pr-10 ${passwordErrors.current_password ? 'border-red-300' : ''}`} dir="ltr" />
                      </div>
                      {passwordErrors.current_password && <p className="text-red-500 text-xs mt-1.5 me-1">{t(passwordErrors.current_password)}</p>}
                    </div>
                    <div>
                      <label className="text-sm font-medium text-stone-700 mb-1.5 block">{t('auth.newPasswordLabel')}</label>
                      <div className="relative">
                        <KeyRound className="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-stone-300" />
                        <input type={showPassword ? 'text' : 'password'} value={newPassword}
                          onChange={e => { setNewPassword(e.target.value); if (passwordErrors.password) setPasswordErrors(p => ({...p, password: ''})); }}
                          className={`input-field pr-10 ${passwordErrors.password ? 'border-red-300' : ''}`} dir="ltr" />
                        <button type="button" onClick={() => setShowPassword(!showPassword)}
                          className="absolute left-3 top-1/2 -translate-y-1/2 text-stone-300 hover:text-stone-500">
                          {showPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                        </button>
                      </div>
                      {passwordErrors.password && <p className="text-red-500 text-xs mt-1.5 me-1">{t(passwordErrors.password)}</p>}
                      <p className="text-xs text-stone-400 mt-1">{t('auth.passwordHint')}</p>
                    </div>
                    <div>
                      <label className="text-sm font-medium text-stone-700 mb-1.5 block">{t('auth.confirmNewPasswordLabel')}</label>
                      <input type={showPassword ? 'text' : 'password'} value={newPasswordConfirm}
                        onChange={e => { setNewPasswordConfirm(e.target.value); if (passwordErrors.password_confirmation) setPasswordErrors(p => ({...p, password_confirmation: ''})); }}
                        className={`input-field ${passwordErrors.password_confirmation ? 'border-red-300' : ''}`} dir="ltr" />
                      {passwordErrors.password_confirmation && <p className="text-red-500 text-xs mt-1.5 me-1">{t(passwordErrors.password_confirmation)}</p>}
                    </div>
                    <button type="submit" disabled={changingPassword}
                      className="btn-primary flex items-center gap-2 !py-3 !px-6">
                      {changingPassword ? <Loader2 className="w-4 h-4 animate-spin" /> : <Lock className="w-4 h-4" />}
                      {t('auth.updatePasswordBtn')}
                    </button>
                  </form>
                </div>
              )}

              {/* ── Tab: Favorites ── */}
              {tab === 'favorites' && (
                <div>
                  {favLoading ? (
                    <div className="flex items-center justify-center h-48"><Loader2 className="w-8 h-8 animate-spin text-primary" /></div>
                  ) : favorites.length === 0 ? (
                    <div className="card-3d p-12 text-center">
                      <Heart className="w-12 h-12 text-stone-300 mx-auto mb-3" />
                      <p className="text-stone-500 mb-2">{t('auth.noFavorites')}</p>
                      <Link to="/properties" className="btn-primary text-sm inline-flex items-center gap-2 !py-2 !px-4">
                        {t('nav.properties')}
                      </Link>
                    </div>
                  ) : (
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                      {favorites.map(fav => (
                        <div key={fav.id} className="relative group">
                          <PropertyCard property={fav.property} />
                          <button onClick={() => handleRemoveFav(fav.property.id)}
                            className="absolute top-2 right-2 w-8 h-8 rounded-lg bg-white/80 backdrop-blur-sm flex items-center justify-center text-red-500 hover:bg-red-50 transition-all opacity-0 group-hover:opacity-100">
                            <Trash2 className="w-4 h-4" />
                          </button>
                        </div>
                      ))}
                    </div>
                  )}
                </div>
              )}

              {/* ── Tab: Saved Searches ── */}
              {tab === 'searches' && (
                <div>
                  {searchLoading ? (
                    <div className="flex items-center justify-center h-48"><Loader2 className="w-8 h-8 animate-spin text-primary" /></div>
                  ) : searches.length === 0 ? (
                    <div className="card-3d p-12 text-center">
                      <Search className="w-12 h-12 text-stone-300 mx-auto mb-3" />
                      <p className="text-stone-500">{t('auth.noSearches')}</p>
                    </div>
                  ) : (
                    <div className="space-y-3">
                      {searches.map(s => {
                        let filters: Record<string, string> = {};
                        try { filters = JSON.parse(s.filters); } catch {}
                        return (
                          <div key={s.id} className="card-3d p-4 flex items-center justify-between group">
                            <div>
                              <div className="font-medium text-stone-900">{s.name}</div>
                              <div className="text-xs text-stone-400 mt-0.5">
                                {Object.entries(filters).map(([k, v]) => `${k}: ${v}`).join(' | ') || s.created_at}
                              </div>
                            </div>
                            <button onClick={() => handleDeleteSearch(s.id)}
                              className="p-2 rounded-lg text-stone-400 hover:text-red-500 hover:bg-red-50 transition-all">
                              <Trash2 className="w-4 h-4" />
                            </button>
                          </div>
                        );
                      })}
                    </div>
                  )}
                </div>
              )}
            </main>
          </div>
        </div>
      </div>
      <Footer />
    </div>
  );
}
