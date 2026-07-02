import { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { Loader2, ChevronLeft, ChevronRight, Search, Ban, CheckCircle2 } from 'lucide-react';
import { fetchAdminUsers, updateAdminUser, type AdminUser } from '../../api/admin';
import SelectField from '../../components/SelectField';

export default function AdminUsers() {
  const { i18n } = useTranslation();
  const isAr = i18n.language === 'ar';
  const [users, setUsers] = useState<AdminUser[]>([]);
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1 });
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');

  const load = async (page = 1) => {
    setLoading(true);
    try {
      const res = await fetchAdminUsers(page);
      setUsers(res.data);
      setMeta(res.meta);
    } catch {}
    setLoading(false);
  };

  useEffect(() => { load(); }, []);

  const handleStatus = async (user: AdminUser, status: string) => {
    await updateAdminUser(user.id, { status } as any);
    load(meta.current_page);
  };

  const handleRole = async (user: AdminUser, role: string) => {
    await updateAdminUser(user.id, { role } as any);
    load(meta.current_page);
  };

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-2xl font-bold text-stone-900">{isAr ? 'المستخدمين' : 'Users'}</h1>
      </div>

      <div className="card-3d overflow-hidden">
        <div className="p-4 border-b border-beige-dark">
          <div className="relative max-w-xs">
            <Search className={`absolute top-1/2 -translate-y-1/2 w-4 h-4 text-stone-400 ${isAr ? 'right-3' : 'left-3'}`} />
            <input placeholder={isAr ? 'بحث...' : 'Search...'} value={search} onChange={e => setSearch(e.target.value)}
              className={`input-field !py-2 text-sm ${isAr ? '!pr-10' : '!pl-10'}`} />
          </div>
        </div>
        {loading ? (
          <div className="flex items-center justify-center h-48"><Loader2 className="w-8 h-8 animate-spin text-primary" /></div>
        ) : (
          <>
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="bg-beige text-stone-500 text-start">
                    <th className="px-4 py-3 font-medium">{isAr ? 'الاسم' : 'Name'}</th>
                    <th className="px-4 py-3 font-medium">Email</th>
                    <th className="px-4 py-3 font-medium">{isAr ? 'الدور' : 'Role'}</th>
                    <th className="px-4 py-3 font-medium">{isAr ? 'الحالة' : 'Status'}</th>
                    <th className="px-4 py-3 font-medium">{isAr ? 'تحكم' : 'Actions'}</th>
                  </tr>
                </thead>
                <tbody>
                  {users.filter(u => !search || u.name.includes(search) || u.email.includes(search)).map(user => (
                    <tr key={user.id} className="border-t border-beige-dark hover:bg-beige/50 transition-colors">
                      <td className="px-4 py-3">
                        <div className="flex items-center gap-3">
                          <div className="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary text-xs font-bold">{(user.name || 'U').charAt(0)}</div>
                          <div>
                            <div className="font-medium text-stone-800">{user.name}</div>
                            <div className="text-xs text-stone-400">{user.phone || '—'}</div>
                          </div>
                        </div>
                      </td>
                      <td className="px-4 py-3 text-stone-500">{user.email}</td>
                      <td className="px-4 py-3">
                        <SelectField
                          value={user.roles?.[0]?.name || 'visitor'}
                          onChange={(v) => handleRole(user, v)}
                          options={[
                            { value: 'admin', label: 'Admin' },
                            { value: 'agency', label: 'Agency' },
                            { value: 'agent', label: 'Agent' },
                            { value: 'visitor', label: 'Visitor' },
                          ]}
                          className="min-w-[120px]"
                        />
                      </td>
                      <td className="px-4 py-3">
                        <span className={`badge ${user.status === 'active' ? 'badge-primary' : 'badge-red'}`}>
                          {user.status || 'active'}
                        </span>
                      </td>
                      <td className="px-4 py-3">
                        <div className="flex gap-1">
                          {user.status !== 'suspended' ? (
                            <button onClick={() => handleStatus(user, 'suspended')} className="p-1.5 rounded-lg text-stone-400 hover:text-red-500 hover:bg-red-50 transition-all" title={isAr ? 'حظر' : 'Suspend'}>
                              <Ban className="w-4 h-4" />
                            </button>
                          ) : (
                            <button onClick={() => handleStatus(user, 'active')} className="p-1.5 rounded-lg text-stone-400 hover:text-emerald-500 hover:bg-emerald-50 transition-all" title={isAr ? 'تفعيل' : 'Activate'}>
                              <CheckCircle2 className="w-4 h-4" />
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
                  className="p-1.5 rounded-lg hover:bg-beige disabled:opacity-30 transition-all"><ChevronLeft className="w-4 h-4 lucide-rtl" /></button>
                <button disabled={meta.current_page >= meta.last_page} onClick={() => load(meta.current_page + 1)}
                  className="p-1.5 rounded-lg hover:bg-beige disabled:opacity-30 transition-all"><ChevronRight className="w-4 h-4 lucide-rtl" /></button>
              </div>
            </div>
          </>
        )}
      </div>
    </div>
  );
}
