import { useTranslation } from 'react-i18next';
import {
  Menu, X, Building2, UserPlus, LayoutDashboard, LogOut,
  Globe, Home, Search, Info, Mail, MessageCircle,
} from 'lucide-react';
import { useState, useEffect, useRef } from 'react';
import { Link, useLocation } from 'react-router-dom';
import { useAuth } from '../auth/AuthContext';

const langMap: Record<string, string> = { ar: 'English', en: 'العربية' };

const navLinks = [
  { to: '/', labelKey: 'nav.home', icon: Home },
  { to: '/properties', labelKey: 'nav.properties', icon: Search },
  { to: '/agencies', labelKey: 'nav.agencies', icon: Building2 },
  { to: '/about', labelKey: 'nav.about', icon: Info },
  { to: '/contact', labelKey: 'nav.contact', icon: Mail },
];

function getInitials(name: string): string {
  return name
    .split(' ')
    .map(w => w[0])
    .filter(Boolean)
    .slice(0, 2)
    .join('')
    .toUpperCase();
}

export default function Navbar() {
  const { t, i18n } = useTranslation();
  const location = useLocation();
  const [open, setOpen] = useState(false);
  const [scrolled, setScrolled] = useState(false);
  const { user, logout } = useAuth();
  const isAr = i18n.language === 'ar';
  const isDarkHero = location.pathname === '/'
    || location.pathname === '/about'
    || location.pathname === '/contact';
  const isLightBg = !isDarkHero || scrolled;
  const s = isLightBg;
  const menuRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    const onScroll = () => setScrolled(window.scrollY > 15);
    window.addEventListener('scroll', onScroll, { passive: true });
    return () => window.removeEventListener('scroll', onScroll);
  }, []);

  useEffect(() => { setOpen(false); }, [location]);

  // Close mobile menu on outside click
  useEffect(() => {
    if (!open) return;
    const onClick = (e: MouseEvent) => {
      if (menuRef.current && !menuRef.current.contains(e.target as Node)) setOpen(false);
    };
    document.addEventListener('mousedown', onClick);
    return () => document.removeEventListener('mousedown', onClick);
  }, [open]);

  const toggleLang = () => {
    const next = i18n.language === 'ar' ? 'en' : 'ar';
    i18n.changeLanguage(next);
  };

  const isActive = (path: string) => {
    if (path === '/') return location.pathname === '/';
    return location.pathname.startsWith(path);
  };

  const avatarLetter = user?.name ? getInitials(user.name)[0] : '?';
  const dashboardLink = user?.roles?.some(r => r.name === 'admin')
    ? '/admin'
    : user?.roles?.some(r => r.name === 'agency')
      ? '/dashboard'
      : '/user/dashboard';

  return (
    <nav
      dir={isAr ? 'rtl' : 'ltr'}
      className={`fixed top-0 left-0 right-0 z-50 transition-all duration-500 ${
        s
          ? 'bg-white/85 backdrop-blur-2xl shadow-[0_1px_20px_rgba(0,0,0,0.06)]'
          : 'bg-gradient-to-b from-black/60 via-black/30 to-transparent'
      }`}
    >
      {/* Top accent line */}
      <div
        className={`absolute top-0 left-0 right-0 h-[2px] transition-all duration-700 ease-out ${
          s ? 'opacity-100 scale-x-100' : 'opacity-0 scale-x-0'
        } bg-gradient-to-r from-primary/40 via-gold to-primary/40`}
      />

      {/* Bottom separator */}
      <div
        className={`absolute bottom-0 left-0 right-0 h-px transition-all duration-500 ${
          s ? 'bg-beige-dark/40' : 'bg-white/8'
        }`}
      />

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex items-center justify-between h-16 md:h-20">
          {/* ─── Logo ─── */}
          <Link to="/" className="flex items-center gap-2.5 group relative shrink-0">
            <div
              className={`w-10 h-10 rounded-xl flex items-center justify-center transition-all duration-300 group-hover:scale-105 group-hover:shadow-lg ${
                s
                  ? 'bg-gradient-to-br from-primary to-primary-dark shadow-md shadow-primary/20'
                  : 'bg-white/12 backdrop-blur-sm border border-white/20 shadow-lg shadow-black/5'
              }`}
            >
              <Building2
                className="w-5.5 h-5.5 transition-transform duration-300 group-hover:scale-110 text-white"
                strokeWidth={1.8}
              />
            </div>
            <div className="flex flex-col items-start leading-tight">
              <span
                className={`font-bold text-xl tracking-tight transition-colors duration-300 ${
                  s ? 'text-stone-900' : 'text-white drop-shadow-sm'
                }`}
              >
                سوريا هومز
              </span>
              <span
                className={`text-2xs font-medium tracking-wider transition-colors duration-300 ${
                  s ? 'text-stone-400' : 'text-white/50'
                }`}
              >
                {isAr ? 'SYRIA HOMES' : 'سوريا هومز'}
              </span>
            </div>
            {/* Brand glow dot */}
            <span
              className={`w-1.5 h-1.5 rounded-full transition-all duration-500 ${
                s
                  ? 'bg-gold'
                  : 'bg-gold shadow-[0_0_8px] shadow-gold/60'
              }`}
            />
          </Link>

          {/* ═══════════ Desktop ═══════════ */}
          <div className="hidden md:flex items-center gap-1">
            {/* ─── Nav links ─── */}
            {navLinks.map((l) => {
              const Icon = l.icon;
              return (
                <Link
                  key={l.to}
                  to={l.to}
                  className={`relative flex items-center gap-1.5 px-3.5 py-2 rounded-lg text-sm font-medium transition-all duration-200 ${
                    isActive(l.to)
                      ? s
                        ? 'text-primary'
                        : 'text-white'
                      : s
                        ? 'text-stone-500 hover:text-stone-800 hover:bg-stone-100/70'
                        : 'text-white/70 hover:text-white hover:bg-white/10'
                  }`}
                >
                  <Icon className="w-4 h-4" strokeWidth={1.8} />
                  {t(l.labelKey)}
                  {/* Active underline */}
                  {isActive(l.to) && (
                    <span
                      className={`absolute -bottom-0.5 left-2 right-2 h-[2px] rounded-full transition-all duration-300 ${
                        s
                          ? 'bg-primary'
                          : 'bg-gold shadow-[0_0_10px] shadow-gold/50'
                      }`}
                    />
                  )}
                </Link>
              );
            })}

            {/* ─── Separator ─── */}
            <div
              className={`w-px h-5 mx-2.5 transition-colors duration-300 ${
                s ? 'bg-beige-dark/50' : 'bg-white/12'
              }`}
            />

            {/* ─── Language toggle ─── */}
            <button
              onClick={toggleLang}
              title={langMap[i18n.language]}
              className={`flex items-center justify-center w-9 h-9 rounded-lg transition-all duration-200 group ${
                s
                  ? 'text-stone-500 hover:bg-beige hover:text-stone-700'
                  : 'text-white/80 hover:bg-white/12 hover:text-white'
              }`}
            >
              <Globe
                className={`w-4 h-4 transition-transform duration-300 group-hover:rotate-12 ${s ? 'text-stone-400' : 'text-white/60'}`}
              />
            </button>

            {/* ─── Auth ─── */}
            {user ? (
              <div className="flex items-center gap-1.5 mr-1">
                <Link
                  to={dashboardLink}
                  className={`flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 ${
                    s
                      ? 'bg-primary/8 text-primary hover:bg-primary/15 border border-primary/8 hover:border-primary/20'
                      : 'bg-white/12 text-white hover:bg-white/20 backdrop-blur-sm border border-white/10'
                  }`}
                >
                  <LayoutDashboard className="w-4 h-4" />
                  <span className="hidden lg:inline">{t('nav.dashboard')}</span>
                </Link>
                <div className="flex items-center gap-2 pl-1.5 pr-0.5 py-1">
                  {/* Avatar */}
                  <div
                    className={`w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition-all duration-200 ${
                      s
                        ? 'bg-primary/10 text-primary border border-primary/20'
                        : 'bg-white/15 text-white border border-white/20'
                    }`}
                  >
                    {avatarLetter}
                  </div>
                  <button
                    onClick={logout}
                    className={`p-2 rounded-lg text-sm font-medium transition-all duration-200 ${
                      s
                        ? 'text-stone-400 hover:text-red-500 hover:bg-red-50'
                        : 'text-white/60 hover:text-white hover:bg-white/8'
                    }`}
                    title={t('nav.logout')}
                  >
                    <LogOut className="w-4 h-4" />
                  </button>
                </div>
              </div>
            ) : (
              <div className="flex items-center mr-1">
                {/* Single CTA — حساب جديد */}
                <Link
                  to="/register"
                  className="btn-gold text-sm !py-2 !px-5 shadow-md shadow-gold/20 inline-flex items-center gap-2"
                >
                  <UserPlus className="w-4 h-4" />
                  {t('nav.register')}
                </Link>
              </div>
            )}
          </div>

          {/* ═══════════ Mobile toggle ═══════════ */}
          <button
            onClick={() => setOpen(!open)}
            className={`md:hidden p-2.5 rounded-xl transition-all duration-200 ${
              s
                ? 'hover:bg-beige/80 text-stone-600 hover:shadow-sm active:scale-95'
                : 'hover:bg-white/12 text-white active:scale-95'
            } ${open ? (s ? 'bg-beige/80' : 'bg-white/12') : ''}`}
            aria-label="Toggle menu"
          >
            <div className="relative w-5 h-5 flex items-center justify-center">
              <span
                className={`absolute transition-all duration-300 ${
                  open ? 'opacity-0 rotate-90 scale-75' : 'opacity-100 rotate-0 scale-100'
                }`}
              >
                <Menu className="w-5 h-5" />
              </span>
              <span
                className={`absolute transition-all duration-300 ${
                  open ? 'opacity-100 rotate-0 scale-100' : 'opacity-0 -rotate-90 scale-75'
                }`}
              >
                <X className="w-5 h-5" />
              </span>
            </div>
          </button>
        </div>

        {/* ═══════════ Mobile Menu ═══════════ */}
        <div
          ref={menuRef}
          className={`md:hidden overflow-hidden transition-all duration-300 ease-out ${
            open
              ? 'max-h-[700px] opacity-100 mb-4'
              : 'max-h-0 opacity-0 mb-0'
          }`}
        >
          <div
            className={`rounded-2xl overflow-hidden border transition-all duration-300 ${
              s
                ? 'bg-white shadow-xl border-beige-dark/40'
                : 'bg-stone-900/90 backdrop-blur-2xl border border-white/10 shadow-2xl'
            }`}
          >
            {/* ─── Nav links ─── */}
            <div className="p-2 space-y-0.5">
              {navLinks.map((l) => {
                const Icon = l.icon;
                return (
                  <Link
                    key={l.to}
                    to={l.to}
                    className={`flex items-center gap-3 px-4 py-3.5 rounded-xl text-sm font-medium transition-all duration-200 ${
                      isActive(l.to)
                        ? s
                          ? 'bg-primary/8 text-primary'
                          : 'bg-white/12 text-white'
                        : s
                          ? 'text-stone-600 hover:bg-beige/70 hover:text-stone-800'
                          : 'text-white/80 hover:bg-white/8 hover:text-white'
                    }`}
                  >
                    <Icon className="w-4.5 h-4.5" strokeWidth={1.8} />
                    {t(l.labelKey)}
                    {/* Active dot indicator */}
                    <span
                      className={`ml-auto w-1.5 h-1.5 rounded-full transition-all duration-300 ${
                        isActive(l.to)
                          ? s
                            ? 'bg-primary scale-100'
                            : 'bg-gold scale-100 shadow-[0_0_6px] shadow-gold/50'
                          : 'bg-transparent scale-0'
                      }`}
                    />
                  </Link>
                );
              })}
            </div>

            {/* ─── Auth section ─── */}
            {user ? (
              <>
                <div className={`h-px mx-4 ${s ? 'bg-beige-dark/40' : 'bg-white/8'}`} />
                <div className="p-2 space-y-0.5">
                  {/* User info */}
                  <div className={`flex items-center gap-3 px-4 py-3 rounded-xl ${s ? 'text-stone-500' : 'text-white/60'}`}>
                    <div
                      className={`w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold ${
                        s ? 'bg-primary/10 text-primary' : 'bg-white/15 text-white'
                      }`}
                    >
                      {avatarLetter}
                    </div>
                    <div className="flex flex-col leading-tight">
                      <span className={`text-sm font-medium ${s ? 'text-stone-800' : 'text-white'}`}>
                        {user.name}
                      </span>
                      <span className="text-xs opacity-60">{user.email}</span>
                    </div>
                  </div>
                  <Link
                    to={dashboardLink}
                    className={`flex items-center gap-3 px-4 py-3.5 rounded-xl text-sm font-medium transition-all duration-200 ${
                      s ? 'text-primary hover:bg-primary/5' : 'text-white hover:bg-white/8'
                    }`}
                  >
                    <LayoutDashboard className="w-4 h-4" />
                    {t('nav.dashboard')}
                  </Link>
                  {(!user?.roles?.some((r: any) => r.name === 'admin') && !user?.roles?.some((r: any) => r.name === 'agency')) && (
                    <Link
                      to="/user/chat"
                      className={`flex items-center gap-3 px-4 py-3.5 rounded-xl text-sm font-medium transition-all duration-200 ${
                        s ? 'text-stone-600 hover:bg-beige/70' : 'text-white/80 hover:bg-white/8'
                      }`}
                    >
                      <MessageCircle className="w-4 h-4" />
                      {isAr ? 'المحادثات' : 'Chat'}
                    </Link>
                  )}
                  <button
                    onClick={logout}
                    className="flex items-center gap-3 w-full text-right px-4 py-3.5 rounded-xl text-sm font-medium transition-all duration-200 text-red-400 hover:bg-red-50/10"
                  >
                    <LogOut className="w-4 h-4" />
                    {t('nav.logout')}
                  </button>
                </div>
              </>
            ) : (
              <>
                <div className={`h-px mx-4 ${s ? 'bg-beige-dark/40' : 'bg-white/8'}`} />
                <div className="p-2">
                  <Link
                    to="/register"
                    className="flex items-center gap-3 justify-center px-4 py-3.5 rounded-xl text-sm font-medium bg-gold text-white shadow-md shadow-gold/20 hover:shadow-lg hover:shadow-gold/25 transition-all duration-200 active:scale-[0.98]"
                  >
                    <UserPlus className="w-4 h-4" />
                    {t('nav.register')}
                  </Link>
                </div>
              </>
            )}

            {/* ─── Language & bottom ─── */}
            <div className={`h-px mx-4 ${s ? 'bg-beige-dark/40' : 'bg-white/8'}`} />
            <div className="p-2">
              <button
                onClick={toggleLang}
                title={langMap[i18n.language]}
                className={`flex items-center justify-center w-full py-3 rounded-xl transition-all duration-200 ${
                  s ? 'text-stone-500 hover:bg-beige/70' : 'text-white/60 hover:bg-white/8'
                }`}
              >
                <Globe className="w-5 h-5" />
              </button>
            </div>
          </div>
        </div>
      </div>
    </nav>
  );
}
