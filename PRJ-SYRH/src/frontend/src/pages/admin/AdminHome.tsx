import { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { Link } from 'react-router-dom';
import {
  Users, Building2, Grid3X3, MessageSquare, CreditCard, DollarSign,
  Loader2, Clock, CheckCircle2, XCircle, ArrowLeft, Shield, ExternalLink,
} from 'lucide-react';
import { fetchAdminDashboard, type AdminDashboard } from '../../api/admin';

export default function AdminHome() {
  const { i18n } = useTranslation();
  const isAr = i18n.language === 'ar';
  const [data, setData] = useState<AdminDashboard | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchAdminDashboard().then(setData).finally(() => setLoading(false));
  }, []);

  if (loading) return <div className="flex items-center justify-center h-64"><Loader2 className="w-8 h-8 animate-spin text-primary" /></div>;

  const cards = [
    { label: isAr ? 'المستخدمين' : 'Users', value: data?.total_users ?? 0, icon: Users, color: 'bg-primary/10 text-primary' },
    { label: isAr ? 'الوكالات' : 'Agencies', value: data?.total_agencies ?? 0, icon: Building2, color: 'bg-gold/10 text-gold-dark' },
    { label: isAr ? 'العقارات' : 'Properties', value: data?.total_properties ?? 0, icon: Grid3X3, color: 'bg-blue-500/10 text-blue-600' },
    { label: isAr ? 'الاستفسارات' : 'Inquiries', value: data?.total_inquiries ?? 0, icon: MessageSquare, color: 'bg-purple-500/10 text-purple-600' },
    { label: isAr ? 'الباقات النشطة' : 'Active Plans', value: data?.active_plans ?? 0, icon: CreditCard, color: 'bg-emerald-500/10 text-emerald-600' },
    { label: isAr ? 'الإيرادات الشهرية' : 'Monthly Revenue', value: `${(data?.monthly_revenue ?? 0).toLocaleString()} $`, icon: DollarSign, color: 'bg-amber-500/10 text-amber-600' },
  ];

  const hasOperations = (data?.pending_agencies ?? 0) > 0 || (data?.unread_messages ?? 0) > 0;

  return (
    <div>
      <h1 className="text-2xl font-bold text-stone-900 mb-6">{isAr ? 'لوحة التحكم' : 'Dashboard'}</h1>

      {/* Stats Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
        {cards.map((card, i) => (
          <div key={i} className="card-3d p-5">
            <div className="flex items-center justify-between mb-3">
              <div className={`w-10 h-10 rounded-xl ${card.color} flex items-center justify-center`}>
                <card.icon className="w-5 h-5" />
              </div>
            </div>
            <div className="text-2xl font-bold text-stone-900">{card.value}</div>
            <div className="text-sm text-stone-500 mt-1">{card.label}</div>
          </div>
        ))}
      </div>

      {/* SAKK Merchant Account Card */}
      {data?.sakk && (
        <div className="card-3d p-5 mb-6">
          <div className="flex items-center justify-between mb-4">
            <h2 className="font-bold text-stone-900 flex items-center gap-2">
              <Shield className="w-5 h-5 text-primary" />
              {isAr ? 'حساب ساك (بوابة الدفع)' : 'SAKK Payment Gateway'}
            </h2>
            <Link to="/admin/settings" className="text-xs text-primary hover:text-primary-dark font-medium flex items-center gap-1">
              {isAr ? 'الإعدادات' : 'Settings'} <ExternalLink className="w-3 h-3" />
            </Link>
          </div>
          <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div>
              <div className="text-xs text-stone-400 mb-1">{isAr ? 'حالة الحساب' : 'Account Status'}</div>
              <div className={`text-sm font-medium flex items-center gap-1.5 ${data.sakk.configured ? 'text-emerald-600' : 'text-amber-600'}`}>
                <span className={`w-2 h-2 rounded-full ${data.sakk.configured ? 'bg-emerald-500' : 'bg-amber-500'}`} />
                {data.sakk.configured
                  ? (isAr ? 'مرتبط' : 'Connected')
                  : (isAr ? 'غير مرتبط' : 'Not Connected')}
              </div>
            </div>
            <div>
              <div className="text-xs text-stone-400 mb-1">{isAr ? 'معرف التاجر' : 'Merchant ID'}</div>
              <div className="text-sm font-mono text-stone-800 truncate" title={data.sakk.merchant_id || ''}>
                {data.sakk.merchant_id || '—'}
              </div>
            </div>
            <div>
              <div className="text-xs text-stone-400 mb-1">{isAr ? 'الوضع' : 'Mode'}</div>
              <div className={`text-sm font-medium ${data.sakk.sandbox ? 'text-amber-500' : 'text-emerald-600'}`}>
                {data.sakk.sandbox ? (isAr ? 'تجريبي (Sandbox)' : 'Sandbox') : (isAr ? 'مباشر (Live)' : 'Live')}
              </div>
            </div>
            <div>
              <div className="text-xs text-stone-400 mb-1">{isAr ? 'الوكالات المرتبطة' : 'Linked Agencies'}</div>
              <div className="text-sm font-medium text-stone-800">{data.sakk.agencies_linked}</div>
            </div>
          </div>
          {(data.sakk.total_payments > 0 || data.sakk.total_revenue > 0) && (
            <div className="mt-3 pt-3 border-t border-stone-100 grid grid-cols-2 gap-4">
              <div className="text-xs text-stone-400">{isAr ? 'مدفوعات مكتملة' : 'Completed Payments'}</div>
              <div className="text-xs text-stone-400">{isAr ? 'إجمالي الإيرادات' : 'Total Revenue'}</div>
              <div className="text-lg font-bold text-stone-900">{data.sakk.total_payments}</div>
              <div className="text-lg font-bold text-primary">${data.sakk.total_revenue.toLocaleString()}</div>
            </div>
          )}
        </div>
      )}

      {/* Operations / Pending Approvals */}
      {hasOperations && (
        <div className="card-3d p-5 mb-6 border-s-4 border-amber-400">
          <h2 className="font-bold text-stone-900 mb-4 flex items-center gap-2">
            <Clock className="w-5 h-5 text-amber-500" />
            {isAr ? 'عمليات في انتظار المراجعة' : 'Pending Operations'}
          </h2>
          <div className="space-y-3">
            {(data?.pending_agencies ?? 0) > 0 && (
              <Link to="/admin/agencies" className="flex items-center gap-3 p-3 rounded-xl bg-amber-50 hover:bg-amber-100 transition-colors group">
                <div className="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center text-amber-600">
                  <Building2 className="w-5 h-5" />
                </div>
                <div className="flex-1">
                  <div className="text-sm font-medium text-stone-800">
                    {isAr ? 'طلبات تسجيل وكالات جديدة' : 'New Agency Registration Requests'}
                  </div>
                  <div className="text-xs text-amber-600 font-bold">
                    {data?.pending_agencies} {isAr ? 'في انتظار الموافقة' : 'pending approval'}
                  </div>
                </div>
                <ArrowLeft className="w-4 h-4 lucide-rtl text-stone-400 group-hover:text-stone-600 transition-colors" />
              </Link>
            )}
            {(data?.unread_messages ?? 0) > 0 && (
              <Link to="/admin/messages" className="flex items-center gap-3 p-3 rounded-xl bg-blue-50 hover:bg-blue-100 transition-colors group">
                <div className="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center text-blue-600">
                  <MessageSquare className="w-5 h-5" />
                </div>
                <div className="flex-1">
                  <div className="text-sm font-medium text-stone-800">
                    {isAr ? 'رسائل غير مقروءة' : 'Unread Messages'}
                  </div>
                  <div className="text-xs text-blue-600 font-bold">
                    {data?.unread_messages} {isAr ? 'رسالة جديدة' : 'new messages'}
                  </div>
                </div>
                <ArrowLeft className="w-4 h-4 lucide-rtl text-stone-400 group-hover:text-stone-600 transition-colors" />
              </Link>
            )}
          </div>
        </div>
      )}

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Recent users */}
        <div className="card-3d p-5">
          <div className="flex items-center justify-between mb-4">
            <h2 className="font-bold text-stone-900">{isAr ? 'آخر المستخدمين' : 'Recent Users'}</h2>
            <Link to="/admin/users" className="text-xs text-primary hover:text-primary-dark font-medium">{isAr ? 'عرض الكل' : 'View all'}</Link>
          </div>
          <div className="space-y-3">
            {(data?.recent_users ?? []).length === 0 && (
              <p className="text-sm text-stone-400 text-center py-4">{isAr ? 'لا يوجد مستخدمون' : 'No users yet'}</p>
            )}
            {data?.recent_users?.map(u => (
              <div key={u.id} className="flex items-center gap-3">
                <div className="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary text-xs font-bold">{(u.name || '?').charAt(0)}</div>
                <div className="flex-1 min-w-0">
                  <div className="text-sm font-medium text-stone-800 truncate">{u.name}</div>
                  <div className="text-xs text-stone-400 truncate">{u.email}</div>
                </div>
                <div className="text-xs text-stone-400">{new Date(u.created_at).toLocaleDateString()}</div>
              </div>
            ))}
          </div>
        </div>

        {/* Recent agencies */}
        <div className="card-3d p-5">
          <div className="flex items-center justify-between mb-4">
            <h2 className="font-bold text-stone-900">{isAr ? 'آخر الوكالات' : 'Recent Agencies'}</h2>
            <Link to="/admin/agencies" className="text-xs text-primary hover:text-primary-dark font-medium">{isAr ? 'عرض الكل' : 'View all'}</Link>
          </div>
          <div className="space-y-3">
            {(data?.recent_agencies ?? []).length === 0 && (
              <p className="text-sm text-stone-400 text-center py-4">{isAr ? 'لا يوجد وكالات' : 'No agencies yet'}</p>
            )}
            {data?.recent_agencies?.map(a => (
              <div key={a.id} className="flex items-center justify-between">
                <div className="flex items-center gap-3">
                  <div className="w-8 h-8 rounded-lg bg-gold/10 flex items-center justify-center text-gold-dark text-xs font-bold">{(a.name || '?').charAt(0)}</div>
                  <div>
                    <div className="text-sm font-medium text-stone-800">{a.name}</div>
                    <div className={`text-xs mt-0.5 inline-flex items-center gap-1 ${
                      a.status === 'active' ? 'text-emerald-600' :
                      a.status === 'pending' ? 'text-amber-600' : 'text-red-600'
                    }`}>
                      {a.status === 'active' ? <CheckCircle2 className="w-3 h-3" /> :
                       a.status === 'pending' ? <Clock className="w-3 h-3" /> :
                       <XCircle className="w-3 h-3" />}
                      {a.status === 'active'
                        ? (isAr ? 'نشط' : 'Active')
                        : a.status === 'pending'
                          ? (isAr ? 'قيد المراجعة' : 'Pending')
                          : (isAr ? 'موقوف' : 'Suspended')}
                    </div>
                  </div>
                </div>
                <div className="text-xs text-stone-400">{new Date(a.created_at).toLocaleDateString()}</div>
              </div>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
}
