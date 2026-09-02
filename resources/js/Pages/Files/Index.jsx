import { Head, Link } from '@inertiajs/react';
import TenantLayout from '@/Layouts/TenantLayout';

export default function FilesIndex({ files, categories, currentCategory, totalUsageFormatted }) {
    return (
        <TenantLayout header="Files">
            <Head title="Files" />

            {/* Header stats */}
            <div className="flex items-center justify-between mb-6">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">Files</h1>
                    <p className="text-sm text-gray-500 mt-1">
                        Total storage: <span className="font-semibold text-gray-700">{totalUsageFormatted}</span>
                        {files.length > 0 && ` • ${files.length} file${files.length !== 1 ? 's' : ''}`}
                    </p>
                </div>
            </div>

            {/* Category filter tabs */}
            {categories.length > 0 && (
                <div className="flex flex-wrap gap-2 mb-6">
                    <Link
                        href="/files"
                        className={`px-3 py-1.5 rounded-lg text-sm font-medium transition-colors ${
                            !currentCategory
                                ? 'bg-primary-600 text-white shadow-sm'
                                : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50'
                        }`}
                    >
                        All
                    </Link>
                    {categories.map((cat) => (
                        <Link
                            key={cat}
                            href={`/files?category=${cat}`}
                            className={`px-3 py-1.5 rounded-lg text-sm font-medium transition-colors capitalize ${
                                currentCategory === cat
                                    ? 'bg-primary-600 text-white shadow-sm'
                                    : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50'
                            }`}
                        >
                            {cat}
                        </Link>
                    ))}
                </div>
            )}

            {/* File list */}
            {files.length === 0 ? (
                <div className="bg-white rounded-xl border border-gray-200 p-12 text-center">
                    <svg className="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1} d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                    <p className="text-gray-500 text-sm">
                        {currentCategory
                            ? `No files in category "${currentCategory}".`
                            : 'No files uploaded yet.'}
                    </p>
                </div>
            ) : (
                <div className="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <table className="w-full">
                        <thead>
                            <tr className="border-b border-gray-100 bg-gray-50/50">
                                <th className="text-left px-4 py-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">File</th>
                                <th className="text-left px-4 py-3 text-xs font-semibold text-gray-400 uppercase tracking-wider hidden sm:table-cell">Category</th>
                                <th className="text-left px-4 py-3 text-xs font-semibold text-gray-400 uppercase tracking-wider hidden md:table-cell">Size</th>
                                <th className="text-left px-4 py-3 text-xs font-semibold text-gray-400 uppercase tracking-wider hidden lg:table-cell">Modified</th>
                                <th className="text-right px-4 py-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-50">
                            {files.map((file) => (
                                <tr key={file.path} className="hover:bg-gray-50/50 transition-colors">
                                    <td className="px-4 py-3">
                                        <div className="flex items-center gap-3">
                                            <FileIcon mimeType={file.mime_type} />
                                            <div className="min-w-0">
                                                <p className="text-sm font-medium text-gray-900 truncate max-w-[200px] sm:max-w-[300px]">
                                                    {file.name}
                                                </p>
                                                <p className="text-xs text-gray-400 truncate max-w-[200px] sm:max-w-[300px]">
                                                    {file.path}
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td className="px-4 py-3 hidden sm:table-cell">
                                        <span className="px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600 capitalize">
                                            {file.category}
                                        </span>
                                    </td>
                                    <td className="px-4 py-3 text-sm text-gray-500 hidden md:table-cell">
                                        {formatFileSize(file.size)}
                                    </td>
                                    <td className="px-4 py-3 text-sm text-gray-400 hidden lg:table-cell">
                                        {formatDate(file.last_modified)}
                                    </td>
                                    <td className="px-4 py-3 text-right">
                                        <a
                                            href={`/files/download?path=${encodeURIComponent(file.path)}`}
                                            className="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium text-primary-600 bg-primary-50 hover:bg-primary-100 transition-colors"
                                        >
                                            <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                            </svg>
                                            Download
                                        </a>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </TenantLayout>
    );
}

function FileIcon({ mimeType }) {
    const isPdf = mimeType?.includes('pdf');
    const isImage = mimeType?.includes('image');
    const isWord = mimeType?.includes('word') || mimeType?.includes('document');
    const isSpreadsheet = mimeType?.includes('spreadsheet') || mimeType?.includes('csv') || mimeType?.includes('excel');

    const colors = isPdf ? 'text-red-500 bg-red-50' :
        isImage ? 'text-purple-500 bg-purple-50' :
        isWord ? 'text-blue-500 bg-blue-50' :
        isSpreadsheet ? 'text-green-500 bg-green-50' :
        'text-gray-500 bg-gray-50';

    const icon = isPdf ? (
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
    ) : isImage ? (
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0022.5 18.75V5.25A2.25 2.25 0 0020.25 3H3.75A2.25 2.25 0 001.5 5.25v13.5A2.25 2.25 0 003.75 21z" />
    ) : (
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
    );

    return (
        <div className={`w-9 h-9 rounded-lg flex items-center justify-center shrink-0 ${colors}`}>
            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                {icon}
            </svg>
        </div>
    );
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 B';
    const units = ['B', 'KB', 'MB', 'GB', 'TB'];
    const pow = Math.floor(Math.log(bytes) / Math.log(1024));
    return (bytes / Math.pow(1024, pow)).toFixed(pow > 0 ? 1 : 0) + ' ' + units[Math.min(pow, units.length - 1)];
}

function formatDate(timestamp) {
    if (!timestamp) return '-';
    const date = new Date(timestamp * 1000);
    return date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}