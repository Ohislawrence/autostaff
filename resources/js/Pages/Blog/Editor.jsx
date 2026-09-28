import { Link, useForm } from '@inertiajs/react';
import PlatformLayout from '@/Layouts/PlatformLayout';
import RichTextEditor from '@/Components/RichTextEditor';

export default function Editor({ post }) {
    const isEditing = !!post;

    const { data, setData, post: submit, put, processing, errors } = useForm({
        title: post?.title || '',
        slug: post?.slug || '',
        excerpt: post?.excerpt || '',
        body: post?.body || '',
        featured_image: null,
        meta_title: post?.meta_title || '',
        meta_description: post?.meta_description || '',
        og_image: post?.og_image || '',
        status: post?.status || 'draft',
        is_featured: !!post?.is_featured,
    });

    const save = (e) => {
        e.preventDefault();
        if (isEditing) put(`/platform/blog/${post.id}`, { forceFormData: true });
        else submit('/platform/blog', { forceFormData: true });
    };

    const label = 'block text-xs font-bold text-ink-dim uppercase tracking-wide mb-1';
    const field = 'w-full px-3 py-2 rounded-lg border border-ink/15 bg-white text-sm text-ink focus:outline-none focus:border-forest';

    return (
        <PlatformLayout title={isEditing ? 'Edit post' : 'New post'}>
            <div className="max-w-4xl">
                <div className="flex items-center justify-between mb-6">
                    <Link href="/platform/blog" className="text-sm font-semibold text-forest hover:underline">← Back to blog</Link>
                </div>

                {Object.keys(errors).length > 0 && (
                    <div className="mb-4 p-4 bg-wine/10 text-wine rounded-xl border border-wine/40 text-sm">
                        {Object.entries(errors).map(([k, v]) => <p key={k}>{v}</p>)}
                    </div>
                )}

                <form onSubmit={save} className="space-y-5">
                    <div>
                        <label className={label}>Title *</label>
                        <input value={data.title} onChange={(e) => setData('title', e.target.value)} className={field} required />
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <label className={label}>Slug (URL)</label>
                            <input value={data.slug} onChange={(e) => setData('slug', e.target.value)} className={field} placeholder="auto-generated from title" />
                        </div>
                        <div>
                            <label className={label}>Status</label>
                            <select value={data.status} onChange={(e) => setData('status', e.target.value)} className={field}>
                                <option value="draft">Draft</option>
                                <option value="published">Published</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label className={label}>Excerpt (summary)</label>
                        <textarea value={data.excerpt} onChange={(e) => setData('excerpt', e.target.value)} rows={2} className={field} placeholder="Short summary shown on the blog index" />
                    </div>

                    <div>
                        <label className={label}>Content *</label>
                        <RichTextEditor value={data.body} onChange={(html) => setData('body', html)} />
                        {errors.body && <p className="text-xs text-wine mt-1">{errors.body}</p>}
                    </div>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label className={label}>Featured image</label>
                            {post?.featured_image_url && (
                                <img src={post.featured_image_url} alt="Featured" className="mb-2 h-28 w-full object-cover rounded-lg border border-ink/10" />
                            )}
                            <input type="file" accept="image/*" onChange={(e) => setData('featured_image', e.target.files[0])} className="text-sm" />
                            <p className="text-xs text-ink-faint mt-1">Recommended 1200×630px.</p>
                        </div>
                        <div>
                            <label className={label}>OG image URL (optional)</label>
                            <input value={data.og_image} onChange={(e) => setData('og_image', e.target.value)} className={field} placeholder="Defaults to featured image" />
                        </div>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label className={label}>Meta title (SEO)</label>
                            <input value={data.meta_title} onChange={(e) => setData('meta_title', e.target.value)} className={field} placeholder="Defaults to post title" />
                        </div>
                        <div>
                            <label className={label}>Meta description (SEO)</label>
                            <textarea value={data.meta_description} onChange={(e) => setData('meta_description', e.target.value)} rows={2} className={field} placeholder="Defaults to excerpt" />
                        </div>
                    </div>

                    <label className="flex items-center gap-2 text-sm text-ink-dim">
                        <input type="checkbox" checked={data.is_featured} onChange={(e) => setData('is_featured', e.target.checked)} className="rounded" />
                        Feature this post
                    </label>

                    <div className="flex gap-3 pt-2">
                        <button type="submit" disabled={processing} className="rounded-full bg-ink px-6 py-2.5 text-sm font-bold text-bone hover:bg-forest disabled:opacity-50">
                            {processing ? 'Saving…' : isEditing ? 'Save changes' : 'Create post'}
                        </button>
                        <Link href="/platform/blog" className="px-4 py-2.5 text-sm font-semibold text-ink-dim hover:text-ink">Cancel</Link>
                    </div>
                </form>
            </div>
        </PlatformLayout>
    );
}
