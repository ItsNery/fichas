@extends('layouts.plantilla')

@section('title', 'Perfiles Regionales')
@section('meta-description', 'Consulta los perfiles estadísticos del estado de Puebla, sus macrorregiones y microrregiones.')

@section('css')
<style>
    #perfiles-regionales {
        --regional-burgundy: #872341;
        --regional-gold: #b88b5b;
        --regional-green: #0d312d;
        --regional-ink: #262626;
        --regional-muted: #687078;
        --regional-line: #eaeaea;
    }

    #perfiles-regionales .regional-state-card {
        align-items: center;
        background: var(--regional-green);
        color: #fff;
        display: flex;
        gap: 1.5rem;
        justify-content: space-between;
        padding: 1.5rem;
    }

    #perfiles-regionales .regional-state-card__eyebrow,
    #perfiles-regionales .regional-section-label {
        color: var(--regional-gold);
        font-size: .75rem;
        font-weight: 700;
        letter-spacing: .1em;
        text-transform: uppercase;
    }

    #perfiles-regionales .regional-state-card h2 { font-size: clamp(1.45rem, 2.5vw, 2rem); margin: .35rem 0; }
    #perfiles-regionales .regional-state-card p { margin: 0; max-width: 42rem; opacity: .86; }
    #perfiles-regionales .regional-state-card__link { border: 1px solid #fff; color: #fff; font-weight: 700; text-decoration: none; white-space: nowrap; }
    #perfiles-regionales .regional-state-card__link:hover { background: #fff; color: var(--regional-green); }

    #perfiles-regionales .regional-section-header { margin: 3rem 0 1.25rem; }
    #perfiles-regionales .regional-section-header h2 { color: var(--regional-ink); font-size: clamp(1.4rem, 2.2vw, 1.8rem); margin: .35rem 0; }
    #perfiles-regionales .regional-section-header p { color: var(--regional-muted); margin: 0; max-width: 46rem; }

    #perfiles-regionales .regional-filter { border-bottom: 1px solid var(--regional-line); display: flex; flex-wrap: wrap; gap: .5rem; margin-bottom: 1.5rem; padding-bottom: 1.25rem; }
    #perfiles-regionales .regional-filter__button { background: #fff; border: 1px solid var(--regional-line); color: var(--regional-ink); font-size: .9rem; font-weight: 700; padding: .55rem .85rem; }
    #perfiles-regionales .regional-filter__button:hover,
    #perfiles-regionales .regional-filter__button[aria-pressed="true"] { background: var(--regional-burgundy); border-color: var(--regional-burgundy); color: #fff; }

    #perfiles-regionales .regional-card { border: 1px solid var(--regional-line); height: 100%; padding: 1.25rem; }
    #perfiles-regionales .regional-card__number { color: var(--regional-burgundy); font-size: .8125rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; }
    #perfiles-regionales .regional-card h3 { color: var(--regional-ink); font-size: 1.15rem; margin: .45rem 0; }
    #perfiles-regionales .regional-card p { color: var(--regional-muted); font-size: .9375rem; margin: 0 0 1.25rem; }
    #perfiles-regionales .regional-card__link { color: var(--regional-burgundy); font-size: .9375rem; font-weight: 700; text-decoration: none; }
    #perfiles-regionales .regional-card__link:hover { text-decoration: underline; }
    #perfiles-regionales .regional-card[hidden] { display: none !important; }

    @media (max-width: 767.98px) {
        #perfiles-regionales .regional-state-card { align-items: flex-start; flex-direction: column; }
        #perfiles-regionales .regional-filter { flex-wrap: nowrap; margin-right: -1rem; overflow-x: auto; padding-right: 1rem; }
        #perfiles-regionales .regional-filter__button { white-space: nowrap; }
    }
</style>
@endsection

