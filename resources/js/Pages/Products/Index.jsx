import { Head, usePage, useForm } from '@inertiajs/react';
import TenantLayout from '@/Layouts/TenantLayout';
import { useState } from 'react';

export default function Index({ products, categories, filters }) {
    const { flash, auth } = usePage().props;
    const [showForm, setShowForm] = useState(false);
    const { data, setData, post, processing, errors } = useForm({
        name: '', description: '', sku: '', price: '', sale_price: '',
        currency: auth.organization?.currency || 'NGN', category: '', quantity: 0,
    });

    const handleCreate = (e) => {
        e.preventDefault();
        post('/products', { onSuccess: () => { setShowForm(false); } });
    };

    return (
        <TenantLayout header="Products">
            <Head title="Products" />
            {flash?.success && <div className="mb-4 p-4 bg-green-50 text-green-700 rounded-lg text-sm">{flash.success}</div>}

            <div className="flex items-center justify-between mb-4">
                <form className="flex gap-2">
                    <input type="text" name="search" defaultValue={filters.search} placeholder="Search products..."
                        className="px-3 py-2 border border-gray-300 rounded-lg text-sm" />
                    <select name="category" defaultValue={filters.category} className="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        <option value="">All Categories</option>
                        {categories.map(c => <option key={c} value={c}>{c}</option>)}
                    </select>
                    <button type="submit" className="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm">Filter</button>
                </form>
                <button onClick={() => setShowForm(!showForm)} className="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm font-medium">
                    + Add Product
                </button>
            </div>

            {showForm && (
                <form onSubmit={handleCreate} className="mb-4 bg-white rounded-xl shadow-sm border border-gray-200 p-4 grid grid-cols-2 md:grid-cols-4 gap-3">
                    <input type="text" value={data.name} onChange={e => setData('name', e.target.value)}
                        className="px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="Product name *" required />
                    <input type="text" value={data.sku} onChange={e => setData('sku', e.target.value)}
                        className="px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="SKU" />
                    <input type="number" value={data.price} onChange={e => setData('price', e.target.value)}
                        className="px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="Price *" step="0.01" required />
                    <input type="number" value={data.quantity} onChange={e => setData('quantity', e.target.value)}
                        className="px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="Stock Qty" />
                    <input type="text" value={data.category} onChange={e => setData('category', e.target.value)}
                        className="px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="Category" />
                    <input type="text" value={data.description} onChange={e => setData('description', e.target.value)}
                        className="px-3 py-2 border border-gray-300 rounded-lg text-sm col-span-2" placeholder="Description" />
                    <div className="flex gap-2 col-span-2 justify-end">
                        <button type="button" onClick={() => setShowForm(false)} className="px-3 py-2 border border-gray-300 rounded-lg text-sm">Cancel</button>
                        <button type="submit" disabled={processing} className="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm">{processing ? 'Saving...' : 'Save'}</button>
                    </div>
                </form>
            )}

            <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <table className="w-full text-sm">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="text-left px-4 py-3 font-medium text-gray-500">Product</th>
                            <th className="text-left px-4 py-3 font-medium text-gray-500">Category</th>
                            <th className="text-left px-4 py-3 font-medium text-gray-500">Price</th>
                            <th className="text-left px-4 py-3 font-medium text-gray-500">Stock</th>
                            <th className="text-left px-4 py-3 font-medium text-gray-500">Status</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-100">
                        {products.data?.map(product => (
                            <tr key={product.id} className="hover:bg-gray-50">
                                <td className="px-4 py-3">
                                    <p className="font-medium text-gray-900">{product.name}</p>
                                    {product.sku && <p className="text-xs text-gray-400">SKU: {product.sku}</p>}
                                </td>
                                <td className="px-4 py-3 text-gray-500 text-xs">{product.category || '—'}</td>
                                <td className="px-4 py-3 font-medium text-gray-900">
                                    {product.sale_price ? (
                                        <span><span className="text-gray-400 line-through mr-2">{product.currency} {product.price}</span>{product.currency} {product.sale_price}</span>
                                    ) : (
                                        <span>{product.currency} {product.price}</span>
                                    )}
                                </td>
                                <td className="px-4 py-3">
                                    <span className={`text-sm ${(product.inventory?.[0]?.quantity || 0) > 0 ? 'text-green-600' : 'text-red-600'}`}>
                                        {product.inventory?.[0]?.quantity || 0}
                                    </span>
                                </td>
                                <td className="px-4 py-3">
                                    <span className={`px-2 py-0.5 rounded-full text-xs font-medium ${product.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'}`}>
                                        {product.is_active ? 'Active' : 'Inactive'}
                                    </span>
                                </td>
                            </tr>
                        ))}
                        {products.data?.length === 0 && (
                            <tr><td colSpan={5} className="px-4 py-8 text-center text-gray-400">No products yet. Add your first product.</td></tr>
                        )}
                    </tbody>
                </table>
            </div>
        </TenantLayout>
    );
}