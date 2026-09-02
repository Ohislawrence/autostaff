import { Head, usePage, useForm } from '@inertiajs/react';
import TenantLayout from '@/Layouts/TenantLayout';

export default function Index({ gaps }) {
    const { flash } = usePage().props;
    const { data, setData, post, processing } = useForm({ suggested_answer: '' });

    const categoryBadge = (category) => {
        const map = {
            escalation: 'bg-red-100 text-red-700',
            failed_tool: 'bg-orange-100 text-orange-700',
            low_confidence: 'bg-yellow-100 text-yellow-700',
            unanswered: 'bg-blue-100 text-blue-700',
            feedback: 'bg-purple-100 text-purple-700',
        };
        return map[category] || 'bg-gray-100 text-gray-600';
    };

    const resolveGap = (e, id) => {
        e.preventDefault();
        post(`/knowledge-gaps/${id}/resolve`, { onSuccess: () => setData('suggested_answer', '') });
    };

    return (
        <TenantLayout header="Knowledge Gaps">
            <Head title="Knowledge Gaps" />
            {flash?.success && <div className="mb-4 p-4 bg-green-50 text-green-700 rounded-lg text-sm">{flash.success}</div>}

            <p className="text-sm text-gray-500 mb-4">
                Recurring requests the AI couldn't fully answer. Resolve them by adding the answer to your knowledge base.
            </p>

            <div className="space-y-3">
                {gaps.map(gap => (
                    <div key={gap.id} className="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                        <div className="flex items-start justify-between gap-4">
                            <div className="flex-1 min-w-0">
                                <div className="flex items-center gap-2 mb-1">
                                    <span className={`px-2 py-0.5 rounded-full text-xs font-medium capitalize ${categoryBadge(gap.category)}`}>{gap.category.replace('_', ' ')}</span>
                                    <span className="text-xs text-gray-400">Asked {gap.frequency}×</span>
                                    <span className="text-xs text-gray-400">· {gap.last_seen_at}</span>
                                </div>
                                <p className="font-medium text-gray-900">{gap.question}</p>
                                {gap.suggested_answer && <p className="text-sm text-gray-500 mt-1">Suggested: {gap.suggested_answer}</p>}
                            </div>
                            <form onSubmit={(e) => resolveGap(e, gap.id)} className="flex gap-2 items-start">
                                <input
                                    type="text"
                                    value={data.suggested_answer}
                                    onChange={(e) => setData('suggested_answer', e.target.value)}
                                    placeholder="Add answer (optional)"
                                    className="px-3 py-1.5 border border-gray-300 rounded-lg text-sm"
                                />
                                <button type="submit" disabled={processing} className="px-3 py-1.5 bg-green-600 text-white rounded-lg text-xs font-medium disabled:opacity-50">
                                    Resolve
                                </button>
                            </form>
                        </div>
                    </div>
                ))}
                {gaps.length === 0 && (
                    <p className="text-center text-gray-400 py-12">No open knowledge gaps. Your AI is handling everything well. 🎉</p>
                )}
            </div>
        </TenantLayout>
    );
}