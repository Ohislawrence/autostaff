import { Head, usePage, useForm, router } from '@inertiajs/react';
import TenantLayout from '@/Layouts/TenantLayout';
import { useState, useEffect } from 'react';

// Available condition fields per trigger type
const triggerFieldMap = {
    new_lead: [
        { value: 'customer_name', label: 'Customer Name' },
        { value: 'lead_score', label: 'Lead Score' },
        { value: 'lead_stage', label: 'Lead Stage' },
        { value: 'product_interest', label: 'Product Interest' },
        { value: 'estimated_value', label: 'Estimated Value' },
        { value: 'source', label: 'Source' },
    ],
    new_message: [
        { value: 'customer_name', label: 'Customer Name' },
        { value: 'customer_email', label: 'Customer Email' },
        { value: 'customer_phone', label: 'Customer Phone' },
        { value: 'message_content', label: 'Message Content' },
        { value: 'channel', label: 'Channel' },
    ],
    lead_stage_changed: [
        { value: 'customer_name', label: 'Customer Name' },
        { value: 'lead_score', label: 'Lead Score' },
        { value: 'lead_stage', label: 'New Stage' },
        { value: 'from_stage', label: 'From Stage' },
        { value: 'to_stage', label: 'To Stage' },
        { value: 'product_interest', label: 'Product Interest' },
        { value: 'estimated_value', label: 'Estimated Value' },
    ],
    order_created: [
        { value: 'customer_name', label: 'Customer Name' },
        { value: 'order_number', label: 'Order Number' },
        { value: 'total', label: 'Order Total' },
        { value: 'status', label: 'Order Status' },
        { value: 'items_count', label: 'Items Count' },
    ],
};

const conditionOperators = [
    { value: 'equals', label: 'Equals' },
    { value: 'not_equals', label: 'Not Equals' },
    { value: 'greater_than', label: 'Greater Than' },
    { value: 'less_than', label: 'Less Than' },
    { value: 'contains', label: 'Contains' },
    { value: 'exists', label: 'Exists' },
    { value: 'not_exists', label: 'Does Not Exist' },
];

