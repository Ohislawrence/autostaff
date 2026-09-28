@extends('frontpage.layouts.app')

@section('title', $post->seo_title)
@section('meta_description', $post->seo_description)
@section('canonical', url()->current())
@section('og_type', 'article')
@section('og_url', url()->current())
@section('og_image', $post->og_image_url)

@push('head')
    <meta property="article:published_time" content="{{ $post->published_at?->toIso8601String() }}">
    <meta property="article:modified_time" content="{{ $post->updated_at?->toIso8601String() }}">
    @if ($post->author)
        <meta property="article:author" content="{{ $post->author->name }}">
    @endif

    @php
        $jsonLd = [
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'headline' => $post->seo_title,
            'description' => $post->seo_description,
            'image' => $post->og_image_url,
            'datePublished' => $post->published_at?->toIso8601String(),
            'dateModified' => $post->updated_at?->toIso8601String(),
            'author' => ['@type' => 'Person', 'name' => $post->author?->name ?? 'Nomdal'],
            'publisher' => ['@type' => 'Organization', 'name' => 'Nomdal', 'logo' => ['@type' => 'ImageObject', 'url' => url('/images/og-image.png')]],
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => url()->current()],
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>

    <style>
        .rich-body { color: #1a1a1a; }
        .rich-body h2 { font-family: 'Archivo', sans-serif; font-weight: 900; font-size: 1.75rem; margin-top: 2.5rem; margin-bottom: 1rem; line-height: 1.2; }
        .rich-body h3 { font-family: 'Archivo', sans-serif; font-weight: 800; font-size: 1.35rem; margin-top: 2rem; margin-bottom: 0.75rem; line-height: 1.3; }
        .rich-body p { margin-bottom: 1.25rem; }
        .rich-body ul { list-style: disc; padding-left: 1.5rem; margin-bottom: 1.25rem; }
        .rich-body ol { list-style: decimal; padding-left: 1.5rem; margin-bottom: 1.25rem; }
        .rich-body li { margin-bottom: 0.5rem; }
        .rich-body a { color: #2a5b40; text-decoration: underline; }
        .rich-body blockquote { border-left: 3px solid #3ab91a; padding-left: 1rem; margin: 1.5rem 0; color: #55504a; font-style: italic; }
        .rich-body pre { background: #1a1a1a; color: #fbf7ef; padding: 1rem; border-radius: 0.75rem; overflow-x: auto; margin: 1.5rem 0; font-family: 'JetBrains Mono', monospace; font-size: 0.85rem; }
        .rich-body code { background: rgba(0,0,0,0.06); padding: 2px 5px; border-radius: 4px; font-family: 'JetBrains Mono', monospace; font-size: 0.85em; }
        .rich-body pre code { background: transparent; padding: 0; }
        .rich-body img { border-radius: 1rem; margin: 1.5rem 0; max-width: 100%; }
        .rich-body strong { font-weight: 700; }
    </style>
@endpush

@section('content')
    <article class="mx-auto max-w-3xl px-6 py-16 lg:py-24">
        <a href="{{ route('blog.index') }}" class="reveal inline-flex items-center gap-2 text-sm font-bold text-forest transition hover:text-ink">← Back to blog</a>

        <header class="reveal mt-8">
            <div class="flex items-center gap-3 text-xs font-mono uppercase tracking-widest text-ink-faint">
                @if ($post->author)<span>{{ $post->author->name }}</span><span>·</span>@endif
                <time datetime="{{ $post->published_at?->toDateString() }}">{{ $post->published_at?->format('F j, Y') }}</time>
            </div>
            <h1 class="mt-4 font-display text-4xl font-black leading-tight tracking-tight text-ink sm:text-5xl">{{ $post->title }}</h1>
        </header>

        @if ($post->featured_image_url)
            <figure class="reveal mt-8 overflow-hidden rounded-3xl border border-ink/10">
                <img src="{{ $post->featured_image_url }}" alt="{{ $post->title }}" class="w-full object-cover" />
            </figure>
        @endif

        <div class="reveal mt-10">
            <div class="rich-body text-lg leading-relaxed">{!! $post->body !!}</div>
        </div>
    </article>
@endsection
