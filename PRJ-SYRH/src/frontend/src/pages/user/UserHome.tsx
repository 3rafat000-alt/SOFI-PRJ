import { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { Link } from 'react-router-dom';
import { Heart, MessageSquare, Search, MessageCircle, Loader2, ArrowLeft } from 'lucide-react';
import { fetchUserDashboard, fetchUserChatUnread, type UserDashboard } from '../../api/user';

export default function UserHome() {
  const { i18n } = useTranslation();
  const isAr = i18n.language === 'ar';
  const [data, setData] = useState<UserDashboard | null>(null);
  const [loading, setLoading] = useState(true);
  const [unreadChat, setUnreadChat] = useState(0);

  useEffect(() => {
    fetchUserDashboard()
      .then(res => setData(res.data))
      .catch(() => {})
      .finally(() => setLoading(false));
    fetchUserChatUnread().then(r => setUnreadChat(r.data?.unread_count ?? 0)).catch(() => {});
  }, []);

  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <Loader2 className="w-8 h-8 animate-spin text-primary" />
      </div>
    );
  }

  const stats = [
    { label: isAr ? 'المفضلة' : 'Favorites', value: data?.favorites_count || 0, icon: Heart, color: 'text-red-500 bg-red-50', link: '/user/favorites' },
    { label: isAr ? 'الاستفسارات' : 'Inquiries', value: data?.inquiries_count || 0, icon: MessageSquare, color: 'text-blue-500 bg-blue-50', link: '/user/inquiries' },
    { label: isAr ? 'المحادثات' : 'Chat', value: unreadChat, icon: MessageCircle, color: 'text-green-500 bg-green-50', link: '/user/chat', badge: unreadChat > 0 },
    { label: isAr ? 'عمليات البحث' : 'Saved Searches', value: data?.searches_count || 0, icon: Search, color: 'text-amber-500 bg-amber-50', link: '/user/searches' },
  ];

  return (
    <div>
      <h1 className="text-2xl font-bold text-stone-900 mb-6">{isAr ? 'لوحة التحكم' : 'Dashboard'}</h1>

      {/* Stats cards */}
      <div className="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
        {stats.map((s) => (
          <Link key={s.label} to={s.link} className="card-3d p-5 flex items-center gap-4 hover:shadow-lg transition-all group">
            <div className={`w-12 h-12 rounded-2xl ${s.color} flex items-center justify-center`}>
              <s.icon className="w-6 h-6" />
            </div>
            <div>
              <div className="text-2xl font-bold text-stone-900">{s.value}</div>
              <div className="text-sm text-stone-500">{s.label}</div>
            </div>
          </Link>
        ))}
      </div>

      {/* Recent inquiries */}
      <div className="card-3d p-6 mb-6">
        <div className="flex items-center justify-between mb-4">
          <h2 className="text-lg font-bold text-stone-900">{isAr ? 'آخر الاستفسارات' : 'Recent Inquiries'}</h2>
          <Link to="/user/inquiries" className="text-sm text-primary hover:underline flex items-center gap-1">
            {isAr ? 'عرض الكل' : 'View All'}
            <ArrowLeft className="w-3.5 h-3.5 lucide-rtl" />
          </Link>
        </div>
        {data?.recent_inquiries?.length ? (
          <div className="space-y-3">
            {data.recent_inquiries.map((inq) => (
              <div key={inq.id} className="flex items-center justify-between py-2 border-b border-beige-dark/30 last:border-0">
                <div>
                  <div className="text-sm font-medium text-stone-900">
                    {inq.property ? (isAr ? inq.property.title_ar : inq.property.title_en) : `#${inq.property_id}`}
                  </div>
                  <div className="text-xs text-stone-400">
                    {new Date(inq.created_at).toLocaleDateString(isAr ? 'ar' : 'en')}
                    {' · '}
                    <span className={`inline-block px-2 py-0.5 rounded-full text-2xs font-medium ${
                      inq.status === 'new' ? 'bg-blue-50 text-blue-600' :
                      inq.status === 'read' ? 'bg-amber-50 text-amber-600' :
                      inq.status === 'contacted' ? 'bg-green-50 text-green-600' :
                      'bg-stone-50 text-stone-500'
                    }`}>
                      {inq.status === 'new' ? (isAr ? 'جديد' : 'New') :
                       inq.status === 'read' ? (isAr ? 'مقروء' : 'Read') :
                       inq.status === 'contacted' ? (isAr ? 'تم التواصل' : 'Contacted') :
                       inq.status}
                    </span>
                  </div>
                </div>
                <Link to={`/properties/${inq.property?.slug || ''}`} className="text-xs text-primary hover:underline">
                  {isAr ? 'عرض العقار' : 'View Property'}
                </Link>
              </div>
            ))}
          </div>
        ) : (
          <div className="text-center py-8 text-stone-400 text-sm">
            {isAr ? 'لا توجد استفسارات بعد' : 'No inquiries yet'}
          </div>
        )}
      </div>

      {/* Quick actions */}
      <div className="card-3d p-6">
        <h2 className="text-lg font-bold text-stone-900 mb-4">{isAr ? 'إجراءات سريعة' : 'Quick Actions'}</h2>
        <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
          <Link to="/properties" className="p-4 rounded-2xl bg-primary/5 hover:bg-primary/10 transition-all text-center">
            <div className="font-medium text-primary mb-1">{isAr ? 'تصفح العقارات' : 'Browse Properties'}</div>
            <div className="text-xs text-stone-500">{isAr ? 'ابحث عن عقارك المناسب' : 'Find your perfect property'}</div>
          </Link>
          <Link to="/user/favorites" className="p-4 rounded-2xl bg-red-50 hover:bg-red-100 transition-all text-center">
            <div className="font-medium text-red-500 mb-1">{isAr ? 'المفضلة' : 'Favorites'}</div>
            <div className="text-xs text-stone-500">{isAr ? 'عقاراتك المحفوظة' : 'Your saved properties'}</div>
          </Link>
          <Link to="/user/chat" className="p-4 rounded-2xl bg-green-50 hover:bg-green-100 transition-all text-center relative">
            <div className="font-medium text-green-600 mb-1">{isAr ? 'المحادثات' : 'Chat'}</div>
            <div className="text-xs text-stone-500">{isAr ? 'راسل الوكالات' : 'Message agencies'}</div>
            {unreadChat > 0 && (
              <span className="absolute -top-1 -right-1 bg-red-500 text-white text-2xs rounded-full w-5 h-5 flex items-center justify-center font-bold">
                {unreadChat > 9 ? '9+' : unreadChat}
              </span>
            )}
          </Link>
          <Link to="/user/profile" className="p-4 rounded-2xl bg-stone-50 hover:bg-stone-100 transition-all text-center">
            <div className="font-medium text-stone-700 mb-1">{isAr ? 'الملف الشخصي' : 'Profile'}</div>
            <div className="text-xs text-stone-500">{isAr ? 'تحديث معلوماتك' : 'Update your info'}</div>
          </Link>
        </div>
      </div>
    </div>
  );
}