export default function Index({ automations, triggers, actions }) {
    const { flash } = usePage().props;
    const [showModal, setShowModal] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '', description: '', trigger_type: 'new_lead',
        conditions: [], actions: [],
        max_executions_per_day: null,
    });

    const resetForm = () => {
        reset();
        setShowModal(false);
    };

    const handleCreate = (e) => {
        e.preventDefault();
        const payload = {
            ...data,
            actions: data.actions.length > 0 ? data.actions : [{ type: 'notify_user', config: { title: 'Automation triggered', body: '{{customer_name}}' } }],
        };
        post('/automations', { data: payload, onSuccess: () => resetForm() });
    };

    const addAction = (type) => {
        setData('actions', [...(data.actions || []), { type, config: {} }]);
    };

    const removeAction = (index) => {
        const updated = [...(data.actions || [])];
        updated.splice(index, 1);
        setData('actions', updated);
    };

    const addCondition = () => {
        const fields = triggerFieldMap[data.trigger_type] || [];
        const firstField = fields.length > 0 ? fields[0].value : 'customer_name';
        setData('conditions', [...(data.conditions || []), { field: firstField, operator: 'equals', value: '', logic: 'and' }]);
    };

    const availableFields = triggerFieldMap[data.trigger_type] || [];

    return (
        <TenantLayout header="Automations">
            <Head title="Automations" />
            {flash?.success && <div className="mb-4 p-4 bg-green-50 text-green-700 rounded-lg text-sm">{flash.success}</div>}

            <div className="flex items-center justify-between mb-6">
                <p className="text-sm text-gray-500">Create automated workflows that run when specific events happen. Trigger → Conditions → Actions.</p>
                <button onClick={() => setShowModal(true)} className="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm font-medium hover:bg-primary-700 transition">
                    + New Automation
                </button>
            </div>

            {/* Create Modal */}
            {showModal && (
                <div className="fixed inset-0 z-50 overflow-y-auto">
                    <div className="flex min-h-full items-center justify-center p-4">
                        {/* Backdrop */}
                        <div className="fixed inset-0 bg-black/40 backdrop-blur-sm" onClick={resetForm} />

                        {/* Modal Panel */}
                        <div className="relative bg-white rounded-2xl shadow-2xl border border-gray-200 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                            <div className="sticky top-0 bg-white border-b border-gray-100 px-6 py-4 flex items-center justify-between rounded-t-2xl z-10">
                                <h2 className="text-lg font-semibold text-gray-900">Create Automation</h2>
                                <button onClick={resetForm} className="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition">
                                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" /></svg>
                                </button>
                            </div>

                            <form onSubmit={handleCreate} className="p-6 space-y-5">
                                {/* Name & Trigger */}
                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <label className="block text-xs font-medium text-gray-600 mb-1">Automation Name *</label>
                                        <input type="text" value={data.name} onChange={e => setData('name', e.target.value)}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none" placeholder="e.g., High-value lead alert" required />
                                    </div>
                                    <div>
                                        <label className="block text-xs font-medium text-gray-600 mb-1">Trigger Event *</label>
                                        <select value={data.trigger_type} onChange={e => setData('trigger_type', e.target.value)}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none">
                                            {triggers.map(t => <option key={t.id} value={t.id}>{t.icon} {t.name}</option>)}
                                        </select>
                                    </div>
                                </div>

                                {/* Description */}
                                <div>
                                    <label className="block text-xs font-medium text-gray-600 mb-1">Description (optional)</label>
                                    <textarea value={data.description} onChange={e => setData('description', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none resize-none" rows={2} placeholder="What does this automation do?" />
                                </div>

                                {/* Conditions */}
                                <div className="bg-gray-50 rounded-xl p-4">
                                    <div className="flex items-center justify-between mb-3">
                                        <span className="text-sm font-semibold text-gray-700">⚙ Conditions</span>
                                        <button type="button" onClick={addCondition}
                                            className="text-xs px-2.5 py-1 bg-white border border-gray-300 rounded-lg text-primary-600 hover:border-primary-400 hover:bg-primary-50 transition font-medium">
                                            + Add Condition
                                        </button>
                                    </div>

                                    {(data.conditions || []).length === 0 && (
                                        <p className="text-xs text-gray-400 text-center py-3">No conditions — automation always fires when triggered.</p>
                                    )}

                                    {(data.conditions || []).map((cond, idx) => (
                                        <div key={idx} className="flex items-center gap-2 mb-2 bg-white rounded-lg p-2.5 border border-gray-200">
                                            <select value={cond.field} onChange={e => { const c = [...data.conditions]; c[idx].field = e.target.value; setData('conditions', c); }}
                                                className="px-2 py-1.5 border border-gray-300 rounded text-xs min-w-[140px]">
                                                {availableFields.map(f => <option key={f.value} value={f.value}>{f.label}</option>)}
                                            </select>

                                            <select value={cond.operator} onChange={e => { const c = [...data.conditions]; c[idx].operator = e.target.value; setData('conditions', c); }}
                                                className="px-2 py-1.5 border border-gray-300 rounded text-xs min-w-[130px]">
                                                {conditionOperators.map(op => <option key={op.value} value={op.value}>{op.label}</option>)}
                                            </select>

                                            {!['exists', 'not_exists'].includes(cond.operator) && (
                                                <input type="text" value={cond.value} onChange={e => { const c = [...data.conditions]; c[idx].value = e.target.value; setData('conditions', c); }}
                                                    className="px-2 py-1.5 border border-gray-300 rounded text-xs flex-1" placeholder="Value" />
                                            )}

                                            <select value={cond.logic} onChange={e => { const c = [...data.conditions]; c[idx].logic = e.target.value; setData('conditions', c); }}
                                                className="px-1.5 py-1.5 border border-gray-200 rounded text-xs w-[60px] text-gray-400">
                                                <option value="and">AND</option>
                                                <option value="or">OR</option>
                                            </select>

                                            <button type="button" onClick={() => { const c = [...data.conditions]; c.splice(idx, 1); setData('conditions', c); }}
                                                className="p-1 text-red-400 hover:text-red-600 hover:bg-red-50 rounded transition ml-auto">
                                                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        </div>
                                    ))}
                                </div>

                                {/* Actions */}
                                <div className="bg-gray-50 rounded-xl p-4">
                                    <div className="flex items-center justify-between mb-3">
                                        <span className="text-sm font-semibold text-gray-700">⚡ Actions</span>
                                        <select onChange={(e) => { if (e.target.value) { addAction(e.target.value); e.target.value = ''; } }}
                                            className="px-2.5 py-1 border border-gray-300 rounded-lg text-xs bg-white text-primary-600 font-medium">
                                            <option value="">+ Add Action</option>
                                            {actions.map(a => <option key={a.id} value={a.id}>{a.name}</option>)}
                                        </select>
                                    </div>

                                    {(data.actions || []).length === 0 && (
                                        <p className="text-xs text-gray-400 text-center py-3">No actions added. A notification will be sent by default.</p>
                                    )}

                                    <div className="space-y-2">
                                        {(data.actions || []).map((act, idx) => (
                                            <div key={idx} className="flex items-center gap-2 bg-white rounded-lg p-2.5 border border-gray-200">
                                                <span className="px-2.5 py-1 bg-primary-50 text-primary-700 rounded-lg text-xs font-semibold flex-shrink-0">
                                                    {actions.find(a => a.id === act.type)?.name || act.type}
                                                </span>
                                                <span className="text-xs text-gray-400 truncate flex-1">
                                                    {actions.find(a => a.id === act.type)?.description || ''}
                                                </span>
                                                <button type="button" onClick={() => removeAction(idx)}
                                                    className="p-1 text-red-400 hover:text-red-600 hover:bg-red-50 rounded transition ml-auto">
                                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                </button>
                                            </div>
                                        ))}
                                    </div>
                                </div>

                                {/* Rate Limit */}
                                <div>
                                    <label className="block text-xs font-medium text-gray-600 mb-1">Max Executions Per Day (optional)</label>
                                    <input type="number" value={data.max_executions_per_day || ''} onChange={e => setData('max_executions_per_day', e.target.value ? parseInt(e.target.value) : null)}
                                        className="w-48 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none" placeholder="Unlimited" min="1" />
                                </div>

                                {/* Footer */}
                                <div className="flex gap-3 pt-3 border-t border-gray-100">
                                    <button type="submit" disabled={processing}
                                        className="px-5 py-2.5 bg-primary-600 text-white rounded-lg text-sm font-semibold hover:bg-primary-700 disabled:opacity-50 transition">
                                        {processing ? 'Creating...' : 'Create Automation'}
                                    </button>
                                    <button type="button" onClick={resetForm}
                                        className="px-5 py-2.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                                        Cancel
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            )}

            {/* Existing Automations */}
            <div className="space-y-3">
                {automations.map(auto => (
                    <div key={auto.id} className="bg-white rounded-xl shadow-sm border border-gray-200 p-5 hover:border-gray-300 transition">
                        <div className="flex items-start justify-between">
                            <div className="flex-1 min-w-0">
                                <div className="flex items-center gap-2 mb-1">
                                    <h3 className="font-semibold text-gray-900">{auto.name}</h3>
                                    <span className={`px-2 py-0.5 rounded-full text-xs font-medium ${auto.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'}`}>
                                        {auto.is_active ? 'Active' : 'Inactive'}
                                    </span>
                                </div>
                                {auto.description && <p className="text-xs text-gray-500 mb-1.5">{auto.description}</p>}
                                <div className="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-400">
                                    <span>Trigger: <span className="font-medium text-gray-600">{triggers.find(t => t.id === auto.trigger_type)?.icon} {triggers.find(t => t.id === auto.trigger_type)?.name || auto.trigger_type}</span></span>
                                    {(auto.conditions || []).length > 0 && <span>Conditions: <span className="font-medium">{auto.conditions.length}</span></span>}
                                    {(auto.actions || []).length > 0 && <span>Actions: <span className="font-medium">{auto.actions.length}</span></span>}
                                    <span>Runs: <span className="font-medium">{auto.runs_count || 0}</span></span>
                                    {auto.last_executed_at && <span>Last: <span className="font-medium">{new Date(auto.last_executed_at).toLocaleDateString()}</span></span>}
                                </div>
                            </div>
                            <div className="flex items-center gap-2 flex-shrink-0 ml-4">
                                <button onClick={() => post(`/automations/${auto.id}/toggle`)}
                                    className="text-xs px-3 py-1.5 bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200 transition font-medium">
                                    {auto.is_active ? 'Pause' : 'Activate'}
                                </button>
                                <button onClick={() => { if (confirm('Delete this automation?')) router.delete(`/automations/${auto.id}`, { onSuccess: () => location.reload() }); }}
                                    className="text-xs px-3 py-1.5 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition font-medium">
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>
                ))}
                {automations.length === 0 && (
                    <div className="text-center py-16">
                        <div className="text-4xl mb-3">⚡</div>
                        <p className="text-sm text-gray-400 mb-2">No automations yet</p>
                        <p className="text-xs text-gray-300">Create one to start automating your workflows.</p>
                    </div>
                )}
            </div>
        </TenantLayout>
    );
}