import { usePage, router } from '@inertiajs/react';
import { useState, useRef, useEffect } from 'react';

export default function TenantSwitcher() {
    const { auth } = usePage().props;
    const organizations = auth?.organizations || [];
    const currentOrg = auth?.organization;
    const [open, setOpen] = useState(false);
    const [showCreate, setShowCreate] = useState(false);
    const [name, setName] = useState('');
    const [creating, setCreating] = useState(false);
    const containerRef = useRef(null);

    // Close dropdown when clicking outside
    useEffect(() => {
        function handleClickOutside(e) {
            if (containerRef.current && !containerRef.current.contains(e.target)) {
                setOpen(false);
            }
        }
        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    // Show the switcher for any organization user; it also provides org creation.
    if (!organizations.length) {
        return null;
    }

    const handleSwitch = (orgId) => {
        router.post('/switch-organization', { organization_id: orgId }, {
            preserveState: false,
            onSuccess: () => setOpen(false),
        });
    };

    const handleCreate = () => {
        if (!name.trim()) return;
        setCreating(true);
        router.post('/organizations', { name }, {
            onFinish: () => setCreating(false),
            onSuccess: () => {
                setOpen(false);
                setShowCreate(false);
                setName('');
            },
        });
    };

    return (
        <div ref={containerRef} className="relative">
            <button
                type="button"
                onClick={() => setOpen(!open)}
                className="flex items-center gap-2 px-3 py-1.5 text-sm rounded-lg border border-gray-200 bg-white hover:bg-gray-50 transition-colors"
                title="Switch organization"
            >
                <span className="max-w-[120px] truncate font-medium text-gray-700">
                    {currentOrg?.name || 'Select Org'}
                </span>
                <svg className={`w-4 h-4 text-gray-400 transition-transform ${open ? 'rotate-180' : ''}`} fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            {open && (
                <div className="absolute right-0 mt-2 w-64 bg-white border border-gray-200 rounded-xl shadow-lg z-50 overflow-hidden">
                    <div className="px-3 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wider bg-gray-50 border-b border-gray-100">
                        Switch Organization
                    </div>
                    <div className="py-1 max-h-64 overflow-y-auto">
                        {organizations.map((org) => {
                            const isActive = currentOrg?.id === org.id;
                            return (
                                <button
                                    key={org.id}
                                    type="button"
                                    onClick={() => !isActive && handleSwitch(org.id)}
                                    disabled={isActive}
                                    className={`w-full flex items-center gap-3 px-3 py-2.5 text-sm text-left transition-colors ${
                                        isActive
                                            ? 'bg-primary-50 text-primary-700 cursor-default'
                                            : 'text-gray-700 hover:bg-gray-50'
                                    }`}
                                >
                                    <div className={`w-8 h-8 rounded-lg flex items-center justify-center text-xs font-bold uppercase shrink-0 ${
                                        isActive ? 'bg-primary-100 text-primary-700' : 'bg-gray-100 text-gray-500'
                                    }`}>
                                        {org.logo ? (
                                            <img src={org.logo} alt={org.name} className="w-full h-full rounded-lg object-cover" />
                                        ) : (
                                            org.name.charAt(0)
                                        )}
                                    </div>
                                    <div className="min-w-0">
                                        <div className="truncate font-medium">{org.name}</div>
                                        <div className="truncate text-xs text-gray-400">{org.slug}</div>
                                    </div>
                                    {isActive && (
                                        <svg className="w-4 h-4 text-primary-600 ml-auto shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                            <path fillRule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clipRule="evenodd" />
                                        </svg>
                                    )}
                                </button>
                            );
                        })}
                    </div>

                    <div className="border-t border-gray-100">
                        {showCreate ? (
                            <div className="p-3 space-y-2">
                                <input
                                    autoFocus
                                    type="text"
                                    value={name}
                                    onChange={(e) => setName(e.target.value)}
                                    placeholder="Organization name"
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                                    onKeyDown={(e) => { if (e.key === 'Enter') handleCreate(); }}
                                />
                                <div className="flex gap-2">
                                    <button
                                        type="button"
                                        onClick={handleCreate}
                                        disabled={creating || !name.trim()}
                                        className="flex-1 px-3 py-1.5 bg-primary-600 text-white rounded-lg text-xs font-semibold hover:bg-primary-700 disabled:opacity-50"
                                    >
                                        {creating ? 'Creating...' : 'Create'}
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => { setShowCreate(false); setName(''); }}
                                        className="px-3 py-1.5 border border-gray-300 text-gray-600 rounded-lg text-xs"
                                    >
                                        Cancel
                                    </button>
                                </div>
                            </div>
                        ) : (
                            <button
                                type="button"
                                onClick={() => setShowCreate(true)}
                                className="w-full px-3 py-2.5 text-sm font-medium text-primary-600 hover:bg-gray-50 flex items-center gap-2"
                            >
                                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
                                </svg>
                                Create Organization
                            </button>
                        )}
                    </div>
                </div>
            )}
        </div>
    );
}