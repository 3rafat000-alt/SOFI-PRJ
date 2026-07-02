import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Outlet, Link, useLocation, useNavigate } from 'react-router-dom';
import {
  LayoutDashboard, Users, Building2, Grid3X3, CreditCard,
  MessageSquare, Star, Settings, LogOut, Menu, X, MapPin,
} from 'lucide-react';
import { useAuth } from '../../auth/AuthContext';

export default function AdminLayout() {
  const { i18n } = useTranslation();
  const isAr = i18n.language === 'ar';
  const location = useLocation();
  const navigate = useNavigate();
  const { user, logout } = useAuth();
  const [sidebarOpen, setSidebarOpen] = useState(false);

  const handleLogout = () => { logout(); navigate('/'); };

  const items = [
    { path: '/admin', icon: LayoutDashboard, label: isAr ? 'لوحة التحكم' : 'Dashboard' },
    { path: '/admin/users', icon: Users, label: isAr ? 'المستخدمين' : 'Users' },
    { path: '/admin/agencies', icon: Building2, label: isAr ? 'الوكالات' : 'Agencies' },
    { path: '/admin/properties', icon: Grid3X3, label: isAr ? 'العقارات' : 'Properties' },
    { path: '/admin/plans', icon: CreditCard, label: isAr ? 'الباقات' : 'Plans' },
    { path: '/admin/messages', icon: MessageSquare, label: isAr ? 'الرسائل' : 'Messages' },
    { path: '/admin/reviews', icon: Star, label: isAr ? 'التقييمات' : 'Reviews' },
    { path: '/admin/areas', icon: MapPin, label: isAr ? 'المناطق' : 'Areas' },
    { path: '/admin/settings', icon: Settings, label: isAr ? 'الإعدادات' : 'Settings' },
  ];

  return (
    <div dir={isAr ? 'rtl' : 'ltr'} className="min-h-screen bg-admin-bg flex">
      {/* Mobile overlay */}
      {sidebarOpen && (
        <div className="fixed inset-0 bg-black/30 z-40 md:hidden" onClick={() => setSidebarOpen(false)} />
      )}

      {/* Sidebar */}
      <aside className={`fixed md:sticky top-0 h-screen w-64 bg-white border-s border-beige-dark z-50 transform transition-transform duration-300 ${
        sidebarOpen ? 'translate-x-0' : isAr ? 'translate-x-full' : '-translate-x-full'
      } md:translate-x-0 md:rtl:translate-x-0 flex flex-col`}>
        <div className="p-5 border-b border-beige-dark">
          <Link to="/admin" className="flex items-center gap-3">
            <div className="w-9 h-9 rounded-xl bg-primary flex items-center justify-center text-white font-bold text-sm">A</div>
            <span className="font-bold text-stone-800">{isAr ? 'لوحة التحكم' : 'Admin Panel'}</span>
          </Link>
        </div>
        <nav className="flex-1 p-3 space-y-1 overflow-y-auto">
          {items.map(item => {
            const active = location.pathname === item.path || (item.path !== '/admin' && location.pathname.startsWith(item.path));
            return (
              <Link key={item.path} to={item.path}
                onClick={() => setSidebarOpen(false)}
                className={`flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all ${
                  active ? 'bg-primary text-white shadow-sm' : 'text-stone-500 hover:bg-beige hover:text-stone-800'
                }`}>
                <item.icon className="w-4 h-4" />
                {item.label}
              </Link>
            );
          })}
        </nav>
        <div className="p-3 border-t border-beige-dark">
          <button onClick={handleLogout}
            className="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-stone-500 hover:bg-red-50 hover:text-red-600 transition-all w-full">
            <LogOut className="w-4 h-4" />
            {isAr ? 'تسجيل خروج' : 'Logout'}
          </button>
        </div>
      </aside>

      {/* Main */}
      <div className="flex-1 flex flex-col min-h-screen">
        {/* Top bar */}
        <header className="sticky top-0 bg-white/90 backdrop-blur-sm border-b border-beige-dark z-30">
          <div className="flex items-center justify-between px-4 md:px-6 h-16">
            <button onClick={() => setSidebarOpen(!sidebarOpen)} className="md:hidden p-2 text-stone-500 hover:bg-beige rounded-xl">
              {sidebarOpen ? <X className="w-5 h-5" /> : <Menu className="w-5 h-5" />}
            </button>
            <div className="hidden md:block" />
            <div className="flex items-center gap-3">
              <Link to="/" className="text-sm text-stone-400 hover:text-primary transition-colors">
                {isAr ? 'العودة للموقع' : 'Back to site'}
              </Link>
              <div className="w-8 h-8 rounded-lg bg-primary flex items-center justify-center text-white text-xs font-bold">
                {user?.name?.charAt(0) || 'A'}
              </div>
            </div>
          </div>
        </header>

        {/* Content */}
        <main className="flex-1 p-4 md:p-6">
          <Outlet />
        </main>
      </div>
    </div>
  );
}
