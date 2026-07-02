import { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { Link } from 'react-router-dom';
import {
  Building2, Users, MessageSquare, Eye, TrendingUp, DollarSign,
  CheckCircle2, Loader2,
} from 'lucide-react';
import { fetchAgencyStats, fetchAgencySubscription, type AgencyStats, type AgencyUsage } from '../../api/agency';

export default function DashboardHome() {
  const { i18n } = useTranslation();
  const isAr = i18n.language === 'ar';
  const L = (ar: string, en: string) => isAr ? ar : en;
  const [stats, setStats] = useState<AgencyStats | null>(null);
  const [usage, setUsage] = useState<AgencyUsage | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    Promise.all([
      fetchAgencyStats(),
      fetchAgencySubscription().then(s => setUsage(s.usage ?? null)),
    ]).then(([s]) => setStats(s)).finally(() => setLoading(false));
  }, []);

  const pct = (cur: number, max: number) => max > 0 ? Math.round((cur / max) * 100) : 0;
  const isFull = (cur: number, max: number) => max > 0 && cur >= max;

  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <Loader2 className="w-8 h-8 animate-spin text-primary" />
      </div>
    );
  }

  const propPct = usage ? pct(usage.properties.current, usage.properties.max) : 0;
  const agentPct = usage ? pct(usage.agents.current, usage.agents.max) : 0;
  const propFull = usage ? isFull(usage.properties.current, usage.properties.max) : false;
  const agentFull = usage ? isFull(usage.agents.current, usage.agents.max) : false;

  const barColor = (p: number) => p >= 90 ? 'bg-red-500' : p >= 70 ? 'bg-amber-500' : 'bg-primary';

  const cards = [
    {
      label: L('العقارات', 'Properties'),
      value: stats?.total_properties ?? 0,
      icon: Building2,
      color: 'bg-primary/10 text-primary',
      sub: usage ? `${usage.properties.current}/${usage.properties.max === 0 ? '∞' : usage.properties.max} ${L('عقار', 'properties')}` : '',
      bar: usage && usage.properties.max > 0 ? { pct: propPct, full: propFull } : null,
    },
    {
      label: L('الوكلاء', 'Agents'),
      value: stats?.total_agents ?? 0,
      icon: Users,
      color: 'bg-gold/10 text-gold-dark',
      sub: usage ? `${usage.agents.current}/${usage.agents.max === 0 ? '∞' : usage.agents.max} ${L('وكيل', 'agents')}` : '',
      bar: usage && usage.agents.max > 0 ? { pct: agentPct, full: agentFull } : null,
    },
    { label: L('الاستفسارات', 'Inquiries'), value: stats?.total_inquiries ?? 0, icon: MessageSquare, color: 'bg-primary/10 text-primary', sub: `${stats?.pending_inquiries ?? 0} ${L('قيد الانتظار', 'pending')}` },
    { label: L('المشاهدات', 'Views'), value: stats?.monthly_views ?? 0, icon: Eye, color: 'bg-gold/10 text-gold-dark', sub: L('شهري', 'monthly') },
    { label: L('الصفقات', 'Deals'), value: stats?.confirmed_deals ?? 0, icon: CheckCircle2, color: 'bg-primary/10 text-primary', sub: `${stats?.total_deals ?? 0} ${L('إجمالي', 'total')}` },
    { label: L('العمولات', 'Commission'), value: `${(stats?.total_commission ?? 0).toLocaleString()}`, icon: DollarSign, color: 'bg-gold/10 text-gold-dark', sub: `${(stats?.monthly_commission ?? 0).toLocaleString()} ${L('هذا الشهر', 'this month')}` },
  ];

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <div>
          <h1 className="text-2xl font-bold text-stone-900">{L('لوحة التحكم', 'Dashboard')}</h1>
          <p className="text-sm text-stone-500 mt-1">{L('نظرة عامة على وكالتك', 'Overview of your agency')}</p>
        </div>
      </div>

      <div className="grid grid-cols-2 md:grid-cols-3 gap-4">
        {cards.map((card, i) => (
          <div key={i} className="card-3d p-5 group hover:-translate-y-1">
            <div className={`w-10 h-10 rounded-xl ${card.color} flex items-center justify-center mb-3`}>
              <card.icon className="w-5 h-5" />
            </div>
            <div className="text-2xl font-bold text-stone-900">{card.value}</div>
            <div className="text-sm text-stone-500 mt-0.5">{card.label}</div>
            {card.sub && <div className="text-xs text-stone-400 mt-1">{card.sub}</div>}
            {'bar' in card && card.bar && (
              <div className="mt-2">
                <div className="w-full h-1.5 rounded-full bg-stone-200">
                  <div
                    className={`h-1.5 rounded-full transition-all ${card.bar.full ? 'bg-red-500' : barColor(card.bar.pct)}`}
                    style={{ width: `${Math.min(card.bar.pct, 100)}%` }}
                  />
                </div>
              </div>
            )}
          </div>
        ))}
      </div>

      {/* Quick actions */}
      <div className="mt-8">
        <h2 className="text-lg font-bold text-stone-900 mb-4">{L('إجراءات سريعة', 'Quick Actions')}</h2>
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
          <Link to="/dashboard/properties" className="card-3d p-4 text-center hover:-translate-y-1 group">
            <Building2 className="w-6 h-6 text-primary mx-auto mb-2 group-hover:scale-110 transition-transform" />
            <div className="font-medium text-stone-700 text-sm">{L('العقارات', 'Properties')}</div>
          </Link>
          <Link to="/dashboard/agents" className="card-3d p-4 text-center hover:-translate-y-1 group">
            <Users className="w-6 h-6 text-gold-dark mx-auto mb-2 group-hover:scale-110 transition-transform" />
            <div className="font-medium text-stone-700 text-sm">{L('الوكلاء', 'Agents')}</div>
          </Link>
          <Link to="/dashboard/deals" className="card-3d p-4 text-center hover:-translate-y-1 group">
            <TrendingUp className="w-6 h-6 text-emerald-600 mx-auto mb-2 group-hover:scale-110 transition-transform" />
            <div className="font-medium text-stone-700 text-sm">{L('الصفقات', 'Deals')}</div>
          </Link>
          <Link to="/dashboard/subscription" className="card-3d p-4 text-center hover:-translate-y-1 group">
            <DollarSign className="w-6 h-6 text-amber-600 mx-auto mb-2 group-hover:scale-110 transition-transform" />
            <div className="font-medium text-stone-700 text-sm">{L('الاشتراك', 'Subscription')}</div>
          </Link>
        </div>
      </div>
    </div>
  );
}
