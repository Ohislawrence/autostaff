import { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import TenantLayout from '@/Layouts/TenantLayout';

const DEPARTMENTS = {
    revenue: { icon: '💰', label: 'Revenue' },
    customer_experience: { icon: '💬', label: 'Customer Experience' },
    operations: { icon: '🛒', label: 'Operations' },
    administration: { icon: '🧑‍💼', label: 'Administration' },
};
const DEPT_ORDER = ['revenue', 'customer_experience', 'operations', 'administration'];

export default function Recommend({ recommendations }) {
    const { flash } = usePage().props;
    const [selected, setSelected] = useState(() => new Set(recommendations.map((r) => r.id)));
    const [submitting, setSubmitting] = useState(false);

    const toggle = (id) => {
        setSelected((prev) => {
            const next = new Set(prev);
            if (next.has(id)) next.delete(id); else next.add(id);
            return next;
        });
    };

    const deploy = () => {
        setSubmitting(true);
        router.post('/ai-employees/workforce/deploy', { templates: [...selected] }, { onFinish: () => setSubmitting(false) });
    };

    const grouped = DEPT_ORDER
        .map((slug) => ({ slug, ...DEPARTMENTS[slug], items: recommendations.filter((r) => r.department === slug) }))
        .filter((g) => g.items.length > 0);

    return (
        <TenantLayout header="Build Your AI Workforce">
            <Head title="Build Your AI Workforce" />

            {flash?.error && <div className="mb-4 p-4 bg-red-50 text-red-700 rounded-xl text-sm">{flash.error}</div>}

            <div className="mb-6 flex items-start justify-between">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">✨ Your recommended AI workforce</h1>
                    <p className="text-sm text-gray-500 mt-1">Based on what you sell, Nomdal recommends these employees. Deselect any you don't need.</p>
                </div>
                <Link href="/ai-employees" className="text-sm text-blue-600 hover:underline">← Back</Link>
            </div>

            <div className="space-y-8 mb-8">
                {grouped.map((group) => (
                    <div key={group.slug}>
                        <div className="flex items-center gap-2 mb-3">
                            <span className="text-lg">{group.icon}</span>
                            <h2 className="text-sm font-bold uppercase tracking-wide text-gray-600">{group.label}</h2>
                            <span className="text-xs text-gray-400">{group.items.length}</span>
                        </div>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {group.items.map((r) => {
                                const checked = selected.has(r.id);
                                return (
                                    <div key={r.id} className={`bg-white rounded-xl border p-5 transition-all ${checked ? 'border-blue-300 shadow-sm' : 'border-gray-200 opacity-60'}`}>
                                        <div className="flex items-start justify-between">
                                            <div className="flex items-center gap-3">
                                                <div className="w-10 h-10 rounded-full bg-gradient-to-br from-blue-100 to-blue-200 flex items-center justify-center text-lg">{r.department_icon}</div>
                                                <div>
                                                    <h3 className="font-semibold text-gray-900 text-sm">{r.name}</h3>
                                                    <p className="text-xs text-gray-500">{r.role}</p>
                                                </div>
                                            </div>
                                            <label className="flex items-center gap-1 text-xs font-medium text-gray-600 cursor-pointer">
                                                <input type="checkbox" checked={checked} onChange={() => toggle(r.id)} className="rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
                                                {r.core ? 'Core' : 'Recommended'}
                                            </label>
                                        </div>
                                        <p className="text-xs text-gray-500 mt-3">{r.description}</p>
                                        <div className="mt-3 bg-blue-50 rounded-lg px-3 py-2 text-xs text-blue-800">
                                            <span className="font-semibold">Why:</span> {r.reason}
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                ))}
            </div>

            <div className="sticky bottom-4 flex items-center justify-between bg-white/90 backdrop-blur rounded-xl border border-gray-200 shadow-lg p-4">
                <p className="text-sm text-gray-500">{selected.size} employee{selected.size === 1 ? '' : 's'} selected</p>
                <div className="flex items-center gap-3">
                    <Link href="/ai-employees" className="px-4 py-2.5 text-sm text-gray-600 font-medium hover:bg-gray-50 rounded-lg">Cancel</Link>
                    <button
                        onClick={deploy}
                        disabled={submitting || selected.size === 0}
                        className="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg text-sm font-semibold hover:from-blue-700 hover:to-blue-800 disabled:opacity-50 transition-all shadow-md shadow-blue-200"
                    >
                        {submitting ? 'Deploying…' : '🚀 Deploy Workforce'}
                    </button>
                </div>
            </div>
        </TenantLayout>
    );
}
