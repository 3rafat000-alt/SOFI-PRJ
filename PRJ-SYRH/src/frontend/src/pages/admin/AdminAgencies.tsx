import { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { Loader2, ChevronLeft, ChevronRight, CheckCircle2, Ban, Clock, Search, X } from 'lucide-react';
import { fetchAdminAgencies, updateAdminAgency, type AdminAgency } from '../../api/admin';

export default function AdminAgencies() {
  const { i18n } = useTranslation();
  const isAr = i18n.language === 'ar';
  const [data, setData] = useState<AdminAgency[]>([]);
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1, total: 0 });
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [confirmModal, setConfirmModal] = useState<{ id: number; action: string } | null>(null);
  const [actionLoading, setActionLoading] = useState(false);

  const load = async (page = 1) => {
    setLoading(true);
    try {
      const res = await fetchAdminAgencies(page);
      setData(res.data);
      setMeta(res.meta);
    } catch {}
    setLoading(false);
  };
  useEffect(() => { load(); }, []);

  const handleStatus = async () => {
    if (!confirmModal) return;
    setActionLoading(true);
    try {
      await updateAdminAgency(confirmModal.id, { status: confirmModal.action } as any);
      setConfirmModal(null);
      load(meta.current_page);
    } catch {}
    setActionLoading(false);
  };

  const statusIcon = (s: string) => {
    if (s === 'active') return <CheckCircle2 className="w-4 h-4 text-emerald-500" />;
    if (s === 'pending') return <Clock className="w-4 h-4 text-amber-500" />;
    return <Ban className="w-4 h-4 text-red-500" />;
  };

  const statusLabel = (s: string) => {
    const labels: Record<string, string> = {
      active: isAr ? 'نشط' : 'Active',
      pending: isAr ? 'قيد المراجعة' : 'Pending',
      suspended: isAr ? 'موقوف' : 'Suspended',
    };
    return labels[s] || s;
  };

  const filtered = data.filter(a =>
    !search || a.name.toLowerCase().includes(search.toLowerCase()) ||
    a.owner?.name?.toLowerCase().includes(search.toLowerCase())
  );

  return (
    <div>
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
          <h1 className="text-2xl font-bold text-stone-900">{isAr ? 'إدارة الوكالات' : 'Agencies'}</h1>
          <p className="text-sm text-stone-500 mt-1">
            {isAr
              ? `إجمالي ${meta.total} وكالة`
              : `${meta.total} agencies total`}
          </p>
        </div>
        <div className="relative">
          <Search className="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-stone-400" />
          <input
            value={search}
            onChange={e => setSearch(e.target.value)}
            placeholder={isAr ? 'بحث عن وكالة...' : 'Search agencies...'}
            className="input-field !pl-3 !pr-10 !py-2 text-sm w-64"
          />
        </div>
      </div>

      {/* Stats summary */}
      <div className="grid grid-cols-3 gap-4 mb-6">
        <div className="card-3d p-4 text-center">
          <div className="text-2xl font-bold text-emerald-600">{data.filter(a => a.status === 'active').length}</div>
          <div className="text-xs text-stone-500">{isAr ? 'نشط' : 'Active'}</div>
        </div>
        <div className="card-3d p-4 text-center">
          <div className="text-2xl font-bold text-amber-600">{data.filter(a => a.status === 'pending').length}</div>
          <div className="text-xs text-stone-500">{isAr ? 'قيد المراجعة' : 'Pending'}</div>
        </div>
        <div className="card-3d p-4 text-center">
          <div className="text-2xl font-bold text-red-600">{data.filter(a => a.status === 'suspended').length}</div>
          <div className="text-xs text-stone-500">{isAr ? 'موقوف' : 'Suspended'}</div>
        </div>
      </div>

      {/* Table */}
      <div className="card-3d overflow-hidden">
        {loading ? (
          <div className="flex items-center justify-center h-48"><Loader2 className="w-8 h-8 animate-spin text-primary" /></div>
        ) : (
          <>
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                  <thead><tr className="bg-beige text-stone-500 text-start">
                  <th className="px-4 py-3 font-medium">{isAr ? 'الاسم' : 'Name'}</th>
                  <th className="px-4 py-3 font-medium">{isAr ? 'المالك' : 'Owner'}</th>
                  <th className="px-4 py-3 font-medium">{isAr ? 'الحالة' : 'Status'}</th>
                  <th className="px-4 py-3 font-medium">{isAr ? 'الباقة' : 'Plan'}</th>
                  <th className="px-4 py-3 font-medium">{isAr ? 'تاريخ التسجيل' : 'Registered'}</th>
                  <th className="px-4 py-3 font-medium">{isAr ? 'تحكم' : 'Actions'}</th>
                </tr></thead>
                <tbody>
                  {filtered.length === 0 ? (
                    <tr><td colSpan={6} className="px-4 py-12 text-center text-stone-400">
                      {isAr ? 'لا توجد وكالات' : 'No agencies found'}
                    </td></tr>
                  ) : filtered.map(a => (
                    <tr key={a.id} className="border-t border-beige-dark hover:bg-beige/50 transition-colors">
                      <td className="px-4 py-3 font-medium text-stone-800">{a.name}</td>
                      <td className="px-4 py-3 text-stone-500">{a.owner?.name || '—'}</td>
                      <td className="px-4 py-3">
                        <div className="flex items-center gap-1.5">
                          {statusIcon(a.status)}
                          <span className="text-xs">{statusLabel(a.status)}</span>
                        </div>
                      </td>
                      <td className="px-4 py-3 text-stone-500 text-xs">
                        {a.subscription?.plan ? (isAr ? a.subscription.plan.name_ar : a.subscription.plan.name_en) : '—'}
                      </td>
                      <td className="px-4 py-3 text-stone-400 text-xs">
                        {new Date(a.created_at).toLocaleDateString(isAr ? 'ar' : 'en')}
                      </td>
                      <td className="px-4 py-3">
                        <div className="flex gap-1">
                          {a.status === 'pending' && (
                            <>
                              <button onClick={() => setConfirmModal({ id: a.id, action: 'active' })}
                                className="px-3 py-1.5 rounded-lg bg-primary/10 text-primary text-xs font-medium hover:bg-primary/20 transition-all">
                                {isAr ? 'موافقة' : 'Approve'}
                              </button>
                              <button onClick={() => setConfirmModal({ id: a.id, action: 'suspended' })}
                                className="px-3 py-1.5 rounded-lg bg-red-50 text-red-600 text-xs font-medium hover:bg-red-100 transition-all">
                                {isAr ? 'رفض' : 'Reject'}
                              </button>
                            </>
                          )}
                          {a.status === 'active' && (
                            <button onClick={() => setConfirmModal({ id: a.id, action: 'suspended' })}
                              className="px-3 py-1.5 rounded-lg bg-red-50 text-red-600 text-xs font-medium hover:bg-red-100 transition-all">
                              {isAr ? 'تعليق' : 'Suspend'}
                            </button>
                          )}
                          {a.status === 'suspended' && (
                            <button onClick={() => setConfirmModal({ id: a.id, action: 'active' })}
                              className="px-3 py-1.5 rounded-lg bg-emerald-50 text-emerald-600 text-xs font-medium hover:bg-emerald-100 transition-all">
                              {isAr ? 'إعادة تفعيل' : 'Reactivate'}
                            </button>
                          )}
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
            <div className="flex items-center justify-between px-4 py-3 border-t border-beige-dark text-sm text-stone-500">
              <span>{isAr ? `صفحة ${meta.current_page} من ${meta.last_page}` : `Page ${meta.current_page} of ${meta.last_page}`}</span>
              <div className="flex gap-2">
                <button disabled={meta.current_page <= 1} onClick={() => load(meta.current_page - 1)}
                  className="p-1.5 rounded-lg hover:bg-beige disabled:opacity-30 transition-all">
                  <ChevronLeft className="w-4 h-4 lucide-rtl" />
                </button>
                <button disabled={meta.current_page >= meta.last_page} onClick={() => load(meta.current_page + 1)}
                  className="p-1.5 rounded-lg hover:bg-beige disabled:opacity-30 transition-all">
                  <ChevronRight className="w-4 h-4 lucide-rtl" />
                </button>
              </div>
            </div>
          </>
        )}
      </div>

      {/* Confirm modal */}
      {confirmModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm">
          <div className="bg-white rounded-2xl shadow-2xl p-6 max-w-sm w-full mx-4">
            <div className="flex items-center justify-between mb-4">
              <h3 className="text-lg font-bold text-stone-900">
                {isAr ? 'تأكيد العملية' : 'Confirm Action'}
              </h3>
              <button onClick={() => setConfirmModal(null)} className="p-1 rounded-lg hover:bg-beige text-stone-400">
                <X className="w-5 h-5" />
              </button>
            </div>
            <p className="text-stone-600 text-sm mb-6">
              {confirmModal.action === 'active'
                ? (isAr ? 'هل أنت متأكد من تفعيل هذه الوكالة؟' : 'Are you sure you want to activate this agency?')
                : confirmModal.action === 'suspended'
                ? (isAr ? 'هل أنت متأكد من تعليق هذه الوكالة؟' : 'Are you sure you want to suspend this agency?')
                : (isAr ? 'هل أنت متأكد من إعادة تفعيل هذه الوكالة؟' : 'Are you sure you want to reactivate this agency?')}
            </p>
            <div className="flex gap-3">
              <button onClick={() => setConfirmModal(null)}
                className="flex-1 px-4 py-2.5 rounded-xl border border-beige-dark text-stone-600 text-sm font-medium hover:bg-beige transition-all">
                {isAr ? 'إلغاء' : 'Cancel'}
              </button>
              <button onClick={handleStatus} disabled={actionLoading}
                className={`flex-1 px-4 py-2.5 rounded-xl text-white text-sm font-medium transition-all flex items-center justify-center gap-2 ${
                  confirmModal.action === 'active' ? 'bg-primary hover:bg-primary-dark' : 'bg-red-500 hover:bg-red-600'
                }`}>
                {actionLoading ? <Loader2 className="w-4 h-4 animate-spin" /> : null}
                {confirmModal.action === 'active'
                  ? (isAr ? 'تفعيل' : 'Activate')
                  : (isAr ? 'تأكيد' : 'Confirm')}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
