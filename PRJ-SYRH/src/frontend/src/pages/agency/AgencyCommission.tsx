import { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { PiggyBank, Loader2 } from 'lucide-react';
import { fetchCommissionReport, type CommissionReport } from '../../api/agency';

const MONTHS_AR = ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];
const MONTHS_EN = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

export default function AgencyCommission() {
  const { i18n } = useTranslation();
  const isAr = i18n.language === 'ar';
  const L = (ar: string, en: string) => isAr ? ar : en;
  const months = isAr ? MONTHS_AR : MONTHS_EN;
  const [report, setReport] = useState<CommissionReport | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchCommissionReport().then(setReport).finally(() => setLoading(false));
  }, []);

  if (loading) {
    return <div className="flex items-center justify-center h-64"><Loader2 className="w-8 h-8 animate-spin text-primary" /></div>;
  }

  const maxCommission = Math.max(...(report?.monthly?.map(m => m.total_commission) || [0]), 1);
  const maxDeals = Math.max(...(report?.monthly?.map(m => m.deal_count) || [0]), 1);

  return (
    <div>
      <h1 className="text-2xl font-bold text-stone-900 mb-6">{L('تقرير العمولات', 'Commission Report')}</h1>

      {!report ? (
        <div className="text-center py-16">
          <PiggyBank className="w-12 h-12 text-stone-300 mx-auto mb-3" />
          <p className="text-stone-500">{L('لا توجد بيانات', 'No data')}</p>
        </div>
      ) : (
        <>
          {/* Summary */}
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            <div className="card-3d p-5">
              <div className="text-sm text-stone-500 mb-1">{L('إجمالي الصفقات', 'Total Deals')}</div>
              <div className="text-2xl font-bold text-stone-900">{report.totals?.total_deals ?? 0}</div>
            </div>
            <div className="card-3d p-5">
              <div className="text-sm text-stone-500 mb-1">{L('إجمالي الحجم', 'Total Volume')}</div>
              <div className="text-2xl font-bold text-stone-900">${(report.totals?.total_volume ?? 0).toLocaleString()}</div>
            </div>
            <div className="card-3d p-5">
              <div className="text-sm text-stone-500 mb-1">{L('إجمالي العمولات', 'Total Commission')}</div>
              <div className="text-2xl font-bold text-primary">${(report.totals?.total_commission ?? 0).toLocaleString()}</div>
              <div className="text-xs text-stone-400 mt-1">{L('نسبة العمولة', 'Rate')}: {report.rate}%</div>
            </div>
          </div>

          {/* Chart */}
          <div className="card-3d p-5">
            <h2 className="font-bold text-stone-900 mb-4">{L('العمولات الشهرية', 'Monthly Commission')}</h2>
            {report.monthly?.length === 0 ? (
              <p className="text-sm text-stone-400 text-center py-8">{L('لا توجد عمولات هذا العام', 'No commissions this year')}</p>
            ) : (
              <div className="space-y-3">
                <div className="flex items-end gap-2 h-48" dir={isAr ? 'rtl' : 'ltr'}>
                  {report.monthly?.map(m => (
                    <div key={m.month} className="flex-1 flex flex-col items-center gap-1 h-full justify-end">
                      <span className="text-xs text-emerald-600 font-medium">${m.total_commission.toLocaleString()}</span>
                      <div className="w-full rounded-lg bg-primary/20 hover:bg-primary/30 transition-colors relative" style={{ height: `${Math.max((m.total_commission / maxCommission) * 80, 4)}%` }}>
                        <div className="absolute inset-0 rounded-lg bg-gradient-to-t from-primary to-primary/40" style={{ height: `${(m.deal_count / maxDeals) * 100}%` }} />
                      </div>
                      <span className="text-xs text-stone-400">{months[m.month - 1]}</span>
                    </div>
                  ))}
                </div>
                <div className="flex items-center justify-center gap-6 text-xs text-stone-500">
                  <span className="flex items-center gap-1.5"><span className="w-3 h-3 rounded bg-primary/20" /> {L('العمولة', 'Commission')}</span>
                  <span className="flex items-center gap-1.5"><span className="w-3 h-3 rounded bg-primary" /> {L('الصفقات', 'Deals')}</span>
                </div>
              </div>
            )}
          </div>
        </>
      )}
    </div>
  );
}
