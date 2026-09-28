@extends('frontpage.layouts.app')

@section('title', 'Blog — Nomdal')
@section('meta_description', 'Insights, guides, and product updates from the Nomdal team on AI employees, automation, and running your business on autopilot.')

@section('content')
    <section class="mx-auto max-w-7xl px-6 py-24 lg:py-32">
        <div class="reveal text-center">
            <p class="font-mono text-xs uppercase tracking-widest text-forest">Blog</p>
            <h1 class="mt-4 font-display text-4xl font-black tracking-tight text-ink sm:text-5xl">The Nomdal Journal</h1>
            <p class="mx-auto mt-4 max-w-xl text-ink-dim">Insights, guides, and product updates on AI employees and running your business on autopilot.</p>
        </div>

        @if ($posts->isEmpty())
            <p class="reveal mt-16 text-center text-ink-dim">No posts yet — check back soon.</p>
        @else
            <div class="mt-16 grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($posts as $post)
                    <a href="{{ route('blog.show', $post->slug) }}" class="reveal group flex flex-col overflow-hidden rounded-3xl border border-ink/10 bg-white/40 transition duration-300 hover:-translate-y-1 hover:shadow-xl">
                        @if ($post->featured_image_url)
                            <div class="aspect-[16/9] overflow-hidden bg-ltbeige">
                                <img src="{{ $post->featured_image_url }}" alt="{{ $post->title }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-105" loading="lazy" />
                            </div>
                        @endif
                        <div class="flex flex-1 flex-col p-6">
                            <time datetime="{{ $post->published_at?->toDateString() }}" class="text-xs font-mono uppercase tracking-widest text-ink-faint">{{ $post->published_at?->format('M j, Y') }}</time>
                            <h2 class="mt-3 font-display text-xl font-black leading-tight text-ink transition group-hover:text-forest">{{ $post->title }}</h2>
                            @if ($post->excerpt)
                                <p class="mt-2 text-sm leading-relaxed text-ink-dim">{{ $post->excerpt }}</p>
                            @endif
                            <span class="mt-4 inline-flex items-center gap-1 text-sm font-bold text-forest">Read more <span aria-hidden="true">→</span></span>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </section>
@endsection
