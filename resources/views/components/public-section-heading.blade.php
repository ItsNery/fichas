@props(['title', 'description' => null, 'eyebrow' => null, 'center' => false])

<style>
    .public-section-heading {
        margin: 3rem 0 1.75rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid #eaeaea;
    }

    .public-section-heading--centered {
        text-align: center;
    }

    .public-section-heading__eyebrow {
        display: block;
        margin-bottom: .45rem;
        color: #872341;
        font-size: .75rem;
        font-weight: 700;
        letter-spacing: .1em;
        text-transform: uppercase;
    }

    .public-section-heading__title {
        margin: 0 0 .5rem;
        color: #262626;
        font-size: clamp(1.5rem, 2.5vw, 2.1rem);
        font-weight: 700;
    }

    .public-section-heading__description {
        max-width: 48rem;
        margin: 0;
        color: #687078;
        font-size: 1rem;
        line-height: 1.6;
    }

    .public-section-heading--centered .public-section-heading__description {
        margin-right: auto;
        margin-left: auto;
    }

    @media (max-width: 767.98px) {
        .public-section-heading { margin: 2rem 0 1.25rem; }
    }
</style>

<header {{ $attributes->class(['public-section-heading', 'public-section-heading--centered' => $center]) }}>
    @if ($eyebrow)
        <span class="public-section-heading__eyebrow">{{ $eyebrow }}</span>
    @endif
    <h2 class="public-section-heading__title">{{ $title }}</h2>
    @if ($description)
        <p class="public-section-heading__description">{{ $description }}</p>
    @endif
</header>
