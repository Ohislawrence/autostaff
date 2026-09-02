import { Link, router, useForm } from '@inertiajs/react';
import PlatformLayout from '@/Layouts/PlatformLayout';

const statusColors = {
    new: 'bg-gray-100 text-gray-600',
    qualified: 'bg-green-100 text-green-700',
    disqualified: 'bg-red-100 text-red-700',
    contacted: 'bg-teal-100 text-teal-700',
    replied: 'bg-orange-100 text-orange-700',
    converted: 'bg-blue-100 text-blue-700',
    bounced: 'bg-red-100 text-red-600',
    unsubscribed: 'bg-gray-100 text-gray-500',
};

export default function ProspectDetail({ prospect }) {
    const reply = useForm({ subject: '', body: '' });
    const act = (url) => router.post(url);
    const breakdown = prospect.score_breakdown || {};
    const messages = [...(prospect.messages || [])].sort((a, b) => new Date(a.created_at) - new Date(b.created_at));

    return (
        <PlatformLayout title="Prospect Detail">
            <Link href="/platform/prospecting/prospects" className="text-sm text-blue-600 hover:underline mb-4 inline-block">← Back to prospects</Link>

            <div className="flex items-start justify-between mb-6">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">{prospect.name || 'Unnamed Prospect'}</h1>
                    <p className="text-sm text-gray-500">{prospect.email} · {prospect.campaign?.name}</p>
                </div>
                <div className="flex items-center gap-2">
                    <span className={`px-2 py-1 rounded-full text-xs font-medium ${statusColors[prospect.status] || 'bg-gray-100 text-gray-600'}`}>{prospect.status}</span>
                    <span className={`inline-flex items-center justify-center w-9 h-9 rounded-full text-sm font-bold ${prospect.score >= 7 ? 'bg-green-100 text-green-700' : prospect.score >= 4 ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-600'}`}>{prospect.score || '–'}/10</span>
                </div>
            </div>

            <div className="grid grid-cols-3 gap-6 mb-6">
                <div className="col-span-2 space-y-6">
                    <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h3 className="font-semibold text-gray-900 mb-3">Profile</h3>
                        <div className="grid grid-cols-2 gap-3 text-sm">
                            <div><p className="text-xs text-gray-400">Title</p><p className="text-gray-800">{prospect.title || '—'}</p></div>
                            <div><p className="text-xs text-gray-400">Company</p><p className="text-gray-800">{prospect.company || '—'}</p></div>
                            <div><p className="text-xs text-gray-400">Company Size</p><p className="text-gray-800">{prospect.company_size || '—'}</p></div>
                            <div><p className="text-xs text-gray-400">Industry</p><p className="text-gray-800">{prospect.industry || '—'}</p></div>
                            <div><p className="text-xs text-gray-400">Location</p><p className="text-gray-800">{prospect.location || '—'}</p></div>
                            <div><p className="text-xs text-gray-400">Source</p><p className="text-gray-800 capitalize">{prospect.source?.replace('_', ' ') || '—'}</p></div>
                            <div className="col-span-2"><p className="text-xs text-gray-400">Website</p><a href={prospect.website} target="_blank" rel="noreferrer" className="text-blue-600 hover:underline">{prospect.website || '—'}</a></div>
                            <div className="col-span-2"><p className="text-xs text-gray-400">LinkedIn</p><a href={prospect.linkedin_url} target="_blank" rel="noreferrer" className="text-blue-600 hover:underline">{prospect.linkedin_url || '—'}</a></div>
                        </div>
                    </div>

                    <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h3 className="font-semibold text-gray-900 mb-3">AI Email (2-pass)</h3>
                        {prospect.email_body ? (
                            <>
                                <p className="text-sm font-medium text-gray-800 mb-2">Subject: {prospect.email_subject}</p>
                                <pre className="whitespace-pre-wrap font-sans text-sm text-gray-700 bg-gray-50 rounded-lg p-4">{prospect.email_body}</pre>
                            </>
                        ) : <p className="text-sm text-gray-400">No email generated yet. Click "Generate Email".</p>}
                    </div>

                    <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h3 className="font-semibold text-gray-900 mb-3">Message Timeline</h3>
                        <div className="space-y-3">
                            {messages.map((m) => (
                                <div key={m.id} className={`border-l-4 ${m.direction === 'inbound' ? 'border-orange-300' : 'border-blue-300'} pl-3`}>
                                    <div className="flex items-center gap-2 text-xs text-gray-500">
                                        <span className={`px-2 py-0.5 rounded-full font-medium ${m.direction === 'inbound' ? 'bg-orange-100 text-orange-700' : 'bg-blue-100 text-blue-700'}`}>{m.direction === 'inbound' ? 'Reply' : `Pass ${m.pass}`}</span>
                                        <span>{m.status}</span>
                                        <span>{new Date(m.created_at).toLocaleString()}</span>
                                    </div>
                                    {m.subject && <p className="text-sm font-medium text-gray-800 mt-1">{m.subject}</p>}
                                    <p className="text-sm text-gray-600 mt-0.5 whitespace-pre-wrap">{m.body?.slice(0, 400)}</p>
                                </div>
                            ))}
                            {messages.length === 0 && <p className="text-sm text-gray-400">No messages yet.</p>}
                        </div>
                    </div>
                </div>

                <div className="space-y-6">
                    <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h3 className="font-semibold text-gray-900 mb-3">Score Breakdown</h3>
                        {Object.keys(breakdown).length > 0 ? (
                            <div className="space-y-2">
                                {Object.entries(breakdown).map(([k, v]) => (
                                    <div key={k} className="flex justify-between text-sm"><span className="text-gray-600 capitalize">{k.replace(/_/g, ' ')}</span><span className="font-medium text-gray-900">{v}</span></div>
                                ))}
                            </div>
                        ) : <p className="text-sm text-gray-400">Not scored yet.</p>}
                        {prospect.qualification_notes && <p className="text-xs text-gray-500 mt-3 border-t border-gray-100 pt-3">{prospect.qualification_notes}</p>}
                    </div>

                    <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h3 className="font-semibold text-gray-900 mb-3">Compliance</h3>
                        <div className="space-y-2 text-sm">
                            <div className="flex justify-between"><span className="text-gray-600">Validation</span><span className="font-medium capitalize">{prospect.validation_status || 'pending'}</span></div>
                            <div className="flex justify-between"><span className="text-gray-600">Data Subject</span><span className="font-medium capitalize">{prospect.data_subject_type || 'unknown'}</span></div>
                            <div className="flex justify-between"><span className="text-gray-600">Legal Basis</span><span className="font-medium capitalize">{prospect.legal_basis || '—'}</span></div>
                        </div>
                    </div>

                    <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h3 className="font-semibold text-gray-900 mb-3">Actions</h3>
                        <div className="space-y-2">
                            <button onClick={() => act(`/platform/prospecting/prospects/${prospect.id}/qualify`)} className="w-full px-3 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">Qualify (score 1-10)</button>
                            <button onClick={() => act(`/platform/prospecting/prospects/${prospect.id}/generate`)} className="w-full px-3 py-2 bg-purple-600 text-white rounded-lg text-sm font-medium hover:bg-purple-700">Generate 2-Pass Email</button>
                            <button onClick={() => act(`/platform/prospecting/prospects/${prospect.id}/send`)} className="w-full px-3 py-2 bg-teal-600 text-white rounded-lg text-sm font-medium hover:bg-teal-700">Send Email</button>
                            <button onClick={() => act(`/platform/prospecting/prospects/${prospect.id}/validate`)} className="w-full px-3 py-2 bg-gray-600 text-white rounded-lg text-sm font-medium hover:bg-gray-700">Validate Email</button>
                            <button onClick={() => { if (confirm('Add to do-not-contact list?')) act(`/platform/prospecting/prospects/${prospect.id}/suppress`); }} className="w-full px-3 py-2 bg-yellow-600 text-white rounded-lg text-sm font-medium hover:bg-yellow-700">Add to DNC List</button>
                            <button onClick={() => { if (confirm('Delete this prospect?')) router.delete(`/platform/prospecting/prospects/${prospect.id}`); }} className="w-full px-3 py-2 bg-red-100 text-red-700 rounded-lg text-sm font-medium hover:bg-red-200">Delete</button>
                        </div>
                    </div>

                    <form onSubmit={(e) => { e.preventDefault(); reply.post(`/platform/prospecting/prospects/${prospect.id}/reply`); }} className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h3 className="font-semibold text-gray-900 mb-3">Record Reply (test alerts)</h3>
                        <input className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm mb-2" placeholder="Subject" value={reply.data.subject} onChange={(e) => reply.setData('subject', e.target.value)} />
                        <textarea className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm mb-2" rows={3} placeholder="Reply body" value={reply.data.body} onChange={(e) => reply.setData('body', e.target.value)} required />
                        <button type="submit" disabled={reply.processing} className="w-full px-3 py-2 bg-orange-500 text-white rounded-lg text-sm font-medium hover:bg-orange-600">Dispatch Reply Alerts</button>
                    </form>
                </div>
            </div>
        </PlatformLayout>
    );
}
