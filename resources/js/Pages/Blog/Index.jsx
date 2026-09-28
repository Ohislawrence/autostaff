import { Link, router, usePage } from '@inertiajs/react';
import PlatformLayout from '@/Layouts/PlatformLayout';

export default function Index({ posts = [] }) {
    const { flash } = usePage().props;

    const destroy = (post) => {
        if (window.confirm(`Delete "${post.title}"?`)) {
            router.delete(`/platform/blog/${post.id}`, { preserveScroll: true });
        }
    };

    return (
        <PlatformLayout title="Blog">
            <div className="flex items-center justify-between mb-6">
                <div>
                    <h1 className="font-display text-2xl font-black text-ink">Blog</h1>
                    <p className="text-sm text-ink-dim">Publish articles to the public blog with SEO &amp; Open Graph metadata.</p>
                </div>
                <Link href="/platform/blog/create" className="rounded-full bg-ink px-5 py-2 text-sm font-bold text-bone hover:bg-forest">+ New post</Link>
            </div>

            {flash?.success && <div className="mb-4 p-4 bg-lime/10 text-forest rounded-xl border border-lime/40 text-sm">{flash.success}</div>}
            {flash?.error && <div className="mb-4 p-4 bg-wine/10 text-wine rounded-xl border border-wine/40 text-sm">{flash.error}</div>}

            {posts.length === 0 ? (
                <div className="bg-white/70 rounded-2xl border border-ink/10 p-10 text-center text-ink-dim">No posts yet. Click “New post” to write your first article.</div>
            ) : (
                <div className="bg-white/70 rounded-2xl border border-ink/10 overflow-hidden">
                    <table className="w-full text-sm">
                        <thead className="text-left text-ink-faint border-b border-ink/10">
                            <tr>
                                <th className="px-4 py-3 font-semibold">Title</th>
                                <th className="px-4 py-3 font-semibold">Status</th>
                                <th className="px-4 py-3 font-semibold">Author</th>
                                <th className="px-4 py-3 font-semibold">Updated</th>
                                <th className="px-4 py-3 text-right font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {posts.map((post) => (
                                <tr key={post.id} className="border-b border-ink/5 hover:bg-white">
                                    <td className="px-4 py-3">
                                        <div className="flex items-center gap-3">
                                            {post.featured_image_url ? (
                                                <img src={post.featured_image_url} alt="" className="h-10 w-14 object-cover rounded-md border border-ink/10" />
                                            ) : (
                                                <div className="h-10 w-14 rounded-md bg-ltbeige" />
                                            )}
                                            <div>
                                                <p className="font-semibold text-ink">{post.title}</p>
                                                {post.is_featured && <span className="text-xs text-forest font-bold">★ Featured</span>}
                                            </div>
                                        </div>
                                    </td>
                                    <td className="px-4 py-3">
                                        <span className={`px-2 py-0.5 rounded-full text-xs font-bold ${post.status === 'published' ? 'bg-lime/20 text-forest' : 'bg-gray-100 text-ink-dim'}`}>
                                            {post.status}
                                        </span>
                                    </td>
                                    <td className="px-4 py-3 text-ink-dim">{post.author || '—'}</td>
                                    <td className="px-4 py-3 text-ink-dim">{new Date(post.created_at).toLocaleDateString()}</td>
                                    <td className="px-4 py-3 text-right whitespace-nowrap">
                                        <Link href={`/platform/blog/${post.id}/edit`} className="text-forest font-semibold hover:underline mr-3">Edit</Link>
                                        {post.status === 'published' && <a href={post.preview_url} target="_blank" rel="noreferrer" className="text-blue-600 hover:underline mr-3">View</a>}
                                        <button onClick={() => destroy(post)} className="text-wine font-semibold hover:underline">Delete</button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </PlatformLayout>
    );
}
