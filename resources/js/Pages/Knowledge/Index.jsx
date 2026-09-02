import { Head, useForm, usePage, router } from '@inertiajs/react';
import TenantLayout from '@/Layouts/TenantLayout';
import { useState } from 'react';

export default function Index({ knowledgeBases, recentSources }) {
    const { flash } = usePage().props;
    const [showNewBase, setShowNewBase] = useState(false);
    const [showUpload, setShowUpload] = useState(null); // kb id or null
    const [inspectingQuery, setInspectingQuery] = useState('');
    const [inspectResults, setInspectResults] = useState(null);
    const [deleteConfirm, setDeleteConfirm] = useState(null); // { type: 'kb'|'source', id, name }

    const baseForm = useForm({ name: '', description: '' });
    const uploadForm = useForm({ type: 'file', title: '', file: null, url: '', content: '' });

    const createBase = (e) => {
        e.preventDefault();
        baseForm.post('/knowledge/bases', { onSuccess: () => { setShowNewBase(false); baseForm.reset(); } });
    };

    const uploadSource = (e) => {
        e.preventDefault();
        uploadForm.post(`/knowledge/bases/${showUpload}/sources`, {
            onSuccess: () => { uploadForm.reset(); setShowUpload(null); },
            onError: () => {
                // Errors are automatically available in uploadForm.errors
            },
            forceFormData: true,
        });
    };

    const doDelete = () => {
        if (!deleteConfirm) return;
        if (deleteConfirm.type === 'kb') {
            router.delete(`/knowledge/bases/${deleteConfirm.id}`, {
                onSuccess: () => setDeleteConfirm(null),
            });
        } else if (deleteConfirm.type === 'source') {
            router.delete(`/knowledge/sources/${deleteConfirm.id}`, {
                onSuccess: () => setDeleteConfirm(null),
            });
        }
    };

    const inspect = () => {
        if (!inspectingQuery.trim()) return;
        
        // Clear previous results and show loading state
        setInspectResults({ loading: true, query: inspectingQuery });
        
        fetch('/knowledge/inspect', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json', 
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ query: inspectingQuery }),
        })
        .then(r => {
            console.log('RAG Inspector Response Status:', r.status);
            if (!r.ok) {
                throw new Error(`Server error (${r.status})`);
            }
            return r.json();
        })
        .then(data => {
            console.log('RAG Inspector Response Data:', data);
            setInspectResults(data);
        })
        .catch(err => {
            console.error('RAG Inspector Error:', err);
            setInspectResults({ 
                query: inspectingQuery, 
                results: [], 
                embedding_used: false, 
                error: 'Search failed. Please try again or check browser console for details.' 
            });
        });
    };

    const sourceStatusBadge = (status) => {
        const map = { pending: 'bg-yellow-100 text-yellow-700', processing: 'bg-blue-100 text-blue-700', completed: 'bg-green-100 text-green-700', failed: 'bg-red-100 text-red-700' };
        return map[status] || 'bg-gray-100 text-gray-600';
    };

    return (
        <TenantLayout header="Knowledge Base">
            <Head title="Knowledge Base" />

            {flash?.success && <div className="mb-4 p-4 bg-green-50 text-green-700 rounded-lg text-sm">{flash.success}</div>}
            {flash?.error && <div className="mb-4 p-4 bg-red-50 text-red-700 rounded-lg text-sm">{flash.error}</div>}
            {(Object.values(uploadForm.errors).length > 0) && (
                <div className="mb-4 p-4 bg-red-50 text-red-700 rounded-lg text-sm">
                    <ul className="list-disc list-inside">
                        {Object.entries(uploadForm.errors).map(([key, msg]) => (
                            <li key={key}>{msg}</li>
                        ))}
                    </ul>
                </div>
            )}

            {/* Delete Confirmation Modal */}
            {deleteConfirm && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
                    <div className="bg-white rounded-xl shadow-xl p-6 max-w-sm w-full mx-4">
                        <h3 className="font-semibold text-gray-900 mb-2">Confirm Delete</h3>
                        <p className="text-sm text-gray-600 mb-4">
                            {deleteConfirm.type === 'kb'
                                ? `Delete knowledge base "${deleteConfirm.name}" and all its sources? This cannot be undone.`
                                : `Delete source "${deleteConfirm.name}"? This cannot be undone.`}
                        </p>
                        <div className="flex gap-3 justify-end">
                            <button onClick={() => setDeleteConfirm(null)}
                                className="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">
                                Cancel
                            </button>
                            <button onClick={doDelete}
                                className="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            )}

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {/* Left - Knowledge Bases */}
                <div className="lg:col-span-1">
                    <div className="flex items-center justify-between mb-3">
                        <h2 className="font-semibold text-gray-900">Knowledge Bases</h2>
                        <button onClick={() => setShowNewBase(!showNewBase)} className="text-xs text-primary-600 hover:text-primary-700 font-medium">
                            + New
                        </button>
                    </div>

                    {showNewBase && (
                        <form onSubmit={createBase} className="mb-4 p-3 bg-gray-50 rounded-lg border border-gray-200">
                            <input type="text" value={baseForm.data.name} onChange={e => baseForm.setData('name', e.target.value)}
                                className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm mb-2" placeholder="Knowledge base name" required />
                            <textarea value={baseForm.data.description} onChange={e => baseForm.setData('description', e.target.value)}
                                className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm mb-2" placeholder="Description (optional)" rows={2} />
                            <div className="flex gap-2">
                                <button type="submit" disabled={baseForm.processing} className="px-3 py-1.5 bg-primary-600 text-white rounded text-xs font-medium">Create</button>
                                <button type="button" onClick={() => setShowNewBase(false)} className="px-3 py-1.5 border border-gray-300 rounded text-xs">Cancel</button>
                            </div>
                        </form>
                    )}

                    <div className="space-y-2">
                        {knowledgeBases.map(kb => (
                            <div key={kb.id} className={`p-3 rounded-lg border ${showUpload === kb.id ? 'border-primary-300 bg-primary-50' : 'border-gray-200 bg-white'}`}>
                                <div className="flex items-center justify-between">
                                    <div>
                                        <h3 className="font-medium text-sm text-gray-900">{kb.name}</h3>
                                        <p className="text-xs text-gray-400">{kb.sources_count || 0} sources</p>
                                    </div>
                                    <div className="flex items-center gap-1">
                                        <button onClick={() => setShowUpload(showUpload === kb.id ? null : kb.id)}
                                            className="text-xs text-primary-600 hover:text-primary-700 font-medium">+ Upload</button>
                                        <button onClick={() => setDeleteConfirm({ type: 'kb', id: kb.id, name: kb.name })}
                                            className="text-xs text-red-400 hover:text-red-600 ml-2" title="Delete knowledge base">🗑</button>
                                    </div>
                                </div>

                                {showUpload === kb.id && (
                                    <form onSubmit={uploadSource} className="mt-3 pt-3 border-t border-gray-200 space-y-3">
                                        <select value={uploadForm.data.type} onChange={e => uploadForm.setData('type', e.target.value)}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                            <option value="file">Upload File (PDF, DOCX, TXT, CSV)</option>
                                            <option value="url">Website URL</option>
                                            <option value="manual">Manual Text Entry</option>
                                            <option value="faq">FAQ Entry</option>
                                        </select>

                                        <input type="text" value={uploadForm.data.title} onChange={e => uploadForm.setData('title', e.target.value)}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="Title" required />

                                        {uploadForm.data.type === 'file' && (
                                            <input type="file" onChange={e => uploadForm.setData('file', e.target.files[0])}
                                                className="w-full text-sm" accept=".pdf,.docx,.txt,.csv" />
                                        )}
                                        {uploadForm.data.type === 'url' && (
                                            <input type="url" value={uploadForm.data.url} onChange={e => uploadForm.setData('url', e.target.value)}
                                                className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="https://..." />
                                        )}
                                        {(uploadForm.data.type === 'manual' || uploadForm.data.type === 'faq') && (
                                            <textarea value={uploadForm.data.content} onChange={e => uploadForm.setData('content', e.target.value)}
                                                className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono" rows={5} placeholder="Paste or type content here..." />
                                        )}

                                        <div className="flex gap-2">
                                            <button type="submit" disabled={uploadForm.processing} className="px-3 py-1.5 bg-primary-600 text-white rounded text-xs font-medium">
                                                {uploadForm.processing ? 'Uploading...' : 'Upload & Process'}
                                            </button>
                                        </div>
                                    </form>
                                )}
                            </div>
                        ))}
                        {knowledgeBases.length === 0 && (
                            <p className="text-sm text-gray-400 p-4 text-center">No knowledge bases yet. Create one to get started.</p>
                        )}
                    </div>
                </div>

                {/* Right - Sources & RAG Inspector */}
                <div className="lg:col-span-2 space-y-6">
                    {/* Recent Sources */}
                    <div>
                        <h2 className="font-semibold text-gray-900 mb-3">Recent Sources</h2>
                        <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                            {recentSources.length === 0 ? (
                                <p className="text-sm text-gray-400 p-6 text-center">No sources uploaded yet.</p>
                            ) : (
                                <table className="w-full text-sm">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="text-left px-4 py-2.5 font-medium text-gray-500">Title</th>
                                            <th className="text-left px-4 py-2.5 font-medium text-gray-500">Type</th>
                                            <th className="text-left px-4 py-2.5 font-medium text-gray-500">Status</th>
                                            <th className="text-left px-4 py-2.5 font-medium text-gray-500">Progress</th>
                                            <th className="text-left px-4 py-2.5 font-medium text-gray-500 w-10"></th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-100">
                                        {recentSources.map(source => (
                                            <tr key={source.id}>
                                                <td className="px-4 py-2.5 text-gray-900">{source.title}</td>
                                                <td className="px-4 py-2.5 text-gray-500 uppercase text-xs">{source.type}</td>
                                                <td className="px-4 py-2.5">
                                                    <span className={`px-2 py-0.5 rounded-full text-xs font-medium ${sourceStatusBadge(source.status)}`}>
                                                        {source.status}
                                                    </span>
                                                </td>
                                                <td className="px-4 py-2.5 text-gray-500">
                                                    {source.status === 'processing' && (
                                                        <div className="flex items-center gap-2">
                                                            <div className="w-16 bg-gray-200 rounded-full h-1.5">
                                                                <div className="bg-primary-600 h-1.5 rounded-full" style={{ width: `${source.progress || 0}%` }} />
                                                            </div>
                                                            <span className="text-xs">{source.progress || 0}%</span>
                                                        </div>
                                                    )}
                                                    {source.status === 'completed' && '✓ Done'}
                                                    {source.status === 'failed' && '✗ Failed'}
                                                    {source.status === 'pending' && 'Queued'}
                                                </td>
                                                <td className="px-4 py-2.5">
                                                    <button onClick={() => setDeleteConfirm({ type: 'source', id: source.id, name: source.title })}
                                                        className="text-xs text-red-400 hover:text-red-600" title="Delete source">🗑</button>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            )}
                        </div>
                    </div>

                    {/* RAG Inspector */}
                    <div>
                        <h2 className="font-semibold text-gray-900 mb-3">RAG Inspector — Test Knowledge Retrieval</h2>
                        <div className="bg-white/80 backdrop-blur rounded-xl shadow-lg border border-blue-100 p-4">
                            <p className="text-xs text-gray-500 mb-3">
                                Enter a query to see which knowledge chunks would be retrieved. This helps you understand what the AI "knows."
                            </p>
                            <div className="flex gap-2 mb-4">
                                <input 
                                    type="text" 
                                    value={inspectingQuery} 
                                    onChange={e => setInspectingQuery(e.target.value)}
                                    className="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none transition-shadow" 
                                    placeholder='e.g., "What is your return policy?"'
                                    onKeyDown={e => e.key === 'Enter' && inspect()} 
                                />
                                <button 
                                    onClick={inspect} 
                                    disabled={inspectResults?.loading}
                                    className="px-4 py-2 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-xl text-sm font-medium hover:from-blue-700 hover:to-blue-800 disabled:opacity-50 transition-all shadow-md shadow-blue-200"
                                >
                                    {inspectResults?.loading ? 'Searching...' : 'Search'}
                                </button>
                            </div>
                            
                            {/* Loading State */}
                            {inspectResults?.loading && (
                                <div className="p-4 bg-blue-50 rounded-lg text-center">
                                    <p className="text-sm text-blue-600">Searching knowledge base...</p>
                                </div>
                            )}
                            
                            {/* Error State */}
                            {inspectResults?.error && !inspectResults?.loading && (
                                <div className="p-4 bg-red-50 border border-red-200 rounded-lg mb-3">
                                    <p className="text-sm text-red-700">{inspectResults.error}</p>
                                </div>
                            )}
                            
                            {/* Results */}
                            {inspectResults && !inspectResults.loading && (
                                <div className="space-y-3">
                                    <div className="flex items-center justify-between">
                                        <p className="text-xs text-gray-400">
                                            Found {inspectResults.results?.length || 0} results for "{inspectResults.query}"
                                            {inspectResults.embedding_used ? ' (using semantic search)' : ' (using keyword search)'}
                                        </p>
                                        {inspectResults.total_chunks !== undefined && (
                                            <p className="text-xs text-gray-400">
                                                Total knowledge: {inspectResults.total_chunks} chunks
                                                {inspectResults.chunks_with_embeddings !== undefined && 
                                                    ` (${inspectResults.chunks_with_embeddings} with embeddings)`
                                                }
                                            </p>
                                        )}
                                    </div>
                                    
                                    {inspectResults.results?.map((result, idx) => (
                                        <div key={idx} className="p-3 bg-gradient-to-r from-blue-50 to-blue-100 rounded-lg border border-blue-200">
                                            <div className="flex items-center gap-2 mb-1">
                                                <span className="text-xs text-gray-700 font-medium">Source: {result.source_title}</span>
                                                <span className="text-xs bg-gradient-to-r from-amber-400 to-amber-500 text-white px-2 py-0.5 rounded-full font-medium shadow-sm">
                                                    {(result.score * 100).toFixed(0)}% match
                                                </span>
                                            </div>
                                            <p className="text-sm text-gray-700 line-clamp-4">{result.content}</p>
                                        </div>
                                    ))}
                                    
                                    {(!inspectResults.results || inspectResults.results.length === 0) && (
                                        <div className="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                                            <p className="text-sm text-yellow-700 font-medium mb-2">
                                                ⚠️ No relevant knowledge found
                                            </p>
                                            {inspectResults.total_chunks === 0 ? (
                                                <p className="text-xs text-yellow-600">
                                                    You haven't uploaded any knowledge yet. Upload documents in the knowledge bases above to get started.
                                                </p>
                                            ) : (
                                                <p className="text-xs text-yellow-600">
                                                    {inspectResults.chunks_with_embeddings === 0 
                                                        ? "Your documents are still being processed. Wait a moment and try again, or check that embeddings are being generated."
                                                        : "Try uploading more relevant documents, rephrasing your query, or using different keywords."
                                                    }
                                                </p>
                                            )}
                                        </div>
                                    )}
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </TenantLayout>
    );
}