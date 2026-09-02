import { Head, usePage } from '@inertiajs/react';
import TenantLayout from '@/Layouts/TenantLayout';

export default function Index({ reports }) {
    const { auth } = usePage().props;
    const currency = auth.organization?.currency || 'NGN';
    const symbol = { USD: '$', NGN: '₦', GHS: 'GH₵', KES: 'KSh', ZAR: 'R', EUR: '€', GBP: '£', CAD: 'CA$', AUD: 'A$', INR: '₹', JPY: '¥' }[currency] || (currency + ' ');

    return (
        <TenantLayout header="Reports">
            <Head title="Reports" />
            <p className="text-sm text-gray-500 mb-4">Automated business performance reports, generated on a schedule.</p>

            <div className="space-y-4">
                {reports.map(r => (
                    <div key={r.id} className="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                        <div className="flex items-center justify-between mb-3">
                            <h3 className="font-semibold text-gray-900 capitalize">{r.period} Report</h3>
                            <span className="text-xs text-gray-400">{r.period_start} → {r.period_end}</span>
                        </div>
                        <p className="text-sm text-gray-600 mb-3">{r.summary_text}</p>
                        <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
                            <Metric label="Conversations" value={r.metrics?.conversations ?? 0} />
                            <Metric label="Leads" value={r.metrics?.leads ?? 0} />
                            <Metric label="Orders" value={r.metrics?.orders ?? 0} />
                            <Metric label="Revenue" value={`${symbol}${Number(r.metrics?.revenue ?? 0).toLocaleString()}`} />
                            <Metric label="Escalations" value={r.metrics?.escalations ?? 0} />
                            <Metric label="AI Resolution" value={`${r.metrics?.ai_resolution_rate ?? 0}%`} />
                            <Metric label="AI Runs" value={r.metrics?.ai_runs ?? 0} />
                            <Metric label="Knowledge Gaps" value={r.metrics?.open_knowledge_gaps ?? 0} />
                        </div>
                    </div>
                ))}
                {reports.length === 0 && (
                    <p className="text-center text-gray-400 py-12">No reports generated yet.</p>
                )}
            </div>
        </TenantLayout>
    );
}

function Metric({ label, value }) {
    return (
        <div className="bg-gray-50 rounded-lg p-3">
            <p className="text-lg font-bold text-gray-900">{value}</p>
            <p className="text-xs text-gray-500">{label}</p>
        </div>
    );
}