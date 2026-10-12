@props(['title', 'description', 'eyebrow' => null])

<style>
    .public-section-header {
        padding: 2.5rem 0 1.75rem;
        margin-bottom: 2rem;
        border-bottom: 1px solid #eaeaea;
    }

    .public-section-header__eyebrow {
        display: block;
        margin-bottom: .45rem;
        color: #872341;
        font-size: .75rem;
        font-weight: 700;
        letter-spacing: .1em;
        text-transform: uppercase;
    }

    .public-section-header__title {
        margin: 0 0 .6rem;
        color: #262626;
        font-size: clamp(1.9rem, 3vw, 2.75rem);
        font-weight: 700;
    }

    .public-section-header__description {
        max-width: 48rem;
        margin: 0;
        color: #687078;
        font-size: 1.05rem;
        line-height: 1.6;
    }

    @media (max-width: 767.98px) {
        .public-section-header { padding: 1.75rem 0 1.25rem; margin-bottom: 1.5rem; }
    }
</style>

<header {{ $attributes->class('public-section-header') }}>
    @if ($eyebrow)
        <span class="public-section-header__eyebrow">{{ $eyebrow }}</span>
    @endif
    <h1 class="public-section-header__title">{{ $title }}</h1>
    <p class="public-section-header__description">{{ $description }}</p>
</header>
