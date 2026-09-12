import { Link, router } from '@inertiajs/react';
import TenantLayout from '@/Layouts/TenantLayout';

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
    const act = (url) => router.post(url);
    const breakdown = prospect.score_breakdown || {};

    return (
        <TenantLayout header="Prospect Detail">
            <Link href="/prospecting/prospects" className="text-sm text-blue-600 hover:underline mb-4 inline-block">← Back to prospects</Link>

            <div className="flex items-start justify-between mb-6">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">{prospect.name || 'Unnamed Prospect'}</h1>
                    <p className="text-sm text-gray-500">{prospect.email} · {prospect.campaign?.name}</p>
                </div>
                <div className="flex items-center gap-2">
                    <span className={`px-2 py-1 rounded-full text-xs font-medium ${statusColors[prospect.status] || 'bg-gray-100 text-gray-600'}`}>{prospect.status}</span>
                    {prospect.intent && (
                        <span className={`px-2 py-1 rounded-full text-xs font-medium ${prospect.intent === 'interested' ? 'bg-green-100 text-green-700' : prospect.intent === 'not_interested' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600'}`}>
                            {prospect.intent === 'interested' ? '✓ interested' : prospect.intent === 'not_interested' ? '✗ not interested' : prospect.intent}
                        </span>
                    )}
                    {prospect.meeting_booked_at && (
                        <a href={prospect.meeting_link || '#'} target="_blank" rel="noreferrer" className="px-2 py-1 rounded-full text-xs font-medium bg-sky-100 text-sky-700">📅 meeting booked</a>
                    )}
                    <span className={`inline-flex items-center justify-center w-9 h-9 rounded-full text-sm font-bold ${prospect.score >= 7 ? 'bg-green-100 text-green-700' : prospect.score >= 4 ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-600'}`}>{prospect.score || '–'}/10</span>
                </div>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div className="lg:col-span-2 space-y-6">
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
                        </div>
                    </div>

                    <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h3 className="font-semibold text-gray-900 mb-3">Why this prospect?</h3>
                        {prospect.qualification_notes ? (
                            <p className="text-sm text-gray-700">{prospect.qualification_notes}</p>
                        ) : (
                            <p className="text-sm text-gray-400">No qualification notes yet. Run a qualification pass to score this prospect.</p>
                        )}
                        {Object.keys(breakdown).length > 0 && (
                            <div className="mt-4 grid grid-cols-2 gap-2">
                                {Object.entries(breakdown).map(([k, v]) => (
                                    <div key={k} className="flex justify-between text-sm bg-gray-50 rounded-lg px-3 py-2">
                                        <span className="text-gray-500 capitalize">{k.replace(/_/g, ' ')}</span>
                                        <span className="font-medium text-gray-800">{v}</span>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>

                    <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div className="flex items-center justify-between mb-3">
                            <h3 className="font-semibold text-gray-900">🔎 Research</h3>
                            <button onClick={() => act(`/prospecting/prospects/${prospect.id}/research`)} className="px-3 py-1.5 bg-indigo-50 text-indigo-700 rounded-lg text-xs font-medium hover:bg-indigo-100">Research now</button>
                        </div>
                        {prospect.research_notes ? (
                            <pre className="whitespace-pre-wrap text-sm text-gray-700 bg-gray-50 rounded-lg p-4">{prospect.research_notes}</pre>
                        ) : (
                            <p className="text-sm text-gray-400">Not researched yet. Click "Research now" to find evidence for why this prospect is a good fit.</p>
                        )}
                        {(prospect.research_sources || []).length > 0 && (
                            <div className="mt-3 space-y-1">
                                <p className="text-xs text-gray-400 font-medium">Sources</p>
                                {prospect.research_sources.map((s, i) => (
                                    <a key={i} href={s.url} target="_blank" rel="noreferrer" className="block text-xs text-blue-600 hover:underline truncate">{s.title || s.url}</a>
                                ))}
                            </div>
                        )}
                    </div>

                    <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h3 className="font-semibold text-gray-900 mb-3">Outreach Email</h3>
                        {prospect.email_body ? (
                            <div className="space-y-3">
                                <p className="text-sm font-medium text-gray-900">Subject: {prospect.email_subject}</p>
                                <pre className="whitespace-pre-wrap text-sm text-gray-700 bg-gray-50 rounded-lg p-4">{prospect.email_body}</pre>
                            </div>
                        ) : (
                            <p className="text-sm text-gray-400">No email drafted yet. Click "Generate 2-Pass Email" to create one.</p>
                        )}
                    </div>
                </div>

                <div className="space-y-6">
                    <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h3 className="font-semibold text-gray-900 mb-3">Actions</h3>
                        <div className="space-y-2">
                            <button onClick={() => act(`/prospecting/prospects/${prospect.id}/research`)} className="w-full px-3 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">🔎 Research prospect</button>
                            <button onClick={() => { if (confirm('Convert this prospect into an opportunity + quote + invoice?')) act(`/prospecting/prospects/${prospect.id}/convert`); }} className="w-full px-3 py-2 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700">💰 Convert to opportunity</button>
                            <button onClick={() => { if (confirm('Book a meeting tomorrow at 10:00 AM on Google Calendar?')) act(`/prospecting/prospects/${prospect.id}/meeting`); }} className="w-full px-3 py-2 bg-sky-600 text-white rounded-lg text-sm font-medium hover:bg-sky-700">📅 Book meeting (Google Calendar)</button>
                            <button onClick={() => act(`/prospecting/prospects/${prospect.id}/qualify`)} className="w-full px-3 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">Qualify (score 1-10)</button>
                            <button onClick={() => act(`/prospecting/prospects/${prospect.id}/generate`)} className="w-full px-3 py-2 bg-purple-600 text-white rounded-lg text-sm font-medium hover:bg-purple-700">Generate 2-Pass Email</button>
                            <button onClick={() => act(`/prospecting/prospects/${prospect.id}/send`)} className="w-full px-3 py-2 bg-teal-600 text-white rounded-lg text-sm font-medium hover:bg-teal-700">Send Email</button>
                            <button onClick={() => { if (confirm('Add to do-not-contact list?')) act(`/prospecting/prospects/${prospect.id}/suppress`); }} className="w-full px-3 py-2 bg-yellow-600 text-white rounded-lg text-sm font-medium hover:bg-yellow-700">Add to DNC List</button>
                        </div>
                    </div>

                    <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h3 className="font-semibold text-gray-900 mb-3">Compliance</h3>
                        <div className="space-y-2 text-sm">
                            <div className="flex justify-between"><span className="text-gray-600">Validation</span><span className="font-medium capitalize">{prospect.validation_status || 'pending'}</span></div>
                            <div className="flex justify-between"><span className="text-gray-600">Data Subject</span><span className="font-medium capitalize">{prospect.data_subject_type || 'unknown'}</span></div>
                            <div className="flex justify-between"><span className="text-gray-600">Legal Basis</span><span className="font-medium capitalize">{prospect.legal_basis || '—'}</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </TenantLayout>
    );
}
