import { usePage } from '@inertiajs/react';
import PlatformLayout from '@/Layouts/PlatformLayout';

export default function Usage({ mrr, aiCostTotal, grossContribution, aiCostByOrg, monthlyCost }) {
    const fc = (n) => `₦${(parseFloat(n) || 0).toLocaleString(undefined, { minimumFractionDigits: 2 })}`;
    const maxCost = Math.max(...monthlyCost.map(d => d.cost || 0), 1);

    return (
        <PlatformLayout title="Usage & Costs">
            <h1 className="text-2xl font-bold text-gray-900 mb-6">Usage & Cost Management</h1>

            <div className="grid grid-cols-3 gap-4 mb-6">
                {[
                    { label: 'MRR', value: fc(mrr), color: 'text-green-600' },
                    { label: 'AI Cost (MTD)', value: fc(aiCostTotal), color: 'text-red-600' },
                    { label: 'Gross Contribution', value: fc(grossContribution), color: grossContribution >= 0 ? 'text-teal-600' : 'text-rose-600' },
                ].map(s => (<div key={s.label} className="bg-white rounded-xl shadow-sm border border-gray-200 p-6"><p className={`text-2xl font-bold ${s.color}`}>{s.value}</p><p className="text-sm text-gray-500 mt-1">{s.label}</p></div>))}
            </div>

            <div className="grid grid-cols-2 gap-6">
                <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 className="font-semibold text-gray-900 mb-4">AI Cost Trend (6 months)</h3>
                    <div className="flex items-end gap-2 h-28">
                        {monthlyCost.map(d => (<div key={d.month} className="flex-1 flex flex-col items-center"><span className="text-xs text-gray-600 mb-1">{fc(d.cost)}</span><div className="w-full bg-red-100 rounded-t flex-1 relative" style={{ minHeight: 4 }}><div className="bg-red-500 w-full rounded-t absolute bottom-0" style={{ height: `${Math.max((d.cost / maxCost) * 100, 4)}%` }} /></div><span className="text-xs text-gray-400 mt-1">{d.month}</span></div>))}
                    </div>
                </div>
                <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 className="font-semibold text-gray-900 mb-4">AI Cost by Organization</h3>
                    <div className="space-y-2">
                        {aiCostByOrg?.map((org, i) => (<div key={i} className="flex items-center justify-between text-sm"><span className="text-gray-600 truncate mr-2">{org.name}</span><span className="font-medium text-red-600">{fc(org.cost)}</span></div>))}
                        {(!aiCostByOrg || aiCostByOrg.length === 0) && <p className="text-sm text-gray-400 text-center py-4">No data yet.</p>}
                    </div>
                </div>
            </div>
        </PlatformLayout>
    );
}