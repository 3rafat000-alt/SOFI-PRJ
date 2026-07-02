import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Link } from 'react-router-dom';
import { Lightbulb, X, ArrowUp } from 'lucide-react';
import type { AgencyUsage } from '../api/agency';

interface Props {
  type: 'properties' | 'agents' | 'subscription';
  usage: AgencyUsage | null;
}

export default function SubscriptionHint({ type, usage }: Props) {
  const { i18n } = useTranslation();
  const isAr = i18n.language === 'ar';
  const L = (ar: string, en: string) => isAr ? ar : en;
  const storageKey = `sofi:hint:dismissed:${type}`;
  const [dismissed, setDismissed] = useState(() => localStorage.getItem(storageKey) === '1');

  const dismiss = () => {
    localStorage.setItem(storageKey, '1');
    setDismissed(true);
  };

  // Don't show if dismissed
  if (dismissed) return null;

  // Don't show if no usage data
  if (!usage) return null;

  const maxProp = usage.properties.max;
  const maxAgent = usage.agents.max;

  // Don't show for unlimited plans
  if (maxProp === 0 && maxAgent === 0) return null;

  if (type === 'properties') {
    // Show if properties < 50% of limit or at 0 (but not at limit)
    const pct = maxProp > 0 ? usage.properties.current / maxProp : 0;
    if (usage.properties.current >= maxProp || pct >= 0.5) return null;

    return (
      <div className="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-6 relative">
        <button onClick={dismiss} className="absolute top-3 left-3 p-0.5 hover:bg-amber-100 rounded-lg transition-colors">
          <X className="w-4 h-4 text-amber-400" />
        </button>
        <div className="flex items-start gap-3">
          <div className="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center shrink-0 mt-0.5">
            <Lightbulb className="w-4 h-4 text-amber-600" />
          </div>
          <div className="text-sm text-amber-800">
            <p className="font-medium mb-1">{L('هل تعلم؟', 'Did you know?')}</p>
            <p className="text-amber-700 leading-relaxed">
              {maxProp > 0
                ? L(
                    `خطتك الحالية تسمح بإضافة حتى ${maxProp} عقار. يمكنك ترقية خطتك لإضافة المزيد من العقارات والوكلاء.`,
                    `Your current plan allows up to ${maxProp} properties. Upgrade your plan to add more properties and agents.`
                  )
                : L(
                    'يمكنك إضافة عقارات غير محدودة مع خطتك الحالية.',
                    'You can add unlimited properties with your current plan.'
                  )}
            </p>
            <Link to="/dashboard/subscription"
              className="inline-flex items-center gap-1 mt-2 text-amber-700 font-medium hover:text-amber-800 underline underline-offset-2">
              {L('عرض الخطط', 'View plans')} <ArrowUp className="w-3.5 h-3.5" />
            </Link>
          </div>
        </div>
      </div>
    );
  }

  if (type === 'agents') {
    const pct = maxAgent > 0 ? usage.agents.current / maxAgent : 0;
    if (usage.agents.current >= maxAgent || pct >= 0.5) return null;

    return (
      <div className="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-6 relative">
        <button onClick={dismiss} className="absolute top-3 left-3 p-0.5 hover:bg-amber-100 rounded-lg transition-colors">
          <X className="w-4 h-4 text-amber-400" />
        </button>
        <div className="flex items-start gap-3">
          <div className="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center shrink-0 mt-0.5">
            <Lightbulb className="w-4 h-4 text-amber-600" />
          </div>
          <div className="text-sm text-amber-800">
            <p className="font-medium mb-1">{L('هل تعلم؟', 'Did you know?')}</p>
            <p className="text-amber-700 leading-relaxed">
              {maxAgent > 0
                ? L(
                    `خطتك الحالية تسمح بإضافة حتى ${maxAgent} وكيل. يمكنك ترقية خطتك لإضافة المزيد من الوكلاء والعقارات.`,
                    `Your current plan allows up to ${maxAgent} agents. Upgrade your plan to add more agents and properties.`
                  )
                : L(
                    'يمكنك إضافة وكلاء غير محدودين مع خطتك الحالية.',
                    'You can add unlimited agents with your current plan.'
                  )}
            </p>
            <Link to="/dashboard/subscription"
              className="inline-flex items-center gap-1 mt-2 text-amber-700 font-medium hover:text-amber-800 underline underline-offset-2">
              {L('عرض الخطط', 'View plans')} <ArrowUp className="w-3.5 h-3.5" />
            </Link>
          </div>
        </div>
      </div>
    );
  }

  // Subscription page hint
  if (maxProp === 0 && maxAgent === 0) return null;

  return (
    <div className="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-6 relative">
      <button onClick={dismiss} className="absolute top-3 left-3 p-0.5 hover:bg-amber-100 rounded-lg transition-colors">
        <X className="w-4 h-4 text-amber-400" />
      </button>
      <div className="flex items-start gap-3">
        <div className="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center shrink-0 mt-0.5">
          <Lightbulb className="w-4 h-4 text-amber-600" />
        </div>
        <div className="text-sm text-amber-800">
          <p className="font-medium mb-1">{L('اختر الخطة المناسبة', 'Choose the right plan')}</p>
          <p className="text-amber-700 leading-relaxed">
            {L(
              'كل خطة توفر عدداً مختلفاً من العقارات والوكلاء. حالياً أنت تستخدم {p} من {mp} عقار و {a} من {ma} وكيل. يمكنك ترقية خطتك في أي وقت.',
              'Each plan offers different property and agent limits. You are currently using {p} of {mp} properties and {a} of {ma} agents. Upgrade anytime.'
            )
              .replace('{p}', String(usage.properties.current))
              .replace('{mp}', String(maxProp))
              .replace('{a}', String(usage.agents.current))
              .replace('{ma}', String(maxAgent))}
          </p>
        </div>
      </div>
    </div>
  );
}
