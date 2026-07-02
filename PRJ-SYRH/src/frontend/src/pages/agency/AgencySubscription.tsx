import { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { useSearchParams } from 'react-router-dom';
import { Check, Loader2, Crown, Star, ExternalLink, Shield, Building2, Users, AlertTriangle, Clock, History, XCircle } from 'lucide-react';
import { fetchAgencySubscription, subscribeToPlan, fetchAgencyPayments, type AgencySubscription, type AgencyPayment } from '../../api/agency';
import SubscriptionHint from '../../components/SubscriptionHint';

export default function AgencySubscription() {
  const { i18n } = useTranslation();
  const isAr = i18n.language === 'ar';
  const L = (ar: string, en: string) => isAr ? ar : en;
  const [searchParams] = useSearchParams();
  const [data, setData] = useState<AgencySubscription | null>(null);
  const [loading, setLoading] = useState(true);
  const [subscribing, setSubscribing] = useState<number | null>(null);
  const [paymentUrl, setPaymentUrl] = useState<string | null>(null);
  const [paid, setPaid] = useState(searchParams.get('paid') === '1');

  const [payments, setPayments] = useState<AgencyPayment[]>([]);
  const [paymentsLoading, setPaymentsLoading] = useState(true);

  useEffect(() => {
    fetchAgencySubscription().then(setData).finally(() => setLoading(false));
    fetchAgencyPayments().then(res => setPayments(res.data)).finally(() => setPaymentsLoading(false));
  }, []);

  // If user returned from SAKK payment, refresh data
  useEffect(() => {
    if (paid) {
      fetchAgencySubscription().then(setData);
      setPaymentUrl(null);
    }
  }, [paid]);

  // Auto-refresh after payment: poll until subscription activates
  // MUST be before early returns — hooks need consistent call count
  const [polling, setPolling] = useState(false);
  useEffect(() => {
    const sub = data?.current_subscription;
    if (!paid || !sub) return;
    if (sub.status === 'active' || sub.status === 'trial') {
      setPolling(false);
      return;
    }
    setPolling(true);
    const interval = setInterval(async () => {
      try {
        const fresh = await fetchAgencySubscription();
        const s = fresh.current_subscription;
        if (s && (s.status === 'active' || s.status === 'trial')) {
          setData(fresh);
          setPolling(false);
          setPaid(false);
          clearInterval(interval);
        }
      } catch {}
    }, 5000);
    return () => clearInterval(interval);
  }, [paid, data?.current_subscription?.status]);

  const handleSubscribe = async (planId: number) => {
    setSubscribing(planId);
    setPaymentUrl(null);
    try {
      const result = await subscribeToPlan(planId);
      if (result.payment_url) {
        setPaymentUrl(result.payment_url);
      } else {
        // Free plan — just refresh
        fetchAgencySubscription().then(setData);
      }
    } catch (err: any) {
      console.error('Subscribe failed', err);
      alert(err?.response?.data?.error || L('فشل الاشتراك', 'Subscription failed'));
    } finally {
      setSubscribing(null);
    }
  };

  if (loading) {
    return <div className="flex items-center justify-center h-64"><Loader2 className="w-8 h-8 animate-spin text-primary" /></div>;
  }

  const current = data?.current_subscription;
  const plans = data?.available_plans || [];
  const usage = data?.usage;

  const getRemainingDays = (endAt: string): number => {
    const end = new Date(endAt);
    const now = new Date();
    return Math.max(0, Math.ceil((end.getTime() - now.getTime()) / (1000 * 60 * 60 * 24)));
  };

  const remainingDays = current ? getRemainingDays(current.end_at) : 0;
  const daysBadgeColor = remainingDays <= 3 ? 'bg-red-50 text-red-600' :
    remainingDays <= 14 ? 'bg-amber-50 text-amber-600' : 'bg-emerald-50 text-emerald-600';

  const UsageBar = ({ current: cur, max, label, icon: Icon, color }: {
    current: number; max: number; label: string; icon: any; color: string;
  }) => {
    const pct = max > 0 ? Math.round((cur / max) * 100) : 0;
    const isFull = max > 0 && cur >= max;
    if (max === 0) return null;
    return (
      <div className="mt-3">
        <div className="flex items-center justify-between text-xs text-stone-500 mb-1">
          <span className="flex items-center gap-1.5">
            <Icon className="w-3.5 h-3.5" />
            {label}
          </span>
          <span className="font-medium">{cur}/{max} <span className="text-stone-400">({pct}%)</span></span>
        </div>
        <div className="w-full h-2 rounded-full bg-stone-200">
          <div className={`h-2 rounded-full transition-all ${
            isFull ? 'bg-red-500' : pct >= 85 ? 'bg-amber-500' : color
          }`} style={{ width: `${Math.min(pct, 100)}%` }} />
        </div>
        {isFull && (
          <div className="flex items-center gap-1 mt-1.5 text-xs text-red-600">
            <AlertTriangle className="w-3 h-3" />
            <span>{L('الحد الأقصى', 'Limit reached')}</span>
          </div>
        )}
      </div>
    );
  };

  // Payment URL overlay
  if (paymentUrl) {
    return (
      <div className="flex flex-col items-center justify-center h-96 text-center">
        <div className="w-16 h-16 rounded-full bg-gold/10 flex items-center justify-center mb-6">
          <Shield className="w-8 h-8 text-gold" />
        </div>
        <h2 className="text-xl font-bold text-stone-900 mb-2">
          {L('توجيه إلى بوابة الدفع', 'Redirecting to payment')}
        </h2>
        <p className="text-stone-500 text-sm mb-6 max-w-md">
          {L('سيتم تحويلك إلى بوابة الدفع الآمنة ساك لإتمام عملية الدفع', 'You will be redirected to SAKK secure payment gateway to complete payment')}
        </p>
        <div className="flex flex-col gap-3">
          <a href={paymentUrl} target="_blank" rel="noopener noreferrer"
            className="inline-flex items-center gap-2 px-6 py-3 bg-primary text-white rounded-xl font-medium hover:bg-primary-dark transition-all">
            {L('اذهب إلى ساك', 'Go to SAKK')} <ExternalLink className="w-4 h-4" />
          </a>
          <button onClick={() => setPaymentUrl(null)}
            className="text-sm text-stone-400 hover:text-stone-600 transition-colors">
            {L('إلغاء', 'Cancel')}
          </button>
        </div>
        {paymentUrl.includes('sakk/checkout') && (
          <p className="text-xs text-amber-500 mt-4">
            {L('وضع تجريبي — لا تتم معالجة دفع حقيقي', 'Dev mode — no real payment processed')}
          </p>
        )}
      </div>
    );
  }

  // Success message after payment
  if (paid) {
    return (
      <div className="flex flex-col items-center justify-center h-96 text-center">
        <div className="w-16 h-16 rounded-full bg-emerald-50 flex items-center justify-center mb-6">
          <Check className="w-8 h-8 text-emerald-500" />
        </div>
        <h2 className="text-xl font-bold text-stone-900 mb-2">
          {L('تم الدفع بنجاح!', 'Payment successful!')}
        </h2>
        {current && (
          <p className="text-stone-500 text-sm mb-4">
            {L('خطتك', 'Your plan')}: <strong>{isAr ? current.plan?.name_ar : current.plan?.name_en}</strong>
          </p>
        )}
        {polling && (
          <div className="flex items-center gap-2 text-xs text-amber-600 mb-4">
            <Loader2 className="w-3.5 h-3.5 animate-spin" />
            {L('بانتظار تفعيل الاشتراك...', 'Waiting for subscription activation...')}
          </div>
        )}
        <button onClick={() => { setPaid(false); fetchAgencySubscription().then(setData); }}
          className="text-primary text-sm font-medium hover:underline">
          {L('العودة إلى الخطط', 'Back to plans')}
        </button>
      </div>
    );
  }

  return (
    <div>
      <h1 className="text-2xl font-bold text-stone-900 mb-2">{L('خطة الاشتراك', 'Subscription')}</h1>
      <p className="text-stone-500 text-sm mb-6">{L('اختر الخطة المناسبة لوكالتك', 'Choose the right plan for your agency')}</p>

      {/* Onboarding hint */}
      <SubscriptionHint type="subscription" usage={usage ?? null} />

      {/* Current subscription */}
      {current && (
        <div className="bg-white rounded-xl border border-stone-200/70 p-5 mb-8 border-l-4 border-primary shadow-sm">
          <div className="flex items-center justify-between">
            <div>
              <span className="text-xs text-stone-400 uppercase tracking-wide">{L('خطتك الحالية', 'Current Plan')}</span>
              <h2 className="text-xl font-bold text-stone-900 mt-1">
                {isAr ? current.plan?.name_ar : current.plan?.name_en}
              </h2>
              <div className="flex items-center flex-wrap gap-x-3 gap-y-1 mt-2 text-sm text-stone-500">
                <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                  current.status === 'active' ? 'bg-emerald-50 text-emerald-600' :
                  current.status === 'trial' ? 'bg-amber-50 text-amber-600' :
                  'bg-red-50 text-red-600'
                }`}>
                  {current.status === 'active' ? L('نشط', 'Active') :
                   current.status === 'trial' ? L('تجريبي', 'Trial') :
                   current.status === 'expired' ? L('منتهي', 'Expired') : L('ملغي', 'Cancelled')}
                </span>
                <span>{L('من', 'From')} {new Date(current.start_at).toLocaleDateString(isAr ? 'ar' : 'en')}</span>
                <span>{L('إلى', 'To')} {new Date(current.end_at).toLocaleDateString(isAr ? 'ar' : 'en')}</span>
                {/* Remaining days badge */}
                {(current.status === 'active' || current.status === 'trial') && (
                  <span className={`inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium ${daysBadgeColor}`}>
                    <Clock className="w-3 h-3" />
                    {remainingDays === 0 ? L('ينتهي اليوم', 'Ends today') :
                     remainingDays === 1 ? L('يوم واحد متبقي', '1 day left') :
                     L('{n} أيام متبقية', '{n} days left').replace('{n}', String(remainingDays))}
                  </span>
                )}
              </div>

              {/* Usage bars */}
              {usage && (
                <div className="mt-4 pt-4 border-t border-stone-100">
                  <UsageBar current={usage.properties.current} max={usage.properties.max}
                    label={L('العقارات المستخدمة', 'Properties used')} icon={Building2} color="bg-primary" />
                  <UsageBar current={usage.agents.current} max={usage.agents.max}
                    label={L('الوكلاء المستخدمون', 'Agents used')} icon={Users} color="bg-gold-dark" />
                </div>
              )}
            </div>
            <div className="hidden sm:block">
              <Crown className="w-10 h-10 text-gold" />
            </div>
          </div>
        </div>
      )}

      {/* Plans grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        {plans.map(plan => {
          const isFree = plan.price === 0;
          const isCurrent = current?.plan?.id === plan.id;
          const isPopular = !isFree && !isCurrent && plan.sort === 2;

          return (
            <div key={plan.id} className={`bg-white rounded-xl border p-6 relative transition-all hover:shadow-md ${
              isCurrent ? 'border-primary ring-1 ring-primary' :
              isPopular ? 'border-gold ring-1 ring-gold' :
              'border-stone-200/70'
            }`}>
              {isPopular && (
                <div className="absolute -top-3 left-1/2 -translate-x-1/2 bg-gold text-white text-xs font-bold px-4 py-1 rounded-full whitespace-nowrap">
                  {L('الأكثر طلباً', 'Most Popular')}
                </div>
              )}
              {isCurrent && (
                <div className="absolute -top-3 left-1/2 -translate-x-1/2 bg-primary text-white text-xs font-bold px-4 py-1 rounded-full whitespace-nowrap">
                  {L('خطتك', 'Your Plan')}
                </div>
              )}

              <div className="flex items-center gap-2 mb-3">
                <Star className={`w-4 h-4 ${isPopular ? 'text-gold' : 'text-stone-300'}`} />
                <h3 className="text-lg font-bold text-stone-900">
                  {isAr ? plan.name_ar : plan.name_en}
                </h3>
              </div>

              {isAr ? plan.description_ar : plan.description_en ? (
                <p className="text-xs text-stone-400 mb-4 leading-relaxed">
                  {isAr ? plan.description_ar : plan.description_en}
                </p>
              ) : null}

              <div className="mb-4">
                <span className="text-3xl font-bold text-stone-900">
                  {isFree ? L('مجاني', 'Free') : `$${plan.price.toLocaleString()}`}
                </span>
                {!isFree && (
                  <span className="text-sm text-stone-400 mr-1">
                    / {plan.duration_days === 30 ? L('شهر', 'month') : plan.duration_days === 365 ? L('سنة', 'year') : `${plan.duration_days} ${L('يوم', 'days')}`}
                  </span>
                )}
              </div>

              <ul className="space-y-2 mb-6 text-sm">
                <li className="flex items-center gap-2 text-stone-600">
                  <Check className="w-4 h-4 text-emerald-500 shrink-0" />
                  {plan.max_properties === 0 ? L('عقارات غير محدودة', 'Unlimited properties') : `${L('حتى', 'Up to')} ${plan.max_properties} ${L('عقار', 'properties')}`}
                </li>
                <li className="flex items-center gap-2 text-stone-600">
                  <Check className="w-4 h-4 text-emerald-500 shrink-0" />
                  {plan.max_agents === 0 ? L('وكلاء غير محدودين', 'Unlimited agents') : `${L('حتى', 'Up to')} ${plan.max_agents} ${L('وكيل', 'agents')}`}
                </li>
                {plan.features?.map((f: any, i: number) => (
                  <li key={i} className="flex items-center gap-2 text-stone-600">
                    <Check className="w-4 h-4 text-emerald-500 shrink-0" />
                    {typeof f === 'string' ? (isAr && f.length > 20 ? f : f) : f}
                  </li>
                ))}
              </ul>

              <button
                onClick={() => handleSubscribe(plan.id)}
                disabled={isCurrent || subscribing !== null}
                className={`w-full !py-3 text-sm font-bold rounded-xl transition-all ${
                  isCurrent
                    ? 'bg-stone-100 text-stone-400 cursor-not-allowed'
                    : isPopular
                      ? 'bg-gold text-white hover:bg-amber-600 shadow-lg shadow-gold/20'
                      : 'bg-primary text-white hover:bg-primary-dark'
                }`}>
                {subscribing === plan.id ? (
                  <Loader2 className="w-4 h-4 animate-spin mx-auto" />
                ) : isCurrent ? (
                  L('خطتك الحالية', 'Current Plan')
                ) : isFree ? (
                  L('ابدأ مجاناً', 'Get Started')
                ) : (
                  L('اشتراك', 'Subscribe')
                )}
              </button>

              {/* SAKK badge for paid plans */}
              {!isFree && (
                <div className="mt-3 flex items-center justify-center gap-1.5 text-xs text-stone-400">
                  <Shield className="w-3 h-3" />
                  {L('دفع آمن عبر ساك', 'Secure via SAKK')}
                </div>
              )}
            </div>
          );
        })}
      </div>

      {/* Payment history */}
      <div className="mt-10 mb-6">
        <h2 className="text-lg font-bold text-stone-900 mb-3">{L('سجل الدفعات', 'Payment History')}</h2>
        {paymentsLoading ? (
          <div className="flex items-center justify-center h-32">
            <Loader2 className="w-6 h-6 animate-spin text-primary" />
          </div>
        ) : payments.length === 0 ? (
          <div className="bg-white rounded-xl border border-stone-200/70 p-8 text-center">
            <History className="w-10 h-10 text-stone-300 mx-auto mb-3" />
            <p className="text-sm text-stone-400">{L('لا توجد دفعات بعد', 'No payments yet')}</p>
          </div>
        ) : (
          <div className="bg-white rounded-xl border border-stone-200/70 overflow-hidden">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-stone-100 text-stone-500 text-xs">
                  <th className="text-right py-3 px-4 font-medium">{L('التاريخ', 'Date')}</th>
                  <th className="text-right py-3 px-4 font-medium">{L('المبلغ', 'Amount')}</th>
                  <th className="text-right py-3 px-4 font-medium">{L('الاشتراك', 'Subscription')}</th>
                  <th className="text-right py-3 px-4 font-medium">{L('الحالة', 'Status')}</th>
                  <th className="text-right py-3 px-4 font-medium">{L('المعاملات', 'Transaction')}</th>
                </tr>
              </thead>
              <tbody>
                {payments.map(p => (
                  <tr key={p.id} className="border-b border-stone-50 hover:bg-beige/30 transition-colors">
                    <td className="py-3 px-4 text-stone-600 whitespace-nowrap">
                      {new Date(p.created_at).toLocaleDateString(isAr ? 'ar' : 'en', {
                        year: 'numeric', month: 'short', day: 'numeric',
                      })}
                    </td>
                    <td className="py-3 px-4 font-medium text-stone-900">
                      ${Number(p.amount).toLocaleString()} <span className="text-xs text-stone-400">{p.currency}</span>
                    </td>
                    <td className="py-3 px-4 text-stone-600">
                      {p.agency_subscription?.plan
                        ? (isAr ? p.agency_subscription.plan.name_ar : p.agency_subscription.plan.name_en)
                        : '-'}
                    </td>
                    <td className="py-3 px-4">
                      <span className={`inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium ${
                        p.status === 'completed' ? 'bg-emerald-50 text-emerald-600' :
                        p.status === 'pending' ? 'bg-amber-50 text-amber-600' :
                        p.status === 'failed' ? 'bg-red-50 text-red-600' :
                        'bg-stone-100 text-stone-500'
                      }`}>
                        {p.status === 'completed' ? <Check className="w-3 h-3" /> :
                         p.status === 'failed' ? <XCircle className="w-3 h-3" /> :
                         <Loader2 className="w-3 h-3 animate-spin" />}
                        {p.status === 'completed' ? L('مكتمل', 'Completed') :
                         p.status === 'pending' ? L('قيد الانتظار', 'Pending') :
                         p.status === 'failed' ? L('فشل', 'Failed') :
                         p.status === 'refunded' ? L('مسترجع', 'Refunded') : p.status}
                      </span>
                    </td>
                    <td className="py-3 px-4">
                      {p.transaction_id ? (
                        <span className="text-xs text-stone-400 font-mono" dir="ltr">
                          {p.transaction_id.length > 16
                            ? p.transaction_id.slice(0, 16) + '...'
                            : p.transaction_id}
                        </span>
                      ) : (
                        <span className="text-xs text-stone-300">-</span>
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </div>
  );
}
