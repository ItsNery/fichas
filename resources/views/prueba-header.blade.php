<style>
    /* ==========================================================================
   1. VARIABLES Y ESTILOS BASE
   ========================================================================== */
    :root {
        --header-bg: #ffffff;
        --nav-bg: #ffffff;
        --text-color: #333333;
        --accent-color: #9D2449;
        --accent-hover: #B32E56;
        --light-gray: #f4f4f4;
        --border-color: #eaeaea;
        --shadow-nav: 0 4px 10px rgba(0, 0, 0, 0.08);
        --shadow-dropdown: 0 10px 30px rgba(0, 0, 0, 0.1);
        --container-width: 1300px;
        --header-height-mobile: 60px;
    }

    body {
        margin: 0;
        padding: 0;
        font-family: 'Gilroy-Bold', sans-serif;
        color: var(--text-color);
    }

    ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    a {
        text-decoration: none;
        color: inherit;
    }



    /* ==========================================================================
   2. HEADER: NIVEL SUPERIOR (LOGOS)
   ========================================================================== */
    .site-header {
        position: relative;
        background-color: var(--header-bg);
        z-index: 1000;
    }

    .header-logos-container {
        background-color: white;
        padding: 12px 0;
        border-bottom: 1px solid var(--border-color);
    }

    .header-logos-container .container {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .logos-wrapper {
        display: flex;
        justify-content: space-between;
        align-items: center;
        width: 100%;
        gap: 20px;
    }

    .logos-group.left {
        flex: 1;
        display: flex;
        justify-content: flex-start;
    }

    .logos-group.left img {
        height: 100px;
        width: auto;
        max-width: 100%;
        object-fit: contain;
        display: block;
    }

    .logos-group.right {
        flex-shrink: 0;
    }

    .logos-group.right img {
        height: 70px;
        width: auto;
        display: block;
    }

    .hamburger-menu {
        display: none;
        flex-direction: column;
        justify-content: space-around;
        width: 28px;
        height: 24px;
        background: transparent;
        border: none;
        cursor: pointer;
        padding: 0;
        z-index: 1100;
    }

    .hamburger-line {
        width: 100%;
        height: 3px;
        background-color: var(--text-color);
        border-radius: 10px;
        transition: all 0.3s ease;
    }

    .hamburger-menu.open .hamburger-line:nth-child(1) {
        transform: rotate(45deg) translate(5px, 6px);
    }

    .hamburger-menu.open .hamburger-line:nth-child(2) {
        opacity: 0;
    }

    .hamburger-menu.open .hamburger-line:nth-child(3) {
        transform: rotate(-45deg) translate(5px, -6px);
    }


    /* ==========================================================================
   3. NAVEGACIÓN PRINCIPAL (STICKY & DESKTOP)
   ========================================================================== */
    .main-nav {
        background-color: var(--nav-bg);
        position: -webkit-sticky;
        position: sticky;
        top: 0;
        z-index: 999;
        transition: box-shadow 0.3s ease;
        border-bottom: 1px solid transparent;
    }

    .main-nav.is-sticky {
        box-shadow: var(--shadow-nav);
        border-bottom: 1px solid var(--border-color);
    }

    .nav-container {
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .mini-brand {
        display: none;
    }

    .nav-links {
        display: flex;
        gap: 5px;
    }

    .nav-links>li>a {
        display: block;
        padding: 18px 20px;
        font-size: 1rem;
        font-weight: 600;
        color: var(--text-color);
        position: relative;
        transition: color 0.3s ease;
    }

    .nav-links>li>a::after {
        content: '';
        position: absolute;
        bottom: 10px;
        left: 50%;
        width: 0;
        height: 3px;
        background-color: var(--accent-color);
        transition: all 0.3s ease;
        transform: translateX(-50%);
        border-radius: 2px;
    }

    .nav-links>li>a:hover,
    .nav-links>li>a.active {
        color: var(--accent-color);
    }

    .nav-links>li>a:hover::after,
    .nav-links>li>a.active::after {
        width: 70%;
    }

    /* ==========================================================================
   4. MENÚS DESPLEGABLES (DROPDOWNS) - DESKTOP
   ========================================================================== */
    .dropdown {
        position: relative;
    }

    .dropdown-content li {
        position: relative;
    }

    .dropdown-content {
        display: none;
        position: absolute;
        top: 100%;
        left: 0;
        min-width: 220px;
        background-color: white;
        box-shadow: var(--shadow-dropdown);
        border-radius: 0 0 8px 8px;
        padding: 10px 0;
        z-index: 100;
        opacity: 0;
        transform: translateY(10px);
        transition: all 0.3s ease;
    }

    .dropdown:hover .dropdown-content {
        display: block;
        opacity: 1;
        transform: translateY(0);
    }

    .dropdown-content a {
        padding: 12px 20px;
        font-size: 0.9em;
        font-weight: 500;
        display: block;
        color: var(--text-color);
        border-bottom: 1px solid #f9f9f9;
    }

    .dropdown-content a::after {
        display: none;
    }

    .dropdown-content a:hover {
        background-color: var(--light-gray);
        color: var(--accent-color);
    }

    .dropdown-content-nested {
        display: none;
        position: absolute;
        left: 100%;
        top: 0;
        min-width: 200px;
        background-color: white;
        box-shadow: var(--shadow-dropdown);
        border-radius: 0 8px 8px 8px;
        padding: 10px 0;
        z-index: 101;
        opacity: 0;
        transform: translateX(-10px);
        transition: all 0.3s ease;
    }

    .dropdown-nested:hover .dropdown-content-nested {
        display: block;
        opacity: 1;
        transform: translateX(0);
    }

    .arrow {
        font-size: 10px;
        margin-left: 5px;
        vertical-align: middle;
    }

    .arrow-right {
        float: right;
        font-size: 12px;
        color: #999;
        margin-top: 2px;
    }


    /* ==========================================================================
   5. RESPONSIVE / MÓVIL (max-width: 991px)
   ========================================================================== */
    @media (max-width: 991px) {

        .header-logos-container {
            padding: 8px 0;
        }

        .logos-wrapper {
            justify-content: flex-start;
            flex-wrap: wrap;
            gap: 12px;
            width: calc(100% - 40px);
        }

        .logos-group.left img {
            height: 32px;
        }

        .logos-group.right img {
            height: 32px;
        }

        .hamburger-menu {
            display: flex;
        }

        .main-nav {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            background-color: white;
            transform: translateX(-100%);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            overflow-y: auto;
            padding-top: 80px;
            display: block;
        }

        .main-nav.active {
            transform: translateX(0);
            box-shadow: 4px 0 15px rgba(0, 0, 0, 0.1);
        }

        .nav-container {
            display: block;
            padding-bottom: 40px;
        }

        .nav-links {
            flex-direction: column;
            width: 100%;
            gap: 0;
        }

        .nav-links>li>a {
            padding: 15px 25px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 1.05rem;
        }

        .nav-links>li>a::after {
            content: none;
        }

        .dropdown-content,
        .dropdown-content-nested {
            display: none;
            position: static;
            float: none;
            box-shadow: none;
            opacity: 1;
            transform: none;
            visibility: visible;
        }

        /* En móvil táctil, el "hover" funciona al hacer tap */
        /* .dropdown:hover .dropdown-content,
        .dropdown-nested:hover .dropdown-content-nested {
            display: block;
        } */

        .dropdown-content a {
            padding-left: 40px;
            color: #555;
            font-weight: 500;
        }

        .dropdown-content-nested {
            background-color: #f0f0f0;
            border-top: 1px solid #ddd;
        }

        .dropdown-content-nested li a {
            padding-left: 60px;
            font-size: 0.9em;
        }

        .arrow-right {
            transform: rotate(90deg);
        }

        .dropdown:hover>.dropdown-content,
        .dropdown-nested:hover>.dropdown-content-nested {
            display: none !important;
        }

        .dropdown.active>.dropdown-content {
            display: block !important;
        }

        .dropdown-nested.active>.dropdown-content-nested {
            display: block !important;
        }
    }

    /* ==========================================================================
   6. MÓVIL PEQUEÑO (max-width: 480px)
   ========================================================================== */
    @media (max-width: 480px) {

        .logos-wrapper {
            flex-direction: column;
            align-items: flex-start;
            gap: 8px;
        }

        .logos-group.left img {
            height: auto;
            max-height: 40px;
            max-width: 220px;
        }

        .logos-group.right {
            margin-left: 0;
        }
    }
</style>
<header class="site-header">
    <div class="header-logos-container">
        <div class="container">
            <div class="logos-wrapper">

                <div class="logos-group left">
                    <a href="https://www.puebla.gob.mx" target="_blank">
                        <img src="{{ asset('img/logo-gobierno.png') }}" alt="Gobierno del Estado de Puebla">
                    </a>
                </div>

                <div class="logos-group right">
                    <a href="#" class="logo-brand">
                        <img src="{{ asset('img/logo-sei.png') }}" alt="SEI">
                    </a>
                </div>

            </div>

            <button id="hamburger-menu" class="hamburger-menu" aria-label="Menú">
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
            </button>
        </div>
    </div>

    <nav id="main-nav" class="main-nav">
        <div class="container nav-container">

            <a href="#" class="mini-brand">SEI</a>

            <ul class="nav-links">
                <li><a href="#" class="active">Inicio</a></li>

                <li class="dropdown">
                    <a href="#">Información <span class="arrow">▾</span></a>
                    <ul class="dropdown-content">
                        <li><a href="#">Misión y Visión</a></li>

                        <li class="dropdown-nested">
                            <a href="#" class="nested-trigger">
                                Transparencia <span class="arrow-right">▸</span>
                            </a>
                            <ul class="dropdown-content-nested">
                                <li><a href="#">Artículo 70</a></li>
                                <li><a href="#">Estados Financieros</a></li>
                                <li><a href="#">Normatividad</a></li>
                            </ul>
                        </li>
                        <li><a href="#">Directorio</a></li>
                    </ul>
                </li>

                <li><a href="#">Publicaciones</a></li>
                <li><a href="#">Otros</a></li>

                <li class="dropdown">
                    <a href="#">Marco Normativo <span class="arrow">▾</span></a>
                    <ul class="dropdown-content">
                        <li><a href="#">Leyes</a></li>
                        <li><a href="#">Reglamentos</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </nav>
</header>

<script>
    document.addEventListener("DOMContentLoaded", function() {

        // --- 1. MENÚ MÓVIL (HAMBURGUESA) ---
        const menuButton = document.getElementById("hamburger-menu");
        const mainNav = document.getElementById("main-nav");

        if (menuButton && mainNav) {
            menuButton.addEventListener("click", function() {
                mainNav.classList.toggle("active");
                menuButton.classList.toggle("open");
            });
        }

        // --- 2. EFECTO STICKY (Sombra al tocar el techo) ---
        const nav = document.querySelector('.main-nav');

        // Creamos un "vigilante" invisible justo encima del nav
        const sentinel = document.createElement('div');
        sentinel.setAttribute('aria-hidden', true);
        // Lo insertamos antes del nav
        if (nav) {
            nav.parentNode.insertBefore(sentinel, nav);

            const observer = new IntersectionObserver((entries) => {
                // Cuando el vigilante sale de la pantalla (scrolleamos hacia abajo)
                // significa que el nav tocó el borde superior.
                if (!entries[0].isIntersecting) {
                    nav.classList.add('is-sticky');
                } else {
                    nav.classList.remove('is-sticky');
                }
            }, {
                threshold: 0,
                rootMargin: "-1px 0px 0px 0px" // Ajuste fino
            });

            observer.observe(sentinel);
        }

        const dropdownToggles = document.querySelectorAll('.dropdown > a, .dropdown-nested > a');

        dropdownToggles.forEach(toggle => {
            toggle.addEventListener('click', function(e) {
                // Solo actuar si es pantalla móvil/tablet
                if (window.innerWidth <= 991) {
                    // Prevenir navegación
                    e.preventDefault();

                    // DETENER PROPAGACIÓN: Esto es vital para anidados.
                    // Evita que el clic en el hijo cierre al padre.
                    e.stopPropagation();

                    // Identificar el LI padre
                    const parentLi = this.parentElement;

                    // Toggle de la clase active
                    // Si ya estaba abierto, lo cierra. Si estaba cerrado, lo abre.
                    const wasActive = parentLi.classList.contains('active');

                    // Opcional: Cerrar hermanos (para que no queden todos abiertos)
                    // Buscamos el padre del LI actual (el UL) y cerramos sus otros hijos
                    const siblings = parentLi.parentElement.children;
                    for (let sibling of siblings) {
                        if (sibling !== parentLi) {
                            sibling.classList.remove('active');
                        }
                    }

                    if (!wasActive) {
                        parentLi.classList.add('active');
                    } else {
                        parentLi.classList.remove('active');
                    }
                }
            });
        });

    });
</script>
