<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class BlogController extends Controller
{
    /**
     * Admin: list all posts (drafts + published).
     */
    public function index()
    {
        $posts = Post::with('author:id,name')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Post $post) => [
                'id' => $post->id,
                'uuid' => $post->uuid,
                'slug' => $post->slug,
                'title' => $post->title,
                'excerpt' => $post->excerpt,
                'status' => $post->status,
                'is_featured' => $post->is_featured,
                'featured_image' => $post->featured_image,
                'featured_image_url' => $post->featured_image_url,
                'author' => $post->author?->name,
                'published_at' => $post->published_at?->toISOString(),
                'created_at' => $post->created_at->toISOString(),
                'preview_url' => route('blog.show', $post->slug),
            ]);

        return Inertia::render('Blog/Index', ['posts' => $posts]);
    }

    /**
     * Admin: new post editor.
     */
    public function create()
    {
        return Inertia::render('Blog/Editor', ['post' => null]);
    }

    /**
     * Admin: edit post editor.
     */
    public function edit(Post $post)
    {
        return Inertia::render('Blog/Editor', [
            'post' => [
                'id' => $post->id,
                'slug' => $post->slug,
                'title' => $post->title,
                'excerpt' => $post->excerpt,
                'body' => $post->body,
                'featured_image_url' => $post->featured_image_url,
                'meta_title' => $post->meta_title,
                'meta_description' => $post->meta_description,
                'og_image' => $post->og_image,
                'status' => $post->status,
                'is_featured' => $post->is_featured,
            ],
        ]);
    }

    /**
     * Admin: create a post.
     */
    public function store(Request $request)
    {
        $data = $this->validatePost($request);

        if (empty($data['slug'])) {
            $data['slug'] = $this->uniqueSlug($data['title']);
        } else {
            $data['slug'] = Str::slug($data['slug']);
        }

        if ($request->hasFile('featured_image')) {
            $data['featured_image'] = $request->file('featured_image')->store('blog', 'public');
        }

        $data['author_id'] = auth()->id();
        $data['published_at'] = $data['status'] === 'published' ? now() : null;

        Post::create($data);

        return redirect()->route('platform.blog.index')->with('success', 'Blog post created.');
    }

    /**
     * Admin: update a post.
     */
    public function update(Request $request, Post $post)
    {
        $data = $this->validatePost($request, $post);

        if (! empty($data['slug'])) {
            $data['slug'] = Str::slug($data['slug']);
        } else {
            unset($data['slug']); // keep existing slug
        }

        if ($request->hasFile('featured_image')) {
            if ($post->featured_image) {
                Storage::disk('public')->delete($post->featured_image);
            }
            $data['featured_image'] = $request->file('featured_image')->store('blog', 'public');
        }

        if ($data['status'] === 'published' && ! $post->published_at) {
            $data['published_at'] = now();
        } elseif ($data['status'] !== 'published') {
            $data['published_at'] = null;
        }

        $post->update($data);

        return redirect()->route('platform.blog.index')->with('success', 'Blog post updated.');
    }

    /**
     * Admin: delete a post.
     */
    public function destroy(Post $post)
    {
        if ($post->featured_image) {
            Storage::disk('public')->delete($post->featured_image);
        }
        if ($post->og_image && $post->og_image !== $post->featured_image) {
            Storage::disk('public')->delete($post->og_image);
        }

        $post->delete();

        return back()->with('success', 'Blog post deleted.');
    }

    /**
     * Public: list published posts.
     */
    public function publicIndex()
    {
        $posts = Post::with('author:id,name')
            ->published()
            ->orderByDesc('published_at')
            ->get();

        return view('frontpage.blog.index', ['posts' => $posts]);
    }

    /**
     * Public: show a single published post.
     */
    public function show(string $slug)
    {
        $post = Post::with('author:id,name')
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

        return view('frontpage.blog.show', ['post' => $post]);
    }

    protected function validatePost(Request $request, ?Post $post = null): array
    {
        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['required', 'string'],
            'featured_image' => ['nullable', 'image', 'max:5120'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:300'],
            'og_image' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:draft,published'],
            'is_featured' => ['sometimes', 'boolean'],
        ];

        $rules['slug'][] = $post
            ? Rule::unique('posts', 'slug')->ignore($post->id)
            : Rule::unique('posts', 'slug');

        $data = $request->validate($rules);
        $data['is_featured'] = $request->boolean('is_featured');

        return $data;
    }

    protected function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'post';
        $slug = $base;
        $i = 1;

        while (Post::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
