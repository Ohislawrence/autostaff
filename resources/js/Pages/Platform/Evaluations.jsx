import { usePage, useForm } from '@inertiajs/react';
import PlatformLayout from '@/Layouts/PlatformLayout';

export default function Evaluations({ evaluations, categories, filters }) {
    const { flash } = usePage().props;
    const { data, setData, post, processing } = useForm({ input: '', expected_output: '', category: 'correctness' });

    return (
        <PlatformLayout title="AI Evaluations">
            <h1 className="text-2xl font-bold text-gray-900 mb-6">AI Evaluation Test Cases</h1>
            <form onSubmit={(e) => { e.preventDefault(); post('/platform/evaluations'); }} className="mb-6 bg-white rounded-xl shadow-sm border border-gray-200 p-4 space-y-3">
                <div className="grid grid-cols-3 gap-3">
                    <select value={data.category} onChange={e => setData('category', e.target.value)} className="px-3 py-2 border rounded text-sm">
                        {categories.map(c => <option key={c} value={c}>{c.replace('_', ' ')}</option>)}
                    </select>
                    <input value={data.input} onChange={e => setData('input', e.target.value)} className="col-span-2 px-3 py-2 border rounded text-sm" placeholder="Test input/question *" required />
                </div>
                <textarea value={data.expected_output} onChange={e => setData('expected_output', e.target.value)} className="w-full px-3 py-2 border rounded text-sm" placeholder="Expected output/behavior *" rows={2} required />
                <button type="submit" disabled={processing} className="px-4 py-2 bg-purple-600 text-white rounded text-sm">{processing ? 'Adding...' : 'Add Test Case'}</button>
            </form>
            <div className="space-y-3">
                {evaluations?.data?.map(e => (
                    <div key={e.id} className="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                        <div className="flex items-center justify-between mb-2">
                            <span className="px-2 py-0.5 bg-purple-100 text-purple-700 rounded text-xs font-medium">{e.category}</span>
                            <span className={`px-2 py-0.5 rounded-full text-xs font-medium ${e.status === 'passed' ? 'bg-green-100 text-green-700' : e.status === 'failed' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600'}`}>{e.status}</span>
                        </div>
                        <p className="text-sm font-medium text-gray-900 mb-1">{e.input}</p>
                        <p className="text-xs text-gray-500">Expected: {e.expected_output}</p>
                        {e.actual_output && <p className="text-xs text-gray-500">Actual: {e.actual_output}</p>}
                    </div>
                ))}
                {(!evaluations?.data || evaluations.data.length === 0) && <p className="text-sm text-gray-400 text-center py-8">No evaluation test cases yet.</p>}
            </div>
        </PlatformLayout>
    );
}