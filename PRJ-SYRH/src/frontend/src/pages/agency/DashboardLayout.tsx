import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Link, Outlet, useLocation } from 'react-router-dom';
import {
  LayoutDashboard, Building2, Users, MessageSquare, FileText,
  PiggyBank, CreditCard, Settings, Menu, LogOut, ChevronLeft, MessageCircle,
} from 'lucide-react';
import { useAuth } from '../../auth/AuthContext';

const navItems = [
  { to: '/dashboard', icon: LayoutDashboard, labelKey: 'home.stats.properties', exact: true, ar: 'الرئيسية', en: 'Dashboard' },
  { to: '/dashboard/properties', icon: Building2, labelKey: 'nav.properties', ar: 'العقارات', en: 'Properties' },
  { to: '/dashboard/agents', icon: Users, labelKey: 'agent.title', ar: 'الوكلاء', en: 'Agents' },
  { to: '/dashboard/inquiries', icon: MessageSquare, labelKey: 'property.inquiry', ar: 'الاستفسارات', en: 'Inquiries' },
  { to: '/dashboard/chat', icon: MessageCircle, labelKey: 'nav.chat', ar: 'الدردشة', en: 'Chat' },
  { to: '/dashboard/deals', icon: FileText, labelKey: 'about.stats', ar: 'الصفقات', en: 'Deals' },
  { to: '/dashboard/commission', icon: PiggyBank, labelKey: 'property.currency', ar: 'العمولات', en: 'Commission' },
  { to: '/dashboard/subscription', icon: CreditCard, labelKey: 'home.ctaTitle', ar: 'الاشتراك', en: 'Subscription' },
  { to: '/dashboard/profile', icon: Settings, labelKey: 'nav.dashboard', ar: 'الملف الشخصي', en: 'Profile' },
];

export default function DashboardLayout() {
  const { t, i18n } = useTranslation();
  const location = useLocation();
  const { user, logout } = useAuth();
  const isAr = i18n.language === 'ar';
  const isChat = location.pathname.startsWith('/dashboard/chat');
  const [sidebarOpen, setSidebarOpen] = useState(false);

  const isActive = (path: string, exact?: boolean) => {
    if (exact) return location.pathname === path;
    return location.pathname.startsWith(path);
  };

  return (
    <div dir={isAr ? 'rtl' : 'ltr'} className="min-h-screen bg-admin-bg flex">
      {/* Mobile overlay */}
      {sidebarOpen && (
        <div className="fixed inset-0 bg-black/30 z-40 md:hidden" onClick={() => setSidebarOpen(false)} />
      )}

      {/* Sidebar */}
      <aside className={`fixed md:sticky top-0 h-screen w-64 bg-white border-s border-beige-dark/50 z-50 transition-transform duration-300 ${
        sidebarOpen ? 'translate-x-0' : isAr ? 'translate-x-full' : '-translate-x-full'
      } md:translate-x-0 md:rtl:translate-x-0 flex flex-col`}>
        <div className="p-5 border-b border-beige-dark/30">
          <Link to="/dashboard" className="flex items-center gap-3">
            <div className="w-9 h-9 rounded-xl bg-primary flex items-center justify-center">
              <Building2 className="w-5 h-5 text-white" />
            </div>
            <div>
              <div className="font-bold text-stone-900 text-sm">سوريا هومز</div>
              <div className="text-xs text-stone-500">{t('nav.dashboard')}</div>
            </div>
          </Link>
        </div>

        <nav className="flex-1 p-3 space-y-1">
          {navItems.map((item) => (
            <Link
              key={item.to}
              to={item.to}
              onClick={() => setSidebarOpen(false)}
              className={`flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all ${
                isActive(item.to, item.exact)
                  ? 'bg-primary/10 text-primary'
                  : 'text-stone-600 hover:bg-beige hover:text-stone-900'
              }`}
            >
              <item.icon className="w-4 h-4" />
              <span>{isAr ? item.ar : item.en}</span>
            </Link>
          ))}
        </nav>

        <div className="p-3 border-t border-beige-dark/30">
          <div className="flex items-center gap-3 px-4 py-2 text-xs text-stone-400">
            <div className="w-7 h-7 rounded-lg bg-primary/10 flex items-center justify-center text-primary font-bold text-xs">
              {user?.name?.charAt(0) || '?'}
            </div>
            <span className="truncate">{user?.name || ''}</span>
          </div>
        </div>
      </aside>

      {/* Main */}
      <div className="flex-1 flex flex-col min-h-screen">
        {/* Topbar */}
        <header className="bg-white border-b border-beige-dark/30 sticky top-0 z-30">
          <div className="flex items-center justify-between px-4 md:px-6 h-16">
            <button onClick={() => setSidebarOpen(true)} className="md:hidden p-2 rounded-lg hover:bg-beige text-stone-600">
              <Menu className="w-5 h-5" />
            </button>

            <div className="hidden md:flex items-center gap-2 text-sm text-stone-500">
              <Link to="/" className="hover:text-primary transition-colors">{t('nav.home')}</Link>
              <ChevronLeft className="w-3 h-3 lucide-rtl" />
              <span className="text-stone-800 font-medium">{t('nav.dashboard')}</span>
            </div>

            <div className="flex items-center gap-3">
              <Link to="/" className="text-xs text-stone-400 hover:text-primary transition-colors">
                {t('nav.home')}
              </Link>
              <button onClick={() => logout()}
                className="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs text-stone-500 hover:text-red-500 hover:bg-red-50 transition-all">
                <LogOut className="w-3.5 h-3.5" />
                {t('nav.logout')}
              </button>
            </div>
          </div>
        </header>

        {/* Content */}
        <main className={`flex-1 ${!isChat && 'p-4 md:p-8'}`}>
          <Outlet />
        </main>
      </div>
    </div>
  );
}
