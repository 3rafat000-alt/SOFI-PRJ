import { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { Users, Plus, Phone, Mail, Loader2, X, AlertTriangle, ArrowUp } from 'lucide-react';
import { Link } from 'react-router-dom';
import { fetchAgencyAgents, storeAgencyAgent, fetchAgencySubscription, type AgencyAgent, type AgencyUsage } from '../../api/agency';
import SubscriptionHint from '../../components/SubscriptionHint';
import axios from 'axios';

export default function AgencyAgents() {
  const { i18n } = useTranslation();
  const isAr = i18n.language === 'ar';
  const L = (ar: string, en: string) => isAr ? ar : en;
  const [agents, setAgents] = useState<AgencyAgent[]>([]);
  const [loading, setLoading] = useState(true);
  const [showForm, setShowForm] = useState(false);
  const [form, setForm] = useState({ display_name: '', email: '', phone: '', whatsapp: '' });
  const [error, setError] = useState('');
  const [saving, setSaving] = useState(false);
  const [usage, setUsage] = useState<AgencyUsage | null>(null);

  useEffect(() => {
    fetchAgencySubscription().then(s => setUsage(s.usage ?? null));
  }, []);

  const load = () => {
    setLoading(true);
    fetchAgencyAgents().then(setAgents).finally(() => setLoading(false));
  };

  useEffect(() => { load(); }, []);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    setSaving(true);
    try {
      await storeAgencyAgent(form);
      setForm({ display_name: '', email: '', phone: '', whatsapp: '' });
      setShowForm(false);
      load();
    } catch (err: any) {
      if (axios.isAxiosError(err) && err.response?.data?.errors) {
        setError(Object.values(err.response.data.errors).flat()[0] as string);
      } else {
        setError(L('خطأ في حفظ الوكيل', 'Error saving agent'));
      }
    } finally {
      setSaving(false);
    }
  };

  return (
    <div>
      <div className="flex items-center justify-between mb-2">
        <h1 className="text-2xl font-bold text-stone-900">{L('الوكلاء', 'Agents')}</h1>
        <button onClick={() => {
          if (usage && usage.agents.max > 0 && usage.agents.current >= usage.agents.max) return;
          setShowForm(true);
        }} className={`text-sm flex items-center gap-2 !py-2 !px-4 rounded-xl font-medium transition-all ${
          usage && usage.agents.max > 0 && usage.agents.current >= usage.agents.max
            ? 'bg-stone-200 text-stone-400 cursor-not-allowed'
            : 'btn-primary'
        }`}>
          <Plus className="w-4 h-4" /> {L('إضافة وكيل', 'Add Agent')}
        </button>
      </div>

      {/* Plan limit bar */}
      {usage && usage.agents.max > 0 && (
        <div className="mb-6">
          <div className="flex items-center justify-between text-xs text-stone-500 mb-1">
            <span>{L('خطة الاشتراك', 'Subscription plan')}</span>
            <span className="font-medium">{usage.agents.current}/{usage.agents.max} {L('وكيل', 'agents')}</span>
          </div>
          <div className="w-full h-2 rounded-full bg-stone-200">
            <div className={`h-2 rounded-full transition-all ${
              usage.agents.current >= usage.agents.max ? 'bg-red-500' :
              usage.agents.current / usage.agents.max >= 0.85 ? 'bg-amber-500' : 'bg-primary'
            }`} style={{ width: `${Math.min((usage.agents.current / usage.agents.max) * 100, 100)}%` }} />
          </div>
          {usage.agents.current >= usage.agents.max && (
            <div className="flex items-center gap-1.5 mt-2 text-xs text-red-600">
              <AlertTriangle className="w-3.5 h-3.5 flex-shrink-0" />
              <span>{L('لقد وصلت إلى الحد الأقصى للوكلاء. قم بترقية خطتك لإضافة المزيد.', 'You have reached the agent limit. Upgrade your plan to add more.')}</span>
              <Link to="/dashboard/subscription" className="text-primary underline hover:no-underline font-medium flex items-center gap-0.5">
                {L('ترقية', 'Upgrade')} <ArrowUp className="w-3 h-3" />
              </Link>
            </div>
          )}
          {usage.agents.max > 0 && usage.agents.current < usage.agents.max && usage.agents.current / usage.agents.max >= 0.85 && (
            <div className="flex items-center gap-1.5 mt-2 text-xs text-amber-600">
              <AlertTriangle className="w-3.5 h-3.5 flex-shrink-0" />
              <span>{L('تقترب من الحد الأقصى للوكلاء', 'You are nearing your agent limit')}</span>
            </div>
          )}
        </div>
      )}

      {/* Onboarding hint */}
      <SubscriptionHint type="agents" usage={usage} />

      {loading ? (
        <div className="flex items-center justify-center h-48">
          <Loader2 className="w-8 h-8 animate-spin text-primary" />
        </div>
      ) : agents.length === 0 ? (
        <div className="text-center py-16">
          <Users className="w-12 h-12 text-stone-300 mx-auto mb-3" />
          <p className="text-stone-500">{L('لا يوجد وكلاء بعد', 'No agents yet')}</p>
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          {agents.map(agent => (
            <div key={agent.id} className="card-3d p-5">
              <div className="flex items-center gap-3 mb-3">
                <div className="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center text-primary font-bold text-lg">
                  {(agent.display_name || '?').charAt(0)}
                </div>
                <div>
                  <div className="font-bold text-stone-900">{agent.display_name}</div>
                  <div className="text-xs text-stone-400">{agent.properties_count || 0} {L('عقار', 'properties')}</div>
                </div>
              </div>
              <div className="space-y-2 text-sm">
                <div className="flex items-center gap-2 text-stone-500">
                  <Phone className="w-3.5 h-3.5" />
                  <span dir="ltr">{agent.phone}</span>
                </div>
                {agent.email && (
                  <div className="flex items-center gap-2 text-stone-500">
                    <Mail className="w-3.5 h-3.5" />
                    <span className="truncate">{agent.email}</span>
                  </div>
                )}
              </div>
            </div>
          ))}
        </div>
      )}

      {/* Add agent modal */}
      {showForm && (
        <div className="fixed inset-0 bg-black/30 z-50 flex items-center justify-center p-4" onClick={() => setShowForm(false)}>
          <div className="bg-white rounded-2xl p-6 w-full max-w-md shadow-xl" onClick={e => e.stopPropagation()}>
            <div className="flex items-center justify-between mb-5">
              <h3 className="font-bold text-stone-900">{L('إضافة وكيل', 'Add Agent')}</h3>
              <button onClick={() => setShowForm(false)} className="p-1 hover:bg-beige rounded-lg">
                <X className="w-5 h-5 text-stone-400" />
              </button>
            </div>
            {error && <div className="bg-red-50 text-red-600 text-sm rounded-xl px-4 py-3 mb-4">{error}</div>}
            <form onSubmit={handleSubmit} className="space-y-3.5">
              <input required value={form.display_name} onChange={e => setForm({...form, display_name: e.target.value})}
                placeholder={L('الاسم الكامل', 'Full Name')} className="input-field text-sm" />
              <input type="email" value={form.email} onChange={e => setForm({...form, email: e.target.value})}
                placeholder={L('البريد الإلكتروني', 'Email')} className="input-field text-sm" />
              <input required value={form.phone} onChange={e => setForm({...form, phone: e.target.value})}
                placeholder={L('رقم الهاتف', 'Phone')} className="input-field text-sm" dir="ltr" />
              <input value={form.whatsapp} onChange={e => setForm({...form, whatsapp: e.target.value})}
                placeholder={L('واتساب', 'WhatsApp')} className="input-field text-sm" dir="ltr" />
              <button type="submit" disabled={saving} className="btn-primary w-full !py-3">
                {saving ? <Loader2 className="w-4 h-4 animate-spin mx-auto" /> : L('إضافة', 'Add Agent')}
              </button>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}