@section('content')
<main id="perfiles-regionales" class="container my-5">
    <x-public-section-header
        title="Perfiles regionales"
        description="Consulta indicadores agregados del estado de Puebla, sus macrorregiones y microrregiones."
        eyebrow="Explora el territorio" />

    <section class="regional-state-card" aria-labelledby="perfil-estatal-title">
        <div>
            <span class="regional-state-card__eyebrow">Visión general</span>
            <h2 id="perfil-estatal-title">Perfil estatal</h2>
            <p>Conoce el panorama de Puebla a partir de indicadores estadísticos agregados de sus municipios.</p>
        </div>
        <a href="{{ route('regiones.estatal.perfil') }}" class="regional-state-card__link btn px-3 py-2">
            Ver perfil estatal <i class="fas fa-arrow-right ms-1" aria-hidden="true"></i>
        </a>
    </section>

    <section aria-labelledby="macrorregiones-title">
        <header class="regional-section-header">
            <span class="regional-section-label">Nivel territorial</span>
            <h2 id="macrorregiones-title">Macrorregiones</h2>
            <p>Explora los perfiles que integran las principales regiones del estado.</p>
        </header>

        <div class="row g-3">
            @forelse ($macrorregiones as $macrorregion)
                <div class="col-12 col-md-6 col-lg-4">
                    <article class="regional-card">
                        <span class="regional-card__number">Macrorregión {{ $macrorregion->id }}</span>
                        <h3>{{ $macrorregion->nombre }}</h3>
                        <p>{{ $macrorregion->microrregiones_count }} {{ Str::plural('microrregión', $macrorregion->microrregiones_count) }}</p>
                        @if ($macrorregion->slug)
                            <a href="{{ route('regiones.macro.perfil', $macrorregion) }}" class="regional-card__link">
                                Ver perfil <i class="fas fa-arrow-right ms-1" aria-hidden="true"></i>
                            </a>
                        @else
                            <span class="text-muted small">Perfil no disponible temporalmente.</span>
                        @endif
                    </article>
                </div>
            @empty
                <p class="text-muted">No hay macrorregiones disponibles actualmente.</p>
            @endforelse
        </div>
    </section>

    <section aria-labelledby="microrregiones-title">
        <header class="regional-section-header">
            <span class="regional-section-label">Detalle territorial</span>
            <h2 id="microrregiones-title">Microrregiones</h2>
            <p>Filtra por macrorregión para localizar y consultar su perfil específico.</p>
        </header>

        <div class="regional-filter" role="group" aria-label="Filtrar microrregiones por macrorregión">
            <button type="button" class="regional-filter__button" data-macro-filter="all" aria-pressed="true">Todas</button>
            @foreach ($macrorregiones as $macrorregion)
                <button type="button" class="regional-filter__button" data-macro-filter="{{ $macrorregion->id }}" aria-pressed="false">
                    {{ $macrorregion->nombre }}
                </button>
            @endforeach
        </div>

        <div class="row g-3" id="microrregiones-grid">
            @forelse ($macrorregiones as $macrorregion)
                @foreach ($macrorregion->microrregiones as $microrregion)
                    <div class="col-12 col-sm-6 col-lg-4 regional-micro-card" data-macro-id="{{ $macrorregion->id }}">
                        <article class="regional-card">
                            <span class="regional-card__number">{{ $macrorregion->nombre }}</span>
                            <h3>{{ $microrregion->nombre }}</h3>
                            <p>{{ $microrregion->municipios_count }} {{ Str::plural('municipio', $microrregion->municipios_count) }}</p>
                            @if ($microrregion->slug)
                                <a href="{{ $microrregion->perfil_url }}" class="regional-card__link">
                                    Ver perfil <i class="fas fa-arrow-right ms-1" aria-hidden="true"></i>
                                </a>
                            @else
                                <span class="text-muted small">Perfil no disponible temporalmente.</span>
                            @endif
                        </article>
                    </div>
                @endforeach
            @empty
                <p class="text-muted">No hay microrregiones disponibles actualmente.</p>
            @endforelse
        </div>
        <p id="microrregiones-empty" class="text-muted mt-3" hidden>No hay microrregiones para esta macrorregión.</p>
    </section>
</main>
@endsection

@section('jss')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const buttons = document.querySelectorAll('[data-macro-filter]');
        const cards = document.querySelectorAll('.regional-micro-card');
        const emptyState = document.getElementById('microrregiones-empty');

        buttons.forEach(button => {
            button.addEventListener('click', () => {
                const macroId = button.dataset.macroFilter;
                let visibleCount = 0;

                buttons.forEach(item => item.setAttribute('aria-pressed', String(item === button)));
                cards.forEach(card => {
                    const visible = macroId === 'all' || card.dataset.macroId === macroId;
                    card.hidden = !visible;
                    if (visible) visibleCount++;
                });
                emptyState.hidden = visibleCount > 0;
            });
        });
    });
</script>
@endsection
