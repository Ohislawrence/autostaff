import { Head } from '@inertiajs/react';
import TenantLayout from '@/Layouts/TenantLayout';

export default function Placeholder({ title }) {
    return (
        <TenantLayout header={title || 'Coming Soon'}>
            <Head title={title || 'Coming Soon'} />
            <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
                <div className="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg className="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M11.42 15.17l-6.48 3.5a.75.75 0 01-1.12-.69v-9.93a.75.75 0 01.37-.65l6.49-3.5a.75.75 0 01.74 0l6.48 3.5a.75.75 0 01.37.65v9.93a.75.75 0 01-1.12.69l-6.48-3.5" />
                    </svg>
                </div>
                <h2 className="text-xl font-semibold text-gray-900 mb-2">{title || 'Coming Soon'}</h2>
                <p className="text-gray-500 max-w-md mx-auto">
                    This section is being built and will be available soon. We're working hard to deliver the best AI employee experience.
                </p>
                <div className="mt-6 inline-flex items-center gap-2 px-4 py-2 bg-primary-50 text-primary-700 rounded-lg text-sm font-medium">
                    <svg className="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                    </svg>
                    In Development
                </div>
            </div>
        </TenantLayout>
    );
}